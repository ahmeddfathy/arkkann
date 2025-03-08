<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Jetstream\Events\TeamCreated;
use Laravel\Jetstream\Events\TeamDeleted;
use Laravel\Jetstream\Events\TeamUpdated;
use Laravel\Jetstream\Team as JetstreamTeam;

class Team extends JetstreamTeam
{
  use HasFactory;

  protected $fillable = [
    'name',
    'personal_team',
  ];

  protected $dispatchesEvents = [
    'created' => TeamCreated::class,
    'updated' => TeamUpdated::class,
    'deleted' => TeamDeleted::class,
  ];

  protected function casts(): array
  {
    return [
      'personal_team' => 'boolean',
    ];
  }

  public function users()
  {
    return $this->belongsToMany(User::class, 'team_user')
      ->withPivot('role')
      ->withTimestamps();
  }

  public function owner()
  {
    return $this->belongsTo(User::class, 'user_id');
  }
}
