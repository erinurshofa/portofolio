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
