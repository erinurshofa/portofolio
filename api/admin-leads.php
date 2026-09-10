<?php
/**
 * Forwarder to the unified Admin Intelligence & Follow-up Console
 * Eri Nur Sofa Portfolio
 */

declare(strict_types=1);

$query = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header('Location: ../admin.php' . $query);
exit;
