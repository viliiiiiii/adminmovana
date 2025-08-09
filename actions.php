<?php
require __DIR__ . '/config.php';
require __DIR__ . '/db.php';
require __DIR__ . '/flash.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check($_POST['csrf'] ?? '')) {
  http_response_code(400); exit('Bad request');
}

$act = $_POST['action'] ?? '';
$back = $_POST['_back'] ?? '/index.php';

try {
  switch ($act) {
    case 'spots_inc':
    case 'spots_dec': {
      $v = (int) setting_get('spots_left', 3);
      $v = $act === 'spots_inc' ? min(99, $v + 1) : max(0, $v - 1);
      setting_set('spots_left', $v);
      activity($act, ['value' => $v]);
      flash_add('success', "Spots set to $v");
      break;
    }

    case 'settings_save': {
      $spots = max(0, (int)($_POST['spots_left'] ?? 0));
      setting_set('spots_left', $spots);
      activity('settings_save', ['spots_left' => $spots]);
      flash_add('success', 'Settings saved.');
      break;
    }

    case 'password_change': {
      $pw1 = $_POST['new_password'] ?? '';
      $pw2 = $_POST['new_password2'] ?? '';
      if ($pw1 === '' || $pw1 !== $pw2 || strlen($pw1) < 8) {
        throw new RuntimeException('Passwords must match and be at least 8 characters.');
      }
      change_password((int)$_SESSION['user_id'], $pw1);
      activity('password_change', ['user' => $_SESSION['admin_user']]);
      flash_add('success', 'Password changed.');
      break;
    }

    case 'lead_add': {
      $name = trim($_POST['name'] ?? '');
      $email = trim($_POST['email'] ?? '');
      $message = trim($_POST['message'] ?? '');
      if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Name and valid email are required.');
      }
      db()->prepare('INSERT INTO leads(name,email,message) VALUES (?,?,?)')->execute([$name,$email,$message]);
      activity('lead_add', ['name'=>$name,'email'=>$email]); flash_add('success','Lead added.');
      break;
    }

    case 'lead_status': {
      $id = (int)($_POST['id'] ?? 0); $status = $_POST['status'] ?? 'new';
      db()->prepare('UPDATE leads SET status=? WHERE id=?')->execute([$status,$id]);
      activity('lead_status', ['id'=>$id,'status'=>$status]); flash_add('success',"Lead #$id → $status");
      break;
    }

    case 'lead_delete': {
      $id = (int)($_POST['id'] ?? 0);
      db()->prepare('DELETE FROM leads WHERE id=?')->execute([$id]);
      activity('lead_delete', ['id'=>$id]); flash_add('success',"Lead #$id deleted.");
      break;
    }

    case 'upload_file': {
      if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload failed.');
      $file = $_FILES['file'];
      if ($file['size'] > 5 * 1024 * 1024) throw new RuntimeException('File too large (max 5MB).');
      $finfo = finfo_open(FILEINFO_MIME_TYPE); $mime = finfo_file($finfo, $file['tmp_name']); finfo_close($finfo);
      $allowed = ['image/jpeg','image/png','image/webp','application/pdf','text/plain','text/csv'];
      if (!in_array($mime, $allowed, true)) throw new RuntimeException('Unsupported file type.');
      $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'bin';
      $safe = bin2hex(random_bytes(8)) . '.' . $ext;
      $dir = __DIR__ . '/var/uploads'; if (!is_dir($dir)) mkdir($dir, 0775, true);
      $dest = $dir . '/' . $safe; if (!move_uploaded_file($file['tmp_name'], $dest)) throw new RuntimeException('Failed to save file.');
      db()->prepare('INSERT INTO uploads(filename,path,size,mime) VALUES (?,?,?,?)')
         ->execute([$file['name'], 'var/uploads/' . $safe, $file['size'], $mime]);
      activity('upload_file', ['fn'=>$file['name'],'mime'=>$mime,'size'=>$file['size']]); flash_add('success','File uploaded.');
      break;
    }

    case 'file_delete': {
      $id = (int)($_POST['id'] ?? 0);
      $row = db()->prepare('SELECT path FROM uploads WHERE id=?'); $row->execute([$id]); $f = $row->fetch();
      if ($f) {
        @unlink(__DIR__ . '/' . $f['path']);
        db()->prepare('DELETE FROM uploads WHERE id=?')->execute([$id]);
        activity('file_delete', ['id'=>$id]); flash_add('success',"File #$id deleted.");
      }
      break;
    }

    case 'purge_cache': {
      activity('purge_cache', ['by'=>$_SESSION['admin_user']]); flash_add('success','Cache purge requested.');
      break;
    }

    default: throw new RuntimeException('Unknown action.');
  }

} catch (Throwable $e) {
  flash_add('error', $e->getMessage());
}

header('Location: ' . $back);
exit;
