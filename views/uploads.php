<?php
$pdo = db();
$p   = max(1,(int)($_GET['p'] ?? 1)); $per = 20; $off = ($p-1)*$per;
$total = (int) ($pdo->query('SELECT COUNT(*) c FROM uploads')->fetch()['c'] ?? 0);
$st=$pdo->prepare("SELECT * FROM uploads ORDER BY uploaded_at DESC, id DESC LIMIT ? OFFSET ?");
$st->execute([$per,$off]); $files=$st->fetchAll();
$pages = max(1,(int)ceil($total/$per));
?>
<section class="bg-white dark:bg-slate-950 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-4">
  <div class="flex items-center justify-between">
    <h2 class="text-lg font-semibold">Files</h2>
    <form method="POST" action="/actions.php" enctype="multipart/form-data" class="flex items-center gap-3">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="_back" value="/index.php?page=uploads">
      <input type="file" name="file" required class="input w-64">
      <button name="action" value="upload_file" class="btn btn-primary">Upload</button>
    </form>
  </div>

  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="sticky top-0 bg-white dark:bg-slate-950 z-10">
        <tr class="text-left text-slate-500 border-b dark:border-slate-800">
          <th class="py-2">ID</th><th>Name</th><th>MIME</th><th>Size</th><th>Uploaded</th><th>Preview</th><th class="text-right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($files as $f): ?>
          <tr class="border-b dark:border-slate-800">
            <td class="py-2"><?= (int)$f['id'] ?></td>
            <td class="max-w-[280px] truncate" title="<?= e($f['filename']) ?>"><?= e($f['filename']) ?></td>
            <td><?= e($f['mime']) ?></td>
            <td><?= number_format((int)$f['size']/1024,1) ?> KB</td>
            <td><?= e($f['uploaded_at']) ?></td>
            <td>
              <button type="button" class="js-preview px-3 py-1.5 rounded-lg border hover:bg-slate-50 dark:hover:bg-slate-800"
                data-id="<?= (int)$f['id'] ?>" data-mime="<?= e($f['mime']) ?>" data-name="<?= e($f['filename']) ?>">Preview</button>
            </td>
            <td class="text-right">
              <form method="POST" action="/actions.php" class="inline" onsubmit="return confirm('Delete file #<?= (int)$f['id'] ?>?')">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="_back" value="/index.php?page=uploads&p=<?= $p ?>">
                <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                <button name="action" value="file_delete" class="px-2 py-1 border rounded-lg text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; if (!$files): ?>
          <tr><td colspan="7" class="py-4 text-center text-slate-500 dark:text-slate-400">No files.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="flex items-center justify-between text-sm">
    <div class="text-slate-500 dark:text-slate-400">Total: <?= $total ?></div>
    <div class="flex gap-1">
      <a class="px-3 py-1.5 border rounded-lg <?= $p<=1?'opacity-50 pointer-events-none':'' ?>" href="?page=uploads&p=<?= max(1,$p-1) ?>">Prev</a>
      <span class="px-3 py-1.5"><?= $p ?> / <?= $pages ?></span>
      <a class="px-3 py-1.5 border rounded-lg <?= $p>=$pages?'opacity-50 pointer-events-none':'' ?>" href="?page=uploads&p=<?= min($pages,$p+1) ?>">Next</a>
    </div>
  </div>
</section>

<!-- Modal -->
<div id="previewModal" class="fixed inset-0 z-50 hidden">
  <div class="absolute inset-0 bg-black/50" onclick="closePreview()"></div>
  <div class="absolute inset-0 flex items-center justify-center p-4">
    <div class="w-full max-w-5xl bg-white dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xl overflow-hidden">
      <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
        <div class="font-semibold text-sm" id="previewTitle">Preview</div>
        <div class="space-x-2">
          <a id="openInNew" href="#" target="_blank" class="px-3 py-1.5 text-sm rounded-lg border hover:bg-slate-50 dark:hover:bg-slate-800">Open in new tab</a>
          <button class="px-3 py-1.5 text-sm rounded-lg border hover:bg-slate-50 dark:hover:bg-slate-800" onclick="closePreview()">Close</button>
        </div>
      </div>
      <div class="p-0">
        <div id="imgWrap" class="hidden w-full max-h-[80vh] overflow-auto grid place-items-center bg-slate-50 dark:bg-slate-900">
          <img id="imgPreview" src="" alt="" class="max-w-full max-h-[80vh] object-contain">
        </div>
        <div id="frameWrap" class="hidden bg-slate-50 dark:bg-slate-900">
          <iframe id="framePreview" src="" class="w-full h-[80vh]" loading="lazy" title="File preview"></iframe>
        </div>
        <div id="fallbackWrap" class="hidden p-6">
          <p class="text-sm text-slate-600 dark:text-slate-300">Can’t preview this file type.</p>
          <a id="fallbackLink" href="#" target="_blank" class="mt-3 inline-block px-3 py-2 rounded-lg border hover:bg-slate-50 dark:hover:bg-slate-800">Open file</a>
        </div>
      </div>
    </div>
  </div>
</div>
