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
