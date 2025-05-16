<?php

namespace App\Http\Controllers\Api;

use App\Mail\SendForgotMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use App\Models\UserProfileModel;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;


class ForgotPasswordController extends Controller
{
    public function forgotPassword(Request $request)
{
    // Basic check without validation
    $email = $request->email;

    if (!$email) {
        return response()->json([
            'status' => false,
            'message' => 'Email is required.'
        ]);
    }

    $user = UserProfileModel::where('email', $email)->first();

    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => 'Email not found in our records.'
        ]);
    }

    $newPassword = Str::random(8); // Generate random 8-character password

    // Update password in DB
    $user->password = Hash::make($newPassword);
    $user->save();

    // Send Email
    Mail::to($user->email)->send(new SendForgotMail($newPassword));

    return response()->json([
        'status' => true,
        'message' => 'A new password has been sent to your email. Please log in and change your password.'
    ]);
}
}
