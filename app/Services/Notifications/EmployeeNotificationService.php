<?php

namespace App\Services\Notifications;

use App\Models\Notification;
use App\Models\AbsenceRequest;
use App\Services\Notifications\Traits\HasFirebaseNotification;

class EmployeeNotificationService
{
    use HasFirebaseNotification;

    public function notifyEmployee(AbsenceRequest $request, string $type, array $data): void
    {
        try {
            $notificationData = array_merge($data, [
                'request_details' => [
                    'absence_date' => $request->absence_date->format('Y-m-d'),
                    'reason' => $request->reason,
                    'status' => $request->status
                ]
            ]);

            Notification::create([
                'user_id' => $request->user_id,
                'type' => $type,
                'data' => $notificationData,
                'related_id' => $request->id
            ]);

            if ($request->user) {
                $this->sendAdditionalFirebaseNotification(
                    $request->user,
                    $data['message'] ?? 'إشعار جديد'
                );
            }
        } catch (\Exception $e) {
        }
    }

    public function deleteExistingNotifications(AbsenceRequest $request, string $type): void
    {
        try {
            Notification::where('related_id', $request->id)
                ->where('type', $type)
                ->delete();
        } catch (\Exception $e) {
        }
    }
}
