<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory;

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
