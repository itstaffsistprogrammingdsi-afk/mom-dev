<script setup lang="ts">
import { Button } from '@/components/ui/button';
import type { JSONContent } from '@tiptap/core';
import { Minus, Plus } from 'lucide-vue-next';
import { computed, nextTick, ref } from 'vue';

interface TableCellNode {
    type?: string;
    attrs?: Record<string, unknown> & { colwidth?: number[] };
    content?: { type?: string; text?: string; content?: { text?: string }[] }[];
}

interface TableRowNode {
    type?: string;
    content?: TableCellNode[];
}

interface TableNode {
    type: string;
    content?: TableRowNode[];
    attrs?: Record<string, unknown>;
}

const props = defineProps<{
    modelValue: JSONContent;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: JSONContent];
}>();

const defaultHeaders = [
    { title: 'Point Diskusi', guidance: '', width: 140 },
    { title: 'Keterangan', guidance: '', width: 140 },
    { title: 'Action Plan', guidance: '', width: 140 },
    { title: 'Skala Prioritas', guidance: 'High Priority / Medium Priority / Low Priority. Diisi Pimpinan Rapat atau saat rapat.', width: 140 },
    { title: 'Divisi', guidance: '', width: 140 },
    { title: 'PIC', guidance: 'Wajib diisi nama penanggung jawab.', width: 140 },
    { title: 'Deadline', guidance: 'Diisi Pimpinan Rapat atau berdasarkan kesepakatan rapat.', width: 140 },
    { title: 'Output', guidance: 'Dapat berupa aturan, laporan, dan sebagainya.', width: 140 },
];

function textFromNode(node: TableCellNode | undefined): string {
    if (!node?.content) {
        return '';
    }

    return node.content
        .map((paragraph) => {
            if (typeof paragraph.text === 'string') {
                return paragraph.text;
            }

            return (paragraph.content ?? []).map((child) => child.text ?? '').join('');
        })
        .join('\n');
}

function findTable(content: JSONContent): TableNode | undefined {
    return (content.content ?? []).find((node) => node.type === 'table') as TableNode | undefined;
}

const table = findTable(props.modelValue);
const existingHeaderRow = table?.content?.[0];
const headerCells = existingHeaderRow?.content ?? [];
const headers =
    headerCells.length > 0
        ? headerCells.map((cell) => {
              const paragraphs = cell.content ?? [];
              const title = textFromNode({ content: paragraphs.slice(0, 1) });
              const guidance = textFromNode({ content: paragraphs.slice(1) });
              const width = Number(cell.attrs?.colwidth?.[0] ?? 140);

              return { title, guidance, width };
          })
        : defaultHeaders;

const rows = ref<string[][]>((table?.content ?? []).slice(1).map((row) => (row.content ?? []).map((cell) => textFromNode(cell))));

if (rows.value.length === 0) {
    rows.value = Array.from({ length: 5 }, () => headers.map(() => ''));
}

const rowsCount = computed(() => rows.value.length);
const columnWidths = ref<number[]>(headers.map((header) => (Number.isFinite(header.width) ? header.width : 140)));
const priorityOptions = ['High Priority', 'Medium Priority', 'Low Priority'];
const cellRefs = new Map<string, HTMLTextAreaElement>();

function setCellRef(el: HTMLTextAreaElement | null, rowIndex: number, colIndex: number) {
    if (el) {
        cellRefs.set(`${rowIndex}:${colIndex}`, el);
    } else {
        cellRefs.delete(`${rowIndex}:${colIndex}`);
    }
}

function focusCell(rowIndex: number, colIndex: number) {
    cellRefs.get(`${rowIndex}:${colIndex}`)?.focus();
}

function onCellKeydown(event: KeyboardEvent, rowIndex: number, colIndex: number) {
    if (event.key !== 'Enter' || event.shiftKey) {
        return;
    }

    event.preventDefault();

    if (rowIndex + 1 >= rows.value.length) {
        addRow();
    }

    nextTick(() => focusCell(rowIndex + 1, colIndex));
}

function cellParagraphs(value: string) {
    const lines = value.split(/\r?\n/);

    return lines.map((line) => ({
        type: 'paragraph',
        content: line ? [{ type: 'text', text: line }] : [],
    }));
}

function headerRowNode(): TableRowNode {
    if (existingHeaderRow) {
        return existingHeaderRow;
    }

    return {
        type: 'tableRow',
        content: defaultHeaders.map((header) => ({
            type: 'tableHeader',
            content: [
                { type: 'paragraph', content: [{ type: 'text', text: header.title }] },
                ...(header.guidance
                    ? [
                          {
                              type: 'paragraph',
                              content: [{ type: 'text', marks: [{ type: 'italic' }], text: header.guidance }],
                          },
                      ]
                    : []),
            ],
        })),
    };
}

function syncContent() {
    const content = JSON.parse(JSON.stringify(props.modelValue)) as JSONContent & { content?: TableNode[] };
    const header = headerRowNode();
    header.content = header.content?.map((cell, index) => ({
        ...cell,
        attrs: { ...(cell.attrs ?? {}), colwidth: [columnWidths.value[index] ?? 140] },
    }));

    const dataRows: TableRowNode[] = rows.value.map((row) => ({
        type: 'tableRow',
        content: row.map((value, index) => ({
            type: 'tableCell',
            attrs: { colwidth: [columnWidths.value[index] ?? 140] },
            content: cellParagraphs(value),
        })),
    }));

    const tableNode: TableNode = {
        type: 'table',
        content: [header, ...dataRows],
    };

    const tableIndex = (content.content ?? []).findIndex((node) => node.type === 'table');

    if (tableIndex >= 0) {
        content.content![tableIndex] = tableNode;
    } else {
        content.content = [...(content.content ?? []), tableNode];
    }

    emit('update:modelValue', content);
}

