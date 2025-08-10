<?php
$pdo = db();
$q = strtolower(trim($_GET['q'] ?? ''));
$p = max(1,(int)($_GET['p'] ?? 1)); $per=20; $off=($p-1)*$per;
$where=''; $args=[];
if ($q!==''){ $where="WHERE (LOWER(name) LIKE ? OR LOWER(email) LIKE ? OR LOWER(message) LIKE ?)"; $args=['%'.$q.'%','%'.$q.'%','%'.$q.'%']; }
$c=$pdo->prepare("SELECT COUNT(*) c FROM leads $where"); $c->execute($args); $total=(int)$c->fetch()['c'];
$st=$pdo->prepare("SELECT * FROM leads $where ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?"); $st->execute([...$args,$per,$off]); $rows=$st->fetchAll();
$pages = max(1,(int)ceil($total/$per));
?>
<section class="rounded-2xl border border-slate-200/60 dark:border-slate-800/60 bg-white/80 dark:bg-slate-950/50 p-6 glass space-y-5">
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
    <h2 class="text-lg font-semibold">Leads</h2>
    <form class="flex gap-2" method="GET">
      <input type="hidden" name="page" value="leads">
      <input name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Search name/email/message" class="border rounded-xl px-3 py-2 bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700">
      <button class="px-3 py-2 rounded-xl border hover:bg-slate-100 dark:hover:bg-slate-800">Search</button>
      <a href="?page=leads&export=csv<?= $q!=='' ? '&q='.urlencode($q) : '' ?>" class="px-3 py-2 rounded-xl border hover:bg-slate-100 dark:hover:bg-slate-800">Export CSV</a>
    </form>
  </div>

  <?php if (isset($_GET['export']) && $_GET['export']==='csv') {
    $stmt = $pdo->prepare("SELECT id,name,email,message,status,created_at FROM leads $where ORDER BY created_at DESC, id DESC");
    $stmt->execute($args);
    header('Content-Type: text/csv'); header('Content-Disposition: attachment; filename="leads.csv"');
    $out=fopen('php://output','w'); fputcsv($out,['id','name','email','message','status','created_at']);
    while($r=$stmt->fetch(PDO::FETCH_ASSOC)) fputcsv($out,$r); fclose($out); exit;
  } ?>

  <form method="POST" action="/actions.php" class="grid md:grid-cols-3 gap-3">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="_back" value="/index.php?page=leads">
    <input name="name" placeholder="Name" class="border px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700" required>
    <input name="email" type="email" placeholder="Email" class="border px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700" required>
    <input name="message" placeholder="Message" class="border px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 md:col-span-3">
    <button name="action" value="lead_add" class="md:col-span-3 px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700">Add Lead</button>
  </form>

  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="sticky top-0 z-10">
        <tr class="text-left text-slate-500 border-b border-slate-200/70 dark:border-slate-800">
          <th class="py-2">ID</th><th>Name</th><th>Email</th><th>Status</th><th>Created</th><th class="text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-200/70 dark:divide-slate-800">
        <?php foreach ($rows as $l):
          $badge = [
            'new'       => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-200 border-sky-200/60 dark:border-sky-800',
            'contacted' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-200 border-amber-200/60 dark:border-amber-800',
            'won'       => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200 border-emerald-200/60 dark:border-emerald-800',
            'lost'      => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-200 border-rose-200/60 dark:border-rose-800',
          ][$l['status']] ?? 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200 border-slate-200/60 dark:border-slate-700';
        ?>
        <tr>
          <td class="py-2"><?= (int)$l['id'] ?></td>
          <td class="font-medium"><?= e($l['name']) ?></td>
          <td class="text-slate-600 dark:text-slate-300"><?= e($l['email']) ?></td>
          <td><span class="chip border <?= $badge ?>"><?= e($l['status']) ?></span></td>
          <td><?= e($l['created_at']) ?></td>
          <td class="text-right space-x-1">
            <form method="POST" action="/actions.php" class="inline">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="_back" value="/index.php?page=leads&p=<?= $p ?>&q=<?= urlencode($q) ?>">
              <input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
              <select name="status" class="border px-2 py-1 rounded-lg bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700">
                <?php foreach (['new','contacted','won','lost'] as $s): ?><option value="<?= $s ?>" <?= $l['status']===$s?'selected':''?>><?= $s ?></option><?php endforeach; ?>
              </select>
              <button name="action" value="lead_status" class="px-2 py-1 border rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800">Save</button>
            </form>
            <form method="POST" action="/actions.php" class="inline" onsubmit="return confirm('Delete lead #<?= (int)$l['id'] ?>?')">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="_back" value="/index.php?page=leads&p=<?= $p ?>&q=<?= urlencode($q) ?>">
              <input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
              <button name="action" value="lead_delete" class="px-2 py-1 border rounded-lg text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; if (!$rows): ?>
          <tr><td colspan="6" class="py-6 text-center text-slate-500">No leads.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="flex items-center justify-between text-sm pt-1">
    <div class="text-slate-500">Total: <?= $total ?></div>
    <div class="flex gap-1">
      <a class="px-3 py-1.5 border rounded-lg <?= $p<=1?'opacity-50 pointer-events-none':'' ?>" href="?page=leads&p=<?= max(1,$p-1) ?>&q=<?= urlencode($q) ?>">Prev</a>
      <span class="px-3 py-1.5"><?= $p ?> / <?= $pages ?></span>
      <a class="px-3 py-1.5 border rounded-lg <?= $p>=$pages?'opacity-50 pointer-events-none':'' ?>" href="?page=leads&p=<?= min($pages,$p+1) ?>&q=<?= urlencode($q) ?>">Next</a>
    </div>
  </div>
</section>
