<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    protected $fillable = [
        'code_num',
        'name',
        'code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];


    protected static function booted(): void
    {
        static::saving(function (Team $team) {
            Ticket::forgetTeamCodeCache($team->code);
            Ticket::forgetTeamCodeCache($team->getOriginal('code'));
        });

        static::saved(function (Team $team) {
            Ticket::forgetTeamCodeCache($team->code);
        });

        static::deleted(function (Team $team) {
            Ticket::forgetTeamCodeCache($team->code);
            Ticket::forgetTeamCodeCache($team->getOriginal('code'));
        });
    }

    public function slaRules(): HasMany
    {
        return $this->hasMany(SlaRule::class, 'team_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'team_id');
    }
}
