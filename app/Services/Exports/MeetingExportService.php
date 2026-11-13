<?php

namespace App\Services\Exports;

use App\Enums\DocumentType;
use App\Models\Meeting;
use App\Services\MeetingNumberGenerator;
use Illuminate\Support\Str;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;
use PhpOffice\PhpWord\SimpleType\Jc;

class MeetingExportService
{
    public function __construct(
        private readonly TiptapHtmlRenderer $renderer,
        private readonly MeetingNumberGenerator $numbers,
    ) {}

    public function pdf(Meeting $meeting): string
    {
        $meeting = $this->prepare($meeting);
        $contentHtml = $this->renderer->render($meeting->content);
        $isMeetingMinutes = $meeting->document_type === DocumentType::Meeting;

        $html = view($isMeetingMinutes ? 'exports.meeting-minutes-pdf' : 'exports.meeting-pdf', [
            'meeting' => $meeting,
            'contentHtml' => $contentHtml,
            'nomor' => $this->effectiveNumber($meeting),
            'logoPath' => public_path('company-logo.png'),
            'hasTable' => str_contains($contentHtml, '<table'),
            'hasResultHeading' => stripos($contentHtml, 'hasil rapat') !== false,
            'hasAgendaHeading' => (bool) preg_match('/<h[1-3][^>]*>\s*Agenda/i', $contentHtml),
        ])->render();

        $tempDir = storage_path('app/mpdf');

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 16,
            'margin_right' => 16,
            'margin_top' => 18,
            'margin_bottom' => 18,
            'default_font' => 'dejavusans',
            'default_font_size' => 10,
            'tempDir' => $tempDir,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ]);

        $logoPath = public_path('company-logo.png');

        if (is_file($logoPath)) {
            $mpdf->imageVars['momlogo'] = file_get_contents($logoPath);
        }

        $mpdf->SetTitle($meeting->title);
        $mpdf->SetAuthor($meeting->creator?->name ?? 'Sistem Notulensi');
        $mpdf->SetHTMLFooter(
            '<div style="border-top:1px solid #e2e7f0;padding-top:4px;font-size:8pt;color:#536079;">'
            .'Sistem Notulensi (MoM) · Halaman {PAGENO} dari {nbpg}'
            .'</div>'
        );
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', Destination::STRING_RETURN);
    }

    public function docx(Meeting $meeting): string
    {
        $meeting = $this->prepare($meeting);
        $contentHtml = $this->renderer->render($meeting->content);

        if ($meeting->document_type === DocumentType::Meeting) {
            return $this->docxMeetingMinutes($meeting, $contentHtml);
        }

        $word = new PhpWord;
        $word->setDefaultFontName('Segoe UI');
        $word->setDefaultFontSize(10);
        $word->getDocInfo()
            ->setTitle($meeting->title)
            ->setCreator($meeting->creator?->name ?? 'Sistem Notulensi')
            ->setDescription('Dokumen '.($meeting->document_type?->label() ?? 'Notulen'));

        $section = $word->addSection([
            'marginTop' => 900,
            'marginBottom' => 900,
            'marginLeft' => 1000,
            'marginRight' => 1000,
        ]);

        $header = $section->addHeader();
        $header->addText('Sistem Notulensi (MoM)', ['size' => 8, 'color' => '536079']);

        $footer = $section->addFooter();
        $footer->addPreserveText('Halaman {PAGE} dari {NUMPAGES}', ['size' => 8, 'color' => '536079'], ['alignment' => Jc::END]);

        $section->addText($this->esc($meeting->title), [
            'size' => 20,
            'bold' => true,
            'color' => '172033',
        ], ['spaceAfter' => 80]);

        $section->addText(
            $this->esc(($meeting->document_type?->label() ?? 'Notulen').' · '.($meeting->confidentiality?->label() ?? '-')),
            ['size' => 9, 'color' => '536079'],
            ['spaceAfter' => 180]
        );

        $meta = [
            ['Jenis dokumen', $meeting->document_type?->label() ?? '-'],
            ['Jadwal', $meeting->scheduled_at?->translatedFormat('d F Y H:i') ?? '-'],
            ['Divisi', $meeting->division?->name ?? '-'],
            ['Pembuat', $meeting->creator?->name ?? '-'],
            ['Status', $meeting->status?->label() ?? '-'],
            ['Kerahasiaan', $meeting->confidentiality?->label() ?? '-'],
        ];

        $table = $section->addTable(['borderSize' => 0, 'cellMargin' => 80]);
        $table->setWidth(9000);

        foreach ($meta as [$label, $value]) {
            $row = $table->addRow(null, ['cantSplit' => true]);
            $row->addCell(2300)->addText($this->esc($label), ['size' => 9, 'color' => '536079']);
            $row->addCell(6700)->addText($this->esc($value), ['size' => 9, 'color' => '172033', 'bold' => true]);
        }

        $section->addTextBreak(1);

        if (trim($contentHtml) !== '') {
            Html::addHtml($section, $contentHtml, false, false);
        }

        if ($meeting->participants->isNotEmpty()) {
            $section->addTitle('Peserta', 2);

            foreach ($meeting->participants as $participant) {
                $name = $participant->user?->name ?? $participant->name ?? $participant->email ?? '-';
                $attendance = $participant->attendance?->label() ?? '-';
                $section->addListItem($this->esc($name.' - '.$attendance), 0);
            }
        }

        if ($meeting->decisions->isNotEmpty()) {
            $section->addTitle('Keputusan', 2);

            foreach ($meeting->decisions as $decision) {
                $section->addListItem($this->esc($decision->content), 0);
            }
        }

        if ($meeting->actionItems->isNotEmpty()) {
            $section->addTitle('Action Item', 2);

            $taskTable = $section->addTable([
                'borderSize' => 4,
                'borderColor' => 'E2E7F0',
                'cellMargin' => 60,
            ]);
            $taskTable->setWidth(9000);

            $headerRow = $taskTable->addRow();
            foreach (['Tugas', 'PIC', 'Divisi', 'Tenggat', 'Status'] as $heading) {
                $headerRow->addCell(1800, ['bgColor' => '2456C7'])->addText($this->esc($heading), [
                    'size' => 8,
                    'bold' => true,
                    'color' => 'FFFFFF',
                ]);
            }

            foreach ($meeting->actionItems as $item) {
                $row = $taskTable->addRow();
                $row->addCell(3000)->addText($this->esc($item->title), ['size' => 8]);
                $row->addCell(1600)->addText($this->esc($item->pic?->name ?? $item->pic_name ?? '-'), ['size' => 8]);
                $row->addCell(1400)->addText($this->esc($item->division?->name ?? '-'), ['size' => 8]);
                $row->addCell(1200)->addText($this->esc($item->due_date?->format('d M Y') ?? '-'), ['size' => 8]);
                $row->addCell(1000)->addText($this->esc($item->status?->label() ?? '-'), ['size' => 8]);
            }
        }

        $temp = tempnam(sys_get_temp_dir(), 'mom_export_');
        $path = $temp.'.docx';
        IOFactory::createWriter($word, 'Word2007')->save($path);
        $content = (string) file_get_contents($path);

        @unlink($path);
        @unlink($temp);

        return $content;
    }

    private function docxMeetingMinutes(Meeting $meeting, string $contentHtml): string
    {
        $word = new PhpWord;
        $word->setDefaultFontName('Segoe UI');
        $word->setDefaultFontSize(9);
        $word->getDocInfo()
            ->setTitle($meeting->title)
            ->setCreator($meeting->creator?->name ?? 'Sistem Notulensi')
            ->setDescription('Notulensi Rapat');

        $section = $word->addSection([
            'marginTop' => 700,
            'marginBottom' => 700,
            'marginLeft' => 800,
            'marginRight' => 800,
        ]);

        $footer = $section->addFooter();
        $footer->addPreserveText(
            'Sistem Notulensi (MoM) · Halaman {PAGE} dari {NUMPAGES}',
            ['size' => 8, 'color' => '536079'],
            ['alignment' => Jc::END]
        );

        $headerTable = $section->addTable([
            'borderSize' => 4,
            'borderColor' => '4B5563',
            'cellMargin' => 80,
        ]);
        $headerTable->setWidth(9600);

        $headerRow = $headerTable->addRow(null, ['cantSplit' => true]);
        $logoCell = $headerRow->addCell(2200, ['valign' => 'center', 'bgColor' => 'FFFFFF']);
        $logoPath = public_path('company-logo.png');

        if (is_file($logoPath)) {
            $logoCell->addImage($logoPath, [
                'width' => 120,
                'height' => 75,
                'alignment' => Jc::CENTER,
            ]);
        }

        $titleCell = $headerRow->addCell(7400, ['bgColor' => 'FDF3D7', 'valign' => 'center']);
        $titleCell->addText('NOTULENSI RAPAT', ['size' => 16, 'bold' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 40]);
        $titleCell->addText(
            'Nomor Notulensi: '.$this->effectiveNumber($meeting),
            ['size' => 8.5, 'color' => 'B91C1C'],
            ['alignment' => Jc::CENTER]
        );

        $participants = $meeting->participants
            ->map(function ($participant) {
                $name = $participant->user?->name ?? $participant->name ?? $participant->email;
                $title = $participant->user?->job_title;

                return $name && $title ? "{$name} ({$title})" : $name;
            })
            ->filter()
            ->implode(', ');

        $metadata = [
            ['Topik', $meeting->title],
            ['Tanggal', $meeting->scheduled_at?->translatedFormat('l, d F Y H:i') ?? '-'],
            ['Peserta', $participants !== '' ? $participants : '-'],
        ];

        $metaTable = $section->addTable([
            'borderSize' => 4,
            'borderColor' => '4B5563',
            'cellMargin' => 60,
        ]);
        $metaTable->setWidth(9600);

        foreach ($metadata as [$label, $value]) {
            $row = $metaTable->addRow(null, ['cantSplit' => true]);
            $row->addCell(1500, ['bgColor' => 'E5E7EB'])->addText($this->esc($label), ['size' => 8.5, 'bold' => true]);
            $row->addCell(8100)->addText($this->esc((string) $value), ['size' => 8.5]);
        }

        $section->addTextBreak(1);

        $hasAgendaHeading = (bool) preg_match('/<h[1-3][^>]*>\s*Agenda/i', $contentHtml);

        if (! $hasAgendaHeading) {
            $section->addText('AGENDA PEMBAHASAN', ['size' => 10, 'bold' => true], ['spaceAfter' => 60]);

            $agendaLines = collect(preg_split('/\r\n|\r|\n/', (string) $meeting->agenda))
                ->map(fn (string $line) => trim($line))
                ->filter()
                ->values();

            if ($agendaLines->isEmpty()) {
                $agendaLines = collect(['', '', '']);
            }

            $agendaTable = $section->addTable([
                'borderSize' => 4,
                'borderColor' => '4B5563',
                'cellMargin' => 40,
            ]);
            $agendaTable->setWidth(9600);

            foreach ($agendaLines as $index => $line) {
                $row = $agendaTable->addRow();
                $row->addCell(500, ['bgColor' => 'FDF3D7'])->addText((string) ($index + 1), ['size' => 8, 'bold' => true], ['alignment' => Jc::CENTER]);
                $row->addCell(9100)->addText($this->esc((string) $line), ['size' => 8.5]);
            }

            $section->addTextBreak(1);
        }

        if (stripos($contentHtml, 'hasil rapat') === false) {
            $section->addText('HASIL RAPAT', ['size' => 10, 'bold' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 60]);
        }

        if (trim($contentHtml) !== '') {
            Html::addHtml($section, $contentHtml, false, false);
        }

        if (! str_contains($contentHtml, '<table') && $meeting->actionItems->isNotEmpty()) {
            $section->addTitle('Action Item', 2);

            $taskTable = $section->addTable([
                'borderSize' => 4,
                'borderColor' => '4B5563',
                'cellMargin' => 40,
            ]);
            $taskTable->setWidth(9600);

            $headerRow = $taskTable->addRow();
            $headings = ['Point Diskusi', 'Keterangan', 'Action Plan', 'Skala Prioritas', 'Divisi', 'PIC', 'Deadline', 'Output'];
            $widths = [1200, 1400, 1400, 900, 1100, 1100, 900, 1000];

            foreach ($headings as $index => $heading) {
                $headerRow->addCell($widths[$index], ['bgColor' => 'FDF3D7'])->addText(
                    $this->esc($heading),
                    ['size' => 7.5, 'bold' => true],
                    ['alignment' => Jc::CENTER]
                );
            }

            foreach ($meeting->actionItems as $item) {
                $row = $taskTable->addRow();
                $values = [
                    $item->title,
                    $item->description ?? '-',
                    $item->title,
                    '-',
                    $item->division?->name ?? '-',
                    $item->pic?->name ?? $item->pic_name ?? '-',
                    $item->due_date?->format('d M Y') ?? '-',
                    $item->notes ?? $item->status?->label() ?? '-',
                ];

                foreach ($values as $index => $value) {
                    $row->addCell($widths[$index])->addText($this->esc((string) $value), ['size' => 7.5]);
                }
            }
        }

        $signatureTable = $section->addTable([
            'borderSize' => 4,
            'borderColor' => '4B5563',
            'cellMargin' => 80,
        ]);
        $signatureTable->setWidth(9600);

        $signatureHeader = $signatureTable->addRow();
        $creatorHeader = $signatureHeader->addCell(4800, ['bgColor' => 'E5E7EB']);
        $creatorHeader->addText('Dibuat Oleh:', ['size' => 8.5, 'bold' => true]);
        $creatorHeader->addText('(wajib diisi tanda tangan notulis)', ['size' => 7, 'italic' => true, 'color' => '4B5563']);

        $approverHeader = $signatureHeader->addCell(4800, ['bgColor' => 'E5E7EB']);
        $approverHeader->addText('Disetujui Oleh:', ['size' => 8.5, 'bold' => true]);
        $approverHeader->addText('(tanda tangan pimpinan sudah mengetahui & approval)', ['size' => 7, 'italic' => true, 'color' => '4B5563']);

        $signatureRow = $signatureTable->addRow();
        $creatorCell = $signatureRow->addCell(4800);
        $creatorCell->addText(' ');
        $creatorCell->addText(' ');
        $creatorCell->addText($this->esc($meeting->creator?->name ?? '-'), ['size' => 8.5, 'bold' => true]);
        $creatorCell->addText('Notulis', ['size' => 8, 'color' => '4B5563']);
        $creatorCell->addText('(wajib diisi nama penyusun notulen)', ['size' => 7, 'italic' => true, 'color' => '4B5563']);

        $approverCell = $signatureRow->addCell(4800);
        $approverCell->addText(' ');
        $approverCell->addText(' ');
        $approverCell->addText($this->esc($meeting->approvedBy?->name ?? config('app.default_approver_name')), ['size' => 8.5, 'bold' => true]);
        $approverCell->addText('Pimpinan Rapat', ['size' => 8, 'color' => '4B5563']);

        $section->addText(
            'Notes: Dilarang mengubah format notulensi yang telah disetujui oleh Pimpinan.',
            ['size' => 8, 'italic' => true, 'color' => '374151']
        );

        $temp = tempnam(sys_get_temp_dir(), 'mom_export_');
        $path = $temp.'.docx';
        IOFactory::createWriter($word, 'Word2007')->save($path);
        $content = (string) file_get_contents($path);

        @unlink($path);
        @unlink($temp);

        return $content;
    }

    public function filename(Meeting $meeting, string $extension): string
    {
        $base = Str::slug($meeting->title) ?: 'dokumen-'.$meeting->id;
        $date = $meeting->scheduled_at?->format('Ymd') ?? now()->format('Ymd');

        return "{$base}-{$date}.{$extension}";
    }

    private function prepare(Meeting $meeting): Meeting
    {
        return $meeting->loadMissing([
            'division:id,name,head_id',
            'division.head:id,name',
            'creator:id,name',
            'template:id,name',
            'participants.user:id,name,job_title',
            'approvedBy:id,name',
            'decisions.topic:id,name',
            'actionItems.pic:id,name',
            'actionItems.division:id,name',
            'tags',
        ]);
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function effectiveNumber(Meeting $meeting): string
    {
        return $meeting->nomor_notulensi ?: $this->numbers->generate($meeting->division, $meeting->scheduled_at);
    }}
