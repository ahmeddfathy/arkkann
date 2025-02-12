<?php

namespace App\Services\Notifications;

use App\Models\Notification;
use App\Models\OverTimeRequests;
use Illuminate\Support\Facades\Log;

class OvertimeEmployeeNotificationService
{
    public function notifyEmployee(OverTimeRequests $request, string $type, string $message): void
    {
        try {
            // التحقق من عدم وجود إشعار مكرر
            $existingNotification = Notification::where([
                'user_id' => $request->user_id,
                'type' => $type,
                'related_id' => $request->id
            ])->exists();

            if (!$existingNotification) {
                Notification::create([
                    'user_id' => $request->user_id,
                    'type' => $type,
                    'data' => [
                        'message' => $message,
                        'request_id' => $request->id,
                        'overtime_date' => $request->overtime_date,
                        'start_time' => $request->start_time,
                        'end_time' => $request->end_time,
                        'reason' => $request->reason,
                        'status' => $request->status,
                        'manager_status' => $request->manager_status,
                        'hr_status' => $request->hr_status,
                        'manager_rejection_reason' => $request->manager_rejection_reason,
                        'hr_rejection_reason' => $request->hr_rejection_reason
                    ],
                    'related_id' => $request->id
                ]);

                Log::info("Employee notification ({$type}) created for user: {$request->user_id}", [
                    'request_id' => $request->id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error in notifyEmployee: ' . $e->getMessage(), [
                'request_id' => $request->id,
                'user_id' => $request->user_id,
                'type' => $type
            ]);
        }
    }
}
