<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_login();

$page = $_GET['page'] ?? 'insights';
$map = [
  'insights'  => __DIR__ . '/views/insights.php',
  'leads'     => __DIR__ . '/views/leads.php',
  'companies' => __DIR__ . '/views/companies.php',
  'uploads'   => __DIR__ . '/views/uploads.php',
  'finance'   => __DIR__ . '/views/finance.php',
  'activity'  => __DIR__ . '/views/activity.php',
  'settings'  => __DIR__ . '/views/settings.php',
];

if (isset($map[$page])) require $map[$page];
else { http_response_code(404); echo 'Not found'; }
