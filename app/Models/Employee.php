<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
  protected $fillable = [
    'name',
    // أضف الحقول الأخرى المطلوبة للموظف
  ];

  public function specialCases()
  {
    return $this->hasMany(SpecialCase::class);
  }
}
