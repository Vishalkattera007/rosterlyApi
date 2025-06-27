<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class LocationUser extends Controller
{
    public function getActiveUsersByLocation($locationId)
{
    $users = DB::table('locationUsers as lu')
        ->join('user_profiles as u', 'lu.user_id', '=', 'u.id')
        ->select('lu.location_id', 'lu.user_id', 'lu.role', 'u.id', 'u.status')
        ->where('lu.location_id', $locationId)
        ->where('u.status', 1)
        ->get();

    return response()->json([
        'status' => true,
        'data' => $users,
    ]);
}

}
