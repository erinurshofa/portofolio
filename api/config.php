<?php
/**
 * Global Configuration & Safe Environment Loader
 * Eri Nur Sofa Portfolio Enterprise AI Services
 * 
 * Strict Zero-Leakage: Never exposes raw keys, tokens or internals to client.
 */

declare(strict_types=1);

// Prevent direct command-line or unwanted error disclosure
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Set default timezone
date_default_timezone_set('Asia/Jakarta');

final class AppConfig
{
    private static ?array $envData = null;

    /**
     * Load environment variables safely from .env file
     */
    public static function init(): void
    {
        if (self::$envData !== null) {
            return;
        }

        self::$envData = [];
        $envPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';

        if (file_exists($envPath) && is_readable($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines !== false) {
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || str_starts_with($line, '#')) {
                        continue;
                    }
                    $parts = explode('=', $line, 2);
                    if (count($parts) === 2) {
                        $key = trim($parts[0]);
                        $val = trim($parts[1]);
                        // Strip wrapping quotes if any
                        if (
                            (str_starts_with($val, '"') && str_ends_with($val, '"')) ||
                            (str_starts_with($val, "'") && str_ends_with($val, "'"))
                        ) {
                            $val = substr($val, 1, -1);
                        }
                        self::$envData[$key] = $val;
                    }
                }
            }
        }
    }

    /**
     * Get environment value securely
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        self::init();
        return self::$envData[$key] ?? $_ENV[$key] ?? getenv($key) ?: $default;
    }

    /**
     * Get Groq API Key
     */
    public static function getGroqApiKey(): ?string
    {
        // Supports GROK_API_KEY or GROQ_API_KEY
        return self::get('GROK_API_KEY') ?: self::get('GROQ_API_KEY');
    }

    /**
     * Get Google Gemini API Key
     */
    public static function getGeminiApiKey(): ?string
    {
        return self::get('GOOGLE_API_KEY') ?: self::get('GEMINI_API_KEY');
    }

    /**
     * Get Telegram Bot Token
     */
    public static function getTelegramBotToken(): ?string
    {
        return self::get('TELEGRAM_BOT_TOKEN');
    }

    /**
     * Get Telegram Chat ID (fallback to notification recipient or channel if configured)
     */
    public static function getTelegramChatId(): ?string
    {
        return self::get('TELEGRAM_CHAT_ID') ?: self::get('TELEGRAM_OWNER_ID');
    }

    /**
     * Get Admin Dashboard password (defaults to eri2026 if not set in .env)
     */
    public static function getAdminPassword(): string
    {
        return self::get('ADMIN_PASSWORD', 'eri2026');
    }

    /**
     * Check if SSL verification should be enabled for external API calls
     * Auto-detected: enabled in production or if curl ca bundle is configured
     */
    public static function isSslVerifyEnabled(): bool
    {
        $envMode = self::get('APP_ENV', 'development');
        if (strtolower($envMode) === 'production') {
            return true;
        }

        // In development/local, check if system has a valid ca bundle configured
        $caInfo = ini_get('curl.cainfo');
        $caPath = ini_get('openssl.cafile');
        if (!empty($caInfo) && file_exists($caInfo)) {
            return true;
        }
        if (!empty($caPath) && file_exists($caPath)) {
            return true;
        }

        return false;
    }

    /**
     * Get Database file path (always inside protected data/ folder)
     */
    public static function getDbPath(): string
    {
        $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data';
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        return $dir . DIRECTORY_SEPARATOR . 'leads.sqlite';
    }

    /**
     * Send standard JSON response and exit
     */
    public static function jsonResponse(array $data, int $statusCode = 200): void
    {
        // Clear any previous output buffers to avoid leaking warnings/notices
        while (ob_get_level()) {
            ob_end_clean();
        }

        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
