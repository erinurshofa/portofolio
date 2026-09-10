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

