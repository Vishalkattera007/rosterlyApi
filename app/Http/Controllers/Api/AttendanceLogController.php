<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RosterAttendanceLog;
use Illuminate\Http\Request;

class AttendanceLogController extends Controller
{
    public function index(Request $request)
    {
        try {
            $userId = $request->user('api')->id;
            $date   = $request->date ?? now()->toDateString();

            $logs = RosterAttendanceLog::where('user_id', $userId)
                ->whereDate('timestamp', $date)
                ->with(['user', 'roster'])
                ->orderBy('timestamp', 'desc')
                ->get();

            return response()->json([
                'status' => true,
                'data'   => $logs,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
