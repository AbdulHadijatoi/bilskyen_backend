<?php

return [

    'default_max_tokens' => (int) env('AI_DEFAULT_MAX_TOKENS', 1200),

    'default_temperature' => (float) env('AI_DEFAULT_TEMPERATURE', 0.7),

    'http_timeout' => (int) env('AI_HTTP_TIMEOUT', 12),

    'public_max_providers' => (int) env('AI_PUBLIC_MAX_PROVIDERS', 1),

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
        'openrouter' => [
            'default_model' => env('AI_OPENROUTER_MODEL', 'openrouter/free'),
        ],
        'opencodezen' => [
            'default_model' => env('AI_OPENCODEZEN_MODEL', 'deepseek-v4-flash-free'),
            // Free Zen models ("Console") reject non-opencode User-Agents with a 429.
            'user_agent' => env('AI_OPENCODEZEN_USER_AGENT', 'opencode/1.18.16'),
        ],
        'ollama' => [
            'default_model' => env('AI_OLLAMA_MODEL', 'llama3.2'),
            'base_url' => env('AI_OLLAMA_BASE_URL', 'http://127.0.0.1:11434'),
        ],
    ],

    'guardrails' => [
        'enabled' => filter_var(env('AI_GUARDRAILS_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'patterns' => [],
    ],

    'tasks' => [
        'car_advisor_profile' => [
            'max_tokens' => 280,
            'temperature' => 0.1,
        ],
        'car_advisor_explain' => [
            'enabled' => false,
            'max_tokens' => 320,
            'temperature' => 0.1,
        ],
        'search_parse' => [
            'max_tokens' => 180,
            'temperature' => 0.0,
        ],
        'faq_chat' => [
            'max_tokens' => 280,
            'temperature' => 0.2,
        ],
        'listing_description' => [
            'max_tokens' => 360,
            'temperature' => 0.4,
        ],
    ],

];
