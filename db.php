<?php
// db.php — PDO bootstrap (Postgres/MySQL with SQLite fallback) + auto-migrate + admin seed

function db(): PDO {
  static $pdo = null;
  if ($pdo instanceof PDO) return $pdo;

  $driver = getenv('DB_DRIVER') ?: '';
  if ($driver === 'pgsql') {
    $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', getenv('DB_HOST'), getenv('DB_PORT') ?: '5432', getenv('DB_NAME'));
    $pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASS'), [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
  } elseif ($driver === 'mysql') {
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', getenv('DB_HOST'), getenv('DB_PORT') ?: '3306', getenv('DB_NAME'), getenv('DB_CHARSET') ?: 'utf8mb4');
    $pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASS'), [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
  } else {
    $dbDir = __DIR__ . '/var'; if (!is_dir($dbDir)) mkdir($dbDir, 0775, true);
    $pdo = new PDO('sqlite:' . $dbDir . '/app.sqlite', null, null, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA journal_mode=WAL; PRAGMA foreign_keys=ON;');
    $driver = 'sqlite';
  }
  ensure_schema($pdo, $driver);
  seed_admin_if_needed($pdo);
  return $pdo;
}

function ensure_schema(PDO $pdo, string $driver): void {
  $autoId = $driver === 'mysql' ? 'INT AUTO_INCREMENT PRIMARY KEY'
         : ($driver === 'pgsql' ? 'SERIAL PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT');
  $tsType = $driver === 'pgsql' ? 'TIMESTAMP' : ($driver === 'mysql' ? 'TIMESTAMP' : 'DATETIME');
  $now    = 'CURRENT_TIMESTAMP';

  // users
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS users (
      id $autoId,
      email VARCHAR(255) NOT NULL UNIQUE,
      pass_hash VARCHAR(255) NOT NULL,
      created_at $tsType DEFAULT $now,
      last_login $tsType NULL
    )
  ");

  // settings
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS settings (
      key VARCHAR(190) PRIMARY KEY,
      value TEXT NOT NULL
    )
  ");

  // leads
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

  // uploads
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

  // activity
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS activity (
      id $autoId,
      action VARCHAR(64) NOT NULL,
      meta TEXT NOT NULL,
      created_at $tsType DEFAULT $now
    )
  ");

  // companies
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS companies (
      id $autoId,
      name VARCHAR(255) NOT NULL,
      domain VARCHAR(255) NULL,
      status VARCHAR(32) NOT NULL DEFAULT 'prospect', -- prospect, active, paused, closed
      site_url VARCHAR(500) NULL,
      notes TEXT NULL,
      created_at $tsType DEFAULT $now
    )
  ");

  // company_financials (monthly)
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS company_financials (
      id $autoId,
      company_id INT NOT NULL,
      period VARCHAR(7) NOT NULL, -- YYYY-MM
      revenue_cents BIGINT NOT NULL DEFAULT 0,
      expenses_cents BIGINT NOT NULL DEFAULT 0,
      notes TEXT NULL,
      created_at $tsType DEFAULT $now
    )
  ");

  // FK if supported
  try {
    if ($driver !== 'sqlite') {
      $pdo->exec("ALTER TABLE company_financials
        ADD CONSTRAINT IF NOT EXISTS fk_fin_company
        FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE");
    }
  } catch (Throwable $e) { /* ignore if already exists */ }

  if (setting_get('spots_left') === null) {
    setting_set('spots_left', 3);
    activity('init_defaults', []);
  }
}

function seed_admin_if_needed(PDO $pdo): void {
  $email = getenv('ADMIN_USER') ?: null;
  $hash  = getenv('ADMIN_PASS_HASH') ?: null;
  if (!$email || !$hash) return;
  $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?'); $stmt->execute([$email]);
  if (!$stmt->fetchColumn()) {
    $pdo->prepare('INSERT INTO users(email, pass_hash) VALUES (?, ?)')->execute([$email, $hash]);
    activity('seed_admin', ['email' => $email]);
  }
}

/** Activity log */
function activity(string $action, array $meta = []): void {
  $pdo = db();
  $pdo->prepare('INSERT INTO activity(action, meta, created_at) VALUES (?, ?, CURRENT_TIMESTAMP)')
      ->execute([$action, json_encode($meta, JSON_UNESCAPED_UNICODE)]);
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
    $sql = 'INSERT INTO settings(`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)';
  } elseif ($driver === 'pgsql') {
    $sql = 'INSERT INTO settings(key,value) VALUES (?,?) ON CONFLICT (key) DO UPDATE SET value=EXCLUDED.value';
  } else {
    $sql = 'INSERT INTO settings(key,value) VALUES (?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value';
  }
  $pdo->prepare($sql)->execute([$key, $val]);
}
