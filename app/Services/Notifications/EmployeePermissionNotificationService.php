<?php

namespace App\Services\Notifications;

use App\Models\Notification;
use App\Models\PermissionRequest;

use App\Services\Notifications\Traits\HasFirebaseNotification;

class EmployeePermissionNotificationService
{
    use HasFirebaseNotification;

    public function notifyEmployee(PermissionRequest $request, string $type, $data): void
    {
        try {
            if (is_string($data)) {
                $message = $data;
                $data = ['message' => $message];
            }

            $existingNotification = Notification::where([
                'user_id' => $request->user_id,
                'type' => $type,
                'related_id' => $request->id
            ])->exists();

            if (!$existingNotification) {
                $notificationData = array_merge($data, [
                    'request_details' => [
                        'departure_time' => $request->departure_time->format('Y-m-d H:i'),
                        'return_time' => $request->return_time->format('Y-m-d H:i'),
                        'minutes_used' => $request->minutes_used,
                        'reason' => $request->reason,
                        'remaining_minutes' => $request->remaining_minutes
                    ]
                ]);

                Notification::create([
                    'user_id' => $request->user_id,
                    'type' => $type,
                    'data' => $notificationData,
                    'related_id' => $request->id
                ]);
            }

            if ($request->user) {
                $this->sendAdditionalFirebaseNotification(
                    $request->user,
                    $data['message'] ?? 'إشعار جديد'
                );
            }
        } catch (\Exception $e) {
        }
    }

    public function notifyTeamMembers(PermissionRequest $request, string $type, array $data): void
    {
        if ($request->user && $request->user->currentTeam) {
            $teamMembers = $request->user->currentTeam->users()
                ->where('users.id', '!=', $request->user_id)
                ->get();

            foreach ($teamMembers as $member) {
                Notification::create([
                    'user_id' => $member->id,
                    'type' => $type,
                    'data' => $data,
                    'related_id' => $request->id
                ]);
            }
        }
    }

    public function deleteExistingNotifications(PermissionRequest $request, string $type): void
    {
        try {
            Notification::where('related_id', $request->id)
                ->where('type', $type)
                ->delete();
        } catch (\Exception $e) {
        }
    }
}
