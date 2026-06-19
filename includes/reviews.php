<?php
declare(strict_types=1);

function reviews_storage_path(): string {
  return __DIR__ . '/../storage/reviews.json';
}

function reviews_new_id(): string {
  if (function_exists('random_bytes')) return bin2hex(random_bytes(6)) . '-' . (string)time();
  return (string)mt_rand(100000, 999999) . '-' . (string)time();
}

function reviews_cut(string $value, int $limit): string {
  $value = trim($value);
  if (function_exists('mb_strlen') && function_exists('mb_substr')) {
    return mb_strlen($value) > $limit ? mb_substr($value, 0, $limit) : $value;
  }
  return strlen($value) > $limit ? substr($value, 0, $limit) : $value;
}

function reviews_normalize_item(array $it): array {
  $it['id'] = (string)($it['id'] ?? reviews_new_id());
  $it['name'] = reviews_cut((string)($it['name'] ?? 'Клиент'), 60);
  $it['rating'] = max(1, min(5, (int)($it['rating'] ?? 5)));
  $it['text'] = reviews_cut((string)($it['text'] ?? ''), 700);
  $it['reply'] = reviews_cut((string)($it['reply'] ?? ''), 700);
  $status = (string)($it['status'] ?? 'done');
  $it['status'] = in_array($status, ['new','in_work','done'], true) ? $status : 'new';
  $it['created_at'] = (string)($it['created_at'] ?? date('Y-m-d H:i:s'));
  $it['updated_at'] = (string)($it['updated_at'] ?? $it['created_at']);
  return $it;
}

function reviews_load(): array {
  $path = reviews_storage_path();
  if (!file_exists($path)) return [];
  $raw = file_get_contents($path);
  if ($raw === false) return [];
  $data = json_decode($raw, true);
  if (!is_array($data)) return [];

  $items = [];
  foreach ($data as $it) {
    if (is_array($it)) $items[] = reviews_normalize_item($it);
  }
  usort($items, function($a, $b) {
    return strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? ''));
  });
  return $items;
}

function reviews_save(array $items): bool {
  $path = reviews_storage_path();
  $dir = dirname($path);
  if (!is_dir($dir)) @mkdir($dir, 0775, true);
  $items = array_values(array_map('reviews_normalize_item', $items));
  $json = json_encode($items, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
  if ($json === false) return false;

  $fp = fopen($path, 'c+');
  if (!$fp) return false;
  try {
    if (!flock($fp, LOCK_EX)) return false;
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, $json);
    fflush($fp);
    flock($fp, LOCK_UN);
    return true;
  } finally {
    fclose($fp);
  }
}

function reviews_add($nameOrData, ?int $rating = null, ?string $text = null): bool {
  if (is_array($nameOrData)) {
    $data = $nameOrData;
  } else {
    $data = [
      'name' => (string)$nameOrData,
      'rating' => (int)($rating ?? 5),
      'text' => (string)($text ?? ''),
      'reply' => '',
      'status' => 'new',
    ];
  }
  $data['id'] = $data['id'] ?? reviews_new_id();
  $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
  $data['updated_at'] = date('Y-m-d H:i:s');

  $items = reviews_load();
  $items[] = reviews_normalize_item($data);
  $items = array_slice($items, -400);
  return reviews_save($items);
}

function reviews_update(string $id, array $fields): bool {
  $items = reviews_load();
  $changed = false;
  foreach ($items as &$it) {
    if ((string)$it['id'] === (string)$id) {
      foreach (['name','rating','text','reply','status'] as $k) {
        if (array_key_exists($k, $fields)) $it[$k] = $fields[$k];
      }
      $it['updated_at'] = date('Y-m-d H:i:s');
      $it = reviews_normalize_item($it);
      $changed = true;
      break;
    }
  }
  unset($it);
  return $changed ? reviews_save($items) : false;
}

function reviews_delete(string $id): bool {
  $items = reviews_load();
  $before = count($items);
  $items = array_values(array_filter($items, function($it) use ($id) {
    return (string)($it['id'] ?? '') !== (string)$id;
  }));
  return count($items) !== $before ? reviews_save($items) : false;
}

function reviews_public(): array {
  return array_values(array_filter(reviews_load(), function($it) {
    return ($it['status'] ?? '') === 'done';
  }));
}

function reviews_all($status = null): array {
  $all = reviews_load();
  if (!$status || $status === 'all') return $all;
  return array_values(array_filter($all, function($it) use ($status) {
    return ($it['status'] ?? 'new') === $status;
  }));
}
