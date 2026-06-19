<?php
require_once __DIR__ . '/includes/service_template.php';
$data = $servicePages['dishwasher'];
$pageTitle = $data['title'] . ' — ' . APP_NAME;
$pageDescription = $data['description'];
require_once __DIR__ . '/includes/header.php';
render_service_page($data);
require_once __DIR__ . '/includes/footer.php';
