<?php
// 9 Minnutes admin panel: leads, content, media, switches, tracking, accounts.
define('PUBLIC_ROOT', dirname(__DIR__));
require dirname(__DIR__) . '/boot.php';
require PRIVATE_DIR . '/lib/auth.php';
require PRIVATE_DIR . '/lib/media.php';
require_once PRIVATE_DIR . '/templates/partials.php';

header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https://i.ytimg.com; media-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
header('Cache-Control: no-store');

admin_session_start();
$page = preg_replace('/[^a-z]/', '', $_GET['p'] ?? 'dashboard');
$isPost = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
$TIERS = ['ESSENTIAL' => 'Essential (2 BHK)', 'SMART' => 'Smart (3 BHK)', 'PREMIUM' => 'Premium (3 BHK)', 'SIGNATURE' => 'Signature (4 BHK)'];

function go(string $p, string $extra = ''): void { header('Location: ?p=' . $p . $extra); exit; }
function str_in(string $k, int $max = 500): string { return mb_substr(trim((string)($_POST[$k] ?? '')), 0, $max); }
function save_content(array $changes): void { json_save('content', array_replace(json_load('content'), $changes)); }
function save_settings(array $changes): void { json_save('settings', array_replace_recursive(json_load('settings'), $changes)); }

/* ---------------- first-run setup ---------------- */
if (!users()) {
    $err = null;
    $token = (string)cfg('setup_token', '');
    if ($isPost) {
        check_csrf();
        $email = strtolower(str_in('email', 120));
        $pw = (string)($_POST['password'] ?? '');
        if (strlen($token) < 16 || !hash_equals($token, (string)($_POST['token'] ?? ''))) {
            note_attempt('login', client_key());
            $err = 'The setup code is wrong.';
        } elseif (too_many('login', client_key(), 5, 900)) $err = 'Too many attempts. Wait 15 minutes.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'Enter a valid email.';
        elseif ($e = password_problem($pw)) $err = $e;
        elseif ($pw !== ($_POST['password2'] ?? '')) $err = 'The two passwords do not match.';
        else {
            save_users([['email' => $email, 'hash' => password_hash($pw, PASSWORD_DEFAULT), 'created' => date('c')]]);
            attempt_login($email, $pw);
            flash('Admin account created. Welcome!');
            go('dashboard');
        }
    }
    layout('Set up the admin panel', function () use ($err, $token) { ?>
        <div class="narrow">
        <h1>Set up the admin panel</h1>
        <?php if (strlen($token) < 16): ?>
          <p class="alert err">Setup is locked. Ask IT to put a <code>setup_token</code> (20+ random characters) in <code>private/config.php</code>, then reload this page.</p>
        <?php else: ?>
          <p>Create the first admin account. You need the setup code from <code>private/config.php</code>.</p>
          <?php if ($err): ?><p class="alert err"><?= e($err) ?></p><?php endif; ?>
          <form method="post" class="stack"><?= csrf_field() ?>
            <label>Setup code<input name="token" type="password" required autocomplete="off"></label>
            <label>Your email<input name="email" type="email" required autocomplete="username" value="<?= e($_POST['email'] ?? '') ?>"></label>
            <label>Password (12+ characters, letters and a number)<input name="password" type="password" required minlength="12" autocomplete="new-password"></label>
            <label>Password again<input name="password2" type="password" required minlength="12" autocomplete="new-password"></label>
            <button class="btn">Create account</button>
          </form>
        <?php endif; ?>
        </div>
    <?php }, false);
    exit;
}

/* ---------------- login / logout ---------------- */
if ($page === 'login') {
    if (current_user()) go('dashboard');
    $err = null;
    if ($isPost) {
        check_csrf();
        $err = attempt_login(str_in('email', 120), (string)($_POST['password'] ?? ''));
        if (!$err) go('dashboard');
    }
    layout('Sign in', function () use ($err) { ?>
        <div class="narrow">
        <h1>9 Minnutes admin</h1>
        <?php if ($err): ?><p class="alert err"><?= e($err) ?></p><?php endif; ?>
        <form method="post" class="stack"><?= csrf_field() ?>
          <label>Email<input name="email" type="email" required autocomplete="username" autofocus value="<?= e($_POST['email'] ?? '') ?>"></label>
          <label>Password<input name="password" type="password" required autocomplete="current-password"></label>
          <button class="btn">Sign in</button>
        </form>
        </div>
    <?php }, false);
    exit;
}
if ($page === 'logout' && $isPost) { check_csrf(); $_SESSION = []; session_destroy(); go('login'); }

$me = require_login();
if ($isPost) check_csrf();

/* ---------------- CSV export ---------------- */
[$where, $args] = lead_filters();
if ($page === 'export') {
    $st = db()->prepare("SELECT * FROM leads $where ORDER BY id DESC");
    $st->execute($args);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="9minnutes-leads-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // so Excel opens Indian names correctly
    $cols = ['id', 'created_at', 'name', 'phone', 'email', 'bhk', 'slot', 'fv_status', 'fv_message', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'fbclid', 'landing_page', 'referrer', 'dump_id'];
    fputcsv($out, $cols);
    while ($r = $st->fetch()) {
        // Stop spreadsheet formulas sneaking in through form fields.
        fputcsv($out, array_map(fn($c) => preg_match('/^[=+\-@\t\r]/', (string)$r[$c]) ? "'" . $r[$c] : $r[$c], $cols));
    }
    exit;
}

function lead_filters(): array {
    $w = []; $a = [];
    $q = trim((string)($_GET['q'] ?? ''));
    if ($q !== '') { $w[] = '(name LIKE ? OR phone LIKE ? OR email LIKE ?)'; array_push($a, "%$q%", "%$q%", "%$q%"); }
    $st = $_GET['status'] ?? '';
    if (in_array($st, ['sent', 'failed', 'pending'], true)) { $w[] = 'fv_status = ?'; $a[] = $st; }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from'] ?? '')) { $w[] = 'created_at >= ?'; $a[] = $_GET['from'] . ' 00:00:00'; }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to'] ?? '')) { $w[] = 'created_at <= ?'; $a[] = $_GET['to'] . ' 23:59:59'; }
    if (!empty($_GET['bhk'])) { $w[] = 'bhk = ?'; $a[] = (string)$_GET['bhk']; }
    return [$w ? 'WHERE ' . implode(' AND ', $w) : '', $a];
}

