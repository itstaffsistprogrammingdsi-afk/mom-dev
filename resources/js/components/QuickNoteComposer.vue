<script setup lang="ts">
import MeetingEditor from '@/components/MeetingEditor.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { getInitials } from '@/composables/useInitials';
import { extractPersonMentions } from '@/lib/mentions';
import { countImages } from '@/lib/tiptap';
import type { Division, QuickNote } from '@/types';
import { Link, useForm } from '@inertiajs/vue3';
import type { JSONContent } from '@tiptap/core';
import { Camera, Clock3, ImagePlus, StickyNote, Trash2, Users } from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';

interface Props {
    note?: QuickNote | null;
    users: { id: number; name: string; email?: string; division_id?: number | null }[];
    divisions: Division[];
    topics: { id: number; name: string }[];
    responseOptions: { value: number; label: string }[];
    emptyContent: Record<string, unknown>;
}

const props = defineProps<Props>();

interface QuickNoteForm {
    title: string;
    content: JSONContent;
    mentions: { user_id: number; response_hours: number }[];
    photos: File[];
}

const isEdit = computed(() => Boolean(props.note));
const form = useForm<QuickNoteForm>({
    title: props.note?.title ?? '',
    content: (props.note?.content ?? props.emptyContent) as unknown as JSONContent,
    mentions: [],
    photos: [],
});

const responseHours = ref<Record<number, number>>(
    Object.fromEntries(
        (props.note?.mentions ?? []).filter((mention) => mention.user_id).map((mention) => [mention.user_id as number, mention.response_hours]),
    ),
);

const mentionedPeople = computed(() => extractPersonMentions(form.content));
const usersById = computed(() => new Map(props.users.map((user) => [user.id, user])));

const editorRef = ref<{ insertPhoto: (src: string) => void; removeImages: () => void; focusEditor: () => void } | null>(null);
const cameraInput = ref<HTMLInputElement | null>(null);
const galleryInput = ref<HTMLInputElement | null>(null);
const cameraOpen = ref(false);
const cameraVideo = ref<HTMLVideoElement | null>(null);
const cameraStream = ref<MediaStream | null>(null);
const cameraError = ref<string | null>(null);
const cameraReady = ref(false);
const selectedPhotoUrls = ref<string[]>([]);
const imageCount = computed(() => countImages(form.content));

function applyPhotoFile(file: File) {
    form.photos = [...form.photos, file];
    const url = URL.createObjectURL(file);
    selectedPhotoUrls.value = [...selectedPhotoUrls.value, url];
    editorRef.value?.insertPhoto(url);
}

