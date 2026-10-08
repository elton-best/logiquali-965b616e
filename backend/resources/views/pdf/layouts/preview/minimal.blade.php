<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --text: #333;
            --muted: #64748b;
            --line: #e2e8f0;
            --bg: #f8fafc;
            --card: #fff;
            --primary: {{ $styles['colors']['primary'] ?? '#1B5E96' }};
        }

        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            padding: 0;
            font-family: {{ $styles['font'] ?? 'Arial' }}, sans-serif;
            color: var(--text);
            background: var(--bg);
        }

        .preview-page {
            max-width: 860px;
            margin: 16px auto;
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 10px;
            overflow: hidden;
        }

        .preview-header {
            border-bottom: 1px solid var(--line);
            padding: 12px 16px;
            display: grid;
            grid-template-columns: 1fr 220px;
            align-items: start;
            gap: 10px;
        }

        .preview-logo {
            max-height: 40px;
            max-width: 150px;
            object-fit: contain;
        }

        .preview-name {
            font-weight: 700;
            font-size: 14px;
            color: var(--primary);
        }

        .preview-meta {
            font-size: 11px;
            line-height: 1.4;
            text-align: right;
            color: var(--muted);
        }

        .preview-content {
            padding: 18px 16px;
            min-height: 280px;
            line-height: 1.55;
        }

        .preview-footer {
            border-top: 1px solid var(--line);
            padding: 10px 16px;
            font-size: 11px;
            color: var(--muted);
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .footer-left {
            width: 82%;
            vertical-align: top;
            padding-right: 10px;
            overflow-wrap: anywhere;
        }

        .footer-right {
            width: 18%;
            vertical-align: top;
            text-align: right;
            border-left: 1px solid var(--line);
            padding-left: 8px;
        }

        .footer-right svg {
            width: 52px;
            height: 52px;
        }

        .footer-line-1 { font-weight: 700; color: #334155; }
        .footer-line-2, .footer-line-3 { color: var(--muted); }
        .preview-page-note { margin-top: 4px; }
    </style>
</head>
<body>
<div class="preview-page">
    <header class="preview-header">
        <div>
            @php
                $logoUrl = !empty($enterprise->logo_path) ? asset('storage/' . ltrim($enterprise->logo_path, '/')) : null;
            @endphp
            @if(($header['show_logo'] ?? false) && !empty($logoUrl))
                <img src="{{ $logoUrl }}" class="preview-logo" alt="Logo">
            @endif
            <div class="preview-name">{{ $header['enterprise_name'] ?? config('app.name') }}</div>
            <div class="preview-name" style="font-size: 12px; margin-top: 4px;">
                {{ $header['document_meta']['document_label'] ?? 'FICHE' }} {{ $header['document_meta']['document_title'] ?? ($content['title'] ?? '') }}
            </div>
        </div>
        <div class="preview-meta">
            <div>Code : {{ $header['document_meta']['code'] ?? '' }}</div>
            <div>Version : {{ $header['document_meta']['version'] ?? '' }}</div>
            <div>Date : {{ $header['document_meta']['effective_date'] ?? '' }}</div>
        </div>
    </header>

    <main class="preview-content">
        {!! $content['html'] !!}
    </main>

    <footer class="preview-footer">
        <table class="footer-table">
            <tr>
                <td class="footer-left">
                    @if(!empty($footer['identity_line']))
                        <div class="footer-line-1">{{ $footer['identity_line'] }}</div>
                    @endif
                    @if(($footer['show_legal_info'] ?? true) && !empty($footer['legal_line']))
                        <div class="footer-line-2">{{ $footer['legal_line'] }}</div>
                    @endif
                    @if(($footer['show_contact'] ?? true) && !empty($footer['contact_line']))
                        <div class="footer-line-3">{{ $footer['contact_line'] }}</div>
                    @endif
                    @if(!empty($footer['custom_text']))
                        <div class="footer-line-3">{{ $footer['custom_text'] }}</div>
                    @endif
                </td>
                <td class="footer-right">
                    @if(($footer['show_qr_code'] ?? false) && isset($footer['qr_code']))
                        <div>{!! $footer['qr_code'] !!}</div>
                    @endif
                    @if($footer['show_page_numbers'] ?? false)
                        <div class="preview-page-note">Aperçu document</div>
                    @endif
                </td>
            </tr>
        </table>
    </footer>
</div>
</body>
</html>
