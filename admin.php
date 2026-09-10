<?php
/**
 * Eri Nur Sofa — Admin Intelligence & Follow-up Console
 * Lightweight Yii-style Front Controller (0-dependency MVC)
 * #LebihWarasPakaiSistem
 */
declare(strict_types=1);

require_once __DIR__ . '/api/config.php';
require_once __DIR__ . '/api/services/LeadStorage.php';
require_once __DIR__ . '/api/services/SecurityGuard.php';
require_once __DIR__ . '/api/services/AiOrchestrator.php';
require_once __DIR__ . '/admin/controllers/AdminController.php';

$controller = new AdminController();
$controller->handleRequest();
