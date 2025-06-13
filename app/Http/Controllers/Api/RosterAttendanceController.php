<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RosterAttendanceLog;
use App\Models\UserProfileModel;
use App\Models\RosterModel;
use Carbon\Carbon;

class RosterAttendanceController extends Controller
{
    // Log attendance action (start, break_start, break_end, end)
    public function logAction(Request $request)
    {
        try {
            $log = RosterAttendanceLog::create([
                'user_id'     => $request->user_id,
                'roster_id'   => $request->roster_id,
                'action_type' => $request->action_type,
                'timestamp'   => Carbon::now(), // Or use $request->timestamp
                'location'    => $request->location,
                'remarks'     => $request->remarks,
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Attendance action logged successfully.',
                'data'    => $log
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    // Get all actions for a specific date/user
    public function getActions(Request $request)
    {
        try {
            $userId = $request->user_id;
            $date = $request->date ?? Carbon::today()->toDateString();

            $logs = RosterAttendanceLog::where('user_id', $userId)
                ->whereDate('timestamp', $date)
                ->orderBy('timestamp')
                ->get();

            return response()->json([
                'status' => true,
                'data'   => $logs
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}