/* ---------------- actions ---------------- */
if ($isPost) {
    $action = $_POST['action'] ?? '';
    try {
        switch ($action) {
            case 'retry':
                [$ok, $msg] = fv_sync_row((int)$_POST['id']);
                flash($ok ? 'Sent to Farvision.' : "Farvision still refused it: $msg", $ok ? 'ok' : 'err');
                go('leads', '&' . http_build_query(array_intersect_key($_GET, array_flip(['q', 'status', 'from', 'to', 'bhk', 'n']))));
            case 'retry_all':
                $ids = db()->query("SELECT id FROM leads WHERE fv_status != 'sent' ORDER BY id LIMIT 50")->fetchAll(PDO::FETCH_COLUMN);
                $sent = 0; foreach ($ids as $id) { if (fv_sync_row((int)$id)[0]) $sent++; }
                flash("Retried " . count($ids) . " leads: $sent sent to Farvision.", $sent === count($ids) ? 'ok' : 'err');
                go('leads');

            case 'content':
                $c = content();
                $new = [];
                foreach (['hero_eyebrow' => 120, 'hero_title' => 120, 'hero_subtitle' => 300, 'metro_note' => 300, 'three_sides_open' => 200,
                          'price_note' => 300, 'terrace_text' => 800, 'amenities_count' => 10, 'phone' => 30, 'email' => 120, 'whatsapp' => 20,
                          'site_address' => 200, 'map_query' => 200, 'developer_address' => 250, 'developer_phone' => 30,
                          'rera_number' => 80, 'disclaimer' => 8000, 'banner_text' => 160, 'banner_link' => 300,
                          'blog_url' => 300, 'main_site_url' => 300, 'privacy_text' => 30000] as $k => $max) {
                    $new[$k] = str_in($k, $max);
                }
                // Save everything that is valid; skip (and report) only the fields that are not.
                $warn = [];
                if ($new['email'] !== '' && !filter_var($new['email'], FILTER_VALIDATE_EMAIL)) { unset($new['email']); $warn[] = 'the email address is not valid'; }
                $new['whatsapp'] = preg_replace('/\D/', '', $new['whatsapp']);
                if ($new['whatsapp'] !== '' && !preg_match('/^91[6-9]\d{9}$/', $new['whatsapp'])) { unset($new['whatsapp']); $warn[] = 'WhatsApp number must be 91 followed by the 10-digit mobile, e.g. 919836780001'; }
                if ($new['banner_link'] !== '' && !preg_match('~^(#[\w-]+|https://\S+)$~', $new['banner_link'])) { unset($new['banner_link']); $warn[] = 'the banner link must start with # or https://'; }
                foreach (['blog_url' => 'Blog link', 'main_site_url' => 'Main website link'] as $k => $label) {
                    if ($new[$k] !== '' && !preg_match('~^https://\S+$~', $new[$k])) { unset($new[$k]); $warn[] = "$label must start with https://"; }
                }
                $social = [];
                foreach (['facebook', 'instagram', 'youtube', 'linkedin', 'x'] as $k) {
                    $u = mb_substr(trim((string)($_POST['social'][$k] ?? '')), 0, 300);
                    if ($u !== '' && !preg_match('~^https://\S+$~', $u)) { $warn[] = ucfirst($k) . ' link must start with https://'; $u = content()['social'][$k] ?? ''; }
                    $social[$k] = $u;
                }
                $new['social'] = $social;
                $new['facts'] = [];
                foreach ($c['facts'] as $i => $f) $new['facts'][] = ['value' => mb_substr(trim($_POST['facts'][$i]['value'] ?? ''), 0, 20), 'label' => mb_substr(trim($_POST['facts'][$i]['label'] ?? ''), 0, 80)];
                $new['homes'] = [];
                foreach ($c['homes'] as $i => $h) {
                    $in = $_POST['homes'][$i] ?? [];
                    $h['desc'] = mb_substr(trim($in['desc'] ?? ''), 0, 200);
                    $h['line'] = mb_substr(trim($in['line'] ?? ''), 0, 120);
                    $h['carpet'] = mb_substr(trim($in['carpet'] ?? ''), 0, 40);
                    $h['price'] = mb_substr(trim($in['price'] ?? ''), 0, 80);
                    $new['homes'][] = $h;
                }
                $new['faq'] = [];
                foreach (($_POST['faq'] ?? []) as $f) {
                    if (!empty($f['delete'])) continue;
                    $q = mb_substr(trim($f['q'] ?? ''), 0, 200); $a = mb_substr(trim($f['a'] ?? ''), 0, 1500);
                    if ($q !== '') $new['faq'][] = ['q' => $q, 'a' => $a];
                }
                save_content($new);
                flash($warn ? 'Saved, except: ' . implode('; ', $warn) . '. Fix that and save again.' : 'Content saved. It is live on the site now.', $warn ? 'err' : 'ok');
                go('content');

            case 'gallery_save':
                $items = [];
                foreach (content()['gallery'] as $i => $g) {
                    $in = $_POST['g'][$i] ?? [];
                    if (!empty($in['delete'])) { delete_upload($g['image']); delete_upload($g['thumb']); continue; }
                    $g['caption'] = mb_substr(trim($in['caption'] ?? $g['caption']), 0, 120);
                    $g['order'] = (int)($in['order'] ?? $i);
                    $items[] = $g;
                }
                usort($items, fn($a, $b) => $a['order'] <=> $b['order']);
                save_content(['gallery' => array_map(fn($g) => array_diff_key($g, ['order' => 1]), $items)]);
                flash('Gallery saved.'); go('media', '#gallery');
            case 'gallery_add':
                $img = save_image_upload($_FILES['file'] ?? [], 1800, 640);
                $g = content()['gallery'];
                $g[] = $img + ['caption' => str_in('caption', 120) ?: 'Artist\'s impression'];
                save_content(['gallery' => $g]);
                flash('Image added to the gallery.'); go('media', '#gallery');

            case 'plan_save':
                $plans = content()['plans'];
                $id = (string)($_POST['id'] ?? '');
                foreach ($plans as &$p) {
                    if ($p['id'] !== $id) continue;
                    $p['floors'] = str_in('floors', 80);
                    $p['carpet'] = str_in('carpet', 20);
                    $p['terrace'] = str_in('terrace', 20);
                    if (!empty($_FILES['file']['name'])) {
                        $img = save_image_upload($_FILES['file'], 2000, 720);
                        delete_upload($p['image']); delete_upload($p['thumb']);
                        $p = array_replace($p, $img);
                    }
                }
                unset($p);
                save_content(['plans' => $plans]);
                flash("Floor plan $id saved."); go('media', "#plan-$id");

            case 'masterplan':
                $changes = ['masterplan_final' => !empty($_POST['final'])];
                if (!empty($_FILES['file']['name'])) {
                    $img = save_image_upload($_FILES['file'], 2600, 720);
                    delete_upload(content()['masterplan']); delete_upload($img['thumb']);
                    $changes['masterplan'] = $img['image'];
                }
                save_content($changes);
                flash('Master plan saved.'); go('media', '#masterplan');

            case 'map_image':
                $img = save_image_upload($_FILES['file'] ?? [], 1600, 400);
                delete_upload($img['thumb']); delete_upload(content()['map_image']);
                save_content(['map_image' => $img['image']]);
                flash('Map screenshot replaced.'); go('media', '#map-image');

            case 'mp_map':
                $m = content()['mp_map'];
                $lim = ['x' => [0, 100], 'y' => [0, 100], 'w' => [2, 100], 'r' => [-180, 180], 'z' => [1, 4]];
                foreach ($lim as $k => [$lo, $hi]) {
                    $v = filter_var($_POST[$k] ?? null, FILTER_VALIDATE_FLOAT);
                    if ($v !== false && $v !== null) $m[$k] = round(max($lo, min($hi, $v)), 2);
                }
                $m['on'] = !empty($_POST['on']);
                $m['confirmed'] = !empty($_POST['confirmed']);
                $m['credit'] = mb_substr(trim((string)($_POST['credit'] ?? $m['credit'])), 0, 160);
                save_content(['mp_map' => $m]);
                flash('Master plan position saved. It is live on the site now.'); go('media', '#mp-map');
            case 'mp_aerial':
                $m = content()['mp_map'];
                $img = save_image_upload($_FILES['file'] ?? [], 2600, 400);
                delete_upload($img['thumb']); delete_upload($m['aerial']);
                $m['aerial'] = $img['image']; $m['confirmed'] = false;
                save_content(['mp_map' => $m]);
                flash('Aerial image replaced. Drag the master plan into place again and save.'); go('media', '#mp-map');
            case 'mp_plan':
                $m = content()['mp_map'];
                $png = save_png_upload($_FILES['file'] ?? []);
                delete_upload($m['plan']); $m['plan'] = $png;
                save_content(['mp_map' => $m]);
                flash('Master plan cut-out replaced.'); go('media', '#mp-map');

            case 'brochure':
                if (!empty($_POST['remove'])) { delete_upload(content()['brochure']); save_content(['brochure' => '']); flash('Brochure removed.'); go('media', '#brochure'); }
                $pdf = save_pdf_upload($_FILES['file'] ?? []);
                delete_upload(content()['brochure']);
                save_content(['brochure' => $pdf]);
                flash('Brochure uploaded. Visitors can download it after registering.'); go('media', '#brochure');

            case 'hero_video':
                if (!empty($_POST['remove'])) { delete_upload(content()['hero_video']); save_content(['hero_video' => '', 'hero_video_mobile' => '', 'hero_poster' => '']); flash('Teaser video removed. The hero image shows instead.'); go('media', '#hero-video'); }
                $mp4 = save_video_upload($_FILES['file'] ?? []);
                delete_upload(content()['hero_video']);
                save_content(['hero_video' => $mp4, 'hero_video_mobile' => '', 'hero_poster' => '']);
                flash('Teaser video uploaded. It now plays at the top of the site.'); go('media', '#hero-video');

            case 'near_image':
                $l = (string)($_POST['letter'] ?? '');
                if (!in_array($l, ['N', 'E', 'A', 'R'], true)) throw new RuntimeException('Unknown letter.');
                $img = save_image_upload($_FILES['file'] ?? [], 1200, 800);
                $near = content()['near_images'];
                delete_upload($near[$l] ?? null); delete_upload($img['image']);
                $near[$l] = $img['thumb'];
                save_content(['near_images' => $near]);
                flash("Image for $l updated."); go('media', '#near-images');

            case 'review_add':
                $url = str_in('video_url', 300);
                if (!youtube_id($url)) throw new RuntimeException('Paste a YouTube link for the review video.');
                $r = content()['reviews'];
                $r[] = ['video_url' => $url, 'name' => str_in('name', 80) ?: 'Realmark homeowner', 'note' => str_in('note', 120)];
                save_content(['reviews' => $r]);
                flash('Review added.'); go('media', '#reviews');
            case 'reviews_save':
                $out = [];
                foreach (content()['reviews'] as $i => $r) {
                    $in = $_POST['r'][$i] ?? [];
                    if (!empty($in['delete'])) continue;
                    $r['name'] = mb_substr(trim($in['name'] ?? $r['name']), 0, 80);
                    $r['note'] = mb_substr(trim($in['note'] ?? $r['note']), 0, 120);
                    $r['order'] = (int)($in['order'] ?? $i);
                    $out[] = $r;
                }
                usort($out, fn($a, $b) => $a['order'] <=> $b['order']);
                save_content(['reviews' => array_map(fn($x) => array_diff_key($x, ['order' => 1]), $out)]);
                flash('Reviews saved.'); go('media', '#reviews');

            case 'legacy_add':
                $name = str_in('name', 80);
                if ($name === '') throw new RuntimeException('Enter the project name.');
                $img = save_image_upload($_FILES['file'] ?? [], 1600, 640);
                $l = content()['legacy'];
                $l[] = ['name' => $name, 'location' => str_in('location', 80)] + $img;
                save_content(['legacy' => $l]);
                flash("$name added to Brand Legacy."); go('media', '#legacy');
            case 'legacy_save':
                $out = [];
                foreach (content()['legacy'] as $i => $p) {
                    $in = $_POST['l'][$i] ?? [];
                    if (!empty($in['delete'])) { delete_upload($p['image']); delete_upload($p['thumb']); continue; }
                    $p['name'] = mb_substr(trim($in['name'] ?? $p['name']), 0, 80);
                    $p['location'] = mb_substr(trim($in['location'] ?? ($p['location'] ?? '')), 0, 80);
                    $p['order'] = (int)($in['order'] ?? $i);
                    $out[] = $p;
                }
                usort($out, fn($a, $b) => $a['order'] <=> $b['order']);
                save_content(['legacy' => array_map(fn($x) => array_diff_key($x, ['order' => 1]), $out)]);
                flash('Brand Legacy saved.'); go('media', '#legacy');

            case 'video':
                $url = str_in('video_url', 300);
                if ($url !== '' && !youtube_id($url)) throw new RuntimeException('Paste a YouTube link (youtube.com/watch?v=… or youtu.be/…).');
                save_content(['video_url' => $url]);
                flash($url ? 'Video saved.' : 'Video removed.'); go('media', '#video');

            case 'switches':
                $sold = [];
                foreach (array_keys($GLOBALS['TIERS']) as $t) $sold[$t] = !empty($_POST['sold_out'][$t]);
                save_settings(['show_prices' => !empty($_POST['show_prices']), 'banner_on' => !empty($_POST['banner_on']),
                               'form_paused' => !empty($_POST['form_paused']), 'show_todo_tags' => !empty($_POST['show_todo_tags']), 'sold_out' => $sold]);
                flash('Switches saved. The site has been updated.'); go('switches');

            case 'tracking':
                $gtm = strtoupper(str_in('gtm_id', 20)); $px = str_in('meta_pixel_id', 25); $ga = strtoupper(str_in('ga4_id', 20));
                if ($gtm !== '' && !valid_gtm($gtm)) throw new RuntimeException('Google Tag Manager ID looks like GTM-XXXXXXX.');
                if ($px !== '' && !valid_pixel($px)) throw new RuntimeException('Meta Pixel ID is a 15–16 digit number.');
                if ($ga !== '' && !valid_ga4($ga)) throw new RuntimeException('GA4 Measurement ID looks like G-XXXXXXXXXX.');
                save_settings(['tracking' => ['gtm_id' => $gtm, 'meta_pixel_id' => $px, 'ga4_id' => $ga]]);
                flash('Tracking saved. The tags load on the site from now on.'); go('tracking');

            case 'password':
                if (!password_verify((string)($_POST['current'] ?? ''), $me['hash'])) throw new RuntimeException('Your current password is wrong.');
                $pw = (string)($_POST['new'] ?? '');
                if ($e = password_problem($pw)) throw new RuntimeException($e);
                if ($pw !== ($_POST['new2'] ?? '')) throw new RuntimeException('The two new passwords do not match.');
                $u = users(); foreach ($u as &$x) if ($x['email'] === $me['email']) $x['hash'] = password_hash($pw, PASSWORD_DEFAULT); unset($x);
                save_users($u); session_regenerate_id(true);
                flash('Password changed.'); go('account');
            case 'add_user':
                $email = strtolower(str_in('email', 120)); $pw = (string)($_POST['password'] ?? '');
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Enter a valid email.');
                if ($e = password_problem($pw)) throw new RuntimeException($e);
                $u = users(); foreach ($u as $x) if ($x['email'] === $email) throw new RuntimeException('That email already has an account.');
                $u[] = ['email' => $email, 'hash' => password_hash($pw, PASSWORD_DEFAULT), 'created' => date('c'), 'added_by' => $me['email']];
                save_users($u); flash("Admin $email added. Share the password with them privately; they can change it under Account."); go('account');
            case 'remove_user':
                $email = (string)($_POST['email'] ?? '');
                if ($email === $me['email']) throw new RuntimeException('You cannot remove your own account.');
                save_users(array_filter(users(), fn($x) => $x['email'] !== $email));
                flash("Removed $email."); go('account');
        }
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'err');
        go($page);
    }
}

