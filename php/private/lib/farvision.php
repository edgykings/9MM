<?php
// Farvision LeadSync client (SyncLeadsV2/RawLeads). Spec supplied by Realmark, Nov 2026.

function fv_dump_id(): string {
    return date('dmYHis') . sprintf('%03d', random_int(0, 999));
}

function fv_comments(array $lead): string {
    $bits = $lead['bhk'] === 'Brochure download'
        ? ['Request: downloaded the brochure from the website', 'Source: 9 Minnutes microsite']
        : ['Looking for: ' . $lead['bhk'], 'Best time to call: ' . ($lead['slot'] ?: 'Any time'), 'Source: 9 Minnutes microsite'];
    $utm = [];
    foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'] as $k) {
        if (!empty($lead[$k])) $utm[] = "$k=" . $lead[$k];
    }
    if ($utm) $bits[] = 'UTM: ' . implode(', ', $utm);
    if (!empty($lead['gclid'])) $bits[] = 'gclid=' . $lead['gclid'];
    if (!empty($lead['fbclid'])) $bits[] = 'fbclid=' . $lead['fbclid'];
    if (!empty($lead['landing_page'])) $bits[] = 'Landing: ' . $lead['landing_page'];
    $bits[] = 'Consent to contact: yes';
    return mb_substr(implode(' | ', $bits), 0, 1000);
}

function fv_payload(array $lead): array {
    $parts = preg_split('/\s+/', trim($lead['name']), 2);
    return [
        'firstName' => $parts[0] ?? '',
        'lastName' => $parts[1] ?? '',
        'email' => $lead['email'] ?? '',
        'mobilePhone' => '+91' . $lead['phone'],
        'comments' => fv_comments($lead),
        'originFrom' => 'Auto Lead from Digital',
        'product' => cfg('farvision_product', '9 MINNUTES'),
        'campaign' => cfg('farvision_campaign', 'Website'),
        'isUpdatefromUIDate' => false,
        'isImported' => true,
        'DumpdataObjectId' => $lead['dump_id'],
    ];
}

/** Sends one lead. Returns [bool ok, string message]. */
function fv_send(array $lead): array {
    $clientId = cfg('farvision_client_id');
    if (!$clientId) return [false, 'Farvision ClientId is not set in config.php'];
    $ch = curl_init(cfg('farvision_url'));
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'ClientId: ' . $clientId],
        CURLOPT_POSTFIELDS => json_encode(fv_payload($lead), JSON_UNESCAPED_UNICODE),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_CONNECTTIMEOUT => 6,
    ]);
    $body = curl_exec($ch);
    $err = curl_error($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($body === false) return [false, 'Network error: ' . $err];
    $json = json_decode((string)$body, true);
    if ($code >= 200 && $code < 300 && is_array($json) && ($json['status'] ?? null) === true) {
        return [true, (string)($json['message'] ?? 'Accepted')];
    }
    return [false, "HTTP $code: " . mb_substr((string)$body, 0, 300)];
}

/** Sends a stored lead and records the result on its row. */
function fv_sync_row(int $id): array {
    $st = db()->prepare('SELECT * FROM leads WHERE id=?');
    $st->execute([$id]);
    $lead = $st->fetch();
    if (!$lead) return [false, 'Lead not found'];
    [$ok, $msg] = fv_send($lead);
    db()->prepare('UPDATE leads SET fv_status=?, fv_message=?, fv_attempts=fv_attempts+1 WHERE id=?')
        ->execute([$ok ? 'sent' : 'failed', $msg, $id]);
    return [$ok, $msg];
}

/** Optional email to pre-sales for each lead (config alert_email). Never blocks the visitor. */
function lead_alert(array $lead, int $id, bool $fvOk, string $fvMsg): void {
    $to = trim((string)cfg('alert_email', ''));
    if ($to === '' || !function_exists('mail')) return;
    $from = trim((string)cfg('alert_from', ''));
    $one = fn($v) => str_replace(["\r", "\n"], ' ', (string)$v);
    $subject = ($fvOk ? 'New 9 Minnutes lead: ' : 'ACTION NEEDED, lead not in Farvision: ') . $one($lead['name']);
    $src = implode(' / ', array_filter([$lead['utm_source'] ?? '', $lead['utm_medium'] ?? '', $lead['utm_campaign'] ?? '']));
    $body = "Name: {$lead['name']}\nPhone: +91{$lead['phone']}\nEmail: {$lead['email']}\n"
          . "Home: {$lead['bhk']}\nBest time to call: {$lead['slot']}\n"
          . 'Source: ' . ($src !== '' ? $src : 'direct / unknown') . "\n"
          . "Lead #$id, received {$lead['created_at']}\n\n"
          . 'Farvision: ' . ($fvOk ? 'accepted' : "NOT accepted ($fvMsg). Retry from the admin panel → Leads, or enter it by hand.") . "\n";
    $headers = 'Content-Type: text/plain; charset=UTF-8';
    if ($from !== '' && filter_var($from, FILTER_VALIDATE_EMAIL)) $headers .= "\r\nFrom: 9 Minnutes website <$from>";
    if (!@mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers)) error_log("[lead] #$id alert email not sent");
}
