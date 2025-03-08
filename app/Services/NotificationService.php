<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\AbsenceRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Notifications\ManagerNotificationService;
use App\Services\Notifications\EmployeeNotificationService;

class NotificationService
{
    protected $managerNotificationService;
    protected $employeeNotificationService;

    public function __construct(
        ManagerNotificationService $managerNotificationService,
        EmployeeNotificationService $employeeNotificationService
    ) {
        $this->managerNotificationService = $managerNotificationService;
        $this->employeeNotificationService = $employeeNotificationService;
    }

    public function createLeaveRequestNotification(AbsenceRequest $request): void
    {
        try {
            $message = "{$request->user->name} قام بتقديم طلب غياب";

            if ($request->user && $request->user->currentTeam && $request->user->currentTeam->owner) {
                $this->managerNotificationService->notifyManagers($request, 'new_leave_request', $message, false);
            }

            $this->managerNotificationService->notifyManagers($request, 'new_leave_request', $message, true);
        } catch (\Exception $e) {
        }
    }

    public function createStatusUpdateNotification(AbsenceRequest $request): void
    {
        try {
            $updatedBy = auth()->guard()->user();
            $currentUserRole = $updatedBy?->roles->first()?->name;
            $hasTeam = DB::table('team_user')
                ->where('team_user.user_id', $request->user_id)
                ->exists();

            $statusMessage = $this->getStatusMessage($request, $currentUserRole);

            Notification::create([
                'user_id' => $request->user_id,
                'type' => 'leave_request_status_update',
                'data' => [
                    'message' => $statusMessage,
                    'request_id' => $request->id,
                    'date' => $request->absence_date->format('Y-m-d'),
                    'status' => $request->status,
                    'manager_status' => $request->manager_status,
                    'hr_status' => $request->hr_status,
                    'manager_rejection_reason' => $request->manager_rejection_reason,
                    'hr_rejection_reason' => $request->hr_rejection_reason,
                    'has_team' => $hasTeam,
                    'response_by' => $currentUserRole
                ],
                'related_id' => $request->id
            ]);
        } catch (\Exception $e) {
        }
    }

    private function getStatusMessage(AbsenceRequest $request, ?string $currentUserRole): string
    {
        $statusArabic = [
            'approved' => 'الموافقة على',
            'rejected' => 'رفض',
            'pending' => 'تعليق'
        ];

        if ($currentUserRole === 'hr') {
            $status = $request->hr_status;
            return "HR قام بـ " . ($statusArabic[$status] ?? 'تحديث') . " طلب الغياب";
        } else {
            $status = $request->manager_status;
            return "المدير قام بـ " . ($statusArabic[$status] ?? 'تحديث') . " طلب الغياب";
        }
    }

    private function sendAdditionalNotifications(AbsenceRequest $request, ?string $currentUserRole, bool $hasTeam): void
    {
        if ($hasTeam && $currentUserRole === 'hr') {
            $this->notifyTeamOwners($request);
        }
        elseif (in_array($currentUserRole, ['team_leader', 'department_manager', 'company_manager'])) {
            $this->notifyHR($request);
        }
    }

    public function notifyStatusReset(AbsenceRequest $request, string $responseType): void
    {
        $hasTeam = DB::table('team_user')
            ->where('team_user.user_id', $request->user_id)
            ->exists();

        Notification::create([
            'user_id' => $request->user_id,
            'type' => 'leave_request_status_reset',
            'data' => [
                'message' => ($responseType === 'manager' ? 'المدير' : 'HR') . " قام بإعادة تعيين حالة طلب الغياب",
                'request_id' => $request->id,
                'response_type' => $responseType,
                'has_team' => $hasTeam
            ],
            'related_id' => $request->id
        ]);

        $currentUserId = auth()->guard()->id();

        if ($responseType === 'manager' && $hasTeam) {
            $hrUsers = User::role('hr')->pluck('id');
            foreach ($hrUsers as $hrUserId) {
                if ($hrUserId !== $currentUserId) {
                    Notification::create([
                        'user_id' => $hrUserId,
                        'type' => 'leave_request_status_reset',
                        'data' => [
                            'message' => "المدير قام بإعادة تعيين حالة طلب الغياب للموظف {$request->user->name}",
                            'request_id' => $request->id,
                            'response_type' => $responseType,
                            'has_team' => $hasTeam
                        ],
                        'related_id' => $request->id
                    ]);
                }
            }
        } elseif ($responseType === 'hr' && $hasTeam) {
            $teamOwners = DB::table('team_user')
                ->join('teams', 'teams.id', '=', 'team_user.team_id')
                ->where('team_user.user_id', $request->user_id)
                ->where('team_user.role', 'owner')
                ->pluck('team_user.user_id');

            foreach ($teamOwners as $ownerId) {
                if ($ownerId !== $currentUserId) {
                    Notification::create([
                        'user_id' => $ownerId,
                        'type' => 'leave_request_status_reset',
                        'data' => [
                            'message' => "HR قام بإعادة تعيين حالة طلب الغياب للموظف {$request->user->name}",
                            'request_id' => $request->id,
                            'response_type' => $responseType,
                            'has_team' => $hasTeam
                        ],
                        'related_id' => $request->id
                    ]);
                }
            }
        }
    }

