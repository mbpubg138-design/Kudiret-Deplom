<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/app_models.php';
require_once __DIR__ . '/../includes/csrf.php';
csrf_start();
if (empty($_SESSION['admin'])) { header('Location: login.php'); exit; }

$errors = [];
$flash = '';
$mode = (string)($_GET['mode'] ?? 'list');
$editId = (int)($_GET['edit'] ?? 0);
$viewId = (int)($_GET['view'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_verify($_POST['csrf'] ?? '')) {
    $errors[] = 'Ошибка безопасности. Обновите страницу.';
  } else {
    $action = (string)($_POST['action'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'hide' && $id > 0) {
      db()->prepare('UPDATE masters SET is_active=0 WHERE id=:id')->execute([':id'=>$id]);
      $flash = 'Мастер скрыт с сайта, но профиль и история заявок сохранены.';
    }

    if ($action === 'remove_forever' && $id > 0) {
      $master = app_get_master($id);
      if (!$master) {
        $errors[] = 'Мастер не найден.';
      } else {
        db()->prepare('UPDATE leads SET master_id=NULL WHERE master_id=:id')->execute([':id'=>$id]);
        db()->prepare('DELETE FROM lead_views WHERE master_id=:id')->execute([':id'=>$id]);
        db()->prepare('DELETE FROM masters WHERE id=:id')->execute([':id'=>$id]);
        $flash = 'Мастер полностью удалён. Его заявки переведены в общий список без назначенного мастера.';
      }
    }

    if ($action === 'save') {
      $login = trim((string)($_POST['login'] ?? ''));
      $name = trim((string)($_POST['name'] ?? ''));
      $email = trim((string)($_POST['email'] ?? ''));
      $phone = trim((string)($_POST['phone'] ?? ''));
      $specialization = trim((string)($_POST['specialization'] ?? ''));
      $experience = trim((string)($_POST['experience'] ?? ''));
      $work = trim((string)($_POST['work'] ?? ''));
      $brands = trim((string)($_POST['brands'] ?? ''));
      $area = trim((string)($_POST['area'] ?? ''));
      $description = trim((string)($_POST['description'] ?? ''));
      $password = trim((string)($_POST['password'] ?? ''));
      $isActive = (int)(($_POST['is_active'] ?? '0') === '1');
      $photo = app_upload_image('photo','master-admin');

      if ($login === '' || $name === '' || $specialization === '' || $experience === '') {
        $errors[] = 'Логин, имя, специализация и опыт обязательны.';
      } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Введите корректный email мастера.';
      } else {
        try {
          if ($id > 0) {
            $old = app_get_master($id);
            if (!$old) {
              $errors[] = 'Мастер не найден.';
            } else {
              $photo = $photo ?: (string)($old['photo'] ?? 'assets/img/masters/master-1.jpg');
              $sql = 'UPDATE masters SET login=:login,name=:name,email=:email,phone=:phone,photo=:photo,specialization=:specialization,experience=:experience,work=:work,brands=:brands,area=:area,description=:description,is_active=:is_active';
              $params = [':login'=>$login,':name'=>$name,':email'=>$email ?: null,':phone'=>$phone ?: null,':photo'=>$photo,':specialization'=>$specialization,':experience'=>$experience,':work'=>$work ?: null,':brands'=>$brands ?: null,':area'=>$area ?: null,':description'=>$description ?: null,':is_active'=>$isActive,':id'=>$id];
              if ($password !== '') { $sql .= ', password_hash=:password_hash'; $params[':password_hash'] = password_hash($password, PASSWORD_DEFAULT); }
              $sql .= ' WHERE id=:id';
              db()->prepare($sql)->execute($params);
              $flash = 'Профиль мастера обновлён.';
              $editId = $id;
              $mode = 'list';
            }
          } else {
            if ($password === '') $password = substr(str_replace(['+','/','='], '', base64_encode(random_bytes(9))), 0, 10);
            $photo = $photo ?: 'assets/img/masters/master-1.jpg';
            db()->prepare('INSERT INTO masters (login,password_hash,name,email,phone,photo,specialization,experience,work,brands,area,description,is_active) VALUES (:login,:password_hash,:name,:email,:phone,:photo,:specialization,:experience,:work,:brands,:area,:description,:is_active)')->execute([':login'=>$login,':password_hash'=>password_hash($password,PASSWORD_DEFAULT),':name'=>$name,':email'=>$email ?: null,':phone'=>$phone ?: null,':photo'=>$photo,':specialization'=>$specialization,':experience'=>$experience,':work'=>$work ?: null,':brands'=>$brands ?: null,':area'=>$area ?: null,':description'=>$description ?: null,':is_active'=>$isActive]);
            $flash = 'Новый мастер добавлен. Временный пароль показан один раз: ' . $password;
            $mode = 'list';
          }
        } catch (Throwable $e) {
          $errors[] = 'Не удалось сохранить мастера. Возможно, такой логин уже существует.';
        }
      }
    }
  }
}

