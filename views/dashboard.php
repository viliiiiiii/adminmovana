<?php
// Database queries
$pdo = db();
$spotsLeft  = (int) setting_get('spots_left', 3);
$totalLeads = (int) ($pdo->query('SELECT COUNT(*) c FROM leads')->fetch()['c'] ?? 0);
$newLeads   = (int) ($pdo->query("SELECT COUNT(*) c FROM leads WHERE status='new'")->fetch()['c'] ?? 0);
$filesCount = (int) ($pdo->query('SELECT COUNT(*) c FROM uploads')->fetch()['c'] ?? 0);
$companyCnt = (int) ($pdo->query('SELECT COUNT(*) c FROM companies')->fetch()['c'] ?? 0);
?>

<!-- Stats Cards Section -->
<section class="grid sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
  <!-- Spots Left Card -->
  <div class="bg-white dark:bg-slate-950 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow">
    <div class="text-sm text-slate-500 dark:text-slate-400 mb-1">Spots Left</div>
    <div class="text-3xl font-bold text-slate-800 dark:text-slate-200"><?= $spotsLeft ?></div>
    <form class="mt-3 flex gap-2" method="POST" action="/actions.php">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="_back" value="/index.php?page=dashboard">
      <button name="action" value="spots_dec" class="px-3 py-1.5 rounded border hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors" aria-label="Decrease spots">
        −
      </button>
      <button name="action" value="spots_inc" class="px-3 py-1.5 rounded border hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors" aria-label="Increase spots">
        +
      </button>
    </form>
  </div>

  <!-- Leads Card -->
  <div class="bg-white dark:bg-slate-950 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow">
    <div class="text-sm text-slate-500 dark:text-slate-400 mb-1">Total Leads</div>
    <div class="text-3xl font-bold text-slate-800 dark:text-slate-200"><?= $totalLeads ?></div>
    <div class="text-sm text-slate-500 dark:text-slate-400 mt-1">
      <span class="inline-flex items-center gap-1">
        <span class="w-2 h-2 rounded-full bg-green-500"></span>
        <?= $newLeads ?> new
      </span>
    </div>
  </div>

  <!-- Files Card -->
  <div class="bg-white dark:bg-slate-950 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow">
    <div class="text-sm text-slate-500 dark:text-slate-400 mb-1">Files Uploaded</div>
    <div class="text-3xl font-bold text-slate-800 dark:text-slate-200"><?= $filesCount ?></div>
  </div>

  <!-- Companies Card -->
  <div class="bg-white dark:bg-slate-950 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow">
    <div class="text-sm text-slate-500 dark:text-slate-400 mb-1">Companies</div>
    <div class="text-3xl font-bold text-slate-800 dark:text-slate-200"><?= $companyCnt ?></div>
  </div>

  <!-- User Card -->
  <div class="bg-white dark:bg-slate-950 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow">
    <div class="text-sm text-slate-500 dark:text-slate-400 mb-1">Logged in as</div>
    <div class="font-semibold text-slate-800 dark:text-slate-200 truncate"><?= e($_SESSION['admin_user']) ?></div>
  </div>
</section>

<!-- Quick Actions Section -->
<section class="bg-white dark:bg-slate-950 p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
  <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-200 mb-4">Quick Actions</h2>
  <div class="flex flex-wrap gap-3">
    <form method="POST" action="/actions.php" class="inline">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="_back" value="/index.php?page=dashboard">
      <button name="action" value="purge_cache" class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700 transition-colors focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
        Purge Cache
      </button>
    </form>
    
    <!-- Additional action buttons can be added here -->
    <a href="/index.php?page=add_lead" class="px-4 py-2 rounded bg-green-600 text-white hover:bg-green-700 transition-colors focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
      Add New Lead
    </a>
  </div>
</section>