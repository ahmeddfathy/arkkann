<?php

namespace App\Services\Notifications;

use App\Models\Notification;
use App\Models\PermissionRequest;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class EmployeePermissionNotificationService
{
    public function notifyEmployee(PermissionRequest $request, string $type, array $data): void
    {
        try {
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
        } catch (\Exception $e) {
            Log::error('Error sending employee permission notification: ' . $e->getMessage());
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
        Notification::where('related_id', $request->id)
            ->where('type', $type)
            ->delete();
    }
}
