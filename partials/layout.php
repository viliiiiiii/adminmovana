<?php
require_once __DIR__ . '/../config.php';
require_login();

$pageKey = $_GET['page'] ?? 'insights';
$nav = [
  ['key'=>'insights','label'=>'Insights','icon'=>'M3 3h18v6H3z M3 13h8v8H3z M13 13h8v8h-8z'],
  ['key'=>'leads','label'=>'Leads','icon'=>'M3 5h18v14H3z M3 9h18 M9 5v14 M15 5v14'],
  ['key'=>'companies','label'=>'Companies','icon'=>'M3 10h18M7 21V3h10v18'],
  ['key'=>'uploads','label'=>'Files','icon'=>'M3 7l9-4 9 4-9 4-9-4z M21 10l-9 4-9-4 M3 17l9 4 9-4'],
  ['key'=>'finance','label'=>'Financials','icon'=>'M3 3v18h18 M7 15l3-3 4 4 5-5'],
  ['key'=>'activity','label'=>'Activity','icon'=>'M12 8v4l3 3 M12 22a10 10 0 100-20 10 10 0 000 20z'],
  ['key'=>'settings','label'=>'Settings','icon'=>'M10.325 4.317l7.794 2.598v7.142l-7.794 2.598z'],
];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'Admin') ?> · Movana</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
(function(){
  const s=localStorage.getItem('theme');
  const d=window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches;
  if(s==='dark'||(!s&&d)) document.documentElement.classList.add('dark');
})();
function toggleTheme(){
  const d=document.documentElement.classList.toggle('dark');
  localStorage.setItem('theme', d?'dark':'light');
}
</script>
<style>
  .glass { backdrop-filter: blur(10px); background: linear-gradient(180deg, rgba(255,255,255,.7), rgba(255,255,255,.55)); }
  .dark .glass { background: linear-gradient(180deg, rgba(2,6,23,.7), rgba(2,6,23,.55)); }
  .btn { @apply inline-flex items-center justify-center rounded-lg border px-3 py-2 text-sm transition; }
  .btn-ghost { @apply border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800; }
  .btn-primary { @apply border-transparent bg-indigo-600 text-white hover:bg-indigo-700; }
  .input { @apply border rounded-lg px-3 py-2 bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700; }
  .tab { @apply inline-flex items-center px-3 py-2 rounded-lg border text-sm; }
  .tab-active { @apply border-slate-900 dark:border-slate-100; }
  .tab-muted { @apply border-transparent hover:bg-slate-100 dark:hover:bg-slate-800; }
  .crumb { @apply text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200; }
</style>
</head>
<body class="min-h-screen bg-slate-100 dark:bg-slate-950 text-slate-900 dark:text-slate-100">

