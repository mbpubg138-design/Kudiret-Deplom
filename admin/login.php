<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/csrf.php';
csrf_start();
if (!empty($_SESSION['admin'])) { header('Location: index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_verify($_POST['csrf'] ?? '')) {
    $error = 'Обновите страницу и попробуйте снова.';
  } else {
    $login = trim((string)($_POST['login'] ?? ''));
    $pass  = (string)($_POST['password'] ?? '');
    if ($login === ADMIN_LOGIN && $pass === ADMIN_PASS) {
      $_SESSION['admin'] = ['login' => ADMIN_LOGIN];
      header('Location: index.php'); exit;
    }
    $error = 'Неверный логин или пароль.';
  }
}
?>
<!doctype html><html lang="ru"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Вход в админ-панель</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;800;900&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;font-family:Manrope,sans-serif;color:#111827;background:radial-gradient(760px 360px at 10% 0%,rgba(37,99,235,.18),transparent 60%),#f6f7fb}.card{width:min(440px,calc(100% - 28px));background:#fff;border:1px solid rgba(17,24,39,.10);border-radius:28px;padding:26px;box-shadow:0 24px 70px rgba(17,24,39,.12)}.brand{display:flex;gap:12px;align-items:center;margin-bottom:20px}.mark{width:46px;height:46px;border-radius:16px;background:linear-gradient(135deg,#2563eb,#60a5fa);color:#fff;display:grid;place-items:center;font-weight:900;font-size:23px}.brand b{font-size:22px;letter-spacing:-.04em}.brand span{display:block;color:#667085;font-size:13px;font-weight:800;margin-top:2px}h1{font-size:32px;letter-spacing:-.04em;margin:0 0 8px}.muted{margin:0 0 20px;color:#667085;font-weight:700;line-height:1.55}label{display:grid;gap:8px;margin-top:12px;font-weight:900;color:#344054}input{width:100%;border:1px solid rgba(17,24,39,.12);border-radius:16px;padding:13px 14px;font:inherit;outline:none}input:focus{border-color:rgba(37,99,235,.48);box-shadow:0 0 0 5px rgba(37,99,235,.10)}button{width:100%;border:0;border-radius:16px;background:linear-gradient(135deg,#2563eb,#0ea5e9);color:#fff;padding:14px;font-weight:900;font:inherit;cursor:pointer;margin-top:18px;box-shadow:0 16px 32px rgba(37,99,235,.24)}.err{padding:12px 14px;border-radius:16px;background:rgba(239,68,68,.10);color:#b91c1c;font-weight:900;margin:12px 0}.hint{margin-top:14px;color:#667085;font-size:13px;font-weight:700;line-height:1.5}.hint code{background:#f2f4f7;border-radius:8px;padding:3px 6px;color:#111827}
</style>
<link rel="stylesheet" href="admin.css?v=20260531-premium">
</head><body>
  <main class="card">
    <div class="brand"><div class="mark">А</div><div><b><?= APP_NAME ?></b><span>Панель управления заявками</span></div></div>
    <h1>Вход</h1>
    <p class="muted">После входа можно управлять заявками и отзывами.</p>
    <?php if($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
      <label>Логин<input name="login" placeholder="Логин" value="" required></label>
      <label>Пароль<input name="password" type="password" placeholder="Введите пароль" required></label>
      <button type="submit">Войти</button>
    </form>
    <div class="hint">Перед публикацией сайта обязательно задайте свой сложный логин и пароль в файле <code>includes/config.php</code>.</div>
  </main>
</body></html>
