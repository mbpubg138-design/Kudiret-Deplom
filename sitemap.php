<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';
header('Content-Type: application/xml; charset=utf-8');
$base = site_base_url();
$pages = [
  ['/', '1.0'],
  ['/uslugi.php', '0.9'],
  ['/prices.php', '0.9'],
  ['/masters.php', '0.8'],
  ['/contacts.php', '0.9'],
  ['/remont-stiralnyh-mashin.php', '0.9'],
  ['/remont-holodilnikov.php', '0.9'],
  ['/remont-mikrovolnovok.php', '0.8'],
  ['/remont-posudomoechnyh-mashin.php', '0.8'],
  ['/remont-plit-duhovok.php', '0.8'],
  ['/remont-melkoy-tehniki.php', '0.7'],
  ['/about.php', '0.6'],
  ['/reviews.php', '0.6'],
  ['/privacy.php', '0.3'],
];
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($pages as [$url, $priority]): ?>
  <url><loc><?= htmlspecialchars($base . $url, ENT_XML1) ?></loc><priority><?= $priority ?></priority></url>
<?php endforeach; ?>
</urlset>
