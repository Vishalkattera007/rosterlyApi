<?php
namespace App\Http\Controllers\Api;

// use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\UnavailabilityModel;
use App\Models\UserProfileModel;
use App\Notifications\UnavailabilityNotification;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UnavailabilityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($id = null)
    {
        try {
            if ($id != null) {
                $unavailability = UnavailabilityModel::with(['userProfile', 'notifyToUserProfile'])
                    ->where('userId', $id)
                    ->get();

                return response()->json([
                    'message' => 'Unavailability list',
                    'data'    => $unavailability,
                ]);
            } else {
                $unavailability = UnavailabilityModel::with(['userProfile', 'notifyToUserProfile'])
                    ->get();

                return response()->json([
                    'message' => 'Unavailability list',
                    'data'    => $unavailability,
                ]);
            }
        } catch (Exception $e) {
            return response()->json(['message' => 'Failed to fetch unavailability list', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $id = null)
    {
        try {
            if ($id != 2) {
                // 1. Validate the datetime format
                $validator = Validator::make($request->all(), [
                    'fromDT' => ['required', 'date_format:Y-m-d H:i:s'],
                    'toDT'   => ['required', 'date_format:Y-m-d H:i:s'],
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'message' => 'Invalid datetime format. Expected format: Y-m-d H:i:s',
                        'errors'  => $validator->errors(),
                    ], 422);
                }

                // 2. Parse for consistency and comparison

                $requestFromDT = Carbon::createFromFormat('Y-m-d h:i:s A', $request->fromDT)->format('Y-m-d H:i:s');
                $requestToDT   = Carbon::createFromFormat('Y-m-d h:i:s A', $request->toDT)->format('Y-m-d H:i:s');

                // 3. Check for exact match in existing records
                $unavailDetails = UnavailabilityModel::where('userId', $request->userId)
                    ->get(['fromDT', 'toDT']);

                foreach ($unavailDetails as $unavailDetail) {

                    $existingFromDT = Carbon::createFromFormat('Y-m-d h:i:s A', $unavailDetail->fromDT)->format('Y-m-d H:i:s');
                    $existingToDT   = Carbon::createFromFormat('Y-m-d h:i:s A', $unavailDetail->toDT)->format('Y-m-d H:i:s');

                    if ($requestFromDT === $existingFromDT && $requestToDT === $existingToDT) {
                        return response()->json([
                            'message' => 'Unavailability already exists for the selected date & time range',
                        ], 400);
                    }
                }

                $statusMap = [
                    'pending'  => 0,
                    'approved' => 1,
                    'rejected' => 2,
                ];

                $unavail                = new UnavailabilityModel();
                $unavail->userId        = $request->userId;
                $unavail->unavailType   = $id;
                $unavail->day           = null;
                $unavail->fromDT        = Carbon::parse($request->fromDT);
                $unavail->toDT          = Carbon::parse($request->toDT);
                $unavail->reason        = $request->reason;
                $unavail->notifyTo      = $request->notifyTo;
                $unavail->unavailStatus = $statusMap[$request->unavailStatus] ?? 0;

                $unavail->save();
                return response()->json([
                    'message' => 'Unavailability saved successfully',
                    'data'    => $unavail,
                ]);

            } else {
                // Reccuring Days Off
                $unavailDetails = UnavailabilityModel::where('userId', $request->userId)
                    ->get(['fromDT', 'toDT']);

                foreach ($unavailDetails as $unavailDetail) {
                    // Parse request times (e.g., "4:15 PM")
                    $requestFromTime = Carbon::createFromFormat('h:i A', trim($request->fromDT));
                    $requestToTime   = Carbon::createFromFormat('h:i A', trim($request->toDT));

                    // Parse existing DB times (already stored in "h:i A" format)
                    $existingFromTime = Carbon::createFromFormat('h:i A', $unavailDetail->fromDT);
                    $existingToTime   = Carbon::createFromFormat('h:i A', $unavailDetail->toDT);

                    // Overlap check
                    if (
                        $requestFromTime->lt($existingToTime) &&
                        $requestToTime->gt($existingFromTime)
                    ) {
                        return response()->json([
                            'message' => 'Recurring unavailability already exists for the selected time range.',
                        ], 400);
                    }
                }
                $statusMap = [
                    'pending'  => 0,
                    'approved' => 1,
                    'rejected' => 2,
                ];
                $unavail                = new UnavailabilityModel();
                $unavail->userId        = $request->userId;
                $unavail->unavailType   = $id;
                $unavail->day           = $request->day;
                $unavail->fromDT        = Carbon::createFromFormat('h:i A', trim($request->fromDT))->format('h:i A');
                $unavail->toDT          = Carbon::createFromFormat('h:i A', trim($request->toDT))->format('h:i A');
                $unavail->reason        = $request->reason;
                $unavail->notifyTo      = $request->notifyTo;
                $unavail->unavailStatus = $statusMap[$request->unavailStatus] ?? 0;

                $unavail->save();

                Log::info('Unavailability record saved successfully. ID: ' . $unavail->id);

// Send notification
                $notifyToUser = UserProfileModel::find($request->notifyTo);

                if ($notifyToUser) {
                    Log::info('Found notifyTo user with ID: ' . $notifyToUser->id);

                    $notification = new UnavailabilityNotification([
                        'userId' => $request->userId,
                        'fromDT' => $request->fromDT,
                        'toDT'   => $request->toDT,
                    ]);

                    $notifyToUser->notify($notification);
                    Log::info("Notification sent to user ID: " . $notifyToUser->id);
                } else {
                    Log::warning('notifyTo user not found. ID: ' . $request->notifyTo);
                }

                return response()->json([
                    'message' => 'Recurring saved successfully',
                    'data'    => $unavail,
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to store unavailability',
                'error'   => $e->getMessage(),
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
            $unavail->fromDT        = $request->fromDT;
            $unavail->toDT          = $request->toDT;
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
