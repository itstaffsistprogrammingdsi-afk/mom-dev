<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: "dejavusans", sans-serif;
            font-size: 9pt;
            line-height: 1.45;
            color: #111827;
        }

        .header-table,
        .info-table,
        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            border: 1px solid #4b5563;
            padding: 6px 8px;
            vertical-align: middle;
        }

        .logo-cell {
            width: 23%;
            text-align: center;
            background: #ffffff;
        }

        .logo-cell img {
            display: block;
            margin: 0 auto;
        }

        .logo-fallback {
            font-size: 16pt;
            font-weight: bold;
            color: #2456c7;
        }

        .logo-text {
            margin-top: 2px;
            font-size: 7.5pt;
            font-weight: bold;
            color: #374151;
            letter-spacing: 0.4px;
        }

        .title-cell {
            width: 77%;
            text-align: center;
            background: #fdf3d7;
        }

        .doc-title {
            font-size: 16pt;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .doc-number {
            margin-top: 4px;
            font-size: 8.5pt;
            color: #b91c1c;
        }

        .info-table {
            margin-top: 6px;
        }

        .info-table th,
        .info-table td {
            border: 1px solid #4b5563;
            padding: 4px 6px;
            vertical-align: top;
            text-align: left;
            font-size: 8.5pt;
        }

        .info-table th {
            width: 16%;
            background: #e5e7eb;
            font-weight: bold;
        }

        .section-heading {
            margin: 12px 0 5px;
            padding: 4px 6px;
            border: 1px solid #4b5563;
            background: #fdf3d7;
            text-align: center;
            font-size: 10pt;
            font-weight: bold;
            letter-spacing: 0.4px;
        }

        .agenda-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
        }

        .agenda-table td {
            border: 1px solid #4b5563;
            padding: 4px 6px;
            font-size: 8.5pt;
            vertical-align: top;
        }

        .agenda-number {
            width: 6%;
            background: #e5e7eb;
            text-align: center;
            font-weight: bold;
        }

        .content {
            font-size: 8.7pt;
        }

        .content h1,
        .content h2,
        .content h3 {
            margin: 10px 0 5px;
            font-size: 10.5pt;
            line-height: 1.3;
            color: #111827;
        }

        .content p {
            margin: 0 0 5px;
        }

        .content ul,
        .content ol {
            margin: 0 0 6px 16px;
            padding: 0;
        }

        .content li {
            margin-bottom: 2px;
        }

        .content table {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0 8px;
            table-layout: fixed;
        }

        .content th,
        .content td {
            border: 1px solid #4b5563;
            padding: 4px;
            vertical-align: top;
            font-size: 7.8pt;
            overflow-wrap: break-word;
        }

        .content th {
            background: #fdf3d7;
            font-weight: bold;
            text-align: center;
        }

        .content th p {
            margin: 0;
        }

        .content th p + p {
            margin-top: 2px;
            font-size: 6.5pt;
            font-weight: normal;
            font-style: italic;
            line-height: 1.25;
            color: #4b5563;
        }

        .task-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        .task-table th,
        .task-table td {
            border: 1px solid #4b5563;
            padding: 4px;
            font-size: 7.8pt;
            vertical-align: top;
            text-align: left;
        }

        .task-table th {
            background: #fdf3d7;
            text-align: center;
        }

        .confidentiality {
            margin-top: 6px;
            border: 1px solid #b91c1c;
            background: #fee2e2;
            color: #7f1d1d;
            padding: 5px 7px;
            font-size: 8pt;
        }

        .signature-table {
            margin-top: 14px;
        }

        .signature-table th,
        .signature-table td {
            border: 1px solid #4b5563;
            padding: 5px 7px;
            font-size: 8.5pt;
            vertical-align: top;
        }

        .signature-table th {
            background: #e5e7eb;
            text-align: left;
        }

        .signature-space {
            height: 42px;
        }

        .signature-hint {
            margin-top: 1px;
            font-size: 6.8pt;
            font-weight: normal;
            font-style: italic;
            color: #4b5563;
        }

        .muted {
            color: #4b5563;
        }

        .note {
            margin-top: 8px;
            font-size: 8pt;
            font-style: italic;
            color: #374151;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                @if ($logoPath && file_exists($logoPath))
                    <img src="var:momlogo" width="120" height="75" alt="Derma Semulia Indonesia">
                @else
                    <div class="logo-fallback">MoM</div>
                @endif
            </td>
            <td class="title-cell">
                <div class="doc-title">NOTULENSI RAPAT</div>
                <div class="doc-number">Nomor Notulensi: {{ $nomor }}</div>
            </td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <th>Topik</th>
            <td>{{ $meeting->title }}</td>
        </tr>
        <tr>
            <th>Tanggal</th>
            <td>{{ $meeting->scheduled_at?->translatedFormat('l, d F Y H:i') ?? '-' }}</td>
        </tr>
        <tr>
            <th>Peserta</th>
            <td>
                @php
                    $participants = $meeting->participants
                        ->map(function ($participant) {
                            $name = $participant->user?->name ?? $participant->name ?? $participant->email;
                            $title = $participant->user?->job_title;

                            return $name && $title ? "{$name} ({$title})" : $name;
                        })
                        ->filter()
                        ->values();
                @endphp
                {{ $participants->isNotEmpty() ? $participants->implode(', ') : '-' }}
            </td>
        </tr>
    </table>

    @if ($meeting->confidentiality?->value === 'secret' || $meeting->confidentiality?->value === 'restricted')
        <div class="confidentiality">
            Dokumen ini berlabel {{ $meeting->confidentiality?->label() }}. Jangan sebarkan tanpa izin pemilik dokumen.
        </div>
    @endif

    @if (! $hasAgendaHeading)
        <div class="section-heading">AGENDA PEMBAHASAN</div>
        @php
            $agendaLines = collect(preg_split('/\r\n|\r|\n/', (string) $meeting->agenda))
                ->map(fn ($line) => trim($line))
                ->filter()
                ->values();

            if ($agendaLines->isEmpty()) {
                $agendaLines = collect(['', '', '']);
            }
        @endphp
        <table class="agenda-table">
            @foreach ($agendaLines as $index => $line)
                <tr>
                    <td class="agenda-number">{{ $index + 1 }}</td>
                    <td>{{ $line }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if (! $hasResultHeading)
        <div class="section-heading">HASIL RAPAT</div>
    @endif

    <div class="content">
        {!! $contentHtml !!}
    </div>

    @if (! $hasTable && $meeting->actionItems->isNotEmpty())
        <div class="section-heading">ACTION ITEM</div>
        <table class="task-table">
            <thead>
                <tr>
                    <th>Point Diskusi</th>
                    <th>Keterangan</th>
                    <th>Action Plan</th>
                    <th>Skala Prioritas</th>
                    <th>Divisi</th>
                    <th>PIC</th>
                    <th>Deadline</th>
                    <th>Output</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($meeting->actionItems as $item)
                    <tr>
                        <td>{{ $item->title }}</td>
                        <td>{{ $item->description ?? '-' }}</td>
                        <td>{{ $item->title }}</td>
                        <td>-</td>
                        <td>{{ $item->division?->name ?? '-' }}</td>
                        <td>{{ $item->pic?->name ?? $item->pic_name ?? '-' }}</td>
                        <td>{{ $item->due_date?->format('d M Y') ?? '-' }}</td>
                        <td>{{ $item->notes ?? $item->status?->label() ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <table class="signature-table">
        <tr>
            <th style="width: 50%;">
                Dibuat Oleh:
                <div class="signature-hint">(wajib diisi tanda tangan notulis)</div>
            </th>
            <th style="width: 50%;">
                Disetujui Oleh:
                <div class="signature-hint">(tanda tangan pimpinan sudah mengetahui &amp; approval)</div>
            </th>
        </tr>
        <tr>
            <td>
                <div class="signature-space"></div>
                <strong>{{ $meeting->creator?->name ?? '-' }}</strong>
                <div class="muted">Notulis</div>
                <div class="signature-hint">(wajib diisi nama penyusun notulen)</div>
            </td>
            <td>
                <div class="signature-space"></div>
                <strong>{{ $meeting->approvedBy?->name ?? config('app.default_approver_name') }}</strong>
                <div class="muted">Pimpinan Rapat</div>
            </td>
        </tr>
    </table>

    <div class="note">Notes: Dilarang mengubah format notulensi yang telah disetujui oleh Pimpinan.</div>
</body>
</html>
