<?php

namespace App\Models;

use App\Enums\DocumentType;
use App\Enums\MeetingType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeetingTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'document_type',
        'meeting_type',
        'description',
        'sections',
        'content',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => DocumentType::class,
            'meeting_type' => MeetingType::class,
            'sections' => 'array',
            'content' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class);
    }
}
