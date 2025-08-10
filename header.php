<?php
require_once __DIR__ . '/config.php';
require_login();

$nav = [
  'dashboard'  => ['Dashboard','M10 3h4a2 2 0 012 2v3M7 21h10a2 2 0 002-2V8l-6-5H9a2 2 0 00-2 2v3'],
  'leads'      => ['Leads','M16 12a4 4 0 10-8 0 4 4 0 008 0z M12 14v7'],
  'uploads'    => ['Files','M3 7l9-4 9 4-9 4-9-4z M21 10l-9 4-9-4 M3 17l9 4 9-4'],
  'companies'  => ['Companies','M3 10h18M7 21V3h10v18'],
  'finance'    => ['Financials','M3 3v18h18 M7 15l3-3 4 4 5-5'],
  'activity'   => ['Activity','M12 8v4l3 3 M12 22a10 10 0 100-20 10 10 0 000 20z'],
  'settings'   => ['Settings','M10.325 4.317a1 1 0 011.35-.936l7.794 2.598a1 1 0 01.651.95v7.142a1 1 0 01-.651.95l-7.794 2.598a1 1 0 01-1.35-.936V4.317z'],
  'profile'    => ['Profile','M12 14c3.866 0 7 1.79 7 4v2H5v-2c0-2.21 3.134-4 7-4zm0-2a4 4 0 100-8 4 4 0 000 8z'],
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
<style>
  .glass { backdrop-filter: blur(8px); background: linear-gradient(180deg, rgba(255,255,255,.75), rgba(255,255,255,.55)); }
  .dark .glass { background: linear-gradient(180deg, rgba(2,6,23,.75), rgba(2,6,23,.55)); }
  .chip { @apply inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs border; }
</style>
</head>
<body class="bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-950 dark:to-slate-900 text-slate-900 dark:text-slate-100 min-h-screen">

<div class="flex min-h-screen">
  <!-- Sidebar -->
  <aside class="hidden md:flex w-72 flex-col border-r border-slate-200/60 dark:border-slate-800/60 glass">
    <div class="p-5 border-b border-slate-200/60 dark:border-slate-800/60">
      <div class="flex items-center gap-3">
        <div class="h-10 w-10 rounded-xl bg-indigo-600"></div>
        <div>
          <div class="text-lg font-bold tracking-tight">Movana Admin</div>
          <div class="text-xs text-slate-500"><?= e($_SESSION['admin_user']) ?></div>
        </div>
      </div>
    </div>
    <nav class="flex-1 p-3 space-y-1">
      <?php foreach($nav as $key => [$label,$path]): $active = $page===$key; ?>
      <a href="?page=<?= $key ?>"
         class="group flex items-center gap-3 px-3 py-2 rounded-lg transition
                <?= $active ? 'bg-indigo-600 text-white shadow-sm' : 'hover:bg-slate-200/60 dark:hover:bg-slate-800/60' ?>">
        <svg viewBox="0 0 24 24" class="h-5 w-5 <?= $active?'text-white':'text-slate-500 group-hover:text-slate-700 dark:group-hover:text-slate-200' ?>" fill="none" stroke="currentColor" stroke-width="2"><path d="<?= $path ?>"/></svg>
        <span class="font-medium"><?= $label ?></span>
      </a>
      <?php endforeach; ?>
    </nav>
    <form class="p-3 border-t border-slate-200/60 dark:border-slate-800/60" method="POST" action="/logout.php">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <button class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-lg border
                     hover:bg-red-600/10 text-red-600 dark:text-red-400">
        Logout
      </button>
    </form>
  </aside>

  <!-- Main -->
  <div class="flex-1 flex flex-col">
    <!-- Topbar -->
    <header class="sticky top-0 z-20 glass border-b border-slate-200/60 dark:border-slate-800/60">
      <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
        <div class="flex items-center gap-3 md:hidden">
          <select class="border rounded-lg px-3 py-2 bg-white dark:bg-slate-900"
                  onchange="location.href='?page='+this.value">
            <?php foreach($nav as $k=>$meta): ?>
              <option value="<?= $k ?>" <?= $page===$k?'selected':'' ?>><?= $meta[0] ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="font-semibold tracking-tight text-slate-800 dark:text-slate-100 capitalize">
          <?= e($nav[$page][0] ?? 'Admin') ?>
        </div>
        <div class="flex items-center gap-2">
          <button onclick="toggleTheme()"
                  class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-sm">
            Theme
          </button>
        </div>
      </div>
    </header>

    <main class="max-w-7xl mx-auto w-full p-4 md:p-8 space-y-8">
      <?php if (!empty($_SESSION['flash'])): $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
        <div class="space-y-2">
          <?php foreach ($flash as $f): ?>
            <div class="px-4 py-3 rounded-xl border <?= $f['type']==='error'
              ?'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/25 dark:text-red-100 dark:border-red-800'
              :'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/25 dark:text-emerald-100 dark:border-emerald-800' ?>">
              <?= e($f['msg']) ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
