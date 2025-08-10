<?php
require_once __DIR__ . '/config.php';
$error = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!csrf_check($_POST['csrf'] ?? '')) { $error='Invalid token'; }
  else {
    $ok = login_admin_db(trim($_POST['email'] ?? ''), $_POST['password'] ?? '');
    if ($ok) { header('Location: /index.php'); exit; }
    $error = 'Invalid credentials';
  }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login · Movana Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
<script> (function(){const s=localStorage.getItem('theme');if(s==='dark')document.documentElement.classList.add('dark')})(); </script>
</head>
<body class="min-h-screen grid place-items-center bg-slate-100 dark:bg-slate-950 text-slate-900 dark:text-slate-100">
  <div class="w-full max-w-md p-8 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-950/60 backdrop-blur">
    <div class="flex items-center gap-3 mb-6">
      <div class="h-10 w-10 rounded-xl bg-indigo-600"></div>
      <div class="text-lg font-bold">Movana Admin</div>
    </div>
    <?php if ($error): ?>
      <div class="mb-4 px-4 py-3 rounded-lg border bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-900/25 dark:text-rose-100 dark:border-rose-800"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="POST" class="space-y-3">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <div>
        <label class="text-sm">Email</label>
        <input type="email" name="email" required class="input w-full">
      </div>
      <div>
        <label class="text-sm">Password</label>
        <input type="password" name="password" required class="input w-full">
      </div>
      <button class="btn btn-primary w-full">Sign in</button>
    </form>
  </div>
</body>
</html>
