<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AbsenceRequest extends Model
{
  protected $fillable = [
    'user_id',
    'absence_date',
    'reason',
    'status',
    'manager_status',
    'hr_status',
    'manager_rejection_reason',
    'hr_rejection_reason'
  ];

  protected $attributes = [
    'status' => 'pending',
    'manager_status' => 'pending',
    'hr_status' => 'pending',
    'manager_rejection_reason' => null,
    'hr_rejection_reason' => null
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

  public function canRespond(User $user): bool
  {
    if ($user->hasRole('hr') && $user->hasPermissionTo('hr_respond_absence_request')) {
      return true;
    }

    if (
      $user->hasAnyRole(['team_leader', 'department_manager', 'company_manager'])
      && $user->hasPermissionTo('manager_respond_absence_request')
    ) {
      return true;
    }

    return false;
  }

  public function canCreate(User $user): bool
  {
    return $user->hasPermissionTo('create_absence');
  }

  public function canUpdate(User $user): bool
  {
    if (!$user->hasPermissionTo('update_absence')) {
      return false;
    }
    return $user->id === $this->user_id && $this->status === 'pending';
  }

  public function canDelete(User $user): bool
  {
    if (!$user->hasPermissionTo('delete_absence')) {
      return false;
    }
    return $user->id === $this->user_id && $this->status === 'pending';
  }

  public function canModifyResponse(User $user): bool
  {
    try {
      if ($user->hasRole('hr') && $user->hasPermissionTo('hr_respond_absence_request')) {
        return true;
      }

      if (
        $user->hasAnyRole(['team_leader', 'department_manager', 'company_manager'])
        && $user->hasPermissionTo('manager_respond_absence_request')
      ) {
        if ($this->user_id === $user->id) {
          return true;
        }

        if ($this->user) {
          foreach ($user->ownedTeams as $team) {
            $isTeamMember = DB::table('team_user')
              ->where('user_id', $this->user_id)
              ->where('team_id', $team->id)
              ->exists();

            if ($isTeamMember) {
              return true;
            }
          }

          $managedTeams = DB::table('team_user')
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'admin'])
            ->pluck('team_id');

          if ($managedTeams->isNotEmpty()) {
            $isInManagedTeam = DB::table('team_user')
              ->where('user_id', $this->user_id)
              ->whereIn('team_id', $managedTeams)
              ->exists();

            if ($isInManagedTeam) {
              return true;
            }
          }
        }
      }

      return false;
    } catch (\Exception $e) {
      return false;
    }
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
    if ($this->manager_status === 'rejected' || $this->hr_status === 'rejected') {
      $this->status = 'rejected';
      return;
    }

    if ($this->manager_status === 'approved' && $this->hr_status === 'approved') {
      $this->status = 'approved';
      return;
    }

    $this->status = 'pending';
  }

  public function hasExceededLimit(): bool
  {
    $maxDays = $this->user->getMaxAllowedAbsenceDays();
    $startOfYear = Carbon::now()->startOfYear();
    $endOfYear = Carbon::now()->endOfYear();

    $approvedDays = static::where('user_id', $this->user_id)
        ->where('status', 'approved')
        ->whereBetween('absence_date', [$startOfYear, $endOfYear])
        ->count();

    return $approvedDays > $maxDays;
  }

  public function getRemainingDays(): int
  {
    $maxDays = $this->user->getMaxAllowedAbsenceDays();
    $startOfYear = Carbon::now()->startOfYear();
    $endOfYear = Carbon::now()->endOfYear();

    $approvedDays = static::where('user_id', $this->user_id)
        ->where('status', 'approved')
        ->whereBetween('absence_date', [$startOfYear, $endOfYear])
        ->count();

    return max(0, $maxDays - $approvedDays);
  }
}
