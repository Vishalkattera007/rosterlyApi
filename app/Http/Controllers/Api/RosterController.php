<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RosterModel;
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
    public function store(Request $request, $id = null)
    {
        try {
            $rosters = $request->input('rosters'); // Expecting an array of roster objects

            if (! is_array($rosters) || empty($rosters)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'No roster data provided.',
                ], 400);
            }

            $savedRosters   = [];
            $skippedRosters = [];

            foreach ($rosters as $roster) {
                // Check for duplicate
                $exists = RosterModel::where('user_id', $roster['user_id'])
                    ->where('location_id', $roster['location_id'])
                    ->where('date', $roster['date'])
                    ->where('startTime', $roster['startTime'])
                    ->where('endTime', $roster['endTime'])
                    ->exists();

                if ($exists) {
                    $skippedRosters[] = $roster; // Optional: log skipped entries
                    continue;
                }

                $saved = RosterModel::create([
                    'user_id'     => $roster['user_id'],
                    'location_id' => $roster['location_id'],
                    'date'        => $roster['date'],
                    'startTime'   => $roster['startTime'],
                    'endTime'     => $roster['endTime'],
                    'breakTime'   => $roster['breakTime'],
                    'hrsRate'     => $roster['hrsRate'],
                    'percentRate' => $roster['percentRate'],
                    'totalPay'    => $roster['totalPay'],
                    'status'      => $roster['status'] ?? 'active',
                    'description' => $roster['description'] ?? null,
                    'created_by'  => $id,
                ]);

                $savedRosters[] = $saved;
            }

            return response()->json([
                'status'             => true,
                'message'            => 'Rosters processed successfully.',
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
                ->where('user_id', $loginId)
                ->with(['user', 'location'])
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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
