<?php
require_once __DIR__ . '/includes/csrf.php';
csrf_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/site_data.php';
require_once __DIR__ . '/includes/layout.php';
$pageTitle = 'О сервисе — ' . APP_NAME;
$pageDescription = 'О сервисе ремонта бытовой техники в Алматы: выезд мастера, диагностика, гарантия и понятные цены.';
$bodyClass = 'page-about';
require_once __DIR__ . '/includes/header.php';
render_site_header('home');
?>
<main>
  <section class="pageLead">
    <div class="container pageLead__grid">
      <div>
        <span class="sectionLabel">О сервисе</span>
        <h1>Ремонт бытовой техники в Алматы с выездом на дом</h1>
        <p>Помогаем быстро вернуть технику в рабочее состояние: объясняем причину поломки, согласуем стоимость до начала ремонта и даём гарантию на выполненные работы.</p>
        <div class="pageActions"><button class="primaryButton" type="button" data-open-modal data-service="Вызов мастера">Вызвать мастера</button><a class="ghostButton" href="client/index.php">Проверить заявку</a></div>
      </div>
      <div class="pageLead__card"><b>До 12 месяцев</b><span>Гарантия зависит от вида ремонта и установленной детали.</span></div>
    </div>
  </section>

  <section class="section">
    <div class="container aboutPanel">
      <div class="contentPanel">
        <h2>Что ремонтируем</h2>
        <p class="muted">Работаем со стиральными машинами, холодильниками, микроволновками, посудомоечными машинами, плитами, духовками и мелкой бытовой техникой.</p>
        <ul class="checkList">
          <li>Выезд мастера по Алматы и ближайшим районам.</li>
          <li>Диагностика и согласование цены до начала работ.</li>
          <li>Подбор запчастей под модель техники.</li>
          <li>Гарантия на ремонт и установленные детали.</li>
        </ul>
      </div>
      <div class="aboutFacts">
        <div class="factCard"><b>В день обращения</b><span>стараемся назначить ближайшее удобное время выезда</span></div>
        <div class="factCard"><b>0 ₸</b><span>диагностика при выполнении ремонта</span></div>
        <div class="factCard"><b>Алматы</b><span>обслуживаем основные районы города</span></div>
        <div class="factCard"><b>Цена заранее</b><span>ремонт начинается только после согласования суммы</span></div>
      </div>
    </div>
  </section>
</main>
<?php render_site_footer($services); render_lead_modal(); require_once __DIR__ . '/includes/footer.php'; ?>
