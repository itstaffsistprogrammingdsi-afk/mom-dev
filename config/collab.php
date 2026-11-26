<?php

$collabSecret = env('COLLAB_SECRET');

return [
    'secret' => in_array($collabSecret, [null, '', 'change-me-in-production'], true)
        ? env('APP_KEY')
        : $collabSecret,
    'url' => env('COLLAB_URL', 'ws://127.0.0.1:1234'),
    'ttl' => (int) env('COLLAB_TOKEN_TTL', 3600),
];
