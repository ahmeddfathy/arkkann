<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Jetstream\Events\TeamCreated;
use Laravel\Jetstream\Events\TeamDeleted;
use Laravel\Jetstream\Events\TeamUpdated;
use Laravel\Jetstream\Team as JetstreamTeam;
use App\Models\User;

class Team extends JetstreamTeam
{
    /** @use HasFactory<\Database\Factories\TeamFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'personal_team',
    ];

    /**
     * The event map for the model.
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => TeamCreated::class,
        'updated' => TeamUpdated::class,
        'deleted' => TeamDeleted::class,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'personal_team' => 'boolean',
        ];
    }

    /**
     * Transfer ownership of the team to another user.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function transferOwnership(User $user): void
    {
        // If this is a personal team being transferred, update personal_team flag
        if ($this->personal_team) {
            // Set all other teams owned by the target user to non-personal
            $user->ownedTeams()->where('personal_team', true)->update(['personal_team' => false]);

            // Set the current owner's other personal team if they have one
            $oldOwner = User::find($this->user_id);
            if ($oldOwner && $oldOwner->ownedTeams()->count() > 1) {
                $oldOwner->ownedTeams()->where('id', '!=', $this->id)->first()->forceFill([
                    'personal_team' => true,
                ])->save();
            }
        }

        $this->forceFill([
            'user_id' => $user->id,
        ])->save();

        $this->load('owner');
    }
}
