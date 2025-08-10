<?php
$pdo = db();
$cq = strtolower(trim($_GET['cq'] ?? ''));
$p = max(1,(int)($_GET['p'] ?? 1)); $per=12; $off=($p-1)*$per;
$where=''; $args=[];
if ($cq!==''){ $where="WHERE (LOWER(name) LIKE ? OR LOWER(domain) LIKE ? OR LOWER(site_url) LIKE ?)"; $args=['%'.$cq.'%','%'.$cq.'%','%'.$cq.'%']; }
$c=$pdo->prepare("SELECT COUNT(*) c FROM companies $where"); $c->execute($args); $total=(int)$c->fetch()['c'];
$st=$pdo->prepare("SELECT * FROM companies $where ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?"); $st->execute([...$args,$per,$off]); $rows=$st->fetchAll();
$pages = max(1,(int)ceil($total/$per));
?>
<section class="space-y-6">
  <div class="rounded-2xl border border-slate-200/60 dark:border-slate-800/60 bg-white/80 dark:bg-slate-950/50 p-6 glass">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
      <h2 class="text-lg font-semibold">Companies</h2>
      <form class="flex gap-2" method="GET">
        <input type="hidden" name="page" value="companies">
        <input name="cq" value="<?= e($_GET['cq'] ?? '') ?>" placeholder="Search name/domain/site"
               class="border rounded-xl px-3 py-2 bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700">
        <button class="px-3 py-2 rounded-xl border hover:bg-slate-100 dark:hover:bg-slate-800">Search</button>
      </form>
    </div>

    <form method="POST" action="/actions.php" class="grid md:grid-cols-3 gap-3 mt-5">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="_back" value="/index.php?page=companies">
      <input name="name" placeholder="Company name" class="border px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700" required>
      <input name="domain" placeholder="Domain (example.com)" class="border px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700">
      <input name="site_url" placeholder="Site URL (https://...)" class="border px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700">
      <select name="status" class="border px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700">
        <?php foreach (['prospect','active','paused','closed'] as $s): ?><option value="<?= $s ?>"><?= $s ?></option><?php endforeach; ?>
      </select>
      <input name="notes" placeholder="Notes" class="md:col-span-2 border px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700">
      <button name="action" value="company_add" class="md:col-span-3 px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700">Add Company</button>
    </form>
  </div>

  <div class="rounded-2xl border border-slate-200/60 dark:border-slate-800/60 bg-white/80 dark:bg-slate-950/50 p-0 overflow-hidden">
    <table class="w-full text-sm">
      <thead>
        <tr class="text-left text-slate-500 border-b border-slate-200/70 dark:border-slate-800">
          <th class="py-3 px-4">Name</th><th>Status</th><th>Domain</th><th>Site</th><th>Notes</th><th>Created</th><th class="text-right pr-4">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-200/70 dark:divide-slate-800">
        <?php foreach($rows as $c):
          $palette = [
            'prospect'=>'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-200',
            'active'=>'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200',
            'paused'=>'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-200',
            'closed'=>'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-200',
          ][$c['status']] ?? 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200';
        ?>
        <tr class="align-top">
          <td class="py-3 px-4 font-medium"><?= e($c['name']) ?></td>
          <td class="px-4"><span class="chip border border-transparent <?= $palette ?>"><?= e($c['status']) ?></span></td>
          <td class="px-4"><?= e($c['domain'] ?? '') ?></td>
          <td class="px-4"><?php if($c['site_url']): ?><a class="text-indigo-600" href="<?= e($c['site_url']) ?>" target="_blank">Open</a><?php endif; ?></td>
          <td class="px-4 max-w-[260px] truncate" title="<?= e($c['notes'] ?? '') ?>"><?= e($c['notes'] ?? '') ?></td>
          <td class="px-4"><?= e($c['created_at']) ?></td>
          <td class="px-4 text-right space-y-1">
            <form method="POST" action="/actions.php" class="inline-flex gap-1">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="_back" value="/index.php?page=companies&p=<?= $p ?>&cq=<?= urlencode($cq) ?>">
              <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
              <select name="status" class="border px-2 py-1 rounded-lg bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700">
                <?php foreach (['prospect','active','paused','closed'] as $s): ?><option value="<?= $s ?>" <?= $c['status']===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?>
              </select>
              <button name="action" value="company_status" class="px-2 py-1 border rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800">Save</button>
            </form>
            <form method="POST" action="/actions.php" class="block">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="_back" value="/index.php?page=companies&p=<?= $p ?>&cq=<?= urlencode($cq) ?>">
              <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
              <div class="flex flex-col md:flex-row gap-1 mt-1">
                <input name="domain" value="<?= e($c['domain'] ?? '') ?>" placeholder="domain" class="border px-2 py-1 rounded-lg bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700">
                <input name="site_url" value="<?= e($c['site_url'] ?? '') ?>" placeholder="site url" class="border px-2 py-1 rounded-lg bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700">
                <input name="notes" value="<?= e($c['notes'] ?? '') ?>" placeholder="notes" class="border px-2 py-1 rounded-lg bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700">
              </div>
              <div class="mt-1 space-x-1">
                <button name="action" value="company_save" class="px-2 py-1 border rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800">Update</button>
                <button name="action" value="company_delete" class="px-2 py-1 border rounded-lg text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30" onclick="return confirm('Delete company #<?= (int)$c['id'] ?>?')">Delete</button>
              </div>
            </form>
          </td>
        </tr>
        <?php endforeach; if(!$rows): ?>
          <tr><td colspan="7" class="py-8 text-center text-slate-500">No companies yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="flex items-center justify-between text-sm">
    <div class="text-slate-500">Total: <?= $total ?></div>
    <div class="flex gap-1">
      <a class="px-3 py-1.5 border rounded-lg <?= $p<=1?'opacity-50 pointer-events-none':'' ?>" href="?page=companies&p=<?= max(1,$p-1) ?>&cq=<?= urlencode($cq) ?>">Prev</a>
      <span class="px-3 py-1.5"><?= $p ?> / <?= $pages ?></span>
      <a class="px-3 py-1.5 border rounded-lg <?= $p>=$pages?'opacity-50 pointer-events-none':'' ?>" href="?page=companies&p=<?= min($pages,$p+1) ?>&cq=<?= urlencode($cq) ?>">Next</a>
    </div>
  </div>
</section>
