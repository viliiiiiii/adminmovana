<?php
require_once __DIR__ . '/config.php';
require_login();

// Navigation configuration
$nav = [
    'dashboard' => [
        'label' => 'Dashboard',
        'icon' => 'M10 3h4a2 2 0 012 2v3M7 21h10a2 2 0 002-2V8l-6-5H9a2 2 0 00-2 2v3'
    ],
    'leads' => [
        'label' => 'Leads',
        'icon' => 'M16 12a4 4 0 10-8 0 4 4 0 008 0z M12 14v7'
    ],
    'uploads' => [
        'label' => 'Files',
        'icon' => 'M3 7l9-4 9 4-9 4-9-4z M21 10l-9 4-9-4 M3 17l9 4 9-4'
    ],
    'companies' => [
        'label' => 'Companies',
        'icon' => 'M3 10h18M7 21V3h10v18'
    ],
    'finance' => [
        'label' => 'Financials',
        'icon' => 'M3 3v18h18 M7 15l3-3 4 4 5-5'
    ],
    'activity' => [
        'label' => 'Activity',
        'icon' => 'M12 8v4l3 3 M12 22a10 10 0 100-20 10 10 0 000 20z'
    ],
    'settings' => [
        'label' => 'Settings',
        'icon' => 'M10.325 4.317a1 1 0 011.35-.936l7.794 2.598a1 1 0 01.651.95v7.142a1 1 0 01-.651.95l-7.794 2.598a1 1 0 01-1.35-.936V4.317z'
    ],
    'profile' => [
        'label' => 'Profile',
        'icon' => 'M12 14c3.866 0 7 1.79 7 4v2H5v-2c0-2.21 3.134-4 7-4zm0-2a4 4 0 100-8 4 4 0 000 8z'
    ],
];

$currentPage = $_GET['page'] ?? 'dashboard';
?>

<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($nav[$currentPage]['label'] ?? 'Admin') ?> | Movana Admin</title>
    
    <!-- Preload Tailwind CSS -->
    <link rel="preload" href="https://cdn.tailwindcss.com" as="script">
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Theme management script -->
    <script>
    (function() {
        // Check for saved theme preference or system preference
        const savedTheme = localStorage.getItem('theme');
        const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        // Apply theme
        if (savedTheme === 'dark' || (!savedTheme && systemPrefersDark)) {
            document.documentElement.classList.add('dark');
        }
        
        // Store theme preference
        function toggleTheme() {
            const htmlEl = document.documentElement;
            const isDark = htmlEl.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            
            // Dispatch event for other components
            document.dispatchEvent(new CustomEvent('themeChanged', {
                detail: { theme: isDark ? 'dark' : 'light' }
            }));
        }
        
        // Expose to global scope
        window.toggleTheme = toggleTheme;
    })();
    </script>
    
    <style>
        .glass {
            backdrop-filter: blur(8px);
            background: linear-gradient(
                180deg,
                rgba(255, 255, 255, 0.75),
                rgba(255, 255, 255, 0.55)
            );
        }
        
        .dark .glass {
            background: linear-gradient(
                180deg,
                rgba(2, 6, 23, 0.75),
                rgba(2, 6, 23, 0.55)
            );
        }
        
        .chip {
            @apply inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs border;
        }
        
        /* Smooth transitions for theme changes */
        html {
            @apply transition-colors duration-200;
        }
        
        /* Focus styles for accessibility */
        [role="button"]:focus,
        button:focus,
        a:focus {
            @apply outline-none ring-2 ring-indigo-500 ring-offset-2;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-950 dark:to-slate-900 text-slate-900 dark:text-slate-100 min-h-screen flex flex-col">

<div class="flex flex-1 min-h-0">
    <!-- Sidebar Navigation -->
    <aside class="hidden md:flex w-72 flex-col border-r border-slate-200/60 dark:border-slate-800/60 glass">
        <div class="p-5 border-b border-slate-200/60 dark:border-slate-800/60">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white font-bold">
                    M
                </div>
                <div>
                    <div class="text-lg font-bold tracking-tight">Movana Admin</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 truncate">
                        <?= e($_SESSION['admin_user']) ?>
                    </div>
                </div>
            </div>
        </div>
        
        <nav class="flex-1 p-3 space-y-1 overflow-y-auto">
            <?php foreach ($nav as $key => $item): ?>
                <?php $isActive = $currentPage === $key; ?>
                <a href="?page=<?= $key ?>"
                   class="group flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200
                          <?= $isActive ? 'bg-indigo-600 text-white shadow-sm' : 'hover:bg-slate-200/60 dark:hover:bg-slate-800/60 text-slate-700 dark:text-slate-300' ?>"
                   aria-current="<?= $isActive ? 'page' : 'false' ?>">
                    <svg viewBox="0 0 24 24" 
                         class="h-5 w-5 flex-shrink-0 <?= $isActive ? 'text-white' : 'text-slate-500 group-hover:text-slate-700 dark:group-hover:text-slate-200' ?>" 
                         fill="none" 
                         stroke="currentColor" 
                         stroke-width="2">
                        <path d="<?= e($item['icon']) ?>"/>
                    </svg>
                    <span class="font-medium truncate"><?= e($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        
        <div class="p-3 border-t border-slate-200/60 dark:border-slate-800/60">
            <form method="POST" action="/logout.php">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <button type="submit"
                        class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-lg border
                               hover:bg-red-600/10 text-red-600 dark:text-red-400 transition-colors
                               focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-h-0">
        <!-- Top Navigation Bar -->
        <header class="sticky top-0 z-20 glass border-b border-slate-200/60 dark:border-slate-800/60">
            <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
                <!-- Mobile Navigation -->
                <div class="flex items-center gap-3 md:hidden">
                    <select class="border rounded-lg px-3 py-2 bg-white dark:bg-slate-900 focus:ring-2 focus:ring-indigo-500"
                            onchange="location.href='?page='+this.value"
                            aria-label="Navigation menu">
                        <?php foreach ($nav as $key => $item): ?>
                            <option value="<?= $key ?>" <?= $currentPage === $key ? 'selected' : '' ?>>
                                <?= e($item['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Page Title -->
                <h1 class="font-semibold tracking-tight text-slate-800 dark:text-slate-100 capitalize text-lg">
                    <?= e($nav[$currentPage]['label'] ?? 'Admin') ?>
                </h1>
                
                <!-- Theme Toggle -->
                <div class="flex items-center gap-3">
                    <button onclick="toggleTheme()"
                            class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 
                                   hover:bg-slate-100 dark:hover:bg-slate-800 text-sm flex items-center gap-2
                                   transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                        <span>Toggle Theme</span>
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto w-full p-4 md:p-8 space-y-8 flex-1 overflow-auto">
            <!-- Flash Messages -->
            <?php if (!empty($_SESSION['flash'])): ?>
                <?php $flashMessages = $_SESSION['flash']; unset($_SESSION['flash']); ?>
                <div class="space-y-3">
                    <?php foreach ($flashMessages as $message): ?>
                        <div role="alert"
                             class="px-4 py-3 rounded-xl border flex items-start gap-3
                                    <?= $message['type'] === 'error'
                                        ? 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/25 dark:text-red-100 dark:border-red-800'
                                        : 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/25 dark:text-emerald-100 dark:border-emerald-800' ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="<?= $message['type'] === 'error' 
                                          ? 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
                                          : 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' ?>" />
                            </svg>
                            <div><?= e($message['msg']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>