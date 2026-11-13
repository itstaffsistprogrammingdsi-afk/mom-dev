<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class QuickNote extends Model
{
    use HasFactory;

    protected $appends = [
        'photo_url',
    ];

    protected $hidden = [
        'photos',
    ];

    protected $fillable = [
        'user_id',
        'division_id',
        'title',
        'content',
        'content_text',
        'photo_path',
        'photo_original_name',
        'photos',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'photos' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function mentions(): HasMany
    {
        return $this->hasMany(QuickNoteMention::class);
    }

    protected function photoUrl(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::get(
            fn () => $this->photo_path ? route('quick-notes.photo', $this->id) : null
        );
    }

    protected static function booted(): void
    {
        static::deleted(function (QuickNote $quickNote) {
            $paths = collect($quickNote->photos ?? [])
                ->pluck('path')
                ->push($quickNote->photo_path)
                ->filter()
                ->unique()
                ->values()
                ->all();

            if ($paths !== []) {
                Storage::disk('public')->delete($paths);
            }
        });
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user) {
            $query->where('user_id', $user->id)
                ->orWhereHas('mentions', fn (Builder $mention) => $mention->where('user_id', $user->id));
        });
    }
}
