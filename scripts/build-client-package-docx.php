<?php

$autoload = getenv('MOM_VENDOR_AUTOLOAD') ?: __DIR__.'/../vendor/autoload.php';

if (! is_file($autoload)) {
    fwrite(STDERR, "Autoload tidak ditemukan: {$autoload}\n");
    exit(1);
}

require $autoload;

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

class DocxBuilder
{
    private const NAVY = '1F3864';

    private const BLUE = '2F5597';

    private const LIGHT = 'EEF3FA';

    private const BORDER = 'D9E2F3';

    private const CONTENT_WIDTH = 9300;

    private PhpWord $word;

    /** @var array<int, array{title: string, blocks: array<int, array<string, mixed>>}> */
    private array $docs = [];

    /** @var array<string, array<int, string>> */
    private array $manifest = [];

    private int $diagramIndex = 0;

    private string $docsDir;

    private string $imagesDir;

    public function __construct()
    {
        $this->docsDir = realpath(__DIR__.'/..');
        $this->imagesDir = getenv('MOM_DOC_IMAGES') ?: __DIR__.'/../docs/doc-images';
        $this->manifest = [];

        $manifestPath = $this->imagesDir.'/manifest.json';

        if (is_file($manifestPath)) {
            $this->manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
        }

        $this->word = new PhpWord;
        $this->word->getDocInfo()
            ->setTitle('Blueprint, PRD, dan ERD - Sistem Notulensi (MoM) Terintegrasi AI')
            ->setSubject('Paket presentasi klien: Blueprint, Product Requirements, dan ERD')
            ->setCreator('Wafiq Lana Pradana')
            ->setCompany('Internal')
            ->setDescription('Paket presentasi klien Sistem Notulensi tanpa pembahasan komersial.');

        $this->word->setDefaultFontName('Segoe UI');
        $this->word->setDefaultFontSize(9.5);

        $this->word->addTitleStyle(1, [
            'size' => 20,
            'bold' => true,
            'color' => self::NAVY,
        ], [
            'spaceBefore' => 0,
            'spaceAfter' => 220,
            'keepNext' => true,
            'borderBottomSize' => 12,
            'borderBottomColor' => self::BLUE,
        ]);

        $this->word->addTitleStyle(2, [
            'size' => 13,
            'bold' => true,
            'color' => self::NAVY,
        ], [
            'spaceBefore' => 260,
            'spaceAfter' => 100,
            'keepNext' => true,
            'borderBottomSize' => 4,
            'borderBottomColor' => self::BORDER,
        ]);

        $this->word->addTitleStyle(3, [
            'size' => 10.5,
            'bold' => true,
            'color' => self::BLUE,
        ], [
            'spaceBefore' => 180,
            'spaceAfter' => 60,
            'keepNext' => true,
        ]);

        $files = [
            '05-Blueprint-Sistem-Notulensi.md',
            '01-PRD-Sistem-Notulensi.md',
            '02-ERD-Sistem-Notulensi.md',
        ];

        foreach ($files as $file) {
            $path = $this->docsDir.'/'.$file;
            $blocks = $this->parseMarkdown((string) file_get_contents($path));
            $title = $this->firstTitle($blocks) ?: $file;

            $this->docs[] = ['title' => $title, 'blocks' => $blocks];
        }
    }

    public function build(): void
    {
        $cover = $this->addSection();
        $this->renderCover($cover);

        $toc = $this->addSection();
        $this->renderToc($toc);

        $content = $this->addSection();
        $this->renderFooter($content);

        foreach ($this->docs as $index => $doc) {
            if ($index > 0) {
                $content->addPageBreak();
            }

            $this->renderBlocks($content, $doc['blocks']);
        }

        $output = $this->docsDir.'/Blueprint-PRD-ERD-Sistem-Notulensi.docx';
        IOFactory::createWriter($this->word, 'Word2007')->save($output);

        echo "DOCX: {$output}\n";
        echo 'Dokumen: '.count($this->docs)."\n";
        echo 'Diagram: '.$this->diagramIndex."\n";
    }

    private function addSection()
    {
        $section = $this->word->addSection([
            'marginTop' => 1000,
            'marginBottom' => 1000,
            'marginLeft' => 1000,
            'marginRight' => 1000,
            'headerHeight' => 400,
            'footerHeight' => 500,
        ]);

        $section->getStyle()->setPaperSize('A4');
        $section->getStyle()->setOrientation('portrait');

        return $section;
    }

