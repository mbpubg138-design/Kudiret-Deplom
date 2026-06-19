<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/app_models.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/config.php';
csrf_start();
if (empty($_SESSION['admin'])) { header('Location: login.php'); exit; }

$allowed = ['new','in_work','done','cancelled'];
$errors = [];
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_verify($_POST['csrf'] ?? '')) {
    $errors[] = 'Ошибка безопасности. Обновите страницу.';
  } else {
    $action = (string)($_POST['action'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'delete' && $id > 0) {
      db()->prepare('DELETE FROM lead_views WHERE lead_id=:id')->execute([':id'=>$id]);
      db()->prepare('DELETE FROM lead_events WHERE lead_id=:id')->execute([':id'=>$id]);
      db()->prepare('DELETE FROM leads WHERE id=:id')->execute([':id'=>$id]);
      $flash = 'Заявка удалена.';
    }

    if ($action === 'quick_assign' && $id > 0) {
      $masterId = (int)($_POST['master_id'] ?? 0);
      if ($masterId <= 0) {
        db()->prepare('UPDATE leads SET master_id=NULL WHERE id=:id AND status NOT IN ("done","cancelled")')->execute([':id'=>$id]);
        db()->prepare('INSERT INTO lead_events (lead_id, actor, message) VALUES (:id, "admin", :message)')->execute([':id'=>$id, ':message'=>'Администратор снял назначенного мастера']);
        $flash = 'Назначение мастера снято.';
      } else {
        $m = app_get_master($masterId);
        if (!$m) {
          $errors[] = 'Мастер не найден.';
        } else {
          $replyStmt = db()->prepare('SELECT reply, status FROM leads WHERE id=:id LIMIT 1');
          $replyStmt->execute([':id'=>$id]);
          $leadRow = $replyStmt->fetch();
          if (!$leadRow || in_array((string)$leadRow['status'], ['done','cancelled'], true)) {
            $errors[] = 'Завершённую или отменённую заявку нельзя назначать мастеру.';
          } else {
            $reply = app_append_reply($leadRow['reply'] ?? null, 'Администратор назначил мастера: ' . (string)$m['name']);
            db()->prepare('UPDATE leads SET master_id=:mid, status="in_work", reply=:reply WHERE id=:id')->execute([':mid'=>$masterId, ':reply'=>$reply, ':id'=>$id]);
            db()->prepare('INSERT INTO lead_events (lead_id, actor, message) VALUES (:id, "admin", :message)')->execute([':id'=>$id, ':message'=>'Администратор отправил заявку мастеру: ' . (string)$m['name']]);
            $flash = 'Заявка отправлена выбранному мастеру.';
          }
        }
      }
    }

    if ($action === 'save' && $id > 0) {
      $name = trim((string)($_POST['name'] ?? ''));
      $phone = trim((string)($_POST['phone'] ?? ''));
      $email = trim((string)($_POST['email'] ?? ''));
      $district = trim((string)($_POST['district'] ?? ''));
      $service = trim((string)($_POST['service'] ?? ''));
      $description = trim((string)($_POST['description'] ?? ''));
      $reply = trim((string)($_POST['reply'] ?? ''));
      $status = (string)($_POST['status'] ?? 'new');
      $masterId = (int)($_POST['master_id'] ?? 0);
      if (!in_array($status, $allowed, true)) $status = 'new';
      if ($name === '' || $phone === '' || $email === '' || $service === '') {
        $errors[] = 'Имя, телефон, email и услуга обязательны.';
      } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Некорректный email клиента.';
      } else {
        $stmt = db()->prepare('UPDATE leads SET name=:name, phone=:phone, email=:email, district=:district, service=:service, description=:description, reply=:reply, status=:status, master_id=:master_id WHERE id=:id');
        $stmt->execute([
          ':name'=>mb_substr($name,0,120),
          ':phone'=>mb_substr($phone,0,32),
          ':email'=>mb_substr($email,0,160),
          ':district'=>$district !== '' ? mb_substr($district,0,180) : null,
          ':service'=>mb_substr($service,0,160),
          ':description'=>$description !== '' ? mb_substr($description,0,500) : null,
          ':reply'=>$reply !== '' ? $reply : null,
          ':status'=>$status,
          ':master_id'=>$masterId > 0 ? $masterId : null,
          ':id'=>$id,
        ]);
        db()->prepare('INSERT INTO lead_events (lead_id, actor, message) VALUES (:id, "admin", :message)')->execute([':id'=>$id, ':message'=>'Администратор обновил заявку. Статус: ' . app_status_label($status)]);
        $flash = 'Заявка сохранена и назначение обновлено.';
      }
    }
  }
}

