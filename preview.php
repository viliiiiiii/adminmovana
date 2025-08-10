<?php
require __DIR__ . '/config.php';
require __DIR__ . '/db.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { http_response_code(404); exit('Not found'); }

$row = db()->prepare('SELECT filename, path, mime FROM uploads WHERE id=?');
$row->execute([$id]);
$f = $row->fetch();
if (!$f) { http_response_code(404); exit('Not found'); }

$abs = __DIR__ . '/' . $f['path'];
if (!is_file($abs)) { http_response_code(404); exit('Missing'); }

// Allow only safe mime types for inline preview
$mime = $f['mime'];
$inline = in_array($mime, ['image/jpeg','image/png','image/webp','application/pdf','text/plain','text/csv'], true);

header('Content-Type: ' . $mime);
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="'.basename($f['filename']).'"');
readfile($abs);
