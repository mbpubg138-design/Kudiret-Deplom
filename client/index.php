<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/app_models.php';
require_once __DIR__ . '/../includes/csrf.php';
csrf_start();

$errors = [];
$flash = '';
$lead = null;
$token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_verify($_POST['csrf'] ?? '')) {
    $errors[] = 'Ошибка безопасности. Обновите страницу.';
  } else {
    $action = (string)($_POST['action'] ?? 'find');

    if ($action === 'find') {
      $code = strtoupper(trim((string)($_POST['access_code'] ?? '')));
      $contact = trim((string)($_POST['contact'] ?? ''));
      $stmt = db()->prepare('SELECT * FROM leads WHERE access_code=:code AND (phone=:contact OR email=:contact) LIMIT 1');
      $stmt->execute([':code' => $code, ':contact' => $contact]);
      $lead = $stmt->fetch();
      if ($lead) $token = (string)$lead['client_token'];
      else $errors[] = 'Заявка не найдена. Проверьте код и телефон или email.';
    }

    if ($action === 'save' && $token !== '') {
      $name = trim((string)($_POST['name'] ?? ''));
      $phone = trim((string)($_POST['phone'] ?? ''));
      $email = trim((string)($_POST['email'] ?? ''));
      $district = trim((string)($_POST['district'] ?? ''));
      $service = trim((string)($_POST['service'] ?? ''));
      $description = trim((string)($_POST['description'] ?? ''));
      if ($name === '' || $phone === '' || $email === '' || $service === '') {
        $errors[] = 'Имя, телефон, email и услуга обязательны.';
      } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Введите корректный email.';
      } else {
        $stmt = db()->prepare('UPDATE leads SET name=:name, phone=:phone, email=:email, district=:district, service=:service, description=:description WHERE client_token=:token AND status IN ("new","in_work")');
        $stmt->execute([
          ':name' => mb_substr($name, 0, 120),
          ':phone' => mb_substr($phone, 0, 32),
          ':email' => mb_substr($email, 0, 160),
          ':district' => $district !== '' ? mb_substr($district, 0, 180) : null,
          ':service' => mb_substr($service, 0, 160),
          ':description' => $description !== '' ? mb_substr($description, 0, 500) : null,
          ':token' => $token,
        ]);
        if ($stmt->rowCount() > 0) {
          db()->prepare('INSERT INTO lead_events (lead_id, actor, message) SELECT id, "client", "Клиент обновил данные заявки" FROM leads WHERE client_token=:token')->execute([':token' => $token]);
          $flash = 'Заявка обновлена.';
        } else {
          $errors[] = 'Эту заявку уже нельзя изменить: она завершена или отменена.';
        }
      }
    }

    if ($action === 'cancel' && $token !== '') {
      $reasonSelect = trim((string)($_POST['cancel_reason'] ?? ''));
      $reasonOther = trim((string)($_POST['cancel_reason_other'] ?? ''));
      $reason = $reasonSelect === 'Другая причина' ? $reasonOther : $reasonSelect;
      if ($reason === '') $reason = 'Клиент отменил заявку';
      $stmt = db()->prepare('UPDATE leads SET status="cancelled", cancel_reason=:reason, reply=:reply WHERE client_token=:token AND status <> "done"');
      $currentStmt = db()->prepare('SELECT id, reply FROM leads WHERE client_token=:token LIMIT 1');
      $currentStmt->execute([':token' => $token]);
      $current = $currentStmt->fetch();
      $reply = app_append_reply($current['reply'] ?? null, 'Клиент отменил заявку. Причина: ' . $reason);
      $stmt->execute([':reason' => mb_substr($reason, 0, 255), ':reply' => $reply, ':token' => $token]);
      if ($current) {
        db()->prepare('INSERT INTO lead_events (lead_id, actor, message) VALUES (:id, "client", :message)')->execute([':id' => (int)$current['id'], ':message' => 'Клиент отменил заявку. Причина: ' . $reason]);
      }
      $flash = 'Заявка отменена. Данные останутся в системе для истории.';
    }
  }
}

