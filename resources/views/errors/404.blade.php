<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Halaman tidak ditemukan - {{ config('app.name', 'MoM') }}</title>
        <style>
            :root {
                color-scheme: light dark;
                --background: #f5f7fa;
                --surface: #ffffff;
                --foreground: #172033;
                --muted: #536079;
                --border: #e2e7f0;
                --primary: #2456c7;
            }

            @media (prefers-color-scheme: dark) {
                :root {
                    --background: #0b1017;
                    --surface: #111823;
                    --foreground: #e8edf5;
                    --muted: #9aa8bc;
                    --border: #223044;
                    --primary: #6f9bf0;
                }
            }

            * {
                box-sizing: border-box;
            }

            body {
                display: grid;
                min-height: 100svh;
                place-items: center;
                margin: 0;
                padding: 24px;
                background: var(--background);
                color: var(--foreground);
                font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            }

            .card {
                width: min(100%, 440px);
                padding: 32px;
                border: 1px solid var(--border);
                border-radius: 16px;
                background: var(--surface);
                box-shadow: 0 12px 32px -8px rgb(23 32 51 / 0.12);
                text-align: center;
            }

            .mark {
                display: inline-flex;
                width: 40px;
                height: 40px;
                align-items: center;
                justify-content: center;
                border-radius: 12px;
                background: var(--primary);
                color: #ffffff;
                font-size: 13px;
                font-weight: 600;
            }

            .code {
                margin: 24px 0 8px;
                color: var(--primary);
                font-size: 13px;
                font-weight: 600;
                letter-spacing: 0.08em;
            }

            h1 {
                margin: 0;
                font-size: 26px;
                line-height: 1.2;
                letter-spacing: -0.02em;
            }

            p {
                margin: 12px 0 0;
                color: var(--muted);
                font-size: 14px;
                line-height: 1.7;
            }

            .actions {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 8px;
                margin-top: 24px;
            }

            a {
                display: inline-flex;
                min-height: 40px;
                align-items: center;
                justify-content: center;
                border-radius: 12px;
                padding: 0 16px;
                font-size: 14px;
                font-weight: 500;
                text-decoration: none;
            }

            .primary {
                background: var(--primary);
                color: #ffffff;
            }

            .secondary {
                border: 1px solid var(--border);
                color: var(--foreground);
            }
        </style>
    </head>
    <body>
        <main class="card">
            <span class="mark" aria-hidden="true">MoM</span>
            <div class="code">404</div>
            <h1>Halaman tidak ditemukan</h1>
            <p>Alamat yang Anda buka tidak tersedia atau sudah dipindahkan. Periksa kembali tautan yang digunakan.</p>
            <div class="actions">
                <a class="primary" href="{{ auth()->check() ? route('dashboard') : route('login') }}">Kembali ke dashboard</a>
                <a class="secondary" href="{{ url()->previous() }}">Halaman sebelumnya</a>
            </div>
        </main>
    </body>
</html>
