<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/csrf.php';
csrf_start();
require_once __DIR__ . '/../includes/reviews.php';
require_once __DIR__ . '/../includes/config.php';
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
if (empty($_SESSION['admin'])) { header('Location: login.php'); exit; }

$errors = [];
$flash = '';
$allowed = ['new','in_work','done'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_verify($_POST['csrf'] ?? '')) {
    $errors[] = 'Ошибка безопасности. Обновите страницу и повторите действие.';
  } else {
    $action = (string)($_POST['action'] ?? '');
    $id = (string)($_POST['id'] ?? '');

    if ($action === 'delete') {
      if ($id && reviews_delete($id)) $flash = 'Отзыв удалён.';
      else $errors[] = 'Не удалось удалить отзыв.';
    }

    if ($action === 'create' || $action === 'save') {
      $name = trim((string)($_POST['name'] ?? ''));
      $rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
      $text = trim((string)($_POST['text'] ?? ''));
      $reply = trim((string)($_POST['reply'] ?? ''));
      $status = (string)($_POST['status'] ?? 'new');
      if (!in_array($status, $allowed, true)) $status = 'new';

      if ($name === '' || $text === '') {
        $errors[] = 'Заполните автора и текст отзыва.';
      } else {
        $data = ['name'=>$name,'rating'=>$rating,'text'=>$text,'reply'=>$reply,'status'=>$status];
        if ($action === 'create') {
          reviews_add($data);
          $flash = 'Отзыв добавлен.';
        } else {
          if ($id && reviews_update($id, $data)) $flash = 'Отзыв сохранён.';
          else $errors[] = 'Не удалось сохранить отзыв.';
        }
      }
    }
  }
}