let resizeStartX = 0;
let resizeStartWidth = 0;
let resizeIndex = 0;

function startResize(index: number, event: MouseEvent) {
    resizeIndex = index;
    resizeStartX = event.clientX;
    resizeStartWidth = columnWidths.value[index] ?? 140;

    window.addEventListener('mousemove', onResizeMove);
    window.addEventListener('mouseup', onResizeEnd);
    event.preventDefault();
}

function onResizeMove(event: MouseEvent) {
    columnWidths.value = columnWidths.value.map((width, index) =>
        index === resizeIndex ? Math.max(80, resizeStartWidth + event.clientX - resizeStartX) : width,
    );
}

function onResizeEnd() {
    window.removeEventListener('mousemove', onResizeMove);
    window.removeEventListener('mouseup', onResizeEnd);
    syncContent();
}

function addRow() {
    rows.value = [...rows.value, headers.map(() => '')];
    syncContent();
}

function removeRow() {
    if (rows.value.length <= 1) {
        return;
    }

    rows.value = rows.value.slice(0, -1);
    syncContent();
}

function columnLetter(index: number): string {
    return String.fromCharCode(65 + index);
}
</script>

<template>
    <div class="bg-card text-foreground">
        <div class="flex flex-wrap items-center justify-between gap-2 border-y border-border bg-muted/50 px-3 py-2">
            <p class="text-xs text-muted-foreground">Tab untuk pindah kolom, Enter untuk pindah baris.</p>
            <div class="flex items-center gap-2">
                <span class="text-xs tabular-nums text-muted-foreground">{{ rowsCount }} baris</span>
                <Button type="button" size="sm" variant="outline" @click="addRow">
                    <Plus class="size-3.5" />
                    Baris
                </Button>
                <Button type="button" size="sm" variant="ghost" :disabled="rowsCount <= 1" @click="removeRow">
                    <Minus class="size-3.5" />
                    Baris
                </Button>
            </div>
        </div>

        <div class="overflow-auto">
            <table class="w-full min-w-[1100px] table-fixed border-collapse text-sm">
                <colgroup>
                    <col style="width: 40px" />
                    <col v-for="(_header, index) in headers" :key="`col-${index}`" :style="{ width: `${columnWidths[index] ?? 140}px` }" />
                </colgroup>
                <thead class="sticky top-0 z-10">
                    <tr>
                        <th class="w-10 border border-border bg-muted p-1 text-[10px] font-medium text-muted-foreground"></th>
                        <th
                            v-for="(_header, index) in headers"
                            :key="`letter-${index}`"
                            class="relative border border-border bg-muted px-2 py-1 text-center text-[10px] font-medium text-muted-foreground"
                        >
                            {{ columnLetter(index) }}
                            <span
                                class="absolute right-0 top-0 z-20 h-full w-1.5 cursor-col-resize select-none hover:bg-primary/30"
                                aria-hidden="true"
                                @mousedown.prevent="startResize(index, $event)"
                            ></span>
                        </th>
                    </tr>
                    <tr>
                        <th class="border border-border bg-muted px-1 py-2 text-center text-[10px] font-medium text-muted-foreground">1</th>
                        <th
                            v-for="(header, index) in headers"
                            :key="`header-${index}`"
                            class="border border-border bg-warning-soft/70 p-2 text-left align-top"
                        >
                            <p class="text-[11px] font-semibold leading-4 text-foreground">{{ header.title }}</p>
                            <p v-if="header.guidance" class="mt-1 text-[10px] font-normal italic leading-4 text-muted-foreground">
                                {{ header.guidance }}
                            </p>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, rowIndex) in rows" :key="`row-${rowIndex}`">
                        <th class="border border-border bg-muted px-1 py-2 text-center text-[10px] font-medium text-muted-foreground">
                            {{ rowIndex + 2 }}
                        </th>
                        <td v-for="(cell, colIndex) in row" :key="`cell-${rowIndex}-${colIndex}`" class="border border-border p-0 align-top">
                            <select
                                v-if="colIndex === 3"
                                v-model="rows[rowIndex][colIndex]"
                                class="block min-h-16 w-full border-0 bg-transparent p-2 text-xs leading-5 text-foreground focus:bg-primary/5 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary"
                                aria-label="Skala prioritas"
                                @change="syncContent"
                            >
                                <option value="">—</option>
                                <option v-for="option in priorityOptions" :key="option" :value="option">{{ option }}</option>
                                <option
                                    v-if="rows[rowIndex][colIndex] && !priorityOptions.includes(rows[rowIndex][colIndex])"
                                    :value="rows[rowIndex][colIndex]"
                                >
                                    {{ rows[rowIndex][colIndex] }}
                                </option>
                            </select>
                            <textarea
                                v-else
                                :ref="(el) => setCellRef(el as HTMLTextAreaElement | null, rowIndex, colIndex)"
                                v-model="rows[rowIndex][colIndex]"
                                rows="2"
                                class="block min-h-16 w-full resize-none border-0 bg-transparent p-2 text-xs leading-5 text-foreground placeholder:text-subtle-foreground focus:bg-primary/5 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary"
                                @input="syncContent"
                                @keydown="onCellKeydown($event, rowIndex, colIndex)"
                            />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
