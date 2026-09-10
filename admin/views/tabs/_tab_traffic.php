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
