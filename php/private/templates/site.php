<?php
/** @var array $c content, @var array $s settings */
$todo = fn(string $t) => $s['show_todo_tags'] ? '<span class="todo">[' . e($t) . ']</span>' : '';
$t = $s['tracking'];
$gtm = valid_gtm($t['gtm_id'] ?? ''); $pixel = valid_pixel($t['meta_pixel_id'] ?? ''); $ga4 = valid_ga4($t['ga4_id'] ?? '');
$yt = youtube_id($c['video_url']);
$wa = preg_replace('/\D/', '', (string)$c['whatsapp']);
$telHref = 'tel:' . preg_replace('/[^\d+]/', '', $c['phone']);

$metro = [['Victoria Memorial', 20], ['Park Street', 25], ['Esplanade', 30], ['Rabindra Sadan', 35], ['Burrabazar', 35], ['Howrah Station', 40], ['Sealdah', 50], ['Airport', 70]];
$near = [
  ['N', 'No more commute anxiety', 'Near to where you need to be.', 'A 9-minute walk to Joka Metro means your day begins with certainty, not calculations. No searching for an auto. Just walk, board and go.'],
  ['E', 'Every life stage, one address', 'Near to every chapter of life.', "Whether you're buying your first home, raising a growing family or looking for more space, there's a 2, 3 or 4 BHK that's right for this stage of life."],
  ['A', 'Access to social infrastructure', 'Near to entertainment, business & leisure.', 'Premier schools, multi-specialty hospitals, your daily market and banks, all within reach in a greener, fast-emerging neighbourhood.'],
  ['R', 'Right-sized community', 'Near to a community that feels like one.', 'Just 127 residences means fewer people sharing more: the pool, the club, the gardens. Familiar faces replace anonymous crowds.'],
];
$amen = [
  'Games & sports' => ['Fitness studio', 'Multipurpose court', 'Table tennis', 'Jogging track', 'Kids’ play area', 'Yoga lawn', 'Acupressure walkway', 'Pool table', 'Chess, carrom & darts', 'Foosball', 'Air hockey', 'Toddlers’ playroom', 'Arcade gaming zone'],
  'Facilities & amenities' => ['Swimming pool with speakers', 'Banquet hall', 'Kids’ pool', 'Party lawn', 'Festival zone', 'Flower & vertical garden', 'Manicured garden', 'Pet-friendly zone', 'BBQ zone', 'Mandir at rooftop', 'Senior citizens’ zone', 'Adda zone', 'Feature wall', 'Outdoor seating', 'Sunken seating'],
  'Infrastructure' => ['Reception lobby', 'CCTV surveillance', 'Intercom', 'Solar panels', 'Water treatment plant', 'Rainwater harvesting', 'Recycled water for gardening & flushing', 'Boundary plantation', 'Wheelchair access ramp', 'Boom barrier at entry', 'Fire door at fire-escape staircase', 'Solar-powered lights in common areas', 'High-speed elevators up to roof', 'Wastewater recycling', 'Sewage treatment plant'],
];
$conn = [['Joka Metro', '0.7'], ['Thakurpukur Metro', '2.4'], ['Behala Chowrasta', '6.3'], ['Taratala Crossing / Metro', '7.3'], ['Majerhat', '8.6'], ['Tollygunge Metro', '8.6'], ['Alipore', '10.0'], ['Park Street', '13.8'], ['BBD Bag', '15.7'], ['Sealdah Station', '17.5'], ['Howrah Station', '18.9'], ['Salt Lake', '22.7'], ['New Town (Biswa Bangla Gate)', '29.4'], ['Airport', '32.0']];
$nearby = [
  'Education' => [['IIM Calcutta', '0.4'], ['Vivekananda Mission School', '1.1'], ['M.P. Birla Foundation H.S. School', '5.9'], ['Delhi Public School, Joka', '6.5']],
  'Healthcare' => [['Getwell Hospital', '0.3'], ['ESI Hospital', '0.7'], ['Kasturi Medical Centre', '1.3'], ['Bharat Sevashram Hospital', '1.3']],
  'Shopping & leisure' => [['More Supermarket', '0.5'], ['Acropolis Mall South', '0.6'], ['Reliance Trends', '2.2'], ['Swaminarayan Temple', '4.0']],
  'Banking' => [['Axis Bank', '0.3'], ['SBI', '0.4'], ['HDFC Bank', '2.5']],
];
$specs = [
  'Walls' => ['Common areas: paint', 'Inside the home: POP finish', 'Exterior: paint'],
  'Flooring' => ['Vitrified tiles in living room and bedrooms', 'Marble, tiles or stone in all common areas'],
  'Kitchen' => ['Granite counter with stainless steel sink', '2 ft ceramic dado tiles'],
  'Toilets' => ['Ceramic tiles up to dado', 'Anti-skid floor tiles', 'Concealed plumbing, CP fittings of a reputed brand'],
  'Doors' => ['Main door: flush door, laminate on one side, with fittings', 'Other doors: door shutters with fittings'],
  'Windows' => ['Aluminium powder-coated or uPVC sliding windows'],
  'Electrical' => ['Concealed copper wiring', 'TV and intercom points in the living room', 'AC points in all bedrooms and the living-dining area'],
  'Power backup' => ['Common-area lighting', '1 kVA for 2 BHK, 1.5 kVA for 3 BHK, 2 kVA for 4 BHK'],
  'Lift' => ['Elevator of a leading brand'],
];
$team = [
  ['Innate', 'Architect', 'Architecture becomes meaningful when it improves the way a home is lived in. We have deliberately balanced openness, proportion and privacy to give every 9 Minnutes apartment its own sense of space.'],
  ['MNU Consultants Pvt. Ltd.', 'Structural consultant', 'A well-engineered structure should give architecture the freedom to do what it does best. Our focus at 9 Minnutes has been on precision, consistency and structural efficiency from the ground up.'],
  ['Inscape Studio', 'Landscape consultant', 'We did not want the landscape to simply surround the building. We wanted it to become part of daily life, with places to move, pause, play, gather and simply be outdoors.'],
];
$tiers = [];
foreach ($c['homes'] as $h) $tiers[$h['tier']] = $h['label'] . ' · ' . $h['bhk'];
$planLabel = function (array $p): string {
    $parts = array_map('trim', explode('|', $p['config']));
    if (isset($parts[1])) $parts[1] = str_replace(' T', ' Toilets', $parts[1]);
    for ($i = 2; $i < count($parts); $i++) $parts[$i] = ucwords(strtolower($parts[$i]));
    return implode(' · ', $parts);
};
?><!doctype html>
<html lang="en-IN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>9 Minnutes, Joka – 2, 3 &amp; 4 BHK homes a 9-minute walk from Joka Metro | Realmark</title>
<meta name="description" content="9 Minnutes by Realmark: 2, 3 &amp; 4 BHK residences on Hanspukur Green Park Road, Joka, a 9-minute walk from Joka Metro. Register for launch priority.">
<meta property="og:title" content="9 Minnutes, Joka – Joka's most walkable address">
<meta property="og:description" content="2, 3 &amp; 4 BHK residences a 9-minute walk from Joka Metro. Register for launch priority.">
<meta property="og:image" content="/assets/hero.jpg">
<meta property="og:type" content="website">
<link rel="icon" href="/assets/logo-9minnutes.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@200;300;400;500;600;700&amp;family=Geologica:wght@400;500&amp;display=swap" rel="stylesheet">
<link rel="stylesheet" href="/site.css?v=<?= @filemtime(PUBLIC_DIR . '/site.css') ?>">
<?php if ($gtm): ?>
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?= $gtm ?>');</script>
<?php endif; ?>
<?php if ($ga4): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= $ga4 ?>"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= $ga4 ?>');</script>
<?php endif; ?>
<?php if ($pixel): ?>
<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','<?= $pixel ?>');fbq('track','PageView');</script>
<?php endif; ?>
</head>
<body>
<?php if ($gtm): ?><noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?= $gtm ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript><?php endif; ?>

