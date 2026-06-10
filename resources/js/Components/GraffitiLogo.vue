<script setup>
import { usePage } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';

const page = usePage();

// Reactive settings props
const appLogoType = computed(() => page.props.settings?.app_logo_type || 'text');
const appLogoPath = computed(() => page.props.settings?.app_logo_path || '');
const appLogoText = computed(() => page.props.settings?.app_logo || 'QMS Portal');

const logoContainer = ref(null);
const logoEffectsCanvas = ref(null);
const logoImg = ref(null);

let animationFrameId = null;

onMounted(() => {
    const canvas = logoEffectsCanvas.value;
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    let width = (canvas.width = logoContainer.value.offsetWidth);
    let height = (canvas.height = logoContainer.value.offsetHeight);

    const resizeObserver = new ResizeObserver(() => {
        if (logoContainer.value) {
            width = canvas.width = logoContainer.value.offsetWidth;
            height = canvas.height = logoContainer.value.offsetHeight;
        }
    });
    resizeObserver.observe(logoContainer.value);

    // Particle systems
    const raindrops = [];
    const splashes = [];
    const drips = [];
    const sprayMist = [];

    // Pre-populate some spray mist particles for the graffiti vibe
    const initMist = () => {
        sprayMist.length = 0;
        for (let i = 0; i < 20; i++) {
            sprayMist.push({
                x: width / 2 + (Math.random() - 0.5) * 120,
                y: height / 2 + (Math.random() - 0.5) * 40,
                radius: Math.random() * 15 + 10,
                color: Math.random() > 0.5 ? 'rgba(6, 182, 212, 0.08)' : 'rgba(37, 99, 235, 0.06)',
                pulseSpeed: Math.random() * 0.02 + 0.01,
                scale: 1,
                growing: true
            });
        }
    };
    initMist();

    const draw = () => {
        ctx.clearRect(0, 0, width, height);

        // 1. Draw animated neon spray-paint graffiti mist in the background
        sprayMist.forEach((m) => {
            // Pulse the mist radius for a living, breathing look
            if (m.growing) {
                m.scale += m.pulseSpeed;
                if (m.scale > 1.25) m.growing = false;
            } else {
                m.scale -= m.pulseSpeed;
                if (m.scale < 0.85) m.growing = true;
            }

            ctx.beginPath();
            const radGrad = ctx.createRadialGradient(m.x, m.y, 0, m.x, m.y, m.radius * m.scale);
            radGrad.addColorStop(0, m.color);
            radGrad.addColorStop(1, 'rgba(0, 0, 0, 0)');
            ctx.fillStyle = radGrad;
            ctx.arc(m.x, m.y, m.radius * m.scale, 0, Math.PI * 2);
            ctx.fill();
        });

        // 2. Spawn and update vertical falling water droplets (Animasi Air Jatuh)
        if (Math.random() < 0.25 && raindrops.length < 35) {
            raindrops.push({
                x: Math.random() * width,
                y: 0,
                vy: Math.random() * 3 + 4,
                length: Math.random() * 10 + 8,
                opacity: Math.random() * 0.35 + 0.25
            });
        }

        // Approx vertical range of the logo image/text to trigger splashes
        const logoTop = height * 0.25;
        const logoBottom = height * 0.75;
        const logoLeft = width * 0.2;
        const logoRight = width * 0.8;

        for (let i = raindrops.length - 1; i >= 0; i--) {
            const r = raindrops[i];
            r.y += r.vy;

            // Draw falling raindrop trail
            ctx.strokeStyle = `rgba(6, 182, 212, ${r.opacity})`;
            ctx.lineWidth = 1.2;
            ctx.beginPath();
            ctx.moveTo(r.x, r.y);
            ctx.lineTo(r.x, r.y + r.length);
            ctx.stroke();

            // Collision detection with the logo boundary
            if (r.x > logoLeft && r.x < logoRight && r.y > logoTop && r.y < logoTop + 4) {
                // Splash on top of the logo!
                if (Math.random() < 0.8) {
                    for (let s = 0; s < 3; s++) {
                        splashes.push({
                            x: r.x,
                            y: logoTop,
                            vx: (Math.random() - 0.5) * 2.5,
                            vy: -Math.random() * 1.5 - 0.5,
                            radius: Math.random() * 1.2 + 0.5,
                            life: 1.0,
                            decay: Math.random() * 0.08 + 0.05
                        });
                    }
                }
                // Option to transition from falling raindrop to a slow sliding drip down the logo
                if (Math.random() < 0.45 && drips.length < 15) {
                    drips.push({
                        x: r.x,
                        y: logoTop + 2,
                        vy: Math.random() * 0.4 + 0.2, // slow slide
                        radius: Math.random() * 1.5 + 1.0,
                        opacity: 0.8,
                        trail: []
                    });
                }
                raindrops.splice(i, 1);
                continue;
            }

            // Remove if off bottom
            if (r.y > height) {
                raindrops.splice(i, 1);
            }
        }

        // 3. Update and draw splashes
        for (let i = splashes.length - 1; i >= 0; i--) {
            const s = splashes[i];
            s.x += s.vx;
            s.y += s.vy;
            s.vy += 0.08; // gravity for splash
            s.life -= s.decay;

            ctx.fillStyle = `rgba(6, 182, 212, ${s.life * 0.75})`;
            ctx.beginPath();
            ctx.arc(s.x, s.y, s.radius, 0, Math.PI * 2);
            ctx.fill();

            if (s.life <= 0) {
                splashes.splice(i, 1);
            }
        }

        // 4. Update and draw slow sliding water drips (which slide down the logo)
        for (let i = drips.length - 1; i >= 0; i--) {
            const d = drips[i];
            
            // Capture trail coordinates for wet streak effect
            d.trail.push({ x: d.x, y: d.y });
            if (d.trail.length > 18) d.trail.shift();

            // Draw the wet trail behind the drip
            if (d.trail.length > 1) {
                ctx.beginPath();
                ctx.strokeStyle = `rgba(6, 182, 212, 0.15)`;
                ctx.lineWidth = d.radius * 0.8;
                ctx.moveTo(d.trail[0].x, d.trail[0].y);
                for (let t = 1; t < d.trail.length; t++) {
                    ctx.lineTo(d.trail[t].x, d.trail[t].y);
                }
                ctx.stroke();
            }

            // Move the drip down
            d.y += d.vy;

            // Wobble slightly sideways for organic fluid movement
            d.x += (Math.random() - 0.5) * 0.25;

            // Draw the main drip bead
            ctx.fillStyle = `rgba(6, 182, 212, ${d.opacity})`;
            ctx.beginPath();
            ctx.arc(d.x, d.y, d.radius, 0, Math.PI * 2);
            ctx.fill();

            // Add a small light highlight to make the drop look 3D and liquid
            ctx.fillStyle = 'rgba(255, 255, 255, 0.65)';
            ctx.beginPath();
            ctx.arc(d.x - d.radius * 0.3, d.y - d.radius * 0.3, d.radius * 0.3, 0, Math.PI * 2);
            ctx.fill();

            // When reaching the bottom of the logo area, accumulate and fall off
            if (d.y > logoBottom) {
                // Grow a bit representing accumulation at the edge
                d.radius += 0.05;
                d.vy *= 0.95; // slow down to hang

                if (d.radius > 3.8 || Math.random() < 0.02) {
                    // Detach and fall fast as a new raindrop from the logo bottom
                    raindrops.push({
                        x: d.x,
                        y: d.y + d.radius,
                        vy: Math.random() * 2 + 5,
                        length: Math.random() * 8 + 6,
                        opacity: 0.75
                    });
                    drips.splice(i, 1);
                }
            } else if (d.y > height) {
                drips.splice(i, 1);
            }
        }

        animationFrameId = requestAnimationFrame(draw);
    };

    draw();

    onUnmounted(() => {
        resizeObserver.disconnect();
        cancelAnimationFrame(animationFrameId);
    });
});
</script>

