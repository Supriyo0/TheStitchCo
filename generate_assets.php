<?php
/**
 * Vector Asset Generator for The Stitch Co.
 */
require_once __DIR__ . '/config/database.php';

$assets = [
    'assets/images/upi_qr.png' => 'assets/images/upi_qr.svg',
    'assets/images/banners/hero_oversized.jpg' => 'assets/images/banners/hero_oversized.svg',
    'assets/images/products/tokyo_vibes_black.jpg' => 'assets/images/products/tokyo_vibes_black.svg',
    'assets/images/products/tokyo_vibes_back.jpg' => 'assets/images/products/tokyo_vibes_back.svg',
    'assets/images/products/tokyo_vibes_model.jpg' => 'assets/images/products/tokyo_vibes_model.svg',
    'assets/images/products/blissful_mind_beige.jpg' => 'assets/images/products/blissful_mind_beige.svg',
    'assets/images/products/blissful_mind_back.jpg' => 'assets/images/products/blissful_mind_back.svg',
    'assets/images/products/chaos_club_green.jpg' => 'assets/images/products/chaos_club_green.svg',
    'assets/images/products/chaos_club_back.jpg' => 'assets/images/products/chaos_club_back.svg',
    'assets/images/products/good_vibes_white.jpg' => 'assets/images/products/good_vibes_white.svg',
    'assets/images/products/minimal_club_black.jpg' => 'assets/images/products/minimal_club_black.svg',
    'assets/images/products/stay_wild_cream.jpg' => 'assets/images/products/stay_wild_cream.svg',
    'assets/images/products/stitch_polo_olive.jpg' => 'assets/images/products/stitch_polo_olive.svg',
];

// 1. QR Code SVG
$qrSvg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300" width="100%" height="100%">
  <rect width="300" height="300" fill="#ffffff" rx="16"/>
  <!-- Corner QR Patterns -->
  <rect x="25" y="25" width="60" height="60" fill="#000000" rx="8"/>
  <rect x="35" y="35" width="40" height="40" fill="#ffffff" rx="4"/>
  <rect x="45" y="45" width="20" height="20" fill="#000000" rx="2"/>

  <rect x="215" y="25" width="60" height="60" fill="#000000" rx="8"/>
  <rect x="225" y="35" width="40" height="40" fill="#ffffff" rx="4"/>
  <rect x="235" y="45" width="20" height="20" fill="#000000" rx="2"/>

  <rect x="25" y="215" width="60" height="60" fill="#000000" rx="8"/>
  <rect x="35" y="225" width="40" height="40" fill="#ffffff" rx="4"/>
  <rect x="45" y="235" width="20" height="20" fill="#000000" rx="2"/>

  <!-- Matrix Data Grid Dots -->
  <g fill="#111827">
    <rect x="100" y="30" width="12" height="12" rx="2"/>
    <rect x="120" y="30" width="12" height="12" rx="2"/>
    <rect x="150" y="30" width="12" height="12" rx="2"/>
    <rect x="175" y="30" width="12" height="12" rx="2"/>
    <rect x="100" y="55" width="12" height="12" rx="2"/>
    <rect x="135" y="55" width="12" height="12" rx="2"/>
    <rect x="160" y="55" width="12" height="12" rx="2"/>
    <rect x="100" y="75" width="12" height="12" rx="2"/>
    <rect x="180" y="75" width="12" height="12" rx="2"/>

    <rect x="30" y="105" width="12" height="12" rx="2"/>
    <rect x="55" y="105" width="12" height="12" rx="2"/>
    <rect x="80" y="105" width="12" height="12" rx="2"/>
    <rect x="105" y="105" width="12" height="12" rx="2"/>
    <rect x="180" y="105" width="12" height="12" rx="2"/>
    <rect x="210" y="105" width="12" height="12" rx="2"/>
    <rect x="245" y="105" width="12" height="12" rx="2"/>

    <rect x="30" y="130" width="12" height="12" rx="2"/>
    <rect x="65" y="130" width="12" height="12" rx="2"/>
    <rect x="210" y="130" width="12" height="12" rx="2"/>
    <rect x="255" y="130" width="12" height="12" rx="2"/>

    <rect x="30" y="155" width="12" height="12" rx="2"/>
    <rect x="75" y="155" width="12" height="12" rx="2"/>
    <rect x="220" y="155" width="12" height="12" rx="2"/>
    <rect x="250" y="155" width="12" height="12" rx="2"/>

    <rect x="100" y="180" width="12" height="12" rx="2"/>
    <rect x="140" y="180" width="12" height="12" rx="2"/>
    <rect x="175" y="180" width="12" height="12" rx="2"/>
    <rect x="240" y="180" width="12" height="12" rx="2"/>

    <rect x="105" y="215" width="12" height="12" rx="2"/>
    <rect x="135" y="215" width="12" height="12" rx="2"/>
    <rect x="165" y="215" width="12" height="12" rx="2"/>
    <rect x="200" y="215" width="12" height="12" rx="2"/>
    <rect x="235" y="215" width="12" height="12" rx="2"/>

    <rect x="110" y="245" width="12" height="12" rx="2"/>
    <rect x="145" y="245" width="12" height="12" rx="2"/>
    <rect x="180" y="245" width="12" height="12" rx="2"/>
    <rect x="220" y="245" width="12" height="12" rx="2"/>
    <rect x="250" y="245" width="12" height="12" rx="2"/>
  </g>

  <!-- Center Stitch Co Badge -->
  <circle cx="150" cy="150" r="34" fill="#000000" stroke="#ffffff" stroke-width="4"/>
  <text x="150" y="146" fill="#ffffff" font-family="Impact, sans-serif" font-size="10" font-weight="900" text-anchor="middle" letter-spacing="1">THE</text>
  <text x="150" y="158" fill="#3B82F6" font-family="Impact, sans-serif" font-size="12" font-weight="900" text-anchor="middle" letter-spacing="1">STITCH CO.</text>
