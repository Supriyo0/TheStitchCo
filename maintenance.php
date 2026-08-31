<?php
/**
 * State-of-the-Art Animated Offline Maintenance Screen
 * The Stitch Co.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$customMsg = get_setting('maintenance_message', 'We are currently upgrading our core infrastructure, fine-tuning checkout performance, and preparing exclusive new streetwear drops.');
$storePhone = get_setting('store_phone', '+91 98765 43210');
$cleanPhone = preg_replace('/[^0-9]/', '', $storePhone);
if (strlen($cleanPhone) === 10) {
    $cleanPhone = '91' . $cleanPhone;
}
$waUrl = "https://wa.me/" . $cleanPhone . "?text=" . urlencode("Hey The Stitch Co. team! Saw the store is under maintenance, when will the drops be back live?");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance in Progress | The Stitch Co.</title>
    <link rel="icon" href="assets/images/logo.jpg" type="image/jpeg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cabinet+Grotesk:wght@800;900&family=Inter:wght@400;600;700;800&family=Outfit:wght@700;900&family=JetBrains+Mono:wght@600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #07090E;
            --accent-blue: #3B82F6;
            --accent-cyan: #06B6D4;
            --accent-red: #EF4444;
            --font-heading: 'Cabinet Grotesk', 'Outfit', sans-serif;
            --font-body: 'Inter', sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-dark);
            color: #F8FAFC;
            font-family: var(--font-body);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated Ambient Background Aura */
        .ambient-glow-1 {
            position: absolute;
            top: -15%;
            left: -10%;
            width: 550px;
            height: 550px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.18) 0%, rgba(7, 9, 14, 0) 70%);
            border-radius: 50%;
            filter: blur(80px);
            animation: pulseGlow 8s infinite alternate ease-in-out;
            pointer-events: none;
        }

        .ambient-glow-2 {
            position: absolute;
            bottom: -15%;
            right: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(239, 68, 68, 0.15) 0%, rgba(7, 9, 14, 0) 70%);
            border-radius: 50%;
            filter: blur(90px);
            animation: pulseGlow 10s infinite alternate-reverse ease-in-out;
            pointer-events: none;
        }

        /* Cyberpunk Grid Mesh */
        .grid-mesh {
            position: absolute;
            inset: 0;
            background-image: linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 40px 40px;
            mask-image: radial-gradient(circle at center, black 40%, transparent 85%);
            -webkit-mask-image: radial-gradient(circle at center, black 40%, transparent 85%);
            pointer-events: none;
        }

        @keyframes pulseGlow {
            0% { transform: scale(0.9) translate(0, 0); opacity: 0.6; }
            100% { transform: scale(1.15) translate(30px, -20px); opacity: 0.9; }
        }

        /* Center Content Container */
        .maintenance-card {
            position: relative;
            z-index: 10;
            max-width: 620px;
            width: 100%;
            background: rgba(15, 23, 42, 0.8);
            border: 1.5px solid rgba(255, 255, 255, 0.12);
            border-radius: 24px;
            padding: 3rem 2.2rem;
            text-align: center;
            box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.8), 0 0 40px rgba(59, 130, 246, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            animation: slideUpFade 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUpFade {
            from { opacity: 0; transform: translateY(20px) scale(0.96); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Brand Logo with Pulsing Aura */
        .brand-logo-wrap {
            position: relative;
            width: 88px;
            height: 88px;
            margin: 0 auto 1.8rem;
        }

        .brand-logo-img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            position: relative;
            z-index: 2;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.6);
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .pulse-ring {
            position: absolute;
            inset: -8px;
            border-radius: 50%;
            border: 2px solid rgba(59, 130, 246, 0.5);
            animation: ringExpand 2.2s infinite cubic-bezier(0.215, 0.61, 0.355, 1);
            z-index: 1;
        }

        @keyframes ringExpand {
            0% { transform: scale(0.9); opacity: 0.8; }
            100% { transform: scale(1.4); opacity: 0; }
        }

        /* Status Pill */
        .status-pill-offline {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 1rem;
            background: rgba(239, 68, 68, 0.12);
            border: 1.5px solid rgba(239, 68, 68, 0.35);
            color: #FCA5A5;
            border-radius: 50px;
            font-family: var(--font-mono);
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            margin-bottom: 1.2rem;
        }

        .status-dot-blink {
            width: 8px;
            height: 8px;
            background: #EF4444;
            border-radius: 50%;
            box-shadow: 0 0 10px #EF4444;
            animation: blinkDot 1.4s infinite alternate ease-in-out;
        }

        @keyframes blinkDot {
            0% { opacity: 0.3; transform: scale(0.8); }
            100% { opacity: 1; transform: scale(1.2); }
        }

        /* Headline & Typography */
        .main-title {
            font-family: var(--font-heading);
            font-size: clamp(1.8rem, 5vw, 2.4rem);
            font-weight: 900;
            line-height: 1.1;
            letter-spacing: -0.5px;
            margin-bottom: 0.85rem;
            background: linear-gradient(135deg, #FFFFFF 0%, #CBD5E1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-transform: uppercase;
        }

        .subtitle {
            font-size: 0.95rem;
            color: #94A3B8;
            line-height: 1.6;
            margin-bottom: 2rem;
            font-weight: 500;
        }

        /* Live Animated Progress Bar */
        .progress-box {
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 1.1rem 1.3rem;
            margin-bottom: 2rem;
            text-align: left;
        }

        .progress-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.78rem;
            font-family: var(--font-mono);
            color: #94A3B8;
            margin-bottom: 0.6rem;
            font-weight: 700;
        }

        .progress-track {
            height: 6px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            overflow: hidden;
            position: relative;
        }

        .progress-bar-fill {
            height: 100%;
            width: 78%;
            background: linear-gradient(90deg, #3B82F6 0%, #06B6D4 100%);
            border-radius: 6px;
            position: relative;
            animation: shimmerBar 2.5s infinite linear;
        }

        @keyframes shimmerBar {
            0% { filter: hue-rotate(0deg); }
            100% { filter: hue-rotate(360deg); }
        }

        /* Action Buttons */
        .action-row {
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
        }

        .btn-whatsapp {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            padding: 0.9rem 1.6rem;
            background: #25D366;
            color: #FFFFFF;
            font-family: var(--font-heading);
            font-size: 0.92rem;
            font-weight: 900;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            text-decoration: none;
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(37, 211, 102, 0.4);
            transition: all 0.25s ease;
        }

        .btn-whatsapp:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px -5px rgba(37, 211, 102, 0.6);
            background: #22c35e;
        }

        .admin-link-discreet {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            font-size: 0.78rem;
            color: #64748B;
            text-decoration: none;
            font-weight: 700;
            margin-top: 1.2rem;
            transition: color 0.2s;
            font-family: var(--font-mono);
        }

        .admin-link-discreet:hover {
            color: #93C5FD;
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .maintenance-card {
                padding: 2.2rem 1.4rem;
            }
            .brand-logo-wrap {
                width: 72px;
                height: 72px;
                margin-bottom: 1.3rem;
            }
            .subtitle {
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <div class="ambient-glow-1"></div>
    <div class="ambient-glow-2"></div>
    <div class="grid-mesh"></div>

    <div class="maintenance-card">
        <!-- Logo & Pulsing Aura -->
        <div class="brand-logo-wrap">
            <div class="pulse-ring"></div>
            <img src="assets/images/logo.jpg" alt="The Stitch Co." class="brand-logo-img">
        </div>

        <!-- Status Badge -->
        <div>
            <div class="status-pill-offline">
                <span class="status-dot-blink"></span>
                <span>System Upgrade in Progress</span>
            </div>
        </div>

        <!-- Headline & Explanation -->
        <h1 class="main-title">Dropping Something Legendary.</h1>
        <p class="subtitle">
            <?= e($customMsg) ?>
        </p>

        <!-- Dynamic Live Progress Box -->
        <div class="progress-box">
            <div class="progress-meta">
                <span>⚡ STORE DEPLOYMENT ENGINE</span>
                <span id="progress-pct" style="color: #60A5FA;">UPGRADING...</span>
            </div>
            <div class="progress-track">
                <div class="progress-bar-fill"></div>
            </div>
        </div>

        <!-- Actions -->
        <div class="action-row">
            <a href="<?= $waUrl ?>" target="_blank" class="btn-whatsapp">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                <span>Ask Support on WhatsApp &rarr;</span>
            </a>
        </div>

        <!-- Secret / Discreet Admin Access Link -->
        <div>
            <a href="admin/login.php" class="admin-link-discreet">
                <span>⚡ Store Administrator Access</span>
            </a>
        </div>
    </div>

    <!-- Automatic Liveness Poller: Reloads page once Admin turns maintenance off -->
    <script>
    setInterval(function() {
        fetch('api/check_status.php')
            .then(res => res.json())
            .then(data => {
                if (data && data.maintenance === false) {
                    window.location.reload();
                }
            })
            .catch(() => {});
    }, 8000);
    </script>
</body>
</html>