<?php if ($s['banner_on'] && trim($c['banner_text']) !== ''): ?>
<a class="banner" href="<?= e($c['banner_link'] ?: '#register') ?>"><?= e($c['banner_text']) ?></a>
<?php endif; ?>

<header class="top"><div class="in">
<a href="#top" aria-label="9 Minnutes home"><img src="/assets/logo-9minnutes.svg" alt="9 Minnutes" style="height:34px;display:block"></a>
<nav aria-label="Sections">
<a href="#near">NEAR</a><a href="#residences">Residences</a><a href="#plans">Floor plans</a><a href="#amenities">Amenities</a><a href="#gallery">Gallery</a><a href="#masterplan">Master plan</a><a href="#location">Location</a><a href="#faq">FAQ</a>
<a href="#register" class="cta">Register interest</a>
</nav>
</div></header>

<section id="top" class="hero"><div class="wrap">
<p class="kicker"><?= e($c['hero_eyebrow']) ?></p>
<h1><?= nl($c['hero_title']) ?></h1>
<p class="sub"><?= e($c['hero_subtitle']) ?></p>
<div class="ctas"><a href="#register" class="btn btn-gold">Register for launch priority</a><a href="#near" class="btn btn-line">Explore the project</a></div>
<p style="margin:28px 0 0;font-size:12px;opacity:.75">Artist's impression.</p>
</div></section>

