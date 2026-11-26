<?php
// Default content and switches. The admin panel saves changes to data/content.json and
// data/settings.json; anything not saved there falls back to these values.

function default_content(): array {
    $plans = [];
    foreach (json_decode((string)file_get_contents(__DIR__ . '/floorplans.json'), true) as $p) {
        $n = sprintf('%02d', $p['page']);
        $plans[] = [
            'id' => $n, 'tier' => $p['tier'], 'flat' => $p['flat'], 'floors' => $p['floors'],
            'config' => $p['cfg'], 'carpet' => $p['carpet'], 'terrace' => $p['terrace'] ?? '',
            'image' => "assets/plans/plan-$n.jpg", 'thumb' => "assets/plans/plan-$n-thumb.jpg",
        ];
    }
    $gal = [['tower-metro', '9 Minnutes beside the Joka Metro line'], ['aerial', 'Aerial view among greenery'], ['facade', 'Tower façade'],
            ['living', 'Living room'], ['dusk', 'Evening view'], ['terrace', 'Terrace apartment'], ['pool', 'Swimming pool'],
            ['fitness', 'Fitness studio'], ['lawn', 'Festival lawn'], ['banquet', 'Banquet']];
    $gallery = array_map(fn($g) => ['image' => "assets/gallery/{$g[0]}.jpg", 'thumb' => "assets/gallery/{$g[0]}-thumb.jpg", 'caption' => $g[1]], $gal);

    return [
        'hero_eyebrow'  => 'Pre-launch · Joka, Kolkata',
        'hero_title'    => "the\nwalkable\naddress",
        'hero_subtitle' => '2, 3 & 4 BHK residences on Hanspukur Green Park Road, a 9-minute walk from Joka Metro.',
        'facts' => [
            ['value' => '9 min', 'label' => 'walk to Joka Metro (0.7 km)'],
            ['value' => '127', 'label' => 'residences in a single G+11 tower'],
            ['value' => '16,400', 'label' => 'sq. ft. lifestyle club'],
            ['value' => '2 · 3 · 4', 'label' => 'BHK, with terrace apartments'],
        ],
        'metro_note' => '',   // e.g. "Projected travel times on completion of the Joka–Esplanade Metro."
        'three_sides_open' => '', // e.g. "Every residence, three sides open." (leave blank until confirmed)
        'homes' => [
            ['tier' => 'ESSENTIAL', 'label' => 'Essential', 'bhk' => '2 BHK', 'desc' => 'Two well-planned bedrooms.', 'line' => 'For starting out together.', 'carpet' => '600', 'price' => ''],
            ['tier' => 'SMART', 'label' => 'Smart', 'bhk' => '3 BHK', 'desc' => 'Two large bedrooms + one versatile room.', 'line' => 'For growing together.', 'carpet' => '668 – 716', 'price' => ''],
            ['tier' => 'PREMIUM', 'label' => 'Premium', 'bhk' => '3 BHK', 'desc' => 'Three well-proportioned bedrooms.', 'line' => 'For a growing family.', 'carpet' => '816 – 877', 'price' => ''],
            ['tier' => 'SIGNATURE', 'label' => 'Signature', 'bhk' => '4 BHK', 'desc' => 'Four spacious bedrooms.', 'line' => 'For generations together.', 'carpet' => '1,096 – 1,099', 'price' => ''],
        ],
        'price_note' => 'Prices are all-inclusive of GST unless stated. Parking and other charges extra.',
        'terrace_text' => 'Select 3 and 4 BHK residences come with a triple-height terrace. Depending on the layout, step out from your living, dining or bedroom space into the open air for morning tea, a quiet moment with your plants or an evening with friends.',
        'amenities_count' => '', // "45" or "48" once confirmed
        'plans' => $plans,
        'gallery' => $gallery,
        'video_url' => '',
        'masterplan' => 'assets/masterplan.jpg',
        'masterplan_final' => false,
        // Master plan shown on the aerial view of the area (position set in admin → Images & files)
        'mp_map' => ['on' => true, 'aerial' => 'assets/masterplan-aerial.jpg', 'plan' => 'assets/masterplan-cutout.png',
                     'x' => 54.9, 'y' => 27.2, 'w' => 13.0, 'r' => -165.0, 'z' => 2.2, 'confirmed' => false,
                     'credit' => 'Aerial imagery: Esri, Maxar. Indicative placement.'],
        'brochure' => '',
        'faq' => [
            ['q' => 'Where exactly is 9 Minnutes?', 'a' => '61, Hanspukur Green Park Road, Joka, Kolkata 700104, about 150 metres from Diamond Harbour Road.'],
            ['q' => 'How far is the Metro?', 'a' => 'Joka Metro station on the Purple Line is 0.7 km away, about a 9-minute walk.'],
            ['q' => 'What types of homes are there?', 'a' => '2 BHK (Essential), 3 BHK (Smart and Premium) and 4 BHK (Signature) apartments, with carpet areas from 600 to 1,099 sq. ft. Select 3 and 4 BHK homes have terraces.'],
            ['q' => 'How big is the project?', 'a' => 'A single G+11 tower with 127 residences and a 16,400 sq. ft. lifestyle club.'],
            ['q' => 'What are the prices?', 'a' => 'Prices will be announced at launch. Register your interest and our team will share them with you first.'],
            ['q' => 'When is possession?', 'a' => ''],
            ['q' => 'Is the project RERA registered?', 'a' => ''],
            ['q' => 'Does the area come under KMC?', 'a' => ''],
            ['q' => 'Can I visit the site?', 'a' => 'Yes. Register your interest or call +91 98367 80001 and our team will set up a visit at a time that suits you.'],
        ],
        'phone' => '+91 98367 80001',
        'email' => 'connect@realmarkrealty.in',
        'whatsapp' => '',   // digits with country code, e.g. 919836780001 — leave blank until confirmed
        'site_address' => '61, Hanspukur Green Park Road, Joka, Kolkata 700104',
        'map_query' => 'Hanspukur Green Park Road, Joka, Kolkata 700104',
        'developer_address' => 'Happy Prestige Building, 3rd Floor, 82 Harish Mukherjee Road, Bhowanipore, Kolkata 700025',
        'developer_phone' => '+91 33 3504 3333',
        'rera_number' => '',
        'disclaimer' => '',
        'banner_text' => 'Pre-launch registrations now open',
        'banner_link' => '#register',

        // Added 26 Nov 2026 (feedback round 1)
        'hero_video' => '',     // uploads/… .mp4 teaser; the hero image shows until one is uploaded
        'near_images' => [
            'N' => 'assets/gallery/tower-metro-thumb.jpg',
            'E' => 'assets/gallery/living-thumb.jpg',
            'A' => 'assets/gallery/aerial-thumb.jpg',
            'R' => 'assets/gallery/lawn-thumb.jpg',
        ],
        'reviews' => [],        // [{video_url, name, note}]
        'legacy' => [],         // [{name, location, image, thumb}]
        'social' => ['facebook' => '', 'instagram' => '', 'youtube' => '', 'linkedin' => '', 'x' => ''],
        'blog_url' => '',
        'main_site_url' => 'https://www.realmarkrealty.in',
        'privacy_text' => '',
    ];
}

function default_settings(): array {
    return [
        'show_prices' => false,
        'banner_on' => false,
        'form_paused' => false,
        'show_todo_tags' => true,   // yellow [ADD]/[CONFIRM] tags for missing content
        'sold_out' => ['ESSENTIAL' => false, 'SMART' => false, 'PREMIUM' => false, 'SIGNATURE' => false],
        'tracking' => ['gtm_id' => '', 'meta_pixel_id' => '', 'ga4_id' => ''],
    ];
}
