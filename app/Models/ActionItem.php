<?php

namespace App\Models;

use App\Enums\ActionItemStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActionItem extends Model
{
    use HasFactory;

    protected $appends = [
        'is_overdue',
    ];

    protected $fillable = [
        'meeting_id',
        'title',
        'description',
        'pic_id',
        'pic_name',
        'division_id',
        'due_date',
        'status',
        'notes',
        'source',
        'ai_confirmed',
        'completed_at',
        'reminder_h3_sent_at',
        'reminder_h1_sent_at',
        'overdue_notified_at',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'status' => ActionItemStatus::class,
            'due_date' => 'date',
            'ai_confirmed' => 'boolean',
            'completed_at' => 'datetime',
            'reminder_h3_sent_at' => 'datetime',
            'reminder_h1_sent_at' => 'datetime',
            'overdue_notified_at' => 'datetime',
        ];
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_id');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    protected function isOverdue(): Attribute
    {
        return Attribute::get(fn () => $this->due_date
            && $this->due_date->lt(today())
            && ! in_array($this->status, [ActionItemStatus::Done, ActionItemStatus::Cancelled], true));
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            ActionItemStatus::Done->value,
            ActionItemStatus::Cancelled->value,
        ]);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->whereHas('meeting', fn (Builder $meeting) => $meeting->visibleTo($user));
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['status'] ?? null, fn (Builder $q, $status) => $q->where('status', $status))
            ->when($filters['pic'] ?? null, fn (Builder $q, $pic) => $q->where('pic_id', $pic))
            ->when($filters['division'] ?? null, fn (Builder $q, $division) => $q->where('division_id', $division))
            ->when($filters['overdue'] ?? null, fn (Builder $q) => $q->whereDate('due_date', '<', now())
                ->whereNotIn('status', [ActionItemStatus::Done->value, ActionItemStatus::Cancelled->value]))
            ->when($filters['search'] ?? null, fn (Builder $q, $search) => $q->whereLike('title', "%{$search}%", caseSensitive: false));
    }
}
