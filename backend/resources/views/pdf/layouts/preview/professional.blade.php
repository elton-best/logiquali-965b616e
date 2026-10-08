<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --primary: {{ $styles['colors']['primary'] ?? '#1B5E96' }};
            --secondary: {{ $styles['colors']['secondary'] ?? '#FF6B00' }};
            --text: #333;
            --muted: #64748b;
            --card: #fff;
            --line: #e2e8f0;
            --bg: #f8fafc;
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
            max-width: 920px;
            margin: 16px auto;
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            overflow: hidden;
        }

        .preview-header {
            border-bottom: 3px solid var(--primary);
            padding: 16px 20px;
            display: grid;
            grid-template-columns: 1fr 260px;
            gap: 16px;
        }

        .preview-brand {
            min-width: 0;
        }

        .preview-logo {
            max-height: 64px;
            max-width: 180px;
            object-fit: contain;
            display: block;
            margin-bottom: 8px;
        }

        .preview-name {
            font-weight: 700;
            font-size: 16px;
            color: var(--primary);
            line-height: 1.2;
        }

        .preview-slogan {
            color: var(--primary);
            font-style: italic;
            font-size: 12px;
            margin-top: 6px;
        }

        .preview-meta {
            font-size: 11px;
            color: #334155;
            line-height: 1.45;
            text-align: right;
        }

        .preview-content {
            padding: 24px 20px 20px;
            min-height: 320px;
            line-height: 1.6;
        }

        .preview-content h1 {
            color: var(--primary);
            border-bottom: 2px solid var(--secondary);
            padding-bottom: 8px;
            margin: 0 0 16px;
            font-size: 26px;
        }

        .preview-content h2 {
            color: #1e293b;
            margin-top: 20px;
            margin-bottom: 8px;
            font-size: 20px;
        }

        .preview-footer {
            border-top: 2px solid var(--secondary);
            padding: 12px 20px 16px;
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
            padding-right: 12px;
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
            width: 58px;
            height: 58px;
        }

        .footer-line-1 { font-weight: 700; color: #334155; margin-bottom: 2px; }
        .footer-line-2, .footer-line-3 { color: var(--muted); margin-bottom: 2px; }
        .preview-page-note { margin-top: 4px; }
    </style>
</head>
<body>
<div class="preview-page">
    <header class="preview-header">
        <div class="preview-brand">
            @php
                $logoUrl = !empty($enterprise->logo_path) ? asset('storage/' . ltrim($enterprise->logo_path, '/')) : null;
            @endphp
            @if(($header['show_logo'] ?? false) && !empty($logoUrl))
                <img src="{{ $logoUrl }}" class="preview-logo" alt="Logo">
            @endif
            <div class="preview-name">{{ $header['enterprise_name'] ?? config('app.name') }}</div>
            <div class="preview-name" style="font-size: 14px; margin-top: 6px; color: #1f2937;">
                {{ $header['document_meta']['document_label'] ?? 'FICHE' }} {{ $header['document_meta']['document_title'] ?? ($content['title'] ?? '') }}
            </div>
            @if(($header['show_slogan'] ?? false) && !empty($header['slogan']))
                <div class="preview-slogan">{{ $header['slogan'] }}</div>
            @endif
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
