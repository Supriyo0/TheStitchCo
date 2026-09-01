/**
 * The Stitch Co. - Festive & Seasonal Particle Engine
 * 60fps HTML5 Canvas physics for Snow, Kash Phool/Marigold Petals, Diya Sparks, and Tricolor Confetti
 */

(function() {
    'use strict';

    // Check if user prefers reduced motion
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    document.addEventListener('DOMContentLoaded', () => {
        const theme = document.body.getAttribute('data-theme') || 'default';
        if (theme === 'default') return; // Default theme has no particle overlay

        // Create or get canvas
        let canvas = document.getElementById('theme-particles-canvas');
        if (!canvas) {
            canvas = document.createElement('canvas');
            canvas.id = 'theme-particles-canvas';
            document.body.appendChild(canvas);
        }

        const ctx = canvas.getContext('2d');
        let width = canvas.width = window.innerWidth;
        let height = canvas.height = window.innerHeight;

        window.addEventListener('resize', () => {
            width = canvas.width = window.innerWidth;
            height = canvas.height = window.innerHeight;
        });

        // Particle configuration based on active theme
        const isMobile = window.innerWidth < 768;
        const particleCount = isMobile ? 35 : 75;
        const particles = [];

        class Particle {
            constructor() {
                this.reset(true);
            }

            reset(initial = false) {
                this.x = Math.random() * width;
                this.y = initial ? Math.random() * height : -20;
                this.size = Math.random() * 4 + 2;
                this.speedY = Math.random() * 1.5 + 0.8;
                this.speedX = Math.random() * 1 - 0.5;
                this.angle = Math.random() * Math.PI * 2;
                this.angleSpeed = Math.random() * 0.03 - 0.015;
                this.opacity = Math.random() * 0.6 + 0.3;

                if (theme === 'diwali') {
                    // Ascending sparks
                    this.y = initial ? Math.random() * height : height + 20;
                    this.speedY = -(Math.random() * 2 + 1);
                    this.size = Math.random() * 3 + 1.5;
                } else if (theme === 'durga_puja') {
                    // Floating flower petals / Kash Phool
                    this.size = Math.random() * 8 + 4;
                    this.isKash = Math.random() > 0.5;
                    this.speedY = Math.random() * 1.2 + 0.6;
                    this.speedX = Math.random() * 1.5 - 0.75;
                } else if (theme === 'freedom') {
                    // Saffron, White, Green Confetti
                    const colors = ['#EA580C', '#FFFFFF', '#15803D', '#1E3A8A'];
                    this.color = colors[Math.floor(Math.random() * colors.length)];
                    this.width = Math.random() * 8 + 4;
                    this.height = Math.random() * 4 + 2;
                } else if (theme === 'summer') {
                    this.size = Math.random() * 4 + 2;
                    this.color = Math.random() > 0.5 ? '#F59E0B' : '#EA580C';
                }
            }

            update() {
                this.x += this.speedX + Math.sin(this.angle) * 0.8;
                this.y += this.speedY;
                this.angle += this.angleSpeed;

                if (theme === 'diwali') {
                    if (this.y < -20) this.reset();
                } else {
                    if (this.y > height + 20) this.reset();
                }

                if (this.x > width + 20) this.x = -20;
                if (this.x < -20) this.x = width + 20;
            }

            draw() {
                ctx.save();
                ctx.globalAlpha = this.opacity;

                if (theme === 'winter' || theme === 'christmas') {
                    // Snowflakes
                    ctx.fillStyle = '#FFFFFF';
                    ctx.shadowBlur = 6;
                    ctx.shadowColor = '#BAE6FD';
                    ctx.beginPath();
                    ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                    ctx.fill();
                } else if (theme === 'durga_puja') {
                    // Kash Phool & Marigold Petal
                    ctx.translate(this.x, this.y);
                    ctx.rotate(this.angle);
                    if (this.isKash) {
                        // White silky Kash Phool reed
                        ctx.fillStyle = 'rgba(255, 255, 255, 0.85)';
                        ctx.beginPath();
                        ctx.ellipse(0, 0, this.size * 0.4, this.size * 1.4, 0, 0, Math.PI * 2);
                        ctx.fill();
                    } else {
                        // Golden Marigold / Gandha flower petal
                        ctx.fillStyle = '#F59E0B';
                        ctx.shadowBlur = 4;
                        ctx.shadowColor = '#D97706';
                        ctx.beginPath();
                        ctx.ellipse(0, 0, this.size * 0.6, this.size, 0, 0, Math.PI * 2);
                        ctx.fill();
                    }
                } else if (theme === 'diwali') {
                    // Golden Diya Sparks
                    ctx.fillStyle = '#FBBF24';
                    ctx.shadowBlur = 10;
                    ctx.shadowColor = '#F59E0B';
                    ctx.beginPath();
                    ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                    ctx.fill();
                } else if (theme === 'freedom') {
                    // Tiranga Confetti
                    ctx.translate(this.x, this.y);
                    ctx.rotate(this.angle);
                    ctx.fillStyle = this.color;
                    ctx.fillRect(-this.width / 2, -this.height / 2, this.width, this.height);
                } else if (theme === 'summer') {
                    // Solar Glow Embers
                    ctx.fillStyle = this.color;
                    ctx.shadowBlur = 8;
                    ctx.shadowColor = '#F59E0B';
                    ctx.beginPath();
                    ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                    ctx.fill();
                }

                ctx.restore();
            }
        }

        for (let i = 0; i < particleCount; i++) {
            particles.push(new Particle());
        }

        let animationFrameId;
        function animate() {
            ctx.clearRect(0, 0, width, height);
            for (let i = 0; i < particles.length; i++) {
                particles[i].update();
                particles[i].draw();
            }
            animationFrameId = requestAnimationFrame(animate);
        }

        animate();

        // Pause animation when tab is inactive to save battery
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                cancelAnimationFrame(animationFrameId);
            } else {
                animate();
            }
        });
    });
})();
