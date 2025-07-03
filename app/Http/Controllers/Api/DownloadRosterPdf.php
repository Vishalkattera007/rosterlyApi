<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LocationModel;
use App\Models\RosterModel;
use App\Models\RosterWeekModel;
use App\Models\UserProfileModel;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;


class DownloadRosterPdf extends Controller
{
    // public function downloadRosterPDF(Request $request)
    // {
    //     $locationId = $request->input('location_id');
    //     $startDate = $request->input('start_date');
    //     $endDate = $request->input('end_date');
    //     $roster = $request->input('roster');
    //     $dates = $request->input('dates');
    //     $id = $request->input('login_id');

    //     // ✅ Get location name
    //     $location = LocationModel::find($locationId)?->location_name  ?? 'Unknown Location';

    //     // ✅ Get creator name (from user profile)
    //     $user = UserProfileModel::find($id);
    //     $createdByName = $user ? $user->firstName . ' ' . $user->lastName : 'Unknown User';

    //     // ✅ Get current date and time
    //     $currentDate = Carbon::now()->format('d M Y H:i A');

    //     // ✅ Load PDF view
    //     $pdf = Pdf::loadView('pdf.roster', [
    //         'location' => $location,
    //         'startDate' => $startDate,
    //         'endDate' => $endDate,
    //         'roster' => $roster,
    //         'dates' => $dates,
    //         'createdBy' => $createdByName, // passing full name
    //         'currentDate' => $currentDate,
    //     ])->setPaper('a4', 'landscape');

    //     return $pdf->download('roster.pdf');
    // }

      public function downloadRosterPDF(Request $request)
    {
        $locationId = $request->query('location_id');
        $rosterWeekId = $request->query('roster_week_id');

        if (!$locationId || !$rosterWeekId) {
            return response()->json(['message' => 'location_id and roster_week_id are required'], 422);
        }

        // Fetch Roster Week (to get date range)
        $rosterWeek = RosterWeekModel::find($rosterWeekId);
        if (!$rosterWeek) {
            return response()->json(['message' => 'Roster Week not found'], 404);
        }

        $start = Carbon::parse($rosterWeek->week_start_date);
        $end = Carbon::parse($rosterWeek->week_end_date);
        $days = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $days[] = $date->format('D, d/m');
        }

        // Fetch Roster data
        $rosters = RosterModel::where('rosterWeekId', $rosterWeekId)
            ->where('location_id', $locationId)
            ->with('user') // adjust if you use a custom relation
            ->orderBy('date')
            ->get()
            ->groupBy('user_id');

        // Prepare weekly data
        $rosterData = [];
        $weeklyTotalMinutes = 0;

        foreach ($rosters as $userId => $shifts) {
            $user = $shifts->first()->user;

            $daysData = [];
            $weeklyMinutes = 0;

            foreach ($days as $day) {
                $formattedDate = Carbon::createFromFormat('D, d/m', $day)->format('Y-m-d');
                $shift = $shifts->where('date', $formattedDate)->first();

                if ($shift) {
                    $start = Carbon::parse($shift->startTime);
                    $end = Carbon::parse($shift->endTime);
                    $break = $shift->breakTime ?? 0;

                    $diff = $end->diffInMinutes($start) - $break;
                    $weeklyMinutes += $diff;

                    $daysData[] = [
                        'start' => $start->format('g:i A'),
                        'end' => $end->format('g:i A'),
                        'duration' => gmdate('H\h i\m', $diff),
                        'break' => "{$break} min break",
                    ];
                } else {
                    $daysData[] = null;
                }
            }

            $rosterData[] = [
                'name' => $user->firstName . ' ' . $user->lastName,
                'rate' => $user->hour_rate,
                'weekly_hours' => gmdate('H\h i\m', $weeklyMinutes * 60),
                'days' => $daysData,
            ];

            $weeklyTotalMinutes += $weeklyMinutes;
        }

        $pdf = Pdf::loadView('pdf.roster', [
            'days' => $days,
            'rosterData' => $rosterData,
            'weeklyTotal' => gmdate('H\h i\m', $weeklyTotalMinutes * 60),
        ]);

        return $pdf->download('roster-sheet.pdf');
    }
}
