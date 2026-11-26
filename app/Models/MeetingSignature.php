<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingSignature extends Model
{
    public const ROLE_CREATOR = 'creator';

    public const ROLE_APPROVER = 'approver';

    public const ROLES = [self::ROLE_CREATOR, self::ROLE_APPROVER];

    protected $fillable = [
        'meeting_id',
        'role',
        'signed_by',
        'name',
        'signature_path',
        'original_name',
        'signed_at',
    ];

    protected function casts(): array
    {
        return [
            'signed_at' => 'datetime',
        ];
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }
}
