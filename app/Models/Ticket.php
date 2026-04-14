<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_code',
        'title',
        'description',
        'status',
        'priority',
        'team',
        'category',
        'issue_type',
        'created_by',
        'sla_deadline_at',
        'holder_id',
        'claimed_at',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'sla_deadline_at' => 'datetime',
        'claimed_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function holder()
    {
        return $this->belongsTo(User::class, 'holder_id');
    }

    public function statusHistories()
    {
        return $this->hasMany(TicketStatusHistory::class)->orderBy('changed_at');
    }

    public function resolverMessages()
    {
        return $this->hasMany(\App\Models\ResolverMessage::class)->latest();
    }
}
