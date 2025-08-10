<?php
$pdo = db();
$p = max(1,(int)($_GET['p'] ?? 1)); $per=30; $off=($p-1)*$per;
$logTotal = (int) ($pdo->query('SELECT COUNT(*) c FROM activity')->fetch()['c'] ?? 0);
$st=$pdo->prepare("SELECT * FROM activity ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?"); $st->execute([$per,$off]); $log=$st->fetchAll();
$pages = max(1,(int)ceil($logTotal/$per));
?>
<section class="rounded-2xl border border-slate-200/60 dark:border-slate-800/60 bg-white/80 dark:bg-slate-950/50 p-6 glass">
  <h2 class="text-lg font-semibold mb-6">Activity</h2>
  <ol class="relative border-s-l-2 border-slate-200 dark:border-slate-800">
    <?php foreach ($log as $a): ?>
      <li class="ms-6 mb-6">
        <span class="absolute -start-3 mt-1 h-5 w-5 rounded-full bg-indigo-600"></span>
        <div class="rounded-xl border border-slate-200/60 dark:border-slate-800/60 p-4 bg-white/70 dark:bg-slate-900/60">
          <div class="text-sm text-slate-500"><?= e($a['created_at']) ?></div>
          <div class="font-medium mt-1"><?= e($a['action']) ?></div>
          <pre class="mt-2 text-xs text-slate-600 dark:text-slate-300 whitespace-pre-wrap"><?= e($a['meta']) ?></pre>
        </div>
      </li>
    <?php endforeach; if (!$log): ?>
      <li class="ms-6 text-slate-500">No activity.</li>
    <?php endif; ?>
  </ol>

  <div class="mt-3 flex items-center justify-between text-sm">
    <div class="text-slate-500">Total: <?= $logTotal ?></div>
    <div class="flex gap-1">
      <a class="px-3 py-1.5 border rounded-lg <?= $p<=1?'opacity-50 pointer-events-none':'' ?>" href="?page=activity&p=<?= max(1,$p-1) ?>">Prev</a>
      <span class="px-3 py-1.5"><?= $p ?> / <?= $pages ?></span>
      <a class="px-3 py-1.5 border rounded-lg <?= $p>=$pages?'opacity-50 pointer-events-none':'' ?>" href="?page=activity&p=<?= min($pages,$p+1) ?>">Next</a>
    </div>
  </div>
</section>
