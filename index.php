<?php
require __DIR__ . '/config.php';
require __DIR__ . '/db.php';
require __DIR__ . '/flash.php';
require_login();

$pdo = db();
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

/* ---------- Helpers ---------- */
function qstr($s){ return strtolower(trim($s ?? '')); }
function param($k,$d=null){ return $_GET[$k] ?? $d; }
function page_params($defaultPer=20){
  $p = max(1, (int)($_GET['p'] ?? 1));
  $per = min(100, max(5, (int)($_GET['per'] ?? $defaultPer)));
  $off = ($p-1)*$per;
  return [$p,$per,$off];
}

/* ---------- Tabs & actions ---------- */
$tab = param('tab','overview');

/* CSV export for leads */
if ($tab==='leads' && isset($_GET['export']) && $_GET['export']==='csv') {
  $q = qstr($_GET['q'] ?? '');
  $where = ''; $args = [];
  if ($q!=='') {
    if ($driver==='pgsql') $where = "WHERE (LOWER(name) LIKE ? OR LOWER(email) LIKE ? OR LOWER(message) LIKE ?)";
    else $where = "WHERE (LOWER(name) LIKE ? OR LOWER(email) LIKE ? OR LOWER(message) LIKE ?)";
    $args = array_fill(0,3,'%'.$q.'%');
  }
  $stmt = $pdo->prepare("SELECT id,name,email,message,status,created_at FROM leads $where ORDER BY created_at DESC, id DESC");
  $stmt->execute($args);
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename="leads.csv"');
  $out = fopen('php://output','w');
  fputcsv($out, ['id','name','email','message','status','created_at']);
  while($row=$stmt->fetch(PDO::FETCH_ASSOC)){ fputcsv($out,$row); }
  fclose($out);
  exit;
}

/* ---------- Dashboard metrics ---------- */
$spotsLeft  = (int) setting_get('spots_left', 3);
$totalLeads = (int) ($pdo->query('SELECT COUNT(*) c FROM leads')->fetch()['c'] ?? 0);
$newLeads   = (int) ($pdo->query("SELECT COUNT(*) c FROM leads WHERE status='new'")->fetch()['c'] ?? 0);
$filesCount = (int) ($pdo->query('SELECT COUNT(*) c FROM uploads')->fetch()['c'] ?? 0);
$flash = flash_getall();

/* ---------- Data for tabs ---------- */
$leads = []; $leadsTotal=0; $q = qstr($_GET['q'] ?? '');
if ($tab==='leads'){
  [$page,$per,$off] = page_params(20);
  $where=''; $args=[];
  if ($q!=='') {
    $where="WHERE (LOWER(name) LIKE ? OR LOWER(email) LIKE ? OR LOWER(message) LIKE ?)";
    $args=array_fill(0,3,'%'.$q.'%');
  }
  $countStmt = $pdo->prepare("SELECT COUNT(*) c FROM leads $where");
  $countStmt->execute($args);
  $leadsTotal = (int)$countStmt->fetch()['c'];
  $stmt = $pdo->prepare("SELECT * FROM leads $where ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?");
  $stmt->execute([...$args, $per, $off]);
  $leads = $stmt->fetchAll();
}

$files = [];
if ($tab==='files'){
  [$page,$per,$off] = page_params(20);
  $stmt = $pdo->prepare("SELECT * FROM uploads ORDER BY uploaded_at DESC, id DESC LIMIT ? OFFSET ?");
  $stmt->execute([$per,$off]);
  $files = $stmt->fetchAll();
  $filesTotal = (int) ($pdo->query('SELECT COUNT(*) c FROM uploads')->fetch()['c'] ?? 0);
}

