<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/flash.php';
require_login();

$page = $_GET['page'] ?? 'dashboard';
$allowed = ['dashboard','activity','leads','settings','uploads','companies','finance','profile'];
if (!in_array($page, $allowed, true)) $page = 'dashboard';

include __DIR__ . '/header.php';
include __DIR__ . '/views/' . $page . '.php';
include __DIR__ . '/footer.php';