    private function renderCover($section): void
    {
        $section->addTextBreak(3);

        $section->addText('PAKET PRESENTASI KLIEN', [
            'size' => 9,
            'bold' => true,
            'color' => '6B7280',
        ], ['spaceAfter' => 240]);

        $section->addText('Sistem Notulensi (MoM)', [
            'size' => 30,
            'bold' => true,
            'color' => self::NAVY,
        ], ['spaceAfter' => 0]);

        $section->addText('Terintegrasi AI', [
            'size' => 30,
            'bold' => true,
            'color' => self::NAVY,
        ], ['spaceAfter' => 180]);

        $section->addText('Blueprint · Product Requirements · ERD dan Kamus Data', [
            'size' => 12,
            'color' => '6B7280',
        ], ['spaceAfter' => 120]);

        $section->addText('', [], [
            'borderBottomSize' => 12,
            'borderBottomColor' => self::BLUE,
            'spaceAfter' => 420,
        ]);

        $meta = [
            ['Kode dokumen', 'DOC-PKG-MOM-001'],
            ['Versi', '1.0'],
            ['Tanggal', '9 November 2026'],
            ['Pemilik', 'Wafiq Lana Pradana'],
            ['Status', 'Draft untuk persetujuan klien'],
            ['Audiens', 'Manajemen, Product Owner, Kepala Divisi, IT, QA, Vendor'],
        ];

        $table = $section->addTable([
            'borderSize' => 0,
            'cellMargin' => 80,
        ]);
        $table->setWidth(8800);

        foreach ($meta as $rowData) {
            $row = $table->addRow(null, ['cantSplit' => true]);
            $row->addCell(2200, ['valign' => 'top'])->addText($rowData[0], [
                'size' => 9,
                'color' => '6B7280',
            ]);
            $row->addCell(6600, ['valign' => 'top'])->addText($rowData[1], [
                'size' => 9,
                'color' => self::NAVY,
                'bold' => true,
            ]);
        }

        $section->addTextBreak(2);

        $note = $section->addTable([
            'borderSize' => 0,
            'cellMargin' => 140,
        ]);
        $note->setWidth(8800);
        $cell = $note->addRow()->addCell(8800, [
            'bgColor' => self::LIGHT,
            'borderLeftSize' => 18,
            'borderLeftColor' => self::BLUE,
        ]);
        $cell->addText(
            'Paket ini menggabungkan Blueprint (05), PRD (01), dan ERD (02). Bisnis Proses (03) dan Matriks Traceability (04) tersedia sebagai dokumen pendukung. Setiap bagian mempertahankan penomoran aslinya. Aspek komersial dan pembiayaan tidak dibahas dalam dokumen ini.',
            ['size' => 9, 'italic' => true, 'color' => self::NAVY],
            ['spaceAfter' => 0],
        );
    }

    private function renderToc($section): void
    {
        $section->addText('Daftar Isi', [
            'size' => 20,
            'bold' => true,
            'color' => self::NAVY,
        ], [
            'spaceAfter' => 120,
            'borderBottomSize' => 12,
            'borderBottomColor' => self::BLUE,
        ]);

        $section->addText('Gunakan panel navigasi Word atau daftar di bawah untuk berpindah bagian.', [
            'size' => 8.5,
            'italic' => true,
            'color' => '6B7280',
        ], ['spaceAfter' => 220]);

        foreach ($this->docs as $doc) {
            $section->addText($this->esc($doc['title']), [
                'size' => 11,
                'bold' => true,
                'color' => self::NAVY,
            ], ['spaceBefore' => 120, 'spaceAfter' => 30]);

            foreach ($doc['blocks'] as $block) {
                if ($block['type'] === 'heading' && $block['level'] === 2) {
                    $section->addText('•  '.$this->esc($this->plainText($block['text'])), [
                        'size' => 9,
                        'color' => '374151',
                    ], ['indent' => 0.2, 'spaceAfter' => 12]);
                }
            }
        }
    }

