<?php
/** @var array $c, @var array $s */
require_once __DIR__ . '/partials.php';
render_head($c, $s, 'Thank you | 9 Minnutes by Realmark', 'Thank you for registering your interest in 9 Minnutes, Joka.', true);
render_header($c, $s, '/');
$wa = wa_link($c);
?>
<main class="thankyou" id="ty"><div class="wrap">
<div class="ty-box">
<img src="/assets/logo-9minnutes.svg" alt="9 Minnutes" class="ty-logo">
<p class="eyebrow">Registration received</p>
<h1 class="h2">Thank you<span id="ty-name"></span>.</h1>
<p class="lead">You're on the 9 Minnutes priority list. Our team will call you shortly to share prices, floor plans and current offers.</p>
<p id="ty-preview" class="preview-note" hidden>Preview only: nothing was sent. On the live site this enquiry is saved and goes straight to Farvision.</p>
<ol class="ty-steps">
<li><strong>A call from our team</strong><span>Usually within one working day, at the time you picked.</span></li>
<li><strong>Prices and floor plans</strong><span>For the home you're looking for.</span></li>
<li><strong>A site visit</strong><span>See Hanspukur Green Park Road and the 9-minute walk to Joka Metro for yourself.</span></li>
</ol>
<div class="ty-actions">
<?php if ($c['brochure']): ?><a href="<?= e(media_url($c['brochure'])) ?>" class="btn btn-navy" download>Download the brochure</a><?php endif; ?>
<?php if ($wa): ?><a href="<?= e($wa) ?>" class="btn btn-wa" target="_blank" rel="noopener"><?= wa_icon() ?> Chat on WhatsApp</a><?php endif; ?>
<?php if ($c['phone']): ?><a href="tel:<?= e(preg_replace('/[^\d+]/', '', $c['phone'])) ?>" class="btn btn-outline">Call <?= e($c['phone']) ?></a><?php endif; ?>
</div>
<p class="ty-back"><a href="/#gallery">Watch the walkthrough film</a> · <a href="/#residences">See the residences</a> · <a href="/">Back to 9 Minnutes</a></p>
</div>
</div></main>
<?php render_footer($c, $s, '/'); ?>
<script src="/app.js?v=<?= @filemtime(PUBLIC_DIR . '/app.js') ?>" defer></script>
</body>
</html>
