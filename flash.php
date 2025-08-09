<?php
// flash.php — tiny flash messaging
function flash_add(string $type, string $msg): void {
  $_SESSION['_flash'][] = ['type' => $type, 'msg' => $msg];
}
function flash_getall(): array {
  $all = $_SESSION['_flash'] ?? [];
  unset($_SESSION['_flash']);
  return $all;
}
