<?php
namespace App\Http\Controllers\Api;

// use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Mail\SendNotificationsMail;
use App\Models\UnavailabilityModel;
use App\Models\UserProfileModel;
use App\Notifications\UnavailabilityNotification;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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

    public function store(Request $request, $id = null)
    {
        try {
            $statusMap = [
                'pending'  => 0,
                'approved' => 1,
                'rejected' => 2,
            ];

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
                $unavail->unavailStatus = $statusMap[$request->unavailStatus] ?? 0;

                $unavail->save();

                $fetchNotifyData = UserProfileModel::where('id', $request->notifyTo)->first();

                $notifyEmail = $fetchNotifyData->email;

                $this->sendNotification($request, $unavail, 'Unavailability requested off on', $notifyEmail);

                return response()->json([
                    'message' => 'Unavailability saved successfully',
                    'data'    => $unavail,
                ]);
            } else {
                // Recurring unavailability
                $requestFromTime = $request->fromDT ? Carbon::parse($request->fromDT)->format('h:i A') : null;
                $requestToTime   = $request->toDT ? Carbon::parse($request->toDT)->format('h:i A') : null;

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
                $unavail->unavailStatus = $statusMap[$request->unavailStatus] ?? 0;

                $unavail->save();
                $fetchNotifyData = UserProfileModel::where('id', $request->notifyTo)->first();

                $notifyEmail = $fetchNotifyData->email;

                $this->sendNotification($request, $unavail, 'Recurring Unavailability requested off on', $notifyEmail);

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

        } catch (Exception $e) {
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

    protected function sendNotification($request, $unavail, $title, $email)
    {
        $notifyToUser = UserProfileModel::find($request->notifyTo);
        $user         = UserProfileModel::find($request->userId);

        $userName = $user ? $user->firstName . ' ' . $user->lastName : 'Unknown User';

        if ($notifyToUser) {
            Log::info('Found notifyTo user with ID: ' . $notifyToUser->id);
            $fromDT = Carbon::parse($request->fromDT)->format('d M Y h:i A');
            $toDT = Carbon::parse($request->toDT)->format('d M Y h:i A');

            $notificationMessage = $userName . ' has submitted ' . $title . ' from ' . $fromDT . ' to ' . $toDT;

            $notification        = new UnavailabilityNotification([
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
            Mail::to($email)->send(new SendNotificationsMail($notificationMessage));
            Log::info("Notification sent to user ID: " . $notifyToUser->id);
        } else {
            Log::warning('notifyTo user not found. ID: ' . $request->notifyTo);
        }
    }

}
