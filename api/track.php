<?php
/**
 * Visitor & Interaction Tracking Endpoint
 * Eri Nur Sofa Portfolio Intelligence
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/services/SecurityGuard.php';
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
        'error' => 'Akses ditolak.'
    ], 403);
}

// Parse body
$rawBody = file_get_contents('php://input');
$input = json_decode($rawBody ?: '', true);

if (!is_array($input)) {
    AppConfig::jsonResponse([
        'success' => false,
        'error' => 'Format payload tidak valid.'
    ], 400);
}

try {
    LeadStorage::recordVisitor([
        'session_id' => $input['session_id'] ?? null,
        'page' => $input['page'] ?? 'index2.html',
        'referrer' => $input['referrer'] ?? null,
        'interaction' => $input['interaction'] ?? 'Page Visit'
    ]);

    AppConfig::jsonResponse(['success' => true]);
} catch (Throwable $e) {
    error_log('[Visitor Track Error] ' . $e->getMessage());
    AppConfig::jsonResponse(['success' => false], 500);
}
