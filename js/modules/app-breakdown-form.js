/**
 * Simplified & Professional Application Breakdown & Lead Generation Controller
 * Pricing starts from 500k to Enterprise. Validates with Zod schema.
 * Eri Nur Sofa Portfolio
 */

(function () {
  'use strict';

  let overlay, form, closeBtn, cancelBtn, submitBtn, successView, errorLabels = {};

  // Pricing benchmarks mapping: Starts from 500k
  const CATEGORY_BENCHMARKS = {
    'Mini Project / Fitur Spesifik': {
      price: 'Mulai Rp 500 rb – Rp 2 jt',
      time: '1 – 4 Hari',
      note: 'Cocok untuk perbaikan modul, formulir khusus, integrasi bot WA sederhana, atau fitur mini.'
    },
    'Landing Page / Web Profil': {
      price: 'Rp 1,5 jt – Rp 4 jt',
      time: '1 – 2 Minggu',
      note: 'Desain kustom bernilai tinggi, loading super cepat, SEO optimal, dan tombol kontak WhatsApp.'
    },
    'Aplikasi Kasir (POS) & Stok': {
      price: 'Rp 3,5 jt – Rp 12 jt',
      time: '2 – 4 Minggu',
      note: 'Kasir barcode, cetak thermal nota, kontrol inventori, laporan laba rugi, dan notif WhatsApp.'
    },
    'Sistem Informasi Kustom / Instansi': {
      price: 'Rp 8 jt – Rp 35 jt+',
      time: '1 – 2 Bulan (3 – 6 Minggu)',
      note: 'Multi-role user (seperti E-Sankem), alur kerja digital berjenjang, dashboard monitoring, dan bimtek.'
    },
    'Otomasi AI & Bot Cerdas': {
      price: 'Rp 2,5 jt – Rp 15 jt',
      time: '2 – 4 Minggu',
      note: 'Asisten AI cerdas, OCR struk belanja/dokumen, bot multi-channel Telegram/WA, dan database sinkron.'
    }
  };

  function init() {
    createDOM();
    bindEvents();
    updateEstimateBanner();
  }

  function createDOM() {
    overlay = document.createElement('div');
    overlay.id = 'ensBreakdownModal';
    overlay.className = 'ens-breakdown-overlay';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-label', 'Formulir Estimasi & Breakdown Aplikasi');

    overlay.innerHTML = `
      <div class="ens-breakdown-card">
        <div class="ens-breakdown-header">
          <div class="ens-breakdown-header-content">
            <h2>Breakdown Kebutuhan Aplikasi</h2>
            <p>Fleksibel & transparan mulai dari <strong>Rp 500 rb</strong>. Dapatkan estimasi realistis dalam hitungan detik.</p>
          </div>
          <button type="button" class="ens-icon-btn" id="ensCloseBreakdownBtn" aria-label="Tutup Formulir" title="Tutup">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <!-- Form View -->
        <form id="ensBreakdownForm" class="ens-breakdown-body" novalidate>
          
          <!-- Hidden anti-spam honeypot -->
          <input type="text" name="website_hp" style="display:none;" tabindex="-1" autocomplete="off">

          <!-- 1. Kategori Proyek Ringkas -->
          <div class="ens-form-group">
            <label class="ens-form-label">
              1. Pilih Kategori Kebutuhan Anda <span class="req">*</span>
            </label>
            <div class="ens-category-grid" id="ensCategoryGrid">
              <label class="ens-category-option active">
                <input type="radio" name="project_category" value="Mini Project / Fitur Spesifik" checked>
                <div>
                  <div class="ens-category-title">⚡ Mini Project / Fitur Spesifik</div>
                  <span style="font-size:0.72rem; color:var(--ai-accent); font-weight:700;">Mulai 500k • Cepat</span>
                </div>
              </label>
              <label class="ens-category-option">
                <input type="radio" name="project_category" value="Landing Page / Web Profil">
                <div>
                  <div class="ens-category-title">🌐 Landing Page / Web Profil</div>
                  <span style="font-size:0.72rem; color:var(--ai-text-muted);">Mulai 1,5 jt • SEO</span>
                </div>
              </label>
              <label class="ens-category-option">
                <input type="radio" name="project_category" value="Aplikasi Kasir (POS) & Stok">
                <div>
                  <div class="ens-category-title">🛒 Aplikasi Kasir (POS) & Stok</div>
                  <span style="font-size:0.72rem; color:var(--ai-text-muted);">Mulai 3,5 jt • Barcode & Struk</span>
                </div>
              </label>
              <label class="ens-category-option">
                <input type="radio" name="project_category" value="Sistem Informasi Kustom / Instansi">
                <div>
                  <div class="ens-category-title">🏢 Sistem Informasi / Instansi</div>
                  <span style="font-size:0.72rem; color:var(--ai-text-muted);">Mulai 8 jt • Dashboard & Alur</span>
                </div>
              </label>
              <label class="ens-category-option">
                <input type="radio" name="project_category" value="Otomasi AI & Bot Cerdas">
                <div>
                  <div class="ens-category-title">🤖 Otomasi AI & Bot Cerdas</div>
                  <span style="font-size:0.72rem; color:var(--ai-text-muted);">Mulai 2,5 jt • OCR & Chatbot</span>
                </div>
              </label>
            </div>
            <span class="ens-error-label" id="err_project_category"></span>
          </div>

          <!-- 2. Status Proyek -->
          <div class="ens-form-group">
            <label class="ens-form-label">
              2. Status Proyek Saat Ini <span class="req">*</span>
            </label>
            <div class="ens-pill-grid" id="ensStatusGrid">
              <label class="ens-radio-pill active">
                <input type="radio" name="project_status" value="🌱 Bangun Baru dari Nol" checked>
                <span>🌱 Bangun Baru dari Nol</span>
              </label>
              <label class="ens-radio-pill">
                <input type="radio" name="project_status" value="🔧 Perbaikan / Tambah Fitur (Mulai 500k)">
                <span>🔧 Perbaikan / Tambah Fitur (Mulai 500k)</span>
              </label>
            </div>
          </div>

          <!-- 3. Target Platform (Web & Android, No Desktop) -->
          <div class="ens-form-group">
            <label class="ens-form-label">
              3. Target Platform Aplikasi <span class="req">*</span>
            </label>
            <div class="ens-pill-grid" id="ensPlatformGrid">
              <label class="ens-radio-pill active">
                <input type="checkbox" name="target_platform[]" value="🌐 Web Browser / Web App" checked>
                <span>🌐 Web Browser / Web App</span>
              </label>
              <label class="ens-radio-pill">
                <input type="checkbox" name="target_platform[]" value="📱 Android App (APK)">
                <span>📱 Android App (APK)</span>
              </label>
            </div>
          </div>

          <!-- 4. Checklist Modul / Fitur Ringkas (Chip pills) -->
          <div class="ens-form-group">
            <label class="ens-form-label">
              4. Fitur Kunci yang Ingin Disertakan <span style="font-weight:400; font-size:0.8rem; color:var(--ai-text-secondary);">(Opsional, klik untuk pilih)</span>
            </label>
            <div class="ens-pill-grid" id="ensFeaturesPillGrid">
              <label class="ens-radio-pill active">
                <input type="checkbox" name="selected_features[]" value="Multi-User & Hak Akses" checked>
                <span>🔐 Multi-User & Hak Akses</span>
              </label>
              <label class="ens-radio-pill active">
                <input type="checkbox" name="selected_features[]" value="Dashboard Monitoring" checked>
                <span>📊 Dashboard Monitoring</span>
              </label>
              <label class="ens-radio-pill">
                <input type="checkbox" name="selected_features[]" value="Payment Gateway QRIS">
                <span>💳 Payment QRIS / Transfer</span>
              </label>
              <label class="ens-radio-pill">
                <input type="checkbox" name="selected_features[]" value="Cetak Thermal & PDF">
                <span>🖨️ Cetak Struk / Export PDF</span>
              </label>
              <label class="ens-radio-pill">
                <input type="checkbox" name="selected_features[]" value="Barcode Scanner & Stok">
                <span>📦 Barcode Scanner & Stok</span>
              </label>
              <label class="ens-radio-pill">
                <input type="checkbox" name="selected_features[]" value="Integrasi AI & OCR Struk">
                <span>🧠 Integrasi AI / OCR Struk</span>
              </label>
            </div>
          </div>

          <!-- Dynamic Live Estimation Banner -->
          <div class="ens-estimate-banner" id="ensEstimateBanner">
            <div>
              <div class="ens-estimate-meta">
                <h5>Estimasi Transparan Mas Eri:</h5>
              </div>
              <div class="ens-estimate-value" id="ensEstimatePrice">Mulai Rp 500 rb – Rp 2 jt</div>
            </div>
            <div class="ens-estimate-note" id="ensEstimateNote">
              Waktu pengerjaan sekitar 1 – 4 Hari. Cocok untuk perbaikan modul, formulir khusus, atau otomasi bot ringkas.
            </div>
          </div>

          <!-- 5. Pilihan Alokasi Budget -->
          <div class="ens-form-group">
            <label class="ens-form-label">
              5. Alokasi Anggaran (Budget) Anda <span class="req">*</span>
            </label>
            <div class="ens-pill-grid" id="ensBudgetGrid">
              <label class="ens-radio-pill active">
                <input type="radio" name="budget_range" value="Rp 500 rb – Rp 2 Juta (Mini / Fitur)" checked>
                <span>Rp 500 rb – Rp 2 Jt (Mini / Fitur)</span>
              </label>
              <label class="ens-radio-pill">
                <input type="radio" name="budget_range" value="Rp 2 Juta – Rp 5 Juta (Standar)">
                <span>Rp 2 Jt – Rp 5 Jt (Standar)</span>
              </label>
              <label class="ens-radio-pill">
                <input type="radio" name="budget_range" value="Rp 5 Juta – Rp 15 Juta (Bisnis & POS)">
                <span>Rp 5 Jt – Rp 15 Jt (Bisnis & POS)</span>
              </label>
              <label class="ens-radio-pill">
                <input type="radio" name="budget_range" value="> Rp 15 Juta (Instansi / Kompleks)">
                <span>&gt; Rp 15 Jt (Instansi / Kompleks)</span>
              </label>
            </div>
            <span class="ens-error-label" id="err_budget_range"></span>
          </div>

          <!-- 6. Target Waktu Pengerjaan -->
          <div class="ens-form-group">
            <label class="ens-form-label">
              6. Target Waktu Pengerjaan (Timeline Realistis) <span style="font-weight:400; font-size:0.78rem; color:var(--ai-text-secondary);">(Menyesuaikan skala agar tidak over-promise)</span>
            </label>
            <div class="ens-pill-grid" id="ensTimelineGrid">
              <label class="ens-radio-pill">
                <input type="radio" name="timeline" value="⚡ Kilat (1–4 Hari)">
                <span>⚡ Kilat (1–4 Hari)</span>
              </label>
              <label class="ens-radio-pill">
                <input type="radio" name="timeline" value="📅 Cepat (1–2 Minggu)">
                <span>📅 Cepat (1–2 Minggu)</span>
              </label>
              <label class="ens-radio-pill active">
                <input type="radio" name="timeline" value="🚀 Standar (2–4 Minggu)" checked>
                <span>🚀 Standar (2–4 Minggu)</span>
              </label>
              <label class="ens-radio-pill">
                <input type="radio" name="timeline" value="🏛️ Komprehensif (1–2 Bulan)">
                <span>🏛️ Komprehensif (1–2 Bulan)</span>
              </label>
              <label class="ens-radio-pill">
                <input type="radio" name="timeline" value="☕ Fleksibel / Sesuai Kesepakatan">
                <span>☕ Fleksibel / Sesuai Kesepakatan</span>
              </label>
            </div>
          </div>

          <!-- 7. Kontak Praktis & Ringkas -->
          <div class="ens-form-group">
            <label class="ens-form-label">7. Kontak Calon Klien <span class="req">*</span></label>
            <div class="ens-contact-grid">
              <div>
                <input type="text" name="name" class="ens-input-field" placeholder="Nama Lengkap Anda *" required>
                <span class="ens-error-label" id="err_name"></span>
              </div>
              <div>
                <input type="tel" name="whatsapp" class="ens-input-field" placeholder="No. WhatsApp Aktif (08xx) *" required>
                <span class="ens-error-label" id="err_whatsapp"></span>
              </div>
            </div>
          </div>

          <!-- 8. Gambaran Detail Kebutuhan -->
          <div class="ens-form-group">
            <label class="ens-form-label">
              8. Gambaran Detail Kebutuhan Sistem <span class="req">*</span>
              <span style="font-weight:400; font-size:0.78rem; color:var(--ai-text-secondary); margin-left:0.25rem;">(Wajib diisi agar Mas Eri lebih paham)</span>
            </label>
            <textarea name="notes" class="ens-input-field" rows="3" placeholder="Ceritakan alur kerja atau masalah yang ingin Anda selesaikan... (Contoh: Butuh sistem kasir untuk 2 cabang toko, scan barcode produk, cetak thermal nota, dan laporan laba-rugi otomatis)" required></textarea>
            <span class="ens-error-label" id="err_notes"></span>
          </div>

          <!-- Action Buttons -->
          <div class="ens-breakdown-footer">
            <button type="button" class="ens-btn ens-btn-ghost" id="ensCancelBreakdownBtn">Batal</button>
            <button type="submit" class="ens-btn ens-btn-primary" id="ensSubmitLeadBtn">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                <polyline points="7 3 7 8 15 8"></polyline>
              </svg>
              Simpan &amp; Dapatkan Estimasi
            </button>
          </div>
        </form>

        <!-- Success Modal View -->
        <div class="ens-success-view" id="ensSuccessView">
          <div class="ens-success-badge">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
          </div>
          <h3 style="margin:0 0 0.5rem 0; font-size:1.35rem; font-weight:800; color:var(--ai-text-primary);">Rincian Kebutuhan Berhasil Disimpan!</h3>
          <p style="margin:0 0 1rem 0; font-size:0.9rem; color:var(--ai-text-secondary); max-width:460px; margin-left:auto; margin-right:auto;">
            Terima kasih! Rincian sistem Anda telah tercatat rapi di database resmi Mas Eri dengan nomor referensi:
          </p>
          <div style="display:inline-flex; align-items:center; gap:0.5rem; padding:0.5rem 1.25rem; border-radius:9999px; background:rgba(59,130,246,0.15); border:1px solid rgba(59,130,246,0.3); font-family:var(--ai-mono); font-weight:800; font-size:1.05rem; margin-bottom:1.25rem;" id="ensLeadRefBadge">
            LEAD-XXXXXX
          </div>
          <p style="margin:0 0 1.25rem 0; font-size:0.875rem; color:var(--ai-text-secondary); max-width:440px; margin-left:auto; margin-right:auto; line-height:1.5;">
            Untuk mendapatkan penawaran &amp; estimasi langsung, silakan klik tombol WhatsApp di bawah:
          </p>
          <div style="display:flex; justify-content:center; gap:0.75rem; flex-wrap:wrap; margin-bottom:1rem;">
            <a href="#" target="_blank" rel="noopener noreferrer" class="ens-btn ens-btn-wa" id="ensSuccessWaBtn" style="padding:0.75rem 1.5rem; font-size:0.95rem; font-weight:700;">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.976.58 2.028.928 3.149.929 3.182 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.771-5.768-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.312.045-.694.067-2.127-.527-1.745-.724-2.883-2.493-2.97-2.609-.086-.115-.71-1.002-.71-1.921 0-.918.47-1.37.643-1.558.174-.187.378-.235.505-.235.127 0 .254.002.366.007.119.006.278-.045.435.333.16.386.549 1.341.597 1.439.048.098.08.213.016.34-.064.127-.096.206-.191.317-.095.112-.2.249-.286.334-.096.096-.196.2-.084.392.112.193.498.823 1.069 1.332.735.655 1.355.858 1.547.954.192.096.304.08.417-.048.112-.128.481-.56.609-.752.127-.193.255-.16.43-.096.175.064 1.112.524 1.303.62.191.096.318.143.366.223.048.079.048.461-.096.866z"/>
              </svg>
              Lanjutkan ke WhatsApp Mas Eri
            </a>
            <button type="button" class="ens-btn ens-btn-ghost" id="ensFinishModalBtn">Selesai / Tutup</button>
          </div>
          <div style="font-size:0.75rem; color:var(--ai-text-secondary); opacity:0.8;">
            ✓ Mas Eri akan menghubungi balik via WhatsApp dalam 1x24 jam kerja jika Anda belum sempat chat.
          </div>
        </div>

      </div>
    `;

    document.body.appendChild(overlay);

    // Cache elements
    form = overlay.querySelector('#ensBreakdownForm');
    closeBtn = overlay.querySelector('#ensCloseBreakdownBtn');
    cancelBtn = overlay.querySelector('#ensCancelBreakdownBtn');
    submitBtn = overlay.querySelector('#ensSubmitLeadBtn');
    successView = overlay.querySelector('#ensSuccessView');

    errorLabels = {
      name: overlay.querySelector('#err_name'),
      whatsapp: overlay.querySelector('#err_whatsapp'),
      project_category: overlay.querySelector('#err_project_category'),
      budget_range: overlay.querySelector('#err_budget_range'),
      notes: overlay.querySelector('#err_notes')
    };
  }

  function bindEvents() {
    closeBtn.addEventListener('click', closeBreakdown);
    cancelBtn.addEventListener('click', closeBreakdown);

    // Category options toggle
    const categoryGrid = overlay.querySelector('#ensCategoryGrid');
    categoryGrid.addEventListener('change', function (e) {
      if (e.target.name === 'project_category') {
        categoryGrid.querySelectorAll('.ens-category-option').forEach(el => el.classList.remove('active'));
        e.target.closest('.ens-category-option').classList.add('active');
        updateEstimateBanner();
        saveDraft();
      }
    });

    // Project status radio toggle
    const statusGrid = overlay.querySelector('#ensStatusGrid');
    if (statusGrid) {
      statusGrid.addEventListener('change', function (e) {
        if (e.target.name === 'project_status') {
          statusGrid.querySelectorAll('.ens-radio-pill').forEach(el => el.classList.remove('active'));
          e.target.closest('.ens-radio-pill').classList.add('active');
          updateEstimateBanner();
          saveDraft();
        }
      });
    }

    // Platform checkbox toggle (Web & Android only)
    const platformGrid = overlay.querySelector('#ensPlatformGrid');
    if (platformGrid) {
      platformGrid.addEventListener('change', function (e) {
        if (e.target.type === 'checkbox') {
          const pill = e.target.closest('.ens-radio-pill');
          if (pill) {
            pill.classList.toggle('active', e.target.checked);
          }
          saveDraft();
        }
      });
    }

    // Features pill click toggle
    const featuresGrid = overlay.querySelector('#ensFeaturesPillGrid');
    featuresGrid.addEventListener('change', function (e) {
      if (e.target.type === 'checkbox') {
        const pill = e.target.closest('.ens-radio-pill');
        if (pill) {
          if (e.target.checked) {
            pill.classList.add('active');
          } else {
            pill.classList.remove('active');
          }
        }
        updateEstimateBanner();
        saveDraft();
      }
    });

    // Budget pill toggle
    const budgetGrid = overlay.querySelector('#ensBudgetGrid');
    budgetGrid.addEventListener('change', function (e) {
      if (e.target.name === 'budget_range') {
        budgetGrid.querySelectorAll('.ens-radio-pill').forEach(el => el.classList.remove('active'));
        e.target.closest('.ens-radio-pill').classList.add('active');
        saveDraft();
      }
    });

    // Timeline pill toggle
    const timelineGrid = overlay.querySelector('#ensTimelineGrid');
    if (timelineGrid) {
      timelineGrid.addEventListener('change', function (e) {
        if (e.target.name === 'timeline') {
          timelineGrid.querySelectorAll('.ens-radio-pill').forEach(el => el.classList.remove('active'));
          e.target.closest('.ens-radio-pill').classList.add('active');
          saveDraft();
        }
      });
    }

    // Autosave text inputs on typing
    form.addEventListener('input', function (e) {
      if (['name', 'whatsapp', 'notes'].includes(e.target.name)) {
        saveDraft();
      }
    });

    // Form submit
    form.addEventListener('submit', handleSubmit);

    // Finish modal button
    const finishBtn = overlay.querySelector('#ensFinishModalBtn');
    if (finishBtn) {
      finishBtn.addEventListener('click', closeBreakdown);
    }

    // Close on escape key
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && overlay.classList.contains('active')) {
        closeBreakdown();
      }
    });
  }

  const DRAFT_KEY = 'ens_breakdown_draft_v1';

  function saveDraft() {
    try {
      const formData = new FormData(form);
      const selectedFeatures = [];
      formData.getAll('selected_features[]').forEach(val => selectedFeatures.push(val));

      const platforms = [];
      formData.getAll('target_platform[]').forEach(val => platforms.push(val));

      const draft = {
        name: formData.get('name') || '',
        whatsapp: formData.get('whatsapp') || '',
        notes: formData.get('notes') || '',
        project_category: formData.get('project_category') || '',
        project_status: formData.get('project_status') || '',
        target_platform: platforms,
        timeline: formData.get('timeline') || '',
        budget_range: formData.get('budget_range') || '',
        selected_features: selectedFeatures
      };
      sessionStorage.setItem(DRAFT_KEY, JSON.stringify(draft));
    } catch (e) {}
  }

  function restoreDraft() {
    try {
      const raw = sessionStorage.getItem(DRAFT_KEY);
      if (!raw) return;
      const draft = JSON.parse(raw);
      if (!draft) return;

      if (draft.name && form.elements['name']) form.elements['name'].value = draft.name;
      if (draft.whatsapp && form.elements['whatsapp']) form.elements['whatsapp'].value = draft.whatsapp;
      if (draft.notes && form.elements['notes']) form.elements['notes'].value = draft.notes;

      if (draft.project_category) {
        const catRadio = form.querySelector(`input[name="project_category"][value="${draft.project_category}"]`);
        if (catRadio) {
          form.querySelectorAll('.ens-category-option').forEach(el => el.classList.remove('active'));
          catRadio.checked = true;
          catRadio.closest('.ens-category-option').classList.add('active');
        }
      }

      if (draft.project_status) {
        const statusRadio = form.querySelector(`input[name="project_status"][value="${draft.project_status}"]`);
        if (statusRadio) {
          form.querySelectorAll('#ensStatusGrid .ens-radio-pill').forEach(el => el.classList.remove('active'));
          statusRadio.checked = true;
          statusRadio.closest('.ens-radio-pill').classList.add('active');
        }
      }

      if (Array.isArray(draft.target_platform) && draft.target_platform.length > 0) {
        form.querySelectorAll('input[name="target_platform[]"]').forEach(cb => {
          const isSelected = draft.target_platform.includes(cb.value);
          cb.checked = isSelected;
          const pill = cb.closest('.ens-radio-pill');
          if (pill) {
            pill.classList.toggle('active', isSelected);
          }
        });
      }

      if (Array.isArray(draft.selected_features)) {
        form.querySelectorAll('input[name="selected_features[]"]').forEach(cb => {
          const isSelected = draft.selected_features.includes(cb.value);
          cb.checked = isSelected;
          const pill = cb.closest('.ens-radio-pill');
          if (pill) {
            if (isSelected) pill.classList.add('active');
            else pill.classList.remove('active');
          }
        });
      }

      if (draft.budget_range) {
        const budgetRadio = form.querySelector(`input[name="budget_range"][value="${draft.budget_range}"]`);
        if (budgetRadio) {
          form.querySelectorAll('#ensBudgetGrid .ens-radio-pill').forEach(el => el.classList.remove('active'));
          budgetRadio.checked = true;
          budgetRadio.closest('.ens-radio-pill').classList.add('active');
        }
      }

      if (draft.timeline) {
        const timelineRadio = form.querySelector(`input[name="timeline"][value="${draft.timeline}"]`);
        if (timelineRadio) {
          form.querySelectorAll('#ensTimelineGrid .ens-radio-pill').forEach(el => el.classList.remove('active'));
          timelineRadio.checked = true;
          timelineRadio.closest('.ens-radio-pill').classList.add('active');
        }
      }
    } catch (e) {}
  }

  function clearDraft() {
    try {
      sessionStorage.removeItem(DRAFT_KEY);
    } catch (e) {}
  }

  function updateEstimateBanner() {
    const selectedCategory = form.querySelector('input[name="project_category"]:checked');
    if (!selectedCategory) return;

    const catName = selectedCategory.value;
    const benchmark = CATEGORY_BENCHMARKS[catName] || {
      price: 'Mulai Rp 500 rb – Rp 2 jt',
      time: '1 – 4 Hari',
      note: 'Disesuaikan dengan skala fitur yang Anda butuhkan.'
    };

    // Calculate checked features count
    const checkedFeatures = form.querySelectorAll('input[name="selected_features[]"]:checked').length;

    // Check project status
    const statusRadio = form.querySelector('input[name="project_status"]:checked');
    const isMaintenance = statusRadio && statusRadio.value.includes('Perbaikan');

    const priceEl = overlay.querySelector('#ensEstimatePrice');
    const noteEl = overlay.querySelector('#ensEstimateNote');

    let dynamicPrice = benchmark.price;
    let dynamicNote = `Waktu pengerjaan sekitar ${benchmark.time}. ${benchmark.note}`;

    if (isMaintenance) {
      dynamicPrice = 'Mulai Rp 500 rb – Rp 2 jt (Perbaikan / Modul)';
      dynamicNote = 'Waktu pengerjaan sekitar 1 – 4 Hari. Fokus pada perbaikan bug, penambahan fitur spesifik, atau optimasi sistem yang sudah ada.';
    } else if (checkedFeatures >= 4) {
      dynamicNote = `Waktu pengerjaan sekitar ${benchmark.time}. (${checkedFeatures} fitur dipilih: mencakup integrasi modul komprehensif).`;
    }

    if (priceEl) priceEl.textContent = dynamicPrice;
    if (noteEl) noteEl.textContent = dynamicNote;
  }

  function openBreakdown() {
    overlay.classList.add('active');
    form.style.display = 'flex';
    successView.classList.remove('active');
    clearErrors();
    restoreDraft();
    updateEstimateBanner();
  }

  function closeBreakdown() {
    overlay.classList.remove('active');
  }

  function clearErrors() {
    Object.values(errorLabels).forEach(el => {
      if (el) {
        el.textContent = '';
        el.classList.remove('show');
      }
    });
  }

  let isSubmitting = false;

  async function handleSubmit(e) {
    e.preventDefault();
    if (isSubmitting) return;

    clearErrors();

    const formData = new FormData(form);
    const selectedFeatures = [];
    formData.getAll('selected_features[]').forEach(val => selectedFeatures.push(val));

    const platforms = [];
    formData.getAll('target_platform[]').forEach(val => platforms.push(val));
    if (platforms.length === 0) {
      platforms.push('🌐 Web Browser / Web App');
    }

    // Auto-clean spaces and hyphens from WhatsApp input
    const rawWa = (formData.get('whatsapp') || '').toString().trim();
    const cleanWa = rawWa.replace(/[\s\-\.\(\)]/g, '');

    const payload = {
      name: (formData.get('name') || '').toString().trim(),
      whatsapp: cleanWa,
      email: (formData.get('email') || '').toString().trim(),
      company: (formData.get('company') || '').toString().trim(),
      project_category: (formData.get('project_category') || '').toString().trim(),
      project_status: (formData.get('project_status') || '🌱 Bangun Baru dari Nol').toString().trim(),
      target_platform: platforms,
      selected_features: selectedFeatures,
      budget_range: (formData.get('budget_range') || '').toString().trim(),
      timeline: (formData.get('timeline') || '📅 Standar (1–2 Minggu)').toString().trim(),
      notes: (formData.get('notes') || '').toString().trim(),
      website_hp: formData.get('website_hp') || ''
    };

    // Client-side schema validation via Zod validator module
    if (window.PortfolioValidator) {
      const validation = window.PortfolioValidator.validateLead(payload);
      if (!validation.isValid) {
        for (const [field, message] of Object.entries(validation.errors)) {
          if (errorLabels[field]) {
            errorLabels[field].textContent = message;
            errorLabels[field].classList.add('show');
          }
        }
        return;
      }
    }

    // Submit to server endpoint with lock
    isSubmitting = true;
    submitBtn.disabled = true;
    submitBtn.innerHTML = `
      <svg class="ens-spinner" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:ens-spin 1s linear infinite;">
        <circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle>
        <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
      </svg>
      Menyimpan Data...
    `;

    try {
      const response = await fetch('api/lead.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      const result = await response.json();

      if (result.success) {
        // Clear saved draft on success
        clearDraft();

        // Show success screen
        form.style.display = 'none';
        successView.classList.add('active');

        const badge = overlay.querySelector('#ensLeadRefBadge');
        if (badge) badge.textContent = result.lead_ref;

        const waBtn = overlay.querySelector('#ensSuccessWaBtn');
        if (waBtn && result.whatsapp_url) {
          waBtn.href = result.whatsapp_url;
        }

        form.reset();
      } else {
        if (result.errors) {
          for (const [field, message] of Object.entries(result.errors)) {
            if (errorLabels[field]) {
              errorLabels[field].textContent = message;
              errorLabels[field].classList.add('show');
            }
          }
        } else {
          alert(result.error || 'Terjadi kesalahan. Silakan coba kembali.');
        }
      }
    } catch (err) {
      alert('Gagal mengirimkan data ke server. Periksa koneksi internet Anda atau hubungi langsung via WhatsApp.');
    } finally {
      isSubmitting = false;
      submitBtn.disabled = false;
      submitBtn.innerHTML = `
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
          <polyline points="17 21 17 13 7 13 7 21"></polyline>
          <polyline points="7 3 7 8 15 8"></polyline>
        </svg>
        Simpan &amp; Dapatkan Estimasi
      `;
    }
  }

  // Inject spinner animation style if not present
  if (!document.getElementById('ensSpinStyle')) {
    const style = document.createElement('style');
    style.id = 'ensSpinStyle';
    style.textContent = `@keyframes ens-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }`;
    document.head.appendChild(style);
  }

  // Public API
  window.PortfolioBreakdown = {
    open: openBreakdown,
    close: closeBreakdown
  };

  // Initialize on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
