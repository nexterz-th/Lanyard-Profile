<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/i18n.php';

if (current_user()) {
    redirect('/dashboard.php');
}

$errors = [];
$old = ['username' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $username = strtolower(trim($_POST['username'] ?? ''));
    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');

    $old['username'] = $username;
    $old['email'] = $email;

    if (!is_valid_username($username)) {
        $errors[] = t('register.err_username');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = t('register.err_email');
    }
    if (strlen($password) < 8) {
        $errors[] = t('register.err_password_len');
    }
    if ($password !== $confirm) {
        $errors[] = t('register.err_password_match');
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $errors[] = t('register.err_taken');
        }
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, display_name, contact_email) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$username, $email, $hash, $username, $email]);
        flash_set('success', t('register.success'));
        redirect('/login.php');
    }
}
?>
<!doctype html>
<html lang="<?= e(current_lang()) ?>">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= e(t('register.title')) ?> | NEXTERZ Profile</title>
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
      <h1><i class="fa-solid fa-user-plus" style="color:var(--accent2)"></i> <span class="gradient-text"><?= e(t('register.title')) ?></span></h1>
      <?php foreach ($errors as $err): ?>
        <div class="msg error"><?= e($err) ?></div>
      <?php endforeach; ?>
      <form method="post" novalidate>
        <?= csrf_field() ?>
        <label for="username"><?= e(t('register.username')) ?></label>
        <input type="text" id="username" name="username" value="<?= e($old['username']) ?>" required pattern="[a-z0-9_-]{3,20}" placeholder="<?= e(t('register.username_placeholder')) ?>">

        <label for="email"><?= e(t('register.email')) ?></label>
        <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" required>

        <label for="password"><?= e(t('register.password')) ?></label>
        <input type="password" id="password" name="password" required minlength="8">

        <label for="confirm_password"><?= e(t('register.confirm_password')) ?></label>
        <input type="password" id="confirm_password" name="confirm_password" required minlength="8">

        <div style="height:16px"></div>
        <button type="submit" class="btn" style="width:100%"><i class="fa-solid fa-user-plus"></i> <?= e(t('register.submit')) ?></button>
      </form>
      <p class="muted" style="margin-top:16px;text-align:center;"><?= e(t('register.have_account')) ?> <a href="/login.php"><?= e(t('register.login_link')) ?></a></p>
    </div>
  </div>
</body>
</html>
