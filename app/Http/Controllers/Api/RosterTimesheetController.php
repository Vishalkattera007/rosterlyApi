<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RosterAttendanceLog;
use App\Models\RosterTimesheet;
use App\Models\UserProfileModel;
use App\Models\RosterModel;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class RosterTimesheetController extends Controller
{
    // Calculate and store total work and break time for the day
   public function store(Request $request)
{
    try {
        $timesheet = RosterTimesheet::create([
            'user_id'       => $request->user_id,
            'roster_id'     => $request->roster_id,
            'date'          => $request->date,
            'start_time'    => $request->start_time,    // Format: HH:MM:SS
            'end_time'      => $request->end_time,      // Format: HH:MM:SS
            'break_minutes' => $request->break_minutes, // e.g., 15
            'shift_minutes' => $request->shift_minutes, // e.g., 480
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Timesheet saved successfully',
            'data'    => $timesheet,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status'  => false,
            'message' => 'Error: ' . $e->getMessage(),
        ], 500);
    }
}

}