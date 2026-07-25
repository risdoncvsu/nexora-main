<?php

namespace Modules\BusinessIntelligence\Services\AI\Providers;

use Illuminate\Support\Facades\Http;
use Modules\BusinessIntelligence\Services\AI\Contracts\AIProviderInterface;
use RuntimeException;

class DigitalOceanAgentProvider implements AIProviderInterface
{
    public function generate(
        string $systemPrompt,
        string $userPrompt,
        bool $jsonMode = false,
        string $thinkingLevel = 'low'
    ): array {
        $apiKey = (string) config('ai.providers.digitalocean-agent.api_key');
        $baseUrl = rtrim((string) config('ai.providers.digitalocean-agent.base_url'), '/');

        if ($apiKey === '' || $baseUrl === '') {
            throw new RuntimeException('DigitalOcean Agent credentials are not configured.');
        }

        if ($jsonMode) {
            $userPrompt .= "\n\nReturn only a valid JSON object. Do not use Markdown fences.";
        }

        // This runs inside a browser request behind App Platform's proxy.
        // Do not retry here: multiple 60-second attempts cause the proxy to
        // return its own 504 before Laravel can return a useful JSON error.
        $response = Http::timeout((int) config('ai.providers.digitalocean-agent.timeout', 25))
            ->acceptJson()
            ->withToken($apiKey)
            ->post($baseUrl.'/api/v1/chat/completions?agent=true', [
                // The Agent endpoint chooses its deployed model; this required
                // field is intentionally ignored by DigitalOcean.
                'model' => (string) config('ai.providers.digitalocean-agent.model', 'ignored'),
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'temperature' => $thinkingLevel === 'low' ? 0.3 : ($thinkingLevel === 'medium' ? 0.5 : 0.7),
                // BI answers are intended for the compact chat panel. Keeping
                // the completion bounded also keeps the HTTP request below the
                // hosting proxy's response deadline.
                'max_tokens' => 800,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('DigitalOcean Agent request failed ('.$response->status().'): '.$response->body());
        }

        $data = $response->json();
        $content = $data['choices'][0]['message']['content'] ?? null;
        if (is_array($content)) {
            $content = collect($content)->pluck('text')->filter()->implode("\n");
        }
        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('DigitalOcean Agent returned no content.');
        }

        return [
            'content' => $content,
            'input_tokens' => (int) ($data['usage']['prompt_tokens'] ?? 0),
            'output_tokens' => (int) ($data['usage']['completion_tokens'] ?? 0),
        ];
    }
}
