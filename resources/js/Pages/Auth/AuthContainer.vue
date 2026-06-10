<script setup>
import { Head, useForm, usePage, router, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import GraffitiLogo from '@/Components/GraffitiLogo.vue';
import FeedbackOverlay from '@/Components/FeedbackOverlay.vue';

const props = defineProps({
    initialMode: {
        type: String,
        default: 'login', // 'login' or 'register'
    },
    status: {
        type: String,
        default: '',
    },
});

const page = usePage();
const mode = ref(props.initialMode);
const rainCanvas = ref(null);

// Forms setup
const loginForm = useForm({
    email: '',
    password: '',
    remember: false,
});

const registerForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submitLogin = () => {
    loginForm.post(route('login'), {
        onFinish: () => loginForm.reset('password'),
    });
};

const submitRegister = () => {
    registerForm.post(route('register'), {
        onFinish: () => registerForm.reset('password', 'password_confirmation'),
    });
};

// Mode Switch Handler with smooth url update
const switchMode = (newMode) => {
    mode.value = newMode;
    const url = newMode === 'register' ? route('register') : route('login');
    window.history.pushState({ mode: newMode }, '', url);
};

// Popstate listener to support browser navigation (Back/Forward)
const handlePopState = (e) => {
    const path = window.location.pathname;
    if (path.includes('register')) {
        mode.value = 'register';
    } else {
        mode.value = 'login';
    }
};

onMounted(() => {
    window.addEventListener('popstate', handlePopState);

    // Setup Canvas Falling Water Rain Animation
    const canvas = rainCanvas.value;
    if (canvas) {
        const ctx = canvas.getContext('2d');
        
        let width = (canvas.width = window.innerWidth);
        let height = (canvas.height = window.innerHeight);
        
        const handleResize = () => {
            width = canvas.width = window.innerWidth;
            height = canvas.height = window.innerHeight;
        };
        window.addEventListener('resize', handleResize);
        
        // Rain particles
        const maxParticles = 75;
        const particles = [];
        
        for (let i = 0; i < maxParticles; i++) {
            particles.push({
                x: Math.random() * width,
                y: Math.random() * height,
                length: Math.random() * 30 + 15,
                speed: Math.random() * 4 + 3,
                opacity: Math.random() * 0.45 + 0.15,
                width: Math.random() * 1.5 + 0.5
            });
        }
        
        let animationFrameId;
        const draw = () => {
            ctx.clearRect(0, 0, width, height);
            
            for (let i = 0; i < maxParticles; i++) {
                const p = particles[i];
                
                // Translucent cyan-blue gradient trails for futuristic digital rain
                const grad = ctx.createLinearGradient(p.x, p.y, p.x, p.y + p.length);
                grad.addColorStop(0, 'rgba(6, 182, 212, 0)');
                grad.addColorStop(1, `rgba(37, 99, 235, ${p.opacity})`);
                
                ctx.strokeStyle = grad;
                ctx.lineWidth = p.width;
                ctx.lineCap = 'round';
                ctx.beginPath();
                ctx.moveTo(p.x, p.y);
                ctx.lineTo(p.x, p.y + p.length);
                ctx.stroke();
                
                // Move down
                p.y += p.speed;
                
                // Loop when going off screen
                if (p.y > height) {
                    p.x = Math.random() * width;
                    p.y = -p.length;
                    p.speed = Math.random() * 4 + 3;
                }
            }
            
            animationFrameId = requestAnimationFrame(draw);
        };
        
        draw();
        
        onUnmounted(() => {
            window.removeEventListener('resize', handleResize);
            cancelAnimationFrame(animationFrameId);
        });
    }
});

onUnmounted(() => {
    window.removeEventListener('popstate', handlePopState);
});

</script>

<template>
    <Head :title="mode === 'register' ? 'Daftar Akun - QMS Terintegrasi' : 'Login - QMS Terintegrasi'" />

    <div class="auth-wrapper">
        <!-- Canvas for falling water rain animation in background -->
        <canvas ref="rainCanvas" class="rain-canvas"></canvas>

        <!-- Floating background blobs -->
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>

        <div class="auth-container" :class="{ 'right-panel-active': mode === 'register' }">
            
            <!-- ====== 1. REGISTER FORM CONTAINER ====== -->
            <div class="form-container sign-up-container">
                <form @submit.prevent="submitRegister">
                    <div class="form-header">
                        <GraffitiLogo />
                        <h3 class="auth-title">Registrasi Akun Baru</h3>
                        <p class="auth-subtitle">Gabung ke pemastian mutu terpadu</p>
                    </div>

                    <div class="form-group">
                        <label for="name" class="form-label">Nama Lengkap</label>
                        <input
                            id="name"
                            type="text"
                            class="form-input"
                            v-model="registerForm.name"
                            required
                            placeholder="Nama Lengkap Anda"
                        />
                        <div v-if="registerForm.errors.name" class="form-error">
                            {{ registerForm.errors.name }}
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="reg-email" class="form-label">Alamat Email</label>
                        <input
                            id="reg-email"
                            type="email"
                            class="form-input"
                            v-model="registerForm.email"
                            required
                            placeholder="contoh@qms.com"
                        />
                        <div v-if="registerForm.errors.email" class="form-error">
                            {{ registerForm.errors.email }}
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="reg-password" class="form-label">Password</label>
                        <input
                            id="reg-password"
                            type="password"
                            class="form-input"
                            v-model="registerForm.password"
                            required
                            placeholder="Minimal 8 karakter"
                        />
                        <div v-if="registerForm.errors.password" class="form-error">
                            {{ registerForm.errors.password }}
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
                        <input
                            id="password_confirmation"
                            type="password"
                            class="form-input"
                            v-model="registerForm.password_confirmation"
                            required
                            placeholder="Ulangi password"
                        />
                        <div v-if="registerForm.errors.password_confirmation" class="form-error">
                            {{ registerForm.errors.password_confirmation }}
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="btn btn-primary auth-submit-btn"
                        :disabled="registerForm.processing"
                    >
                        {{ registerForm.processing ? 'Mendaftarkan...' : 'Daftar Sekarang' }}
                    </button>

                    <!-- Mobile Fallback Switch Link -->
                    <div class="mobile-only-link">
                        Sudah punya akun? 
                        <a href="#" @click.prevent="switchMode('login')" class="cyber-link">Masuk di sini</a>
                    </div>
                </form>
            </div>

            <!-- ====== 2. LOGIN FORM CONTAINER ====== -->
            <div class="form-container sign-in-container">
                <form @submit.prevent="submitLogin">
                    <div class="form-header">
                        <GraffitiLogo />
                        <h3 class="auth-title">Masuk ke Portal</h3>
                        <p class="auth-subtitle">Gunakan kredensial QMS terdaftar</p>
                    </div>

                    <div v-if="status" class="status-banner">
                        {{ status }}
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Alamat Email</label>
                        <input
                            id="email"
                            type="email"
                            class="form-input"
                            v-model="loginForm.email"
                            required
                            placeholder="contoh@qms.com"
                        />
                        <div v-if="loginForm.errors.email" class="form-error">
                            {{ loginForm.errors.email }}
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input
                            id="password"
                            type="password"
                            class="form-input"
                            v-model="loginForm.password"
                            required
                            placeholder="••••••••"
                        />
                        <div v-if="loginForm.errors.password" class="form-error">
                            {{ loginForm.errors.password }}
                        </div>
                    </div>

                    <div class="form-utilities">
                        <div class="remember-box">
                            <input
                                type="checkbox"
                                id="remember"
                                v-model="loginForm.remember"
                                class="remember-checkbox"
                            />
                            <label for="remember" class="remember-label">
                                Ingat Saya
                            </label>
                        </div>
                        <Link :href="route('password.request')" class="cyber-link" style="font-size: 0.775rem;">
                            Lupa Password?
                        </Link>
                    </div>

                    <button
                        type="submit"
                        class="btn btn-primary auth-submit-btn"
                        :disabled="loginForm.processing"
                    >
                        {{ loginForm.processing ? 'Sedang Masuk...' : 'Masuk Portal' }}
                    </button>

                    <!-- Mobile Fallback Switch Link -->
                    <div class="mobile-only-link">
                        Belum punya akun? 
                        <a href="#" @click.prevent="switchMode('register')" class="cyber-link">Daftar di sini</a>
                    </div>

                    <!-- Demo Accounts Box -->
                    <div class="demo-box">
                        <div class="demo-header">Akun Uji Coba Demo:</div>
                        <div class="demo-list">
                            <div>Inisiator: <strong>initiator@qms.com</strong> / password</div>
                            <div>QA Officer: <strong>qa@qms.com</strong> / password</div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- ====== 3. SLIDING OVERLAY COVER PANEL ====== -->
            <div class="overlay-container">
                <div class="overlay">
                    
                    <!-- Overlay Left: Displayed when REGISTER is active (shows button to switch to LOGIN) -->
                    <div class="overlay-panel overlay-left">
                        <h2 class="overlay-title">Sudah Terdaftar?</h2>
                        <p class="overlay-desc">
                            Untuk tetap memantau dokumen mutu Anda, silakan masuk ke sistem menggunakan akun Anda.
                        </p>
                        <button @click="switchMode('login')" class="overlay-btn">
                            Sign In / Masuk
                        </button>
                    </div>

                    <!-- Overlay Right: Displayed when LOGIN is active (shows button to switch to REGISTER) -->
                    <div class="overlay-panel overlay-right">
                        <h2 class="overlay-title">Halo, Rekan!</h2>
                        <p class="overlay-desc">
                            Daftarkan akun baru Anda untuk mengajukan Change Request dan mengunggah tindakan CAPA.
                        </p>
                        <button @click="switchMode('register')" class="overlay-btn">
                            Sign Up / Daftar
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <!-- Global Loading & Validation Alert Overlay -->
        <FeedbackOverlay />
    </div>
</template>

<style scoped>
.auth-wrapper {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-tertiary) 100%);
    padding: 20px;
    font-family: var(--font-inter);
    position: relative;
    overflow: hidden;
    perspective: 1500px;
}

