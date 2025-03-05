<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
  protected $fillable = [
    'name',
    // Add other employee fields here
  ];

  public function specialCases()
  {
    return $this->hasMany(SpecialCase::class);
  }
}
