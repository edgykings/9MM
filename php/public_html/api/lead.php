<?php
// POST /api/lead.php – saves the enquiry on this server, then sends it to Farvision.
define('PUBLIC_ROOT', dirname(__DIR__));
require dirname(__DIR__) . '/boot.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function out(int $code, array $body): void { http_response_code($code); echo json_encode($body); exit; }

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { header('Allow: POST'); out(405, ['ok' => false, 'error' => 'method']); }
if (settings()['form_paused']) out(503, ['ok' => false, 'error' => 'paused']);

// Same-site requests only.
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '') {
    $o = parse_url($origin);
    $originHost = strtolower(($o['host'] ?? '') . (isset($o['port']) ? ':' . $o['port'] : ''));
    $siteHost = strtolower(preg_replace('/:(80|443)$/', '', $_SERVER['HTTP_HOST'] ?? ''));
    if ($originHost !== $siteHost) out(403, ['ok' => false, 'error' => 'origin']);
}

$raw = file_get_contents('php://input', false, null, 0, 20000);
$b = json_decode((string)$raw, true);
if (!is_array($b)) out(400, ['ok' => false, 'error' => 'json']);

if (!empty($b['company'])) out(200, ['ok' => true]); // honeypot: bots fill this hidden field

$clean = fn($v, int $max) => mb_substr(trim(preg_replace('/[\x00-\x1F<>]/u', '', (string)($v ?? ''))), 0, $max);

$name = $clean($b['name'] ?? '', 80);
$phone = preg_replace('/\D/', '', (string)($b['phone'] ?? ''));
if (strlen($phone) === 12 && str_starts_with($phone, '91')) $phone = substr($phone, 2);
if (strlen($phone) === 11 && $phone[0] === '0') $phone = substr($phone, 1);
$email = $clean($b['email'] ?? '', 120);

$bhkOptions = ['Not sure yet'];
foreach (content()['homes'] as $h) $bhkOptions[] = $h['bhk'] . ' – ' . $h['label'];
$slots = ['Any time', 'Morning (10 am – 1 pm)', 'Afternoon (1 – 5 pm)', 'Evening (5 – 8 pm)'];

// Brochure download: only a mobile number is required.
$brochure = ($b['source'] ?? '') === 'brochure';
if ($brochure) {
    if (!content()['brochure']) out(400, ['ok' => false, 'error' => 'brochure']);
    if (mb_strlen($name) < 2) $name = 'Brochure Enquiry';
    $b['bhk'] = 'Brochure download';
    $b['slot'] = 'Any time';
    $bhkOptions[] = 'Brochure download';
}

if (mb_strlen($name) < 2) out(400, ['ok' => false, 'error' => 'name']);
if (!preg_match('/^[6-9]\d{9}$/', $phone) || preg_match('/^(\d)\1{9}$/', $phone) || in_array($phone, ['9876543210', '6789012345'], true)) out(400, ['ok' => false, 'error' => 'phone']);
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) out(400, ['ok' => false, 'error' => 'email']);
if (!in_array($b['bhk'] ?? '', $bhkOptions, true)) out(400, ['ok' => false, 'error' => 'bhk']);
if (($b['consent'] ?? null) !== true) out(400, ['ok' => false, 'error' => 'consent']);

// At most 5 enquiries per visitor per 10 minutes.
$key = client_key();
if (too_many('lead', $key, 5, 600)) out(429, ['ok' => false, 'error' => 'rate']);
note_attempt('lead', $key);

$a = is_array($b['attribution'] ?? null) ? $b['attribution'] : [];
$lead = [
    'created_at' => date('Y-m-d H:i:s'),
    'name' => $name, 'phone' => $phone, 'email' => $email,
    'bhk' => $b['bhk'], 'slot' => in_array($b['slot'] ?? '', $slots, true) ? $b['slot'] : 'Any time',
    'dump_id' => fv_dump_id(),
];
foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'fbclid', 'landing_page', 'referrer'] as $k) {
    $lead[$k] = $clean($a[$k] ?? '', 300);
}

try {
    $cols = array_keys($lead);
    $st = db()->prepare('INSERT INTO leads (' . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')');
    $st->execute(array_values($lead));
    $id = (int)db()->lastInsertId();
} catch (Throwable $e) {
    error_log('[lead] save failed: ' . $e->getMessage());
    // Still try Farvision so the lead is not lost.
    [$ok] = fv_send($lead);
    out($ok ? 200 : 502, ['ok' => $ok]);
}

[$ok, $msg] = fv_sync_row($id);
if (!$ok) error_log("[lead] #$id Farvision failed: $msg");
lead_alert($lead, $id, $ok, (string)$msg);
out(200, ['ok' => true]); // saved on our server; failed syncs can be retried from the admin panel
