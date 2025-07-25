<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\SendPasswordMail;
use App\Models\LocationUsers;
use App\Models\Role;
use App\Models\UnavailabilityModel;
use App\Models\UserProfileModel;
use App\Notifications\UnavailabilityResponseNotification;
use Carbon\Carbon;
use Http\Discovery\Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB as Enter;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserProfileController extends Controller
{
    public function login(Request $request)
    {
        $user = UserProfileModel::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('API Token')->accessToken;

        return response()->json([
            'token' => $token,
            'user'  => $user,
        ]);
    }

    public function index($id = null)
    {
        if ($id != null) {
            $userProfile = UserProfileModel::with('locationUsers')
                ->where('id', $id)
            // ->where('status', 1)
                ->where('deletestatus', 0)
                ->first();

            if ($userProfile) {
                $userArray = $userProfile->toArray();

                if (! empty($userArray['location_users'])) {
                    $userArray['location_id'] = $userArray['location_users']['location_id'];
                } else {
                    $userArray['location_id'] = null;
                }

                unset($userArray['location_users']);

                return response()->json($userArray);
            }

            return response()->json([
                'message' => 'User not found',
            ], 404);
        } else {
            $findAllUsers = UserProfileModel::with('locationUsers')
                ->where('deletestatus', 0)
                ->get()
                ->map(function ($user) {
                    $userArray = $user->toArray();
                    $userArray['location_id'] = ! empty($userArray['location_users'])
                    ? $userArray['location_users']['location_id']
                    : null;
                    unset($userArray['location_users']);
                    return $userArray;
                });

            return response()->json([
                'message' => 'User profile list',
                'data'    => $findAllUsers,
            ]);
        }
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {

            $validatePayratePercent = $request->payratePercent ?? 0;
            if ($validatePayratePercent < 0 || $validatePayratePercent > 100) {
                return response()->json([
                    'message' => "Pay rate percent must be between 0 and 100",
                    'status'  => false,
                ], 400);
            }

            // Check if user already exists
            $existingUser = UserProfileModel::where('email', $request->email)->first();
            if ($existingUser) {
                return response()->json([
                    'message' => "User already exists",
                    'status'  => false,
                ], 409);
            }

            // Always generate random 8-character password
            $generatedPassword = Str::random(8);

            // Store profile image (if uploaded)
            $profileImagePath = null;
            if ($request->hasFile('profileImage')) {
                $profileImagePath = $request->file('profileImage')->store('profile_images', 'public');
            }

            // Create user
            $userCreate = UserProfileModel::create([
                'role_id'        => $request->role_id,
                'firstName'      => $request->firstName,
                'lastName'       => $request->lastName,
                'email'          => $request->email,
                'password'       => Hash::make($generatedPassword),
                'dob'            => $request->dob,
                'mobileNumber'   => $request->mobileNumber,
                'payrate'        => $request->payrate,
                'payratePercent' => $validatePayratePercent,
                'profileImage'   => $profileImagePath,
                'created_by'     => $request->created_by,
                'created_at'     => now(),
            ]);

            // Get role name
            $roleName = optional($userCreate->role)->role_name ?? 'Role';

            // Send generated password to user via email
            Mail::to($request->email)->send(new SendPasswordMail($generatedPassword, $request->firstName));

            return response()->json([
                'message' => "<h3>{$roleName} Created</h3><h5>Confirmation email sent.</h5>",
                'data'    => $userCreate,
                'status'  => true,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => "Something went wrong",
                'error'   => $e->getMessage(),
                'status'  => false,
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $userRole = UserProfileModel::with('role')->where('role_id', $id)->get();
        if ($userRole->isEmpty()) {
            return response()->json([
                'message' => 'No user profiles found for this role.',
            ], 404);
        }

        return response()->json([
            'message' => 'User Profiles found',
            'role'    => $userRole->first()->role->role_name ?? 'unknown role',
            'data'    => $userRole,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $user = UserProfileModel::findOrFail($id);

            $validatePayratePercent = $request->payratePercent ?? 0;
            if ($validatePayratePercent < 0 || $validatePayratePercent > 100) {
                return response()->json([
                    'message' => "Pay rate percent must be between 0 and 100",
                    'status'  => false,
                ], 400);
            }

            // If only "status" is in the request, do minimal update
            if ($request->only(['status']) && count($request->all()) === 1) {
                $user->update([
                    'status'     => $request->status,
                    'updated_by' => $request->input('updated_by', $user->updated_by),
                    // 'updated_at' => now(),
                ]);

                return response()->json([
                    'message' => 'User status updated successfully',
                    'data'    => $user,
                ], 200);
            }

            // Handle profile image upload
            if ($request->hasFile('profileImage')) {
                // Delete old image if exists
                if ($user->profileImage && Storage::disk('public')->exists($user->profileImage)) {
                    Storage::disk('public')->delete($user->profileImage);
                }
                // Store new image
                $path = $request->file('profileImage')->store('profile_images', 'public');
            } else {
                $path = $user->profileImage;
            }

            // Handle password
            $password = $request->filled('password') ? Hash::make($request->password) : $user->password;

            $requestedRoleId = $request->input('role_id', $user->role_id);
            $created_by      = $request->input('created_by', $user->created_by);

// Override if the updated role is manager
            if ((int) $requestedRoleId === 2) {
                $created_by = 1;
            }

            $user->update([
                'role_id'        => $requestedRoleId,
                'firstName'      => $request->input('firstName', $user->firstName),
                'lastName'       => $request->input('lastName', $user->lastName),
                'email'          => $request->input('email', $user->email),
                'password'       => $password,
                'dob'            => $request->input('dob', $user->dob),
                'mobileNumber'   => $request->input('mobileNumber', $user->mobileNumber),
                'status'         => $request->input('status', $user->status),
                'payrate'        => $request->input('payrate', $user->payrate),
                'payratePercent' => $request->input('payratePercent', $validatePayratePercent),
                'profileImage'   => $path,
                'created_by'     => $created_by,
                'updated_by'     => $request->input('updated_by', $user->updated_by),
                'updated_at'     => now(),
            ]);

            return response()->json([
                'message' => 'Profile Updated Successfully',
                'data'    => $user,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to update user',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function updateStatus(Request $request, string $id)
    {
        try {
            $user = UserProfileModel::findOrFail($id);

            $user->update([
                'status'     => $request->status,
                'updated_by' => $request->input('updated_by', $user->updated_by),
            ]);

            return response()->json([
                'message' => 'User status updated successfully',
                'data'    => $user,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to update user status',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function getUsersCreatedBy(Request $request, $location_id = null)
    {
        try {

            $loginId = $request->user('api')->id;
            $roleId  = $request->user('api')->role_id;

            if ($location_id !== null) {
                $excludedUserIds = LocationUsers::where('location_id', $location_id)
                    ->pluck('user_id')
                    ->toArray(); //[15,22]

                if ($roleId === 1) {
                    $users = UserProfileModel::with('locationUsers')
                        ->whereNotIn('id', $excludedUserIds)->where('deletestatus', 0)
                        ->get();
                } else {
                    $users = UserProfileModel::with('locationUsers')
                        ->whereNotIn('id', $excludedUserIds)->where('created_by', $loginId)->where('deletestatus', 0)
                        ->get();
                }

                if ($users->isEmpty()) {
                    return response()->json([
                        'message' => 'No users found outside this location.',
                        'status'  => false,
                    ], 404);
                }

                $customData = $users->map(function ($user) {
                    return [
                        'id'        => $user->id,
                        'firstName' => $user->firstName,
                        'lastName'  => $user->lastName,
                        'roleId'    => $user->role_id,
                        // Add more fields as needed
                    ];
                });

                return response()->json([
                    'message'     => 'Users fetched successfully.',
                    'status'      => true,
                    'data'        => $customData,
                    'roleNagaraj' => $roleId,
                ]);
            } else {
                $query = UserProfileModel::with('locationUsers')
                    ->where('created_by', $loginId);

                $users = $query->get();

                if ($users->isEmpty()) {
                    return response()->json([
                        'message' => 'No users found for the given creator.',
                        'status'  => false,
                    ], 404);
                }

// Transform users to flatten location_id
                $transformedUsers = $users->map(function ($user) {
                    $userArray                = $user->toArray();
                    $userArray['location_id'] = ! empty($userArray['location_users'])
                    ? $userArray['location_users']['location_id']
                    : null;

                    unset($userArray['location_users']);
                    return $userArray;
                });

                return response()->json([
                    'message' => 'Users fetched successfully.',
                    'data'    => $transformedUsers,
                    'status'  => true,
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong.',
                'error'   => $e->getMessage(),
                'status'  => false,
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        $user = UserProfileModel::find($id);

        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

                                                // Fetch role name
        $role     = Role::find($user->role_id); // Or use relationship if defined
        $roleName = $role ? $role->role_name : 'User';

        $user->deletestatus = 1;
        $user->deletedby    = $request->deletedby;
        $user->deleted_at   = Carbon::now();
        $user->save();

        return response()->json(['message' => "{$roleName} deleted successfully"]);
    }

    // get deleted user
   public function deletedUsers()
{
    $deletedUsers = UserProfileModel::with('locationUsers')
        ->where('deletestatus', 1)
        ->get()
        ->map(function ($user) {
            $userArray = $user->toArray();
            $userArray['location_id'] = !empty($userArray['location_users'])
                ? $userArray['location_users']['location_id']
                : null;
            unset($userArray['location_users']);
            return $userArray;
        });

    return response()->json([
        'message' => 'Deleted users list',
        'data'    => $deletedUsers,
    ]);
}

// Restore User

public function restore($id, Request $request)
{
    try {
        $loginId = $request->user('api')->id;
        $user = UserProfileModel::findOrFail($id);

        $user->status = 1;
        $user->deletestatus = 0;
        $user->deletedBy = $loginId; // optional
        $user->deleted_at = Carbon::now();

        $user->save();

        return response()->json([
            'message' => 'User restored successfully.',
            'data' => $user
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Failed to restore user',
            'error' => $e->getMessage()
        ], 500);
    }
}



    public function getnotifications(Request $request)
    {
        $user = $request->user('api'); // Authenticated user

        if (! $user) {
            return response()->json([
                'message' => 'User not authenticated',
            ], 401);
        }

        $fetchNotificationResponse = Enter::table('notifications')
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')->get();
        if ($fetchNotificationResponse === 0) {
            return response()->json([
                'message' => 'No notifications found',
                'data'    => [],
            ]);
        } else {
            $formattedNotifications = $fetchNotificationResponse->map(function ($notification) {

                $allnotdata = [
                    'id'            => $notification->id,
                    'notifiable_id' => $notification->notifiable_id,
                    'data'          => json_decode($notification->data, true), // Decode the JSON data
                    'read_at'       => $notification->read_at,
                    'created_at'    => $notification->created_at,
                    'updated_at'    => $notification->updated_at,
                ];

                                    // $outerData = json_decode($notification->data, true);
                return $allnotdata; // Extract the nested "data"
            });
            return response()->json([
                'message'       => 'Notifications fetched successfully',
                'notifications' => $formattedNotifications,

            ]);
        }

        // Fetch notifications where notifiable_id = logged in user ID
        $notifications = Enter::table('notifications')
            ->where('notifiable_id', $user->id)
            ->select('id', 'notifiable_id', 'data', 'read_at')
            ->get();

        $sep_data = $notifications->map(function ($notifing) {
            return [
                'id'            => $notifing->id,
                'notifiable_id' => $notifing->notifiable_id,
                'data'          => json_decode($notifing->data),
                'read_at'       => $notifing->read_at,
            ];
        });
        return response()->json([
            'message' => 'Notifications fetched successfully',
            'data'    => $sep_data,
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        $request->validate([
            'notification_id' => 'required|string',
            'action'          => 'required|in:1,2',
        ]);

        $manager = $request->user('api');

        $notification = Enter::table('notifications')->where('id', $request->notification_id)->first();

        if (! $notification || $notification->notifiable_id !== $manager->id) {
            return response()->json([
                'message' => 'Notification not found or unauthorized',
            ], 404);
        }

        $data       = json_decode($notification->data, true);
        $employeeId = $data['userId'] ?? null;

        if (! $employeeId) {
            return response()->json([
                'message' => 'Invalid notification data',
            ], 400);
        }

        $updateUnavail = UnavailabilityModel::where('id', $data['unavailabilityId'] ?? null)
            ->update([
                'unavailStatus'    => $request->action,
                'statusUpdated_by' => $manager->id,
            ]);

        if ($updateUnavail === 0) {
            return response()->json([
                'message' => 'Failed to update unavailability status',
            ], 500);
        } else {
            // Mark the notification as read
            Enter::table('notifications')->where('id', $request->notification_id)->update(['read_at' => now()]);
            $employee = UserProfileModel::find($employeeId);
            if (! $employee) {
                return response()->json([
                    'message' => 'Employee not found',
                ], 404);
            } else {
                $action  = strtolower($request->input('action', 'updated'));
                $fromDt  = $data['fromDT'] ?? null;
                $toDt    = $data['toDT'] ?? null;
                $reason  = $data['reason'] ?? null;
                $day     = $data['day'] ?? null;
                $dayMess = $day ? "for {$day}" : "from {$fromDt} to {$toDt}";

                // your request for fever for all day has been approved by John Doe
                // your request for fever from 2023-10-01 to 2023-10-05 has been approved by John Doe

                if ($action === '1') {
                    $actionWords = 'approved';
                } elseif ($action === '2') {
                    $actionWords = 'denied';
                } else {
                    return response()->json([
                        'message' => 'Invalid action',
                    ], 400);
                }

                $managerName     = trim("{$manager->firstName} {$manager->lastName}");
                $responseMessage = "Your {$reason} request {$dayMess} has been {$actionWords} by {$managerName}";
                $employee->notify(new UnavailabilityResponseNotification([
                    'status'  => $action,
                    'manager' => $manager->firstName . ' ' . $manager->lastName,
                    'message' => $responseMessage,
                ]));
                return response()->json([
                    'message' => "Notifications {$actionWords} successfully",
                    'data'    => $data,
                ]);
            }

        }

    }

    public function forManagers(Request $request)
    {
        $loginId = $request->user('api')->id;

        $getLoggedInUserLocation = LocationUsers::where('user_id', $loginId)
            ->pluck('location_id');

        $getUsersData = LocationUsers::with('user')->whereIn('location_id', $getLoggedInUserLocation)
            ->get();
        $users = $getUsersData
            ->pluck('user')
            ->map(function ($user, $key) use ($getUsersData) {
                $user->location_id = $getUsersData[$key]->location_id ?? null;
                return $user;
            })
            ->where('deletestatus', 0) // Optional: only active users
            ->unique('id')             // Remove duplicates
            ->values();

        return response()->json([
            'message' => 'Employees fetched successfully',
            'data'    => $users,
        ]);

    }

}
