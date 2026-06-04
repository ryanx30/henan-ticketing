<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents ticket categories used for classification, routing, and ticket code generation.
 */
class Category extends Model
{
    protected $fillable = [
        'code_num',
        'name',
        'slug',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function issueTypes(): HasMany
    {
        return $this->hasMany(IssueType::class, 'category_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'category_id');
    }
}
