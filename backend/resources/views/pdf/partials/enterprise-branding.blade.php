<style>
    @page { margin: 120px 40px 95px 40px; }
    .enterprise-doc-header {
        position: fixed;
        top: -105px;
        left: 0;
        right: 0;
        height: 90px;
        border-bottom: 2px solid {{ $branding['styles']['colors']['primary'] ?? '#1B5E96' }};
        font-family: {{ $branding['styles']['font'] ?? 'Arial' }}, sans-serif;
    }
    .enterprise-doc-footer {
        position: fixed;
        bottom: -80px;
        left: 0;
        right: 0;
        height: 70px;
        border-top: 1px solid {{ $branding['styles']['colors']['secondary'] ?? '#FF6B00' }};
        font-size: 9px;
        color: #666;
        font-family: {{ $branding['styles']['font'] ?? 'Arial' }}, sans-serif;
    }
    .enterprise-doc-content {
        font-family: {{ $branding['styles']['font'] ?? 'Arial' }}, sans-serif;
    }
    .enterprise-page-number:after { content: counter(page); }
</style>

<div class="enterprise-doc-header">
    <table style="width:100%; border-collapse:collapse;">
        <tr>
            <td style="width:30%; vertical-align:top;">
                @if(($branding['header']['show_logo'] ?? false) && !empty($branding['header']['logo_path']))
                    <img src="{{ $branding['header']['logo_path'] }}" alt="Logo" style="max-height:55px;">
                @else
                    <div style="font-weight:700; font-size:12px; color:#2E3B55;">
                        {{ $branding['header']['enterprise_name'] ?? '' }}
                    </div>
                @endif
            </td>
            <td style="width:70%; text-align:right; vertical-align:top;">
                <div style="font-weight:700; font-size:13px;">{{ $branding['header']['enterprise_name'] ?? '' }}</div>
                @if(($branding['header']['show_slogan'] ?? false) && !empty($branding['header']['slogan']))
                    <div style="font-style:italic; font-size:10px; color:#666;">{{ $branding['header']['slogan'] }}</div>
                @endif
                @if(($branding['header']['show_certifications'] ?? false) && !empty($branding['header']['certifications']))
                    <div style="font-size:9px; color:#666; margin-top:4px;">
                        @foreach($branding['header']['certifications'] as $certification)
                            <span>{{ $certification['name'] }}{{ !empty($certification['number']) ? ' - '.$certification['number'] : '' }}</span>@if(!$loop->last) | @endif
                        @endforeach
                    </div>
                @endif
            </td>
        </tr>
    </table>
</div>

<div class="enterprise-doc-footer">
    <table style="width:100%; border-collapse:collapse;">
        <tr>
            <td style="width:75%; vertical-align:top;">
                @if(($branding['footer']['show_contact'] ?? false))
                    <div>
                        {{ trim(($branding['footer']['contact']['address'] ?? '').' '.($branding['footer']['contact']['postal_code'] ?? '').' '.($branding['footer']['contact']['city'] ?? '')) }}
                        @if(!empty($branding['footer']['contact']['phone_primary'])) | {{ $branding['footer']['contact']['phone_primary'] }} @endif
                        @if(!empty($branding['footer']['contact']['email'])) | {{ $branding['footer']['contact']['email'] }} @endif
                    </div>
                @endif
                @if(($branding['footer']['show_legal_info'] ?? false) && !empty($branding['footer']['legal']))
                    <div>{{ $branding['footer']['legal'] }}</div>
                @endif
                @if(!empty($branding['footer']['custom_text']))
                    <div>{{ $branding['footer']['custom_text'] }}</div>
                @endif
            </td>
            <td style="width:25%; text-align:right; vertical-align:bottom;">
                <div style="margin-bottom: 5px;">Généré le {{ date('d/m/Y à H:i') }}</div>
                @if(($branding['footer']['show_page_numbers'] ?? true))
                    Page <span class="enterprise-page-number"></span>
                @endif
            </td>
        </tr>
    </table>
</div>
