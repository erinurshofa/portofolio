<!-- ===================================================
     LOGIN SCREEN
     =================================================== -->
<div class="login-screen">
  <div class="login-box">
    <div class="login-top">
      <div style="font-size:2.5rem; margin-bottom:0.35rem;">🔐</div>
      <div class="login-top-title">Admin Console</div>
      <div class="login-top-tag">#LebihWarasPakaiSistem</div>
    </div>
    <div class="login-body">
      <p style="font-size:0.82rem; color:#64748b; font-weight:500; margin-bottom:1.25rem; line-height:1.5;">
        Konsol analisis pengunjung, log percakapan AI Chat, dan data calon klien Mas Eri Nur Sofa. Masukkan kata sandi untuk melanjutkan.
      </p>

      <?php if ($loginError): ?>
        <div class="login-error">⚠️ <?= htmlspecialchars($loginError) ?></div>
      <?php endif; ?>

      <form method="POST" action="admin.php">
        <label class="login-label" for="adminPasswordInput">Kata Sandi Admin</label>
        <input type="password" name="password" id="adminPasswordInput" class="form-input" placeholder="••••••••••••" required autofocus style="margin-bottom:1rem;">
        <button type="submit" class="btn btn-yellow" style="width:100%; justify-content:center; padding:0.75rem; font-size:0.95rem;">
          🚀 Masuk ke Konsol Admin
        </button>
      </form>
    </div>
    <div style="background:var(--paper-2); border-top:var(--border-thin); padding:0.85rem 1.75rem; text-align:center;">
      <a href="index2.html" style="font-size:0.78rem; color:var(--ink-light); text-decoration:none; font-weight:700;">
        ← Kembali ke Website Portofolio
      </a>
    </div>
  </div>
</div>