<div class="flex min-h-screen">
  <!-- Sidebar (desktop) -->
  <aside class="hidden md:flex w-72 flex-col border-r border-slate-200/70 dark:border-slate-800/70 glass">
    <div class="p-5 border-b border-slate-200/70 dark:border-slate-800/70">
      <div class="flex items-center gap-3">
        <div class="h-9 w-9 rounded-xl bg-indigo-600"></div>
        <div>
          <div class="text-base font-bold tracking-tight">Movana Admin</div>
          <div class="text-xs text-slate-500"><?= e($_SESSION['admin_user']) ?></div>
        </div>
      </div>
    </div>
    <nav class="flex-1 p-3 space-y-1">
      <?php foreach ($nav as $item): $active = $pageKey === $item['key']; ?>
      <a href="/index.php?page=<?= $item['key'] ?>"
         class="group flex items-center gap-3 px-3 py-2 rounded-lg <?= $active?'bg-indigo-600 text-white shadow-sm':'hover:bg-slate-200/60 dark:hover:bg-slate-800/60' ?>">
        <svg viewBox="0 0 24 24" class="h-5 w-5 <?= $active?'text-white':'text-slate-500 group-hover:text-slate-700 dark:group-hover:text-slate-200' ?>" fill="none" stroke="currentColor" stroke-width="2">
          <path d="<?= $item['icon'] ?>"/>
        </svg>
        <span class="font-medium"><?= e($item['label']) ?></span>
      </a>
      <?php endforeach; ?>
    </nav>
    <form class="p-3 border-t border-slate-200/70 dark:border-slate-800/70" method="POST" action="/logout.php">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <button class="w-full btn btn-ghost text-rose-600 dark:text-rose-400">Logout</button>
    </form>
  </aside>

  <!-- Drawer (mobile) -->
  <div id="drawer" class="hidden fixed inset-0 z-50 md:hidden">
    <div class="absolute inset-0 bg-black/50" onclick="drawerClose()"></div>
    <aside class="absolute left-0 top-0 h-full w-80 p-4 border-r border-slate-800/40 bg-slate-950 text-slate-100">
      <div class="flex items-center justify-between mb-6">
        <div class="text-lg font-bold">Movana</div>
        <button class="btn btn-ghost" onclick="drawerClose()">Close</button>
      </div>
      <nav class="space-y-1">
        <?php foreach ($nav as $item): $active = $pageKey === $item['key']; ?>
          <a href="/index.php?page=<?= $item['key'] ?>" class="flex items-center gap-3 px-3 py-2 rounded-lg <?= $active?'bg-indigo-600 text-white':'hover:bg-slate-800' ?>" onclick="drawerClose()">
            <svg viewBox="0 0 24 24" class="h-5 w-5"><path d="<?= $item['icon'] ?>" fill="none" stroke="currentColor" stroke-width="2"/></svg>
            <?= e($item['label']) ?>
          </a>
        <?php endforeach; ?>
      </nav>
    </aside>
  </div>

  <!-- Main -->
  <div class="flex-1 flex flex-col">
    <!-- Top bar -->
    <header class="sticky top-0 z-20 glass border-b border-slate-200/70 dark:border-slate-800/70">
      <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <button class="md:hidden btn btn-ghost" onclick="drawerOpen()">Menu</button>
          <div class="font-semibold tracking-tight capitalize"><?= e($title ?? 'Admin') ?></div>
        </div>
        <div class="flex items-center gap-2">
          <?php if (isset($toolbar) && is_callable($toolbar)) { $toolbar(); } ?>
          <button onclick="toggleTheme()" class="btn btn-ghost">Theme</button>
        </div>
      </div>
      <?php if (!empty($breadcrumbs)): ?>
      <div class="max-w-7xl mx-auto px-4 pb-3">
        <nav class="flex items-center gap-2">
          <?php foreach ($breadcrumbs as $i=>$c): ?>
            <?php if (!empty($c['href'])): ?>
              <a class="crumb" href="<?= e($c['href']) ?>"><?= e($c['label']) ?></a>
              <?php if ($i < count($breadcrumbs)-1): ?><span class="text-slate-400">/</span><?php endif; ?>
            <?php else: ?>
              <span class="crumb text-slate-700 dark:text-slate-200"><?= e($c['label']) ?></span>
            <?php endif; ?>
          <?php endforeach; ?>
        </nav>
      </div>
      <?php endif; ?>
      <?php if (!empty($primaryTabs)): ?>
      <div class="max-w-7xl mx-auto px-4 pb-3 flex items-center gap-2">
        <?php foreach ($primaryTabs as $t): $on = ($activePrimary ?? '') === $t['key']; ?>
          <a href="<?= e($t['href'] ?? '?page='.$pageKey.'&tab='.$t['key']) ?>" class="tab <?= $on?'tab-active':'tab-muted' ?>"><?= e($t['label']) ?></a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <?php if (!empty($secondaryTabs)): ?>
      <div class="max-w-7xl mx-auto px-4 pb-3 flex items-center gap-2">
        <?php foreach ($secondaryTabs as $t): $on = ($activeSecondary ?? '') === $t['key']; ?>
          <a href="<?= e($t['href'] ?? '?page='.$pageKey.'&sub='.$t['key']) ?>" class="tab text-xs <?= $on?'tab-active':'tab-muted' ?>"><?= e($t['label']) ?></a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </header>

    <!-- Content -->
    <main class="max-w-7xl mx-auto w-full p-4 md:p-8 space-y-8">
      <?php if (!empty($_SESSION['flash'])): $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
        <?php foreach ($flash as $f): ?>
          <div class="px-4 py-3 rounded-xl border <?= $f['type']==='error'
            ?'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-900/25 dark:text-rose-100 dark:border-rose-800'
            :'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/25 dark:text-emerald-100 dark:border-emerald-800' ?>">
            <?= e($f['msg']) ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

      <?= $content ?? '' ?>
    </main>
  </div>
</div>

<script src="/public/js/ui.js"></script>
</body>
</html>
