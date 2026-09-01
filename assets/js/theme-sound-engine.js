/**
 * The Stitch Co. - Procedural Web Audio API Festive Sound Engine v1.0
 * Pure client-side synthesis: 0 external audio files, 0ms buffering, 100% offline & lightweight.
 * 
 * Themes:
 * - Durga Puja: Rhythmic Dhak Drum Triplet + Mandir Bell Resonance
 * - Diwali: Brass Temple Bell Chime + Sparkling Firework Crackle
 * - Winter: Crystalline Ice Glockenspiel Chime
 * - Christmas: Festive Jingle Bells Double Chime
 * - Freedom: Ceremonial Triumphant Major Fanfare
 * - Summer: Warm Tropical Marimba Pop
 * - Default: Luxury Obsidian Glass Tap / Haptic UI Pop
 */

(function () {
    'use strict';

    let audioCtx = null;
    let soundEnabled = localStorage.getItem('stitch_theme_sound_enabled') !== '0'; // Default ON

    function getAudioContext() {
        if (!audioCtx) {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (AudioContext) {
                audioCtx = new AudioContext();
            }
        }
        if (audioCtx && audioCtx.state === 'suspended') {
            audioCtx.resume();
        }
        return audioCtx;
    }

    // Helper: Create Gain Node with Exponential Decay
    function createEnvelope(ctx, startTime, attack, decay, peakGain) {
        const gain = ctx.createGain();
        gain.gain.setValueAtTime(0.0001, startTime);
        gain.gain.exponentialRampToValueAtTime(peakGain, startTime + attack);
        gain.gain.exponentialRampToValueAtTime(0.0001, startTime + attack + decay);
        return gain;
    }

    // =========================================================================
    // 1. DURGA PUJA: Rhythmic Dhak Drum Triplet + Temple Bell
    // =========================================================================
    function playDurgaPujaSound() {
        const ctx = getAudioContext();
        if (!ctx) return;
        const now = ctx.currentTime + 0.05;

        // Dhak Drum Beat 1: "Dum" (Low resonant bass hit)
        playDhakHit(ctx, now, 110, 0.45);
        // Dhak Drum Beat 2: "Dum"
        playDhakHit(ctx, now + 0.18, 115, 0.5);
        // Dhak Drum Beat 3: "Tadak!" (Sharp stick slap with high snap)
        playDhakSlap(ctx, now + 0.36, 0.6);
        // Temple Mandir Bell Chime (harmonious ending)
        playMandirBell(ctx, now + 0.55, 784, 0.35);
    }

    function playDhakHit(ctx, time, freq, vol) {
        const osc = ctx.createOscillator();
        const gain = createEnvelope(ctx, time, 0.005, 0.22, vol);
        osc.type = 'sine';
        osc.frequency.setValueAtTime(freq, time);
        osc.frequency.exponentialRampToValueAtTime(45, time + 0.18);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start(time);
        osc.stop(time + 0.25);
    }

    function playDhakSlap(ctx, time, vol) {
        // High pitched tone + bandpass noise burst
        const osc = ctx.createOscillator();
        const gain = createEnvelope(ctx, time, 0.002, 0.12, vol);
        osc.type = 'triangle';
        osc.frequency.setValueAtTime(320, time);
        osc.frequency.exponentialRampToValueAtTime(80, time + 0.1);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start(time);
        osc.stop(time + 0.15);

        // Stick snap noise
        createNoiseBurst(ctx, time, 0.08, vol * 0.4, 2500);
    }

    function playMandirBell(ctx, time, freq, vol) {
        // Multi-harmonic brass bell
        [freq, freq * 1.5, freq * 2.02].forEach((f, i) => {
            const osc = ctx.createOscillator();
            const gain = createEnvelope(ctx, time, 0.005, 1.8 - (i * 0.4), vol / (i + 1));
            osc.type = 'sine';
            osc.frequency.setValueAtTime(f, time);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(time);
            osc.stop(time + 2.0);
        });
    }

    // =========================================================================
    // 2. DIWALI: Brass Temple Bell + Sparkle Fireworks Crackle
    // =========================================================================
    function playDiwaliSound() {
        const ctx = getAudioContext();
        if (!ctx) return;
        const now = ctx.currentTime + 0.05;

        // Resonating Brass Bell
        playMandirBell(ctx, now, 587.33, 0.4); // D5 Brass note

        // Sparkle fireworks crackles (sequence of soft pops)
        for (let i = 0; i < 5; i++) {
            const crackleTime = now + 0.15 + (i * 0.08) + (Math.random() * 0.04);
            createNoiseBurst(ctx, crackleTime, 0.04, 0.15, 3500 + Math.random() * 2000);
        }
    }

    // =========================================================================
    // 3. WINTER: Crystalline Ice Glockenspiel Chime (Arpeggio)
    // =========================================================================
    function playWinterSound() {
        const ctx = getAudioContext();
        if (!ctx) return;
        const now = ctx.currentTime + 0.05;
        const notes = [1046.5, 1318.5, 1567.98, 2093]; // C6, E6, G6, C7 Ice Chimes

        notes.forEach((freq, idx) => {
            const time = now + (idx * 0.1);
            const osc = ctx.createOscillator();
            const gain = createEnvelope(ctx, time, 0.003, 0.9, 0.25);
            osc.type = 'sine';
            osc.frequency.setValueAtTime(freq, time);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(time);
            osc.stop(time + 1.0);
        });
    }

    // =========================================================================
    // 4. CHRISTMAS: Festive Jingle Bells Double Chime
    // =========================================================================
    function playChristmasSound() {
        const ctx = getAudioContext();
        if (!ctx) return;
        const now = ctx.currentTime + 0.05;

        // Two bright metallic jingle bursts (E6 -> E6 -> G6)
        playJingleHit(ctx, now, 1318.51, 0.35);
        playJingleHit(ctx, now + 0.14, 1318.51, 0.35);
        playJingleHit(ctx, now + 0.28, 1567.98, 0.45);
    }

    function playJingleHit(ctx, time, freq, vol) {
        [freq, freq * 1.62, freq * 2.41, freq * 3.1].forEach((f, i) => {
            const osc = ctx.createOscillator();
            const gain = createEnvelope(ctx, time, 0.002, 0.35 - (i * 0.06), vol / (i + 1));
            osc.type = 'sine';
            osc.frequency.setValueAtTime(f, time);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(time);
            osc.stop(time + 0.4);
        });
    }

    // =========================================================================
    // 5. FREEDOM: Ceremonial Triumphant Major Fanfare (C-Major Chords)
    // =========================================================================
    function playFreedomSound() {
        const ctx = getAudioContext();
        if (!ctx) return;
        const now = ctx.currentTime + 0.05;

        // Fanfare bugle motif: G4 -> C5 -> E5 -> G5 (Grand chord)
        const notes = [
            { f: 392.00, t: 0, d: 0.15 },
            { f: 523.25, t: 0.12, d: 0.15 },
            { f: 659.25, t: 0.24, d: 0.18 },
            { f: 783.99, t: 0.38, d: 0.9 }
        ];

        notes.forEach(n => {
            const time = now + n.t;
            const osc = ctx.createOscillator();
            const gain = createEnvelope(ctx, time, 0.02, n.d, 0.32);
            osc.type = 'triangle'; // Brassy warmth
            osc.frequency.setValueAtTime(n.f, time);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(time);
            osc.stop(time + n.d + 0.1);
        });
    }

    // =========================================================================
    // 6. SUMMER: Warm Tropical Marimba Pop + Wave Resonance
    // =========================================================================
    function playSummerSound() {
        const ctx = getAudioContext();
        if (!ctx) return;
        const now = ctx.currentTime + 0.05;
        const notes = [523.25, 659.25, 783.99, 1046.5]; // C5, E5, G5, C6 Marimba

        notes.forEach((freq, idx) => {
            const time = now + (idx * 0.09);
            const osc = ctx.createOscillator();
            const gain = createEnvelope(ctx, time, 0.002, 0.3, 0.3);
            osc.type = 'sine';
            osc.frequency.setValueAtTime(freq, time);
            // Marimba harmonic overtone
            const osc2 = ctx.createOscillator();
            osc2.type = 'sine';
            osc2.frequency.setValueAtTime(freq * 3.8, time);
            const gain2 = createEnvelope(ctx, time, 0.001, 0.08, 0.1);

            osc.connect(gain);
            gain.connect(ctx.destination);
            osc2.connect(gain2);
            gain2.connect(ctx.destination);

            osc.start(time);
            osc2.start(time);
            osc.stop(time + 0.35);
            osc2.stop(time + 0.1);
        });
    }

    // =========================================================================
    // 7. DEFAULT: Luxury Obsidian Glass Pop (Apple Haptic Tap)
    // =========================================================================
    function playDefaultGlassSound() {
        const ctx = getAudioContext();
        if (!ctx) return;
        const now = ctx.currentTime + 0.02;

        const osc = ctx.createOscillator();
        const gain = createEnvelope(ctx, now, 0.001, 0.09, 0.35);
        osc.type = 'sine';
        osc.frequency.setValueAtTime(1400, now);
        osc.frequency.exponentialRampToValueAtTime(350, now + 0.08);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start(now);
        osc.stop(now + 0.1);
    }

    // =========================================================================
    // UNIVERSAL: Add to Cart / Order Success Chime
    // =========================================================================
    function playCartSuccessSound() {
        const ctx = getAudioContext();
        if (!ctx) return;
        const now = ctx.currentTime + 0.02;

        // 2-tone melodic chime: 659.25Hz (E5) -> 880Hz (A5)
        [
            { f: 659.25, t: 0, d: 0.18 },
            { f: 880.00, t: 0.12, d: 0.45 }
        ].forEach(n => {
            const time = now + n.t;
            const osc = ctx.createOscillator();
            const gain = createEnvelope(ctx, time, 0.005, n.d, 0.35);
            osc.type = 'sine';
            osc.frequency.setValueAtTime(n.f, time);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(time);
            osc.stop(time + n.d + 0.05);
        });
    }

    // Noise Generator for slaps, crackles, and wind
    function createNoiseBurst(ctx, time, duration, vol, filterFreq) {
        const bufferSize = ctx.sampleRate * duration;
        const buffer = ctx.createBuffer(1, bufferSize, ctx.sampleRate);
        const data = buffer.getChannelData(0);
        for (let i = 0; i < bufferSize; i++) {
            data[i] = Math.random() * 2 - 1;
        }

        const noise = ctx.createBufferSource();
        noise.buffer = buffer;

        const filter = ctx.createBiquadFilter();
        filter.type = 'bandpass';
        filter.frequency.setValueAtTime(filterFreq || 2000, time);
        filter.Q.setValueAtTime(3, time);

        const gain = createEnvelope(ctx, time, 0.002, duration, vol);

        noise.connect(filter);
        filter.connect(gain);
        gain.connect(ctx.destination);

        noise.start(time);
        noise.stop(time + duration + 0.02);
    }

    function isSoundActive() {
        if (typeof window.themeSoundMasterEnabled !== 'undefined' && !window.themeSoundMasterEnabled) {
            return false;
        }
        return soundEnabled;
    }

    // =========================================================================
    // PUBLIC THEME SOUND DISPATCHER
    // =========================================================================
    window.playThemeSound = function (themeKey) {
        if (!isSoundActive()) return;
        const theme = themeKey || document.body.getAttribute('data-theme') || 'default';

        try {
            switch (theme) {
                case 'durga_puja':
                    playDurgaPujaSound();
                    break;
                case 'diwali':
                    playDiwaliSound();
                    break;
                case 'winter':
                    playWinterSound();
                    break;
                case 'christmas':
                    playChristmasSound();
                    break;
                case 'freedom':
                    playFreedomSound();
                    break;
                case 'summer':
                    playSummerSound();
                    break;
                default:
                    playDefaultGlassSound();
                    break;
            }
        } catch (e) {
            console.warn('[SoundEngine] Audio error:', e);
        }
    };

    window.playCartSound = function () {
        if (!isSoundActive()) return;
        try {
            playCartSuccessSound();
        } catch (e) {}
    };

    window.toggleThemeSound = function () {
        soundEnabled = !soundEnabled;
        localStorage.setItem('stitch_theme_sound_enabled', soundEnabled ? '1' : '0');
        updateSoundPillUI();
        if (isSoundActive()) {
            window.playThemeSound();
        }
        return soundEnabled;
    };

    window.isThemeSoundEnabled = function () {
        return isSoundActive();
    };

    function updateSoundPillUI() {
        const btn = document.getElementById('theme-sound-toggle-btn');
        if (btn) {
            btn.innerHTML = soundEnabled ? '🔊 <span>Sound: ON</span>' : '🔇 <span>Sound: OFF</span>';
            btn.setAttribute('title', soundEnabled ? 'Theme audio enabled. Click to mute.' : 'Theme audio muted. Click to enable.');
            btn.classList.toggle('sound-muted', !soundEnabled);
        }
    }

    // =========================================================================
    // EVENT BINDINGS ON DOM LOAD
    // =========================================================================
    document.addEventListener('DOMContentLoaded', () => {
        updateSoundPillUI();

        // 1. Theme Corner Badge Click Trigger
        const cornerBadge = document.querySelector('.theme-corner-festive-badge');
        if (cornerBadge) {
            cornerBadge.style.cursor = 'pointer';
            cornerBadge.addEventListener('click', (e) => {
                // Don't trigger if clicked on child mute button
                if (e.target.closest('#theme-sound-toggle-btn')) return;
                window.playThemeSound();

                // Playful haptic bounce animation on badge
                cornerBadge.style.transform = 'scale(1.18) rotate(3deg)';
                setTimeout(() => {
                    cornerBadge.style.transform = '';
                }, 250);
            });
        }

        // 2. Add to Cart / Buy Now Click Chimes
        document.addEventListener('click', (e) => {
            const target = e.target.closest('.btn-add-to-cart, .btn-buy-now, [onclick*="addToCart"], [onclick*="buyNow"]');
            if (target) {
                window.playCartSound();
            }
        });
    });

})();
