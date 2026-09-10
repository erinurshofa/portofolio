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

