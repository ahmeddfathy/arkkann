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
use Illuminate\Support\Facades\Log;

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
        try {
            if (!auth()->user()->hasPermissionTo('create_permission')) {
                return [
                    'success' => false,
                    'message' => 'ليس لديك صلاحية تقديم طلب استئذان'
                ];
            }

            $userId = Auth::id();
            $validation = $this->validateTimeRequest($userId, $data['departure_time'], $data['return_time']);

            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => $validation['message']
                ];
            }

            $currentUser = Auth::user();

            $managerStatus = 'pending';
            $hrStatus = 'pending';
            $status = 'pending';

            // عند إنشاء طلب للنفس، جميع الردود تكون معلقة بغض النظر عن دور المستخدم

            $returnTime = Carbon::parse($data['return_time']);
            $user = User::find($userId);

            // تحديد الحالة النهائية بناءً على ما إذا كان المستخدم لديه فريق أم لا
            // نحافظ على أن جميع الردود تكون معلقة في حالة طلب المستخدم لنفسه، ولكن نضع القاعدة هنا للاتساق
            if ($user && (!$user->teams()->exists() || $user->teams()->where('name', 'HR')->exists())) {
                // إذا كان المستخدم ليس لديه فريق أو كان من فريق HR، فيكفي موافقة HR فقط
                if ($hrStatus === 'approved') {
                    $status = 'approved';
                } elseif ($hrStatus === 'rejected') {
                    $status = 'rejected';
                }
            } else {
                // إذا كان المستخدم لديه فريق، فيتطلب موافقة المدير وHR معًا
                if ($managerStatus === 'approved' && $hrStatus === 'approved') {
                    $status = 'approved';
                } elseif ($managerStatus === 'rejected' || $hrStatus === 'rejected') {
                    $status = 'rejected';
                }
            }

            $workShift = $user->workShift;
            $returnedOnTime = false;

            if ($workShift) {
                $shiftEndTime = Carbon::parse($workShift->check_out_time)->setDateFrom($returnTime);

                if ($returnTime->format('H:i') === $shiftEndTime->format('H:i')) {
                    $returnedOnTime = true;
                }
            }

            $request = PermissionRequest::create([
                'user_id' => $userId,
                'departure_time' => $data['departure_time'],
                'return_time' => $data['return_time'],
                'minutes_used' => $validation['duration'],
                'remaining_minutes' => $this->getRemainingMinutes($userId) - $validation['duration'],
                'reason' => $data['reason'],
                'manager_status' => $managerStatus,
                'hr_status' => $hrStatus,
                'status' => $status,
                'returned_on_time' => $returnedOnTime,
            ]);


            $this->notificationService->createPermissionRequestNotification($request);

            return [
                'success' => true,
                'request_id' => $request->id,
                'message' => 'تم إنشاء طلب الاستئذان بنجاح.',
                'exceeded_limit' => $validation['exceeded_limit'] ?? false
            ];
        } catch (\Exception $e) {
            Log::error('Error creating request: ' . $e->getMessage());
            throw $e;
        }
    }

    public function createRequestForUser(int $userId, array $data): array
    {
        try {
            if (!auth()->user()->hasPermissionTo('create_permission')) {
                return [
                    'success' => false,
                    'message' => 'ليس لديك صلاحية تقديم طلب استئذان'
                ];
            }

            $validation = $this->validateTimeRequest($userId, $data['departure_time'], $data['return_time']);

            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => $validation['message']
                ];
            }

            $user = Auth::user();

            $remainingMinutes = $this->getRemainingMinutes($userId);

            $managerStatus = 'pending';
            $hrStatus = 'pending';
            $status = 'pending';

            if ($user) {
                $roles = $user->getRoleNames();

                if ($roles->contains('team_leader') || $roles->contains('department_manager') || $roles->contains('company_manager')) {
                    $managerStatus = 'approved';
                }

                if ($roles->contains('hr')) {
                    $hrStatus = 'approved';
                }
            }

            // المستخدم المستهدف
            $targetUser = User::find($userId);

            // تحديد الحالة النهائية بناءً على ما إذا كان المستخدم المستهدف لديه فريق أم لا
            if ($targetUser && (!$targetUser->teams()->exists() || $targetUser->teams()->where('name', 'HR')->exists())) {
                // إذا كان المستخدم المستهدف ليس لديه فريق أو كان من فريق HR، فيكفي موافقة HR فقط
                if ($hrStatus === 'approved') {
                    $status = 'approved';
                } elseif ($hrStatus === 'rejected') {
                    $status = 'rejected';
                }
            } else {
                // إذا كان المستخدم المستهدف لديه فريق، فيتطلب موافقة المدير وHR معًا
                if ($managerStatus === 'approved' && $hrStatus === 'approved') {
                    $status = 'approved';
                } elseif ($managerStatus === 'rejected' || $hrStatus === 'rejected') {
                    $status = 'rejected';
                }
            }

            $returnTime = Carbon::parse($data['return_time']);
            $workShift = $targetUser->workShift;
            $returnedOnTime = false;

            if ($workShift) {
                $shiftEndTime = Carbon::parse($workShift->check_out_time)->setDateFrom($returnTime);

                if ($returnTime->format('H:i') === $shiftEndTime->format('H:i')) {
                    $returnedOnTime = true;
                }
            }

            $request = PermissionRequest::create([
                'user_id' => $userId,
                'departure_time' => $data['departure_time'],
                'return_time' => $data['return_time'],
                'minutes_used' => $validation['duration'],
                'remaining_minutes' => $remainingMinutes - $validation['duration'],
                'reason' => $data['reason'],
                'manager_status' => $managerStatus,
                'hr_status' => $hrStatus,
                'status' => $status,
                'returned_on_time' => $returnedOnTime,
            ]);

            // إرسال إشعار بغض النظر عن حالة الطلب
            $this->notificationService->createPermissionRequestNotification($request);

            return [
                'success' => true,
                'request_id' => $request->id,
                'message' => 'تم إنشاء طلب الاستئذان بنجاح للموظف.',
                'exceeded_limit' => $validation['exceeded_limit'] ?? false
            ];
        } catch (\Exception $e) {
            Log::error('Error creating request for user: ' . $e->getMessage());
            throw $e;
        }
    }

    public function updateRequest(PermissionRequest $request, array $data): array
    {
        try {
            if (!auth()->user()->hasPermissionTo('update_permission')) {
                return [
                    'success' => false,
                    'message' => 'ليس لديك صلاحية تعديل طلب الاستئذان'
                ];
            }

            if ($request->status !== 'pending' || auth()->id() !== $request->user_id) {
                return [
                    'success' => false,
                    'message' => 'لا يمكن تعديل هذا الطلب'
                ];
            }

            $validation = $this->validateTimeRequest($request->user_id, $data['departure_time'], $data['return_time'], $request->id);

            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => $validation['message']
                ];
            }

            $returnTime = Carbon::parse($data['return_time']);
            $user = User::find($request->user_id);
            $workShift = $user->workShift;
            $returnedOnTime = false;

            if ($workShift) {
                $shiftEndTime = Carbon::parse($workShift->check_out_time)->setDateFrom($returnTime);

                if ($returnTime->format('H:i') === $shiftEndTime->format('H:i')) {
                    $returnedOnTime = true;
                }
            }

            $remainingMinutes = $this->getRemainingMinutes($request->user_id);
            $minutesUsed = $validation['duration'];
            $oldMinutesUsed = $request->minutes_used;

            $updateData = [
                'departure_time' => $data['departure_time'],
                'return_time' => $data['return_time'],
                'minutes_used' => $minutesUsed,
                'remaining_minutes' => $remainingMinutes + ($oldMinutesUsed - $minutesUsed),
                'reason' => $data['reason']
            ];

            if ($returnedOnTime !== null) {
                $updateData['returned_on_time'] = $returnedOnTime;
            }

            $request->update($updateData);

            // إضافة إشعار عند تعديل الطلب
            $this->notificationService->notifyPermissionModified($request);

            return ['success' => true];
        } catch (\Exception $e) {
            Log::error('Error updating request: ' . $e->getMessage());
            throw $e;
        }
    }

    public function updateStatus(PermissionRequest $request, array $data): array
    {
        $responseType = $data['response_type'];
        $status = $data['status'];
        $rejectionReason = $status === 'rejected' ? $data['rejection_reason'] : null;

        // التحقق من الصلاحيات حسب نوع الرد
        if ($responseType === 'manager' && !auth()->user()->hasPermissionTo('manager_respond_permission_request')) {
            return [
                'success' => false,
                'message' => 'ليس لديك صلاحية الرد على طلبات الاستئذان كمدير'
            ];
        }

        if ($responseType === 'hr' && !auth()->user()->hasPermissionTo('hr_respond_permission_request')) {
            return [
                'success' => false,
                'message' => 'ليس لديك صلاحية الرد على طلبات الاستئذان كموارد بشرية'
            ];
        }

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
        try {
            // التحقق من الصلاحيات حسب نوع الرد
            if ($responseType === 'manager' && !auth()->user()->hasPermissionTo('manager_respond_permission_request')) {
                throw new \Illuminate\Auth\Access\AuthorizationException(
                    'ليس لديك صلاحية إعادة تعيين الرد على طلبات الاستئذان كمدير'
                );
            }

            if ($responseType === 'hr' && !auth()->user()->hasPermissionTo('hr_respond_permission_request')) {
                throw new \Illuminate\Auth\Access\AuthorizationException(
                    'ليس لديك صلاحية إعادة تعيين الرد على طلبات الاستئذان كموارد بشرية'
                );
            }

            if ($responseType === 'manager') {
                $request->updateManagerStatus('pending', null);
                $request->updateFinalStatus();
                $request->save();
                $this->notificationService->notifyManagerResponseDeleted($request);
            } elseif ($responseType === 'hr') {
                $request->updateHrStatus('pending', null);
                $request->updateFinalStatus();
                $request->save();
                $this->notificationService->notifyStatusReset($request, 'hr');
            }

            return $request;
        } catch (\Exception $e) {
            Log::error('Error resetting status: ' . $e->getMessage());
            throw $e;
        }
    }

    public function modifyResponse(PermissionRequest $request, array $data): array
    {
        if (isset($data['status'])) {
            $status = $data['status'];
            $rejectionReason = $status === 'rejected' ? ($data['rejection_reason'] ?? null) : null;

            $request->updateManagerStatus($status, $rejectionReason);
            $request->save();

            $this->notificationService->notifyManagerStatusUpdate($request);
        }

        return ['success' => true];
    }

    public function updateReturnStatus(PermissionRequest $request, int $returnStatus): array
    {
        try {
            $now = Carbon::now()->setTimezone('Africa/Cairo');
            $departureTime = Carbon::parse($request->departure_time);
            $returnTime = Carbon::parse($request->return_time);

            // Get the specific shift end time for this user
            $user = User::find($request->user_id);
            if ($user && $user->workShift) {
                $shiftEndTime = Carbon::parse($user->workShift->check_out_time)->setDateFrom($departureTime);
            } else {
                // Use default if no work shift found (4:00 PM)
                $shiftEndTime = Carbon::parse($departureTime)->setTime(16, 0, 0);
            }

            // لا نقوم بتسجيل "لم يرجع" تلقائياً عند انتهاء الوردية
            // بل فقط نتأكد من أن حساب الدقائق يكون حتى نهاية الوردية
            // يجب أن يقوم المدير يدوياً بالضغط على زر "لم يرجع" لتسجيل مخالفة

            // Handle different return status values
            if ($returnTime->format('H:i') === $shiftEndTime->format('H:i')) {
                // If return time is same as shift end, mark as returned
                $request->returned_on_time = true;
            } else if ($returnStatus == 1) {
                // Employee marked themselves as returned
                $request->returned_on_time = true;

                // تحديد وقت العودة الفعلي بالوقت الحالي أو نهاية الوردية أيهما أسبق
                if ($now->gt($shiftEndTime)) {
                    $request->actual_return_time = $shiftEndTime;
                    Log::info('Employee returned after shift end time - using shift end time', [
                        'request_id' => $request->id,
                        'now' => $now->format('Y-m-d H:i:s'),
                        'shift_end_time' => $shiftEndTime->format('Y-m-d H:i:s')
                    ]);
                } else {
                    $request->actual_return_time = $now;
                    Log::info('Employee returned before shift end time - using current time', [
                        'request_id' => $request->id,
                        'now' => $now->format('Y-m-d H:i:s')
                    ]);
                }
            } else if ($returnStatus == 0) {
                // Reset status
                $request->returned_on_time = false;
                $request->actual_return_time = null;
            } else if ($returnStatus == 2) {
                // Not returned - explicit action by manager or employee
                $request->returned_on_time = 2;
                $request->actual_return_time = null;

                // Create violation record only when explicitly marked as not returned
                $this->violationService->handleReturnViolation(
                    $request,
                    $returnStatus
                );
            }

            // Update minutes used based on return status
            $request->updateActualMinutesUsed();

            $request->save();

            // Send notification
            $this->notificationService->notifyReturnStatus($request);

            $message = 'تم تحديث حالة العودة بنجاح';
            if ($returnStatus == 1) {
                $message = 'تم تسجيل عودتك بنجاح';
            } else if ($returnStatus == 2) {
                $message = 'تم تسجيل عدم العودة';
            } else if ($returnStatus == 0) {
                $message = 'تم إعادة تعيين حالة العودة بنجاح';
            }

            return [
                'success' => true,
                'message' => $message,
                'actual_minutes_used' => $request->minutes_used
            ];
        } catch (\Exception $e) {
            Log::error('Error updating return status: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث حالة العودة: ' . $e->getMessage()
            ];
        }
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
        $departureDateTime = Carbon::parse($departureTime);
        $returnDateTime = Carbon::parse($returnTime);
        $duration = $departureDateTime->diffInMinutes($returnDateTime);

        $user = User::find($userId);
        $workShift = $user->workShift;

        if ($workShift) {
            $shiftStartTime = Carbon::parse($workShift->check_in_time)->setDateFrom($departureDateTime);
            $shiftEndTime = Carbon::parse($workShift->check_out_time)->setDateFrom($departureDateTime);
        } else {
            $shiftStartTime = Carbon::parse($departureTime)->setTime(8, 0, 0);
            $shiftEndTime = Carbon::parse($departureTime)->setTime(16, 0, 0);
        }

        Log::info('Validating time request', [
            'user_id' => $userId,
            'departure_time' => $departureTime,
            'return_time' => $returnTime,
            'shift_start_time' => $shiftStartTime->format('Y-m-d H:i:s'),
            'shift_end_time' => $shiftEndTime->format('Y-m-d H:i:s')
        ]);

        if ($departureDateTime->greaterThanOrEqualTo($returnDateTime)) {
            return [
                'valid' => false,
                'message' => 'وقت المغادرة يجب أن يكون قبل وقت العودة.',
                'duration' => $duration,
                'exceeded_limit' => false
            ];
        }

        if ($departureDateTime->lessThan($shiftStartTime)) {
            return [
                'valid' => false,
                'message' => 'وقت المغادرة يجب أن يكون بعد بداية الوردية (' . $shiftStartTime->format('h:i A') . ').',
                'duration' => $duration,
                'exceeded_limit' => false
            ];
        }

        if ($returnDateTime->greaterThan($shiftEndTime)) {
            return [
                'valid' => false,
                'message' => 'وقت العودة يجب أن يكون قبل نهاية الوردية (' . $shiftEndTime->format('h:i A') . ').',
                'duration' => $duration,
                'exceeded_limit' => false
            ];
        }

        if ($departureDateTime->diffInMinutes($returnDateTime) > 180) {
            return [
                'valid' => false,
                'message' => 'مدة الاستئذان يجب أن لا تزيد عن 3 ساعات.',
                'duration' => $duration,
                'exceeded_limit' => false
            ];
        }

        $overlappingRequests = PermissionRequest::where('user_id', $userId)
            ->where('status', '!=', 'rejected')
            ->where(function ($query) use ($departureTime, $returnTime) {
                $query->where(function ($query) use ($departureTime, $returnTime) {
                    $query->where('departure_time', '<=', $departureTime)
                        ->where('return_time', '>=', $departureTime);
                })->orWhere(function ($query) use ($departureTime, $returnTime) {
                    $query->where('departure_time', '<=', $returnTime)
                        ->where('return_time', '>=', $returnTime);
                })->orWhere(function ($query) use ($departureTime, $returnTime) {
                    $query->where('departure_time', '>=', $departureTime)
                        ->where('return_time', '<=', $returnTime);
                });
            });

        if ($excludeId !== null) {
            $overlappingRequests->where('id', '!=', $excludeId);
        }

        $count = $overlappingRequests->count();

        if ($count > 0) {
            return [
                'valid' => false,
                'message' => 'هناك تعارض مع طلب استئذان آخر في نفس الوقت.',
                'duration' => $duration,
                'exceeded_limit' => false
            ];
        }

        $remainingMinutes = $this->getRemainingMinutes($userId);
        $requestedMinutes = $departureDateTime->diffInMinutes($returnDateTime);

        if ($requestedMinutes > $remainingMinutes) {
            return [
                'valid' => true,
                'message' => "لقد استنفدت الحد الأقصى المسموح به من الاستئذان الشهري. المتبقي: {$remainingMinutes} دقيقة.",
                'duration' => $duration,
                'exceeded_limit' => true
            ];
        }

        return [
            'valid' => true,
            'duration' => $duration,
            'exceeded_limit' => false
        ];
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
        if (!auth()->user()->hasPermissionTo('delete_permission')) {
            throw new \Illuminate\Auth\Access\AuthorizationException(
                'ليس لديك صلاحية حذف طلب الاستئذان'
            );
        }

        if ($request->status !== 'pending' || auth()->id() !== $request->user_id) {
            throw new \Illuminate\Auth\Access\AuthorizationException(
                'لا يمكن حذف هذا الطلب'
            );
        }

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
