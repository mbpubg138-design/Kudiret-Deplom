<?php
require_once __DIR__ . '/includes/csrf.php';
csrf_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/app_models.php';
$pageTitle = 'Ремонт бытовой техники в Алматы с выездом мастера на дом — ' . APP_NAME;
$pageDescription = 'Выездной ремонт бытовой техники в Алматы: стиральные машины, холодильники, микроволновки, посудомойки, плиты и духовки. Диагностика 0 ₸ при ремонте, гарантия до 12 месяцев.';
$bodyClass = 'page-home';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/reviews.php';

$services = [
  [
    'title' => 'Ремонт стиральных машин',
    'image' => 'assets/img/content/service-washer.jpg',
    'items' => ['не сливает воду', 'не греет', 'шумит', 'не отжимает', 'течёт'],
    'price' => 'от 8 000 ₸',
    'url' => 'remont-stiralnyh-mashin.php',
  ],
  [
    'title' => 'Ремонт холодильников',
    'image' => 'assets/img/content/service-fridge.jpg',
    'items' => ['не морозит', 'шумит', 'течёт', 'не отключается', 'намерзает лёд'],
    'price' => 'от 10 000 ₸',
    'url' => 'remont-holodilnikov.php',
  ],
  [
    'title' => 'Ремонт микроволновок',
    'image' => 'assets/img/content/service-microwave.jpg',
    'items' => ['не греет', 'искрит', 'не включается', 'не крутит тарелку'],
    'price' => 'от 5 000 ₸',
    'url' => 'remont-mikrovolnovok.php',
  ],
  [
    'title' => 'Ремонт посудомоек',
    'image' => 'assets/img/content/service-dishwasher.jpg',
    'items' => ['не сливает воду', 'плохо моет', 'показывает ошибку', 'протекает'],
    'price' => 'от 9 000 ₸',
    'url' => 'remont-posudomoechnyh-mashin.php',
  ],
  [
    'title' => 'Ремонт плит и духовок',
    'image' => 'assets/img/content/service-stove.jpg',
    'items' => ['не греет', 'не включается', 'слабый нагрев', 'выбивает автомат'],
    'price' => 'от 7 000 ₸',
    'url' => 'remont-plit-duhovok.php',
  ],
  [
    'title' => 'Ремонт мелкой техники',
    'image' => 'assets/img/content/service-small.jpg',
    'items' => ['кофемашины', 'пылесосы', 'мясорубки', 'по согласованию'],
    'price' => 'от 4 000 ₸',
    'url' => 'remont-melkoy-tehniki.php',
  ],
];

$faults = [
  'не сливает воду', 'не греет', 'шумит при работе', 'течёт вода', 'не включается', 'выбивает автомат',
  'плохо отжимает', 'запах гари', 'ошибка на дисплее', 'прыгает при отжиме', 'дверца заблокировалась', 'сильно вибрирует'
];

$prices = [
  ['Диагностика при ремонте', '0 ₸'],
  ['Выезд мастера по Алматы', 'от 2 000 ₸'],
  ['Диагностика без ремонта', 'от 3 000 ₸'],
  ['Чистка фильтра / сливной системы', 'от 2 000 ₸'],
  ['Замена ремня стиральной машины', 'от 3 000 ₸'],
  ['Замена насоса / помпы', 'от 4 500 ₸'],
  ['Замена амортизаторов', 'от 5 000 ₸'],
  ['Замена клапана подачи воды', 'от 5 500 ₸'],
  ['Замена щёток двигателя', 'от 6 000 ₸'],
  ['Замена подшипников', 'от 12 000 ₸'],
  ['Ремонт электронного модуля', 'от 8 000 ₸'],
  ['Ремонт холодильника', 'от 10 000 ₸'],
];

