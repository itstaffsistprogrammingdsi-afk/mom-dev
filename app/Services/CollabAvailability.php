<?php

namespace App\Services;

class CollabAvailability
{
    public function isAvailable(): bool
    {
        $url = (string) config('collab.url', 'ws://localhost:1234');
        $parts = parse_url($url);
        $host = $parts['host'] ?? '127.0.0.1';
        $port = (int) ($parts['port'] ?? 1234);

        if (in_array($host, ['localhost', '::1'], true)) {
            $host = '127.0.0.1';
        }

        $socket = @fsockopen($host, $port, $errorCode, $errorMessage, 0.3);

        if (! is_resource($socket)) {
            return false;
        }

        fclose($socket);

        return true;
    }
}
