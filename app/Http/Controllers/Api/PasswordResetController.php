<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ResetPasswordMail;
use App\Models\PasswordResetRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class PasswordResetController extends Controller
{
    public function forgotPassword(Request $request)
    {
        $valid = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($valid->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $valid->errors(),
            ], 422);
        }

        $data = $valid->validated();

        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        // Delete previous reset request
        PasswordResetRequest::where('user_id', $user->id)->delete();

        // Generate secure token
        $token = Str::random(64);

        // Save reset request
        PasswordResetRequest::create([
            'user_id' => $user->id,
            'token' => $token,
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);

        // Generate reset link
        $resetLink = env('FRONTEND_URL') . '/reset-password?token=' . $token;

        // Send Email
        Mail::to($user->email)->send(
            new ResetPasswordMail($resetLink)
        );

        return response()->json([
            'success' => true,
            'message' => 'Password reset link has been sent to your email.'
        ], 200);
    }

    public function resetPassword(Request $request)
    {
        $valid = Validator::make($request->all(), [
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($valid->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $valid->errors(),
            ], 422);
        }

        $data = $valid->validated();

        $resetRequest = PasswordResetRequest::where('token', $data['token'])->first();

        if (!$resetRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid reset link.'
            ], 404);
        }

        if ($resetRequest->used_at) {
            return response()->json([
                'success' => false,
                'message' => 'This reset link has already been used.'
            ], 422);
        }

        if (Carbon::now()->greaterThan($resetRequest->expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Reset link has expired.'
            ], 422);
        }

        $user = User::find($resetRequest->user_id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);
        }

        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        $resetRequest->update([
            'used_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully.'
        ], 200);
    }
}
