<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use Carbon\Carbon;

class PasswordResetController extends Controller
{
    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        // Generate 6 digit OTP acak
        $otp = rand(100000, 999999);

        // Simpan atau update OTP di tabel password_reset_tokens
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => $otp, // Di sistem nyata yang sangat ketat, ini bisa di-hash. Untuk kemudahan API mobile, plain string aman selama ada expired time.
                'created_at' => Carbon::now()
            ]
        );

        // Kirim OTP via Email
        Mail::raw("Kode OTP untuk reset password Anda adalah: $otp. Kode ini berlaku selama 15 menit.", function ($message) use ($request) {
            $message->to($request->email)
                    ->subject('Kode OTP Reset Password finTrack');
        });

        return response()->json([
            'success' => true,
            'message' => 'OTP berhasil dikirim ke email Anda'
        ], 200);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'    => 'required|email|exists:users,email',
            'otp'      => 'required|numeric|digits:6',
            'password' => 'required|min:8|confirmed', // Pastikan dari Flutter mengirim 'password' dan 'password_confirmation'
        ]);

        // Cek apakah OTP valid dan ada di database
        $resetRequest = DB::table('password_reset_tokens')
                            ->where('email', $request->email)
                            ->where('token', $request->otp)
                            ->first();

        if (!$resetRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Kode OTP salah atau tidak valid'
            ], 400);
        }

        // Cek apakah OTP sudah kadaluarsa (misal: lebih dari 15 menit)
        $createdAt = Carbon::parse($resetRequest->created_at);
        if (Carbon::now()->diffInMinutes($createdAt) > 15) {
            // Hapus OTP yang sudah kadaluarsa
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            
            return response()->json([
                'success' => false,
                'message' => 'Kode OTP sudah kadaluarsa. Silakan minta ulang.'
            ], 400);
        }

        // Update password user di database
        $user = User::where('email', $request->email)->first();
        $user->update([
            'password' => Hash::make($request->password)
        ]);

        // Hapus token OTP karena sudah berhasil dipakai
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah. Silakan login dengan password baru.'
        ], 200);
    }
}