<?php
$pdo = db();
$spotsLeft  = (int) setting_get('spots_left', 3);
$totalLeads = (int) ($pdo->query('SELECT COUNT(*) c FROM leads')->fetch()['c'] ?? 0);
$newLeads   = (int) ($pdo->query("SELECT COUNT(*) c FROM leads WHERE status='new'")->fetch()['c'] ?? 0);
$filesCount = (int) ($pdo->query('SELECT COUNT(*) c FROM uploads')->fetch()['c'] ?? 0);
$companyCnt = (int) ($pdo->query('SELECT COUNT(*) c FROM companies')->fetch()['c'] ?? 0);
?>
<section class="grid sm:grid-cols-2 lg:grid-cols-5 gap-4">
  <div class="bg-white dark:bg-slate-950 p-5 rounded-xl border border-slate-200 dark:border-slate-800">
    <div class="text-sm text-slate-500">Spots Left</div>
    <div class="mt-1 text-3xl font-bold"><?= $spotsLeft ?></div>
    <form class="mt-3 flex gap-2" method="POST" action="/actions.php">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="_back" value="/index.php?page=dashboard">
      <button name="action" value="spots_dec" class="px-3 py-1.5 rounded border hover:bg-slate-50 dark:hover:bg-slate-800">−</button>
      <button name="action" value="spots_inc" class="px-3 py-1.5 rounded border hover:bg-slate-50 dark:hover:bg-slate-800">+</button>
    </form>
  </div>
  <div class="bg-white dark:bg-slate-950 p-5 rounded-xl border border-slate-200 dark:border-slate-800"><div class="text-sm text-slate-500">Leads</div><div class="mt-1 text-3xl font-bold"><?= $totalLeads ?></div><div class="text-sm text-slate-500"><?= $newLeads ?> new</div></div>
  <div class="bg-white dark:bg-slate-950 p-5 rounded-xl border border-slate-200 dark:border-slate-800"><div class="text-sm text-slate-500">Files</div><div class="mt-1 text-3xl font-bold"><?= $filesCount ?></div></div>
  <div class="bg-white dark:bg-slate-950 p-5 rounded-xl border border-slate-200 dark:border-slate-800"><div class="text-sm text-slate-500">Companies</div><div class="mt-1 text-3xl font-bold"><?= $companyCnt ?></div></div>
  <div class="bg-white dark:bg-slate-950 p-5 rounded-xl border border-slate-200 dark:border-slate-800"><div class="text-sm text-slate-500">You</div><div class="mt-1 font-semibold"><?= e($_SESSION['admin_user']) ?></div></div>
</section>
<section class="bg-white dark:bg-slate-950 p-6 rounded-xl border border-slate-200 dark:border-slate-800">
  <h2 class="text-lg font-semibold mb-3">Quick Actions</h2>
  <form method="POST" action="/actions.php" class="inline">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="_back" value="/index.php?page=dashboard">
    <button name="action" value="purge_cache" class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700">Purge Cache</button>
  </form>
</section>
