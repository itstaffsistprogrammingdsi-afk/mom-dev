<?php

namespace App\Models;

use App\Enums\MeetingType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeetingTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'meeting_type',
        'description',
        'sections',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'meeting_type' => MeetingType::class,
            'sections' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class);
    }
}
