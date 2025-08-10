<?php
if (!function_exists('stat_card')) {
  function stat_card(string $title, string $value, string $accent='from-indigo-500 to-indigo-700'): void { ?>
    <div class="rounded-2xl p-1 bg-gradient-to-br <?= $accent ?> shadow-lg">
      <div class="rounded-2xl p-5 bg-white/90 dark:bg-slate-900/80">
        <div class="text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400"><?= e($title) ?></div>
        <div class="mt-1 text-3xl font-extrabold"><?= e($value) ?></div>
      </div>
    </div>
<?php } }

if (!function_exists('table_shell')) {
  function table_shell(array $thead, callable $tbody): void { ?>
    <div class="rounded-2xl border border-slate-200/60 dark:border-slate-800/60 overflow-hidden bg-white/80 dark:bg-slate-950/50">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-slate-500 border-b border-slate-200/70 dark:border-slate-800">
            <?php foreach($thead as $th): ?><th class="py-3 px-4"><?= e($th) ?></th><?php endforeach; ?>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200/70 dark:divide-slate-800">
          <?php $tbody(); ?>
        </tbody>
      </table>
    </div>
<?php } }

if (!function_exists('chip')) {
  function chip(string $text, string $class=''): void { ?>
    <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs <?= $class ?>"><?= e($text) ?></span>
<?php } }