/* Background Canvas Rain */
.rain-canvas {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1;
    pointer-events: none;
    opacity: 0.18; /* Subtle cyber-rain backdrop */
}

/* Floating Futuristic Blobs */
.blob {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    z-index: 0;
    opacity: 0.45;
    animation: float 20s infinite alternate ease-in-out;
}
.blob-1 {
    width: 450px;
    height: 450px;
    background: rgba(37, 99, 235, 0.35);
    top: -10%;
    left: -10%;
}
.blob-2 {
    width: 500px;
    height: 500px;
    background: rgba(139, 92, 246, 0.25);
    bottom: -15%;
    right: -10%;
    animation-delay: -5s;
}
.blob-3 {
    width: 350px;
    height: 350px;
    background: rgba(6, 182, 212, 0.2);
    top: 40%;
    left: 45%;
    animation-delay: -10s;
}
@keyframes float {
    0% { transform: translate(0, 0) scale(1) rotate(0deg); }
    50% { transform: translate(60px, 90px) scale(1.1) rotate(180deg); }
    100% { transform: translate(-40px, -60px) scale(0.9) rotate(360deg); }
}

/* Auth Card Container - Glassmorphism style */
.auth-container {
    background: rgba(255, 255, 255, 0.72);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: 24px;
    box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.08), 
                0 1px 3px rgba(255, 255, 255, 0.3) inset,
                0 0 40px rgba(37, 99, 235, 0.05);
    position: relative;
    overflow: hidden;
    width: 860px;
    max-width: 100%;
    min-height: 590px;
    z-index: 5;
    transform-style: preserve-3d;
    transition: box-shadow 0.3s ease;
}

