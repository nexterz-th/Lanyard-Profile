<?php
declare(strict_types=1);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/i18n.php';

$username = strtolower(trim($_GET['u'] ?? ''));

if (!is_valid_username($username)) {
    http_response_code(404);
    die('User not found.');
}

$stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
$stmt->execute([$username]);
$profileUser = $stmt->fetch();

if (!$profileUser) {
    http_response_code(404);
    die('User not found.');
}

$stmt = $pdo->prepare('SELECT platform, label, icon, css_class, url FROM socials WHERE user_id = ? ORDER BY sort_order ASC, id ASC');
$stmt->execute([$profileUser['id']]);
$socials = $stmt->fetchAll();

// Count this page view (moved here from counter.php so the counter can be rendered as plain text)
$pdo->prepare('UPDATE users SET hit_count = hit_count + 1 WHERE id = ?')->execute([$profileUser['id']]);
$hitCount = (int) $profileUser['hit_count'] + 1;

$ipHash = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
try {
    $pdo->prepare('INSERT INTO visitor_logs (user_id, ip_hash, visit_date) VALUES (?, ?, CURDATE())')
        ->execute([$profileUser['id'], $ipHash]);
} catch (PDOException $e) {
    // Already logged for this IP today — ignore duplicate key error.
}

$discordId = $profileUser['discord_id'] ?: '';
$displayName = $profileUser['display_name'] ?: $profileUser['username'];

$hasCustomBg = !empty($profileUser['background_image']) || !empty($profileUser['background_url']);
$bgUrl = $profileUser['background_image']
    ? '/uploads/backgrounds/' . rawurlencode($profileUser['background_image'])
    : ($profileUser['background_url'] ?: null);

// No background set -> plain white page with dark text. Background set -> original dark glass look.
// A saved text_color always overrides the automatic choice.
$textColor = is_valid_hex_color((string) ($profileUser['text_color'] ?? '')) ? $profileUser['text_color'] : ($hasCustomBg ? '#e6eef6' : '#1c2230');

// Secondary/muted text is derived from the chosen text color (translucent), so every label,
// icon, and helper string on the page recolors together instead of staying a fixed gray.
$textRgb = implode(',', array_map('hexdec', str_split(ltrim($textColor, '#'), 2)));

$theme = $hasCustomBg ? [
    'bodyBg'       => '#000000',
    'overlayRgb'   => '255,255,255',
    'cardBg'       => 'rgba(255,255,255,0.03)',
    'cardBorder'   => 'rgba(255,255,255,0.04)',
    'statusBg'     => 'rgba(11,15,18,0.92)',
    'avatarStart'  => '#0b0f12',
    'socialBg'     => 'rgba(255,255,255,0.05)',
    'socialBorder' => 'rgba(255,255,255,0.07)',
] : [
    'bodyBg'       => '#ffffff',
    'overlayRgb'   => '15,23,42',
    'cardBg'       => '#f4f5fb',
    'cardBorder'   => 'rgba(15,23,42,0.08)',
    'statusBg'     => 'rgba(255,255,255,0.92)',
    'avatarStart'  => '#eef0f7',
    'socialBg'     => '#eef0f7',
    'socialBorder' => 'rgba(15,23,42,0.1)',
];

$counterColorStyle = is_valid_hex_color((string) ($profileUser['counter_color'] ?? ''))
    ? 'color:' . $profileUser['counter_color'] . ';'
    : '';

$contactEmail = $profileUser['contact_email'] ?: '';
$emailCodes = [];
foreach (str_split($contactEmail) as $ch) {
    $emailCodes[] = ord($ch);
}

$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
$socialsJson = json_encode($socials, $jsonFlags);
$emailCodesJson = json_encode($emailCodes, $jsonFlags);

