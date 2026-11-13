<?php

namespace App\Models;

use App\Enums\ActionItemStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ActionItem extends Model
{
    use HasFactory;

    protected $appends = [
        'is_overdue',
        'is_owned',
        'response_time_hours',
        'overdue_hours',
        'is_response_overdue',
        'response_overdue_hours',
    ];

    protected $fillable = [
        'meeting_id',
        'title',
        'description',
        'pic_id',
        'pic_name',
        'division_id',
        'due_date',
        'response_hours',
        'response_due_at',
        'status',
        'notes',
        'source',
        'ai_confirmed',
        'completed_at',
        'first_responded_at',
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
            'response_hours' => 'integer',
            'response_due_at' => 'datetime',
            'ai_confirmed' => 'boolean',
            'completed_at' => 'datetime',
            'first_responded_at' => 'datetime',
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

    public function feedback(): HasMany
    {
        return $this->hasMany(ActionItemFeedback::class);
    }

    protected function isOverdue(): Attribute
    {
        return Attribute::get(fn () => $this->due_date
            && $this->due_date->lt(today())
            && ! in_array($this->status, [ActionItemStatus::Done, ActionItemStatus::Cancelled], true));
    }

    protected function isOwned(): Attribute
    {
        return Attribute::get(fn () => $this->pic_id !== null || $this->division_id !== null);
    }

    protected function responseTimeHours(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->first_responded_at || ! $this->created_at) {
                return null;
            }

            return round(max(0, $this->created_at->diffInMinutes($this->first_responded_at)) / 60, 1);
        });
    }

    protected function overdueHours(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->due_date || in_array($this->status, [ActionItemStatus::Done, ActionItemStatus::Cancelled], true)) {
                return null;
            }

            $due = $this->due_date->copy()->endOfDay();

            if ($due->isFuture()) {
                return null;
            }

            return round($due->diffInMinutes(now()) / 60, 1);
        });
    }

    protected function isResponseOverdue(): Attribute
    {
        return Attribute::get(fn () => $this->response_due_at !== null
            && $this->first_responded_at === null
            && ! in_array($this->status, [ActionItemStatus::Done, ActionItemStatus::Cancelled], true)
            && $this->response_due_at->isPast());
    }

    protected function responseOverdueHours(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->is_response_overdue || ! $this->response_due_at) {
                return null;
            }

            return round($this->response_due_at->diffInMinutes(now()) / 60, 1);
        });
    }

    protected static function booted(): void
    {
        static::deleting(function (ActionItem $actionItem) {
            $paths = $actionItem->feedback()
                ->whereNotNull('photo_path')
                ->pluck('photo_path')
                ->all();

            if ($paths !== []) {
                Storage::disk('public')->delete($paths);
            }
        });
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
            ->when(array_key_exists('owner', $filters) && $filters['owner'] !== null && $filters['owner'] !== '', function (Builder $q) use ($filters) {
                if ((string) $filters['owner'] === '1') {
                    $q->where(fn (Builder $sub) => $sub->whereNotNull('pic_id')->orWhereNotNull('division_id'));
                } else {
                    $q->whereNull('pic_id')->whereNull('division_id');
                }
            })
            ->when($filters['search'] ?? null, fn (Builder $q, $search) => $q->whereLike('title', "%{$search}%", caseSensitive: false));
    }
}