<section aria-label="Key facts" class="facts"><div class="in">
<?php foreach ($c['facts'] as $f): ?><div><div class="v"><?= e($f['value']) ?></div><div class="l"><?= e($f['label']) ?></div></div><?php endforeach; ?>
</div></section>

<section id="near" class="blush"><div class="wrap">
<div class="split"><div><p class="eyebrow">Our project philosophy</p><h2 class="h2">Designed around <strong style="font-weight:600">NEAR.</strong></h2></div>
<p style="font-size:20px;font-weight:300;line-height:1.55">Not simply because the Metro is near. But because everything that matters is. NEAR isn't about distance. It's about removing distance from what matters.</p></div>
<div class="grid near">
<?php foreach ($near as [$l, $h, $tag, $p]): ?>
<div class="card"><div class="letter"><?= $l ?></div><h3><?= e($h) ?></h3><p class="tag"><?= e($tag) ?></p><p><?= e($p) ?></p></div>
<?php endforeach; ?>
</div></div></section>

<section class="metro"><div class="wrap">
<div class="col">
<h2 class="h2" style="color:#fff">Walk 9 minutes.<br>Let the Metro do the rest.</h2>
<p style="margin:24px 0 36px;font-size:18px;font-weight:300;line-height:1.6;opacity:.9">Joka Metro is 0.7 km from your door, on the Purple Line into the heart of the city.</p>
<div class="times"><?php foreach ($metro as [$p, $m]): ?><div><div style="font-size:16px"><?= e($p) ?></div><div class="m"><?= $m ?> <span style="font-size:15px">mins</span></div></div><?php endforeach; ?></div>
<p style="margin:28px 0 0;font-size:13px;line-height:1.5;opacity:.85"><?= $c['metro_note'] !== '' ? e($c['metro_note']) : 'Indicative travel times. ' . $todo('CONFIRM Metro wording with legal') ?></p>
</div>
<img src="/assets/metro-tower.jpg" alt="Artist's impression of the 9 Minnutes tower beside the Joka Metro line">
</div></section>

