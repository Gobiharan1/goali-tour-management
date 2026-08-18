<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

$testFixture = null;
$testFixturePath = PHP_SAPI === 'cli' ? (string)getenv('GOALI_PDF_TEST_DATA') : '';
if ($testFixturePath !== '' && is_file($testFixturePath)) {
    $decodedFixture = json_decode((string)file_get_contents($testFixturePath), true);
    if (is_array($decodedFixture) && isset($decodedFixture['tour']) && is_array($decodedFixture['tour'])) {
        $testFixture = $decodedFixture;
    }
}

if ($testFixture !== null) {
    $tour = $testFixture['tour'];
    $id = (int)($tour['id'] ?? 1);
} else {
    require_login();

    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
    if ($id < 1) {
        http_response_code(400);
        exit('A valid tour ID is required.');
    }

    $stmt = db()->prepare(
        'SELECT t.*, c.name AS category_name
         FROM tours t
         JOIN categories c ON c.id = t.category_id
         WHERE t.id = ?
         LIMIT 1'
    );
    $stmt->execute([$id]);
    $tour = $stmt->fetch();

    if (!$tour) {
        http_response_code(404);
        exit('Tour not found.');
    }
}

function line_items(?string $value): array
{
    if ($value === null || trim($value) === '') {
        return [];
    }

    return array_values(array_filter(array_map(
        'trim',
        preg_split('/\R/', trim($value)) ?: []
    )));
}

function json_array(?string $value): ?array
{
    if ($value === null || trim($value) === '') {
        return null;
    }

    $decoded = json_decode($value, true);
    return json_last_error() === JSON_ERROR_NONE && is_array($decoded)
        ? $decoded
        : null;
}

function local_image_url(?string $path, bool $forPdf = false): string
{
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }

    if (preg_match('#^(https?://|data:)#i', $path)) {
        return $path;
    }

    $relative = ltrim($path, '/');
    $relative = preg_replace('#^goali-tour-management/#', '', $relative) ?? $relative;
    $absolute = realpath(__DIR__ . '/' . $relative);

    if ($forPdf && $absolute && is_file($absolute)) {
        return 'file://' . $absolute;
    }

    return $relative;
}

function normalize_days(?string $raw): array
{
    $decoded = json_array($raw);
    if ($decoded !== null) {
        $days = [];
        foreach ($decoded as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $days[] = [
                'day' => $item['day'] ?? ($index + 1),
                'title' => trim((string)($item['title'] ?? $item['name'] ?? '')),
                'details' => trim((string)($item['details'] ?? $item['description'] ?? $item['content'] ?? '')),
                'image' => trim((string)($item['image'] ?? $item['image_url'] ?? '')),
            ];
        }
        return $days;
    }

    $days = [];
    foreach (line_items($raw) as $index => $line) {
        $number = $index + 1;
        $title = $line;
        if (preg_match('/^day\s*(\d+)\s*:\s*(.*)$/i', $line, $matches)) {
            $number = (int)$matches[1];
            $title = trim($matches[2]);
        }
        $days[] = ['day' => $number, 'title' => $title, 'details' => '', 'image' => ''];
    }
    return $days;
}

function normalize_gallery(?string $raw): array
{
    $decoded = json_array($raw);
    if ($decoded === null) {
        return line_items($raw);
    }

    $images = [];
    foreach ($decoded as $item) {
        $image = is_string($item)
            ? $item
            : (is_array($item) ? ($item['image'] ?? $item['image_url'] ?? $item['src'] ?? '') : '');
        if (trim((string)$image) !== '') {
            $images[] = trim((string)$image);
        }
    }
    return $images;
}

$downloadRequested = (isset($_GET['download']) && $_GET['download'] === '1') || $testFixture !== null;
$days = normalize_days($tour['day_plans'] ?? '');
$locations = line_items($tour['locations'] ?? '');
$highlights = line_items($tour['highlights'] ?? '');
$inclusions = line_items($tour['inclusions'] ?? '');
$exclusions = line_items($tour['exclusions'] ?? '');
$gallery = normalize_gallery($tour['gallery'] ?? '');
$legacyImages = line_items($tour['images'] ?? '');

