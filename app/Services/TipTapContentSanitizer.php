<?php

namespace App\Services;

class TipTapContentSanitizer
{
    /**
     * @return array<string, mixed>
     */
    public function sanitize(?array $content): array
    {
        if (! $content) {
            return $this->emptyDocument();
        }

        $cleaned = $this->walk($content);

        if (! is_array($cleaned)) {
            return $this->emptyDocument();
        }

        if (empty($cleaned['content'])) {
            $cleaned['content'] = [['type' => 'paragraph']];
        }

        return $cleaned;
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>|null
     */
    private function walk(array $node): ?array
    {
        if (($node['type'] ?? null) === 'text') {
            $text = $node['text'] ?? null;

            if (! is_string($text) || $text === '') {
                return null;
            }
        }

        if (isset($node['content']) && is_array($node['content'])) {
            $children = [];

            foreach ($node['content'] as $child) {
                if (! is_array($child)) {
                    continue;
                }

                $cleaned = $this->walk($child);

                if (is_array($cleaned)) {
                    $children[] = $cleaned;
                }
            }

            $node['content'] = $children;
        }

        return $node;
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyDocument(): array
    {
        return [
            'type' => 'doc',
            'content' => [
                ['type' => 'paragraph'],
            ],
        ];
    }
}