<section id="residences"><div class="wrap">
<p class="eyebrow">Residences</p>
<h2 class="h2">A home for every chapter of life.</h2>
<p class="lead" style="margin-bottom:56px"><?= $c['three_sides_open'] !== '' ? e($c['three_sides_open']) : $todo('CONFIRM: "Every residence, three sides open"') ?></p>
<div class="grid homes">
<?php foreach ($c['homes'] as $h): $sold = !empty($s['sold_out'][$h['tier']]); ?>
<div class="home">
<?php if ($sold): ?><span class="soldout">Sold out</span><?php endif; ?>
<div class="tier"><?= e($h['label']) ?></div>
<div class="bhk"><?= e($h['bhk']) ?></div>
<div class="desc"><?= e($h['desc']) ?></div>
<div class="line"><?= e($h['line']) ?></div>
<div class="area">Carpet area<br><strong><?= e($h['carpet']) ?></strong> sq. ft.</div>
<?php if ($s['show_prices'] && trim($h['price']) !== ''): ?><div class="price"><?= e($h['price']) ?></div><?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<p style="margin:28px 0 0;font-size:15px;font-weight:300">
<?php if ($s['show_prices']): ?><?= e($c['price_note']) ?> <?php else: ?>Prices will be announced at launch. Registered buyers hear first. <?php endif; ?>
<a href="#plans">See all floor plans</a> · <a href="#register">Register now</a></p>
</div></section>

<section id="plans" class="blush"><div class="wrap">
<p class="eyebrow">Floor plans</p>
<h2 class="h2">Find the plan that fits.</h2>
<p class="lead"><?= count($c['plans']) ?> layouts across four residence types. Tap a plan to see it in full.</p>
<div class="plan-tabs" role="group" aria-label="Residence type">
<?php $first = true; foreach ($tiers as $k => $label): ?><button type="button" class="plan-tab" data-tier="<?= e($k) ?>" aria-pressed="<?= $first ? 'true' : 'false' ?>"><?= e($label) ?><?= !empty($s['sold_out'][$k]) ? ' (sold out)' : '' ?></button><?php $first = false; endforeach; ?>
</div>
<div class="grid plans">
<?php foreach ($c['plans'] as $p): $alt = "Floor plan, flat {$p['flat']}, {$p['config']}, {$p['floors']}"; ?>
<figure class="plan" data-tier="<?= e($p['tier']) ?>">
<a href="<?= e(media_url($p['image'])) ?>" class="zoom" data-caption="<?= e($alt) ?>" aria-label="Enlarge <?= e($alt) ?>"><img src="<?= e(media_url($p['thumb'] ?: $p['image'])) ?>" alt="<?= e($alt) ?>" loading="lazy" width="720" height="498"></a>
<figcaption><div class="t"><?= e(ucfirst(strtolower($p['tier']))) ?> · Flat <?= e($p['flat']) ?></div><div class="c"><?= e($planLabel($p)) ?></div><div class="f"><?= e($p['floors']) ?></div>
<div class="a">Carpet area <strong><?= e($p['carpet']) ?> sq. ft.</strong></div><?php if ($p['terrace'] !== ''): ?><div class="f">Open terrace <?= e($p['terrace']) ?> sq. ft.</div><?php endif; ?></figcaption>
</figure>
<?php endforeach; ?>
</div>
<p class="small" style="margin:24px 0 0">Plans are indicative and not to scale. Carpet area as per RERA.</p>
</div></section>

<section id="specifications"><div class="wrap">
<p class="eyebrow">Specifications</p>
<h2 class="h2">Built with care, inside and out.</h2>
<div class="grid specs"><?php foreach ($specs as $h => $items): ?><div><h3><?= e($h) ?></h3><ul><?php foreach ($items as $i): ?><li><?= e($i) ?></li><?php endforeach; ?></ul></div><?php endforeach; ?></div>
<p class="small" style="margin:28px 0 0">Specifications are indicative and subject to change.</p>
</div></section>

<section class="terrace blush"><div class="wrap">
<figure><img src="/assets/terrace.jpg" alt="Artist's impression of a terrace apartment at sunset" loading="lazy"><figcaption class="small" style="margin-top:8px">Artist's impression.</figcaption></figure>
<div class="col"><p class="eyebrow">Terrace apartments</p><h2 class="h2" style="font-size:clamp(32px,3.8vw,48px)">Your own piece of sky.</h2>
<p style="margin:0;font-size:18px;font-weight:300;line-height:1.65"><?= e($c['terrace_text']) ?></p></div>
</div></section>

