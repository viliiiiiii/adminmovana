<?php
// db.php — PDO bootstrap for Postgres/MySQL with SQLite fallback + auto-migrate

function db(): PDO {
  static $pdo = null;
  if ($pdo instanceof PDO) return $pdo;

  $driver = getenv('DB_DRIVER') ?: '';
  if ($driver === 'pgsql') {
    $host = getenv('DB_HOST') ?: 'localhost';
    $port = getenv('DB_PORT') ?: '5432';
    $name = getenv('DB_NAME') ?: 'movana_admin';
    $user = getenv('DB_USER') ?: 'movana';
    $pass = getenv('DB_PASS') ?: '';
    $dsn  = "pgsql:host={$host};port={$port};dbname={$name}";
    $pdo  = new PDO($dsn, $user, $pass, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
  } elseif ($driver === 'mysql') {
    $host = getenv('DB_HOST') ?: 'localhost';
    $port = getenv('DB_PORT') ?: '3306';
    $name = getenv('DB_NAME') ?: 'movana_admin';
    $user = getenv('DB_USER') ?: 'movana';
    $pass = getenv('DB_PASS') ?: '';
    $charset = getenv('DB_CHARSET') ?: 'utf8mb4';
    $dsn  = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
    $pdo  = new PDO($dsn, $user, $pass, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
  } else {
    // Fallback: SQLite (handy for local dev)
    $dbDir = __DIR__ . '/var';
    if (!is_dir($dbDir)) mkdir($dbDir, 0775, true);
    $dsn  = 'sqlite:' . $dbDir . '/app.sqlite';
    $pdo  = new PDO($dsn, null, null, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA journal_mode=WAL;');
    $pdo->exec('PRAGMA foreign_keys=ON;');
    $driver = 'sqlite';
  }

  ensure_schema($pdo, $driver);
  return $pdo;
}

function ensure_schema(PDO $pdo, string $driver): void {
  // id/autoinc + timestamp types per driver
  $autoId = $driver === 'mysql' ? 'INT AUTO_INCREMENT PRIMARY KEY'
           : ($driver === 'pgsql' ? 'SERIAL PRIMARY KEY'
           : 'INTEGER PRIMARY KEY AUTOINCREMENT'); // sqlite
  $tsType = $driver === 'pgsql' ? 'TIMESTAMP' : ($driver === 'mysql' ? 'TIMESTAMP' : 'DATETIME');
  $now    = $driver === 'mysql' ? 'CURRENT_TIMESTAMP' : 'CURRENT_TIMESTAMP';

  // Create tables if not exist
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS settings (
      key VARCHAR(190) PRIMARY KEY,
      value TEXT NOT NULL
    )
  ");
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS leads (
      id $autoId,
      name VARCHAR(255) NOT NULL,
      email VARCHAR(255) NOT NULL,
      message TEXT,
      status VARCHAR(32) NOT NULL DEFAULT 'new',
      created_at $tsType DEFAULT $now
    )
  ");
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS uploads (
      id $autoId,
      filename VARCHAR(255) NOT NULL,
      path VARCHAR(500) NOT NULL,
      size BIGINT NOT NULL,
      mime VARCHAR(100) NOT NULL,
      uploaded_at $tsType DEFAULT $now
    )
  ");
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS activity (
      id $autoId,
      action VARCHAR(64) NOT NULL,
      meta TEXT NOT NULL,
      created_at $tsType DEFAULT $now
    )
  ");

  // Set default settings once
  if (setting_get('spots_left') === null) {
    setting_set('spots_left', 3);
    activity('init_defaults', []);
  }
}

/** Activity log */
function activity(string $action, array $meta = []): void {
  $pdo = db();
  $stmt = $pdo->prepare('INSERT INTO activity(action, meta, created_at) VALUES (?, ?, CURRENT_TIMESTAMP)');
  $stmt->execute([$action, json_encode($meta, JSON_UNESCAPED_UNICODE)]);
}

/** Settings helpers with portable upsert */
function setting_get(string $key, $default = null) {
  $pdo = db();
  $stmt = $pdo->prepare('SELECT value FROM settings WHERE key = ?');
  $stmt->execute([$key]);
  $row = $stmt->fetch();
  return $row ? json_decode($row['value'], true) : $default;
}
function setting_set(string $key, $value): void {
  $pdo = db();
  $val = json_encode($value, JSON_UNESCAPED_UNICODE);
  $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
  if ($driver === 'mysql') {
    $sql = 'INSERT INTO settings(`key`,`value`) VALUES (?,?)
            ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)';
  } elseif ($driver === 'pgsql') {
    $sql = 'INSERT INTO settings(key,value) VALUES (?,?)
            ON CONFLICT (key) DO UPDATE SET value=EXCLUDED.value';
  } else { // sqlite
    $sql = 'INSERT INTO settings(key,value) VALUES (?,?)
            ON CONFLICT(key) DO UPDATE SET value=excluded.value';
  }
  $pdo->prepare($sql)->execute([$key, $val]);
}
