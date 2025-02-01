<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
  protected $fillable = [
    'user_id',
    'type',
    'data',
    'related_id',
    'read_at'
  ];

  protected $casts = [
    'data' => 'array',
    'read_at' => 'datetime'
  ];

  public function user()
  {
    return $this->belongsTo(User::class);
  }

  public function administrativeDecisions()
  {
    return $this->hasMany(AdministrativeDecision::class);
  }

  public function markAsRead()
  {
    $this->update(['read_at' => now()]);
  }

  public function isUnread(): bool
  {
    return $this->read_at === null;
  }
}
