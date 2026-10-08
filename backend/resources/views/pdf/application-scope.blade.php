<style>
    .doc-header {
        margin-bottom: 25px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e2e8f0;
    }
    .doc-header h1 {
        font-size: 18pt;
        margin: 0 0 8px 0;
        color: #1e293b;
    }
    .doc-header .badge {
        background-color: #f1f5f9;
        color: #475569;
        font-size: 10pt;
        padding: 4px 10px;
        border-radius: 6px;
        display: inline-block;
        font-weight: bold;
    }
    .section {
        margin-bottom: 22px;
    }
    .section h2 {
        font-size: 12pt;
        font-weight: bold;
        color: #1e3a8a;
        margin-top: 0;
        margin-bottom: 8px;
        text-transform: uppercase;
    }
    .section p {
        margin: 0;
        font-size: 10.5pt;
        text-align: justify;
        color: #334155;
    }
    ul {
        margin: 0 0 0 20px;
        padding: 0;
    }
    li {
        font-size: 10.5pt;
        margin-bottom: 4px;
        color: #334155;
    }
    .process-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 8px;
    }
    .process-table th {
        background-color: #f8fafc;
        color: #475569;
        font-weight: bold;
        font-size: 10pt;
        border: 1px solid #e2e8f0;
        padding: 8px 12px;
        text-align: left;
    }
    .process-table td {
        font-size: 10pt;
        border: 1px solid #e2e8f0;
        padding: 8px 12px;
        color: #334155;
    }
    .exclusion-block {
        background-color: #f8fafc;
        border-left: 3px solid #cbd5e1;
        padding: 10px 14px;
        margin-top: 8px;
    }
    .exclusion-block p {
        margin-bottom: 6px;
    }
    .exclusion-block p:last-child {
        margin-bottom: 0;
    }
</style>

<div class="doc-header">
    <h1>DOMAINE D'APPLICATION DU SMQ</h1>
    <div class="badge">Référence : {{ $document->code ?? '—' }} | Version : {{ $document->version ?? '1.0' }}</div>
</div>

@if(!empty($scope->document_objective))
    <div class="section">
        <h2>1. Objet du document</h2>
        <p>{{ $scope->document_objective }}</p>
    </div>
@endif

@if(!empty($scope->scope_definition))
    <div class="section">
        <h2>2. Définition du domaine d'application</h2>
        <p>{{ $scope->scope_definition }}</p>
    </div>
@endif

@if(!empty($scope->referenced_documents) && is_array($scope->referenced_documents))
    <div class="section">
        <h2>3. Documents référencés</h2>
        <ul>
            @foreach($scope->referenced_documents as $doc)
                @if(!empty($doc))
                    <li>{{ $doc }}</li>
                @endif
            @endforeach
        </ul>
    </div>
@endif

@if(!empty($scope->processes) && is_array($scope->processes))
    <div class="section">
        <h2>4. Processus du système</h2>
        <table class="process-table">
            <thead>
                <tr>
                    <th>Processus</th>
                    <th>Type</th>
                </tr>
            </thead>
            <tbody>
                @foreach($scope->processes as $process)
                    @if(!empty($process['name']))
                        <tr>
                            <td>{{ $process['name'] }}</td>
                            <td>
                                @php
                                    $labels = [
                                        'management' => 'Management',
                                        'pilotage' => 'Pilotage',
                                        'realization' => 'Réalisation',
                                        'operationnel' => 'Opérationnel',
                                        'support' => 'Support',
                                    ];
                                    $typeKey = $process['type'] ?? '';
                                    echo $labels[$typeKey] ?? ucfirst($typeKey);
                                @endphp
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@if(!empty($scope->products_services) && is_array($scope->products_services))
    <div class="section">
        <h2>5. Produits et services</h2>
        <ul>
            @foreach($scope->products_services as $item)
                @if(!empty($item))
                    <li>{{ $item }}</li>
                @endif
            @endforeach
        </ul>
    </div>
@endif

@if(!empty($scope->organizational_units) && is_array($scope->organizational_units))
    <div class="section">
        <h2>6. Unités organisationnelles</h2>
        <ul>
            @foreach($scope->organizational_units as $unit)
                @if(!empty($unit))
                    <li>{{ $unit }}</li>
                @endif
            @endforeach
        </ul>
    </div>
@endif

@if(!empty($scope->locations) && is_array($scope->locations))
    <div class="section">
        <h2>7. Lieux d'activités</h2>
        <ul>
            @foreach($scope->locations as $location)
                @if(!empty($location))
                    <li>{{ $location }}</li>
                @endif
            @endforeach
        </ul>
    </div>
@endif

@if(!empty($scope->scope_exclusions) || !empty($scope->iso_exclusions))
    <div class="section">
        <h2>8. Exclusions et justifications</h2>
        <div class="exclusion-block">
            @if(!empty($scope->scope_exclusions))
                <p><strong>Exclusions générales du domaine :</strong></p>
                <p>{{ $scope->scope_exclusions }}</p>
            @endif

            @if(!empty($scope->iso_exclusions))
                @if(!empty($scope->scope_exclusions))
                    <hr style="border: 0; border-top: 1px solid #cbd5e1; margin: 10px 0;">
                @endif
                <p><strong>Exclusions aux exigences de la norme ISO 9001:2015 :</strong></p>
                <p>{{ $scope->iso_exclusions }}</p>
                @if(!empty($scope->iso_exclusions_justification))
                    <p style="margin-top: 4px; font-style: italic; color: #475569;">
                        <strong>Justification :</strong> {{ $scope->iso_exclusions_justification }}
                    </p>
                @endif
            @endif
        </div>
    </div>
@endif
