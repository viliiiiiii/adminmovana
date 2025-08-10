<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../partials/ui.php';

$pdo = db();
$title = 'Leads';
$breadcrumbs = [['label'=>'Home','href'=>'/index.php'], ['label'=>'Leads']];

$q = strtolower(trim($_GET['q'] ?? ''));
$p = max(1,(int)($_GET['p'] ?? 1)); $per=20; $off=($p-1)*$per;
$where=''; $args=[];
if ($q!==''){ $where="WHERE (LOWER(name) LIKE ? OR LOWER(email) LIKE ? OR LOWER(message) LIKE ?)"; $args=["%$q%","%$q%","%$q%"]; }

$c=$pdo->prepare("SELECT COUNT(*) c FROM leads $where"); $c->execute($args); $total=(int)$c->fetch()['c'];
$st=$pdo->prepare("SELECT * FROM leads $where ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?");
$st->execute([...$args,$per,$off]); $rows=$st->fetchAll();
$pages = max(1,(int)ceil($total/$per));

$toolbar = function() use ($q){ ?>
  <input class="input" placeholder="Search leads…" value="<?= e($q) ?>"
         onkeydown="if(event.key==='Enter'){location.href='?page=leads&q='+encodeURIComponent(this.value)}">
<?php };

ob_start(); ?>
<section class="rounded-2xl border border-slate-200/60 dark:border-slate-800/60 bg-white/80 dark:bg-slate-950/50 p-6 space-y-5">
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
    <h2 class="text-lg font-semibold">Leads</h2>
    <form class="flex gap-2" method="GET">
      <input type="hidden" name="page" value="leads">
      <input name="q" value="<?= e($q) ?>" placeholder="Search name/email/message" class="input">
      <button class="btn btn-ghost">Search</button>
      <a href="?page=leads&export=csv<?= $q!=='' ? '&q='.urlencode($q) : '' ?>" class="btn btn-ghost">Export CSV</a>
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
    <input name="name" placeholder="Name" class="input" required>
    <input name="email" type="email" placeholder="Email" class="input" required>
    <input name="message" placeholder="Message" class="input md:col-span-3">
    <button name="action" value="lead_add" class="md:col-span-3 btn btn-primary">Add Lead</button>
  </form>

  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="sticky top-0 z-10">
        <tr class="text-left text-slate-500 border-b border-slate-200/70 dark:border-slate-800">
          <th class="py-2">ID</th><th>Name</th><th>Email</th><th>Status</th><th>Created</th><th class="text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-200/70 dark:divide-slate-800">
        <?php foreach ($rows as $l): ?>
        <tr>
          <td class="py-2"><?= (int)$l['id'] ?></td>
          <td class="font-medium"><?= e($l['name']) ?></td>
          <td class="text-slate-600 dark:text-slate-300"><?= e($l['email']) ?></td>
          <td><?= e($l['status']) ?></td>
          <td><?= e($l['created_at']) ?></td>
          <td class="text-right space-x-1">
            <form method="POST" action="/actions.php" class="inline">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="_back" value="/index.php?page=leads&p=<?= $p ?>&q=<?= urlencode($q) ?>">
              <input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
              <select name="status" class="input w-auto">
                <?php foreach (['new','contacted','won','lost'] as $s): ?><option value="<?= $s ?>" <?= $l['status']===$s?'selected':''?>><?= $s ?></option><?php endforeach; ?>
              </select>
              <button name="action" value="lead_status" class="btn btn-ghost">Save</button>
            </form>
            <form method="POST" action="/actions.php" class="inline" onsubmit="return confirm('Delete lead #<?= (int)$l['id'] ?>?')">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="_back" value="/index.php?page=leads&p=<?= $p ?>&q=<?= urlencode($q) ?>">
              <input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
              <button name="action" value="lead_delete" class="btn btn-ghost text-rose-600">Delete</button>
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
<?php
$content = ob_get_clean();
require __DIR__ . '/../partials/layout.php';
