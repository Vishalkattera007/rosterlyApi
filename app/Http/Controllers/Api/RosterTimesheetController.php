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
    public function generateTimesheet(Request $request)
    {
        try {
            $userId    = $request->user_id;
            $rosterId  = $request->roster_id;
            $date      = $request->date ?? Carbon::today()->toDateString();

            // Fetch all logs of the day for the user and roster
            $logs = RosterAttendanceLog::where('user_id', $userId)
                ->where('roster_id', $rosterId)
                ->whereDate('timestamp', $date)
                ->orderBy('timestamp')
                ->get();

            if ($logs->isEmpty()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'No attendance logs found for the given date.',
                ], 404);
            }

            $startTime    = null;
            $endTime      = null;
            $breakStart   = null;
            $totalBreak   = 0;
            $totalWork    = 0;

            foreach ($logs as $log) {
                switch ($log->action_type) {
                    case 'start':
                        $startTime = Carbon::parse($log->timestamp);
                        break;

                    case 'break_start':
                        $breakStart = Carbon::parse($log->timestamp);
                        break;

                    case 'break_end':
                        if ($breakStart) {
                            $breakEnd = Carbon::parse($log->timestamp);
                            $totalBreak += $breakEnd->diffInMinutes($breakStart);
                            $breakStart = null;
                        }
                        break;

                    case 'end':
                        $endTime = Carbon::parse($log->timestamp);
                        break;
                }
            }

            if ($startTime && $endTime) {
                $totalWork = $endTime->diffInMinutes($startTime) - $totalBreak;

                // Save or update timesheet
                $timesheet = RosterTimesheet::updateOrCreate(
                    [
                        'user_id'   => $userId,
                        'roster_id' => $rosterId,
                        'date'      => $date,
                    ],
                    [
                        'total_work_time'  => round($totalWork / 60, 2),   // in hours
                        'total_break_time' => round($totalBreak / 60, 2),  // in hours
                    ]
                );

                return response()->json([
                    'status'  => true,
                    'message' => 'Timesheet calculated and saved.',
                    'data'    => $timesheet,
                ]);
            }

            return response()->json([
                'status'  => false,
                'message' => 'Start or end time missing for calculating work hours.',
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }
}