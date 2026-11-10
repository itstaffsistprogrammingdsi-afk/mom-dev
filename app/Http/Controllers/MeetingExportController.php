<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Services\AuditLogger;
use App\Services\Exports\MeetingExportService;
use Illuminate\Http\Response;

class MeetingExportController extends Controller
{
    public function pdf(Meeting $meeting, MeetingExportService $exporter, AuditLogger $audit): Response
    {
        $this->authorize('view', $meeting);

        $audit->log('meeting.exported', $meeting, 'Mengekspor dokumen ke PDF', ['format' => 'pdf'], $meeting->id);

        return response($exporter->pdf($meeting), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$exporter->filename($meeting, 'pdf').'"',
        ]);
    }

    public function docx(Meeting $meeting, MeetingExportService $exporter, AuditLogger $audit): Response
    {
        $this->authorize('view', $meeting);

        $audit->log('meeting.exported', $meeting, 'Mengekspor dokumen ke Word', ['format' => 'docx'], $meeting->id);

        return response($exporter->docx($meeting), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="'.$exporter->filename($meeting, 'docx').'"',
        ]);
    }
}
