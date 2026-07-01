<?php

return [

    'default_max_tokens' => (int) env('AI_DEFAULT_MAX_TOKENS', 1200),

    'default_temperature' => (float) env('AI_DEFAULT_TEMPERATURE', 0.7),

    'providers' => [
        'openai' => [
            'default_model' => env('AI_OPENAI_MODEL', 'gpt-4o-mini'),
        ],
        'anthropic' => [
            'default_model' => env('AI_ANTHROPIC_MODEL', 'claude-3-5-haiku-latest'),
        ],
        'gemini' => [
            'default_model' => env('AI_GEMINI_MODEL', 'gemini-1.5-flash'),
        ],
    ],

];
