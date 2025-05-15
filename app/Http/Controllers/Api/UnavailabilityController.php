<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UnavailabilityModel;
use Illuminate\Http\Request;
use Exception;

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
            $unavail = new UnavailabilityModel();
            $unavail->userId = $request->userId;
            $unavail->unavailType = $request->unavailType; // one-time or recurring
            $unavail->day = $request->day;
            $unavail->fromDate = $request->fromDate;
            $unavail->toDate = $request->toDate;
            $unavail->startTime = $request->startTime;
            $unavail->endTime = $request->endTime;
            $unavail->notifyTo = $request->notifyTo;
            $unavail->unavailStatus = $request->unavailStatus ?? 'pending';
            $unavail->created_on = now();
            $unavail->save();

            return response()->json(['message' => 'Unavailability saved successfully', 'data' => $unavail]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Failed to store unavailability', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $data = UnavailabilityModel::with(['userProfile', 'notifyToUserProfile'])->find($id);

            if (!$data) {
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

            if (!$unavail) {
                return response()->json(['message' => 'Not found'], 404);
            }

            $unavail->unavailType = $request->unavailType;
            $unavail->day = $request->day;
            $unavail->fromDate = $request->fromDate;
            $unavail->toDate = $request->toDate;
            $unavail->startTime = $request->startTime;
            $unavail->endTime = $request->endTime;
            $unavail->notifyTo = $request->notifyTo;
            $unavail->unavailStatus = $request->unavailStatus ?? $unavail->unavailStatus;
            $unavail->updated_on = now();
            $unavail->updated_by = $request->updated_by;
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

            if (!$unavail) {
                return response()->json(['message' => 'Not found'], 404);
            }

            $unavail->delete();

            return response()->json(['message' => 'Unavailability deleted successfully']);
        } catch (Exception $e) {
            return response()->json(['message' => 'Failed to delete unavailability', 'error' => $e->getMessage()], 500);
        }
    }
}
