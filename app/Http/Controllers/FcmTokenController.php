<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FcmTokenController extends Controller
{
    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'token' => 'required|string'
            ]);

            $user = $request->user();
            if (!$user) {
                Log::error('User not authenticated when updating FCM token');
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // تسجيل معلومات إضافية للتشخيص
            Log::info('FCM token update attempt', [
                'user_id' => $user->id,
                'role' => $user->role,
                'old_token' => substr($user->fcm_token ?? 'none', 0, 15) . '...',
                'new_token' => substr($validated['token'], 0, 15) . '...',
                'is_same' => $user->fcm_token === $validated['token'],
                'is_new_login' => session('new_login', false)
            ]);

            // Check if the token has actually changed to prevent unnecessary updates
            if ($user->fcm_token === $validated['token'] && !session('new_login', false)) {
                Log::info('FCM token already up to date, skipping update', [
                    'user_id' => $user->id
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'FCM token is already up to date'
                ]);
            }

            // تحديث التوكن
            $user->fcm_token = $validated['token'];
            $user->save();

            // مسح متغير الجلسة بعد التحديث الناجح
            session()->forget('new_login');

            Log::info('FCM token updated successfully', [
                'user_id' => $user->id,
                'role' => $user->role
            ]);

            return response()->json([
                'success' => true,
                'message' => 'FCM token updated successfully',
                'user_role' => $user->role
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating FCM token: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error updating FCM token'
            ], 500);
        }
    }
}
