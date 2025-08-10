<?php
require_once __DIR__ . '/config.php';
if (is_logged_in()) { header('Location: /index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $pass  = $_POST['password'] ?? '';
  $tok   = $_POST['csrf'] ?? '';
  if (!csrf_check($tok))        $error = 'Invalid CSRF token.';
  elseif ($email === '' || $pass === '') $error = 'Email and password required.';
  elseif (login_admin_db($email, $pass)) { header('Location: /index.php'); exit; }
  else $error = 'Invalid credentials.';
}
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Movana Admin Login</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>(function(){const s=localStorage.getItem('theme');const d=window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches;if(s==='dark'||(!s&&d))document.documentElement.classList.add('dark');})();</script>
</head>
<body class="bg-gray-100 dark:bg-slate-900 min-h-screen grid place-items-center p-6">
  <div class="bg-white dark:bg-slate-950 shadow-lg p-8 rounded w-full max-w-md border border-slate-200 dark:border-slate-800">
    <h1 class="text-2xl font-bold mb-6 text-center">Movana Admin</h1>
    <?php if ($error): ?><div class="mb-4 text-sm text-red-600"><?= e($error) ?></div><?php endif; ?>
    <form method="POST" class="space-y-4">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="email" name="email" placeholder="Email" class="w-full border px-3 py-2 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700" required>
      <input type="password" name="password" placeholder="Password" class="w-full border px-3 py-2 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700" required>
      <button class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">Sign in</button>
    </form>
  </div>
</body></html>
