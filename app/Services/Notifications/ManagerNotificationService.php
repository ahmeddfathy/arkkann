<?php

namespace App\Services\Notifications;

use App\Models\Notification;
use App\Models\User;
use App\Models\AbsenceRequest;
use App\Services\Notifications\Traits\HasFirebaseNotification;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Auth;

class ManagerNotificationService
{
    use HasFirebaseNotification;

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
                    $this->sendNotificationsToMultipleUsers($hrUsers, $request, $type, $message, true);
                }
            } else {
                $userTeams = $request->user->teams;
                $managersToNotify = collect();

                foreach ($userTeams as $team) {
                    if ($team->owner_id === $request->user_id) {
                        continue;
                    }

                    $isAdmin = DB::table('team_user')
                        ->where('team_id', $team->id)
                        ->where('user_id', $request->user_id)
                        ->where('role', 'admin')
                        ->exists();

                    if ($isAdmin) {
                        continue;
                    }

                    $teamMembersCount = $team->users()
                        ->where('users.id', '!=', $team->owner_id)
                        ->count();

                    if ($teamMembersCount > 0) {
                        $teamOwner = $team->owner;
                        if ($teamOwner && $teamOwner->id !== $request->user_id) {
                            $managersToNotify->push($teamOwner);
                        }
                    }
                }

                if ($managersToNotify->isNotEmpty()) {
                    $this->sendNotificationsToMultipleUsers($managersToNotify, $request, $type, $message, false);
                }
            }
        } catch (\Exception $e) {
            error_log('Error in ManagerNotificationService::notifyManagers - ' . $e->getMessage());
        }
    }

    private function sendNotificationsToMultipleUsers($users, AbsenceRequest $request, string $type, string $message, bool $isHRNotification): void
    {
        try {
            foreach ($users as $user) {
                $this->createNotification($user, $request, $type, $message, $isHRNotification);
            }
        } catch (\Exception $e) {
            error_log('Error in ManagerNotificationService::sendNotificationsToMultipleUsers - ' . $e->getMessage());
        }
    }

    private function createNotification(User $user, AbsenceRequest $request, string $type, string $message, bool $isHRNotification): void
    {
        try {
            $existingNotification = Notification::where([
                'user_id' => $user->id,
                'type' => $type,
                'related_id' => $request->id
            ])->exists();

            if (!$existingNotification ||
                strpos($type, '_modified') !== false ||
                strpos($type, '_deleted') !== false ||
                strpos($type, '_reset') !== false) {

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
                    'is_team_owner' => $isTeamOwner,
                    'notification_time' => now()->format('Y-m-d H:i:s'),
                    'responder_name' => Auth::check() ? Auth::user()->name : 'النظام'
                ];

                if ($type === 'leave_request_modified') {
                    $notificationData['modification_time'] = now()->format('Y-m-d H:i:s');
                    $notificationData['modified_by'] = Auth::check() ? Auth::user()->name : 'النظام';
                } elseif ($type === 'leave_request_deleted') {
                    $notificationData['deletion_time'] = now()->format('Y-m-d H:i:s');
                    $notificationData['deleted_by'] = Auth::check() ? Auth::user()->name : 'النظام';
                }

                if (isset($request->status)) {
                    $notificationData['status'] = $request->status;
                }
                if (isset($request->manager_status)) {
                    $notificationData['manager_status'] = $request->manager_status;
                }
                if (isset($request->hr_status)) {
                    $notificationData['hr_status'] = $request->hr_status;
                }

                $notification = Notification::create([
                    'user_id' => $user->id,
                    'type' => $type,
                    'data' => $notificationData,
                    'related_id' => $request->id
                ]);

                $actionType = $this->determineFirebaseActionType($type);
                $title = $this->determineNotificationTitle($type, $request);
                $this->sendTypedFirebaseNotification($user, 'absence', $actionType, $message, $request->id);
            }
        } catch (\Exception $e) {
            error_log('Error in ManagerNotificationService::createNotification - ' . $e->getMessage());
        }
    }

    private function determineFirebaseActionType(string $type): string
    {
        if (strpos($type, 'new_leave_request') !== false) {
            return 'created';
        } elseif (strpos($type, 'leave_request_modified') !== false) {
            return 'updated';
        } elseif (strpos($type, 'leave_request_deleted') !== false) {
            return 'deleted';
        } elseif (strpos($type, 'manager_response') !== false || strpos($type, 'hr_response') !== false) {
            if (strpos($type, 'approved') !== false) {
                return 'approved';
            } elseif (strpos($type, 'rejected') !== false) {
                return 'rejected';
            }
        } elseif (strpos($type, 'status_reset') !== false) {
            return 'reset';
        }

        return 'updated';
    }

    private function determineNotificationTitle(string $type, AbsenceRequest $request): string
    {
        if (strpos($type, 'new_leave_request') !== false) {
            return 'طلب غياب جديد';
        } elseif (strpos($type, 'leave_request_modified') !== false) {
            return 'تم تحديث طلب الغياب';
        } elseif (strpos($type, 'leave_request_deleted') !== false) {
            return 'تم حذف طلب الغياب';
        } elseif (strpos($type, 'manager_response') !== false) {
            if (isset($request->manager_status) && $request->manager_status === 'approved') {
                return 'تمت الموافقة على طلب الغياب من المدير';
            } elseif (isset($request->manager_status) && $request->manager_status === 'rejected') {
                return 'تم رفض طلب الغياب من المدير';
            }
        } elseif (strpos($type, 'hr_response') !== false) {
            if (isset($request->hr_status) && $request->hr_status === 'approved') {
                return 'تمت الموافقة على طلب الغياب من الموارد البشرية';
            } elseif (isset($request->hr_status) && $request->hr_status === 'rejected') {
                return 'تم رفض طلب الغياب من الموارد البشرية';
            }
        } elseif (strpos($type, 'status_reset') !== false) {
            return 'تمت إعادة تعيين حالة طلب الغياب';
        }

        return 'إشعار طلب غياب';
    }

    public function sendFirebaseNotification(User $user, string $message, string $title = 'إشعار جديد', ?string $link = null): ?array
    {
        return $this->sendAdditionalFirebaseNotification($user, $message, $title, $link);
    }
}
