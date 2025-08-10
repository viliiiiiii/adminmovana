<?php
$pdo = db();
$p = max(1,(int)($_GET['p'] ?? 1)); $per=20; $off=($p-1)*$per;
$total = (int) ($pdo->query('SELECT COUNT(*) c FROM uploads')->fetch()['c'] ?? 0);
$st=$pdo->prepare("SELECT * FROM uploads ORDER BY uploaded_at DESC, id DESC LIMIT ? OFFSET ?"); $st->execute([$per,$off]); $files=$st->fetchAll();
$pages = max(1,(int)ceil($total/$per));
?>
<section class="bg-white dark:bg-slate-950 p-6 rounded-xl border border-slate-200 dark:border-slate-800 space-y-4">
  <h2 class="text-lg font-semibold">Files</h2>
  <form method="POST" action="/actions.php" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="_back" value="/index.php?page=uploads">
    <input type="file" name="file" required class="border px-3 py-2 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700">
    <button name="action" value="upload_file" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Upload</button>
  </form>

  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="sticky top-0 bg-white dark:bg-slate-950 z-10">
        <tr class="text-left text-slate-500 border-b">
          <th class="py-2">ID</th><th>Name</th><th>MIME</th><th>Size</th><th>Uploaded</th><th>Preview</th><th class="text-right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($files as $f): $isImg = str_starts_with($f['mime'], 'image/'); ?>
        <tr class="border-b">
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
              <input type="hidden" name="_back" value="/index.php?page=uploads&p=<?= $p ?>">
              <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
              <button name="action" value="file_delete" class="px-2 py-1 border rounded text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; if (!$files): ?>
          <tr><td colspan="7" class="py-4 text-center text-slate-500">No files.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="flex items-center justify-between text-sm">
    <div class="text-slate-500">Total: <?= $total ?></div>
    <div class="flex gap-1">
      <a class="px-3 py-1.5 border rounded <?= $p<=1?'opacity-50 pointer-events-none':'' ?>" href="?page=uploads&p=<?= max(1,$p-1) ?>">Prev</a>
      <span class="px-3 py-1.5"><?= $p ?> / <?= $pages ?></span>
      <a class="px-3 py-1.5 border rounded <?= $p>=$pages?'opacity-50 pointer-events-none':'' ?>" href="?page=uploads&p=<?= min($pages,$p+1) ?>">Next</a>
    </div>
  </div>
</section>
