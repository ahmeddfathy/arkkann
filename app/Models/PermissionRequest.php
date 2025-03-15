<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PermissionRequest extends Model
{
  protected $fillable = [
    'user_id',
    'departure_time',
    'return_time',
    'returned_on_time',
    'minutes_used',
    'remaining_minutes',
    'reason',
    'manager_status',
    'manager_rejection_reason',
    'hr_status',
    'hr_rejection_reason',
    'status'
  ];

  protected $casts = [
    'departure_time' => 'datetime',
    'return_time' => 'datetime'
  ];

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  /**
   * الحصول على وردية المستخدم المرتبط بطلب الاستئذان
   */
  public function userWorkShift()
  {
    return $this->user->workShift;
  }

  /**
   * الحصول على وقت نهاية الوردية للمستخدم
   */
  public function getShiftEndTime()
  {
    $workShift = $this->userWorkShift();

    if ($workShift) {
      return \Carbon\Carbon::parse($workShift->check_out_time);
    }

    // إذا لم يكن للمستخدم وردية محددة، استخدم الوقت الافتراضي (4:00 PM)
    return \Carbon\Carbon::now()->setTimezone('Africa/Cairo')->setTime(16, 0, 0);
  }

  public function violations()
  {
    return $this->hasMany(Violation::class, 'permission_requests_id');
  }

  public function canRespond(User $user): bool
  {
    if ($user->hasRole('hr') && $user->hasPermissionTo('hr_respond_permission_request')) {
      return true;
    }

    if ($user->hasPermissionTo('manager_respond_permission_request')) {
      if ($this->user && $this->user->teams()->exists()) {
        return DB::table('team_user')
          ->where('user_id', $user->id)
          ->where('team_id', $this->user->currentTeam->id)
          ->where(function ($query) {
            $query->where('role', 'admin')
              ->orWhere('role', 'owner');
          })
          ->exists();
      }
    }

    return false;
  }

  public function canCreate(User $user): bool
  {
    return $user->hasPermissionTo('create_permission');
  }

  public function canUpdate(User $user): bool
  {
    if (!$user->hasPermissionTo('update_permission')) {
      return false;
    }
    return $user->id === $this->user_id && $this->status === 'pending';
  }

  public function canDelete(User $user): bool
  {
    if (!$user->hasPermissionTo('delete_permission')) {
      return false;
    }
    return $user->id === $this->user_id && $this->status === 'pending';
  }

  public function canModifyResponse(User $user): bool
  {
    if ($user->hasRole('hr') && $user->hasPermissionTo('hr_respond_permission_request')) {
      return true;
    }

    if (!$user->hasPermissionTo('manager_respond_permission_request')) {
      return false;
    }

    if ($user->hasRole(['team_leader', 'department_manager', 'company_manager'])) {
      return DB::table('team_user')
        ->where('user_id', $user->id)
        ->where('team_id', $this->user->currentTeam->id)
        ->where(function ($query) {
          $query->where('role', 'admin')
            ->orWhere('role', 'owner');
        })
        ->exists();
    }

    return false;
  }

  public function updateManagerStatus(string $status, ?string $rejectionReason = null): void
  {
    $this->manager_status = $status;
    $this->manager_rejection_reason = $rejectionReason;
    $this->updateFinalStatus();
    $this->save();
  }

  public function updateHrStatus(string $status, ?string $rejectionReason = null): void
  {
    $this->hr_status = $status;
    $this->hr_rejection_reason = $rejectionReason;
    $this->updateFinalStatus();
    $this->save();
  }

  public function updateFinalStatus(): void
  {
    if ($this->user && (!$this->user->teams()->exists() || $this->user->teams()->where('name', 'HR')->exists())) {
      if ($this->hr_status === 'rejected') {
        $this->status = 'rejected';
      } elseif ($this->hr_status === 'approved') {
        $this->status = 'approved';
      } else {
        $this->status = 'pending';
      }
    } else {
      if ($this->manager_status === 'rejected' || $this->hr_status === 'rejected') {
        $this->status = 'rejected';
      } elseif ($this->manager_status === 'approved' && $this->hr_status === 'approved') {
        $this->status = 'approved';
      } else {
        $this->status = 'pending';
      }
    }
  }

  public function isApproved(): bool
  {
    return $this->status === 'approved';
  }

  public function getReturnStatusLabel(): string
  {
    Log::info('getReturnStatusLabel for request #' . $this->id, [
      'request_id' => $this->id,
      'returned_on_time' => $this->returned_on_time,
      'returned_on_time_type' => gettype($this->returned_on_time),
      'as_int' => (int)$this->returned_on_time,
      'is_null' => $this->returned_on_time === null
    ]);

    // تعديل الشرط لمعالجة حالة returned_on_time = 2 و returned_on_time = 0 بشكل صحيح
    if ($this->returned_on_time === 2 || $this->returned_on_time == 2) {
      return 'لم يعد في الوقت المحدد';
    } elseif ($this->returned_on_time === 1 || $this->returned_on_time === true) {
      return 'عاد في الوقت المحدد';
    } elseif ($this->returned_on_time === 0 || $this->returned_on_time === null) {
      return 'غير محدد';
    } else {
      return 'غير محدد';
    }
  }

  public function calculateMinutesUsed()
  {
    return $this->departure_time->diffInMinutes($this->return_time);
  }

  public function calculateRemainingMinutes()
  {
    $totalAllowed = 180;
    $startOfMonth = Carbon::now()->startOfMonth();
    $endOfMonth = Carbon::now()->endOfMonth();

    $usedMinutes = self::where('user_id', $this->user_id)
      ->whereBetween('departure_time', [$startOfMonth, $endOfMonth])
      ->where('status', 'approved')
      ->sum('minutes_used');

    return $totalAllowed - $usedMinutes;
  }

  public function getFormattedDuration(): string
  {
    $minutes = $this->minutes_used;
    $hours = floor($minutes / 60);
    $remainingMinutes = $minutes % 60;

    if ($hours > 0) {
      return sprintf('%d ساعة %d دقيقة', $hours, $remainingMinutes);
    }

    return sprintf('%d دقيقة', $minutes);
  }

  public function calculateActualMinutesUsed(): int
  {
    $now = Carbon::now()->setTimezone('Africa/Cairo');
    $departure = $this->departure_time;
    $scheduledReturn = $this->return_time;
    $maxReturnTime = $scheduledReturn->copy();
    $endOfWorkDay = Carbon::now()->setTimezone('Africa/Cairo')->setTime(16, 0, 0);

    Log::info('Calculating actual minutes used', [
      'request_id' => $this->id,
      'departure_time' => $departure->format('Y-m-d H:i:s'),
      'scheduled_return' => $scheduledReturn->format('Y-m-d H:i:s'),
      'current_time' => $now->format('Y-m-d H:i:s'),
      'return_status' => $this->returned_on_time,
      'max_return_time' => $maxReturnTime->format('Y-m-d H:i:s'),
      'end_of_work_day' => $endOfWorkDay->format('Y-m-d H:i:s')
    ]);

    if ($scheduledReturn->gte($endOfWorkDay)) {
      $scheduledReturn = $endOfWorkDay;
      Log::info('Using end of work day as return time', [
        'request_id' => $this->id,
        'end_of_work_day' => $endOfWorkDay->format('Y-m-d H:i:s')
      ]);
    }

    $minutesUsed = 0;

    if ($this->returned_on_time === true || $this->returned_on_time === 1) {
      if ($now->lte($scheduledReturn)) {
        $minutesUsed = $departure->diffInMinutes($now);
        Log::info('Returned on time - using current time', [
          'request_id' => $this->id,
          'minutes_used' => $minutesUsed,
          'current_time' => $now->format('Y-m-d H:i:s')
        ]);
      } else {
        $minutesUsed = $departure->diffInMinutes($now);
        Log::info('Returned late - using actual late time', [
          'request_id' => $this->id,
          'minutes_used' => $minutesUsed,
          'current_time' => $now->format('Y-m-d H:i:s'),
          'expected_return_time' => $scheduledReturn->format('Y-m-d H:i:s')
        ]);
      }
    }
    else if ($this->returned_on_time === 2) {
      $minutesUsed = $departure->diffInMinutes($endOfWorkDay);
      Log::info('Marked as not returned - using end of work day', [
        'request_id' => $this->id,
        'minutes_used' => $minutesUsed,
        'end_of_work_day' => $endOfWorkDay->format('Y-m-d H:i:s')
      ]);
    }
    else if ($this->returned_on_time === null || $this->returned_on_time === 0) {
      if ($now->gt($scheduledReturn)) {
        $minutesUsed = $departure->diffInMinutes($now);
        Log::info('Not returned and past return time - using current time', [
          'request_id' => $this->id,
          'minutes_used' => $minutesUsed,
          'current_time' => $now->format('Y-m-d H:i:s')
        ]);
      } else {
        $minutesUsed = $departure->diffInMinutes($scheduledReturn);
        Log::info('Not returned yet - using scheduled return time', [
          'request_id' => $this->id,
          'minutes_used' => $minutesUsed,
          'scheduled_return' => $scheduledReturn->format('Y-m-d H:i:s')
        ]);
      }
    }
    else {
      $minutesUsed = $departure->diffInMinutes($scheduledReturn);
      Log::info('Default case - using scheduled return time', [
        'request_id' => $this->id,
        'minutes_used' => $minutesUsed,
        'scheduled_return' => $scheduledReturn->format('Y-m-d H:i:s')
      ]);
    }

    return $minutesUsed;
  }

  public function updateActualMinutesUsed(): void
  {
    $oldMinutes = $this->minutes_used;
    $newMinutes = $this->calculateActualMinutesUsed();

    Log::info('Updating actual minutes used', [
      'request_id' => $this->id,
      'old_minutes' => $oldMinutes,
      'new_minutes' => $newMinutes,
      'difference' => $newMinutes - $oldMinutes
    ]);

    $this->minutes_used = $newMinutes;
    $this->save();

    Log::info('Minutes updated successfully', [
      'request_id' => $this->id,
      'final_minutes' => $this->minutes_used
    ]);
  }

  public function canMarkAsReturned(User $user): bool
  {
    // إذا كان المستخدم ليس صاحب الطلب، لا يمكنه تسجيل العودة
    if ($user->id !== $this->user_id) {
      return false;
    }

    // إذا لم تتم الموافقة على الطلب، لا يمكن تسجيل العودة
    if ($this->status !== 'approved') {
      return false;
    }

    // إذا تم تسجيل العودة مسبقًا (سواء عاد أو لم يعد) ولم تكن 0، لا يمكن تسجيل العودة مرة أخرى
    if ($this->returned_on_time === true || $this->returned_on_time === 1 || $this->returned_on_time === 2) {
      return false;
    }

    // يمكن للموظف تسجيل عودته طالما أن وقت المغادرة قد بدأ
    $now = \Carbon\Carbon::now()->setTimezone('Africa/Cairo');
    $departureTime = \Carbon\Carbon::parse($this->departure_time);

    // يمكن تسجيل العودة فقط بعد وقت المغادرة
    return $now->gte($departureTime);
  }

  public function canResetReturnStatus(User $user): bool
  {
    // يتم التحقق فقط من أن المستخدم هو صاحب الطلب
    if ($user->id !== $this->user_id) {
      return false;
    }

    // يجب أن يكون الطلب موافق عليه
    if ($this->status !== 'approved') {
      return false;
    }

    // يجب أن تكون حالة العودة محددة مسبقًا (سواء عاد أو لم يعد) وليست 0
    if ($this->returned_on_time !== true && $this->returned_on_time !== 2 && $this->returned_on_time !== 0) {
      return false;
    }

    // السماح بإعادة التعيين في أي وقت بدون قيود زمنية
    return true;
  }

  public function isReturnTimePassed(): bool
  {
    $now = \Carbon\Carbon::now()->setTimezone('Africa/Cairo');
    $returnTime = \Carbon\Carbon::parse($this->return_time);

    // استخدام وقت العودة المحدد في الطلب أو وقت نهاية الوردية أيهما أقل
    $maxReturnTime = min($returnTime, $this->getShiftEndTime());


    if ($returnTime->format('H:i') === $this->getShiftEndTime()->format('H:i')) {
      Log::info('Return time is end of shift', [
        'request_id' => $this->id,
        'return_time' => $returnTime->format('Y-m-d H:i:s'),
        'shift_end_time' => $this->getShiftEndTime()->format('Y-m-d H:i:s')
      ]);

      // إذا لم يكن هناك حالة محددة بالفعل، فقم بتعيين حالة العودة تلقائيًا
      if ($this->returned_on_time === false) {
        $this->returned_on_time = true; // عاد في الوقت المحدد
        $this->save();
      }

      return true;
    }

    return $now->gt($maxReturnTime);
  }

  public function shouldShowCountdown(): bool
  {
    Log::info('shouldShowCountdown check for request #' . $this->id, [
      'request_id' => $this->id,
      'returned_on_time' => $this->returned_on_time,
      'returned_on_time_type' => gettype($this->returned_on_time),
      'is_null' => $this->returned_on_time === null,
      'departure_time' => $this->departure_time,
      'return_time' => $this->return_time,
      'now' => \Carbon\Carbon::now()->setTimezone('Africa/Cairo')->format('Y-m-d H:i:s'),
      'shift_end_time' => $this->getShiftEndTime()->format('Y-m-d H:i:s')
    ]);

    // الشرط الأساسي: إذا كانت حالة العودة مسجلة (1 أو 2)، لا نعرض العداد
    if ($this->returned_on_time == true || $this->returned_on_time == 2) {
      Log::info('shouldShowCountdown returning false due to returned_on_time being true or 2', [
        'request_id' => $this->id,
        'returned_on_time' => $this->returned_on_time
      ]);
      return false;
    }

    // إذا لم يتم تحديد وقت العودة، فلا داعي لإظهار العد التنازلي
    if (!$this->return_time) {
      return false;
    }

    // إذا كان وقت العودة هو نفس وقت نهاية الوردية، لا تعرض العد التنازلي
    $returnTime = \Carbon\Carbon::parse($this->return_time);
    if ($returnTime->format('H:i') === $this->getShiftEndTime()->format('H:i')) {
      return false;
    }

    $now = \Carbon\Carbon::now()->setTimezone('Africa/Cairo');
    $departureTime = \Carbon\Carbon::parse($this->departure_time);

    // إذا كانت حالة returned_on_time تساوي 0 وتمت الموافقة على الطلب، نعرض العداد بغض النظر عن التاريخ
    // هذا يسمح بعرض العداد للطلبات المستقبلية في واجهة المستخدم
    if (($this->returned_on_time === 0 || $this->returned_on_time === null) && $this->isApproved()) {
      // في حالة التشغيل الفعلي (وليس العرض فقط)، نتحقق من الوقت
      if (request()->is('*check-end-of-day*') || request()->isMethod('post')) {
        // استخدام وقت العودة المحدد في الطلب أو وقت نهاية الوردية أيهما أقل
        $maxReturnTime = min($returnTime, $this->getShiftEndTime());

        // لا تعرض العد التنازلي إذا لم يبدأ الاستئذان بعد
        if ($now->lt($departureTime)) {
          return false;
        }

        // تعرض العد التنازلي فقط إذا كان الوقت الحالي بين وقت المغادرة ووقت العودة (أو نهاية الوردية)
        return $now->gte($departureTime) && $now->lte($maxReturnTime);
      }

      // للعرض في الواجهة، نعرض العداد بغض النظر عن الوقت
      return true;
    }

    // لغير ذلك، نستخدم المنطق الأصلي
    // استخدام وقت العودة المحدد في الطلب أو وقت نهاية الوردية أيهما أقل
    $maxReturnTime = min($returnTime, $this->getShiftEndTime());

    // لا تعرض العد التنازلي إذا لم يبدأ الاستئذان بعد
    if ($now->lt($departureTime)) {
      return false;
    }

    // تعرض العد التنازلي فقط إذا كان الوقت الحالي بين وقت المغادرة ووقت العودة (أو نهاية الوردية)
    return $now->gte($departureTime) && $now->lte($maxReturnTime);
  }

  public function canView(User $user): bool
  {
    // صاحب الطلب يمكنه رؤية طلبه
    if ($user->id === $this->user_id) {
      return true;
    }

    // التحقق من صلاحية عرض الطلبات
    return $user->hasPermissionTo('view_permission');
  }

  public function canRespondAsManager(User $user): bool
  {
    // يمكن الرد كمدير إذا كان:
    // 1. المستخدم لديه صلاحية الرد كمدير
    // 2. المستخدم ليس صاحب الطلب
    return $user->hasPermissionTo('manager_respond_permission_request') &&
           $user->id !== $this->user_id;
  }

  public function canRespondAsHR(User $user): bool
  {

    return $user->hasPermissionTo('hr_respond_permission_request') &&
           $user->id !== $this->user_id;
  }

  public function canResetManagerResponse(User $user): bool
  {
    return $user->hasPermissionTo('manager_respond_permission_request') &&
           $this->manager_status !== 'pending';
  }

  public function canResetHRResponse(User $user): bool
  {
    return $user->hasPermissionTo('hr_respond_permission_request') &&
           $this->hr_status !== 'pending';
  }

  public function markAsNotReturnedAtEndOfShift(): bool
  {
    $now = \Carbon\Carbon::now()->setTimezone('Africa/Cairo');
    $endOfWorkDay = \Carbon\Carbon::now()->setTimezone('Africa/Cairo')->setTime(16, 0, 0);

    // نتحقق مما إذا كنا في نفس يوم الطلب ووصلنا لنهاية يوم العمل
    $isToday = $now->isSameDay($this->departure_time);
    $isEndOfDay = $now->gte($endOfWorkDay);

    Log::info('Checking if should mark as not returned automatically', [
      'request_id' => $this->id,
      'is_today' => $isToday,
      'is_end_of_day' => $isEndOfDay,
      'returned_on_time' => $this->returned_on_time,
      'now' => $now->format('Y-m-d H:i:s'),
      'end_of_work_day' => $endOfWorkDay->format('Y-m-d H:i:s')
    ]);

    // نقوم بتسجيل عدم العودة تلقائيًا فقط إذا:
    // 1. كنا في نفس يوم الطلب
    // 2. وصلنا لنهاية يوم العمل
    // 3. لم يتم تسجيل حالة العودة (null أو 0)
    if ($isToday && $isEndOfDay && ($this->returned_on_time === null || $this->returned_on_time === 0)) {
      $this->returned_on_time = 2; // لم يعد
      $this->updateActualMinutesUsed();
      $this->save();

      Log::info('Marked as not returned automatically at end of day', [
        'request_id' => $this->id,
        'minutes_used' => $this->minutes_used
      ]);

      return true;
    }

    return false;
  }
}
