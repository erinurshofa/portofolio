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
  <link rel="stylesheet" href="admin/assets/css/admin.css">
</head>
<body>

<?php if (!$isLoggedIn): ?>
  <?php include __DIR__ . '/login.php'; ?>
<?php else: ?>
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
      <?php include __DIR__ . '/../tabs/_tab_kpi.php'; ?>
      <?php include __DIR__ . '/../tabs/_tab_leads.php'; ?>
      <?php include __DIR__ . '/../tabs/_tab_chats.php'; ?>
      <?php include __DIR__ . '/../tabs/_tab_traffic.php'; ?>
    </div>
  </div>
</div>

<!-- ===================================================
     MODALS
     =================================================== -->
<?php include __DIR__ . '/../modals/_transcript_modal.php'; ?>
<?php include __DIR__ . '/../modals/_summary_modal.php'; ?>
<?php include __DIR__ . '/../modals/_notes_modal.php'; ?>
<?php include __DIR__ . '/../modals/_detail_modal.php'; ?>

<?php endif; ?>

<script src="admin/assets/js/admin.js"></script>
</body>
</html>
