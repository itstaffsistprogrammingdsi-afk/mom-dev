<script setup lang="ts">
import type { MeetingSignature } from '@/types';

withDefaults(
    defineProps<{
        creatorName: string;
        approverName: string;
        creatorSignature?: MeetingSignature | null;
        approverSignature?: MeetingSignature | null;
    }>(),
    {
        creatorSignature: null,
        approverSignature: null,
    },
);
</script>

<template>
    <table class="w-full border-collapse text-xs sm:text-sm">
        <tbody>
            <tr>
                <th class="w-1/2 border border-border bg-muted p-2 text-left font-semibold">
                    Dibuat Oleh:
                    <span class="block text-[10px] font-normal italic text-muted-foreground">(wajib diisi tanda tangan notulis)</span>
                </th>
                <th class="w-1/2 border border-border bg-muted p-2 text-left font-semibold">
                    Disetujui Oleh:
                    <span class="block text-[10px] font-normal italic text-muted-foreground">
                        (tanda tangan pimpinan sudah mengetahui &amp; approval)
                    </span>
                </th>
            </tr>
            <tr>
                <td class="border border-border p-2 align-top">
                    <div class="flex h-12 items-end">
                        <img
                            v-if="creatorSignature"
                            :src="creatorSignature.url"
                            :alt="`Tanda tangan ${creatorSignature.name ?? creatorName}`"
                            class="max-h-12 w-auto object-contain"
                        />
                    </div>
                    <p class="font-semibold">{{ creatorSignature?.name ?? creatorName }}</p>
                    <p class="text-xs text-muted-foreground">Notulis</p>
                    <p v-if="creatorSignature?.signed_at" class="text-[10px] text-muted-foreground">
                        Ditandatangani {{ new Date(creatorSignature.signed_at).toLocaleDateString('id-ID') }}
                    </p>
                    <p v-else class="text-[10px] italic text-muted-foreground">(wajib diisi nama penyusun notulen)</p>
                </td>
                <td class="border border-border p-2 align-top">
                    <div class="flex h-12 items-end">
                        <img
                            v-if="approverSignature"
                            :src="approverSignature.url"
                            :alt="`Tanda tangan ${approverSignature.name ?? approverName}`"
                            class="max-h-12 w-auto object-contain"
                        />
                    </div>
                    <p class="font-semibold">{{ approverSignature?.name ?? approverName }}</p>
                    <p class="text-xs text-muted-foreground">Pimpinan Rapat</p>
                    <p v-if="approverSignature?.signed_at" class="text-[10px] text-muted-foreground">
                        Ditandatangani {{ new Date(approverSignature.signed_at).toLocaleDateString('id-ID') }}
                    </p>
                </td>
            </tr>
        </tbody>
    </table>
</template>
