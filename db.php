<?php
// db.php — SQLite bootstrap + helpers

const DB_DIR  = __DIR__ . '/var';
const DB_FILE = DB_DIR . '/app.sqlite';

if (!is_dir(DB_DIR)) {
  mkdir(DB_DIR, 0775, true);
}

function db(): PDO {
  static $pdo = null;
  if ($pdo instanceof PDO) return $pdo;

  $pdo = new PDO('sqlite:' . DB_FILE, null, null, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
  ]);
  $pdo->exec('PRAGMA journal_mode=WAL;');
  $pdo->exec('PRAGMA foreign_keys=ON;');
  return $pdo;
}

/** Write to activity log */
function activity(string $action, array $meta = []): void {
  $pdo = db();
  $stmt = $pdo->prepare('INSERT INTO activity(action, meta, created_at) VALUES (?, ?, CURRENT_TIMESTAMP)');
  $stmt->execute([$action, json_encode($meta, JSON_UNESCAPED_UNICODE)]);
}

/** Quick setting get/set */
function setting_get(string $key, $default = null) {
  $pdo = db();
  $stmt = $pdo->prepare('SELECT value FROM settings WHERE key = ?');
  $stmt->execute([$key]);
  $row = $stmt->fetch();
  return $row ? json_decode($row['value'], true) : $default;
}
function setting_set(string $key, $value): void {
  $pdo = db();
  $json = json_encode($value, JSON_UNESCAPED_UNICODE);
  $pdo->prepare('INSERT INTO settings(key, value) VALUES (?, ?)
                 ON CONFLICT(key) DO UPDATE SET value=excluded.value')->execute([$key, $json]);
}