<template>
    <div ref="logoContainer" class="graffiti-logo-wrapper">
        <!-- Background spray paint glow behind the logo -->
        <div class="graffiti-spray-bg"></div>

        <!-- HTML5 Canvas for the dripping water particle simulation -->
        <canvas ref="logoEffectsCanvas" class="logo-effects-canvas"></canvas>

        <!-- Dynamic Content: custom uploaded image or default styling text -->
        <div class="logo-content">
            <template v-if="appLogoType === 'image' && appLogoPath">
                <div class="logo-side-by-side">
                    <img 
                        ref="logoImg"
                        :src="'/storage/' + appLogoPath" 
                        alt="App Logo" 
                        class="logo-image" 
                    />
                    <h2 class="logo-text">
                        {{ appLogoText }}
                    </h2>
                </div>
            </template>
            <template v-else>
                <h2 class="logo-text">
                    {{ appLogoText }}
                </h2>
            </template>
        </div>

        <!-- Custom SVG Fresh Spray Paint Drips -->
        <div class="graffiti-drips-overlay">
            <svg viewBox="0 0 100 25" preserveAspectRatio="none" class="drip-svg">
                <!-- Cyan / blue neon dripping spray-paint path -->
                <path 
                    d="M 0,0 
                       L 5,0 Q 6,8 8,10 Q 10,12 11,8 L 12,0 
                       L 22,0 Q 23,18 25,20 Q 27,22 28,16 L 30,0
                       L 45,0 Q 46,6 48,8 Q 50,10 51,6 L 52,0 
                       L 68,0 Q 69,14 71,16 Q 73,18 74,13 L 76,0
                       L 85,0 Q 86,10 88,12 Q 90,14 91,10 L 92,0 
                       L 100,0 L 100,2 L 0,2 Z" 
                    fill="currentColor" 
                    class="drip-path"
                />
            </svg>
        </div>
    </div>