</svg>
SVG;

file_put_contents(__DIR__ . '/assets/images/upi_qr.svg', $qrSvg);

// 2. Product SVG Generator Helper
function makeProductSvg($bg, $title, $subtitle, $badge, $graphicType = 'anime') {
    $graphic = '';
    if ($graphicType === 'anime') {
        $graphic = '
        <circle cx="200" cy="200" r="70" fill="#2563EB" opacity="0.2"/>
        <path d="M160 220 C 160 160, 240 160, 240 220 Z" fill="#ffffff"/>
        <path d="M150 170 L200 130 L250 170 L230 200 L170 200 Z" fill="#3B82F6"/>
        <circle cx="185" cy="180" r="6" fill="#000000"/>
        <circle cx="215" cy="180" r="6" fill="#000000"/>
        <path d="M190 200 Q 200 210 210 200" stroke="#000000" stroke-width="3" fill="none"/>
        <text x="200" y="260" fill="#ffffff" font-family="Impact, sans-serif" font-size="22" font-weight="bold" text-anchor="middle" letter-spacing="2">TOKYO CYBER</text>';
    } elseif ($graphicType === 'smiley') {
        $graphic = '
        <circle cx="200" cy="200" r="75" fill="#EAB308"/>
        <!-- Dripping Smiley -->
        <ellipse cx="175" cy="180" rx="8" ry="16" fill="#000000"/>
        <ellipse cx="225" cy="180" rx="8" ry="16" fill="#000000"/>
        <path d="M165 220 Q 200 255 235 220" stroke="#000000" stroke-width="8" stroke-linecap="round" fill="none"/>
        <path d="M190 238 C 190 260, 195 270, 195 280 C 195 285, 185 285, 185 280 Z" fill="#000000"/>
        <path d="M210 236 C 210 255, 215 265, 215 275 C 215 280, 205 280, 205 275 Z" fill="#000000"/>
        <text x="200" y="110" fill="#111827" font-family="Impact, sans-serif" font-size="28" font-weight="900" text-anchor="middle" letter-spacing="3">BLISSFUL</text>';
    } elseif ($graphicType === 'bear') {
        $graphic = '
        <!-- Teddy Streetwear -->
        <circle cx="170" cy="160" r="28" fill="#92400E"/>
        <circle cx="230" cy="160" r="28" fill="#92400E"/>
        <circle cx="200" cy="205" r="55" fill="#B45309"/>
        <!-- Eyes X X -->
        <text x="180" y="200" fill="#000000" font-family="Arial, sans-serif" font-size="24" font-weight="900" text-anchor="middle">✕</text>
        <text x="220" y="200" fill="#000000" font-family="Arial, sans-serif" font-size="24" font-weight="900" text-anchor="middle">✕</text>
        <ellipse cx="200" cy="220" rx="16" ry="12" fill="#FDE68A"/>
        <circle cx="200" cy="216" r="6" fill="#000000"/>
        <text x="200" y="280" fill="#FDE68A" font-family="Impact, sans-serif" font-size="26" font-weight="900" text-anchor="middle" letter-spacing="4">CHAOS CLUB</text>';
    } elseif ($graphicType === 'crown') {
        $graphic = '
        <!-- Crown & Good Vibes -->
        <path d="M160 160 L175 135 L200 155 L225 135 L240 160 Z" fill="#F59E0B"/>
        <text x="200" y="210" fill="#DC2626" font-family="Impact, sans-serif" font-size="34" font-weight="900" text-anchor="middle" letter-spacing="1">GOOD</text>
        <text x="200" y="245" fill="#2563EB" font-family="Impact, sans-serif" font-size="34" font-weight="900" text-anchor="middle" letter-spacing="1">VIBES</text>
        <text x="200" y="275" fill="#10B981" font-family="Arial, sans-serif" font-size="20" font-weight="900" text-anchor="middle" letter-spacing="4">ONLY</text>';
    } elseif ($graphicType === 'hoodie') {
        $graphic = '
        <path d="M120 180 Q 200 120 280 180 L 260 330 L 140 330 Z" fill="#E2E8F0" opacity="0.3"/>
        <circle cx="200" cy="210" r="45" fill="#78350F"/>
        <text x="200" y="285" fill="#F59E0B" font-family="Impact, sans-serif" font-size="26" font-weight="900" text-anchor="middle" letter-spacing="2">STAY WILD</text>';
    } else {
        $graphic = '
        <text x="200" y="200" fill="#94A3B8" font-family="Impact, sans-serif" font-size="30" font-weight="900" text-anchor="middle" letter-spacing="3">' . strtoupper($title) . '</text>
        <text x="200" y="240" fill="#3B82F6" font-family="sans-serif" font-size="16" font-weight="bold" text-anchor="middle">OVERSIZED 240 GSM</text>';
    }

    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 480" width="100%" height="100%">
  <defs>
    <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="{$bg}"/>
      <stop offset="100%" stop-color="#0f172a"/>
    </linearGradient>
  </defs>
  <rect width="400" height="480" fill="url(#grad)" rx="16"/>
  <!-- T-Shirt Silhouette Outline -->
  <path d="M120 90 L160 90 Q200 120 240 90 L280 90 L340 140 L310 190 L280 170 L280 420 L120 420 L120 170 L90 190 L60 140 Z" fill="rgba(255,255,255,0.06)" stroke="rgba(255,255,255,0.15)" stroke-width="2"/>
  {$graphic}
  <!-- Badge -->
  <rect x="20" y="20" width="100" height="26" rx="13" fill="#3B82F6"/>
  <text x="70" y="37" fill="#ffffff" font-family="sans-serif" font-size="11" font-weight="bold" text-anchor="middle">{$badge}</text>
  <!-- Footer Brand -->
  <text x="200" y="455" fill="rgba(255,255,255,0.4)" font-family="sans-serif" font-size="12" font-weight="600" text-anchor="middle" letter-spacing="1">THE STITCH CO. • 100% COMBED COTTON</text>
</svg>
SVG;
}

