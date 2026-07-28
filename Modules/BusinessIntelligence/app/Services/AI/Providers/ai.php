<?php

use Modules\BusinessIntelligence\Services\AI\Providers\DigitalOceanProvider;
use Modules\BusinessIntelligence\Services\AI\Providers\DigitalOceanAgentProvider;
use Modules\BusinessIntelligence\Services\AI\Providers\GeminiProvider;
use Modules\BusinessIntelligence\Services\AI\Providers\OpenAIProvider;

return [

    // Nexora already provisions DigitalOcean inference for the ERP. Keep an
    // explicit AI_PROVIDER override, but use that configured service by
    // default instead of silently selecting OpenAI when no OpenAI key exists.
    // Prefer an Agent Platform endpoint whenever it is configured. The ERP
    // may retain legacy DO_INFERENCE_* variables even when Serverless
    // Inference is unavailable for the account. AI_PROVIDER can still select
    // another provider explicitly.
    'default' => env(
        'AI_PROVIDER',
        env('DO_AGENT_API_KEY') && env('DO_AGENT_BASE_URL')
            ? 'digitalocean-agent'
            : (env('DO_INFERENCE_API_KEY') ? 'digitalocean' : 'openai')
    ),

    'providers' => [

        // DigitalOcean serverless inference (OpenAI-compatible). Select with
        // AI_PROVIDER=digitalocean and set DO_INFERENCE_API_KEY + DO_INFERENCE_MODEL.
        'digitalocean' => [
            'driver' => DigitalOceanProvider::class,
            'api_key' => env('DO_INFERENCE_API_KEY'),
            'model' => env('DO_INFERENCE_MODEL'),
            'base_url' => env('DO_INFERENCE_BASE_URL', 'https://inference.do-ai.run/v1'),
            'timeout' => (int) env('DO_INFERENCE_TIMEOUT', 25),
        ],

        // DigitalOcean Agent Platform endpoint. Keep this separate from
        // Serverless Inference: it uses an agent-scoped access key and the
        // customer-specific /api/v1/chat/completions?agent=true endpoint.
        'digitalocean-agent' => [
            'driver' => DigitalOceanAgentProvider::class,
            'api_key' => env('DO_AGENT_API_KEY'),
            'base_url' => env('DO_AGENT_BASE_URL'),
            'model' => env('DO_AGENT_MODEL', 'ignored'),
            'timeout' => (int) env('DO_AGENT_TIMEOUT', 25),
            'connect_timeout' => (int) env('DO_AGENT_CONNECT_TIMEOUT', 5),
        ],

        'openai' => [
            'driver' => OpenAIProvider::class,
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4o'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'timeout' => (int) env('OPENAI_TIMEOUT', 60),
        ],

        'gemini' => [
            'driver' => GeminiProvider::class,
            'api_key' => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_MODEL', 'gemini-3.5-flash'),
            'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
            'timeout' => (int) env('GEMINI_TIMEOUT', 60),
        ],
    ],
    'generation_interval_hours' => (int) env('AI_GENERATION_INTERVAL_HOURS', 12),
    'cache_ttl_hours' => (int) env('AI_CACHE_TTL_HOURS', 12),

    'chatbot' => [
        'name' => 'Nexora AI',
        'db_lookup_keywords' => ['today', 'current', 'right now', 'how many', 'how much'],
    ],
    'thresholds' => [
        'inventory_low_stock_enabled' => true,
        'manufacturing_downtime_enabled' => true,
        'finance_anomaly_enabled' => true,
    ],
];
