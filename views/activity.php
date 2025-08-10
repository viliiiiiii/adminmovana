<?php
$pdo = db();
$p = max(1,(int)($_GET['p'] ?? 1)); $per=30; $off=($p-1)*$per;
$logTotal = (int) ($pdo->query('SELECT COUNT(*) c FROM activity')->fetch()['c'] ?? 0);
$st=$pdo->prepare("SELECT * FROM activity ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?"); $st->execute([$per,$off]); $log=$st->fetchAll();
$pages = max(1,(int)ceil($logTotal/$per));
?>
<section class="bg-white dark:bg-slate-950 p-6 rounded-xl border border-slate-200 dark:border-slate-800">
  <h2 class="text-lg font-semibold mb-4">Activity</h2>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="sticky top-0 bg-white dark:bg-slate-950 z-10">
        <tr class="text-left text-slate-500 border-b"><th class="py-2">Time</th><th>Action</th><th>Meta</th></tr>
      </thead>
      <tbody>
        <?php foreach ($log as $a): $meta=json_decode($a['meta'],true)?:[]; ?>
          <tr class="border-b">
            <td class="py-2 whitespace-nowrap"><?= e($a['created_at']) ?></td>
            <td class="whitespace-nowrap"><?= e($a['action']) ?></td>
            <td><code class="text-slate-500"><?= e(json_encode($meta)) ?></code></td>
          </tr>
        <?php endforeach; if (!$log): ?>
          <tr><td colspan="3" class="py-4 text-center text-slate-500">No activity.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="mt-3 flex items-center justify-between text-sm">
    <div class="text-slate-500">Total: <?= $logTotal ?></div>
    <div class="flex gap-1">
      <a class="px-3 py-1.5 border rounded <?= $p<=1?'opacity-50 pointer-events-none':'' ?>" href="?page=activity&p=<?= max(1,$p-1) ?>">Prev</a>
      <span class="px-3 py-1.5"><?= $p ?> / <?= $pages ?></span>
      <a class="px-3 py-1.5 border rounded <?= $p>=$pages?'opacity-50 pointer-events-none':'' ?>" href="?page=activity&p=<?= min($pages,$p+1) ?>">Next</a>
    </div>
  </div>
</section>
