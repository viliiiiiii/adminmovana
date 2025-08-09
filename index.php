<?php
require __DIR__ . '/config.php';
require __DIR__ . '/db.php';
require __DIR__ . '/flash.php';
require_login();

$tab = $_GET['tab'] ?? 'overview';
$pdo = db();

// Overview metrics
$spotsLeft = (int) setting_get('spots_left', 3);
$totalLeads = (int) $pdo->query('SELECT COUNT(*) AS c FROM leads')->fetch()['c'] ?? 0;
$newLeads = (int) $pdo->query("SELECT COUNT(*) AS c FROM leads WHERE status='new'")->fetch()['c'] ?? 0;
$filesCount = (int) $pdo->query('SELECT COUNT(*) AS c FROM uploads')->fetch()['c'] ?? 0;

$flash = flash_getall();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Movana Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
  <header class="bg-white border-b shadow-sm">
    <div class="max-w-6xl mx-auto flex items-center justify-between p-4">
      <h1 class="text-xl font-bold">Movana Admin</h1>
      <nav class="flex items-center gap-4 text-sm">
        <a href="?tab=overview" class="<?= $tab==='overview'?'text-blue-600 font-semibold':'text-gray-600'?>">Overview</a>
        <a href="?tab=leads" class="<?= $tab==='leads'?'text-blue-600 font-semibold':'text-gray-600'?>">Leads</a>
        <a href="?tab=files" class="<?= $tab==='files'?'text-blue-600 font-semibold':'text-gray-600'?>">Files</a>
        <a href="?tab=activity" class="<?= $tab==='activity'?'text-blue-600 font-semibold':'text-gray-600'?>">Activity</a>
        <form method="POST" action="/logout.php">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <button class="ml-3 px-3 py-1.5 rounded bg-red-600 text-white hover:bg-red-700">Logout</button>
        </form>
      </nav>
    </div>
  </header>

  <main class="max-w-6xl mx-auto p-6 space-y-6">
    <?php if ($flash): ?>
      <div class="space-y-2">
        <?php foreach ($flash as $f): ?>
          <div class="px-4 py-3 rounded <?= $f['type']==='error'?'bg-red-50 text-red-700 border border-red-200':'bg-green-50 text-green-700 border border-green-200' ?>">
            <?= e($f['msg']) ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($tab === 'overview'): ?>
      <section class="grid md:grid-cols-3 gap-6">
        <div class="bg-white p-5 rounded shadow">
          <div class="text-gray-500 text-sm">Spots Left</div>
          <div class="text-3xl font-bold mt-2"><?= $spotsLeft ?></div>
          <form class="mt-4 flex gap-2" method="POST" action="/actions.php">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="_back" value="/index.php?tab=overview">
            <button name="action" value="spots_dec" class="px-3 py-1.5 border rounded hover:bg-gray-50">−</button>
            <button name="action" value="spots_inc" class="px-3 py-1.5 border rounded hover:bg-gray-50">+</button>
          </form>
        </div>
        <div class="bg-white p-5 rounded shadow">
          <div class="text-gray-500 text-sm">Leads (new)</div>
          <div class="text-3xl font-bold mt-2"><?= $newLeads ?></div>
          <a href="?tab=leads" class="text-blue-600 text-sm mt-2 inline-block">View</a>
        </div>
        <div class="bg-white p-5 rounded shadow">
          <div class="text-gray-500 text-sm">Files</div>
          <div class="text-3xl font-bold mt-2"><?= $filesCount ?></div>
          <a href="?tab=files" class="text-blue-600 text-sm mt-2 inline-block">Manage</a>
        </div>
      </section>

      <section class="bg-white p-6 rounded shadow">
        <h2 class="text-lg font-semibold mb-3">Quick Actions</h2>
        <form method="POST" action="/actions.php" class="inline">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="_back" value="/index.php?tab=overview">
          <button name="action" value="purge_cache" class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700">Purge Cache</button>
        </form>
      </section>

    <?php elseif ($tab === 'leads'):
      $leads = $pdo->query('SELECT * FROM leads ORDER BY created_at DESC LIMIT 100')->fetchAll();
    ?>
      <section class="bg-white p-6 rounded shadow space-y-6">
        <h2 class="text-lg font-semibold">Leads</h2>
        <form method="POST" action="/actions.php" class="grid md:grid-cols-3 gap-3">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="_back" value="/index.php?tab=leads">
          <input name="name" placeholder="Name" class="border px-3 py-2 rounded" required>
          <input name="email" type="email" placeholder="Email" class="border px-3 py-2 rounded" required>
          <input name="message" placeholder="Message" class="border px-3 py-2 rounded md:col-span-3">
          <button name="action" value="lead_add" class="md:col-span-3 px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Add Lead</button>
        </form>

        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left text-gray-500 border-b">
                <th class="py-2">ID</th><th>Name</th><th>Email</th><th>Status</th><th>Created</th><th class="text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($leads as $l): ?>
              <tr class="border-b last:border-0">
                <td class="py-2"><?= (int)$l['id'] ?></td>
                <td><?= e($l['name']) ?></td>
                <td><?= e($l['email']) ?></td>
                <td><?= e($l['status']) ?></td>
                <td><?= e($l['created_at']) ?></td>
                <td class="text-right space-x-1">
                  <form method="POST" action="/actions.php" class="inline">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="_back" value="/index.php?tab=leads">
                    <input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
                    <select name="status" class="border px-2 py-1 rounded">
                      <?php foreach (['new','contacted','won','lost'] as $s): ?>
                        <option value="<?= $s ?>" <?= $l['status']===$s?'selected':''?>><?= $s ?></option>
                      <?php endforeach; ?>
                    </select>
                    <button name="action" value="lead_status" class="px-2 py-1 border rounded hover:bg-gray-50">Save</button>
                  </form>
                  <form method="POST" action="/actions.php" class="inline" onsubmit="return confirm('Delete lead #<?= (int)$l['id'] ?>?')">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="_back" value="/index.php?tab=leads">
                    <input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
                    <button name="action" value="lead_delete" class="px-2 py-1 border rounded text-red-600 hover:bg-red-50">Delete</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (!$leads): ?>
              <tr><td colspan="6" class="py-4 text-center text-gray-500">No leads yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>

    <?php elseif ($tab === 'files'):
      $files = $pdo->query('SELECT * FROM uploads ORDER BY uploaded_at DESC LIMIT 100')->fetchAll();
    ?>
      <section class="bg-white p-6 rounded shadow space-y-6">
        <h2 class="text-lg font-semibold">Files</h2>
        <form method="POST" action="/actions.php" enctype="multipart/form-data" class="flex items-center gap-3">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="_back" value="/index.php?tab=files">
          <input type="file" name="file" required class="border px-3 py-2 rounded">
          <button name="action" value="upload_file" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Upload</button>
        </form>

        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left text-gray-500 border-b">
                <th class="py-2">ID</th><th>Name</th><th>MIME</th><th>Size</th><th>Uploaded</th><th>Path</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($files as $f): ?>
              <tr class="border-b last:border-0">
                <td class="py-2"><?= (int)$f['id'] ?></td>
                <td><?= e($f['filename']) ?></td>
                <td><?= e($f['mime']) ?></td>
                <td><?= number_format((int)$f['size']/1024, 1) ?> KB</td>
                <td><?= e($f['uploaded_at']) ?></td>
                <td><code><?= e($f['path']) ?></code></td>
              </tr>
              <?php endforeach; ?>
              <?php if (!$files): ?>
              <tr><td colspan="6" class="py-4 text-center text-gray-500">No files uploaded.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>

    <?php elseif ($tab === 'activity'):
      $log = $pdo->query('SELECT * FROM activity ORDER BY created_at DESC, id DESC LIMIT 200')->fetchAll();
    ?>
      <section class="bg-white p-6 rounded shadow">
        <h2 class="text-lg font-semibold mb-4">Activity</h2>
        <ul class="space-y-2 text-sm">
          <?php foreach ($log as $a): ?>
            <li class="border-b last:border-0 pb-2">
              <span class="text-gray-500"><?= e($a['created_at']) ?></span> — 
              <strong><?= e($a['action']) ?></strong>
              <?php $meta = json_decode($a['meta'], true) ?: []; if ($meta): ?>
                <code class="ml-2 text-gray-500"><?= e(json_encode($meta)) ?></code>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
          <?php if (!$log): ?>
            <li class="text-gray-500">No activity yet.</li>
          <?php endif; ?>
        </ul>
      </section>
    <?php endif; ?>
  </main>
</body>
</html>
