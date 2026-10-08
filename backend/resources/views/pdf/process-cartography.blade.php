<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Cartographie des Processus - {{ $enterprise->name ?? 'SMQ' }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm 12mm 12mm 12mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
            color: #1e293b;
            margin: 0;
            padding: 0;
            line-height: 1.35;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
        }

        .header-logo {
            width: 180px;
            vertical-align: middle;
        }

        .header-title-box {
            text-align: center;
            vertical-align: middle;
        }

        .header-title-box h1 {
            margin: 0;
            font-size: 14pt;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .header-title-box .sub-title {
            margin: 3px 0 0 0;
            font-size: 9pt;
            color: #475569;
            font-weight: 600;
        }

        .header-meta {
            width: 190px;
            font-size: 8pt;
            color: #334155;
            text-align: right;
            vertical-align: middle;
        }

        .header-meta table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-meta td {
            padding: 1px 3px;
        }

        /* Cartography visual schema */
        .carto-schema-box {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: #ffffff;
            margin-bottom: 14px;
            page-break-inside: avoid;
        }

        .carto-layout-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px;
        }

        .side-pillar {
            width: 13%;
            background: #f8fafc;
            border: 1.5px dashed #94a3b8;
            border-radius: 6px;
            text-align: center;
            vertical-align: middle;
            padding: 10px 4px;
        }

        .side-pillar-title {
            font-size: 8pt;
            font-weight: 800;
            text-transform: uppercase;
            color: #1e293b;
            line-height: 1.25;
        }

        .side-pillar-desc {
            font-size: 7pt;
            color: #64748b;
            margin-top: 4px;
        }

        .lanes-container {
            width: 74%;
            vertical-align: middle;
        }

        .lane {
            border-radius: 6px;
            padding: 6px 8px;
            margin-bottom: 6px;
        }

        .lane-management {
            background: #faf5ff;
            border: 1.5px solid #d8b4fe;
        }

        .lane-realization {
            background: #f0fdf4;
            border: 1.5px solid #86efac;
        }

        .lane-support {
            background: #eef2ff;
            border: 1.5px solid #a5b4fc;
        }

        .lane-header {
            font-size: 8pt;
            font-weight: 800;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .lane-management .lane-header { color: #7e22ce; }
        .lane-realization .lane-header { color: #15803d; }
        .lane-support .lane-header { color: #4338ca; }

        .process-cards-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 4px;
        }

        .process-card {
            border-radius: 4px;
            padding: 4px 6px;
            text-align: center;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            font-size: 7.5pt;
        }

        .lane-management .process-card { border-top: 2.5px solid #9333ea; }
        .lane-realization .process-card { border-top: 2.5px solid #16a34a; }
        .lane-support .process-card { border-top: 2.5px solid #4f46e5; }

        .process-code {
            font-weight: 800;
            color: #0f172a;
            font-size: 8pt;
        }

        .process-title {
            color: #334155;
            font-weight: 600;
            margin-top: 1px;
        }

        .process-pilot {
            color: #64748b;
            font-size: 6.8pt;
            margin-top: 2px;
        }

        /* Detail table (Word model compliance) */
        .recap-title {
            font-size: 10.5pt;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            margin: 10px 0 6px 0;
            border-left: 3px solid #2563eb;
            padding-left: 6px;
        }

        .recap-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5pt;
            margin-bottom: 12px;
        }

        .recap-table th {
            background: #0f172a;
            color: #ffffff;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 7pt;
            padding: 5px 6px;
            border: 1px solid #334155;
            text-align: left;
        }

        .recap-table td {
            padding: 4px 6px;
            border: 1px solid #cbd5e1;
            vertical-align: top;
        }

        .recap-table tr:nth-child(even) {
            background: #f8fafc;
        }

        .tag-category {
            display: inline-block;
            font-size: 6.5pt;
            font-weight: 700;
            padding: 1px 4px;
            border-radius: 3px;
            text-transform: uppercase;
        }

        .tag-mgmt { background: #f3e8ff; color: #7e22ce; border: 1px solid #d8b4fe; }
        .tag-real { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
        .tag-sup { background: #e0e7ff; color: #4338ca; border: 1px solid #a5b4fc; }

        /* Signatures block */
        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            page-break-inside: avoid;
        }

        .signature-box {
            width: 48%;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 6px 8px;
            background: #f8fafc;
        }

        .signature-title {
            font-weight: 800;
            font-size: 8pt;
            color: #0f172a;
            text-transform: uppercase;
            margin-bottom: 25px;
        }

        .signature-footer {
            font-size: 7pt;
            color: #64748b;
            border-top: 1px dashed #cbd5e1;
            padding-top: 3px;
        }
    </style>
</head>
<body>

    <!-- Official Document Header -->
    <table class="header-table">
        <tr>
            <td class="header-logo">
                <div style="font-weight: 900; font-size: 13pt; color: #0f172a;">
                    {{ $enterprise->name ?? 'BEST EXPERTS-GROUP' }}
                </div>
                <div style="font-size: 7.5pt; color: #64748b;">
                    Système de Management QHSE & Qualité
                </div>
            </td>
            <td class="header-title-box">
                <h1>CARTOGRAPHIE ET INTERACTIONS DES PROCESSUS</h1>
                <div class="sub-title">Conformité ISO 9001:2015 — Clause §4.4 (Système de management de la qualité et ses processus)</div>
            </td>
            <td class="header-meta">
                <table>
                    <tr>
                        <td style="font-weight: bold;">Réf Doc :</td>
                        <td>{{ $document->code ?? $document_code ?? 'SMQ-DOC-CART-01' }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Version :</td>
                        <td>{{ $document->version ?? $document_version ?? '1.0' }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Date :</td>
                        <td>{{ $generated_at }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Site :</td>
                        <td>{{ $data['site_name'] ?? 'Principal' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Cartography Visual Schema -->
    <div class="carto-schema-box">
        <table class="carto-layout-table">
            <tr>
                <!-- Entrées / Client amont -->
                <td class="side-pillar">
                    <div class="side-pillar-title" style="color: #2563eb;">
                        ENTRÉES DU SYSTÈME
                    </div>
                    <div style="font-size: 14pt; color: #2563eb; margin: 4px 0;">&#10148;</div>
                    <div class="side-pillar-title">
                        EXIGENCES & ATTENTES
                    </div>
                    <div class="side-pillar-desc">
                        Clients & Bénéficiaires<br>
                        Parties Intéressées Pertinentes<br>
                        Exigences Réglementaires & ISO
                    </div>
                </td>

                <!-- Blocs Processus (Management / Réalisation / Support) -->
                <td class="lanes-container">
                    <!-- Lane Management -->
                    <div class="lane lane-management">
                        <div class="lane-header">
                            &#9654; Processus de Management / Pilotage ({{ count($data['categories']['management']) }})
                        </div>
                        <table class="process-cards-table">
                            <tr>
                                @forelse($data['categories']['management'] as $proc)
                                    <td class="process-card">
                                        <div class="process-code">{{ $proc['code'] }}</div>
                                        <div class="process-title">{{ $proc['title'] }}</div>
                                        <div class="process-pilot">Pilote : {{ $proc['pilot_name'] }}</div>
                                    </td>
                                @empty
                                    <td class="process-card" style="color: #94a3b8;">Aucun processus de management configuré</td>
                                @endforelse
                            </tr>
                        </table>
                    </div>

                    <!-- Lane Réalisation (Cœur de métier) -->
                    <div class="lane lane-realization">
                        <div class="lane-header">
                            &#9654; Processus de Réalisation / Opérationnels (Chaîne de Valeur) ({{ count($data['categories']['realization']) }})
                        </div>
                        <table class="process-cards-table">
                            <tr>
                                @forelse($data['categories']['realization'] as $proc)
                                    <td class="process-card">
                                        <div class="process-code">{{ $proc['code'] }}</div>
                                        <div class="process-title">{{ $proc['title'] }}</div>
                                        <div class="process-pilot">Pilote : {{ $proc['pilot_name'] }}</div>
                                    </td>
                                @empty
                                    <td class="process-card" style="color: #94a3b8;">Aucun processus de réalisation configuré</td>
                                @endforelse
                            </tr>
                        </table>
                    </div>

                    <!-- Lane Support -->
                    <div class="lane lane-support" style="margin-bottom: 0;">
                        <div class="lane-header">
                            &#9654; Processus de Support / Soutien aux Opérations ({{ count($data['categories']['support']) }})
                        </div>
                        <table class="process-cards-table">
                            <tr>
                                @forelse($data['categories']['support'] as $proc)
                                    <td class="process-card">
                                        <div class="process-code">{{ $proc['code'] }}</div>
                                        <div class="process-title">{{ $proc['title'] }}</div>
                                        <div class="process-pilot">Pilote : {{ $proc['pilot_name'] }}</div>
                                    </td>
                                @empty
                                    <td class="process-card" style="color: #94a3b8;">Aucun processus de support configuré</td>
                                @endforelse
                            </tr>
                        </table>
                    </div>
                </td>

                <!-- Sorties / Client aval -->
                <td class="side-pillar">
                    <div class="side-pillar-title" style="color: #16a34a;">
                        SORTIES DU SYSTÈME
                    </div>
                    <div style="font-size: 14pt; color: #16a34a; margin: 4px 0;">&#10148;</div>
                    <div class="side-pillar-title">
                        SATISFACTION CLIENT
                    </div>
                    <div class="side-pillar-desc">
                        Produits & Services Conformes<br>
                        Valeur Ajoutée Livrée<br>
                        Amélioration Continue du SMQ
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Official Summary Table (Model compliant: Proposition de nom, activités principales, observation, responsable) -->
    <!-- Official Summary Table (Strictly compliant with User Model: 4 columns) -->
    <div class="recap-title">
        Cartographie des Processus — Synthèse & Responsabilités
    </div>

    <table class="recap-table">
        <thead>
            <tr>
                <th style="width: 25%;">PROPOSITION DE NOM PROCESSUS</th>
                <th style="width: 42%;">ACTIVITES PRINCIPALES</th>
                <th style="width: 18%;">OBSERVATION</th>
                <th style="width: 15%;">RESPONSABLE PAR DEPARTEMENT</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['all_processes'] as $process)
                <tr>
                    <td>
                        <strong style="color: #0f172a; text-transform: uppercase;">{{ $process['title'] }}</strong>
                        @if(!empty($process['code']))
                            <div style="color: #64748b; font-size: 7pt; margin-top: 2px;">{{ $process['code'] }}</div>
                        @endif
                    </td>
                    <td>
                        {{ $process['activities_summary'] }}
                    </td>
                    <td>
                        {{ !empty($process['observation']) ? $process['observation'] : '' }}
                    </td>
                    <td>
                        <strong>{{ $process['responsible_display'] ?? $process['pilot_name'] }}</strong>
                        @if(!empty($process['department']))
                            <div style="color: #64748b; font-size: 7pt; margin-top: 2px;">{{ $process['department'] }}</div>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Validation & Signatures Block -->
    <table class="signatures-table">
        <tr>
            <td class="signature-box">
                <div class="signature-title">Établi par le Responsable Qualité / Pilote SMQ :</div>
                <div class="signature-footer">Nom, Date & Signature</div>
            </td>
            <td style="width: 4%;"></td>
            <td class="signature-box">
                <div class="signature-title">Approuvé par la Direction Générale :</div>
                <div class="signature-footer">Nom, Date, Cachet & Signature</div>
            </td>
        </tr>
    </table>

</body>
</html>

