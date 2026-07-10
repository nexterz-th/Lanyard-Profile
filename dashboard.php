<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/i18n.php';

$user = require_login();
$errors = [];
$success = flash_get('success');
$flashError = flash_get('error');
if ($flashError) {
    $errors[] = $flashError;
}

const MAX_BG_BYTES = 5 * 1024 * 1024; // 5MB
const ALLOWED_BG_MIME = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
$bgDir = __DIR__ . '/uploads/backgrounds';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_discord') {
        $discordId = trim($_POST['discord_id'] ?? '');
        if (!is_valid_discord_id($discordId)) {
            $errors[] = t('dash.err_discord');
        } else {
            $pdo->prepare('UPDATE users SET discord_id = ? WHERE id = ?')
                ->execute([$discordId === '' ? null : $discordId, $user['id']]);
            flash_set('success', t('dash.success_discord'));
            redirect('/dashboard.php');
        }
    } elseif ($action === 'update_contact') {
        $displayName = trim($_POST['display_name'] ?? '');
        $contactEmail = trim($_POST['contact_email'] ?? '');
        if ($contactEmail !== '' && !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = t('dash.err_contact_email');
        } else {
            $pdo->prepare('UPDATE users SET display_name = ?, contact_email = ? WHERE id = ?')
                ->execute([$displayName !== '' ? $displayName : null, $contactEmail !== '' ? $contactEmail : null, $user['id']]);
            flash_set('success', t('dash.success_contact'));
            redirect('/dashboard.php');
        }
    } elseif ($action === 'add_social') {
        $platform = $_POST['platform'] ?? 'custom';
        $url = trim($_POST['url'] ?? '');
        $customLabel = trim($_POST['custom_label'] ?? '');

        if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('~^https?://~i', $url)) {
            $errors[] = t('dash.err_url');
        } else {
            $meta = social_icon_meta($platform);
            $label = $platform === 'custom' && $customLabel !== '' ? $customLabel : $meta['label'];
            $stmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), -1) + 1 AS next_order FROM socials WHERE user_id = ?');
            $stmt->execute([$user['id']]);
            $nextOrder = (int) $stmt->fetch()['next_order'];

            $pdo->prepare('INSERT INTO socials (user_id, platform, label, icon, css_class, url, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute([$user['id'], $platform, $label, $meta['icon'], $meta['class'], $url, $nextOrder]);
            flash_set('success', t('dash.success_social_add'));
            redirect('/dashboard.php');
        }
    } elseif ($action === 'delete_social') {
        $socialId = (int) ($_POST['social_id'] ?? 0);
        $pdo->prepare('DELETE FROM socials WHERE id = ? AND user_id = ?')->execute([$socialId, $user['id']]);
        flash_set('success', t('dash.success_social_delete'));
        redirect('/dashboard.php');
    } elseif ($action === 'update_background') {
        if (empty($_FILES['background']) || $_FILES['background']['error'] === UPLOAD_ERR_NO_FILE) {
            $errors[] = t('dash.err_no_file');
        } elseif ($_FILES['background']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = t('dash.err_upload_failed');
        } elseif ($_FILES['background']['size'] > MAX_BG_BYTES) {
            $errors[] = t('dash.err_file_too_big');
        } else {
            $tmpPath = $_FILES['background']['tmp_name'];
            $imgInfo = @getimagesize($tmpPath);
            $mime = $imgInfo['mime'] ?? null;
            if (!$imgInfo || !isset(ALLOWED_BG_MIME[$mime])) {
                $errors[] = t('dash.err_file_type');
            } else {
                if (!is_dir($bgDir)) {
                    mkdir($bgDir, 0755, true);
                }
                $ext = ALLOWED_BG_MIME[$mime];
                $filename = 'bg_' . $user['id'] . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $destPath = $bgDir . '/' . $filename;

                if (move_uploaded_file($tmpPath, $destPath)) {
                    $oldFile = $user['background_image'] ?? null;
                    $pdo->prepare('UPDATE users SET background_image = ?, background_url = NULL WHERE id = ?')
                        ->execute([$filename, $user['id']]);
                    if ($oldFile && is_file($bgDir . '/' . $oldFile)) {
                        @unlink($bgDir . '/' . $oldFile);
                    }
                    flash_set('success', t('dash.success_bg'));
                    redirect('/dashboard.php');
                } else {
                    $errors[] = t('dash.err_save_failed');
                }
            }
        }
    } elseif ($action === 'update_background_url') {
        $bgUrlInput = trim($_POST['background_url'] ?? '');
        if (!filter_var($bgUrlInput, FILTER_VALIDATE_URL) || !preg_match('~^https?://~i', $bgUrlInput)) {
            $errors[] = t('dash.err_bg_url');
        } else {
            $oldFile = $user['background_image'] ?? null;
            $pdo->prepare('UPDATE users SET background_url = ?, background_image = NULL WHERE id = ?')
                ->execute([$bgUrlInput, $user['id']]);
            if ($oldFile && is_file($bgDir . '/' . $oldFile)) {
                @unlink($bgDir . '/' . $oldFile);
            }
            flash_set('success', t('dash.success_bg_url'));
            redirect('/dashboard.php');
        }
    } elseif ($action === 'remove_background') {
        if (!empty($user['background_image']) && is_file($bgDir . '/' . $user['background_image'])) {
            @unlink($bgDir . '/' . $user['background_image']);
        }
        $pdo->prepare('UPDATE users SET background_image = NULL, background_url = NULL WHERE id = ?')->execute([$user['id']]);
        flash_set('success', t('dash.success_bg_removed'));
        redirect('/dashboard.php');
    } elseif ($action === 'update_text_color') {
        $textColor = trim($_POST['text_color'] ?? '');
        if (!is_valid_hex_color($textColor)) {
            $errors[] = t('dash.err_color');
        } else {
            $pdo->prepare('UPDATE users SET text_color = ? WHERE id = ?')->execute([$textColor, $user['id']]);
            flash_set('success', t('dash.success_color'));
            redirect('/dashboard.php');
        }
    } elseif ($action === 'reset_text_color') {
        $pdo->prepare('UPDATE users SET text_color = NULL WHERE id = ?')->execute([$user['id']]);
        flash_set('success', t('dash.success_color_reset'));
        redirect('/dashboard.php');
    } elseif ($action === 'update_counter_color') {
        $counterColor = trim($_POST['counter_color'] ?? '');
        if (!is_valid_hex_color($counterColor)) {
            $errors[] = t('dash.err_color');
        } else {
            $pdo->prepare('UPDATE users SET counter_color = ? WHERE id = ?')->execute([$counterColor, $user['id']]);
            flash_set('success', t('dash.success_counter_color'));
            redirect('/dashboard.php');
        }
    } elseif ($action === 'reset_counter_color') {
        $pdo->prepare('UPDATE users SET counter_color = NULL WHERE id = ?')->execute([$user['id']]);
        flash_set('success', t('dash.success_counter_color_reset'));
        redirect('/dashboard.php');
    }
}

