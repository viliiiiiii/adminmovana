<?php
$pdo = db();
$p   = max(1, (int)($_GET['p'] ?? 1));
$per = 20; 
$off = ($p - 1) * $per;

// Helper functions
function format_file_size($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

function format_date($dateString) {
    $date = new DateTime($dateString);
    return $date->format('M j, Y g:i A');
}

$total = (int) ($pdo->query('SELECT COUNT(*) c FROM uploads')->fetch()['c'] ?? 0);
$st = $pdo->prepare("SELECT * FROM uploads ORDER BY uploaded_at DESC, id DESC LIMIT ? OFFSET ?");
$st->execute([$per, $off]);
$files = $st->fetchAll();
$pages = max(1, (int)ceil($total / $per));
?>

<section class="bg-white dark:bg-slate-950 p-6 rounded-xl border border-slate-200 dark:border-slate-800 space-y-4">
  <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <h2 class="text-lg font-semibold">Files</h2>
    <form method="POST" action="/actions.php" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="_back" value="/index.php?page=uploads">
      <input type="file" name="file" required 
             class="border px-3 py-2 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700
                    file:mr-3 file:py-1 file:px-3 file:border-0 file:text-sm file:font-medium
                    file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200
                    dark:file:bg-slate-800 dark:file:text-slate-200 dark:hover:file:bg-slate-700">
      <button name="action" value="upload_file" 
              class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700 transition-colors">
        Upload
      </button>
    </form>
  </div>

  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="sticky top-0 bg-white dark:bg-slate-950 z-10">
        <tr class="text-left text-slate-500 border-b dark:border-slate-800">
          <th class="py-2">ID</th>
          <th>Name</th>
          <th>MIME</th>
          <th>Size</th>
          <th>Uploaded</th>
          <th>Preview</th>
          <th class="text-right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($files as $f): ?>
          <tr class="border-b dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-900/50 transition-colors">
            <td class="py-3"><?= (int)$f['id'] ?></td>
            <td class="max-w-[280px] truncate" title="<?= e($f['filename']) ?>"><?= e($f['filename']) ?></td>
            <td><?= e($f['mime']) ?></td>
            <td><?= format_file_size((int)$f['size']) ?></td>
            <td><?= format_date($f['uploaded_at']) ?></td>
            <td>
              <button type="button" 
                      class="js-preview px-3 py-1.5 rounded-xl border bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900 
                             hover:opacity-90 transition-opacity"
                      data-id="<?= (int)$f['id'] ?>"
                      data-mime="<?= e($f['mime']) ?>"
                      data-name="<?= e($f['filename']) ?>">
                Preview
              </button>
            </td>
            <td class="text-right">
              <form method="POST" action="/actions.php" class="inline" onsubmit="return confirm('Delete file #<?= (int)$f['id'] ?>?')">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="_back" value="/index.php?page=uploads&p=<?= $p ?>">
                <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                <button name="action" value="file_delete" 
                        class="px-2 py-1 border rounded text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors">
                  Delete
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$files): ?>
          <tr>
            <td colspan="7" class="py-4 text-center text-slate-500 dark:text-slate-400">
              No files uploaded yet.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="flex flex-col sm:flex-row items-center justify-between gap-4 text-sm">
    <div class="text-slate-500 dark:text-slate-400">
      Showing <?= $off + 1 ?>-<?= min($off + $per, $total) ?> of <?= $total ?> files
    </div>
    <div class="flex gap-1">
      <a class="px-3 py-1.5 border rounded <?= $p <= 1 ? 'opacity-50 pointer-events-none' : 'hover:bg-slate-100 dark:hover:bg-slate-800' ?>" 
         href="?page=uploads&p=<?= max(1, $p - 1) ?>">
        Prev
      </a>
      <span class="px-3 py-1.5"><?= $p ?> / <?= $pages ?></span>
      <a class="px-3 py-1.5 border rounded <?= $p >= $pages ? 'opacity-50 pointer-events-none' : 'hover:bg-slate-100 dark:hover:bg-slate-800' ?>" 
         href="?page=uploads&p=<?= min($pages, $p + 1) ?>">
        Next
      </a>
    </div>
  </div>
</section>

