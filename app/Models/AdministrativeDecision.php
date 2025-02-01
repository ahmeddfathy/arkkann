<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdministrativeDecision extends Model
{
    protected $fillable = [
        'notification_id',
        'user_id',
        'acknowledged_at'
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime'
    ];

    public function notification()
    {
        return $this->belongsTo(Notification::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
