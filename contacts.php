<?php
require_once __DIR__ . '/includes/csrf.php';
csrf_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/site_data.php';
require_once __DIR__ . '/includes/layout.php';
$pageTitle = 'Контакты сервиса ремонта бытовой техники в Алматы — ' . APP_NAME;
$pageDescription = 'Контакты выездного сервиса ремонта бытовой техники в Алматы. Телефон, WhatsApp, заявка на выезд мастера и районы обслуживания.';
$bodyClass = 'page-contacts';
require_once __DIR__ . '/includes/header.php';
render_site_header('contacts');
?>
<main>
  <section class="pageLead">
    <div class="container pageLead__grid">
      <div>
        <span class="sectionLabel">Связаться с нами</span>
        <h1>Контакты и заявка на выезд мастера</h1>
        <p>Работаем как выездной сервис: мастер приезжает на адрес клиента, проводит диагностику и согласует стоимость ремонта до начала работ.</p>
        <div class="pageActions"><a class="primaryButton" href="tel:<?= MAIN_PHONE_DIGITS ?>">Позвонить</a><a class="whatsappLink" href="<?= htmlspecialchars(whatsapp_url(), ENT_QUOTES) ?>" target="_blank" rel="noopener">Написать в WhatsApp</a></div>
      </div>
      <div class="pageLead__card"><b><?= MAIN_PHONE ?></b><span><?= WORK_TIME ?>. Заявки принимаются через форму, звонок и WhatsApp.</span></div>
    </div>
  </section>

  <section class="screenSection contactsSection">
    <div class="container contactsGrid">
      <div class="contactInfo">
        <span class="sectionLabel">Контакты</span>
        <h2 class="sectionTitle">Вызвать мастера в Алматы</h2>
        <p class="sectionIntro">Заполните форму или напишите в WhatsApp. Мы уточним поломку, район и удобное время выезда.</p>
        <div class="contactCards">
          <a href="tel:<?= MAIN_PHONE_DIGITS ?>"><b>Телефон</b><span><?= MAIN_PHONE ?></span></a>
          <a href="<?= htmlspecialchars(whatsapp_url(), ENT_QUOTES) ?>" target="_blank" rel="noopener"><b>WhatsApp</b><span>Написать сообщение</span></a>
          <div><b>Формат работы</b><span><?= COMPANY_ADDRESS ?></span></div>
          <div><b>График</b><span><?= WORK_TIME ?></span></div>
        </div>
        <div class="districts"><b>Районы обслуживания:</b><?php foreach ($districts as $district): ?><span><?= htmlspecialchars($district) ?></span><?php endforeach; ?></div>
        <div class="serviceAreaBox"><div><b>Выездной сервис по Алматы</b><span>Мастер приезжает на адрес клиента. Точный район, время выезда и ориентир по стоимости согласуем заранее.</span></div><a href="tel:<?= MAIN_PHONE_DIGITS ?>">Позвонить</a></div>
      </div>
      <form class="leadForm" id="leadForm">
        <h3>Оставить заявку</h3>
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
        <input type="hidden" name="source" value="contacts_page">
        <label>Имя<input name="name" type="text" placeholder="Ваше имя" autocomplete="name" required></label>
        <label>Телефон<input name="phone" type="tel" placeholder="+7 ___ ___ __ __" autocomplete="tel" required></label>
        <label>Email для кода заявки<input name="email" type="email" placeholder="example@mail.kz" autocomplete="email" required></label>
        <label>Район или адрес<input name="district" type="text" placeholder="Например: Бостандыкский район" maxlength="180"></label>
        <label class="wide">Услуга<input name="service" type="text" placeholder="Например: ремонт стиральной машины" required></label>
        <label class="wide">Описание<span class="fieldHint">до 500 символов</span><textarea name="description" rows="4" maxlength="500" data-count-target="contactDescCount" placeholder="Коротко опишите проблему"></textarea><small class="charCount" id="contactDescCount">0/500</small></label>
        <label class="checkLine"><input name="privacy" type="checkbox" value="1" required> <span>Я согласен на обработку персональных данных и принимаю <a href="privacy.php">политику конфиденциальности</a>.</span></label>
        <button class="primaryButton" type="submit">Отправить заявку</button>
        <p class="msg" id="formMsg" aria-live="polite"></p>
      </form>
    </div>
  </section>
</main>
<?php render_site_footer($services); render_lead_modal(); require_once __DIR__ . '/includes/footer.php'; ?>
