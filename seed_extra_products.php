<?php
/**
 * Catalog Expansion Seeder: 12+ Premium Streetwear Products across All Categories
 * The Stitch Co.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = get_db();

// 1. Ensure Categories Exist
$categories = [
    [
        'cat_key' => 'oversized',
        'cat_name' => 'Oversized Fit',
        'description' => '240 GSM Boxy Drop-Shoulder Graphic Tees',
        'image' => 'assets/images/products/tokyo_vibes_black.svg',
        'icon' => 'tshirt',
        'display_order' => 1
    ],
    [
        'cat_key' => 'tshirts',
        'cat_name' => 'Graphic Tees',
        'description' => 'Streetwear & Collegiate Regular Fit Tees',
        'image' => 'assets/images/products/good_vibes_white.svg',
        'icon' => 'tshirt',
        'display_order' => 2
    ],
    [
        'cat_key' => 'hoodies',
        'cat_name' => 'Hoodies & Fleece',
        'description' => '350 GSM Heavyweight Winter Drops',
        'image' => 'assets/images/products/stay_wild_cream.svg',
        'icon' => 'tag',
        'display_order' => 3
    ],
    [
        'cat_key' => 'polo',
        'cat_name' => 'Knit Polos',
        'description' => 'Textured Pique & Ribbed Structured Polos',
        'image' => 'assets/images/products/stitch_polo_olive.svg',
        'icon' => 'star',
        'display_order' => 4
    ],
    [
        'cat_key' => 'acid_wash',
        'cat_name' => 'Acid Wash & Vintage',
        'description' => 'Distressed Mineral Washed Heavy Cotton',
        'image' => 'assets/images/products/acid_wash_tee.svg',
        'icon' => 'flame',
        'display_order' => 5
    ],
    [
        'cat_key' => 'bottoms',
        'cat_name' => 'Cargoes & Bottoms',
        'description' => 'Tactical Street Cargo & Parachute Pants',
        'image' => 'assets/images/products/tactical_cargo_black.svg',
        'icon' => 'grid',
        'display_order' => 6
    ]
];

$catStmt = $db->prepare("
    INSERT INTO categories (cat_key, cat_name, description, image, icon, display_order, is_active)
    VALUES (?, ?, ?, ?, ?, ?, 1)
    ON DUPLICATE KEY UPDATE 
        cat_name = VALUES(cat_name),
        description = VALUES(description),
        image = VALUES(image),
        icon = VALUES(icon),
        display_order = VALUES(display_order)
");

foreach ($categories as $c) {
    $catStmt->execute([$c['cat_key'], $c['cat_name'], $c['description'], $c['image'], $c['icon'], $c['display_order']]);
}

// 2. SVG Generator Function
function generateProductSvg($bg, $title, $sub, $badge, $styleType) {
    $art = '';
    if ($styleType === 'dragon') {
        $art = '
        <circle cx="200" cy="200" r="70" fill="#EF4444" opacity="0.25"/>
        <path d="M140 230 Q 200 130 260 230 Q 200 180 140 230 Z" fill="#DC2626"/>
        <circle cx="185" cy="180" r="5" fill="#FEF08A"/>
        <circle cx="215" cy="180" r="5" fill="#FEF08A"/>
        <text x="200" y="270" fill="#F87171" font-family="Impact, sans-serif" font-size="24" font-weight="900" text-anchor="middle" letter-spacing="3">CYBER DRAGON</text>';
    } elseif ($styleType === 'waves') {
        $art = '
        <circle cx="200" cy="190" r="65" fill="#0284C7" opacity="0.3"/>
        <path d="M140 210 Q 170 170 200 210 T 260 210 L 260 240 L 140 240 Z" fill="#38BDF8"/>
        <circle cx="200" cy="160" r="25" fill="#F97316"/>
        <text x="200" y="275" fill="#BAE6FD" font-family="Impact, sans-serif" font-size="22" font-weight="bold" text-anchor="middle" letter-spacing="2">AESTHETIC WAVES</text>';
    } elseif ($styleType === 'motors') {
        $art = '
        <rect x="140" y="160" width="120" height="70" rx="8" fill="#F59E0B" opacity="0.3"/>
        <text x="200" y="195" fill="#FBBF24" font-family="Impact, sans-serif" font-size="24" font-weight="900" text-anchor="middle">SPEED RACER</text>
        <text x="200" y="225" fill="#FFFFFF" font-family="Arial, sans-serif" font-size="14" font-weight="bold" text-anchor="middle" letter-spacing="3">CUSTOM GARAGE</text>';
    } elseif ($styleType === 'neotokyo') {
        $art = '
        <circle cx="200" cy="190" r="60" fill="#EC4899" opacity="0.25"/>
        <text x="200" y="180" fill="#F43F5E" font-family="Impact, sans-serif" font-size="32" font-weight="900" text-anchor="middle" letter-spacing="2">NEO TOKYO</text>
        <text x="200" y="215" fill="#A855F7" font-family="Arial, sans-serif" font-size="16" font-weight="900" text-anchor="middle" letter-spacing="4">NEW HORIZON</text>';
    } elseif ($styleType === 'phantom') {
        $art = '
        <path d="M120 180 Q 200 120 280 180 L 260 330 L 140 330 Z" fill="#6366F1" opacity="0.2"/>
        <circle cx="200" cy="200" r="45" fill="#4F46E5"/>
        <text x="200" y="275" fill="#A5B4FC" font-family="Impact, sans-serif" font-size="24" font-weight="900" text-anchor="middle" letter-spacing="2">PHANTOM CYBER</text>';
    } elseif ($styleType === 'midnight') {
        $art = '
        <path d="M120 180 Q 200 120 280 180 L 260 330 L 140 330 Z" fill="#1E293B" opacity="0.5"/>
        <text x="200" y="210" fill="#93C5FD" font-family="Impact, sans-serif" font-size="26" font-weight="900" text-anchor="middle" letter-spacing="2">MIDNIGHT CLUB</text>
        <text x="200" y="240" fill="#64748B" font-family="Arial, sans-serif" font-size="14" font-weight="bold" text-anchor="middle">OVERSIZED ZIP HOODIE</text>';
    } elseif ($styleType === 'waffle') {
        $art = '
        <rect x="145" y="150" width="110" height="90" rx="6" fill="rgba(255,255,255,0.08)" stroke="rgba(255,255,255,0.2)" stroke-dasharray="4 4"/>
        <text x="200" y="195" fill="#E2E8F0" font-family="Impact, sans-serif" font-size="22" font-weight="bold" text-anchor="middle">WAFFLE KNIT</text>
        <text x="200" y="220" fill="#94A3B8" font-family="Arial, sans-serif" font-size="12" font-weight="bold" text-anchor="middle" letter-spacing="2">TEXTURED ZIP POLO</text>';
    } elseif ($styleType === 'acid') {
        $art = '
        <circle cx="180" cy="180" r="50" fill="#64748B" opacity="0.4"/>
        <circle cx="220" cy="210" r="45" fill="#475569" opacity="0.4"/>
        <text x="200" y="200" fill="#F1F5F9" font-family="Impact, sans-serif" font-size="26" font-weight="900" text-anchor="middle" letter-spacing="2">VINTAGE ACID</text>
        <text x="200" y="230" fill="#CBD5E1" font-family="Arial, sans-serif" font-size="13" font-weight="bold" text-anchor="middle" letter-spacing="3">MINERAL WASHED</text>';
    } elseif ($styleType === 'cargo') {
        $art = '
        <path d="M140 140 L 170 340 L 195 340 L 195 200 L 205 200 L 205 340 L 230 340 L 260 140 Z" fill="rgba(255,255,255,0.12)" stroke="#94A3B8" stroke-width="2"/>
        <rect x="130" y="210" width="30" height="40" rx="4" fill="#334155"/>
        <rect x="240" y="210" width="30" height="40" rx="4" fill="#334155"/>
        <text x="200" y="375" fill="#E2E8F0" font-family="Impact, sans-serif" font-size="20" font-weight="bold" text-anchor="middle" letter-spacing="2">TACTICAL CARGO</text>';
    } else {
        $art = '
        <text x="200" y="200" fill="#FFFFFF" font-family="Impact, sans-serif" font-size="26" font-weight="900" text-anchor="middle" letter-spacing="2">' . strtoupper($title) . '</text>
        <text x="200" y="235" fill="#93C5FD" font-family="Arial, sans-serif" font-size="14" font-weight="bold" text-anchor="middle">' . $sub . '</text>';
    }

    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 480" width="100%" height="100%">
  <defs>
    <linearGradient id="g_{$styleType}" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="{$bg}"/>
      <stop offset="100%" stop-color="#090d16"/>
    </linearGradient>
  </defs>
  <rect width="400" height="480" fill="url(#g_{$styleType})" rx="16"/>
  <!-- Outline -->
  <path d="M120 90 L160 90 Q200 120 240 90 L280 90 L340 140 L310 190 L280 170 L280 420 L120 420 L120 170 L90 190 L60 140 Z" fill="rgba(255,255,255,0.05)" stroke="rgba(255,255,255,0.12)" stroke-width="2"/>
  {$art}
  <!-- Badge -->
  <rect x="20" y="20" width="110" height="26" rx="13" fill="#2563EB"/>
  <text x="75" y="37" fill="#ffffff" font-family="sans-serif" font-size="11" font-weight="bold" text-anchor="middle">{$badge}</text>
  <!-- Footer Brand -->
  <text x="200" y="455" fill="rgba(255,255,255,0.4)" font-family="sans-serif" font-size="12" font-weight="600" text-anchor="middle" letter-spacing="1">THE STITCH CO. • 100% COMBED COTTON</text>
</svg>
SVG;
}

// Generate SVG files
$svgList = [
    'cyber_dragon_black.svg' => ['#1e1b4b', 'Cyber Dragon', '240 GSM Oversized', 'HOT DROP', 'dragon'],
    'aesthetic_waves_navy.svg' => ['#082f49', 'Aesthetic Waves', 'Boxy Streetwear', 'POPULAR', 'waves'],
    'vintage_motors_charcoal.svg' => ['#18181b', 'Vintage Motors', 'Distressed Graphic', 'TRENDING', 'motors'],
    'neo_tokyo_white.svg' => ['#334155', 'Neo Tokyo Horizons', 'Street Typography', 'NEW ARRIVAL', 'neotokyo'],
    'phantom_cyber_hoodie.svg' => ['#1e1b4b', 'Phantom Cyber Hoodie', '350 GSM Fleece', 'BEST SELLER', 'phantom'],
    'midnight_zip_hoodie.svg' => ['#0f172a', 'Midnight Club Hoodie', 'Boxy Zip-Up', 'WINTER DROP', 'midnight'],
    'waffle_knit_polo.svg' => ['#27272a', 'Waffle Knit Zip Polo', 'Textured Knit Cotton', 'PREMIUM', 'waffle'],
    'resort_stripe_polo.svg' => ['#1e293b', 'Resort Stripe Polo', 'Relaxed Knit Fit', 'SUMMER DROP', 'waffle'],
    'acid_wash_tee.svg' => ['#334155', 'Retro Acid Wash Tee', 'Mineral Grunge Fit', 'LIMITED', 'acid'],
    'mineral_grunge_tee.svg' => ['#1f2937', 'Smokey Grunge Acid Tee', 'Vintage Overdyed', 'HOT DROP', 'acid'],
    'tactical_cargo_black.svg' => ['#09090b', 'Tactical Street Cargo', 'Multi-Pocket Relaxed', 'BEST SELLER', 'cargo'],
    'parachute_pants_olive.svg' => ['#14532d', 'Parachute Street Pants', 'Toggle Hem Street Fit', 'TRENDING', 'cargo']
];

foreach ($svgList as $file => $cfg) {
    file_put_contents(__DIR__ . '/assets/images/products/' . $file, generateProductSvg($cfg[0], $cfg[1], $cfg[2], $cfg[3], $cfg[4]));
}

// 3. Insert Products into Database
$products = [
    // 1. Cyber Dragon Oversized Tee
    [
        'name' => 'Cyber Dragon Oversized T-Shirt',
        'slug' => 'cyber-dragon-oversized-t-shirt',
        'sku' => 'TSC-OS-003',
        'category' => 'oversized',
        'subcategory' => 'Anime & Japanese Streetwear',
        'description' => 'Unleash fierce mythical energy with our Cyber Dragon Oversized Drop-Shoulder Graphic Tee. Crafted from 240 GSM 100% Super Combed French Terry Cotton, bio-washed for an ultra-smooth handfeel and zero shrinkage. High-density Japanese dragon screen print across the back with subtle cyber kanji chest embroidery.',
        'short_description' => 'Heavyweight 240 GSM drop-shoulder boxy tee with high-density Cyber Dragon back artwork.',
        'fabric' => '100% Super Combed Cotton | 240 GSM Bio-Wash French Terry',
        'mrp' => 1399.00,
        'price' => 749.00,
        'discount_percent' => 46,
        'stock' => 50,
        'rating' => 4.9,
        'review_count' => 198,
        'sizes_json' => json_encode(['S', 'M', 'L', 'XL', 'XXL']),
        'colors_json' => json_encode([
            ['name' => 'Midnight Black', 'hex' => '#111111'],
            ['name' => 'Cyber Purple', 'hex' => '#2E1065'],
            ['name' => 'Smokey Grey', 'hex' => '#374151']
        ]),
        'thumbnail' => 'assets/images/products/cyber_dragon_black.svg',
        'images_json' => json_encode(['assets/images/products/cyber_dragon_black.svg']),
        'badge' => 'Hot Drop',
        'is_featured' => 1,
        'is_best_seller' => 1,
        'is_new_arrival' => 1,
        'is_hero' => 0
    ],

    // 2. Aesthetic Waves Oversized Tee
    [
        'name' => 'Aesthetic Waves Boxy Oversized Tee',
        'slug' => 'aesthetic-waves-boxy-oversized-tee',
        'sku' => 'TSC-OS-004',
        'category' => 'oversized',
        'subcategory' => 'Minimal & Art Graphic',
        'description' => 'Ride the retro Japanese aesthetic with the Aesthetic Waves Heavyweight Oversized Tee. Cut with a modern dropped shoulder and boxy torso for that effortless streetwear drape. Built with 240 GSM combed cotton that holds its structure all day long.',
        'short_description' => 'Modern boxy fit graphic tee featuring vaporwave sunset and wave artwork.',
        'fabric' => '100% Bio-Wash Combed Cotton | 240 GSM',
        'mrp' => 1299.00,
        'price' => 699.00,
        'discount_percent' => 46,
        'stock' => 42,
        'rating' => 4.8,
        'review_count' => 112,
        'sizes_json' => json_encode(['S', 'M', 'L', 'XL', 'XXL']),
        'colors_json' => json_encode([
            ['name' => 'Deep Navy', 'hex' => '#0F172A'],
            ['name' => 'Optic White', 'hex' => '#F8FAFC']
        ]),
        'thumbnail' => 'assets/images/products/aesthetic_waves_navy.svg',
        'images_json' => json_encode(['assets/images/products/aesthetic_waves_navy.svg']),
        'badge' => 'Popular',
        'is_featured' => 1,
        'is_best_seller' => 0,
        'is_new_arrival' => 1,
        'is_hero' => 0
    ],

    // 3. Vintage Motors Graphic Tee
    [
        'name' => 'Vintage Speed Motors Graphic Tee',
        'slug' => 'vintage-speed-motors-graphic-tee',
        'sku' => 'TSC-TS-003',
        'category' => 'tshirts',
        'subcategory' => 'Automotive & Vintage',
        'description' => 'Inspired by classic 90s racing culture. Features distressed typography and retro motorsport emblems printed on breathable 200 GSM ring-spun cotton. Perfect for relaxed daily rotation.',
        'short_description' => 'Vintage automotive distressed graphic crew neck t-shirt.',
        'fabric' => '100% Ring-Spun Cotton | 200 GSM Bio-Wash',
        'mrp' => 1099.00,
        'price' => 599.00,
        'discount_percent' => 45,
        'stock' => 35,
        'rating' => 4.7,
        'review_count' => 87,
        'sizes_json' => json_encode(['S', 'M', 'L', 'XL', 'XXL']),
        'colors_json' => json_encode([
            ['name' => 'Washed Charcoal', 'hex' => '#1F2937'],
            ['name' => 'Vintage Khaki', 'hex' => '#78716C']
        ]),
        'thumbnail' => 'assets/images/products/vintage_motors_charcoal.svg',
        'images_json' => json_encode(['assets/images/products/vintage_motors_charcoal.svg']),
        'badge' => 'Trending',
        'is_featured' => 0,
        'is_best_seller' => 1,
        'is_new_arrival' => 0,
        'is_hero' => 0
    ],

    // 4. Neo Tokyo Horizons Crew Tee
    [
        'name' => 'Neo Tokyo Horizons Street Tee',
        'slug' => 'neo-tokyo-horizons-street-tee',
        'sku' => 'TSC-TS-004',
        'category' => 'tshirts',
        'subcategory' => 'Typography & Streetwear',
        'description' => 'Crisp urban typography celebrating futuristic Tokyo architecture. Constructed from durable 210 GSM combed cotton with reinforced neck ribbing.',
        'short_description' => 'Futuristic cyber typography tee in premium heavy cotton.',
        'fabric' => '100% Combed Cotton | 210 GSM',
        'mrp' => 999.00,
        'price' => 549.00,
        'discount_percent' => 45,
        'stock' => 60,
        'rating' => 4.8,
        'review_count' => 94,
        'sizes_json' => json_encode(['S', 'M', 'L', 'XL', 'XXL']),
        'colors_json' => json_encode([
            ['name' => 'Pure White', 'hex' => '#FFFFFF'],
            ['name' => 'Jet Black', 'hex' => '#0A0A0A']
        ]),
        'thumbnail' => 'assets/images/products/neo_tokyo_white.svg',
        'images_json' => json_encode(['assets/images/products/neo_tokyo_white.svg']),
        'badge' => 'New Arrival',
        'is_featured' => 0,
        'is_best_seller' => 0,
        'is_new_arrival' => 1,
        'is_hero' => 0
    ],

    // 5. Phantom Cyber Heavyweight Pullover Hoodie
    [
        'name' => 'Phantom Cyber 350 GSM Pullover Hoodie',
        'slug' => 'phantom-cyber-350-gsm-pullover-hoodie',
        'sku' => 'TSC-HD-002',
        'category' => 'hoodies',
        'subcategory' => 'Heavyweight Hoodies',
        'description' => 'Engineered for extreme winter comfort. Crafted from ultra-dense 350 GSM brushed fleece with double-lined thermal hood, heavyweight cotton drawstrings, and a kangaroo pouch pocket.',
        'short_description' => 'Heavyweight 350 GSM brushed fleece streetwear pullover hoodie.',
        'fabric' => '80% Combed Cotton, 20% Polyester Fleece | 350 GSM',
        'mrp' => 2499.00,
        'price' => 1299.00,
        'discount_percent' => 48,
        'stock' => 30,
        'rating' => 4.9,
        'review_count' => 156,
        'sizes_json' => json_encode(['M', 'L', 'XL', 'XXL']),
        'colors_json' => json_encode([
            ['name' => 'Dark Indigo', 'hex' => '#1E1B4B'],
            ['name' => 'Onyx Black', 'hex' => '#0F172A']
        ]),
        'thumbnail' => 'assets/images/products/phantom_cyber_hoodie.svg',
        'images_json' => json_encode(['assets/images/products/phantom_cyber_hoodie.svg']),
        'badge' => 'Best Seller',
        'is_featured' => 1,
        'is_best_seller' => 1,
        'is_new_arrival' => 1,
        'is_hero' => 0
    ],

    // 6. Midnight Club Oversized Zip Hoodie
    [
        'name' => 'Midnight Club Oversized Zip-Up Hoodie',
        'slug' => 'midnight-club-oversized-zip-up-hoodie',
        'sku' => 'TSC-HD-003',
        'category' => 'hoodies',
        'subcategory' => 'Zip-Up Hoodies',
        'description' => 'The ultimate layering staple. Features a smooth gunmetal custom YKK zipper, dropped shoulders, relaxed oversized fit, and cozy ribbed cuffs.',
        'short_description' => 'Oversized full-zip hoodie with heavy-duty metal hardware and fleece lining.',
        'fabric' => '100% Premium Fleece Cotton | 340 GSM',
        'mrp' => 2699.00,
        'price' => 1399.00,
        'discount_percent' => 48,
        'stock' => 25,
        'rating' => 4.9,
        'review_count' => 138,
        'sizes_json' => json_encode(['S', 'M', 'L', 'XL', 'XXL']),
        'colors_json' => json_encode([
            ['name' => 'Jet Black', 'hex' => '#0F172A'],
            ['name' => 'Heather Grey', 'hex' => '#94A3B8']
        ]),
        'thumbnail' => 'assets/images/products/midnight_zip_hoodie.svg',
        'images_json' => json_encode(['assets/images/products/midnight_zip_hoodie.svg']),
        'badge' => 'Winter Drop',
        'is_featured' => 1,
        'is_best_seller' => 0,
        'is_new_arrival' => 1,
        'is_hero' => 0
    ],

    // 7. Waffle Knit Textured Zip Polo
    [
        'name' => 'Textured Waffle Knit Quarter-Zip Polo',
        'slug' => 'textured-waffle-knit-quarter-zip-polo',
        'sku' => 'TSC-PL-002',
        'category' => 'polo',
        'subcategory' => 'Textured Polos',
        'description' => 'Modern luxury meets casual ease. Knitted from breathable textured waffle cotton with a brushed antique silver quarter-zip and structured ribbed collar.',
        'short_description' => 'Luxury waffle-knit textured polo with quarter-zip closure.',
        'fabric' => '100% Pique Waffle Knit Cotton | 240 GSM',
        'mrp' => 1699.00,
        'price' => 949.00,
        'discount_percent' => 44,
        'stock' => 32,
        'rating' => 4.7,
        'review_count' => 78,
        'sizes_json' => json_encode(['S', 'M', 'L', 'XL', 'XXL']),
        'colors_json' => json_encode([
            ['name' => 'Charcoal Heather', 'hex' => '#27272A'],
            ['name' => 'Desert Sand', 'hex' => '#D4D4D8']
        ]),
        'thumbnail' => 'assets/images/products/waffle_knit_polo.svg',
        'images_json' => json_encode(['assets/images/products/waffle_knit_polo.svg']),
        'badge' => 'Premium Knit',
        'is_featured' => 0,
        'is_best_seller' => 0,
        'is_new_arrival' => 1,
        'is_hero' => 0
    ],

    // 8. Retro Acid Wash Oversized Tee
    [
        'name' => 'Retro Acid Wash Mineral Oversized Tee',
        'slug' => 'retro-acid-wash-mineral-oversized-tee',
        'sku' => 'TSC-AW-001',
        'category' => 'acid_wash',
        'subcategory' => 'Acid Wash Drops',
        'description' => 'Individually dyed using artisanal mineral wash techniques for a one-of-a-kind vintage fade. 240 GSM heavyweight combed cotton with raw streetwear character.',
        'short_description' => 'Unique mineral washed heavyweight boxy graphic tee.',
        'fabric' => '100% Mineral-Washed Heavy Cotton | 240 GSM',
        'mrp' => 1499.00,
        'price' => 799.00,
        'discount_percent' => 46,
        'stock' => 38,
        'rating' => 4.9,
        'review_count' => 165,
        'sizes_json' => json_encode(['S', 'M', 'L', 'XL', 'XXL']),
        'colors_json' => json_encode([
            ['name' => 'Acid Charcoal', 'hex' => '#334155'],
            ['name' => 'Acid Olive', 'hex' => '#3F4A3C']
        ]),
        'thumbnail' => 'assets/images/products/acid_wash_tee.svg',
        'images_json' => json_encode(['assets/images/products/acid_wash_tee.svg']),
        'badge' => 'Limited Drop',
        'is_featured' => 1,
        'is_best_seller' => 1,
        'is_new_arrival' => 1,
        'is_hero' => 0
    ],

    // 9. Smokey Grunge Mineral Tee
    [
        'name' => 'Smokey Mineral Grunge Heavy Tee',
        'slug' => 'smokey-mineral-grunge-heavy-tee',
        'sku' => 'TSC-AW-002',
        'category' => 'acid_wash',
        'subcategory' => 'Acid Wash Drops',
        'description' => 'Grunge aesthetic with subtle distress detailing and an oversized vintage silhouette. Built with premium 230 GSM combed cotton.',
        'short_description' => 'Vintage overdyed smokey mineral streetwear tee.',
        'fabric' => '100% Combed Cotton | 230 GSM Bio-Wash',
        'mrp' => 1399.00,
        'price' => 749.00,
        'discount_percent' => 46,
        'stock' => 40,
        'rating' => 4.8,
        'review_count' => 92,
        'sizes_json' => json_encode(['S', 'M', 'L', 'XL', 'XXL']),
        'colors_json' => json_encode([
            ['name' => 'Smokey Black', 'hex' => '#1F2937']
        ]),
        'thumbnail' => 'assets/images/products/mineral_grunge_tee.svg',
        'images_json' => json_encode(['assets/images/products/mineral_grunge_tee.svg']),
        'badge' => 'Hot Drop',
        'is_featured' => 0,
        'is_best_seller' => 0,
        'is_new_arrival' => 1,
        'is_hero' => 0
    ],

    // 10. Multi-Pocket Tactical Street Cargo Pants
    [
        'name' => 'Multi-Pocket Tactical Street Cargo Pants',
        'slug' => 'multi-pocket-tactical-street-cargo-pants',
        'sku' => 'TSC-BT-001',
        'category' => 'bottoms',
        'subcategory' => 'Cargo Pants',
        'description' => 'Engineered for form and function. Constructed from durable high-density cotton twill with 6 tactical cargo pockets, elasticated waistband with drawstring, and adjustable ankle toggles.',
        'short_description' => 'Heavyweight cotton twill tactical cargo pants with 6 pockets and ankle toggles.',
        'fabric' => '100% Cotton Heavyweight Twill | 280 GSM',
        'mrp' => 2499.00,
        'price' => 1299.00,
        'discount_percent' => 48,
        'stock' => 35,
        'rating' => 4.9,
        'review_count' => 184,
        'sizes_json' => json_encode(['30', '32', '34', '36', '38']),
        'colors_json' => json_encode([
            ['name' => 'Stealth Black', 'hex' => '#0A0A0A'],
            ['name' => 'Military Olive', 'hex' => '#2E382E'],
            ['name' => 'Desert Khaki', 'hex' => '#78716C']
        ]),
        'thumbnail' => 'assets/images/products/tactical_cargo_black.svg',
        'images_json' => json_encode(['assets/images/products/tactical_cargo_black.svg']),
        'badge' => 'Best Seller',
        'is_featured' => 1,
        'is_best_seller' => 1,
        'is_new_arrival' => 1,
        'is_hero' => 0
    ],

    // 11. Relaxed Fit Parachute Street Pants
    [
        'name' => 'Relaxed Fit Parachute Street Pants',
        'slug' => 'relaxed-fit-parachute-street-pants',
        'sku' => 'TSC-BT-002',
        'category' => 'bottoms',
        'subcategory' => 'Parachute Pants',
        'description' => 'Lightweight parachute pants with an exaggerated relaxed drape, knee pleating for ergonomic movement, and bungee cord adjusters at the waist and cuffs.',
        'short_description' => 'Ultra-comfortable parachute track pants with toggle hem adjustments.',
        'fabric' => 'Durable Water-Resistant Cotton-Nylon Blend',
        'mrp' => 2299.00,
        'price' => 1199.00,
        'discount_percent' => 47,
        'stock' => 28,
        'rating' => 4.8,
        'review_count' => 120,
        'sizes_json' => json_encode(['S', 'M', 'L', 'XL']),
        'colors_json' => json_encode([
            ['name' => 'Olive Drab', 'hex' => '#14532D'],
            ['name' => 'Matte Black', 'hex' => '#111827']
        ]),
        'thumbnail' => 'assets/images/products/parachute_pants_olive.svg',
        'images_json' => json_encode(['assets/images/products/parachute_pants_olive.svg']),
        'badge' => 'Trending',
        'is_featured' => 0,
        'is_best_seller' => 0,
        'is_new_arrival' => 1,
        'is_hero' => 0
    ]
];

$prodStmt = $db->prepare("
    INSERT INTO products (
        name, slug, sku, category, subcategory, description, short_description,
        fabric, mrp, price, discount_percent, stock, rating, review_count,
        sizes_json, colors_json, thumbnail, images_json, badge,
        is_featured, is_best_seller, is_new_arrival, is_hero, is_active
    ) VALUES (
        :name, :slug, :sku, :category, :subcategory, :description, :short_description,
        :fabric, :mrp, :price, :discount_percent, :stock, :rating, :review_count,
        :sizes_json, :colors_json, :thumbnail, :images_json, :badge,
        :is_featured, :is_best_seller, :is_new_arrival, :is_hero, 1
    )
    ON DUPLICATE KEY UPDATE
        name = VALUES(name),
        category = VALUES(category),
        subcategory = VALUES(subcategory),
        description = VALUES(description),
        short_description = VALUES(short_description),
        fabric = VALUES(fabric),
        mrp = VALUES(mrp),
        price = VALUES(price),
        discount_percent = VALUES(discount_percent),
        stock = VALUES(stock),
        rating = VALUES(rating),
        review_count = VALUES(review_count),
        sizes_json = VALUES(sizes_json),
        colors_json = VALUES(colors_json),
        thumbnail = VALUES(thumbnail),
        images_json = VALUES(images_json),
        badge = VALUES(badge),
        is_featured = VALUES(is_featured),
        is_best_seller = VALUES(is_best_seller),
        is_new_arrival = VALUES(is_new_arrival)
");

$insertedCount = 0;
foreach ($products as $p) {
    $prodStmt->execute($p);
    $insertedCount++;
}

echo "Successfully seeded {$insertedCount} new products and updated all categories!\n";
