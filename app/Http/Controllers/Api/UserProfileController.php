<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\SendPasswordMail;
use App\Models\UserProfileModel;
use Http\Discovery\Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;


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
            $findAllUsers = UserProfileModel::all();
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

       // Determine password to store (hashed)
        if ($request->filled('password')) {
            $password = Hash::make($request->password);
        } else {
            $password = $user->password;  // keep existing hashed password
        }

        
        // Update all other fields
        $user->update([
            'role_id'      => $request->input('role_id', $user->role_id),
            'firstName'    => $request->input('firstName', $user->firstName),
            'lastName'     => $request->input('lastName', $user->lastName),
            'email'        => $request->input('email', $user->email),
            'password'     => $password,   // hashed or existing password
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
   public function destroy(string $id)
{
    try {
        $user = UserProfileModel::findOrFail($id);
        
        // Update status from 1 to 0 instead of deleting
        $user->status = 0;
        $user->save();

        return response()->json(['message' => 'User deactivated successfully'], 200);
    } catch (Exception $e) {
        return response()->json(['message' => 'Failed to deactivate user', 'error' => $e->getMessage()], 500);
    }
}

}
