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
            $rosters        = $request->input('rosters');

            // Validate required fields
            if (! $rWeekStartDate || ! $rWeekEndDate) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Week start and end dates are required.',
                ], 400);
            }

            if (! is_array($rosters) || empty($rosters)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'No roster data provided.',
                ], 400);
            }

            $existingRosterWeek = RosterWeekModel::where('week_start_date', $rWeekStartDate)
                ->where('week_end_date', $rWeekEndDate)
                ->where('location_id', $locationId)
                ->first();

            // If roster week already exists
            if ($existingRosterWeek) {
                $existingRosterWeek->update(['is_published' => 1]);

                $updatedRosters = [];

                foreach ($rosters as $roster) {
                    $existingRoster = RosterModel::where('user_id', $roster['user_id'])
                        ->where('rosterWeekId', $rosterWeekId)
                        ->where('date', $roster['date'])
                        ->where('startTime', $roster['startTime'])
                        ->where('endTime', $roster['endTime'])
                        ->first();

                    if ($existingRoster) {
                        $existingRoster->update([
                            'startTime'   => $roster['startTime'],
                            'endTime'     => $roster['endTime'],
                            'breakTime'   => $roster['breakTime'],
                            'hrsRate'     => $roster['hrsRate'],
                            'percentRate' => $roster['percentRate'],
                            'totalPay'    => $roster['totalPay'],
                            'status'      => $roster['status'] ?? 'active',
                            'description' => $roster['description'] ?? null,
                            'created_by'  => $authUser->id,
                        ]);
                        $updatedRosters[] = $existingRoster;
                    }
                }

                return response()->json([
                    'status'  => true,
                    'message' => 'Roster week and rosters updated successfully.',
                    'updated' => $updatedRosters,
                ]);
            }

            // Create new roster week
            $newRosterWeek = RosterWeekModel::create([
                'week_start_date' => $rWeekStartDate,
                'week_end_date'   => $rWeekEndDate,
                'created_by'      => $authUser->id,
                'location_id'     => $locationId,
                'is_published'    => 1,
            ]);

            if (! $newRosterWeek) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Failed to create roster week.',
                ], 500);
            }

            $savedRosters   = [];
            $skippedRosters = [];

            foreach ($rosters as $roster) {
                $isDuplicate = RosterModel::where('user_id', $roster['user_id'])
                    ->where('rosterWeekId', $newRosterWeek->id)
                    ->where('date', $roster['date'])
                    ->where('startTime', $roster['startTime'])
                    ->where('endTime', $roster['endTime'])
                    ->exists();

                if ($isDuplicate) {
                    $skippedRosters[] = $roster;
                    continue;
                }

                $savedRosters[] = RosterModel::create([
                    'user_id'      => $roster['user_id'],
                    'rosterWeekId' => $newRosterWeek->id,
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
            }

            return response()->json([
                'status'             => true,
                'message'            => 'Roster week and rosters created successfully.',
                'roster_week_id'     => $newRosterWeek->id,
                'saved'              => $savedRosters,
                'skipped_duplicates' => $skippedRosters,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Error occurred while creating rosters: ' . $e->getMessage(),
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
