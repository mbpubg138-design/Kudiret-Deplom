<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

function h(?string $value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function app_status_label(string $status): string {
  return ['new'=>'Новая','in_work'=>'В работе','done'=>'Завершена','cancelled'=>'Отменена'][$status] ?? $status;
}

function app_status_class(string $status): string {
  return preg_replace('/[^a-z_]/', '', $status) ?: 'new';
}

function app_master_public_array(array $row): array {
  return [
    'id' => (int)$row['id'],
    'name' => (string)$row['name'],
    'photo' => (string)($row['photo'] ?: 'assets/img/masters/master-1.jpg'),
    'exp' => 'Опыт: ' . (string)$row['experience'],
    'experience' => (string)$row['experience'],
    'work' => (string)($row['work'] ?: $row['specialization']),
    'specialization' => (string)$row['specialization'],
    'area' => (string)($row['area'] ?: 'Алматы'),
    'brands' => (string)($row['brands'] ?: 'По популярным брендам'),
    'description' => (string)($row['description'] ?: ''),
    'phone' => (string)($row['phone'] ?: ''),
    'email' => (string)($row['email'] ?: ''),
  ];
}


function app_default_public_masters(): array {
  return [
    ['id'=>1,'name'=>'Сергей Иванов','photo'=>'assets/img/masters/master-1.jpg','exp'=>'Опыт: 6 лет','experience'=>'6 лет','work'=>'Насосы, ремни, подшипники, люки, клапаны','specialization'=>'Мастер по стиральным машинам','area'=>'Алмалинский, Бостандыкский','brands'=>'LG, Samsung, Bosch, Indesit','description'=>'Специалист по диагностике стиральных машин и замене основных узлов.','phone'=>'','email'=>''],
    ['id'=>2,'name'=>'Алексей Морозов','photo'=>'assets/img/masters/master-2.jpg','exp'=>'Опыт: 8 лет','experience'=>'8 лет','work'=>'Компрессоры, фреон, датчики, терморегуляторы','specialization'=>'Мастер по холодильникам','area'=>'Ауэзовский, Наурызбайский','brands'=>'Atlant, Beko, Haier, Electrolux','description'=>'Работает с холодильниками, утечками фреона и температурными датчиками.','phone'=>'','email'=>''],
    ['id'=>3,'name'=>'Данияр Касымов','photo'=>'assets/img/masters/master-3.jpg','exp'=>'Опыт: 5 лет','experience'=>'5 лет','work'=>'Платы, магнетроны, питание, кнопки, дверцы','specialization'=>'Мастер по микроволновкам','area'=>'Медеуский, Жетысуский','brands'=>'Panasonic, Samsung, LG, Midea','description'=>'Проводит ремонт микроволновых печей, блоков питания и дверных механизмов.','phone'=>'','email'=>''],
    ['id'=>4,'name'=>'Руслан Ахметов','photo'=>'assets/img/masters/master-4.jpg','exp'=>'Опыт: 7 лет','experience'=>'7 лет','work'=>'Нагрев, конфорки, термостаты, электрика','specialization'=>'Мастер по плитам и духовкам','area'=>'Турксибский, Алатауский','brands'=>'Bosch, Hansa, Gorenje, Siemens','description'=>'Занимается электрическими плитами, духовыми шкафами и нагревательными элементами.','phone'=>'','email'=>''],
    ['id'=>5,'name'=>'Илья Петров','photo'=>'assets/img/masters/master-5.jpg','exp'=>'Опыт: 5 лет','experience'=>'5 лет','work'=>'Мелкая техника и комплексная диагностика','specialization'=>'Мастер универсального ремонта','area'=>'Все районы Алматы','brands'=>'Candy, Zanussi, Daewoo, Whirlpool','description'=>'Выезжает на комплексную диагностику и ремонт мелкой бытовой техники.','phone'=>'','email'=>''],
  ];
}

function app_get_public_masters(): array {
  try {
    $stmt = db()->query('SELECT * FROM masters WHERE is_active=1 ORDER BY id ASC');
    $items = array_map('app_master_public_array', $stmt->fetchAll());
    return $items ?: app_default_public_masters();
  } catch (Throwable $e) {
    return app_default_public_masters();
  }
}

function app_get_masters_for_select(): array {
  return db()->query('SELECT id, name, specialization FROM masters WHERE is_active=1 ORDER BY name ASC')->fetchAll();
}

function app_get_master(int $id): ?array {
  $stmt = db()->prepare('SELECT * FROM masters WHERE id=:id LIMIT 1');
  $stmt->execute([':id'=>$id]);
  $row = $stmt->fetch();
  return $row ?: null;
}

function app_upload_image(string $field, string $prefix = 'upload'): ?string {
  if (empty($_FILES[$field]) || !is_array($_FILES[$field]) || (int)($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
  if ((int)$_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;
  $tmp = (string)$_FILES[$field]['tmp_name'];
  $name = (string)$_FILES[$field]['name'];
  $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
  if (!in_array($ext, ['jpg','jpeg','png','webp','svg'], true)) return null;
  $dir = __DIR__ . '/../assets/img/masters/uploads';
  if (!is_dir($dir)) @mkdir($dir, 0775, true);
  $safe = $prefix . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
  $target = $dir . '/' . $safe;
  if (!move_uploaded_file($tmp, $target)) return null;
  return 'assets/img/masters/uploads/' . $safe;
}

function app_record_lead_view(int $leadId, int $masterId): void {
  $stmt = db()->prepare('INSERT IGNORE INTO lead_views (lead_id, master_id) VALUES (:lead_id, :master_id)');
  $stmt->execute([':lead_id'=>$leadId, ':master_id'=>$masterId]);
}

function app_lead_views_count(int $leadId): int {
  $stmt = db()->prepare('SELECT COUNT(*) FROM lead_views WHERE lead_id=:id');
  $stmt->execute([':id'=>$leadId]);
  return (int)$stmt->fetchColumn();
}

function app_lead_viewers(int $leadId): array {
  $stmt = db()->prepare('SELECT m.name, m.specialization, v.viewed_at FROM lead_views v JOIN masters m ON m.id=v.master_id WHERE v.lead_id=:id ORDER BY v.viewed_at DESC');
  $stmt->execute([':id'=>$leadId]);
  return $stmt->fetchAll();
}


function app_base_url(): string {
  return function_exists('site_base_url') ? site_base_url() : 'http://localhost';
}

function app_absolute_url(string $path): string {
  if (preg_match('~^https?://~i', $path)) return $path;
  return app_base_url() . '/' . ltrim($path, '/');
}

function app_client_link(string $token): string {
  return 'client/index.php?token=' . rawurlencode($token);
}

function app_client_url(string $token): string {
  return app_absolute_url(app_client_link($token));
}

function app_mail_headers(string $fromName = APP_NAME): string {
  $host = parse_url(app_base_url(), PHP_URL_HOST) ?: 'localhost';
  $from = 'no-reply@' . preg_replace('~[^a-z0-9.-]~i', '', $host);
  $encodedName = function_exists('mb_encode_mimeheader') ? mb_encode_mimeheader($fromName, 'UTF-8') : $fromName;
  return "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nFrom: " . $encodedName . " <{$from}>\r\n";
}

function app_send_plain_mail(string $to, string $subject, string $message): bool {
  if (!filter_var($to, FILTER_VALIDATE_EMAIL)) return false;
  $encodedSubject = function_exists('mb_encode_mimeheader') ? mb_encode_mimeheader($subject, 'UTF-8') : $subject;
  return @mail($to, $encodedSubject, $message, app_mail_headers());
}

function app_append_reply(?string $current, string $line): string {
  $current = trim((string)$current);
  $stamp = date('d.m.Y H:i');
  $entry = '[' . $stamp . '] ' . trim($line);
  return $current === '' ? $entry : $current . "\n" . $entry;
}

function app_status_step(string $status): int {
  return ['new'=>1, 'in_work'=>3, 'done'=>5, 'cancelled'=>0][$status] ?? 1;
}

function app_short(string $text, int $max = 150): string {
  $text = trim($text);
  if (function_exists('mb_strlen') && mb_strlen($text) > $max) return mb_substr($text, 0, $max - 1) . '…';
  if (strlen($text) > $max) return substr($text, 0, $max - 1) . '…';
  return $text;
}
