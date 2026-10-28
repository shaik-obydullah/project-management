<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Column extends Model
{
    use HasFactory;

    protected $fillable = ['project_id', 'name', 'position', 'color', 'wip_limit'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class)->orderBy('position');
    }

    protected static function booted(): void
    {
        static::creating(fn (Column $column) => $column->position = static::where('project_id', $column->project_id)->max('position') + 1);
    }
}