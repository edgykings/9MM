<?php
/** @var array $c, @var array $s, @var string $legalTitle, @var string $legalText, @var string $legalTodo */
require_once __DIR__ . '/partials.php';
render_head($c, $s, $legalTitle . ' | 9 Minnutes by Realmark', $legalTitle . ' for the 9 Minnutes microsite by Realmark Realty.');
render_header($c, $s, '/');
?>
<main class="legal"><div class="wrap" style="max-width:860px">
<p class="eyebrow">9 Minnutes</p>
<h1 class="h2"><?= e($legalTitle) ?></h1>
<?php if (trim($legalText) === ''): ?>
<p class="lead">This page is being prepared and will be published shortly.</p>
<p><?= todo_tag($s, $legalTodo) ?></p>
<?php else:
    // Plain text: blank line = new paragraph, a line starting with "## " = heading, "- " = list item.
    $blocks = preg_split("/\R{2,}/", trim($legalText));
    foreach ($blocks as $b) {
        $lines = preg_split("/\R/", $b);
        if (str_starts_with($lines[0], '## ')) { echo '<h2>' . e(substr($lines[0], 3)) . '</h2>'; array_shift($lines); if (!$lines) continue; }
        if (str_starts_with($lines[0], '- ')) { echo '<ul>'; foreach ($lines as $l) echo '<li>' . e(ltrim(substr($l, 2))) . '</li>'; echo '</ul>'; continue; }
        echo '<p>' . implode('<br>', array_map('e', $lines)) . '</p>';
    }
endif; ?>
<p style="margin-top:40px"><a href="/">Back to 9 Minnutes</a></p>
</div></main>
<?php render_footer($c, $s, '/'); ?>
<script src="/app.js?v=<?= @filemtime(PUBLIC_DIR . '/app.js') ?>" defer></script>
</body>
</html>
