<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Download } from 'lucide-vue-next';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';

interface InstallPromptEvent extends Event {
    prompt: () => Promise<void>;
    userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>;
}

const { t } = useI18n();
const deferredPrompt = ref<InstallPromptEvent | null>(null);
const visible = ref(false);

function handleBeforeInstall(event: Event) {
    if (window.matchMedia('(display-mode: standalone)').matches) {
        return;
    }

    event.preventDefault();
    deferredPrompt.value = event as InstallPromptEvent;

    if (localStorage.getItem('pwa-install-dismissed') !== '1') {
        visible.value = true;
    }
}

async function install() {
    if (!deferredPrompt.value) {
        return;
    }

    await deferredPrompt.value.prompt();
    const choice = await deferredPrompt.value.userChoice;

    if (choice.outcome === 'accepted') {
        visible.value = false;
    }

    deferredPrompt.value = null;
}

function dismiss() {
    visible.value = false;
    localStorage.setItem('pwa-install-dismissed', '1');
}

onMounted(() => window.addEventListener('beforeinstallprompt', handleBeforeInstall));
onBeforeUnmount(() => window.removeEventListener('beforeinstallprompt', handleBeforeInstall));
</script>

<template>
    <div
        v-if="visible"
        class="fixed bottom-[calc(5rem+env(safe-area-inset-bottom))] right-4 z-30 w-[min(22rem,calc(100vw-2rem))] rounded-xl border border-border/80 bg-surface p-4 shadow-popover md:bottom-6"
        role="dialog"
        :aria-label="t('pwa.installTitle')"
    >
        <div class="flex items-start gap-3">
            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                <Download class="size-4" aria-hidden="true" />
            </span>
            <div class="min-w-0">
                <p class="text-sm font-semibold">{{ t('pwa.installTitle') }}</p>
                <p class="mt-1 text-xs leading-relaxed text-muted-foreground">{{ t('pwa.installBody') }}</p>
                <div class="mt-3 flex items-center gap-2">
                    <Button size="sm" @click="install">{{ t('pwa.install') }}</Button>
                    <Button size="sm" variant="ghost" @click="dismiss">{{ t('pwa.later') }}</Button>
                </div>
            </div>
        </div>
    </div>
</template>
