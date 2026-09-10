<?php
/**
 * Google Gemini AI Service Handler
 * High-intelligence secondary failover engine
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

final class GeminiService
{
    private const PRIMARY_MODEL = 'gemini-2.5-flash';
    private const FALLBACK_MODEL = 'gemini-2.5-flash-lite';

    public static function generateChat(string $systemPrompt, array $conversationHistory, string $userMessage): array
    {
        $apiKey = AppConfig::getGeminiApiKey();
        if (empty($apiKey)) {
            return [
                'success' => false,
                'error' => 'Gemini API Key not configured',
                'provider' => 'gemini'
            ];
        }

        return self::callModel(self::PRIMARY_MODEL, $apiKey, $systemPrompt, $conversationHistory, $userMessage);
    }

    private static function callModel(string $model, string $apiKey, string $systemPrompt, array $conversationHistory, string $userMessage): array
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($apiKey);

        // Build contents array
        $contents = [];

        // Include recent history (up to last 6 messages)
        foreach (array_slice($conversationHistory, -6) as $msg) {
            $role = ($msg['role'] ?? 'user') === 'assistant' ? 'model' : 'user';
            $content = trim((string)($msg['content'] ?? ''));
            if ($content !== '') {
                $contents[] = [
                    'role' => $role,
                    'parts' => [['text' => $content]]
                ];
            }
        }

        // Current message
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $userMessage]]
        ];

        $payload = [
            'system_instruction' => [
                'parts' => [['text' => $systemPrompt]]
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.65,
                'maxOutputTokens' => 850
            ]
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'User-Agent: EriPortfolioAI/1.0'
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 12,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => AppConfig::isSslVerifyEnabled()
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError || $httpCode !== 200 || !$response) {
            return [
                'success' => false,
                'error' => "Gemini HTTP {$httpCode}: " . ($curlError ?: substr((string)$response, 0, 150)),
                'provider' => 'gemini'
            ];
        }

        $data = json_decode((string)$response, true);
        $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (empty($reply)) {
            return [
                'success' => false,
                'error' => 'Empty candidate from Gemini',
                'provider' => 'gemini'
            ];
        }

        return [
            'success' => true,
            'content' => trim($reply),
            'provider' => 'gemini',
            'model' => $model
        ];
    }

    /**
     * Generate structured JSON output with Gemini
     */
    public static function generateJson(string $systemPrompt, array $conversationHistory, string $userMessage): ?array
    {
        $apiKey = AppConfig::getGeminiApiKey();
        if (empty($apiKey)) {
            return null;
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/" . self::PRIMARY_MODEL . ":generateContent?key=" . urlencode($apiKey);

        $contents = [];
        foreach (array_slice($conversationHistory, -6) as $msg) {
            $role = ($msg['role'] ?? 'user') === 'assistant' ? 'model' : 'user';
            $content = trim((string)($msg['content'] ?? ''));
            if ($content !== '') {
                $contents[] = [
                    'role' => $role,
                    'parts' => [['text' => $content]]
                ];
            }
        }

        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $userMessage]]
        ];

        $payload = [
            'system_instruction' => [
                'parts' => [['text' => $systemPrompt]]
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.1,
                'maxOutputTokens' => 500,
                'responseMimeType' => 'application/json'
            ]
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'User-Agent: EriPortfolioAI/1.0'
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_SSL_VERIFYPEER => AppConfig::isSslVerifyEnabled()
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return null;
        }

        $data = json_decode((string)$response, true);
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if (empty($text)) {
            return null;
        }

        $parsed = json_decode((string)$text, true);
        return is_array($parsed) ? $parsed : null;
    }
}

