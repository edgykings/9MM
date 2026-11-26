<?php
// Shared pieces used by the main page and the legal pages.

function todo_tag(array $s, string $t): string {
    return $s['show_todo_tags'] ? '<span class="todo">[' . e($t) . ']</span>' : '';
}

/** Wraps a render image so it carries the "Artist's impression" label. */
function ai(string $imgHtml): string { return '<span class="aiw">' . $imgHtml . '</span>'; }

function wa_link(array $c): string {
    $wa = preg_replace('/\D/', '', (string)$c['whatsapp']);
    return $wa === '' ? '' : 'https://wa.me/' . $wa . '?text=' . rawurlencode("Hi, I'm interested in 9 Minnutes, Joka.");
}

function wa_icon(int $size = 22, string $fill = '#fff'): string {
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" aria-hidden="true"><path fill="' . $fill . '" d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.2-.4.7-1.4.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.8 11.8 0 0 0 4.5 4c1.7.7 2.3.8 3.2.7a2.7 2.7 0 0 0 1.8-1.3 2.2 2.2 0 0 0 .2-1.3c-.1-.1-.3-.2-.5-.3Z"/></svg>';
}

function social_icon(string $k): string {
    $p = [
        'facebook' => '<path d="M14 8h2.5V4.5H14c-2.2 0-3.5 1.6-3.5 3.8V10H8v3.5h2.5V21H14v-7.5h2.6l.4-3.5h-3V8.6c0-.4.3-.6.6-.6Z" fill="currentColor"/>',
        'instagram' => '<rect x="3.5" y="3.5" width="17" height="17" rx="5" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="17.2" cy="6.8" r="1.2" fill="currentColor"/>',
        'youtube' => '<rect x="2.5" y="5.5" width="19" height="13" rx="4" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M10 9v6l5-3-5-3Z" fill="currentColor"/>',
        'linkedin' => '<rect x="3.5" y="3.5" width="17" height="17" rx="3" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M7.5 10v6.5M7.5 7.3v.2M11 16.5V10m0 3.2c0-1.8 1-3.2 2.7-3.2 1.6 0 2.3 1.1 2.3 2.9v3.6" stroke="currentColor" stroke-width="1.8" fill="none" stroke-linecap="round"/>',
        'x' => '<path d="M4 4l16 16M20 4L4 20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
    ][$k] ?? '';
    return '<svg width="22" height="22" viewBox="0 0 24 24" aria-hidden="true">' . $p . '</svg>';
}

function render_head(array $c, array $s, string $title, string $description): void {
    $t = $s['tracking'];
    $gtm = valid_gtm($t['gtm_id'] ?? ''); $pixel = valid_pixel($t['meta_pixel_id'] ?? ''); $ga4 = valid_ga4($t['ga4_id'] ?? '');
    ?><!doctype html>
<html lang="en-IN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($description) ?>">
<meta property="og:title" content="9 Minnutes, Joka – Joka's most walkable address">
<meta property="og:description" content="2, 3 &amp; 4 BHK residences a 9-minute walk from Joka Metro. Register for launch priority.">
<meta property="og:image" content="/assets/hero.jpg">
<meta property="og:type" content="website">
<link rel="icon" href="/assets/pin-9.png" type="image/png">
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
<?php
}

