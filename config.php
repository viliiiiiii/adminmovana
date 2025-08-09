<?php
// config.php — shared bootstrap for Movana Admin (PHP)

// --- Security headers (basic hardening) ---
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: no-referrer-when-downgrade');
header('X-XSS-Protection: 0'); // modern browsers ignore; kept explicit
// Content-Security-Policy kept minimal since we load Tailwind CDN on login page.
header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' data: https://fonts.gstatic.com; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com");

// --- Session (secure cookie) ---
$cookieDomain = getenv('COOKIE_DOMAIN') ?: '';
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
      || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

session_set_cookie_params([
  'lifetime' => 60 * 60 * 8, // 8h
  'path'     => '/',
  'domain'   => $cookieDomain ?: null,
  'secure'   => $https,       // requires HTTPS in prod
  'httponly' => true,
  'samesite' => 'Lax',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

// --- Env-based credentials ---
define('ADMIN_USER', getenv('ADMIN_USER') ?: 'admin@movana.me');
// Store a PASSWORD_DEFAULT hash in ADMIN_PASS_HASH env.
// Generate with: php -r "echo password_hash('StrongPass123!', PASSWORD_DEFAULT), PHP_EOL;"
define('ADMIN_PASS_HASH', getenv('ADMIN_PASS_HASH') ?: '');
if (ADMIN_PASS_HASH === '') {
  http_response_code(500);
  exit('Server not configured: set ADMIN_PASS_HASH env var.');
}

// --- CSRF helpers ---
if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
function csrf_token(): string {
  return $_SESSION['csrf'];
}
function csrf_check(string $token): bool {
  return hash_equals($_SESSION['csrf'] ?? '', $token);
}

// --- Auth helpers ---
function is_logged_in(): bool {
  return !empty($_SESSION['auth']) && $_SESSION['auth'] === true && !empty($_SESSION['admin_user']);
}
function require_login(): void {
  if (!is_logged_in()) {
    header('Location: /login.php');
    exit;
  }
}
function login_admin(string $email): void {
  $_SESSION['auth'] = true;
  $_SESSION['admin_user'] = $email;
  $_SESSION['last_login'] = time();
}
function logout_admin(): void {
  $_SESSION = [];
  if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
  }
  session_destroy();
}

// --- Small view helpers ---
function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
