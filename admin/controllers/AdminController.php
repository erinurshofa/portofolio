<?php
/**
 * AdminController — Lightweight Yii-style MVC Controller
 * Handles authentication, AJAX APIs, analytics aggregation, and view rendering.
 *
 * Eri Nur Sofa Portfolio (#LebihWarasPakaiSistem)
 */
declare(strict_types=1);

final class AdminController
{
    private bool $isLoggedIn = false;
    private string $loginError = '';

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->isLoggedIn = !empty($_SESSION['ens_admin_logged_in']);
    }

    /**
     * Dispatch incoming request to matching action
     */
    public function handleRequest(): void
    {
        $action = trim((string)($_GET['action'] ?? ''));

        // 1. Logout Action
        if ($action === 'logout') {
            $this->actionLogout();
            return;
        }

        // 2. Login Submit Action
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
            $this->actionLogin();
            return;
        }

        // 3. Authenticated AJAX Actions
        if ($this->isLoggedIn) {
            switch ($action) {
                case 'update_followup':
                    $this->actionUpdateFollowup();
                    return;
                case 'get_transcript':
                    $this->actionGetTranscript();
                    return;
                case 'summarize_chat':
                    $this->actionSummarizeChat();
                    return;
                case 'export_csv':
                    $this->actionExportCsv();
                    return;
                case 'delete_lead':
                    $this->actionDeleteLead();
                    return;
                case 'delete_chat':
                    $this->actionDeleteChat();
                    return;
                case 'delete_visitor':
                    $this->actionDeleteVisitor();
                    return;
            }
        }

        // 4. Default View (Dashboard / Login)
        $this->actionIndex();
    }

    /**
     * Action: Dashboard Index View
     */
    public function actionIndex(): void
    {
        $analytics    = [];
        $leadsList    = [];
        $chatSessions = [];
        $visitorsList = [];

        if ($this->isLoggedIn) {
            try {
                $analytics    = LeadStorage::getAnalyticsSummary();
                $db           = LeadStorage::getConnection();
                $leadsList    = $db->query('SELECT * FROM leads ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
                $chatSessions = LeadStorage::getChatSessions(50);
                $visitorsList = LeadStorage::getVisitors(60);
            } catch (Throwable $e) {
                error_log('[AdminController::actionIndex Exception] ' . $e->getMessage());
            }
        }

        $this->render('layouts/main', [
            'isLoggedIn'   => $this->isLoggedIn,
            'loginError'   => $this->loginError,
            'analytics'    => $analytics,
            'leadsList'    => $leadsList,
            'chatSessions' => $chatSessions,
            'visitorsList' => $visitorsList
        ]);
    }

    /**
     * Action: Process Login
     */
    public function actionLogin(): void
    {
        $adminPassword = AppConfig::getAdminPassword();
        $submittedPass = (string)($_POST['password'] ?? '');

        if (hash_equals($adminPassword, $submittedPass)) {
            $_SESSION['ens_admin_logged_in'] = true;
            header('Location: admin.php');
            exit;
        }

        $this->loginError = 'Kata sandi salah. Silakan coba lagi!';
        $this->isLoggedIn = false;
        $this->actionIndex();
    }

    /**
     * Action: Process Logout
     */
    public function actionLogout(): void
    {
        $_SESSION['ens_admin_logged_in'] = false;
        session_destroy();
        header('Location: admin.php');
        exit;
    }

    /**
     * Action: Update Follow-up Status & Notes (AJAX)
     */
    public function actionUpdateFollowup(): void
    {
        header('Content-Type: application/json');
        $raw    = file_get_contents('php://input');
        $data   = json_decode($raw ?: '', true);
        $leadId = (int)($data['id'] ?? 0);
        $status = (string)($data['status'] ?? 'belum_dikontak');
        $notes  = isset($data['notes']) ? (string)$data['notes'] : null;

        if ($leadId > 0) {
            $ok = LeadStorage::updateLeadFollowup($leadId, $status, $notes);
            echo json_encode(['success' => $ok]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid ID']);
        }
        exit;
    }

    /**
     * Action: Get Chat Transcript (AJAX)
     */
    public function actionGetTranscript(): void
    {
        header('Content-Type: application/json');
        $sid  = trim((string)($_GET['session_id'] ?? ''));
        $msgs = $sid ? LeadStorage::getChatTranscript($sid) : [];
        echo json_encode(['success' => true, 'messages' => $msgs]);
        exit;
    }

    /**
     * Action: Summarize Chat with AI & DB Caching (AJAX)
     */
    public function actionSummarizeChat(): void
    {
        header('Content-Type: application/json');
        $raw   = file_get_contents('php://input');
        $data  = json_decode($raw ?: '', true);
        $sid   = trim((string)($data['session_id'] ?? ''));
        $force = !empty($data['force_refresh']);

        if ($sid === '') {
            echo json_encode(['success' => false, 'error' => 'Session ID tidak valid']);
            exit;
        }

        // 1. Cek cache database jika tidak diminta force_refresh
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

        // 2. Ambil transkrip dari database
        $msgs = LeadStorage::getChatTranscript($sid);
        if (empty($msgs)) {
            echo json_encode(['success' => false, 'error' => 'Belum ada percakapan dalam sesi ini untuk diringkas.']);
            exit;
        }

        $db = LeadStorage::getConnection();
        $stmt = $db->prepare('SELECT cs.*, l.name as lead_name FROM chat_sessions cs LEFT JOIN leads l ON cs.lead_ref = l.lead_ref WHERE cs.session_id = :sid LIMIT 1');
        $stmt->execute([':sid' => $sid]);
        $sessionMeta = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        // 3. Ringkas dengan AI (Groq -> Gemini -> Fallback)
        $result = AiOrchestrator::summarizeTranscript($msgs, $sessionMeta);
        if (!empty($result['success']) && !empty($result['summary'])) {
            LeadStorage::saveChatSummary($sid, $result['summary']);
            $result['from_cache'] = false;
        }

        echo json_encode($result);
        exit;
    }

    /**
     * Action: Export Leads to CSV
     */
    public function actionExportCsv(): void
    {
        $db    = LeadStorage::getConnection();
        $leads = $db->query('SELECT * FROM leads ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="leads_eri_portfolio_' . date('Y-m-d_His') . '.csv"');

        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF"); // UTF-8 BOM
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

    /**
     * Action: Delete Lead (AJAX)
     */
    public function actionDeleteLead(): void
    {
        header('Content-Type: application/json');
        $raw    = file_get_contents('php://input');
        $data   = json_decode($raw ?: '', true);
        $leadId = (int)($data['id'] ?? 0);

        if ($leadId > 0) {
            try {
                $db   = LeadStorage::getConnection();
                $stmt = $db->prepare('DELETE FROM leads WHERE id = :id');
                $ok   = $stmt->execute([':id' => $leadId]);
                echo json_encode(['success' => $ok]);
            } catch (Throwable $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid ID']);
        }
        exit;
    }

    /**
     * Action: Delete Chat Session (AJAX)
     */
    public function actionDeleteChat(): void
    {
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

    /**
     * Action: Delete Visitor Record (AJAX)
     */
    public function actionDeleteVisitor(): void
    {
        header('Content-Type: application/json');
        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '', true);
        $vid = (int)($data['id'] ?? 0);

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

    /**
     * Render view template with parameters (Yii style)
     */
    public function render(string $viewPath, array $params = []): void
    {
        extract($params);
        $fullPath = dirname(__DIR__) . '/views/' . $viewPath . '.php';

        if (file_exists($fullPath)) {
            require $fullPath;
        } else {
            echo "View not found: " . htmlspecialchars($viewPath);
        }
    }
}
