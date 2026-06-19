<?php
require_once __DIR__ . '/includes/csrf.php';
csrf_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/site_data.php';
require_once __DIR__ . '/includes/app_models.php';
require_once __DIR__ . '/includes/layout.php';
$pageTitle = 'Мастера по ремонту бытовой техники в Алматы — ' . APP_NAME;
$pageDescription = 'Специалисты по ремонту стиральных машин, холодильников, микроволновок, плит, духовок и мелкой техники в Алматы.';
$masters = app_get_public_masters();
$bodyClass = 'page-masters';
require_once __DIR__ . '/includes/header.php';
render_site_header('masters');
?>
<main>
  <section class="pageLead">
    <div class="container pageLead__grid">
      <div>
        <span class="sectionLabel">Команда сервиса</span>
        <h1>Мастера по ремонту техники</h1>
        <p>Каждый специалист отвечает за своё направление: стиральные машины, холодильники, микроволновки, плиты, духовки и мелкая техника.</p>
        <div class="pageActions"><button class="primaryButton" type="button" data-open-modal data-service="Подбор мастера">Подобрать мастера</button><a class="ghostButton" href="client/index.php">Проверить свою заявку</a></div>
      </div>
      <div class="pageLead__card"><b>5 специалистов</b><span>Карточки берут данные из профилей мастеров. Кнопка под карточкой отправляет заявку конкретному специалисту, а не просто в WhatsApp.</span></div>
    </div>
  </section>

  <section class="screenSection mastersSection">
    <div class="container">
      <div class="sectionTopLine">
        <div>
          <span class="sectionLabel">Команда сервиса</span>
          <h2 class="sectionTitle">Наши мастера</h2>
        </div>
        <div class="sliderControls" aria-label="Управление списком мастеров">
          <button type="button" class="sliderBtn" data-master-prev aria-label="Назад"></button>
          <button type="button" class="sliderBtn sliderBtn--next" data-master-next aria-label="Вперёд"></button>
        </div>
      </div>
      <div class="mastersTrack" id="mastersTrack">
        <?php foreach ($masters as $master): ?>
          <article class="masterCard">
            <div class="masterPhoto">
              <img src="<?= htmlspecialchars(asset_image_url($master['photo']), ENT_QUOTES) ?>" alt="<?= htmlspecialchars($master['name']) ?>">
            </div>
            <div class="masterInfo">
              <h3><?= htmlspecialchars($master['name']) ?></h3>
              <div class="masterRole"><?= htmlspecialchars($master['specialization'] ?? 'Мастер сервиса') ?></div>
              <p><?= htmlspecialchars($master['exp']) ?></p>
              <span><?= htmlspecialchars($master['work']) ?></span>
              <div class="masterMeta">
                <small><b>Район:</b>&nbsp;<?= htmlspecialchars($master['area']) ?></small>
                <small><b>Бренды:</b>&nbsp;<?= htmlspecialchars($master['brands']) ?></small>
              </div>
              <button class="masterRequestBtn" type="button" data-open-modal data-master-id="<?= (int)($master['id'] ?? 0) ?>" data-service="Заявка мастеру: <?= htmlspecialchars($master['specialization'] ?? $master['name'], ENT_QUOTES) ?>">Оставить заявку этому мастеру</button>
            </div>
          </article>
        <?php endforeach; ?>
        <article class="masterCard masterJoinCard">
          <a href="masters_apply.php" class="joinPlus" aria-label="Оставить анкету мастера">+</a>
          <div class="masterInfo">
            <h3>Вы мастер и ищете работу?</h3>
            <p>Оставьте отдельную анкету: опыт, район, бренды, фото и контакты.</p>
            <span>Анкета попадёт только администратору, клиентам она не мешает.</span>
            <a class="masterRequestBtn" href="masters_apply.php">Заполнить анкету мастера</a>
          </div>
        </article>
      </div>
      <div class="scrollHint"><span></span>Прокрутите влево или вправо, чтобы увидеть больше мастеров</div>
    </div>
  </section>

  <section class="screenSection processSection">
    <div class="container">
      <span class="sectionLabel">Порядок работы</span>
      <h2 class="sectionTitle">Как мастер выполняет заявку</h2>
      <div class="stepsGrid">
        <?php foreach ($steps as $step): ?>
          <article class="stepCard"><b><?= htmlspecialchars($step['num']) ?></b><h3><?= htmlspecialchars($step['title']) ?></h3><p><?= htmlspecialchars($step['text']) ?></p></article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</main>
<?php render_site_footer($services); render_lead_modal(); require_once __DIR__ . '/includes/footer.php'; ?>
