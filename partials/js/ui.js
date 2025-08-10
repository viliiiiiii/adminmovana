/* Drawer (mobile sidebar) */
window.drawerOpen = () => {
  const el = document.getElementById('drawer');
  if (el) {
    el.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
  }
};
window.drawerClose = () => {
  const el = document.getElementById('drawer');
  if (el) {
    el.classList.add('hidden');
    document.body.style.overflow = '';
  }
};

/* File preview modal */
document.addEventListener('click', (e) => {
  const btn = e.target.closest('.js-preview');
  if (!btn) return;

  const id = btn.dataset.id;
  const mime = btn.dataset.mime || '';
  const name = btn.dataset.name || '';
  openPreview(id, mime, name);
});

window.openPreview = (id, mime, name) => {
  const url = 'preview.php?id=' + encodeURIComponent(id);
  document.getElementById('previewTitle').textContent = name || ('File #' + id);
  document.getElementById('openInNew').href = url;

  const imgWrap = document.getElementById('imgWrap');
  const frameWrap = document.getElementById('frameWrap');
  const fbWrap = document.getElementById('fallbackWrap');

  imgWrap.classList.add('hidden');
  frameWrap.classList.add('hidden');
  fbWrap.classList.add('hidden');

  if (mime.startsWith('image/')) {
    document.getElementById('imgPreview').src = url;
    imgWrap.classList.remove('hidden');
  } else if (mime === 'application/pdf' || mime.startsWith('text/')) {
    document.getElementById('framePreview').src = url;
    frameWrap.classList.remove('hidden');
  } else {
    document.getElementById('fallbackLink').href = url;
    fbWrap.classList.remove('hidden');
  }

  document.getElementById('previewModal').classList.remove('hidden');
  document.body.style.overflow = 'hidden';
};

window.closePreview = () => {
  document.getElementById('imgPreview').src = '';
  document.getElementById('framePreview').src = '';
  document.getElementById('previewModal').classList.add('hidden');
  document.body.style.overflow = '';
};

/* ESC key closes modal */
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    closePreview();
  }
});