$log = []; $logTotal=0;
if ($tab==='activity'){
  [$page,$per,$off] = page_params(30);
  $logTotal = (int) ($pdo->query('SELECT COUNT(*) c FROM activity')->fetch()['c'] ?? 0);
  $stmt = $pdo->prepare("SELECT * FROM activity ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?");
  $stmt->execute([$per,$off]);
  $log = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en" class="">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Movana Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
  .scroll-smooth::-webkit-scrollbar{height:8px;width:8px}
  .scroll-smooth::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:9999px}
  .dark .scroll-smooth::-webkit-scrollbar-thumb{background:#475569}
</style>
<script>
// Theme toggle with persistence
(function(){
  const saved = localStorage.getItem('theme');
  const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  if ((saved==='dark') || (!saved && prefersDark)) document.documentElement.classList.add('dark');
})();
function toggleTheme(){
  const el=document.documentElement;
  const dark=el.classList.toggle('dark');
  localStorage.setItem('theme', dark?'dark':'light');
}
</script>
</head>
<body class="bg-gray-50 text-slate-900 dark:bg-slate-900 dark:text-slate-100">
  <!-- Layout -->
  <div class="min-h-screen flex">
    <!-- Sidebar -->
    <aside class="hidden md:block w-64 border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950">
      <div class="p-4 border-b border-slate-200 dark:border-slate-800">
        <div class="text-xl font-bold">Movana Admin</div>
        <div class="text-xs text-slate-500 mt-1"><?= e($_SESSION['admin_user']) ?></div>
      </div>
      <nav class="p-3 space-y-1 text-sm">
        <?php
          $nav = [
            'overview'=>'Overview',
            'leads'=>'Leads',
            'files'=>'Files',
            'activity'=>'Activity',
            'settings'=>'Settings',
            'profile'=>'Profile'
          ];
          foreach($nav as $k=>$label):
            $active = $tab===$k;
        ?>
          <a href="?tab=<?= $k ?>" class="block px-3 py-2 rounded-md <?= $active?'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-200':'hover:bg-slate-50 dark:hover:bg-slate-800' ?>">
            <?= $label ?>
          </a>
        <?php endforeach; ?>
        <form class="pt-2 mt-2 border-t border-slate-200 dark:border-slate-800" method="POST" action="/logout.php">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <button class="w-full text-left px-3 py-2 rounded-md hover:bg-red-50 text-red-700 dark:hover:bg-red-900/30 dark:text-red-300">Logout</button>
        </form>
      </nav>
    </aside>

    <!-- Main -->
    <div class="flex-1 flex flex-col">
      <!-- Topbar -->
      <header class="sticky top-0 z-10 bg-white/80 backdrop-blur border-b border-slate-200 dark:bg-slate-950/70 dark:border-slate-800">
        <div class="mx-auto max-w-7xl px-4 h-14 flex items-center justify-between">
          <div class="md:hidden">
            <select class="border rounded px-2 py-1 bg-white dark:bg-slate-900" onchange="location.href='?tab='+this.value">
              <?php foreach($nav as $k=>$label): ?>
                <option value="<?= $k ?>" <?= $tab===$k?'selected':'' ?>><?= $label ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="font-semibold capitalize"><?= e($nav[$tab] ?? 'Admin') ?></div>
          <div class="flex items-center gap-2">
            <button onclick="toggleTheme()" class="px-3 py-1.5 rounded border border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800 text-sm">
              Toggle Theme
            </button>
          </div>
        </div>
      </header>

      <main class="mx-auto max-w-7xl p-4 md:p-6 space-y-6">
        <?php if ($flash): ?>
          <div class="space-y-2">
            <?php foreach ($flash as $f): ?>
              <div class="px-4 py-3 rounded border <?= $f['type']==='error'?'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-200 dark:border-red-800':'bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:text-green-200 dark:border-green-800' ?>">
                <?= e($f['msg']) ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if ($tab==='overview'): ?>
          <section class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
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
            <div class="bg-white dark:bg-slate-950 p-5 rounded-xl border border-slate-200 dark:border-slate-800">
              <div class="text-sm text-slate-500 dark:text-slate-400">Leads (total)</div>
              <div class="mt-1 text-3xl font-bold"><?= $totalLeads ?></div>
              <div class="text-sm text-slate-500 dark:text-slate-400"><?= $newLeads ?> new</div>
            </div>
            <div class="bg-white dark:bg-slate-950 p-5 rounded-xl border border-slate-200 dark:border-slate-800">
              <div class="text-sm text-slate-500 dark:text-slate-400">Files</div>
              <div class="mt-1 text-3xl font-bold"><?= $filesCount ?></div>
            </div>
            <div class="bg-white dark:bg-slate-950 p-5 rounded-xl border border-slate-200 dark:border-slate-800">
              <div class="text-sm text-slate-500 dark:text-slate-400">Signed in</div>
              <div class="mt-1 font-semibold"><?= e($_SESSION['admin_user']) ?></div>
              <div class="text-sm text-slate-500 dark:text-slate-400">ID #<?= (int)$_SESSION['user_id'] ?></div>
            </div>
          </section>

          <section class="bg-white dark:bg-slate-950 p-6 rounded-xl border border-slate-200 dark:border-slate-800">
            <h2 class="text-lg font-semibold mb-3">Quick Actions</h2>
            <form method="POST" action="/actions.php" class="inline">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="_back" value="/index.php?tab=overview">
              <button name="action" value="purge_cache" class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700">Purge Cache</button>
            </form>
          </section>

        <?php elseif ($tab==='leads'): ?>
          <section class="bg-white dark:bg-slate-950 p-6 rounded-xl border border-slate-200 dark:border-slate-800 space-y-4">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
              <h2 class="text-lg font-semibold">Leads</h2>
              <form class="flex gap-2" method="GET">
                <input type="hidden" name="tab" value="leads">
                <input name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Search name/email/message" class="border rounded px-3 py-2 bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700">
                <button class="px-3 py-2 rounded border hover:bg-slate-50 dark:hover:bg-slate-800">Search</button>
                <a href="?tab=leads&export=csv<?= $q!=='' ? '&q='.urlencode($q) : '' ?>" class="px-3 py-2 rounded border hover:bg-slate-50 dark:hover:bg-slate-800">Export CSV</a>
              </form>
            </div>

            <form method="POST" action="/actions.php" class="grid md:grid-cols-3 gap-3">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="_back" value="/index.php?tab=leads">
              <input name="name" placeholder="Name" class="border px-3 py-2 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700" required>
              <input name="email" type="email" placeholder="Email" class="border px-3 py-2 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700" required>
              <input name="message" placeholder="Message" class="border px-3 py-2 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 md:col-span-3">
              <button name="action" value="lead_add" class="md:col-span-3 px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Add Lead</button>
            </form>

            <div class="overflow-x-auto scroll-smooth">
              <table class="w-full text-sm">
                <thead class="sticky top-0 bg-white dark:bg-slate-950 z-10">
                  <tr class="text-left text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <th class="py-2">ID</th><th>Name</th><th>Email</th><th>Status</th><th>Created</th><th class="text-right">Actions</th>
                  </tr>
                </thead>
                <tbody>
                <?php foreach ($leads as $l): ?>
                  <tr class="border-b border-slate-100 dark:border-slate-800">
                    <td class="py-2"><?= (int)$l['id'] ?></td>
                    <td><?= e($l['name']) ?></td>
                    <td><?= e($l['email']) ?></td>
                    <td><?= e($l['status']) ?></td>
                    <td><?= e($l['created_at']) ?></td>
                    <td class="text-right space-x-1">
                      <form method="POST" action="/actions.php" class="inline">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="_back" value="/index.php?tab=leads&p=<?= (int)($_GET['p'] ?? 1) ?>&q=<?= urlencode($q) ?>">
                        <input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
                        <select name="status" class="border px-2 py-1 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700">
                          <?php foreach (['new','contacted','won','lost'] as $s): ?>
                            <option value="<?= $s ?>" <?= $l['status']===$s?'selected':''?>><?= $s ?></option>
                          <?php endforeach; ?>
                        </select>
                        <button name="action" value="lead_status" class="px-2 py-1 border rounded hover:bg-slate-50 dark:hover:bg-slate-800">Save</button>
                      </form>
                      <form method="POST" action="/actions.php" class="inline" onsubmit="return confirm('Delete lead #<?= (int)$l['id'] ?>?')">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="_back" value="/index.php?tab=leads&p=<?= (int)($_GET['p'] ?? 1) ?>&q=<?= urlencode($q) ?>">
                        <input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
                        <button name="action" value="lead_delete" class="px-2 py-1 border rounded text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30">Delete</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; if (!$leads): ?>
                  <tr><td colspan="6" class="py-4 text-center text-slate-500 dark:text-slate-400">No leads.</td></tr>
                <?php endif; ?>
                </tbody>
              </table>
            </div>

            <?php
              [$page,$per,$off] = page_params(20);
              $pages = max(1, (int)ceil(($leadsTotal ?: 0)/$per));
              $base = '?tab=leads'.($q!==''?'&q='.urlencode($q):'')."&per=$per";
            ?>
            <div class="flex items-center justify-between text-sm">
              <div class="text-slate-500 dark:text-slate-400">Total: <?= $leadsTotal ?></div>
              <div class="flex gap-1">
                <a class="px-3 py-1.5 border rounded <?= $page<=1?'opacity-50 pointer-events-none':'' ?>" href="<?= $base.'&p='.max(1,$page-1) ?>">Prev</a>
                <span class="px-3 py-1.5"><?= $page ?> / <?= $pages ?></span>
                <a class="px-3 py-1.5 border rounded <?= $page>=$pages?'opacity-50 pointer-events-none':'' ?>" href="<?= $base.'&p='.min($pages,$page+1) ?>">Next</a>
              </div>
            </div>
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

            <div class="overflow-x-auto scroll-smooth">
              <table class="w-full text-sm">
                <thead class="sticky top-0 bg-white dark:bg-slate-950 z-10">
                  <tr class="text-left text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <th class="py-2">ID</th><th>Name</th><th>MIME</th><th>Size</th><th>Uploaded</th><th class="text-right">Actions</th>
                  </tr>
                </thead>
                <tbody>
                <?php foreach ($files as $f): ?>
                  <tr class="border-b border-slate-100 dark:border-slate-800">
                    <td class="py-2"><?= (int)$f['id'] ?></td>
                    <td><?= e($f['filename']) ?></td>
                    <td><?= e($f['mime']) ?></td>
                    <td><?= number_format((int)$f['size']/1024,1) ?> KB</td>
                    <td><?= e($f['uploaded_at']) ?></td>
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
                  <tr><td colspan="6" class="py-4 text-center text-slate-500 dark:text-slate-400">No files.</td></tr>
                <?php endif; ?>
                </tbody>
              </table>
            </div>

            <?php
              [$page,$per,$off] = page_params(20);
              $pages = max(1, (int)ceil(($filesTotal ?? 0)/$per));
              $base = '?tab=files' . "&per=$per";
            ?>
            <div class="flex items-center justify-between text-sm">
              <div class="text-slate-500 dark:text-slate-400">Total: <?= (int)($filesTotal ?? 0) ?></div>
              <div class="flex gap-1">
                <a class="px-3 py-1.5 border rounded <?= $page<=1?'opacity-50 pointer-events-none':'' ?>" href="<?= $base.'&p='.max(1,$page-1) ?>">Prev</a>
                <span class="px-3 py-1.5"><?= $page ?> / <?= $pages ?></span>
                <a class="px-3 py-1.5 border rounded <?= $page>=$pages?'opacity-50 pointer-events-none':'' ?>" href="<?= $base.'&p='.min($pages,$page+1) ?>">Next</a>
              </div>
            </div>
          </section>

        <?php elseif ($tab==='activity'): ?>
          <section class="bg-white dark:bg-slate-950 p-6 rounded-xl border border-slate-200 dark:border-slate-800">
            <h2 class="text-lg font-semibold mb-4">Activity</h2>
            <div class="overflow-x-auto scroll-smooth">
              <table class="w-full text-sm">
                <thead class="sticky top-0 bg-white dark:bg-slate-950 z-10">
                  <tr class="text-left text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <th class="py-2">Time</th><th>Action</th><th>Meta</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($log as $a): $meta = json_decode($a['meta'], true) ?: []; ?>
                    <tr class="border-b border-slate-100 dark:border-slate-800">
                      <td class="py-2 whitespace-nowrap"><?= e($a['created_at']) ?></td>
                      <td class="whitespace-nowrap"><?= e($a['action']) ?></td>
                      <td><code class="text-slate-500 dark:text-slate-400"><?= e(json_encode($meta)) ?></code></td>
                    </tr>
                  <?php endforeach; if (!$log): ?>
                    <tr><td colspan="3" class="py-4 text-center text-slate-500 dark:text-slate-400">No activity.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
            <?php
              [$page,$per,$off] = page_params(30);
              $pages = max(1, (int)ceil(($logTotal ?? 0)/$per));
              $base='?tab=activity'."&per=$per";
            ?>
            <div class="mt-3 flex items-center justify-between text-sm">
              <div class="text-slate-500 dark:text-slate-400">Total: <?= (int)($logTotal ?? 0) ?></div>
              <div class="flex gap-1">
                <a class="px-3 py-1.5 border rounded <?= $page<=1?'opacity-50 pointer-events-none':'' ?>" href="<?= $base.'&p='.max(1,$page-1) ?>">Prev</a>
                <span class="px-3 py-1.5"><?= $page ?> / <?= $pages ?></span>
                <a class="px-3 py-1.5 border rounded <?= $page>=$pages?'opacity-50 pointer-events-none':'' ?>" href="<?= $base.'&p='.min($pages,$page+1) ?>">Next</a>
              </div>
            </div>
          </section>

        <?php elseif ($tab==='settings'): ?>
          <section class="bg-white dark:bg-slate-950 p-6 rounded-xl border border-slate-200 dark:border-slate-800 space-y-4 max-w-lg">
            <h2 class="text-lg font-semibold">Settings</h2>
            <form method="POST" action="/actions.php" class="space-y-3">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="_back" value="/index.php?tab=settings">
              <label class="block">
                <span class="text-sm text-slate-600 dark:text-slate-300">Spots Left</span>
                <input type="number" min="0" max="99" name="spots_left" value="<?= $spotsLeft ?>" class="mt-1 w-full border px-3 py-2 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700">
              </label>
              <button name="action" value="settings_save" class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700">Save</button>
            </form>
          </section>

        <?php elseif ($tab==='profile'): ?>
          <section class="bg-white dark:bg-slate-950 p-6 rounded-xl border border-slate-200 dark:border-slate-800 space-y-4 max-w-lg">
            <h2 class="text-lg font-semibold">Profile</h2>
            <div class="text-sm text-slate-600 dark:text-slate-300">Signed in as <strong><?= e($_SESSION['admin_user']) ?></strong></div>
            <form method="POST" action="/actions.php" class="space-y-3">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="_back" value="/index.php?tab=profile">
              <label class="block">
                <span class="text-sm text-slate-600 dark:text-slate-300">New Password</span>
                <input name="new_password" type="password" minlength="8" class="mt-1 w-full border px-3 py-2 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700" required>
              </label>
              <label class="block">
                <span class="text-sm text-slate-600 dark:text-slate-300">Confirm Password</span>
                <input name="new_password2" type="password" minlength="8" class="mt-1 w-full border px-3 py-2 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700" required>
              </label>
              <button name="action" value="password_change" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Change Password</button>
            </form>
          </section>
        <?php endif; ?>
      </main>
    </div>
  </div>
</body>
</html>
