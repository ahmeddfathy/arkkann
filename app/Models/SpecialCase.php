<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecialCase extends Model
{
  protected $fillable = [
    'employee_id',
    'date',
    'check_in',
    'check_out',
    'late_minutes',
    'early_leave_minutes',
    'reason'
  ];

  protected $casts = [
    'date' => 'date',
    'check_in' => 'datetime',
    'check_out' => 'datetime',
  ];

  public function employee()
  {
    return $this->belongsTo(User::class, 'employee_id', 'employee_id');
  }
}