/* ---------------- pages ---------------- */
$c = content(); $s = settings();

$todos = [];
if ($c['rera_number'] === '') $todos[] = ['RERA registration number', 'content', '#legal'];
if ($c['disclaimer'] === '') $todos[] = ['Legal disclaimer', 'content', '#legal'];
if ($c['amenities_count'] === '') $todos[] = ['Amenity count (45 or 48)', 'content', '#residences'];
if ($c['three_sides_open'] === '') $todos[] = ['"Three sides open" line (or leave blank to hide)', 'content', '#residences'];
if ($c['metro_note'] === '') $todos[] = ['Metro travel-time wording', 'content', '#hero'];
foreach ($c['faq'] as $f) if (trim($f['a']) === '') $todos[] = ['FAQ answer: ' . $f['q'], 'content', '#faq'];
if ($c['whatsapp'] === '') $todos[] = ['WhatsApp number (button hidden until set)', 'content', '#contact'];
if (!$c['masterplan_final']) $todos[] = ['Final master plan', 'media', '#masterplan'];
if (!empty($c['mp_map']['on']) && empty($c['mp_map']['confirmed'])) $todos[] = ['Check the master plan position on the aerial view', 'media', '#mp-map'];
if ($c['brochure'] === '') $todos[] = ['Brochure PDF', 'media', '#brochure'];
if ($c['video_url'] === '') $todos[] = ['Walkthrough video', 'media', '#video'];
if ($c['hero_video'] === '') $todos[] = ['Teaser video for the top of the page', 'media', '#hero-video'];
if (!$c['reviews']) $todos[] = ['Customer review videos', 'media', '#reviews'];
if (!$c['legacy']) $todos[] = ['Brand Legacy: previous projects', 'media', '#legacy'];
if (!array_filter($c['social'])) $todos[] = ['Social media links', 'content', '#links'];
if ($c['blog_url'] === '') $todos[] = ['Blog link', 'content', '#links'];
if ($c['privacy_text'] === '') $todos[] = ['Privacy policy (from Legal)', 'content', '#legal'];
if (!valid_gtm($s['tracking']['gtm_id']) && !valid_pixel($s['tracking']['meta_pixel_id'])) $todos[] = ['Ad tracking IDs', 'tracking', ''];

