      <!-- ================================================
           TAB 2: LEADS & FOLLOW-UP
           ================================================ -->
      <div id="tab-leads" class="tab-panel" style="display:none;">
        <div class="section-card">
          <div class="filter-bar">
            <div style="display:flex; flex-wrap:wrap; gap:0.5rem; align-items:center;">
              <input type="text" class="filter-input" id="searchLeads" placeholder="🔍 Cari nama, WA, proyek..." oninput="filterLeads()">
              <select class="filter-input" id="filterStatus" style="min-width:140px;" onchange="filterLeads()">
                <option value="">Semua Status</option>
                <option value="belum_dikontak">🟡 Belum Dikontak</option>
                <option value="diskusi_wa">🔵 Diskusi WA</option>
                <option value="proposal">🟣 Proposal</option>
                <option value="deal">🟢 Deal</option>
                <option value="pending">⚪ Pending</option>
              </select>
              <select class="filter-input" id="filterSource" style="min-width:140px;" onchange="filterLeads()">
                <option value="">Semua Sumber</option>
                <option value="ai_chat">🤖 AI Chat</option>
                <option value="breakdown_form">📋 Form Breakdown</option>
              </select>
            </div>
            <div class="filter-count">
              <span id="leadsCount"><?= count($leadsList) ?></span> leads ditampilkan
            </div>
          </div>

          <div class="tbl-wrap">
            <table id="leadsTable">
              <thead><tr>
                <th onclick="sortTable(0,this)" style="cursor:pointer;user-select:none;" title="Klik untuk sort">Ref ↕</th>
                <th onclick="sortTable(1,this)" style="cursor:pointer;user-select:none;" title="Klik untuk sort">Sumber ↕</th>
                <th onclick="sortTable(2,this)" style="cursor:pointer;user-select:none;" title="Klik untuk sort">Waktu ↕</th>
                <th onclick="sortTable(3,this)" style="cursor:pointer;user-select:none;" title="Klik untuk sort">Calon Klien ↕</th>
                <th onclick="sortTable(4,this)" style="cursor:pointer;user-select:none;" title="Klik untuk sort">Kategori & Platform ↕</th>
                <th onclick="sortTable(5,this)" style="cursor:pointer;user-select:none;" title="Klik untuk sort">Budget & Timeline ↕</th>
                <th>Fitur Kunci</th>
                <th onclick="sortTable(7,this)" style="cursor:pointer;user-select:none;" title="Klik untuk sort">Status ↕</th>
                <th>Catatan Mas Eri</th><th>Aksi</th>
              </tr></thead>
              <tbody>
                <?php if (empty($leadsList)): ?>
                  <tr><td colspan="10"><div class="empty-state"><div class="empty-state-emoji">📭</div><div class="empty-state-text">Belum ada data leads.</div></div></td></tr>
                <?php else: ?>
                  <?php foreach ($leadsList as $lead):
                    $st = $lead['followup_status'] ?? 'belum_dikontak';
                    $cr = htmlspecialchars((string)$lead['lead_ref']);
                    $cn = htmlspecialchars((string)$lead['name']);
                    $cw = htmlspecialchars((string)$lead['whatsapp']);
                    $cc = htmlspecialchars((string)($lead['company'] ?? ''));
                    $ccat = htmlspecialchars((string)$lead['project_category']);
                    $cpf = htmlspecialchars((string)($lead['target_platform'] ?? 'Web'));
                    $cps = htmlspecialchars((string)($lead['project_status'] ?? 'Bangun Baru'));
                    $cb = htmlspecialchars((string)$lead['budget_range']);
                    $ct = htmlspecialchars((string)($lead['timeline'] ?? '-'));
                    $cnotes = htmlspecialchars((string)($lead['notes'] ?? ''));
                    $masNotes = htmlspecialchars((string)($lead['followup_notes'] ?? ''));
                    $src = $lead['source'] ?? 'breakdown_form';
                    $feats = json_decode((string)($lead['selected_features'] ?? '[]'), true);
                    $waNum = preg_replace('/[^0-9]/', '', (string)$lead['whatsapp']);
                    if (str_starts_with($waNum, '08')) { $waNum = '628' . substr($waNum, 2); }
                    $wafStr = "Halo Mas/Mbak {$cn}, saya Eri Nur Sofa (Software Engineer). Terima kasih sudah diskusi di portofolio saya (Ref: {$cr}) mengenai {$ccat}. Estimasi budget: {$cb}, target platform: {$cpf}. Saya siap bantu diskusi lebih lanjut kapanpun Mas/Mbak berkenan 🙏";
                    $waEncoded = urlencode($wafStr);
                    $searchStr = strtolower("{$cn} {$cw} {$ccat} {$cc} {$cr}");
                  ?>
                    <tr data-status="<?= htmlspecialchars($st) ?>" data-source="<?= htmlspecialchars($src) ?>" data-search="<?= $searchStr ?>">
                      <td><span class="badge-ref"><?= $cr ?></span></td>
                      <td><?= $src === 'ai_chat' ? '<span class="badge-ai">🤖 AI Chat</span>' : '<span class="badge-form">📋 Form</span>' ?></td>
                      <td style="white-space:nowrap; font-size:0.72rem; color:#64748b; font-family:var(--font-mono);">
                        <?= htmlspecialchars(date('d M Y', strtotime((string)$lead['created_at']))) ?><br>
                        <?= htmlspecialchars(date('H:i', strtotime((string)$lead['created_at']))) ?>
                      </td>
                      <td>
                        <div style="font-weight:800; font-size:0.85rem;"><?= $cn ?></div>
                        <?php if ($cc): ?><div style="font-size:0.7rem; color:#64748b;">🏢 <?= $cc ?></div><?php endif; ?>
                        <div style="font-family:var(--font-mono); font-size:0.73rem; color:var(--blue);"><?= $cw ?></div>
                      </td>
                      <td>
                        <span class="badge-cat"><?= $ccat ?></span>
                        <div style="font-size:0.7rem; margin-top:3px; color:#64748b;">📱 <?= $cpf ?></div>
                        <div style="font-size:0.7rem; color:#64748b;">⚡ <?= $cps ?></div>
                      </td>
                      <td>
                        <span class="badge-budget"><?= $cb ?></span>
                        <div style="font-size:0.7rem; margin-top:3px; color:#64748b;">⏱ <?= $ct ?></div>
                      </td>
                      <td style="max-width:200px;">
                        <?php if (is_array($feats) && !empty($feats)): ?>
                          <?php foreach ($feats as $f): ?><span class="inline-tag"><?= htmlspecialchars($f) ?></span><?php endforeach; ?>
                        <?php else: ?><span style="color:#94a3b8; font-size:0.72rem;">-</span><?php endif; ?>
                      </td>
                      <td>
                        <select class="status-sel st-<?= htmlspecialchars($st) ?>" onchange="updateStatus(<?= (int)$lead['id'] ?>, this.value, this)">
                          <option value="belum_dikontak" <?= $st==='belum_dikontak'?'selected':'' ?>>🟡 Belum Dikontak</option>
                          <option value="diskusi_wa" <?= $st==='diskusi_wa'?'selected':'' ?>>🔵 Diskusi WA</option>
                          <option value="proposal" <?= $st==='proposal'?'selected':'' ?>>🟣 Proposal</option>
                          <option value="deal" <?= $st==='deal'?'selected':'' ?>>🟢 Deal</option>
                          <option value="pending" <?= $st==='pending'?'selected':'' ?>>⚪ Pending</option>
                        </select>
                      </td>
                      <td style="min-width:160px;">
                        <div class="notes-preview" id="notesPreview_<?= (int)$lead['id'] ?>"><?= $masNotes ?: '<em style="color:#94a3b8;">belum ada catatan</em>' ?></div>
                        <button class="btn btn-ghost btn-sm" onclick="openNotes(<?= (int)$lead['id'] ?>, '<?= addslashes($cn) ?>', '<?= addslashes($masNotes) ?>')">✏️ Catatan</button>
                      </td>
                      <td style="white-space:nowrap;">
                        <a href="https://wa.me/<?= $waNum ?>?text=<?= $waEncoded ?>" target="_blank" rel="noopener noreferrer" class="btn btn-wa btn-sm" style="margin-bottom:4px; display:inline-flex;">
                          <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.976.58 2.028.928 3.149.929 3.182 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.771-5.768-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.312.045-.694.067-2.127-.527-1.745-.724-2.883-2.493-2.97-2.609-.086-.115-.71-1.002-.71-1.921 0-.918.47-1.37.643-1.558.174-.187.378-.235.505-.235.127 0 .254.002.366.007.119.006.278-.045.435.333.16.386.549 1.341.597 1.439.048.098.08.213.016.34-.064.127-.096.206-.191.317-.095.112-.2.249-.286.334-.096.096-.196.2-.084.392.112.193.498.823 1.069 1.332.735.655 1.355.858 1.547.954.192.096.304.08.417-.048.112-.128.481-.56.609-.752.127-.193.255-.16.43-.096.175.064 1.112.524 1.303.62.191.096.318.143.366.223.048.079.048.461-.096.866z"/></svg>
                          Follow-up WA
                        </a>
                        <br>
                        <button class="btn btn-ghost btn-sm" onclick='openDetail(<?= json_encode($lead, JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>🔍 Detail</button>
                        <br>
                        <button class="btn btn-sm" style="background:#fee2e2;border-color:var(--danger);color:#991b1b;box-shadow:2px 2px 0 var(--danger);margin-top:4px;" onclick="deleteLead(<?= (int)$lead['id'] ?>, '<?= addslashes($cn) ?>', this.closest('tr'))">🗑️ Hapus</button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <!-- END TAB 2 -->
