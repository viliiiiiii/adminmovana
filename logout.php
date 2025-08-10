<?php
require_once __DIR__ . '/config.php';

// Only allow POST logout with valid CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check($_POST['csrf'] ?? '')) {
    logout_admin();
}

// Redirect to login page whether CSRF passed or not
header('Location: /login.php');
exit;
