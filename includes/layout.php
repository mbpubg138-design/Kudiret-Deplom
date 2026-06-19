<?php
function nav_class(string $active, string $key): string {
  return $active === $key ? ' class="is-active"' : '';
}

function render_site_header(string $active = 'home'): void { ?>
<header class="siteHeader" id="top">
  <div class="container headerRow">
    <a class="logo" href="index.php" aria-label="<?= APP_NAME ?>">
      <img class="logo__icon" src="<?= htmlspecialchars(site_logo_url(), ENT_QUOTES) ?>" alt="Логотип <?= htmlspecialchars(APP_NAME, ENT_QUOTES) ?>">
      <span class="logo__text">
        <strong><?= APP_NAME ?></strong>
        <small>Ремонт бытовой техники</small>
      </span>
    </a>

    <nav class="mainNav" id="mainNav" aria-label="Главная навигация">
      <a<?= nav_class($active, 'home') ?> href="index.php">Главная</a>
      <a<?= nav_class($active, 'services') ?> href="uslugi.php">Услуги</a>
      <a<?= nav_class($active, 'prices') ?> href="prices.php">Цены</a>
      <a<?= nav_class($active, 'masters') ?> href="masters.php">Мастера</a>
      <a<?= nav_class($active, 'reviews') ?> href="reviews.php">Отзывы</a>
      <a<?= nav_class($active, 'contacts') ?> href="contacts.php">Контакты</a>
    </nav>

    <div class="headerContact">
      <a class="headerPhone" href="tel:<?= MAIN_PHONE_DIGITS ?>" aria-label="Позвонить">
        <img class="phoneIcon" src="<?= htmlspecialchars(phone_icon_url(), ENT_QUOTES) ?>" alt="">
        <span><b><?= MAIN_PHONE ?></b><small><?= WORK_TIME ?></small></span>
      </a>
      <a class="headerClient" href="client/index.php" aria-label="Проверить заявку">Статус</a>
      <button class="headerButton" type="button" data-open-modal data-service="Вызов мастера">Оставить заявку</button>
      <button class="burger" id="burger" type="button" aria-label="Открыть меню" aria-controls="mainNav" aria-expanded="false"><span></span></button>
    </div>
  </div>
</header>
<?php }

function render_whatsapp_icon(): void { ?>
<span class="waIcon" aria-hidden="true">
  <svg viewBox="0 0 32 32" width="24" height="24" focusable="false">
    <path fill="currentColor" d="M16.03 3.2A12.68 12.68 0 0 0 5.1 22.33L3.6 28.8l6.62-1.47A12.68 12.68 0 1 0 16.03 3.2Zm0 2.25a10.43 10.43 0 0 1 8.84 15.98 10.45 10.45 0 0 1-13.92 3.75l-.39-.2-3.62.8.82-3.52-.24-.4A10.43 10.43 0 0 1 16.03 5.45Zm-4.3 5.24c-.25 0-.65.1-.99.48-.34.38-1.3 1.27-1.3 3.1 0 1.82 1.33 3.59 1.51 3.83.19.25 2.57 4.12 6.37 5.61 3.16 1.24 3.81 1 4.5.94.69-.07 2.22-.91 2.54-1.79.31-.87.31-1.62.22-1.78-.1-.16-.34-.25-.72-.44-.38-.19-2.22-1.1-2.56-1.22-.34-.13-.6-.19-.85.19-.25.37-.97 1.22-1.19 1.47-.22.25-.44.28-.82.1-.38-.2-1.6-.59-3.05-1.88-1.13-1-1.9-2.25-2.12-2.63-.22-.37-.02-.58.17-.77.17-.17.38-.44.56-.65.19-.22.25-.38.38-.63.13-.25.06-.47-.03-.66-.1-.19-.84-2.04-1.15-2.79-.3-.72-.61-.62-.84-.63h-.71Z"/>
  </svg>
</span>
<?php }