<section id="amenities"><div class="wrap">
<div class="split" style="margin-bottom:48px"><div><p class="eyebrow">Amenities</p><h2 class="h2">More to life.<br>Right at home.</h2></div>
<p style="font-size:18px;font-weight:300;line-height:1.6">A 16,400 sq. ft. lifestyle club, rooftop amenities and <?= $c['amenities_count'] !== '' ? e($c['amenities_count']) . ' facilities and amenities' : 'a long list of facilities ' . $todo('CONFIRM amenity count: 45 or 48') ?>, shared by just 127 families.</p></div>
<div class="grid photos">
<figure><img src="/assets/pool.jpg" alt="Swimming pool" loading="lazy"><figcaption>Swimming pool</figcaption></figure>
<figure><img src="/assets/gym.jpg" alt="Fitness studio" loading="lazy"><figcaption>Fitness studio</figcaption></figure>
<figure><img src="/assets/lawn.jpg" alt="Landscaped lawn" loading="lazy"><figcaption>Festival lawn</figcaption></figure>
<figure><img src="/assets/banquet.jpg" alt="Banquet setting" loading="lazy"><figcaption>Banquet</figcaption></figure>
</div>
<div class="grid lists"><?php foreach ($amen as $h => $items): ?><div><h3><?= e($h) ?></h3><ul><?php foreach ($items as $i): ?><li><?= e($i) ?></li><?php endforeach; ?></ul></div><?php endforeach; ?></div>
<p class="small" style="margin:24px 0 0">Images are artist's impressions.</p>
</div></section>

<section id="gallery" class="blush"><div class="wrap">
<p class="eyebrow">Gallery</p>
<h2 class="h2">A look around.</h2>
<div class="grid gallery" style="margin-top:32px">
<?php foreach ($c['gallery'] as $g): ?>
<a href="<?= e(media_url($g['image'])) ?>" class="zoom" data-caption="<?= e($g['caption']) ?> (artist's impression)"><img src="<?= e(media_url($g['thumb'] ?: $g['image'])) ?>" alt="<?= e($g['caption']) ?>, artist's impression" loading="lazy"></a>
<?php endforeach; ?>
</div>
<?php if ($yt): ?>
<div class="video" id="video"><iframe src="https://www.youtube-nocookie.com/embed/<?= e($yt) ?>" title="9 Minnutes walkthrough film" loading="lazy" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>
<?php elseif ($s['show_todo_tags']): ?>
<div class="video empty" id="video"><div><div style="font-size:24px;font-weight:300;margin-bottom:10px">Walkthrough film</div><?= $todo('ADD VIDEO: YouTube link in the admin panel') ?></div></div>
<?php endif; ?>
<p class="small" style="margin:16px 0 0">All images are artist's impressions.</p>
</div></section>

<section id="masterplan"><div class="wrap">
<p class="eyebrow">Master plan</p>
<h2 class="h2">A thoughtfully planned community.</h2>
<p class="lead">One G+11 tower with landscaped spaces to relax, play, celebrate and connect, on Hanspukur Green Park Road.</p>
<a href="<?= e(media_url($c['masterplan'])) ?>" class="zoom" data-caption="Master plan (artist's impression)" style="display:block"><img src="<?= e(media_url($c['masterplan'])) ?>" alt="Master plan of 9 Minnutes showing the tower, landscaped areas, pool and amenities" loading="lazy" style="width:100%;height:auto;display:block;border:1px solid #E4DCD8"></a>
<?php if (!$c['masterplan_final']): ?><p style="margin:12px 0 0;font-size:13px"><?= $todo('REPLACE: cleaned-up master plan from the designer') ?></p><?php endif; ?>
</div></section>

