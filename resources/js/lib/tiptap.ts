import type { JSONContent } from '@tiptap/core';

interface TipTapNode {
    type?: string;
    text?: unknown;
    content?: TipTapNode[];
    [key: string]: unknown;
}

export function sanitizeTipTapContent(content: unknown): JSONContent {
    if (!content || typeof content !== 'object') {
        return { type: 'doc', content: [{ type: 'paragraph' }] } as JSONContent;
    }

    const clone = JSON.parse(JSON.stringify(content)) as TipTapNode;

    const walk = (node: TipTapNode): TipTapNode | null => {
        if (node.type === 'text') {
            return typeof node.text === 'string' && node.text !== '' ? node : null;
        }

        if (Array.isArray(node.content)) {
            node.content = node.content
                .map((child) => (child && typeof child === 'object' ? walk(child) : null))
                .filter((child): child is TipTapNode => child !== null);
        }

        return node;
    };

    const cleaned = walk(clone);

    return (cleaned ?? { type: 'doc', content: [{ type: 'paragraph' }] }) as JSONContent;
}

export function contentHasImage(content: unknown): boolean {
    if (!content || typeof content !== 'object') {
        return false;
    }

    let found = false;

    const walk = (node: TipTapNode) => {
        if (found) {
            return;
        }

        if (node.type === 'image') {
            found = true;

            return;
        }

        for (const child of node.content ?? []) {
            walk(child);
        }
    };

    walk(content as TipTapNode);

    return found;
}

export function countImages(content: unknown): number {
    if (!content || typeof content !== 'object') {
        return 0;
    }

    let count = 0;

    const walk = (node: TipTapNode) => {
        if (node.type === 'image') {
            count++;
        }

        for (const child of node.content ?? []) {
            walk(child);
        }
    };

    walk(content as TipTapNode);

    return count;
}
