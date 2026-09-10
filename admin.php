<?php
/**
 * Eri Nur Sofa — Admin Intelligence & Follow-up Console
 * World-class comic-editorial design. Mobile-first. Sidebar layout.
 */
declare(strict_types=1);

require_once __DIR__ . '/api/config.php';
require_once __DIR__ . '/api/services/LeadStorage.php';
require_once __DIR__ . '/api/services/SecurityGuard.php';
require_once __DIR__ . '/api/services/AiOrchestrator.php';

session_start();

$adminPassword = AppConfig::getAdminPassword();
$loginError = '';

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION['ens_admin_logged_in'] = false;
    session_destroy();
    header('Location: admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if (hash_equals($adminPassword, (string)$_POST['password'])) {
        $_SESSION['ens_admin_logged_in'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $loginError = 'Kata sandi salah. Silakan coba lagi!';
    }
}

$isLoggedIn = !empty($_SESSION['ens_admin_logged_in']);

// AJAX: Update Follow-up
if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'update_followup') {
    header('Content-Type: application/json');
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '', true);
    $leadId = (int)($data['id'] ?? 0);
    $status = (string)($data['status'] ?? 'belum_dikontak');
    $notes = isset($data['notes']) ? (string)$data['notes'] : null;
    if ($leadId > 0) {
        $ok = LeadStorage::updateLeadFollowup($leadId, $status, $notes);
        echo json_encode(['success' => $ok]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

// AJAX: Transcript
if ($isLoggedIn && isset($_GET['action']) && $_GET['action'] === 'get_transcript') {
    header('Content-Type: application/json');
    $sid = trim((string)($_GET['session_id'] ?? ''));
    $msgs = $sid ? LeadStorage::getChatTranscript($sid) : [];
    echo json_encode(['success' => true, 'messages' => $msgs]);
    exit;
}

// AJAX: Summarize Chat Session (AI Analysis with DB Caching)
if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'summarize_chat') {
    header('Content-Type: application/json');
    $raw   = file_get_contents('php://input');
    $data  = json_decode($raw ?: '', true);
    $sid   = trim((string)($data['session_id'] ?? ''));
    $force = !empty($data['force_refresh']);

    if ($sid !== '') {
        // Cek jika sudah pernah diringkas di database dan tidak diminta paksa refresh
        if (!$force) {
            $saved = LeadStorage::getChatSummary($sid);
            if ($saved && !empty($saved['ai_summary'])) {
                $savedTime = !empty($saved['ai_summary_at']) ? date('d M Y, H:i', strtotime($saved['ai_summary_at'])) : 'Tersimpan';
                echo json_encode([
                    'success'    => true,
                    'summary'    => $saved['ai_summary'],
                    'provider'   => '💾 Database Cache (' . $savedTime . ')',
                    'from_cache' => true,
                    'saved_at'   => $saved['ai_summary_at']
                ]);
                exit;
            }
        }

        $msgs = LeadStorage::getChatTranscript($sid);
        if (empty($msgs)) {
            echo json_encode(['success' => false, 'error' => 'Belum ada percakapan dalam sesi ini untuk diringkas.']);
            exit;
        }
        $db = LeadStorage::getConnection();
        $stmt = $db->prepare('SELECT cs.*, l.name as lead_name FROM chat_sessions cs LEFT JOIN leads l ON cs.lead_ref = l.lead_ref WHERE cs.session_id = :sid LIMIT 1');
        $stmt->execute([':sid' => $sid]);
        $sessionMeta = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $result = AiOrchestrator::summarizeTranscript($msgs, $sessionMeta);
        if (!empty($result['success']) && !empty($result['summary'])) {
            LeadStorage::saveChatSummary($sid, $result['summary']);
            $result['from_cache'] = false;
        }

        echo json_encode($result);
    } else {
        echo json_encode(['success' => false, 'error' => 'Session ID tidak valid']);
    }
    exit;
}

// CSV Export
if ($isLoggedIn && isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    $db = LeadStorage::getConnection();
    $leads = $db->query('SELECT * FROM leads ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="leads_eri_portfolio_' . date('Y-m-d_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF");
    fputcsv($out, ['ID','Referensi','Sumber','Status Follow-up','Catatan','Waktu','Nama','WhatsApp','Email','Perusahaan','Kategori','Status Proyek','Platform','Fitur','Budget','Timeline','Catatan Klien','IP']);
    foreach ($leads as $l) {
        $feats = json_decode((string)($l['selected_features'] ?? '[]'), true);
        fputcsv($out, [
            $l['id'], $l['lead_ref'],
            ($l['source'] ?? '') === 'ai_chat' ? 'AI Chat' : 'Form',
            $l['followup_status'] ?? 'belum_dikontak',
            $l['followup_notes'] ?? '',
            $l['created_at'], $l['name'], $l['whatsapp'],
            $l['email'] ?: '-', $l['company'] ?: '-', $l['project_category'],
            $l['project_status'] ?: 'Baru', $l['target_platform'] ?: 'Web',
            is_array($feats) ? implode(', ', $feats) : '',
            $l['budget_range'], $l['timeline'] ?: '-', $l['notes'] ?: '-', $l['ip_address']
        ]);
    }
    fclose($out);
    exit;
}

// AJAX: Delete Lead
if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'delete_lead') {
    header('Content-Type: application/json');
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '', true);
    $leadId = (int)($data['id'] ?? 0);
    if ($leadId > 0) {
        try {
            $db = LeadStorage::getConnection();
            $stmt = $db->prepare('DELETE FROM leads WHERE id = :id');
            $ok = $stmt->execute([':id' => $leadId]);
            echo json_encode(['success' => $ok]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid ID']);
    }
    exit;
}

// AJAX: Delete Chat Session (beserta semua pesan di dalamnya)
if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'delete_chat') {
    header('Content-Type: application/json');
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw ?: '', true);
    $sid  = trim((string)($data['session_id'] ?? ''));
    if ($sid !== '') {
        try {
            $db = LeadStorage::getConnection();
            $db->prepare('DELETE FROM chat_messages WHERE session_id = :sid')->execute([':sid' => $sid]);
            $db->prepare('DELETE FROM chat_sessions WHERE session_id = :sid')->execute([':sid' => $sid]);
            echo json_encode(['success' => true]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid session_id']);
    }
    exit;
}

// AJAX: Delete Visitor Record
if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'delete_visitor') {
    header('Content-Type: application/json');
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw ?: '', true);
    $vid  = (int)($data['id'] ?? 0);
    if ($vid > 0) {
        try {
            $db = LeadStorage::getConnection();
            $db->prepare('DELETE FROM visitors WHERE id = :id')->execute([':id' => $vid]);
            echo json_encode(['success' => true]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid ID']);
    }
    exit;
}

$analytics = [];
$leadsList = [];
$chatSessions = [];
$visitorsList = [];

if ($isLoggedIn) {
    try {
        $analytics = LeadStorage::getAnalyticsSummary();
        $db = LeadStorage::getConnection();
        $leadsList = $db->query('SELECT * FROM leads ORDER BY id DESC')->fetchAll();
        $chatSessions = LeadStorage::getChatSessions(50);
        $visitorsList = LeadStorage::getVisitors(60);
    } catch (Throwable $e) {
        error_log('[Dashboard Exception] ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Console — Eri Nur Sofa #LebihWarasPakaiSistem</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Caveat:wght@600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/svg+xml" href="favicon.svg">
  <style>
    /* ================================================
       ROOT & TOKENS — Comic Editorial Theme
       ================================================ */
    :root {
      --ink:       #16202C;
      --ink-light: #2D3A4A;
      --paper:     #FAF5EB;
      --paper-2:   #F3ECD8;
      --paper-3:   #EDE3CC;
      --yellow:    #FFCE38;
      --yellow-dk: #E5B218;
      --cream:     #FFF8E7;
      --wa-green:  #25D366;
      --wa-dk:     #1EBE5D;
      --danger:    #EE5253;
      --blue:      #2D72D9;
      --purple:    #7C3AED;
      --green:     #10B981;

      --shadow-sm:  3px 3px 0 var(--ink);
      --shadow-md:  5px 5px 0 var(--ink);
      --shadow-lg:  7px 7px 0 var(--ink);
      --border:     2.5px solid var(--ink);
      --border-thin:1.5px solid var(--ink);
      --radius:     14px;
      --radius-sm:  8px;

      --font-display: 'Fredoka', 'Plus Jakarta Sans', sans-serif;
      --font-body:    'Plus Jakarta Sans', system-ui, sans-serif;
      --font-hand:    'Caveat', cursive;
      --font-mono:    'JetBrains Mono', monospace;

      --sidebar-w:  260px;
      --header-h:   64px;
    }

    @keyframes floatIcon {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-7px); }
    }

    /* ================================================
       RESET
       ================================================ */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { scroll-behavior: smooth; }
    body {
      background-color: var(--paper);
      background-image:
        radial-gradient(rgba(22,32,44,.045) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(255,255,255,.35), rgba(240,228,200,.25));
      background-size: 22px 22px, 100% 100%;
      color: var(--ink);
      font-family: var(--font-body);
      min-height: 100vh;
      overflow-x: hidden;
    }

    /* Halftone noise overlay */
    body::before {
      content: '';
      position: fixed; inset: 0;
      background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.018'/%3E%3C/svg%3E");
      pointer-events: none; z-index: 9999;
    }

    /* ================================================
       LAYOUT SKELETON
       ================================================ */
    .admin-layout {
      display: flex;
      min-height: 100vh;
    }

    /* ================================================
       SIDEBAR
       ================================================ */
    .sidebar {
      width: var(--sidebar-w);
      background: var(--ink);
      border-right: var(--border);
      display: flex;
      flex-direction: column;
      position: fixed;
      top: 0; left: 0; bottom: 0;
      z-index: 200;
      transition: transform 0.3s cubic-bezier(.4,0,.2,1);
      overflow-y: auto;
    }

    .sidebar-brand {
      padding: 1.25rem 1.25rem 1rem;
      border-bottom: var(--border-thin);
      border-color: rgba(255,255,255,.15);
    }

    .brand-pill {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      background: var(--yellow);
      color: var(--ink);
      padding: 0.35rem 0.75rem 0.35rem 0.5rem;
      border-radius: 100px;
      border: var(--border-thin);
      text-decoration: none;
    }

    .brand-pill-badge {
      background: var(--ink);
      color: var(--yellow);
      font-family: var(--font-display);
      font-size: 0.75rem;
      font-weight: 800;
      padding: 0.15rem 0.45rem;
      border-radius: 100px;
      letter-spacing: 0.03em;
    }

    .brand-pill-text {
      font-family: var(--font-display);
      font-weight: 700;
      font-size: 0.85rem;
      color: var(--ink);
    }

    .brand-tagline {
      font-family: var(--font-hand);
      font-size: 0.9rem;
      color: rgba(255,255,255,.55);
      margin-top: 0.65rem;
      padding-left: 0.1rem;
    }

    .sidebar-section {
      padding: 1rem 0.75rem 0.5rem;
    }

    .sidebar-section-label {
      font-size: 0.65rem;
      font-weight: 800;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: rgba(255,255,255,.4);
      padding: 0 0.5rem;
      margin-bottom: 0.35rem;
    }

    .nav-item {
      display: flex;
      align-items: center;
      gap: 0.65rem;
      padding: 0.65rem 0.85rem;
      border-radius: var(--radius-sm);
      border: 2px solid transparent;
      cursor: pointer;
      color: rgba(255,255,255,.7);
      font-family: var(--font-display);
      font-size: 0.95rem;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.15s ease;
      background: none;
      width: 100%;
      text-align: left;
    }

    .nav-item:hover {
      background: rgba(255,206,56,.1);
      color: var(--yellow);
      border-color: rgba(255,206,56,.2);
    }

    .nav-item.active {
      background: var(--yellow);
      color: var(--ink);
      border-color: var(--ink);
      box-shadow: var(--shadow-sm);
    }

    .nav-item .nav-count {
      margin-left: auto;
      background: rgba(255,255,255,.15);
      color: #fff;
      font-size: 0.7rem;
      font-family: var(--font-mono);
      padding: 0.1rem 0.4rem;
      border-radius: 100px;
      font-weight: 700;
    }

    .nav-item.active .nav-count {
      background: var(--ink);
      color: var(--yellow);
    }

    .sidebar-footer {
      margin-top: auto;
      padding: 1rem;
      border-top: var(--border-thin);
      border-color: rgba(255,255,255,.12);
    }

    .btn-sidebar-action {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      width: 100%;
      padding: 0.55rem 0.85rem;
      border-radius: var(--radius-sm);
      border: 1.5px solid rgba(255,255,255,.2);
      background: rgba(255,255,255,.06);
      color: rgba(255,255,255,.65);
      font-family: var(--font-body);
      font-size: 0.8rem;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.15s;
      margin-bottom: 0.4rem;
    }

    .btn-sidebar-action:hover {
      background: rgba(255,255,255,.12);
      color: #fff;
    }

    .btn-sidebar-logout {
      border-color: rgba(238,82,83,.4);
      color: #fca5a5;
    }
    .btn-sidebar-logout:hover {
      background: rgba(238,82,83,.15);
      color: #f87171;
    }

    /* ================================================
       MAIN AREA
       ================================================ */
    .main-area {
      margin-left: var(--sidebar-w);
      flex: 1;
      min-width: 0;
      display: flex;
      flex-direction: column;
    }

    /* ================================================
       TOP HEADER BAR
       ================================================ */
    .top-bar {
      background: var(--yellow);
      border-bottom: var(--border);
      height: var(--header-h);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 1.5rem;
      position: sticky;
      top: 0;
      z-index: 100;
      gap: 1rem;
    }

    .top-bar-left {
      display: flex;
      align-items: center;
      gap: 0.85rem;
    }

    .hamburger-btn {
      display: none;
      background: var(--ink);
      border: none;
      color: var(--yellow);
      width: 2.25rem;
      height: 2.25rem;
      border-radius: var(--radius-sm);
      cursor: pointer;
      align-items: center;
      justify-content: center;
      border: var(--border-thin);
    }

    .page-headline {
      font-family: var(--font-display);
      font-size: 1.25rem;
      font-weight: 800;
      color: var(--ink);
      letter-spacing: -0.01em;
    }

    .page-sub {
      font-size: 0.78rem;
      color: rgba(22,32,44,.65);
      font-weight: 500;
    }

    .top-bar-right {
      display: flex;
      align-items: center;
      gap: 0.65rem;
    }

    /* ================================================
       BUTTONS
       ================================================ */
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      padding: 0.5rem 1rem;
      border-radius: var(--radius-sm);
      font-family: var(--font-display);
      font-size: 0.85rem;
      font-weight: 700;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.15s ease;
      border: var(--border-thin);
      outline: none;
      white-space: nowrap;
    }

    .btn-yellow {
      background: var(--yellow);
      color: var(--ink);
      border-color: var(--ink);
      box-shadow: var(--shadow-sm);
    }
    .btn-yellow:hover {
      box-shadow: var(--shadow-md);
      transform: translate(-1px, -1px);
    }
    .btn-yellow:active { box-shadow: 1px 1px 0 var(--ink); transform: translate(2px,2px); }

    .btn-ink {
      background: var(--ink);
      color: #fff;
      border-color: var(--ink);
      box-shadow: var(--shadow-sm);
    }
    .btn-ink:hover { box-shadow: var(--shadow-md); transform: translate(-1px,-1px); }

    .btn-wa {
      background: var(--wa-green);
      color: #fff;
      border-color: var(--ink);
      box-shadow: var(--shadow-sm);
      font-family: var(--font-display);
      font-size: 0.8rem;
    }
    .btn-wa:hover { background: var(--wa-dk); box-shadow: var(--shadow-md); transform: translate(-1px,-1px); }

    .btn-ghost {
      background: transparent;
      color: var(--ink);
      border-color: var(--ink);
      box-shadow: var(--shadow-sm);
    }
    .btn-ghost:hover { background: var(--paper-2); box-shadow: var(--shadow-md); transform: translate(-1px,-1px); }

    .btn-sm { padding: 0.3rem 0.65rem; font-size: 0.75rem; border-width: 1.5px; box-shadow: 2px 2px 0 var(--ink); }
    .btn-sm:hover { box-shadow: 3px 3px 0 var(--ink); }

    /* ================================================
       PAGE CONTENT
       ================================================ */
    .page-content {
      padding: 1.5rem;
      flex: 1;
    }

    /* ================================================
       KPI CARDS
       ================================================ */
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
      gap: 1rem;
      margin-bottom: 1.5rem;
    }

    .kpi-card {
      background: #fff;
      border: var(--border);
      border-radius: var(--radius);
      padding: 1.25rem;
      box-shadow: var(--shadow-md);
      position: relative;
      overflow: hidden;
      transition: all 0.18s ease;
    }

    .kpi-card:hover {
      box-shadow: var(--shadow-lg);
      transform: translate(-2px,-2px);
    }

    .kpi-card::after {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 4px;
      background: var(--ink);
    }

    .kpi-card.accent-yellow::after { background: var(--yellow-dk); }
    .kpi-card.accent-green::after { background: var(--green); }
    .kpi-card.accent-blue::after { background: var(--blue); }
    .kpi-card.accent-red::after { background: var(--danger); }

    .kpi-icon {
      font-size: 1.4rem;
      margin-bottom: 0.5rem;
      line-height: 1;
    }

    .kpi-label {
      font-size: 0.72rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      color: var(--ink-light);
      margin-bottom: 0.3rem;
    }

    .kpi-value {
      font-family: var(--font-display);
      font-size: 2.25rem;
      font-weight: 800;
      color: var(--ink);
      line-height: 1;
      margin-bottom: 0.35rem;
    }

    .kpi-sub {
      font-size: 0.73rem;
      color: #64748b;
      display: flex;
      align-items: center;
      gap: 0.35rem;
    }

    .kpi-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.2rem;
      background: var(--yellow);
      color: var(--ink);
      border: 1px solid var(--ink);
      padding: 0.1rem 0.4rem;
      border-radius: 100px;
      font-size: 0.68rem;
      font-weight: 800;
      font-family: var(--font-mono);
    }

    .kpi-badge.green { background: #d1fae5; color: #065f46; border-color: #065f46; }
    .kpi-badge.red { background: #fee2e2; color: #991b1b; border-color: #991b1b; }

    /* ================================================
       SECTION CARDS & PANELS
       ================================================ */
    .section-card {
      background: #fff;
      border: var(--border);
      border-radius: var(--radius);
      box-shadow: var(--shadow-md);
      overflow: hidden;
      margin-bottom: 1.25rem;
    }

    .section-card:hover { box-shadow: var(--shadow-lg); }

    .card-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 1rem 1.25rem;
      border-bottom: var(--border-thin);
      background: var(--cream);
      gap: 1rem;
      flex-wrap: wrap;
    }

    .card-title {
      font-family: var(--font-display);
      font-size: 1.1rem;
      font-weight: 800;
      color: var(--ink);
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .card-sub {
      font-size: 0.75rem;
      color: #64748b;
      font-weight: 500;
      margin-top: 0.15rem;
    }

    /* ================================================
       INTEL GRID (2 columns on desktop)
       ================================================ */
    .intel-grid {
      display: grid;
      grid-template-columns: 1.3fr 1fr;
      gap: 1.25rem;
      margin-bottom: 1.25rem;
    }

    /* ================================================
       BAR CHART ROWS
       ================================================ */
    .bar-row { margin-bottom: 0.8rem; }

    .bar-row-label {
      display: flex;
      justify-content: space-between;
      font-size: 0.8rem;
      font-weight: 700;
      margin-bottom: 0.3rem;
      color: var(--ink);
    }

    .bar-row-label span:last-child {
      font-family: var(--font-mono);
      font-size: 0.72rem;
      color: #64748b;
    }

    .bar-track {
      background: var(--paper-2);
      height: 10px;
      border-radius: 100px;
      border: 1.5px solid var(--ink);
      overflow: hidden;
    }

    .bar-fill {
      height: 100%;
      border-radius: 100px;
      background: var(--yellow);
      border-right: 1px solid var(--ink);
      transition: width 0.7s cubic-bezier(.4,0,.2,1);
    }

    .bar-fill.ink { background: var(--ink); }
    .bar-fill.green { background: var(--green); }
    .bar-fill.blue { background: var(--blue); }

    /* ================================================
       FEATURE TAGS CLOUD
       ================================================ */
    .feature-cloud {
      display: flex;
      flex-wrap: wrap;
      gap: 0.45rem;
    }

    .feature-tag {
      background: var(--paper-2);
      border: var(--border-thin);
      color: var(--ink);
      padding: 0.3rem 0.65rem;
      border-radius: var(--radius-sm);
      font-size: 0.78rem;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      box-shadow: 2px 2px 0 var(--ink);
      transition: all 0.15s;
    }

    .feature-tag:hover {
      background: var(--yellow);
      box-shadow: 3px 3px 0 var(--ink);
      transform: translate(-1px,-1px);
    }

    .feature-tag .cnt {
      background: var(--ink);
      color: var(--yellow);
      padding: 0.05rem 0.35rem;
      border-radius: 100px;
      font-size: 0.65rem;
      font-family: var(--font-mono);
      font-weight: 700;
    }

    /* ================================================
       TREND BARS (7 day sparkline)
       ================================================ */
    .trend-chart {
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
      gap: 0.35rem;
      height: 80px;
      margin-top: 0.5rem;
      background: var(--paper-2);
      border: var(--border-thin);
      border-radius: var(--radius-sm);
      padding: 0.5rem 0.75rem 0.25rem;
    }

    .trend-day {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: flex-end;
      height: 100%;
    }

    .trend-bars {
      display: flex;
      gap: 2px;
      align-items: flex-end;
    }

    .trend-bar {
      width: 8px;
      border-radius: 3px 3px 0 0;
      border: 1px solid var(--ink);
    }

    .trend-bar.v { background: var(--yellow); }
    .trend-bar.c { background: var(--ink); }
    .trend-bar.l { background: var(--green); }

    .trend-label {
      font-size: 0.6rem;
      color: #64748b;
      margin-top: 4px;
      font-family: var(--font-mono);
    }

    /* ================================================
       FILTER BAR
       ================================================ */
    .filter-bar {
      display: flex;
      flex-wrap: wrap;
      gap: 0.65rem;
      align-items: center;
      justify-content: space-between;
      padding: 0.85rem 1.25rem;
      background: var(--cream);
      border-bottom: var(--border-thin);
    }

    .filter-input {
      background: #fff;
      border: var(--border-thin);
      color: var(--ink);
      padding: 0.45rem 0.8rem;
      border-radius: var(--radius-sm);
      font-size: 0.8rem;
      font-family: var(--font-body);
      font-weight: 600;
      outline: none;
      min-width: 180px;
      box-shadow: 2px 2px 0 var(--ink);
      transition: all 0.15s;
    }

    .filter-input:focus {
      box-shadow: 3px 3px 0 var(--ink);
    }

    .filter-count {
      font-size: 0.78rem;
      font-weight: 700;
      color: #64748b;
    }

    /* ================================================
       TABLE
       ================================================ */
    .tbl-wrap {
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.82rem;
    }

    th {
      background: var(--paper-2);
      border-bottom: var(--border);
      padding: 0.75rem 1rem;
      text-align: left;
      font-family: var(--font-display);
      font-size: 0.78rem;
      font-weight: 800;
      color: var(--ink);
      white-space: nowrap;
      letter-spacing: 0.02em;
      transition: background 0.12s;
    }

    th[onclick]:hover {
      background: var(--paper-3);
    }

    td {
      padding: 0.85rem 1rem;
      border-bottom: var(--border-thin);
      border-color: var(--paper-2);
      vertical-align: middle;
    }

    tr:hover td { background: var(--cream); }

    /* ================================================
       BADGES & PILLS
       ================================================ */
    .badge-ref {
      font-family: var(--font-mono);
      font-size: 0.72rem;
      font-weight: 700;
      background: var(--ink);
      color: var(--yellow);
      padding: 0.2rem 0.5rem;
      border-radius: var(--radius-sm);
      border: 1px solid var(--ink);
    }

    .badge-ai {
      background: #e9d5ff;
      color: #5b21b6;
      border: 1.5px solid #5b21b6;
      padding: 0.15rem 0.45rem;
      border-radius: 100px;
      font-size: 0.68rem;
      font-weight: 800;
    }

    .badge-form {
      background: #d1fae5;
      color: #065f46;
      border: 1.5px solid #065f46;
      padding: 0.15rem 0.45rem;
      border-radius: 100px;
      font-size: 0.68rem;
      font-weight: 800;
    }

    .badge-cat {
      background: var(--paper-2);
      color: var(--ink);
      border: 1.5px solid var(--ink);
      padding: 0.15rem 0.45rem;
      border-radius: var(--radius-sm);
      font-size: 0.72rem;
      font-weight: 700;
    }

    .badge-budget {
      background: var(--yellow);
      color: var(--ink);
      border: 1.5px solid var(--ink);
      padding: 0.15rem 0.45rem;
      border-radius: var(--radius-sm);
      font-size: 0.72rem;
      font-weight: 800;
      box-shadow: 1px 1px 0 var(--ink);
    }

    /* ================================================
       STATUS SELECT
       ================================================ */
    .status-sel {
      background: #fff;
      border: 1.5px solid var(--ink);
      color: var(--ink);
      padding: 0.3rem 0.55rem;
      border-radius: var(--radius-sm);
      font-size: 0.72rem;
      font-weight: 700;
      font-family: var(--font-body);
      cursor: pointer;
      outline: none;
      box-shadow: 2px 2px 0 var(--ink);
      transition: all 0.15s;
    }

    .status-sel:focus { box-shadow: 3px 3px 0 var(--ink); }

    .status-sel.st-belum_dikontak { background: #fef3c7; }
    .status-sel.st-diskusi_wa     { background: #dbeafe; }
    .status-sel.st-proposal       { background: #ede9fe; }
    .status-sel.st-deal           { background: #d1fae5; }
    .status-sel.st-pending        { background: var(--paper-2); }

    /* ================================================
       INLINE TAG (feature micro)
       ================================================ */
    .inline-tag {
      display: inline-block;
      background: var(--paper-2);
      border: 1px solid rgba(22,32,44,.3);
      color: var(--ink);
      font-size: 0.65rem;
      font-weight: 700;
      padding: 0.1rem 0.35rem;
      border-radius: 4px;
      margin: 1px;
    }

    /* ================================================
       MODAL BACKDROP
       ================================================ */
    .modal-overlay {
      position: fixed; inset: 0;
      background: rgba(22,32,44,.6);
      backdrop-filter: blur(6px);
      z-index: 500;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 1rem;
    }

    .modal-overlay.open { display: flex; }

    .modal {
      background: #fff;
      border: var(--border);
      border-radius: var(--radius);
      box-shadow: var(--shadow-lg);
      width: 100%;
      max-width: 660px;
      max-height: 88vh;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    .modal-head {
      padding: 1rem 1.25rem;
      border-bottom: var(--border-thin);
      background: var(--cream);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .modal-head-title {
      font-family: var(--font-display);
      font-size: 1.1rem;
      font-weight: 800;
      color: var(--ink);
    }

    .modal-close-btn {
      background: var(--ink);
      border: none;
      color: var(--yellow);
      width: 2rem;
      height: 2rem;
      border-radius: var(--radius-sm);
      cursor: pointer;
      font-size: 1rem;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.15s;
    }

    .modal-close-btn:hover { background: var(--danger); }

    .modal-body {
      padding: 1.25rem;
      overflow-y: auto;
      flex: 1;
    }

    .modal-foot {
      padding: 0.75rem 1.25rem;
      border-top: var(--border-thin);
      background: var(--cream);
      display: flex;
      justify-content: flex-end;
      gap: 0.5rem;
    }

    /* ================================================
       CHAT TRANSCRIPT BUBBLES
       ================================================ */
    .bubble-row { margin-bottom: 0.85rem; display: flex; flex-direction: column; }
    .bubble-row.user { align-items: flex-end; }
    .bubble-row.bot  { align-items: flex-start; }

    .bubble-meta {
      font-size: 0.65rem;
      color: #94a3b8;
      margin-bottom: 0.2rem;
      font-weight: 600;
    }

    .bubble {
      max-width: 78%;
      padding: 0.65rem 0.95rem;
      border: var(--border-thin);
      border-radius: var(--radius-sm);
      font-size: 0.82rem;
      line-height: 1.5;
      white-space: pre-wrap;
      word-break: break-word;
    }

    .bubble-row.user .bubble {
      background: var(--ink);
      color: var(--yellow);
      border-color: var(--ink);
      border-bottom-right-radius: 3px;
      box-shadow: var(--shadow-sm);
    }

    .bubble-row.bot .bubble {
      background: var(--cream);
      color: var(--ink);
      border-color: var(--ink);
      border-bottom-left-radius: 3px;
      box-shadow: var(--shadow-sm);
    }

    /* ================================================
       FORM INPUTS
       ================================================ */
    .form-input {
      width: 100%;
      padding: 0.7rem 0.95rem;
      border: var(--border-thin);
      border-radius: var(--radius-sm);
      background: #fff;
      color: var(--ink);
      font-family: var(--font-body);
      font-size: 0.88rem;
      font-weight: 600;
      outline: none;
      box-shadow: 2px 2px 0 var(--ink);
      transition: all 0.15s;
    }

    .form-input:focus { box-shadow: 4px 4px 0 var(--ink); }

    /* ================================================
       VISITOR DEVICE BADGE
       ================================================ */
    .device-mobile { color: #7c3aed; background: #ede9fe; border: 1px solid #7c3aed; padding: 0.12rem 0.45rem; border-radius: 100px; font-size: 0.68rem; font-weight: 800; }
    .device-desktop { color: #1e40af; background: #dbeafe; border: 1px solid #1e40af; padding: 0.12rem 0.45rem; border-radius: 100px; font-size: 0.68rem; font-weight: 800; }

    /* ================================================
       SIDEBAR OVERLAY (mobile)
       ================================================ */
    .sidebar-overlay {
      display: none;
      position: fixed; inset: 0;
      background: rgba(22,32,44,.55);
      z-index: 190;
    }

    /* ================================================
       LOGIN SCREEN
       ================================================ */
    .login-screen {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
    }

    .login-box {
      background: #fff;
      border: var(--border);
      border-radius: var(--radius);
      box-shadow: var(--shadow-lg);
      width: 100%;
      max-width: 420px;
      overflow: hidden;
    }

    .login-top {
      background: var(--yellow);
      border-bottom: var(--border);
      padding: 1.5rem;
      text-align: center;
    }

    .login-top-title {
      font-family: var(--font-display);
      font-size: 1.75rem;
      font-weight: 800;
      color: var(--ink);
    }

    .login-top-tag {
      font-family: var(--font-hand);
      font-size: 1rem;
      color: var(--ink-light);
      margin-top: 0.15rem;
    }

    .login-body {
      padding: 1.75rem;
    }

    .login-label {
      display: block;
      font-size: 0.8rem;
      font-weight: 800;
      color: var(--ink);
      margin-bottom: 0.4rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    .login-error {
      background: #fee2e2;
      border: 1.5px solid var(--danger);
      border-radius: var(--radius-sm);
      color: #991b1b;
      font-size: 0.82rem;
      font-weight: 700;
      padding: 0.65rem 0.85rem;
      margin-bottom: 1rem;
      box-shadow: 2px 2px 0 var(--danger);
    }

    /* ================================================
       EMPTY STATE
       ================================================ */
    .empty-state {
      text-align: center;
      padding: 3rem 1rem;
      color: #94a3b8;
    }

    .empty-state-emoji { font-size: 2.5rem; margin-bottom: 0.5rem; }
    .empty-state-text { font-size: 0.9rem; font-weight: 600; }

    /* ================================================
       NOTES PREVIEW
       ================================================ */
    .notes-preview {
      font-size: 0.72rem;
      color: #64748b;
      max-height: 2.5rem;
      overflow: hidden;
      margin-bottom: 4px;
      line-height: 1.4;
    }

    /* ================================================
       RESPONSIVE — MOBILE
       ================================================ */
    @media (max-width: 768px) {
      .sidebar {
        transform: translateX(-100%);
      }

      .sidebar.open {
        transform: translateX(0);
      }

      .sidebar-overlay.open { display: block; }

      .main-area {
        margin-left: 0;
      }

      .hamburger-btn { display: flex; }

      .intel-grid {
        grid-template-columns: 1fr;
      }

      .kpi-grid {
        grid-template-columns: repeat(2, 1fr);
      }

      .page-headline { font-size: 1rem; }
      .top-bar { padding: 0 1rem; }
      .page-content { padding: 1rem; }

      .modal { max-width: 100%; }

      .tbl-wrap table { font-size: 0.75rem; }
      th, td { padding: 0.65rem 0.75rem; }

      .filter-bar { padding: 0.75rem 1rem; }
      .filter-input { min-width: 140px; }
    }

    @media (max-width: 480px) {
      .kpi-grid { grid-template-columns: 1fr; }
      .page-content { padding: 0.75rem; }
    }
  </style>
</head>
<body>

<?php if (!$isLoggedIn): ?>
<!-- ===================================================
     LOGIN SCREEN
     =================================================== -->
<div class="login-screen">
  <div class="login-box">
    <div class="login-top">
      <div style="font-size:2.5rem; margin-bottom:0.35rem;">🔐</div>
      <div class="login-top-title">Admin Console</div>
      <div class="login-top-tag">#LebihWarasPakaiSistem</div>
    </div>
    <div class="login-body">
      <p style="font-size:0.82rem; color:#64748b; font-weight:500; margin-bottom:1.25rem; line-height:1.5;">
        Konsol analisis pengunjung, log percakapan AI Chat, dan data calon klien Mas Eri Nur Sofa. Masukkan kata sandi untuk melanjutkan.
      </p>

      <?php if ($loginError): ?>
        <div class="login-error">⚠️ <?= htmlspecialchars($loginError) ?></div>
      <?php endif; ?>

      <form method="POST" action="admin.php">
        <label class="login-label" for="adminPasswordInput">Kata Sandi Admin</label>
        <input type="password" name="password" id="adminPasswordInput" class="form-input" placeholder="••••••••••••" required autofocus style="margin-bottom:1rem;">
        <button type="submit" class="btn btn-yellow" style="width:100%; justify-content:center; padding:0.75rem; font-size:0.95rem;">
          🚀 Masuk ke Konsol Admin
        </button>
      </form>
    </div>
    <div style="background:var(--paper-2); border-top:var(--border-thin); padding:0.85rem 1.75rem; text-align:center;">
      <a href="index2.html" style="font-size:0.78rem; color:var(--ink-light); text-decoration:none; font-weight:700;">
        ← Kembali ke Website Portofolio
      </a>
    </div>
  </div>
</div>

<?php else: ?>
<!-- ===================================================
     ADMIN LAYOUT
     =================================================== -->
<div class="admin-layout">

  <!-- Sidebar Overlay (mobile) -->
  <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

  <!-- ===================================================
       SIDEBAR NAVIGATION
       =================================================== -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <a href="index2.html" target="_blank" class="brand-pill" style="margin-bottom:0.65rem; display:inline-flex;">
        <span class="brand-pill-badge">ENS</span>
        <span class="brand-pill-text">Eri Nur Sofa</span>
      </a>
      <div class="brand-tagline">Admin Intelligence Console</div>
    </div>

    <div class="sidebar-section">
      <div class="sidebar-section-label">Menu Utama</div>
      <button class="nav-item active" data-tab="tab-dashboard" onclick="switchTab(this)">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
        Dashboard
      </button>
      <button class="nav-item" data-tab="tab-leads" onclick="switchTab(this)">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="23 11 17 11 20 14 17 17"/></svg>
        Leads & Follow-up
        <span class="nav-count"><?= count($leadsList) ?></span>
      </button>
      <button class="nav-item" data-tab="tab-chats" onclick="switchTab(this)">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        Obrolan AI Chat
        <span class="nav-count"><?= count($chatSessions) ?></span>
      </button>
      <button class="nav-item" data-tab="tab-traffic" onclick="switchTab(this)">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        Traffic Pengunjung
        <span class="nav-count"><?= count($visitorsList) ?></span>
      </button>
    </div>

    <div class="sidebar-section" style="margin-top:auto;">
      <div class="sidebar-section-label">Aksi Cepat</div>
      <a href="?action=export_csv" class="btn-sidebar-action">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Export CSV / Excel
      </a>
      <a href="index2.html" target="_blank" class="btn-sidebar-action">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
        Lihat Website
      </a>
    </div>

    <div class="sidebar-footer">
      <a href="?action=logout" class="btn-sidebar-action btn-sidebar-logout">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Keluar dari Admin
      </a>
    </div>
  </aside>

  <!-- ===================================================
       MAIN AREA
       =================================================== -->
  <div class="main-area">

    <!-- Top Bar -->
    <div class="top-bar">
      <div class="top-bar-left">
        <button class="hamburger-btn" id="hamburgerBtn" onclick="openSidebar()" aria-label="Buka menu">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
        <div>
          <div class="page-headline" id="pageHeadline">📊 Dashboard Analitik</div>
          <div class="page-sub" id="pageSub">Ringkasan aktivitas dan insight utama portofolio</div>
        </div>
      </div>
      <div class="top-bar-right">
        <div style="font-family:var(--font-hand); font-size:0.85rem; color:var(--ink-light); background:rgba(22,32,44,.08); padding:0.25rem 0.65rem; border-radius:100px; border:1px solid rgba(22,32,44,.2); display:flex; align-items:center; gap:0.35rem;">
          <span style="width:7px;height:7px;background:#10b981;border-radius:50%;display:inline-block;border:1px solid var(--ink);"></span>
          Online · <?= date('d M Y, H:i') ?>
        </div>
      </div>
    </div>

    <!-- ================================================== -->
    <!-- TAB PANELS                                          -->
    <!-- ================================================== -->
    <div class="page-content">

      <!-- ================================================
           TAB 1: DASHBOARD
           ================================================ -->
      <div id="tab-dashboard" class="tab-panel">

        <!-- KPI Cards -->
        <div class="kpi-grid">
          <div class="kpi-card accent-yellow">
            <div class="kpi-icon">👥</div>
            <div class="kpi-label">Pengunjung Unik</div>
            <div class="kpi-value"><?= $analytics['metrics']['total_visitors'] ?? 0 ?></div>
            <div class="kpi-sub">
              <span class="kpi-badge green">+<?= $analytics['metrics']['today_visitors'] ?? 0 ?> hari ini</span>
              total kunjungan halaman
            </div>
          </div>

          <div class="kpi-card accent-blue">
            <div class="kpi-icon">💬</div>
            <div class="kpi-label">Sesi AI Chat</div>
            <div class="kpi-value"><?= $analytics['metrics']['total_chats'] ?? 0 ?></div>
            <div class="kpi-sub">
              <span class="kpi-badge green">+<?= $analytics['metrics']['today_chats'] ?? 0 ?> hari ini</span>
              interaksi tanya jawab
            </div>
          </div>

          <div class="kpi-card accent-green">
            <div class="kpi-icon">🎯</div>
            <div class="kpi-label">Leads Calon Klien</div>
            <div class="kpi-value"><?= $analytics['metrics']['total_leads'] ?? 0 ?></div>
            <div class="kpi-sub">
              <span class="kpi-badge green">+<?= $analytics['metrics']['today_leads'] ?? 0 ?> hari ini</span>
              Deal: <strong><?= $analytics['metrics']['deal_count'] ?? 0 ?></strong>
            </div>
          </div>

          <div class="kpi-card accent-red">
            <div class="kpi-icon">⚡</div>
            <div class="kpi-label">Siap Di-Follow Up</div>
            <div class="kpi-value" style="color:var(--danger);"><?= $analytics['metrics']['pending_followup'] ?? 0 ?></div>
            <div class="kpi-sub">
              <span class="kpi-badge red">Belum dikontak</span>
              prioritas segera
            </div>
          </div>
        </div>

        <!-- Intelligence Grid -->
        <div class="intel-grid">

          <!-- What do they want? -->
          <div class="section-card">
            <div class="card-header">
              <div>
                <div class="card-title">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/></svg>
                  Kategori yang Paling Diminati
                </div>
                <div class="card-sub">Proporsi kebutuhan sistem yang ditanyakan calon klien</div>
              </div>
            </div>
            <div style="padding:1.25rem;">
              <?php
              $cats = $analytics['categories'] ?? [];
              $maxCat = 1;
              foreach ($cats as $c) { if ($c['cnt'] > $maxCat) $maxCat = (int)$c['cnt']; }
              $fillClasses = ['', 'ink', 'green', 'blue', ''];
              ?>
              <?php if (empty($cats)): ?>
                <div class="empty-state"><div class="empty-state-emoji">📭</div><div class="empty-state-text">Belum ada data kategori.</div></div>
              <?php else: ?>
                <?php foreach ($cats as $i => $cat):
                  $pct = round(($cat['cnt'] / max($maxCat,1)) * 100);
                  $fc = $fillClasses[$i % count($fillClasses)];
                ?>
                  <div class="bar-row">
                    <div class="bar-row-label">
                      <span><?= htmlspecialchars((string)$cat['project_category']) ?></span>
                      <span><?= $cat['cnt'] ?> leads · <?= $pct ?>%</span>
                    </div>
                    <div class="bar-track">
                      <div class="bar-fill <?= $fc ?>" style="width:<?= max($pct, 8) ?>%;"></div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>

              <!-- 7-Day Trend -->
              <div style="margin-top:1.35rem; padding-top:1rem; border-top:var(--border-thin);">
                <div style="font-family:var(--font-display); font-size:0.85rem; font-weight:800; margin-bottom:0.5rem; display:flex; justify-content:space-between;">
                  <span>Tren 7 Hari Terakhir</span>
                  <span style="font-size:0.65rem; color:#94a3b8; font-weight:600;">
                    <span style="color:var(--yellow-dk);">█</span> Pengunjung
                    <span style="color:var(--ink); margin-left:4px;">█</span> Chat
                  </span>
                </div>
                <div class="trend-chart">
                  <?php foreach (($analytics['trend'] ?? []) as $t):
                    $vh = min(55, max(4, $t['visitors'] * 10));
                    $ch = min(55, max(3, $t['chats'] * 12));
                  ?>
                    <div class="trend-day" title="<?= $t['date'] ?>: <?= $t['visitors'] ?> Pengunjung, <?= $t['chats'] ?> Chat, <?= $t['leads'] ?> Leads">
                      <div class="trend-bars">
                        <div class="trend-bar v" style="height:<?= $vh ?>px;"></div>
                        <div class="trend-bar c" style="height:<?= $ch ?>px;"></div>
                      </div>
                      <div class="trend-label"><?= $t['label'] ?></div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>

          <!-- Top Features & Budget -->
          <div>
            <div class="section-card" style="margin-bottom:1.25rem;">
              <div class="card-header">
                <div>
                  <div class="card-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--green)" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    Fitur Paling Dicari
                  </div>
                  <div class="card-sub">Pilihan fitur yang paling sering diminta calon klien</div>
                </div>
              </div>
              <div style="padding:1.25rem;">
                <div class="feature-cloud">
                  <?php $topFeats = $analytics['top_features'] ?? []; ?>
                  <?php if (empty($topFeats)): ?>
                    <div class="empty-state"><div class="empty-state-emoji">🏷️</div><div class="empty-state-text">Belum ada data fitur.</div></div>
                  <?php else: ?>
                    <?php foreach ($topFeats as $fn => $fc): ?>
                      <div class="feature-tag">
                        <?= htmlspecialchars((string)$fn) ?>
                        <span class="cnt"><?= $fc ?>×</span>
                      </div>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <div class="section-card">
              <div class="card-header">
                <div>
                  <div class="card-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--yellow-dk)" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 18V6"/></svg>
                    Ekspektasi Budget
                  </div>
                  <div class="card-sub">Range budget yang paling banyak dikomunikasikan</div>
                </div>
              </div>
              <div style="padding:1rem 1.25rem;">
                <?php foreach (array_slice(($analytics['budgets'] ?? []), 0, 5) as $b): ?>
                  <div style="display:flex; justify-content:space-between; align-items:center; padding:0.4rem 0; border-bottom:1.5px dashed var(--paper-2);">
                    <span style="font-weight:700; font-size:0.8rem;"><?= htmlspecialchars((string)$b['budget_range']) ?></span>
                    <span class="badge-budget"><?= $b['cnt'] ?>×</span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

        </div>

        <!-- Recent Leads Snapshot -->
        <div class="section-card">
          <div class="card-header">
            <div>
              <div class="card-title">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="23 11 17 11 20 14 17 17"/></svg>
                Leads Terbaru Masuk
              </div>
              <div class="card-sub">5 calon klien terakhir yang perlu ditindaklanjuti</div>
            </div>
            <button class="btn btn-ghost btn-sm" onclick="switchTabByName('tab-leads')">Semua Leads →</button>
          </div>
          <?php
          // Quick leads table (5 rows)
          $recentLeads = array_slice($leadsList, 0, 5);
          ?>
          <div class="tbl-wrap">
            <table>
              <thead><tr>
                <th>Referensi</th><th>Sumber</th><th>Calon Klien</th>
                <th>Kategori</th><th>Budget</th><th>Status</th><th>Aksi</th>
              </tr></thead>
              <tbody>
                <?php if (empty($recentLeads)): ?>
                  <tr><td colspan="7"><div class="empty-state"><div class="empty-state-emoji">📭</div><div class="empty-state-text">Belum ada leads tersimpan.</div></div></td></tr>
                <?php else: ?>
                  <?php foreach ($recentLeads as $row):
                    $st = $row['followup_status'] ?? 'belum_dikontak';
                    $cleanName = htmlspecialchars((string)$row['name']);
                    $cleanRef = htmlspecialchars((string)$row['lead_ref']);
                    $waNum = preg_replace('/[^0-9]/', '', (string)$row['whatsapp']);
                    if (str_starts_with($waNum, '08')) { $waNum = '628' . substr($waNum, 2); }
                    $waMsg = urlencode("Halo {$cleanName}, saya Eri Nur Sofa (Software Engineer). Terima kasih sudah diskusi di portofolio saya (Ref: {$cleanRef}) mengenai {$row['project_category']}. Saya siap bantu diskusi lebih lanjut!");
                  ?>
                    <tr>
                      <td><span class="badge-ref"><?= $cleanRef ?></span></td>
                      <td><?= ($row['source'] ?? '') === 'ai_chat' ? '<span class="badge-ai">🤖 AI Chat</span>' : '<span class="badge-form">📋 Form</span>' ?></td>
                      <td>
                        <div style="font-weight:700;"><?= $cleanName ?></div>
                        <div style="font-size:0.72rem; color:#64748b; font-family:var(--font-mono);"><?= htmlspecialchars((string)$row['whatsapp']) ?></div>
                      </td>
                      <td><span class="badge-cat"><?= htmlspecialchars((string)$row['project_category']) ?></span></td>
                      <td><span class="badge-budget"><?= htmlspecialchars((string)$row['budget_range']) ?></span></td>
                      <td>
                        <select class="status-sel st-<?= htmlspecialchars($st) ?>" onchange="updateStatus(<?= (int)$row['id'] ?>, this.value, this)">
                          <option value="belum_dikontak" <?= $st==='belum_dikontak'?'selected':'' ?>>🟡 Belum Dikontak</option>
                          <option value="diskusi_wa" <?= $st==='diskusi_wa'?'selected':'' ?>>🔵 Diskusi WA</option>
                          <option value="proposal" <?= $st==='proposal'?'selected':'' ?>>🟣 Proposal</option>
                          <option value="deal" <?= $st==='deal'?'selected':'' ?>>🟢 Deal</option>
                          <option value="pending" <?= $st==='pending'?'selected':'' ?>>⚪ Pending</option>
                        </select>
                      </td>
                      <td>
                        <a href="https://wa.me/<?= $waNum ?>?text=<?= $waMsg ?>" target="_blank" rel="noopener noreferrer" class="btn btn-wa btn-sm">
                          <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.976.58 2.028.928 3.149.929 3.182 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.771-5.768-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.312.045-.694.067-2.127-.527-1.745-.724-2.883-2.493-2.97-2.609-.086-.115-.71-1.002-.71-1.921 0-.918.47-1.37.643-1.558.174-.187.378-.235.505-.235.127 0 .254.002.366.007.119.006.278-.045.435.333.16.386.549 1.341.597 1.439.048.098.08.213.016.34-.064.127-.096.206-.191.317-.095.112-.2.249-.286.334-.096.096-.196.2-.084.392.112.193.498.823 1.069 1.332.735.655 1.355.858 1.547.954.192.096.304.08.417-.048.112-.128.481-.56.609-.752.127-.193.255-.16.43-.096.175.064 1.112.524 1.303.62.191.096.318.143.366.223.048.079.048.461-.096.866z"/></svg>
                          Follow-up WA
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <!-- END TAB 1 -->

      <!-- ================================================
           TAB 2: LEADS & FOLLOW-UP
           ================================================ -->
      <div id="tab-leads" class="tab-panel" style="display:none;">
        <div class="section-card">
          <div class="filter-bar">
            <div style="display:flex; flex-wrap:wrap; gap:0.5rem; align-items:center;">
              <input type="text" class="filter-input" id="searchLeads" placeholder="🔍 Cari nama, WA, proyek..." oninput="filterLeads()">
              <select class="filter-input" id="filterStatus" style="min-width:140px;" onchange="filterLeads()">
                <option value="">Semua Status</option>
                <option value="belum_dikontak">🟡 Belum Dikontak</option>
                <option value="diskusi_wa">🔵 Diskusi WA</option>
                <option value="proposal">🟣 Proposal</option>
                <option value="deal">🟢 Deal</option>
                <option value="pending">⚪ Pending</option>
              </select>
              <select class="filter-input" id="filterSource" style="min-width:140px;" onchange="filterLeads()">
                <option value="">Semua Sumber</option>
                <option value="ai_chat">🤖 AI Chat</option>
                <option value="breakdown_form">📋 Form Breakdown</option>
              </select>
            </div>
            <div class="filter-count">
              <span id="leadsCount"><?= count($leadsList) ?></span> leads ditampilkan
            </div>
          </div>

          <div class="tbl-wrap">
            <table id="leadsTable">
              <thead><tr>
                <th onclick="sortTable(0,this)" style="cursor:pointer;user-select:none;" title="Klik untuk sort">Ref ↕</th>
                <th onclick="sortTable(1,this)" style="cursor:pointer;user-select:none;" title="Klik untuk sort">Sumber ↕</th>
                <th onclick="sortTable(2,this)" style="cursor:pointer;user-select:none;" title="Klik untuk sort">Waktu ↕</th>
                <th onclick="sortTable(3,this)" style="cursor:pointer;user-select:none;" title="Klik untuk sort">Calon Klien ↕</th>
                <th onclick="sortTable(4,this)" style="cursor:pointer;user-select:none;" title="Klik untuk sort">Kategori & Platform ↕</th>
                <th onclick="sortTable(5,this)" style="cursor:pointer;user-select:none;" title="Klik untuk sort">Budget & Timeline ↕</th>
                <th>Fitur Kunci</th>
                <th onclick="sortTable(7,this)" style="cursor:pointer;user-select:none;" title="Klik untuk sort">Status ↕</th>
                <th>Catatan Mas Eri</th><th>Aksi</th>
              </tr></thead>
              <tbody>
                <?php if (empty($leadsList)): ?>
                  <tr><td colspan="10"><div class="empty-state"><div class="empty-state-emoji">📭</div><div class="empty-state-text">Belum ada data leads.</div></div></td></tr>
                <?php else: ?>
                  <?php foreach ($leadsList as $lead):
                    $st = $lead['followup_status'] ?? 'belum_dikontak';
                    $cr = htmlspecialchars((string)$lead['lead_ref']);
                    $cn = htmlspecialchars((string)$lead['name']);
                    $cw = htmlspecialchars((string)$lead['whatsapp']);
                    $cc = htmlspecialchars((string)($lead['company'] ?? ''));
                    $ccat = htmlspecialchars((string)$lead['project_category']);
                    $cpf = htmlspecialchars((string)($lead['target_platform'] ?? 'Web'));
                    $cps = htmlspecialchars((string)($lead['project_status'] ?? 'Bangun Baru'));
                    $cb = htmlspecialchars((string)$lead['budget_range']);
                    $ct = htmlspecialchars((string)($lead['timeline'] ?? '-'));
                    $cnotes = htmlspecialchars((string)($lead['notes'] ?? ''));
                    $masNotes = htmlspecialchars((string)($lead['followup_notes'] ?? ''));
                    $src = $lead['source'] ?? 'breakdown_form';
                    $feats = json_decode((string)($lead['selected_features'] ?? '[]'), true);
                    $waNum = preg_replace('/[^0-9]/', '', (string)$lead['whatsapp']);
                    if (str_starts_with($waNum, '08')) { $waNum = '628' . substr($waNum, 2); }
                    $wafStr = "Halo Mas/Mbak {$cn}, saya Eri Nur Sofa (Software Engineer). Terima kasih sudah diskusi di portofolio saya (Ref: {$cr}) mengenai {$ccat}. Estimasi budget: {$cb}, target platform: {$cpf}. Saya siap bantu diskusi lebih lanjut kapanpun Mas/Mbak berkenan 🙏";
                    $waEncoded = urlencode($wafStr);
                    $searchStr = strtolower("{$cn} {$cw} {$ccat} {$cc} {$cr}");
                  ?>
                    <tr data-status="<?= htmlspecialchars($st) ?>" data-source="<?= htmlspecialchars($src) ?>" data-search="<?= $searchStr ?>">
                      <td><span class="badge-ref"><?= $cr ?></span></td>
                      <td><?= $src === 'ai_chat' ? '<span class="badge-ai">🤖 AI Chat</span>' : '<span class="badge-form">📋 Form</span>' ?></td>
                      <td style="white-space:nowrap; font-size:0.72rem; color:#64748b; font-family:var(--font-mono);">
                        <?= htmlspecialchars(date('d M Y', strtotime((string)$lead['created_at']))) ?><br>
                        <?= htmlspecialchars(date('H:i', strtotime((string)$lead['created_at']))) ?>
                      </td>
                      <td>
                        <div style="font-weight:800; font-size:0.85rem;"><?= $cn ?></div>
                        <?php if ($cc): ?><div style="font-size:0.7rem; color:#64748b;">🏢 <?= $cc ?></div><?php endif; ?>
                        <div style="font-family:var(--font-mono); font-size:0.73rem; color:var(--blue);"><?= $cw ?></div>
                      </td>
                      <td>
                        <span class="badge-cat"><?= $ccat ?></span>
                        <div style="font-size:0.7rem; margin-top:3px; color:#64748b;">📱 <?= $cpf ?></div>
                        <div style="font-size:0.7rem; color:#64748b;">⚡ <?= $cps ?></div>
                      </td>
                      <td>
                        <span class="badge-budget"><?= $cb ?></span>
                        <div style="font-size:0.7rem; margin-top:3px; color:#64748b;">⏱ <?= $ct ?></div>
                      </td>
                      <td style="max-width:200px;">
                        <?php if (is_array($feats) && !empty($feats)): ?>
                          <?php foreach ($feats as $f): ?><span class="inline-tag"><?= htmlspecialchars($f) ?></span><?php endforeach; ?>
                        <?php else: ?><span style="color:#94a3b8; font-size:0.72rem;">-</span><?php endif; ?>
                      </td>
                      <td>
                        <select class="status-sel st-<?= htmlspecialchars($st) ?>" onchange="updateStatus(<?= (int)$lead['id'] ?>, this.value, this)">
                          <option value="belum_dikontak" <?= $st==='belum_dikontak'?'selected':'' ?>>🟡 Belum Dikontak</option>
                          <option value="diskusi_wa" <?= $st==='diskusi_wa'?'selected':'' ?>>🔵 Diskusi WA</option>
                          <option value="proposal" <?= $st==='proposal'?'selected':'' ?>>🟣 Proposal</option>
                          <option value="deal" <?= $st==='deal'?'selected':'' ?>>🟢 Deal</option>
                          <option value="pending" <?= $st==='pending'?'selected':'' ?>>⚪ Pending</option>
                        </select>
                      </td>
                      <td style="min-width:160px;">
                        <div class="notes-preview" id="notesPreview_<?= (int)$lead['id'] ?>"><?= $masNotes ?: '<em style="color:#94a3b8;">belum ada catatan</em>' ?></div>
                        <button class="btn btn-ghost btn-sm" onclick="openNotes(<?= (int)$lead['id'] ?>, '<?= addslashes($cn) ?>', '<?= addslashes($masNotes) ?>')">✏️ Catatan</button>
                      </td>
                      <td style="white-space:nowrap;">
                        <a href="https://wa.me/<?= $waNum ?>?text=<?= $waEncoded ?>" target="_blank" rel="noopener noreferrer" class="btn btn-wa btn-sm" style="margin-bottom:4px; display:inline-flex;">
                          <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.976.58 2.028.928 3.149.929 3.182 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.771-5.768-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.312.045-.694.067-2.127-.527-1.745-.724-2.883-2.493-2.97-2.609-.086-.115-.71-1.002-.71-1.921 0-.918.47-1.37.643-1.558.174-.187.378-.235.505-.235.127 0 .254.002.366.007.119.006.278-.045.435.333.16.386.549 1.341.597 1.439.048.098.08.213.016.34-.064.127-.096.206-.191.317-.095.112-.2.249-.286.334-.096.096-.196.2-.084.392.112.193.498.823 1.069 1.332.735.655 1.355.858 1.547.954.192.096.304.08.417-.048.112-.128.481-.56.609-.752.127-.193.255-.16.43-.096.175.064 1.112.524 1.303.62.191.096.318.143.366.223.048.079.048.461-.096.866z"/></svg>
                          Follow-up WA
                        </a>
                        <br>
                        <button class="btn btn-ghost btn-sm" onclick='openDetail(<?= json_encode($lead, JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>🔍 Detail</button>
                        <br>
                        <button class="btn btn-sm" style="background:#fee2e2;border-color:var(--danger);color:#991b1b;box-shadow:2px 2px 0 var(--danger);margin-top:4px;" onclick="deleteLead(<?= (int)$lead['id'] ?>, '<?= addslashes($cn) ?>', this.closest('tr'))">🗑️ Hapus</button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <!-- END TAB 2 -->

      <!-- ================================================
           TAB 3: AI CHAT SESSIONS
           ================================================ -->
      <div id="tab-chats" class="tab-panel" style="display:none;">
        <div class="section-card">
          <div class="card-header">
            <div>
              <div class="card-title">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                Log Percakapan Pengunjung & AI Chat
              </div>
              <div class="card-sub">Baca transkrip obrolan calon klien untuk mengerti kebutuhan mereka lebih dalam</div>
            </div>
          </div>
          <div class="tbl-wrap">
            <table>
              <thead><tr>
                <th>Session & Perangkat</th><th>Waktu Terakhir</th>
                <th>Lead Terkait</th><th>Pesan Terakhir</th>
                <th>Total Pesan</th><th>Transkrip</th>
              </tr></thead>
              <tbody>
                <?php if (empty($chatSessions)): ?>
                  <tr><td colspan="6"><div class="empty-state"><div class="empty-state-emoji">💬</div><div class="empty-state-text">Belum ada log percakapan AI Chat.</div></div></td></tr>
                <?php else: ?>
                  <?php foreach ($chatSessions as $cs):
                    $sid = htmlspecialchars((string)$cs['session_id']);
                    $dev = (string)($cs['device_type'] ?? 'Desktop');
                  ?>
                    <tr>
                      <td>
                        <div style="font-family:var(--font-mono); font-size:0.72rem; color:var(--blue);"><?= substr($sid, 0, 18) ?>…</div>
                        <?php if ($dev === 'Mobile'): ?>
                          <span class="device-mobile">📱 HP / Tablet</span>
                        <?php else: ?>
                          <span class="device-desktop">💻 Komputer</span>
                        <?php endif; ?>
                      </td>
                      <td style="font-size:0.75rem; color:#64748b; font-family:var(--font-mono); white-space:nowrap;">
                        <?= htmlspecialchars(date('d M Y', strtotime((string)$cs['updated_at']))) ?><br>
                        <?= htmlspecialchars(date('H:i', strtotime((string)$cs['updated_at']))) ?>
                      </td>
                      <td>
                        <?php if (!empty($cs['lead_ref'])): ?>
                          <span class="badge-ref"><?= htmlspecialchars((string)$cs['lead_ref']) ?></span>
                          <div style="font-size:0.75rem; font-weight:700; margin-top:2px;"><?= htmlspecialchars((string)($cs['lead_name'] ?? 'Calon Klien')) ?></div>
                        <?php else: ?>
                          <span style="font-size:0.72rem; color:#94a3b8; font-style:italic;">Pengunjung umum</span>
                        <?php endif; ?>
                      </td>
                      <td style="max-width:280px; font-size:0.78rem; color:var(--ink-light);">
                        <?= htmlspecialchars(mb_substr((string)($cs['last_message'] ?? '-'), 0, 100)) ?>
                      </td>
                      <td><span class="badge-cat"><?= (int)$cs['message_count'] ?> pesan</span></td>
                      <td>
                        <?php $hasSummary = !empty($cs['ai_summary']); ?>
                        <div style="display:flex; flex-direction:column; gap:4px;">
                          <button class="btn btn-yellow btn-sm" onclick="viewTranscript('<?= $sid ?>', '<?= addslashes((string)($cs['lead_name'] ?? 'Pengunjung Website')) ?>')">
                            💬 Transkrip
                          </button>
                          <?php if ($hasSummary): ?>
                            <button class="btn btn-sm" style="background:#dcfce7;border-color:#16a34a;color:#15803d;box-shadow:2px 2px 0 #16a34a;" onclick="openSummary('<?= $sid ?>', '<?= addslashes((string)($cs['lead_name'] ?? 'Calon Klien')) ?>')">
                              ✅ Ringkasan DB
                            </button>
                          <?php else: ?>
                            <button class="btn btn-sm" style="background:#e0f2fe;border-color:var(--blue);color:var(--blue);box-shadow:2px 2px 0 var(--blue);" onclick="openSummary('<?= $sid ?>', '<?= addslashes((string)($cs['lead_name'] ?? 'Calon Klien')) ?>')">
                              ✨ Ringkasan AI
                            </button>
                          <?php endif; ?>
                          <button class="btn btn-sm" style="background:#fee2e2;border-color:var(--danger);color:#991b1b;box-shadow:2px 2px 0 var(--danger);" onclick="deleteChat('<?= addslashes($sid) ?>', '<?= addslashes((string)($cs['lead_name'] ?? 'Pengunjung')) ?>', this.closest('tr'))">
                            🗑️ Hapus
                          </button>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <!-- END TAB 3 -->

      <!-- ================================================
           TAB 4: TRAFFIC PENGUNJUNG
           ================================================ -->
      <div id="tab-traffic" class="tab-panel" style="display:none;">
        <div class="section-card">
          <div class="card-header">
            <div>
              <div class="card-title">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                Analisa Lalu Lintas Kunjungan
              </div>
              <div class="card-sub">Data pengunjung yang membuka website, perangkat, referrer, dan interaksi yang dilakukan</div>
            </div>
          </div>
          <div class="tbl-wrap">
            <table>
              <thead><tr>
                <th>Waktu</th><th>Halaman</th><th>Perangkat & Browser</th>
                <th>Referrer / Sumber</th><th>Interaksi</th><th>IP Address</th><th>Aksi</th>
              </tr></thead>
              <tbody>
                <?php if (empty($visitorsList)): ?>
                  <tr><td colspan="6"><div class="empty-state"><div class="empty-state-emoji">👁️</div><div class="empty-state-text">Belum ada data kunjungan tercatat.</div></div></td></tr>
                <?php else: ?>
                  <?php foreach ($visitorsList as $vis):
                    $page = (string)$vis['page'];
                    $isComic = str_contains($page, 'index2');
                  ?>
                    <tr>
                      <td style="font-size:0.72rem; font-family:var(--font-mono); color:#64748b; white-space:nowrap;">
                        <?= htmlspecialchars(date('d M Y', strtotime((string)$vis['created_at']))) ?><br>
                        <?= htmlspecialchars(date('H:i:s', strtotime((string)$vis['created_at']))) ?>
                      </td>
                      <td>
                        <?php if ($isComic): ?>
                          <span style="background:var(--yellow); color:var(--ink); border:1.5px solid var(--ink); padding:0.18rem 0.5rem; border-radius:100px; font-size:0.7rem; font-weight:800; box-shadow:1px 1px 0 var(--ink);">🎨 Edisi Komik</span>
                        <?php else: ?>
                          <span style="background:var(--ink); color:#fff; border:1.5px solid var(--ink); padding:0.18rem 0.5rem; border-radius:100px; font-size:0.7rem; font-weight:800;">🌐 Edisi Klasik</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <div style="font-weight:700; font-size:0.8rem;">
                          <?php if ((string)$vis['device_type'] === 'Mobile / Tablet'): ?>
                            <span class="device-mobile">📱 <?= htmlspecialchars((string)$vis['device_type']) ?></span>
                          <?php else: ?>
                            <span class="device-desktop">💻 <?= htmlspecialchars((string)$vis['device_type']) ?></span>
                          <?php endif; ?>
                        </div>
                        <div style="font-size:0.7rem; color:#64748b; margin-top:2px;"><?= htmlspecialchars((string)$vis['browser']) ?> · <?= htmlspecialchars((string)$vis['os']) ?></div>
                      </td>
                      <td style="max-width:180px; font-size:0.73rem; word-break:break-all; color:#64748b;">
                        <?= htmlspecialchars((string)($vis['referrer'] ?: 'Direct / Bookmark')) ?>
                      </td>
                      <td>
                        <span style="background:var(--paper-2); border:1.5px solid var(--ink); padding:0.2rem 0.55rem; border-radius:var(--radius-sm); font-size:0.72rem; font-weight:700; box-shadow:1px 1px 0 var(--ink);">
                          <?= htmlspecialchars((string)($vis['interaction_summary'] ?: 'Kunjungan Halaman')) ?>
                        </span>
                      </td>
                      <td style="font-family:var(--font-mono); font-size:0.72rem; color:#94a3b8;"><?= htmlspecialchars((string)$vis['ip_address']) ?></td>
                      <td style="white-space:nowrap;">
                        <button class="btn btn-sm" style="background:#fee2e2;border-color:var(--danger);color:#991b1b;box-shadow:2px 2px 0 var(--danger);" onclick="deleteVisitor(<?= (int)$vis['id'] ?>, this.closest('tr'))">
                          🗑️ Hapus
                        </button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <!-- END TAB 4 -->

    </div><!-- /page-content -->
  </div><!-- /main-area -->
</div><!-- /admin-layout -->


<!-- ===================================================
     MODAL: TRANSKRIP CHAT
     =================================================== -->
<div class="modal-overlay" id="transcriptModal">
  <div class="modal" style="max-width:680px;">
    <div class="modal-head">
      <div>
        <div class="modal-head-title" id="transcriptTitle">💬 Transkrip Obrolan</div>
        <div style="font-size:0.72rem; color:#64748b; margin-top:2px;" id="transcriptSub">Session ID: -</div>
      </div>
      <button class="modal-close-btn" onclick="closeModal('transcriptModal')">✕</button>
    </div>
    <div class="modal-body" id="transcriptBody">
      <div class="empty-state"><div class="empty-state-emoji">⏳</div><div class="empty-state-text">Memuat percakapan...</div></div>
    </div>
    <div class="modal-foot" style="display:flex; justify-content:space-between; align-items:center;">
      <button class="btn btn-sm" id="btnSummarizeFromTranscript" style="background:#e0f2fe;border-color:var(--blue);color:var(--blue);box-shadow:2px 2px 0 var(--blue);" onclick="summarizeCurrentTranscript()">
        ✨ Ringkas dengan AI
      </button>
      <button class="btn btn-ghost btn-sm" onclick="closeModal('transcriptModal')">Tutup</button>
    </div>
  </div>
</div>

<!-- ===================================================
     MODAL: RINGKASAN AI PERCAKAPAN
     =================================================== -->
<div class="modal-overlay" id="summaryModal">
  <div class="modal" style="max-width:720px;">
    <div class="modal-head">
      <div>
        <div class="modal-head-title" id="summaryTitle">✨ Ringkasan Kebutuhan Klien (AI)</div>
        <div style="font-size:0.72rem; color:#64748b; margin-top:2px;" id="summarySub">Session ID: -</div>
      </div>
      <button class="modal-close-btn" onclick="closeModal('summaryModal')">✕</button>
    </div>
    <div class="modal-body" id="summaryBody" style="background:var(--paper);">
      <div class="empty-state">
        <div class="empty-state-emoji">🤖</div>
        <div class="empty-state-text">Memuat ringkasan...</div>
      </div>
    </div>
    <div class="modal-foot" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem;">
      <div id="summaryMeta" style="font-size:0.72rem; color:#64748b; font-family:var(--font-mono); font-weight:700;"></div>
      <div style="display:flex; gap:0.5rem; align-items:center;">
        <button class="btn btn-ghost btn-sm" id="btnRefreshSummary" style="display:none;" onclick="openSummary(_currentSummarySid, _currentSummaryName, true)">🔄 Ringkas Ulang (AI)</button>
        <button class="btn btn-ghost btn-sm" onclick="closeModal('summaryModal')">Tutup</button>
        <button class="btn btn-yellow btn-sm" id="btnCopySummary" onclick="copySummaryText()">
          📋 Salin Ringkasan
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ===================================================
     MODAL: CATATAN FOLLOW-UP
     =================================================== -->
<div class="modal-overlay" id="notesModal">
  <div class="modal" style="max-width:500px;">
    <div class="modal-head">
      <div class="modal-head-title">✏️ Catatan Progres Follow-up</div>
      <button class="modal-close-btn" onclick="closeModal('notesModal')">✕</button>
    </div>
    <div class="modal-body">
      <p style="font-size:0.82rem; font-weight:600; color:#64748b; margin-bottom:0.75rem;" id="notesClientLabel">Catatan untuk:</p>
      <input type="hidden" id="notesLeadId">
      <textarea id="notesTextarea" class="form-input" rows="5" style="resize:vertical;" placeholder="Tulis progres, misalnya: Sudah telpon hari ini, menunggu review proposal budget dari klien..."></textarea>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost btn-sm" onclick="closeModal('notesModal')">Batal</button>
      <button class="btn btn-yellow btn-sm" onclick="saveNotes()">💾 Simpan Catatan</button>
    </div>
  </div>
</div>

<!-- ===================================================
     MODAL: DETAIL KEBUTUHAN KLIEN
     =================================================== -->
<div class="modal-overlay" id="detailModal">
  <div class="modal" style="max-width:580px;">
    <div class="modal-head">
      <div>
        <div class="modal-head-title" id="detailTitle">🔍 Detail Kebutuhan Calon Klien</div>
        <div style="font-size:0.72rem; color:#64748b; margin-top:2px;" id="detailRef">Ref: -</div>
      </div>
      <button class="modal-close-btn" onclick="closeModal('detailModal')">✕</button>
    </div>
    <div class="modal-body" id="detailBody" style="font-size:0.85rem; line-height:1.6;"></div>
    <div class="modal-foot">
      <button class="btn btn-ghost btn-sm" onclick="closeModal('detailModal')">Tutup</button>
    </div>
  </div>
</div>

<?php endif; ?>

<!-- ===================================================
     JAVASCRIPT
     =================================================== -->
<script>
  // ---- Tab Switching ----
  const TAB_META = {
    'tab-dashboard': { headline: '📊 Dashboard Analitik', sub: 'Ringkasan aktivitas dan insight utama portofolio' },
    'tab-leads':     { headline: '🎯 Leads & Follow-up',  sub: 'Kelola calon klien dan tindak lanjut kontak WhatsApp' },
    'tab-chats':     { headline: '💬 Obrolan AI Chat',    sub: 'Baca transkrip percakapan pengunjung dengan asisten AI' },
    'tab-traffic':   { headline: '👁️ Traffic Pengunjung', sub: 'Data kunjungan halaman, perangkat, dan interaksi' },
  };

  function switchTab(btn) {
    const tabId = btn.dataset.tab;
    document.querySelectorAll('.nav-item').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(p => p.style.display = 'none');
    btn.classList.add('active');
    const panel = document.getElementById(tabId);
    if (panel) panel.style.display = 'block';
    const meta = TAB_META[tabId] || {};
    const hl = document.getElementById('pageHeadline');
    const sub = document.getElementById('pageSub');
    if (hl) hl.textContent = meta.headline || '';
    if (sub) sub.textContent = meta.sub || '';
    closeSidebar();
  }

  function switchTabByName(tabId) {
    const btn = document.querySelector(`.nav-item[data-tab="${tabId}"]`);
    if (btn) switchTab(btn);
  }

  // ---- Sidebar Mobile ----
  function openSidebar() {
    document.getElementById('sidebar').classList.add('open');
    document.getElementById('sidebarOverlay').classList.add('open');
  }
  function closeSidebar() {
    document.getElementById('sidebar')?.classList.remove('open');
    document.getElementById('sidebarOverlay')?.classList.remove('open');
  }

  // ---- Lead Filter ----
  function filterLeads() {
    const q = (document.getElementById('searchLeads')?.value || '').toLowerCase();
    const st = document.getElementById('filterStatus')?.value || '';
    const src = document.getElementById('filterSource')?.value || '';
    const rows = document.querySelectorAll('#leadsTable tbody tr');
    let visible = 0;
    rows.forEach(r => {
      const ms = (r.getAttribute('data-search') || '').toLowerCase();
      const rs = r.getAttribute('data-status') || '';
      const rsrc = r.getAttribute('data-source') || '';
      const ok = (!q || ms.includes(q)) && (!st || rs === st) && (!src || rsrc === src);
      r.style.display = ok ? '' : 'none';
      if (ok) visible++;
    });
    const c = document.getElementById('leadsCount');
    if (c) c.textContent = visible;
  }

  // ---- Update Status ----
  async function updateStatus(id, status, sel) {
    sel.className = 'status-sel st-' + status;
    try {
      await fetch('admin.php?action=update_followup', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, status })
      });
    } catch(e) {}
  }

  // ---- Delete Lead ----
  async function deleteLead(id, name, rowEl) {
    if (!confirm(`⚠️ Hapus lead "${name}" (ID: ${id})?\n\nData ini tidak bisa dikembalikan.`)) return;
    try {
      const res = await fetch('admin.php?action=delete_lead', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
      });
      const data = await res.json();
      if (data.success) {
        rowEl.style.transition = 'all 0.3s';
        rowEl.style.opacity = '0';
        rowEl.style.transform = 'translateX(-20px)';
        setTimeout(() => { rowEl.remove(); filterLeads(); }, 300);
      } else {
        alert('Gagal menghapus. Coba lagi.');
      }
    } catch(e) { alert('Error: ' + e.message); }
  }

  // ---- Delete Chat Session ----
  async function deleteChat(sessionId, name, rowEl) {
    if (!confirm(`⚠️ Hapus sesi chat "${name}"?\n\nSemua pesan dalam sesi ini akan ikut terhapus.`)) return;
    try {
      const res = await fetch('admin.php?action=delete_chat', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ session_id: sessionId })
      });
      const data = await res.json();
      if (data.success) {
        rowEl.style.transition = 'all 0.3s';
        rowEl.style.opacity = '0';
        rowEl.style.transform = 'translateX(-20px)';
        setTimeout(() => { rowEl.remove(); }, 300);
      } else {
        alert('Gagal menghapus sesi chat. Coba lagi.');
      }
    } catch(e) { alert('Error: ' + e.message); }
  }

  // ---- Delete Visitor Record ----
  async function deleteVisitor(id, rowEl) {
    if (!confirm(`⚠️ Hapus data kunjungan ini?\n\nData tidak bisa dikembalikan.`)) return;
    try {
      const res = await fetch('admin.php?action=delete_visitor', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
      });
      const data = await res.json();
      if (data.success) {
        rowEl.style.transition = 'all 0.3s';
        rowEl.style.opacity = '0';
        rowEl.style.transform = 'translateX(-20px)';
        setTimeout(() => { rowEl.remove(); }, 300);
      } else {
        alert('Gagal menghapus data kunjungan.');
      }
    } catch(e) { alert('Error: ' + e.message); }
  }

  // ---- Sort Table ----
  let _sortCol = -1, _sortDir = 1;
  function sortTable(colIdx, thEl) {
    const tbody = document.querySelector('#leadsTable tbody');
    if (!tbody) return;
    const rows = Array.from(tbody.querySelectorAll('tr'));
    if (_sortCol === colIdx) { _sortDir *= -1; } else { _sortDir = 1; _sortCol = colIdx; }
    rows.sort((a, b) => {
      const av = (a.cells[colIdx]?.textContent || '').trim().toLowerCase();
      const bv = (b.cells[colIdx]?.textContent || '').trim().toLowerCase();
      return av.localeCompare(bv, 'id') * _sortDir;
    });
    rows.forEach(r => tbody.appendChild(r));
    // Update header indicator
    document.querySelectorAll('#leadsTable th').forEach(th => {
      th.querySelector('.sort-arrow')?.remove();
    });
    const arr = document.createElement('span');
    arr.className = 'sort-arrow';
    arr.textContent = _sortDir === 1 ? ' ▲' : ' ▼';
    arr.style.fontSize = '0.65rem';
    thEl.appendChild(arr);
  }

  // ---- Modals ----
  function closeModal(id) {
    document.getElementById(id)?.classList.remove('open');
  }

  // esc() untuk innerHTML — encode HTML entities
  function esc(s) {
    if (!s) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
  }
  // txt() untuk textContent — kembalikan teks asli tanpa encode (untuk ditampilkan sebagai plain text)
  function txt(s) { return s ? String(s) : ''; }

  // ---- Transcript & Summary State ----
  let _currentSummaryText = '';
  let _currentTranscriptSid = '';
  let _currentTranscriptName = '';

  // ---- Transcript ----
  async function viewTranscript(sid, name) {
    _currentTranscriptSid = sid;
    _currentTranscriptName = name;
    const m = document.getElementById('transcriptModal');
    document.getElementById('transcriptTitle').textContent = '💬 Transkrip: ' + name;
    document.getElementById('transcriptSub').textContent = 'Session: ' + sid;
    document.getElementById('transcriptBody').innerHTML = '<div class="empty-state"><div class="empty-state-emoji">⏳</div><div class="empty-state-text">Memuat...</div></div>';
    m.classList.add('open');
    try {
      const res = await fetch('admin.php?action=get_transcript&session_id=' + encodeURIComponent(sid));
      const data = await res.json();
      if (data.success && data.messages?.length) {
        let html = '';
        data.messages.forEach(msg => {
          const isUser = msg.sender === 'user';
          const label = isUser ? '👤 Calon Klien' : '🤖 Eri AI';
          const t = msg.created_at ? msg.created_at.split(' ')[1] || '' : '';
          html += `
            <div class="bubble-row ${isUser ? 'user' : 'bot'}">
              <div class="bubble-meta">${esc(label)} · ${esc(t)}</div>
              <div class="bubble">${esc(msg.message)}</div>
            </div>`;
        });
        document.getElementById('transcriptBody').innerHTML = html;
      } else {
        document.getElementById('transcriptBody').innerHTML = '<div class="empty-state"><div class="empty-state-emoji">💬</div><div class="empty-state-text">Tidak ada pesan dalam sesi ini.</div></div>';
      }
    } catch(e) {
      document.getElementById('transcriptBody').innerHTML = '<div class="empty-state"><div class="empty-state-emoji">❌</div><div class="empty-state-text">Gagal memuat transkrip.</div></div>';
    }
  }

  function summarizeCurrentTranscript() {
    if (!_currentTranscriptSid) return;
    closeModal('transcriptModal');
    openSummary(_currentTranscriptSid, _currentTranscriptName);
  }

  // ---- AI Summary ----
  let _currentSummarySid = '';
  let _currentSummaryName = '';

  async function openSummary(sid, name, force = false) {
    _currentSummaryText = '';
    _currentSummarySid = sid;
    _currentSummaryName = name;
    const m = document.getElementById('summaryModal');
    document.getElementById('summaryTitle').textContent = '✨ Ringkasan Kebutuhan: ' + (name || 'Calon Klien');
    document.getElementById('summarySub').textContent = 'Session ID: ' + sid;
    document.getElementById('summaryMeta').textContent = '';
    const btnCopy = document.getElementById('btnCopySummary');
    const btnRef = document.getElementById('btnRefreshSummary');
    btnCopy.style.display = 'none';
    if (btnRef) btnRef.style.display = 'none';
    btnCopy.innerHTML = '📋 Salin Ringkasan';
    btnCopy.style.background = 'var(--yellow)';
    btnCopy.style.color = 'var(--ink)';

    document.getElementById('summaryBody').innerHTML = `
      <div class="empty-state" style="padding:2.5rem 1rem;">
        <div class="empty-state-emoji" style="font-size:3rem; display:inline-block; animation:floatIcon 1.6s ease-in-out infinite;">🤖</div>
        <div class="empty-state-text" style="font-size:1.05rem; font-weight:800; margin-top:0.75rem;">${force ? 'Menghubungi AI untuk analisa ulang...' : 'Memuat ringkasan kebutuhan...'}</div>
        <div style="font-size:0.78rem; color:#64748b; margin-top:6px;">Mengekstrak inti kebutuhan, spesifikasi fitur, estimasi budget, dan langkah follow-up.</div>
      </div>`;
    m.classList.add('open');

    try {
      const res = await fetch('admin.php?action=summarize_chat', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ session_id: sid, force_refresh: force })
      });
      const data = await res.json();
      if (data.success && data.summary) {
        _currentSummaryText = data.summary;
        btnCopy.style.display = 'inline-flex';
        if (btnRef) btnRef.style.display = 'inline-flex';
        document.getElementById('summaryMeta').textContent = data.provider || 'AI Intelligence';

        const box = document.createElement('div');
        box.style.cssText = 'background:#fff;border:var(--border-thin);border-radius:var(--radius-sm);padding:1.25rem;font-size:0.84rem;line-height:1.7;font-family:var(--font-mono);white-space:pre-wrap;color:var(--ink);box-shadow:var(--shadow-sm);word-break:break-word;user-select:all;';
        box.textContent = data.summary;

        const tip = document.createElement('div');
        tip.style.cssText = 'font-size:0.75rem;color:#64748b;margin-top:0.85rem;display:flex;align-items:center;gap:0.4rem;';
        const isDb = data.from_cache ? '✅ Ringkasan ini dimuat langsung dari database (hemat AI quota).' : '💾 Ringkasan ini baru dibuat oleh AI dan telah otomatis tersimpan ke database.';
        tip.innerHTML = `💡 <em>${isDb} Siap dicopas langsung ke WhatsApp / CRM untuk follow-up klien.</em>`;

        const body = document.getElementById('summaryBody');
        body.innerHTML = '';
        body.appendChild(box);
        body.appendChild(tip);
      } else {
        document.getElementById('summaryBody').innerHTML = `
          <div class="empty-state" style="padding:2.5rem 1rem;">
            <div class="empty-state-emoji">⚠️</div>
            <div class="empty-state-text">${esc(data.error || 'Gagal membuat ringkasan AI.')}</div>
            <button class="btn btn-sm btn-yellow" style="margin-top:1rem;" onclick="openSummary('${esc(sid)}', '${esc(name)}', true)">🔄 Coba Lagi</button>
          </div>`;
      }
    } catch(e) {
      document.getElementById('summaryBody').innerHTML = `
        <div class="empty-state" style="padding:2.5rem 1rem;">
          <div class="empty-state-emoji">❌</div>
          <div class="empty-state-text">Terjadi kendala: ${esc(e.message)}</div>
          <button class="btn btn-sm btn-yellow" style="margin-top:1rem;" onclick="openSummary('${esc(sid)}', '${esc(name)}', true)">🔄 Coba Lagi</button>
        </div>`;
    }
  }

  async function copySummaryText() {
    if (!_currentSummaryText) return;
    const btn = document.getElementById('btnCopySummary');
    try {
      await navigator.clipboard.writeText(_currentSummaryText);
      btn.innerHTML = '✅ Berhasil Disalin!';
      btn.style.background = 'var(--wa-green)';
      btn.style.color = '#fff';
      setTimeout(() => {
        btn.innerHTML = '📋 Salin Ringkasan';
        btn.style.background = 'var(--yellow)';
        btn.style.color = 'var(--ink)';
      }, 2500);
    } catch(e) {
      // Fallback manual copy
      const ta = document.createElement('textarea');
      ta.value = _currentSummaryText;
      document.body.appendChild(ta);
      ta.select();
      document.execCommand('copy');
      document.body.removeChild(ta);
      btn.innerHTML = '✅ Berhasil Disalin!';
      btn.style.background = 'var(--wa-green)';
      btn.style.color = '#fff';
      setTimeout(() => {
        btn.innerHTML = '📋 Salin Ringkasan';
        btn.style.background = 'var(--yellow)';
        btn.style.color = 'var(--ink)';
      }, 2500);
    }
  }

  // ---- Notes ----
  function openNotes(id, name, currentNotes) {
    document.getElementById('notesLeadId').value = id;
    document.getElementById('notesClientLabel').textContent = `Catatan follow-up untuk: ${name}`;
    document.getElementById('notesTextarea').value = currentNotes || '';
    document.getElementById('notesModal').classList.add('open');
  }

  async function saveNotes() {
    const id = parseInt(document.getElementById('notesLeadId').value);
    const notes = document.getElementById('notesTextarea').value.trim();
    try {
      const res = await fetch('admin.php?action=update_followup', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, notes })
      });
      const data = await res.json();
      if (data.success) {
        const el = document.getElementById('notesPreview_' + id);
        if (el) el.innerHTML = notes ? esc(notes) : '<em style="color:#94a3b8;">belum ada catatan</em>';
        closeModal('notesModal');
      }
    } catch(e) {}
  }

  // ---- Lead Detail ----
  function openDetail(lead) {
    // Gunakan textContent untuk judul agar tidak ada &amp;
    document.getElementById('detailTitle').textContent = '🔍 ' + txt(lead.name || '-');
    document.getElementById('detailRef').textContent = 'Ref: ' + txt(lead.lead_ref || '-');
    let feats = [];
    try { feats = JSON.parse(lead.selected_features || '[]'); } catch(e) {}
    const body = document.getElementById('detailBody');
    // Buat elemen secara programatik agar karakter & tampil asli (bukan &amp;)
    body.innerHTML = '';

    // Kartu identitas
    const card = document.createElement('div');
    card.style.cssText = 'background:var(--cream);border:var(--border);border-radius:var(--radius-sm);padding:1rem;margin-bottom:1rem;box-shadow:var(--shadow-sm);';
    const nameEl = document.createElement('div');
    nameEl.style.cssText = 'font-family:var(--font-display);font-size:1.15rem;font-weight:800;';
    nameEl.textContent = txt(lead.name || '-');
    const waEl = document.createElement('div');
    waEl.style.cssText = 'color:var(--blue);font-family:var(--font-mono);font-size:0.82rem;';
    waEl.textContent = txt(lead.whatsapp || '-');
    card.appendChild(nameEl); card.appendChild(waEl);
    if (lead.company) { const el = document.createElement('div'); el.style.cssText='font-size:0.78rem;color:#64748b;'; el.textContent = '🏢 ' + txt(lead.company); card.appendChild(el); }
    if (lead.email)   { const el = document.createElement('div'); el.style.cssText='font-size:0.78rem;color:#64748b;'; el.textContent = '✉️ ' + txt(lead.email);   card.appendChild(el); }
    body.appendChild(card);

    // Grid info proyek
    const grid = document.createElement('div');
    grid.style.cssText = 'display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-bottom:0.75rem;';
    const mkCell = (label, val, badge) => {
      const d = document.createElement('div');
      const lbl = document.createElement('div'); lbl.style.cssText='font-weight:800;font-size:0.8rem;margin-bottom:2px;'; lbl.textContent = label;
      const v = document.createElement(badge ? 'span' : 'div');
      if (badge) v.className = badge;
      v.style.fontSize = '0.8rem';
      if (!badge) v.style.color = '#64748b';
      v.textContent = txt(val || '-');
      d.appendChild(lbl); d.appendChild(v); return d;
    };
    grid.appendChild(mkCell('Kategori Proyek', lead.project_category, 'badge-cat'));
    grid.appendChild(mkCell('Status Proyek', lead.project_status || 'Baru'));
    grid.appendChild(mkCell('Platform', '📱 ' + txt(lead.target_platform || 'Web')));
    grid.appendChild(mkCell('Timeline', lead.timeline));
    body.appendChild(grid);

    // Budget
    const budgetWrap = document.createElement('div'); budgetWrap.style.marginBottom='0.75rem';
    const budgetLbl = document.createElement('div'); budgetLbl.style.cssText='font-weight:800;font-size:0.8rem;margin-bottom:4px;'; budgetLbl.textContent='Estimasi Budget';
    const budgetBadge = document.createElement('span'); budgetBadge.className='badge-budget'; budgetBadge.style.fontSize='0.82rem';
    budgetBadge.textContent = txt(lead.budget_range || '-');
    budgetWrap.appendChild(budgetLbl); budgetWrap.appendChild(budgetBadge);
    body.appendChild(budgetWrap);

    // Fitur
    const featWrap = document.createElement('div'); featWrap.style.marginBottom='0.75rem';
    const featLbl = document.createElement('div'); featLbl.style.cssText='font-weight:800;font-size:0.8rem;margin-bottom:4px;'; featLbl.textContent='Fitur yang Diinginkan';
    featWrap.appendChild(featLbl);
    if (feats.length) {
      feats.forEach(f => { const sp = document.createElement('span'); sp.className='inline-tag'; sp.textContent=txt(f); featWrap.appendChild(sp); featWrap.appendChild(document.createTextNode(' ')); });
    } else {
      const none = document.createElement('span'); none.style.cssText='color:#94a3b8;font-size:0.78rem;'; none.textContent='Belum ada fitur spesifik'; featWrap.appendChild(none);
    }
    body.appendChild(featWrap);

    // Catatan klien
    const notesWrap = document.createElement('div');
    const notesLbl = document.createElement('div'); notesLbl.style.cssText='font-weight:800;font-size:0.8rem;margin-bottom:4px;'; notesLbl.textContent='Catatan Kebutuhan Klien';
    const notesBox = document.createElement('div'); notesBox.style.cssText='background:var(--cream);border:1.5px solid rgba(22,32,44,.2);border-radius:var(--radius-sm);padding:0.65rem;font-size:0.8rem;color:#475569;line-height:1.5;';
    notesBox.textContent = txt(lead.notes || 'Tidak ada catatan.');
    notesWrap.appendChild(notesLbl); notesWrap.appendChild(notesBox);
    body.appendChild(notesWrap);

    // Footer info
    const footer = document.createElement('div'); footer.style.cssText='margin-top:0.75rem;padding-top:0.75rem;border-top:1.5px dashed rgba(22,32,44,.2);font-size:0.68rem;color:#94a3b8;';
    footer.textContent = 'Sumber: ' + (lead.source==='ai_chat'?'🤖 AI Chat':'📋 Form') + ' · IP: ' + txt(lead.ip_address||'-') + ' · Waktu: ' + txt(lead.created_at||'-');
    body.appendChild(footer);

    document.getElementById('detailModal').classList.add('open');
  }
</script>
</body>
</html>
