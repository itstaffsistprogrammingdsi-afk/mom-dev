<script setup lang="ts">
import ThemeToggle from '@/components/ThemeToggle.vue';
import { Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
    kicker?: string;
}>();
</script>

<template>
    <div class="mom-auth">
        <div class="sky" aria-hidden="true">
            <span class="cloud cloud-one"></span>
            <span class="cloud cloud-two"></span>
            <span class="cloud cloud-three"></span>
        </div>

        <main class="page-shell">
            <section class="login-card" aria-label="Halaman masuk MoM">
                <aside class="visual-panel" aria-label="MoM, sistem notulensi modern">
                    <img
                        src="/images/mom-auth-visual.webp"
                        alt="Maskot MoM di atas bukit dengan ikon kalender, catatan, dan produktivitas"
                        class="visual-art"
                    />
                    <div class="visual-shade" aria-hidden="true"></div>
                </aside>

                <section class="form-panel">
                    <div class="form-content">
                        <div class="theme-corner">
                            <ThemeToggle />
                        </div>

                        <Link :href="route('home')" class="brand" aria-label="MoM beranda">
                            <span class="brand-mark" aria-hidden="true"><i></i><i></i></span>
                            <span>MoM<span class="brand-period">.</span></span>
                        </Link>

                        <div class="welcome-block">
                            <div class="welcome-kicker">{{ kicker ?? 'Sistem notulensi' }}</div>
                            <h1>{{ title }}</h1>
                            <p>{{ description }}</p>
                        </div>

                        <slot />
                    </div>
                </section>
            </section>
        </main>
    </div>
</template>
