<?php

namespace App\Services;

use App\Models\PermissionRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Services\ViolationService;
use App\Services\NotificationPermissionService;
use App\Models\User;

class PermissionRequestService
{
    protected $violationService;
    protected $notificationService;
    const MONTHLY_LIMIT_MINUTES = 180; // 3 ساعات في الشهر

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

        // تطبيق الفلاتر
        if (!empty($filters['employee_name'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['employee_name'] . '%');
            });
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        // التحقق من صلاحيات المستخدم
        if ($user->hasRole('hr')) {
            // جلب طلبات الموظفين الذين ليسوا في أي فريق
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

        // للموظفين العاديين
        return $query->where('user_id', $user->id)
            ->latest()
            ->paginate(10);
    }

    public function createRequest(array $data): array
    {
        $userId = Auth::id();

        // التحقق من صحة الوقت والدقائق المتبقية
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

        // إنشاء الطلب
        $request = PermissionRequest::create([
            'user_id' => $userId,
            'departure_time' => $data['departure_time'],
            'return_time' => $data['return_time'],
            'minutes_used' => $validation['duration'],
            'reason' => $data['reason'],
            'remaining_minutes' => $remainingMinutes - $validation['duration'],
            'status' => 'pending',
            'manager_status' => 'pending',
            'hr_status' => 'pending',
            'returned_on_time' => false,
        ]);

        // إرسال إشعار
        $this->notificationService->createPermissionRequestNotification($request);

        return ['success' => true];
    }

    public function createRequestForUser(int $userId, array $data): array
    {
        $user = Auth::user();

        // التحقق من صحة الوقت والدقائق المتبقية
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

        // تحديد الحالات الأولية بناءً على دور المستخدم
        $managerStatus = $user->hasRole(['team_leader', 'department_manager', 'company_manager']) ? 'approved' : 'pending';
        $hrStatus = $user->hasRole('hr') ? 'approved' : 'pending';

        // إنشاء الطلب
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

        // تحديث الحالة النهائية
        $request->updateFinalStatus();
        $request->save();

        // إرسال إشعار
        $this->notificationService->createPermissionRequestNotification($request);

        return [
            'success' => true,
            'used_minutes' => $validation['used_minutes'],
            'remaining_minutes' => $validation['remaining_minutes']
        ];
    }

    public function updateRequest(PermissionRequest $request, array $data): array
    {
        // التحقق من صحة الوقت
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

        // تحديث الطلب
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

    private function validateTimeRequest(int $userId, string $departureTime, string $returnTime, ?int $excludeRequestId = null): array
    {
        if (Carbon::parse($returnTime) <= Carbon::parse($departureTime)) {
            return [
                'valid' => false,
                'message' => 'Return time must be after departure time.'
            ];
        }

        $duration = Carbon::parse($departureTime)->diffInMinutes(Carbon::parse($returnTime));

        if ($this->hasTimeOverlap($userId, $departureTime, $returnTime, $excludeRequestId)) {
            return [
                'valid' => false,
                'message' => 'You already have a permission request during this time period.'
            ];
        }

        // حساب الدقائق المستخدمة للعرض فقط - بدون منع الطلب
        $usedMinutes = $this->getUsedMinutes($userId);
        $remainingMinutes = self::MONTHLY_LIMIT_MINUTES - $usedMinutes;

        return [
            'valid' => true,
            'duration' => $duration,
            'used_minutes' => $usedMinutes,
            'remaining_minutes' => max(0, $remainingMinutes)
        ];
    }

    private function hasTimeOverlap(int $userId, string $departureTime, string $returnTime, ?int $excludeRequestId = null): bool
    {
        $query = PermissionRequest::where('user_id', $userId)
            ->where(function ($query) use ($departureTime, $returnTime) {
                $query->where('departure_time', '<=', $returnTime)
                    ->where('return_time', '>=', $departureTime);
            })
            ->whereIn('status', ['pending', 'approved']);

        if ($excludeRequestId) {
            $query->where('id', '!=', $excludeRequestId);
        }

        return $query->exists();
    }

    public function canRespond($user = null)
    {
        $user = $user ?? Auth::user();

        // التحقق من صلاحيات المديرين
        if (
            $user->hasRole(['team_leader', 'department_manager', 'company_manager']) &&
            $user->hasPermissionTo('manager_respond_permission_request')
        ) {
            return true;
        }

        // التحقق من صلاحيات HR
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

    // دالة جديدة لحساب الدقائق المستخدمة
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
        // إذا كان المستخدم HR، يمكنه رؤية جميع المستخدمين ما عدا HR و company_manager
        if ($user->hasRole('hr')) {
            return User::whereDoesntHave('roles', function ($q) {
                $q->whereIn('name', ['hr', 'company_manager']);
            })->get();
        }

        // للمدراء، نستخدم المنطق القديم
        if (!$user->currentTeam) {
            return collect();
        }

        // تحديد الأدوار المسموح بها حسب دور المستخدم
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