$filter = (string)($_GET['status'] ?? 'all');
$items = reviews_all($filter === 'all' ? null : $filter);
$counts = ['all'=>0,'new'=>0,'in_work'=>0,'done'=>0];
foreach (reviews_load() as $it) { $st = (string)($it['status'] ?? 'new'); if(isset($counts[$st])) $counts[$st]++; $counts['all']++; }
function review_status_label(string $status): string {
  switch ($status) {
    case 'new': return 'Новый';
    case 'in_work': return 'В работе';
    case 'done': return 'Опубликован';
    default: return $status;
  }
}
?>
<!doctype html><html lang="ru"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Админка — отзывы</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{--accent:#8b5cf6;--accent-2:#6d28d9;--line:rgba(17,24,39,.10);--bg:#f7f5ff;--text:#111827;--muted:#667085;--shadow:0 16px 42px rgba(17,24,39,.08)}*{box-sizing:border-box}body{margin:0;font-family:Manrope,sans-serif;background:radial-gradient(circle at top left,rgba(139,92,246,.12),transparent 30%),var(--bg);color:var(--text)}a{text-decoration:none;color:inherit}.top{position:sticky;top:0;z-index:5;background:rgba(255,255,255,.92);backdrop-filter:blur(14px);border-bottom:1px solid var(--line)}.top__row{width:min(1320px,calc(100% - 32px));margin:0 auto;display:flex;justify-content:space-between;align-items:center;gap:16px;padding:14px 0}.nav{display:flex;gap:8px;flex-wrap:wrap}.tab{padding:10px 14px;border-radius:14px;background:#fff;border:1px solid var(--line);font-weight:900}.tab.is-active{background:var(--accent);border-color:var(--accent);color:#fff}.right{display:flex;align-items:center;gap:12px;font-weight:900}.container{width:min(1320px,calc(100% - 32px));margin:0 auto;padding:24px 0}h1{font-size:36px;letter-spacing:-.04em;margin:0 0 6px}.muted{color:var(--muted);font-weight:700}.hero{display:flex;justify-content:space-between;gap:20px;align-items:end;margin-bottom:18px}.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin:18px 0}.stat{background:#fff;border:1px solid var(--line);border-radius:20px;padding:16px;box-shadow:var(--shadow)}.stat b{display:block;font-size:26px}.stat span{color:var(--muted);font-size:13px;font-weight:800}.notice{padding:14px 16px;border-radius:16px;margin:14px 0;font-weight:900}.ok{background:rgba(16,185,129,.12);color:#047857}.bad{background:rgba(239,68,68,.12);color:#b91c1c}.panel,.reviewCard{background:linear-gradient(180deg,#fff,#fcfbff);border:1px solid var(--line);border-radius:24px;padding:18px;box-shadow:var(--shadow)}.panel{margin-bottom:16px}.formGrid{display:grid;grid-template-columns:1fr 120px 180px;gap:12px}.formGrid .wide{grid-column:1/-1}label{display:grid;gap:7px;font-size:12px;text-transform:uppercase;letter-spacing:.04em;font-weight:900;color:#475467}input,select,textarea{width:100%;font:inherit;border:1px solid var(--line);border-radius:12px;padding:10px;background:#fff;outline:none}textarea{min-height:86px;resize:vertical}.btn{border:0;border-radius:12px;padding:11px 14px;font-weight:900;cursor:pointer}.save{background:#111827;color:#fff}.delete{background:rgba(239,68,68,.12);color:#b91c1c}.reviewsGrid{display:grid;grid-template-columns:repeat(2,1fr);gap:16px}.reviewCard__head{display:flex;justify-content:space-between;gap:14px;align-items:flex-start;margin-bottom:14px}.reviewCard__head b{font-size:20px}.badge{display:inline-flex;border-radius:999px;padding:7px 10px;font-size:12px;font-weight:900}.new{background:rgba(37,99,235,.12);color:#92430b}.in_work{background:rgba(37,99,235,.12);color:#1d4ed8}.done{background:rgba(16,185,129,.12);color:#047857}.reviewForm{display:grid;grid-template-columns:1fr 120px 180px;gap:12px}.reviewForm .wide{grid-column:1/-1}.actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px}.empty{text-align:center;color:var(--muted);font-weight:900;padding:28px}.adminBrand{font-size:18px;font-weight:1000;letter-spacing:-.03em}@media(max-width:900px){.top__row,.hero{display:block}.right{margin-top:12px}.stats,.reviewsGrid{grid-template-columns:1fr}.formGrid,.reviewForm{grid-template-columns:1fr}.formGrid .wide,.reviewForm .wide{grid-column:auto}.hero .tab{display:inline-flex;margin-top:12px}}
</style>
<link rel="stylesheet" href="admin.css?v=20260531-premium">
</head><body>
<div class="top"><div class="top__row">
  <div class="adminBrand">Контроль отзывов</div>
  <div class="nav"><a class="tab" href="index.php">Заявки</a><a class="tab" href="masters.php">Мастера</a><a class="tab" href="master_applications.php">Заявки мастеров</a><a class="tab is-active" href="reviews.php">Отзывы</a><a class="tab" href="../index.php" target="_blank" rel="noopener">Открыть сайт</a></div>
  <div class="right"><span class="tab" style="padding:10px 14px"><?= htmlspecialchars($_SESSION['admin']['login']) ?></span><a class="tab" href="logout.php">Выйти</a></div>
</div></div>
<main class="container">
  <div class="hero"><div><h1>Отзывы клиентов</h1><div class="muted">Отдельная страница для модерации отзывов, ответа клиенту и публикации на сайте.</div></div><a class="tab" href="../reviews.php" target="_blank">Страница отзывов</a></div>
  <div class="stats">
    <?php foreach(['all'=>'Все','new'=>'Новые','in_work'=>'В работе','done'=>'Опубликованы'] as $key=>$label): ?><a class="stat" href="reviews.php?status=<?= urlencode($key) ?>"><b><?= (int)($counts[$key] ?? 0) ?></b><span><?= $label ?></span></a><?php endforeach; ?>
  </div>
  <?php if($flash): ?><div class="notice ok"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
  <?php if($errors): ?><div class="notice bad"><?php foreach($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?></div><?php endif; ?>

  <section class="panel">
    <form method="post" class="formGrid" action="reviews.php?status=<?= urlencode($filter) ?>">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>"><input type="hidden" name="action" value="create">
      <label>Автор<input name="name" placeholder="Имя клиента"></label>
      <label>Оценка<select name="rating"><?php for($i=5;$i>=1;$i--): ?><option value="<?= $i ?>"><?= $i ?></option><?php endfor; ?></select></label>
      <label>Статус<select name="status"><option value="done">Опубликован</option><option value="new">Новый</option><option value="in_work">В работе</option></select></label>
      <label class="wide">Текст<textarea name="text" placeholder="Текст отзыва"></textarea></label>
      <label class="wide">Ответ администратора<textarea name="reply" placeholder="Ответ сервиса, если нужен"></textarea></label>
      <button class="btn save" type="submit">Добавить отзыв</button>
    </form>
  </section>

  <section class="reviewsGrid">
    <?php if(!$items): ?><div class="panel empty">Отзывы в выбранном статусе не найдены.</div><?php endif; ?>
    <?php foreach($items as $r): ?>
      <article class="reviewCard">
        <div class="reviewCard__head"><div><b><?= htmlspecialchars((string)($r['name'] ?? 'Клиент')) ?></b><div class="muted" style="font-size:12px;margin-top:4px"><?= htmlspecialchars((string)($r['created_at'] ?? '')) ?></div></div><span class="badge <?= htmlspecialchars((string)($r['status'] ?? 'new')) ?>"><?= review_status_label((string)($r['status'] ?? 'new')) ?></span></div>
        <form method="post" class="reviewForm" action="reviews.php?status=<?= urlencode($filter) ?>">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= htmlspecialchars((string)($r['id'] ?? '')) ?>">
          <label>Автор<input name="name" value="<?= htmlspecialchars((string)($r['name'] ?? ''), ENT_QUOTES) ?>"></label>
          <label>Оценка<select name="rating"><?php for($i=1;$i<=5;$i++): ?><option value="<?= $i ?>" <?= ((int)($r['rating'] ?? 5)===$i)?'selected':'' ?>><?= $i ?></option><?php endfor; ?></select></label>
          <label>Статус<select name="status"><?php foreach($allowed as $st): ?><option value="<?= $st ?>" <?= (($r['status'] ?? '')===$st)?'selected':'' ?>><?= review_status_label($st) ?></option><?php endforeach; ?></select></label>
          <label class="wide">Отзыв<textarea name="text"><?= htmlspecialchars((string)($r['text'] ?? '')) ?></textarea></label>
          <label class="wide">Ответ<textarea name="reply"><?= htmlspecialchars((string)($r['reply'] ?? '')) ?></textarea></label>
          <div class="actions"><button class="btn save" type="submit">Сохранить</button></div>
        </form>
        <form method="post" action="reviews.php?status=<?= urlencode($filter) ?>" onsubmit="return confirm('Удалить отзыв?');"><input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= htmlspecialchars((string)($r['id'] ?? '')) ?>"><button class="btn delete" type="submit">Удалить отзыв</button></form>
      </article>
    <?php endforeach; ?>
  </section>
</main>
</body></html>