function render_site_footer(array $services = []): void { ?>
<footer class="footer" id="footer">
  <div class="container footerGrid">
    <div class="footerBrand">
      <a class="logo logo--footer" href="index.php" aria-label="<?= APP_NAME ?>">
        <img class="logo__icon" src="<?= htmlspecialchars(site_logo_url(), ENT_QUOTES) ?>" alt="Логотип <?= htmlspecialchars(APP_NAME, ENT_QUOTES) ?>">
        <span class="logo__text"><strong><?= APP_NAME ?></strong><small>Ремонт бытовой техники</small></span>
      </a>
      <p>Выездной ремонт бытовой техники в Алматы. Согласуем цену до начала работ, приезжаем на адрес и даём гарантию.</p>
      <a class="footerWa" href="client/index.php"><span>Кабинет заявки</span></a>
    </div>

    <div class="footerCol"><h3>Навигация</h3><a href="index.php">Главная</a><a href="uslugi.php">Услуги</a><a href="prices.php">Цены</a><a href="masters.php">Мастера</a><a href="reviews.php">Отзывы</a><a href="contacts.php">Контакты</a><a href="privacy.php">Политика</a></div>
    <div class="footerCol"><h3>Услуги</h3><?php foreach (array_slice($services,0,6) as $service): ?><a href="<?= htmlspecialchars($service['url']) ?>"><?= htmlspecialchars($service['title']) ?></a><?php endforeach; ?></div>
    <div class="footerCol footerContacts"><h3>Контакты</h3><a href="tel:<?= MAIN_PHONE_DIGITS ?>"><?= MAIN_PHONE ?></a><a href="client/index.php">Кабинет заявки</a><span><?= COMPANY_ADDRESS ?></span><span><?= WORK_TIME ?></span></div>
    <div class="footerCta"><h3>Нужен ремонт?</h3><p>Оставьте заявку — мы перезвоним, уточним неисправность и подскажем ближайшее время выезда.</p><button class="footerButton" type="button" data-open-modal data-service="Заявка из футера">Оставить заявку</button></div>
  </div>
  <div class="container footerBottom"><span>© <?= date('Y') ?> <?= APP_NAME ?>. Все права защищены.</span><span>Алматы · ул. Примерная, д. 1 · +7 (700) 000-00-00</span></div>
</footer>
<a class="floatWhatsapp floatRequest" href="client/index.php" aria-label="Проверить заявку">
  <span class="waIcon">◎</span>
  <span class="floatWhatsapp__text">Моя заявка</span>
</a>
<?php }

function render_lead_modal(): void { ?>
<div class="modal" id="modal" aria-hidden="true">
  <div class="modal__overlay" data-close-modal></div>
  <div class="modal__panel" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="modal__head">
      <div><span class="sectionLabel">Заявка</span><h2 id="modalTitle">Вызвать мастера</h2><p>Заполните форму — код заявки придёт на email, а клиент сможет отслеживать статус в личном кабинете.</p></div>
      <button class="modalClose" type="button" data-close-modal aria-label="Закрыть">×</button>
    </div>
    <form class="modal__form" id="modalForm">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
      <input type="hidden" name="master_id" id="modalMasterId" value="">
      <input type="hidden" name="source" value="modal">
      <label>Имя<input name="name" type="text" placeholder="Ваше имя" autocomplete="name" required></label>
      <label>Телефон<input name="phone" type="tel" placeholder="+7 ___ ___ __ __" autocomplete="tel" required></label>
      <label>Email для кода заявки<input name="email" type="email" placeholder="example@mail.kz" autocomplete="email" required></label>
      <label>Район или адрес<input name="district" type="text" placeholder="Например: Бостандыкский район" maxlength="180"></label>
      <label class="wide">Услуга<input name="service" id="modalService" type="text" placeholder="Какая услуга нужна?" required></label>
      <label class="wide">Описание<span class="fieldHint">до 500 символов</span><textarea name="description" rows="3" maxlength="500" data-count-target="modalDescCount" placeholder="Коротко опишите проблему: что не работает, модель техники, ошибка на дисплее"></textarea><small class="charCount" id="modalDescCount">0/500</small></label>
      <label class="checkLine modalCheck"><input name="privacy" type="checkbox" value="1" required> <span>Я согласен на обработку персональных данных и принимаю <a href="privacy.php">политику конфиденциальности</a>.</span></label>
      <button class="headerButton modalSubmit" type="submit">Отправить</button>
      <p class="msg" id="modalMsg" aria-live="polite"></p>
    </form>
  </div>
</div>
<?php }
