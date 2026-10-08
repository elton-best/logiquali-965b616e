<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Document')</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #333;
            margin: 40px;
        }

        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }

        h2 {
            color: #2c3e50;
            margin-top: 25px;
            margin-bottom: 10px;
            font-size: 14pt;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .section {
            margin-bottom: 20px;
        }

        .label {
            font-weight: bold;
            color: #2c3e50;
        }

        ul {
            margin-left: 20px;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10pt;
            font-weight: bold;
            margin-bottom: 10px;
            background-color: #f1f5f9;
            color: #475569;
        }
    </style>
    @yield('styles')
    @if(!empty($isDraft))
    <style>
        .watermark { position: fixed; top: 38%; left: 5%; width: 90%; text-align: center;
            font-size: 90px; font-weight: bold; color: rgba(200,0,0,0.10);
            transform: rotate(-35deg); z-index: 1000; letter-spacing: 10px; }
    </style>
    @endif
    @if(!empty($isExpired))
    <style>
        .watermark-expired { position: fixed; top: 38%; left: 5%; width: 90%; text-align: center;
            font-size: 90px; font-weight: bold; color: rgba(100,100,100,0.12);
            transform: rotate(-35deg); z-index: 1000; letter-spacing: 10px; }
    </style>
    @endif
</head>
<body>
    @if(!empty($isDraft))<div class="watermark">BROUILLON</div>@endif
    @if(!empty($isExpired))<div class="watermark-expired">EXPIRÉ</div>@endif
    @if(isset($branding))
        @include('pdf.partials.enterprise-branding')
    @endif
    
    <div class="{{ isset($branding) ? 'enterprise-doc-content' : '' }}">
        @yield('content')
    </div>
</body>
</html>