// Generate Product SVGs
file_put_contents(__DIR__ . '/assets/images/products/tokyo_vibes_black.svg', makeProductSvg('#18181b', 'Tokyo Vibes', 'Cyberpunk Streetwear', 'BEST SELLER', 'anime'));
file_put_contents(__DIR__ . '/assets/images/products/tokyo_vibes_back.svg', makeProductSvg('#09090b', 'Tokyo Back Print', 'Cyberpunk Streetwear', 'BACK PRINT', 'anime'));
file_put_contents(__DIR__ . '/assets/images/products/tokyo_vibes_model.svg', makeProductSvg('#27272a', 'Tokyo Model View', 'Cyberpunk Streetwear', 'STREET VIBE', 'anime'));
file_put_contents(__DIR__ . '/assets/images/products/blissful_mind_beige.svg', makeProductSvg('#3f3f46', 'Blissful Mind', 'Dripping Smiley', 'TRENDING', 'smiley'));
file_put_contents(__DIR__ . '/assets/images/products/blissful_mind_back.svg', makeProductSvg('#292524', 'Blissful Mind Back', 'Dripping Smiley', 'OVERSIZED', 'smiley'));
file_put_contents(__DIR__ . '/assets/images/products/chaos_club_green.svg', makeProductSvg('#14532d', 'Chaos Club', 'Teddy Bear Grunge', 'HOT DROP', 'bear'));
file_put_contents(__DIR__ . '/assets/images/products/chaos_club_back.svg', makeProductSvg('#064e3b', 'Chaos Club Back', 'Teddy Bear Grunge', 'LIMITED', 'bear'));
file_put_contents(__DIR__ . '/assets/images/products/good_vibes_white.svg', makeProductSvg('#334155', 'Good Vibes Only', 'Optic White Streetwear', 'POPULAR', 'crown'));
file_put_contents(__DIR__ . '/assets/images/products/minimal_club_black.svg', makeProductSvg('#1e1e24', 'Minimal Club', 'Collegiate Typography', 'ESSENTIAL', 'text'));
file_put_contents(__DIR__ . '/assets/images/products/stay_wild_cream.svg', makeProductSvg('#44403c', 'Stay Wild Hoodie', '350 GSM Fleece', 'WINTER DROP', 'hoodie'));
file_put_contents(__DIR__ . '/assets/images/products/stitch_polo_olive.svg', makeProductSvg('#1c1917', 'Signature Knit Polo', 'Textured Pique Cotton', 'PREMIUM KNIT', 'text'));

