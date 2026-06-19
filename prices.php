<?php
require_once __DIR__ . '/includes/csrf.php';
csrf_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/site_data.php';
require_once __DIR__ . '/includes/layout.php';
$pageTitle = 'Цены на ремонт бытовой техники в Алматы — ' . APP_NAME;
$pageDescription = 'Прайс-лист на ремонт бытовой техники в Алматы. Диагностика 0 ₸ при ремонте, стоимость согласуется до начала работ.';
$bodyClass = 'page-prices';
require_once __DIR__ . '/includes/header.php';
render_site_header('prices');
?>
<main>
  <section class="pageLead">
    <div class="container pageLead__grid">
      <div>
        <span class="sectionLabel">Прайс-лист</span>
        <h1>Цены на ремонт бытовой техники</h1>
        <p>Указаны ориентировочные цены “от”. Точная сумма зависит от модели, детали и сложности ремонта, поэтому мастер согласует стоимость после диагностики.</p>
        <div class="pageActions"><button class="primaryButton" type="button" data-open-modal data-service="Расчёт стоимости ремонта">Узнать точную стоимость</button><a class="ghostButton" href="client/index.php">Проверить заявку</a></div>
      </div>
      <div class="pageLead__card"><b>0 ₸</b><span>Диагностика при выполнении ремонта. Без скрытых работ: цена согласуется заранее.</span></div>
    </div>
  </section>

  <section class="screenSection pricesSection">
    <div class="container pricesGrid">
      <div class="priceInfo">
        <span class="sectionLabel">Прайс-лист</span>
        <h2 class="sectionTitle">Основные цены</h2>
        <p class="sectionIntro">Цена “от” указана за работу без стоимости детали. Ремонт начинается только после согласования суммы с клиентом.</p>
        <button class="primaryButton" type="button" data-open-modal data-service="Расчёт стоимости ремонта">Оставить заявку</button>
      </div>
      <div>
        <div class="priceTable" role="table" aria-label="Цены на ремонт">
          <?php foreach ($prices as $row): ?>
            <div class="priceRow" role="row"><span role="cell"><?= htmlspecialchars($row[0]) ?></span><b role="cell"><?= htmlspecialchars($row[1]) ?></b></div>
          <?php endforeach; ?>
        </div>
        <div class="priceNote">Стоимость детали, если она нужна, рассчитывается отдельно и обязательно согласуется с клиентом.</div>
      </div>
    </div>
  </section>

  <section class="screenSection faqSection">
    <div class="container faqGrid">
      <div class="faqHead">
        <span class="sectionLabel">Вопросы по цене</span>
        <h2 class="sectionTitle">Частые вопросы</h2>
        <p class="sectionIntro">Эти ответы помогают клиенту понять, почему цена не всегда может быть точной только по телефону.</p>
      </div>
      <div class="faqList">
        <?php foreach ($faq as $item): ?>
          <details class="faqItem"><summary><?= htmlspecialchars($item['q']) ?><i></i></summary><p><?= htmlspecialchars($item['a']) ?></p></details>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</main>
<?php render_site_footer($services); render_lead_modal(); require_once __DIR__ . '/includes/footer.php'; ?>
