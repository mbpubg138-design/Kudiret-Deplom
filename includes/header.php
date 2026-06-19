<?php
require_once __DIR__ . '/config.php';
$assetVersion = defined('ASSET_VERSION') ? ASSET_VERSION : '20260520-pro-polish';
$pageTitle = $pageTitle ?? (APP_NAME . ' — ремонт бытовой техники в Алматы');
$pageDescription = $pageDescription ?? 'Ремонт стиральных машин, холодильников, микроволновок, посудомоек, плит, духовок и мелкой техники в Алматы. Выезд мастера, понятные цены и гарантия до 12 месяцев.';
$baseUrl = site_base_url();
$pageUrl = rtrim($baseUrl, '/') . '/' . ltrim(basename((string)($_SERVER['SCRIPT_NAME'] ?? 'index.php')), '/');
$schema = [
  '@context' => 'https://schema.org',
  '@type' => 'LocalBusiness',
  'name' => APP_NAME,
  'description' => $pageDescription,
  'url' => rtrim($baseUrl, '/'),
  'telephone' => MAIN_PHONE,
  'priceRange' => '₸₸',
  'address' => [
    '@type' => 'PostalAddress',
    'addressLocality' => CITY_NAME,
    'addressCountry' => 'KZ',
    'streetAddress' => COMPANY_ADDRESS,
  ],
  'areaServed' => [
    ['@type' => 'City', 'name' => CITY_NAME],
    ['@type' => 'AdministrativeArea', 'name' => 'Бостандыкский район'],
    ['@type' => 'AdministrativeArea', 'name' => 'Алмалинский район'],
    ['@type' => 'AdministrativeArea', 'name' => 'Ауэзовский район'],
    ['@type' => 'AdministrativeArea', 'name' => 'Медеуский район'],
    ['@type' => 'AdministrativeArea', 'name' => 'Турксибский район'],
    ['@type' => 'AdministrativeArea', 'name' => 'Жетысуский район'],
    ['@type' => 'AdministrativeArea', 'name' => 'Наурызбайский район'],
    ['@type' => 'AdministrativeArea', 'name' => 'Алатауский район'],
  ],
  'openingHoursSpecification' => [[
    '@type' => 'OpeningHoursSpecification',
    'dayOfWeek' => ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'],
    'opens' => '09:00',
    'closes' => '20:00',
  ]],
  'makesOffer' => [
    ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => 'Ремонт стиральных машин']],
    ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => 'Ремонт холодильников']],
    ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => 'Ремонт микроволновок']],
    ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => 'Ремонт посудомоечных машин']],
    ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => 'Ремонт плит и духовок']],
  ],
];
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
  <meta name="theme-color" content="#2563eb">
  <link rel="canonical" href="<?= htmlspecialchars($pageUrl) ?>">
  <link rel="icon" href="<?= htmlspecialchars(site_favicon_url(), ENT_QUOTES) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css?v=<?= urlencode($assetVersion) ?>" />
  <script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
</head>
<body class="<?= htmlspecialchars($bodyClass ?? '', ENT_QUOTES) ?>">