// Generate Hero Banner SVG
$heroSvg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 500" width="100%" height="100%">
  <defs>
    <linearGradient id="heroGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#09090b"/>
      <stop offset="50%" stop-color="#18181b"/>
      <stop offset="100%" stop-color="#020617"/>
    </linearGradient>
  </defs>
  <rect width="1200" height="500" fill="url(#heroGrad)"/>
  <!-- Decorative Grid & Glow -->
  <circle cx="950" cy="250" r="220" fill="#1E3A8A" opacity="0.25" filter="blur(40px)"/>
  <circle cx="200" cy="150" r="140" fill="#2563EB" opacity="0.15" filter="blur(30px)"/>
  
  <!-- Content -->
  <rect x="80" y="80" width="160" height="32" rx="16" fill="rgba(37,99,235,0.2)" stroke="#2563EB" stroke-width="1.5"/>
  <text x="160" y="101" fill="#60A5FA" font-family="sans-serif" font-size="13" font-weight="bold" text-anchor="middle" letter-spacing="2">NEW ARRIVALS</text>

  <text x="80" y="180" fill="#ffffff" font-family="Impact, sans-serif" font-size="64" font-weight="900" letter-spacing="3">OVERSIZED</text>
  <text x="80" y="245" fill="#3B82F6" font-family="Impact, sans-serif" font-size="64" font-weight="900" letter-spacing="3">T-SHIRTS</text>

  <text x="80" y="295" fill="#94A3B8" font-family="sans-serif" font-size="18" font-weight="500">Premium Quality | 180-240 GSM | 100% Combed Cotton</text>

  <!-- CTA Button -->
  <rect x="80" y="335" width="180" height="52" rx="26" fill="#ffffff"/>
  <text x="170" y="367" fill="#000000" font-family="sans-serif" font-size="15" font-weight="900" text-anchor="middle" letter-spacing="1">SHOP NOW →</text>

  <!-- Right Graphic Collage Mockup -->
  <g transform="translate(700, 50)">
    <!-- Black Tee Model Box -->
    <rect x="0" y="30" width="220" height="340" rx="16" fill="#18181b" stroke="rgba(255,255,255,0.15)" stroke-width="2"/>
    <text x="110" y="180" fill="#ffffff" font-family="Impact, sans-serif" font-size="28" font-weight="bold" text-anchor="middle">STITCH</text>
    <text x="110" y="210" fill="#3B82F6" font-family="sans-serif" font-size="13" font-weight="bold" text-anchor="middle">CYBERPUNK</text>

    <!-- Beige Teddy Tee Model Box -->
    <rect x="180" y="60" width="220" height="340" rx="16" fill="#27272a" stroke="rgba(255,255,255,0.15)" stroke-width="2"/>
    <circle cx="290" cy="200" r="40" fill="#B45309"/>
    <text x="290" y="260" fill="#FDE68A" font-family="Impact, sans-serif" font-size="20" font-weight="900" text-anchor="middle">STAY WILD</text>
  </g>
</svg>
SVG;
file_put_contents(__DIR__ . '/assets/images/banners/hero_oversized.svg', $heroSvg);

// Update database to use generated SVG assets
$db = get_db();
$db->exec("
    UPDATE products SET thumbnail = REPLACE(thumbnail, '.jpg', '.svg'), images_json = REPLACE(images_json, '.jpg', '.svg');
    UPDATE hero_banners SET image = REPLACE(image, '.jpg', '.svg');
    UPDATE settings SET setting_val = 'assets/images/upi_qr.svg' WHERE setting_key = 'upi_qr_image';
");

echo "All vector SVG assets generated and database records synced successfully!\n";
