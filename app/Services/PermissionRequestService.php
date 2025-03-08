<?php

namespace App\Services;

use App\Models\PermissionRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Services\ViolationService;
use App\Services\NotificationPermissionService;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PermissionRequestService
{
    protected $violationService;
    protected $notificationService;
    const MONTHLY_LIMIT_MINUTES = 180;

    public function __construct(
        ViolationService $violationService,
        NotificationPermissionService $notificationService
    ) {
        $this->violationService = $violationService;
        $this->notificationService = $notificationService;
    }

    public function getAllRequests($filters = []): LengthAwarePaginator
    {
        $user = Auth::user();
        $query = PermissionRequest::with('user');

        if (!empty($filters['employee_name'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['employee_name'] . '%');
            });
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if ($user->hasRole('hr')) {
            return $query->whereHas('user', function ($q) {
                $q->whereDoesntHave('teams');
            })->latest()->paginate(10);
        } elseif ($user->hasRole(['team_leader', 'department_manager', 'company_manager'])) {
            $team = $user->currentTeam;
            if ($team) {
                $teamMembers = $team->users->pluck('id')->toArray();
                return $query->whereIn('user_id', $teamMembers)->latest()->paginate(10);
            }
        }

        return $query->where('user_id', $user->id)
            ->latest()
            ->paginate(10);
    }

    public function createRequest(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $userId = $data['user_id'] ?? Auth::id();
            $currentUser = Auth::user();

            $validation = $this->validateTimeRequest(
                $userId,
                $data['departure_time'],
                $data['return_time']
            );

            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => $validation['message']
                ];
            }

            $managerStatus = 'pending';

            // إذا كان المدير يقوم بإنشاء طلب لأحد موظفيه
            if ($userId != $currentUser->id) {
                $isTeamOwner = false;

                if ($currentUser->currentTeam && $currentUser->currentTeam->user_id == $currentUser->id) {
                    $isTeamOwner = true;
                }

                $requestUser = User::find($userId);
                $isTeamMember = false;

                if ($requestUser && $currentUser->currentTeam) {
                    $isTeamMember = DB::table('team_user')
                        ->where('team_id', $currentUser->currentTeam->id)
                        ->where('user_id', $userId)
                        ->exists();
                }

                if ($isTeamOwner && $isTeamMember) {
                    $managerStatus = 'approved';
                }
            }

            // حساب الدقائق المتبقية
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();
            $usedMinutes = PermissionRequest::where('user_id', $userId)
                ->whereBetween('departure_time', [$startOfMonth, $endOfMonth])
                ->where('status', 'approved')
                ->sum('minutes_used');
            $remainingMinutes = self::MONTHLY_LIMIT_MINUTES - $usedMinutes;

            $request = PermissionRequest::create([
                'user_id' => $userId,
                'departure_time' => $data['departure_time'],
                'return_time' => $data['return_time'],
                'minutes_used' => $validation['duration'],
                'reason' => $data['reason'],
                'manager_status' => $managerStatus,
                'hr_status' => 'pending',
                'status' => 'pending',
                'remaining_minutes' => max(0, $remainingMinutes - $validation['duration']),
                'returned_on_time' => false
            ]);

            $this->notificationService->createPermissionRequestNotification($request);

            return [
                'success' => true,
                'used_minutes' => $validation['duration'],
                'remaining_minutes' => $request->remaining_minutes
            ];
        });
    }

    public function createRequestForUser(int $userId, array $data): array
    {
        $user = Auth::user();

        $validation = $this->validateTimeRequest(
            $userId,
            $data['departure_time'],
            $data['return_time']
        );

        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => $validation['message']
            ];
        }

        $remainingMinutes = $this->getRemainingMinutes($userId);

        $managerStatus = $user->hasRole(['team_leader', 'department_manager', 'company_manager']) ? 'approved' : 'pending';
        $hrStatus = $user->hasRole('hr') ? 'approved' : 'pending';

        $request = PermissionRequest::create([
            'user_id' => $userId,
            'departure_time' => $data['departure_time'],
            'return_time' => $data['return_time'],
            'minutes_used' => $validation['duration'],
            'reason' => $data['reason'],
            'remaining_minutes' => $remainingMinutes - $validation['duration'],
            'status' => 'pending',
            'manager_status' => $managerStatus,
            'hr_status' => $hrStatus,
            'returned_on_time' => false,
        ]);

        $request->updateFinalStatus();
        $request->save();

        $this->notificationService->createPermissionRequestNotification($request);

        return [
            'success' => true,
            'used_minutes' => $validation['used_minutes'],
            'remaining_minutes' => $validation['remaining_minutes']
        ];
    }

    public function updateRequest(PermissionRequest $request, array $data): array
    {
        $validation = $this->validateTimeRequest(
            $request->user_id,
            $data['departure_time'],
            $data['return_time'],
            $request->id
        );

        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => $validation['message']
            ];
        }

        $request->update([
            'departure_time' => $data['departure_time'],
            'return_time' => $data['return_time'],
            'reason' => $data['reason'],
            'minutes_used' => $validation['duration'],
        ]);

        $this->notificationService->notifyPermissionModified($request);

        return ['success' => true];
    }

    public function updateStatus(PermissionRequest $request, array $data): array
    {
        $responseType = $data['response_type'];
        $status = $data['status'];
        $rejectionReason = $status === 'rejected' ? $data['rejection_reason'] : null;

        if ($responseType === 'manager') {
            $request->updateManagerStatus($status, $rejectionReason);
        } elseif ($responseType === 'hr') {
            $request->updateHrStatus($status, $rejectionReason);
        }

        $this->notificationService->createPermissionStatusUpdateNotification($request);

        return ['success' => true];
    }

    public function resetStatus(PermissionRequest $request, string $responseType)
    {
        if ($responseType === 'manager') {
            $request->updateManagerStatus('pending', null);
        } elseif ($responseType === 'hr') {
            $request->updateHrStatus('pending', null);
        }

        $this->notificationService->notifyManagerResponseDeleted($request);

        return $request;
    }

    public function updateReturnStatus(PermissionRequest $request, int $returnStatus): array
    {
        $request->update(['returned_on_time' => $returnStatus]);
        $this->violationService->handleReturnViolation($request, $returnStatus);

        return ['success' => true];
    }

    public function getRemainingMinutes(int $userId): int
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $usedMinutes = PermissionRequest::where('user_id', $userId)
            ->whereBetween('departure_time', [$startOfMonth, $endOfMonth])
            ->whereIn('status', ['pending', 'approved'])
            ->sum('minutes_used');

        return max(0, self::MONTHLY_LIMIT_MINUTES - $usedMinutes);
    }

    private function validateTimeRequest(int $userId, string $departureTime, string $returnTime, ?int $excludeId = null): array
    {
        try {
            $departure = Carbon::parse($departureTime);
            $return = Carbon::parse($returnTime);
            $duration = $departure->diffInMinutes($return);

            // التحقق من أن وقت العودة بعد وقت المغادرة
            if ($return <= $departure) {
                return [
                    'valid' => false,
                    'message' => 'يجب أن يكون وقت العودة بعد وقت المغادرة'
                ];
            }

            // التحقق من عدم وجود تداخل مع طلبات أخرى
            $query = PermissionRequest::where('user_id', $userId)
                ->where(function ($q) use ($departureTime, $returnTime) {
                    $q->where(function ($q) use ($departureTime, $returnTime) {
                        $q->where('departure_time', '<=', $departureTime)
                            ->where('return_time', '>', $departureTime);
                    })
                    ->orWhere(function ($q) use ($departureTime, $returnTime) {
                        $q->where('departure_time', '<', $returnTime)
                            ->where('return_time', '>=', $returnTime);
                    })
                    ->orWhere(function ($q) use ($departureTime, $returnTime) {
                        $q->where('departure_time', '>=', $departureTime)
                            ->where('return_time', '<=', $returnTime);
                    });
                });

            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            $overlappingRequest = $query->first();

            if ($overlappingRequest) {
                return [
                    'valid' => false,
                    'message' => 'يوجد تداخل مع طلب استئذان آخر في نفس الوقت'
                ];
            }

            // التحقق من الحد الأقصى للدقائق المسموح بها شهرياً
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();

            $usedMinutes = PermissionRequest::where('user_id', $userId)
                ->whereBetween('departure_time', [$startOfMonth, $endOfMonth])
                ->where('status', 'approved')
                ->sum('minutes_used');

            if (($usedMinutes + $duration) > self::MONTHLY_LIMIT_MINUTES) {
                return [
                    'valid' => false,
                    'message' => 'لقد تجاوزت الحد الأقصى المسموح به للاستئذان هذا الشهر'
                ];
            }

            return [
                'valid' => true,
                'duration' => $duration
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'message' => 'حدث خطأ أثناء التحقق من صحة الطلب'
            ];
        }
    }

    public function canRespond($user = null)
    {
        $user = $user ?? Auth::user();

        if (
            $user->hasRole(['team_leader', 'department_manager', 'company_manager']) &&
            $user->hasPermissionTo('manager_respond_permission_request')
        ) {
            return true;
        }

        if ($user->hasRole('hr') && $user->hasPermissionTo('hr_respond_permission_request')) {
            return true;
        }

        return false;
    }

    public function deleteRequest(PermissionRequest $request)
    {
        $this->notificationService->notifyPermissionDeleted($request);
        $request->delete();
        return ['success' => true];
    }

    public function getUserRequests(int $userId): LengthAwarePaginator
    {
        return PermissionRequest::where('user_id', $userId)
            ->latest()
            ->paginate(10);
    }

    private function getUsedMinutes(int $userId): int
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        return PermissionRequest::where('user_id', $userId)
            ->whereBetween('departure_time', [$startOfMonth, $endOfMonth])
            ->whereIn('status', ['pending', 'approved'])
            ->sum('minutes_used');
    }

    public function getAllowedUsers($user)
    {
        if ($user->hasRole('hr')) {
            return User::whereDoesntHave('roles', function ($q) {
                $q->whereIn('name', ['hr', 'company_manager']);
            })->get();
        }

        if (!$user->currentTeam) {
            return collect();
        }

        $allowedRoles = [];
        if ($user->hasRole('team_leader')) {
            $allowedRoles = ['employee'];
        } elseif ($user->hasRole('department_manager')) {
            $allowedRoles = ['employee', 'team_leader'];
        } elseif ($user->hasRole('company_manager')) {
            $allowedRoles = ['employee', 'team_leader', 'department_manager'];
        }

        return $user->currentTeam->users()
            ->whereHas('roles', function ($q) use ($allowedRoles) {
                $q->whereIn('name', $allowedRoles);
            })
            ->whereDoesntHave('teams', function ($q) use ($user) {
                $q->where('teams.id', $user->currentTeam->id)
                    ->where(function ($q) {
                        $q->where('team_user.role', 'owner')
                            ->orWhere('team_user.role', 'admin');
                    });
            })
            ->get();
    }
}
