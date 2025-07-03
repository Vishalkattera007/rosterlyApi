<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RosterWeekModel;
use Barryvdh\DomPDF\Facade\Pdf;
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
            'location_id'  =>  $request->location,
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


public function getWeeklySummary(Request $request)
{
    try {
        $userId     = $request->query('user_id');
        $locationId = $request->query('location_id');
        $weekId     = $request->query('roster_week_id');

        // Get start and end dates for the week
        $week = RosterWeekModel::where('id', $weekId)
            ->where('location_id', $locationId)
            ->first();

        if (!$week) {
            return response()->json(['status' => false, 'message' => 'Roster week not found'], 404);
        }

        $startDate = Carbon::parse($week->week_start_date);
        $endDate   = Carbon::parse($week->week_end_date);
        $period    = CarbonPeriod::create($startDate, $endDate);

        $result = [];

        foreach ($period as $date) {
            $day = $date->toDateString();

            // Scheduled shift
            $roster = RosterModel::where('user_id', $userId)
                ->where('location_id', $locationId)
                ->where('rosterWeekId', $weekId)
                ->whereDate('date', $day)
                ->first();

            $scheduledShift = $scheduledBreak = '—';

            if ($roster && $roster->startTime && $roster->endTime) {
                $start = Carbon::parse($roster->startTime)->format('h:i A');
                $end   = Carbon::parse($roster->endTime)->format('h:i A');
                $scheduledShift = "$start - $end";

                if ($roster->breakTime) {
                    $scheduledBreak = $roster->breakTime . ' mins';
                }
            }

            // Actual logged times
            $timesheet = RosterTimesheet::where('user_id', $userId)
                ->where('location_id', $locationId)
                ->whereDate('date', $day)
                ->first();

            $actualStart = $actualEnd = $actualBreak = $actualWork = '—';

            if ($timesheet) {
                $actualStart  = $timesheet->start_time ?? '—';
                $actualEnd    = $timesheet->end_time ?? '—';
                $actualBreak  = $timesheet->break_minutes ? $timesheet->break_minutes . ' mins' : '—';
                $actualWork   = $timesheet->shift_minutes ? $timesheet->shift_minutes . ' mins' : '—';
            }

            $result[] = [
                'date'              => $date->format('D, d/m'),
                'scheduled_shift'   => $scheduledShift,
                'scheduled_break'   => $scheduledBreak,
                'actual_start'      => $actualStart,
                'actual_end'        => $actualEnd,
                'actual_break'      => $actualBreak,
                'actual_work_time'  => $actualWork,
            ];
        }

        return response()->json([
            'status' => true,
            'data'   => $result,
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Error: ' . $e->getMessage(),
        ], 500);
    }
}


// pdf generation

public function downloadTimesheetPdf(Request $request)
{
    $employee = $request->employee;
    $week = $request->week;
    $rows = $request->data;
    $totalOvertime = $request->totalOvertime;
    $totalLessTime = $request->totalLessTime;
    $totalPay = $request->totalPay;

    $pdf = Pdf::loadView('pdf.timesheet', compact(
        'employee',
        'week',
        'rows',
        'totalOvertime',
        'totalLessTime',
        'totalPay'
    ));

    return $pdf->download("timesheet-$employee.pdf");
}


}