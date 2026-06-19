<?php
declare(strict_types=1);

const APP_NAME = 'ТехноМастер';
const PROJECT_TITLE = 'Ремонт бытовой техники в Алматы';
const CITY_NAME = 'Алматы';
const SITE_URL = ''; // Можно оставить пустым: сайт сам определит домен. После покупки домена можно указать https://ваш-домен.kz
const ASSET_VERSION = '20260601-blue-photo-fix';

const MAIN_PHONE = '+7 (700) 000-00-00';
const MAIN_PHONE_DIGITS = '77000000000';
const COMPANY_ADDRESS = 'г. Алматы, ул. Примерная, д. 1';
const WORK_TIME = 'Ежедневно с 09:00 до 18:00';


// Брендинг сайта. Просто замените файлы в папке assets/img/branding/.
const BRANDING_DIR = 'assets/img/branding';
const SITE_LOGO_PATH = BRANDING_DIR . '/sitelogo.png';
const SITE_FAVICON_PATH = BRANDING_DIR . '/favicon.svg';
const PHONE_ICON_PATH = BRANDING_DIR . '/phone-icon.png';


if (!function_exists('site_base_url')) {
  function site_base_url(): string {
    $configured = trim((string)SITE_URL);
    if ($configured !== '' && stripos($configured, 'example.') === false) return rtrim($configured, '/');
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    $scheme = $https ? 'https' : 'http';
    $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
    return $scheme . '://' . $host;
  }
}

if (!function_exists('asset_url')) {
  function asset_url(string $path): string {
    $clean = ltrim($path, '/');
    $fullPath = __DIR__ . '/../' . $clean;
    if (is_file($fullPath)) {
      return $clean . '?v=' . filemtime($fullPath);
    }
    return $clean;
  }
}

if (!function_exists('asset_image_url')) {
  function asset_image_url(string $path): string {
    $clean = ltrim($path, '/');
    $fullPath = __DIR__ . '/../' . $clean;
    if (is_file($fullPath)) {
      return asset_url($clean);
    }

    $info = pathinfo($clean);
    $dir = isset($info['dirname']) && $info['dirname'] !== '.' ? $info['dirname'] : '';
    $name = $info['filename'] ?? '';
    $extensions = ['png', 'webp', 'jpg', 'jpeg', 'svg'];
    foreach ($extensions as $ext) {
      $candidate = ($dir ? $dir . '/' : '') . $name . '.' . $ext;
      if (is_file(__DIR__ . '/../' . $candidate)) {
        return asset_url($candidate);
      }
    }

    return $clean;
  }
}

if (!function_exists('site_logo_url')) {
  function site_logo_url(): string { return asset_image_url(SITE_LOGO_PATH); }
}
if (!function_exists('site_favicon_url')) {
  function site_favicon_url(): string { return asset_image_url(SITE_FAVICON_PATH); }
}
if (!function_exists('phone_icon_url')) {
  function phone_icon_url(): string { return asset_image_url(PHONE_ICON_PATH); }
}

const DB_HOST = 'sql104.infinityfree.com';
const DB_NAME = 'if0_42198584_almar_repair';
const DB_USER = 'if0_42198584';
const DB_PASS = 'UBWNxiO19tgGUT';

const ADMIN_PATH = 'admin';
define('ADMIN_LOGIN', 'manager');
define('ADMIN_PASS',  'manager123'); 

const WHATSAPP_PHONE = MAIN_PHONE_DIGITS;
const WHATSAPP_TEXT = 'Здравствуйте! Хочу вызвать мастера по ремонту бытовой техники. Подскажите, пожалуйста, стоимость и ближайшее время выезда.';

if (!function_exists('whatsapp_url')) {
  function whatsapp_url(string $text = WHATSAPP_TEXT): string {
    return 'https://wa.me/' . WHATSAPP_PHONE . '?text=' . rawurlencode($text);
  }
}


// Необязательно: заполните эти поля, чтобы получать новые заявки на email или в Telegram.
const ADMIN_EMAIL = '';
const TELEGRAM_BOT_TOKEN = '';
const TELEGRAM_CHAT_ID = '';


// Доступы мастеров создаются в админ-панели. Авто-посев нужен только для демонстрации проекта.
// Перед публикацией создайте мастерам отдельные пароли через админ-панель.
const MASTER_DEFAULT_PASS = 'Master_Temp_2026!';
const CLIENT_PORTAL_PATH = 'client/index.php';
