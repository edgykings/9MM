<?php
// Finds the private folder (outside public_html if possible) and loads the shared code.
// Each entry point defines PUBLIC_ROOT before including this file.
if (!defined('PUBLIC_ROOT')) { http_response_code(404); exit; }
foreach ([PUBLIC_ROOT . '/../private', PUBLIC_ROOT . '/private'] as $candidate) {
    if (is_file($candidate . '/lib/bootstrap.php')) {
        require $candidate . '/lib/bootstrap.php';
        require $candidate . '/lib/farvision.php';
        return;
    }
}
http_response_code(500);
exit('Site files incomplete: the "private" folder was not found next to public_html.');
