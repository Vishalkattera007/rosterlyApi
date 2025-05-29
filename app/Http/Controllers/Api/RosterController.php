<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RosterModel;
use App\Models\RosterWeekModel;
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

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $authUser = $request->user('api');

            $rWeekStartDate = $request->input('rWeekStartDate');
            $rWeekEndDate   = $request->input('rWeekEndDate');
            $locationId     = $request->input('location_id');
            $rosterWeekId   = $request->input('rosterWeekId');

            $findRosterWeekId = RosterWeekModel::where('id', $rosterWeekId)->first();

            $updatedRosters = [];
            $createdRosters = [];
            $skippedRosters = [];

            $rosters = $request->input('rosters');

            if (! is_array($rosters) || empty($rosters)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'No roster data provided.',
                ], 400);
            }

            if ($findRosterWeekId) {
                foreach ($rosters as $roster) {

                    $shiftId = $roster['shiftId'] ?? null;
                    $isRealShiftId = is_numeric($shiftId);

                    if ($$isRealShiftId) {
                        $existingShift = RosterModel::where('id', $shiftId)
                            ->where('user_id', $roster['user_id'])
                            ->where('location_id', $roster['location_id'])
                            ->where('rosterWeekId', $roster['weekId'])
                            ->first();

                        if ($existingShift) {
                            $existingShift->update([
                                'user_id'      => $roster['user_id'],
                                'rosterWeekId' => $roster['weekId'],
                                'location_id'  => $roster['location_id'],
                                'date'         => $roster['date'],
                                'startTime'    => $roster['startTime'],
                                'endTime'      => $roster['endTime'],
                                'breakTime'    => $roster['breakTime'],
                                'hrsRate'      => $roster['hrsRate'],
                                'percentRate'  => $roster['percentRate'],
                                'totalPay'     => $roster['totalPay'],
                                'status'       => $roster['status'] ?? 'active',
                                'description'  => $roster['description'] ?? null,
                                'updated_by'   => $authUser->id,
                            ]);

                            $updatedRosters[] = $existingShift;
                            continue;
                        }
                    }

                    // Check if exact shift already exists
                    $duplicateExists = RosterModel::where('user_id', $roster['user_id'])
                        ->where('rosterWeekId', $roster['weekId'])
                        ->where('date', $roster['date'])
                        ->where('startTime', $roster['startTime'])
                        ->where('endTime', $roster['endTime'])
                        ->exists();

                    if ($duplicateExists) {
                        $skippedRosters[] = $roster;
                        continue;
                    }

                    $created = RosterModel::create([
                        'user_id'      => $roster['user_id'],
                        'rosterWeekId' => $roster['weekId'],
                        'location_id'  => $roster['location_id'],
                        'date'         => $roster['date'],
                        'startTime'    => $roster['startTime'],
                        'endTime'      => $roster['endTime'],
                        'breakTime'    => $roster['breakTime'],
                        'hrsRate'      => $roster['hrsRate'],
                        'percentRate'  => $roster['percentRate'],
                        'totalPay'     => $roster['totalPay'],
                        'status'       => $roster['status'] ?? 'active',
                        'description'  => $roster['description'] ?? null,
                        'created_by'   => $authUser->id,
                    ]);

                    $createdRosters[] = $created;
                }

                $findRosterWeekId->update(['is_published' => 1]);

                return response()->json([
                    'status'  => true,
                    'message' => 'Roster week processed successfully.',
                    'updated' => $updatedRosters,
                    'created' => $createdRosters,
                    'skipped' => $skippedRosters,
                ], 200);
            } else {
                // Roster Week does not exist, create new one
                if (! $rWeekStartDate || ! $rWeekEndDate) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Week start and end dates are required.',
                    ], 400);
                }

                $existingRosterWeek = RosterWeekModel::where('week_start_date', $rWeekStartDate)
                    ->where('week_end_date', $rWeekEndDate)
                    ->where('location_id', $locationId)
                    ->first();

                if ($existingRosterWeek) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Roster week already exists for the given dates and location.',
                    ], 409);
                }

                $newRosterWeek = RosterWeekModel::create([
                    'week_start_date' => $rWeekStartDate,
                    'week_end_date'   => $rWeekEndDate,
                    'created_by'      => $authUser->id,
                    'location_id'     => $locationId,
                    'is_published'    => 1,
                ]);

                $rosterWeekId = $newRosterWeek->id;

                foreach ($rosters as $roster) {
                    $duplicateExists = RosterModel::where('user_id', $roster['user_id'])
                        ->where('rosterWeekId', $rosterWeekId)
                        ->where('date', $roster['date'])
                        ->where('startTime', $roster['startTime'])
                        ->where('endTime', $roster['endTime'])
                        ->exists();

                    if ($duplicateExists) {
                        $skippedRosters[] = $roster;
                        continue;
                    }

                    $created = RosterModel::create([
                        'user_id'      => $roster['user_id'],
                        'rosterWeekId' => $rosterWeekId,
                        'location_id'  => $roster['location_id'],
                        'date'         => $roster['date'],
                        'startTime'    => $roster['startTime'],
                        'endTime'      => $roster['endTime'],
                        'breakTime'    => $roster['breakTime'],
                        'hrsRate'      => $roster['hrsRate'],
                        'percentRate'  => $roster['percentRate'],
                        'totalPay'     => $roster['totalPay'],
                        'status'       => $roster['status'] ?? 'active',
                        'description'  => $roster['description'] ?? null,
                        'created_by'   => $authUser->id,
                    ]);

                    $createdRosters[] = $created;
                }

                return response()->json([
                    'status'         => true,
                    'message'        => 'New roster week and rosters created.',
                    'roster_week_id' => $rosterWeekId,
                    'created'        => $createdRosters,
                    'skipped'        => $skippedRosters,
                ], 201);
            }
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

    public function pubUnpub($id = null)
    {
        if ($id !== null) {
            $rosterWeek = RosterWeekModel::find($id);

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
                    'id'           => $rosterWeek->id,
                    'is_published' => $rosterWeek->is_published,
                ],
            ], 200);
        }

        return response()->json([
            'status'  => false,
            'message' => 'ID is required.',
        ], 400);

    }
}
