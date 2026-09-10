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

