<?php

namespace App\Models;

use App\Enums\TagType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'type',
        'target_id',
        'target_name',
        'position',
        'source',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => TagType::class,
            'position' => 'array',
        ];
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_id');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'target_id');
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class, 'target_id');
    }

    public function label(): string
    {
        return $this->type->prefix().($this->target_name ?? '');
    }
}
