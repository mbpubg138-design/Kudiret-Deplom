<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/app_models.php';
require_once __DIR__ . '/../includes/csrf.php';
csrf_start();
if (empty($_SESSION['master'])) { header('Location: login.php'); exit; }
$masterId = (int)$_SESSION['master']['id'];
$master = app_get_master($masterId);
if (!$master) { unset($_SESSION['master']); header('Location: login.php'); exit; }

$errors = [];
$flash = '';
$allowed = ['in_work','done','cancelled'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_verify($_POST['csrf'] ?? '')) {
    $errors[] = 'Ошибка безопасности. Обновите страницу.';
  } else {
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'profile') {
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
      $photo = app_upload_image('photo','master-profile') ?: (string)($master['photo'] ?? 'assets/img/masters/master-1.jpg');
      if ($name === '' || $specialization === '' || $experience === '') {
        $errors[] = 'ФИО, специализация и опыт обязательны.';
      } else {
        $sql = 'UPDATE masters SET name=:name,email=:email,phone=:phone,photo=:photo,specialization=:specialization,experience=:experience,work=:work,brands=:brands,area=:area,description=:description';
        $params = [':name'=>$name,':email'=>$email ?: null,':phone'=>$phone ?: null,':photo'=>$photo,':specialization'=>$specialization,':experience'=>$experience,':work'=>$work ?: null,':brands'=>$brands ?: null,':area'=>$area ?: null,':description'=>$description ?: null,':id'=>$masterId];
        if ($password !== '') { $sql .= ', password_hash=:password_hash'; $params[':password_hash'] = password_hash($password, PASSWORD_DEFAULT); }
        $sql .= ' WHERE id=:id';
        db()->prepare($sql)->execute($params);
        $_SESSION['master']['name'] = $name;
        $flash = 'Профиль сохранён. Карточка на сайте обновилась.';
        $master = app_get_master($masterId);
      }
    }

    if ($action === 'take') {
      $leadId = (int)($_POST['lead_id'] ?? 0);
      $currentStmt = db()->prepare('SELECT reply FROM leads WHERE id=:id AND master_id IS NULL AND status="new" LIMIT 1');
      $currentStmt->execute([':id'=>$leadId]);
      $current = $currentStmt->fetch();
      if ($current) {
        $reply = app_append_reply($current['reply'] ?? null, 'Мастер взял заявку в работу: ' . (string)$master['name']);
        $stmt = db()->prepare('UPDATE leads SET master_id=:mid, status="in_work", reply=:reply WHERE id=:id AND master_id IS NULL AND status="new"');
        $stmt->execute([':mid'=>$masterId, ':id'=>$leadId, ':reply'=>$reply]);
        db()->prepare('INSERT INTO lead_events (lead_id, actor, message) VALUES (:id, "master", :message)')->execute([':id'=>$leadId, ':message'=>'Мастер ' . (string)$master['name'] . ' взял заявку в работу']);
        $flash = 'Заявка взята в работу.';
      } else $errors[] = 'Заявку уже взял другой мастер или она недоступна.';
    }

    if ($action === 'lead') {
      $leadId = (int)($_POST['lead_id'] ?? 0);
      $status = (string)($_POST['status'] ?? 'in_work');
      if (!in_array($status, $allowed, true)) $status = 'in_work';
      $reply = trim((string)($_POST['reply'] ?? ''));
      $stmt = db()->prepare('UPDATE leads SET status=:status, reply=:reply WHERE id=:id AND master_id=:mid');
      $stmt->execute([':status'=>$status, ':reply'=>$reply !== '' ? $reply : null, ':id'=>$leadId, ':mid'=>$masterId]);
      db()->prepare('INSERT INTO lead_events (lead_id, actor, message) VALUES (:id, "master", :message)')->execute([':id'=>$leadId, ':message'=>'Мастер обновил статус: ' . app_status_label($status)]);
      $flash = 'Статус заявки обновлён.';
    }

    if ($action === 'refuse') {
      $leadId = (int)($_POST['lead_id'] ?? 0);
      $reasonSelect = trim((string)($_POST['refuse_reason'] ?? ''));
      $reasonOther = trim((string)($_POST['refuse_reason_other'] ?? ''));
      $reason = $reasonSelect === 'Другая причина' ? $reasonOther : $reasonSelect;
      if ($reason === '') $reason = 'Мастер отказался от заявки';
      $stmt = db()->prepare('SELECT reply FROM leads WHERE id=:id AND master_id=:mid LIMIT 1');
      $stmt->execute([':id'=>$leadId, ':mid'=>$masterId]);
      $lead = $stmt->fetch();
      if ($lead) {
        $reply = app_append_reply($lead['reply'] ?? null, 'Мастер отказался от заявки. Причина: ' . $reason);
        db()->prepare('UPDATE leads SET master_id=NULL, status="new", reply=:reply WHERE id=:id AND master_id=:mid')->execute([':reply'=>$reply, ':id'=>$leadId, ':mid'=>$masterId]);
        db()->prepare('INSERT INTO lead_events (lead_id, actor, message) VALUES (:id, "master", :message)')->execute([':id'=>$leadId, ':message'=>'Мастер отказался. Причина: ' . $reason]);
        $flash = 'Заявка возвращена в общий список с причиной отказа.';
      } else $errors[] = 'Заявка не найдена или уже не назначена вам.';
    }
  }
}

