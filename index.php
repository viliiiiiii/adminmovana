<?php
require __DIR__ . '/config.php';
require __DIR__ . '/db.php';
require __DIR__ . '/flash.php';
require_login();

$pdo = db();
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

/* Helpers */
function qstr($s){ return strtolower(trim($s ?? '')); }
function param($k,$d=null){ return $_GET[$k] ?? $d; }
function page_params($defaultPer=20){ $p=max(1,(int)($_GET['p']??1)); $per=min(100,max(5,(int)($_GET['per']??$defaultPer))); $off=($p-1)*$per; return [$p,$per,$off]; }

/* Tabs */
$tab = param('tab','overview');

/* Metrics */
$spotsLeft  = (int) setting_get('spots_left', 3);
$totalLeads = (int) ($pdo->query('SELECT COUNT(*) c FROM leads')->fetch()['c'] ?? 0);
$newLeads   = (int) ($pdo->query("SELECT COUNT(*) c FROM leads WHERE status='new'")->fetch()['c'] ?? 0);
$filesCount = (int) ($pdo->query('SELECT COUNT(*) c FROM uploads')->fetch()['c'] ?? 0);
$companyCount = (int) ($pdo->query('SELECT COUNT(*) c FROM companies')->fetch()['c'] ?? 0);
$flash = flash_getall();

/* Data per tab */
$leads=[]; $leadsTotal=0; $q = qstr($_GET['q'] ?? '');
if ($tab==='leads'){ [$page,$per,$off]=page_params(20); $where=''; $args=[]; if($q!==''){ $where="WHERE (LOWER(name) LIKE ? OR LOWER(email) LIKE ? OR LOWER(message) LIKE ?)"; $args=array_fill(0,3,'%'.$q.'%'); }
  $c=$pdo->prepare("SELECT COUNT(*) c FROM leads $where"); $c->execute($args); $leadsTotal=(int)$c->fetch()['c'];
  $st=$pdo->prepare("SELECT * FROM leads $where ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?"); $st->execute([...$args,$per,$off]); $leads=$st->fetchAll();
}

$files=[]; $filesTotal=0;
if ($tab==='files'){ [$page,$per,$off]=page_params(20); $filesTotal=(int)($pdo->query('SELECT COUNT(*) c FROM uploads')->fetch()['c'] ?? 0);
  $st=$pdo->prepare("SELECT * FROM uploads ORDER BY uploaded_at DESC, id DESC LIMIT ? OFFSET ?"); $st->execute([$per,$off]); $files=$st->fetchAll();
}

$log=[]; $logTotal=0;
if ($tab==='activity'){ [$page,$per,$off]=page_params(30); $logTotal=(int)($pdo->query('SELECT COUNT(*) c FROM activity')->fetch()['c'] ?? 0);
  $st=$pdo->prepare("SELECT * FROM activity ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?"); $st->execute([$per,$off]); $log=$st->fetchAll();
}

$companies=[]; $companiesTotal=0; $cq=qstr($_GET['cq'] ?? '');
if ($tab==='companies'){ [$page,$per,$off]=page_params(20); $where=''; $args=[]; if($cq!==''){ $where="WHERE (LOWER(name) LIKE ? OR LOWER(domain) LIKE ? OR LOWER(site_url) LIKE ?)"; $args=array_fill(0,3,'%'.$cq.'%'); }
  $c=$pdo->prepare("SELECT COUNT(*) c FROM companies $where"); $c->execute($args); $companiesTotal=(int)$c->fetch()['c'];
  $st=$pdo->prepare("SELECT * FROM companies $where ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?"); $st->execute([...$args,$per,$off]); $companies=$st->fetchAll();
}

