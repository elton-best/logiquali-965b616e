<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Fiche de responsabilite</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; }
        h1 { font-size: 18px; margin: 0 0 8px 0; }
        .meta { margin: 0 0 14px 0; color: #6b7280; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 8px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; font-size: 11px; }
        @if(!empty($isDraft))
        .watermark { position: fixed; top: 38%; left: 5%; width: 90%; text-align: center;
            font-size: 90px; font-weight: bold; color: rgba(200,0,0,0.10);
            transform: rotate(-35deg); z-index: 1000; letter-spacing: 10px; }
        @endif
    </style>
</head>
<body>
@if(!empty($isDraft))<div class="watermark">BROUILLON</div>@endif
<h1>Fiche de responsabilite</h1>
<p class="meta">Genere le {{ $generatedAt->format('d/m/Y H:i') }}</p>

<table>
    <thead>
    <tr>
        <th>Processus</th>
        <th>Niveau</th>
        <th>Role et responsabilite</th>
        <th>Livrables</th>
    </tr>
    </thead>
    <tbody>
    @foreach($rows as $row)
        <tr>
            <td>{{ $row['process'] }}</td>
            <td>{{ $row['level'] }}</td>
            <td>{{ $row['roles'] }}</td>
            <td>{{ $row['deliverables'] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>

