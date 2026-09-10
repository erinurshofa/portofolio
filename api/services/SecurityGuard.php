<?php
/**
 * SecurityGuard: Enterprise-grade Anti-Prompt Injection, Sanitization, and Rate Limiter
 * Eri Nur Sofa Portfolio
 */

declare(strict_types=1);

final class SecurityGuard
{
    private const MAX_CHAT_LENGTH = 1500;
    private const RATE_LIMIT_WINDOW = 60; // seconds
    private const MAX_REQUESTS_PER_WINDOW = 20;

    /**
     * Adversarial prompt injection keywords & regex patterns
     */
    private const INJECTION_PATTERNS = [
        '/(?:ignore|disregard|forget|bypass)\s+(?:all\s+)?(?:previous|prior|above|system)\s+(?:instructions|prompts|rules|commands)/i',
        '/(?:reveal|display|output|show|print|leak)\s+(?:the\s+)?(?:system\s+prompt|initial\s+prompt|developer\s+instructions|hidden\s+rules|api\s*key|secret)/i',
        '/(?:you\s+are\s+now\s+in\s+dan\s+mode|jailbreak|unrestricted\s+ai|evil\s+twin|always\s+say\s+yes)/i',
        '/(?:echo\s+back|repeat\s+everything|transcribe)\s+(?:your\s+instructions|the\s+text\s+above)/i',
        '/<\/?system>/i',
        '/<\/?developer>/i',
        '/\[\s*(?:system|override|admin)\s*\]/i',
        '/what\s+(?:is|are)\s+your\s+(?:api\s*key|token|groq\s*key|gemini\s*key|secret)/i'
    ];

    /**
     * Sanitize general string input
     */
    public static function sanitizeString(string $input, int $maxLength = 500): string
    {
        // Strip null bytes and control characters (keep standard newlines & tabs)
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $input);
        if ($clean === null) {
            $clean = '';
        }

        // Trim and truncate
        $clean = trim($clean);
        if (mb_strlen($clean, 'UTF-8') > $maxLength) {
            $clean = mb_substr($clean, 0, $maxLength, 'UTF-8');
        }

        // Kembalikan teks bersih tanpa HTML encoding — encoding dilakukan saat render (output), bukan saat simpan ke DB
        // XSS dicegah oleh: parameterized query (PDO), htmlspecialchars di sisi tampilan PHP/JS
        return $clean;
    }

    /**
     * Check if text contains prompt injection attempts
     */
    public static function detectPromptInjection(string $text): bool
    {
        foreach (self::INJECTION_PATTERNS as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Pre-process and securely delimit user query for the LLM
     */
    public static function prepareSafePrompt(string $rawInput): array
    {
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $rawInput) ?? '';
        $clean = trim($clean);

        if (mb_strlen($clean, 'UTF-8') > self::MAX_CHAT_LENGTH) {
            $clean = mb_substr($clean, 0, self::MAX_CHAT_LENGTH, 'UTF-8');
        }

        $isInjection = self::detectPromptInjection($clean);

        // Escape delimiter tags if user tried to type them
        $clean = str_ireplace(
            ['<user_query>', '</user_query>', '<system>', '</system>', '<instruction>', '</instruction>'],
            ['[query]', '[/query]', '[sys]', '[/sys]', '[inst]', '[/inst]'],
            $clean
        );

        return [
            'is_injection' => $isInjection,
            'clean_text' => $clean,
            'framed_text' => "<user_query>\n" . $clean . "\n</user_query>"
        ];
    }

    /**
     * Sliding Window Rate Limiter based on Client IP
     */
    public static function checkRateLimit(): bool
    {
        $ip = self::getClientIp();
        $rateLimitFile = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'rate_limits.json';

        $now = time();
        $records = [];

        if (file_exists($rateLimitFile)) {
            $content = @file_get_contents($rateLimitFile);
            if ($content) {
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    $records = $decoded;
                }
            }
        }

        // Clean expired records older than 5 minutes
        foreach ($records as $key => $timestamps) {
            $records[$key] = array_filter($timestamps, fn($ts) => ($now - $ts) < self::RATE_LIMIT_WINDOW);
            if (empty($records[$key])) {
                unset($records[$key]);
            }
        }

        // Check for current IP
        $currentHits = $records[$ip] ?? [];
        if (count($currentHits) >= self::MAX_REQUESTS_PER_WINDOW) {
            return false; // Rate limit exceeded
        }

        // Append current hit
        $currentHits[] = $now;
        $records[$ip] = $currentHits;

        // Save atomically
        @file_put_contents($rateLimitFile, json_encode($records), LOCK_EX);

        return true;
    }

    /**
     * Check honeypot field (must be completely empty)
     */
    public static function verifyHoneypot(array $data, string $field = 'website_hp'): bool
    {
        return empty($data[$field]);
    }

    /**
     * Get real client IP address (anti-spoofing)
     */
    public static function getClientIp(): string
    {
        // 1. Cloudflare verified connecting IP
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $cfIp = trim((string)$_SERVER['HTTP_CF_CONNECTING_IP']);
            if (filter_var($cfIp, FILTER_VALIDATE_IP)) {
                return $cfIp;
            }
        }

        // 2. REMOTE_ADDR is set by the web server TCP layer and cannot be forged by headers
        if (!empty($_SERVER['REMOTE_ADDR'])) {
            $remoteIp = trim((string)$_SERVER['REMOTE_ADDR']);
            if (filter_var($remoteIp, FILTER_VALIDATE_IP)) {
                return $remoteIp;
            }
        }

        return '127.0.0.1';
    }

    /**
     * Verify same-origin request to prevent cross-site API abuse
     */
    public static function verifySameOrigin(): bool
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $host = $_SERVER['HTTP_HOST'] ?? '';

        if (empty($host) || empty($origin)) {
            return true; // Allow CLI, direct local calls, or browsers that do not send Origin on same-origin POST
        }

        $parsedOrigin = parse_url($origin, PHP_URL_HOST);
        $cleanHost = explode(':', $host)[0];

        if ($parsedOrigin && strcasecmp($parsedOrigin, $cleanHost) !== 0) {
            // Check localhost / 127.0.0.1 interchangeability
            if (in_array($parsedOrigin, ['localhost', '127.0.0.1'], true) && in_array($cleanHost, ['localhost', '127.0.0.1'], true)) {
                return true;
            }
            return false;
        }

        return true;
    }
}
