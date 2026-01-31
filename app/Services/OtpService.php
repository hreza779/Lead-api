<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OtpService
{
    /**
     * Generate OTP code for a phone number
     */
    public function generateOtp(string $phone): array
    {
        // Clean expired codes for this phone
        $this->cleanExpiredForPhone($phone);

        // Generate 4-digit code
        $code = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

        // Store in database with 5-minute expiry
        DB::table('otp_codes')->insert([
            'phone' => $phone,
            'code' => $code,
            'expires_at' => Carbon::now()->addMinutes(5),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return [
            'code' => $code,
            'expires_at' => Carbon::now()->addMinutes(5)->toDateTimeString(),
        ];
    }

    /**
     * Verify OTP code
     */
    public function verifyOtp(string $phone, string $code): bool
    {
        $otp = DB::table('otp_codes')
            ->where('phone', $phone)
            ->where('code', $code)
            ->whereNull('verified_at')
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$otp) {
            return false;
        }

        // Mark as verified
        DB::table('otp_codes')
            ->where('id', $otp->id)
            ->update(['verified_at' => Carbon::now()]);

        return true;
    }

    /**
     * Clean expired codes for a specific phone
     */
    private function cleanExpiredForPhone(string $phone): void
    {
        DB::table('otp_codes')
            ->where('phone', $phone)
            ->where('expires_at', '<', Carbon::now())
            ->delete();
    }

    /**
     * Clean all expired codes
     */
    public function cleanExpired(): void
    {
        DB::table('otp_codes')
            ->where('expires_at', '<', Carbon::now())
            ->delete();
    }

    /**
     * Check rate limit (max 3 OTPs per hour per phone)
     */
    public function checkRateLimit(string $phone): bool
    {
        $count = DB::table('otp_codes')
            ->where('phone', $phone)
            ->where('created_at', '>', Carbon::now()->subHour())
            ->count();

        return $count < 3;
    }
}
