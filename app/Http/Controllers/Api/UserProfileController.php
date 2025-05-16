<?php
namespace App\Http\Controllers\Api;

use App\Mail\SendPasswordMail;
use Illuminate\Http\Request;
use Http\Discovery\Exception;
use App\Models\UserProfileModel;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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

    

    public function index()
    {
        $userProfile = UserProfileModel::all();
        return response()->json([
            'message' => 'userProfile list',
            'data'    => $userProfile,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
<<<<<<< HEAD
   public function store(Request $request)
{
    try {
        // Check if user already exists
        $existingUser = UserProfileModel::where('email', $request->email)->first();
        if ($existingUser) {
=======
    public function store(Request $request)
    {
        try {
            // Check if the email already exists
            $existingUser = UserProfileModel::where('email', $request->email)->first();

            if ($existingUser) {
                return response()->json([
                    'message' => "User already exists",
                    'status' => false
                ], 409); 
            }

            if (!$request->has('password') || empty($request->password)) {
                return response()->json([
                    'message' => "Password is required",
                    'status' => false
                ], 400);
            }

            // // Create a new admin
            $userCreate = UserProfileModel::create([
                'firstName' =>  $request->firstName,
                'lastName'  =>  $request->lastName,
                'email'     =>  $request->email,
                'password' => Hash::make($request->password), 
                'mobileNumber'     =>  $request->phone,
                'location_id' => $request->locationId,
                'dob' => $request->dateOfBirth,
                'created_by' =>  $request->created_by,
                'created_at' =>  $request->created_on,
                'status' =>  $request->status,
                'role_id' =>  $request->role_id,
                'payrate' =>  $request->payrate,
                'profileImage' =>  $request->profileImage,
                'updated_at' =>  $request->updated_on,
                'updated_by' =>  $request->updated_by,
            ]);

>>>>>>> 1e1262be69383263b7e098f66ba964353549c6b2
            return response()->json([
                'message' => "User already exists",
                'status' => false
            ], 409);
        }

        // Generate password if requested
        $password = $request->generatePassword ? Str::random(8) : $request->password;

        if (!$password) {
            return response()->json([
                'message' => "Password is required",
                'status' => false
            ], 400);
        }

        // Create user
        $userCreate = UserProfileModel::create([
            'role_id' => $request->role_id,
            'firstName' => $request->firstName,
            'lastName' => $request->lastName,
            'email' => $request->email,
            'password' => Hash::make($password),
            'dob' => $request->dob,
            'mobileNumber' => $request->phone,
            'location_id' => $request->location_id,
            'payrate' => $request->payrate,
            'profileImage' => $request->profileImage,
            'created_by' => $request->created_by,
            'created_at' => $request->created_on,
            'updated_at' => $request->updated_on,
            'updated_by' => $request->updated_by,
            'status' => $request->status,
        ]);

        // Send password mail if it was generated
        if ($request->generatePassword) {
            Mail::to($request->email)->send(new SendPasswordMail($password));
        }

        return response()->json([
            'message' => "User Created Successfully",
            'data' => $userCreate,
            'status' => true
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'message' => "Something went wrong",
            'error' => $e->getMessage(),
            'status' => false
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

            // Only update if keys are present
            $user->update([
                'firstName'    => $request->firstName ?? $user->firstName,
                'lastName'     => $request->lastName ?? $user->lastName,
                'email'        => $request->email ?? $user->email,
                'password'     => $request->password ? Hash::make($request->password) : $user->password,
                'mobileNumber' => $request->phone ?? $user->mobileNumber,
                'location_id'  => $request->dateOfBirth ?? $user->location_id,
                'updated_by'   => $request->updated_by ?? $user->updated_by,
                'updated_at'   => now(),
                'status'       => $request->status ?? $user->status,
                'role_id'      => $request->role_id ?? $user->role_id,
                'payrate'      => $request->payrate ?? $user->payrate,
                'profileImage' => $request->profileImage ?? $user->profileImage,
            ]);

            return response()->json(['message' => 'User updated successfully', 'data' => $user], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'Failed to update user', 'error' => $e->getMessage()], 500);
        }
    }


    public function getUsersCreatedBy($loginId, Request $request)
    {
        try {
            // Build query: fetch users where created_by = given login ID
            $query = UserProfileModel::with('location')
                        ->where('created_by', $loginId);

            // Optional: if you want to filter by location_id as well
            if ($request->has('location_id') && !empty($request->location_id)) {
                $query->where('location_id', $request->location_id);
            }

            $users = $query->get();

            if ($users->isEmpty()) {        
                return response()->json([
                    'message' => 'No users found for the given creator.',
                    'status' => false
                ], 404);
            }

            return response()->json([
                'message' => 'Users fetched successfully.',
                'data' => $users,
                'status' => true
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong.',
                'error' => $e->getMessage(),
                'status' => false
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
            $user->delete();

            return response()->json(['message' => 'User deleted successfully'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'Failed to delete user', 'error' => $e->getMessage()], 500);
        }
    }
}
