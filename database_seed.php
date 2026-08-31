<?php
/**
 * Database Seeder for The Stitch Co.
 */
require_once __DIR__ . '/config/database.php';

$db = get_db();

// Clear and seed Products
$db->exec("DELETE FROM products;");

$products = [
    [
        'name' => 'Tokyo Vibes Oversized T-Shirt',
        'slug' => 'tokyo-vibes-oversized-t-shirt',
        'sku' => 'TSC-TS-001',
        'category' => 'oversized',
        'subcategory' => 'Anime & Japanese Streetwear',
        'description' => 'Unleash urban cyberpunk aesthetics with our signature Tokyo Vibes Oversized Graphic T-Shirt. Engineered with ultra-soft 240 GSM French Terry combed cotton, bio-washed for lasting softness. Featuring vibrant, fade-resistant screen printed back artwork and a drop-shoulder boxy silhouette designed for ultimate street presence.',
        'short_description' => 'Heavyweight 240 GSM oversized graphic tee with high-definition Tokyo cyberpunk back print.',
        'fabric' => '100% Super Combed Cotton | 240 GSM Bio Wash',
        'mrp' => 1299.00,
        'price' => 699.00,
        'discount_percent' => 46,
        'stock' => 45,
        'rating' => 4.9,
        'review_count' => 184,
        'sizes_json' => json_encode(['S', 'M', 'L', 'XL', 'XXL']),
        'colors_json' => json_encode([
            ['name' => 'Black', 'hex' => '#111111'],
            ['name' => 'Beige', 'hex' => '#D9C5B2'],
            ['name' => 'Charcoal', 'hex' => '#2D3748']
        ]),
        'thumbnail' => 'assets/images/products/tokyo_vibes_black.jpg',
        'images_json' => json_encode([
            'assets/images/products/tokyo_vibes_black.jpg',
            'assets/images/products/tokyo_vibes_back.jpg',
            'assets/images/products/tokyo_vibes_model.jpg'
        ]),
        'badge' => 'Best Seller',
        'is_featured' => 1,
        'is_best_seller' => 1,
        'is_new_arrival' => 1,
        'is_hero' => 1
    ],
    [
        'name' => 'Blissful Mind Oversized T-Shirt',
        'slug' => 'blissful-mind-oversized-t-shirt',
        'sku' => 'TSC-TS-002',
        'category' => 'oversized',
        'subcategory' => 'Minimal & Art Graphic',
        'description' => 'Embrace effortless chill vibes with the Blissful Mind dripping smiley graphic tee. Tailored in an oversized boxy cut from heavyweight 220 GSM 100% bio-wash cotton. Pairs seamlessly with cargo pants, baggy denim, and streetwear sneakers.',
        'short_description' => 'Dripping smiley back graphic on premium earthy beige 220 GSM cotton.',
        'fabric' => '100% Bio-Wash Combed Cotton | 220 GSM',
        'mrp' => 1199.00,
        'price' => 699.00,
        'discount_percent' => 42,
        'stock' => 38,
        'rating' => 4.8,
        'review_count' => 142,
        'sizes_json' => json_encode(['S', 'M', 'L', 'XL', 'XXL']),
        'colors_json' => json_encode([
            ['name' => 'Beige', 'hex' => '#D9C5B2'],
            ['name' => 'Black', 'hex' => '#111111'],
            ['name' => 'Olive Green', 'hex' => '#3B4D3C']
        ]),
        'thumbnail' => 'assets/images/products/blissful_mind_beige.jpg',
        'images_json' => json_encode([
            'assets/images/products/blissful_mind_beige.jpg',
            'assets/images/products/blissful_mind_back.jpg'
        ]),
        'badge' => 'Trending',
        'is_featured' => 1,
        'is_best_seller' => 1,
        'is_new_arrival' => 0,
        'is_hero' => 1
    ],
    [
        'name' => 'Chaos Club Oversized T-Shirt',
        'slug' => 'chaos-club-oversized-t-shirt',
        'sku' => 'TSC-TS-003',
        'category' => 'oversized',
        'subcategory' => 'Dark Streetwear',
        'description' => 'The definitive statement piece for street culture lovers. The Chaos Club tee showcases our stitched teddy bear emblem with distressed typography on deep forest green fabric.',
        'short_description' => 'Forest green oversized graphic tee featuring iconic grunge Chaos Club teddy.',
        'fabric' => '100% Super Combed Cotton | 240 GSM Bio Wash',
        'mrp' => 1249.00,
        'price' => 699.00,
        'discount_percent' => 44,
        'stock' => 29,
        'rating' => 4.9,
        'review_count' => 98,
        'sizes_json' => json_encode(['S', 'M', 'L', 'XL', 'XXL']),
        'colors_json' => json_encode([
            ['name' => 'Forest Green', 'hex' => '#2D4A3E'],
            ['name' => 'Black', 'hex' => '#111111']
        ]),
        'thumbnail' => 'assets/images/products/chaos_club_green.jpg',
        'images_json' => json_encode([
            'assets/images/products/chaos_club_green.jpg',
            'assets/images/products/chaos_club_back.jpg'
        ]),
        'badge' => 'Hot Drop',
        'is_featured' => 1,
        'is_best_seller' => 1,
        'is_new_arrival' => 1,
        'is_hero' => 0
    ],
    [
        'name' => 'Good Vibes Only Oversized T-Shirt',
        'slug' => 'good-vibes-only-oversized-t-shirt',
        'sku' => 'TSC-TS-004',
        'category' => 'tshirts',
        'subcategory' => 'Graphic Tees',
        'description' => 'Infuse positivity into your wardrobe with the Good Vibes Only oversized fit tee. Pristine optic white fabric with multi-color graffiti typography and golden crown details.',
        'short_description' => 'Clean white boxy tee with vibrant typography and crown graphic.',
        'fabric' => '100% Super Combed Cotton | 200 GSM',
        'mrp' => 1199.00,
        'price' => 699.00,
        'discount_percent' => 42,
        'stock' => 50,
        'rating' => 4.7,
        'review_count' => 115,
        'sizes_json' => json_encode(['S', 'M', 'L', 'XL', 'XXL']),
        'colors_json' => json_encode([
            ['name' => 'Optic White', 'hex' => '#F8F9FA'],
            ['name' => 'Cream', 'hex' => '#F5F2EB']
        ]),
        'thumbnail' => 'assets/images/products/good_vibes_white.jpg',
        'images_json' => json_encode([
            'assets/images/products/good_vibes_white.jpg'
        ]),
        'badge' => 'Popular',
        'is_featured' => 1,
        'is_best_seller' => 1,
        'is_new_arrival' => 0,
        'is_hero' => 0
    ],
    [
        'name' => 'Minimal Club Oversized T-Shirt',
        'slug' => 'minimal-club-oversized-t-shirt',
        'sku' => 'TSC-TS-005',
        'category' => 'tshirts',
        'subcategory' => 'Minimal Streetwear',
        'description' => 'For those who prefer subtle edge over loud graphics. The Minimal Club tee features understated collegiate typography with premium heavy ribbed crew collar.',
        'short_description' => 'Understated typography on ultra-comfortable breathable 200 GSM ring spun cotton.',
        'fabric' => '100% Ring Spun Cotton | 200 GSM',
        'mrp' => 1099.00,
        'price' => 599.00,
        'discount_percent' => 45,
        'stock' => 32,
        'rating' => 4.8,
        'review_count' => 86,
        'sizes_json' => json_encode(['S', 'M', 'L', 'XL', 'XXL']),
        'colors_json' => json_encode([
            ['name' => 'Black', 'hex' => '#111111'],
            ['name' => 'Charcoal Grey', 'hex' => '#333333']
        ]),
        'thumbnail' => 'assets/images/products/minimal_club_black.jpg',
        'images_json' => json_encode([
            'assets/images/products/minimal_club_black.jpg'
        ]),
        'badge' => 'Essential',
        'is_featured' => 0,
        'is_best_seller' => 0,
        'is_new_arrival' => 1,
        'is_hero' => 0
    ],
    [
        'name' => 'Stay Wild Heavyweight Hoodie',
        'slug' => 'stay-wild-heavyweight-hoodie',
        'sku' => 'TSC-HD-001',
        'category' => 'hoodies',
        'subcategory' => 'Fleece Hoodies',
        'description' => 'Built for cold nights and urban wanderers. Crafted from thick 350 GSM brushed fleece cotton, featuring our iconic Stay Wild teddy bear back print, kangaroo pocket, and double-lined hood.',
        'short_description' => 'Ultra-warm 350 GSM brushed fleece oversized hoodie with teddy streetwear print.',
        'fabric' => '350 GSM Brushed Fleece Cotton | Anti-Pilling',
        'mrp' => 2499.00,
        'price' => 1499.00,
        'discount_percent' => 40,
        'stock' => 22,
        'rating' => 5.0,
        'review_count' => 76,
        'sizes_json' => json_encode(['S', 'M', 'L', 'XL']),
        'colors_json' => json_encode([
            ['name' => 'Cream', 'hex' => '#F4EFEA'],
            ['name' => 'Black', 'hex' => '#111111']
        ]),
        'thumbnail' => 'assets/images/products/stay_wild_cream.jpg',
        'images_json' => json_encode([
            'assets/images/products/stay_wild_cream.jpg'
        ]),
        'badge' => 'Winter Special',
        'is_featured' => 1,
        'is_best_seller' => 1,
        'is_new_arrival' => 1,
        'is_hero' => 1
    ],
    [
        'name' => 'Signature Stitch Knit Polo',
        'slug' => 'signature-stitch-knit-polo',
        'sku' => 'TSC-PL-001',
        'category' => 'polo',
        'subcategory' => 'Structured Polos',
        'description' => 'Elevate your everyday wardrobe with our structured knit polo. Featuring a modern ribbed collar, two-button placket, and subtle embroidered stitch emblem on chest.',
        'short_description' => 'Premium textured pique cotton polo with structured ribbed collar.',
        'fabric' => '100% Pique Combed Cotton Knit | 220 GSM',
        'mrp' => 1499.00,
        'price' => 899.00,
        'discount_percent' => 40,
        'stock' => 28,
        'rating' => 4.6,
        'review_count' => 64,
        'sizes_json' => json_encode(['S', 'M', 'L', 'XL', 'XXL']),
        'colors_json' => json_encode([
            ['name' => 'Olive Green', 'hex' => '#3D4F41'],
            ['name' => 'Navy Blue', 'hex' => '#1E293B']
        ]),
        'thumbnail' => 'assets/images/products/stitch_polo_olive.jpg',
        'images_json' => json_encode([
            'assets/images/products/stitch_polo_olive.jpg'
        ]),
        'badge' => 'Premium Knit',
        'is_featured' => 0,
        'is_best_seller' => 0,
        'is_new_arrival' => 1,
        'is_hero' => 0
    ]
];

