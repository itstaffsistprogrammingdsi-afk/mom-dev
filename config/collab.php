<?php

return [
    'secret' => env('COLLAB_SECRET', env('APP_KEY')),
    'url' => env('COLLAB_URL', 'ws://localhost:1234'),
    'ttl' => (int) env('COLLAB_TOKEN_TTL', 3600),
];
