<?php

namespace App\Services;

use App\Exceptions\OpenAiException;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiService
{
    private string $apiKey;
    private string $baseUrl;
    private array $defaultHeaders;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->baseUrl = 'https://api.openai.com/v1';
        
        if (!$this->apiKey) {
            throw new OpenAiException('OpenAI API key is not configured');
        }

        $this->defaultHeaders = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ];

        if (config('services.openai.organization')) {
            $this->defaultHeaders['OpenAI-Organization'] = config('services.openai.organization');
        }
    }

    /**
     * Generate a chat completion using OpenAI's API
     */
    public function generateChatCompletion(
        string $prompt,
        string $model = 'gpt-3.5-turbo',
        float $temperature = 0.7,
        int $maxTokens = 1500
    ): array {
        try {
            $response = Http::withHeaders($this->defaultHeaders)
                ->timeout(60)
                ->post($this->baseUrl . '/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a professional career advisor and job matching expert. Provide detailed, structured analysis of job opportunities based on user preferences and qualifications.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'temperature' => $temperature,
                    'max_tokens' => $maxTokens,
                ]);

            if (!$response->successful()) {
                throw new OpenAiException('OpenAI API request failed: ' . $response->body());
            }

            $data = $response->json();

            if (!isset($data['choices'][0]['message']['content'])) {
                throw new OpenAiException('Invalid response format from OpenAI API');
            }

            return [
                'content' => $data['choices'][0]['message']['content'],
                'model' => $data['model'] ?? $model,
                'usage' => $data['usage'] ?? [],
                'finish_reason' => $data['choices'][0]['finish_reason'] ?? null,
            ];

        } catch (Exception $e) {
            Log::error('OpenAI API error: ' . $e->getMessage(), [
                'prompt' => $prompt,
                'model' => $model,
            ]);
            throw $e;
        }
    }

    /**
     * Calculate the estimated cost based on token usage
     */
    public function calculateCost(int $promptTokens, int $completionTokens, string $model = 'gpt-3.5-turbo'): float
    {
        // Pricing as of 2024 (per 1K tokens)
        $pricing = [
            'gpt-3.5-turbo' => ['input' => 0.0015, 'output' => 0.002],
            'gpt-4' => ['input' => 0.03, 'output' => 0.06],
            'gpt-4-turbo' => ['input' => 0.01, 'output' => 0.03],
            'gpt-4o' => ['input' => 0.005, 'output' => 0.015],
            'gpt-4o-mini' => ['input' => 0.00015, 'output' => 0.0006],
        ];

        $modelPricing = $pricing[$model] ?? $pricing['gpt-3.5-turbo'];
        
        $inputCost = ($promptTokens / 1000) * $modelPricing['input'];
        $outputCost = ($completionTokens / 1000) * $modelPricing['output'];
        
        return $inputCost + $outputCost;
    }
}
