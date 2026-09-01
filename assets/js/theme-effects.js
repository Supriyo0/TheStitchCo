/**
 * The Stitch Co. — Ultra-Smooth 60fps Particle Engine v3.0
 * High-performance, low-power canvas physics engine
 * Features: Zero GC-thrashing, no expensive shadow rasterizations,
 *           automatic pause when tab is inactive, subtle premium aesthetic.
 */
(function () {
    'use strict';

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    document.addEventListener('DOMContentLoaded', () => {
        const theme = document.body.getAttribute('data-theme') || 'default';
        if (theme === 'default' || !theme) return;

        let canvas = document.getElementById('theme-particles-canvas');
        if (!canvas) {
            canvas = document.createElement('canvas');
            canvas.id = 'theme-particles-canvas';
            document.body.appendChild(canvas);
        }

        const ctx = canvas.getContext('2d', { alpha: true });
        let W = canvas.width = window.innerWidth;
        let H = canvas.height = window.innerHeight;
        const isMobile = W < 768;

        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                W = canvas.width = window.innerWidth;
                H = canvas.height = window.innerHeight;
            }, 100);
        }, { passive: true });

        // =============================================
        // OPTIMIZED PARTICLE IMPLEMENTATIONS
        // =============================================

        class Snowflake {
            constructor(layer) {
                this.layer = layer;
                this.reset(true);
            }
            reset(initial = false) {
                this.x = Math.random() * W;
                this.y = initial ? Math.random() * H : -10;
                this.r = this.layer === 1 ? Math.random() * 2.5 + 2 : Math.random() * 1.5 + 0.8;
                this.speed = this.layer === 1 ? Math.random() * 0.7 + 0.4 : Math.random() * 1.2 + 0.6;
                this.opacity = this.layer === 1 ? Math.random() * 0.4 + 0.3 : Math.random() * 0.3 + 0.15;
                this.drift = Math.random() * 0.5 - 0.25;
                this.angle = Math.random() * Math.PI * 2;
                this.angleSpeed = Math.random() * 0.015 - 0.007;
            }
            update() {
                this.y += this.speed;
                this.x += this.drift + Math.sin(this.angle) * 0.4;
                this.angle += this.angleSpeed;
                if (this.y > H + 10) this.reset();
                if (this.x > W + 10) this.x = -10;
                if (this.x < -10) this.x = W + 10;
            }
            draw() {
                ctx.globalAlpha = this.opacity;
                ctx.fillStyle = '#FFFFFF';
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.r, 0, Math.PI * 2);
                ctx.fill();
            }
        }

        class DurgaPujaParticle {
            constructor() {
                this.isKash = Math.random() > 0.4;
                this.reset(true);
            }
            reset(initial = false) {
                this.x = Math.random() * W;
                this.y = initial ? Math.random() * H : -15;
                this.length = Math.random() * 14 + 10;
                this.speed = Math.random() * 0.6 + 0.35;
                this.drift = Math.random() * 0.4 - 0.2;
                this.angle = Math.random() * 0.5 - 0.25;
                this.angleSpeed = Math.random() * 0.008 - 0.004;
                this.opacity = Math.random() * 0.35 + 0.2;
                this.color = this.isKash ? '#B45309' : '#D97706';
            }
            update() {
                this.y += this.speed;
                this.x += this.drift + Math.sin(this.angle) * 0.3;
                this.angle += this.angleSpeed;
                if (this.y > H + 20) this.reset();
                if (this.x > W + 20) this.x = -20;
                if (this.x < -20) this.x = W + 20;
            }
            draw() {
                ctx.globalAlpha = this.opacity;
                ctx.strokeStyle = this.color;
                ctx.lineWidth = 1;
                ctx.beginPath();
                ctx.moveTo(this.x, this.y);
                ctx.lineTo(this.x + Math.sin(this.angle) * this.length, this.y + this.length);
                ctx.stroke();
            }
        }

        class DiwaliSpark {
            constructor() {
                this.reset(true);
            }
            reset(initial = false) {
                this.x = Math.random() * W;
                this.y = initial ? Math.random() * H : H + 10;
                this.r = Math.random() * 2 + 1;
                this.speed = Math.random() * 0.8 + 0.4;
                this.drift = Math.random() * 0.4 - 0.2;
                this.opacity = Math.random() * 0.45 + 0.25;
                this.pulse = Math.random() * Math.PI * 2;
            }
            update() {
                this.y -= this.speed;
                this.x += this.drift;
                this.pulse += 0.03;
                if (this.y < -10) this.reset();
            }
            draw() {
                const currentOpacity = this.opacity * (0.8 + 0.2 * Math.sin(this.pulse));
                ctx.globalAlpha = currentOpacity;
                ctx.fillStyle = '#F5D78A';
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.r, 0, Math.PI * 2);
                ctx.fill();
            }
        }

        class FreedomConfetti {
            constructor() {
                this.colors = ['#EA580C', '#FFFFFF', '#15803D'];
                this.color = this.colors[Math.floor(Math.random() * this.colors.length)];
                this.reset(true);
            }
            reset(initial = false) {
                this.x = Math.random() * W;
                this.y = initial ? Math.random() * H : -10;
                this.w = Math.random() * 5 + 3;
                this.h = Math.random() * 3 + 2;
                this.speed = Math.random() * 0.8 + 0.4;
                this.drift = Math.random() * 0.5 - 0.25;
                this.opacity = Math.random() * 0.35 + 0.15;
                this.angle = Math.random() * Math.PI * 2;
                this.angleSpeed = Math.random() * 0.02 - 0.01;
            }
            update() {
                this.y += this.speed;
                this.x += this.drift;
                this.angle += this.angleSpeed;
                if (this.y > H + 10) this.reset();
            }
            draw() {
                ctx.globalAlpha = this.opacity;
                ctx.fillStyle = this.color;
                ctx.beginPath();
                ctx.rect(this.x, this.y, this.w, this.h);
                ctx.fill();
            }
        }

        class SummerParticle {
            constructor() {
                this.reset(true);
            }
            reset(initial = false) {
                this.x = Math.random() * W;
                this.y = initial ? Math.random() * H : H + 10;
                this.r = Math.random() * 2 + 1;
                this.speed = Math.random() * 0.6 + 0.3;
                this.drift = Math.random() * 0.3 - 0.15;
                this.opacity = Math.random() * 0.3 + 0.15;
                this.angle = Math.random() * Math.PI * 2;
            }
            update() {
                this.y -= this.speed;
                this.x += this.drift + Math.sin(this.angle) * 0.3;
                this.angle += 0.02;
                if (this.y < -10) this.reset();
            }
            draw() {
                ctx.globalAlpha = this.opacity;
                ctx.fillStyle = '#FED7AA';
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.r, 0, Math.PI * 2);
                ctx.fill();
            }
        }

        // =============================================
        // POOL GENERATION (Restrained & Lightweight)
        // =============================================
        const particles = [];
        const count = isMobile ? 22 : 42;

        for (let i = 0; i < count; i++) {
            if (theme === 'winter' || theme === 'christmas') {
                particles.push(new Snowflake(i % 2 === 0 ? 1 : 2));
            } else if (theme === 'durga_puja') {
                particles.push(new DurgaPujaParticle());
            } else if (theme === 'diwali') {
                particles.push(new DiwaliSpark());
            } else if (theme === 'freedom') {
                particles.push(new FreedomConfetti());
            } else if (theme === 'summer') {
                particles.push(new SummerParticle());
            }
        }

        // =============================================
        // 60FPS RAF LOOP
        // =============================================
        let animId;
        function animate() {
            ctx.clearRect(0, 0, W, H);
            const len = particles.length;
            for (let i = 0; i < len; i++) {
                particles[i].update();
                particles[i].draw();
            }
            animId = requestAnimationFrame(animate);
        }

        animate();

        // Pause automatically on hidden tabs to preserve battery & CPU
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                cancelAnimationFrame(animId);
            } else {
                animate();
            }
        });
    });
})();
