<?php
// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: no-referrer-when-downgrade');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' data: https://fonts.gstatic.com; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com");

// Sessions
$cookieDomain = getenv('COOKIE_DOMAIN') ?: '';
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
      || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

session_set_cookie_params([
  'lifetime' => 60 * 60 * 8,
  'path'     => '/',
  'domain'   => $cookieDomain ?: null,
  'secure'   => $https,
  'httponly' => true,
  'samesite' => 'Lax',
]);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// CSRF
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
function csrf_token(): string { return $_SESSION['csrf']; }
function csrf_check(string $t): bool { return hash_equals($_SESSION['csrf'] ?? '', $t); }

// Auth helpers (DB-backed)
function is_logged_in(): bool {
  return !empty($_SESSION['auth']) && !empty($_SESSION['user_id']);
}
function require_login(): void {
  if (!is_logged_in()) { header('Location: /login.php'); exit; }
}
function login_admin_db(string $email, string $password): bool {
  require_once __DIR__ . '/db.php';
  $pdo = db();
  $stmt = $pdo->prepare('SELECT id, email, pass_hash FROM users WHERE email = ?');
  $stmt->execute([$email]);
  $u = $stmt->fetch();
  if ($u && password_verify($password, $u['pass_hash'])) {
    $_SESSION['auth'] = true;
    $_SESSION['user_id'] = (int)$u['id'];
    $_SESSION['admin_user'] = $u['email'];
    $pdo->prepare('UPDATE users SET last_login=CURRENT_TIMESTAMP WHERE id=?')->execute([$u['id']]);
    return true;
  }
  return false;
}
function change_password(int $userId, string $newPass): void {
  require_once __DIR__ . '/db.php';
  $pdo = db();
  $hash = password_hash($newPass, PASSWORD_DEFAULT);
  $pdo->prepare('UPDATE users SET pass_hash=? WHERE id=?')->execute([$hash, $userId]);
}
function logout_admin(): void {
  $_SESSION = [];
  if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
  }
  session_destroy();
}
function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
