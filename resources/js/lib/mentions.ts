export interface PersonMention {
    id: number;
    label: string;
}

interface MentionNode {
    type?: string;
    attrs?: Record<string, unknown>;
    content?: MentionNode[];
}

export function extractPersonMentions(content: unknown): PersonMention[] {
    const found = new Map<number, string>();

    const walk = (node: MentionNode) => {
        if (node.type === 'mention' && node.attrs?.type === 'person' && node.attrs.id !== undefined) {
            const id = Number(node.attrs.id);

            if (Number.isFinite(id)) {
                found.set(id, String(node.attrs.label ?? 'Pengguna'));
            }
        }

        for (const child of node.content ?? []) {
            walk(child);
        }
    };

    if (content && typeof content === 'object') {
        walk(content as MentionNode);
    }

    return [...found.entries()].map(([id, label]) => ({ id, label }));
}
