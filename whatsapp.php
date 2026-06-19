<?php
require_once __DIR__ . '/includes/csrf.php';
csrf_start();
require_once __DIR__ . '/includes/config.php';
$pageTitle = 'WhatsApp для вызова мастера — ' . APP_NAME;
$pageDescription = 'Быстрая связь с сервисом ремонта бытовой техники в Алматы через WhatsApp.';
require_once __DIR__ . '/includes/header.php';

$phone = preg_replace('/\D+/', '', WHATSAPP_PHONE);
$text = WHATSAPP_TEXT;
$link = 'https://wa.me/' . $phone . '?text=' . rawurlencode($text);
?>
<div class="topbar">
  <div class="container topbar__row">
    <div class="topbar__left"><span class="dot"></span><span>WhatsApp • Быстрая связь</span></div>
    <div class="topbar__right"><span><?= htmlspecialchars(MAIN_PHONE) ?></span></div>
  </div>
</div>

<header class="header" id="top">
  <div class="container header__row">
    <a class="brand" href="index.php"><span class="brand__mark">Т</span><span><span class="brand__name">Техно</span><span class="brand__accent">Мастер</span></span></a>

    <nav class="nav">
      <a href="index.php">Главная</a>
      <a href="uslugi.php">Услуги</a>
      <a href="prices.php">Цены</a>
      <a href="masters.php">Мастера</a>
      <a href="contacts.php">Контакты</a>
    </nav>

    <div class="header__actions">
      <a class="btn btn--primary" href="<?= htmlspecialchars($link) ?>" target="_blank" rel="noopener">Написать в WhatsApp</a>
    </div>
  </div>
</header>

<section class="section">
  <div class="container">
    <div class="section__head">
      <h2>Написать в WhatsApp</h2>
      <p>Нажмите одну кнопку — откроется чат с готовым сообщением.</p>
    </div>

    <div class="card" style="max-width:760px">
      <div class="card__title">Готовый текст</div>
      <div class="muted" style="margin-bottom:12px">Вы можете изменить текст перед отправкой.</div>
      <div class="input" style="display:block; padding:12px 14px; border-radius:14px; border:1px solid var(--line); background:#fff">
        <?= nl2br(htmlspecialchars($text)) ?>
      </div>

      <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:14px">
        <a class="btn btn--primary" href="<?= htmlspecialchars($link) ?>" target="_blank" rel="noopener">Открыть WhatsApp</a>
        <button class="btn btn--ghost" id="copyBtn" type="button">Скопировать текст</button>
      </div>
      <div class="muted" id="copyMsg" style="margin-top:10px"></div>
    </div>
  </div>
</section>

<script>
(() => {
  const btn = document.getElementById('copyBtn');
  const msg = document.getElementById('copyMsg');
  if (!btn) return;
  btn.addEventListener('click', async () => {
    try{
      await navigator.clipboard.writeText(<?= json_encode($text, JSON_UNESCAPED_UNICODE) ?>);
      msg.textContent = 'Скопировано ✅';
    }catch(e){
      msg.textContent = 'Не удалось скопировать. Выделите текст вручную.';
    }
  });
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
