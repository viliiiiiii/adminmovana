<?php
$pdo = db();
$companies = $pdo->query('SELECT id,name FROM companies ORDER BY name')->fetchAll();
$companyId=(int)($_GET['company_id'] ?? 0);
$period=($_GET['period'] ?? '');
$where=[]; $args=[];
if ($companyId){ $where[]='company_id=?'; $args[]=$companyId; }
if (preg_match('/^\d{4}-\d{2}$/',$period)){ $where[]='period=?'; $args[]=$period; }
$whereSql = $where ? ('WHERE '.implode(' AND ',$where)) : '';
$p = max(1,(int)($_GET['p'] ?? 1)); $per=20; $off=($p-1)*$per;

$c=$pdo->prepare("SELECT COUNT(*) c FROM company_financials $whereSql"); $c->execute($args); $total=(int)$c->fetch()['c'];
$st=$pdo->prepare("SELECT f.*, c.name company_name FROM company_financials f JOIN companies c ON c.id=f.company_id $whereSql ORDER BY period DESC, f.id DESC LIMIT ? OFFSET ?");
$st->execute([...$args,$per,$off]); $rows=$st->fetchAll();
$sum=$pdo->prepare("SELECT COALESCE(SUM(revenue_cents),0) r, COALESCE(SUM(expenses_cents),0) e FROM company_financials $whereSql");
$sum->execute($args); $tot=$sum->fetch(); $revTot=$tot['r']/100; $expTot=$tot['e']/100; $profit=$revTot-$expTot;
$pages = max(1,(int)ceil($total/$per));
?>
<section class="space-y-6">
  <div class="rounded-2xl border border-slate-200/60 dark:border-slate-800/60 bg-white/80 dark:bg-slate-950/50 p-6 glass space-y-5">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
      <h2 class="text-lg font-semibold">Financials</h2>
      <form class="flex flex-wrap gap-2" method="GET">
        <input type="hidden" name="page" value="finance">
        <select name="company_id" class="border rounded-xl px-3 py-2 bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700">
          <option value="0">All companies</option>
          <?php foreach ($companies as $fc): ?>
            <option value="<?= (int)$fc['id'] ?>" <?= $companyId===(int)$fc['id']?'selected':'' ?>><?= e($fc['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <input name="period" placeholder="YYYY-MM" value="<?= e($period) ?>" class="border rounded-xl px-3 py-2 bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700">
        <button class="px-3 py-2 rounded-xl border hover:bg-slate-100 dark:hover:bg-slate-800">Filter</button>
      </form>
    </div>

    <form method="POST" action="/actions.php" class="grid md:grid-cols-5 gap-3">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="_back" value="/index.php?page=finance">
      <select name="company_id" class="border rounded-xl px-3 py-2 bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700" required>
        <option value="">Company…</option>
        <?php foreach ($companies as $fc): ?><option value="<?= (int)$fc['id'] ?>"><?= e($fc['name']) ?></option><?php endforeach; ?>
      </select>
      <input name="period" placeholder="YYYY-MM" class="border rounded-xl px-3 py-2 bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700" required>
      <input name="revenue" type="number" step="0.01" placeholder="Revenue" class="border rounded-xl px-3 py-2 bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700" required>
      <input name="expenses" type="number" step="0.01" placeholder="Expenses" class="border rounded-xl px-3 py-2 bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700" required>
      <input name="notes" placeholder="Notes" class="border rounded-xl px-3 py-2 bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700">
      <button name="action" value="finance_add" class="md:col-span-5 px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700">Add Entry</button>
    </form>
  </div>

  <div class="grid sm:grid-cols-3 gap-4">
    <div class="rounded-2xl p-1 bg-gradient-to-br from-emerald-500 to-emerald-700 shadow-lg">
      <div class="rounded-2xl p-5 bg-white/90 dark:bg-slate-900/80">
        <div class="text-xs uppercase text-slate-600 dark:text-slate-400">Revenue</div>
        <div class="mt-1 text-2xl font-bold">$<?= number_format($revTot,2) ?></div>
      </div>
    </div>
    <div class="rounded-2xl p-1 bg-gradient-to-br from-rose-500 to-rose-700 shadow-lg">
      <div class="rounded-2xl p-5 bg-white/90 dark:bg-slate-900/80">
        <div class="text-xs uppercase text-slate-600 dark:text-slate-400">Expenses</div>
        <div class="mt-1 text-2xl font-bold">$<?= number_format($expTot,2) ?></div>
      </div>
    </div>
    <div class="rounded-2xl p-1 bg-gradient-to-br from-indigo-500 to-indigo-700 shadow-lg">
      <div class="rounded-2xl p-5 bg-white/90 dark:bg-slate-900/80">
        <div class="text-xs uppercase text-slate-600 dark:text-slate-400">Profit</div>
        <div class="mt-1 text-2xl font-bold">$<?= number_format($profit,2) ?></div>
      </div>
    </div>
  </div>

  <div class="rounded-2xl border border-slate-200/60 dark:border-slate-800/60 overflow-hidden">
    <table class="w-full text-sm">
      <thead>
        <tr class="text-left text-slate-500 border-b border-slate-200/70 dark:border-slate-800">
          <th class="py-3 px-4">Company</th><th>Period</th><th>Revenue</th><th>Expenses</th><th>Profit</th><th>Notes</th><th class="text-right pr-4">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-200/70 dark:divide-slate-800">
        <?php foreach ($rows as $r): $rev=$r['revenue_cents']/100; $exp=$r['expenses_cents']/100; $prof=$rev-$exp; ?>
          <tr>
            <td class="py-3 px-4"><?= e($r['company_name']) ?></td>
            <td class="px-4"><?= e($r['period']) ?></td>
            <td class="px-4">$<?= number_format($rev,2) ?></td>
            <td class="px-4">$<?= number_format($exp,2) ?></td>
            <td class="px-4 <?= $prof>=0?'text-emerald-600':'text-rose-600' ?>">$<?= number_format($prof,2) ?></td>
            <td class="px-4 max-w-[240px] truncate" title="<?= e($r['notes'] ?? '') ?>"><?= e($r['notes'] ?? '') ?></td>
            <td class="px-4 text-right">
              <form method="POST" action="/actions.php" class="inline" onsubmit="return confirm('Delete entry #<?= (int)$r['id'] ?>?')">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="_back" value="/index.php?page=finance&company_id=<?= (int)$companyId ?>&period=<?= urlencode($period) ?>">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button name="action" value="finance_delete" class="px-2 py-1 border rounded-lg text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; if (!$rows): ?>
          <tr><td colspan="7" class="py-8 text-center text-slate-500">No entries.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="flex items-center justify-between text-sm">
    <div class="text-slate-500">Total: <?= $total ?></div>
    <div class="flex gap-1">
      <a class="px-3 py-1.5 border rounded-lg <?= $p<=1?'opacity-50 pointer-events-none':'' ?>" href="?page=finance&p=<?= max(1,$p-1) ?>&company_id=<?= (int)$companyId ?>&period=<?= urlencode($period) ?>">Prev</a>
      <span class="px-3 py-1.5"><?= $p ?> / <?= $pages ?></span>
      <a class="px-3 py-1.5 border rounded-lg <?= $p>=$pages?'opacity-50 pointer-events-none':'' ?>" href="?page=finance&p=<?= min($pages,$p+1) ?>&company_id=<?= (int)$companyId ?>&period=<?= urlencode($period) ?>">Next</a>
    </div>
  </div>
</section>
