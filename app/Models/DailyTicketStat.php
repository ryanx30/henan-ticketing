<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stores pre-aggregated daily ticket metrics used by reporting automation and analytics.
 */
class DailyTicketStat extends Model
{
    protected $fillable = [
        'stat_date',
        'team_id',
        'priority_id',
        'tickets_created',
        'tickets_resolved',
        'tickets_closed',
        'tickets_auto_closed',
        'tickets_reopened',
        'sla_breached',
        'sla_met',
        'first_response_seconds_sum',
        'first_response_count',
        'resolution_seconds_sum',
        'resolution_count',
        'open_at_end_of_day',
    ];

    protected $casts = [
        'stat_date'                  => 'date',
        'tickets_created'            => 'integer',
        'tickets_resolved'           => 'integer',
        'tickets_closed'             => 'integer',
        'tickets_auto_closed'        => 'integer',
        'tickets_reopened'           => 'integer',
        'sla_breached'               => 'integer',
        'sla_met'                    => 'integer',
        'first_response_seconds_sum' => 'integer',
        'first_response_count'       => 'integer',
        'resolution_seconds_sum'     => 'integer',
        'resolution_count'           => 'integer',
        'open_at_end_of_day'         => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(Priority::class);
    }

    // -------------------------------------------------------------------------
    // Computed helpers
    // -------------------------------------------------------------------------

    public function avgFirstResponseSeconds(): ?float
    {
        if ($this->first_response_count === 0) {
            return null;
        }

        return $this->first_response_seconds_sum / $this->first_response_count;
    }

    public function avgResolutionSeconds(): ?float
    {
        if ($this->resolution_count === 0) {
            return null;
        }

        return $this->resolution_seconds_sum / $this->resolution_count;
    }

    public function slaBreachRate(): ?float
    {
        $total = $this->sla_breached + $this->sla_met;

        if ($total === 0) {
            return null;
        }

        return $this->sla_breached / $total;
    }
}