function onPhotoSelected(event: Event) {
    const input = event.target as HTMLInputElement;
    const files = Array.from(input.files ?? []);

    for (const file of files) {
        applyPhotoFile(file);
    }

    editorRef.value?.focusEditor();
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

function focusComposer() {
    nextTick(() => editorRef.value?.focusEditor());
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
    applyPhotoFile(new File([blob], `kamera-${stamp}.jpg`, { type: 'image/jpeg' }));
    closeCamera();

    setTimeout(() => editorRef.value?.focusEditor(), 80);
}

function clearPhotos() {
    form.photos = [];
    editorRef.value?.removeImages();
    selectedPhotoUrls.value.forEach((url) => URL.revokeObjectURL(url));
    selectedPhotoUrls.value = [];

    if (cameraInput.value) {
        cameraInput.value.value = '';
    }

    if (galleryInput.value) {
        galleryInput.value.value = '';
    }
}

onBeforeUnmount(() => {
    stopCamera();

    const urls = selectedPhotoUrls.value;
    setTimeout(() => urls.forEach((url) => URL.revokeObjectURL(url)), 1000);
});

watch(
    mentionedPeople,
    (people) => {
        const ids = new Set(people.map((person) => person.id));
        const next = { ...responseHours.value };

        for (const person of people) {
            if (next[person.id] === undefined) {
                next[person.id] = 24;
            }
        }

        for (const key of Object.keys(next)) {
            if (!ids.has(Number(key))) {
                delete next[Number(key)];
            }
        }

        responseHours.value = next;
    },
    { immediate: true, deep: true },
);

function setHours(userId: number, value: string | number) {
    responseHours.value = { ...responseHours.value, [userId]: Number(value) };
}

function submit() {
    form.mentions = mentionedPeople.value.map((person) => ({
        user_id: person.id,
        response_hours: responseHours.value[person.id] ?? 24,
    }));

    if (props.note) {
        form.patch(route('quick-notes.update', props.note.id), { forceFormData: true });
        return;
    }

    form.post(route('quick-notes.store'), { forceFormData: true });
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px] lg:items-start">
            <div class="min-w-0 space-y-6">
                <section class="rounded-lg border border-border bg-card p-5 md:p-6">
                    <div class="space-y-1.5">
                        <Label for="quick-note-title">Judul</Label>
                        <Input
                            id="quick-note-title"
                            v-model="form.title"
                            class="h-11 text-base font-semibold"
                            placeholder="Contoh: Ide perbaikan alur approval"
                            :aria-invalid="Boolean(form.errors.title)"
                            :aria-describedby="form.errors.title ? 'quick-note-title-error' : undefined"
                        />
                        <p v-if="form.errors.title" id="quick-note-title-error" class="text-xs font-medium text-destructive" role="alert">
                            {{ form.errors.title }}
                        </p>
                        <p class="text-xs text-muted-foreground">Kosongkan untuk mengambil judul dari isi catatan.</p>
                    </div>

                    <div class="mt-5 space-y-2">
                        <div class="flex flex-wrap items-end justify-between gap-2">
                            <Label>Isi catatan</Label>
                            <p class="text-xs text-muted-foreground">
                                Tag orang dengan <span class="font-semibold text-foreground">@nama</span>, topik dengan
                                <span class="font-semibold text-foreground">#topik</span>.
                            </p>
                        </div>
                        <MeetingEditor
                            ref="editorRef"
                            v-model="form.content"
                            :users="props.users"
                            :divisions="props.divisions"
                            :topics="props.topics"
                            placeholder="Tulis inspirasi atau temuan cepat di sini..."
                        />
                    </div>
                </section>

                <section class="rounded-lg border border-border bg-card p-5 md:p-6">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-semibold">Foto ide</h2>
                            <p class="mt-1 text-xs leading-5 text-muted-foreground">
                                Ambil foto langsung dari kamera atau pilih gambar. Foto akan masuk ke isi catatan. Maksimal 5 MB.
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <Button type="button" variant="outline" @click="openCamera">
                                <Camera class="size-4" />
                                Ambil foto
                            </Button>
                            <Button type="button" variant="outline" @click="galleryInput?.click()">
                                <ImagePlus class="size-4" />
                                Pilih gambar
                            </Button>
                        </div>
                    </div>

                    <input
                        ref="cameraInput"
                        type="file"
                        accept="image/*"
                        capture="environment"
                        class="sr-only"
                        aria-label="Ambil foto dengan kamera"
                        @change="onPhotoSelected"
                    />
                    <input
                        ref="galleryInput"
                        type="file"
                        accept="image/*"
                        multiple
                        class="sr-only"
                        aria-label="Pilih gambar dari galeri"
                        @change="onPhotoSelected"
                    />

                    <div
                        v-if="imageCount > 0"
                        class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-md border border-success/25 bg-success/10 p-3"
                    >
                        <p class="text-xs leading-5 text-success-strong">
                            {{ imageCount }} foto sudah masuk ke isi catatan. Tambahkan keterangan atau tag di bawah foto, misalnya
                            <span class="font-semibold">@nama yang kaya gini bagus</span>.
                        </p>
                        <Button type="button" variant="ghost" class="text-destructive-strong" @click="clearPhotos">
                            <Trash2 class="size-4" />
                            Hapus semua foto
                        </Button>
                    </div>
                    <p v-else class="mt-3 text-xs leading-5 text-muted-foreground">
                        Setelah Jepret, foto langsung masuk ke isi catatan. Kursor berada di bawah foto agar Anda bisa langsung menulis keterangan
                        atau men-tag orang.
                    </p>

                    <p v-if="form.errors.photos" class="mt-2 text-xs font-medium text-destructive" role="alert">
                        {{ form.errors.photos }}
                    </p>
                </section>
            </div>

            <aside class="min-w-0 space-y-4 lg:sticky lg:top-20">
                <section class="rounded-lg border border-border bg-card p-5">
                    <h2 class="flex items-center gap-2 text-sm font-semibold">
                        <Users class="size-4 text-muted-foreground" />
                        Penerima dan batas respons
                    </h2>
                    <p class="mt-1 text-xs leading-5 text-muted-foreground">
                        Setiap orang yang di-tag punya batas respons sendiri. Nilai tampil di samping tag pada catatan.
                    </p>

                    <div v-if="mentionedPeople.length === 0" class="mt-4 rounded-md border border-dashed border-border bg-muted/20 p-4">
                        <StickyNote class="size-5 text-muted-foreground" aria-hidden="true" />
                        <p class="mt-2 text-sm font-medium">Belum ada orang yang di-tag</p>
                        <p class="mt-1 text-xs leading-5 text-muted-foreground">
                            Ketik @ di editor lalu pilih anggota tim. Panel ini akan menampilkan pengaturan batas respons.
                        </p>
                    </div>

                    <ul v-else class="mt-4 space-y-2">
                        <li
                            v-for="person in mentionedPeople"
                            :key="person.id"
                            class="flex items-center gap-3 rounded-md border border-border bg-muted/20 p-2.5"
                        >
                            <span
                                class="flex size-8 shrink-0 items-center justify-center rounded-full bg-secondary text-[11px] font-semibold text-secondary-foreground"
                                aria-hidden="true"
                            >
                                {{ getInitials(usersById.get(person.id)?.name ?? person.label) }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">{{ usersById.get(person.id)?.name ?? person.label }}</p>
                                <p class="truncate text-xs text-muted-foreground">{{ usersById.get(person.id)?.email ?? 'Anggota tim' }}</p>
                            </div>
                            <div class="w-28 shrink-0">
                                <label :for="`response-hours-${person.id}`" class="sr-only">Batas respons {{ person.label }}</label>
                                <Select
                                    :id="`response-hours-${person.id}`"
                                    :model-value="responseHours[person.id] ?? 24"
                                    wrapper-class="w-full"
                                    class="h-9 text-xs"
                                    @update:model-value="setHours(person.id, $event)"
                                >
                                    <option v-for="option in props.responseOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </Select>
                            </div>
                        </li>
                    </ul>

                    <p class="mt-4 flex items-start gap-2 rounded-md bg-muted/40 p-3 text-xs leading-5 text-muted-foreground">
                        <Clock3 class="mt-0.5 size-3.5 shrink-0" aria-hidden="true" />
                        Batas respons mulai dihitung saat catatan disimpan. Default 24 jam.
                    </p>
                </section>

                <section class="rounded-lg border border-border bg-card p-5">
                    <h2 class="text-sm font-semibold">Catatan</h2>
                    <p class="mt-1 text-xs leading-5 text-muted-foreground">
                        Quick Note hanya terlihat oleh Anda, orang yang di-tag, dan admin. Gunakan untuk ide cepat, bukan dokumen resmi.
                    </p>
                </section>
            </aside>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-2 border-t border-border pt-4">
            <Button variant="outline" as-child>
                <Link :href="props.note ? route('quick-notes.show', props.note.id) : route('quick-notes.index')">Batal</Link>
            </Button>
            <Button type="submit" :loading="form.processing">
                {{ isEdit ? 'Simpan perubahan' : 'Simpan Quick Note' }}
            </Button>
        </div>

        <Dialog
            v-model:open="cameraOpen"
            @update:open="
                (open) => {
                    if (!open) closeCamera();
                }
            "
        >
            <DialogContent class="sm:max-w-2xl" @close-auto-focus.prevent="focusComposer">
                <DialogHeader>
                    <DialogTitle>Ambil foto ide</DialogTitle>
                    <DialogDescription
                        >Posisikan objek di depan kamera, lalu tekan Jepret. Foto akan langsung masuk ke Quick Note ini.</DialogDescription
                    >
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
                        <Camera class="size-4" />
                        Jepret
                    </Button>
                    <Button v-else type="button" variant="outline" @click="useNativeCamera">
                        <Camera class="size-4" />
                        Gunakan kamera perangkat
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </form>
</template>
