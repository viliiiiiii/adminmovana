<?php $spotsLeft = (int) setting_get('spots_left', 3); ?>
<section class="bg-white dark:bg-slate-950 p-6 rounded-xl border border-slate-200 dark:border-slate-800 space-y-4 max-w-lg">
  <h2 class="text-lg font-semibold">Settings</h2>
  <form method="POST" action="/actions.php" class="space-y-3">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="_back" value="/index.php?page=settings">
    <label class="block">
      <span class="text-sm text-slate-600 dark:text-slate-300">Spots Left</span>
      <input type="number" min="0" max="99" name="spots_left" value="<?= $spotsLeft ?>" class="mt-1 w-full border px-3 py-2 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700">
    </label>
    <button name="action" value="settings_save" class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700">Save</button>
  </form>
</section>
