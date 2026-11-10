<?php

namespace App\Services\Exports;

class TiptapHtmlRenderer
{
    /**
     * @param  array<string, mixed>|null  $document
     */
    public function render(?array $document): string
    {
        if (! $document) {
            return '';
        }

        return $this->node($document);
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function node(array $node): string
    {
        $type = $node['type'] ?? null;
        $content = $this->children($node['content'] ?? []);

        return match ($type) {
            'doc' => $content,
            'paragraph' => $this->paragraph($node, $content),
            'heading' => $this->heading($node, $content),
            'text' => $this->text($node),
            'hardBreak' => '<br>',
            'horizontalRule' => '<hr>',
            'bulletList' => '<ul>'.$content.'</ul>',
            'orderedList' => '<ol>'.$content.'</ol>',
            'listItem' => '<li>'.$this->stripParagraphWrapper($content).'</li>',
            'taskList' => '<ul class="task-list">'.$content.'</ul>',
            'taskItem' => $this->taskItem($node, $content),
            'blockquote' => '<blockquote>'.$content.'</blockquote>',
            'codeBlock' => '<pre><code>'.e($this->plainText($node)).'</code></pre>',
            'table' => '<table>'.$content.'</table>',
            'tableRow' => '<tr>'.$content.'</tr>',
            'tableHeader' => '<th>'.$content.'</th>',
            'tableCell' => '<td>'.$content.'</td>',
            'mention' => $this->mention($node),
            'topicMention' => $this->mention($node, '#'),
            'image' => $this->image($node),
            default => $content,
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     */
    private function children(array $nodes): string
    {
        $html = '';

        foreach ($nodes as $node) {
            if (is_array($node)) {
                $html .= $this->node($node);
            }
        }

        return $html;
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function paragraph(array $node, string $content): string
    {
        $align = $node['attrs']['textAlign'] ?? null;
        $style = in_array($align, ['left', 'center', 'right', 'justify'], true) ? ' style="text-align:'.$align.'"' : '';

        return '<p'.$style.'>'.($content === '' ? '&nbsp;' : $content).'</p>';
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function heading(array $node, string $content): string
    {
        $level = min(6, max(1, (int) ($node['attrs']['level'] ?? 2)));
        $align = $node['attrs']['textAlign'] ?? null;
        $style = in_array($align, ['left', 'center', 'right', 'justify'], true) ? ' style="text-align:'.$align.'"' : '';

        return "<h{$level}{$style}>{$content}</h{$level}>";
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function text(array $node): string
    {
        $text = e((string) ($node['text'] ?? ''));

        foreach ($node['marks'] ?? [] as $mark) {
            if (! is_array($mark)) {
                continue;
            }

            $text = match ($mark['type'] ?? null) {
                'bold' => '<strong>'.$text.'</strong>',
                'italic' => '<em>'.$text.'</em>',
                'underline' => '<u>'.$text.'</u>',
                'strike' => '<s>'.$text.'</s>',
                'code' => '<code>'.$text.'</code>',
                'highlight' => '<mark>'.$text.'</mark>',
                'link' => $this->link($mark, $text),
                default => $text,
            };
        }

        return $text;
    }

    /**
     * @param  array<string, mixed>  $mark
     */
    private function link(array $mark, string $text): string
    {
        $href = $this->safeUrl($mark['attrs']['href'] ?? null);

        if (! $href) {
            return $text;
        }

        return '<a href="'.e($href).'">'.$text.'</a>';
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function taskItem(array $node, string $content): string
    {
        $checked = (bool) ($node['attrs']['checked'] ?? false);
        $mark = $checked ? '☑' : '☐';

        return '<li class="task-item"><span class="task-mark">'.$mark.'</span> '.$this->stripParagraphWrapper($content).'</li>';
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function mention(array $node, ?string $prefix = null): string
    {
        $label = (string) ($node['attrs']['label'] ?? '');
        $type = (string) ($node['attrs']['type'] ?? ($node['type'] === 'topicMention' ? 'topic' : 'person'));

        if ($label === '') {
            return '';
        }

        $prefix ??= $type === 'topic' ? '#' : '@';

        return '<span class="mention mention-'.$type.'">'.e($prefix.$label).'</span>';
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function image(array $node): string
    {
        $src = $this->safeUrl($node['attrs']['src'] ?? null);

        if (! $src) {
            return '';
        }

        return '<img src="'.e($src).'" alt="'.e((string) ($node['attrs']['alt'] ?? '')).'" style="max-width:100%;">';
    }

    private function stripParagraphWrapper(string $html): string
    {
        return preg_replace('/^<p>(.*)<\/p>$/s', '$1', $html) ?? $html;
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function plainText(array $node): string
    {
        if (isset($node['text'])) {
            return (string) $node['text'];
        }

        $text = '';

        foreach ($node['content'] ?? [] as $child) {
            if (is_array($child)) {
                $text .= $this->plainText($child);
            }
        }

        return $text;
    }

    private function safeUrl(?string $url): ?string
    {
        if (! is_string($url) || trim($url) === '') {
            return null;
        }

        $url = trim($url);

        if (str_starts_with($url, '/')) {
            return $url;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        return in_array($scheme, ['http', 'https', 'mailto'], true) ? $url : null;
    }
}
