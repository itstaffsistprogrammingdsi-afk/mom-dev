<?php

namespace App\Models;

use App\Enums\AiSummaryScope;
use App\Enums\AiSummaryStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'scope',
        'topic_id',
        'division_id',
        'period_start',
        'period_end',
        'audience',
        'language',
        'content',
        'sources',
        'usage',
        'model',
        'version',
        'status',
        'generation_status',
        'error_message',
        'generated_by',
        'approved_by',
        'approved_at',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'scope' => AiSummaryScope::class,
            'status' => AiSummaryStatus::class,
            'content' => 'array',
            'sources' => 'array',
            'usage' => 'array',
            'period_start' => 'date',
            'period_end' => 'date',
            'approved_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
