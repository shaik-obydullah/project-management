<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Issue extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id', 'key', 'type', 'title', 'description', 'priority',
        'column_id', 'assigned_to', 'created_by', 'parent_id', 'position',
        'estimate', 'due_at', 'sprint_id',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'estimate' => 'decimal:2',
    ];

    protected $appends = ['status'];

    public function getStatusAttribute(): string
    {
        $name = strtolower($this->column?->name ?? '');

        if (str_contains($name, 'done') || str_contains($name, 'complete') || $name === '') {
            return 'done';
        }

        if (str_contains($name, 'progress')) {
            return 'in_progress';
        }

        return 'todo';
    }

    public static function booted(): void
    {
        static::creating(fn (Issue $issue) => $issue->position = static::max('position') + 1);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function column(): BelongsTo
    {
        return $this->belongsTo(Column::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Issue::class, 'parent_id');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(Issue::class, 'parent_id');
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class);
    }
}