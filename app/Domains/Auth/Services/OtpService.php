<?php

namespace App\Domains\Auth\Services;

use App\Models\User;
use App\Models\UserOtp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OtpService
{
    public function generateForUser(User $user): string
    {
        $otp = (string) random_int(100000, 999999);
        
        // Invalidate existing OTPs
        UserOtp::where('user_id', $user->id)->delete();

        UserOtp::create([
            'user_id' => $user->id,
            'otp_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes(5),
        ]);

        return $otp;
    }

    public function send(User $user, string $otp): void
    {
        \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\OtpMail($otp));
    }

    public function verify(User $user, string $otp): void
    {
        $userOtp = UserOtp::where('user_id', $user->id)->first();

        // Check if OTP exists and matches
        if (! $userOtp || ! Hash::check($otp, $userOtp->otp_hash)) {
             throw \Illuminate\Validation\ValidationException::withMessages([
                'otp' => [trans('auth.failed')],
            ]);
        }

        // Check expiry
        if ($userOtp->expires_at->isPast()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'otp' => [trans('auth.otp_expired')],
            ]);
        }

        // Delete OTP after successful verification (Single Use)
        $userOtp->delete();
    }
}
