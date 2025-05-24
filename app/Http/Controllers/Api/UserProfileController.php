<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\SendPasswordMail;
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
            $userProfile = UserProfileModel::find($id);
            if (! $userProfile) {
                return response()->json([
                    'message' => 'User not found',
                ], 404);
            }
            return response()->json([
                'message' => 'User Profile found',
                'data'    => $userProfile,
            ]);
        } else {
            $findAllUsers = UserProfileModel::where('deletestatus', 0)->get();
            return response()->json([
                'message' => 'userProfile list',
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
                'role_id'      => $request->role_id,
                'firstName'    => $request->firstName,
                'lastName'     => $request->lastName,
                'email'        => $request->email,
                'password'     => Hash::make($generatedPassword),
                'dob'          => $request->dob,
                'mobileNumber' => $request->mobileNumber,
                'payrate'      => $request->payrate,
                'payratePercent'     => $request->payratePercent,
                'profileImage' => $profileImagePath,
                'created_by'   => $request->created_by,
                'created_at'   => now(),
            ]);

            // Send generated password to user via email
            Mail::to($request->email)->send(new SendPasswordMail($generatedPassword));

            return response()->json([
                'message' => "User created and a confirmation email has been sent to the user's email address.",
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

            // Full update
            $user->update([
                'role_id'      => $request->input('role_id', $user->role_id),
                'firstName'    => $request->input('firstName', $user->firstName),
                'lastName'     => $request->input('lastName', $user->lastName),
                'email'        => $request->input('email', $user->email),
                'password'     => $password,
                'dob'          => $request->input('dob', $user->dob),
                'mobileNumber' => $request->input('mobileNumber', $user->mobileNumber),
                'location_id'  => $request->input('location_id', $user->location_id),
                'status'       => $request->input('status', $user->status),
                'payrate'      => $request->input('payrate', $user->payrate),
                'profileImage' => $path,
                'updated_by'   => $request->input('updated_by', $user->updated_by),
                'updated_at'   => now(),
            ]);

            return response()->json([
                'message' => 'User updated successfully',
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

    public function getUsersCreatedBy($loginId, Request $request)
    {
        try {
            // Build query: fetch users where created_by = given login ID
            $query = UserProfileModel::with('location')
                ->where('created_by', $loginId);

            // Optional: if you want to filter by location_id as well
            if ($request->has('location_id') && ! empty($request->location_id)) {
                $query->where('location_id', $request->location_id);
            }

            $users = $query->get();

            if ($users->isEmpty()) {
                return response()->json([
                    'message' => 'No users found for the given creator.',
                    'status'  => false,
                ], 404);
            }

            return response()->json([
                'message' => 'Users fetched successfully.',
                'data'    => $users,
                'status'  => true,
            ]);

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

        $user->deletestatus = 1;
        $user->deletedby    = $request->deletedby;
        $user->deleted_at   = Carbon::now(); // or now() helper
        $user->save();

        return response()->json(['message' => 'User soft-deleted successfully']);
    }

    public function getnotifications(Request $request)
    {
        $user = $request->user('api'); // Authenticated user

        if (! $user) {
            return response()->json([
                'message' => 'User not authenticated',
            ], 401);
        }

        // Fetch notifications where notifiable_id = logged in user ID
        $notifications = Enter::table('notifications')
            ->where('notifiable_id', $user->id)
            ->select('id', 'notifiable_id', 'data')
            ->get();

        $sep_data = $notifications->map(function ($notifing) {
            return [
                'id'            => $notifing->id,
                'notifiable_id' => $notifing->notifiable_id,
                'data'          => json_decode($notifing->data),
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

        $data = json_decode($notification->data, true);

        $employeeId = $data['userId'] ?? null;

        if (! $employeeId) {
            return response()->json([
                'message' => 'Invalid notification data',
            ], 400);
        }

        UnavailabilityModel::where('id', $data['unavailabilityId'] ?? null)
            ->update([
                'unavailStatus'    => $request->action,
                'statusUpdated_by' => $manager->id,
            ]);

        $employee = UserProfileModel::find($employeeId);
        if (! $employee) {
            return response()->json([
                'message' => 'Employee not found',
            ], 404);
        } else {
            $employee->notify(new UnavailabilityResponseNotification([
                'status'  => $request->action,
                'manager' => $manager->firstName . ' ' . $manager->lastName,
                'message' => "Your leave request has been {$request->action} by {$manager->name}.",
            ]));
            return response()->json([
                'message' => 'Notifications marked as read successfully',
                'data'    => $data,
            ]);
        }

    }

}