$stmt = $db->prepare("
    INSERT INTO products (
        name, slug, sku, category, subcategory, description, short_description, 
        fabric, mrp, price, discount_percent, stock, rating, review_count, 
        sizes_json, colors_json, thumbnail, images_json, badge, 
        is_featured, is_best_seller, is_new_arrival, is_hero
    ) VALUES (
        :name, :slug, :sku, :category, :subcategory, :description, :short_description, 
        :fabric, :mrp, :price, :discount_percent, :stock, :rating, :review_count, 
        :sizes_json, :colors_json, :thumbnail, :images_json, :badge, 
        :is_featured, :is_best_seller, :is_new_arrival, :is_hero
    )
");

foreach ($products as $p) {
    $stmt->execute($p);
}

// Clear and seed Hero Banners
$db->exec("DELETE FROM hero_banners;");
$bannerStmt = $db->prepare("
    INSERT INTO hero_banners (title, subtitle, tag, badge_text, button_text, button_url, image, display_order, is_active)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
");
$bannerStmt->execute([
    'OVERSIZED T-SHIRTS',
    'Premium Quality | 180 GSM | 100% Cotton',
    'NEW ARRIVALS',
    'WEAR YOUR VIBE',
    'SHOP NOW',
    'shop.php?cat=oversized',
    'assets/images/banners/hero_oversized.jpg',
    1
]);

echo "Database seeded successfully with " . count($products) . " products and hero banner.\n";
