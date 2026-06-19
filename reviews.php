<?php
require_once __DIR__ . '/includes/csrf.php';
csrf_start();
require_once __DIR__ . '/includes/reviews.php';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/site_data.php';
require_once __DIR__ . '/includes/layout.php';
$pageTitle = 'Отзывы клиентов — ' . APP_NAME;
$pageDescription = 'Отзывы клиентов о сервисе ремонта бытовой техники в Алматы.';
$bodyClass = 'page-reviews';
require_once __DIR__ . '/includes/header.php';

$errors = [];
$ok = false;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  if (!csrf_verify($_POST['csrf'] ?? '')) $errors[] = 'Обновите страницу и попробуйте снова.';
  $name = trim((string)($_POST['name'] ?? ''));
  $rating = (int)($_POST['rating'] ?? 5);
  $text = trim((string)($_POST['text'] ?? ''));
  if ($name === '' || strlen($name) < 2) $errors[] = 'Введите имя.';
  if ($rating < 1 || $rating > 5) $errors[] = 'Выберите оценку от 1 до 5.';
  if ($text === '' || strlen($text) < 8) $errors[] = 'Напишите отзыв подробнее.';
  if (!$errors) {
    $ok = reviews_add($name, $rating, $text);
    if (!$ok) $errors[] = 'Не удалось сохранить отзыв. Попробуйте позже или свяжитесь с нами по телефону.';
  }
}
$items = reviews_public();
render_site_header('reviews');
?>
<main>
  <section class="pageLead">
    <div class="container pageLead__grid">
      <div>
        <span class="sectionLabel">Отзывы</span>
        <h1>Отзывы клиентов</h1>
        <p>Мы публикуем отзывы клиентов после проверки и отвечаем на обращения. Ваш отзыв поможет другим людям выбрать мастера.</p>
        <div class="pageActions"><button class="primaryButton" type="button" data-open-modal data-service="Вызов мастера после просмотра отзывов">Вызвать мастера</button><a class="ghostButton" href="client/index.php">Моя заявка</a></div>
      </div>
      <div class="pageLead__card"><b><?= count($items) ?></b><span>Опубликованных отзывов на сайте после модерации.</span></div>
    </div>
  </section>

  <section class="section">
    <div class="container reviewsPageGrid">
      <div class="contentPanel">
        <h2>Оставить отзыв</h2>
        <p>После проверки отзыв появится на сайте.</p>
        <?php if ($ok): ?><div class="notice notice--ok">Спасибо! Отзыв отправлен на проверку.</div><?php endif; ?>
        <?php if ($errors): ?><div class="notice notice--bad"><b>Исправьте:</b><ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <form method="post" class="reviewForm">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
          <label>Имя<input name="name" placeholder="Например: Алия" required></label>
          <label>Оценка<select name="rating" required><option value="5">★★★★★ 5</option><option value="4">★★★★☆ 4</option><option value="3">★★★☆☆ 3</option><option value="2">★★☆☆☆ 2</option><option value="1">★☆☆☆☆ 1</option></select></label>
          <label>Текст<textarea name="text" rows="5" placeholder="Опишите качество работы" required></textarea></label>
          <button class="primaryButton" type="submit">Отправить отзыв</button>
        </form>
      </div>

      <div class="reviewsListStack">
        <?php if (!$items): ?><div class="contentPanel"><h2>Пока нет отзывов</h2><p>Первый опубликованный отзыв появится здесь.</p></div><?php endif; ?>
        <?php foreach ($items as $r): ?>
          <?php $rating = max(1,min(5,(int)($r['rating'] ?? 5))); ?>
          <article class="reviewCard">
            <div class="reviewTop"><b><?= htmlspecialchars($r['name'] ?? 'Клиент') ?></b><span><?= str_repeat('★',$rating) ?><?= str_repeat('☆',5-$rating) ?></span></div>
            <p><?= nl2br(htmlspecialchars($r['text'] ?? '')) ?></p>
            <?php if (!empty($r['reply'])): ?><div class="reply"><b>Ответ сервиса:</b><span><?= nl2br(htmlspecialchars($r['reply'])) ?></span></div><?php endif; ?>
            <small><?= htmlspecialchars($r['created_at'] ?? '') ?> • Алматы</small>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</main>
<?php render_site_footer($services); render_lead_modal(); require_once __DIR__ . '/includes/footer.php'; ?>
