<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class AbsenceRequest extends Model
{
  protected $fillable = [
    'user_id',
    'absence_date',
    'reason',
    'status'
  ];

  protected $dates = [
    'absence_date'
  ];

  protected $casts = [
    'absence_date' => 'datetime',

  ];


  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  // التحقق من إمكانية الرد على الطلب
  public function canRespond(User $user): bool
  {
    // HR يمكنه الرد على أي طلب
    if ($user->hasRole('hr') && $user->hasPermissionTo('hr_respond_absence_request')) {
      return true;
    }

    // التحقق من ألاحيات المدير
    if (
      $user->hasAnyRole(['team_leader', 'department_manager', 'company_manager'])
      && $user->hasPermissionTo('manager_respond_absence_request')
    ) {
      return true;
    }

    return false;
  }

  // التحقق من إمكانية إنشاء طلب
  public function canCreate(User $user): bool
  {
    return $user->hasPermissionTo('create_absence');
  }

  // التحقق من إمكانية تعديل الطلب
  public function canUpdate(User $user): bool
  {
    if (!$user->hasPermissionTo('update_absence')) {
      return false;
    }
    return $user->id === $this->user_id && $this->status === 'pending';
  }

  // التحقق من إمكانية حذف الطلب
  public function canDelete(User $user): bool
  {
    if (!$user->hasPermissionTo('delete_absence')) {
      return false;
    }
    return $user->id === $this->user_id && $this->status === 'pending';
  }

  // التحقق من إمكانية تعديل الرد
  public function canModifyResponse(User $user): bool
  {
    try {
      // HR يمكنه الرد على أي طلب
      if ($user->hasRole('hr') && $user->hasPermissionTo('hr_respond_absence_request')) {
        return true;
      }

      // التحقق من صلاحيات المدير
      if (
        $user->hasAnyRole(['team_leader', 'department_manager', 'company_manager'])
        && $user->hasPermissionTo('manager_respond_absence_request')
      ) {

        // التحقق من أن المستخدم هو مالك أو مدير في الفريق
        if ($this->user && $this->user->currentTeam) {
          // طريقة 1: التحقق من خلال العلاقة المباشرة
          if ($this->user->currentTeam->user_id === $user->id) {
            return true; // المستخدم هو مالك الفريق
          }

          // طريقة 2: التحقق من خلال جدول team_user
          $isTeamOwnerOrAdmin = DB::table('team_user')
            ->where('team_user.user_id', $user->id)
            ->where('team_user.team_id', $this->user->currentTeam->id)
            ->whereIn('team_user.role', ['owner', 'admin'])
            ->exists();

          return $isTeamOwnerOrAdmin;
        }
      }

      return false;
    } catch (\Exception $e) {
      \Log::error('Error in canModifyResponse: ' . $e->getMessage(), [
        'user_id' => $user->id,
        'request_id' => $this->id,
        'team_id' => $this->user->currentTeam->id ?? null
      ]);
      return false;
    }
  }

  // تحديث حالة المدير
  public function updateManagerStatus(string $status, ?string $rejectionReason = null): void
  {
    $this->manager_status = $status;
    $this->manager_rejection_reason = $rejectionReason;
    $this->updateFinalStatus();
    $this->save();
  }

  // تحديث حالة HR
  public function updateHrStatus(string $status, ?string $rejectionReason = null): void
  {
    $this->hr_status = $status;
    $this->hr_rejection_reason = $rejectionReason;
    $this->updateFinalStatus();
    $this->save();
  }

  // تحديث الحالة النهائية
  public function updateFinalStatus(): void
  {
    // إذا كان أحد الردود مرفوض، الطلب مرفوض
    if ($this->manager_status === 'rejected' || $this->hr_status === 'rejected') {
      $this->status = 'rejected';
      return;
    }

    // إذا كان كلا الردين موافق، الطلب موافق
    if ($this->manager_status === 'approved' && $this->hr_status === 'approved') {
      $this->status = 'approved';
      return;
    }

    // في أي حالة أخرى، الطلب معلق
    $this->status = 'pending';
  }
}
