<?php

namespace App\Services;

class MeetingContentParser
{
    /**
     * @return array{text: string, mentions: array<int, array{type: string, id: int, label: string}>}
     */
    public function parse(?array $document): array
    {
        $parts = [];
        $mentions = [];

        if (! $document) {
            return ['text' => '', 'mentions' => []];
        }

        $walk = function (array $node) use (&$walk, &$parts, &$mentions) {
            if (isset($node['text']) && is_string($node['text'])) {
                $parts[] = $node['text'];
            }

            if (($node['type'] ?? null) === 'mention') {
                $attrs = $node['attrs'] ?? [];
                $type = $attrs['type'] ?? null;
                $id = $attrs['id'] ?? null;
                $label = $attrs['label'] ?? null;

                if (in_array($type, ['person', 'division', 'topic'], true) && $id && $label) {
                    $mentions["{$type}:{$id}"] = [
                        'type' => $type,
                        'id' => (int) $id,
                        'label' => (string) $label,
                    ];
                }

                if (is_string($label)) {
                    $parts[] = $label;
                }
            }

            foreach ($node['content'] ?? [] as $child) {
                if (is_array($child)) {
                    $walk($child);
                }
            }
        };

        $walk($document);

        return [
            'text' => trim(preg_replace('/\s+/', ' ', implode(' ', $parts)) ?? ''),
            'mentions' => array_values($mentions),
        ];
    }
}
