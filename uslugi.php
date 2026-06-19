<?php
require_once __DIR__ . '/includes/csrf.php';
csrf_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/site_data.php';
require_once __DIR__ . '/includes/layout.php';
$pageTitle = 'Услуги ремонта бытовой техники в Алматы — ' . APP_NAME;
$pageDescription = 'Все услуги ремонта бытовой техники в Алматы: стиральные машины, холодильники, микроволновки, посудомойки, плиты, духовки и мелкая техника.';
$bodyClass = 'page-services';
require_once __DIR__ . '/includes/header.php';
render_site_header('services');
?>
<main>
  <section class="pageLead">
    <div class="container pageLead__grid">
      <div>
        <span class="sectionLabel">Все направления ремонта</span>
        <h1>Услуги ремонта бытовой техники</h1>
        <p>Выберите нужную категорию техники. На странице услуги указаны частые неисправности, ориентировочные цены и удобная форма обращения.</p>
        <div class="pageActions">
          <button class="primaryButton" type="button" data-open-modal data-service="Вызов мастера">Вызвать мастера</button>
          <a class="ghostButton" href="client/index.php">Проверить заявку</a>
        </div>
      </div>
      <div class="pageLead__card"><b>6 направлений</b><span>Стиральные машины, холодильники, микроволновки, посудомойки, плиты, духовки и мелкая техника.</span></div>
    </div>
  </section>

  <section class="screenSection servicesSection">
    <div class="container">
      <div class="sectionHead">
        <span class="sectionLabel">Каталог услуг</span>
        <h2 class="sectionTitle">Что ремонтируем</h2>
        <p class="sectionIntro">Откройте нужную услугу, чтобы посмотреть подробности, частые поломки и ориентир по цене.</p>
      </div>
      <div class="serviceGrid">
        <?php foreach ($services as $service): ?>
          <article class="serviceCard">
            <div class="serviceImageWrap"><img src="<?= htmlspecialchars(asset_image_url($service['image']), ENT_QUOTES) ?>" alt="<?= htmlspecialchars($service['title']) ?>"></div>
            <h3><?= htmlspecialchars($service['title']) ?></h3>
            <ul><?php foreach ($service['items'] as $item): ?><li><?= htmlspecialchars($item) ?></li><?php endforeach; ?></ul>
            <p class="servicePrice"><?= htmlspecialchars($service['price']) ?></p>
            <div class="serviceActions">
              <a class="ghostButton" href="<?= htmlspecialchars($service['url']) ?>">Подробнее</a>
              <button class="softButton" type="button" data-open-modal data-service="<?= htmlspecialchars($service['title'], ENT_QUOTES) ?>">Заявка</button>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="screenSection faultsSection">
    <div class="container faultsPanel">
      <div>
        <span class="sectionLabel">Неисправности</span>
        <h2 class="sectionTitle">Можно обратиться без точного названия поломки</h2>
        <p class="sectionIntro">Клиент просто описывает проблему, мастер уточняет модель и подсказывает ориентир по цене.</p>
      </div>
      <div class="faultTags">
        <?php foreach ($faults as $fault): ?><button type="button" data-open-modal data-service="<?= htmlspecialchars($fault, ENT_QUOTES) ?>"><?= htmlspecialchars($fault) ?></button><?php endforeach; ?>
      </div>
    </div>
  </section>
</main>
<?php render_site_footer($services); render_lead_modal(); require_once __DIR__ . '/includes/footer.php'; ?>
