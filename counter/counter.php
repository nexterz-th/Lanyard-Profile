<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';

header('Content-Type: image/png');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');

$username = strtolower(trim($_GET['u'] ?? ''));
$count = 0;

if (preg_match('/^[a-z0-9_-]{3,20}$/', $username)) {
    $stmt = $pdo->prepare('SELECT id, hit_count FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        $pdo->prepare('UPDATE users SET hit_count = hit_count + 1 WHERE id = ?')->execute([$user['id']]);
        $count = (int) $user['hit_count'] + 1;

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ipHash = hash('sha256', $ip);
        try {
            $pdo->prepare('INSERT INTO visitor_logs (user_id, ip_hash, visit_date) VALUES (?, ?, CURDATE())')
                ->execute([$user['id'], $ipHash]);
        } catch (PDOException $e) {
            // Already logged for this IP today — ignore duplicate key error.
        }
    }
}

$digits = (string) $count;
$font = 5; // GD's largest built-in font, no external font file required
$digitCellWidth = imagefontwidth($font) + 6;
$height = 30;
$width = max(60, strlen($digits) * $digitCellWidth + 16);

$img = imagecreatetruecolor($width, $height);
$bg = imagecolorallocate($img, 11, 15, 18);
$border = imagecolorallocate($img, 60, 65, 70);
$fg = imagecolorallocate($img, 67, 181, 129);

imagefill($img, 0, 0, $bg);
imagerectangle($img, 0, 0, $width - 1, $height - 1, $border);

$textWidth = imagefontwidth($font) * strlen($digits);
$textHeight = imagefontheight($font);
$x = (int) (($width - $textWidth) / 2);
$y = (int) (($height - $textHeight) / 2);
imagestring($img, $font, $x, $y, $digits, $fg);

imagepng($img);
imagedestroy($img);