if ($token !== '' && !$lead) {
  $stmt = db()->prepare('SELECT l.*, m.name AS master_name, m.specialization AS master_specialization FROM leads l LEFT JOIN masters m ON m.id=l.master_id WHERE l.client_token=:token LIMIT 1');
  $stmt->execute([':token' => $token]);
  $lead = $stmt->fetch();
  if (!$lead && $_SERVER['REQUEST_METHOD'] !== 'POST') $errors[] = 'Ссылка заявки не найдена.';
}

$viewers = $lead ? app_lead_viewers((int)$lead['id']) : [];
$viewCount = count($viewers);
$events = [];
if ($lead) {
  $stmt = db()->prepare('SELECT actor, message, created_at FROM lead_events WHERE lead_id=:id ORDER BY created_at ASC');
  $stmt->execute([':id' => (int)$lead['id']]);
  $events = $stmt->fetchAll();
}
$steps = [
  1 => 'Заявка принята',
  2 => 'Ожидает мастера',
  3 => 'Мастер назначен',
  4 => 'Диагностика / ремонт',
  5 => 'Завершено',
];
$activeStep = $lead ? app_status_step((string)$lead['status']) : 1;
?><!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Кабинет заявки клиента</title><link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800;900&display=swap" rel="stylesheet"><style>
:root{--accent:#2563eb;--accent-dark:#1d4ed8;--dark:#111827;--line:rgba(17,24,39,.09);--bg:#f4f7fc;--panel:#ffffff;--muted:#667085;--good:#16a34a;--red:#dc2626;--shadow:0 22px 55px rgba(15,23,42,.08);--shadow-soft:0 12px 28px rgba(15,23,42,.06)}*{box-sizing:border-box}body{margin:0;font-family:Manrope,Arial,sans-serif;background:radial-gradient(circle at top left,rgba(37,99,235,.12),transparent 34%),linear-gradient(180deg,#f8fbff 0%,#f3f7fc 55%,#fff 100%);color:var(--dark);-webkit-font-smoothing:antialiased}a{text-decoration:none;color:inherit}button,input,select,textarea{font:inherit}button{cursor:pointer}.top{position:sticky;top:0;z-index:10;background:rgba(255,255,255,.92);backdrop-filter:blur(14px);border-bottom:1px solid var(--line);box-shadow:0 10px 30px rgba(15,23,42,.04)}.row,.container{width:min(1100px,calc(100% - 28px));margin:auto}.row{display:flex;justify-content:space-between;align-items:center;gap:14px;padding:14px 0}.brandWrap{display:grid;gap:2px}.brand{font-size:18px;font-weight:1000;letter-spacing:-.02em}.brandWrap small{color:var(--muted);font-size:12px;font-weight:800}.nav{display:flex;gap:10px;flex-wrap:wrap}.tab,.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:12px 16px;border-radius:16px;border:1px solid var(--line);background:#fff;color:var(--dark);font-weight:900;box-shadow:var(--shadow-soft)}.tab.primary,.btn.primary{background:linear-gradient(135deg,var(--accent),#60a5fa);border-color:transparent;color:#fff;box-shadow:0 14px 30px rgba(37,99,235,.22)}.container{padding:34px 0 48px}.hero{display:grid;grid-template-columns:minmax(0,1fr) minmax(280px,.48fr);gap:18px;align-items:stretch;margin-bottom:22px}.heroCard,.card,.findBox{background:rgba(255,255,255,.94);border:1px solid rgba(17,24,39,.07);border-radius:30px;box-shadow:var(--shadow)}.heroCard{padding:30px 30px 28px;background:linear-gradient(135deg,#ffffff 0%,#f7fbff 55%,#eef5ff 100%)}.heroCard h1{font-size:clamp(36px,5vw,60px);line-height:.96;margin:0 0 12px;letter-spacing:-.06em}.heroCard p{margin:0;color:#556070;font-size:16px;line-height:1.7;font-weight:800;max-width:620px}.heroMeta{padding:24px;display:grid;gap:14px;align-content:center}.heroMetaItem{padding:16px 18px;border-radius:22px;background:#fff;border:1px solid var(--line);box-shadow:var(--shadow-soft)}.heroMetaItem b{display:block;font-size:14px}.heroMetaItem span{display:block;margin-top:6px;color:var(--muted);font-size:13px;font-weight:800;line-height:1.55}.notice{padding:14px 16px;border-radius:18px;margin:0 0 16px;font-weight:900}.ok{background:rgba(22,163,74,.12);color:#15803d}.bad{background:rgba(220,38,38,.12);color:#b91c1c}.findBox{max-width:580px;margin:0 auto;padding:28px}.findBox h2,.card h2{margin:0 0 10px;font-size:34px;letter-spacing:-.04em}.findBox p,.cardLead,.sectionNote{margin:0;color:var(--muted);line-height:1.7;font-weight:800}.formGrid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:18px}.wide{grid-column:1/-1}label{display:grid;gap:8px;color:#344054;font-size:12px;text-transform:uppercase;letter-spacing:.04em;font-weight:950}input,select,textarea{width:100%;border:1px solid rgba(17,24,39,.10);border-radius:14px;padding:13px 14px;background:#fff;color:var(--dark);outline:none;box-shadow:none}input:focus,select:focus,textarea:focus{border-color:rgba(37,99,235,.40);box-shadow:0 0 0 4px rgba(37,99,235,.10)}textarea{min-height:120px;max-height:120px;resize:none;overflow:auto}.btn{border:0}.btn.save{background:#111827;color:#fff}.btn.delete{background:rgba(220,38,38,.10);color:#b91c1c}.btn.primary.wide,.btn.save.wide,.btn.delete.wide{min-height:50px}.leadLayout{display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:18px;align-items:start}.card{padding:24px}.cardTop{display:flex;justify-content:space-between;gap:14px;align-items:flex-start;margin-bottom:14px}.cardTop h2{font-size:30px;margin:0 0 6px}.muted{color:var(--muted);font-weight:800}.badge{display:inline-flex;border-radius:999px;padding:8px 12px;font-size:12px;font-weight:950}.new{background:rgba(37,99,235,.13);color:#1746a2}.in_work{background:rgba(37,99,235,.13);color:#1d4ed8}.done{background:rgba(22,163,74,.13);color:#15803d}.cancelled{background:rgba(220,38,38,.12);color:#b91c1c}.progress{display:grid;grid-template-columns:repeat(5,1fr);gap:8px;margin:18px 0}.step{position:relative;min-height:84px;padding:12px;border:1px solid var(--line);border-radius:18px;background:#fbfcff;font-size:12px;font-weight:900;color:#667085}.step b{display:grid;place-items:center;width:28px;height:28px;border-radius:50%;background:#e5e7eb;color:#111827;margin-bottom:8px}.step.is-active{background:rgba(37,99,235,.08);border-color:rgba(37,99,235,.22);color:#1746a2}.step.is-active b{background:var(--accent);color:#fff}.step.is-cancelled{background:rgba(220,38,38,.08);border-color:rgba(220,38,38,.22);color:#b91c1c}.miniLine{display:flex;gap:8px;flex-wrap:wrap;margin:10px 0 18px}.pill{display:inline-flex;gap:6px;align-items:center;border:1px solid var(--line);border-radius:999px;background:#fbfbfd;padding:8px 11px;font-weight:900;color:#475467;font-size:13px}.replyBox{padding:16px 18px;border-radius:20px;background:linear-gradient(135deg,#eef5ff,#fff);border:1px solid rgba(37,99,235,.14);margin:0 0 18px}.reply{white-space:pre-wrap}.statusSide{display:grid;gap:14px}.sideBlock{padding:18px;border-radius:24px;background:#fff;border:1px solid var(--line);box-shadow:var(--shadow)}.sideBlock h3{margin:0 0 10px;font-size:18px;letter-spacing:-.02em}.eventList{display:grid;gap:10px}.eventList div{padding:12px 14px;border:1px solid var(--line);border-radius:16px;background:#fbfbfd;font-weight:800}.eventList small{display:block;margin-top:4px;color:var(--muted)}.actions{display:flex;gap:10px;flex-wrap:wrap}.cancelBox{margin-top:16px;padding-top:16px;border-top:1px solid var(--line)}.sideHint{font-size:13px;line-height:1.65;color:var(--muted);font-weight:800}.emptyState{max-width:580px;margin:0 auto;padding:22px 0 4px;text-align:center;color:var(--muted);font-weight:800}.helperPoints{display:grid;gap:10px;margin-top:18px}.helperPoints div{padding:12px 14px;border-radius:16px;background:#fbfbfd;border:1px solid var(--line);font-weight:800;color:#344054}.lastUpdate{font-size:13px;color:var(--muted);font-weight:800}.clientDetails{margin-top:18px;border:1px solid var(--line);border-radius:22px;background:#fff;overflow:hidden}.clientDetails summary{cursor:pointer;list-style:none;padding:15px 18px;font-weight:950;color:#1746a2;background:linear-gradient(135deg,#eef5ff,#fff);border-bottom:1px solid var(--line)}.clientDetails summary::-webkit-details-marker{display:none}.clientDetails .formGrid{padding:0 18px 18px}.viewerList{display:grid;gap:10px}.viewerList div{padding:12px 14px;border:1px solid var(--line);border-radius:16px;background:#fbfbfd;font-weight:850}.viewerList b{display:block}.viewerList small{display:block;margin-top:4px;color:var(--muted)}@media(max-width:980px){.hero,.leadLayout{grid-template-columns:1fr}.heroMeta{grid-template-columns:1fr 1fr}.progress{grid-template-columns:1fr 1fr}}@media(max-width:760px){.row,.container{width:min(100% - 20px,1100px)}.row{align-items:flex-start;flex-direction:column}.heroCard{padding:24px 22px}.heroCard h1{font-size:38px}.heroMeta{padding:20px}.findBox,.card{padding:20px}.findBox h2,.card h2{font-size:28px}.formGrid{grid-template-columns:1fr}.wide{grid-column:auto}.progress{grid-template-columns:1fr}.tab,.btn{width:100%}}</style></head><body>
<div class="top"><div class="row"><div class="brandWrap"><div class="brand">Кабинет заявки клиента</div><small>Проверка статуса, изменение данных и отмена обращения</small></div><div class="nav"><a class="tab" href="../index.php">На сайт</a><a class="tab primary" href="#lookup">Найти заявку</a></div></div></div>
<main class="container"><section class="hero"><div class="heroCard"><h1>Проверка заявки</h1><p>Откройте заявку по коду из письма. На этой странице сразу видно статус, назначенного мастера и ответ сервиса — без лишних блоков и перегруженного интерфейса.</p></div><aside class="heroMeta"><div class="heroMetaItem"><b>Что нужно ввести</b><span>Код заявки и телефон или email, который был указан при отправке формы.</span></div><div class="heroMetaItem"><b>Что можно сделать</b><span>Проверить статус, обновить описание проблемы и при необходимости отменить заявку.</span></div></aside></section>
<?php if ($flash): ?><div class="notice ok"><?= h($flash) ?></div><?php endif; ?><?php if ($errors): ?><div class="notice bad"><?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?></div><?php endif; ?>
<?php if (!$lead): ?>
  <section class="findBox" id="lookup"><h2>Найти заявку</h2><p>Введите код из письма и контакт, который вы указали в заявке. После этого откроется карточка обращения.</p><form class="formGrid" method="post"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="find"><label class="wide">Код заявки<input name="access_code" placeholder="Например: A1B2C3D4" required></label><label class="wide">Телефон или email<input name="contact" placeholder="+7 7__ ___ __ __ или email" required></label><button class="btn primary wide">Открыть заявку</button></form><div class="helperPoints"><div>Код приходит на email после отправки заявки.</div><div>Если заявка не находится — проверьте код и контактные данные.</div></div></section>
<?php else: ?>
<section class="leadLayout"><div class="card"><div class="cardTop"><div><h2>Заявка #<?= (int)$lead['id'] ?></h2><div class="lastUpdate">Создана: <?= h($lead['created_at']) ?> · код: <?= h($lead['access_code']) ?></div></div><span class="badge <?= app_status_class((string)$lead['status']) ?>"><?= app_status_label((string)$lead['status']) ?></span></div>
<?php if ((string)$lead['status'] === 'cancelled'): ?><div class="notice bad">Заявка отменена. <?= !empty($lead['cancel_reason']) ? 'Причина: ' . h($lead['cancel_reason']) : '' ?></div><?php endif; ?>
<div class="progress" aria-label="Статус заявки"><?php foreach ($steps as $num => $label): ?><div class="step <?= (string)$lead['status'] === 'cancelled' ? 'is-cancelled' : ($num <= $activeStep ? 'is-active' : '') ?>"><b><?= $num ?></b><span><?= h($label) ?></span></div><?php endforeach; ?></div>
<div class="miniLine"><span class="pill">👤 <?= $lead['master_name'] ? h($lead['master_name']) : 'Пока без назначенного мастера' ?></span><span class="pill">👁 Просмотров мастерами: <?= (int)$viewCount ?></span><?php if(!empty($lead['district'])): ?><span class="pill">📍 <?= h($lead['district']) ?></span><?php endif; ?><span class="pill">📞 <?= h($lead['phone']) ?></span></div>
<?php if (!empty($lead['reply'])): ?><div class="replyBox"><b>Ответ сервиса</b><div class="reply"><?= h($lead['reply']) ?></div></div><?php endif; ?>
<details class="clientDetails" open><summary>Открыть / закрыть данные заявки</summary><p class="cardLead" style="padding:16px 18px 0">Ниже можно изменить данные заявки, пока она не завершена и не отменена.</p>
<form class="formGrid" method="post"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="token" value="<?= h($token) ?>"><label>Имя<input name="name" value="<?= h($lead['name']) ?>" <?= in_array($lead['status'], ['done','cancelled'], true) ? 'disabled' : '' ?>></label><label>Телефон<input name="phone" value="<?= h($lead['phone']) ?>" <?= in_array($lead['status'], ['done','cancelled'], true) ? 'disabled' : '' ?>></label><label>Email<input name="email" type="email" value="<?= h($lead['email'] ?? '') ?>" <?= in_array($lead['status'], ['done','cancelled'], true) ? 'disabled' : '' ?>></label><label>Район / адрес<input name="district" value="<?= h($lead['district'] ?? '') ?>" <?= in_array($lead['status'], ['done','cancelled'], true) ? 'disabled' : '' ?>></label><label class="wide">Услуга<input name="service" value="<?= h($lead['service']) ?>" <?= in_array($lead['status'], ['done','cancelled'], true) ? 'disabled' : '' ?>></label><label class="wide">Описание проблемы<textarea name="description" maxlength="500" <?= in_array($lead['status'], ['done','cancelled'], true) ? 'disabled' : '' ?>><?= h($lead['description'] ?? '') ?></textarea></label><?php if(!in_array($lead['status'], ['done','cancelled'], true)): ?><div class="wide actions"><button class="btn save" type="submit">Сохранить изменения</button></div><?php endif; ?></form></details>
<?php if(!in_array($lead['status'], ['done','cancelled'], true)): ?><form class="cancelBox formGrid" method="post" onsubmit="return confirm('Отменить заявку?');"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="cancel"><input type="hidden" name="token" value="<?= h($token) ?>"><label>Причина отмены<select name="cancel_reason"><option>Передумал</option><option>Уже нашёл мастера</option><option>Неудобное время</option><option>Цена не подошла</option><option>Другая причина</option></select></label><label>Свой вариант<input name="cancel_reason_other" placeholder="Если выбрали другую причину"></label><button class="btn delete wide">Отменить заявку</button></form><?php endif; ?></div>
<aside class="statusSide"><div class="sideBlock"><h3>Кратко по заявке</h3><p class="sideHint">Статус обновляется после действий администратора и мастера. Если мастер уже назначен, его имя появится в основной карточке заявки.</p></div><div class="sideBlock"><h3>Кто смотрел заявку</h3><p class="sideHint">Всего просмотров мастерами: <b><?= (int)$viewCount ?></b></p><div class="viewerList"><?php if(!$viewers): ?><div>Пока ни один мастер не открывал эту заявку.</div><?php else: ?><?php foreach($viewers as $v): ?><div><b><?= h($v['name']) ?></b><small><?= h($v['specialization']) ?> · <?= h($v['viewed_at']) ?></small></div><?php endforeach; ?><?php endif; ?></div></div><div class="sideBlock"><h3>История статусов</h3><div class="eventList"><?php if(!$events): ?><div>История появится после первого действия сервиса.</div><?php else: ?><?php foreach(array_slice($events, -5) as $ev): ?><div><?= h($ev['message']) ?><small><?= h($ev['created_at']) ?></small></div><?php endforeach; ?><?php endif; ?></div></div></aside></section>
<?php endif; ?>
</main></body></html>