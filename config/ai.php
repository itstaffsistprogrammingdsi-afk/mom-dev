<?php

return [
    'driver' => env('AI_DRIVER', 'mock'),
    'base_url' => env('AI_BASE_URL', 'https://api.openai.com/v1'),
    'api_key' => env('AI_API_KEY'),
    'model' => env('AI_MODEL', 'gpt-4o-mini'),
    'timeout' => (int) env('AI_TIMEOUT', 60),
    'max_tokens' => (int) env('AI_MAX_TOKENS', 2000),
];
