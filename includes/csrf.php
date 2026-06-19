<?php
declare(strict_types=1);

function csrf_start(): void {
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
  }
}
function csrf_token(): string {
  csrf_start();
  return $_SESSION['csrf'];
}
function csrf_verify(string $token): bool {
  csrf_start();
  return hash_equals($_SESSION['csrf'] ?? '', $token);
}