function render_header(array $c, array $s, string $base = ''): void {
    $wa = wa_link($c);
    if ($s['banner_on'] && trim($c['banner_text']) !== ''): ?>
<a class="banner" href="<?= e($c['banner_link'] ?: '#register') ?>"><?= e($c['banner_text']) ?></a>
<?php endif; ?>
<header class="top"><div class="in">
<a href="<?= e($c['main_site_url'] ?: '/') ?>" class="logo-rm" target="_blank" rel="noopener" aria-label="Realmark Realty (opens the main website)"><img src="/assets/logo-realmark.svg" alt="Realmark"></a>
<nav aria-label="Sections">
<a href="<?= $base ?>#near">NEAR</a><a href="<?= $base ?>#residences">Residences</a><a href="<?= $base ?>#amenities">Amenities</a><a href="<?= $base ?>#gallery">Gallery</a><a href="<?= $base ?>#masterplan">Master plan</a><a href="<?= $base ?>#location">Location</a><a href="<?= $base ?>#faq">FAQ</a>
</nav>
<div class="right">
<a href="<?= $base ?>#register" class="cta">Register interest</a>
<?php if ($wa): ?>
<a href="<?= e($wa) ?>" class="wa-top" target="_blank" rel="noopener" aria-label="Chat with us on WhatsApp"><?= wa_icon() ?></a>
<?php elseif ($s['show_todo_tags']): ?>
<span class="wa-top off" title="WhatsApp number to be added in the admin panel"><?= wa_icon() ?></span>
<?php endif; ?>
<a href="<?= $base ?>#top" class="logo-9" aria-label="9 Minnutes home"><img src="/assets/logo-9minnutes.svg" alt="9 Minnutes"></a>
</div>
</div></header>
<?php
}

function render_footer(array $c, array $s, string $base = ''): void {
    $telHref = 'tel:' . preg_replace('/[^\d+]/', '', $c['phone']);
    $socials = array_filter($c['social'] ?? [], fn($u) => trim((string)$u) !== '');
    $labels = ['facebook' => 'Facebook', 'instagram' => 'Instagram', 'youtube' => 'YouTube', 'linkedin' => 'LinkedIn', 'x' => 'X'];
    ?>
<footer id="contact"><div class="in">
<h2 class="foot-h">Get in touch</h2>
<div class="cols">
<div><strong>Site office</strong><br><?= e($c['site_address']) ?><br>
<a href="<?= e($telHref) ?>"><?= e($c['phone']) ?></a><br><a href="mailto:<?= e($c['email']) ?>"><?= e($c['email']) ?></a></div>
<div><img src="/assets/logo-realmark.svg" alt="Realmark" class="foot-logo"><strong>A development by Realmark</strong><br><?= e($c['developer_address']) ?><br><?= e($c['developer_phone']) ?><br>
<?php if ($c['main_site_url']): ?><a href="<?= e($c['main_site_url']) ?>" target="_blank" rel="noopener">Visit realmarkrealty.in</a><?php endif; ?></div>
<div><strong>More</strong>
<ul class="foot-links">
<li><?php if ($c['blog_url']): ?><a href="<?= e($c['blog_url']) ?>" target="_blank" rel="noopener">Blogs</a><?php else: ?>Blogs <?= todo_tag($s, 'ADD BLOG LINK') ?><?php endif; ?></li>
<li><a href="/disclaimer.php">Disclaimer</a></li>
<li><a href="/privacy-policy.php">Privacy policy</a></li>
<li><a href="<?= e($c['main_site_url'] ?: 'https://www.realmarkrealty.in') ?>" target="_blank" rel="noopener">Realmark main website</a></li>
</ul></div>
<div><strong>Follow Realmark</strong>
<?php if ($socials): ?><div class="social"><?php foreach ($socials as $k => $u): ?><a href="<?= e($u) ?>" target="_blank" rel="noopener" aria-label="Realmark on <?= e($labels[$k] ?? $k) ?>"><?= social_icon($k) ?></a><?php endforeach; ?></div>
<?php else: ?><p style="margin:6px 0 0"><?= todo_tag($s, 'ADD SOCIAL MEDIA LINKS') ?></p><?php endif; ?></div>
</div>
<p class="rera">WBRERA Registration No.
<?php if ($c['rera_number'] !== ''): ?><a href="https://rera.wb.gov.in/" target="_blank" rel="noopener"><?= e($c['rera_number']) ?></a> · available at <a href="https://rera.wb.gov.in/" target="_blank" rel="noopener">rera.wb.gov.in</a>
<?php else: ?><?= todo_tag($s, 'ADD RERA REGISTRATION NUMBER') ?> · <a href="https://rera.wb.gov.in/" target="_blank" rel="noopener">rera.wb.gov.in</a><?php endif; ?></p>
<p class="disc">All images are artist's impressions. Specifications, areas and amenities are indicative and subject to change. <a href="/disclaimer.php">Read the full disclaimer</a>.</p>
</div></footer>
<?php
}