html.dark .auth-container {
    background: rgba(21, 29, 48, 0.75);
    border: 1px solid rgba(255, 255, 255, 0.06);
    box-shadow: 0 30px 60px -20px rgba(0, 0, 0, 0.4), 
                0 1px 2px rgba(255, 255, 255, 0.05) inset,
                0 0 50px rgba(59, 130, 246, 0.1);
}

/* 3D Squeeze-Tilt motion keyframes for futuristic transition */
.auth-container.right-panel-active {
    animation: flip-squeeze-right 0.85s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
}
.auth-container:not(.right-panel-active) {
    animation: flip-squeeze-left 0.85s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
}

@keyframes flip-squeeze-right {
    0% { transform: scale(1) rotateY(0deg) rotateX(0deg); }
    40% { transform: scale(0.92) rotateY(-12deg) rotateX(1deg) skewY(-0.5deg); }
    70% { transform: scale(0.98) rotateY(3deg); }
    100% { transform: scale(1) rotateY(0deg) rotateX(0deg); }
}

@keyframes flip-squeeze-left {
    0% { transform: scale(1) rotateY(0deg) rotateX(0deg); }
    40% { transform: scale(0.92) rotateY(12deg) rotateX(-1deg) skewY(0.5deg); }
    70% { transform: scale(0.98) rotateY(-3deg); }
    100% { transform: scale(1) rotateY(0deg) rotateX(0deg); }
}

