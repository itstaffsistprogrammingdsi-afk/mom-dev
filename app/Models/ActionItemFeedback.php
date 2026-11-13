<?php

namespace App\Models;

use App\Enums\ActionItemFeedbackStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActionItemFeedback extends Model
{
    protected $table = 'action_item_feedback';

    protected $appends = [
        'response_status_label',
        'photo_url',
    ];

    protected $fillable = [
        'action_item_id',
        'user_id',
        'body',
        'photo_path',
        'photo_original_name',
        'response_status',
    ];

    protected function casts(): array
    {
        return [
            'response_status' => ActionItemFeedbackStatus::class,
        ];
    }

    public function actionItem(): BelongsTo
    {
        return $this->belongsTo(ActionItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function responseStatusLabel(): Attribute
    {
        return Attribute::get(fn () => $this->response_status?->label() ?? $this->response_status);
    }

    protected function photoUrl(): Attribute
    {
        return Attribute::get(fn () => $this->photo_path
            ? route('action-items.feedback.photo', [$this->action_item_id, $this->id])
            : null);
    }
}
