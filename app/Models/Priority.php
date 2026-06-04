<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents ticket priorities and provides cached lookup helpers for filters and ticket code generation.
 */
class Priority extends Model
{
    protected $fillable = [
        'code_num',
        'name',
        'code',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];


    protected static function booted(): void
    {
        static::saving(function (Priority $priority) {
            Ticket::forgetPriorityCodeCache($priority->code);
            Ticket::forgetPriorityCodeCache($priority->getOriginal('code'));
        });

        static::saved(function (Priority $priority) {
            Ticket::forgetPriorityCodeCache($priority->code);
        });

        static::deleted(function (Priority $priority) {
            Ticket::forgetPriorityCodeCache($priority->code);
            Ticket::forgetPriorityCodeCache($priority->getOriginal('code'));
        });
    }

    public function slaRules(): HasMany
    {
        return $this->hasMany(SlaRule::class, 'priority_id');
    }
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'priority_id');
    }
}
