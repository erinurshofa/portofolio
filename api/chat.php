<?php
/**
 * Public AI Chat API Endpoint
 * Safe Proxy for Groq & Google Gemini with Failover & Security Protection
 * Eri Nur Sofa Portfolio
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/services/SecurityGuard.php';
require_once __DIR__ . '/services/SchemaValidator.php';
require_once __DIR__ . '/services/AiOrchestrator.php';
require_once __DIR__ . '/services/LeadStorage.php';

// Accept only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    AppConfig::jsonResponse([
        'success' => false,
        'error' => 'Method not allowed. Use POST.'
    ], 405);
}

// Same-origin verification
if (!SecurityGuard::verifySameOrigin()) {
    AppConfig::jsonResponse([
        'success' => false,
        'error' => 'Akses ditolak: Permintaan lintas domain tidak diizinkan.'
    ], 403);
}

// Rate limit check
if (!SecurityGuard::checkRateLimit()) {
    AppConfig::jsonResponse([
        'success' => false,
        'error' => 'Terlalu banyak permintaan. Silakan tunggu beberapa detik sebelum mengirim pesan berikutnya.'
    ], 429);
}

// Parse incoming JSON body
$rawBody = file_get_contents('php://input');
$input = json_decode($rawBody ?: '', true);

if (!is_array($input)) {
    AppConfig::jsonResponse([
        'success' => false,
        'error' => 'Format JSON tidak valid.'
    ], 400);
}

// Validate input schema
$validation = SchemaValidator::validateChat($input);
if (!$validation['isValid']) {
    AppConfig::jsonResponse([
        'success' => false,
        'errors' => $validation['errors']
    ], 422);
}

$userMessage = (string)$input['message'];
$history = is_array($input['history'] ?? null) ? $input['history'] : [];
$incomingLeadRef = !empty($input['lead_ref']) ? trim((string)$input['lead_ref']) : null;
$sessionId = !empty($input['session_id']) ? trim((string)$input['session_id']) : ('sid_' . substr(md5(SecurityGuard::getClientIp() . date('YmdH')), 0, 16));

try {
    // 1. Process conversation reply with auto-failover
    $result = AiOrchestrator::processChat($userMessage, $history);

    // 2. Perform autonomous structured extraction and lead persistence
    $savedLead = AiOrchestrator::extractAndSaveLead($userMessage, $history, $incomingLeadRef);
    $activeLeadRef = $savedLead['lead_ref'] ?? $incomingLeadRef;

    // 3. Persist user message and assistant reply to chat history
    try {
        LeadStorage::recordChatMessage($sessionId, 'user', $userMessage, null, $activeLeadRef);
        LeadStorage::recordChatMessage($sessionId, 'assistant', $result['content'], $result['provider'] ?? 'groq', $activeLeadRef);
        LeadStorage::recordVisitor([
            'session_id' => $sessionId,
            'interaction' => 'AI Chat'
        ]);
    } catch (Throwable $eLog) {
        error_log('[Chat Storage Log Error] ' . $eLog->getMessage());
    }

    $responsePayload = [
        'success' => true,
        'reply' => $result['content'],
        'provider' => $result['provider'] ?? 'groq',
        'failover' => $result['failover'] ?? false
    ];

    if ($savedLead !== null) {
        $responsePayload['structured_lead'] = $savedLead;
    }

    AppConfig::jsonResponse($responsePayload);
} catch (Throwable $e) {
    error_log('[Chat Endpoint Exception] ' . $e->getMessage());
    AppConfig::jsonResponse([
        'success' => false,
        'error' => 'Mohon maaf, terjadi kendala teknis sementara. Silakan coba lagi atau hubungi WhatsApp Mas Eri secara langsung.'
    ], 500);
}