$filter = (string)($_GET['status'] ?? 'all');
$search = trim((string)($_GET['q'] ?? ''));
$params = [];
$whereParts = [];
if ($filter !== 'all' && in_array($filter, $allowed, true)) { $whereParts[] = 'l.status = :status'; $params[':status'] = $filter; }
if ($search !== '') {
  $whereParts[] = '(CAST(l.id AS CHAR) LIKE :q OR l.name LIKE :q OR l.phone LIKE :q OR l.email LIKE :q OR l.service LIKE :q OR l.district LIKE :q OR l.access_code LIKE :q)';
  $params[':q'] = '%' . $search . '%';
}
$where = $whereParts ? 'WHERE ' . implode(' AND ', $whereParts) : '';
$stmt = db()->prepare("SELECT l.*, m.name AS master_name, m.specialization AS master_specialization, COALESCE(vc.views_count,0) AS views_count, COALESCE(vc.viewer_names,'') AS viewer_names FROM leads l LEFT JOIN masters m ON m.id=l.master_id LEFT JOIN (SELECT lv.lead_id, COUNT(*) AS views_count, GROUP_CONCAT(CONCAT(mm.name, ' — ', mm.specialization) ORDER BY lv.viewed_at DESC SEPARATOR '||') AS viewer_names FROM lead_views lv JOIN masters mm ON mm.id=lv.master_id GROUP BY lv.lead_id) vc ON vc.lead_id=l.id $where ORDER BY FIELD(l.status,'new','in_work','cancelled','done'), l.id DESC LIMIT 300");
$stmt->execute($params);
$leads = $stmt->fetchAll();
$counts = ['all'=>0,'new'=>0,'in_work'=>0,'done'=>0,'cancelled'=>0];
foreach (db()->query('SELECT status, COUNT(*) AS c FROM leads GROUP BY status')->fetchAll() as $row) { $counts[$row['status']] = (int)$row['c']; $counts['all'] += (int)$row['c']; }
$noMaster = (int)db()->query("SELECT COUNT(*) FROM leads WHERE master_id IS NULL AND status='new'")->fetchColumn();
$stale = (int)db()->query("SELECT COUNT(*) FROM leads WHERE master_id IS NULL AND status='new' AND created_at < (NOW() - INTERVAL 30 MINUTE)")->fetchColumn();
$masters = app_get_masters_for_select();
$pendingApps = (int)db()->query("SELECT COUNT(*) FROM master_applications WHERE status='pending'")->fetchColumn();
$lastEvents = db()->query('SELECT e.*, l.service FROM lead_events e LEFT JOIN leads l ON l.id=e.lead_id ORDER BY e.id DESC LIMIT 6')->fetchAll();
?>
<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Панель управления</title><link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800;900&display=swap" rel="stylesheet"><style>
:root{--accent:#2563eb;--dark:#111827;--line:rgba(17,24,39,.10);--bg:#f6f8fd;--muted:#667085;--good:#16a34a;--blue:#2563eb;--red:#dc2626;--shadow:0 16px 42px rgba(17,24,39,.08)}*{box-sizing:border-box}body{margin:0;font-family:Manrope,Arial,sans-serif;background:radial-gradient(circle at top left,rgba(37,99,235,.11),transparent 34%),var(--bg);color:var(--dark)}a{text-decoration:none;color:inherit}.top{position:sticky;top:0;z-index:10;background:rgba(255,255,255,.94);backdrop-filter:blur(12px);border-bottom:1px solid var(--line)}.top__row{width:min(1360px,calc(100% - 32px));margin:auto;display:flex;align-items:center;justify-content:space-between;gap:16px;padding:14px 0}.brand{font-weight:1000;font-size:18px}.nav{display:flex;gap:8px;flex-wrap:wrap}.tab{display:inline-flex;align-items:center;gap:6px;padding:10px 13px;border-radius:14px;background:#fff;border:1px solid var(--line);font-weight:900}.tab.is-active{background:var(--accent);color:#fff;border-color:var(--accent)}.container{width:min(1360px,calc(100% - 32px));margin:auto;padding:24px 0 40px}.hero{display:flex;justify-content:space-between;gap:18px;align-items:end;margin-bottom:18px}.hero h1{font-size:38px;line-height:1;margin:0 0 8px;letter-spacing:-.04em}.muted{color:var(--muted);font-weight:750}.stats{display:grid;grid-template-columns:repeat(7,1fr);gap:12px;margin:16px 0}.stat{background:#fff;border:1px solid var(--line);border-radius:22px;padding:16px;box-shadow:var(--shadow)}.stat b{display:block;font-size:28px}.stat span{display:block;margin-top:3px;color:var(--muted);font-size:12px;font-weight:900;text-transform:uppercase}.stat.warn{background:#eff6ff;border-color:rgba(37,99,235,.20)}.stat.danger{background:#fef2f2;border-color:rgba(220,38,38,.20)}.notice{padding:14px 16px;border-radius:16px;margin:14px 0;font-weight:900}.ok{background:rgba(22,163,74,.12);color:#15803d}.bad{background:rgba(220,38,38,.12);color:#b91c1c}.toolbar{display:grid;grid-template-columns:1fr auto;gap:12px;margin:10px 0 18px}.search{display:grid;grid-template-columns:1fr 150px auto;gap:10px;background:#fff;padding:12px;border:1px solid var(--line);border-radius:20px;box-shadow:var(--shadow)}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.card{position:relative;background:#fff;border:1px solid var(--line);border-radius:24px;padding:18px;box-shadow:var(--shadow);overflow:hidden}.card:before{content:'';position:absolute;left:0;top:0;bottom:0;width:6px;background:#e5e7eb}.card.status-new:before{background:#60a5fa}.card.status-in_work:before{background:#2563eb}.card.status-done:before{background:#16a34a}.card.status-cancelled:before{background:#dc2626}.cardHead{display:flex;justify-content:space-between;gap:14px;align-items:flex-start;margin-bottom:14px}.cardHead b{font-size:22px}.cardHead small{display:block;margin-top:5px;color:var(--muted);font-weight:800}.badge{display:inline-flex;border-radius:999px;padding:7px 10px;font-size:12px;font-weight:950}.new{background:rgba(37,99,235,.12);color:#1d4ed8}.in_work{background:rgba(37,99,235,.12);color:#1d4ed8}.done{background:rgba(22,163,74,.13);color:#15803d}.cancelled{background:rgba(220,38,38,.12);color:#b91c1c}.formGrid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.wide{grid-column:1/-1}label{display:grid;gap:7px;color:#475467;font-size:12px;text-transform:uppercase;letter-spacing:.04em;font-weight:950}input,select,textarea{width:100%;font:inherit;border:1px solid var(--line);border-radius:12px;padding:10px 12px;background:#fff;color:var(--dark);outline:none}textarea{min-height:74px;max-height:145px;resize:vertical}.btn{border:0;border-radius:12px;padding:10px 13px;font-weight:950;cursor:pointer;display:inline-flex;align-items:center;justify-content:center}.save{background:var(--dark);color:#fff}.delete{background:rgba(220,38,38,.11);color:#b91c1c}.soft{background:#fff;border:1px solid var(--line)}.assign{background:rgba(37,99,235,.12);color:#1d4ed8}.actions{display:flex;gap:8px;flex-wrap:wrap}.miniLine{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:10px 0;color:#667085;font-weight:800;font-size:13px}.pill{display:inline-flex;gap:6px;align-items:center;border:1px solid var(--line);border-radius:999px;background:#fbfbfd;padding:7px 10px}.empty{padding:28px;text-align:center}.sideBy{display:grid;grid-template-columns:1fr .55fr;gap:16px}.events{background:#fff;border:1px solid var(--line);border-radius:24px;padding:18px;box-shadow:var(--shadow)}.events h3{margin:0 0 10px}.events div{padding:10px 0;border-top:1px solid var(--line);font-weight:800}.events small{display:block;color:var(--muted);margin-top:3px}.loginBox{max-width:460px;margin:8vh auto;padding:26px;background:#fff;border:1px solid var(--line);border-radius:28px;box-shadow:var(--shadow)}details summary{cursor:pointer;font-weight:950;color:#1746a2;margin-bottom:10px}.quickAssign{display:grid;grid-template-columns:minmax(180px,1fr) auto;gap:8px;margin:12px 0 14px;padding:12px;border:1px solid var(--line);border-radius:18px;background:#f8fbff}.leadActions{margin-top:12px}.leadActions .btn{min-height:42px}.viewerMini{width:100%;margin-top:4px;padding:9px 11px;border:1px solid var(--line);border-radius:14px;background:#fff;color:#475467;font-size:12px;line-height:1.5}.viewerMini b{color:#111827}@media(max-width:1100px){.sideBy{grid-template-columns:1fr}.stats{grid-template-columns:repeat(2,1fr)}}@media(max-width:760px){.grid,.formGrid,.toolbar,.search,.quickAssign{grid-template-columns:1fr}.wide{grid-column:auto}.top__row,.hero{display:block}.nav{margin-top:12px}.hero h1{font-size:31px}.container,.top__row{width:min(100% - 20px,1360px)}.stats{grid-template-columns:1fr}}
</style><link rel="stylesheet" href="admin.css?v=20260531-premium"></head><body>
<div class="top"><div class="top__row"><div class="brand">Диспетчерская <?= h(APP_NAME) ?></div><div class="nav"><a class="tab is-active" href="index.php">Заявки</a><a class="tab" href="masters.php">Мастера</a><a class="tab" href="master_applications.php">Заявки мастеров</a><a class="tab" href="reviews.php">Отзывы</a><a class="tab" href="../index.php" target="_blank" rel="noopener">Открыть сайт</a></div><div class="nav"><span class="tab"><?= h($_SESSION['admin']['login'] ?? 'admin') ?></span><a class="tab" href="logout.php">Выйти</a></div></div></div>
<main class="container">
  <div class="hero"><div><h1>Заявки клиентов</h1><div class="muted">Центр управления: новые заявки, назначение мастеров, ответы клиентам, поиск и зависшие обращения.</div></div><a class="tab" href="index.php">Обновить</a></div>
  <div class="stats"><?php foreach (['all'=>'Все','new'=>'Новые','in_work'=>'В работе','done'=>'Завершены','cancelled'=>'Отменены'] as $key=>$label): ?><a class="stat" href="index.php?status=<?= urlencode($key) ?>"><b><?= (int)($counts[$key] ?? 0) ?></b><span><?= h($label) ?></span></a><?php endforeach; ?><a class="stat warn" href="index.php?status=new"><b><?= $noMaster ?></b><span>Без мастера</span></a><a class="stat danger" href="master_applications.php"><b><?= $pendingApps ?></b><span>Мастера ждут</span></a></div>
  <?php if($stale > 0): ?><div class="notice bad">⚠️ <?= $stale ?> заявок без мастера больше 30 минут. Их лучше обработать первыми.</div><?php endif; ?>
  <?php if ($flash): ?><div class="notice ok"><?= h($flash) ?></div><?php endif; ?><?php if ($errors): ?><div class="notice bad"><?php foreach($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?></div><?php endif; ?>
  <form class="toolbar" method="get"><div class="search"><input name="q" value="<?= h($search) ?>" placeholder="Поиск: номер, имя, телефон, email, услуга, район, код"><select name="status"><option value="all">Все статусы</option><?php foreach($allowed as $st): ?><option value="<?= h($st) ?>" <?= $filter===$st?'selected':'' ?>><?= h(app_status_label($st)) ?></option><?php endforeach; ?></select><button class="btn save">Найти</button></div></form>
  <div class="sideBy"><div class="grid"><?php if (!$leads): ?><div class="card empty muted">Пока нет заявок по выбранному фильтру.</div><?php endif; ?><?php foreach ($leads as $l): ?>
    <article class="card status-<?= app_status_class((string)$l['status']) ?>"><div class="cardHead"><div><b>#<?= (int)$l['id'] ?> · <?= h($l['service']) ?></b><small><?= h((string)$l['created_at']) ?> · код клиента: <?= h($l['access_code'] ?? '') ?></small></div><span class="badge <?= app_status_class((string)$l['status']) ?>"><?= app_status_label((string)$l['status']) ?></span></div><div class="miniLine"><span class="pill">👤 <?= h($l['name']) ?></span><span class="pill">📞 <?= h($l['phone']) ?></span><span class="pill">✉️ <?= h($l['email'] ?? 'нет email') ?></span><?php if(!empty($l['district'])): ?><span class="pill">📍 <?= h($l['district']) ?></span><?php endif; ?><span class="pill">👁 <?= (int)$l['views_count'] ?> просмотров</span><span class="pill">🧰 <?= $l['master_name'] ? h($l['master_name']) : 'Без мастера' ?></span><?php if(!empty($l['viewer_names'])): ?><div class="viewerMini"><b>Кто смотрел:</b> <?= h(str_replace('||', ', ', (string)$l['viewer_names'])) ?></div><?php endif; ?></div>
      <?php if(!in_array((string)$l['status'], ['done','cancelled'], true)): ?><form class="quickAssign" method="post" action="index.php?status=<?= urlencode($filter) ?>&q=<?= urlencode($search) ?>"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="quick_assign"><input type="hidden" name="id" value="<?= (int)$l['id'] ?>"><select name="master_id"><option value="0">Без мастера</option><?php foreach($masters as $m): ?><option value="<?= (int)$m['id'] ?>" <?= (int)($l['master_id'] ?? 0)===(int)$m['id']?'selected':'' ?>><?= h($m['name'].' — '.$m['specialization']) ?></option><?php endforeach; ?></select><button class="btn assign" type="submit">Отправить мастеру</button></form><?php endif; ?>
      <div class="actions leadActions">
        <a class="btn save" href="lead_edit.php?id=<?= (int)$l['id'] ?>&status=<?= urlencode($filter) ?>&q=<?= urlencode($search) ?>">Открыть / редактировать</a>
        <a class="btn soft" href="../client/index.php?token=<?= urlencode((string)$l['client_token']) ?>" target="_blank">Как видит клиент</a>
      </div>
    </article><?php endforeach; ?></div><aside class="events"><h3>Последние действия</h3><?php if(!$lastEvents): ?><div class="muted">История появится после новых заявок.</div><?php endif; ?><?php foreach($lastEvents as $ev): ?><div>#<?= (int)$ev['lead_id'] ?> · <?= h($ev['message']) ?><small><?= h($ev['created_at']) ?> · <?= h($ev['service'] ?? '') ?></small></div><?php endforeach; ?></aside></div>
</main></body></html>
