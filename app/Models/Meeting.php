<?php

namespace App\Models;

use App\Enums\Confidentiality;
use App\Enums\MeetingStatus;
use App\Enums\MeetingType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Meeting extends Model
{
    use HasFactory, Searchable, SoftDeletes;

    protected $fillable = [
        'title',
        'meeting_type',
        'meeting_template_id',
        'division_id',
        'created_by',
        'scheduled_at',
        'started_at',
        'ended_at',
        'location',
        'meeting_link',
        'agenda',
        'content',
        'content_text',
        'content_updated_at',
        'status',
        'version',
        'confidentiality',
        'finalized_at',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'meeting_type' => MeetingType::class,
            'status' => MeetingStatus::class,
            'confidentiality' => Confidentiality::class,
            'content' => 'array',
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'content_updated_at' => 'datetime',
            'finalized_at' => 'datetime',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(MeetingTemplate::class, 'meeting_template_id');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(MeetingParticipant::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(Decision::class);
    }

    public function actionItems(): HasMany
    {
        return $this->hasMany(ActionItem::class);
    }

    public function summaries(): HasMany
    {
        return $this->hasMany(AiSummary::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(MeetingVersion::class)->orderByDesc('version');
    }

    public function isFinal(): bool
    {
        return $this->status === MeetingStatus::Final;
    }

    public function isLocked(): bool
    {
        return $this->status === MeetingStatus::Locked;
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user) {
            $query->where('confidentiality', Confidentiality::Open->value)
                ->orWhere('created_by', $user->id)
                ->orWhereHas('participants', fn (Builder $q) => $q->where('user_id', $user->id))
                ->orWhere(function (Builder $q) use ($user) {
                    $q->where('confidentiality', '!=', Confidentiality::Secret->value)
                        ->whereHas('tags', function (Builder $tag) use ($user) {
                            $tag->where(function (Builder $inner) use ($user) {
                                $inner->where('type', 'person')->where('target_id', $user->id);

                                if ($user->division_id) {
                                    $inner->orWhere(function (Builder $division) use ($user) {
                                        $division->where('type', 'division')
                                            ->where('target_id', $user->division_id);
                                    });
                                }
                            });
                        });
                });
        });
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->whereLike('title', "%{$search}%", caseSensitive: false)
                        ->orWhereLike('content_text', "%{$search}%", caseSensitive: false);
                });
            })
            ->when($filters['division'] ?? null, fn (Builder $q, $division) => $q->where('division_id', $division))
            ->when($filters['topic'] ?? null, fn (Builder $q, $topic) => $q->whereHas(
                'tags',
                fn (Builder $tag) => $tag->where('type', 'topic')->where('target_id', $topic)
            ))
            ->when($filters['status'] ?? null, fn (Builder $q, $status) => $q->where('status', $status))
            ->when($filters['type'] ?? null, fn (Builder $q, $type) => $q->where('meeting_type', $type))
            ->when($filters['from'] ?? null, fn (Builder $q, $from) => $q->whereDate('scheduled_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $q, $to) => $q->whereDate('scheduled_at', '<=', $to));
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'agenda' => $this->agenda,
            'content_text' => $this->content_text,
            'status' => $this->status?->value,
            'meeting_type' => $this->meeting_type?->value,
            'division_id' => $this->division_id,
            'scheduled_at' => $this->scheduled_at?->timestamp,
        ];
    }

    public static function contentToText(?array $content): string
    {
        if (! $content) {
            return '';
        }

        $parts = [];

        $walk = function (array $node) use (&$walk, &$parts) {
            if (isset($node['text']) && is_string($node['text'])) {
                $parts[] = $node['text'];
            }

            foreach ($node['content'] ?? [] as $child) {
                if (is_array($child)) {
                    $walk($child);
                }
            }
        };

        $walk($content);

        return trim(preg_replace('/\s+/', ' ', implode(' ', $parts)) ?? '');
    }
}
