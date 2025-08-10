<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../partials/ui.php';

$pdo = db();
$title = 'Insights';
$breadcrumbs = [['label'=>'Home','href'=>'/index.php'], ['label'=>'Insights']];

ob_start();
$spots = (int) setting_get('spots_left', 3);
$leads = (int) ($pdo->query('SELECT COUNT(*) c FROM leads')->fetch()['c'] ?? 0);
$files = (int) ($pdo->query('SELECT COUNT(*) c FROM uploads')->fetch()['c'] ?? 0);
$cos   = (int) ($pdo->query('SELECT COUNT(*) c FROM companies')->fetch()['c'] ?? 0);
?>
<section class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
  <?php stat_card('Spots Left', (string)$spots, 'from-violet-500 to-fuchsia-700'); ?>
  <?php stat_card('Leads', number_format($leads), 'from-emerald-500 to-emerald-700'); ?>
  <?php stat_card('Files', number_format($files), 'from-sky-500 to-sky-700'); ?>
  <?php stat_card('Companies', number_format($cos), 'from-amber-500 to-amber-700'); ?>
</section>

<section class="grid lg:grid-cols-3 gap-6">
  <div class="lg:col-span-2 rounded-2xl border p-6 bg-white/80 dark:bg-slate-950/50 border-slate-200/60 dark:border-slate-800/60">
    <h2 class="text-lg font-semibold mb-4">Recent Leads</h2>
    <?php
    $rows = $pdo->query("SELECT id,name,email,status,created_at FROM leads ORDER BY created_at DESC, id DESC LIMIT 8")->fetchAll();
    table_shell(['ID','Name','Email','Status','Created'], function() use ($rows) {
      foreach ($rows as $r): ?>
        <tr>
          <td class="py-3 px-4"><?= (int)$r['id'] ?></td>
          <td class="px-4 font-medium"><?= e($r['name']) ?></td>
          <td class="px-4 text-slate-600 dark:text-slate-300"><?= e($r['email']) ?></td>
          <td class="px-4"><?= e($r['status']) ?></td>
          <td class="px-4"><?= e($r['created_at']) ?></td>
        </tr>
      <?php endforeach; if (!$rows): ?>
        <tr><td colspan="5" class="py-6 text-center text-slate-500">No data</td></tr>
      <?php endif;
    }); ?>
    <div class="mt-3 text-right">
      <a href="/index.php?page=leads" class="text-indigo-600 hover:underline">Open Leads</a>
    </div>
  </div>

  <div class="rounded-2xl border p-6 bg-white/80 dark:bg-slate-950/50 border-slate-200/60 dark:border-slate-800/60">
    <h2 class="text-lg font-semibold mb-4">Recent Activity</h2>
    <ul class="space-y-3">
      <?php
      $acts = $pdo->query("SELECT action,created_at FROM activity ORDER BY created_at DESC, id DESC LIMIT 8")->fetchAll();
      foreach ($acts as $a): ?>
        <li class="flex items-start gap-3">
          <span class="mt-1 h-2.5 w-2.5 rounded-full bg-indigo-600"></span>
          <div>
            <div class="text-sm font-medium"><?= e($a['action']) ?></div>
            <div class="text-xs text-slate-500"><?= e($a['created_at']) ?></div>
          </div>
        </li>
      <?php endforeach; if (!$acts): ?>
        <li class="text-slate-500 text-sm">No activity yet.</li>
      <?php endif; ?>
    </ul>
    <div class="mt-3 text-right">
      <a href="/index.php?page=activity" class="text-indigo-600 hover:underline">Open Activity</a>
    </div>
  </div>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../partials/layout.php';
