<?php
function flash_add(string $type, string $msg): void {
  $_SESSION['flash'][] = ['type'=>$type, 'msg'=>$msg];
}
function flash_getall(): array {
  $all = $_SESSION['flash'] ?? [];
  unset($_SESSION['flash']);
  return $all;
}
