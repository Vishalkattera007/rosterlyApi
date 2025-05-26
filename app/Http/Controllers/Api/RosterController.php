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
    public function index($id = null)
    {
        // check the id is null or not
        if ($id == null) {
            // get all the data
            $roster = RosterModel::with(['user', 'location'])->get();
            return response()->json([
                'status'  => true,
                'message' => 'Roster Data fetched successfully',
                'data'    => $roster,
            ]);
        } else {
            // get the data by id
            $roster = RosterModel::all();
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

            $savedRosters = [];

            foreach ($rosters as $roster) {
                $saved = RosterModel::create([
                    'user_id'     => $roster['user_id'],
                    'location_id' => $roster['location_id'],
                    'date'        => $roster['date'],
                    'startTime'   => $roster['startTime'],
                    'endTime'     => $roster['endTime'],
                    'breakTime'   => $roster['breakTime'],
                    // 'totalHrs'    => $roster['startTime']+$roster['endTime'] - $roster['breakTime'], // Assuming totalHrs is calculated this way
                    // 'totalHrs'    => $roster['totalHrs'],
                    'hrsRate'   => $roster['hrsRate'],
                    'percentRate' => $roster['percentRate'],
                    'totalPay'    => $roster['totalPay'],
                    'status'      => $roster['status'] ?? 'active', // Default to 'active' if not provided
                    'description' => $roster['description'] ?? null,
                    'created_by'  => $id
                ]);

                $savedRosters[] = $saved;
            }

            return response()->json([
                'status'  => true,
                'message' => 'Rosters created successfully.',
                'data'    => $savedRosters,
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
    public function show(string $id)
    {
        //
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
