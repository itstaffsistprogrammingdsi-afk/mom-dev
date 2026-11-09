<?php

namespace App\Services\Ai;

interface AiClient
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function summarizeMeeting(array $payload): array;
}