<section id="location" class="blush"><div class="wrap">
<p class="eyebrow">Location</p>
<h2 class="h2">The city's heart is closer than you think.</h2>
<p class="lead" style="margin-bottom:48px"><?= e($c['site_address']) ?>, 150 metres from Diamond Harbour Road.</p>
<div class="loc">
<div class="table"><h3 style="margin:0 0 12px;font-size:15px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:#1C3A50">Connectivity</h3>
<table><thead><tr><th scope="col">Destination</th><th scope="col">km</th></tr></thead><tbody>
<?php foreach ($conn as [$p, $k]): ?><tr><td><?= e($p) ?></td><td><?= $k ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<div class="near-lists">
<?php foreach ($nearby as $h => $rows): ?><div><h3><?= e($h) ?></h3><?php foreach ($rows as [$p, $k]): ?><div class="row"><span><?= e($p) ?></span><span><?= $k ?> km</span></div><?php endforeach; ?></div><?php endforeach; ?>
<img src="/assets/aerial.jpg" alt="Aerial artist's impression of 9 Minnutes among greenery near the Metro" loading="lazy" style="grid-column:1/-1;width:100%;aspect-ratio:21/9;object-fit:cover;display:block">
</div>
</div>
<div class="map"><iframe title="Map showing 9 Minnutes, <?= e($c['site_address']) ?>" src="https://maps.google.com/maps?q=<?= rawurlencode($c['map_query']) ?>&amp;z=15&amp;output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
<p style="margin:10px 0 0;font-size:14px"><a href="https://maps.google.com/?q=<?= rawurlencode($c['map_query']) ?>" target="_blank" rel="noopener">Open in Google Maps</a></p></div>
</div></section>

<section id="team"><div class="wrap">
<p class="eyebrow">The team</p>
<h2 class="h2">The vision behind your address.</h2>
<div class="grid team"><?php foreach ($team as [$n, $r, $q]): ?><figure><blockquote>“<?= e($q) ?>”</blockquote><figcaption><strong><?= e($n) ?></strong><span><?= e($r) ?></span></figcaption></figure><?php endforeach; ?></div>
</div></section>

<section id="faq" class="blush"><div class="wrap" style="max-width:1008px">
<p class="eyebrow">FAQ</p>
<h2 class="h2">Questions buyers ask.</h2>
<div class="faq">
<?php foreach ($c['faq'] as $f): if (trim($f['q']) === '') continue; if (trim($f['a']) === '' && !$s['show_todo_tags']) continue; ?>
<details><summary><?= e($f['q']) ?></summary><p><?= trim($f['a']) !== '' ? nl($f['a']) : $todo('ADD ANSWER in the admin panel') ?></p></details>
<?php endforeach; ?>
</div>
</div></section>

