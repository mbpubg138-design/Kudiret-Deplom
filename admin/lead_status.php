<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/db.php';
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
if (empty($_SESSION['admin'])) { header('Location: login.php'); exit; }

$id = (int)($_POST['id'] ?? 0);
$status = (string)($_POST['status'] ?? 'new');
$allowed = ['new','in_work','done','cancelled'];
if ($id > 0 && in_array($status, $allowed, true)) {
  $st = db()->prepare('UPDATE leads SET status=:s WHERE id=:id');
  $st->execute([':s'=>$status, ':id'=>$id]);
}
header('Location: index.php');
