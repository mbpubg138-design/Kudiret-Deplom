<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

function db(): PDO {
  static $pdo = null;
  if ($pdo instanceof PDO) return $pdo;

  $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
  $options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ];

  try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
  } catch (PDOException $e) {
    // Для локального OSPanel/XAMPP: если базы ещё нет, пробуем создать её автоматически.
    // На реальном хостинге база обычно уже создана в панели, поэтому прав CREATE DATABASE может не быть.
    $rootDsn = 'mysql:host=' . DB_HOST . ';charset=utf8mb4';
    $root = new PDO($rootDsn, DB_USER, DB_PASS, $options);
    $root->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
  }

  db_install_schema($pdo);
  return $pdo;
}

function db_install_schema(PDO $pdo): void {
  $pdo->exec("CREATE TABLE IF NOT EXISTS leads (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    name VARCHAR(120) NOT NULL,
    phone VARCHAR(32) NOT NULL,
    email VARCHAR(160) NULL,
    district VARCHAR(180) NULL,
    service VARCHAR(160) NOT NULL,
    description TEXT NULL,
    reply TEXT NULL,
    cancel_reason VARCHAR(255) NULL,
    lead_source VARCHAR(80) NULL,
    status ENUM('new','in_work','done','cancelled') NOT NULL DEFAULT 'new',
    master_id INT UNSIGNED NULL,
    client_token VARCHAR(64) NULL,
    access_code VARCHAR(16) NULL,
    ip VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    PRIMARY KEY (id),
    KEY idx_created_at (created_at),
    KEY idx_status (status),
    KEY idx_master_id (master_id),
    KEY idx_client_token (client_token),
    KEY idx_access_code (access_code)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

  db_ensure_column($pdo, 'leads', 'updated_at', "TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP");
  db_ensure_column($pdo, 'leads', 'email', "VARCHAR(160) NULL");
  db_ensure_column($pdo, 'leads', 'district', "VARCHAR(180) NULL");
  db_ensure_column($pdo, 'leads', 'reply', "TEXT NULL");
  db_ensure_column($pdo, 'leads', 'cancel_reason', "VARCHAR(255) NULL");
  db_ensure_column($pdo, 'leads', 'lead_source', "VARCHAR(80) NULL");
  db_ensure_column($pdo, 'leads', 'master_id', "INT UNSIGNED NULL");
  db_ensure_column($pdo, 'leads', 'client_token', "VARCHAR(64) NULL");
  db_ensure_column($pdo, 'leads', 'access_code', "VARCHAR(16) NULL");
  db_ensure_index($pdo, 'leads', 'idx_master_id', 'master_id');
  db_ensure_index($pdo, 'leads', 'idx_client_token', 'client_token');
  db_ensure_index($pdo, 'leads', 'idx_access_code', 'access_code');

  $pdo->exec("CREATE TABLE IF NOT EXISTS masters (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    login VARCHAR(80) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(160) NOT NULL,
    email VARCHAR(160) NULL,
    phone VARCHAR(32) NULL,
    photo VARCHAR(255) NULL,
    specialization VARCHAR(180) NOT NULL,
    experience VARCHAR(80) NOT NULL,
    work TEXT NULL,
    brands VARCHAR(255) NULL,
    area VARCHAR(255) NULL,
    description TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_master_login (login),
    KEY idx_active (is_active)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

  $pdo->exec("CREATE TABLE IF NOT EXISTS lead_views (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    lead_id INT UNSIGNED NOT NULL,
    master_id INT UNSIGNED NOT NULL,
    viewed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_lead_master (lead_id, master_id),
    KEY idx_lead (lead_id),
    KEY idx_master (master_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

  $pdo->exec("CREATE TABLE IF NOT EXISTS lead_events (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    lead_id INT UNSIGNED NOT NULL,
    actor VARCHAR(80) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_lead_created (lead_id, created_at)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

  $pdo->exec("CREATE TABLE IF NOT EXISTS master_applications (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    name VARCHAR(160) NOT NULL,
    email VARCHAR(160) NOT NULL,
    phone VARCHAR(32) NOT NULL,
    photo VARCHAR(255) NULL,
    specialization VARCHAR(180) NOT NULL,
    experience VARCHAR(80) NOT NULL,
    brands VARCHAR(255) NULL,
    area VARCHAR(255) NULL,
    description TEXT NULL,
    status ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
    admin_note TEXT NULL,
    created_master_id INT UNSIGNED NULL,
    PRIMARY KEY (id),
    KEY idx_status (status),
    KEY idx_created_at (created_at)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

  db_seed_masters($pdo);
  db_migrate_master_photos($pdo);
  db_backfill_lead_tokens($pdo);
}

function db_ensure_column(PDO $pdo, string $table, string $column, string $definition): void {
  $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE :column");
  $stmt->execute([':column' => $column]);
  if (!$stmt->fetch()) {
    $pdo->exec("ALTER TABLE `$table` ADD `$column` $definition");
  }
}

function db_ensure_index(PDO $pdo, string $table, string $index, string $column): void {
  $stmt = $pdo->prepare("SHOW INDEX FROM `$table` WHERE Key_name = :idx");
  $stmt->execute([':idx' => $index]);
  if (!$stmt->fetch()) {
    $pdo->exec("ALTER TABLE `$table` ADD INDEX `$index` (`$column`)");
  }
}

function db_seed_masters(PDO $pdo): void {
  $count = (int)$pdo->query('SELECT COUNT(*) FROM masters')->fetchColumn();
  if ($count > 0) return;

  $masters = [
    ['master1','Сергей Иванов','sergey.master@example.kz','+7 700 111 10 01','assets/img/masters/master-1.jpg','Мастер по стиральным машинам','6 лет','Насосы, ремни, подшипники, люки, клапаны','LG, Samsung, Bosch, Indesit','Алмалинский, Бостандыкский','Специалист по диагностике стиральных машин и замене основных узлов.'],
    ['master2','Алексей Морозов','alexey.master@example.kz','+7 700 111 10 02','assets/img/masters/master-2.jpg','Мастер по холодильникам','8 лет','Компрессоры, фреон, датчики, терморегуляторы','Atlant, Beko, Haier, Electrolux','Ауэзовский, Наурызбайский','Работает с холодильниками, утечками фреона и температурными датчиками.'],
    ['master3','Данияр Касымов','daniyar.master@example.kz','+7 700 111 10 03','assets/img/masters/master-3.jpg','Мастер по микроволновкам','5 лет','Платы, магнетроны, питание, кнопки, дверцы','Panasonic, Samsung, LG, Midea','Медеуский, Жетысуский','Проводит ремонт микроволновых печей, блоков питания и дверных механизмов.'],
    ['master4','Руслан Ахметов','ruslan.master@example.kz','+7 700 111 10 04','assets/img/masters/master-4.jpg','Мастер по плитам и духовкам','7 лет','Нагрев, конфорки, термостаты, электрика','Bosch, Hansa, Gorenje, Siemens','Турксибский, Алатауский','Занимается электрическими плитами, духовыми шкафами и нагревательными элементами.'],
    ['master5','Илья Петров','ilya.master@example.kz','+7 700 111 10 05','assets/img/masters/master-5.jpg','Мастер универсального ремонта','5 лет','Мелкая техника и комплексная диагностика','Candy, Zanussi, Daewoo, Whirlpool','Все районы Алматы','Выезжает на комплексную диагностику и ремонт мелкой бытовой техники.'],
  ];
  $stmt = $pdo->prepare('INSERT INTO masters (login,password_hash,name,email,phone,photo,specialization,experience,work,brands,area,description,is_active) VALUES (:login,:password_hash,:name,:email,:phone,:photo,:specialization,:experience,:work,:brands,:area,:description,1)');
  foreach ($masters as $m) {
    $stmt->execute([
      ':login'=>$m[0], ':password_hash'=>password_hash(MASTER_DEFAULT_PASS, PASSWORD_DEFAULT), ':name'=>$m[1], ':email'=>$m[2], ':phone'=>$m[3], ':photo'=>$m[4], ':specialization'=>$m[5], ':experience'=>$m[6], ':work'=>$m[7], ':brands'=>$m[8], ':area'=>$m[9], ':description'=>$m[10]
    ]);
  }
}


function db_migrate_master_photos(PDO $pdo): void {
  $rows = $pdo->query('SELECT id, login, photo FROM masters ORDER BY id ASC')->fetchAll();
  if (!$rows) return;
  $mapByLogin = [
    'master1' => 'assets/img/masters/master-1.jpg',
    'master2' => 'assets/img/masters/master-2.jpg',
    'master3' => 'assets/img/masters/master-3.jpg',
    'master4' => 'assets/img/masters/master-4.jpg',
    'master5' => 'assets/img/masters/master-5.jpg',
  ];
  $mapByIndex = [
    1 => 'assets/img/masters/master-1.jpg',
    2 => 'assets/img/masters/master-2.jpg',
    3 => 'assets/img/masters/master-3.jpg',
    4 => 'assets/img/masters/master-4.jpg',
    5 => 'assets/img/masters/master-5.jpg',
  ];
  $update = $pdo->prepare('UPDATE masters SET photo=:photo WHERE id=:id');
  foreach ($rows as $index => $row) {
    $photo = (string)($row['photo'] ?? '');
    $needsUpdate = $photo === '' || preg_match('~assets/img/masters/profile-[1-5]\.svg$~', $photo);
    if (!$needsUpdate) continue;
    $replacement = $mapByLogin[(string)($row['login'] ?? '')] ?? $mapByIndex[$index + 1] ?? null;
    if ($replacement) {
      $update->execute([':photo' => $replacement, ':id' => (int)$row['id']]);
    }
  }
}

function db_backfill_lead_tokens(PDO $pdo): void {
  $stmt = $pdo->query("SELECT id FROM leads WHERE client_token IS NULL OR client_token = '' OR access_code IS NULL OR access_code = '' LIMIT 300");
  $update = $pdo->prepare('UPDATE leads SET client_token=:token, access_code=:code WHERE id=:id');
  foreach ($stmt->fetchAll() as $row) {
    $update->execute([':token' => bin2hex(random_bytes(16)), ':code' => strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)), ':id' => (int)$row['id']]);
  }
}
