<?php
require __DIR__ . '/config.php';
require_login();
$nav = [
  'dashboard'  => 'Dashboard',
  'leads'      => 'Leads',
  'uploads'    => 'Files',
  'companies'  => 'Companies',
  'finance'    => 'Financials',
  'activity'   => 'Activity',
  'settings'   => 'Settings',
  'profile'    => 'Profile',
];
$page = $_GET['page'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Movana Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
(function(){
  const s=localStorage.getItem('theme');
  const d=window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches;
  if(s==='dark'||(!s&&d))document.documentElement.classList.add('dark');
})();
function toggleTheme(){const e=document.documentElement;const d=e.classList.toggle('dark');localStorage.setItem('theme',d?'dark':'light');}
</script>
</head>
<body class="bg-gray-50 text-slate-900 dark:bg-slate-900 dark:text-slate-100">
<div class="min-h-screen flex">
  <aside class="hidden md:block w-64 border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950">
    <div class="p-4 border-b border-slate-200 dark:border-slate-800">
      <div class="text-xl font-bold">Movana Admin</div>
      <div class="text-xs text-slate-500 mt-1"><?= e($_SESSION['admin_user']) ?></div>
    </div>
    <nav class="p-3 space-y-1 text-sm">
      <?php foreach($nav as $k=>$label): $active = $page===$k; ?>
        <a href="?page=<?= $k ?>" class="block px-3 py-2 rounded-md <?= $active?'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-200':'hover:bg-slate-50 dark:hover:bg-slate-800' ?>"><?= $label ?></a>
      <?php endforeach; ?>
      <form class="pt-2 mt-2 border-t border-slate-200 dark:border-slate-800" method="POST" action="/logout.php">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <button class="w-full text-left px-3 py-2 rounded-md hover:bg-red-50 text-red-700 dark:hover:bg-red-900/30 dark:text-red-300">Logout</button>
      </form>
    </nav>
  </aside>

  <div class="flex-1 flex flex-col">
    <header class="sticky top-0 z-10 bg-white/80 backdrop-blur border-b border-slate-200 dark:bg-slate-950/70 dark:border-slate-800">
      <div class="mx-auto max-w-7xl px-4 h-14 flex items-center justify-between">
        <div class="md:hidden">
          <select class="border rounded px-2 py-1 bg-white dark:bg-slate-900" onchange="location.href='?page='+this.value">
            <?php foreach($nav as $k=>$label): ?><option value="<?= $k ?>" <?= $page===$k?'selected':'' ?>><?= $label ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="font-semibold capitalize"><?= e($nav[$page] ?? 'Admin') ?></div>
        <button onclick="toggleTheme()" class="px-3 py-1.5 rounded border border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800 text-sm">Toggle Theme</button>
      </div>
    </header>

    <main class="mx-auto max-w-7xl p-4 md:p-6 space-y-6">
      <?php if (!empty($_SESSION['flash'])): $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
        <div class="space-y-2">
          <?php foreach ($flash as $f): ?>
            <div class="px-4 py-3 rounded border <?= $f['type']==='error'?'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-200 dark:border-red-800':'bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:text-green-200 dark:border-green-800' ?>"><?= e($f['msg']) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
