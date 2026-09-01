/**
 * The Stitch Co. - Festive & Seasonal Particle Engine v2.0
 * Multi-layer 60fps HTML5 Canvas physics for all 7 themes
 * Layers: Snow, Kash Phool, Marigold Petals, Diya Sparks, Fireworks,
 *         Tricolor Confetti, Solar Embers, Christmas Stars & Ornaments
 */

(function () {
    'use strict';

    // Respect reduced motion preference
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    document.addEventListener('DOMContentLoaded', () => {
        const theme = document.body.getAttribute('data-theme') || 'default';
        if (theme === 'default') return;

        let canvas = document.getElementById('theme-particles-canvas');
        if (!canvas) {
            canvas = document.createElement('canvas');
            canvas.id = 'theme-particles-canvas';
            document.body.appendChild(canvas);
        }

        const ctx = canvas.getContext('2d');
        let W = canvas.width = window.innerWidth;
        let H = canvas.height = window.innerHeight;
        const isMobile = W < 768;

        window.addEventListener('resize', () => {
            W = canvas.width = window.innerWidth;
            H = canvas.height = window.innerHeight;
        });

        // =============================================
        // PARTICLE CLASSES BY THEME
        // =============================================

        /**
         * WINTER & CHRISTMAS: Multi-layer snowflakes
         */
        class Snowflake {
            constructor(layer) {
                this.layer = layer; // 1=large, 2=medium, 3=tiny sparkle
                this.reset(true);
            }
            reset(initial = false) {
                this.x = Math.random() * W;
                this.y = initial ? Math.random() * H : -15;
                if (this.layer === 1) {
                    this.r = Math.random() * 4 + 3;
                    this.speed = Math.random() * 0.8 + 0.5;
                    this.opacity = Math.random() * 0.5 + 0.4;
                    this.drift = Math.random() * 0.6 - 0.3;
                } else if (this.layer === 2) {
                    this.r = Math.random() * 2.5 + 1.5;
                    this.speed = Math.random() * 1.2 + 0.8;
                    this.opacity = Math.random() * 0.4 + 0.3;
                    this.drift = Math.random() * 1 - 0.5;
                } else {
                    this.r = Math.random() * 1.5 + 0.5;
                    this.speed = Math.random() * 2 + 1.2;
                    this.opacity = Math.random() * 0.3 + 0.2;
                    this.drift = Math.random() * 1.5 - 0.75;
                }
                this.angle = Math.random() * Math.PI * 2;
                this.angleSpeed = Math.random() * 0.02 - 0.01;
            }
            update() {
                this.y += this.speed;
                this.x += this.drift + Math.sin(this.angle) * 0.5;
                this.angle += this.angleSpeed;
                if (this.y > H + 20) this.reset();
                if (this.x > W + 20) this.x = -20;
                if (this.x < -20) this.x = W + 20;
            }
            draw() {
                ctx.save();
                ctx.globalAlpha = this.opacity;
                if (this.layer === 3) {
                    // Sparkle crystal
                    ctx.fillStyle = theme === 'christmas' ? '#FAF5C8' : '#BAE6FD';
                    ctx.shadowBlur = 4;
                    ctx.shadowColor = theme === 'christmas' ? '#EAB308' : '#38BDF8';
                } else {
                    ctx.fillStyle = '#FFFFFF';
                    ctx.shadowBlur = this.layer === 1 ? 8 : 4;
                    ctx.shadowColor = theme === 'christmas' ? 'rgba(255,255,255,0.8)' : '#BAE6FD';
                }
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.r, 0, Math.PI * 2);
                ctx.fill();
                ctx.restore();
            }
        }

        /**
         * CHRISTMAS: Colored confetti ornament drops
         */
        class ChristmasConfetti {
            constructor() { this.reset(true); }
            reset(initial = false) {
                this.x = Math.random() * W;
                this.y = initial ? Math.random() * H : -10;
                this.size = Math.random() * 8 + 4;
                this.speed = Math.random() * 1.5 + 0.8;
                this.drift = Math.random() * 1.2 - 0.6;
                this.angle = Math.random() * Math.PI * 2;
                this.angleSpeed = Math.random() * 0.06 - 0.03;
                this.opacity = Math.random() * 0.5 + 0.4;
                const colors = ['#DC2626', '#15803D', '#EAB308', '#FFFFFF', '#D97706'];
                this.color = colors[Math.floor(Math.random() * colors.length)];
                this.type = Math.random() > 0.5 ? 'rect' : 'circle';
            }
            update() {
                this.y += this.speed;
                this.x += this.drift;
                this.angle += this.angleSpeed;
                if (this.y > H + 20) this.reset();
            }
            draw() {
                ctx.save();
                ctx.globalAlpha = this.opacity;
                ctx.translate(this.x, this.y);
                ctx.rotate(this.angle);
                ctx.fillStyle = this.color;
                if (this.type === 'rect') {
                    ctx.fillRect(-this.size / 2, -this.size / 4, this.size, this.size / 2);
                } else {
                    ctx.beginPath();
                    ctx.arc(0, 0, this.size / 2, 0, Math.PI * 2);
                    ctx.fill();
                }
                ctx.restore();
            }
        }

        /**
         * DURGA PUJA: Kash Phool (white reeds) & Marigold/Gandha petals
         */
        class DurgaPujaParticle {
            constructor() { this.reset(true); }
            reset(initial = false) {
                this.x = Math.random() * W;
                this.y = initial ? Math.random() * H : -20;
                this.angle = Math.random() * Math.PI * 2;
                this.angleSpeed = Math.random() * 0.04 - 0.02;
                this.opacity = Math.random() * 0.6 + 0.3;
                this.speed = Math.random() * 1.2 + 0.5;
                this.drift = Math.random() * 1.5 - 0.75;
                // 40% Kash Phool, 40% Marigold, 20% Dhunuchi smoke wisp
                const r = Math.random();
                if (r < 0.4) {
                    this.type = 'kash';
                    this.size = Math.random() * 9 + 5;
                } else if (r < 0.8) {
                    this.type = 'marigold';
                    this.size = Math.random() * 7 + 4;
                } else {
                    this.type = 'smoke';
                    this.size = Math.random() * 18 + 10;
                    this.opacity = Math.random() * 0.12 + 0.05;
                    this.speed = Math.random() * 0.6 + 0.2;
                    this.y = initial ? Math.random() * H : H + 10;
                    this.speed *= -1; // smoke rises
                }
            }
            update() {
                this.y += this.speed;
                this.x += this.drift + Math.sin(this.angle) * 0.7;
                this.angle += this.angleSpeed;
                if (this.type === 'smoke') {
                    if (this.y < -30) this.reset();
                } else {
                    if (this.y > H + 20) this.reset();
                }
                if (this.x > W + 20) this.x = -20;
                if (this.x < -20) this.x = W + 20;
            }
            draw() {
                ctx.save();
                ctx.globalAlpha = this.opacity;
                ctx.translate(this.x, this.y);
                ctx.rotate(this.angle);
                if (this.type === 'kash') {
                    // White silky Kash Phool reed
                    ctx.fillStyle = 'rgba(255, 255, 255, 0.9)';
                    ctx.shadowBlur = 6;
                    ctx.shadowColor = 'rgba(255, 255, 255, 0.5)';
                    ctx.beginPath();
                    ctx.ellipse(0, 0, this.size * 0.35, this.size * 1.5, 0, 0, Math.PI * 2);
                    ctx.fill();
                } else if (this.type === 'marigold') {
                    // Golden Marigold petal
                    const colors = ['#F59E0B', '#D97706', '#DC2626', '#FDE68A'];
                    ctx.fillStyle = colors[Math.floor(Math.random() * colors.length)] || '#F59E0B';
                    ctx.shadowBlur = 5;
                    ctx.shadowColor = '#D97706';
                    ctx.beginPath();
                    ctx.ellipse(0, 0, this.size * 0.55, this.size, 0, 0, Math.PI * 2);
                    ctx.fill();
                } else {
                    // Dhunuchi smoke wisp — rising soft grey
                    const grad = ctx.createRadialGradient(0, 0, 0, 0, 0, this.size);
                    grad.addColorStop(0, 'rgba(200, 180, 140, 0.25)');
                    grad.addColorStop(1, 'transparent');
                    ctx.fillStyle = grad;
                    ctx.beginPath();
                    ctx.arc(0, 0, this.size, 0, Math.PI * 2);
                    ctx.fill();
                }
                ctx.restore();
            }
        }

        /**
         * DIWALI: Golden sparks (ascending) + Colored firework bursts + Purple shimmer
         */
        class DiwaliSpark {
            constructor() { this.reset(true); }
            reset(initial = false) {
                this.x = Math.random() * W;
                this.y = initial ? Math.random() * H : H + 10;
                this.opacity = Math.random() * 0.7 + 0.3;
                const r = Math.random();
                if (r < 0.6) {
                    this.type = 'spark';
                    this.r = Math.random() * 3 + 1;
                    this.speed = -(Math.random() * 2 + 1);
                    this.drift = Math.random() * 1.5 - 0.75;
                    this.color = ['#FBBF24', '#F59E0B', '#FDE68A', '#FEF3C7'][Math.floor(Math.random() * 4)];
                } else if (r < 0.85) {
                    this.type = 'firework';
                    this.r = Math.random() * 5 + 2;
                    this.x = Math.random() * W;
                    this.y = Math.random() * H * 0.6;
                    this.speedX = (Math.random() - 0.5) * 4;
                    this.speedY = (Math.random() - 0.5) * 4;
                    this.life = Math.random() * 80 + 40;
                    this.maxLife = this.life;
                    this.color = ['#F59E0B', '#EF4444', '#8B5CF6', '#FBBF24', '#EC4899'][Math.floor(Math.random() * 5)];
                } else {
                    this.type = 'shimmer';
                    this.r = Math.random() * 2 + 0.5;
                    this.speed = -(Math.random() * 0.5 + 0.2);
                    this.drift = Math.random() * 2 - 1;
                    this.opacity = Math.random() * 0.3 + 0.1;
                    this.color = ['#C4B5FD', '#DDD6FE', '#EDE9FE'][Math.floor(Math.random() * 3)];
                }
            }
            update() {
                if (this.type === 'spark') {
                    this.y += this.speed;
                    this.x += this.drift;
                    if (this.y < -20) this.reset();
                } else if (this.type === 'firework') {
                    this.x += this.speedX;
                    this.y += this.speedY;
                    this.speedX *= 0.96;
                    this.speedY *= 0.96;
                    this.life--;
                    this.opacity = this.life / this.maxLife;
                    if (this.life <= 0) this.reset();
                } else {
                    this.y += this.speed;
                    this.x += this.drift;
                    if (this.y < -10) this.reset();
                }
            }
            draw() {
                ctx.save();
                ctx.globalAlpha = this.opacity;
                ctx.fillStyle = this.color;
                if (this.type === 'spark') {
                    ctx.shadowBlur = 10;
                    ctx.shadowColor = '#F59E0B';
                } else if (this.type === 'firework') {
                    ctx.shadowBlur = 12;
                    ctx.shadowColor = this.color;
                } else {
                    ctx.shadowBlur = 4;
                    ctx.shadowColor = this.color;
                }
                ctx.beginPath();
                ctx.arc(this.type === 'firework' ? this.x : 0, this.type === 'firework' ? this.y : 0, this.r, 0, Math.PI * 2);
                if (this.type !== 'firework') ctx.translate(this.x, this.y);
                ctx.fill();
                ctx.restore();
            }
        }

        /**
         * FREEDOM: Saffron, White, Green confetti ribbons + Chakra symbols
         */
        class FreedomConfetti {
            constructor() { this.reset(true); }
            reset(initial = false) {
                this.x = Math.random() * W;
                this.y = initial ? Math.random() * H : -10;
                this.angle = Math.random() * Math.PI * 2;
                this.angleSpeed = Math.random() * 0.05 - 0.025;
                this.speed = Math.random() * 1.5 + 0.6;
                this.drift = Math.random() * 1.5 - 0.75;
                this.opacity = Math.random() * 0.6 + 0.3;
                this.isChakra = Math.random() < 0.05; // 5% chakra symbols
                if (this.isChakra) {
                    this.size = Math.random() * 14 + 8;
                    this.color = '#1E3A8A';
                    this.speed *= 0.5;
                } else {
                    const palette = [
                        { c: '#EA580C', w: 10, h: 4 },  // Saffron ribbon
                        { c: '#FFFFFF', w: 10, h: 4 },   // White ribbon
                        { c: '#15803D', w: 10, h: 4 },  // Green ribbon
                        { c: '#1E3A8A', w: 6, h: 6 },   // Navy dot
                    ];
                    const p = palette[Math.floor(Math.random() * palette.length)];
                    this.color = p.c;
                    this.w = p.w + Math.random() * 4;
                    this.h = p.h + Math.random() * 2;
                }
            }
            update() {
                this.y += this.speed;
                this.x += this.drift;
                this.angle += this.angleSpeed;
                if (this.y > H + 20) this.reset();
            }
            draw() {
                ctx.save();
                ctx.globalAlpha = this.opacity;
                ctx.translate(this.x, this.y);
                ctx.rotate(this.angle);
                ctx.fillStyle = this.color;
                if (this.isChakra) {
                    ctx.font = `${this.size}px serif`;
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillText('☸', 0, 0);
                } else {
                    ctx.fillRect(-this.w / 2, -this.h / 2, this.w, this.h);
                }
                ctx.restore();
            }
        }

        /**
         * SUMMER: Solar embers (descending) + Fire sparks + Shimmer heatwave
         */
        class SummerParticle {
            constructor() { this.reset(true); }
            reset(initial = false) {
                this.x = Math.random() * W;
                this.y = initial ? Math.random() * H : -10;
                this.opacity = Math.random() * 0.6 + 0.25;
                const r = Math.random();
                if (r < 0.5) {
                    this.type = 'ember';
                    this.r = Math.random() * 3 + 1.5;
                    this.speed = Math.random() * 1 + 0.5;
                    this.drift = Math.random() * 1.5 - 0.75;
                    this.color = Math.random() > 0.5 ? '#F59E0B' : '#EA580C';
                } else if (r < 0.8) {
                    this.type = 'spark';
                    this.r = Math.random() * 2 + 1;
                    this.speed = Math.random() * 2 + 1;
                    this.drift = Math.random() * 2 - 1;
                    this.color = '#DC2626';
                    this.opacity *= 0.8;
                } else {
                    this.type = 'shimmer';
                    this.r = Math.random() * 4 + 2;
                    this.speed = Math.random() * 0.5 + 0.2;
                    this.drift = Math.random() * 0.8 - 0.4;
                    this.color = '#FEF3C7';
                    this.opacity = Math.random() * 0.2 + 0.08;
                }
                this.angle = Math.random() * Math.PI * 2;
                this.angleSpeed = Math.random() * 0.05 - 0.025;
            }
            update() {
                this.y += this.speed;
                this.x += this.drift + Math.sin(this.angle) * 0.4;
                this.angle += this.angleSpeed;
                if (this.y > H + 20) this.reset();
                if (this.x > W + 20) this.x = -20;
                if (this.x < -20) this.x = W + 20;
            }
            draw() {
                ctx.save();
                ctx.globalAlpha = this.opacity;
                ctx.fillStyle = this.color;
                ctx.shadowBlur = this.type === 'shimmer' ? 2 : 8;
                ctx.shadowColor = this.color;
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.r, 0, Math.PI * 2);
                ctx.fill();
                ctx.restore();
            }
        }

        // =============================================
        // BUILD PARTICLE POOL BY THEME
        // =============================================
        const particles = [];
        const totalParticles = isMobile ? 50 : 110;

        if (theme === 'winter') {
            // 3 layers: large fluffy, medium, tiny sparkle
            for (let i = 0; i < totalParticles; i++) {
                const layer = i < totalParticles * 0.25 ? 1 : (i < totalParticles * 0.65 ? 2 : 3);
                particles.push(new Snowflake(layer));
            }
        } else if (theme === 'christmas') {
            // Mix of snowflakes + colored confetti
            const snowCount = Math.floor(totalParticles * 0.6);
            const confettiCount = totalParticles - snowCount;
            for (let i = 0; i < snowCount; i++) {
                const layer = i < snowCount * 0.3 ? 1 : (i < snowCount * 0.7 ? 2 : 3);
                particles.push(new Snowflake(layer));
            }
            for (let i = 0; i < confettiCount; i++) {
                particles.push(new ChristmasConfetti());
            }
        } else if (theme === 'durga_puja') {
            for (let i = 0; i < totalParticles; i++) {
                particles.push(new DurgaPujaParticle());
            }
        } else if (theme === 'diwali') {
            for (let i = 0; i < totalParticles; i++) {
                particles.push(new DiwaliSpark());
            }
        } else if (theme === 'freedom') {
            for (let i = 0; i < totalParticles; i++) {
                particles.push(new FreedomConfetti());
            }
        } else if (theme === 'summer') {
            for (let i = 0; i < totalParticles; i++) {
                particles.push(new SummerParticle());
            }
        }

        // =============================================
        // ANIMATION LOOP
        // =============================================
        let animId;
        function animate() {
            ctx.clearRect(0, 0, W, H);
            for (let i = 0; i < particles.length; i++) {
                particles[i].update();
                particles[i].draw();
            }
            animId = requestAnimationFrame(animate);
        }

        animate();

        // Pause when tab not visible to save battery
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                cancelAnimationFrame(animId);
            } else {
                animate();
            }
        });
    });
})();
