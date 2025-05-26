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
    // public function store(Request $request, $id = null)
    // {
    //     try {
    //         if ($id != 2) {

    //             if ($request->fromDT == null || $request->toDT == null) {
    //                 $requestFromDT = null;
    //                 $requestToDT   = null;
    //             } else {
    //                 $requestFromDT = Carbon::parse($request->fromDT)->format('Y-m-d h:i A');
    //                 $requestToDT   = Carbon::parse($request->toDT)->format('Y-m-d h:i A');
    //             }

    //             $unavailDetails = UnavailabilityModel::where('userId', $request->userId)
    //                 ->get(['fromDT', 'toDT']);

    //             foreach ($unavailDetails as $unavailDetail) {
    //                 $existingFromDT = Carbon::parse($unavailDetail->fromDT)->format('Y-m-d h:i A');
    //                 $existingToDT   = Carbon::parse($unavailDetail->toDT)->format('Y-m-d h:i A');

    //                 if ($requestFromDT === $existingFromDT && $requestToDT === $existingToDT) {
    //                     return response()->json([
    //                         'message' => 'Unavailability already exists for the selected date & time range',
    //                     ], 400);
    //                 }
    //             }

    //             $statusMap = [
    //                 'pending'  => 0,
    //                 'approved' => 1,
    //                 'rejected' => 2,
    //             ];

    //             $unavail                = new UnavailabilityModel();
    //             $unavail->userId        = $request->userId;
    //             $unavail->unavailType   = $id;
    //             $unavail->day           = null;
    //             $unavail->fromDT        = $requestFromDT;
    //             $unavail->toDT          = $requestToDT;
    //             $unavail->reason        = $request->reason;
    //             $unavail->notifyTo      = $request->notifyTo;
    //             $unavail->unavailStatus = $statusMap[$request->unavailStatus] ?? 0;

    //             $unavail->save();

    //             Log::info('Reccurring record saved successfully. ID: ' . $unavail->id);
    //             // Send notification to the user
    //             $notifyToUser = UserProfileModel::find($request->notifyTo);
    //             $user         = UserProfileModel::find($request->userId);

    //             if ($user) {
    //                 $userName = $user->firstName . ' ' . $user->lastName;
    //             } else {
    //                 $userName = 'Unknown User';
    //             }
    //             if ($notifyToUser) {
    //                 Log::info('Found notifyTo user with ID: ' . $notifyToUser->id);

    //                 $notification = new UnavailabilityNotification([
    //                     'title'     => 'Unavailability Notification',
    //                     'userId'    => $request->userId,
    //                     'userName'  => $userName,
    //                     'fromDT'    => $request->fromDT,
    //                     'toDT'      => $request->toDT,
    //                     'reason'    => $request->reason,
    //                     'unavailId' => $unavail->id,
    //                 ]);

    //                 $notifyToUser->notify($notification);
    //                 Log::info("Notification sent to user ID: " . $notifyToUser->id);
    //             } else {
    //                 Log::warning('notifyTo user not found. ID: ' . $request->notifyTo);
    //             }

    //             return response()->json([
    //                 'message' => 'Unavailability saved successfully',
    //                 'data'    => $unavail,
    //             ]);

    //         } else {
    //             // Reccuring Days Off
    //             $unavailDetails = UnavailabilityModel::where('userId', $request->userId)
    //                 ->get(['fromDT', 'toDT']);

    //             foreach ($unavailDetails as $unavailDetail) {
    //                 $requestFromTime = Carbon::parse($request->fromDT)->format('h:i A');
    //                 $requestToTime   = Carbon::parse($request->toDT)->format('h:i A');

    //                 $existingFromTime = Carbon::parse($unavailDetail->fromDT)->format('h:i A');
    //                 $existingToTime   = Carbon::parse($unavailDetail->toDT)->format('h:i A');

    //                 if (
    //                     ($requestFromTime < $existingToTime) &&
    //                     ($requestToTime > $existingFromTime)
    //                 ) {
    //                     return response()->json([
    //                         'message' => 'Recurring unavailability already exists for the selected time range.',
    //                     ], 400);
    //                 }
    //             }

    //             $statusMap = [
    //                 'pending'  => 0,
    //                 'approved' => 1,
    //                 'rejected' => 2,
    //             ];

    //             $unavail                = new UnavailabilityModel();
    //             $unavail->userId        = $request->userId;
    //             $unavail->unavailType   = $id;
    //             $unavail->day           = $request->day;
    //             $unavail->fromDT        = Carbon::parse($request->fromDT)->format('h:i A');
    //             $unavail->toDT          = Carbon::parse($request->toDT)->format('h:i A');
    //             $unavail->reason        = $request->reason;
    //             $unavail->notifyTo      = $request->notifyTo;
    //             $unavail->unavailStatus = $statusMap[$request->unavailStatus] ?? 0;

    //             $unavail->save();

    //             Log::info('Reccurring record saved successfully. ID: ' . $unavail->id);
    //             // Send notification to the user
    //             $notifyToUser = UserProfileModel::find($request->notifyTo);
    //             $user         = UserProfileModel::find($request->userId);

    //             if ($user) {
    //                 $userName = $user->firstName . ' ' . $user->lastName;
    //             } else {
    //                 $userName = 'Unknown User';
    //             }

    //             if ($notifyToUser) {
    //                 Log::info('Found notifyTo user with ID: ' . $notifyToUser->id);

    //                 $notification = new UnavailabilityNotification([
    //                     'title'     => 'Recurring Notification',
    //                     'userId'    => $request->userId,
    //                     'userName'  => $userName, // correct: user making the request
    //                     'fromDT'    => $request->fromDT,
    //                     'toDT'      => $request->toDT,
    //                     'reason'    => $request->reason,
    //                     'unavailId' => $unavail->id,
    //                 ]);

    //                 $notifyToUser->notify($notification);
    //                 Log::info("Notification sent to user ID: " . $notifyToUser->id);
    //             } else {
    //                 Log::warning('notifyTo user not found. ID: ' . $request->notifyTo);
    //             }

    //             return response()->json([
    //                 'message' => 'Reccurring saved successfully',
    //                 'data'    => $unavail,
    //             ]);
    //         }

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'Failed to store unavailability',
    //             'error'   => $e->getMessage(),
    //         ], 500);
    //     }

    // }

    public function store(Request $request, $id = null)
    {
        try {
            $request->validate([
                'unavailStatus' => 'nullable|in:pending,approved,rejected',
            ]);
            $statusMap = [
                'pending'  => 0,
                'approved' => 1,
                'rejected' => 2,
            ];

            $inputStatus = $request->input('unavailStatus', 'pending');

            if ($id != 2) {
                // One-time unavailability
                $requestFromDT = Carbon::parse($request->fromDT)->format('Y-m-d h:i A');
                $requestToDT   = Carbon::parse($request->toDT)->format('Y-m-d h:i A');

                $unavailDetails = UnavailabilityModel::where('userId', $request->userId)
                    ->get(['fromDT', 'toDT']);

                foreach ($unavailDetails as $unavailDetail) {
                    $existingFromDT = Carbon::parse($unavailDetail->fromDT)->format('Y-m-d h:i A');
                    $existingToDT   = Carbon::parse($unavailDetail->toDT)->format('Y-m-d h:i A');

                    if ($requestFromDT === $existingFromDT && $requestToDT === $existingToDT) {
                        return response()->json([
                            'message' => 'Unavailability already exists for the selected date & time range',
                        ], 400);
                    }
                }

                $unavail                = new UnavailabilityModel();
                $unavail->userId        = $request->userId;
                $unavail->unavailType   = $id;
                $unavail->day           = null;
                $unavail->fromDT        = Carbon::parse($request->fromDT);
                $unavail->toDT          = Carbon::parse($request->toDT);
                $unavail->reason        = $request->reason;
                $unavail->notifyTo      = $request->notifyTo;
                $unavail->unavailStatus = $statusMap[$inputStatus] ?? 0;

                $unavail->save();

                $this->sendNotification($request, $unavail, 'Unavailability Notification');

                return response()->json([
                    'message' => 'Unavailability saved successfully',
                    'data'    => $unavail,
                ]);

            } else {

                if ($request->fromDT == null || $request->toDT == null) {
                    $requestFromTime = null;
                    $requestToTime   = null;
                } else {
                    $requestFromTime = Carbon::parse($request->fromDT)->format('h:i A');
                    $requestToTime   = Carbon::parse($request->toDT)->format('h:i A');
                }

                $unavailDetails = UnavailabilityModel::where('userId', $request->userId)
                    ->where('day', $request->day)
                    ->get(['day', 'fromDT', 'toDT']);

                foreach ($unavailDetails as $unavailDetail) {
                    $existingFromTime = Carbon::parse($unavailDetail->fromDT)->format('h:i A');
                    $existingToTime   = Carbon::parse($unavailDetail->toDT)->format('h:i A');

                    if (
                        ($requestFromTime < $existingToTime) &&
                        ($requestToTime > $existingFromTime)
                    ) {
                        return response()->json([
                            'message' => 'Recurring unavailability already exists for the selected time range on the same day.',
                        ], 400);
                    }
                }

                $unavail                = new UnavailabilityModel();
                $unavail->userId        = $request->userId;
                $unavail->unavailType   = $id;
                $unavail->day           = $request->day;
                $unavail->fromDT        = $requestFromTime;
                $unavail->toDT          = $requestToTime;
                $unavail->reason        = $request->reason;
                $unavail->notifyTo      = $request->notifyTo;
                $unavail->unavailStatus = $statusMap[$inputStatus] ?? 0;

                $unavail->save();

                $this->sendNotification($request, $unavail, 'Recurring Notification');

                return response()->json([
                    'message' => 'Recurring unavailability saved successfully',
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
            // Find the unavailability record
            $unavail = UnavailabilityModel::find($id);

            if (! $unavail) {
                return response()->json(['message' => 'Unavailability record not found'], 404);
            }

            // Common fields for both types
            $unavail->unavailType = $request->unavailType;
            $unavail->fromDT      = $request->fromDT;
            $unavail->toDT        = $request->toDT;
            $unavail->reason      = $request->reason;
            $unavail->notifyTo    = $request->notifyTo;
            $unavail->updated_by  = $request->userId;

            // Conditional field based on unavailType
            if ($id == 2) {
                $unavail->day = $request->day;
            } else {
                $unavail->day = null;
            }

            // Optional: handle status update only if provided
            if (isset($request->unavailStatus)) {
                $statusMap = [
                    'pending'  => 0,
                    'approved' => 1,
                    'rejected' => 2,
                ];
                $unavail->unavailStatus = $statusMap[$request->unavailStatus] ?? $unavail->unavailStatus;
            }

            $unavail->save();

            return response()->json([
                'message' => $id == 2
                ? "Recurring unavailability for {$request->day} updated successfully"
                : "Unavailability updated successfully",
                'data'    => $unavail,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update unavailability',
                'error'   => $e->getMessage(),
            ], 500);
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

    // helper function to send notification

    protected function sendNotification($request, $unavail, $title)
    {
        $notifyToUser = UserProfileModel::find($request->notifyTo);
        $user         = UserProfileModel::find($request->userId);

        $userName = $user ? $user->firstName . ' ' . $user->lastName : 'Unknown User';

        if ($notifyToUser) {
            Log::info('Found notifyTo user with ID: ' . $notifyToUser->id);

            $notification = new UnavailabilityNotification([
                'title'     => $title,
                'userId'    => $request->userId,
                'userName'  => $userName,
                'fromDT'    => $request->fromDT,
                'toDT'      => $request->toDT,
                'reason'    => $request->reason,
                'unavailId' => $unavail->id,
                'day'       => $request->day,
            ]);

            $notifyToUser->notify($notification);
            Log::info("Notification sent to user ID: " . $notifyToUser->id);
        } else {
            Log::warning('notifyTo user not found. ID: ' . $request->notifyTo);
        }
    }

}
