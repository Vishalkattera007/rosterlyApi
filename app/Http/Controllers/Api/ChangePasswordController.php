<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\UserProfileModel;
use Illuminate\Support\Facades\Auth;


class ChangePasswordController extends Controller
{
  
   public function changePassword(Request $request)
    {
        // Find user by ID from request (e.g., from localStorage)
        $user = UserProfileModel::where('id', $request->login_id)->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found.'
            ], 404);
        }

        // Check if current password matches the hashed password in DB
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Current password is incorrect.'
            ]);
        }

        // Check if new password matches confirm password
        if ($request->new_password !== $request->confirm_password) {
            return response()->json([
                'status' => false,
                'message' => 'New password and confirm password do not match.'
            ]);
        }

        // Update password with new hashed password
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Password Updated Successfully.'
        ]);
    }

}
