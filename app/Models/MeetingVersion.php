<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'version',
        'content',
        'content_text',
        'reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
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
}
