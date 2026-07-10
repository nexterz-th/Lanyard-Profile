<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/i18n.php';

if (current_user()) {
    redirect('/dashboard.php');
}

$errors = [];
$success = flash_get('success');
$old = ['login' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $login = trim($_POST['login'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $old['login'] = $login;

    if ($login === '' || $password === '') {
        $errors[] = t('login.err_required');
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1');
        $stmt->execute([strtolower($login), $login]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = t('login.err_invalid');
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            redirect('/dashboard.php');
        }
    }
}
?>
<!doctype html>
<html lang="<?= e(current_lang()) ?>">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= e(t('login.title')) ?> | NEXTERZ Profile</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <script>(function(){try{var t=localStorage.getItem('theme');document.documentElement.setAttribute('data-theme',t==='light'?'light':'dark');}catch(e){document.documentElement.setAttribute('data-theme','dark');}})();</script>
  <link rel="stylesheet" href="/assets/css/theme.css?v=5" />
  <style>
    body { justify-content: center; }
  </style>
</head>
<body>
  <?= theme_toggle_html() ?>
  <?= lang_switcher_html() ?>
  <div class="bg-blobs"><span></span><span></span><span></span></div>
  <div class="wrap">
    <div class="card">
      <h1><i class="fa-solid fa-right-to-bracket" style="color:var(--accent2)"></i> <span class="gradient-text"><?= e(t('login.title')) ?></span></h1>
      <?php if ($success): ?><div class="msg success"><?= e($success) ?></div><?php endif; ?>
      <?php foreach ($errors as $err): ?>
        <div class="msg error"><?= e($err) ?></div>
      <?php endforeach; ?>
      <form method="post" novalidate>
        <?= csrf_field() ?>
        <label for="login"><?= e(t('login.field')) ?></label>
        <input type="text" id="login" name="login" value="<?= e($old['login']) ?>" required autofocus>

        <label for="password"><?= e(t('login.password')) ?></label>
        <input type="password" id="password" name="password" required>

        <div style="height:16px"></div>
        <button type="submit" class="btn" style="width:100%"><i class="fa-solid fa-right-to-bracket"></i> <?= e(t('login.submit')) ?></button>
      </form>
      <p class="muted" style="margin-top:16px;text-align:center;"><?= e(t('login.no_account')) ?> <a href="/register.php"><?= e(t('login.register_link')) ?></a></p>
    </div>
  </div>
</body>
</html>
