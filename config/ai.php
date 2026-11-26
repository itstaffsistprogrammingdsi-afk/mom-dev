<?php

return [
    'driver' => env('AI_DRIVER', 'mock'),
    'allow_secret' => env('AI_ALLOW_SECRET', false),
    'daily_generations' => (int) env('AI_DAILY_GENERATIONS', 20),
    'max_input_chars' => (int) env('AI_MAX_INPUT_CHARS', 12000),
    'base_url' => env('AI_BASE_URL', 'https://api.openai.com/v1'),
    'api_key' => env('AI_API_KEY'),
    'model' => env('AI_MODEL', 'gpt-4o-mini'),
    'models' => [
        'auto' => env('AI_MODEL_AUTO', env('AI_MODEL', 'gpt-4o-mini')),
        'expert' => env('AI_MODEL_EXPERT', env('AI_MODEL', 'gpt-4o-mini')),
    ],
    'timeout' => (int) env('AI_TIMEOUT', 60),
    'max_tokens' => (int) env('AI_MAX_TOKENS', 2000),
];
