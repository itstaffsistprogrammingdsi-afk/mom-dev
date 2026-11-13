<?php

namespace App\Models;

use App\Enums\ActionItemFeedbackStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuickNoteMention extends Model
{
    use HasFactory;

    protected $appends = [
        'response_status_label',
        'is_overdue',
        'is_late',
        'response_time_hours',
        'overdue_hours',
    ];

    protected $fillable = [
        'quick_note_id',
        'user_id',
        'response_hours',
        'due_at',
        'response_status',
        'response_body',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'response_hours' => 'integer',
            'due_at' => 'datetime',
            'response_status' => ActionItemFeedbackStatus::class,
            'responded_at' => 'datetime',
        ];
    }

    public function quickNote(): BelongsTo
    {
        return $this->belongsTo(QuickNote::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function responseStatusLabel(): Attribute
    {
        return Attribute::get(fn () => $this->response_status?->label());
    }

    protected function isOverdue(): Attribute
    {
        return Attribute::get(fn () => $this->responded_at === null
            && $this->due_at !== null
            && $this->due_at->isPast());
    }

    protected function isLate(): Attribute
    {
        return Attribute::get(fn () => $this->responded_at !== null
            && $this->due_at !== null
            && $this->responded_at->gt($this->due_at));
    }

    protected function responseTimeHours(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->responded_at || ! $this->created_at) {
                return null;
            }

            return round(max(0, $this->created_at->diffInMinutes($this->responded_at)) / 60, 1);
        });
    }

    protected function overdueHours(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->is_overdue || ! $this->due_at) {
                return null;
            }

            return round($this->due_at->diffInMinutes(now()) / 60, 1);
        });
    }
}
