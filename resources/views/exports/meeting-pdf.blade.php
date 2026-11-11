<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: "dejavusans", sans-serif;
            font-size: 10pt;
            line-height: 1.55;
            color: #172033;
        }

        h1, h2, h3, h4 {
            color: #172033;
            line-height: 1.25;
            margin: 14px 0 6px;
        }

        h1 { font-size: 18pt; }
        h2 { font-size: 13pt; border-bottom: 1px solid #e2e7f0; padding-bottom: 3px; }
        h3 { font-size: 11pt; }

        p { margin: 0 0 7px; }
        ul, ol { margin: 0 0 8px 18px; padding: 0; }
        li { margin-bottom: 3px; }

        table { width: 100%; border-collapse: collapse; margin: 8px 0 12px; }
        th, td { border: 1px solid #e2e7f0; padding: 6px 7px; vertical-align: top; text-align: left; }
        th { background: #2456c7; color: #ffffff; font-size: 8.5pt; }
        td { font-size: 8.8pt; }

        .brand {
            font-size: 8.5pt;
            color: #536079;
            margin-bottom: 6px;
        }

        .title {
            font-size: 20pt;
            font-weight: bold;
            color: #172033;
            margin: 0 0 6px;
        }

        .subtitle {
            color: #536079;
            font-size: 9pt;
            margin-bottom: 12px;
        }

        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 10px;
            font-size: 8pt;
            font-weight: bold;
            margin-right: 4px;
        }

        .badge-blue { background: #e8f0fe; color: #1f4fa8; }
        .badge-green { background: #e9f7f0; color: #11663f; }
        .badge-amber { background: #fff4e2; color: #7a4400; }
        .badge-red { background: #fdecee; color: #a61b2b; }

        .meta-table td { border: 0; border-bottom: 1px solid #f1f4f9; padding: 4px 0; }
        .meta-label { width: 120px; color: #536079; font-size: 9pt; }
        .meta-value { color: #172033; font-size: 9pt; font-weight: bold; }

        .section-title {
            font-size: 12.5pt;
            color: #172033;
            border-bottom: 1px solid #e2e7f0;
            padding-bottom: 3px;
            margin: 18px 0 8px;
        }

        .mention {
            border-radius: 9999px;
            padding: 1px 5px;
            font-weight: bold;
        }

        .mention-person { background: #e8f0fe; color: #1f4fa8; }
        .mention-division { background: #e3f5f1; color: #0b655d; }
        .mention-topic { background: #fff2d9; color: #7a4a00; }

        .task-list { list-style: none; margin-left: 0; padding-left: 0; }
        .task-item { margin-bottom: 4px; }
        .task-mark { font-weight: bold; margin-right: 4px; }

        .muted { color: #536079; }
        .small { font-size: 8.5pt; }
        .confidentiality {
            border-left: 3px solid #2456c7;
            background: #eaf4fc;
            padding: 7px 9px;
            font-size: 8.5pt;
            color: #1e3f85;
            margin: 10px 0 14px;
        }
    </style>
</head>
<body>
    <div class="brand">Sistem Notulensi (MoM)</div>
    <div class="title">{{ $meeting->title }}</div>
    <div class="subtitle">
        <span class="badge badge-blue">{{ $meeting->document_type?->label() ?? 'Dokumen' }}</span>
        <span class="badge badge-green">{{ $meeting->status?->label() ?? '-' }}</span>
        <span class="badge {{ $meeting->confidentiality?->value === 'secret' ? 'badge-red' : ($meeting->confidentiality?->value === 'restricted' ? 'badge-amber' : 'badge-blue') }}">
            {{ $meeting->confidentiality?->label() ?? '-' }}
        </span>
    </div>

    <table class="meta-table">
        <tr>
            <td class="meta-label">Jadwal</td>
            <td class="meta-value">{{ $meeting->scheduled_at?->translatedFormat('d F Y H:i') ?? '-' }}</td>
            <td class="meta-label">Divisi</td>
            <td class="meta-value">{{ $meeting->division?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Pembuat</td>
            <td class="meta-value">{{ $meeting->creator?->name ?? '-' }}</td>
            <td class="meta-label">Notulen</td>
            <td class="meta-value">{{ $meeting->creator?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Lokasi</td>
            <td class="meta-value">{{ $meeting->location ?? '-' }}</td>
            <td class="meta-label">Template</td>
            <td class="meta-value">{{ $meeting->template?->name ?? '-' }}</td>
        </tr>
    </table>

    @if ($meeting->confidentiality?->value === 'secret' || $meeting->confidentiality?->value === 'restricted')
        <div class="confidentiality">
            Dokumen ini memiliki label {{ $meeting->confidentiality?->label() }}. Jangan sebarkan tanpa izin pemilik dokumen.
        </div>
    @endif

    @if ($meeting->tags->isNotEmpty())
        <p class="small muted">
            Tag:
            @foreach ($meeting->tags as $tag)
                <span class="mention mention-{{ $tag->type?->value ?? 'person' }}">
                    {{ $tag->type?->value === 'topic' ? '#' : '@' }}{{ $tag->target_name }}
                </span>
            @endforeach
        </p>
    @endif

    <div class="section-title">Isi Dokumen</div>
    {!! $contentHtml !!}

    @if ($meeting->participants->isNotEmpty())
        <div class="section-title">Peserta</div>
        <ul>
            @foreach ($meeting->participants as $participant)
                <li>
                    {{ $participant->user?->name ?? $participant->name ?? $participant->email ?? '-' }}
                    <span class="muted small">· {{ $participant->attendance?->label() ?? '-' }}</span>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($meeting->decisions->isNotEmpty())
        <div class="section-title">Keputusan</div>
        <ol>
            @foreach ($meeting->decisions as $decision)
                <li>
                    {{ $decision->content }}
                    @if ($decision->topic)
                        <span class="muted small">#{{ $decision->topic->name }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    @endif

    @if ($meeting->actionItems->isNotEmpty())
        <div class="section-title">Action Item</div>
        <table>
            <thead>
                <tr>
                    <th>Tugas</th>
                    <th>PIC</th>
                    <th>Divisi</th>
                    <th>Tenggat</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($meeting->actionItems as $item)
                    <tr>
                        <td>{{ $item->title }}</td>
                        <td>{{ $item->pic?->name ?? $item->pic_name ?? '-' }}</td>
                        <td>{{ $item->division?->name ?? '-' }}</td>
                        <td>{{ $item->due_date?->format('d M Y') ?? '-' }}</td>
                        <td>{{ $item->status?->label() ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p class="small muted" style="margin-top: 20px;">
        Dibuat otomatis oleh Sistem Notulensi (MoM) pada {{ now()->translatedFormat('d F Y H:i') }}.
    </p>
</body>
</html>