/* Form layouts */
.form-container {
    position: absolute;
    top: 0;
    height: 100%;
    transition: all 0.6s ease-in-out;
}

.sign-in-container {
    left: 0;
    width: 50%;
    z-index: 2;
    padding: 44px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.sign-up-container {
    left: 0;
    width: 50%;
    opacity: 0;
    z-index: 1;
    padding: 44px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

/* Panel switching logic */
.auth-container.right-panel-active .sign-in-container {
    transform: translateX(100%);
    opacity: 0;
    z-index: 1;
}

.auth-container.right-panel-active .sign-up-container {
    transform: translateX(100%);
    opacity: 1;
    z-index: 5;
    animation: show 0.6s;
}

@keyframes show {
    0%, 49.99% { opacity: 0; z-index: 1; }
    50%, 100% { opacity: 1; z-index: 5; }
}

/* Sequential Staggered reveal for form elements */
.form-container form > * {
    transform: translateY(20px);
    opacity: 0;
    transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.auth-container:not(.right-panel-active) .sign-in-container form > *:nth-child(1) { transform: translateY(0); opacity: 1; transition-delay: 0.15s; }
.auth-container:not(.right-panel-active) .sign-in-container form > *:nth-child(2) { transform: translateY(0); opacity: 1; transition-delay: 0.22s; }
.auth-container:not(.right-panel-active) .sign-in-container form > *:nth-child(3) { transform: translateY(0); opacity: 1; transition-delay: 0.29s; }
.auth-container:not(.right-panel-active) .sign-in-container form > *:nth-child(4) { transform: translateY(0); opacity: 1; transition-delay: 0.36s; }
.auth-container:not(.right-panel-active) .sign-in-container form > *:nth-child(5) { transform: translateY(0); opacity: 1; transition-delay: 0.43s; }
.auth-container:not(.right-panel-active) .sign-in-container form > *:nth-child(6) { transform: translateY(0); opacity: 1; transition-delay: 0.50s; }
.auth-container:not(.right-panel-active) .sign-in-container form > *:nth-child(7) { transform: translateY(0); opacity: 1; transition-delay: 0.57s; }

.auth-container.right-panel-active .sign-up-container form > *:nth-child(1) { transform: translateY(0); opacity: 1; transition-delay: 0.15s; }
.auth-container.right-panel-active .sign-up-container form > *:nth-child(2) { transform: translateY(0); opacity: 1; transition-delay: 0.22s; }
.auth-container.right-panel-active .sign-up-container form > *:nth-child(3) { transform: translateY(0); opacity: 1; transition-delay: 0.29s; }
.auth-container.right-panel-active .sign-up-container form > *:nth-child(4) { transform: translateY(0); opacity: 1; transition-delay: 0.36s; }
.auth-container.right-panel-active .sign-up-container form > *:nth-child(5) { transform: translateY(0); opacity: 1; transition-delay: 0.43s; }
.auth-container.right-panel-active .sign-up-container form > *:nth-child(6) { transform: translateY(0); opacity: 1; transition-delay: 0.50s; }
.auth-container.right-panel-active .sign-up-container form > *:nth-child(7) { transform: translateY(0); opacity: 1; transition-delay: 0.57s; }
.auth-container.right-panel-active .sign-up-container form > *:nth-child(8) { transform: translateY(0); opacity: 1; transition-delay: 0.64s; }

/* Sub elements */
.form-header {
    text-align: center;
    margin-bottom: 24px;
}

/* Cyberpunk Graffiti marking container for logo */
.graffiti-logo-container {
    position: relative;
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    margin: 0 auto 10px;
    padding: 10px 22px;
}
/* Background graffiti spray paint mark behind the logo */
.graffiti-logo-container::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 140%;
    height: 140%;
    transform: translate(-50%, -50%) rotate(-4deg);
    background: radial-gradient(circle, rgba(6, 182, 212, 0.22) 0%, rgba(37, 99, 235, 0.08) 45%, rgba(0, 0, 0, 0) 70%);
    filter: blur(5px);
    z-index: -1;
    pointer-events: none;
}
/* Graffiti stencil framing border lines (stamps) */
.graffiti-logo-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border-top: 2px solid rgba(6, 182, 212, 0.45);
    border-bottom: 2px solid rgba(37, 99, 235, 0.45);
    border-left: 2px dashed rgba(6, 182, 212, 0.2);
    border-right: 2px dashed rgba(37, 99, 235, 0.2);
    border-radius: 8px;
    transform: rotate(2deg);
    pointer-events: none;
    animation: pulseGlow 4s infinite alternate ease-in-out;
}
@keyframes pulseGlow {
    0% {
        border-color: rgba(6, 182, 212, 0.4) rgba(37, 99, 235, 0.4);
        box-shadow: 0 0 10px rgba(6, 182, 212, 0.08), 0 0 20px rgba(37, 99, 235, 0.04);
        transform: rotate(2deg) scale(0.99);
    }
    100% {
        border-color: rgba(37, 99, 235, 0.7) rgba(6, 182, 212, 0.7);
        box-shadow: 0 0 15px rgba(37, 99, 235, 0.2), 0 0 30px rgba(6, 182, 212, 0.15);
        transform: rotate(1deg) scale(1.02);
    }
}

.logo-image {
    max-height: 48px;
    object-fit: contain;
    filter: drop-shadow(0 0 8px rgba(6, 182, 212, 0.55)) 
            drop-shadow(0 0 22px rgba(37, 99, 235, 0.38));
    transition: all 0.3s ease;
    z-index: 2;
}
.logo-image:hover {
    transform: scale(1.05) rotate(-1deg);
    filter: drop-shadow(0 0 12px rgba(6, 182, 212, 0.78)) 
            drop-shadow(0 0 32px rgba(37, 99, 235, 0.58));
}
.logo-text {
    font-size: 1.65rem;
    color: var(--accent-color);
    font-weight: 800;
    letter-spacing: -0.03em;
    filter: drop-shadow(0 0 8px rgba(6, 182, 212, 0.45));
}

.auth-title {
    font-size: 1.25rem;
    font-weight: 700;
    margin-top: 4px;
    color: var(--text-primary);
}
.auth-subtitle {
    color: var(--text-secondary);
    font-size: 0.8rem;
    margin-top: 2px;
}

.form-group {
    margin-bottom: 14px;
    display: flex;
    flex-direction: column;
}
.form-label {
    font-size: 0.775rem;
    margin-bottom: 5px;
    font-weight: 500;
    color: var(--text-secondary);
}
.form-input {
    padding: 8px 14px;
    font-size: 0.875rem;
    border-radius: 8px;
    background-color: var(--bg-primary);
    border: 1px solid var(--border-color);
    color: var(--text-primary);
    transition: all 0.2s ease;
}
.form-input:focus {
    border-color: var(--accent-color);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}
.form-error {
    color: #ef4444;
    font-size: 0.75rem;
    margin-top: 4px;
}

.form-utilities {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
}
.remember-box {
    display: flex;
    align-items: center;
    gap: 6px;
}
.remember-checkbox {
    width: 14px;
    height: 14px;
    accent-color: var(--accent-color);
    cursor: pointer;
}
.remember-label {
    font-size: 0.775rem;
    color: var(--text-secondary);
    cursor: pointer;
    user-select: none;
}
.cyber-link {
    color: var(--accent-color);
    text-decoration: none;
    font-weight: 600;
    transition: color 0.2s ease;
}
.cyber-link:hover {
    color: var(--accent-hover);
    text-decoration: underline;
}

.auth-submit-btn {
    width: 100%;
    padding: 11px;
    font-size: 0.875rem;
    font-weight: 600;
    border-radius: 8px;
    background-color: var(--accent-color);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}
.auth-submit-btn:hover {
    background-color: var(--accent-hover);
    box-shadow: 0 6px 16px rgba(37, 99, 235, 0.3);
}

.status-banner {
    margin-bottom: 16px;
    padding: 10px;
    border-radius: 8px;
    background-color: var(--status-complete-bg);
    color: var(--status-complete-text);
    font-size: 0.8rem;
    text-align: center;
    border: 1px solid rgba(16, 185, 129, 0.2);
}

.demo-box {
    margin-top: 20px;
    padding: 10px 14px;
    border-radius: 8px;
    background-color: rgba(var(--text-muted), 0.08);
    border: 1px solid var(--border-color);
    font-size: 0.725rem;
    color: var(--text-secondary);
}
html.dark .demo-box {
    background-color: rgba(255, 255, 255, 0.02);
}
.demo-header {
    font-weight: 700;
    margin-bottom: 4px;
    color: var(--text-primary);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.demo-list {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

/* Sliding Overlay panel */
.overlay-container {
    position: absolute;
    top: 0;
    left: 50%;
    width: 50%;
    height: 100%;
    overflow: hidden;
    transition: transform 0.6s ease-in-out;
    z-index: 100;
    border-left: 1px solid rgba(255, 255, 255, 0.1);
}
.auth-container.right-panel-active .overlay-container {
    transform: translateX(-100%);
    border-left: none;
    border-right: 1px solid rgba(255, 255, 255, 0.1);
}

.overlay {
    background: linear-gradient(135deg, var(--accent-color) 0%, #1e3a8a 50%, #4f46e5 100%);
    background-size: 200% 200%;
    animation: gradientShift 10s infinite alternate ease-in-out;
    color: #ffffff;
    position: relative;
    left: -100%;
    height: 100%;
    width: 200%;
    transform: translateX(0);
    transition: transform 0.6s ease-in-out;
}
@keyframes gradientShift {
    0% { background-position: 0% 50%; }
    100% { background-position: 100% 50%; }
}

.auth-container.right-panel-active .overlay {
    transform: translateX(50%);
}

.overlay-panel {
    position: absolute;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    padding: 0 44px;
    text-align: center;
    top: 0;
    height: 100%;
    width: 50%;
    transform: translateX(0);
    transition: transform 0.6s ease-in-out;
}

.overlay-left {
    transform: translateX(-20%);
}
.auth-container.right-panel-active .overlay-left {
    transform: translateX(0);
}

.overlay-right {
    right: 0;
    transform: translateX(0);
}
.auth-container.right-panel-active .overlay-right {
    transform: translateX(20%);
}

.overlay-title {
    font-size: 2rem;
    font-weight: 800;
    font-family: var(--font-outfit);
    margin-bottom: 12px;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
}
.overlay-desc {
    font-size: 0.9rem;
    opacity: 0.88;
    line-height: 1.6;
    margin-bottom: 24px;
}

.overlay-btn {
    background-color: transparent;
    border: 2px solid #ffffff;
    color: #ffffff;
    font-size: 0.85rem;
    font-weight: 600;
    padding: 10px 24px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.25s ease;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}
.overlay-btn:hover {
    background-color: #ffffff;
    color: var(--accent-color);
    box-shadow: 0 6px 16px rgba(0,0,0,0.2);
    transform: translateY(-1px);
}

.mobile-only-link {
    display: none;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .mobile-only-link {
        display: block;
        text-align: center;
        margin-top: 16px;
        font-size: 0.8rem;
        color: var(--text-secondary);
    }
    .auth-container {
        width: 100%;
        min-height: auto;
        display: flex;
        flex-direction: column;
        border-radius: 16px;
        animation: none !important;
    }
    .form-container {
        position: relative;
        width: 100% !important;
        transform: none !important;
        opacity: 1 !important;
        z-index: 5 !important;
        display: none;
        padding: 32px 20px;
    }
    .auth-container:not(.right-panel-active) .sign-in-container {
        display: flex;
    }
    .auth-container.right-panel-active .sign-up-container {
        display: flex;
        animation: none;
    }
    .overlay-container {
        display: none;
    }
    .form-container form > * {
        transform: none !important;
        opacity: 1 !important;
        transition: none !important;
    }
}
</style>
