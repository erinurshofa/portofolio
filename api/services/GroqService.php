<?php
/**
 * Groq AI Service Handler
 * Ultra-fast inference with sub-second latency
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

final class GroqService
{
    private const API_URL = 'https://api.groq.com/openai/v1/chat/completions';
    private const DEFAULT_MODEL = 'qwen/qwen3.8-27b';
    private const FALLBACK_MODEL = 'openai/gpt-oss-120b';

    public static function generateChat(string $systemPrompt, array $conversationHistory, string $userMessage): array
    {
        $apiKey = AppConfig::getGroqApiKey();
        if (empty($apiKey)) {
            return [
                'success' => false,
                'error' => 'Groq API Key not configured',
                'provider' => 'groq'
            ];
        }

        // Build messages payload
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];

        // Include recent history (up to last 6 messages)
        foreach (array_slice($conversationHistory, -6) as $msg) {
            $role = ($msg['role'] ?? 'user') === 'assistant' ? 'assistant' : 'user';
            $content = trim((string)($msg['content'] ?? ''));
            if ($content !== '') {
                $messages[] = ['role' => $role, 'content' => $content];
            }
        }

        // Append current message
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $payload = [
            'model' => self::DEFAULT_MODEL,
            'messages' => $messages,
            'temperature' => 0.65,
            'max_tokens' => 850
        ];

        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
                'User-Agent: EriPortfolioAI/1.0'
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 9,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_SSL_VERIFYPEER => AppConfig::isSslVerifyEnabled()
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError || $httpCode !== 200 || !$response) {
            return [
                'success' => false,
                'error' => "Groq HTTP {$httpCode}: " . ($curlError ?: substr((string)$response, 0, 150)),
                'provider' => 'groq'
            ];
        }

        $data = json_decode((string)$response, true);
        $reply = $data['choices'][0]['message']['content'] ?? null;

        if (empty($reply)) {
            return [
                'success' => false,
                'error' => 'Empty response from Groq',
                'provider' => 'groq'
            ];
        }

        return [
            'success' => true,
            'content' => trim($reply),
            'provider' => 'groq',
            'model' => self::DEFAULT_MODEL
        ];
    }

    /**
     * Generate structured JSON output with Groq
     */
    public static function generateJson(string $systemPrompt, array $conversationHistory, string $userMessage): ?array
    {
        $apiKey = AppConfig::getGroqApiKey();
        if (empty($apiKey)) {
            return null;
        }

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];

        foreach (array_slice($conversationHistory, -6) as $msg) {
            $role = ($msg['role'] ?? 'user') === 'assistant' ? 'assistant' : 'user';
            $content = trim((string)($msg['content'] ?? ''));
            if ($content !== '') {
                $messages[] = ['role' => $role, 'content' => $content];
            }
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $payload = [
            'model' => self::DEFAULT_MODEL,
            'response_format' => ['type' => 'json_object'],
            'messages' => $messages,
            'temperature' => 0.1,
            'max_tokens' => 500
        ];

        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
                'User-Agent: EriPortfolioAI/1.0'
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 8,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_SSL_VERIFYPEER => AppConfig::isSslVerifyEnabled()
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return null;
        }

        $data = json_decode((string)$response, true);
        $content = $data['choices'][0]['message']['content'] ?? null;
        if (empty($content)) {
            return null;
        }

        $parsed = json_decode((string)$content, true);
        return is_array($parsed) ? $parsed : null;
    }
}

