import { Extension, type Editor } from '@tiptap/core';
import Suggestion from '@tiptap/suggestion';

interface SlashItem {
    title: string;
    description: string;
    command: (editor: Editor) => void;
}

const commands: SlashItem[] = [
    {
        title: 'Judul bagian',
        description: 'Buat heading level 2',
        command: (editor) => editor.chain().focus().toggleHeading({ level: 2 }).run(),
    },
    {
        title: 'Daftar butir',
        description: 'Buat bullet list',
        command: (editor) => editor.chain().focus().toggleBulletList().run(),
    },
    {
        title: 'Daftar bernomor',
        description: 'Buat numbered list',
        command: (editor) => editor.chain().focus().toggleOrderedList().run(),
    },
    {
        title: 'Daftar tugas',
        description: 'Buat checklist',
        command: (editor) => editor.chain().focus().toggleTaskList().run(),
    },
    {
        title: 'Tabel',
        description: 'Sisipkan tabel 3 x 3',
        command: (editor) => editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run(),
    },
    {
        title: 'Kutipan',
        description: 'Tandai sebagai blockquote',
        command: (editor) => editor.chain().focus().toggleBlockquote().run(),
    },
    {
        title: 'Garis pemisah',
        description: 'Sisipkan horizontal rule',
        command: (editor) => editor.chain().focus().setHorizontalRule().run(),
    },
];

function escapeHtml(value: string): string {
    return value.replace(/[&<>"']/g, (character) => {
        const entities: Record<string, string> = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;',
        };

        return entities[character] ?? character;
    });
}

export const SlashCommand = Extension.create({
    name: 'slashCommand',

    addProseMirrorPlugins() {
        return [
            Suggestion({
                editor: this.editor,
                char: '/',
                startOfLine: true,
                command: ({ editor, range, props }: any) => {
                    editor.chain().focus().deleteRange(range).run();
                    props.command(editor);
                },
                items: ({ query }: { query: string }) => {
                    const normalized = query.trim().toLowerCase();

                    return commands.filter(
                        (item) => item.title.toLowerCase().includes(normalized) || item.description.toLowerCase().includes(normalized),
                    );
                },
                render: () => {
                    let popup: HTMLDivElement | null = null;
                    let items: SlashItem[] = [];
                    let selected = 0;
                    let command: ((item: SlashItem) => void) | null = null;

                    const destroy = () => {
                        popup?.remove();
                        popup = null;
                    };

                    const select = (item: SlashItem | undefined) => {
                        if (!item) {
                            return;
                        }

                        command?.(item);
                        destroy();
                    };

                    const paint = () => {
                        if (!popup) {
                            return;
                        }

                        popup.innerHTML =
                            items.length === 0
                                ? '<div class="mention-suggestion-empty">Tidak ada perintah</div>'
                                : items
                                      .map(
                                          (item, index) => `
                                            <div role="option" id="slash-option-${index}" aria-selected="${index === selected}" data-selected="${index === selected}" data-index="${index}" class="mention-suggestion-item slash-suggestion-item">
                                                <span class="slash-suggestion-icon" aria-hidden="true">/</span>
                                                <span class="mention-suggestion-copy">
                                                    <span class="mention-suggestion-label">${escapeHtml(item.title)}</span>
                                                    <span class="mention-suggestion-hint">${escapeHtml(item.description)}</span>
                                                </span>
                                            </div>
                                        `,
                                      )
                                      .join('');

                        popup.setAttribute('aria-activedescendant', items.length > 0 ? `slash-option-${selected}` : '');

                        popup.querySelectorAll<HTMLElement>('[data-index]').forEach((option) => {
                            option.addEventListener('mousedown', (event) => {
                                event.preventDefault();
                                select(items[Number(option.dataset.index)]);
                            });
                        });
                    };

                    const position = (rect: DOMRect | null) => {
                        if (!popup) {
                            return;
                        }

                        let fallback: DOMRect | null = null;

                        try {
                            const selection = window.getSelection();

                            if (selection && selection.rangeCount > 0) {
                                fallback = selection.getRangeAt(0).getBoundingClientRect();
                            }
                        } catch {
                            fallback = null;
                        }

                        const bounds = rect ?? fallback;

                        if (!bounds) {
                            return;
                        }

                        const popupWidth = 320;
                        const left = Math.min(bounds.left + window.scrollX, window.scrollX + window.innerWidth - popupWidth - 16);
                        const top = Math.min(bounds.bottom + window.scrollY + 8, window.scrollY + window.innerHeight - 300);

                        popup.style.left = `${Math.max(window.scrollX + 12, left)}px`;
                        popup.style.top = `${Math.max(window.scrollY + 12, top)}px`;
                    };

                    return {
                        onStart: (props: any) => {
                            items = Array.isArray(props.items) ? props.items : [];
                            command = props.command;
                            selected = 0;
                            popup = document.createElement('div');
                            popup.className = 'mention-suggestion slash-suggestion';
                            popup.setAttribute('role', 'listbox');
                            popup.setAttribute('aria-label', 'Perintah editor');
                            document.body.appendChild(popup);
                            paint();
                            position(props.clientRect?.() ?? null);
                        },
                        onUpdate: (props: any) => {
                            items = Array.isArray(props.items) ? props.items : [];
                            command = props.command;
                            selected = Math.max(0, Math.min(selected, items.length - 1));
                            paint();
                            position(props.clientRect?.() ?? null);
                        },
                        onKeyDown: (props: any) => {
                            if (!popup) {
                                return false;
                            }

                            if (props.event.key === 'ArrowDown') {
                                selected = (selected + 1) % Math.max(items.length, 1);
                                paint();

                                return true;
                            }

                            if (props.event.key === 'ArrowUp') {
                                selected = (selected - 1 + items.length) % Math.max(items.length, 1);
                                paint();

                                return true;
                            }

                            if (props.event.key === 'Enter') {
                                select(items[selected]);

                                return true;
                            }

                            if (props.event.key === 'Escape') {
                                destroy();

                                return true;
                            }

                            return false;
                        },
                        onExit: destroy,
                    };
                },
            }),
        ];
    },
});
