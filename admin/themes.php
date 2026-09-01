<?php
/**
 * Admin Theme Management Studio
 * 1-Click Storewide Theme Customizer & Festive Engine
 * The Stitch Co.
 */

$adminTitle = 'Themes & Festive Studio';
require_once __DIR__ . '/header.php';

$db = get_db();
$activeTheme = get_setting('active_theme', 'default');
$particlesEnabled = (int)get_setting('theme_particles_enabled', '1');
$soundEnabled = (int)get_setting('theme_sound_enabled', '1');

$themes = [
    'default' => [
        'name' => 'Obsidian Liquid Glass',
        'tag' => 'DEFAULT / ALL-SEASON',
        'icon' => 'ðŸ–¤',
        'desc' => 'Signature modern aesthetic with high-definition frosted liquid glass, deep charcoal tones, and cobalt blue accents.',
        'colors' => ['#0F172A', '#1E3A8A', '#2563EB', '#F4F6FB'],
        'particles' => 'Subtle ambient light refraction',
        'season' => 'All year round streetwear drops'
    ],
    'durga_puja' => [
        'name' => 'Durga Puja (Pujor Mahotsav)',
        'tag' => 'FESTIVE MAHALAYA / AGOMONI',
        'icon' => 'ðŸª”',
        'desc' => 'Traditional vermillion crimson, celebratory gold accents, festive announcement bar, and floating Kash Phool & Marigold flower petals.',
        'colors' => ['#991B1B', '#DC2626', '#D97706', '#FEF08A'],
        'particles' => 'Floating Kash Phool reeds & Marigold petals',
        'season' => 'Pujo Festival season & exclusive drops'
    ],
    'diwali' => [
        'name' => 'Diwali (Festival of Lights)',
        'tag' => 'DEEPAVALI CELEBRATION',
        'icon' => 'âœ¨',
        'desc' => 'Royal deep midnight purple, glowing golden diya sparks, festive discounts, and ascending firework particle embers.',
        'colors' => ['#4C1D95', '#6D28D9', '#F59E0B', '#FAF5FF'],
        'particles' => 'Ascending golden diya sparks & starbursts',
        'season' => 'Diwali mega sale & festive collections'
    ],
    'winter' => [
        'name' => 'Winter (Frost & Blizzard)',
        'tag' => 'COLD WEATHER & HOODIES',
        'icon' => 'â„ï¸',
        'desc' => 'Arctic Cyan and icy frost blue palette, crystalline borders, and a smooth multi-layered snowfall particle engine.',
        'colors' => ['#0369A1', '#0284C7', '#38BDF8', '#F0F9FF'],
        'particles' => 'Realistic falling snowflakes with wind drift',
        'season' => 'Winter hoodies, heavy tees & jackets'
    ],
    'christmas' => [
        'name' => 'Christmas (Yuletide Noel)',
        'tag' => 'HOLIDAY SALE & NEW YEAR',
        'icon' => 'ðŸŽ„',
        'desc' => 'Rich pine emerald green, ruby velvet crimson, holiday gold, festive greeting banners, and gentle holiday snowfall.',
        'colors' => ['#15803D', '#DC2626', '#EAB308', '#F0FDF4'],
        'particles' => 'Gentle holiday snowfall & starbursts',
        'season' => 'End of year holiday drops & gift shopping'
    ],
    'freedom' => [
        'name' => 'Freedom (Tiranga Spirit)',
        'tag' => 'INDEPENDENCE & REPUBLIC DAY',
        'icon' => 'ðŸ‡®ðŸ‡³',
        'desc' => 'Proud Indian Tiranga tricolor theme with saffron, pure white, and emerald green ribbons with Ashoka navy blue accents.',
        'colors' => ['#EA580C', '#FFFFFF', '#15803D', '#1E3A8A'],
        'particles' => 'Floating saffron, white & green confetti ribbons',
        'season' => '15th August & 26th January celebration'
    ],
    'summer' => [
        'name' => 'Summer (Solar Wave & Sunset)',
        'tag' => 'HOT WEATHER & ACID WASH',
        'icon' => 'â˜€ï¸',
        'desc' => 'Warm coral and sun-kissed golden amber gradients with energetic solar embers and breathable summer streetwear vibes.',
        'colors' => ['#C2410C', '#EA580C', '#F59E0B', '#FFFBEB'],
        'particles' => 'Floating solar embers & heatwave glow',
        'season' => 'Summer tees, oversized drops & polos'
    ]
];
?>