    private function renderFooter($section): void
    {
        $footer = $section->addFooter();
        $footer->addText('Blueprint, PRD, dan ERD - Sistem Notulensi (MoM) - v1.0', [
            'size' => 8,
            'color' => '777777',
        ]);
        $footer->addPreserveText('Hal. {PAGE} dari {NUMPAGES}', [
            'size' => 8,
            'color' => '777777',
        ], ['alignment' => 'right']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     */
    private function renderBlocks($section, array $blocks): void
    {
        foreach ($blocks as $block) {
            match ($block['type']) {
                'heading' => $this->renderHeading($section, $block),
                'paragraph' => $this->renderParagraph($section, $block['text']),
                'list' => $this->renderList($section, $block),
                'table' => $this->renderTable($section, $block),
                'quote' => $this->renderQuote($section, $block['text']),
                'code' => $this->renderCode($section, $block['text']),
                'mermaid' => $this->renderDiagram($section),
                default => null,
            };
        }
    }

    private function renderHeading($section, array $block): void
    {
        $section->addTitle($this->esc($this->plainText($block['text'])), min(3, max(1, $block['level'])));
    }

    private function renderParagraph($section, string $text): void
    {
        $chunks = explode("\n", $text);

        foreach ($chunks as $index => $chunk) {
            $run = $section->addTextRun([
                'spaceAfter' => $index === count($chunks) - 1 ? 90 : 0,
                'alignment' => 'both',
            ]);
            $this->writeInline($run, $chunk, ['size' => 9.5, 'color' => '1F2937']);
        }
    }

    private function renderList($section, array $block): void
    {
        $number = 1;

        foreach ($block['items'] as $item) {
            $prefix = $block['ordered'] ? ($number++).'.  ' : '•  ';
            $run = $section->addTextRun([
                'indent' => 0.25,
                'spaceAfter' => 45,
                'alignment' => 'both',
            ]);
            $run->addText($prefix, ['size' => 9.5, 'color' => self::NAVY, 'bold' => true]);
            $this->writeInline($run, $item, ['size' => 9.5, 'color' => '1F2937']);
        }
    }

    private function renderQuote($section, string $text): void
    {
        $run = $section->addTextRun([
            'indent' => 0.2,
            'spaceBefore' => 60,
            'spaceAfter' => 120,
            'borderLeftSize' => 12,
            'borderLeftColor' => self::BLUE,
        ]);
        $this->writeInline($run, $text, [
            'size' => 9,
            'italic' => true,
            'color' => self::NAVY,
        ]);
    }

    private function renderCode($section, string $text): void
    {
        $table = $section->addTable([
            'borderSize' => 4,
            'borderColor' => self::BORDER,
            'cellMargin' => 100,
        ]);
        $table->setWidth(9300);
        $cell = $table->addRow()->addCell(9300, ['bgColor' => 'F6F8FA']);

        $lines = preg_split('/\R/', trim($text));
        foreach ($lines as $line) {
            $cell->addText($line === '' ? ' ' : $this->esc($line), [
                'name' => 'Consolas',
                'size' => 8,
                'color' => '374151',
            ], ['spaceAfter' => 0]);
        }

        $section->addTextBreak(1, ['size' => 4]);
    }

    private function renderDiagram($section): void
    {
        $this->diagramIndex++;
        $key = sprintf('diagram-%02d', $this->diagramIndex);
        $parts = $this->manifest[$key] ?? [];

        if ($parts === []) {
            return;
        }

        foreach ($parts as $index => $part) {
            $path = $this->imagesDir.'/'.$part;
            [$width, $height] = getimagesize($path);
            $scale = min(460 / $width, 650 / $height, 1.3);
            $displayWidth = (int) round($width * $scale);
            $displayHeight = (int) round($height * $scale);

            $section->addImage($path, [
                'width' => $displayWidth,
                'height' => $displayHeight,
                'alignment' => 'center',
            ]);

            if (count($parts) > 1) {
                $section->addText(
                    'Bagian '.($index + 1).' dari '.count($parts),
                    ['size' => 8, 'italic' => true, 'color' => '6B7280'],
                    ['alignment' => 'center', 'spaceAfter' => 80],
                );
            }
        }

        $section->addTextBreak(1, ['size' => 4]);
    }

    private function renderTable($section, array $block): void
    {
        $headers = $block['headers'];
        $rows = $block['rows'];

        if ($headers === []) {
            return;
        }

        $weights = [];
        foreach ($headers as $header) {
            $weights[] = max(6, min(30, mb_strlen($this->plainText($header)) + 4));
        }

        foreach ($rows as $row) {
            foreach ($row as $index => $value) {
                $length = mb_strlen($this->plainText($value));
                $weights[$index] = max($weights[$index] ?? 6, min(30, 6 + $length * 0.35));
            }
        }
        $weightSum = array_sum($weights);
        $widths = [];
        foreach ($weights as $weight) {
            $widths[] = max(700, (int) round(self::CONTENT_WIDTH * $weight / $weightSum));
        }

        $difference = self::CONTENT_WIDTH - array_sum($widths);
        if ($difference !== 0) {
            $widths[count($widths) - 1] += $difference;
        }

        $table = $section->addTable([
            'borderSize' => 4,
            'borderColor' => self::BORDER,
            'cellMargin' => 70,
        ]);
        $table->setWidth(self::CONTENT_WIDTH);

        $headerRow = $table->addRow(null, ['tblHeader' => true, 'cantSplit' => true]);
        foreach ($headers as $index => $header) {
            $cell = $headerRow->addCell($widths[$index], [
                'bgColor' => self::NAVY,
                'valign' => 'center',
            ]);
            $cell->addText($this->plainText($header), [
                'size' => 8.5,
                'bold' => true,
                'color' => 'FFFFFF',
            ], ['spaceAfter' => 0]);
        }

        foreach ($rows as $rowIndex => $row) {
            $tableRow = $table->addRow(null, ['cantSplit' => true]);
            foreach ($row as $index => $value) {
                $cell = $tableRow->addCell($widths[$index] ?? 1200, [
                    'valign' => 'top',
                    'bgColor' => $rowIndex % 2 === 0 ? 'FFFFFF' : 'F7F9FC',
                ]);
                $run = $cell->addTextRun(['spaceAfter' => 0]);
                $this->writeInline($run, $value, ['size' => 8, 'color' => '1F2937']);
            }
        }

        $section->addTextBreak(1, ['size' => 4]);
    }

    private function writeInline($run, string $text, array $font): void
    {
        $pattern = '/(\*\*[^*]+\*\*|`[^`]+`|\[[^\]]+\]\([^)]+\)|\*[^*]+\*)/u';
        $parts = preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        if ($parts === false) {
            $run->addText($this->esc($text), $font);

            return;
        }

        foreach ($parts as $part) {
            if (str_starts_with($part, '**') && str_ends_with($part, '**') && mb_strlen($part) > 4) {
                $run->addText($this->esc(substr($part, 2, -2)), array_merge($font, ['bold' => true]));

                continue;
            }

            if (str_starts_with($part, '`') && str_ends_with($part, '`') && mb_strlen($part) > 2) {
                $run->addText($this->esc(substr($part, 1, -1)), [
                    'name' => 'Consolas',
                    'size' => ($font['size'] ?? 9) - 1,
                    'color' => self::NAVY,
                ]);

                continue;
            }

            if (preg_match('/^\[([^\]]+)\]\(([^)]+)\)$/u', $part, $matches)) {
                $run->addLink($this->esc($matches[2]), $this->esc($matches[1]), array_merge($font, [
                    'color' => self::NAVY,
                    'underline' => 'single',
                ]));

                continue;
            }

            if (str_starts_with($part, '*') && str_ends_with($part, '*') && mb_strlen($part) > 2) {
                $run->addText($this->esc(substr($part, 1, -1)), array_merge($font, ['italic' => true]));

                continue;
            }

            $run->addText($this->esc($part), $font);
        }
    }

    private function esc(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function plainText(string $text): string
    {
        $text = preg_replace('/\[([^\]]+)\]\([^)]+\)/u', '$1', $text) ?? $text;
        $text = preg_replace('/\*\*([^*]+)\*\*/u', '$1', $text) ?? $text;
        $text = preg_replace('/\*([^*]+)\*/u', '$1', $text) ?? $text;
        $text = preg_replace('/`([^`]+)`/u', '$1', $text) ?? $text;

        return trim($text);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseMarkdown(string $markdown): array
    {
        $lines = preg_split('/\R/u', $markdown) ?: [];
        $blocks = [];
        $count = count($lines);
        $i = 0;

        while ($i < $count) {
            $line = $lines[$i];

            if (trim($line) === '') {
                $i++;

                continue;
            }

            if (preg_match('/^```([a-zA-Z0-9_-]*)\s*$/', trim($line), $matches)) {
                $language = strtolower($matches[1]);
                $buffer = [];
                $i++;

                while ($i < $count && ! preg_match('/^```\s*$/', trim($lines[$i]))) {
                    $buffer[] = $lines[$i];
                    $i++;
                }
                $i++;

                $blocks[] = [
                    'type' => $language === 'mermaid' ? 'mermaid' : 'code',
                    'text' => implode("\n", $buffer),
                ];

                continue;
            }

            if (preg_match('/^(#{1,6})\s+(.*)$/u', $line, $matches)) {
                $blocks[] = [
                    'type' => 'heading',
                    'level' => strlen($matches[1]),
                    'text' => trim($matches[2]),
                ];
                $i++;

                continue;
            }

            if (preg_match('/^\s*([-*]|\d+\.)\s+(.*)$/u', $line, $matches)) {
                $ordered = (bool) preg_match('/^\d+\./', $matches[1]);
                $items = [];

                while ($i < $count && preg_match('/^\s*([-*]|\d+\.)\s+(.*)$/u', $lines[$i], $itemMatch)) {
                    $items[] = trim($itemMatch[2]);
                    $i++;
                }

                $blocks[] = ['type' => 'list', 'ordered' => $ordered, 'items' => $items];

                continue;
            }

            if (str_starts_with(ltrim($line), '>')) {
                $buffer = [];

                while ($i < $count && str_starts_with(ltrim($lines[$i]), '>')) {
                    $buffer[] = trim(preg_replace('/^>\s?/', '', ltrim($lines[$i])) ?? '');
                    $i++;
                }

                $blocks[] = ['type' => 'quote', 'text' => implode(' ', $buffer)];

                continue;
            }

            if (str_starts_with(trim($line), '|')) {
                $rawRows = [];

                while ($i < $count && str_starts_with(trim($lines[$i]), '|')) {
                    $rawRows[] = trim($lines[$i]);
                    $i++;
                }

                $parsedRows = [];
                foreach ($rawRows as $rawRow) {
                    $parsedRows[] = array_map('trim', preg_split('/\s*\|\s*/', trim($rawRow, " \t|")) ?: []);
                }

                if (count($parsedRows) < 2) {
                    continue;
                }

                $headers = array_shift($parsedRows);
                if ($parsedRows !== [] && preg_match('/^[-: ]+$/', implode('', $parsedRows[0]))) {
                    array_shift($parsedRows);
                }

                $blocks[] = [
                    'type' => 'table',
                    'headers' => $headers,
                    'rows' => $parsedRows,
                ];

                continue;
            }

            if (preg_match('/^---+$/', trim($line))) {
                $i++;

                continue;
            }

            $buffer = [];
            $current = '';
            while ($i < $count && trim($lines[$i]) !== '' && ! $this->startsBlock($lines[$i])) {
                $raw = $lines[$i];
                $hardBreak = preg_match('/  $/', $raw) === 1;
                $text = trim($raw);
                $current = $current === '' ? $text : $current.' '.$text;

                if ($hardBreak) {
                    $buffer[] = $current;
                    $current = '';
                }

                $i++;
            }

            if ($current !== '') {
                $buffer[] = $current;
            }

            if ($buffer !== []) {
                $blocks[] = ['type' => 'paragraph', 'text' => implode("\n", $buffer)];
            }
        }

        return $blocks;
    }

    private function startsBlock(string $line): bool
    {
        $trimmed = trim($line);

        return str_starts_with($trimmed, '```')
            || str_starts_with($trimmed, '|')
            || str_starts_with($trimmed, '>')
            || preg_match('/^#{1,6}\s+/u', $trimmed) === 1
            || preg_match('/^\s*([-*]|\d+\.)\s+/u', $line) === 1
            || preg_match('/^---+$/', $trimmed) === 1;
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     */
    private function firstTitle(array $blocks): ?string
    {
        foreach ($blocks as $block) {
            if ($block['type'] === 'heading' && $block['level'] === 1) {
                return $this->plainText($block['text']);
            }
        }

        return null;
    }
}

$builder = new DocxBuilder;
$builder->build();