    public function notifyRequestModified(AbsenceRequest $request): void
    {
        try {
            $message = "{$request->user->name} قام بتعديل طلب الغياب";

            if ($request->user && $request->user->currentTeam && $request->user->currentTeam->owner) {
                $this->managerNotificationService->notifyManagers($request, 'leave_request_modified', $message, false);
            }

            $this->managerNotificationService->notifyManagers($request, 'leave_request_modified', $message, true);
        } catch (\Exception $e) {
        }
    }

    public function notifyRequestDeleted(AbsenceRequest $request): void
    {
        try {
            $message = "{$request->user->name} قام بحذف طلب الغياب";

            if ($request->user && $request->user->currentTeam && $request->user->currentTeam->owner) {
                $this->managerNotificationService->notifyManagers($request, 'leave_request_deleted', $message, false);
            }

            $this->managerNotificationService->notifyManagers($request, 'leave_request_deleted', $message, true);
        } catch (\Exception $e) {
        }
    }

    public function getUnreadCount(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    public function getUserNotifications(User $user)
    {
        return Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    public function markAsRead(Notification $notification): void
    {
        $notification->markAsRead();
    }

    private function notifyTeamOwners(AbsenceRequest $request): void
    {
        try {
            $teamOwners = DB::table('team_user')
                ->join('teams', 'teams.id', '=', 'team_user.team_id')
                ->where('team_user.user_id', $request->user_id)
                ->where('team_user.role', 'owner')
                ->pluck('team_user.user_id');

            foreach ($teamOwners as $ownerId) {
                Notification::create([
                    'user_id' => $ownerId,
                    'type' => 'leave_request_response_update',
                    'data' => [
                        'message' => "HR قام بالرد على طلب الغياب للموظف {$request->user->name}",
                        'request_id' => $request->id,
                        'date' => $request->absence_date->format('Y-m-d'),
                        'status' => $request->hr_status,
                        'rejection_reason' => $request->hr_rejection_reason,
                        'is_hr_response' => true
                    ],
                    'related_id' => $request->id
                ]);
            }
        } catch (\Exception $e) {
        }
    }

    private function notifyHR(AbsenceRequest $request): void
    {
        try {
            $hrUsers = User::role('hr')->get();
            foreach ($hrUsers as $hrUser) {
                Notification::create([
                    'user_id' => $hrUser->id,
                    'type' => 'leave_request_response_update',
                    'data' => [
                        'message' => "المدير قام بالرد على طلب الغياب للموظف {$request->user->name}",
                        'request_id' => $request->id,
                        'date' => $request->absence_date->format('Y-m-d'),
                        'status' => $request->manager_status,
                        'rejection_reason' => $request->manager_rejection_reason,
                        'is_manager_response' => true
                    ],
                    'related_id' => $request->id
                ]);
            }
        } catch (\Exception $e) {
        }
    }

    public function createResponseModificationNotification(AbsenceRequest $request, string $responseType): void
    {
        try {
            Notification::create([
                'user_id' => $request->user_id,
                'type' => 'leave_request_response_modified',
                'data' => [
                    'message' => ($responseType === 'manager' ? 'المدير' : 'HR') . " قام بتعديل الرد على طلب الغياب",
                    'request_id' => $request->id,
                    'date' => $request->absence_date->format('Y-m-d'),
                    'status' => $responseType === 'manager' ? $request->manager_status : $request->hr_status,
                    'rejection_reason' => $responseType === 'manager' ? $request->manager_rejection_reason : $request->hr_rejection_reason,
                    'response_type' => $responseType,
                    'final_status' => $request->status
                ],
                'related_id' => $request->id
            ]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function createStatusResetNotification(AbsenceRequest $request, string $responseType): void
    {
        try {
            Notification::create([
                'user_id' => $request->user_id,
                'type' => 'leave_request_status_reset',
                'data' => [
                    'message' => ($responseType === 'manager' ? 'المدير' : 'HR') . " قام بإعادة تعيين الرد على طلب الغياب",
                    'request_id' => $request->id,
                    'date' => $request->absence_date->format('Y-m-d'),
                    'response_type' => $responseType,
                    'final_status' => $request->status
                ],
                'related_id' => $request->id
            ]);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
