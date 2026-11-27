<?php
/** @var array $c content, @var array $s settings */
require_once __DIR__ . '/partials.php';
$todo = fn(string $t) => todo_tag($s, $t);
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
$amenIcons = [
  ['Swimming pool', '<path d="M6 34c3 0 3-2 6-2s3 2 6 2 3-2 6-2 3 2 6 2 3-2 6-2 3 2 6 2"/><path d="M6 41c3 0 3-2 6-2s3 2 6 2 3-2 6-2 3 2 6 2 3-2 6-2 3 2 6 2"/><path d="M16 28V12a4 4 0 0 1 8 0M30 28V12a4 4 0 0 1 8 0M16 18h14M16 24h14"/>'],
  ['Fitness studio', '<path d="M8 20v8M13 16v16M35 16v16M40 20v8M13 24h22"/>'],
  ['Banquet hall', '<path d="M18 8h12l-1 10a5 5 0 0 1-10 0L18 8Z"/><path d="M24 23v14M17 40h14"/>'],
  ['Kids’ play area', '<circle cx="15" cy="12" r="4"/><path d="M15 16v10l-5 12M15 26l5 12M8 22h14"/><path d="M30 40V18l10 22M30 18h6"/>'],
  ['Jogging track', '<circle cx="29" cy="9" r="4"/><path d="M14 40l6-10 6 4 2-12-8 2-5 6M28 22l6 6 6-2"/>'],
  ['Yoga lawn', '<circle cx="24" cy="10" r="4"/><path d="M24 14v12M12 24l12 2 12-2M14 38c4-5 7-8 10-8s6 3 10 8"/>'],
  ['Lifestyle club', '<path d="M6 40h36M10 40V20l14-10 14 10v20M20 40V28h8v12"/>'],
  ['CCTV & security', '<path d="M24 6l14 5v10c0 10-6 17-14 21-8-4-14-11-14-21V11l14-5Z"/><path d="M18 24l4 4 8-8"/>'],
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
?><?php render_head($c, $s, '9 Minnutes, Joka – 2, 3 & 4 BHK homes a 9-minute walk from Joka Metro | Realmark', '9 Minnutes by Realmark: 2, 3 & 4 BHK residences on Hanspukur Green Park Road, Joka, a 9-minute walk from Joka Metro. Register your interest.'); ?>
<?php render_header($c, $s); ?>

<section id="top" class="hero<?= $c['hero_video'] ? ' has-video' : '' ?>">
<?php if ($c['hero_video']): ?>
<video class="hero-video" autoplay muted loop playsinline webkit-playsinline disablepictureinpicture preload="auto" poster="<?= e(media_url($c['hero_poster'] ?: 'assets/hero.jpg')) ?>" aria-hidden="true"><?php if ($c['hero_video_mobile']): ?><source src="<?= e(media_url($c['hero_video_mobile'])) ?>" type="video/mp4" media="(max-width: 700px)"><?php endif; ?><source src="<?= e(media_url($c['hero_video'])) ?>" type="video/mp4"></video>
<button type="button" class="video-toggle" aria-pressed="false" aria-label="Pause background video">Pause</button>
<?php endif; ?>
<div class="wrap">
<img src="/assets/logo-9minnutes-light.svg" alt="9 Minnutes" class="hero-logo" width="294" height="72">
<?php if (trim($c['hero_eyebrow']) !== ''): ?><p class="kicker"><?= e($c['hero_eyebrow']) ?></p><?php endif; ?>
<h1><?= nl($c['hero_title']) ?></h1>
<p class="sub"><?= e($c['hero_subtitle']) ?></p>
<div class="ctas"><a href="#register" class="btn btn-gold">Register your interest</a><?php if ($c['brochure']): ?><a href="<?= e(media_url($c['brochure'])) ?>" class="btn btn-line js-brochure">Download brochure</a><?php else: ?><a href="#near" class="btn btn-line">Explore the project</a><?php endif; ?></div>
<?php if (!$c['hero_video'] && $s['show_todo_tags']): ?><p style="margin:24px 0 0"><?= $todo('TEASER VIDEO: upload in admin → Images & files') ?></p><?php endif; ?>
</div>
<span class="ai-label">Artist's impression</span>
</section>

<section aria-label="Key facts" class="facts"><div class="in">
<?php foreach ($c['facts'] as $f): ?><div><div class="v"><?= e($f['value']) ?></div><div class="l"><?= e($f['label']) ?></div></div><?php endforeach; ?>
</div></section>

<section id="near" class="blush"><div class="wrap">
<div class="split"><div><p class="eyebrow">Our project philosophy</p><h2 class="h2">Designed around <strong style="font-weight:600">NEAR.</strong></h2></div>
<p style="font-size:20px;font-weight:300;line-height:1.55">Not simply because the Metro is near. But because everything that matters is. NEAR isn't about distance. It's about removing distance from what matters.</p></div>
<div class="grid near">
<?php foreach ($near as [$l, $h, $tag, $p]): ?>
<div class="card"><?php if (!empty($c['near_images'][$l])): ?><?= ai('<img class="near-img" src="' . e(media_url($c['near_images'][$l])) . '" alt="" loading="lazy">') ?><?php endif; ?><div class="letter"><?= $l ?></div><h3><?= e($h) ?></h3><p class="tag"><?= e($tag) ?></p><p><?= e($p) ?></p></div>
<?php endforeach; ?>
</div></div></section>

<section class="metro"><div class="wrap">
<div class="col">
<h2 class="h2" style="color:#fff">Walk 9 minutes.<br>Let the Metro do the rest.</h2>
<p style="margin:24px 0 36px;font-size:18px;font-weight:300;line-height:1.6;opacity:.9">Joka Metro is 0.7 km from your door, on the Purple Line into the heart of the city.</p>
<div class="times"><?php foreach ($metro as [$p, $m]): ?><div><div style="font-size:16px"><?= e($p) ?></div><div class="m"><?= $m ?> <span style="font-size:15px">mins</span></div></div><?php endforeach; ?></div>
<p style="margin:28px 0 0;font-size:13px;line-height:1.5;opacity:.85"><?= $c['metro_note'] !== '' ? e($c['metro_note']) : 'Indicative travel times. ' . $todo('CONFIRM Metro wording with legal') ?></p>
</div>
<?= ai('<img src="/assets/metro-tower.jpg" alt="The 9 Minnutes tower beside the Joka Metro line">') ?>
</div></section>

<section id="residences"><div class="wrap">
<p class="eyebrow">Residences</p>
<h2 class="h2">A home for every chapter of life.</h2>
<p class="lead" style="margin-bottom:8px">Choose a residence type to see its floor plans.</p>
<p class="lead" style="margin-bottom:24px"><?= $c['three_sides_open'] !== '' ? e($c['three_sides_open']) : $todo('CONFIRM: "Every residence, three sides open"') ?></p>
<div class="grid homes" id="homes">
<?php foreach ($c['homes'] as $h): $sold = !empty($s['sold_out'][$h['tier']]); $n = count(array_filter($c['plans'], fn($p) => $p['tier'] === $h['tier'])); ?>
<button type="button" class="home" data-tier="<?= e($h['tier']) ?>" aria-expanded="false" aria-controls="plans-panel">
<?php if ($sold): ?><span class="soldout">Sold out</span><?php endif; ?>
<span class="tier"><?= e($h['label']) ?></span>
<span class="bhk"><?= e($h['bhk']) ?></span>
<span class="desc"><?= e($h['desc']) ?></span>
<span class="line"><?= e($h['line']) ?></span>
<span class="area">Carpet area<br><strong><?= e($h['carpet']) ?></strong> sq. ft.</span>
<?php if ($s['show_prices'] && trim($h['price']) !== ''): ?><span class="price"><?= e($h['price']) ?></span><?php endif; ?>
<span class="open-plans"><span class="when-closed">View <?= $n ?> floor plan<?= $n === 1 ? '' : 's' ?></span><span class="when-open">Hide floor plans</span> <svg width="14" height="14" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg></span>
</button>
<?php endforeach; ?>
<div class="plans-panel" id="plans-panel" role="region" aria-label="Floor plans" hidden tabindex="-1">
<div class="pp-head"><h3 class="pp-title">Floor plans</h3><button type="button" class="pp-close">Close</button></div>
<div class="grid plans"></div>
<p class="small" style="margin:16px 0 0">Plans are indicative and not to scale. Carpet area as per RERA. Tap a plan to see it in full.</p>
</div>
</div>
<?php foreach ($c['homes'] as $h): ?>
<template id="plans-<?= e($h['tier']) ?>" data-title="<?= e($h['label'] . ' · ' . $h['bhk'] . ' floor plans') ?>">
<?php foreach ($c['plans'] as $p): if ($p['tier'] !== $h['tier']) continue; $alt = "Floor plan, flat {$p['flat']}, {$p['config']}, {$p['floors']}"; ?>
<figure class="plan">
<a href="<?= e(media_url($p['image'])) ?>" class="zoom" data-caption="<?= e($alt) ?>" aria-label="Enlarge <?= e($alt) ?>"><img src="<?= e(media_url($p['thumb'] ?: $p['image'])) ?>" alt="<?= e($alt) ?>" loading="lazy" width="720" height="498"></a>
<figcaption><div class="t">Flat <?= e($p['flat']) ?></div><div class="c"><?= e($planLabel($p)) ?></div><div class="f"><?= e($p['floors']) ?></div>
<div class="a">Carpet area <strong><?= e($p['carpet']) ?> sq. ft.</strong></div><?php if ($p['terrace'] !== ''): ?><div class="f">Open terrace <?= e($p['terrace']) ?> sq. ft.</div><?php endif; ?></figcaption>
</figure>
<?php endforeach; ?>
</template>
<?php endforeach; ?>
<p style="margin:28px 0 0;font-size:15px;font-weight:300">
<?php if ($s['show_prices']): ?><?= e($c['price_note']) ?> Register to get the latest prices first. <?php endif; ?>
<a href="#register">Register now</a></p>
</div></section>

<section id="specifications"><div class="wrap">
<p class="eyebrow">Specifications</p>
<h2 class="h2">Built with care, inside and out.</h2>
<div class="grid specs"><?php foreach ($specs as $h => $items): ?><div><h3><?= e($h) ?></h3><ul><?php foreach ($items as $i): ?><li><?= e($i) ?></li><?php endforeach; ?></ul></div><?php endforeach; ?></div>
<p class="small" style="margin:28px 0 0">Specifications are indicative and subject to change.</p>
</div></section>

<section class="terrace blush"><div class="wrap">
<figure><?= ai('<img src="/assets/terrace.jpg" alt="A terrace apartment at sunset" loading="lazy">') ?></figure>
<div class="col"><p class="eyebrow">Terrace apartments</p><h2 class="h2" style="font-size:clamp(32px,3.8vw,48px)">Your own piece of sky.</h2>
<p style="margin:0;font-size:18px;font-weight:300;line-height:1.65"><?= e($c['terrace_text']) ?></p></div>
</div></section>

<section id="amenities"><div class="wrap">
<div class="split" style="margin-bottom:48px"><div><p class="eyebrow">Amenities</p><h2 class="h2">More to life.<br>Right at home.</h2></div>
<p style="font-size:18px;font-weight:300;line-height:1.6">A 16,400 sq. ft. lifestyle club, rooftop amenities and <?= $c['amenities_count'] !== '' ? e($c['amenities_count']) . ' facilities and amenities' : 'a long list of facilities ' . $todo('CONFIRM amenity count: 45 or 48') ?>, shared by just 127 families.</p></div>
<ul class="amen-icons" aria-label="Signature amenities">
<?php foreach ($amenIcons as [$label, $svg]): ?><li><svg viewBox="0 0 48 48" width="48" height="48" aria-hidden="true" fill="none" stroke="#B48B2A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?= $svg ?></svg><span><?= e($label) ?></span></li><?php endforeach; ?>
</ul>
<?php if ($s['show_todo_tags']): ?><p style="margin:-24px 0 40px"><?= $todo('ICON LIST: final amenities to feature, from Realmark') ?></p><?php endif; ?>
<div class="grid photos">
<figure><?= ai('<img src="/assets/pool.jpg" alt="Swimming pool" loading="lazy">') ?><figcaption>Swimming pool</figcaption></figure>
<figure><?= ai('<img src="/assets/gym.jpg" alt="Fitness studio" loading="lazy">') ?><figcaption>Fitness studio</figcaption></figure>
<figure><?= ai('<img src="/assets/lawn.jpg" alt="Landscaped lawn" loading="lazy">') ?><figcaption>Festival lawn</figcaption></figure>
<figure><?= ai('<img src="/assets/banquet.jpg" alt="Banquet setting" loading="lazy">') ?><figcaption>Banquet</figcaption></figure>
</div>
<div class="grid lists"><?php foreach ($amen as $h => $items): ?><div><h3><?= e($h) ?></h3><ul><?php foreach ($items as $i): ?><li><?= e($i) ?></li><?php endforeach; ?></ul></div><?php endforeach; ?></div>
<p class="small" style="margin:24px 0 0">Images are artist's impressions.</p>
</div></section>

<section id="gallery" class="blush"><div class="wrap">
<p class="eyebrow">Gallery</p>
<h2 class="h2">A look around.</h2>
<div class="carousel" data-auto="3500" style="margin-top:32px">
<div class="car-track gallery">
<?php foreach ($c['gallery'] as $g): ?>
<a href="<?= e(media_url($g['image'])) ?>" class="zoom aiw" data-caption="<?= e($g['caption']) ?> (artist's impression)"><img src="<?= e(media_url($g['thumb'] ?: $g['image'])) ?>" alt="<?= e($g['caption']) ?>, artist's impression" loading="lazy"></a>
<?php endforeach; ?>
</div>
<button type="button" class="car-btn prev" aria-label="Previous images">‹</button><button type="button" class="car-btn next" aria-label="Next images">›</button>
</div>
<?php if ($yt): ?>
<div class="video" id="video"><iframe src="https://www.youtube-nocookie.com/embed/<?= e($yt) ?>" title="9 Minnutes walkthrough film" loading="lazy" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>
<?php elseif ($c['walkthrough_video']): ?>
<div class="video" id="video"><video controls playsinline webkit-playsinline preload="metadata" poster="<?= e(media_url($c['walkthrough_poster'] ?: 'assets/hero.jpg')) ?>" title="9 Minnutes walkthrough film"><source src="<?= e(media_url($c['walkthrough_video'])) ?>" type="video/mp4"></video><span class="ai-label">Artist's impression</span></div>
<?php elseif ($s['show_todo_tags']): ?>
<div class="video empty" id="video"><div><div style="font-size:24px;font-weight:300;margin-bottom:10px">Walkthrough film</div><?= $todo('ADD VIDEO: YouTube link in the admin panel') ?></div></div>
<?php endif; ?>
<p class="small" style="margin:16px 0 0">All images are artist's impressions.</p>
</div></section>

<section id="masterplan"><div class="wrap">
<p class="eyebrow">Master plan</p>
<h2 class="h2">A thoughtfully planned community.</h2>
<p class="lead">One G+11 tower with landscaped spaces to relax, play, celebrate and connect, on Hanspukur Green Park Road.</p>
<?php $mp = $c['mp_map']; if (!empty($mp['on'])): ?>
<?php mp_stage($mp); ?>
<p class="mp-actions"><a href="<?= e(media_url($c['masterplan'])) ?>" class="zoom btn-navy-line" data-caption="Master plan with legend (artist's impression)">View the master plan with legend</a></p>
<?php if (empty($mp['confirmed'])): ?><p style="margin:12px 0 0;font-size:13px"><?= $todo('CONFIRM: plot position on the aerial view (admin → Images & files → Master plan on aerial view)') ?></p><?php endif; ?>
<?php else: ?>
<a href="<?= e(media_url($c['masterplan'])) ?>" class="zoom aiw" data-caption="Master plan (artist's impression)" style="display:block"><img src="<?= e(media_url($c['masterplan'])) ?>" alt="Master plan of 9 Minnutes showing the tower, landscaped areas, pool and amenities" loading="lazy" style="width:100%;height:auto;display:block;border:1px solid #E4DCD8"></a>
<?php endif; ?>
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
<span class="aiw" style="grid-column:1/-1"><img src="/assets/aerial.jpg" alt="Aerial view of 9 Minnutes among greenery near the Metro" loading="lazy" style="width:100%;aspect-ratio:21/9;object-fit:cover;display:block"></span>
</div>
</div>
<?php $mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($c['map_query']); ?>
<div class="map"><a class="map-shot" href="<?= e($mapsUrl) ?>" target="_blank" rel="noopener" aria-label="Open the 9 Minnutes location in Google Maps (opens in a new tab)">
<img src="<?= e(media_url($c['map_image'])) ?>" alt="Google Maps view of the 9 Minnutes location on Hanspukur Green Park Road, Joka, near Joka Metro and IIM Calcutta" loading="lazy" width="1330" height="740">
<span class="map-cta"><svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M12 2a7 7 0 0 0-7 7c0 5.2 7 13 7 13s7-7.8 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/></svg>Open in Google Maps</span></a></div>
</div></section>

<?php if ($c['reviews'] || $s['show_todo_tags']): ?>
<section id="reviews" class="navy"><div class="wrap">
<p class="eyebrow" style="color:#E9C977">Customer reviews</p>
<h2 class="h2">In our homeowners' words.</h2>
<?php $revs = array_values(array_filter($c['reviews'], fn($r) => youtube_id($r['video_url'] ?? ''))); ?>
<?php if ($revs): ?>
<div class="grid reviews">
<?php foreach ($revs as $r): $id = youtube_id($r['video_url']); ?>
<figure class="review"><button type="button" class="yt" data-yt="<?= e($id) ?>" aria-label="Play video review by <?= e($r['name']) ?>"><img src="https://i.ytimg.com/vi/<?= e($id) ?>/hqdefault.jpg" alt="" loading="lazy"><span class="play" aria-hidden="true"></span></button>
<figcaption><strong><?= e($r['name']) ?></strong><?php if (!empty($r['note'])): ?><span><?= e($r['note']) ?></span><?php endif; ?></figcaption></figure>
<?php endforeach; ?>
</div>
<?php else: ?>
<div class="grid reviews"><?php for ($i = 0; $i < 3; $i++): ?><div class="review-empty">Video review</div><?php endfor; ?></div>
<p style="margin:16px 0 0"><?= $todo('ADD REVIEW VIDEOS in admin → Images & files (YouTube links)') ?></p>
<?php endif; ?>
</div></section>
<?php endif; ?>

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

<?php if ($c['legacy'] || $s['show_todo_tags']): ?>
<section id="legacy"><div class="wrap">
<p class="eyebrow">Brand Legacy</p>
<h2 class="h2">Homes Realmark has built.</h2>
<?php if ($c['legacy']): ?>
<div class="carousel" data-auto="3000">
<div class="car-track legacy">
<?php foreach ($c['legacy'] as $p): ?>
<?php $img = '<img src="' . e(media_url($p['thumb'] ?: $p['image'])) . '" alt="' . e($p['name']) . '" loading="lazy">'; ?>
<figure><?= !empty($p['photo']) ? '<span class="aiw-none">' . $img . '</span>' : ai($img) ?><figcaption><strong><?= e($p['name']) ?></strong><?php if (!empty($p['location'])): ?><span><?= e($p['location']) ?></span><?php endif; ?></figcaption></figure>
<?php endforeach; ?>
</div>
<button type="button" class="car-btn prev" aria-label="Previous projects">‹</button><button type="button" class="car-btn next" aria-label="Next projects">›</button>
</div>
<?php else: ?>
<div class="grid legacy"><?php for ($i = 0; $i < 4; $i++): ?><figure><div class="legacy-empty">Project photo</div><figcaption><strong>Project name</strong></figcaption></figure><?php endfor; ?></div>
<p style="margin:16px 0 0"><?= $todo('ADD PREVIOUS PROJECTS (name + photo) in admin → Images & files') ?></p>
<?php endif; ?>
</div></section>
<?php endif; ?>

<section id="register" class="register navy"><div class="wrap">
<div class="col">
<h2 class="h2">Find your home<br>at 9 Minnutes.</h2>
<p style="margin:0 0 28px;font-size:18px;font-weight:300;line-height:1.6;opacity:.92">Register your interest and our team will call you with prices, floor plans and offers.<?php if ($c['brochure']): ?> <a href="<?= e(media_url($c['brochure'])) ?>" class="js-brochure" style="color:#E9C977">Download the brochure</a>.<?php endif; ?></p>
<p style="margin:0;font-size:16px;font-weight:300;line-height:1.8">Prefer to talk now?<br><a href="<?= e($telHref) ?>" class="tel"><?= e($c['phone']) ?></a><br><a href="mailto:<?= e($c['email']) ?>" style="color:#fff"><?= e($c['email']) ?></a></p>
</div>
<div class="box">
<?php if ($s['form_paused']): ?>
<div role="status" style="padding:24px 0"><h3 style="margin:0 0 12px;font-size:26px;font-weight:400;color:#1C3A50">Registrations are paused</h3><p style="margin:0;font-size:17px;font-weight:300;line-height:1.6">Please call us on <a href="<?= e($telHref) ?>"><?= e($c['phone']) ?></a> and our team will help you.</p></div>
<?php else: ?>
<form id="lead-form" novalidate>
<label>Full name<input name="name" required maxlength="80" autocomplete="name"></label>
<label>Mobile number<input name="phone" type="tel" required inputmode="numeric" maxlength="18" autocomplete="tel" placeholder="10-digit mobile"><span class="field-msg" aria-live="polite"></span></label>
<label class="full">Email (optional)<input name="email" type="email" maxlength="120" autocomplete="email" inputmode="email"><span class="field-msg" aria-live="polite"></span></label>
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
<p style="margin:0;font-size:17px;font-weight:300;line-height:1.6">You're on the 9 Minnutes priority list. Our team will call you shortly.</p>
<?php if ($c['brochure']): ?><p style="margin:18px 0 0"><a href="<?= e(media_url($c['brochure'])) ?>" class="btn btn-navy" download>Download the brochure</a></p><?php endif; ?>
</div>
<?php endif; ?>
</div>
</div></section>

<?php render_footer($c, $s); ?>
<?php if ($c['brochure'] && !$s['form_paused']): ?>
<div class="bmodal" id="brochure-modal" hidden data-file="<?= e(media_url($c['brochure'])) ?>">
<div class="bm-box" role="dialog" aria-modal="true" aria-labelledby="bm-title">
<button type="button" class="bm-close" aria-label="Close">×</button>
<img src="/assets/logo-9minnutes.svg" alt="9 Minnutes" class="bm-logo">
<div id="bm-step1">
<h3 id="bm-title">Download the brochure</h3>
<p class="bm-lead">Share your mobile number and the 9 Minnutes brochure downloads right away.</p>
<form id="brochure-form" novalidate>
<label>Mobile number<input name="phone" type="tel" required inputmode="numeric" maxlength="18" autocomplete="tel" placeholder="10-digit mobile number"><span class="field-msg" aria-live="polite"></span></label>
<label>Name (optional)<input name="name" maxlength="80" autocomplete="name"></label>
<div class="hp" aria-hidden="true"><label>Company<input name="company" tabindex="-1" autocomplete="off"></label></div>
<label class="consent"><input type="checkbox" name="consent" required>I agree to be contacted by Realmark Realty about 9 Minnutes by call, SMS, WhatsApp and email.</label>
<p class="bm-error" role="alert"></p>
<button type="submit">Download brochure</button>
</form>
</div>
<div id="bm-step2" hidden role="status">
<h3>Your brochure is downloading</h3>
<p class="bm-lead">Our team may call you to share prices and floor plans.</p>
<p><a href="<?= e(media_url($c['brochure'])) ?>" class="btn btn-navy" download>Didn't start? Download again</a></p>
</div>
</div>
</div>
<?php endif; ?>
<dialog id="lightbox" aria-label="Enlarged image"><button type="button" class="lb-close" aria-label="Close">×</button><img alt=""><p class="lb-cap"></p></dialog>
<script src="/mpmap.js?v=<?= @filemtime(PUBLIC_DIR . '/mpmap.js') ?>" defer></script>
<script src="/app.js?v=<?= @filemtime(PUBLIC_DIR . '/app.js') ?>" defer></script>
</body>
</html>
