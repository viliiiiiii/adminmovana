<?php
$dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s',
  getenv('DB_HOST'), getenv('DB_PORT') ?: '5432', getenv('DB_NAME'));
try {
  $pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASS'));
  echo "OK: " . $pdo->query('SELECT version()')->fetchColumn();
} catch (Throwable $e) {
  http_response_code(500);
  echo "DB ERROR: " . $e->getMessage();
}
