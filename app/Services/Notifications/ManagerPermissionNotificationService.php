<?php

namespace App\Services\Notifications;

use App\Models\Notification;
use App\Models\User;
use App\Models\PermissionRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ManagerPermissionNotificationService
{
    public function notifyEmployee(PermissionRequest $request, string $type, string $message, bool $isHRNotification = false): void
    {
        try {
            if ($isHRNotification) {
                // إرسال إشعار لجميع مستخدمي HR (ما عدا صاحب الطلب)
                $hrUsers = User::whereHas('roles', function ($q) {
                    $q->where('name', 'hr');
                })
                    ->where('id', '!=', $request->user_id) // استثناء صاحب الطلب
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
                // الحصول على جميع الفرق التي ينتمي إليها الموظف
                $userTeams = $request->user->teams;

                foreach ($userTeams as $team) {
                    // تجاهل الفرق التي يكون فيها الموظف هو المالك
                    if ($team->owner_id === $request->user_id) {
                        continue;
                    }

                    // التحقق مما إذا كان الموظف admin في هذا الفريق
                    $isAdmin = DB::table('team_user')
                        ->where('team_id', $team->id)
                        ->where('user_id', $request->user_id)
                        ->where('role', 'admin')
                        ->exists();

                    // إذا كان الموظف admin في هذا الفريق، نتخطى إرسال الإشعار لمالك الفريق
                    if ($isAdmin) {
                        Log::info('Skipping notification for team owner as user is admin in team: ' . $team->id);
                        continue;
                    }

                    // التحقق من وجود أعضاء في الفريق
                    $teamMembersCount = $team->users()
                        ->where('users.id', '!=', $team->owner_id)
                        ->count();

                    if ($teamMembersCount > 0) {
                        // إرسال إشعار لمالك الفريق
                        $teamOwner = $team->owner;
                        if ($teamOwner && $teamOwner->id !== $request->user_id) {
                            $this->createNotification($teamOwner, $request, $type, $message, false);
                            Log::info('Notification sent to team owner: ' . $teamOwner->id . ' for team: ' . $team->id . ' for request: ' . $request->id);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in notifyEmployee: ' . $e->getMessage(), [
                'request_id' => $request->id,
                'is_hr_notification' => $isHRNotification
            ]);
        }
    }

    private function createNotification(User $user, PermissionRequest $request, string $type, string $message, bool $isHRNotification): void
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
                    'departure_time' => $request->departure_time->format('Y-m-d H:i'),
                    'return_time' => $request->return_time->format('Y-m-d H:i'),
                    'minutes_used' => $request->minutes_used,
                    'reason' => $request->reason,
                    'remaining_minutes' => $request->remaining_minutes,
                    'is_hr_notification' => $isHRNotification,
                    'is_manager_notification' => !$isHRNotification,
                    'is_team_owner' => $isTeamOwner
                ];

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
