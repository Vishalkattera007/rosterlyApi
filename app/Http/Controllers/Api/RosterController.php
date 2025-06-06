<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RosterModel;
use App\Models\RosterWeekModel;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

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

    public function postRoster(Request $request)
    {
        try {
            $authenticate   = $request->user('api');
            $locationId     = $request->input('locationId');
            $rWeekStartDate = $request->input('rWeekStartDate');
            $rWeekEndDate   = $request->input('rWeekEndDate');
            $rosters        = $request->input('rosters');

            if (! is_array($rosters) || empty($rosters)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'No roster data provided.',
                ], 400);
            }

            // Check or create roster week
            $rosterWeek = RosterWeekModel::where('week_start_date', $rWeekStartDate)
                ->where('week_end_date', $rWeekEndDate)
                ->where('location_id', $locationId)
                ->where('created_by', $authenticate->id)
                ->first();

            if (! $rosterWeek) {
                $rosterWeek = RosterWeekModel::create([
                    'week_start_date' => $rWeekStartDate,
                    'week_end_date'   => $rWeekEndDate,
                    'created_by'      => $authenticate->id,
                    'location_id'     => $locationId,
                ]);
            }

            $createdWeekId         = $rosterWeek->id;
            $createdWeekLocationId = $rosterWeek->location_id;

            $savedRosters = [];

            foreach ($rosters as $roster) {
                $rawShiftId = $roster['shiftId'] ?? null;
                $shiftId    = (is_numeric($rawShiftId) && ctype_digit((string) $rawShiftId)) ? (int) $rawShiftId : null;

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
                            'breakTime'    => (float) $roster['breakTime'],
                            'totalHrs'     => $roster['totalHrs'],
                            'hrsRate'      => $roster['hrsRate'],
                            'percentRate'  => $roster['percentRate'],
                            'totalPay'     => $roster['totalPay'],
                            'status'       => $roster['status'] ?? 'active',
                            'description'  => $roster['description'] ?? null,
                            'updated_by'   => $authenticate->id,
                        ]);
                        $updatedRosters[] = $existingShift;
                        if (count($updatedRosters) > 0) {
                            $rosterWeek->update([
                                'is_published' => 1,
                            ]);
                        }
                        continue; // ✅ Skip creating new one
                    }
                }

                // Create new
                $saved = RosterModel::create([
                    'user_id'      => $roster['user_id'],
                    'rosterWeekId' => $createdWeekId,
                    'location_id'  => $roster['location_id'] ?? $createdWeekLocationId,
                    'date'         => $roster['date'],
                    'startTime'    => $roster['startTime'],
                    'endTime'      => $roster['endTime'],
                    'breakTime'    => (float) $roster['breakTime'],
                    'totalHrs'     => $roster['totalHrs'],
                    'hrsRate'      => $roster['hrsRate'],
                    'percentRate'  => $roster['percentRate'],
                    'totalPay'     => $roster['totalPay'],
                    'status'       => $roster['status'] ?? 'active',
                    'description'  => $roster['description'] ?? null,
                    'created_by'   => $authenticate->id,
                ]);

                $savedRosters[] = $saved;
            }
            if (count($savedRosters) > 0) {
                $rosterWeek->update([
                    'is_published' => 1,
                ]);
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
            $authenticate = $request->user('api');
            $rosterWeekId = $request->rosterWeekId;
            $employeeId   = $request->empId;
            // THIS IS SINGLE ROSTER SHIFT DELERE
            $checkforDelete = RosterModel::where('id', $shiftId)
                ->where('location_id', $locationId)
                ->where('created_by', $authenticate->id)
                ->where('rosterWeekId', $rosterWeekId)
                ->where('user_id', $employeeId)->first();

            if ($checkforDelete) {
                $checkforDelete->delete();

                return response()->json([
                    'status'  => true,
                    'data'    => $checkforDelete,
                    'message' => "Roster Shift Deleted Successfully",
                ], 200);
            }

            return response()->json([
                'status'  => false,
                'message' => 'No matching shift found.',
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Error occurred while fetching shift data: ' . $e->getMessage(),
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
            $authenticate = $request->user('api');
            $loginId      = $authenticate->id;
            $currentDate  = Carbon::now()->toDateString();

            $fetchLocations = RosterModel::with('location')
                ->where('user_id', $loginId)
                ->where('date', $currentDate)
                ->get();

            if ($fetchLocations->isEmpty()) {
                return response()->json([
                    "status"    => 200,
                    "user_id"   => $loginId,
                    "shiftdata" => [],
                ], 200);
            }

            $grouped = [];

            foreach ($fetchLocations as $shift) {
                $locationId = $shift->location->id;

                // If the location is not already added
                if (! isset($grouped[$locationId])) {
                    $grouped[$locationId]                      = $shift->location->toArray();
                    $grouped[$locationId]['locationwiseshift'] = [];
                }

                $shiftData = $shift->toArray();
                unset($shiftData['user_id'], $shiftData['location_id'], $shiftData['location']);
                $grouped[$locationId]['locationwiseshift'][] = $shiftData;
            }

            return response()->json([
                "status"    => 200,
                "user_id"   => $loginId,
                "shiftdata" => [
                    [
                        "locationwise" => array_values($grouped),
                    ],
                ],
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
            $authenticate = $request->user('api');
            $loginId      = $authenticate->id;
            $rosterWeekId = $request->rosterWeekId;
            $locationId = $request->locationId;
            if ($rosterWeekId) {
                $fetchDetails = RosterModel::where('rosterWeekId', $rosterWeekId)
                    ->where('location_id', $locationId)
                    ->where('user_id', $loginId)
                    ->get();

                    return response()->json([
                        'status' => true,
                        'data'   => $fetchDetails,
                    ], 200);

            }

            // $fetchLocations = RosterModel::with('location', 'unavail')
            //     ->where('user_id', $loginId)
            //     ->get();

            // if ($fetchLocations->isEmpty()) {
            //     return response()->json([
            //         "status"    => 200,
            //         "user_id"   => $loginId,
            //         "shiftdata" => [],
            //     ], 200);
            // }

            // $grouped = [];
            // foreach ($fetchLocations as $shift) {
            //     $locationId = $shift->location->id;

            //     // If the location is not already added
            //     if (! isset($grouped[$locationId])) {
            //         $grouped[$locationId]                      = $shift->location->toArray();
            //         $grouped[$locationId]['locationwiseshift'] = [];
            //     }

            //     $shiftData = $shift->toArray();
            //     unset($shiftData['user_id'], $shiftData['location_id'], $shiftData['location']);
            //     $grouped[$locationId]['locationwiseshift'][] = $shiftData;
            // }

            // return response()->json([
            //     "status"    => 200,
            //     "user_id"   => $loginId,
            //     "shiftdata" => [
            //         [
            //             "locationwise" => array_values($grouped),
            //         ],
            //     ],
            // ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Error occurred:' . $e->getMessage(),
            ], 500);
        }

    }

}
