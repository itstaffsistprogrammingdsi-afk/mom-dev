<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Camera, ImagePlus, X } from 'lucide-vue-next';
import { nextTick, onBeforeUnmount, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        modelValue: File | null;
        hint?: string;
    }>(),
    {
        hint: 'Ambil foto langsung dari kamera atau pilih gambar. Maksimal 5 MB.',
    },
);

const emit = defineEmits<{
    'update:modelValue': [file: File | null];
}>();

const galleryInput = ref<HTMLInputElement | null>(null);
const cameraInput = ref<HTMLInputElement | null>(null);
const previewUrl = ref<string | null>(null);
const cameraOpen = ref(false);
const cameraVideo = ref<HTMLVideoElement | null>(null);
const cameraStream = ref<MediaStream | null>(null);
const cameraError = ref<string | null>(null);
const cameraReady = ref(false);

watch(
    () => props.modelValue,
    (file) => {
        if (previewUrl.value) {
            URL.revokeObjectURL(previewUrl.value);
        }

        previewUrl.value = file ? URL.createObjectURL(file) : null;
    },
    { immediate: true },
);

function setFile(file: File | null) {
    emit('update:modelValue', file);
}

function onNativeSelected(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    if (file) {
        setFile(file);
    }

    input.value = '';
}

async function openCamera() {
    if (!navigator.mediaDevices?.getUserMedia) {
        useNativeCamera();
        return;
    }

    cameraError.value = null;
    cameraReady.value = false;
    cameraOpen.value = true;

    await nextTick();

    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: { ideal: 'environment' } },
            audio: false,
        });

        if (!cameraOpen.value) {
            stream.getTracks().forEach((track) => track.stop());
            return;
        }

        cameraStream.value = stream;
        const video = cameraVideo.value;

        if (video) {
            video.srcObject = stream;

            await video.play();
            cameraReady.value = true;
        }
    } catch {
        cameraError.value = 'Kamera tidak dapat diakses. Izinkan akses kamera di browser atau gunakan kamera perangkat.';
    }
}

function stopCamera() {
    cameraStream.value?.getTracks().forEach((track) => track.stop());
    cameraStream.value = null;

    if (cameraVideo.value) {
        cameraVideo.value.srcObject = null;
    }

    cameraReady.value = false;
}

function closeCamera() {
    stopCamera();
    cameraOpen.value = false;
}

function useNativeCamera() {
    closeCamera();
    cameraInput.value?.click();
}

async function capturePhoto() {
    const video = cameraVideo.value;

    if (!video || video.videoWidth === 0) {
        return;
    }

    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    const context = canvas.getContext('2d');

    if (!context) {
        return;
    }

    context.drawImage(video, 0, 0, canvas.width, canvas.height);

    const blob = await new Promise<Blob | null>((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.9));

    if (!blob) {
        return;
    }

    const stamp = new Date().toISOString().replace(/[:.]/g, '-');
    setFile(new File([blob], `kamera-${stamp}.jpg`, { type: 'image/jpeg' }));
    closeCamera();
}

function clearPhoto() {
    setFile(null);

    if (cameraInput.value) {
        cameraInput.value.value = '';
    }

    if (galleryInput.value) {
        galleryInput.value.value = '';
    }
}

onBeforeUnmount(() => {
    stopCamera();

    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }
});
</script>

<template>
    <div class="space-y-2">
        <div class="flex flex-wrap gap-2">
            <Button type="button" variant="outline" size="sm" @click="openCamera">
                <Camera class="size-4" aria-hidden="true" />
                Ambil foto
            </Button>
            <Button type="button" variant="outline" size="sm" @click="galleryInput?.click()">
                <ImagePlus class="size-4" aria-hidden="true" />
                Pilih gambar
            </Button>
        </div>

        <input
            ref="cameraInput"
            type="file"
            accept="image/*"
            capture="environment"
            class="sr-only"
            aria-label="Ambil foto dengan kamera perangkat"
            @change="onNativeSelected"
        />
        <input ref="galleryInput" type="file" accept="image/*" class="sr-only" aria-label="Pilih gambar dari galeri" @change="onNativeSelected" />

        <div v-if="previewUrl" class="relative w-fit">
            <img :src="previewUrl" alt="Pratinjau foto respons" class="max-h-40 rounded-md border border-border object-cover" />
            <Button
                type="button"
                size="icon"
                variant="destructive"
                class="absolute right-1.5 top-1.5 size-7"
                aria-label="Hapus foto"
                @click="clearPhoto"
            >
                <X class="size-3.5" aria-hidden="true" />
            </Button>
        </div>
        <p v-else class="text-xs leading-5 text-muted-foreground">{{ props.hint }}</p>
    </div>

    <Dialog
        v-model:open="cameraOpen"
        @update:open="
            (open) => {
                if (!open) closeCamera();
            }
        "
    >
        <DialogContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>Ambil foto</DialogTitle>
                <DialogDescription>Posisikan objek di depan kamera, lalu tekan Jepret. Foto akan dilampirkan pada respons ini.</DialogDescription>
            </DialogHeader>

            <div class="relative overflow-hidden rounded-lg border border-border bg-slate-950">
                <video ref="cameraVideo" class="aspect-video w-full object-cover" autoplay muted playsinline />
                <div
                    v-if="cameraError"
                    class="absolute inset-0 flex items-center justify-center bg-slate-950/85 p-4 text-center text-sm text-white"
                    role="alert"
                >
                    {{ cameraError }}
                </div>
            </div>

            <DialogFooter>
                <Button type="button" variant="outline" @click="closeCamera">Batal</Button>
                <Button v-if="!cameraError" type="button" :disabled="!cameraReady" @click="capturePhoto">
                    <Camera class="size-4" aria-hidden="true" />
                    Jepret
                </Button>
                <Button v-else type="button" variant="outline" @click="useNativeCamera">
                    <Camera class="size-4" aria-hidden="true" />
                    Gunakan kamera perangkat
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
