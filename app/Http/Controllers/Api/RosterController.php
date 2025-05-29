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
            $authUser       = $request->user('api'); // Get the authenticated user
            $rWeekStartDate = $request->input('rWeekStartDate');
            $rWeekEndDate   = $request->input('rWeekEndDate');
            $locationId     = $request->input('location_id');
            $rosterWeekId   = $request->input('rosterWeekId');

            // Check if the rosterWeekId exists
            $findRosterWeek = RosterWeekModel::where('id', $rosterWeekId)->first();

            if ($findRosterWeek) {
                $findRosterWeek->update([
                    'is_published' => 1,
                ]);

            } else {
                // Validate required fields
                if (! $rWeekStartDate || ! $rWeekEndDate) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Week start and end dates are required.',
                    ], 400);
                }

                // Prevent duplicate roster week creation
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

                // Create new roster week
                $insertedRosterWeek = RosterWeekModel::create([
                    'week_start_date' => $rWeekStartDate,
                    'week_end_date'   => $rWeekEndDate,
                    'created_by'      => $authUser->id,
                    'location_id'     => $locationId,
                    'is_published'    => 1,
                ]);

                if (! $insertedRosterWeek) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Failed to create roster week.',
                    ], 500);
                }

                $rosterWeekId = $insertedRosterWeek->id;
            }

            // Handle roster shifts
            $rosters = $request->input('rosters');

            if (! is_array($rosters) || empty($rosters)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'No roster data provided.',
                ], 400);
            }

            $savedRosters   = [];
            $updatedRosters = [];

            foreach ($rosters as $roster) {
                $existingRoster = RosterModel::where('user_id', $roster['user_id'])
                    ->where('rosterWeekId', $rosterWeekId)
                    ->where('date', $roster['date'])
                    ->where('location_id', $roster['location_id'])
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
                } else {
                    $saved = RosterModel::create([
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
                    $savedRosters[] = $saved;
                }
            }

            return response()->json([
                'status'         => true,
                'message'        => 'Roster week processed successfully.',
                'roster_week_id' => $rosterWeekId,
                'created'        => $savedRosters,
                'updated'        => $updatedRosters,
            ], 200);

        } catch (\Exception $e) {
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
