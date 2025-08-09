<?php
require __DIR__ . '/config.php';
require __DIR__ . '/db.php';
require __DIR__ . '/flash.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check($_POST['csrf'] ?? '')) {
  http_response_code(400);
  exit('Bad request');
}

$act = $_POST['action'] ?? '';
$redirect = $_POST['_back'] ?? '/index.php';

try {
  switch ($act) {
    case 'spots_inc': {
      $v = (int) setting_get('spots_left', 3);
      $v = min(99, $v + 1);
      setting_set('spots_left', $v);
      activity('spots_inc', ['value' => $v]);
      flash_add('success', "Spots set to $v");
      break;
    }
    case 'spots_dec': {
      $v = (int) setting_get('spots_left', 3);
      $v = max(0, $v - 1);
      setting_set('spots_left', $v);
      activity('spots_dec', ['value' => $v]);
      flash_add('success', "Spots set to $v");
      break;
    }
    case 'lead_add': {
      $name = trim($_POST['name'] ?? '');
      $email = trim($_POST['email'] ?? '');
      $message = trim($_POST['message'] ?? '');
      if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Name and valid email are required.');
      }
      $pdo = db();
      $stmt = $pdo->prepare('INSERT INTO leads(name, email, message) VALUES (?, ?, ?)');
      $stmt->execute([$name, $email, $message]);
      activity('lead_add', ['name' => $name, 'email' => $email]);
      flash_add('success', 'Lead added.');
      break;
    }
    case 'lead_status': {
      $id = (int)($_POST['id'] ?? 0);
      $status = $_POST['status'] ?? 'new';
      $pdo = db();
      $stmt = $pdo->prepare('UPDATE leads SET status=? WHERE id=?');
      $stmt->execute([$status, $id]);
      activity('lead_status', ['id' => $id, 'status' => $status]);
      flash_add('success', "Lead #$id → $status");
      break;
    }
    case 'lead_delete': {
      $id = (int)($_POST['id'] ?? 0);
      $pdo = db();
      $pdo->prepare('DELETE FROM leads WHERE id=?')->execute([$id]);
      activity('lead_delete', ['id' => $id]);
      flash_add('success', "Lead #$id deleted.");
      break;
    }
    case 'upload_file': {
      if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed.');
      }
      $file = $_FILES['file'];
      if ($file['size'] > 5 * 1024 * 1024) { // 5MB
        throw new RuntimeException('File too large (max 5MB).');
      }
      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $mime = finfo_file($finfo, $file['tmp_name']);
      finfo_close($finfo);
      $allowed = ['image/jpeg','image/png','image/webp','application/pdf','text/plain','text/csv'];
      if (!in_array($mime, $allowed, true)) {
        throw new RuntimeException('Unsupported file type.');
      }
      $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'bin';
      $safeName = bin2hex(random_bytes(8)) . '.' . $ext;
      $destDir = __DIR__ . '/var/uploads';
      if (!is_dir($destDir)) mkdir($destDir, 0775, true);
      $dest = $destDir . '/' . $safeName;
      if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Failed to save file.');
      }
      $pdo = db();
      $pdo->prepare('INSERT INTO uploads(filename, path, size, mime) VALUES (?,?,?,?)')
          ->execute([$file['name'], 'var/uploads/' . $safeName, $file['size'], $mime]);

      activity('upload_file', ['filename' => $file['name'], 'mime' => $mime, 'size' => $file['size']]);
      flash_add('success', 'File uploaded.');
      break;
    }
    case 'purge_cache': {
      // Placeholder: hook your CDN here
      activity('purge_cache', ['by' => $_SESSION['admin_user']]);
      flash_add('success', 'Cache purge requested.');
      break;
    }
    default:
      throw new RuntimeException('Unknown action.');
  }

} catch (Throwable $e) {
  flash_add('error', $e->getMessage());
}

header('Location: ' . $redirect);
exit;
