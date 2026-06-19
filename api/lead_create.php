<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/app_models.php';
require_once __DIR__ . '/../includes/csrf.php';

header('Content-Type: application/json; charset=utf-8');

function cut_text(string $value, int $limit): string {
  $value = trim($value);
  if (function_exists('mb_strlen') && function_exists('mb_substr')) {
    return mb_strlen($value) > $limit ? mb_substr($value, 0, $limit) : $value;
  }
  return strlen($value) > $limit ? substr($value, 0, $limit) : $value;
}

function notify_new_lead(int $id, string $name, string $phone, string $email, string $district, string $service, string $desc, ?string $masterName, string $code, string $clientUrl): void {
  $message = "Новая заявка #{$id}\n"
    . "Код клиента: {$code}\n"
    . "Имя: {$name}\n"
    . "Телефон: {$phone}\n"
    . "Email: {$email}\n"
    . ($district !== '' ? "Район/адрес: {$district}\n" : '')
    . "Услуга: {$service}\n"
    . ($masterName ? "Назначение: {$masterName}\n" : "Назначение: общая заявка\n")
    . ($desc !== '' ? "Описание: {$desc}\n" : '')
    . "Кабинет клиента: {$clientUrl}";

  if (defined('ADMIN_EMAIL') && filter_var(ADMIN_EMAIL, FILTER_VALIDATE_EMAIL)) {
    app_send_plain_mail(ADMIN_EMAIL, 'Новая заявка с сайта ' . APP_NAME, $message);
  }

  if (defined('TELEGRAM_BOT_TOKEN') && defined('TELEGRAM_CHAT_ID') && TELEGRAM_BOT_TOKEN !== '' && TELEGRAM_CHAT_ID !== '') {
    $url = 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/sendMessage';
    $payload = http_build_query(['chat_id'=>TELEGRAM_CHAT_ID, 'text'=>$message]);
    $context = stream_context_create(['http'=>['method'=>'POST','header'=>"Content-Type: application/x-www-form-urlencoded\r\n",'content'=>$payload,'timeout'=>4]]);
    @file_get_contents($url, false, $context);
  }
}

function send_client_lead_mail(string $email, int $id, string $name, string $service, string $code, string $clientUrl): void {
  $message = "Здравствуйте, {$name}!\n\n"
    . "Ваша заявка №{$id} принята сервисом " . APP_NAME . ".\n"
    . "Услуга: {$service}\n"
    . "Код заявки: {$code}\n\n"
    . "Проверить статус, изменить описание или отменить заявку можно по ссылке:\n{$clientUrl}\n\n"
    . "Мы свяжемся с вами, уточним неисправность и согласуем удобное время выезда мастера.\n\n"
    . "С уважением, " . APP_NAME . "\n"
    . MAIN_PHONE . "\n"
    . WORK_TIME;
  app_send_plain_mail($email, 'Ваша заявка №' . $id . ' принята — ' . APP_NAME, $message);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'Метод не поддерживается']);
  exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) $body = $_POST;

if (!csrf_verify((string)($body['csrf'] ?? ''))) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Обновите страницу и попробуйте снова']);
  exit;
}

$name = cut_text((string)($body['name'] ?? ''), 120);
$phone = cut_text((string)($body['phone'] ?? ''), 32);
$email = cut_text((string)($body['email'] ?? ''), 160);
$district = cut_text((string)($body['district'] ?? ''), 180);
$service = cut_text((string)($body['service'] ?? ''), 160);
$desc = cut_text((string)($body['description'] ?? ''), 500);
$source = cut_text((string)($body['source'] ?? 'site'), 80);
$masterId = (int)($body['master_id'] ?? 0);
$masterName = null;

if ($name === '' || $phone === '' || $email === '' || $service === '') {
  http_response_code(422);
  echo json_encode(['ok' => false, 'error' => 'Заполните имя, телефон, email и услугу']);
  exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(422);
  echo json_encode(['ok' => false, 'error' => 'Введите корректный email, чтобы получить код заявки']);
  exit;
}

if ((string)($body['privacy'] ?? '') !== '1') {
  http_response_code(422);
  echo json_encode(['ok' => false, 'error' => 'Подтвердите согласие на обработку персональных данных']);
  exit;
}

if ($masterId > 0) {
  $m = app_get_master($masterId);
  if (!$m || (int)$m['is_active'] !== 1) $masterId = 0;
  else $masterName = (string)$m['name'];
}

$token = bin2hex(random_bytes(16));
$code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
$ip = $_SERVER['REMOTE_ADDR'] ?? null;
$ua = isset($_SERVER['HTTP_USER_AGENT']) ? cut_text((string)$_SERVER['HTTP_USER_AGENT'], 255) : null;

try {
  $stmt = db()->prepare('INSERT INTO leads (name, phone, email, district, service, description, master_id, client_token, access_code, ip, user_agent, lead_source) VALUES (:name, :phone, :email, :district, :service, :desc, :master_id, :token, :code, :ip, :ua, :source)');
  $stmt->execute([
    ':name' => $name,
    ':phone' => $phone,
    ':email' => $email,
    ':district' => $district !== '' ? $district : null,
    ':service' => $service,
    ':desc' => $desc !== '' ? $desc : null,
    ':master_id' => $masterId > 0 ? $masterId : null,
    ':token' => $token,
    ':code' => $code,
    ':ip' => $ip,
    ':ua' => $ua,
    ':source' => $source !== '' ? $source : 'site',
  ]);
  $id = (int)db()->lastInsertId();
  db()->prepare('INSERT INTO lead_events (lead_id, actor, message) VALUES (:id, :actor, :message)')->execute([
    ':id' => $id,
    ':actor' => 'client',
    ':message' => 'Клиент оставил заявку через сайт',
  ]);
  $clientUrl = app_client_url($token);
  notify_new_lead($id, $name, $phone, $email, $district, $service, $desc, $masterName, $code, $clientUrl);
  send_client_lead_mail($email, $id, $name, $service, $code, $clientUrl);
  echo json_encode(['ok' => true, 'id' => $id, 'access_code' => $code, 'client_url' => app_client_link($token), 'client_url_abs' => $clientUrl, 'assigned_to' => $masterName]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Не удалось отправить заявку. Попробуйте позже или позвоните по номеру сайта.']);
}