// Reload fresh user + related data for display
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$user['id']]);
$user = $stmt->fetch();

$stmt = $pdo->prepare('SELECT * FROM socials WHERE user_id = ? ORDER BY sort_order ASC, id ASC');
$stmt->execute([$user['id']]);
$socials = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM visitor_logs WHERE user_id = ?');
$stmt->execute([$user['id']]);
$uniqueVisitors = (int) $stmt->fetch()['c'];

$stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM visitor_logs WHERE user_id = ? AND visit_date = CURDATE()');
$stmt->execute([$user['id']]);
$todayVisitors = (int) $stmt->fetch()['c'];

$profileUrl = '/u/' . $user['username'];
$bgUrl = $user['background_image']
    ? '/uploads/backgrounds/' . rawurlencode($user['background_image'])
    : ($user['background_url'] ?: null);
?>
<!doctype html>
<html lang="<?= e(current_lang()) ?>">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Dashboard | NEXTERZ Profile</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <script>(function(){try{var t=localStorage.getItem('theme');document.documentElement.setAttribute('data-theme',t==='light'?'light':'dark');}catch(e){document.documentElement.setAttribute('data-theme','dark');}})();</script>
  <link rel="stylesheet" href="/assets/css/theme.css?v=5" />
</head>
<body>
  <?= lang_switcher_html() ?>
  <div class="bg-blobs"><span></span><span></span><span></span></div>
  <div class="top-nav">
    <a href="/index.php" class="brand">NEXTERZ Profile</a>
    <div class="links">
      <span class="muted"><?= e(t('dash.hello', ['name' => $user['username']])) ?></span>
      <a href="<?= e($profileUrl) ?>" target="_blank"><?= e(t('dash.view_profile')) ?></a>
      <a href="/logout.php"><?= e(t('dash.logout')) ?></a>
    </div>
  </div>

  <div class="wrap wide">
    <?php if ($success): ?><div class="msg success"><?= e($success) ?></div><?php endif; ?>
    <?php foreach ($errors as $err): ?><div class="msg error"><?= e($err) ?></div><?php endforeach; ?>

    <div class="card">
      <h2><i class="fa-solid fa-chart-simple"></i> <?= e(t('dash.counter.title')) ?></h2>
      <div class="stat-row">
        <div class="stat-box">
          <div class="num"><?= (int) $user['hit_count'] ?></div>
          <div class="label"><?= e(t('dash.counter.total')) ?></div>
        </div>
        <div class="stat-box">
          <div class="num"><?= $uniqueVisitors ?></div>
          <div class="label"><?= e(t('dash.counter.unique_all')) ?></div>
        </div>
        <div class="stat-box">
          <div class="num"><?= $todayVisitors ?></div>
          <div class="label"><?= e(t('dash.counter.unique_today')) ?></div>
        </div>
      </div>
      <p class="muted" style="margin-top:12px;"><?= t('dash.counter.hint', ['url' => '<a href="' . e($profileUrl) . '">' . e($profileUrl) . '</a>']) ?></p>
    </div>

    <div class="card">
      <h2><i class="fab fa-discord"></i> Discord ID</h2>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update_discord">
        <label for="discord_id"><?= e(t('dash.discord.label')) ?></label>
        <input type="text" id="discord_id" name="discord_id" value="<?= e($user['discord_id'] ?? '') ?>" pattern="\d{15,25}" placeholder="<?= e(t('dash.discord.placeholder')) ?>">
        <p class="muted"><?= e(t('dash.discord.note')) ?></p>
        <button type="submit" class="btn"><?= e(t('dash.save')) ?></button>
      </form>
    </div>

    <div class="card">
      <h2><i class="fa-solid fa-share-nodes"></i> <?= e(t('dash.social.title')) ?></h2>
      <?php foreach ($socials as $s): ?>
        <div class="social-row">
          <div class="icon-preview"><i class="<?= e(social_icon_prefix($s['platform'])) ?> <?= e($s['icon']) ?>"></i></div>
          <div style="min-width:110px"><?= e($s['label']) ?></div>
          <div class="url"><?= e($s['url']) ?></div>
          <form method="post" onsubmit="return confirm('<?= e(t('dash.social.confirm_delete')) ?>');">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete_social">
            <input type="hidden" name="social_id" value="<?= (int) $s['id'] ?>">
            <button type="submit" class="btn danger small"><?= e(t('dash.social.delete')) ?></button>
          </form>
        </div>
      <?php endforeach; ?>
      <?php if (!$socials): ?><p class="muted"><?= e(t('dash.social.empty')) ?></p><?php endif; ?>

      <div style="height:16px"></div>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add_social">
        <label for="platform"><?= e(t('dash.social.platform')) ?></label>
        <select id="platform" name="platform">
          <?php foreach (SOCIAL_PLATFORMS as $key => $meta): ?>
            <option value="<?= e($key) ?>"><?= e($meta['label']) ?></option>
          <?php endforeach; ?>
        </select>
        <label for="custom_label"><?= e(t('dash.social.custom_label')) ?></label>
        <input type="text" id="custom_label" name="custom_label" placeholder="<?= e(t('dash.social.custom_label_placeholder')) ?>">
        <label for="url"><?= e(t('dash.social.url')) ?></label>
        <input type="text" id="url" name="url" placeholder="https://...">
        <div style="height:12px"></div>
        <button type="submit" class="btn"><?= e(t('dash.social.add')) ?></button>
      </form>
    </div>

    <div class="card">
      <h2><i class="fa-solid fa-envelope"></i> <?= e(t('dash.contact.title')) ?></h2>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update_contact">
        <label for="display_name"><?= e(t('dash.contact.display_name')) ?></label>
        <input type="text" id="display_name" name="display_name" value="<?= e($user['display_name'] ?? '') ?>" maxlength="64">
        <label for="contact_email"><?= e(t('dash.contact.email')) ?></label>
        <input type="email" id="contact_email" name="contact_email" value="<?= e($user['contact_email'] ?? '') ?>">
        <div style="height:12px"></div>
        <button type="submit" class="btn"><?= e(t('dash.save')) ?></button>
      </form>
    </div>

    <div class="card">
      <h2><i class="fa-solid fa-image"></i> <?= e(t('dash.bg.title')) ?></h2>
      <?php if ($bgUrl): ?>
        <div class="bg-preview" style="background-image:url('<?= e($bgUrl) ?>')"></div>
        <p class="muted"><?= e(t('dash.bg.source')) ?> <?= $user['background_image'] ? e(t('dash.bg.source_file')) : e(t('dash.bg.source_url')) ?></p>
      <?php else: ?>
        <p class="muted"><?= e(t('dash.bg.default')) ?></p>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update_background">
        <label for="background"><?= e(t('dash.bg.upload_label')) ?></label>
        <input type="file" id="background" name="background" accept="image/jpeg,image/png,image/webp" required>
        <div style="height:12px"></div>
        <button type="submit" class="btn"><i class="fa-solid fa-upload"></i> <?= e(t('dash.bg.upload')) ?></button>
      </form>

      <div style="height:20px;border-top:1px solid rgba(255,255,255,0.08);margin-top:20px;"></div>

      <form method="post" style="margin-top:20px;">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update_background_url">
        <label for="background_url"><?= e(t('dash.bg.or_url')) ?></label>
        <input type="text" id="background_url" name="background_url" value="<?= e($user['background_url'] ?? '') ?>" placeholder="https://example.com/wallpaper.jpg">
        <div style="height:12px"></div>
        <button type="submit" class="btn"><i class="fa-solid fa-link"></i> <?= e(t('dash.bg.use_url')) ?></button>
      </form>

      <?php if ($bgUrl): ?>
        <form method="post" style="margin-top:14px;" onsubmit="return confirm('<?= e(t('dash.bg.confirm_remove')) ?>');">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="remove_background">
          <button type="submit" class="btn secondary"><i class="fa-solid fa-trash"></i> <?= e(t('dash.bg.remove')) ?></button>
        </form>
      <?php endif; ?>
    </div>

    <?php $defaultTextColor = $bgUrl ? '#e6eef6' : '#1c2230'; ?>
    <div class="card">
      <h2><i class="fa-solid fa-font"></i> <?= e(t('dash.color.title')) ?></h2>
      <p class="muted"><?= t('dash.color.desc', ['url' => e($profileUrl)]) ?></p>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update_text_color">
        <label for="text_color"><?= e(t('dash.color.label')) ?></label>
        <div style="display:flex;align-items:center;gap:12px;">
          <input type="color" id="text_color" name="text_color" value="<?= e($user['text_color'] ?? $defaultTextColor) ?>" style="width:60px;height:42px;padding:4px;cursor:pointer;">
          <span class="muted"><?= e($user['text_color'] ?? ($defaultTextColor . t('dash.color.default_suffix'))) ?></span>
        </div>
        <div style="height:12px"></div>
        <button type="submit" class="btn"><i class="fa-solid fa-check"></i> <?= e(t('dash.save')) ?></button>
      </form>
      <?php if (!empty($user['text_color'])): ?>
        <form method="post" style="margin-top:10px;">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="reset_text_color">
          <button type="submit" class="btn secondary"><i class="fa-solid fa-rotate-left"></i> <?= e(t('dash.color.reset')) ?></button>
        </form>
      <?php endif; ?>

      <div style="height:16px;border-top:1px solid rgba(255,255,255,0.08);margin-top:20px;"></div>

      <form method="post" style="margin-top:20px;">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update_counter_color">
        <label for="counter_color"><?= e(t('dash.counter_color.label')) ?></label>
        <div style="display:flex;align-items:center;gap:12px;">
          <input type="color" id="counter_color" name="counter_color" value="<?= e($user['counter_color'] ?? ($user['text_color'] ?? $defaultTextColor)) ?>" style="width:60px;height:42px;padding:4px;cursor:pointer;">
          <span class="muted"><?= $user['counter_color'] ? e($user['counter_color']) : e(($user['text_color'] ?? $defaultTextColor) . t('dash.color.default_suffix')) ?></span>
        </div>
        <div style="height:12px"></div>
        <button type="submit" class="btn"><i class="fa-solid fa-check"></i> <?= e(t('dash.save')) ?></button>
      </form>
      <?php if (!empty($user['counter_color'])): ?>
        <form method="post" style="margin-top:10px;">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="reset_counter_color">
          <button type="submit" class="btn secondary"><i class="fa-solid fa-rotate-left"></i> <?= e(t('dash.color.reset')) ?></button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
