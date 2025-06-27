<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\RosterAssigned;
use App\Models\LocationUsers;
use App\Models\RosterModel;
use App\Models\RosterWeekModel;
use App\Models\UnavailabilityModel;
use App\Models\UserProfileModel;
use Carbon\CarbonPeriod;
use Exception;
use function PHPUnit\Framework\isEmpty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\RosterShiftDeleted; // Create this mailable

class RosterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // check the id is null or not
        $roster = RosterModel::all();
        if ($roster->isEmpty()) {
            return response()->json([
                'status'  => false,
                'message' => 'No rosters found.',
            ], 404);
        } else {
            return response()->json([
                'status'  => true,
                'message' => 'Roster fetched successfully',
                'data'    => $roster,
            ]);
        }

    }

    public function getWeekDatesId(Request $request)
    {
        $authenticate = $request->user('api');
        $loginId      = $authenticate->id;

        $getLatitude    = (float) $request->latitude;
        $getLongitude   = (float) $request->longitude;
        $rWeekStartDate = $request->input('rWeekStartDate');
        $rWeekEndDate   = $request->input('rWeekEndDate');

        // Fetch locations assigned to the user
        $fetchLocations = LocationUsers::with('location')
            ->where('user_id', $loginId)
            ->get()
            ->pluck('location');

        // Filter locations within 100 meters radius
        $matchedLocations = $fetchLocations->filter(function ($location) use ($getLatitude, $getLongitude) {
            $distance = $this->getDistanceInMeters(
                $getLatitude,
                $getLongitude,
                (float) $location->latitude,
                (float) $location->longitude
            );
            return $distance <= 200; // within 100 meters
        })->values();

        if ($matchedLocations->isEmpty()) {
            return response()->json([
                'status'  => false,
                'message' => "You are not at the correct location",
            ], 404);
        }

        $matchedLocationIds = $matchedLocations->pluck('id');

        $fetchCreatedBy = UserProfileModel::find($loginId);
        $created_by_id  = $fetchCreatedBy->created_by;

        $fethRosterWeekId = RosterWeekModel::where('week_start_date', $rWeekStartDate)
            ->where('week_end_date', $rWeekEndDate)
            ->where('created_by', $created_by_id)
            ->whereIn('location_id', $matchedLocationIds)
            ->get();

        return response()->json([
            'status'       => true,
            'rosterWeekId' => $fethRosterWeekId,
        ], 200);
    }

    private function getDistanceInMeters($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // meters

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo   = deg2rad($lat2);
        $lonTo   = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
        cos($latFrom) * cos($latTo) *
        sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

   public function postRoster(Request $request)
{
    try {
        $authenticate   = $request->user('api');
        $locationId     = $request->input('locationId');
        $rWeekStartDate = $request->input('rWeekStartDate');
        $rWeekEndDate   = $request->input('rWeekEndDate');
        $rosters        = $request->input('rosters');

        if (!is_array($rosters) || empty($rosters)) {
            return response()->json([
                'status'  => false,
                'message' => 'No roster data provided.',
            ], 400);
        }

        $rosterWeek = RosterWeekModel::where('week_start_date', $rWeekStartDate)
            ->where('week_end_date', $rWeekEndDate)
            ->where('location_id', $locationId)
            ->where('created_by', $authenticate->id)
            ->first();

        if (!$rosterWeek) {
            $rosterWeek = RosterWeekModel::create([
                'week_start_date' => $rWeekStartDate,
                'week_end_date'   => $rWeekEndDate,
                'created_by'      => $authenticate->id,
                'location_id'     => $locationId,
            ]);
        }

        $createdWeekId         = $rosterWeek->id;
        $createdWeekLocationId = $rosterWeek->location_id;

        $savedRosters   = [];
        $updatedRosters = [];
        $userIdsToEmail = [];

        foreach ($rosters as $roster) {
            $rawShiftId = $roster['shiftId'] ?? null;
            $shiftId    = (is_numeric($rawShiftId) && ctype_digit((string)$rawShiftId)) ? (int)$rawShiftId : null;

            if ($shiftId) {
                $existingShift = RosterModel::where('id', $shiftId)
                    ->where('user_id', $roster['user_id'])
                    ->where('location_id', $roster['location_id'])
                    ->where('rosterWeekId', $createdWeekId)
                    ->first();

                if ($existingShift) {
                    $existingShift->update([
                        'user_id'      => $roster['user_id'],
                        'rosterWeekId' => $createdWeekId,
                        'location_id'  => $roster['location_id'],
                        'date'         => $roster['date'],
                        'startTime'    => $roster['startTime'],
                        'endTime'      => $roster['endTime'],
                        'breakTime'    => (float)$roster['breakTime'],
                        'totalHrs'     => $roster['totalHrs'],
                        'hrsRate'      => $roster['hrsRate'],
                        'percentRate'  => $roster['percentRate'],
                        'totalPay'     => $roster['totalPay'],
                        'status'       => $roster['status'] ?? 'active',
                        'description'  => $roster['description'] ?? null,
                        'updated_by'   => $authenticate->id,
                    ]);

                    $updatedRosters[] = $existingShift;
                    $userIdsToEmail[] = $roster['user_id'];
                    continue;
                }
            }

            $saved = RosterModel::create([
                'user_id'      => $roster['user_id'],
                'rosterWeekId' => $createdWeekId,
                'location_id'  => $roster['location_id'] ?? $createdWeekLocationId,
                'date'         => $roster['date'],
                'startTime'    => $roster['startTime'],
                'endTime'      => $roster['endTime'],
                'breakTime'    => (float)$roster['breakTime'],
                'totalHrs'     => $roster['totalHrs'],
                'hrsRate'      => $roster['hrsRate'],
                'percentRate'  => $roster['percentRate'],
                'totalPay'     => $roster['totalPay'],
                'status'       => $roster['status'] ?? 'active',
                'description'  => $roster['description'] ?? null,
                'created_by'   => $authenticate->id,
            ]);

            $savedRosters[] = $saved;
            $userIdsToEmail[] = $roster['user_id'];
        }

        // ✅ Send emails only to users involved in this request
        $uniqueUserIds = collect($userIdsToEmail)->unique();

        foreach ($uniqueUserIds as $userId) {
            $user = UserProfileModel::find($userId);
            if (!$user || !$user->email) {
                continue;
            }

            $weeklyShifts = [];
            $dates = CarbonPeriod::create($rWeekStartDate, $rWeekEndDate);

            foreach ($dates as $date) {
                $shift = RosterModel::where('user_id', $user->id)
                    ->where('rosterWeekId', $createdWeekId)
                    ->whereDate('date', $date->format('Y-m-d'))
                    ->first();

                $weeklyShifts[] = [
                    'date'      => $date->format('Y-m-d'),
                    'startTime' => $shift ? $shift->startTime : null,
                    'endTime'   => $shift ? $shift->endTime : null,
                    'breakTime' => $shift ? $shift->breakTime : 0,
                    'totalHrs'  => $shift ? $shift->totalHrs : 0,
                ];
            }

            Mail::to($user->email)->send(
                new RosterAssigned($user, $rWeekStartDate, $rWeekEndDate, $weeklyShifts)
            );
        }

        if (count($savedRosters) > 0 || count($updatedRosters) > 0) {
            $rosterWeek->update(['is_published' => 1]);
        }

        return response()->json([
            'status'  => true,
            'message' => "Roster data saved successfully",
            'data'    => $savedRosters,
        ], 201);

    } catch (Exception $e) {
        return response()->json([
            'status'  => false,
            'message' => 'Error occurred while processing rosters: ' . $e->getMessage(),
        ], 500);
    }
}


    /**
     * Display the specified resource.
     */
    public function getRosterByLoginId($location_id = null, $loginId = null)
    {
        // Check if both parameters are provided
        if ($location_id && $loginId) {
            // Fetch roster by login ID and location ID
            $roster = RosterModel::where('location_id', $location_id)
                ->where('created_by', $loginId)
                ->get();

            return response()->json([
                'status'  => true,
                'message' => 'Roster fetched successfully',
                'data'    => $roster,
            ]);
        } else {
            // If no parameters, return all rosters
            return $this->index();
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function getRosterWeekData(Request $request)
    {
        try {
            $authUser = $request->user('api'); // Get the authenticated user

            $rWeekStartDate = $request->input('rWeekStartDate');
            $rWeekEndDate   = $request->input('rWeekEndDate');
            $locationId     = $request->input('location_id');

            if (! $rWeekStartDate || ! $rWeekEndDate) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Week start and end dates are required.',
                ], 400);
            }
            $findWeeks = RosterWeekModel::where('week_start_date', $rWeekStartDate)
                ->where('week_end_date', $rWeekEndDate)
                ->where('created_by', $authUser->id)
                ->where('location_id', $locationId)
                ->first();
            if (! $findWeeks) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Create a roster week first for the given dates.',
                ], 404);
            }
            return response()->json([
                'status'      => true,
                'weekId'      => $findWeeks->id,
                'isPublished' => $findWeeks->is_published,
                'userId'      => $findWeeks->created_by,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Error occurred while fetching roster week data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */

    public function pubUnpub(Request $request, $weekId = null, $locationId = null)
    {
        if ($weekId !== null) {
            $authenticate = $request->user('api');
            $rosterWeek   = RosterWeekModel::where('id', $weekId)
                ->where('created_by', $authenticate->id)
                ->where('location_id', $locationId)
                ->first();

            if (! $rosterWeek) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Roster week not found.',
                ], 404);
            }

            // Toggle the is_published value
            $rosterWeek->is_published = $rosterWeek->is_published ? 0 : 1;
            $rosterWeek->save();

            return response()->json([
                'status'  => true,
                'message' => 'Roster week publication status updated.',
                'data'    => [
                    'id'           => $weekId,
                    'is_published' => $rosterWeek->is_published,
                    'location_id'  => $locationId,
                ],
            ], 200);
        }

        return response()->json([
            'status'  => false,
            'message' => 'ID is required.',
        ], 400);

    }


