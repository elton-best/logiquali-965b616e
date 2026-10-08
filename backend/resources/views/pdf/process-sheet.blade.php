<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fiche Processus - {{ $process->code ?? $process->id }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm 15mm 15mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
            color: #1e293b;
            margin: 0;
            padding: 0;
            line-height: 1.4;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            border-bottom: 2.5px solid #0f172a;
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
            font-size: 15pt;
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
            width: 180px;
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

        .section-title {
            font-size: 10.5pt;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            margin: 14px 0 6px 0;
            border-left: 3.5px solid #2563eb;
            padding-left: 6px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
            margin-bottom: 10px;
        }

        .info-table th, .info-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 8px;
            vertical-align: top;
        }

        .info-table th {
            background: #f1f5f9;
            font-weight: 700;
            color: #334155;
            text-align: left;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
            margin-bottom: 10px;
        }

        .data-table th {
            background: #0f172a;
            color: #ffffff;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 7.5pt;
            padding: 5px 6px;
            border: 1px solid #334155;
            text-align: left;
        }

        .data-table td {
            padding: 5px 6px;
            border: 1px solid #cbd5e1;
            vertical-align: top;
        }

        .data-table tr:nth-child(even) {
            background: #f8fafc;
        }

        .sequence-card {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            margin-bottom: 10px;
            page-break-inside: avoid;
        }

        .sequence-header {
            background: #e2e8f0;
            font-weight: 800;
            font-size: 8.5pt;
            padding: 5px 8px;
            color: #0f172a;
            border-bottom: 1px solid #cbd5e1;
        }

        .badge-cat {
            display: inline-block;
            font-size: 7pt;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
            text-transform: uppercase;
        }
        .badge-mgmt { background: #f3e8ff; color: #7e22ce; border: 1px solid #d8b4fe; }
        .badge-real { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
        .badge-sup { background: #e0e7ff; color: #4338ca; border: 1px solid #a5b4fc; }

        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
            page-break-inside: avoid;
        }

        .signature-box {
            width: 31%;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 6px 8px;
            background: #f8fafc;
        }

        .signature-title {
            font-weight: 800;
            font-size: 7.5pt;
            color: #0f172a;
            text-transform: uppercase;
            margin-bottom: 22px;
        }

        .signature-footer {
            font-size: 6.8pt;
            color: #64748b;
            border-top: 1px dashed #cbd5e1;
            padding-top: 3px;
        }
    </style>
</head>
<body>

    <!-- Header -->
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
                <h1>FICHE DE PROCESSUS</h1>
                <div class="sub-title">Norme ISO 9001:2015 — Clause §4.4</div>
            </td>
            <td class="header-meta">
                <table>
                    <tr>
                        <td style="font-weight: bold;">Code :</td>
                        <td>{{ $document->code ?? $process->code ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Version :</td>
                        <td>v{{ $document->version ?? $process->version ?? '1.0' }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Date :</td>
                        <td>{{ $effective_date ?? now()->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Site :</td>
                        <td>{{ $process->site?->name ?? 'Site Principal' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- 1. Informations Générales -->
    <div class="section-title">1. Informations Générales</div>
    <table class="info-table">
        <tr>
            <th style="width: 25%;">Intitulé du Processus</th>
            <td style="width: 75%; font-weight: bold; font-size: 9.5pt;">{{ $process->title ?? $process->name }}</td>
        </tr>
        <tr>
            <th>Code Processus</th>
            <td><code>{{ $process->code }}</code></td>
        </tr>
        <tr>
            <th>Catégorie ISO 9001</th>
            <td>
                @php
                    $cat = strtolower((string) ($process->category ?? $process->type));
                @endphp
                @if(in_array($cat, ['management', 'pilotage', 'mesure_amelioration']))
                    <span class="badge-cat badge-mgmt">Processus de Management / Pilotage</span>
                @elseif(in_array($cat, ['support', 'ressources']))
                    <span class="badge-cat badge-sup">Processus de Support / Ressources</span>
                @else
                    <span class="badge-cat badge-real">Processus de Réalisation / Cœur de métier</span>
                @endif
            </td>
        </tr>
        <tr>
            <th>Pilote du Processus</th>
            <td>
                <strong>{{ $process->pilot?->name ?? 'Non assigné' }}</strong>
                @if(!empty($process->pilot?->job_title))
                    <span style="color: #64748b;">({{ $process->pilot->job_title }})</span>
                @endif
            </td>
        </tr>
        @if(!empty($copilotNames))
        <tr>
            <th>Co-pilote(s)</th>
            <td>{{ implode(', ', $copilotNames) }}</td>
        </tr>
        @endif
        <tr>
            <th>Finalité du Processus</th>
            <td>{{ $process->purpose ?? $process->finalite ?? 'Non définie' }}</td>
        </tr>
    </table>

    <!-- 2. Séquences d'Activités & Interactions (SIPOC) -->
    <div class="section-title">2. Séquences d'Activités & Interactions (SIPOC)</div>
    @if($process->sequences && count($process->sequences) > 0)
        @foreach($process->sequences as $index => $seq)
            @php
                $actDesc = $seq->activity_description ?? $seq->name ?? $seq->description ?? ('Activité ' . ($index + 1));
                $suppliers = $seq->supplier_processes ?? $seq->suppliers ?? null;
                $inDesc = $seq->input_description ?? $seq->inputs ?? null;
                $outDesc = $seq->output_description ?? $seq->outputs ?? null;
                $clients = $seq->client_processes ?? $seq->clients ?? null;
                $subActivities = $seq->sub_activities ?? $seq->subActivities ?? null;
            @endphp
            <div class="sequence-card">
                <div class="sequence-header">
                    SÉQUENCE {{ $index + 1 }} : {{ $actDesc }}
                </div>
                <table class="info-table" style="margin-bottom: 0; border: none;">
                    <tr>
                        <th style="width: 20%; background: #eff6ff; color: #1e40af;">Fournisseurs (Amont)</th>
                        <td style="width: 80%;">
                            {{ is_array($suppliers) && count($suppliers) > 0 ? implode(', ', $suppliers) : 'Clients / Direction / Parties intéressées' }}
                        </td>
                    </tr>
                    <tr>
                        <th style="background: #eff6ff; color: #1e40af;">Données d'Entrée</th>
                        <td>{{ $inDesc ?: 'Exigences et intrants du processus' }}</td>
                    </tr>
                    <tr>
                        <th style="background: #f0fdf4; color: #166534;">Activité & Sous-activités</th>
                        <td>
                            <strong>{{ $actDesc }}</strong>
                            @if(is_array($subActivities) && count($subActivities) > 0)
                                <ul style="margin: 3px 0 3px 18px; padding: 0;">
                                    @foreach($subActivities as $sub)
                                        <li>{{ $sub }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th style="background: #f0fdf4; color: #166534;">Données de Sortie</th>
                        <td>{{ $outDesc ?: 'Livrables conformes et résultats' }}</td>
                    </tr>
                    <tr>
                        <th style="background: #f0fdf4; color: #166534;">Clients (Aval)</th>
                        <td>
                            {{ is_array($clients) && count($clients) > 0 ? implode(', ', $clients) : 'Clients / Processus partenaires' }}
                        </td>
                    </tr>
                </table>
            </div>
        @endforeach
    @else
        <div style="color: #64748b; font-style: italic; margin-bottom: 10px;">
            Aucune séquence détaillée n'a été formalisée pour ce processus.
        </div>
    @endif

    <!-- 3. Objectifs et Indicateurs de Performance -->
    <div class="section-title">3. Objectifs & Indicateurs Associés</div>
    @php
        $objs = $process->processObjectives ?? collect();
    @endphp
    @if(count($objs) > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 45%;">Objectif Qualité</th>
                    <th style="width: 35%;">Indicateur de Performance</th>
                    <th style="width: 20%;">Cible</th>
                </tr>
            </thead>
            <tbody>
                @foreach($objs as $obj)
                    <tr>
                        <td><strong>{{ $obj->title ?? 'Objectif' }}</strong></td>
                        <td>{{ $obj->indicator?->name ?? $obj->indicator_name ?? '—' }}</td>
                        <td>{{ !empty($obj->target_value) ? $obj->target_value . ' ' . ($obj->indicator?->unit ?? '') : 'Conforme' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div style="color: #64748b; font-style: italic; margin-bottom: 10px;">
            Aucun objectif formalisé pour ce processus.
        </div>
    @endif

    <!-- 4. Ressources Allouées -->
    <div class="section-title">4. Ressources Nécessaires</div>
    @php
        $res = $process->ressources ?? [];
    @endphp
    <table class="info-table">
        <tr>
            <th style="width: 25%;">Ressources Humaines</th>
            <td>{{ !empty($res['human']) ? (is_array($res['human']) ? implode(', ', $res['human']) : $res['human']) : 'Personnel qualifié et habilité' }}</td>
        </tr>
        <tr>
            <th>Ressources Matérielles</th>
            <td>{{ !empty($res['material']) ? (is_array($res['material']) ? implode(', ', $res['material']) : $res['material']) : 'Équipements et infrastructure' }}</td>
        </tr>
        <tr>
            <th>Ressources Technologiques & SI</th>
            <td>{{ !empty($res['technological']) ? (is_array($res['technological']) ? implode(', ', $res['technological']) : $res['technological']) : 'Systèmes d’information et logiciels métier' }}</td>
        </tr>
        <tr>
            <th>Ressources Documentaires</th>
            <td>{{ !empty($res['documentary']) ? (is_array($res['documentary']) ? implode(', ', $res['documentary']) : $res['documentary']) : 'Procédures, instructions et formulaires applicables' }}</td>
        </tr>
    </table>

    <!-- 5. Risques et Opportunités -->
    <div class="section-title">5. Risques et Opportunités Clés</div>
    @php
        $risks = $process->risksOpportunities ?? collect();
    @endphp
    @if(count($risks) > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 15%;">Type</th>
                    <th style="width: 65%;">Description</th>
                    <th style="width: 20%;">Niveau</th>
                </tr>
            </thead>
            <tbody>
                @foreach($risks as $item)
                    <tr>
                        <td>
                            @if(($item->type ?? '') === 'opportunite' || ($item->type ?? '') === 'opportunity')
                                <strong style="color: #16a34a;">Opportunité</strong>
                            @else
                                <strong style="color: #dc2626;">Risque</strong>
                            @endif
                        </td>
                        <td>{{ $item->description ?? $item->title ?? '—' }}</td>
                        <td>{{ ucfirst($item->niveau ?? $item->criticality ?? 'Moyen') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div style="color: #64748b; font-style: italic; margin-bottom: 10px;">
            Risques et opportunités gérés via la matrice des risques globale.
        </div>
    @endif

    <!-- 6. Signatures et Approbation -->
    <table class="signatures-table">
        <tr>
            <td class="signature-box">
                <div class="signature-title">Rédigé par le Pilote :</div>
                <div style="font-weight: bold; font-size: 8pt;">{{ $process->pilot?->name ?? 'Pilote' }}</div>
                <div class="signature-footer">Date & Signature</div>
            </td>
            <td style="width: 3%;"></td>
            <td class="signature-box">
                <div class="signature-title">Vérifié par le RQ / SMQ :</div>
                <div style="font-weight: bold; font-size: 8pt;">Responsable Qualité</div>
                <div class="signature-footer">Date & Signature</div>
            </td>
            <td style="width: 3%;"></td>
            <td class="signature-box">
                <div class="signature-title">Approuvé par la Direction :</div>
                <div style="font-weight: bold; font-size: 8pt;">Direction Générale</div>
                <div class="signature-footer">Date, Cachet & Signature</div>
            </td>
        </tr>
    </table>

</body>
</html>
