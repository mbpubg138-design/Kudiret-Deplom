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
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$filter = (string)($_GET['status'] ?? $_POST['status_filter'] ?? 'all');
$search = (string)($_GET['q'] ?? $_POST['q_filter'] ?? '');
$back = 'index.php?status=' . urlencode($filter) . '&q=' . urlencode($search);
$masters = app_get_masters_for_select();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_verify($_POST['csrf'] ?? '')) {
    $errors[] = 'Ошибка безопасности. Обновите страницу.';
  } else {
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'save' && $id > 0) {
      $name = trim((string)($_POST['name'] ?? ''));
      $phone = trim((string)($_POST['phone'] ?? ''));
      $email = trim((string)($_POST['email'] ?? ''));
      $district = trim((string)($_POST['district'] ?? ''));
      $service = trim((string)($_POST['service'] ?? ''));
      $description = trim((string)($_POST['description'] ?? ''));
      $reply = trim((string)($_POST['reply'] ?? ''));
      $status = (string)($_POST['lead_status'] ?? 'new');
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
        $masterText = 'без мастера';
        if ($masterId > 0) {
          foreach ($masters as $m) { if ((int)$m['id'] === $masterId) { $masterText = (string)$m['name']; break; } }
        }
        db()->prepare('INSERT INTO lead_events (lead_id, actor, message) VALUES (:id, "admin", :message)')->execute([':id'=>$id, ':message'=>'Администратор обновил заявку. Статус: ' . app_status_label($status) . '. Мастер: ' . $masterText]);
        $flash = 'Заявка сохранена.';
      }
    }

    if ($action === 'delete' && $id > 0) {
      db()->prepare('DELETE FROM lead_views WHERE lead_id=:id')->execute([':id'=>$id]);
      db()->prepare('DELETE FROM lead_events WHERE lead_id=:id')->execute([':id'=>$id]);
      db()->prepare('DELETE FROM leads WHERE id=:id')->execute([':id'=>$id]);
      header('Location: ' . $back);
      exit;
    }
  }
}

