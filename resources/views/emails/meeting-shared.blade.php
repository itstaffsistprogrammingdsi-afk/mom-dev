<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, Helvetica, sans-serif; background: #f5f7fb; margin: 0; padding: 24px; color: #1f2937; }
        .wrap { max-width: 620px; margin: 0 auto; }
        .card { background: #ffffff; border: 1px solid #e5eaf3; border-radius: 12px; overflow: hidden; }
        .head { background: #1f3864; color: #ffffff; padding: 22px 26px; }
        .brand { font-size: 11px; letter-spacing: 0.14em; text-transform: uppercase; color: #bfd0ea; }
        .title { font-size: 21px; font-weight: bold; margin: 8px 0 0; line-height: 1.3; }
        .body { padding: 24px 26px; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 999px; font-size: 11px; font-weight: bold; background: #e0edff; color: #1d4ed8; margin-right: 4px; }
        .meta { width: 100%; border-collapse: collapse; margin: 16px 0; }
        .meta td { padding: 4px 0; font-size: 13px; color: #4b5563; }
        .meta td:first-child { width: 110px; color: #6b7280; }
        .message { background: #f8fafc; border-left: 3px solid #2f5597; padding: 12px 14px; font-size: 14px; white-space: pre-line; margin: 16px 0; }
        .button { display: inline-block; background: #1f3864; color: #ffffff; text-decoration: none; padding: 11px 18px; border-radius: 8px; font-size: 14px; font-weight: bold; }
        .note { font-size: 12px; color: #6b7280; line-height: 1.5; margin-top: 16px; }
        .footer { text-align: center; font-size: 11px; color: #9ca3af; padding: 16px; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <div class="head">
                <div class="brand">Sistem Notulensi (MoM)</div>
                <div class="title">{{ $meeting->title }}</div>
            </div>
            <div class="body">
                <p style="margin-top:0;font-size:14px;">Halo,</p>
                <p style="font-size:14px;">
                    <strong>{{ $senderName }}</strong> membagikan dokumen berikut kepada Anda.
                </p>

                <div>
                    <span class="badge">{{ $meeting->document_type?->label() ?? 'Dokumen' }}</span>
                    <span class="badge">{{ $meeting->confidentiality?->label() ?? '-' }}</span>
                </div>

                <table class="meta">
                    <tr><td>Jadwal</td><td>{{ $meeting->scheduled_at?->translatedFormat('d F Y H:i') ?? '-' }}</td></tr>
                    <tr><td>Divisi</td><td>{{ $meeting->division?->name ?? '-' }}</td></tr>
                    <tr><td>Status</td><td>{{ $meeting->status?->label() ?? '-' }}</td></tr>
                </table>

                @if ($messageText)
                    <div class="message">{{ $messageText }}</div>
                @endif

                <p style="margin-top:20px;">
                    <a href="{{ $meetingUrl }}" class="button">Buka dokumen</a>
                </p>

                <p class="note">
                    Tautan di atas memerlukan akun dan hak akses ke dokumen.
                    @if ($includePdf)
                        Salinan PDF dokumen dilampirkan pada email ini.
                    @else
                        Jika Anda tidak memiliki akses, minta pengirim mengirimkan salinan PDF.
                    @endif
                </p>

                <p class="note">
                    Label kerahasiaan: <strong>{{ $meeting->confidentiality?->label() ?? '-' }}</strong>.
                    Jangan sebarkan dokumen ini di luar penerima yang berhak.
                </p>
            </div>
        </div>
        <div class="footer">
            Email ini dikirim otomatis oleh Sistem Notulensi (MoM).
        </div>
    </div>
</body>
</html>
