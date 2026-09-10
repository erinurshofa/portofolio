<?php
/**
 * Lead Storage & Notification Service
 * Secure local SQLite persistence + Telegram Bot dispatch + WhatsApp click format
 * Eri Nur Sofa Portfolio
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/SecurityGuard.php';

final class LeadStorage
{
    private static ?PDO $pdo = null;

    /**
     * Get or initialize SQLite PDO connection
     */
    public static function getConnection(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $dbPath = AppConfig::getDbPath();
        self::$pdo = new PDO('sqlite:' . $dbPath);
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Optimize SQLite performance & concurrency
        self::$pdo->exec('PRAGMA journal_mode = WAL;');
        self::$pdo->exec('PRAGMA synchronous = NORMAL;');

        self::createTableIfNotExists();

        return self::$pdo;
    }

    /**
     * Initialize leads table
     */
    private static function createTableIfNotExists(): void
    {
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS leads (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            lead_ref TEXT UNIQUE NOT NULL,
            name TEXT NOT NULL,
            whatsapp TEXT NOT NULL,
            email TEXT,
            company TEXT,
            project_category TEXT NOT NULL,
            project_status TEXT,
            target_platform TEXT,
            selected_features TEXT,
            budget_range TEXT,
            timeline TEXT,
            notes TEXT,
            source TEXT DEFAULT 'breakdown_form',
            followup_status TEXT DEFAULT 'belum_dikontak',
            followup_notes TEXT,
            ip_address TEXT,
            user_agent TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        CREATE INDEX IF NOT EXISTS idx_leads_created_at ON leads(created_at);
        CREATE INDEX IF NOT EXISTS idx_leads_ref ON leads(lead_ref);

        CREATE TABLE IF NOT EXISTS visitors (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            session_id TEXT NOT NULL,
            ip_address TEXT,
            page TEXT NOT NULL,
            referrer TEXT,
            device_type TEXT,
            browser TEXT,
            os TEXT,
            interaction_summary TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        CREATE INDEX IF NOT EXISTS idx_visitors_session ON visitors(session_id);
        CREATE INDEX IF NOT EXISTS idx_visitors_created_at ON visitors(created_at);

        CREATE TABLE IF NOT EXISTS chat_sessions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            session_id TEXT UNIQUE NOT NULL,
            lead_ref TEXT,
            visitor_ip TEXT,
            user_agent TEXT,
            device_type TEXT,
            message_count INTEGER DEFAULT 0,
            last_message TEXT,
            summary_intent TEXT,
            started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        CREATE INDEX IF NOT EXISTS idx_chat_sessions_sid ON chat_sessions(session_id);
        CREATE INDEX IF NOT EXISTS idx_chat_sessions_lead ON chat_sessions(lead_ref);

        CREATE TABLE IF NOT EXISTS chat_messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            session_id TEXT NOT NULL,
            sender TEXT NOT NULL,
            message TEXT NOT NULL,
            provider TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        CREATE INDEX IF NOT EXISTS idx_chat_messages_sid ON chat_messages(session_id);
SQL;
        self::$pdo->exec($sql);

        // Safe column migration for existing databases
        try {
            self::$pdo->exec("ALTER TABLE leads ADD COLUMN project_status TEXT;");
        } catch (Throwable $e) {}
        try {
            self::$pdo->exec("ALTER TABLE leads ADD COLUMN target_platform TEXT;");
        } catch (Throwable $e) {}
        try {
            self::$pdo->exec("ALTER TABLE leads ADD COLUMN source TEXT DEFAULT 'breakdown_form';");
        } catch (Throwable $e) {}
        try {
            self::$pdo->exec("ALTER TABLE leads ADD COLUMN followup_status TEXT DEFAULT 'belum_dikontak';");
        } catch (Throwable $e) {}
        try {
            self::$pdo->exec("ALTER TABLE leads ADD COLUMN followup_notes TEXT;");
        } catch (Throwable $e) {}
        // Tambah kolom created_at jika belum ada (database lama sebelum schema diperbarui)
        try {
            self::$pdo->exec("ALTER TABLE leads ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP;");
            // Isi nilai created_at yang null dengan CURRENT_TIMESTAMP
            self::$pdo->exec("UPDATE leads SET created_at = CURRENT_TIMESTAMP WHERE created_at IS NULL;");
        } catch (Throwable $e) {}
        try {
            self::$pdo->exec("ALTER TABLE leads ADD COLUMN user_agent TEXT;");
        } catch (Throwable $e) {}
        // Buat tabel analytics jika belum ada (upgrade dari versi lama)
        try {
            self::$pdo->exec("CREATE TABLE IF NOT EXISTS visitors (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                session_id TEXT NOT NULL,
                ip_address TEXT,
                page TEXT NOT NULL DEFAULT '/',
                referrer TEXT,
                device_type TEXT,
                browser TEXT,
                os TEXT,
                interaction_summary TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );");
        } catch (Throwable $e) {}
        try {
            self::$pdo->exec("CREATE TABLE IF NOT EXISTS chat_sessions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                session_id TEXT UNIQUE NOT NULL,
                lead_ref TEXT,
                visitor_ip TEXT,
                user_agent TEXT,
                device_type TEXT,
                message_count INTEGER DEFAULT 0,
                last_message TEXT,
                summary_intent TEXT,
                started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );");
        } catch (Throwable $e) {}
        try {
            self::$pdo->exec("CREATE TABLE IF NOT EXISTS chat_messages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                session_id TEXT NOT NULL,
                sender TEXT NOT NULL,
                message TEXT NOT NULL,
                provider TEXT,
                lead_ref TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );");
        } catch (Throwable $e) {}
        try {
            self::$pdo->exec("ALTER TABLE chat_sessions ADD COLUMN ai_summary TEXT;");
        } catch (Throwable $e) {}
        try {
            self::$pdo->exec("ALTER TABLE chat_sessions ADD COLUMN ai_summary_at DATETIME;");
        } catch (Throwable $e) {}
    }

    /**
     * Save or update existing lead by lead_ref
     */
    public static function saveOrUpdateLead(array $data, ?string $existingRef = null): array
    {
        $db = self::getConnection();

        if (!empty($existingRef)) {
            $stmt = $db->prepare('SELECT * FROM leads WHERE lead_ref = :ref LIMIT 1');
            $stmt->execute([':ref' => $existingRef]);
            $existing = $stmt->fetch();

            if ($existing) {
                $name = SecurityGuard::sanitizeString($data['name'] ?? '', 100);
                if ($name === '' || $name === 'Calon Klien (AI Chat)') {
                    $name = $existing['name'];
                }

                $whatsapp = SecurityGuard::sanitizeString($data['whatsapp'] ?? '', 30);
                if ($whatsapp === '' || $whatsapp === '(Belum mengisi kontak WA)' || $whatsapp === '080000000000') {
                    $whatsapp = $existing['whatsapp'];
                }

                $category = !empty($data['project_category'])
                    ? SecurityGuard::sanitizeString($data['project_category'], 80)
                    : $existing['project_category'];

                $projectStatus = !empty($data['project_status'])
                    ? SecurityGuard::sanitizeString($data['project_status'], 80)
                    : $existing['project_status'];

                if (!empty($data['target_platform'])) {
                    if (is_array($data['target_platform'])) {
                        $platformList = array_map([SecurityGuard::class, 'sanitizeString'], $data['target_platform']);
                        $platform = implode(', ', array_filter($platformList));
                    } else {
                        $platform = SecurityGuard::sanitizeString($data['target_platform'], 120);
                    }
                } else {
                    $platform = $existing['target_platform'];
                }

                if (!empty($data['selected_features']) && is_array($data['selected_features'])) {
                    $featuresArr = array_values(array_filter(array_map([SecurityGuard::class, 'sanitizeString'], $data['selected_features'])));
                    $features = json_encode($featuresArr, JSON_UNESCAPED_UNICODE);
                } else {
                    $features = $existing['selected_features'];
                    $featuresArr = json_decode((string)$features, true) ?: [];
                }

                $budget = !empty($data['budget_range'])
                    ? SecurityGuard::sanitizeString($data['budget_range'], 60)
                    : $existing['budget_range'];

                $timeline = !empty($data['timeline'])
                    ? SecurityGuard::sanitizeString($data['timeline'], 60)
                    : $existing['timeline'];

                $newNotes = SecurityGuard::sanitizeString($data['notes'] ?? '', 1000);
                $notes = !empty($newNotes) ? $newNotes : $existing['notes'];

                $upStmt = $db->prepare(<<<SQL
                    UPDATE leads SET
                        name = :name,
                        whatsapp = :whatsapp,
                        project_category = :category,
                        project_status = :project_status,
                        target_platform = :platform,
                        selected_features = :features,
                        budget_range = :budget,
                        timeline = :timeline,
                        notes = :notes
                    WHERE lead_ref = :ref
SQL);
                $upStmt->execute([
                    ':name' => $name,
                    ':whatsapp' => $whatsapp,
                    ':category' => $category,
                    ':project_status' => $projectStatus,
                    ':platform' => $platform,
                    ':features' => $features,
                    ':budget' => $budget,
                    ':timeline' => $timeline,
                    ':notes' => $notes,
                    ':ref' => $existingRef
                ]);

                return [
                    'lead_ref' => $existingRef,
                    'name' => $name,
                    'whatsapp' => $whatsapp,
                    'project_category' => $category,
                    'project_status' => $projectStatus,
                    'target_platform' => $platform,
                    'selected_features' => $featuresArr,
                    'budget_range' => $budget,
                    'timeline' => $timeline,
                    'notes' => $notes,
                    'source' => $existing['source'] ?? 'ai_chat',
                    'is_update' => true
                ];
            }
        }

        return self::saveLead($data);
    }

    /**
     * Save lead to SQLite
     */
    public static function saveLead(array $data): array
    {
        $db = self::getConnection();

        $ref = 'LEAD-' . strtoupper(bin2hex(random_bytes(4)));
        $name = SecurityGuard::sanitizeString($data['name'] ?? '', 100);
        $whatsapp = SecurityGuard::sanitizeString($data['whatsapp'] ?? '', 30);
        $email = SecurityGuard::sanitizeString($data['email'] ?? '', 100);
        $company = SecurityGuard::sanitizeString($data['company'] ?? '', 120);
        $category = SecurityGuard::sanitizeString($data['project_category'] ?? 'Umum', 80);
        $projectStatus = SecurityGuard::sanitizeString($data['project_status'] ?? 'Bangun Baru dari Nol', 80);
        $source = SecurityGuard::sanitizeString($data['source'] ?? 'breakdown_form', 40);

        if ($source === 'ai_chat') {
            if ($name === '') {
                $name = 'Calon Klien (AI Chat)';
            }
            if ($whatsapp === '' || $whatsapp === '080000000000') {
                $whatsapp = '(Belum mengisi kontak WA)';
            }
        }
        
        // Target platform (strictly web / android, array or string)
        if (is_array($data['target_platform'] ?? null)) {
            $platformList = array_map([SecurityGuard::class, 'sanitizeString'], $data['target_platform']);
            $platform = implode(', ', array_filter($platformList));
        } else {
            $platform = SecurityGuard::sanitizeString($data['target_platform'] ?? 'Web Browser / Web App', 120);
        }
        if ($platform === '') {
            $platform = 'Web Browser / Web App';
        }

        $featuresArr = is_array($data['selected_features'] ?? null)
            ? array_values(array_filter(array_map([SecurityGuard::class, 'sanitizeString'], $data['selected_features'])))
            : [];
        $features = json_encode($featuresArr, JSON_UNESCAPED_UNICODE);

        $budget = SecurityGuard::sanitizeString($data['budget_range'] ?? 'Belum ditentukan', 60);
        $timeline = SecurityGuard::sanitizeString($data['timeline'] ?? 'Standar (2–4 Minggu)', 60);
        $notes = SecurityGuard::sanitizeString($data['notes'] ?? '', 1000);
        $ip = SecurityGuard::getClientIp();
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250);

        $stmt = $db->prepare(<<<SQL
            INSERT INTO leads (
                lead_ref, name, whatsapp, email, company,
                project_category, project_status, target_platform,
                selected_features, budget_range,
                timeline, notes, source, ip_address, user_agent
            ) VALUES (
                :lead_ref, :name, :whatsapp, :email, :company,
                :category, :project_status, :target_platform,
                :features, :budget,
                :timeline, :notes, :source, :ip, :ua
            )
SQL);

        $stmt->execute([
            ':lead_ref' => $ref,
            ':name' => $name,
            ':whatsapp' => $whatsapp,
            ':email' => $email ?: null,
            ':company' => $company ?: null,
            ':category' => $category,
            ':project_status' => $projectStatus,
            ':target_platform' => $platform,
            ':features' => $features,
            ':budget' => $budget,
            ':timeline' => $timeline,
            ':notes' => $notes ?: null,
            ':source' => $source,
            ':ip' => $ip,
            ':ua' => $ua
        ]);

        // Attempt Telegram notification if token exists
        self::sendTelegramAlert([
            'ref' => $ref,
            'name' => $name,
            'whatsapp' => $whatsapp,
            'company' => $company,
            'category' => $category,
            'project_status' => $projectStatus,
            'target_platform' => $platform,
            'features' => $featuresArr,
            'budget' => $budget,
            'timeline' => $timeline,
            'notes' => $notes,
            'source' => $source
        ]);

        // Format features bullet points for WhatsApp pre-fill
        $featureBulletList = '';
        if (!empty($featuresArr)) {
            $featureBulletList = "• Fitur yang Dibutuhkan:\n  - " . implode("\n  - ", $featuresArr) . "\n";
        }

        // Generate formatted WhatsApp URL for user convenience
        $waMessage = "Halo Mas Eri Nur Sofa,\n"
            . "Saya ingin konsultasi pembuatan sistem (Ref: {$ref}):\n\n"
            . "• Nama: {$name}\n"
            . ($company ? "• Usaha/Instansi: {$company}\n" : '')
            . "• Kategori: {$category}\n"
            . "• Status Proyek: {$projectStatus}\n"
            . "• Platform Target: {$platform}\n"
            . $featureBulletList
            . "• Estimasi Budget: {$budget}\n"
            . "• Target Waktu: {$timeline}\n"
            . ($notes ? "• Catatan Kebutuhan: {$notes}\n" : '')
            . "\nMohon info ketersediaan dan langkah selanjutnya. Terima kasih!";

        $cleanWaText = htmlspecialchars_decode($waMessage, ENT_QUOTES | ENT_HTML5);
        $waUrl = 'https://wa.me/6285641280960?text=' . urlencode($cleanWaText);

        return [
            'success' => true,
            'lead_ref' => $ref,
            'whatsapp_url' => $waUrl
        ];
    }

    /**
     * Dispatch notification via Telegram Bot (Optional, fail-safe)
     */
    private static function sendTelegramAlert(array $info): void
    {
        $botToken = AppConfig::getTelegramBotToken();
        $chatId = AppConfig::getTelegramChatId();

        if (empty($botToken) || empty($chatId)) {
            return;
        }

        $featuresText = !empty($info['features']) ? implode(', ', $info['features']) : '-';
        $text = "🚀 <b>LEAD BARU MASUK - PORTOFOLIO</b>\n"
            . "<b>Ref:</b> {$info['ref']}\n"
            . "<b>Nama:</b> {$info['name']}\n"
            . "<b>WA:</b> {$info['whatsapp']}\n"
            . ($info['company'] ? "<b>Instansi/Usaha:</b> {$info['company']}\n" : "")
            . "<b>Kategori:</b> {$info['category']}\n"
            . "<b>Status:</b> {$info['project_status']}\n"
            . "<b>Platform:</b> {$info['target_platform']}\n"
            . "<b>Fitur Kunci:</b> {$featuresText}\n"
            . "<b>Budget:</b> {$info['budget']}\n"
            . "<b>Timeline:</b> {$info['timeline']}\n"
            . ($info['notes'] ? "<b>Catatan:</b> {$info['notes']}\n" : "");

        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode([
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML'
            ]),
            CURLOPT_TIMEOUT => 4,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        @curl_exec($ch);
        @curl_close($ch);
    }

    /**
     * Record visitor page view or interaction ping
     */
    public static function recordVisitor(array $data): void
    {
        $db = self::getConnection();
        $sessionId = SecurityGuard::sanitizeString($data['session_id'] ?? bin2hex(random_bytes(8)), 64);
        $page = SecurityGuard::sanitizeString($data['page'] ?? 'index2.html', 150);
        $referrer = SecurityGuard::sanitizeString($data['referrer'] ?? 'Direct / None', 255);
        $ip = SecurityGuard::getClientIp();
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

        // Parse basic device and browser info
        $deviceType = 'Desktop';
        if (preg_match('/(android|iphone|ipad|mobile)/i', $ua)) {
            $deviceType = 'Mobile / Tablet';
        }

        $browser = 'Other';
        if (stripos($ua, 'Chrome') !== false && stripos($ua, 'Edg') === false) {
            $browser = 'Chrome';
        } elseif (stripos($ua, 'Edg') !== false) {
            $browser = 'Edge';
        } elseif (stripos($ua, 'Firefox') !== false) {
            $browser = 'Firefox';
        } elseif (stripos($ua, 'Safari') !== false) {
            $browser = 'Safari';
        }

        $os = 'Unknown';
        if (stripos($ua, 'Windows') !== false) {
            $os = 'Windows';
        } elseif (stripos($ua, 'Android') !== false) {
            $os = 'Android';
        } elseif (stripos($ua, 'iPhone') !== false || stripos($ua, 'iPad') !== false || stripos($ua, 'Macintosh') !== false) {
            $os = 'Apple iOS / macOS';
        } elseif (stripos($ua, 'Linux') !== false) {
            $os = 'Linux';
        }

        $interaction = SecurityGuard::sanitizeString($data['interaction'] ?? 'Page Visit', 100);

        // Check if session already recorded within last 30 minutes for this page
        $stmt = $db->prepare('SELECT id, interaction_summary FROM visitors WHERE session_id = :sid AND page = :page ORDER BY id DESC LIMIT 1');
        $stmt->execute([':sid' => $sessionId, ':page' => $page]);
        $existing = $stmt->fetch();

        if ($existing) {
            $summary = $existing['interaction_summary'];
            if (!empty($interaction) && $interaction !== 'Page Visit' && !str_contains($summary, $interaction)) {
                $summary .= ', ' . $interaction;
            }
            $up = $db->prepare('UPDATE visitors SET interaction_summary = :inter, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
            $up->execute([':inter' => $summary, ':id' => $existing['id']]);
        } else {
            $ins = $db->prepare(<<<SQL
                INSERT INTO visitors (session_id, ip_address, page, referrer, device_type, browser, os, interaction_summary)
                VALUES (:sid, :ip, :page, :ref, :dev, :browser, :os, :inter)
SQL);
            $ins->execute([
                ':sid' => $sessionId,
                ':ip' => $ip,
                ':page' => $page,
                ':ref' => $referrer ?: 'Direct',
                ':dev' => $deviceType,
                ':browser' => $browser,
                ':os' => $os,
                ':inter' => $interaction
            ]);
        }
    }

    /**
     * Get recent visitors
     */
    public static function getVisitors(int $limit = 100): array
    {
        $db = self::getConnection();
        $stmt = $db->prepare('SELECT * FROM visitors ORDER BY id DESC LIMIT :lim');
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Record a chat message and update chat session
     */
    public static function recordChatMessage(string $sessionId, string $sender, string $message, ?string $provider = null, ?string $leadRef = null): void
    {
        $db = self::getConnection();
        $cleanSid = SecurityGuard::sanitizeString($sessionId, 64);
        if (empty($cleanSid)) {
            $cleanSid = 'session_' . bin2hex(random_bytes(6));
        }

        // 1. Insert chat message
        $stmt = $db->prepare(<<<SQL
            INSERT INTO chat_messages (session_id, sender, message, provider)
            VALUES (:sid, :sender, :msg, :provider)
SQL);
        $stmt->execute([
            ':sid' => $cleanSid,
            ':sender' => $sender,
            ':msg' => $message,
            ':provider' => $provider
        ]);

        // 2. Insert or update chat session
        $ip = SecurityGuard::getClientIp();
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
        $deviceType = preg_match('/(android|iphone|ipad|mobile)/i', $ua) ? 'Mobile' : 'Desktop';

        $checkStmt = $db->prepare('SELECT id, message_count, lead_ref FROM chat_sessions WHERE session_id = :sid LIMIT 1');
        $checkStmt->execute([':sid' => $cleanSid]);
        $session = $checkStmt->fetch();

        $previewMsg = mb_substr($message, 0, 150);

        if ($session) {
            $newLeadRef = !empty($leadRef) ? $leadRef : $session['lead_ref'];
            $up = $db->prepare(<<<SQL
                UPDATE chat_sessions SET
                    message_count = message_count + 1,
                    last_message = :last_msg,
                    lead_ref = :lref,
                    updated_at = CURRENT_TIMESTAMP
                WHERE session_id = :sid
SQL);
            $up->execute([
                ':last_msg' => ($sender === 'user' ? '👤 ' : '🤖 ') . $previewMsg,
                ':lref' => $newLeadRef,
                ':sid' => $cleanSid
            ]);
        } else {
            $ins = $db->prepare(<<<SQL
                INSERT INTO chat_sessions (session_id, lead_ref, visitor_ip, user_agent, device_type, message_count, last_message)
                VALUES (:sid, :lref, :ip, :ua, :dev, 1, :last_msg)
SQL);
            $ins->execute([
                ':sid' => $cleanSid,
                ':lref' => $leadRef,
                ':ip' => $ip,
                ':ua' => $ua,
                ':dev' => $deviceType,
                ':last_msg' => ($sender === 'user' ? '👤 ' : '🤖 ') . $previewMsg
            ]);
        }
    }

    /**
     * Get all chat sessions with summary
     */
    public static function getChatSessions(int $limit = 50): array
    {
        $db = self::getConnection();
        $stmt = $db->prepare(<<<SQL
            SELECT cs.*, l.name as lead_name, l.whatsapp as lead_wa, l.project_category
            FROM chat_sessions cs
            LEFT JOIN leads l ON cs.lead_ref = l.lead_ref
            ORDER BY cs.updated_at DESC
            LIMIT :lim
SQL);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get full transcript for a chat session
     */
    public static function getChatTranscript(string $sessionId): array
    {
        $db = self::getConnection();
        $stmt = $db->prepare('SELECT * FROM chat_messages WHERE session_id = :sid ORDER BY id ASC');
        $stmt->execute([':sid' => $sessionId]);
        return $stmt->fetchAll();
    }

    /**
     * Simpan ringkasan AI untuk suatu sesi chat ke database
     */
    public static function saveChatSummary(string $sessionId, string $summary): bool
    {
        $db = self::getConnection();
        $stmt = $db->prepare('UPDATE chat_sessions SET ai_summary = :summary, ai_summary_at = CURRENT_TIMESTAMP WHERE session_id = :sid');
        return $stmt->execute([
            ':summary' => $summary,
            ':sid'     => $sessionId
        ]);
    }

    /**
     * Ambil ringkasan AI yang sudah pernah tersimpan untuk suatu sesi chat
     */
    public static function getChatSummary(string $sessionId): ?array
    {
        $db = self::getConnection();
        $stmt = $db->prepare('SELECT ai_summary, ai_summary_at FROM chat_sessions WHERE session_id = :sid LIMIT 1');
        $stmt->execute([':sid' => $sessionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['ai_summary'])) {
            return $row;
        }
        return null;
    }

    /**
     * Update lead follow-up status and notes
     */
    public static function updateLeadFollowup(int $leadId, string $status, ?string $notes = null): bool
    {
        $db = self::getConnection();
        $allowedStatuses = ['belum_dikontak', 'diskusi_wa', 'proposal', 'deal', 'pending'];
        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'belum_dikontak';
        }

        $cleanNotes = $notes !== null ? SecurityGuard::sanitizeString($notes, 2000) : null;

        if ($cleanNotes !== null) {
            $stmt = $db->prepare('UPDATE leads SET followup_status = :st, followup_notes = :notes WHERE id = :id');
            return $stmt->execute([':st' => $status, ':notes' => $cleanNotes, ':id' => $leadId]);
        } else {
            $stmt = $db->prepare('UPDATE leads SET followup_status = :st WHERE id = :id');
            return $stmt->execute([':st' => $status, ':id' => $leadId]);
        }
    }

    /**
     * Comprehensive Analytics & Intelligence Summary
     */
    public static function getAnalyticsSummary(): array
    {
        $db = self::getConnection();
        $today = date('Y-m-d');

        // 1. Leads counts
        $totalLeads = (int)$db->query('SELECT COUNT(*) FROM leads')->fetchColumn();
        $todayLeads = (int)$db->query("SELECT COUNT(*) FROM leads WHERE created_at LIKE '{$today}%'")->fetchColumn();

        // 2. Visitors counts
        $totalVisitors = (int)$db->query('SELECT COUNT(DISTINCT session_id) FROM visitors')->fetchColumn();
        $todayVisitors = (int)$db->query("SELECT COUNT(DISTINCT session_id) FROM visitors WHERE created_at LIKE '{$today}%'")->fetchColumn();

        // 3. Chat sessions
        $totalChats = (int)$db->query('SELECT COUNT(*) FROM chat_sessions')->fetchColumn();
        $todayChats = (int)$db->query("SELECT COUNT(*) FROM chat_sessions WHERE started_at LIKE '{$today}%'")->fetchColumn();

        // 4. Pending follow-up
        $pendingFollowup = (int)$db->query("SELECT COUNT(*) FROM leads WHERE followup_status = 'belum_dikontak' OR followup_status IS NULL")->fetchColumn();
        $dealCount = (int)$db->query("SELECT COUNT(*) FROM leads WHERE followup_status = 'deal'")->fetchColumn();

        // 5. Project category breakdown (What do they want?)
        $catStmt = $db->query('SELECT project_category, COUNT(*) as cnt FROM leads GROUP BY project_category ORDER BY cnt DESC');
        $categories = $catStmt->fetchAll();

        // 6. Top requested features aggregation
        $allLeads = $db->query('SELECT selected_features FROM leads WHERE selected_features IS NOT NULL')->fetchAll();
        $featureCounts = [];
        foreach ($allLeads as $row) {
            $feats = json_decode((string)$row['selected_features'], true);
            if (is_array($feats)) {
                foreach ($feats as $f) {
                    $trimmed = trim((string)$f);
                    if ($trimmed !== '') {
                        $featureCounts[$trimmed] = ($featureCounts[$trimmed] ?? 0) + 1;
                    }
                }
            }
        }
        arsort($featureCounts);
        $topFeatures = array_slice($featureCounts, 0, 10, true);

        // 7. Budget distribution
        $budgetStmt = $db->query('SELECT budget_range, COUNT(*) as cnt FROM leads GROUP BY budget_range ORDER BY cnt DESC');
        $budgets = $budgetStmt->fetchAll();

        // 8. Timeline distribution
        $timeStmt = $db->query('SELECT timeline, COUNT(*) as cnt FROM leads GROUP BY timeline ORDER BY cnt DESC');
        $timelines = $timeStmt->fetchAll();

        // 9. Last 7 days trend
        $trendData = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $dLabel = date('d M', strtotime($d));

            $vCount = (int)$db->query("SELECT COUNT(DISTINCT session_id) FROM visitors WHERE created_at LIKE '{$d}%'")->fetchColumn();
            $cCount = (int)$db->query("SELECT COUNT(*) FROM chat_sessions WHERE started_at LIKE '{$d}%'")->fetchColumn();
            $lCount = (int)$db->query("SELECT COUNT(*) FROM leads WHERE created_at LIKE '{$d}%'")->fetchColumn();

            $trendData[] = [
                'date' => $d,
                'label' => $dLabel,
                'visitors' => $vCount,
                'chats' => $cCount,
                'leads' => $lCount
            ];
        }

        return [
            'metrics' => [
                'total_leads' => $totalLeads,
                'today_leads' => $todayLeads,
                'total_visitors' => $totalVisitors,
                'today_visitors' => $todayVisitors,
                'total_chats' => $totalChats,
                'today_chats' => $todayChats,
                'pending_followup' => $pendingFollowup,
                'deal_count' => $dealCount
            ],
            'categories' => $categories,
            'top_features' => $topFeatures,
            'budgets' => $budgets,
            'timelines' => $timelines,
            'trend' => $trendData
        ];
    }
}
