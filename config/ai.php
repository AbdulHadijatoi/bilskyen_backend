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
        'deepseek' => [
            'default_model' => env('AI_DEEPSEEK_MODEL', 'deepseek-v4-flash'),
        ],
        'ollama' => [
            'default_model' => env('AI_OLLAMA_MODEL', 'llama3.2'),
            'base_url' => env('AI_OLLAMA_BASE_URL', 'http://127.0.0.1:11434'),
        ],
    ],

];
