<?php
require __DIR__ . '/config.php';
require_login();

// Simulated data for demonstration
$stats = [
    'status' => 'All systems go ✅',
    'new_leads' => 12,
    'pending_tasks' => 3,
    'total_users' => 456,
    'revenue' => '$12,345',
    'active_sessions' => 28,
];

$recent_leads = [
    ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'date' => '2025-08-09', 'status' => 'New'],
    ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com', 'date' => '2025-08-08', 'status' => 'Contacted'],
    ['id' => 3, 'name' => 'Bob Johnson', 'email' => 'bob@example.com', 'date' => '2025-08-07', 'status' => 'New'],
    ['id' => 4, 'name' => 'Alice Brown', 'email' => 'alice@example.com', 'date' => '2025-08-06', 'status' => 'Closed'],
    ['id' => 5, 'name' => 'Charlie Davis', 'email' => 'charlie@example.com', 'date' => '2025-08-05', 'status' => 'New'],
];

$tasks = [
    ['id' => 1, 'title' => 'Follow up with lead #123', 'priority' => 'High', 'due' => 'Today'],
    ['id' => 2, 'title' => 'Update inventory', 'priority' => 'Medium', 'due' => 'Tomorrow'],
    ['id' => 3, 'title' => 'Review team performance', 'priority' => 'Low', 'due' => 'This Week'],
];