$coverImage = '';
foreach ($days as $day) {
    if ($day['image'] !== '') {
        $coverImage = $day['image'];
        break;
    }
}
if ($coverImage === '') {
    $coverImage = $gallery[0] ?? $legacyImages[0] ?? '';
}

$logoPath = $testFixture['brand']['logo_path'] ?? company_logo();
$companyName = $testFixture['brand']['company_name'] ?? company_name();
$currency = strtoupper(trim((string)($tour['price_currency'] ?? 'LKR')));
$symbols = ['USD' => '$', 'EUR' => 'EUR', 'GBP' => 'GBP', 'LKR' => 'LKR'];
$price = (float)($tour['price_amount'] ?? 0);
$priceText = ($symbols[$currency] ?? $currency) . ' ' . number_format($price, 2);
$safeName = preg_replace('/[^A-Za-z0-9-]+/', '-', (string)$tour['tour_name']) ?: 'Goali-Tours';
$pdfFilename = trim($safeName, '-') . '-' . ($tour['package_id'] ?? $id) . '.pdf';
$dompdfAvailable = is_file(__DIR__ . '/vendor/autoload.php');
$forPdf = $downloadRequested && $dompdfAvailable;

ob_start();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($pdfFilename) ?></title>
<style>
:root{--forest:#123f2d;--forest2:#1f6247;--gold:#d7a84b;--cream:#f5f1e8;--ink:#14231c;--muted:#66766d;--line:#dce5df;--paper:#fff}
*{box-sizing:border-box}html,body{margin:0;padding:0}body{font-family:Arial,Helvetica,sans-serif;color:var(--ink);background:#e9eeeb;line-height:1.55}.controls{position:sticky;z-index:20;top:0;display:flex;justify-content:center;gap:10px;padding:13px;background:rgba(18,35,27,.92);backdrop-filter:blur(10px)}.controls a,.controls button{padding:10px 16px;border:0;border-radius:9px;color:#fff;background:#28684d;font:700 13px Arial;text-decoration:none;cursor:pointer}.controls .secondary{color:#173d2c;background:#fff}.notice{width:210mm;margin:14px auto;padding:12px 16px;border-radius:10px;color:#754d09;background:#fff3cf;font-size:13px}.sheet{position:relative;width:210mm;min-height:297mm;margin:16px auto;padding:19mm 18mm 20mm;overflow:hidden;background:var(--paper);page-break-after:always}.sheet:last-of-type{page-break-after:auto}.eyebrow{margin-bottom:7px;color:var(--forest2);font-size:10px;font-weight:800;letter-spacing:.18em;text-transform:uppercase}.page-title{margin:0 0 8px;font-family:Georgia,'Times New Roman',serif;font-size:30px;line-height:1.15;letter-spacing:-.02em}.lead{max-width:560px;margin:0;color:var(--muted);font-size:14px}.section-head{margin:0 0 16px;padding-bottom:10px;border-bottom:1px solid var(--line)}.section-head:after{display:block;width:38px;height:3px;margin-top:10px;background:var(--gold);content:''}.brand{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}.logo{max-width:48mm;max-height:17mm}.wordmark{font-size:15px;font-weight:900;letter-spacing:.17em;text-transform:uppercase}.package-id{color:var(--muted);font-size:10px;letter-spacing:.08em}.cover{padding:0;background:var(--forest);color:#fff}.cover-image{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}.cover-overlay{position:absolute;inset:0;background:linear-gradient(180deg,rgba(7,26,18,.12),rgba(7,26,18,.3) 38%,rgba(7,26,18,.95) 86%)}.cover-content{position:absolute;right:18mm;bottom:18mm;left:18mm}.cover-top{position:absolute;z-index:2;top:17mm;right:18mm;left:18mm;display:flex;align-items:center;justify-content:space-between}.cover .wordmark,.cover .package-id{color:#fff}.cover-kicker{color:#e7c982;font-size:11px;font-weight:800;letter-spacing:.18em;text-transform:uppercase}.cover h1{max-width:155mm;margin:9px 0 13px;font-family:Georgia,'Times New Roman',serif;font-size:46px;line-height:1.02;letter-spacing:-.035em}.cover-meta{display:flex;gap:8px;flex-wrap:wrap}.cover-meta span{padding:6px 10px;border:1px solid rgba(255,255,255,.26);border-radius:99px;background:rgba(255,255,255,.1);font-size:11px}.prepared{margin-top:23px;padding-top:16px;border-top:1px solid rgba(255,255,255,.25);color:#dce8df;font-size:12px}.prepared strong{color:#fff}.stat-row{display:flex;gap:10px;margin:25px 0}.stat{flex:1;padding:16px;border:1px solid var(--line);border-radius:12px;background:#f8faf8}.stat b{display:block;font-family:Georgia,'Times New Roman',serif;font-size:21px}.stat span{color:var(--muted);font-size:9px;font-weight:800;letter-spacing:.1em;text-transform:uppercase}.route{margin:21px 0;padding:18px 20px;border-radius:13px;color:#fff;background:var(--forest)}.route ol{margin:10px 0 0;padding-left:19px;columns:2;column-gap:30px}.route li{margin:5px 0;color:#dfe9e3;font-size:12px}.highlights{display:flex;flex-wrap:wrap;gap:8px;margin:18px 0}.highlight{padding:8px 11px;border-radius:8px;color:var(--forest);background:#e7f0ea;font-size:11px;font-weight:700}.customer{margin-top:18px;padding:16px 18px;border-left:4px solid var(--gold);background:var(--cream);font-size:12px}.day{display:table;width:100%;margin:0 0 15px;border:1px solid var(--line);border-radius:13px;background:#fff;page-break-inside:avoid;overflow:hidden}.day-media,.day-copy{display:table-cell;vertical-align:top}.day-media{width:39%;height:51mm}.day-media img{width:100%;height:51mm;object-fit:cover}.day-copy{padding:15px 17px}.day-number{color:var(--forest2);font-size:9px;font-weight:900;letter-spacing:.16em;text-transform:uppercase}.day h3{margin:3px 0 8px;font-family:Georgia,'Times New Roman',serif;font-size:19px;line-height:1.2}.day p{margin:0;color:#53655b;font-size:11px;line-height:1.6}.day.no-image .day-copy{display:block}.two-col{display:table;width:100%;table-layout:fixed}.col{display:table-cell;width:50%;padding-right:9mm;vertical-align:top}.col:last-child{padding-right:0;padding-left:9mm;border-left:1px solid var(--line)}.checklist{margin:0;padding:0;list-style:none}.checklist li{position:relative;margin:0 0 10px;padding-left:20px;font-size:12px}.checklist li:before{position:absolute;left:0;top:2px;width:13px;height:13px;border-radius:50%;color:#fff;background:var(--forest2);font-size:8px;line-height:13px;text-align:center;content:'+'}.checklist.excluded li:before{background:#9d5c51;content:'-'} .price-card{margin-top:26px;padding:20px 22px;border-radius:14px;color:#fff;background:var(--forest)}.price-card small{display:block;color:#cfe0d6;font-size:9px;font-weight:800;letter-spacing:.13em;text-transform:uppercase}.price-card strong{display:block;margin-top:3px;font-family:Georgia,'Times New Roman',serif;font-size:30px}.notes{margin-top:20px;padding:17px 19px;border:1px solid #eadbb8;border-radius:12px;background:#fbf7ed;font-size:12px}.gallery{display:grid;grid-template-columns:1fr 1fr;gap:9px}.gallery figure{height:64mm;margin:0;overflow:hidden;border-radius:12px;background:#dfe8e2;page-break-inside:avoid}.gallery img{width:100%;height:100%;object-fit:cover}.footer{position:absolute;right:18mm;bottom:8mm;left:18mm;display:flex;justify-content:space-between;padding-top:7px;border-top:1px solid var(--line);color:#829087;font-size:8px;letter-spacing:.06em;text-transform:uppercase}.closing{display:flex;min-height:245mm;align-items:center;justify-content:center;text-align:center}.closing-mark{width:18mm;height:3px;margin:0 auto 20px;background:var(--gold)}.closing h2{margin:0;font-family:Georgia,'Times New Roman',serif;font-size:34px}.closing p{color:var(--muted)}
@media print{@page{size:A4;margin:0}body{background:#fff}.controls,.notice{display:none!important}.sheet{width:210mm;min-height:297mm;margin:0;box-shadow:none}}
@media screen{.sheet{box-shadow:0 16px 50px rgba(18,35,27,.16)}}
@media(max-width:850px){.sheet{width:100%;min-height:auto;margin:0;padding:28px 22px}.cover{min-height:100vh}.day{display:block}.day-media,.day-copy{display:block;width:100%}.two-col,.col{display:block;width:100%}.col,.col:last-child{padding:0;border:0}.gallery{grid-template-columns:1fr}.footer{display:none}}
<?php if ($forPdf): ?>
@page{size:A4;margin:0}
html,body{width:210mm;margin:0;padding:0;background:#fff}
*{box-sizing:content-box}
.sheet{width:174mm;height:258mm;min-height:0;margin:0;padding:19mm 18mm 20mm;overflow:hidden;page-break-after:always}
.sheet:last-of-type{page-break-after:auto}
.cover{width:210mm;height:297mm;padding:0;background-color:#123f2d;background-position:center;background-repeat:no-repeat;background-size:cover}
.cover-overlay{top:0;right:auto;bottom:auto;left:0;width:210mm;height:297mm}
.cover-top{display:table;width:174mm}
.cover-top>*{display:table-cell;vertical-align:middle}
.cover-top .package-id{text-align:right}
.brand{display:table;width:100%}
.brand>*{display:table-cell;vertical-align:middle}
.brand .package-id{text-align:right}
.stat-row{display:table;width:100%;border-collapse:separate;border-spacing:3mm}
.stat{display:table-cell;width:33.33%}
.highlights{display:block}
.highlight{display:inline-block;margin:0 2mm 2mm 0}
.pdf-day{width:100%;margin:0 0 5mm;border:.25mm solid #dce5df;border-collapse:collapse;page-break-inside:avoid}
.pdf-day td{vertical-align:middle}
.pdf-day-media{width:66mm;height:50mm;overflow:hidden;background:#e7f0ea;text-align:center}
.pdf-day-media img{display:block;width:66mm;height:auto;max-height:50mm;margin:0 auto}
.pdf-day-copy{padding:5mm}
.pdf-day h3{margin:1mm 0 3mm;font-family:Georgia,'Times New Roman',serif;font-size:16pt;line-height:1.2}
.pdf-day p{margin:0;color:#53655b;font-size:8.5pt;line-height:1.55}
.pdf-gallery{width:100%;border-collapse:separate;border-spacing:3mm}
.pdf-gallery td{width:50%;height:64mm;background:#dfe8e2;text-align:center;vertical-align:middle;overflow:hidden}
.pdf-gallery img{display:inline-block;width:auto;height:auto;max-width:82mm;max-height:64mm}
.footer{display:table;width:174mm}
.footer span{display:table-cell}
.footer span:last-child{text-align:right}
.closing{display:table;width:100%;height:245mm;min-height:0}
.closing>div{display:table-cell;vertical-align:middle}
<?php endif; ?>
</style>
</head>
<body>
<?php if (!$forPdf): ?>
<div class="controls">
  <button type="button" onclick="window.print()">Print / Save PDF</button>
  <a class="secondary" href="generate_pdf.php?id=<?= $id ?>&amp;download=1">Download PDF</a>
  <a href="dashboard.php">Back to dashboard</a>
</div>
<?php if ($downloadRequested && !$dompdfAvailable): ?>
<div class="notice">Direct PDF download needs Dompdf. Run <strong>composer install</strong>, or use Print / Save PDF now.</div>
<?php endif; ?>
<?php endif; ?>

<section class="sheet cover"<?php if ($forPdf && $coverImage !== ''): ?> style="background-image:url('<?= e(local_image_url($coverImage, true)) ?>')"<?php endif; ?>>
  <?php if (!$forPdf && $coverImage !== ''): ?><img class="cover-image" src="<?= e(local_image_url($coverImage, false)) ?>" alt=""><?php endif; ?>
  <div class="cover-overlay"></div>
  <div class="cover-top">
    <?php if ($logoPath !== ''): ?><img class="logo" src="<?= e(local_image_url($logoPath, $forPdf)) ?>" alt="<?= e($companyName) ?>"><?php else: ?><div class="wordmark"><?= e($companyName) ?></div><?php endif; ?>
    <div class="package-id"><?= e((string)$tour['package_id']) ?></div>
  </div>
  <div class="cover-content">
    <div class="cover-kicker"><?= e((string)$tour['category_name']) ?></div>
    <h1><?= e((string)$tour['tour_name']) ?></h1>
    <div class="cover-meta">
      <span><?= (int)$tour['duration_days'] ?> days / <?= (int)$tour['duration_nights'] ?> nights</span>
      <span><?= e((string)$tour['activity_level']) ?> pace</span>
      <?php if ($price > 0): ?><span>From <?= e($priceText) ?></span><?php endif; ?>
    </div>
    <?php if (!empty($tour['customer_name'])): ?><div class="prepared">Exclusively prepared for <strong><?= e((string)$tour['customer_name']) ?></strong></div><?php endif; ?>
  </div>
</section>

<section class="sheet">
  <div class="brand"><div class="wordmark"><?= e($companyName) ?></div><div class="package-id"><?= e((string)$tour['package_id']) ?></div></div>
  <header class="section-head"><div class="eyebrow">Journey at a glance</div><h2 class="page-title">Your Sri Lankan story</h2><p class="lead">A considered route with the details organized, so the traveller can focus on the experience.</p></header>
  <div class="stat-row">
    <div class="stat"><b><?= (int)$tour['duration_days'] ?></b><span>Days</span></div>
    <div class="stat"><b><?= (int)$tour['duration_nights'] ?></b><span>Nights</span></div>
    <div class="stat"><b><?= e((string)$tour['activity_level']) ?></b><span>Activity</span></div>
  </div>
  <?php if ($highlights): ?><div class="eyebrow">Signature highlights</div><div class="highlights"><?php foreach ($highlights as $item): ?><span class="highlight"><?= e($item) ?></span><?php endforeach; ?></div><?php endif; ?>
  <?php if ($locations): ?><div class="route"><div class="eyebrow" style="color:#e7c982">The route</div><ol><?php foreach ($locations as $location): ?><li><?= e($location) ?></li><?php endforeach; ?></ol></div><?php endif; ?>
  <?php if (!empty($tour['customer_details'])): ?><div class="customer"><div class="eyebrow">Traveller notes</div><?= nl2br(e((string)$tour['customer_details'])) ?></div><?php endif; ?>
  <div class="footer"><span><?= e($companyName) ?></span><span>Journey overview</span></div>
</section>

<?php if ($days): ?>
<?php $dayPages = $forPdf ? array_chunk($days, 2) : [$days]; ?>
<?php foreach ($dayPages as $dayPage): ?>
<section class="sheet">
  <div class="brand"><div class="wordmark"><?= e($companyName) ?></div><div class="package-id"><?= e((string)$tour['package_id']) ?></div></div>
  <header class="section-head"><div class="eyebrow">Day by day</div><h2 class="page-title">The itinerary</h2></header>
  <?php foreach ($dayPage as $day): ?>
    <?php if ($forPdf): ?>
    <table class="pdf-day"><tr>
      <?php if ($day['image'] !== ''): ?><td class="pdf-day-media"><img src="<?= e(local_image_url($day['image'], true)) ?>" alt="Day <?= e((string)$day['day']) ?>"></td><?php endif; ?>
      <td class="pdf-day-copy"><div class="day-number">Day <?= e((string)$day['day']) ?></div><h3><?= e($day['title'] !== '' ? $day['title'] : 'A new chapter') ?></h3><?php if ($day['details'] !== ''): ?><p><?= nl2br(e($day['details'])) ?></p><?php endif; ?></td>
    </tr></table>
    <?php else: ?>
    <article class="day <?= $day['image'] === '' ? 'no-image' : '' ?>">
      <?php if ($day['image'] !== ''): ?><div class="day-media"><img src="<?= e(local_image_url($day['image'], false)) ?>" alt="Day <?= e((string)$day['day']) ?>"></div><?php endif; ?>
      <div class="day-copy"><div class="day-number">Day <?= e((string)$day['day']) ?></div><h3><?= e($day['title'] !== '' ? $day['title'] : 'A new chapter') ?></h3><?php if ($day['details'] !== ''): ?><p><?= nl2br(e($day['details'])) ?></p><?php endif; ?></div>
    </article>
    <?php endif; ?>
  <?php endforeach; ?>
  <div class="footer"><span><?= e($companyName) ?></span><span>Day-by-day itinerary</span></div>
</section>
<?php endforeach; ?>
<?php endif; ?>

<section class="sheet">
  <div class="brand"><div class="wordmark"><?= e($companyName) ?></div><div class="package-id"><?= e((string)$tour['package_id']) ?></div></div>
  <header class="section-head"><div class="eyebrow">Know before you go</div><h2 class="page-title">What is included</h2></header>
  <div class="two-col">
    <div class="col"><div class="eyebrow">Included</div><ul class="checklist"><?php foreach ($inclusions ?: ['Details will be confirmed with your travel specialist.'] as $item): ?><li><?= e($item) ?></li><?php endforeach; ?></ul></div>
    <div class="col"><div class="eyebrow">Not included</div><ul class="checklist excluded"><?php foreach ($exclusions ?: ['Any item not specifically listed as included.'] as $item): ?><li><?= e($item) ?></li><?php endforeach; ?></ul></div>
  </div>
  <?php if ($price > 0): ?><div class="price-card"><small>Package investment</small><strong><?= e($priceText) ?></strong></div><?php endif; ?>
  <?php if (!empty($tour['important_notes'])): ?><div class="notes"><div class="eyebrow">Important notes</div><?= nl2br(e((string)$tour['important_notes'])) ?></div><?php endif; ?>
  <?php if (!empty($tour['custom_field'])): ?><div class="notes"><div class="eyebrow">Additional information</div><?= nl2br(e((string)$tour['custom_field'])) ?></div><?php endif; ?>
  <div class="footer"><span><?= e($companyName) ?></span><span>Package details</span></div>
</section>

<?php if ($gallery): ?>
<?php $galleryPages = $forPdf ? array_chunk(array_slice($gallery, 0, 6), 4) : [array_slice($gallery, 0, 6)]; ?>
<?php foreach ($galleryPages as $galleryPage): ?>
<section class="sheet">
  <div class="brand"><div class="wordmark"><?= e($companyName) ?></div><div class="package-id"><?= e((string)$tour['package_id']) ?></div></div>
  <header class="section-head"><div class="eyebrow">A glimpse ahead</div><h2 class="page-title">Moments from the journey</h2></header>
  <?php if ($forPdf): ?>
  <table class="pdf-gallery"><?php foreach (array_chunk($galleryPage, 2) as $row): ?><tr><?php foreach ($row as $image): ?><td><img src="<?= e(local_image_url($image, true)) ?>" alt="Tour destination"></td><?php endforeach; ?><?php if (count($row) === 1): ?><td></td><?php endif; ?></tr><?php endforeach; ?></table>
  <?php else: ?>
  <div class="gallery"><?php foreach ($galleryPage as $image): ?><figure><img src="<?= e(local_image_url($image, false)) ?>" alt="Tour destination"></figure><?php endforeach; ?></div>
  <?php endif; ?>
  <div class="footer"><span><?= e($companyName) ?></span><span>Journey gallery</span></div>
</section>
<?php endforeach; ?>
<?php endif; ?>

<section class="sheet">
  <div class="closing"><div><div class="closing-mark"></div><div class="eyebrow">Designed around you</div><h2>Ready when you are.</h2><p>We look forward to creating an unforgettable journey.</p></div></div>
  <div class="footer"><span><?= e($companyName) ?></span><span><?= e((string)$tour['package_id']) ?></span></div>
</section>
</body>
</html>
<?php
$html = ob_get_clean();

if ($forPdf) {
    require __DIR__ . '/vendor/autoload.php';
    $options = new Dompdf\Options();
    $options->set('isRemoteEnabled', true);
    $options->set('chroot', __DIR__);
    $dompdf = new Dompdf\Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream($pdfFilename, ['Attachment' => true]);
    exit;
}

echo $html;
