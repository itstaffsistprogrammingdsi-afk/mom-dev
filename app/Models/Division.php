<?php

namespace App\Models;

use App\Enums\TagType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Division extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'head_id',
    ];

    protected static function booted(): void
    {
        static::saving(function (Division $division) {
            if (blank($division->slug)) {
                $division->slug = Str::slug($division->name);
            }
        });
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class);
    }

    public function taggedMeetings(): BelongsToMany
    {
        return $this->belongsToMany(Meeting::class, 'tags', 'target_id', 'meeting_id')
            ->wherePivot('type', TagType::Division->value)
            ->withTimestamps();
    }
}
