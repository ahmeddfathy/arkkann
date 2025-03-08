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
    return match ($this->returned_on_time) {
      0 => 'Not Specified',
      1 => 'Returned On Time',
      2 => 'Did Not Return On Time',
      default => 'N/A'
    };
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
    $maxReturnTime = $scheduledReturn->copy()->addMinutes(10);
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

    // If return time is after work day end, use work day end as return time
    if ($scheduledReturn->gte($endOfWorkDay)) {
      $scheduledReturn = $endOfWorkDay;
      Log::info('Using end of work day as return time', [
        'request_id' => $this->id,
        'end_of_work_day' => $endOfWorkDay->format('Y-m-d H:i:s')
      ]);
    }

    $minutesUsed = 0;

    // If returned on time (within 10 minutes of scheduled return)
    if ($this->returned_on_time === 1) {
      // Use current time if it's within the allowed return window
      if ($now->lte($maxReturnTime)) {
        $minutesUsed = $departure->diffInMinutes($now);
        Log::info('Returned on time - using current time', [
          'request_id' => $this->id,
          'minutes_used' => $minutesUsed,
          'current_time' => $now->format('Y-m-d H:i:s')
        ]);
      } else {
        $minutesUsed = $departure->diffInMinutes($maxReturnTime);
        Log::info('Returned on time but past max time - using max return time', [
          'request_id' => $this->id,
          'minutes_used' => $minutesUsed,
          'max_return_time' => $maxReturnTime->format('Y-m-d H:i:s')
        ]);
      }
    }
    // If returned late
    else if ($this->returned_on_time === 2) {
      $minutesUsed = $departure->diffInMinutes($now);
      Log::info('Returned late - using current time', [
        'request_id' => $this->id,
        'minutes_used' => $minutesUsed,
        'current_time' => $now->format('Y-m-d H:i:s')
      ]);
    }
    // If not returned yet
    else if ($this->returned_on_time === null) {
      if ($now->gt($maxReturnTime)) {
        $minutesUsed = $departure->diffInMinutes($now);
        Log::info('Not returned and past max time - using current time', [
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
    // Default case - use scheduled return time
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
}
