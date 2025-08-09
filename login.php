<?php
require __DIR__ . '/config.php';

// If already logged in, go to dashboard
if (is_logged_in()) {
    header('Location: /index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $token = $_POST['csrf'] ?? '';

    if (!csrf_check($token)) {
        $error = 'Invalid CSRF token. Please try again.';
    } elseif (strcasecmp($email, ADMIN_USER) === 0 && password_verify($pass, ADMIN_PASS_HASH)) {
        login_admin($email);
        header('Location: /index.php');
        exit;
    } else {
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Movana Admin Login</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
<div class="bg-white shadow-lg p-8 rounded w-96">
  <h1 class="text-2xl font-bold mb-6 text-center">Admin Login</h1>
  <?php if (!empty($error)): ?>
    <div class="mb-4 text-red-600 text-sm"><?= e($error) ?></div>
  <?php endif; ?>
  <form method="POST" class="space-y-4">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <div>
      <input type="email" name="email" placeholder="Email" value="<?= e($_POST['email'] ?? '') ?>" 
             class="w-full border px-3 py-2 rounded" required />
    </div>
    <div>
      <input type="password" name="password" placeholder="Password" 
             class="w-full border px-3 py-2 rounded" required />
    </div>
    <div>
      <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">
        Sign in
      </button>
    </div>
  </form>
</div>
</body>
</html>