public function delete(Request $request)
{
    try {
        $shiftId      = $request->shiftId;
        $locationId   = $request->locationId;
        $rosterWeekId = $request->rosterWeekId;
        $employeeId   = $request->empId;

        // Find the shift
        $checkforDelete = RosterModel::where('id', $shiftId)
            ->where('location_id', $locationId)
            ->where('rosterWeekId', $rosterWeekId)
            ->where('user_id', $employeeId)
            ->first();

        if (!$checkforDelete) {
            return response()->json([
                'status' => false,
                'message' => 'No matching shift found.',
            ], 404);
        }

        // Save deleted date before deleting
        $deletedDate = $checkforDelete->date;

        // Delete shift
        $checkforDelete->delete();

        // Fetch user info
        $user = UserProfileModel::find($employeeId);

        // Fetch week start & end dates
        $week = RosterWeekModel::find($rosterWeekId);
        $weekStartDate = \Carbon\Carbon::parse($week?->week_start_date);
        $weekEndDate   = \Carbon\Carbon::parse($week?->week_end_date);

        // Fetch existing shifts
        $existingShifts = RosterModel::where('rosterWeekId', $rosterWeekId)
            ->where('user_id', $employeeId)
            ->where('location_id', $locationId)
            ->get()
            ->keyBy(fn($shift) => \Carbon\Carbon::parse($shift->date)->toDateString());

        // Fill full week shifts
        $weeklyShifts = [];
        for ($date = $weekStartDate->copy(); $date->lte($weekEndDate); $date->addDay()) {
            $key = $date->toDateString();
            $shift = $existingShifts[$key] ?? null;

            $weeklyShifts[] = [
                'date'      => $key,
                'startTime' => $shift->startTime ?? null,
                'endTime'   => $shift->endTime ?? null,
                'breakTime' => $shift->breakTime ?? 0,
                'totalHrs'  => $shift->totalHrs ?? 0,
            ];
        }

        // Send email if user has email
        if ($user && $user->email) {
            Mail::to($user->email)->send(new RosterShiftDeleted(
                $user,
                $weeklyShifts,
                $weekStartDate,
                $weekEndDate,
                $deletedDate
            ));
        }

        return response()->json([
            'status' => true,
            'message' => 'Roster Shift Deleted Successfully',
            'deletedDate' => $deletedDate,
            'updatedRoster' => $weeklyShifts
        ], 200);

    } catch (Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Error occurred while deleting shift: ' . $e->getMessage(),
        ], 500);
    }
}

    public function allweekdelete(Request $request)
    {
        try {
            $locationId   = $request->locationId;
            $authenticate = $request->user('api');
            $rosterWeekId = $request->rosterWeekId;
            if ($rosterWeekId) {
                $deletingRosterWeek = RosterWeekModel::where('id', $rosterWeekId)
                    ->where('location_id', $locationId)
                    ->where('created_by', $authenticate->id)
                    ->first();

                if ($deletingRosterWeek) {
                    $deletingRosterWeek->delete();
                    return response()->json([
                        'status' => true,
                        'data'   => $deletingRosterWeek,
                    ], 200);
                }

            }

            return response()->json([
                'status'  => false,
                'message' => 'Roster week not found',
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Error occurred while deleting data: ' . $e->getMessage(),
            ], 500);
        }

    }

    public function dashboardData(Request $request)
    {
        try {
            $authenticate      = $request->user('api');
            $loginId           = $authenticate->id;
            $rosterWeekId      = $request->rosterWeekId;
            $matchedlocationId = $request->locationId;

            $fetchRoster  = RosterModel::with('location')->where('location_id', $matchedlocationId)->where('user_id', $loginId)->where('rosterWeekId', $rosterWeekId)->get();
            $fetchUnavail = UnavailabilityModel::where('userId', $loginId)->get();

            $filteredRoster = $fetchRoster->map(function ($item) {
                return [
                    'rosterWeekId'  => $item->rosterWeekId,
                    'rosterId'      => $item->id,
                    'location_Id'   => $item->location->id,
                    'location_name' => $item->location->location_name ?? null,
                    'date'          => $item->date,
                    'startTime'     => $item->startTime,
                    'breakTime'     => $item->breakTime,
                    'endTime'       => $item->endTime,
                    'totalHrs'      => $item->totalHrs,
                    'description'   => $item->description,
                    'status'        => $item->status,
                ];
            });
            return response()->json([
                "status"      => 200,
                'RosterData'  => $filteredRoster,
                'UnavailData' => $fetchUnavail,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Error occurred:' . $e->getMessage(),
            ], 500);
        }

    }

    public function dashboardCards(Request $request)
    {
        try {
            $authenticate   = $request->user('api');
            $loginId        = $authenticate->id;
            $rWeekStartDate = $request->input('rWeekStartDate');
            $rWeekEndDate   = $request->input('rWeekEndDate');

            $fetchCreatedBy = UserProfileModel::find($loginId);
            $created_by_id  = $fetchCreatedBy->created_by;

            $fethRosterWeekId = RosterWeekModel::where('week_start_date', $rWeekStartDate)
                ->where('week_end_date', $rWeekEndDate)
                ->where('created_by', $created_by_id)
                ->pluck('id');

            $rosters = RosterModel::with('location') // or any relationships you want
                ->whereIn('rosterWeekId', $fethRosterWeekId)
                ->where('user_id', $loginId)
                ->get();

            $filteredRoster = $rosters->map(function ($item) {
                return [
                    'rosterWeekId'  => $item->rosterWeekId,
                    'location_name' => $item->location->location_name ?? null,
                    'date'          => $item->date,
                    'startTime'     => $item->startTime,
                    'breakTime'     => $item->breakTime,
                    'endTime'       => $item->endTime,
                    'totalHrs'      => $item->totalHrs,
                    'description'   => $item->description,
                    'status'        => $item->status,
                ];
            });

            return response()->json([
                'status'  => true,
                'RosterData' => $filteredRoster,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Error occurred:' . $e->getMessage(),
            ], 500);
        }

    }

}
