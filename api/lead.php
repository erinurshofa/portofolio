<?php
/**
 * Public Lead Storage API Endpoint
 * Accepts validated application breakdown and saves as prospective lead
 * Eri Nur Sofa Portfolio
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/services/SecurityGuard.php';
require_once __DIR__ . '/services/SchemaValidator.php';
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
        'error' => 'Terlalu banyak permintaan. Silakan tunggu sebentar sebelum mencoba kembali.'
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

// Validate input schema (Mirror of Zod)
$validation = SchemaValidator::validateLead($input);
if (!$validation['isValid']) {
    AppConfig::jsonResponse([
        'success' => false,
        'errors' => $validation['errors']
    ], 422);
}

try {
    $saveResult = LeadStorage::saveLead($input);
    AppConfig::jsonResponse([
        'success' => true,
        'message' => 'Rincian kebutuhan aplikasi Anda berhasil disimpan! Mas Eri akan segera meninjau dan merespons.',
        'lead_ref' => $saveResult['lead_ref'],
        'whatsapp_url' => $saveResult['whatsapp_url']
    ], 201);
} catch (Throwable $e) {
    error_log('[Lead Endpoint Exception] ' . $e->getMessage());
    AppConfig::jsonResponse([
        'success' => false,
        'error' => 'Gagal menyimpan data saat ini. Anda dapat langsung mengirimkan kebutuhan melalui WhatsApp Mas Eri.'
    ], 500);
}
