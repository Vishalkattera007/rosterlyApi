<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LocationModel;
use App\Models\UserProfileModel;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;


class DownloadRosterPdf extends Controller
{
    public function downloadRosterPDF(Request $request)
    {
        $locationId = $request->input('location_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $roster = $request->input('roster');
        $dates = $request->input('dates');
        $id = $request->input('login_id');

        // ✅ Get location name
        $location = LocationModel::find($locationId)?->location_name  ?? 'Unknown Location';

        // ✅ Get creator name (from user profile)
        $user = UserProfileModel::find($id);
        $createdByName = $user ? $user->firstName . ' ' . $user->lastName : 'Unknown User';

        // ✅ Get current date and time
        $currentDate = Carbon::now()->format('d M Y H:i A');

        // ✅ Load PDF view
        $pdf = Pdf::loadView('pdf.roster', [
            'location' => $location,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'roster' => $roster,
            'dates' => $dates,
            'createdBy' => $createdByName, // passing full name
            'currentDate' => $currentDate,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('roster.pdf');
    }
}
