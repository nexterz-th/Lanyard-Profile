<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/i18n.php';

$user = current_user();
?>
<!doctype html>
<html lang="<?= e(current_lang()) ?>">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Lanyard Profile</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <script>(function(){try{var t=localStorage.getItem('theme');document.documentElement.setAttribute('data-theme',t==='light'?'light':'dark');}catch(e){document.documentElement.setAttribute('data-theme','dark');}})();</script>
  <link rel="stylesheet" href="/assets/css/theme.css?v=5" />
  <style>
    body { justify-content: center; }
    .hero { text-align: center; max-width: 480px; }
    .hero .badge {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 6px 14px; border-radius: 999px; font-size: 12px; font-weight: 600;
      background: rgba(124, 92, 255, 0.1); color: #6d3fd6; border: 1px solid rgba(124, 92, 255, 0.25);
      margin-bottom: 16px;
    }
    .hero h1 { font-size: 32px; margin-bottom: 10px; }
    .hero p { line-height: 1.6; }
    .cta { display: flex; gap: 12px; justify-content: center; margin-top: 24px; }
  </style>
</head>
<body>
  <?= theme_toggle_html() ?>
  <?= lang_switcher_html() ?>
  <div class="bg-blobs"><span></span><span></span><span></span></div>
  <div class="wrap">
    <div class="card hero">
      <span class="badge"><i class="fa-solid fa-bolt"></i> <?= e(t('home.badge')) ?></span>
      <h1 class="gradient-text">Lanyard Profile</h1>
      <p class="muted"><?= e(t('home.desc')) ?></p>
      <div class="cta">
        <?php if ($user): ?>
          <a class="btn" href="/dashboard.php"><i class="fa-solid fa-gauge"></i> <?= e(t('home.cta_dashboard')) ?></a>
          <a class="btn secondary" href="/u/<?= e($user['username']) ?>"><i class="fa-solid fa-id-badge"></i> <?= e(t('home.cta_view_profile')) ?></a>
        <?php else: ?>
          <a class="btn" href="/register.php"><i class="fa-solid fa-user-plus"></i> <?= e(t('home.cta_register')) ?></a>
          <a class="btn secondary" href="/login.php"><i class="fa-solid fa-right-to-bracket"></i> <?= e(t('home.cta_login')) ?></a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>
</html>
