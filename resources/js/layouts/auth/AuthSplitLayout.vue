<script setup lang="ts">
import LocaleSwitcher from '@/components/LocaleSwitcher.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

defineProps<{
    title?: string;
    description?: string;
    kicker?: string;
}>();

const { t } = useI18n();
</script>

<template>
    <div class="mom-auth">
        <main class="page-shell">
            <section class="login-card" aria-label="Halaman masuk MoM">
                <aside
                    class="visual-panel"
                    style="background: hsl(var(--background))"
                    aria-label="Pratinjau notulen MoM"
                >
                    <div class="flex min-h-[690px] flex-col justify-center gap-10 p-10 max-sm:min-h-[260px] max-sm:p-6 max-sm:gap-6">
                        <div class="max-w-sm">
                            <p class="text-xs font-medium text-muted-foreground">{{ t('auth.heroKicker') }}</p>
                            <h2 class="mt-3 text-3xl font-semibold leading-tight tracking-[-0.03em] text-foreground">
                                {{ t('auth.heroTitle') }}
                            </h2>
                            <p class="mt-3 text-sm leading-6 text-muted-foreground">
                                {{ t('auth.heroDescription') }}
                            </p>
                        </div>

                        <div class="max-w-md rounded-2xl border border-border/80 bg-card p-5 text-card-foreground shadow-soft-md max-sm:hidden">
                            <div class="flex items-start justify-between gap-3 border-b border-border/70 pb-3">
                                <div>
                                    <p class="text-[11px] text-muted-foreground">{{ t('auth.previewLabel') }}</p>
                                    <p class="mt-0.5 text-sm font-semibold">{{ t('auth.previewTitle') }}</p>
                                </div>
                                <span class="rounded-md bg-success-soft px-2 py-0.5 text-[10px] font-medium text-success-strong">Final</span>
                            </div>

                            <div class="mt-4 space-y-1.5">
                                <p class="text-[11px] font-medium text-muted-foreground">{{ t('auth.previewDecision') }}</p>
                                <p class="text-sm leading-6">{{ t('auth.previewDecisionBody') }}</p>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-3 border-t border-border/70 pt-3 text-xs">
                                <div>
                                    <p class="text-muted-foreground">{{ t('auth.previewTask') }}</p>
                                    <p class="mt-0.5 font-medium">{{ t('auth.previewTaskTitle') }}</p>
                                </div>
                                <div>
                                    <p class="text-muted-foreground">{{ t('auth.previewDue') }}</p>
                                    <p class="mt-0.5 font-medium">{{ t('auth.previewDueDate') }}</p>
                                </div>
                            </div>

                            <div class="mt-4 flex items-center gap-2 text-xs text-muted-foreground">
                                <span class="inline-flex size-5 items-center justify-center rounded-full bg-accent text-[10px] font-semibold text-accent-foreground">RN</span>
                                {{ t('auth.previewPeople') }}
                            </div>
                        </div>
                    </div>
                </aside>

                <section class="form-panel">
                    <div class="form-content">
                        <div class="theme-corner flex items-center gap-1">
                            <LocaleSwitcher />
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
