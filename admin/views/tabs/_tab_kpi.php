      <!-- ================================================
           TAB 1: DASHBOARD
           ================================================ -->
      <div id="tab-dashboard" class="tab-panel">

        <!-- KPI Cards -->
        <div class="kpi-grid">
          <div class="kpi-card accent-yellow">
            <div class="kpi-icon">👥</div>
            <div class="kpi-label">Pengunjung Unik</div>
            <div class="kpi-value"><?= $analytics['metrics']['total_visitors'] ?? 0 ?></div>
            <div class="kpi-sub">
              <span class="kpi-badge green">+<?= $analytics['metrics']['today_visitors'] ?? 0 ?> hari ini</span>
              total kunjungan halaman
            </div>
          </div>

          <div class="kpi-card accent-blue">
            <div class="kpi-icon">💬</div>
            <div class="kpi-label">Sesi AI Chat</div>
            <div class="kpi-value"><?= $analytics['metrics']['total_chats'] ?? 0 ?></div>
            <div class="kpi-sub">
              <span class="kpi-badge green">+<?= $analytics['metrics']['today_chats'] ?? 0 ?> hari ini</span>
              interaksi tanya jawab
            </div>
          </div>

          <div class="kpi-card accent-green">
            <div class="kpi-icon">🎯</div>
            <div class="kpi-label">Leads Calon Klien</div>
            <div class="kpi-value"><?= $analytics['metrics']['total_leads'] ?? 0 ?></div>
            <div class="kpi-sub">
              <span class="kpi-badge green">+<?= $analytics['metrics']['today_leads'] ?? 0 ?> hari ini</span>
              Deal: <strong><?= $analytics['metrics']['deal_count'] ?? 0 ?></strong>
            </div>
          </div>

          <div class="kpi-card accent-red">
            <div class="kpi-icon">⚡</div>
            <div class="kpi-label">Siap Di-Follow Up</div>
            <div class="kpi-value" style="color:var(--danger);"><?= $analytics['metrics']['pending_followup'] ?? 0 ?></div>
            <div class="kpi-sub">
              <span class="kpi-badge red">Belum dikontak</span>
              prioritas segera
            </div>
          </div>
        </div>

        <!-- Intelligence Grid -->
        <div class="intel-grid">

          <!-- What do they want? -->
          <div class="section-card">
            <div class="card-header">
              <div>
                <div class="card-title">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/></svg>
                  Kategori yang Paling Diminati
                </div>
                <div class="card-sub">Proporsi kebutuhan sistem yang ditanyakan calon klien</div>
              </div>
            </div>
            <div style="padding:1.25rem;">
              <?php
              $cats = $analytics['categories'] ?? [];
              $maxCat = 1;
              foreach ($cats as $c) { if ($c['cnt'] > $maxCat) $maxCat = (int)$c['cnt']; }
              $fillClasses = ['', 'ink', 'green', 'blue', ''];
              ?>
              <?php if (empty($cats)): ?>
                <div class="empty-state"><div class="empty-state-emoji">📭</div><div class="empty-state-text">Belum ada data kategori.</div></div>
              <?php else: ?>
                <?php foreach ($cats as $i => $cat):
                  $pct = round(($cat['cnt'] / max($maxCat,1)) * 100);
                  $fc = $fillClasses[$i % count($fillClasses)];
                ?>
                  <div class="bar-row">
                    <div class="bar-row-label">
                      <span><?= htmlspecialchars((string)$cat['project_category']) ?></span>
                      <span><?= $cat['cnt'] ?> leads · <?= $pct ?>%</span>
                    </div>
                    <div class="bar-track">
                      <div class="bar-fill <?= $fc ?>" style="width:<?= max($pct, 8) ?>%;"></div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>

              <!-- 7-Day Trend -->
              <div style="margin-top:1.35rem; padding-top:1rem; border-top:var(--border-thin);">
                <div style="font-family:var(--font-display); font-size:0.85rem; font-weight:800; margin-bottom:0.5rem; display:flex; justify-content:space-between;">
                  <span>Tren 7 Hari Terakhir</span>
                  <span style="font-size:0.65rem; color:#94a3b8; font-weight:600;">
                    <span style="color:var(--yellow-dk);">█</span> Pengunjung
                    <span style="color:var(--ink); margin-left:4px;">█</span> Chat
                  </span>
                </div>
                <div class="trend-chart">
                  <?php foreach (($analytics['trend'] ?? []) as $t):
                    $vh = min(55, max(4, $t['visitors'] * 10));
                    $ch = min(55, max(3, $t['chats'] * 12));
                  ?>
                    <div class="trend-day" title="<?= $t['date'] ?>: <?= $t['visitors'] ?> Pengunjung, <?= $t['chats'] ?> Chat, <?= $t['leads'] ?> Leads">
                      <div class="trend-bars">
                        <div class="trend-bar v" style="height:<?= $vh ?>px;"></div>
                        <div class="trend-bar c" style="height:<?= $ch ?>px;"></div>
                      </div>
                      <div class="trend-label"><?= $t['label'] ?></div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>

          <!-- Top Features & Budget -->
          <div>
            <div class="section-card" style="margin-bottom:1.25rem;">
              <div class="card-header">
                <div>
                  <div class="card-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--green)" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    Fitur Paling Dicari
                  </div>
                  <div class="card-sub">Pilihan fitur yang paling sering diminta calon klien</div>
                </div>
              </div>
              <div style="padding:1.25rem;">
                <div class="feature-cloud">
                  <?php $topFeats = $analytics['top_features'] ?? []; ?>
                  <?php if (empty($topFeats)): ?>
                    <div class="empty-state"><div class="empty-state-emoji">🏷️</div><div class="empty-state-text">Belum ada data fitur.</div></div>
                  <?php else: ?>
                    <?php foreach ($topFeats as $fn => $fc): ?>
                      <div class="feature-tag">
                        <?= htmlspecialchars((string)$fn) ?>
                        <span class="cnt"><?= $fc ?>×</span>
                      </div>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <div class="section-card">
              <div class="card-header">
                <div>
                  <div class="card-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--yellow-dk)" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 18V6"/></svg>
                    Ekspektasi Budget
                  </div>
                  <div class="card-sub">Range budget yang paling banyak dikomunikasikan</div>
                </div>
              </div>
              <div style="padding:1rem 1.25rem;">
                <?php foreach (array_slice(($analytics['budgets'] ?? []), 0, 5) as $b): ?>
                  <div style="display:flex; justify-content:space-between; align-items:center; padding:0.4rem 0; border-bottom:1.5px dashed var(--paper-2);">
                    <span style="font-weight:700; font-size:0.8rem;"><?= htmlspecialchars((string)$b['budget_range']) ?></span>
                    <span class="badge-budget"><?= $b['cnt'] ?>×</span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

        </div>

        <!-- Recent Leads Snapshot -->
        <div class="section-card">
          <div class="card-header">
            <div>
              <div class="card-title">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="23 11 17 11 20 14 17 17"/></svg>
                Leads Terbaru Masuk
              </div>
              <div class="card-sub">5 calon klien terakhir yang perlu ditindaklanjuti</div>
            </div>
            <button class="btn btn-ghost btn-sm" onclick="switchTabByName('tab-leads')">Semua Leads →</button>
          </div>
          <?php
          // Quick leads table (5 rows)
          $recentLeads = array_slice($leadsList, 0, 5);
          ?>
          <div class="tbl-wrap">
            <table>
              <thead><tr>
                <th>Referensi</th><th>Sumber</th><th>Calon Klien</th>
                <th>Kategori</th><th>Budget</th><th>Status</th><th>Aksi</th>
              </tr></thead>
              <tbody>
                <?php if (empty($recentLeads)): ?>
                  <tr><td colspan="7"><div class="empty-state"><div class="empty-state-emoji">📭</div><div class="empty-state-text">Belum ada leads tersimpan.</div></div></td></tr>
                <?php else: ?>
                  <?php foreach ($recentLeads as $row):
                    $st = $row['followup_status'] ?? 'belum_dikontak';
                    $cleanName = htmlspecialchars((string)$row['name']);
                    $cleanRef = htmlspecialchars((string)$row['lead_ref']);
                    $waNum = preg_replace('/[^0-9]/', '', (string)$row['whatsapp']);
                    if (str_starts_with($waNum, '08')) { $waNum = '628' . substr($waNum, 2); }
                    $waMsg = urlencode("Halo {$cleanName}, saya Eri Nur Sofa (Software Engineer). Terima kasih sudah diskusi di portofolio saya (Ref: {$cleanRef}) mengenai {$row['project_category']}. Saya siap bantu diskusi lebih lanjut!");
                  ?>
                    <tr>
                      <td><span class="badge-ref"><?= $cleanRef ?></span></td>
                      <td><?= ($row['source'] ?? '') === 'ai_chat' ? '<span class="badge-ai">🤖 AI Chat</span>' : '<span class="badge-form">📋 Form</span>' ?></td>
                      <td>
                        <div style="font-weight:700;"><?= $cleanName ?></div>
                        <div style="font-size:0.72rem; color:#64748b; font-family:var(--font-mono);"><?= htmlspecialchars((string)$row['whatsapp']) ?></div>
                      </td>
                      <td><span class="badge-cat"><?= htmlspecialchars((string)$row['project_category']) ?></span></td>
                      <td><span class="badge-budget"><?= htmlspecialchars((string)$row['budget_range']) ?></span></td>
                      <td>
                        <select class="status-sel st-<?= htmlspecialchars($st) ?>" onchange="updateStatus(<?= (int)$row['id'] ?>, this.value, this)">
                          <option value="belum_dikontak" <?= $st==='belum_dikontak'?'selected':'' ?>>🟡 Belum Dikontak</option>
                          <option value="diskusi_wa" <?= $st==='diskusi_wa'?'selected':'' ?>>🔵 Diskusi WA</option>
                          <option value="proposal" <?= $st==='proposal'?'selected':'' ?>>🟣 Proposal</option>
                          <option value="deal" <?= $st==='deal'?'selected':'' ?>>🟢 Deal</option>
                          <option value="pending" <?= $st==='pending'?'selected':'' ?>>⚪ Pending</option>
                        </select>
                      </td>
                      <td>
                        <a href="https://wa.me/<?= $waNum ?>?text=<?= $waMsg ?>" target="_blank" rel="noopener noreferrer" class="btn btn-wa btn-sm">
                          <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.976.58 2.028.928 3.149.929 3.182 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.771-5.768-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.312.045-.694.067-2.127-.527-1.745-.724-2.883-2.493-2.97-2.609-.086-.115-.71-1.002-.71-1.921 0-.918.47-1.37.643-1.558.174-.187.378-.235.505-.235.127 0 .254.002.366.007.119.006.278-.045.435.333.16.386.549 1.341.597 1.439.048.098.08.213.016.34-.064.127-.096.206-.191.317-.095.112-.2.249-.286.334-.096.096-.196.2-.084.392.112.193.498.823 1.069 1.332.735.655 1.355.858 1.547.954.192.096.304.08.417-.048.112-.128.481-.56.609-.752.127-.193.255-.16.43-.096.175.064 1.112.524 1.303.62.191.096.318.143.366.223.048.079.048.461-.096.866z"/></svg>
                          Follow-up WA
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <!-- END TAB 1 -->