<div class="admin-content" style="padding: 2rem;">
    <!-- Top Header & Current Theme Status Banner -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2 style="font-family: var(--font-heading); font-size: 1.6rem; font-weight: 900; color: #0F172A; margin: 0; text-transform: uppercase;">
                ðŸŽ¨ Themes & Festive Studio
            </h2>
            <p style="font-size: 0.86rem; color: #64748B; margin-top: 0.2rem;">
                Transform your storefront in 1-click with custom festive color palettes, seasonal banners, and 60fps particle physics.
            </p>
        </div>
        <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="../index.php" target="_blank" class="btn-fintech-pill" style="text-decoration: none;">
                <span class="btn-icon-badge badge-blue">ðŸ‘ï¸</span>
                <span>Open Live Store</span>
            </a>
        </div>
    </div>

    <!-- Active Theme Controller Bar -->
    <div style="background: rgba(255, 255, 255, 0.9); border: 1.5px solid #E2E8F0; border-radius: 16px; padding: 1.25rem 1.5rem; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.2rem; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <div style="width: 50px; height: 50px; border-radius: 14px; background: #0F172A; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.6rem;">
                <?= $themes[$activeTheme]['icon'] ?? 'ðŸŽ¨' ?>
            </div>
            <div>
                <div style="font-size: 0.72rem; font-weight: 800; color: #16A34A; text-transform: uppercase; letter-spacing: 1px;">
                    â— LIVE ON STOREFRONT
                </div>
                <div style="font-family: var(--font-heading); font-size: 1.25rem; font-weight: 900; color: #0F172A;">
                    <?= e($themes[$activeTheme]['name'] ?? 'Default') ?>
                </div>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">            <!-- Particles Toggle -->
            <label style="display: flex; align-items: center; gap: 0.6rem; cursor: pointer; font-size: 0.85rem; font-weight: 700; color: #334155; background: #F8FAFC; padding: 0.45rem 0.9rem; border-radius: 10px; border: 1.5px solid #E2E8F0;">
                <input type="checkbox" id="particles-toggle" <?= $particlesEnabled ? 'checked' : '' ?> onchange="toggleParticles(this.checked)" style="width: 18px; height: 18px; cursor: pointer;">
                <span>âœ¨ Particle Physics (60fps)</span>
            </label>
        </div>
    </div>

    <!-- Themes Gallery Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
        <?php foreach ($themes as $key => $thm): 
            $isActive = ($key === $activeTheme);
        ?>
            <div style="background: rgba(255, 255, 255, 0.9); border: 2px solid <?= $isActive ? '#2563EB' : '#E2E8F0' ?>; border-radius: 18px; padding: 1.5rem; display: flex; flex-direction: column; justify-content: space-between; box-shadow: <?= $isActive ? '0 15px 35px -5px rgba(37, 99, 235, 0.2)' : '0 8px 20px -5px rgba(0,0,0,0.04)' ?>; transition: all 0.25s ease; position: relative;">
                
                <?php if ($isActive): ?>
                    <span style="position: absolute; top: 12px; right: 12px; background: #2563EB; color: #FFFFFF; font-size: 0.68rem; font-weight: 900; padding: 0.25rem 0.75rem; border-radius: 20px; letter-spacing: 0.5px;">
                        âœ“ ACTIVE NOW
                    </span>
                <?php endif; ?>

                <div>
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.6rem;">
                        <span style="font-size: 1.8rem;"><?= $thm['icon'] ?></span>
                        <div>
                            <span style="font-size: 0.65rem; font-weight: 900; color: #64748B; text-transform: uppercase; letter-spacing: 0.8px;">
                                <?= $thm['tag'] ?>
                            </span>
                            <h3 style="font-family: var(--font-heading); font-size: 1.15rem; font-weight: 900; color: #0F172A; margin: 0;">
                                <?= $thm['name'] ?>
                            </h3>
                        </div>
                    </div>

                    <p style="font-size: 0.82rem; color: #475569; line-height: 1.5; margin-bottom: 1.2rem;">
                        <?= $thm['desc'] ?>
                    </p>

                    <!-- Color Swatches -->
                    <div style="margin-bottom: 1rem;">
                        <div style="font-size: 0.7rem; font-weight: 800; color: #94A3B8; text-transform: uppercase; margin-bottom: 0.4rem; letter-spacing: 0.5px;">
                            Color Palette
                        </div>
                        <div style="display: flex; gap: 0.5rem;">
                            <?php foreach ($thm['colors'] as $c): ?>
                                <div style="width: 28px; height: 28px; border-radius: 8px; background: <?= $c ?>; border: 1.5px solid rgba(0,0,0,0.1); box-shadow: 0 2px 5px rgba(0,0,0,0.1);" title="<?= $c ?>"></div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Special Effects & Sound Motif -->
                    <div style="font-size: 0.75rem; color: #334155; margin-bottom: 0.8rem; background: #F8FAFC; padding: 0.6rem 0.8rem; border-radius: 8px; border: 1px solid #E2E8F0;">
                        âœ¨ <strong>Particles:</strong> <?= $thm['particles'] ?>
                    </div>

                    <!-- Test Sound FX Button -->
                    <div style="margin-bottom: 1.2rem;">
                        <button type="button" onclick="window.playThemeSound('<?= $key ?>')" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.4rem; background: #F1F5F9; border: 1.5px solid #CBD5E1; color: #0F172A; padding: 0.45rem; border-radius: 10px; font-size: 0.76rem; font-weight: 800; cursor: pointer; transition: all 0.2s ease;">
                            <span>ðŸŽµ</span>
                            <span>Test <?= explode(' ', $thm['name'])[0] ?> Audio Motif</span>
                        </button>
                    </div>
                </div>

                <!-- Action Button -->
                <div>
                    <?php if ($isActive): ?>
                        <button disabled class="btn-fintech-pill" style="width: 100%; justify-content: center; background: #10B981 !important; border-color: #10B981 !important; cursor: default;">
                            <span class="btn-icon-badge badge-green">âœ“</span>
                            <span>CURRENTLY ACTIVE</span>
                        </button>
                    <?php else: ?>
                        <button type="button" onclick="activateTheme('<?= $key ?>')" class="btn-fintech-pill" style="width: 100%; justify-content: center;">
                            <span class="btn-icon-badge badge-blue">âš¡</span>
                            <span>ACTIVATE THIS THEME</span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="../assets/js/theme-sound-engine.js?v=<?= time() ?>"></script>

<script>
function activateTheme(themeKey) {
    if (!confirm('Apply the ' + themeKey.toUpperCase() + ' theme to your live store?')) return;

    const formData = new FormData();
    formData.append('action', 'set_active_theme');
    formData.append('theme', themeKey);

    fetch('../api/admin_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Theme updated successfully! Your storefront is now live with the new aesthetic.');
            window.location.reload();
        } else {
            alert(data.message || 'Failed to update theme.');
        }
    })
    .catch((err) => {
        console.error(err);
        alert('Network error while updating theme.');
    });
}

function toggleParticles(enabled) {
    const formData = new FormData();
    formData.append('action', 'toggle_theme_particles');
    formData.append('enabled', enabled ? '1' : '0');

    fetch('../api/admin_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Particle animations ' + (enabled ? 'enabled' : 'disabled'));
        }
    });
}

function toggleSound(enabled) {
    const formData = new FormData();
    formData.append('action', 'toggle_theme_sound');
    formData.append('enabled', enabled ? '1' : '0');

    fetch('../api/admin_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Festive sound effects ' + (enabled ? 'ENABLED' : 'DISABLED') + ' storewide!');
        }
    });
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>