$finCompanies=[]; $finRows=[]; $finTotal=0; $companyId=(int)($_GET['company_id'] ?? 0); $period=($_GET['period'] ?? '');
if ($tab==='financials'){
  $finCompanies = $pdo->query('SELECT id,name FROM companies ORDER BY name')->fetchAll();
  $where=[]; $args=[];
  if ($companyId){ $where[]='company_id=?'; $args[]=$companyId; }
  if (preg_match('/^\d{4}-\d{2}$/',$period)){ $where[]='period=?'; $args[]=$period; }
  $whereSql = $where ? ('WHERE '.implode(' AND ',$where)) : '';
  [$page,$per,$off]=page_params(20);
  $c=$pdo->prepare("SELECT COUNT(*) c FROM company_financials $whereSql"); $c->execute($args); $finTotal=(int)$c->fetch()['c'];
  $st=$pdo->prepare("SELECT f.*, c.name company_name FROM company_financials f JOIN companies c ON c.id=f.company_id $whereSql ORDER BY period DESC, f.id DESC LIMIT ? OFFSET ?");
  $st->execute([...$args,$per,$off]); $finRows=$st->fetchAll();
  // quick totals
  $sum = $pdo->prepare("SELECT COALESCE(SUM(revenue_cents),0) r, COALESCE(SUM(expenses_cents),0) e FROM company_financials $whereSql");
  $sum->execute($args); $tot=$sum->fetch(); $finRevTot=$tot['r']/100; $finExpTot=$tot['e']/100; $finProfitTot=$finRevTot-$finExpTot;
}
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Movana Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>(function(){const s=localStorage.getItem('theme');const d=window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches;if(s==='dark'||(!s&&d))document.documentElement.classList.add('dark');})();function toggleTheme(){const e=document.documentElement;const d=e.classList.toggle('dark');localStorage.setItem('theme',d?'dark':'light');}</script>
</head>
<body class="bg-gray-50 text-slate-900 dark:bg-slate-900 dark:text-slate-100">
<div class="min-h-screen flex">
  <!-- Sidebar -->
  <aside class="hidden md:block w-64 border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950">
    <div class="p-4 border-b border-slate-200 dark:border-slate-800">
      <div class="text-xl font-bold">Movana Admin</div>
      <div class="text-xs text-slate-500 mt-1"><?= e($_SESSION['admin_user']) ?></div>
    </div>
    <?php $nav=['overview'=>'Overview','leads'=>'Leads','files'=>'Files','companies'=>'Companies','financials'=>'Financials','activity'=>'Activity','settings'=>'Settings','profile'=>'Profile']; ?>
    <nav class="p-3 space-y-1 text-sm">
      <?php foreach($nav as $k=>$label): $active=$tab===$k; ?>
      <a href="?tab=<?= $k ?>" class="block px-3 py-2 rounded-md <?= $active?'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-200':'hover:bg-slate-50 dark:hover:bg-slate-800' ?>"><?= $label ?></a>
      <?php endforeach; ?>
      <form class="pt-2 mt-2 border-t border-slate-200 dark:border-slate-800" method="POST" action="/logout.php">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <button class="w-full text-left px-3 py-2 rounded-md hover:bg-red-50 text-red-700 dark:hover:bg-red-900/30 dark:text-red-300">Logout</button>
      </form>
    </nav>
  </aside>

  <!-- Content -->
  <div class="flex-1 flex flex-col">
    <!-- Topbar -->
    <header class="sticky top-0 z-10 bg-white/80 backdrop-blur border-b border-slate-200 dark:bg-slate-950/70 dark:border-slate-800">
      <div class="mx-auto max-w-7xl px-4 h-14 flex items-center justify-between">
        <div class="md:hidden">
          <select class="border rounded px-2 py-1 bg-white dark:bg-slate-900" onchange="location.href='?tab='+this.value">
            <?php foreach($nav as $k=>$label): ?><option value="<?= $k ?>" <?= $tab===$k?'selected':'' ?>><?= $label ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="font-semibold capitalize"><?= e($nav[$tab] ?? 'Admin') ?></div>
        <button onclick="toggleTheme()" class="px-3 py-1.5 rounded border border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800 text-sm">Toggle Theme</button>
      </div>
    </header>

    <main class="mx-auto max-w-7xl p-4 md:p-6 space-y-6">
      <?php if ($flash): ?><div class="space-y-2">
        <?php foreach ($flash as $f): ?>
          <div class="px-4 py-3 rounded border <?= $f['type']==='error'?'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-200 dark:border-red-800':'bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:text-green-200 dark:border-green-800' ?>"><?= e($f['msg']) ?></div>
        <?php endforeach; ?>
      </div><?php endif; ?>

      <?php if ($tab==='overview'): ?>
        <section class="grid sm:grid-cols-2 lg:grid-cols-5 gap-4">
          <div class="bg-white dark:bg-slate-950 p-5 rounded-xl border border-slate-200 dark:border-slate-800">
            <div class="text-sm text-slate-500 dark:text-slate-400">Spots Left</div>
            <div class="mt-1 text-3xl font-bold"><?= $spotsLeft ?></div>
            <form class="mt-3 flex gap-2" method="POST" action="/actions.php">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="_back" value="/index.php?tab=overview">
              <button name="action" value="spots_dec" class="px-3 py-1.5 rounded border hover:bg-slate-50 dark:hover:bg-slate-800">−</button>
              <button name="action" value="spots_inc" class="px-3 py-1.5 rounded border hover:bg-slate-50 dark:hover:bg-slate-800">+</button>
            </form>
          </div>
          <div class="bg-white dark:bg-slate-950 p-5 rounded-xl border border-slate-200 dark:border-slate-800"><div class="text-sm text-slate-500">Leads</div><div class="mt-1 text-3xl font-bold"><?= $totalLeads ?></div><div class="text-sm text-slate-500"><?= $newLeads ?> new</div></div>
          <div class="bg-white dark:bg-slate-950 p-5 rounded-xl border border-slate-200 dark:border-slate-800"><div class="text-sm text-slate-500">Files</div><div class="mt-1 text-3xl font-bold"><?= $filesCount ?></div></div>
          <div class="bg-white dark:bg-slate-950 p-5 rounded-xl border border-slate-200 dark:border-slate-800"><div class="text-sm text-slate-500">Companies</div><div class="mt-1 text-3xl font-bold"><?= $companyCount ?></div></div>
          <div class="bg-white dark:bg-slate-950 p-5 rounded-xl border border-slate-200 dark:border-slate-800"><div class="text-sm text-slate-500">You</div><div class="mt-1 font-semibold"><?= e($_SESSION['admin_user']) ?></div></div>
        </section>

      <?php elseif ($tab==='files'): ?>
        <section class="bg-white dark:bg-slate-950 p-6 rounded-xl border border-slate-200 dark:border-slate-800 space-y-4">
          <h2 class="text-lg font-semibold">Files</h2>
          <form method="POST" action="/actions.php" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="_back" value="/index.php?tab=files">
            <input type="file" name="file" required class="border px-3 py-2 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700">
            <button name="action" value="upload_file" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Upload</button>
          </form>

          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="sticky top-0 bg-white dark:bg-slate-950 z-10">
                <tr class="text-left text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                  <th class="py-2">ID</th><th>Name</th><th>MIME</th><th>Size</th><th>Uploaded</th><th>Preview</th><th class="text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($files as $f): $isImg=str_starts_with($f['mime'],'image/'); ?>
                  <tr class="border-b border-slate-100 dark:border-slate-800">
                    <td class="py-2"><?= (int)$f['id'] ?></td>
                    <td><?= e($f['filename']) ?></td>
                    <td><?= e($f['mime']) ?></td>
                    <td><?= number_format((int)$f['size']/1024,1) ?> KB</td>
                    <td><?= e($f['uploaded_at']) ?></td>
                    <td>
                      <?php if ($isImg): ?>
                        <img src="preview.php?id=<?= (int)$f['id'] ?>" alt="" class="h-12 w-12 object-cover rounded border border-slate-200 dark:border-slate-700">
                      <?php else: ?>
                        <a class="text-blue-600" href="preview.php?id=<?= (int)$f['id'] ?>" target="_blank">Open</a>
                      <?php endif; ?>
                    </td>
                    <td class="text-right">
                      <form method="POST" action="/actions.php" class="inline" onsubmit="return confirm('Delete file #<?= (int)$f['id'] ?>?')">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="_back" value="/index.php?tab=files&p=<?= (int)($_GET['p'] ?? 1) ?>">
                        <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                        <button name="action" value="file_delete" class="px-2 py-1 border rounded text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30">Delete</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; if (!$files): ?>
                  <tr><td colspan="7" class="py-4 text-center text-slate-500 dark:text-slate-400">No files.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          <?php [$page,$per,$off]=page_params(20); $pages=max(1,(int)ceil(($filesTotal??0)/$per)); $base='?tab=files'."&per=$per"; ?>
          <div class="flex items-center justify-between text-sm">
            <div class="text-slate-500">Total: <?= (int)($filesTotal??0) ?></div>
            <div class="flex gap-1">
              <a class="px-3 py-1.5 border rounded <?= $page<=1?'opacity-50 pointer-events-none':'' ?>" href="<?= $base.'&p='.max(1,$page-1) ?>">Prev</a>
              <span class="px-3 py-1.5"><?= $page ?> / <?= $pages ?></span>
              <a class="px-3 py-1.5 border rounded <?= $page>=$pages?'opacity-50 pointer-events-none':'' ?>" href="<?= $base.'&p='.min($pages,$page+1) ?>">Next</a>
            </div>
          </div>
        </section>

      <?php elseif ($tab==='companies'): ?>
        <section class="bg-white dark:bg-slate-950 p-6 rounded-xl border border-slate-200 dark:border-slate-800 space-y-5">
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
            <h2 class="text-lg font-semibold">Companies</h2>
            <form class="flex gap-2" method="GET">
              <input type="hidden" name="tab" value="companies">
              <input name="cq" value="<?= e($_GET['cq'] ?? '') ?>" placeholder="Search name/domain/site" class="border rounded px-3 py-2 bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700">
              <button class="px-3 py-2 rounded border hover:bg-slate-50 dark:hover:bg-slate-800">Search</button>
            </form>
          </div>

          <!-- Add company -->
          <form method="POST" action="/actions.php" class="grid md:grid-cols-3 gap-3">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="_back" value="/index.php?tab=companies">
            <input name="name" placeholder="Company name" class="border px-3 py-2 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700" required>
            <input name="domain" placeholder="Domain (example.com)" class="border px-3 py-2 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700">
            <input name="site_url" placeholder="Site URL (https://...)" class="border px-3 py-2 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700">
            <select name="status" class="border px-3 py-2 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700">
              <?php foreach (['prospect','active','paused','closed'] as $s): ?><option value="<?= $s ?>"><?= $s ?></option><?php endforeach; ?>
            </select>
            <input name="notes" placeholder="Notes" class="md:col-span-2 border px-3 py-2 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700">
            <button name="action" value="company_add" class="md:col-span-3 px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Add Company</button>
          </form>

          <!-- Table -->
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="sticky top-0 bg-white dark:bg-slate-950 z-10">
                <tr class="text-left text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                  <th class="py-2">ID</th><th>Name</th><th>Status</th><th>Domain</th><th>Site</th><th>Notes</th><th>Created</th><th class="text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($companies as $c): ?>
                <tr class="border-b border-slate-100 dark:border-slate-800 align-top">
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
                      <input type="hidden" name="_back" value="/index.php?tab=companies&p=<?= (int)($_GET['p'] ?? 1) ?>&cq=<?= urlencode($cq) ?>">
                      <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                      <select name="status" class="border px-2 py-1 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700">
                        <?php foreach (['prospect','active','paused','closed'] as $s): ?><option value="<?= $s ?>" <?= $c['status']===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?>
                      </select>
                      <button name="action" value="company_status" class="px-2 py-1 border rounded hover:bg-slate-50 dark:hover:bg-slate-800">Save</button>
                    </form>
                    <form method="POST" action="/actions.php" class="block">
                      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="_back" value="/index.php?tab=companies&p=<?= (int)($_GET['p'] ?? 1) ?>&cq=<?= urlencode($cq) ?>">
                      <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                      <input name="domain" value="<?= e($c['domain'] ?? '') ?>" placeholder="domain" class="border px-2 py-1 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 mt-1">
                      <input name="site_url" value="<?= e($c['site_url'] ?? '') ?>" placeholder="site url" class="border px-2 py-1 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 mt-1">
                      <input name="notes" value="<?= e($c['notes'] ?? '') ?>" placeholder="notes" class="border px-2 py-1 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 mt-1">
                      <button name="action" value="company_save" class="mt-1 px-2 py-1 border rounded hover:bg-slate-50 dark:hover:bg-slate-800">Update</button>
                      <button name="action" value="company_delete" class="mt-1 px-2 py-1 border rounded text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30" onclick="return confirm('Delete company #<?= (int)$c['id'] ?>?')">Delete</button>
                    </form>
                  </td>
                </tr>
                <?php endforeach; if(!$companies): ?>
                  <tr><td colspan="8" class="py-4 text-center text-slate-500 dark:text-slate-400">No companies yet.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          <?php [$page,$per,$off]=page_params(20); $pages=max(1,(int)ceil(($companiesTotal?:0)/$per)); $base='?tab=companies'.($cq!==''?'&cq='.urlencode($cq):'')."&per=$per"; ?>
          <div class="flex items-center justify-between text-sm">
            <div class="text-slate-500">Total: <?= $companiesTotal ?></div>
            <div class="flex gap-1">
              <a class="px-3 py-1.5 border rounded <?= $page<=1?'opacity-50 pointer-events-none':'' ?>" href="<?= $base.'&p='.max(1,$page-1) ?>">Prev</a>
              <span class="px-3 py-1.5"><?= $page ?> / <?= $pages ?></span>
              <a class="px-3 py-1.5 border rounded <?= $page>=$pages?'opacity-50 pointer-events-none':'' ?>" href="<?= $base.'&p='.min($pages,$page+1) ?>">Next</a>
            </div>
          </div>
        </section>

      <?php elseif ($tab==='financials'): ?>
        <section class="bg-white dark:bg-slate-950 p-6 rounded-xl border border-slate-200 dark:border-slate-800 space-y-5">
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
            <h2 class="text-lg font-semibold">Financials</h2>
            <form class="flex flex-wrap gap-2" method="GET">
              <input type="hidden" name="tab" value="financials">
              <select name="company_id" class="border rounded px-3 py-2 bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700">
                <option value="0">All companies</option>
                <?php foreach ($finCompanies as $fc): ?>
                  <option value="<?= (int)$fc['id'] ?>" <?= $companyId===(int)$fc['id']?'selected':'' ?>><?= e($fc['name']) ?></option>
                <?php endforeach; ?>
              </select>
              <input name="period" placeholder="YYYY-MM" value="<?= e($period) ?>" class="border rounded px-3 py-2 bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700">
              <button class="px-3 py-2 rounded border hover:bg-slate-50 dark:hover:bg-slate-800">Filter</button>
            </form>
          </div>

          <!-- Add financial row -->
          <form method="POST" action="/actions.php" class="grid md:grid-cols-5 gap-3">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="_back" value="/index.php?tab=financials">
            <select name="company_id" class="border rounded px-3 py-2 bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700" required>
              <option value="">Company…</option>
              <?php foreach ($finCompanies as $fc): ?><option value="<?= (int)$fc['id'] ?>"><?= e($fc['name']) ?></option><?php endforeach; ?>
            </select>
            <input name="period" placeholder="YYYY-MM" class="border rounded px-3 py-2 bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700" required>
            <input name="revenue" type="number" step="0.01" placeholder="Revenue" class="border rounded px-3 py-2 bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700" required>
            <input name="expenses" type="number" step="0.01" placeholder="Expenses" class="border rounded px-3 py-2 bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700" required>
            <input name="notes" placeholder="Notes" class="border rounded px-3 py-2 bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700">
            <button name="action" value="finance_add" class="md:col-span-5 px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Add Entry</button>
          </form>

          <!-- Totals -->
          <?php if (isset($finRevTot)): ?>
          <div class="grid sm:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-slate-950 p-5 rounded-xl border border-slate-200 dark:border-slate-800">
              <div class="text-sm text-slate-500">Revenue (filtered)</div>
              <div class="mt-1 text-2xl font-bold">$
                <?= number_format($finRevTot, 2) ?>
              </div>
            </div>
            <div class="bg-white dark:bg-slate-950 p-5 rounded-xl border border-slate-200 dark:border-slate-800">
              <div class="text-sm text-slate-500">Expenses (filtered)</div>
              <div class="mt-1 text-2xl font-bold">$
                <?= number_format($finExpTot, 2) ?>
              </div>
            </div>
            <div class="bg-white dark:bg-slate-950 p-5 rounded-xl border border-slate-200 dark:border-slate-800">
              <div class="text-sm text-slate-500">Profit (filtered)</div>
              <div class="mt-1 text-2xl font-bold">$
                <?= number_format($finProfitTot, 2) ?>
              </div>
            </div>
          </div>
          <?php endif; ?>

          <!-- Table -->
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="sticky top-0 bg-white dark:bg-slate-950 z-10">
                <tr class="text-left text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                  <th class="py-2">ID</th><th>Company</th><th>Period</th><th>Revenue</th><th>Expenses</th><th>Profit</th><th>Notes</th><th class="text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($finRows as $r): $rev=$r['revenue_cents']/100; $exp=$r['expenses_cents']/100; $prof=$rev-$exp; ?>
                <tr class="border-b border-slate-100 dark:border-slate-800">
                  <td class="py-2"><?= (int)$r['id'] ?></td>
                  <td><?= e($r['company_name']) ?></td>
                  <td><?= e($r['period']) ?></td>
                  <td>$<?= number_format($rev,2) ?></td>
                  <td>$<?= number_format($exp,2) ?></td>
                  <td class="<?= $prof>=0?'text-green-600':'text-red-600' ?>">$<?= number_format($prof,2) ?></td>
                  <td class="max-w-[240px] truncate" title="<?= e($r['notes'] ?? '') ?>"><?= e($r['notes'] ?? '') ?></td>
                  <td class="text-right">
                    <form method="POST" action="/actions.php" class="inline" onsubmit="return confirm('Delete entry #<?= (int)$r['id'] ?>?')">
                      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="_back" value="/index.php?tab=financials&company_id=<?= (int)$companyId ?>&period=<?= urlencode($period) ?>">
                      <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                      <button name="action" value="finance_delete" class="px-2 py-1 border rounded text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30">Delete</button>
                    </form>
                  </td>
                </tr>
                <?php endforeach; if(!$finRows): ?>
                  <tr><td colspan="8" class="py-4 text-center text-slate-500 dark:text-slate-400">No entries.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          <?php [$page,$per,$off]=page_params(20); $pages=max(1,(int)ceil(($finTotal?:0)/$per)); $base='?tab=financials' . ($companyId?('&company_id='.$companyId):'') . ($period?('&period='.urlencode($period)):'') . "&per=$per"; ?>
          <div class="flex items-center justify-between text-sm">
            <div class="text-slate-500">Total: <?= $finTotal ?></div>
            <div class="flex gap-1">
              <a class="px-3 py-1.5 border rounded <?= $page<=1?'opacity-50 pointer-events-none':'' ?>" href="<?= $base.'&p='.max(1,$page-1) ?>">Prev</a>
              <span class="px-3 py-1.5"><?= $page ?> / <?= $pages ?></span>
              <a class="px-3 py-1.5 border rounded <?= $page>=$pages?'opacity-50 pointer-events-none':'' ?>" href="<?= $base.'&p='.min($pages,$page+1) ?>">Next</a>
            </div>
          </div>
        </section>

      <?php elseif ($tab==='activity'): /* unchanged list with pagination, from your last version */ ?>
        <!-- keep your existing activity block -->

      <?php elseif ($tab==='settings'): /* keep settings form from last version */ ?>
        <!-- keep settings block -->

      <?php elseif ($tab==='profile'): /* keep profile/password block */ ?>
        <!-- keep profile block -->
      <?php endif; ?>
    </main>
  </div>
</div>
</body>
</html>
