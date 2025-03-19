<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;

use Spatie\Permission\Traits\HasRoles;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class User extends Authenticatable
{
  use HasApiTokens;
  use HasFactory;
  use HasProfilePhoto;
  use HasTeams;
  use HasRoles;
  use Notifiable;
  use TwoFactorAuthenticatable;

  protected $fillable = [
    'name',
    "employee_id",
    'email',
    'password',
    'employee_id',
    'age',
    'date_of_birth',
    'national_id_number',
    'phone_number',
    'start_date_of_employment',
    'last_contract_start_date',
    'last_contract_end_date',
    'job_progression',
    'department',
    'gender',
    'address',
    'education_level',
    'marital_status',
    'number_of_children',
    'employee_status',
    'work_shift_id',
    'fcm_token',
  ];

  protected $hidden = [
    'password',
    'remember_token',
    'two_factor_recovery_codes',
    'two_factor_secret',
  ];

  protected $appends = [
    'profile_photo_url',
  ];

  protected $casts = [
    'email_verified_at' => 'datetime',
    'password' => 'hashed',
    'date_of_birth' => 'date',
  ];

  public function attendanceRecords()
  {
    return $this->hasMany(AttendanceRecord::class, 'employee_id', 'employee_id');
  }

  public function sentMessages()
  {
    return $this->hasMany(Message::class, 'sender_id');
  }

  public function receivedMessages()
  {
    return $this->hasMany(Message::class, 'receiver_id');
  }

  public function ownedTeams()
  {
    return $this->hasMany(Team::class, 'user_id');
  }

  public function teams()
  {
    return $this->belongsToMany(Team::class, 'team_user')
      ->withPivot('role')
      ->withTimestamps();
  }

  public function hasPermissionTo($permission, $guardName = null): bool
  {
    $permissionName = $permission instanceof Permission ? $permission->name : $permission;

    $permissionModel = Permission::where('name', $permissionName)->first();
    if (!$permissionModel) {
      return false;
    }

    $forbidden = DB::table('model_has_permissions')
      ->where([
        'model_type' => get_class($this),
        'model_id' => $this->id,
        'permission_id' => $permissionModel->id,
        'forbidden' => true
      ])
      ->exists();

    if ($forbidden) {
      return false;
    }

    return $this->permissions->contains('name', $permissionName) ||
      $this->roles->flatMap->permissions->contains('name', $permissionName);
  }

  public function hasRole($roles, $guard = null): bool
  {
    // If roles is an array
    if (is_array($roles)) {
      foreach ($roles as $role) {
        if ($this->roles->contains('name', $role)) {
          return true;
        }
      }
      return false;
    }

    // If roles is a string
    return $this->roles->contains('name', $roles);
  }

  public function hasAnyRole($roles): bool
  {
    if (is_array($roles)) {
      foreach ($roles as $role) {
        if ($this->roles->contains('name', $role)) {
          return true;
        }
      }
      return false;
    }
    return $this->roles->contains('name', $roles);
  }

  public function overtimeRequests()
  {
    return $this->hasMany(OverTimeRequests::class);
  }

  public function getMaxAllowedAbsenceDays(): int
  {
    if ($this->date_of_birth) {
        $age = abs(now()->diffInYears($this->date_of_birth));

        if ($age >= 50) {
            return 45;
        }
    }
    return 21;
  }

  public function workShift()
  {
    return $this->belongsTo(WorkShift::class);
  }
}
