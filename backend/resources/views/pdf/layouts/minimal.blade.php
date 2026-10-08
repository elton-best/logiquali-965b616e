<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 110px 40px 90px 40px; }
        body { font-family: {{ $styles['font'] }}, sans-serif; color: #333; }
        header { position: fixed; top: -90px; left: 0; right: 0; height: 70px; border-bottom: 1px solid #ddd; }
        footer { position: fixed; bottom: -70px; left: 0; right: 0; height: 60px; border-top: 1px solid #ddd; font-size: 8px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-meta { font-size: 8px; line-height: 1.4; text-align: right; }
        .footer-table { width: 100%; border-collapse: collapse; }
        .footer-left { width: 82%; vertical-align: top; padding-right: 8px; }
        .footer-right { width: 18%; vertical-align: top; text-align: right; border-left: 1px solid #d1d5db; padding-left: 8px; }
        .footer-line-1 { font-weight: 700; }
        .qr-code svg { width: 50px; height: 50px; }
        .page-number:after { content: counter(page); }
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
        <table class="header-table">
            <tr>
                <td style="width: 72%; vertical-align: top;">
                    <strong>{{ $header['document_meta']['document_label'] ?? 'FICHE' }}</strong><br>
                    <span>{{ $header['document_meta']['document_title'] ?? ($content['title'] ?? 'DOCUMENT INTERNE') }}</span>
                </td>
                <td style="width: 28%; vertical-align: top;" class="header-meta">
                    <div>Code : {{ $header['document_meta']['code'] ?? '' }}</div>
                    <div>Version : {{ $header['document_meta']['version'] ?? '' }}</div>
                    <div>Date : {{ $header['document_meta']['effective_date'] ?? '' }}</div>
                </td>
            </tr>
        </table>
    </header>

    <footer>
        <table class="footer-table">
            <tr>
                <td class="footer-left">
                    @if(!empty($footer['identity_line']))
                        <div class="footer-line-1">{{ $footer['identity_line'] }}</div>
                    @endif
                    @if(($footer['show_legal_info'] ?? true) && !empty($footer['legal_line']))
                        <div>{{ $footer['legal_line'] }}</div>
                    @endif
                    @if(($footer['show_contact'] ?? true) && !empty($footer['contact_line']))
                        <div>{{ $footer['contact_line'] }}</div>
                    @endif
                    @if(!empty($footer['custom_text']))
                        <div>{{ $footer['custom_text'] }}</div>
                    @endif
                </td>
                <td class="footer-right">
                    @if(($footer['show_qr_code'] ?? false) && isset($footer['qr_code']))
                        <div class="qr-code">{!! $footer['qr_code'] !!}</div>
                    @endif
                    @if($footer['show_page_numbers'])
                        <div>Page <span class="page-number"></span></div>
                    @endif
                </td>
            </tr>
        </table>
    </footer>

    <main>
        {!! $content['html'] !!}
    </main>
</body>
</html>
