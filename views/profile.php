<section class="bg-white dark:bg-slate-950 p-6 rounded-xl border border-slate-200 dark:border-slate-800 space-y-4 max-w-lg">
  <h2 class="text-lg font-semibold">Profile</h2>
  <div class="text-sm text-slate-600 dark:text-slate-300">Signed in as <strong><?= e($_SESSION['admin_user']) ?></strong></div>
  <form method="POST" action="/actions.php" class="space-y-3">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="_back" value="/index.php?page=profile">
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
