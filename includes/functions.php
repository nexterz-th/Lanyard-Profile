<?php
declare(strict_types=1);

function redirect(string $path): void {
    header('Location: ' . $path);
    exit;
}

function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function flash_set(string $key, string $message): void {
    $_SESSION['flash'][$key] = $message;
}

function flash_get(string $key): ?string {
    if (!empty($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

function is_valid_username(string $username): bool {
    return (bool) preg_match('/^[a-z0-9_-]{3,20}$/', $username);
}

function is_valid_discord_id(string $id): bool {
    return $id === '' || (bool) preg_match('/^\d{15,25}$/', $id);
}

function is_valid_hex_color(string $color): bool {
    return (bool) preg_match('/^#[0-9a-fA-F]{6}$/', $color);
}

// 'prefix' matters because Font Awesome's brand logos (fab) and general solid icons (fa-solid)
// are separate icon sets — using the wrong one renders a blank/missing glyph.
const SOCIAL_PLATFORMS = [
    'facebook'  => ['label' => 'Facebook',      'icon' => 'fa-facebook-f', 'class' => 'icon-fb',      'prefix' => 'fab'],
    'x'         => ['label' => 'X (Twitter)',   'icon' => 'fa-x-twitter',  'class' => 'icon-x',       'prefix' => 'fab'],
    'instagram' => ['label' => 'Instagram',     'icon' => 'fa-instagram',  'class' => 'icon-ig',      'prefix' => 'fab'],
    'youtube'   => ['label' => 'YouTube',       'icon' => 'fa-youtube',    'class' => 'icon-yt',      'prefix' => 'fab'],
    'tiktok'    => ['label' => 'TikTok',        'icon' => 'fa-tiktok',     'class' => 'icon-tiktok',  'prefix' => 'fab'],
    'twitch'    => ['label' => 'Twitch',        'icon' => 'fa-twitch',     'class' => 'icon-twitch',  'prefix' => 'fab'],
    'github'    => ['label' => 'GitHub',        'icon' => 'fa-github',     'class' => 'icon-github',  'prefix' => 'fab'],
    'telegram'  => ['label' => 'Telegram',      'icon' => 'fa-telegram',   'class' => 'icon-telegram','prefix' => 'fab'],
    'discord'   => ['label' => 'Discord Server','icon' => 'fa-discord',    'class' => 'icon-discord', 'prefix' => 'fab'],
    'custom'    => ['label' => 'Custom Link',   'icon' => 'fa-globe',      'class' => 'icon-custom',  'prefix' => 'fa-solid'],
];

function social_icon_meta(string $platform): array {
    return SOCIAL_PLATFORMS[$platform] ?? SOCIAL_PLATFORMS['custom'];
}

function social_icon_prefix(string $platform): string {
    return SOCIAL_PLATFORMS[$platform]['prefix'] ?? 'fa-solid';
}
