<?php

namespace App\Services;

use App\Models\Meeting;
use App\Models\User;

class CollabTokenService
{
    public function issue(Meeting $meeting, User $user, bool $canEdit = true): string
    {
        $secret = (string) config('collab.secret');

        if ($secret === '' || $secret === 'change-me-in-production') {
            throw new \RuntimeException('COLLAB_SECRET belum dikonfigurasi dengan aman.');
        }

        $payload = [
            'mid' => $meeting->id,
            'uid' => $user->id,
            'name' => $user->name,
            'edit' => $canEdit,
            'exp' => now()->addSeconds((int) config('collab.ttl', 3600))->timestamp,
        ];

        $encoded = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_UNICODE));
        $signature = hash_hmac('sha256', $encoded, $secret);

        return $encoded.'.'.$signature;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