switch ($page) {
case 'leads':
    $per = 50; $n = max(1, (int)($_GET['n'] ?? 1));
    $st = db()->prepare("SELECT COUNT(*) FROM leads $where"); $st->execute($args); $total = (int)$st->fetchColumn();
    $st = db()->prepare("SELECT * FROM leads $where ORDER BY id DESC LIMIT $per OFFSET " . (($n - 1) * $per)); $st->execute($args);
    $rows = $st->fetchAll();
    $failed = (int)db()->query("SELECT COUNT(*) FROM leads WHERE fv_status != 'sent'")->fetchColumn();
    $qs = http_build_query(array_intersect_key($_GET, array_flip(['q', 'status', 'from', 'to', 'bhk'])));
    layout('Leads', function () use ($rows, $total, $n, $per, $failed, $qs, $c) { ?>
        <div class="head"><h1>Leads <span class="muted">(<?= $total ?>)</span></h1>
          <a class="btn ghost" href="?p=export&amp;<?= e($qs) ?>">Download CSV</a></div>
        <?php if ($failed): ?>
          <form method="post" class="alert err row"><?= csrf_field() ?><input type="hidden" name="action" value="retry_all">
            <span><?= $failed ?> lead<?= $failed > 1 ? 's have' : ' has' ?> not reached Farvision yet. They are safe here.</span><button class="btn small">Retry sending</button></form>
        <?php endif; ?>
        <form method="get" class="filters"><input type="hidden" name="p" value="leads">
          <label>Search<input name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Name, mobile or email"></label>
          <label>Farvision<select name="status"><option value="">All</option><?php foreach (['sent' => 'Sent', 'failed' => 'Failed', 'pending' => 'Pending'] as $k => $v): ?><option value="<?= $k ?>" <?= ($_GET['status'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></label>
          <label>Home<select name="bhk"><option value="">All</option><?php foreach ($c['homes'] as $h): $o = $h['bhk'] . ' – ' . $h['label']; ?><option <?= ($_GET['bhk'] ?? '') === $o ? 'selected' : '' ?>><?= e($o) ?></option><?php endforeach; ?><option <?= ($_GET['bhk'] ?? '') === 'Not sure yet' ? 'selected' : '' ?>>Not sure yet</option></select></label>
          <label>From<input type="date" name="from" value="<?= e($_GET['from'] ?? '') ?>"></label>
          <label>To<input type="date" name="to" value="<?= e($_GET['to'] ?? '') ?>"></label>
          <button class="btn small">Filter</button> <a href="?p=leads" class="small-link">Clear</a>
        </form>
        <div class="scroll"><table class="data">
          <thead><tr><th>#</th><th>Received</th><th>Name</th><th>Mobile</th><th>Email</th><th>Looking for</th><th>Call time</th><th>Source</th><th>Farvision</th></tr></thead>
          <tbody>
          <?php if (!$rows): ?><tr><td colspan="9" class="muted">No leads yet<?= $qs ? ' for these filters' : '' ?>.</td></tr><?php endif; ?>
          <?php foreach ($rows as $r): $src = $r['utm_source'] ?: ($r['gclid'] ? 'google (gclid)' : ($r['fbclid'] ? 'facebook (fbclid)' : 'direct')); ?>
            <tr>
              <td><?= (int)$r['id'] ?></td>
              <td class="nowrap"><?= e(date('d M Y, g:i a', strtotime($r['created_at']))) ?></td>
              <td><?= e($r['name']) ?></td>
              <td class="nowrap"><a href="tel:+91<?= e($r['phone']) ?>">+91 <?= e($r['phone']) ?></a></td>
              <td><?= e($r['email']) ?></td>
              <td><?= e($r['bhk']) ?></td>
              <td><?= e($r['slot']) ?></td>
              <td title="<?= e(trim($r['utm_campaign'] . ' ' . $r['landing_page'])) ?>"><?= e($src) ?><?= $r['utm_campaign'] ? '<br><span class="muted">' . e($r['utm_campaign']) . '</span>' : '' ?></td>
              <td>
                <?php if ($r['fv_status'] === 'sent'): ?><span class="pill ok">Sent</span>
                <?php else: ?><span class="pill err" title="<?= e($r['fv_message']) ?>"><?= $r['fv_status'] === 'failed' ? 'Failed' : 'Pending' ?></span>
                  <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="retry"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="link">Retry</button></form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody></table></div>
        <?php $pages = (int)ceil($total / $per); if ($pages > 1): ?>
          <p class="pager"><?php for ($i = 1; $i <= $pages; $i++): ?><a href="?p=leads&amp;n=<?= $i ?>&amp;<?= e($qs) ?>" <?= $i === $n ? 'aria-current="page"' : '' ?>><?= $i ?></a> <?php endfor; ?></p>
        <?php endif; ?>
    <?php });
    break;

case 'content':
    layout('Content', function () use ($c) { ?>
        <h1>Content</h1>
        <p class="muted">Changes go live on the site as soon as you save. Leave a field blank to hide that line (or show a yellow reminder while reminders are on).</p>
        <form method="post" class="cards" id="content-form"><?= csrf_field() ?><input type="hidden" name="action" value="content">
          <fieldset id="hero"><legend>Top of the page</legend>
            <label>Small line above the title<input name="hero_eyebrow" value="<?= e($c['hero_eyebrow']) ?>" maxlength="120"></label>
            <label>Big title (each line on its own row)<textarea name="hero_title" rows="3" maxlength="120"><?= e($c['hero_title']) ?></textarea></label>
            <label>Line under the title<textarea name="hero_subtitle" rows="2" maxlength="300"><?= e($c['hero_subtitle']) ?></textarea></label>
            <div class="grid4"><?php foreach ($c['facts'] as $i => $f): ?><div class="mini"><label>Key fact <?= $i + 1 ?><input name="facts[<?= $i ?>][value]" value="<?= e($f['value']) ?>" maxlength="20"></label><label>Label<input name="facts[<?= $i ?>][label]" value="<?= e($f['label']) ?>" maxlength="80"></label></div><?php endforeach; ?></div>
            <label>Note under the Metro times<input name="metro_note" value="<?= e($c['metro_note']) ?>" maxlength="300" placeholder="e.g. Projected travel times on completion of the Joka–Esplanade Metro."></label>
          </fieldset>
          <fieldset id="residences"><legend>Residences and prices</legend>
            <label>Line under "A home for every chapter of life"<input name="three_sides_open" value="<?= e($c['three_sides_open']) ?>" maxlength="200" placeholder="e.g. Every residence, three sides open."></label>
            <div class="grid4"><?php foreach ($c['homes'] as $i => $h): ?><div class="mini"><strong><?= e($h['label'] . ' · ' . $h['bhk']) ?></strong>
              <label>Description<input name="homes[<?= $i ?>][desc]" value="<?= e($h['desc']) ?>" maxlength="200"></label>
              <label>Tagline<input name="homes[<?= $i ?>][line]" value="<?= e($h['line']) ?>" maxlength="120"></label>
              <label>Carpet area (sq. ft.)<input name="homes[<?= $i ?>][carpet]" value="<?= e($h['carpet']) ?>" maxlength="40"></label>
              <label>Price (shown only when "Show prices" is on)<input name="homes[<?= $i ?>][price]" value="<?= e($h['price']) ?>" maxlength="80" placeholder="e.g. From ₹52.4 lakh"></label></div><?php endforeach; ?></div>
            <label>Note under prices<input name="price_note" value="<?= e($c['price_note']) ?>" maxlength="300"></label>
            <label>Number of facilities and amenities<input name="amenities_count" value="<?= e($c['amenities_count']) ?>" maxlength="10" placeholder="45 or 48"></label>
            <label>Terrace apartments text<textarea name="terrace_text" rows="3" maxlength="800"><?= e($c['terrace_text']) ?></textarea></label>
          </fieldset>
          <fieldset id="faq"><legend>FAQ</legend>
            <div id="faq-rows"><?php foreach ($c['faq'] as $i => $f): ?>
              <div class="faq-row"><label>Question<input name="faq[<?= $i ?>][q]" value="<?= e($f['q']) ?>" maxlength="200"></label><label>Answer<textarea name="faq[<?= $i ?>][a]" rows="2" maxlength="1500"><?= e($f['a']) ?></textarea></label><label class="check"><input type="checkbox" name="faq[<?= $i ?>][delete]" value="1"> Delete this question</label></div>
            <?php endforeach; ?></div>
            <button type="button" class="btn ghost small" id="faq-add" data-next="<?= count($c['faq']) ?>">Add a question</button>
          </fieldset>
          <fieldset id="contact"><legend>Contact and address</legend>
            <div class="grid2">
              <label>Phone<input name="phone" value="<?= e($c['phone']) ?>" maxlength="30"></label>
              <label>Email<input name="email" type="email" value="<?= e($c['email']) ?>" maxlength="120"></label>
              <label>WhatsApp number (91 + mobile; blank hides the button)<input name="whatsapp" value="<?= e($c['whatsapp']) ?>" maxlength="20" placeholder="919836780001"></label>
              <label>Site address<input name="site_address" value="<?= e($c['site_address']) ?>" maxlength="200"></label>
              <label>Map search (address or "lat,long" of the site)<input name="map_query" value="<?= e($c['map_query']) ?>" maxlength="200"></label>
              <label>Developer phone<input name="developer_phone" value="<?= e($c['developer_phone']) ?>" maxlength="30"></label>
            </div>
            <label>Developer address<input name="developer_address" value="<?= e($c['developer_address']) ?>" maxlength="250"></label>
          </fieldset>
          <fieldset id="links"><legend>Links and social media</legend>
            <div class="grid2">
              <label>Blog link<input name="blog_url" value="<?= e($c['blog_url']) ?>" maxlength="300" placeholder="https://www.realmarkrealty.in/blog"></label>
              <label>Realmark main website<input name="main_site_url" value="<?= e($c['main_site_url']) ?>" maxlength="300"></label>
              <?php foreach (['facebook' => 'Facebook', 'instagram' => 'Instagram', 'youtube' => 'YouTube', 'linkedin' => 'LinkedIn', 'x' => 'X (Twitter)'] as $k => $label): ?>
              <label><?= $label ?> page<input name="social[<?= $k ?>]" value="<?= e($c['social'][$k] ?? '') ?>" maxlength="300" placeholder="https://…"></label>
              <?php endforeach; ?>
            </div>
            <p class="muted">Leave a social link blank to hide its icon.</p>
          </fieldset>
          <fieldset id="legal"><legend>Legal</legend>
            <label>WBRERA registration number (links to rera.wb.gov.in)<input name="rera_number" value="<?= e($c['rera_number']) ?>" maxlength="80" placeholder="WBRERA/P/…"></label>
            <label>Disclaimer (shown on the Disclaimer page)<textarea name="disclaimer" rows="6" maxlength="8000"><?= e($c['disclaimer']) ?></textarea></label>
            <label>Privacy policy (shown on the Privacy policy page)<textarea name="privacy_text" rows="12" maxlength="30000"><?= e($c['privacy_text']) ?></textarea></label>
            <p class="muted">Formatting for both: leave a blank line between paragraphs, start a line with <code>## </code> for a heading and <code>- </code> for a bullet point.</p>
          </fieldset>
          <fieldset id="banner"><legend>Announcement banner (turn on under Switches)</legend>
            <div class="grid2"><label>Banner text<input name="banner_text" value="<?= e($c['banner_text']) ?>" maxlength="160"></label>
            <label>Banner link (#register or https://…)<input name="banner_link" value="<?= e($c['banner_link']) ?>" maxlength="300"></label></div>
          </fieldset>
          <div class="savebar"><button class="btn">Save content</button></div>
        </form>
    <?php });
    break;

case 'media':
    layout('Images & files', function () use ($c, $TIERS) { ?>
        <h1>Images &amp; files</h1>
        <p class="muted">Images: JPG, PNG or WebP up to 12 MB (resized and saved as JPG). Teaser video: MP4 up to 80 MB. Brochure: PDF up to 40 MB. Every image on the site is labelled "Artist's impression" automatically.</p>

        <section class="card" id="hero-video"><h2>Teaser video (top of the page)</h2>
          <?php if ($c['hero_video']): ?><video src="<?= e(media_url($c['hero_video'])) ?>" class="preview" muted controls preload="metadata"></video><?php else: ?><p class="muted">No video yet: the hero image shows instead. Use an MP4, ideally 10–20 MB and 15–30 seconds; it plays muted on a loop.</p><?php endif; ?>
          <form method="post" enctype="multipart/form-data" class="row"><?= csrf_field() ?><input type="hidden" name="action" value="hero_video">
            <label>Upload MP4<input type="file" name="file" accept="video/mp4" required></label><button class="btn">Upload</button></form>
          <?php if ($c['hero_video']): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="hero_video"><input type="hidden" name="remove" value="1"><button class="link danger">Remove the video</button></form><?php endif; ?>
        </section>

        <section class="card" id="near-images"><h2>NEAR section images</h2>
          <div class="thumbs"><?php foreach (['N' => 'No more commute anxiety', 'E' => 'Every life stage, one address', 'A' => 'Access to social infrastructure', 'R' => 'Right-sized community'] as $l => $label): ?>
            <form method="post" enctype="multipart/form-data" class="thumb"><?= csrf_field() ?><input type="hidden" name="action" value="near_image"><input type="hidden" name="letter" value="<?= $l ?>">
              <img src="<?= e(media_url($c['near_images'][$l] ?? '')) ?>" alt=""><strong><?= $l ?> · <?= e($label) ?></strong>
              <label>Replace image<input type="file" name="file" accept="image/jpeg,image/png,image/webp" required></label><button class="btn small">Save</button></form>
          <?php endforeach; ?></div>
        </section>

        <section class="card" id="reviews"><h2>Customer reviews (videos)</h2>
          <?php if ($c['reviews']): ?>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="reviews_save">
            <div class="thumbs"><?php foreach ($c['reviews'] as $i => $r): $yid = youtube_id($r['video_url']); ?>
              <div class="thumb"><?php if ($yid): ?><img src="https://i.ytimg.com/vi/<?= e($yid) ?>/mqdefault.jpg" alt=""><?php endif; ?>
                <label>Name<input name="r[<?= $i ?>][name]" value="<?= e($r['name']) ?>" maxlength="80"></label>
                <label>Line under the name<input name="r[<?= $i ?>][note]" value="<?= e($r['note']) ?>" maxlength="120" placeholder="e.g. Homeowner, Realmark Seasonss"></label>
                <label>Order<input name="r[<?= $i ?>][order]" type="number" value="<?= $i + 1 ?>" min="1" max="99"></label>
                <label class="check"><input type="checkbox" name="r[<?= $i ?>][delete]" value="1"> Remove</label></div>
            <?php endforeach; ?></div>
            <button class="btn">Save reviews</button></form>
          <?php else: ?><p class="muted">No reviews yet. Upload each review to YouTube (it can be unlisted), then paste the link here.</p><?php endif; ?>
          <form method="post" class="row add"><?= csrf_field() ?><input type="hidden" name="action" value="review_add">
            <label class="grow">YouTube link<input name="video_url" required placeholder="https://youtu.be/…"></label>
            <label>Name<input name="name" maxlength="80"></label>
            <label>Line under the name<input name="note" maxlength="120" placeholder="Homeowner, Realmark Seasonss"></label>
            <button class="btn ghost">Add review</button></form>
        </section>

        <section class="card" id="legacy"><h2>Brand Legacy (previous projects)</h2>
          <?php if ($c['legacy']): ?>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="legacy_save">
            <div class="thumbs"><?php foreach ($c['legacy'] as $i => $p): ?>
              <div class="thumb"><img src="<?= e(media_url($p['thumb'] ?: $p['image'])) ?>" alt="">
                <label>Project name<input name="l[<?= $i ?>][name]" value="<?= e($p['name']) ?>" maxlength="80"></label>
                <label>Location (optional)<input name="l[<?= $i ?>][location]" value="<?= e($p['location'] ?? '') ?>" maxlength="80"></label>
                <label>Order<input name="l[<?= $i ?>][order]" type="number" value="<?= $i + 1 ?>" min="1" max="99"></label>
                <label class="check"><input type="checkbox" name="l[<?= $i ?>][delete]" value="1"> Remove</label></div>
            <?php endforeach; ?></div>
            <button class="btn">Save Brand Legacy</button></form>
          <?php endif; ?>
          <form method="post" enctype="multipart/form-data" class="row add"><?= csrf_field() ?><input type="hidden" name="action" value="legacy_add">
            <label>Project name<input name="name" required maxlength="80"></label>
            <label>Location (optional)<input name="location" maxlength="80" placeholder="e.g. Joka"></label>
            <label>Photo<input type="file" name="file" accept="image/jpeg,image/png,image/webp" required></label>
            <button class="btn ghost">Add project</button></form>
        </section>

        <section class="card" id="gallery"><h2>Gallery</h2>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="gallery_save">
            <div class="thumbs"><?php foreach ($c['gallery'] as $i => $g): ?>
              <div class="thumb"><img src="<?= e(media_url($g['thumb'] ?: $g['image'])) ?>" alt="">
                <label>Caption<input name="g[<?= $i ?>][caption]" value="<?= e($g['caption']) ?>" maxlength="120"></label>
                <label>Order<input name="g[<?= $i ?>][order]" type="number" value="<?= $i + 1 ?>" min="1" max="99"></label>
                <label class="check"><input type="checkbox" name="g[<?= $i ?>][delete]" value="1"> Remove</label></div>
            <?php endforeach; ?></div>
            <button class="btn">Save gallery</button>
          </form>
          <form method="post" enctype="multipart/form-data" class="row add"><?= csrf_field() ?><input type="hidden" name="action" value="gallery_add">
            <label>Add an image<input type="file" name="file" accept="image/jpeg,image/png,image/webp" required></label>
            <label>Caption<input name="caption" maxlength="120" placeholder="e.g. Clubhouse lounge"></label>
            <button class="btn ghost">Upload</button></form>
        </section>

        <section class="card" id="video"><h2>Walkthrough video</h2>
          <form method="post" class="row"><?= csrf_field() ?><input type="hidden" name="action" value="video">
            <label class="grow">YouTube link (blank removes it)<input name="video_url" value="<?= e($c['video_url']) ?>" placeholder="https://www.youtube.com/watch?v=…"></label>
            <button class="btn">Save</button></form>
        </section>

        <section class="card" id="masterplan"><h2>Master plan</h2>
          <img src="<?= e(media_url($c['masterplan'])) ?>" alt="Current master plan" class="preview">
          <form method="post" enctype="multipart/form-data" class="row"><?= csrf_field() ?><input type="hidden" name="action" value="masterplan">
            <label>Replace image<input type="file" name="file" accept="image/jpeg,image/png,image/webp"></label>
            <label class="check"><input type="checkbox" name="final" value="1" <?= $c['masterplan_final'] ? 'checked' : '' ?>> This is the approved final version (hides the reminder)</label>
            <button class="btn">Save</button></form>
        </section>

        <section class="card" id="map-image"><h2>Location map (Google Maps screenshot)</h2>
          <img src="<?= e(media_url($c['map_image'])) ?>" alt="Current map screenshot" class="preview">
          <p class="muted">Clicking it on the site opens Google Maps for: <strong><?= e($c['map_query']) ?></strong> (change this in Content). Keep the Google logo and "Map data" line visible in any new screenshot.</p>
          <form method="post" enctype="multipart/form-data" class="row"><?= csrf_field() ?><input type="hidden" name="action" value="map_image">
            <label>Replace screenshot<input type="file" name="file" accept="image/jpeg,image/png,image/webp" required></label><button class="btn ghost">Upload</button></form>
        </section>

        <section class="card" id="mp-map"><h2>Master plan on aerial view</h2>
          <?php $mp = $c['mp_map']; ?>
          <p class="muted">Drag the master plan onto the plot. Use the sliders to turn it until north matches and to size it to the plot. The small frame shows how visitors see it.</p>
          <form method="post" id="mp-align"><?= csrf_field() ?><input type="hidden" name="action" value="mp_map">
            <?php mp_stage($mp, 'mp-full'); ?>
            <div class="grid2 mp-controls">
              <label>Turn (degrees) <input type="range" min="-180" max="180" step="0.5" data-k="r"><input type="number" name="r" step="0.5" data-k="r"></label>
              <label>Size (% of aerial width) <input type="range" min="2" max="60" step="0.1" data-k="w"><input type="number" name="w" step="0.1" data-k="w"></label>
              <label>Zoom on the website <input type="range" min="1" max="4" step="0.05" data-k="z"><input type="number" name="z" step="0.05" data-k="z"></label>
              <label>Position across / down (%) <span class="row"><input type="number" name="x" step="0.1" data-k="x"><input type="number" name="y" step="0.1" data-k="y"></span></label>
            </div>
            <h3>How visitors see it</h3>
            <div class="mp-preview"><?php mp_stage($mp); ?></div>
            <label>Credit line on the image<input name="credit" value="<?= e($mp['credit']) ?>" maxlength="160"></label>
            <label class="check"><input type="checkbox" name="on" value="1" <?= !empty($mp['on']) ? 'checked' : '' ?>> Show the aerial view on the site (otherwise the plain master plan shows)</label>
            <label class="check"><input type="checkbox" name="confirmed" value="1" <?= !empty($mp['confirmed']) ? 'checked' : '' ?>> The position is checked against the actual plot (hides the reminder)</label>
            <button class="btn">Save position</button>
          </form>
          <div class="row" style="margin-top:18px">
            <form method="post" enctype="multipart/form-data" class="row"><?= csrf_field() ?><input type="hidden" name="action" value="mp_aerial">
              <label>Replace aerial photo (drone shot or satellite image, JPG)<input type="file" name="file" accept="image/jpeg,image/png,image/webp" required></label><button class="btn ghost">Upload</button></form>
            <form method="post" enctype="multipart/form-data" class="row"><?= csrf_field() ?><input type="hidden" name="action" value="mp_plan">
              <label>Replace master plan cut-out (PNG, transparent background)<input type="file" name="file" accept="image/png" required></label><button class="btn ghost">Upload</button></form>
          </div>
        </section>

        <section class="card" id="brochure"><h2>Brochure PDF</h2>
          <p><?= $c['brochure'] ? 'Current: <a href="' . e(media_url($c['brochure'])) . '" target="_blank" rel="noopener">' . e(basename($c['brochure'])) . '</a>. Visitors get a download button after registering.' : 'No brochure yet. When you upload one, visitors get a download button after they register.' ?></p>
          <form method="post" enctype="multipart/form-data" class="row"><?= csrf_field() ?><input type="hidden" name="action" value="brochure">
            <label>Upload PDF<input type="file" name="file" accept="application/pdf" required></label><button class="btn">Upload</button></form>
          <?php if ($c['brochure']): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="brochure"><input type="hidden" name="remove" value="1"><button class="link danger">Remove the brochure</button></form><?php endif; ?>
        </section>

        <section class="card" id="plans"><h2>Floor plans</h2>
          <p class="muted">Edit a plan's details or replace its image, then press Save on that plan.</p>
          <?php foreach ($TIERS as $tier => $label): ?><h3><?= e($label) ?></h3><div class="thumbs">
            <?php foreach ($c['plans'] as $p): if ($p['tier'] !== $tier) continue; ?>
              <form method="post" enctype="multipart/form-data" class="thumb" id="plan-<?= e($p['id']) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="plan_save"><input type="hidden" name="id" value="<?= e($p['id']) ?>">
                <img src="<?= e(media_url($p['thumb'] ?: $p['image'])) ?>" alt="">
                <strong>Flat <?= e($p['flat']) ?> · <?= e($p['config']) ?></strong>
                <label>Floors<input name="floors" value="<?= e($p['floors']) ?>" maxlength="80"></label>
                <div class="grid2"><label>Carpet (sq. ft.)<input name="carpet" value="<?= e($p['carpet']) ?>" maxlength="20"></label><label>Terrace (sq. ft.)<input name="terrace" value="<?= e($p['terrace']) ?>" maxlength="20"></label></div>
                <label>Replace image<input type="file" name="file" accept="image/jpeg,image/png,image/webp"></label>
                <button class="btn small">Save plan</button></form>
            <?php endforeach; ?></div>
          <?php endforeach; ?>
        </section>
    <?php });
    break;

case 'switches':
    layout('Switches', function () use ($s, $TIERS) { ?>
        <h1>Switches</h1>
        <form method="post" class="card stack"><?= csrf_field() ?><input type="hidden" name="action" value="switches">
          <label class="switch"><input type="checkbox" name="show_prices" value="1" <?= $s['show_prices'] ? 'checked' : '' ?>><span><strong>Show prices</strong><br><span class="muted">Shows the price on each residence card (set the prices under Content). Off: "Prices will be announced at launch".</span></span></label>
          <label class="switch"><input type="checkbox" name="banner_on" value="1" <?= $s['banner_on'] ? 'checked' : '' ?>><span><strong>Announcement banner</strong><br><span class="muted">A gold strip at the very top, e.g. for a launch offer. Text and link are under Content.</span></span></label>
          <label class="switch"><input type="checkbox" name="form_paused" value="1" <?= $s['form_paused'] ? 'checked' : '' ?>><span><strong>Pause the enquiry form</strong><br><span class="muted">Replaces the form with "Registrations are paused, please call us". Turn this off again before running ads.</span></span></label>
          <label class="switch"><input type="checkbox" name="show_todo_tags" value="1" <?= $s['show_todo_tags'] ? 'checked' : '' ?>><span><strong>Show yellow reminders for missing content</strong><br><span class="muted">Turn off before launch: missing items are then simply hidden.</span></span></label>
          <fieldset><legend>Sold out</legend><p class="muted">Marks the residence type as sold out and removes it from the form.</p>
            <?php foreach ($TIERS as $t => $label): ?><label class="check"><input type="checkbox" name="sold_out[<?= $t ?>]" value="1" <?= !empty($s['sold_out'][$t]) ? 'checked' : '' ?>> <?= e($label) ?></label><?php endforeach; ?>
          </fieldset>
          <div><button class="btn">Save switches</button></div>
        </form>
    <?php });
    break;

case 'tracking':
    layout('Ad tracking', function () use ($s) { $t = $s['tracking']; ?>
        <h1>Ad tracking</h1>
        <p class="muted">Paste the IDs from your ad accounts. The tags load on every page, and a successful enquiry sends a <code>lead_submitted</code> event (Google Tag Manager) and a <code>Lead</code> event (Meta Pixel). If you use Google Tag Manager, add Google Ads and GA4 inside GTM and leave the GA4 box empty.</p>
        <form method="post" class="card stack"><?= csrf_field() ?><input type="hidden" name="action" value="tracking">
          <label>Google Tag Manager container ID<input name="gtm_id" value="<?= e($t['gtm_id']) ?>" placeholder="GTM-XXXXXXX" maxlength="20"></label>
          <label>Meta (Facebook) Pixel ID<input name="meta_pixel_id" value="<?= e($t['meta_pixel_id']) ?>" placeholder="123456789012345" maxlength="25" inputmode="numeric"></label>
          <label>GA4 Measurement ID (only if not using GTM)<input name="ga4_id" value="<?= e($t['ga4_id']) ?>" placeholder="G-XXXXXXXXXX" maxlength="20"></label>
          <p class="alert">Before switching tracking on, the site needs a privacy policy and, for visitors from outside India, a cookie consent banner. Check with legal.</p>
          <div><button class="btn">Save tracking</button></div>
        </form>
    <?php });
    break;

case 'account':
    layout('Account', function () use ($me) { ?>
        <h1>Account</h1>
        <section class="card"><h2>Change your password</h2>
          <form method="post" class="stack narrowform"><?= csrf_field() ?><input type="hidden" name="action" value="password">
            <label>Current password<input type="password" name="current" required autocomplete="current-password"></label>
            <label>New password (12+ characters, letters and a number)<input type="password" name="new" required minlength="12" autocomplete="new-password"></label>
            <label>New password again<input type="password" name="new2" required minlength="12" autocomplete="new-password"></label>
            <div><button class="btn">Change password</button></div></form></section>
        <section class="card"><h2>Admins</h2>
          <table class="data"><thead><tr><th>Email</th><th>Added</th><th></th></tr></thead><tbody>
          <?php foreach (users() as $u): ?><tr><td><?= e($u['email']) ?><?= $u['email'] === $me['email'] ? ' <span class="muted">(you)</span>' : '' ?></td><td><?= e(date('d M Y', strtotime($u['created']))) ?></td>
            <td><?php if ($u['email'] !== $me['email']): ?><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="remove_user"><input type="hidden" name="email" value="<?= e($u['email']) ?>"><button class="link danger">Remove</button></form><?php endif; ?></td></tr><?php endforeach; ?>
          </tbody></table>
          <h3>Add an admin</h3>
          <form method="post" class="row"><?= csrf_field() ?><input type="hidden" name="action" value="add_user">
            <label>Email<input type="email" name="email" required></label>
            <label>Temporary password (12+)<input type="password" name="password" required minlength="12" autocomplete="new-password"></label>
            <button class="btn ghost">Add admin</button></form></section>
    <?php });
    break;

default: // dashboard
    $today = db()->prepare('SELECT COUNT(*) FROM leads WHERE created_at >= ?'); $today->execute([date('Y-m-d 00:00:00')]);
    $week = db()->prepare('SELECT COUNT(*) FROM leads WHERE created_at >= ?'); $week->execute([date('Y-m-d 00:00:00', strtotime('-6 days'))]);
    $stats = ['Today' => (int)$today->fetchColumn(), 'Last 7 days' => (int)$week->fetchColumn(),
              'All time' => (int)db()->query('SELECT COUNT(*) FROM leads')->fetchColumn(),
              'Not in Farvision' => (int)db()->query("SELECT COUNT(*) FROM leads WHERE fv_status != 'sent'")->fetchColumn()];
    $recent = db()->query('SELECT * FROM leads ORDER BY id DESC LIMIT 5')->fetchAll();
    layout('Dashboard', function () use ($stats, $recent, $todos, $s) { ?>
        <h1>Dashboard</h1>
        <?php if ($s['form_paused']): ?><p class="alert err">The enquiry form is paused. <a href="?p=switches">Turn it back on</a></p><?php endif; ?>
        <div class="stats"><?php foreach ($stats as $k => $v): ?><div class="stat<?= $k === 'Not in Farvision' && $v ? ' bad' : '' ?>"><div class="n"><?= $v ?></div><div><?= e($k) ?></div></div><?php endforeach; ?></div>
        <section class="card"><div class="head"><h2>Latest leads</h2><a href="?p=leads">All leads</a></div>
          <?php if (!$recent): ?><p class="muted">No enquiries yet.</p><?php else: ?>
          <table class="data"><tbody><?php foreach ($recent as $r): ?><tr><td class="nowrap"><?= e(date('d M, g:i a', strtotime($r['created_at']))) ?></td><td><?= e($r['name']) ?></td><td>+91 <?= e($r['phone']) ?></td><td><?= e($r['bhk']) ?></td><td><span class="pill <?= $r['fv_status'] === 'sent' ? 'ok' : 'err' ?>"><?= $r['fv_status'] === 'sent' ? 'In Farvision' : 'Not sent' ?></span></td></tr><?php endforeach; ?></tbody></table>
          <?php endif; ?></section>
        <section class="card"><h2>Still to fill in before launch</h2>
          <?php if (!$todos): ?><p>Everything is filled in. Remember to turn off the yellow reminders under <a href="?p=switches">Switches</a>.</p><?php else: ?>
          <ul class="todo-list"><?php foreach ($todos as [$label, $p, $anchor]): ?><li><a href="?p=<?= $p ?><?= $anchor ?>"><?= e($label) ?></a></li><?php endforeach; ?></ul><?php endif; ?>
        </section>
    <?php });
}

/* ---------------- layout ---------------- */
function layout(string $title, callable $body, bool $nav = true): void {
    global $page;
    $f = flash();
    ?><!doctype html>
<html lang="en-IN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow"><title><?= e($title) ?> · 9 Minnutes admin</title>
<link rel="icon" href="/assets/logo-9minnutes.svg" type="image/svg+xml">
<link rel="stylesheet" href="admin.css?v=<?= @filemtime(__DIR__ . '/admin.css') ?>"></head>
<body>
<?php if ($nav): ?>
<header class="bar"><a href="?p=dashboard" class="brand"><img src="/assets/logo-9minnutes.svg" alt="9 Minnutes"> <span>Admin</span></a>
<nav><?php foreach (['dashboard' => 'Dashboard', 'leads' => 'Leads', 'content' => 'Content', 'media' => 'Images & files', 'switches' => 'Switches', 'tracking' => 'Ad tracking', 'account' => 'Account'] as $k => $v): ?><a href="?p=<?= $k ?>" <?= $page === $k || ($k === 'dashboard' && !in_array($page, ['leads', 'content', 'media', 'switches', 'tracking', 'account'], true)) ? 'aria-current="page"' : '' ?>><?= $v ?></a><?php endforeach; ?></nav>
<div class="right"><a href="/" target="_blank" rel="noopener">View site</a>
<form method="post" action="?p=logout" class="inline"><?= csrf_field() ?><button class="link">Sign out</button></form></div></header>
<?php endif; ?>
<main><?php if ($f): ?><p class="alert <?= $f[0] === 'err' ? 'err' : 'ok' ?>" role="status"><?= e($f[1]) ?></p><?php endif; ?>
<?php $body(); ?></main>
<script src="/mpmap.js?v=<?= @filemtime(PUBLIC_DIR . '/mpmap.js') ?>"></script>
<script src="admin.js?v=<?= @filemtime(__DIR__ . '/admin.js') ?>"></script>
</body></html><?php
}