$viewId = (int)($_GET['view'] ?? 0);
$viewLead = null;
if ($viewId > 0) {
  $stmt = db()->prepare('SELECT l.*, m.name AS master_name FROM leads l LEFT JOIN masters m ON m.id=l.master_id WHERE l.id=:id AND (l.master_id=:mid OR l.master_id IS NULL) LIMIT 1');
  $stmt->execute([':id'=>$viewId, ':mid'=>$masterId]);
  $viewLead = $stmt->fetch();
  if ($viewLead) app_record_lead_view((int)$viewLead['id'], $masterId);
}
$stmt = db()->prepare('SELECT l.*, COALESCE(vc.views_count,0) AS views_count FROM leads l LEFT JOIN (SELECT lead_id, COUNT(*) AS views_count FROM lead_views GROUP BY lead_id) vc ON vc.lead_id=l.id WHERE l.master_id=:mid ORDER BY l.id DESC');
$stmt->execute([':mid'=>$masterId]);
$myLeads = $stmt->fetchAll();
$general = db()->query('SELECT l.*, COALESCE(vc.views_count,0) AS views_count FROM leads l LEFT JOIN (SELECT lead_id, COUNT(*) AS views_count FROM lead_views GROUP BY lead_id) vc ON vc.lead_id=l.id WHERE l.master_id IS NULL AND l.status="new" ORDER BY l.id DESC LIMIT 100')->fetchAll();
?><!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Кабинет мастера</title><link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800;900&display=swap" rel="stylesheet"><style>
:root{--accent:#2563eb;--blue2:#2563eb;--dark:#111827;--line:rgba(17,24,39,.10);--bg:#f3f6fb;--muted:#667085;--good:#16a34a;--red:#dc2626;--shadow:0 16px 42px rgba(17,24,39,.08)}*{box-sizing:border-box}body{margin:0;font-family:Manrope,Arial,sans-serif;background:radial-gradient(circle at top right,rgba(37,99,235,.10),transparent 35%),var(--bg);color:var(--dark)}a{text-decoration:none;color:inherit}.top{position:sticky;top:0;z-index:5;background:rgba(255,255,255,.94);backdrop-filter:blur(12px);border-bottom:1px solid var(--line)}.row{width:min(1180px,calc(100% - 28px));margin:auto;display:flex;justify-content:space-between;align-items:center;gap:14px;padding:14px 0}.brand{font-size:18px;font-weight:1000}.nav{display:flex;gap:8px;flex-wrap:wrap}.tab,.btn{display:inline-flex;align-items:center;justify-content:center;padding:10px 13px;border-radius:14px;background:#fff;border:1px solid var(--line);font-weight:900;color:var(--dark);cursor:pointer}.tab.is-active,.btn.primary{background:var(--accent);border-color:var(--accent);color:#fff}.container{width:min(1180px,calc(100% - 28px));margin:auto;padding:24px 0 44px}.hero{display:flex;justify-content:space-between;gap:18px;align-items:end;margin-bottom:18px}.hero h1{font-size:38px;line-height:1;margin:0 0 8px;letter-spacing:-.04em}.muted{color:var(--muted);font-weight:750}.grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.card{background:#fff;border:1px solid var(--line);border-radius:24px;padding:18px;box-shadow:var(--shadow)}.cardHead{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:12px}.cardHead b{font-size:18px}.badge{display:inline-flex;border-radius:999px;padding:7px 10px;font-size:12px;font-weight:950}.new{background:rgba(37,99,235,.13);color:#1746a2}.in_work{background:rgba(37,99,235,.12);color:#1d4ed8}.done{background:rgba(22,163,74,.13);color:#15803d}.cancelled{background:rgba(220,38,38,.12);color:#b91c1c}label{display:grid;gap:7px;color:#475467;font-size:12px;text-transform:uppercase;letter-spacing:.04em;font-weight:950}input,select,textarea{width:100%;font:inherit;border:1px solid var(--line);border-radius:12px;padding:10px 12px;background:#fff;color:var(--dark);outline:none}textarea{min-height:110px;max-height:110px;resize:none;overflow:auto}.formGrid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.wide{grid-column:1/-1}.notice{padding:14px 16px;border-radius:16px;margin:14px 0;font-weight:900}.ok{background:rgba(22,163,74,.12);color:#15803d}.bad{background:rgba(220,38,38,.12);color:#b91c1c}.actions{display:flex;gap:8px;flex-wrap:wrap}.delete{background:rgba(220,38,38,.11);color:#b91c1c}.save{background:var(--dark);color:#fff;border-color:var(--dark)}.pill{display:inline-flex;gap:6px;align-items:center;border:1px solid var(--line);border-radius:999px;background:#fbfbfd;padding:7px 10px;font-weight:900;color:#475467}.miniLine{display:flex;gap:8px;flex-wrap:wrap;margin:10px 0}.avatar{width:112px;height:84px;border-radius:20px;object-fit:cover;border:1px solid var(--line);background:#fff}.profile{display:flex;gap:14px;align-items:center}.empty{text-align:center;padding:28px}.split{display:grid;grid-template-columns:.78fr 1.22fr;gap:16px}.smallForm{display:grid;grid-template-columns:1fr 1fr auto;gap:8px;margin-top:10px;padding-top:10px;border-top:1px solid var(--line)}.viewPanel{border:2px solid rgba(37,99,235,.18);background:linear-gradient(135deg,#fff,#f8fbff)}.viewTop{display:flex;justify-content:space-between;gap:12px;align-items:flex-start}.viewClose{background:#fff;border:1px solid var(--line);color:#111827}.viewDescription{padding:14px 16px;border-radius:18px;background:#fbfbfd;border:1px solid var(--line);line-height:1.65;font-weight:800;color:#344054}@media(max-width:900px){.grid,.split,.formGrid,.smallForm{grid-template-columns:1fr}.wide{grid-column:auto}.row,.hero{display:block}.nav{margin-top:12px}.hero h1{font-size:31px}}
</style></head><body>
<div class="top"><div class="row"><div class="brand">Кабинет мастера</div><div class="nav"><a class="tab" href="../index.php" target="_blank" rel="noopener">Сайт</a><a class="tab" href="logout.php">Выйти</a></div></div></div>
<main class="container">
  <div class="hero"><div><h1>Здравствуйте, <?= h($master['name']) ?></h1><div class="muted">Рабочая лента заявок: новые обращения, ваши заявки, статус и отказ с причиной.</div></div><div class="miniLine"><span class="pill">Мои заявки: <?= count($myLeads) ?></span><span class="pill">Новые доступные: <?= count($general) ?></span></div></div>
  <?php if ($flash): ?><div class="notice ok"><?= h($flash) ?></div><?php endif; ?><?php if ($errors): ?><div class="notice bad"><?php foreach($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?></div><?php endif; ?>
  <?php if ($viewLead): ?><section class="card viewPanel" id="openedLead"><div class="viewTop"><div><h2>Открытая заявка #<?= (int)$viewLead['id'] ?></h2><div class="muted">Клиент видит, что мастер открыл заявку. Чтобы закрыть просмотр, нажмите кнопку справа.</div></div><div class="actions"><span class="badge <?= app_status_class((string)$viewLead['status']) ?>"><?= app_status_label((string)$viewLead['status']) ?></span><a class="btn viewClose" href="index.php">Закрыть</a></div></div><div class="miniLine"><span class="pill">👤 <?= h($viewLead['name']) ?></span><span class="pill">📞 <?= h($viewLead['phone']) ?></span><?php if(!empty($viewLead['district'])): ?><span class="pill">📍 <?= h($viewLead['district']) ?></span><?php endif; ?><span class="pill">🧰 <?= h($viewLead['service']) ?></span></div><div class="viewDescription"><?= nl2br(h($viewLead['description'] ?? 'Описание не указано')) ?></div><?php if ((int)($viewLead['master_id'] ?? 0) === $masterId): ?><form class="formGrid" method="post" style="margin-top:14px"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="lead"><input type="hidden" name="lead_id" value="<?= (int)$viewLead['id'] ?>"><label>Статус<select name="status"><?php foreach($allowed as $st): ?><option value="<?= h($st) ?>" <?= $viewLead['status']===$st?'selected':'' ?>><?= h(app_status_label($st)) ?></option><?php endforeach; ?></select></label><label class="wide">Комментарий клиенту / админу<textarea name="reply"><?= h($viewLead['reply'] ?? '') ?></textarea></label><div class="wide actions"><button class="btn save" type="submit">Сохранить статус</button><a class="btn viewClose" href="index.php">Закрыть заявку</a></div></form><form class="smallForm" method="post" onsubmit="return confirm('Отказаться от этой заявки?');"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="refuse"><input type="hidden" name="lead_id" value="<?= (int)$viewLead['id'] ?>"><select name="refuse_reason"><option>Далеко ехать</option><option>Нет нужной детали</option><option>Не моя специализация</option><option>Клиент не отвечает</option><option>Другая причина</option></select><input name="refuse_reason_other" placeholder="Свой вариант"><button class="btn delete">Отказаться</button></form><?php else: ?><form method="post" style="margin-top:14px"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="take"><input type="hidden" name="lead_id" value="<?= (int)$viewLead['id'] ?>"><div class="actions"><button class="btn primary">Взять эту заявку себе</button><a class="btn viewClose" href="index.php">Закрыть</a></div></form><?php endif; ?></section><?php endif; ?>
  <div class="split" style="margin-top:16px"><section class="card"><div class="profile"><img class="avatar" src="../<?= h(asset_image_url($master['photo'] ?: 'assets/img/masters/master-1.jpg')) ?>" alt=""><div><h2>Мой профиль</h2><div class="muted">Эти данные меняют карточку на сайте.</div></div></div><form class="formGrid" method="post" enctype="multipart/form-data" style="margin-top:14px"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="profile"><label>ФИО<input name="name" value="<?= h($master['name']) ?>"></label><label>Новый пароль<input name="password" placeholder="если нужно поменять"></label><label>Email<input name="email" value="<?= h($master['email']) ?>"></label><label>Телефон<input name="phone" value="<?= h($master['phone']) ?>"></label><label>Фото<input type="file" name="photo" accept="image/*"></label><label>Опыт<input name="experience" value="<?= h($master['experience']) ?>"></label><label class="wide">Мастер по...<input name="specialization" value="<?= h($master['specialization']) ?>"></label><label class="wide">Что ремонтирую<textarea name="work"><?= h($master['work']) ?></textarea></label><label>Бренды<input name="brands" value="<?= h($master['brands']) ?>"></label><label>Район<input name="area" value="<?= h($master['area']) ?>"></label><label class="wide">Описание<textarea name="description"><?= h($master['description']) ?></textarea></label><button class="btn save wide">Сохранить профиль</button></form></section>
    <section><h2>Мои заявки</h2><div class="grid" style="grid-template-columns:1fr"><?php if(!$myLeads): ?><div class="card empty muted">Вам пока не назначили заявок.</div><?php endif; ?><?php foreach($myLeads as $l): ?><article class="card"><div class="cardHead"><b>#<?= (int)$l['id'] ?> · <?= h($l['service']) ?></b><span class="badge <?= app_status_class((string)$l['status']) ?>"><?= app_status_label((string)$l['status']) ?></span></div><p><?= h($l['name']) ?> · <?= h($l['phone']) ?></p><div class="miniLine"><?php if(!empty($l['district'])): ?><span class="pill">📍 <?= h($l['district']) ?></span><?php endif; ?><span class="pill">👁 <?= (int)$l['views_count'] ?> просмотров</span><a class="pill" href="index.php?view=<?= (int)$l['id'] ?>">Открыть</a></div></article><?php endforeach; ?></div></section></div>
  <section style="margin-top:18px"><h2>Новые доступные заявки</h2><div class="grid"><?php if(!$general): ?><div class="card empty muted">Новых общих заявок нет.</div><?php endif; ?><?php foreach($general as $l): ?><article class="card"><div class="cardHead"><b>#<?= (int)$l['id'] ?> · <?= h($l['service']) ?></b><span class="badge <?= app_status_class((string)$l['status']) ?>"><?= app_status_label((string)$l['status']) ?></span></div><p><?= h(app_short((string)($l['description'] ?? ''), 130)) ?></p><div class="miniLine"><?php if(!empty($l['district'])): ?><span class="pill">📍 <?= h($l['district']) ?></span><?php endif; ?><span class="pill">👁 <?= (int)$l['views_count'] ?> просмотров</span><a class="pill" href="index.php?view=<?= (int)$l['id'] ?>">Подробнее</a></div></article><?php endforeach; ?></div></section>
</main></body></html>
