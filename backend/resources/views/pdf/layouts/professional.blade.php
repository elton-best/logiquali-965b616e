<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 150px 50px 120px 50px; }
        body { font-family: {{ $styles['font'] }}, sans-serif; color: #333; }
        header { position: fixed; top: -130px; left: 0; right: 0; height: 120px; border-bottom: 3px solid {{ $styles['colors']['primary'] }}; }
        footer { position: fixed; bottom: -100px; left: 0; right: 0; height: 92px; border-top: 2px solid {{ $styles['colors']['secondary'] }}; font-size: 9px; }
        .header-content { padding: 6px 0; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-title { font-weight: 700; font-size: 13px; color: {{ $styles['colors']['primary'] }}; }
        .header-subtitle { font-weight: 700; font-size: 11px; color: #1f2937; margin-top: 4px; }
        .header-meta { font-size: 9px; color: #334155; line-height: 1.45; }
        .logo { max-height: 80px; }
        .slogan { color: {{ $styles['colors']['primary'] }}; font-style: italic; font-size: 12px; }
        .certifications { font-size: 9px; color: #666; margin-top: 5px; }
        .footer-content { padding: 8px 0; font-size: 9px; }
        .footer-table { width: 100%; border-collapse: collapse; }
        .footer-left { width: 82%; vertical-align: top; padding-right: 10px; }
        .footer-right {
            width: 18%;
            vertical-align: top;
            text-align: right;
            border-left: 1px solid #d1d5db;
            padding-left: 8px;
        }
        .footer-line-1 { font-weight: 700; color: #334155; margin-bottom: 2px; }
        .footer-line-2, .footer-line-3 { color: #475569; margin-bottom: 2px; }
        .qr-code svg { width: 58px; height: 58px; }
        .page-slot { margin-top: 4px; text-align: right; }
        .page-number:after { content: counter(page); }
        h1 { color: {{ $styles['colors']['primary'] }}; border-bottom: 2px solid {{ $styles['colors']['secondary'] }}; padding-bottom: 10px; }
        @if(!empty($isDraft))
        .watermark {
            position: fixed;
            top: 38%;
            left: 5%;
            width: 90%;
            text-align: center;
            font-size: 90px;
            font-weight: bold;
            color: rgba(200, 0, 0, 0.10);
            transform: rotate(-35deg);
            z-index: 1000;
            pointer-events: none;
            letter-spacing: 10px;
        }
        @endif
        @if(!empty($isExpired))
        .watermark-expired {
            position: fixed;
            top: 38%;
            left: 5%;
            width: 90%;
            text-align: center;
            font-size: 90px;
            font-weight: bold;
            color: rgba(100, 100, 100, 0.12);
            transform: rotate(-35deg);
            z-index: 1000;
            pointer-events: none;
            letter-spacing: 10px;
        }
        @endif
    </style>
</head>
<body>
    @if(!empty($isDraft))<div class="watermark">BROUILLON</div>@endif
    @if(!empty($isExpired))<div class="watermark-expired">EXPIRÉ</div>@endif
    <header>
        <div class="header-content">
            <table class="header-table">
                <tr>
                    <td style="width: 70%; vertical-align: top;">
                        <div class="header-title">{{ $header['document_meta']['document_label'] ?? 'FICHE' }}</div>
                        <div class="header-subtitle">{{ $header['document_meta']['document_title'] ?? ($content['title'] ?? 'DOCUMENT INTERNE') }}</div>
                        @if($header['show_slogan'] && $header['slogan'])
                            <div class="slogan">{{ $header['slogan'] }}</div>
                        @endif
                    </td>
                    <td style="width: 30%; vertical-align: top; text-align: right;">
                        <div class="header-meta">Code : {{ $header['document_meta']['code'] ?? '' }}</div>
                        <div class="header-meta">Version : {{ $header['document_meta']['version'] ?? '' }}</div>
                        <div class="header-meta">Date : {{ $header['document_meta']['effective_date'] ?? '' }}</div>
                        @if($header['show_logo'] && !empty($header['logo_path']))
                            <div style="margin-top: 4px;">
                                <img src="{{ $header['logo_path'] }}" class="logo" alt="Logo" style="max-height: 40px;" onerror="this.style.display='none'">
                            </div>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    </header>

    <footer>
        <div class="footer-content">
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
                            <div class="qr-code">{!! $footer['qr_code'] !!}</div>
                        @endif
                        @if($footer['show_page_numbers'])
                            <div class="page-slot">Page <span class="page-number"></span></div>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    </footer>

    <main>
        {!! $content['html'] !!}
    </main>
</body>
</html>
