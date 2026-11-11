<?php

namespace App\Services\Exports;

use App\Models\Meeting;
use Illuminate\Support\Str;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;
use PhpOffice\PhpWord\SimpleType\Jc;

class MeetingExportService
{
    public function __construct(private readonly TiptapHtmlRenderer $renderer) {}

    public function pdf(Meeting $meeting): string
    {
        $meeting = $this->prepare($meeting);
        $html = view('exports.meeting-pdf', [
            'meeting' => $meeting,
            'contentHtml' => $this->renderer->render($meeting->content),
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

    public function filename(Meeting $meeting, string $extension): string
    {
        $base = Str::slug($meeting->title) ?: 'dokumen-'.$meeting->id;
        $date = $meeting->scheduled_at?->format('Ymd') ?? now()->format('Ymd');

        return "{$base}-{$date}.{$extension}";
    }

    private function prepare(Meeting $meeting): Meeting
    {
        return $meeting->loadMissing([
            'division:id,name',
            'creator:id,name',
            'template:id,name',
            'participants.user:id,name',
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
}