$masters = [
  [
    'name' => 'Мастер по стиральным машинам',
    'photo' => 'assets/img/masters/master-1.jpg',
    'exp' => 'Опыт от 6 лет',
    'work' => 'Насосы, ремни, подшипники, люки, клапаны',
    'area' => 'Алмалинский, Бостандыкский',
    'brands' => 'LG, Samsung, Bosch, Indesit',
  ],
  [
    'name' => 'Мастер по холодильникам',
    'photo' => 'assets/img/masters/master-2.jpg',
    'exp' => 'Опыт от 8 лет',
    'work' => 'Компрессоры, фреон, датчики, терморегуляторы',
    'area' => 'Ауэзовский, Наурызбайский',
    'brands' => 'Atlant, Beko, Haier, Electrolux',
  ],
  [
    'name' => 'Мастер по микроволновкам',
    'photo' => 'assets/img/masters/master-3.jpg',
    'exp' => 'Опыт от 5 лет',
    'work' => 'Платы, магнетроны, питание, кнопки, дверцы',
    'area' => 'Медеуский, Жетысуский',
    'brands' => 'Panasonic, Samsung, LG, Midea',
  ],
  [
    'name' => 'Мастер по плитам и духовкам',
    'photo' => 'assets/img/masters/master-4.jpg',
    'exp' => 'Опыт от 7 лет',
    'work' => 'Нагрев, конфорки, термостаты, электрика',
    'area' => 'Турксибский, Алатауский',
    'brands' => 'Bosch, Hansa, Gorenje, Siemens',
  ],
  [
    'name' => 'Мастер универсального ремонта',
    'photo' => 'assets/img/masters/master-5.jpg',
    'exp' => 'Опыт от 5 лет',
    'work' => 'Мелкая техника и комплексная диагностика',
    'area' => 'Все районы Алматы',
    'brands' => 'Candy, Zanussi, Daewoo, Whirlpool',
  ],
];

$steps = [
  ['num' => '1', 'title' => 'Вы оставляете заявку', 'text' => 'Через форму на сайте или звонок. После отправки клиент получает код заявки.'],
  ['num' => '2', 'title' => 'Мы уточняем проблему', 'text' => 'Задаём несколько вопросов и согласуем удобное время.'],
  ['num' => '3', 'title' => 'Мастер приезжает на адрес', 'text' => 'Проводит диагностику и называет точную стоимость.'],
  ['num' => '4', 'title' => 'Ремонт и гарантия', 'text' => 'Проверяем технику вместе с клиентом и даём гарантию.'],
];

$faq = [
  ['q' => 'Диагностика платная?', 'a' => 'При выполнении ремонта диагностика стоит 0 ₸. Если ремонт не выполняется, стоимость диагностики и выезда согласуется заранее.'],
  ['q' => 'Можно узнать точную цену по телефону?', 'a' => 'По телефону можно назвать ориентир. Точная стоимость зависит от модели, детали и сложности ремонта, поэтому мастер согласует цену после диагностики.'],
  ['q' => 'Есть ли гарантия?', 'a' => 'Да, гарантия действует на выполненные работы и установленные детали. Срок зависит от вида ремонта и может составлять до 12 месяцев.'],
  ['q' => 'Сколько времени занимает ремонт?', 'a' => 'Частые поломки обычно устраняются в день обращения. Сложный ремонт зависит от наличия детали.'],
  ['q' => 'Мастер приезжает с запчастями?', 'a' => 'По распространённым неисправностям мастер берёт основные детали с собой. Редкие детали заказываются после диагностики.'],
  ['q' => 'Работаете ли вечером?', 'a' => 'Да, заявки принимаются ежедневно с 09:00 до 20:00. Вечерний выезд согласуется с оператором.'],
  ['q' => 'Какие районы Алматы обслуживаете?', 'a' => 'Работаем по Алматы и ближайшим районам: Бостандыкский, Алмалинский, Ауэзовский, Медеуский, Турксибский, Жетысуский, Наурызбайский и Алатауский.'],
];

$brands = ['Samsung','LG','Bosch','Indesit','Ariston','Beko','Haier','Electrolux','Whirlpool','Atlant','Candy','Zanussi','Siemens','Gorenje','Midea','Hansa','Panasonic','Daewoo'];
$districts = ['Бостандыкский','Алмалинский','Ауэзовский','Медеуский','Турксибский','Жетысуский','Наурызбайский','Алатауский'];
$masters = app_get_public_masters();
$allReviews = reviews_public();
$reviews = array_slice($allReviews, 0, 3);
$reviewCount = count($allReviews);
$avgRating = null;
if ($reviewCount > 0) {
  $sum = 0;
  foreach ($allReviews as $r) $sum += max(1, min(5, (int)($r['rating'] ?? 5)));
  $avgRating = round($sum / $reviewCount, 1);
}
?>

<?php render_site_header('home'); ?>

