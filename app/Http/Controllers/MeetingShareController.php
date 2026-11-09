<?php

namespace App\Http\Controllers;

use App\Enums\Confidentiality;
use App\Mail\MeetingSharedMail;
use App\Models\Meeting;
use App\Services\AuditLogger;
use App\Services\Exports\MeetingExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class MeetingShareController extends Controller
{
    public function send(
        Request $request,
        Meeting $meeting,
        AuditLogger $audit,
        MeetingExportService $exporter,
    ): RedirectResponse {
        $this->authorize('view', $meeting);

        $validated = $request->validate([
            'recipients' => ['required', 'string', 'max:1000'],
            'message' => ['nullable', 'string', 'max:2000'],
            'include_pdf' => ['boolean'],
            'confirm_share' => ['boolean'],
        ]);

        $emails = collect(explode(',', $validated['recipients']))
            ->map(fn (string $email) => trim($email))
            ->filter()
            ->unique()
            ->values();

        if ($emails->isEmpty()) {
            throw ValidationException::withMessages(['recipients' => 'Masukkan minimal satu alamat email.']);
        }

        if ($emails->count() > 10) {
            throw ValidationException::withMessages(['recipients' => 'Maksimal 10 penerima per pengiriman.']);
        }

        $invalid = $emails->filter(fn (string $email) => filter_var($email, FILTER_VALIDATE_EMAIL) === false);

        if ($invalid->isNotEmpty()) {
            throw ValidationException::withMessages([
                'recipients' => 'Alamat email tidak valid: '.$invalid->implode(', '),
            ]);
        }

        if (
            in_array($meeting->confidentiality, [Confidentiality::Restricted, Confidentiality::Secret], true)
            && ! $request->boolean('confirm_share')
        ) {
            throw ValidationException::withMessages([
                'confirm_share' => 'Konfirmasi bahwa penerima berhak menerima dokumen dengan label ini.',
            ]);
        }

        $includePdf = $request->boolean('include_pdf');
        $pdfFilename = $includePdf ? $exporter->filename($meeting, 'pdf') : null;

        try {
            $pdfContent = $includePdf ? $exporter->pdf($meeting) : null;

            foreach ($emails as $email) {
                Mail::to($email)->send(new MeetingSharedMail(
                    $meeting,
                    $validated['message'] ?? null,
                    $includePdf,
                    $pdfContent,
                    $pdfFilename,
                    $request->user(),
                ));
            }
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'Email gagal dikirim. Periksa konfigurasi email lalu coba lagi.');
        }

        $audit->log(
            'meeting.shared',
            $meeting,
            'Membagikan dokumen melalui email',
            ['recipients' => $emails->all(), 'include_pdf' => $includePdf],
            $meeting->id,
        );

        return back()->with('success', 'Dokumen dibagikan ke '.$emails->count().' alamat email.');
    }
}