</template>

<style scoped>
.graffiti-logo-wrapper {
    position: relative;
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
    padding: 14px 28px;
    min-height: 85px;
    min-width: 200px;
    border-radius: 14px;
    overflow: hidden;
    user-select: none;
}

/* Stencil spray border stamps surrounding the logo */
.graffiti-logo-wrapper::before {
    content: '';
    position: absolute;
    inset: 0;
    border-top: 2px solid rgba(6, 182, 212, 0.55);
    border-bottom: 2px solid rgba(37, 99, 235, 0.55);
    border-left: 2px dashed rgba(6, 182, 212, 0.25);
    border-right: 2px dashed rgba(37, 99, 235, 0.25);
    border-radius: 12px;
    transform: rotate(-1.5deg);
    pointer-events: none;
    animation: neonBorderGlow 5s infinite alternate ease-in-out;
}

/* Backlit neon radial spray background */
.graffiti-spray-bg {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 140%;
    height: 140%;
    transform: translate(-50%, -50%) rotate(3deg);
    background: radial-gradient(circle, rgba(6, 182, 212, 0.25) 0%, rgba(37, 99, 235, 0.1) 40%, rgba(139, 92, 246, 0.03) 65%, rgba(0, 0, 0, 0) 80%);
    filter: blur(8px);
    z-index: 1;
    pointer-events: none;
}

/* Canvas element overlay */
.logo-effects-canvas {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    z-index: 2;
    pointer-events: none;
}

/* Logo content holder */
.logo-content {
    position: relative;
    z-index: 3;
    display: flex;
    align-items: center;
    justify-content: center;
}

.logo-side-by-side {
    display: flex;
    align-items: center;
    gap: 12px;
}

/* Uploaded Image logo styling (Cyber Stencil stencil glow) */
.logo-image {
    max-height: 52px;
    max-width: 180px;
    object-fit: contain;
    filter: drop-shadow(0 0 6px rgba(6, 182, 212, 0.6)) 
            drop-shadow(0 0 16px rgba(37, 99, 235, 0.4));
    transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.logo-image:hover {
    transform: scale(1.06) rotate(1.5deg);
    filter: drop-shadow(0 0 10px rgba(6, 182, 212, 0.8)) 
            drop-shadow(0 0 25px rgba(37, 99, 235, 0.6));
}

/* Text fallback logo styling */
.logo-text {
    font-size: 1.75rem;
    font-weight: 900;
    font-family: var(--font-outfit), sans-serif;
    letter-spacing: -0.04em;
    background: linear-gradient(135deg, #06b6d4 10%, #2563eb 50%, #8b5cf6 90%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    filter: drop-shadow(0 0 8px rgba(6, 182, 212, 0.45));
    transition: all 0.3s ease;
}
.logo-text:hover {
    transform: scale(1.05);
    filter: drop-shadow(0 0 12px rgba(6, 182, 212, 0.65));
}

/* Static spray paint drips at the bottom of the logo box */
.graffiti-drips-overlay {
    position: absolute;
    bottom: -1px;
    left: 10%;
    right: 10%;
    height: 18px;
    z-index: 4;
    pointer-events: none;
    overflow: hidden;
}
.drip-svg {
    width: 100%;
    height: 100%;
    color: rgba(6, 182, 212, 0.7);
    filter: drop-shadow(0 2px 4px rgba(6, 182, 212, 0.4));
}
.drip-path {
    animation: dripStretch 6s infinite alternate ease-in-out;
    transform-origin: top center;
}

@keyframes neonBorderGlow {
    0% {
        border-color: rgba(6, 182, 212, 0.45) rgba(37, 99, 235, 0.45);
        box-shadow: 0 0 10px rgba(6, 182, 212, 0.1), 0 0 20px rgba(37, 99, 235, 0.05);
        transform: rotate(-1.5deg) scale(0.98);
    }
    100% {
        border-color: rgba(37, 99, 235, 0.8) rgba(6, 182, 212, 0.8);
        box-shadow: 0 0 15px rgba(37, 99, 235, 0.25), 0 0 30px rgba(6, 182, 212, 0.15);
        transform: rotate(-0.5deg) scale(1.01);
    }
}

@keyframes dripStretch {
    0% {
        transform: scaleY(0.9) skewX(-1deg);
        color: rgba(6, 182, 212, 0.7);
    }
    100% {
        transform: scaleY(1.15) skewX(1deg);
        color: rgba(37, 99, 235, 0.85);
    }
}
</style>