<section id="register" class="register navy"><div class="wrap">
<div class="col">
<h2 class="h2">Be first in line<br>at launch.</h2>
<p style="margin:0 0 28px;font-size:18px;font-weight:300;line-height:1.6;opacity:.92">Register your interest<?= $c['brochure'] ? ' to download the brochure' : '' ?>. Our team will call you before prices are announced, with floor plans and pre-launch details.</p>
<p style="margin:0;font-size:16px;font-weight:300;line-height:1.8">Prefer to talk now?<br><a href="<?= e($telHref) ?>" class="tel"><?= e($c['phone']) ?></a><br><a href="mailto:<?= e($c['email']) ?>" style="color:#fff"><?= e($c['email']) ?></a></p>
</div>
<div class="box">
<?php if ($s['form_paused']): ?>
<div role="status" style="padding:24px 0"><h3 style="margin:0 0 12px;font-size:26px;font-weight:400;color:#1C3A50">Registrations are paused</h3><p style="margin:0;font-size:17px;font-weight:300;line-height:1.6">Please call us on <a href="<?= e($telHref) ?>"><?= e($c['phone']) ?></a> and our team will help you.</p></div>
<?php else: ?>
<form id="lead-form" novalidate>
<label>Full name<input name="name" required maxlength="80" autocomplete="name"></label>
<label>Mobile number<input name="phone" type="tel" required inputmode="tel" maxlength="16" autocomplete="tel" placeholder="10-digit mobile"></label>
<label class="full">Email (optional)<input name="email" type="email" maxlength="120" autocomplete="email"></label>
<label>Home you're looking for<select name="bhk" required><option value="">Select</option>
<?php foreach ($c['homes'] as $h): if (!empty($s['sold_out'][$h['tier']])) continue; ?><option><?= e($h['bhk'] . ' – ' . $h['label']) ?></option><?php endforeach; ?>
<option>Not sure yet</option></select></label>
<label>Best time for a call<select name="slot"><option>Any time</option><option>Morning (10 am – 1 pm)</option><option>Afternoon (1 – 5 pm)</option><option>Evening (5 – 8 pm)</option></select></label>
<div class="hp" aria-hidden="true"><label>Company<input name="company" tabindex="-1" autocomplete="off"></label></div>
<label class="full consent"><input type="checkbox" name="consent" required>I agree to be contacted by Realmark Realty about 9 Minnutes by call, SMS, WhatsApp and email.</label>
<p id="form-error" role="alert"></p>
<button type="submit">Register interest</button>
</form>
<div id="lead-thanks" role="status" hidden style="padding:24px 0">
<h3 style="margin:0 0 12px;font-size:28px;font-weight:400;color:#1C3A50">Thank you<span id="lead-name"></span>.</h3>
<p style="margin:0;font-size:17px;font-weight:300;line-height:1.6">You're on the 9 Minnutes priority list. Our team will call you before the launch.</p>
<?php if ($c['brochure']): ?><p style="margin:18px 0 0"><a href="<?= e(media_url($c['brochure'])) ?>" class="btn btn-navy" download>Download the brochure</a></p><?php endif; ?>
</div>
<?php endif; ?>
</div>
</div></section>

<footer><div class="in">
<div class="cols">
<div><img src="/assets/logo-9minnutes.svg" alt="9 Minnutes" style="height:38px;display:block;margin-bottom:12px"><div style="font-family:'Geologica',sans-serif">Joka's most walkable address</div></div>
<div><strong>Site office</strong><br><?= e($c['site_address']) ?><br><?= e($c['phone']) ?> · <?= e($c['email']) ?></div>
<div><img src="/assets/logo-realmark.svg" alt="Realmark" style="height:30px;display:block;margin-bottom:10px"><strong>A development by Realmark</strong><br><?= e($c['developer_address']) ?><br><?= e($c['developer_phone']) ?> · www.realmarkrealty.in</div>
</div>
<p style="margin:24px 0 8px;font-size:13px">WBRERA No. <?= $c['rera_number'] !== '' ? e($c['rera_number']) : $todo('ADD RERA REGISTRATION NUMBER') ?> · rera.wb.gov.in</p>
<p style="margin:0;font-size:12px;line-height:1.6"><?= $c['disclaimer'] !== '' ? nl($c['disclaimer']) : 'Disclaimer: ' . $todo('ADD LEGAL-APPROVED DISCLAIMER') . ' Images are artist\'s impressions. Specifications, areas and amenities are indicative and subject to change.' ?></p>
</div></footer>

<?php if ($wa !== ''): ?>
<a href="https://wa.me/<?= e($wa) ?>?text=<?= rawurlencode("Hi, I'm interested in 9 Minnutes, Joka.") ?>" class="wa" target="_blank" rel="noopener" aria-label="Chat with us on WhatsApp"><svg width="28" height="28" viewBox="0 0 24 24" aria-hidden="true"><path fill="#fff" d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.2-.4.7-1.4.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.8 11.8 0 0 0 4.5 4c1.7.7 2.3.8 3.2.7a2.7 2.7 0 0 0 1.8-1.3 2.2 2.2 0 0 0 .2-1.3c-.1-.1-.3-.2-.5-.3Z"/></svg></a>
<?php endif; ?>
<dialog id="lightbox" aria-label="Enlarged image"><button type="button" class="lb-close" aria-label="Close">×</button><img alt=""><p class="lb-cap"></p></dialog>
<script src="/app.js?v=<?= @filemtime(PUBLIC_DIR . '/app.js') ?>" defer></script>
</body>
</html>