<main>
  <section class="heroSection">
    <div class="container heroGrid">
      <div class="heroText">
        <span class="sectionLabel">Выезд мастера по Алматы</span>
        <h1>Ремонт бытовой техники в Алматы с выездом мастера на дом</h1>
        <p>Стиральные машины, холодильники, микроволновки, посудомойки, плиты и духовки. Диагностика 0 ₸ при ремонте, стоимость согласуем до начала работы, гарантия — до 12 месяцев.</p>
        <div class="heroActions">
          <button class="primaryButton" type="button" data-open-modal data-service="Вызов мастера">Вызвать мастера</button>
          <a class="whatsappLink" href="client/index.php">Проверить заявку по коду</a>
          <a class="ghostButton" href="prices.php">Посмотреть цены</a>
        </div>
        <div class="heroAssurance" aria-label="Условия сервиса">
          <span>Без навязанных работ</span>
          <span>Цена до начала ремонта</span>
          <span>Фото/видео отчёт по запросу</span>
        </div>
        <div class="heroStats">
          <div><b>Выезд 0 ₸</b><span>при выполнении ремонта</span></div>
          <div><b>От 30 минут</b><span>первичный контакт по заявке</span></div>
          <div><b>До 12 месяцев</b><span>гарантия на работу</span></div>
        </div>
      </div>
      <div class="heroCard">
        <img src="<?= htmlspecialchars(asset_image_url('assets/img/content/hero-main.jpg'), ENT_QUOTES) ?>" alt="Консультация по ремонту техники">
        <div class="heroMiniCard">
          <b>Бесплатная консультация</b>
          <span>Опишите поломку — подскажем ориентир по цене и времени выезда.</span>
        </div>
      </div>
    </div>
  </section>

  <section class="trustStrip" aria-label="Преимущества сервиса">
    <div class="container trustGrid">
      <div><b>Выездной сервис</b><span>мастер приезжает на адрес</span></div>
      <div><b>Понятные цены</b><span>сумма до начала ремонта</span></div>
      <div><b>Гарантия</b><span>на работы и установленные детали</span></div>
      <div><b>Личный код</b><span>статус заявки можно проверить</span></div>
    </div>
  </section>

  <section class="screenSection servicesSection" id="services">
    <div class="container">
      <div class="sectionHead">
        <span class="sectionLabel">Основные направления</span>
        <h2 class="sectionTitle">Услуги</h2>
        <p class="sectionIntro">Выберите категорию техники — можно открыть подробную страницу услуги или сразу оставить заявку.</p>
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

  <section class="screenSection faultsSection" id="faults">
    <div class="container faultsPanel">
      <div>
        <span class="sectionLabel">Популярные поломки</span>
        <h2 class="sectionTitle">Неисправности, с которыми чаще всего обращаются</h2>
        <p class="sectionIntro">Клиенту не нужно знать название детали. Достаточно описать проблему простыми словами.</p>
      </div>
      <div class="faultTags">
        <?php foreach ($faults as $fault): ?><button type="button" data-open-modal data-service="<?= htmlspecialchars($fault, ENT_QUOTES) ?>"><?= htmlspecialchars($fault) ?></button><?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="screenSection diagnosticSection" id="diagnostic">
    <div class="container diagnosticPanel">
      <div class="diagnosticText">
        <span class="sectionLabel">Быстрый подбор</span>
        <h2 class="sectionTitle">Не знаете, что сломалось?</h2>
        <p class="sectionIntro">Выберите ситуацию — форма сразу подставит нужную услугу, а мастер уточнит детали и назовёт ориентир по цене.</p>
      </div>
      <div class="diagnosticCards">
        <button type="button" data-open-modal data-service="Стиральная машина: нужна диагностика"><b>Стиральная машина</b><span>не сливает, шумит, не отжимает</span></button>
        <button type="button" data-open-modal data-service="Холодильник: нужна диагностика"><b>Холодильник</b><span>не морозит, течёт, намерзает лёд</span></button>
        <button type="button" data-open-modal data-service="Срочный выезд мастера"><b>Срочный выезд</b><span>мастер нужен сегодня</span></button>
        <button type="button" data-open-modal data-service="Диагностика по описанию"><b>Онлайн-заявка</b><span>клиент получает код и отслеживает статус</span></button>
      </div>
    </div>
  </section>

  <section class="screenSection pricesSection" id="prices">
    <div class="container pricesGrid">
      <div class="priceInfo">
        <span class="sectionLabel">Прайс-лист</span>
        <h2 class="sectionTitle">Цены на ремонт</h2>
        <p class="sectionIntro">Цена “от” указана за работу без стоимости детали. Точная стоимость согласуется после диагностики до начала ремонта.</p>
        <button class="primaryButton" type="button" data-open-modal data-service="Расчёт стоимости ремонта">Узнать стоимость</button>
      </div>
      <div>
        <div class="priceTable" role="table" aria-label="Цены на ремонт">
          <?php foreach ($prices as $row): ?>
            <div class="priceRow" role="row"><span role="cell"><?= htmlspecialchars($row[0]) ?></span><b role="cell"><?= htmlspecialchars($row[1]) ?></b></div>
          <?php endforeach; ?>
        </div>
        <div class="priceNote">Итоговая цена зависит от модели техники, наличия детали и сложности доступа к узлу. Ремонт начинается только после согласования суммы с клиентом.</div>
      </div>
    </div>
  </section>

  <section class="screenSection mastersSection" id="masters">
    <div class="container">
      <div class="sectionTopLine">
        <div>
          <span class="sectionLabel">Наши мастера</span>
          <h2 class="sectionTitle">Специалисты по разным видам техники</h2>
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

  <section class="screenSection processSection" id="process">
    <div class="container">
      <span class="sectionLabel">Как мы работаем</span>
      <h2 class="sectionTitle">4 простых шага до исправной техники</h2>
      <div class="stepsGrid">
        <?php foreach ($steps as $step): ?>
          <article class="stepCard">
            <b><?= htmlspecialchars($step['num']) ?></b>
            <h3><?= htmlspecialchars($step['title']) ?></h3>
            <p><?= htmlspecialchars($step['text']) ?></p>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="screenSection guaranteeSection" id="guarantee">
    <div class="container guaranteePanel">
      <div class="guaranteeText">
        <span class="sectionLabel">Гарантия</span>
        <h2>Гарантия на ремонт до 12 месяцев</h2>
        <p>Мы даём гарантию на выполненные работы и установленные детали. Срок зависит от вида ремонта и запчасти. После ремонта мастер проверяет технику вместе с клиентом.</p>
      </div>
      <div class="guaranteeCard guaranteeCard--big"><strong>до 12</strong><p>месяцев гарантии</p></div>
      <div class="guaranteeCard"><strong>100%</strong><p>согласование цены до ремонта</p></div>
    </div>
  </section>

  <section class="screenSection promoSection" id="promo">
    <div class="container promoPanel">
      <div>
        <span class="sectionLabel">Консультация</span>
        <h2>Бесплатная консультация и диагностика 0 ₸ при ремонте</h2>
        <p>Оставьте заявку — уточним неисправность, подскажем ориентир по цене и согласуем удобное время выезда мастера.</p>
      </div>
      <form class="miniForm" id="promoForm">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
        <input type="hidden" name="service" value="Бесплатная консультация">
        <input type="hidden" name="source" value="consultation">
        <input name="name" type="text" placeholder="Ваше имя" autocomplete="name" required>
        <input name="phone" type="tel" placeholder="+7 ___ ___ __ __" autocomplete="tel" required>
        <input name="email" type="email" placeholder="Email для кода заявки" autocomplete="email" required>
        <label class="checkLine"><input name="privacy" type="checkbox" value="1" required> <span>Согласен на обработку персональных данных</span></label>
        <button class="primaryButton" type="submit">Получить консультацию</button>
        <p class="msg" id="promoMsg" aria-live="polite"></p>
      </form>
    </div>
  </section>

  <section class="screenSection brandsSection" id="brands">
    <div class="container">
      <span class="sectionLabel">Бренды</span>
      <h2 class="sectionTitle">Работаем с популярными брендами</h2>
      <div class="brandGrid"><?php foreach ($brands as $brand): ?><span><?= htmlspecialchars($brand) ?></span><?php endforeach; ?></div>
    </div>
  </section>

  <section class="screenSection reviewsSection" id="reviews">
    <div class="container reviewsGridHome">
      <div>
        <span class="sectionLabel">Отзывы</span>
        <h2 class="sectionTitle">Что говорят клиенты</h2>
        <p class="sectionIntro">Мы публикуем отзывы клиентов после проверки и отвечаем на обращения.</p>
        <?php if ($avgRating !== null): ?>
          <div class="ratingBox"><b><?= htmlspecialchars((string)$avgRating) ?>/5</b><span><?= (int)$reviewCount ?> опубликованных отзывов</span></div>
        <?php else: ?>
          <div class="ratingBox ratingBox--empty"><b>Отзывы</b><span>появятся после проверки заявок клиентов</span></div>
        <?php endif; ?>
        <a class="ghostButton" href="reviews.php">Оставить отзыв</a>
      </div>
      <div class="reviewsListHome">
        <?php if (!$reviews): ?>
          <article class="reviewCard"><b>Пока нет опубликованных отзывов</b><p>Первый отзыв клиента появится здесь после проверки.</p></article>
        <?php endif; ?>
        <?php foreach ($reviews as $r): ?>
          <?php $rating = max(1, min(5, (int)($r['rating'] ?? 5))); ?>
          <article class="reviewCard">
            <div class="reviewTop"><b><?= htmlspecialchars($r['name'] ?? 'Клиент') ?></b><span><?= str_repeat('★', $rating) ?><?= str_repeat('☆', 5 - $rating) ?></span></div>
            <p><?= nl2br(htmlspecialchars($r['text'] ?? '')) ?></p>
            <small><?= htmlspecialchars($r['created_at'] ?? '') ?> • Алматы</small>
            <?php if (!empty($r['reply'])): ?><div class="reply"><b>Ответ сервиса:</b><span><?= nl2br(htmlspecialchars($r['reply'])) ?></span></div><?php endif; ?>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="screenSection faqSection" id="faq">
    <div class="container faqGrid">
      <div class="faqHead">
        <span class="sectionLabel">Вопросы и ответы</span>
        <h2 class="sectionTitle">Частые вопросы</h2>
        <p class="sectionIntro">Короткие ответы снимают сомнения клиента перед заявкой.</p>
      </div>
      <div class="faqList">
        <?php foreach ($faq as $item): ?>
          <details class="faqItem"><summary><?= htmlspecialchars($item['q']) ?><i></i></summary><p><?= htmlspecialchars($item['a']) ?></p></details>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="screenSection contactsSection" id="contacts">
    <div class="container contactsGrid">
      <div class="contactInfo">
        <span class="sectionLabel">Контакты</span>
        <h2 class="sectionTitle">Вызвать мастера в Алматы</h2>
        <p class="sectionIntro">Работаем как выездной сервис: мастер приезжает на адрес клиента, проводит диагностику и согласует стоимость ремонта.</p>
        <div class="contactCards">
          <a href="tel:<?= MAIN_PHONE_DIGITS ?>"><b>Телефон</b><span><?= MAIN_PHONE ?></span></a>
          <a href="client/index.php"><b>Моя заявка</b><span>Проверить по коду</span></a>
          <div><b>Формат работы</b><span><?= COMPANY_ADDRESS ?></span></div>
          <div><b>График</b><span><?= WORK_TIME ?></span></div>
        </div>
        <div class="districts"><b>Районы обслуживания:</b><?php foreach ($districts as $district): ?><span><?= htmlspecialchars($district) ?></span><?php endforeach; ?></div>
        <div class="serviceAreaBox">
          <div><b>Выездной сервис по Алматы</b><span>Мастер приезжает на адрес клиента. Точный район, время выезда и ориентир по стоимости согласуем по телефону или через заявку на сайте.</span></div>
          <a href="tel:<?= MAIN_PHONE_DIGITS ?>">Позвонить</a>
        </div>
      </div>
      <form class="leadForm" id="leadForm">
        <h3>Оставить заявку</h3>
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
        <input type="hidden" name="source" value="contacts_home">
        <label>Имя<input name="name" type="text" placeholder="Ваше имя" autocomplete="name" required></label>
        <label>Телефон<input name="phone" type="tel" placeholder="+7 ___ ___ __ __" autocomplete="tel" required></label>
        <label>Email для кода заявки<input name="email" type="email" placeholder="example@mail.kz" autocomplete="email" required></label>
        <label>Район или адрес<input name="district" type="text" placeholder="Например: Алмалинский район" maxlength="180"></label>
        <label class="wide">Услуга<input name="service" type="text" placeholder="Например: ремонт стиральной машины" required></label>
        <label class="wide">Описание<span class="fieldHint">до 500 символов</span><textarea name="description" rows="4" maxlength="500" data-count-target="formDescCount" placeholder="Коротко опишите проблему"></textarea><small class="charCount" id="formDescCount">0/500</small></label>
        <label class="checkLine"><input name="privacy" type="checkbox" value="1" required> <span>Я согласен на обработку персональных данных и принимаю <a href="privacy.php">политику конфиденциальности</a>.</span></label>
        <button class="primaryButton" type="submit">Отправить заявку</button>
        <p class="msg" id="formMsg" aria-live="polite"></p>
      </form>
    </div>
  </section>
</main>

<?php render_site_footer($services); render_lead_modal(); require_once __DIR__ . '/includes/footer.php'; ?>
