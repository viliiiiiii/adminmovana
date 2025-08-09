<?php
require __DIR__ . '/config.php';
require_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Movana Admin Dashboard</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
  <header class="bg-white border-b shadow-sm">
    <div class="max-w-5xl mx-auto flex items-center justify-between p-4">
      <h1 class="text-xl font-bold">Movana Admin Dashboard</h1>
      <form method="POST" action="/logout.php">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <button type="submit" class="px-3 py-1.5 rounded bg-red-600 text-white hover:bg-red-700">
          Logout
        </button>
      </form>
    </div>
  </header>

  <main class="max-w-5xl mx-auto p-6 space-y-6">
    <section class="bg-white p-6 rounded shadow">
      <h2 class="text-lg font-semibold mb-4">Welcome, <?= e($_SESSION['admin_user']) ?>!</h2>
      <p class="text-gray-600">You are now logged in to the secure Movana admin area.</p>
    </section>

    <section class="grid md:grid-cols-3 gap-6">
      <div class="bg-white p-5 rounded shadow">
        <div class="text-gray-500 text-sm">Status</div>
        <div class="text-2xl font-bold mt-2">All systems go ✅</div>
      </div>
      <div class="bg-white p-5 rounded shadow">
        <div class="text-gray-500 text-sm">New Leads (7d)</div>
        <div class="text-2xl font-bold mt-2">12</div>
      </div>
      <div class="bg-white p-5 rounded shadow">
        <div class="text-gray-500 text-sm">Tasks</div>
        <div class="text-2xl font-bold mt-2">3 pending</div>
      </div>
    </section>
  </main>
</body>
</html>
