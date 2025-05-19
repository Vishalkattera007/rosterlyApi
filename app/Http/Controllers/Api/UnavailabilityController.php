<?php
namespace App\Http\Controllers\Api;

use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\UnavailabilityModel;
use App\Http\Controllers\Controller;

class UnavailabilityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $userId = $request->userId;

            $data = UnavailabilityModel::with(['userProfile', 'notifyToUserProfile'])
                ->where('userId', $userId)
                ->orderBy('fromDate', 'desc')
                ->get();

            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['message' => 'Failed to fetch unavailability list', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
{
    try {
        // Check for overlapping unavailability
        $unavailDetails = UnavailabilityModel::where('userId', $request->userId)
            ->get(['fromDate', 'toDate']);

        if ($unavailDetails->isNotEmpty()) {
            foreach ($unavailDetails as $unavailDetail) {
                if (
                    ($request->fromDate >= $unavailDetail->fromDate && $request->fromDate <= $unavailDetail->toDate) ||
                    ($request->toDate >= $unavailDetail->fromDate && $request->toDate <= $unavailDetail->toDate) ||
                    ($request->fromDate <= $unavailDetail->fromDate && $request->toDate >= $unavailDetail->toDate)
                ) {
                    return response()->json([
                        'message' => 'Unavailability already exists for the selected date range'
                    ], 400);
                }
            }
        }

        // Status map (adjust as per your actual convention)
        $statusMap = [
            'pending'  => 0,
            'approved' => 1,
            'rejected' => 2
        ];

        // Save new unavailability
        $unavail = new UnavailabilityModel();
        $unavail->userId        = $request->userId;
        $unavail->unavailType   = $request->unavailType;
        $unavail->day           = $request->day;
        $unavail->fromDate      = Carbon::parse($request->fromDate);
        $unavail->toDate        = Carbon::parse($request->toDate);
        $unavail->notifyTo      = $request->notifyTo;
        $unavail->unavailStatus = $statusMap[$request->unavailStatus] ?? 0; // default to 'pending'

        $unavail->save();

        return response()->json([
            'message' => 'Unavailability saved successfully',
            'data' => $unavail
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Failed to store unavailability',
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $data = UnavailabilityModel::with(['userProfile', 'notifyToUserProfile'])->find($id);

            if (! $data) {
                return response()->json(['message' => 'Not found'], 404);
            }

            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['message' => 'Failed to fetch unavailability', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $unavail = UnavailabilityModel::find($id);

            if (! $unavail) {
                return response()->json(['message' => 'Not found'], 404);
            }

            $unavail->unavailType   = $request->unavailType;
            $unavail->day           = $request->day;
            $unavail->fromDate      = $request->fromDate;
            $unavail->toDate        = $request->toDate;
            $unavail->startTime     = $request->startTime;
            $unavail->endTime       = $request->endTime;
            $unavail->notifyTo      = $request->notifyTo;
            $unavail->unavailStatus = $request->unavailStatus ?? $unavail->unavailStatus;
            $unavail->updated_on    = now();
            $unavail->updated_by    = $request->updated_by;
            $unavail->save();

            return response()->json(['message' => 'Unavailability updated successfully', 'data' => $unavail]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Failed to update unavailability', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $unavail = UnavailabilityModel::find($id);

            if (! $unavail) {
                return response()->json(['message' => 'Not found'], 404);
            }

            $unavail->delete();

            return response()->json(['message' => 'Unavailability deleted successfully']);
        } catch (Exception $e) {
            return response()->json(['message' => 'Failed to delete unavailability', 'error' => $e->getMessage()], 500);
        }
    }
}