$stmt = db()->prepare('SELECT l.*, m.name AS master_name, m.specialization AS master_specialization FROM leads l LEFT JOIN masters m ON m.id=l.master_id WHERE l.id=:id LIMIT 1');
$stmt->execute([':id'=>$id]);
$lead = $stmt->fetch();
if (!$lead) { $errors[] = 'Заявка не найдена.'; }
$viewers = $lead ? app_lead_viewers((int)$lead['id']) : [];
$events = [];
if ($lead) {
  $ev = db()->prepare('SELECT actor, message, created_at FROM lead_events WHERE lead_id=:id ORDER BY id DESC LIMIT 30');
  $ev->execute([':id'=>$id]);
  $events = $ev->fetchAll();
}
?>
<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Редактирование заявки</title><link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800;900&display=swap" rel="stylesheet"><style>
:root{--accent:#2563eb;--dark:#111827;--line:rgba(17,24,39,.10);--bg:#f6f8fd;--muted:#667085;--green:#16a34a;--red:#dc2626;--shadow:0 18px 46px rgba(15,23,42,.07)}*{box-sizing:border-box}body{margin:0;font-family:Manrope,Arial,sans-serif;background:radial-gradient(circle at top left,rgba(37,99,235,.12),transparent 34%),var(--bg);color:var(--dark)}a{text-decoration:none;color:inherit}.top{position:sticky;top:0;z-index:10;background:rgba(255,255,255,.94);backdrop-filter:blur(14px);border-bottom:1px solid var(--line)}.row,.container{width:min(1180px,calc(100% - 28px));margin:auto}.row{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:14px 0}.brand{font-weight:1000;font-size:18px}.nav{display:flex;gap:8px;flex-wrap:wrap}.tab,.btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:42px;padding:10px 14px;border-radius:14px;background:#fff;border:1px solid var(--line);font-weight:900;cursor:pointer}.tab.primary,.btn.save{background:linear-gradient(135deg,#2563eb,#60a5fa);border-color:transparent;color:#fff;box-shadow:0 14px 28px rgba(37,99,235,.20)}.container{padding:24px 0 48px}.hero{display:flex;justify-content:space-between;align-items:flex-start;gap:18px;margin-bottom:18px;padding:24px;border:1px solid var(--line);border-radius:28px;background:linear-gradient(135deg,#fff,#f7fbff);box-shadow:var(--shadow)}h1{font-size:38px;letter-spacing:-.045em;line-height:1;margin:0 0 8px}.muted{color:var(--muted);font-weight:800}.badge{display:inline-flex;border-radius:999px;padding:8px 12px;font-size:12px;font-weight:950}.new{background:rgba(37,99,235,.13);color:#1746a2}.in_work{background:rgba(37,99,235,.13);color:#1d4ed8}.done{background:rgba(22,163,74,.13);color:#15803d}.cancelled{background:rgba(220,38,38,.12);color:#b91c1c}.grid{display:grid;grid-template-columns:minmax(0,1fr) 360px;gap:16px;align-items:start}.card{background:#fff;border:1px solid var(--line);border-radius:26px;padding:20px;box-shadow:var(--shadow)}.formGrid{display:grid;grid-template-columns:1fr 1fr;gap:13px}.wide{grid-column:1/-1}label{display:grid;gap:8px;color:#475467;font-size:12px;text-transform:uppercase;letter-spacing:.04em;font-weight:950}input,select,textarea{width:100%;font:inherit;border:1px solid var(--line);border-radius:14px;padding:12px 13px;background:#fff;color:var(--dark);outline:none}input:focus,select:focus,textarea:focus{border-color:rgba(37,99,235,.40);box-shadow:0 0 0 4px rgba(37,99,235,.10)}textarea{min-height:118px;max-height:118px;resize:none;overflow:auto}.actions{display:flex;gap:9px;flex-wrap:wrap}.delete{background:rgba(220,38,38,.10);color:#b91c1c}.soft{background:#fff;border:1px solid var(--line)}.notice{padding:14px 16px;border-radius:16px;margin:0 0 16px;font-weight:900}.ok{background:rgba(22,163,74,.12);color:#15803d}.bad{background:rgba(220,38,38,.12);color:#b91c1c}.miniLine{display:flex;gap:8px;flex-wrap:wrap;margin:10px 0}.pill{display:inline-flex;gap:6px;align-items:center;border:1px solid var(--line);border-radius:999px;background:#fbfbfd;padding:8px 11px;font-weight:900;color:#475467}.sideList{display:grid;gap:9px}.sideList div{padding:12px 13px;border:1px solid var(--line);border-radius:16px;background:#fbfbfd;font-weight:800}.sideList small{display:block;margin-top:4px;color:var(--muted)}@media(max-width:900px){.grid,.formGrid{grid-template-columns:1fr}.wide{grid-column:auto}.row,.hero{display:block}.nav{margin-top:10px}h1{font-size:31px}}
</style><link rel="stylesheet" href="admin.css?v=20260601-lead-edit"></head><body>
<div class="top"><div class="row"><div class="brand">Редактирование заявки</div><div class="nav"><a class="tab primary" href="<?= h($back) ?>">← Назад к заявкам</a><a class="tab" href="index.php">Все заявки</a></div></div></div>
<main class="container">
<?php if ($flash): ?><div class="notice ok"><?= h($flash) ?></div><?php endif; ?><?php if ($errors): ?><div class="notice bad"><?php foreach($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?></div><?php endif; ?>
<?php if ($lead): ?>
<section class="hero"><div><h1>#<?= (int)$lead['id'] ?> · <?= h($lead['service']) ?></h1><div class="muted">Создана: <?= h((string)$lead['created_at']) ?> · код клиента: <?= h($lead['access_code'] ?? '') ?></div><div class="miniLine"><span class="pill">👤 <?= h($lead['name']) ?></span><span class="pill">📞 <?= h($lead['phone']) ?></span><span class="pill">✉️ <?= h($lead['email'] ?? 'нет email') ?></span><?php if(!empty($lead['district'])): ?><span class="pill">📍 <?= h($lead['district']) ?></span><?php endif; ?></div></div><span class="badge <?= app_status_class((string)$lead['status']) ?>"><?= app_status_label((string)$lead['status']) ?></span></section>
<div class="grid"><section class="card"><form class="formGrid" method="post"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)$lead['id'] ?>"><input type="hidden" name="status_filter" value="<?= h($filter) ?>"><input type="hidden" name="q_filter" value="<?= h($search) ?>"><label>Клиент<input name="name" value="<?= h($lead['name']) ?>" required></label><label>Телефон<input name="phone" value="<?= h($lead['phone']) ?>" required></label><label>Email<input name="email" type="email" value="<?= h($lead['email'] ?? '') ?>" required></label><label>Район / адрес<input name="district" value="<?= h($lead['district'] ?? '') ?>"></label><label>Услуга<input name="service" value="<?= h($lead['service']) ?>" required></label><label>Статус<select name="lead_status"><?php foreach($allowed as $st): ?><option value="<?= h($st) ?>" <?= $lead['status']===$st?'selected':'' ?>><?= h(app_status_label($st)) ?></option><?php endforeach; ?></select></label><label class="wide">Назначить мастера<select name="master_id"><option value="0">Без мастера / общая заявка</option><?php foreach($masters as $m): ?><option value="<?= (int)$m['id'] ?>" <?= (int)($lead['master_id'] ?? 0)===(int)$m['id']?'selected':'' ?>><?= h($m['name'].' — '.$m['specialization']) ?></option><?php endforeach; ?></select></label><label class="wide">Описание клиента<textarea name="description" maxlength="500"><?= h($lead['description'] ?? '') ?></textarea></label><label class="wide">Ответ / заметка администратора<textarea name="reply" placeholder="Например: мастер назначен, согласован выезд, клиенту перезвонили"><?= h($lead['reply'] ?? '') ?></textarea></label><div class="wide actions"><button class="btn save" type="submit">Сохранить изменения</button><a class="btn soft" href="../client/index.php?token=<?= urlencode((string)$lead['client_token']) ?>" target="_blank">Посмотреть как клиент</a><a class="btn soft" href="<?= h($back) ?>">Назад</a></div></form><form method="post" onsubmit="return confirm('Удалить заявку полностью?');" style="margin-top:12px"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$lead['id'] ?>"><input type="hidden" name="status_filter" value="<?= h($filter) ?>"><input type="hidden" name="q_filter" value="<?= h($search) ?>"><button class="btn delete" type="submit">Удалить заявку полностью</button></form></section>
<aside class="card"><h2>Контроль заявки</h2><div class="miniLine"><span class="pill">🧰 <?= $lead['master_name'] ? h($lead['master_name']) : 'Без мастера' ?></span><span class="pill">👁 <?= count($viewers) ?> просмотров</span></div><h3>Кто смотрел</h3><div class="sideList"><?php if(!$viewers): ?><div>Пока никто из мастеров не открывал заявку.</div><?php endif; ?><?php foreach($viewers as $v): ?><div><?= h($v['name']) ?><small><?= h($v['specialization']) ?> · <?= h($v['viewed_at']) ?></small></div><?php endforeach; ?></div><h3>История</h3><div class="sideList"><?php if(!$events): ?><div>История пока пустая.</div><?php endif; ?><?php foreach($events as $ev): ?><div><?= h($ev['message']) ?><small><?= h($ev['created_at']) ?> · <?= h($ev['actor']) ?></small></div><?php endforeach; ?></div></aside></div>
<?php endif; ?>
</main></body></html>