$jsI18n = [
    'statusOnline' => t('profile.js.status_online'),
    'statusIdle' => t('profile.js.status_idle'),
    'statusDnd' => t('profile.js.status_dnd'),
    'statusOffline' => t('profile.js.status_offline'),
    'restingTitle' => t('profile.js.resting_title'),
    'restingDesc' => t('profile.js.resting_desc'),
    'playing' => t('profile.js.playing'),
    'connecting' => t('profile.js.connecting'),
    'connectedRest' => t('profile.js.connected_rest'),
    'connectingWs' => t('profile.js.connecting_ws'),
    'wsConnected' => t('profile.js.ws_connected'),
    'wsClosed' => t('profile.js.ws_closed'),
    'update' => t('profile.js.update'),
    'restError' => t('profile.js.rest_error'),
    'locale' => current_lang() === 'th' ? 'th-TH' : 'en-US',
];
$jsI18nJson = json_encode($jsI18n, $jsonFlags);
?>
<!doctype html>
<html lang="<?= e(current_lang()) ?>">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= e($displayName) ?></title>
  <meta name="description" content="<?= e($displayName) ?> โปรไฟล์ออนไลน์ แสดง Discord Presence และ Social Links แบบเรียลไทม์" />
  <meta name="robots" content="index, follow" />

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/twemoji@14.0.2/dist/twemoji.min.js" crossorigin="anonymous" defer></script>

  <style>
    :root {
      --bg: <?= e($theme['bodyBg']) ?>; --text: <?= e($textColor) ?>; --text-rgb: <?= e($textRgb) ?>; --muted: rgba(var(--text-rgb), 0.66);
      --overlay-rgb: <?= e($theme['overlayRgb']) ?>; --card-bg: <?= e($theme['cardBg']) ?>; --card-border: <?= e($theme['cardBorder']) ?>;
      --status-bg: <?= e($theme['statusBg']) ?>; --avatar-start: <?= e($theme['avatarStart']) ?>;
      --social-bg: <?= e($theme['socialBg']) ?>; --social-border: <?= e($theme['socialBorder']) ?>;
      --accent: #ff4d6d; --green: #43b581; --yellow: #faa61a; --red: #f04747; --offline: #6f7780;
      font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
    }
    html, body { height: 100%; min-height: 100vh; }
    body { margin: 0; height: 100%; min-height: 100vh; font-family: "IBM Plex Sans Thai", sans-serif; font-weight: 400; display: flex; align-items: flex-start; justify-content: center; padding: 28px; box-sizing: border-box; background: var(--bg); color: var(--text); position: relative; overflow-x: hidden; overscroll-behavior: none; }
    .bg-fixed { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-size: cover; background-position: center center; background-repeat: no-repeat; z-index: -999; pointer-events: none; transform: translateZ(0); will-change: transform; filter: blur(10px); }
    .container { width: 100%; max-width: 980px; display: grid; grid-template-columns: 360px 1fr; gap: 20px; min-height: 100%; }
    .card { background: var(--card-bg); border: 1px solid var(--card-border); box-shadow: 0 6px 30px rgba(2, 6, 10, 0.15); border-radius: 14px; padding: 18px; }
    .profile { display: flex; flex-direction: column; gap: 14px; align-items: center; text-align: center; align-self: start; }
    .avatar-wrapper { position: relative; width: 150px; height: 150px; }
    .name-row { margin-top: 22px; text-align: center; }
    .avatar { position: relative; box-sizing: border-box; width: 100%; height: 100%; border-radius: 20px; overflow: hidden; border: 3px solid var(--card-border); box-shadow: 0 8px 30px rgba(3, 8, 12, 0.35); background: linear-gradient(180deg, var(--avatar-start), rgba(var(--overlay-rgb), 0.01)); }
    .avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .status-dot { display: inline-block; width: 10px; height: 10px; flex-shrink: 0; border-radius: 50%; background: var(--offline); }
    .main { display: flex; flex-direction: column; gap: 12px; }
    .activities { display: flex; flex-direction: column; gap: 12px; }
    .activity { display: flex; gap: 12px; align-items: flex-start; padding: 12px; border-radius: 12px; background: linear-gradient(180deg, rgba(var(--overlay-rgb), 0.06), rgba(var(--overlay-rgb), 0.03)); border: 1px solid var(--card-border); }
    .activity .icon { width: 56px; height: 56px; border-radius: 10px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, rgba(var(--overlay-rgb), 0.08), rgba(var(--overlay-rgb), 0.03)); }
    .activity h4 { margin: 0; font-size: 15px; color: var(--text); }
    .activity p { margin: 4px 0 0 0; color: var(--muted); font-size: 13px; }
    .spotify { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-start; padding: 12px; border-radius: 12px; border: 1px solid var(--card-border); background: linear-gradient(90deg, rgba(67, 181, 129, 0.1), rgba(var(--overlay-rgb), 0.03)); }
    .spotify .meta { min-width: 150px; }
    .spotify .cover { position: relative; width: 66px; height: 66px; border-radius: 8px; overflow: hidden; }
    .pause-overlay { display: none; position: absolute; inset: 0; align-items: center; justify-content: center; background: rgba(0, 0, 0, 0.45); color: #fff; font-size: 18px; }
    .spotify .meta h4 { margin: 0; font-size: 15px; color: var(--text); }
    .spotify .meta p { margin: 6px 0 0 0; color: var(--muted); font-size: 13px; word-wrap: break-word; overflow-wrap: anywhere; white-space: normal; }
    .progress { height: 6px; background: rgba(var(--overlay-rgb), 0.12); border-radius: 6px; margin-top: 8px; overflow: hidden; }
    .progress .bar { height: 100%; width: 0%; border-radius: 6px; background: linear-gradient(90deg, var(--green), #6ddd9b); }
    .meta-row { display: flex; justify-content: space-between; align-items: center; gap: 12px; color: var(--muted); font-size: 13px; }
    .pulse { background: linear-gradient(90deg, rgba(var(--overlay-rgb), 0.03), rgba(var(--overlay-rgb), 0.07), rgba(var(--overlay-rgb), 0.03)); background-size: 200% 100%; animation: pulse 1.8s linear infinite; }
    @keyframes pulse { from { background-position: 200% 0 } to { background-position: -200% 0 } }
    @keyframes trackChange { 0% { opacity: 0; transform: translateY(6px) scale(0.98); } 100% { opacity: 1; transform: translateY(0) scale(1); } }
    .track-changed { animation: trackChange 0.45s ease; }
    .reveal { opacity: 0; transform: translateY(18px); transition: opacity 0.55s ease, transform 0.55s ease; }
    .reveal.in-view { opacity: 1; transform: translateY(0); }
    @media (prefers-reduced-motion: reduce) { .reveal, .track-changed { animation: none !important; transition: none !important; opacity: 1 !important; transform: none !important; } }
    .discord-btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 8px 14px; font-size: 14px; font-weight: 500; color: #fff; background: #5865F2; border-radius: 8px; text-decoration: none; line-height: 1; }
    .discord-btn i { font-size: 16px; display: flex; align-items: center; }
    .social-link.icon-fb:hover { background: #1877F2; border-color: #1877F2; color: #fff; }
    .social-link.icon-x:hover { background: #000000; border-color: #3a3a3a; color: #fff; }
    .social-link.icon-ig:hover { background: linear-gradient(135deg, #feda75, #fa7e1e, #d62976, #962fbf, #4f5bd5); border-color: transparent; color: #fff; }
    .social-link.icon-yt:hover { background: #FF0000; border-color: #FF0000; color: #fff; }
    .social-link.icon-tiktok:hover { background: #000000; border-color: #25F4EE; color: #fff; }
    .social-link.icon-twitch:hover { background: #9146FF; border-color: #9146FF; color: #fff; }
    .social-link.icon-github:hover { background: #333; border-color: #333; color: #fff; }
    .social-link.icon-telegram:hover { background: #26A5E4; border-color: #26A5E4; color: #fff; }
    .social-link.icon-discord:hover { background: #5865F2; border-color: #5865F2; color: #fff; }
    .social-link.icon-custom:hover { background: var(--accent); border-color: var(--accent); color: #fff; }
    .email-btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 8px 14px; font-size: 14px; font-weight: 500; color: #fff; background: #ed4545; border-radius: 8px; text-decoration: none; line-height: 1; transition: background 0.2s ease; }
    .email-btn:hover { background: #c44747; }
    .email-btn i { font-size: 16px; display: flex; align-items: center; }
    .contact-links { background: rgba(var(--overlay-rgb), 0.05); border: 1px solid var(--card-border); border-radius: 12px; padding: 12px; display: flex !important; flex-direction: column; gap: 4px; margin-top: 8px; backdrop-filter: blur(6px); width: 100%; box-sizing: border-box; }
    .social-title { font-weight: 700; font-size: 14px; color: var(--text); text-align: center; margin-bottom: 4px; }
    .visitor-count {
      text-align: center;
      font-size: 20px;
      font-weight: 700;
      letter-spacing: 0.02em;
      margin-top: 6px;
      color: var(--text);
    }
    .social-container { margin-top: 8px; display: flex; justify-content: center; align-items: center; gap: 6px; flex-wrap: wrap; width: 100%; }
    .social-link { display: inline-flex; align-items: center; justify-content: center; width: 38px; height: 38px; font-size: 16px; color: var(--muted); background: var(--social-bg); border: 1px solid var(--social-border); text-decoration: none; border-radius: 10px; transition: background 0.2s ease, border-color 0.2s ease, color 0.2s ease, transform 0.15s ease; }
    .social-link:hover { transform: translateY(-2px); }
    .custom-state-wrapper { display: flex; flex-direction: column; align-items: flex-start; margin-top: 40px; width: 100%; }
    .custom-state-title { font-weight: 700; font-size: 14px; color: var(--text); text-align: center; margin-bottom: 4px; }
    .custom-state { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; padding: 6px 12px; font-size: 15px; color: var(--text); background: rgba(var(--overlay-rgb), 0.05); border: 1px solid var(--card-border); border-radius: 12px; backdrop-filter: blur(6px); width: 100%; box-sizing: border-box; justify-content: center; }
    .custom-state span { display: inline-flex; align-items: center; justify-content: center; gap: 4px; flex-shrink: 1; word-break: break-word; white-space: normal; }
    .custom-state img { width: 18px; height: 18px; vertical-align: middle; border-radius: 4px; }
    .displayname { font-weight: 700; font-size: 22px; color: var(--text); display: inline-block; line-height: 1.2; }
    .status-label { position: absolute; left: 50%; bottom: 0; transform: translate(-50%, 50%); z-index: 2; display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; font-size: 11px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; white-space: nowrap; border-radius: 999px; border: 1px solid var(--card-border); color: var(--offline); background: var(--status-bg); backdrop-filter: blur(4px); }
    .status-label::before { content: ""; width: 6px; height: 6px; flex-shrink: 0; border-radius: 50%; background: currentColor; box-shadow: 0 0 6px currentColor; }
    .status-label.status-online { color: var(--green); border-color: rgba(67, 181, 129, 0.4); }
    .status-label.status-idle { color: var(--yellow); border-color: rgba(250, 166, 26, 0.4); }
    .status-label.status-dnd { color: var(--red); border-color: rgba(240, 71, 71, 0.4); }
    .status-label.status-offline { color: var(--offline); border-color: rgba(111, 119, 128, 0.4); }
    .gray-btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 8px 14px; font-size: 14px; font-weight: 500; color: #fff; background: #444; border-radius: 8px; text-decoration: none; line-height: 1; transition: background 0.2s ease; }
    .gray-btn:hover { background: #666; }
    .gray-btn i { font-size: 16px; display: flex; align-items: center; }
    @media (max-width:880px) { .container { grid-template-columns: 1fr; } .avatar-wrapper { width: 170px; height: 170px; } .profile { align-items: center; text-align: center; } }
    #loadingOverlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: var(--bg); display: flex; justify-content: center; align-items: center; z-index: 9999; }
    .loader { border: 6px solid rgba(var(--overlay-rgb), 0.15); border-top: 6px solid var(--accent); border-radius: 50%; width: 60px; height: 60px; animation: spin 1s linear infinite; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    .no-discord-msg { text-align: center; color: var(--muted); padding: 20px 0; }
    .lang-switch { position: fixed; top: 16px; right: 16px; z-index: 100; display: flex; gap: 2px; padding: 3px; border-radius: 999px; background: var(--status-bg); backdrop-filter: blur(10px); border: 1px solid var(--card-border); }
    .lang-switch .lang-btn { border: none; background: transparent; color: var(--muted); font-size: 12px; font-weight: 700; letter-spacing: 0.04em; padding: 5px 12px; border-radius: 999px; cursor: pointer; text-decoration: none; transition: background 0.2s ease, color 0.2s ease; }
    .lang-switch .lang-btn.active { background: var(--accent); color: #fff; }
    .lang-switch .lang-btn:hover:not(.active) { color: var(--text); }
  </style>
</head>

<body>
  <?= lang_switcher_html() ?>
  <div id="loadingOverlay"><div class="loader"></div></div>
  <?php if ($bgUrl): ?>
  <div class="bg-fixed" style="background-image:url('<?= e(safe_css_url($bgUrl)) ?>')"></div>
  <?php endif; ?>

  <div class="container">
    <div class="card profile" id="profileCard">
      <div class="avatar-wrapper">
        <div class="avatar" id="avatarWrap"><img id="avatarImg" src="" alt="avatar" /></div>
        <span id="statusLabel" class="status-label">—</span>
        <div class="name-row"><div class="displayname" id="displayName"><?= e($displayName) ?></div></div>
      </div>
      <div class="custom-state-wrapper">
        <div class="custom-state-title"><?= e(t('profile.status')) ?> <i class="fa-solid fa-comment-dots"></i></div>
        <div id="customState" class="custom-state">—</div>
      </div>

      <div class="contact-links">
        <?php if ($discordId): ?>
        <a href="https://discord.com/users/<?= e($discordId) ?>" target="_blank" class="discord-btn">
          <i class="fab fa-discord"></i> <span><?= e(t('profile.discord_profile')) ?></span>
        </a>
        <?php endif; ?>
        <div class="social-container" id="socialContainer"></div>
      </div>

      <?php if ($contactEmail): ?>
      <div class="contact-links">
        <div class="social-title"><?= e(t('profile.contact_us')) ?></div>
        <a href="#" id="emailBtn" class="gray-btn"><i class="fa-solid fa-envelope"></i> <span><?= e(t('profile.send_email')) ?></span></a>
      </div>
      <?php endif; ?>

      <div class="contact-links">
        <div class="social-title"><?= e(t('profile.visitor_counter')) ?></div>
        <div class="visitor-count" style="<?= e($counterColorStyle) ?>"><?= number_format($hitCount) ?></div>
      </div>

      <div class="meta-row" style="margin-top:auto; width:100%;">
        <div id="lastUpdated">—</div>
        <div><?= e(t('profile.lanyard_api')) ?></div>
      </div>
    </div>

    <div class="main">
      <?php if ($discordId): ?>
      <div class="card" style="padding:14px;">
        <div style="display:flex;align-items:center;justify-content:space-between;">
          <div style="display:flex; align-items:center; gap:12px;">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="48" height="48" aria-hidden="true" role="img" focusable="false">
              <path fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" d="M2 12h4l2-6 3 12 3-9 2 3h6" />
            </svg>
            <div>
              <div style="display:flex; align-items:center; gap:8px; font-weight:700"><?= e(t('profile.activity')) ?> <span id="statusDot" class="status-dot"></span></div>
              <div style="color:var(--muted); font-size:13px"><?= e(t('profile.activity_desc')) ?></div>
            </div>
          </div>
          <div style="font-size:13px;color:var(--muted)"><?= e(t('profile.discord_id')) ?> <span id="uid"><?= e($discordId) ?></span></div>
        </div>
        <div style="height:10px"></div>
        <div id="activities" class="activities">
          <div class="activity pulse" id="placeholderActivity">
            <div class="icon" style="width:48px;height:48px;border-radius:8px"></div>
            <div style="flex:1">
              <div style="width:40%;height:12px;border-radius:6px;margin-bottom:8px;background:rgba(var(--overlay-rgb),0.08)"></div>
              <div style="width:70%;height:10px;border-radius:6px;background:rgba(var(--overlay-rgb),0.05)"></div>
            </div>
          </div>
        </div>
      </div>

      <div id="watchingCard" class="card" style="display:none;">
        <div style="display:flex;justify-content:space-between;align-items:center;">
          <div style="font-weight:700"><i class="fa-solid fa-tv"></i> <?= e(t('profile.now_watching')) ?></div>
          <div style="color:var(--muted);font-size:13px" id="watchingSource">YouTube</div>
        </div>
        <div style="height:10px"></div>
        <div class="spotify" id="watchingInner">
          <div class="cover" id="watchingCover"><img src="" alt="cover" style="width:100%;height:100%;object-fit:cover" /><div class="pause-overlay" id="watchingPauseOverlay"><i class="fa-solid fa-pause"></i></div></div>
          <div class="meta" style="flex:1;"><h4 id="watchingTitle">Title</h4><p id="watchingArtist">Channel</p><div class="progress"><div class="bar" id="watchingProgress"></div></div></div>
          <div style="min-width:80px;margin-left:auto;text-align:right;color:var(--muted);font-size:13px" id="watchingTime">0:00</div>
        </div>
      </div>

      <div id="spotifyCard" class="card" style="display:none;">
        <div style="display:flex;justify-content:space-between;align-items:center;">
          <div style="font-weight:700"><i class="fas fa-headphones"></i> <?= e(t('profile.now_listening')) ?></div>
          <div style="color:var(--muted);font-size:13px">Spotify</div>
        </div>
        <div style="height:10px"></div>
        <div class="spotify" id="spotifyInner">
          <div class="cover" id="spotifyCover"><img src="" alt="cover" style="width:100%;height:100%;object-fit:cover" /></div>
          <div class="meta" style="flex:1;"><h4 id="spotifyTitle">Title</h4><p id="spotifyArtist">Artist • Album</p><div class="progress"><div class="bar" id="spotifyProgress"></div></div></div>
          <div style="min-width:80px;margin-left:auto;text-align:right;color:var(--muted);font-size:13px" id="spotifyTime">0:00</div>
        </div>
      </div>

      <div class="card" style="padding:12px; text-align:center; color:var(--muted); font-size:13px;">
        <div id="connectionInfo"><?= e(t('profile.js.connecting')) ?></div>
      </div>
      <?php else: ?>
      <div class="card no-discord-msg">
        <?= e(t('profile.no_discord')) ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const overlay = document.getElementById("loadingOverlay");
      overlay.style.opacity = 1;
      const fadeOut = setInterval(() => {
        overlay.style.opacity -= 0.05;
        if (overlay.style.opacity <= 0) { overlay.style.display = "none"; clearInterval(fadeOut); }
      }, 15);
    });

    // Social icons (rendered from server-side data, no hardcoded links)
    const SOCIALS = <?= $socialsJson ?: '[]' ?>;
    const socialContainer = document.getElementById("socialContainer");
    SOCIALS.forEach(s => {
      const a = document.createElement('a');
      a.className = 'social-link ' + (s.css_class || '');
      a.href = s.url;
      a.target = '_blank';
      a.rel = 'noopener noreferrer';
      a.setAttribute('aria-label', s.label);
      // Font Awesome brand logos (fab) and general solid icons (fa-solid) are separate sets;
      // "custom" links use a solid icon (globe/link), everything else is a brand logo.
      const prefix = s.platform === 'custom' ? 'fa-solid' : 'fab';
      a.innerHTML = `<i class="${prefix} ${s.icon}"></i>`;
      socialContainer.appendChild(a);
    });

    // Contact email: reconstructed client-side to avoid plain-text scraping
    const EMAIL_CODES = <?= $emailCodesJson ?: '[]' ?>;
    if (EMAIL_CODES.length) {
      const email = EMAIL_CODES.map(c => String.fromCharCode(c)).join('');
      const btn = document.getElementById('emailBtn');
      if (btn) btn.addEventListener('click', (ev) => { ev.preventDefault(); window.location.href = 'mailto:' + email; });
    }

    const I18N = <?= $jsI18nJson ?>;

    <?php if ($discordId): ?>
    (function () {
      const USER_ID = <?= json_encode($discordId) ?>;
      const API_URL = `https://api.lanyard.rest/v1/users/${USER_ID}`;
      const WS_URL = "wss://api.lanyard.rest/socket";
      const statusColors = { online: "var(--green)", idle: "var(--yellow)", dnd: "var(--red)", offline: "var(--offline)" };
      const statusLabels = { online: I18N.statusOnline, idle: I18N.statusIdle, dnd: I18N.statusDnd, offline: I18N.statusOffline };

      const uidEl = document.getElementById("uid"), avatarImg = document.getElementById("avatarImg"),
        displayName = document.getElementById("displayName"), statusDot = document.getElementById("statusDot"),
        statusLabel = document.getElementById("statusLabel"),
        activitiesEl = document.getElementById("activities"),
        placeholder = document.getElementById("placeholderActivity"), spotifyCard = document.getElementById("spotifyCard"),
        spotifyTitle = document.getElementById("spotifyTitle"), spotifyArtist = document.getElementById("spotifyArtist"),
        spotifyCover = document.querySelector("#spotifyCover img"), spotifyProgress = document.getElementById("spotifyProgress"),
        spotifyTime = document.getElementById("spotifyTime"), lastUpdated = document.getElementById("lastUpdated"),
        connectionInfo = document.getElementById("connectionInfo"),
        watchingCard = document.getElementById("watchingCard"), watchingSource = document.getElementById("watchingSource"),
        watchingTitle = document.getElementById("watchingTitle"), watchingArtist = document.getElementById("watchingArtist"),
        watchingCover = document.querySelector("#watchingCover img"), watchingProgress = document.getElementById("watchingProgress"),
        watchingTime = document.getElementById("watchingTime"), watchingPauseOverlay = document.getElementById("watchingPauseOverlay");

      function timeNowISO() { return new Date().toLocaleString(I18N.locale); }
      function msToHMS(ms) {
        if (!ms || ms < 0) return "0:00";
        const t = Math.floor(ms / 1000);
        const h = Math.floor(t / 3600), m = Math.floor((t % 3600) / 60), s = t % 60;
        const pad = (n) => (n < 10 ? "0" + n : "" + n);
        return h > 0 ? `${h}:${pad(m)}:${pad(s)}` : `${m}:${pad(s)}`;
      }

      function withFallbackImage(imgEl, fallbackUrl) {
        imgEl.addEventListener("error", () => { if (imgEl.src !== fallbackUrl) imgEl.src = fallbackUrl; });
      }
      withFallbackImage(avatarImg, "https://cdn.discordapp.com/embed/avatars/0.png");
      withFallbackImage(spotifyCover, "https://via.placeholder.com/128?text=Spotify");
      withFallbackImage(watchingCover, "https://via.placeholder.com/128?text=Watching");

      const customStateEl = document.getElementById("customState");

      function escapeHtml(s) { return !s && s !== 0 ? "" : String(s).replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll(">", "&gt;").replaceAll('"', "&quot;").replaceAll("'", "&#039;"); }

      function renderProfile(data) {
        const u = data.discord_user || {};
        uidEl.textContent = u.id || USER_ID;
        displayName.textContent = u.global_name || u.username || displayName.textContent;
        avatarImg.src = u.avatar
          ? `https://cdn.discordapp.com/avatars/${u.id}/${u.avatar}.png?size=512`
          : `https://cdn.discordapp.com/embed/avatars/${parseInt(u.discriminator || 0) % 5}.png`;

        const st = data.discord_status || "offline";
        statusDot.style.background = statusColors[st] || "var(--offline)";
        statusLabel.textContent = statusLabels[st] || I18N.statusOffline;
        statusLabel.className = "status-label status-" + (statusLabels[st] ? st : "offline");

        const customActivity = (data.activities || []).find(a => a.type === 4);
        const titleEl = document.querySelector(".custom-state-title");
        if (customActivity && customActivity.state) {
          customStateEl.innerHTML = "";
          titleEl.style.display = "block";
          customStateEl.style.display = "flex";
          if (customActivity.emoji) {
            const emojiSpan = document.createElement("span");
            if (customActivity.emoji.id) {
              const img = document.createElement("img");
              img.src = `https://cdn.discordapp.com/emojis/${customActivity.emoji.id}.${customActivity.emoji.animated ? "gif" : "png"}`;
              img.alt = customActivity.emoji.name;
              img.style.width = "18px"; img.style.height = "18px"; img.style.verticalAlign = "middle";
              emojiSpan.appendChild(img);
            } else if (typeof twemoji !== "undefined") {
              emojiSpan.innerHTML = twemoji.parse(customActivity.emoji.name || "", { folder: "svg", ext: ".svg" });
            } else {
              emojiSpan.textContent = customActivity.emoji.name || "";
            }
            customStateEl.appendChild(emojiSpan);
          }
          const textSpan = document.createElement("span");
          textSpan.textContent = customActivity.state;
          customStateEl.appendChild(textSpan);
        } else {
          customStateEl.textContent = "—";
          titleEl.style.display = "none";
          customStateEl.style.display = "none";
        }
        lastUpdated.textContent = I18N.update ? I18N.update.replace(":time", timeNowISO()) : ("Update: " + timeNowISO());
      }

      const ACTIVITY_FALLBACK_ICON = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="48" height="48" aria-hidden="true" focusable="false"><path fill="currentColor" d="M8 5v14l11-7z"/></svg>`;
      const appIconCache = new Map();
      function getAppIconUrl(applicationId) {
        if (!applicationId) return Promise.resolve("");
        if (appIconCache.has(applicationId)) return Promise.resolve(appIconCache.get(applicationId));
        return fetch(`https://discord.com/api/v10/applications/${applicationId}/rpc`)
          .then(res => res.ok ? res.json() : null)
          .then(data => { const url = data && data.icon ? `https://cdn.discordapp.com/app-icons/${applicationId}/${data.icon}.png` : ""; appIconCache.set(applicationId, url); return url; })
          .catch(() => "");
      }
      function setActivityIcon(iconWrap, url) {
        if (!url) return;
        const img = document.createElement("img");
        img.src = url; img.alt = "";
        img.style.cssText = "width:100%;height:100%;object-fit:cover;border-radius:10px";
        img.addEventListener("error", () => { iconWrap.innerHTML = ACTIVITY_FALLBACK_ICON; }, { once: true });
        iconWrap.innerHTML = ""; iconWrap.appendChild(img);
      }

      function clearActivities() { activitiesEl.innerHTML = ""; }
      function renderActivities(data) {
        clearActivities();
        const acts = (data.activities || []).filter(a => a.type !== 2 && a.type !== 3 && a.type !== 4);
        if (!acts.length) {
          const el = document.createElement("div"); el.className = "activity";
          el.innerHTML = `<div style="width:48px;height:48px;border-radius:8px;background:rgba(var(--overlay-rgb),0.08)"></div><div style="flex:1"><h4 style="margin:0;color:var(--muted)"><i class="fa-solid fa-cloud-moon"></i> ${escapeHtml(I18N.restingTitle)}</h4><p style="margin:6px 0 0 0;color:var(--muted)">${escapeHtml(I18N.restingDesc)}</p></div>`;
          activitiesEl.appendChild(el); return;
        }
        acts.forEach(a => {
          const el = document.createElement("div"); el.className = "activity";
          const title = a.name || "Activity", details = a.details || "", state = a.state || "";
          const iconWrap = document.createElement("div"); iconWrap.className = "icon"; iconWrap.innerHTML = ACTIVITY_FALLBACK_ICON;
          const rpcIconUrl = discordAssetUrl(a.assets && a.assets.large_image, a.application_id);
          if (rpcIconUrl) { setActivityIcon(iconWrap, rpcIconUrl); }
          else if (a.application_id) { getAppIconUrl(a.application_id).then(url => setActivityIcon(iconWrap, url)); }
          const body = document.createElement("div"); body.style.flex = "1";
          const subtitle = title + ((details || state) ? ` — ${details || state}` : "");
          const elapsedHtml = a.timestamps && a.timestamps.start
            ? `<p class="activity-elapsed" data-start="${a.timestamps.start}" style="display:flex;align-items:center;gap:5px;"><i class="fa-solid fa-gamepad"></i> <span class="elapsed-text">0:00</span></p>` : "";
          body.innerHTML = `<h4>${escapeHtml(I18N.playing)}</h4><p>${escapeHtml(subtitle)}</p>${elapsedHtml}`;
          el.appendChild(iconWrap); el.appendChild(body); activitiesEl.appendChild(el);
        });
      }

      function updateActivityElapsedTimes() {
        document.querySelectorAll(".activity-elapsed[data-start]").forEach(row => {
          const start = Number(row.dataset.start);
          const textEl = row.querySelector(".elapsed-text");
          if (textEl && start) textEl.textContent = msToHMS(Date.now() - start);
        });
      }
      setInterval(updateActivityElapsedTimes, 1000);

      let currentSpotify = null, currentWatching = null, lastSpotifyTrackId = null, lastWatchingKey = null;
      function playTrackChangeAnim(el) { el.classList.remove("track-changed"); void el.offsetWidth; el.classList.add("track-changed"); }
      function discordAssetUrl(image, applicationId) {
        if (!image) return "";
        if (image.startsWith("mp:")) return "https://media.discordapp.net/" + image.slice(3);
        if (image.startsWith("spotify:")) return "https://i.scdn.co/image/" + image.slice(8);
        return `https://cdn.discordapp.com/app-assets/${applicationId}/${image}.png`;
      }

      function autoCropLetterbox(imgEl, srcUrl) {
        const probe = new Image(); probe.crossOrigin = "anonymous";
        probe.onload = () => {
          try {
            const w = probe.naturalWidth, h = probe.naturalHeight;
            const canvas = document.createElement("canvas"); canvas.width = w; canvas.height = h;
            const ctx = canvas.getContext("2d"); ctx.drawImage(probe, 0, 0);
            const cx = Math.floor(w / 2);
            const isDark = (y) => { const d = ctx.getImageData(cx, y, 1, 1).data; return d[0] < 16 && d[1] < 16 && d[2] < 16; };
            let top = 0; while (top < h / 2 && isDark(top)) top++;
            let bottom = h - 1; while (bottom > h / 2 && isDark(bottom)) bottom--;
            const contentH = bottom - top;
            if (contentH > h * 0.15 && contentH < h * 0.92) {
              const scale = h / contentH; const contentCenter = (top + bottom) / 2;
              const boxSize = imgEl.clientWidth || 66; const shift = (h / 2 - contentCenter) * (boxSize / h);
              imgEl.style.transform = `scale(${scale.toFixed(3)}) translateY(${shift.toFixed(2)}px)`;
            } else { imgEl.style.transform = ""; }
          } catch (e) { imgEl.style.transform = ""; }
        };
        probe.onerror = () => { imgEl.style.transform = ""; };
        probe.src = srcUrl;
      }

      function renderSpotify(s) {
        if (!s || !s.track_id) { spotifyCard.style.display = "none"; currentSpotify = null; lastSpotifyTrackId = null; return; }
        spotifyCard.style.display = "block";
        spotifyTitle.textContent = s.song || s.track || "Unknown Track";
        spotifyArtist.textContent = (s.artist || "Unknown Artist") + " • " + (s.album || "");
        spotifyCover.src = s.album_art_url || "https://via.placeholder.com/128?text=Spotify";
        if (s.track_id !== lastSpotifyTrackId) { lastSpotifyTrackId = s.track_id; playTrackChangeAnim(document.getElementById("spotifyInner")); }
        currentSpotify = s; updateSpotifyProgress();
      }

      function updateProgressUI(current, progressEl, timeEl) {
        if (!current || !current.timestamps?.start || !current.timestamps?.end) { progressEl.style.width = "0%"; timeEl.textContent = ""; return; }
        const now = Date.now(); const { start, end } = current.timestamps;
        const total = end - start; const elapsed = now - start;
        const pct = Math.max(0, Math.min(100, (elapsed / total) * 100));
        progressEl.style.width = pct + "%"; timeEl.textContent = msToHMS(elapsed) + " / " + msToHMS(total);
      }
      function updateSpotifyProgress() { updateProgressUI(currentSpotify, spotifyProgress, spotifyTime); }

      function renderWatching(a) {
        if (!a) { watchingCard.style.display = "none"; currentWatching = null; lastWatchingKey = null; return; }
        watchingCard.style.display = "block";
        watchingSource.textContent = a.name || "Watching";
        watchingTitle.textContent = a.details || a.name || "Unknown";
        watchingArtist.textContent = a.state || "";
        const coverUrl = discordAssetUrl(a.assets && a.assets.large_image, a.application_id) || "https://via.placeholder.com/128?text=Watching";
        watchingCover.style.transform = ""; watchingCover.src = coverUrl; autoCropLetterbox(watchingCover, coverUrl);
        const isPaused = !!(a.assets && a.assets.small_text && /pause/i.test(a.assets.small_text));
        watchingPauseOverlay.style.display = isPaused ? "flex" : "none";
        const watchingKey = (a.session_id || "") + "|" + (a.details || a.name || "");
        if (watchingKey !== lastWatchingKey) { lastWatchingKey = watchingKey; playTrackChangeAnim(document.getElementById("watchingInner")); }
        currentWatching = a; updateWatchingProgress();
      }
      function updateWatchingProgress() { updateProgressUI(currentWatching, watchingProgress, watchingTime); }
      setInterval(updateSpotifyProgress, 500);
      setInterval(updateWatchingProgress, 500);

      function setConnectionInfo(text, isWarning) { connectionInfo.textContent = text; connectionInfo.style.color = isWarning ? "var(--yellow)" : ""; }

      async function fetchPresence() {
        try {
          const res = await fetch(API_URL, { cache: "no-store" });
          if (!res.ok) throw new Error("HTTP " + res.status);
          const j = await res.json(); const d = j.data;
          renderProfile(d); renderActivities(d); renderSpotify(d.spotify); renderWatching((d.activities || []).find(a => a.type === 3));
          setConnectionInfo(I18N.connectedRest, false);
          if (placeholder) placeholder.remove();
        } catch (e) { setConnectionInfo(I18N.restError.replace(":msg", e.message), true); }
      }

      let ws;
      function connectWS() {
        ws = new WebSocket(WS_URL);
        setConnectionInfo(I18N.connectingWs, false);
        ws.onopen = () => { setConnectionInfo(I18N.wsConnected, false); };
        ws.onmessage = ev => {
          try {
            const m = JSON.parse(ev.data);
            if (m.op === 1 && m.d?.heartbeat_interval) {
              setInterval(() => ws.send(JSON.stringify({ op: 3 })), m.d.heartbeat_interval);
              ws.send(JSON.stringify({ op: 2, d: { subscribe_to_id: USER_ID } }));
              return;
            }
            if ((m.t === "INIT_STATE" || m.t === "PRESENCE_UPDATE") && m.d) { applyPresence(m.d); }
          } catch (e) { }
        };
        ws.onclose = () => { setConnectionInfo(I18N.wsClosed, true); setTimeout(connectWS, 3000); };
      }
      function applyPresence(p) {
        const mapped = { discord_user: p.discord_user, discord_status: p.discord_status, activities: p.activities, spotify: p.spotify || null };
        renderProfile(mapped); renderActivities(mapped); renderSpotify(mapped.spotify); renderWatching((mapped.activities || []).find(a => a.type === 3));
      }

      if (!("IntersectionObserver" in window)) {
        document.querySelectorAll(".card").forEach(el => el.classList.add("reveal", "in-view"));
      } else {
        const revealObserver = new IntersectionObserver((entries) => {
          entries.forEach(entry => { if (entry.isIntersecting) { entry.target.classList.add("in-view"); revealObserver.unobserve(entry.target); } });
        }, { threshold: 0.1, rootMargin: "0px 0px -40px 0px" });
        document.querySelectorAll(".card").forEach(el => { el.classList.add("reveal"); revealObserver.observe(el); setTimeout(() => el.classList.add("in-view"), 1500); });
      }

      fetchPresence(); setInterval(fetchPresence, 60000);
      connectWS();
      document.getElementById("avatarWrap").addEventListener("click", () => { window.open(`https://discord.com/users/${USER_ID}`, "_blank"); });
    })();
    <?php else: ?>
    document.querySelectorAll(".card").forEach(el => el.classList.add("in-view"));
    <?php endif; ?>
  </script>
</body>
</html>
