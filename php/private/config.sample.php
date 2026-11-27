<?php
// Copy this file to config.php (same folder) and fill in the values.
// config.php must never be committed to GitHub or placed where the web can read it.

return [
    // Farvision LeadSync (from Gamut). The ClientId works like a password.
    'farvision_url'       => 'https://fvintegration.farvisioncloud.com/LeadSync/api/SyncLeadsV2/RawLeads',
    'farvision_client_id' => '',
    'farvision_product'   => '9 MINNUTES',
    'farvision_campaign'  => 'Website',          // must exist in Farvision with this exact spelling

    // Optional email alert for every lead (blank = off). The subject says
    // "ACTION NEEDED" when Farvision did not accept the lead.
    'alert_email' => '',                       // e.g. presales@realmarkrealty.com (comma-separate several)
    'alert_from'  => '',                       // e.g. no-reply@your-microsite-domain (an address on this domain)
    // One-time code needed to create the first admin account at /admin/.
    // Use 20+ random characters, create the account, then you may blank it.
    'setup_token' => '',

    // Where leads, content and accounts are stored. Must be writable by PHP
    // and must NOT be inside public_html. Default: private/data next to this file.
    'data_dir' => __DIR__ . '/data',

    // Where admin uploads go (inside public_html so visitors can load them).
    // Default: public_html/uploads. Set an absolute path if your layout differs.
    'uploads_dir' => null,
    'uploads_url' => '/uploads',

    // Time zone for lead timestamps.
    'timezone' => 'Asia/Kolkata',
];
