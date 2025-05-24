<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RosterModel;
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
    public function store(Request $request)
    {
        $rosterData = new RosterModel();
        $rosterData->user_id      = $request->user_id;
        $rosterData->location_id  = $request->location_id;
        $rosterData->Date         = $request->Date;
        $rosterData->StartTime    = $request->StartTime;
        $rosterData->EndTime      = $request->EndTime;
        
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