$activity_log = [
    ['time' => '10:45 AM', 'user' => 'Admin', 'action' => 'Logged in'],
    ['time' => '10:30 AM', 'user' => 'System', 'action' => 'Backup completed'],
    ['time' => '09:15 AM', 'user' => 'Jane Smith', 'action' => 'Submitted new lead'],
    ['time' => '08:50 AM', 'user' => 'Admin', 'action' => 'Updated settings'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movana Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen font-sans antialiased">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-white border-r shadow-md flex flex-col">
            <div class="p-4 border-b">
                <h1 class="text-xl font-bold text-gray-800">Movana Admin</h1>
            </div>
            <nav class="flex-1 p-4 space-y-2">
                <a href="#" class="flex items-center px-4 py-2 text-gray-700 bg-gray-100 rounded-lg">
                    <i class="fas fa-home mr-3"></i> Dashboard
                </a>
                <a href="#" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg">
                    <i class="fas fa-users mr-3"></i> Users
                </a>
                <a href="#" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg">
                    <i class="fas fa-chart-line mr-3"></i> Analytics
                </a>
                <a href="#" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg">
                    <i class="fas fa-tasks mr-3"></i> Tasks
                </a>
                <a href="#" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg">
                    <i class="fas fa-cog mr-3"></i> Settings
                </a>
            </nav>
            <div class="p-4 border-t">
                <form method="POST" action="/logout.php" class="flex">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <button type="submit" class="flex items-center px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg w-full">
                        <i class="fas fa-sign-out-alt mr-3"></i> Logout
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto">
            <header class="bg-white border-b shadow-sm p-4">
                <div class="max-w-7xl mx-auto flex items-center justify-between">
                    <h2 class="text-xl font-semibold">Dashboard Overview</h2>
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <button class="p-2 text-gray-600 hover:text-gray-800">
                                <i class="fas fa-bell"></i>
                                <span class="absolute top-0 right-0 bg-red-500 text-white text-xs rounded-full px-1">3</span>
                            </button>
                        </div>
                        <div class="text-gray-600">Welcome, <?= e($_SESSION['admin_user']) ?>!</div>
                    </div>
                </div>
            </header>

            <div class="max-w-7xl mx-auto p-6 space-y-6">
                <!-- Stats Cards -->
                <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6">
                    <div class="bg-white p-5 rounded-lg shadow">
                        <div class="text-gray-500 text-sm">Status</div>
                        <div class="text-xl font-bold mt-2"><?= $stats['status'] ?></div>
                    </div>
                    <div class="bg-white p-5 rounded-lg shadow">
                        <div class="text-gray-500 text-sm">New Leads (7d)</div>
                        <div class="text-xl font-bold mt-2"><?= $stats['new_leads'] ?></div>
                    </div>
                    <div class="bg-white p-5 rounded-lg shadow">
                        <div class="text-gray-500 text-sm">Pending Tasks</div>
                        <div class="text-xl font-bold mt-2"><?= $stats['pending_tasks'] ?></div>
                    </div>
                    <div class="bg-white p-5 rounded-lg shadow">
                        <div class="text-gray-500 text-sm">Total Users</div>
                        <div class="text-xl font-bold mt-2"><?= $stats['total_users'] ?></div>
                    </div>
                    <div class="bg-white p-5 rounded-lg shadow">
                        <div class="text-gray-500 text-sm">Revenue (MTD)</div>
                        <div class="text-xl font-bold mt-2"><?= $stats['revenue'] ?></div>
                    </div>
                    <div class="bg-white p-5 rounded-lg shadow">
                        <div class="text-gray-500 text-sm">Active Sessions</div>
                        <div class="text-xl font-bold mt-2"><?= $stats['active_sessions'] ?></div>
                    </div>
                </section>

                <!-- Charts Section -->
                <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-lg font-semibold mb-4">Leads Over Time</h3>
                        <canvas id="leadsChart" height="200"></canvas>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-lg font-semibold mb-4">Task Distribution</h3>
                        <canvas id="tasksChart" height="200"></canvas>
                    </div>
                </section>

                <!-- Recent Leads Table -->
                <section class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-semibold mb-4">Recent Leads</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($recent_leads as $lead): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm"><?= $lead['id'] ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm"><?= $lead['name'] ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm"><?= $lead['email'] ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm"><?= $lead['date'] ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="px-2 py-1 rounded-full text-xs <?= $lead['status'] === 'New' ? 'bg-yellow-100 text-yellow-800' : ($lead['status'] === 'Contacted' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800') ?>">
                                            <?= $lead['status'] ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <button class="text-blue-600 hover:text-blue-800">View</button>
                                        <button class="ml-2 text-red-600 hover:text-red-800">Delete</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Tasks and Activity Log -->
                <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-lg font-semibold mb-4">Pending Tasks</h3>
                        <ul class="space-y-4">
                            <?php foreach ($tasks as $task): ?>
                            <li class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div>
                                    <div class="font-medium"><?= $task['title'] ?></div>
                                    <div class="text-sm text-gray-500">Due: <?= $task['due'] ?></div>
                                </div>
                                <span class="px-2 py-1 rounded-full text-xs <?= $task['priority'] === 'High' ? 'bg-red-100 text-red-800' : ($task['priority'] === 'Medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') ?>">
                                    <?= $task['priority'] ?>
                                </span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-lg font-semibold mb-4">Recent Activity</h3>
                        <ul class="space-y-4">
                            <?php foreach ($activity_log as $log): ?>
                            <li class="flex items-center p-4 bg-gray-50 rounded-lg">
                                <i class="fas fa-clock mr-3 text-gray-400"></i>
                                <div>
                                    <div class="font-medium"><?= $log['action'] ?></div>
                                    <div class="text-sm text-gray-500">By <?= $log['user'] ?> at <?= $log['time'] ?></div>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script>
        // Leads Chart
        const leadsCtx = document.getElementById('leadsChart').getContext('2d');
        new Chart(leadsCtx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'New Leads',
                    data: [5, 8, 3, 7, 2, 9, 4],
                    borderColor: 'rgb(59, 130, 246)',
                    tension: 0.4,
                    fill: true,
                    backgroundColor: 'rgba(59, 130, 246, 0.1)'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Tasks Chart
        const tasksCtx = document.getElementById('tasksChart').getContext('2d');
        new Chart(tasksCtx, {
            type: 'doughnut',
            data: {
                labels: ['High', 'Medium', 'Low'],
                datasets: [{
                    data: [1, 1, 1],
                    backgroundColor: ['rgb(239, 68, 68)', 'rgb(245, 158, 11)', 'rgb(34, 197, 94)']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'right' }
                }
            }
        });
    </script>
</body>
</html>