<?php
$pdo = db();
$cq = strtolower(trim($_GET['cq'] ?? ''));
$p = max(1,(int)($_GET['p'] ?? 1)); $per=20; $off=($p-1)*$per;
$where=''; $args=[];
if ($cq!==''){ $where="WHERE (LOWER(name) LIKE ? OR LOWER(domain) LIKE ? OR LOWER(site_url) LIKE ?)"; $args=['%'.$cq.'%','%'.$cq.'%','%'.$cq.'%']; }
$c=$pdo->prepare("SELECT COUNT(*) c FROM companies $where"); $c->execute($args); $total=(int)$c->fetch()['c'];
$st=$pdo->prepare("SELECT * FROM companies $where ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?"); $st->execute([...$args,$per,$off]); $rows=$st->fetchAll();
$pages = max(1,(int)ceil($total/$per));
?>
<section class="bg-white dark:bg-slate-950 p-6 rounded-xl border border-slate-200 dark:border-slate-800 space-y-5">
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
    <h2 class="text-lg font-semibold">Companies</h2>
    <form class="flex gap-2" method="GET">
      <input type="hidden" name="page" value="companies">
      <input name="cq" value="<?= e($_GET['cq'] ?? '') ?>" placeholder="Search name/domain/site" class="border rounded px-3 py-2 bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700">
      <button class="px-3 py-2 rounded border hover:bg-slate-50 dark:hover:bg-slate-800">Search</button>
    </form>
  </div>

  <form method="POST" action="/actions.php" class="grid md:grid-cols-3 gap-3">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="_back" value="/index.php?page=companies">
    <input name="name" placeholder="Company name" class="border px-3 py-2 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700" required>
    <input name="domain" placeholder="Domain (example.com)" class="border px-3 py-2 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700">
    <input name="site_url" placeholder="Site URL (https://...)" class="border px-3 py-2 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700">
    <select name="status" class="border px-3 py-2 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700">
      <?php foreach (['prospect','active','paused','closed'] as $s): ?><option value="<?= $s ?>"><?= $s ?></option><?php endforeach; ?>
    </select>
    <input name="notes" placeholder="Notes" class="md:col-span-2 border px-3 py-2 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700">
    <button name="action" value="company_add" class="md:col-span-3 px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Add Company</button>
  </form>

  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="sticky top-0 bg-white dark:bg-slate-950 z-10">
        <tr class="text-left text-slate-500 border-b">
          <th class="py-2">ID</th><th>Name</th><th>Status</th><th>Domain</th><th>Site</th><th>Notes</th><th>Created</th><th class="text-right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($rows as $c): ?>
        <tr class="border-b align-top">
          <td class="py-2"><?= (int)$c['id'] ?></td>
          <td class="font-medium"><?= e($c['name']) ?></td>
          <td><?= e($c['status']) ?></td>
          <td><?= e($c['domain'] ?? '') ?></td>
          <td><?php if($c['site_url']): ?><a class="text-blue-600" href="<?= e($c['site_url']) ?>" target="_blank">Open</a><?php endif; ?></td>
          <td class="max-w-[240px] truncate" title="<?= e($c['notes'] ?? '') ?>"><?= e($c['notes'] ?? '') ?></td>
          <td><?= e($c['created_at']) ?></td>
          <td class="text-right space-y-1">
            <form method="POST" action="/actions.php" class="inline-flex gap-1">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="_back" value="/index.php?page=companies&p=<?= $p ?>&cq=<?= urlencode($cq) ?>">
              <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
              <select name="status" class="border px-2 py-1 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700">
                <?php foreach (['prospect','active','paused','closed'] as $s): ?><option value="<?= $s ?>" <?= $c['status']===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?>
              </select>
              <button name="action" value="company_status" class="px-2 py-1 border rounded hover:bg-slate-50 dark:hover:bg-slate-800">Save</button>
            </form>
            <form method="POST" action="/actions.php" class="block">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="_back" value="/index.php?page=companies&p=<?= $p ?>&cq=<?= urlencode($cq) ?>">
              <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
              <input name="domain" value="<?= e($c['domain'] ?? '') ?>" placeholder="domain" class="border px-2 py-1 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 mt-1">
              <input name="site_url" value="<?= e($c['site_url'] ?? '') ?>" placeholder="site url" class="border px-2 py-1 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 mt-1">
              <input name="notes" value="<?= e($c['notes'] ?? '') ?>" placeholder="notes" class="border px-2 py-1 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 mt-1">
              <button name="action" value="company_save" class="mt-1 px-2 py-1 border rounded hover:bg-slate-50 dark:hover:bg-slate-800">Update</button>
              <button name="action" value="company_delete" class="mt-1 px-2 py-1 border rounded text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30" onclick="return confirm('Delete company #<?= (int)$c['id'] ?>?')">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; if(!$rows): ?>
          <tr><td colspan="8" class="py-4 text-center text-slate-500">No companies yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="flex items-center justify-between text-sm">
    <div class="text-slate-500">Total: <?= $total ?></div>
    <div class="flex gap-1">
      <a class="px-3 py-1.5 border rounded <?= $p<=1?'opacity-50 pointer-events-none':'' ?>" href="?page=companies&p=<?= max(1,$p-1) ?>&cq=<?= urlencode($cq) ?>">Prev</a>
      <span class="px-3 py-1.5"><?= $p ?> / <?= $pages ?></span>
      <a class="px-3 py-1.5 border rounded <?= $p>=$pages?'opacity-50 pointer-events-none':'' ?>" href="?page=companies&p=<?= min($pages,$p+1) ?>&cq=<?= urlencode($cq) ?>">Next</a>
    </div>
  </div>
</section>