<!-- Preview Modal -->
<div id="previewModal" class="fixed inset-0 z-50 hidden" aria-modal="true" aria-labelledby="previewTitle">
  <!-- Backdrop -->
  <div class="absolute inset-0 bg-black/50" onclick="closePreview()"></div>

  <!-- Dialog -->
  <div class="absolute inset-0 flex items-center justify-center p-4">
    <div class="w-full max-w-5xl bg-white dark:bg-slate-950 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xl overflow-hidden max-h-[90vh] flex flex-col">
      <!-- Header -->
      <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
        <div class="font-semibold truncate max-w-[50%]" id="previewTitle">Preview</div>
        <div class="flex gap-2">
          <a id="downloadFile" href="#" download
             class="px-3 py-1.5 text-sm rounded border hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Download
          </a>
          <a id="openInNew" href="#" target="_blank"
             class="px-3 py-1.5 text-sm rounded border hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
            </svg>
            Open
          </a>
          <button onclick="closePreview()"
                  class="px-3 py-1.5 text-sm rounded border hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            Close
          </button>
        </div>
      </div>

      <!-- Content -->
      <div class="flex-1 overflow-auto">
        <!-- Image Preview -->
        <div id="imgWrap" class="hidden w-full h-full overflow-auto grid place-items-center bg-slate-50 dark:bg-slate-900 p-4">
          <img id="imgPreview" src="" alt="" class="max-w-full max-h-full object-contain">
        </div>

        <!-- PDF/Text Preview -->
        <div id="frameWrap" class="hidden w-full h-full bg-slate-50 dark:bg-slate-900">
          <iframe id="framePreview" src="" class="w-full h-full" loading="lazy" title="File preview"></iframe>
        </div>

        <!-- Video/Audio Preview -->
        <div id="mediaWrap" class="hidden w-full h-full bg-slate-50 dark:bg-slate-900 grid place-items-center p-4">
          <div class="w-full max-w-2xl">
            <video id="videoPreview" controls class="w-full hidden"></video>
            <audio id="audioPreview" controls class="w-full hidden"></audio>
          </div>
        </div>

        <!-- Unsupported Preview -->
        <div id="fallbackWrap" class="hidden w-full h-full p-6 flex flex-col items-center justify-center text-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          <h3 class="mt-2 font-medium text-slate-700 dark:text-slate-300">Preview not available</h3>
          <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">This file type cannot be previewed.</p>
          <div class="mt-4 flex gap-3">
            <a id="fallbackDownload" href="#" download
               class="px-4 py-2 rounded border hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
              </svg>
              Download
            </a>
            <a id="fallbackOpen" href="#" target="_blank"
               class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700 flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
              </svg>
              Open
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Helper function
function $(selector, context = document) {
  return context.querySelector(selector);
}

// Modal elements
const modal = $('#previewModal');
const titleEl = $('#previewTitle');
const imgWrap = $('#imgWrap'), imgEl = $('#imgPreview');
const frameWrap = $('#frameWrap'), frameEl = $('#framePreview');
const mediaWrap = $('#mediaWrap'), videoEl = $('#videoPreview'), audioEl = $('#audioPreview');
const fallbackWrap = $('#fallbackWrap'), fallbackDownload = $('#fallbackDownload'), fallbackOpen = $('#fallbackOpen');
const openInNew = $('#openInNew'), downloadFile = $('#downloadFile');

// Preview functions
function openPreview(id, mime, name) {
  // Reset all previews
  imgWrap.classList.add('hidden'); imgEl.src = '';
  frameWrap.classList.add('hidden'); frameEl.src = '';
  mediaWrap.classList.add('hidden'); videoEl.src = ''; audioEl.src = '';
  fallbackWrap.classList.add('hidden');
  
  // Set common elements
  const url = 'preview.php?id=' + encodeURIComponent(id);
  const downloadUrl = 'download.php?id=' + encodeURIComponent(id);
  titleEl.textContent = name || ('File #' + id);
  openInNew.href = url;
  downloadFile.href = downloadUrl;
  fallbackDownload.href = downloadUrl;
  fallbackOpen.href = url;

  // Determine preview type
  if (mime.startsWith('image/')) {
    imgEl.src = url;
    imgEl.alt = name || 'Image preview';
    imgWrap.classList.remove('hidden');
  } 
  else if (mime === 'application/pdf' || mime.startsWith('text/')) {
    frameEl.src = url;
    frameWrap.classList.remove('hidden');
  } 
  else if (mime.startsWith('video/')) {
    videoEl.src = url;
    videoEl.classList.remove('hidden');
    mediaWrap.classList.remove('hidden');
  } 
  else if (mime.startsWith('audio/')) {
    audioEl.src = url;
    audioEl.classList.remove('hidden');
    mediaWrap.classList.remove('hidden');
  } 
  else {
    fallbackWrap.classList.remove('hidden');
  }

  // Show modal
  modal.classList.remove('hidden');
  document.body.style.overflow = 'hidden';
  document.addEventListener('keydown', handleEscapeKey);
}

function closePreview() {
  modal.classList.add('hidden');
  // Clear sources to stop media rendering
  imgEl.src = ''; 
  frameEl.src = '';
  videoEl.src = ''; videoEl.classList.add('hidden');
  audioEl.src = ''; audioEl.classList.add('hidden');
  document.body.style.overflow = '';
  document.removeEventListener('keydown', handleEscapeKey);
}

function handleEscapeKey(e) {
  if (e.key === 'Escape') closePreview();
}

// Event delegation for preview buttons
document.addEventListener('click', (e) => {
  const btn = e.target.closest('.js-preview');
  if (!btn) return;
  
  const id = btn.dataset.id;
  const mime = btn.dataset.mime || '';
  const name = btn.dataset.name || '';
  openPreview(id, mime, name);
});

// Close modal when clicking outside content
modal.addEventListener('click', (e) => {
  if (e.target === modal) closePreview();
});
</script>