<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

/**
 * Represents the main ticket record, including routing, SLA, lifecycle dates, relationships, and reusable scopes.
 */
class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ticket_code',
        'title',
        'description',
        'status',

        // Master data foreign keys for stable reporting/filtering.
        'team_id',
        'category_id',
        'issue_type_id',
        'priority_id',

        // Snapshot fields kept for backward compatibility and historical display.
        'priority',
        'team',
        'category',
        'issue_type',

        'created_by',
        'client_id',
        'sla_deadline_at',
        'holder_id',
        'claimed_at',
        'resolved_at',
        'closed_at',

        // Create ticket extended fields
        'client_name',
        'client_contact',
        'client_email',
        'platform_type',
        'amount',
        'flow_type',
        'request_time',
        'internal_notes',
    ];

    protected $casts = [
        'sla_deadline_at' => 'datetime',
        'claimed_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'request_time' => 'datetime',
    ];


    public const TEAM_ID_BY_CODE_CACHE_PREFIX = 'tickets:team-id-by-code:';
    public const PRIORITY_ID_BY_CODE_CACHE_PREFIX = 'tickets:priority-id-by-code:';

    public static function cachedTeamIdForCode(string $code): ?int
    {
        $code = self::normalizeLookupCode($code);

        if ($code === '') {
            return null;
        }

        $id = Cache::rememberForever(self::TEAM_ID_BY_CODE_CACHE_PREFIX . $code, function () use ($code) {
            return Team::query()
                ->where('code', $code)
                ->value('id');
        });

        return $id ? (int) $id : null;
    }

    public static function cachedPriorityIdForCode(string $code): ?int
    {
        $code = self::normalizeLookupCode($code);

        if ($code === '') {
            return null;
        }

        $id = Cache::rememberForever(self::PRIORITY_ID_BY_CODE_CACHE_PREFIX . $code, function () use ($code) {
            return Priority::query()
                ->where('code', $code)
                ->value('id');
        });

        return $id ? (int) $id : null;
    }

    public static function forgetTeamCodeCache(?string $code): void
    {
        $code = self::normalizeLookupCode((string) $code);

        if ($code !== '') {
            Cache::forget(self::TEAM_ID_BY_CODE_CACHE_PREFIX . $code);
        }
    }

    public static function forgetPriorityCodeCache(?string $code): void
    {
        $code = self::normalizeLookupCode((string) $code);

        if ($code !== '') {
            Cache::forget(self::PRIORITY_ID_BY_CODE_CACHE_PREFIX . $code);
        }
    }

    // ========= QUERY SCOPES =========

    public function scopeForTeamCode(Builder $query, string $code): Builder
    {
        $code = self::normalizeLookupCode($code);
        $teamId = self::cachedTeamIdForCode($code);

        return $query->where(function (Builder $query) use ($code, $teamId) {
            if ($teamId) {
                $query->where('team_id', $teamId)
                    ->orWhere('team', $code);

                return;
            }

            $query->where('team', $code);
        });
    }

    public function scopeForPriorityCode(Builder $query, string $code): Builder
    {
        $code = self::normalizeLookupCode($code);
        $priorityId = self::cachedPriorityIdForCode($code);

        return $query->where(function (Builder $query) use ($code, $priorityId) {
            if ($priorityId) {
                $query->where('priority_id', $priorityId)
                    ->orWhere('priority', $code);

                return;
            }

            $query->where('priority', $code);
        });
    }

    private static function normalizeLookupCode(string $code): string
    {
        return trim(strtolower($code));
    }

    public function isTeamCode(string $code): bool
    {
        if ($this->relationLoaded('teamMaster') && $this->teamMaster) {
            return $this->teamMaster->code === $code;
        }

        return $this->team === $code;
    }

    public function displayTeamCode(): string
    {
        if ($this->relationLoaded('teamMaster') && $this->teamMaster) {
            return (string) $this->teamMaster->code;
        }

        return (string) $this->team;
    }

    public function displayPriorityCode(): string
    {
        if ($this->relationLoaded('priorityMaster') && $this->priorityMaster) {
            return (string) $this->priorityMaster->code;
        }

        return (string) $this->priority;
    }

    public function displayCategoryName(): string
    {
        if ($this->relationLoaded('categoryMaster') && $this->categoryMaster) {
            return (string) $this->categoryMaster->name;
        }

        return (string) $this->category;
    }

    public function displayIssueTypeName(): string
    {
        if ($this->relationLoaded('issueTypeMaster') && $this->issueTypeMaster) {
            return (string) $this->issueTypeMaster->name;
        }

        return (string) $this->issue_type;
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function teamMaster(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function categoryMaster(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function issueTypeMaster(): BelongsTo
    {
        return $this->belongsTo(IssueType::class, 'issue_type_id');
    }

    public function priorityMaster(): BelongsTo
    {
        return $this->belongsTo(Priority::class, 'priority_id');
    }

    // ========= RELATIONSHIPS =========

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function holder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'holder_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(TicketStatusHistory::class)->orderBy('changed_at');
    }

    public function resolverMessages(): HasMany
    {
        return $this->hasMany(ResolverMessage::class)->latest();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class)->latest();
    }
}
