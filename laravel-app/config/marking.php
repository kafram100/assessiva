<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Script marking (theory / fill-in questions)
    |--------------------------------------------------------------------------
    |
    | Driver: "external" (FastAPI — default), "openai", or "gemini" (built-in Laravel).
    |
    | Default architecture: Laravel → FastAPI (AI_SERVICE_URL) → OpenAI/Gemini in Python.
    | Laravel does NOT need OPENAI_API_KEY when driver=external.
    |
    | Set SCRIPT_MARKING_API_ENABLED=false to disable. For external driver, set
    | SCRIPT_MARKING_API_URL or AI_SERVICE_URL to your FastAPI base URL.
    |
    */

    'driver' => env('SCRIPT_MARKING_DRIVER', 'external'),

    'api' => [
        'enabled' => env('SCRIPT_MARKING_API_ENABLED', true),
        /** Verbose pipeline logs — set SCRIPT_MARKING_LOG_PIPELINE=true while debugging. */
        'log_pipeline' => env('SCRIPT_MARKING_LOG_PIPELINE', false),
        'timeout' => (int) env('SCRIPT_MARKING_API_TIMEOUT', env('AI_SERVICE_TIMEOUT', 120)),
        'use_for_fill_in' => env('SCRIPT_MARKING_USE_FOR_FILL_IN', true),
        /** Full URL to POST /mark-answer, or base AI service URL ( /mark-answer appended ). */
        'external_url' => env('SCRIPT_MARKING_API_URL') ?: env('AI_SERVICE_URL', 'http://127.0.0.1:8088'),
        /** @deprecated Use external_url — kept for backward compatibility. */
        'url' => env('SCRIPT_MARKING_API_URL') ?: env('AI_SERVICE_URL', 'http://127.0.0.1:8088'),
        'key' => env('SCRIPT_MARKING_API_KEY', ''),
    ],

    'openai' => [
        'key' => env('OPENAI_API_KEY', ''),
        'model' => env('OPENAI_MARKING_MODEL', 'gpt-4o-mini'),
        'verify_ssl' => env('OPENAI_VERIFY_SSL', true),
    ],

    'gemini' => [
        'key' => env('GEMINI_API_KEY', ''),
        'model' => env('GEMINI_MARKING_MODEL', 'gemini-2.0-flash'),
        'verify_ssl' => env('GEMINI_VERIFY_SSL', true),
    ],

];