$showForm = $mode === 'create' || $editId > 0;
$edit = $editId > 0 ? app_get_master($editId) : null;
if ($editId > 0 && !$edit) { $errors[] = 'Мастер для редактирования не найден.'; $showForm = false; }

$masters = db()->query("SELECT m.*, COUNT(l.id) AS assigned_total, SUM(l.status='in_work') AS active_total, SUM(l.status='done') AS done_total, SUM(l.status='cancelled') AS cancelled_total FROM masters m LEFT JOIN leads l ON l.master_id=m.id GROUP BY m.id ORDER BY m.is_active DESC, m.id ASC")->fetchAll();
$selectedMaster = $viewId > 0 ? app_get_master($viewId) : null;
$selectedLeads = [];
if ($selectedMaster) {
  $stmt = db()->prepare('SELECT * FROM leads WHERE master_id=:id ORDER BY FIELD(status,"in_work","new","done","cancelled"), id DESC LIMIT 200');
  $stmt->execute([':id'=>$viewId]);
  $selectedLeads = $stmt->fetchAll();
}
?>
<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Мастера — админка</title><link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800;900&display=swap" rel="stylesheet"><style>
:root{--accent:#2563eb;--dark:#111827;--line:rgba(17,24,39,.10);--bg:#f4f8ff;--muted:#667085;--good:#16a34a;--red:#dc2626;--shadow:0 16px 42px rgba(17,24,39,.08)}*{box-sizing:border-box}body{margin:0;font-family:Manrope,Arial,sans-serif;background:radial-gradient(circle at top right,rgba(37,99,235,.13),transparent 30%),var(--bg);color:var(--dark)}a{text-decoration:none;color:inherit}.top{position:sticky;top:0;z-index:10;background:rgba(255,255,255,.94);backdrop-filter:blur(12px);border-bottom:1px solid var(--line)}.top__row{width:min(1320px,calc(100% - 32px));margin:auto;display:flex;align-items:center;justify-content:space-between;gap:16px;padding:14px 0}.brand{font-weight:1000;font-size:18px}.nav{display:flex;gap:8px;flex-wrap:wrap}.tab,.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:10px 13px;border-radius:14px;background:#fff;border:1px solid var(--line);font-weight:900;cursor:pointer}.tab.is-active,.btn.primary{background:var(--accent);color:#fff;border-color:var(--accent)}.container{width:min(1320px,calc(100% - 32px));margin:auto;padding:24px 0 42px}.hero{display:flex;justify-content:space-between;gap:18px;align-items:end;margin-bottom:18px;padding:24px 28px;border-radius:28px;background:linear-gradient(135deg,#fff,#f7fbff 58%,#edf4ff);border:1px solid var(--line);box-shadow:var(--shadow)}.hero h1{font-size:38px;line-height:1;margin:0 0 8px;letter-spacing:-.04em}.muted{color:var(--muted);font-weight:750}.notice{padding:14px 16px;border-radius:16px;margin:14px 0;font-weight:900}.ok{background:rgba(22,163,74,.12);color:#15803d}.bad{background:rgba(220,38,38,.12);color:#b91c1c}.masterGrid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}.masterCard{background:#fff;border:1px solid var(--line);border-radius:26px;box-shadow:var(--shadow);overflow:hidden}.masterTop{display:grid;grid-template-columns:94px 1fr;gap:14px;padding:16px;border-bottom:1px solid var(--line)}.avatar{width:94px;height:94px;border-radius:22px;object-fit:cover;object-position:center 18%;border:1px solid var(--line);background:#eef6ff}.masterTop h3{margin:0 0 6px;font-size:21px;letter-spacing:-.03em}.masterTop p{margin:0;color:#475467;font-weight:800;line-height:1.45}.masterBody{padding:16px}.miniLine{display:flex;gap:8px;flex-wrap:wrap;margin:12px 0}.pill{display:inline-flex;gap:6px;align-items:center;border:1px solid var(--line);border-radius:999px;background:#fbfbfd;padding:7px 10px;font-weight:900;color:#475467;font-size:12px}.badge{display:inline-flex;border-radius:999px;padding:7px 10px;font-size:12px;font-weight:950}.done{background:rgba(22,163,74,.13);color:#15803d}.cancelled{background:rgba(220,38,38,.12);color:#b91c1c}.in_work,.new{background:rgba(37,99,235,.12);color:#1d4ed8}.actions{display:flex;gap:8px;flex-wrap:wrap}.save{background:var(--dark);color:#fff}.delete{background:rgba(220,38,38,.10);color:#b91c1c}.soft{background:#fff;color:#111827}.card,.tableWrap{background:#fff;border:1px solid var(--line);border-radius:26px;padding:18px;box-shadow:var(--shadow);margin-bottom:18px}.formGrid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.wide{grid-column:1/-1}label{display:grid;gap:7px;color:#475467;font-size:12px;text-transform:uppercase;letter-spacing:.04em;font-weight:950}input,select,textarea{width:100%;font:inherit;border:1px solid var(--line);border-radius:12px;padding:10px 12px;background:#fff;color:var(--dark);outline:none}textarea{min-height:92px;max-height:120px;resize:none;overflow:auto}table{width:100%;border-collapse:collapse;min-width:880px}th,td{padding:13px;border-bottom:1px solid var(--line);text-align:left;vertical-align:top}th{font-size:12px;text-transform:uppercase;color:#475467;background:#fbfbfd}.tableScroll{overflow:auto}.dangerForm{display:inline}.empty{padding:28px;text-align:center;color:#667085;font-weight:850}@media(max-width:1100px){.masterGrid{grid-template-columns:repeat(2,1fr)}}@media(max-width:760px){.top__row,.hero{display:block}.nav{margin-top:12px}.masterGrid,.formGrid{grid-template-columns:1fr}.wide{grid-column:auto}.hero h1{font-size:31px}.container,.top__row{width:min(100% - 20px,1320px)}}
</style><link rel="stylesheet" href="admin.css?v=20260601-master-panel"></head><body>
<div class="top"><div class="top__row"><div class="brand">Команда сервиса</div><div class="nav"><a class="tab" href="index.php">Заявки</a><a class="tab is-active" href="masters.php">Мастера</a><a class="tab" href="master_applications.php">Заявки мастеров</a><a class="tab" href="reviews.php">Отзывы</a><a class="tab" href="../index.php" target="_blank" rel="noopener">Открыть сайт</a></div><div class="nav"><span class="tab"><?= h($_SESSION['admin']['login'] ?? 'admin') ?></span><a class="tab" href="logout.php">Выйти</a></div></div></div>
<main class="container">
  <div class="hero"><div><h1>Профили мастеров</h1><div class="muted">По умолчанию видно только карточки мастеров и статистику заявок. Анкета редактирования открывается только по кнопке.</div></div><div class="actions"><a class="btn primary" href="masters.php?mode=create">Добавить мастера</a><a class="btn soft" href="masters.php">Только профили</a></div></div>
  <?php if($flash): ?><div class="notice ok"><?= h($flash) ?></div><?php endif; ?><?php if($errors): ?><div class="notice bad"><?php foreach($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?></div><?php endif; ?>

  <?php if($showForm): ?>
  <section class="card"><h2><?= $edit ? 'Редактировать мастера' : 'Добавить мастера' ?></h2><p class="muted">Эта анкета открыта отдельно. На основной странице она не мешает просмотру профилей.</p>
    <form class="formGrid" method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
      <label>Логин<input name="login" value="<?= h($edit['login'] ?? '') ?>" placeholder="master6" required></label><label>Новый пароль<input name="password" type="password" placeholder="Оставьте пустым, если не менять"></label><label>ФИО<input name="name" value="<?= h($edit['name'] ?? '') ?>" required></label><label>Email<input name="email" value="<?= h($edit['email'] ?? '') ?>"></label><label>Телефон<input name="phone" value="<?= h($edit['phone'] ?? '') ?>"></label><label>Фото<input type="file" name="photo" accept="image/*"></label><label>Мастер по...<input name="specialization" value="<?= h($edit['specialization'] ?? '') ?>" required></label><label>Опыт<input name="experience" value="<?= h($edit['experience'] ?? '') ?>" placeholder="6 лет" required></label><label class="wide">Что ремонтирует<textarea name="work"><?= h($edit['work'] ?? '') ?></textarea></label><label>Бренды<input name="brands" value="<?= h($edit['brands'] ?? '') ?>"></label><label>Район<input name="area" value="<?= h($edit['area'] ?? '') ?>"></label><label class="wide">Описание<textarea name="description"><?= h($edit['description'] ?? '') ?></textarea></label><label>Показывать на сайте<select name="is_active"><option value="1" <?= (int)($edit['is_active'] ?? 1)===1?'selected':'' ?>>Да</option><option value="0" <?= isset($edit['is_active']) && (int)$edit['is_active']===0?'selected':'' ?>>Нет</option></select></label><div class="wide actions"><button class="btn save" type="submit">Сохранить мастера</button><a class="btn soft" href="masters.php">Закрыть анкету</a></div>
    </form>
  </section>
  <?php endif; ?>

  <?php if($selectedMaster): ?>
  <section class="tableWrap"><h2>Заявки мастера: <?= h($selectedMaster['name']) ?></h2><p class="muted">Здесь видно, какие заявки назначены мастеру и выполнил он их или нет.</p><div class="tableScroll"><table><thead><tr><th>Заявка</th><th>Клиент</th><th>Статус</th><th>Дата</th><th>Открыть</th></tr></thead><tbody><?php if(!$selectedLeads): ?><tr><td colspan="5" class="empty">У мастера пока нет назначенных заявок.</td></tr><?php endif; ?><?php foreach($selectedLeads as $l): ?><tr><td><b>#<?= (int)$l['id'] ?> · <?= h($l['service']) ?></b><br><span class="muted"><?= h(app_short((string)($l['description'] ?? ''), 110)) ?></span></td><td><?= h($l['name']) ?><br><span class="muted"><?= h($l['phone']) ?></span></td><td><span class="badge <?= app_status_class((string)$l['status']) ?>"><?= app_status_label((string)$l['status']) ?></span></td><td><?= h($l['created_at']) ?></td><td><a class="btn soft" href="index.php?q=<?= urlencode((string)$l['id']) ?>">В заявках</a></td></tr><?php endforeach; ?></tbody></table></div></section>
  <?php endif; ?>

  <section class="masterGrid">
    <?php foreach($masters as $m): ?><article class="masterCard"><div class="masterTop"><img class="avatar" src="../<?= h(asset_image_url($m['photo'] ?: 'assets/img/masters/master-1.jpg')) ?>" alt=""><div><h3><?= h($m['name']) ?></h3><p><?= h($m['specialization']) ?> · опыт <?= h($m['experience']) ?></p><span class="badge <?= (int)$m['is_active']===1?'done':'cancelled' ?>"><?= (int)$m['is_active']===1?'На сайте':'Скрыт' ?></span></div></div><div class="masterBody"><div class="miniLine"><span class="pill">В работе: <?= (int)($m['active_total'] ?? 0) ?></span><span class="pill">Выполнено: <?= (int)($m['done_total'] ?? 0) ?></span><span class="pill">Всего назначено: <?= (int)($m['assigned_total'] ?? 0) ?></span></div><p class="muted"><?= h($m['area'] ?: 'Район не указан') ?><br><?= h($m['brands'] ?: 'Бренды не указаны') ?></p><div class="actions"><a class="btn soft" href="masters.php?view=<?= (int)$m['id'] ?>">Заявки мастера</a><a class="btn save" href="masters.php?edit=<?= (int)$m['id'] ?>">Изменить профиль</a><form method="post" class="dangerForm" onsubmit="return confirm('Скрыть мастера с сайта? Профиль сохранится в админке.');"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="hide"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><button class="btn delete" type="submit">Скрыть</button></form><form method="post" class="dangerForm" onsubmit="return confirm('Удалить мастера полностью? Его заявки станут без назначенного мастера.');"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="remove_forever"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><button class="btn delete" type="submit">Удалить полностью</button></form></div></div></article><?php endforeach; ?>
  </section>
</main></body></html>
