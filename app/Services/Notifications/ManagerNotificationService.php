<?php

namespace App\Services\Notifications;

use App\Models\Notification;
use App\Models\User;
use App\Models\AbsenceRequest;
use Illuminate\Support\Facades\Log;

class ManagerNotificationService
{
    public function notifyManagers(AbsenceRequest $request, string $type, string $message, bool $isHRNotification = false): void
    {
        try {
            if ($isHRNotification) {

                $hrUsers = User::whereHas('roles', function ($q) {
                    $q->where('name', 'hr');
                })
                    ->where('id', '!=', $request->user_id) 
                    ->get();

                if ($hrUsers->isNotEmpty()) {
                    foreach ($hrUsers as $hrUser) {
                        $this->createNotification($hrUser, $request, $type, $message, true);
                    }
                    Log::info('Notifications sent to HR users for request: ' . $request->id);
                } else {
                    Log::info('No HR notifications sent - request owner is the only HR user for request: ' . $request->id);
                }
            } else {
                // إرسال إشعار لمالك الفريق (فقط إذا كان الفريق به عضو على الأقل غير المالك وليس هو صاحب الطلب)
                if (
                    $request->user &&
                    $request->user->currentTeam &&
                    $request->user->currentTeam->owner &&
                    $request->user->currentTeam->owner->id !== $request->user_id
                ) {
                    // التحقق من وجود أعضاء في الفريق غير المالك
                    $teamMembersCount = $request->user->currentTeam->users()
                        ->where('users.id', '!=', $request->user->currentTeam->owner_id)
                        ->count();

                    if ($teamMembersCount > 0) {
                        $teamOwner = $request->user->currentTeam->owner;
                        $this->createNotification($teamOwner, $request, $type, $message, false);
                        Log::info('Notification sent to team owner: ' . $teamOwner->id . ' for request: ' . $request->id . ' (team members excluding owner: ' . $teamMembersCount . ')');
                    } else {
                        Log::info('No notification sent - team has no members other than owner for request: ' . $request->id);
                    }
                } else {
                    Log::info('No notification sent - team owner is the request owner or no team found for request: ' . $request->id);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in notifyManagers: ' . $e->getMessage(), [
                'request_id' => $request->id,
                'is_hr_notification' => $isHRNotification
            ]);
        }
    }

    private function createNotification(User $user, AbsenceRequest $request, string $type, string $message, bool $isHRNotification): void
    {
        try {
            // التحقق من عدم وجود إشعار مكرر
            $existingNotification = Notification::where([
                'user_id' => $user->id,
                'type' => $type,
                'related_id' => $request->id
            ])->exists();

            if (!$existingNotification) {
                $isTeamOwner = false;
                if (!$isHRNotification && $request->user && $request->user->currentTeam) {
                    $isTeamOwner = $request->user->currentTeam->owner_id === $user->id;
                }

                $notificationData = [
                    'message' => $message,
                    'request_id' => $request->id,
                    'employee_name' => $request->user->name,
                    'absence_date' => $request->absence_date->format('Y-m-d'),
                    'reason' => $request->reason,
                    'is_hr_notification' => $isHRNotification,
                    'is_manager_notification' => !$isHRNotification,
                    'is_team_owner' => $isTeamOwner
                ];

                // إضافة معلومات إضافية حسب نوع الإشعار
                if ($type === 'leave_request_modified') {
                    $notificationData['modification_time'] = now()->format('Y-m-d H:i:s');
                } elseif ($type === 'leave_request_deleted') {
                    $notificationData['deletion_time'] = now()->format('Y-m-d H:i:s');
                }

                Notification::create([
                    'user_id' => $user->id,
                    'type' => $type,
                    'data' => $notificationData,
                    'related_id' => $request->id
                ]);

                Log::info("Notification ({$type}) created for user: {$user->id} for request: {$request->id}");
            } else {
                Log::info("Duplicate notification ({$type}) prevented for user: {$user->id} for request: {$request->id}");
            }
        } catch (\Exception $e) {
            Log::error('Error in createNotification: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'request_id' => $request->id,
                'type' => $type
            ]);
        }
    }
}
