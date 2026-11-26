<?php
// Shared setup for the 9 Minnutes site and admin panel.
declare(strict_types=1);

define('PRIVATE_DIR', dirname(__DIR__));
define('PUBLIC_DIR', realpath(PUBLIC_ROOT));

$cfgFile = PRIVATE_DIR . '/config.php';
if (!is_file($cfgFile)) {
    http_response_code(500);
    exit('Site not configured: copy private/config.sample.php to private/config.php.');
}
$GLOBALS['CFG'] = require $cfgFile;
function cfg(string $k, $default = null) { return $GLOBALS['CFG'][$k] ?? $default; }

date_default_timezone_set(cfg('timezone', 'Asia/Kolkata'));

define('DATA_DIR', rtrim(cfg('data_dir', PRIVATE_DIR . '/data'), '/'));
define('UPLOADS_DIR', rtrim(cfg('uploads_dir') ?: PUBLIC_DIR . '/uploads', '/'));
define('UPLOADS_URL', rtrim(cfg('uploads_url', '/uploads'), '/'));

if (!is_dir(DATA_DIR)) { @mkdir(DATA_DIR, 0750, true); }
if (!is_dir(UPLOADS_DIR)) { @mkdir(UPLOADS_DIR, 0755, true); }

require __DIR__ . '/defaults.php';

/* ---------- output ---------- */
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function nl(string $s): string { return nl2br(e($s), false); }

/* ---------- JSON storage (atomic write, locked) ---------- */
function json_load(string $name, array $default = []): array {
    $f = DATA_DIR . "/$name.json";
    if (!is_file($f)) return $default;
    $fh = fopen($f, 'r');
    if (!$fh) return $default;
    flock($fh, LOCK_SH);
    $raw = stream_get_contents($fh);
    flock($fh, LOCK_UN); fclose($fh);
    $data = json_decode((string)$raw, true);
    return is_array($data) ? $data : $default;
}
function json_save(string $name, array $data): void {
    $f = DATA_DIR . "/$name.json";
    $tmp = $f . '.' . bin2hex(random_bytes(4)) . '.tmp';
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (file_put_contents($tmp, $json, LOCK_EX) === false || !rename($tmp, $f)) {
        @unlink($tmp);
        throw new RuntimeException("Could not save $name");
    }
    @chmod($f, 0640);
}

/* Content and switches: stored values merged over the defaults, so new fields appear automatically. */
function content(): array {
    static $c = null;
    if ($c === null) $c = array_replace(default_content(), json_load('content'));
    return $c;
}
function settings(): array {
    static $s = null;
    if ($s === null) {
        $s = array_replace_recursive(default_settings(), json_load('settings'));
    }
    return $s;
}

/* ---------- SQLite: leads and login throttling ---------- */
function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $pdo = new PDO('sqlite:' . DATA_DIR . '/site.sqlite', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA journal_mode=WAL; PRAGMA busy_timeout=5000;');
    $pdo->exec('CREATE TABLE IF NOT EXISTS leads (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        created_at TEXT NOT NULL,
        name TEXT NOT NULL, phone TEXT NOT NULL, email TEXT,
        bhk TEXT, slot TEXT,
        utm_source TEXT, utm_medium TEXT, utm_campaign TEXT, utm_term TEXT, utm_content TEXT,
        gclid TEXT, fbclid TEXT, landing_page TEXT, referrer TEXT,
        dump_id TEXT, fv_status TEXT NOT NULL DEFAULT "pending", fv_message TEXT, fv_attempts INTEGER NOT NULL DEFAULT 0
    )');
    $pdo->exec('CREATE INDEX IF NOT EXISTS leads_created ON leads(created_at)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS attempts (kind TEXT, k TEXT, ts INTEGER)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS attempts_k ON attempts(kind, k, ts)');
    @chmod(DATA_DIR . '/site.sqlite', 0640);
    return $pdo;
}

/* Simple rate limit: returns true if this key has hit $max events of $kind within $window seconds. */
function too_many(string $kind, string $key, int $max, int $window): bool {
    $st = db()->prepare('SELECT COUNT(*) FROM attempts WHERE kind=? AND k=? AND ts>?');
    $st->execute([$kind, $key, time() - $window]);
    return (int)$st->fetchColumn() >= $max;
}
function note_attempt(string $kind, string $key): void {
    db()->prepare('INSERT INTO attempts(kind,k,ts) VALUES(?,?,?)')->execute([$kind, $key, time()]);
    if (random_int(1, 50) === 1) db()->prepare('DELETE FROM attempts WHERE ts<?')->execute([time() - 86400]);
}
function client_key(): string {
    return hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . cfg('setup_token', 'k'));
}

/* ---------- tracking IDs: only output values in the expected shape ---------- */
function valid_gtm(?string $v): ?string { return ($v && preg_match('/^GTM-[A-Z0-9]{4,10}$/', $v)) ? $v : null; }
function valid_pixel(?string $v): ?string { return ($v && preg_match('/^\d{10,20}$/', $v)) ? $v : null; }
function valid_ga4(?string $v): ?string { return ($v && preg_match('/^G-[A-Z0-9]{6,12}$/', $v)) ? $v : null; }

/* ---------- media URLs ---------- */
function media_url(string $path): string {
    // Stored paths are either "assets/..." (shipped) or "uploads/..." (admin uploads).
    if (str_starts_with($path, 'uploads/')) return UPLOADS_URL . '/' . substr($path, 8);
    return '/' . ltrim($path, '/');
}
function youtube_id(?string $url): ?string {
    if (!$url) return null;
    if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([A-Za-z0-9_-]{11})~', $url, $m)) return $m[1];
    return null;
}
