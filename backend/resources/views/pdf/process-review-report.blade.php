<!doctype html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Rapport Revue Processus</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #0f172a; }
    .title { font-size: 20px; font-weight: 700; text-align: center; margin-bottom: 20px; }
    .meta { margin-bottom: 14px; }
    .meta div { margin-bottom: 4px; }
    .block { margin-bottom: 16px; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 8px; }
    .block h3 { margin: 0 0 10px; font-size: 14px; }
    .muted { color: #475569; }
    .label { font-weight: 700; }
    .metric { display: inline-block; margin: 0 8px 8px 0; padding: 4px 8px; border: 1px solid #cbd5e1; border-radius: 6px; }
  </style>
</head>
<body>
  <div class="title">Rapport de Revue Processus</div>

  <div class="meta">
    <div><span class="label">Processus:</span> {{ $process?->title ?? 'N/A' }} ({{ $process?->code ?? 'N/A' }})</div>
    <div><span class="label">Date revue:</span> {{ $reviewDate }}</div>
    <div><span class="label">Statut:</span> {{ $review->status }}</div>
  </div>

  <div class="block">
    <h3>1. Identification</h3>
    <div><span class="label">RQ:</span> {{ $identification['rq_name'] ?? 'N/A' }}</div>
    <div><span class="label">Période:</span> {{ $identification['coverage_start'] ?? 'N/A' }} -> {{ $identification['coverage_end'] ?? 'N/A' }}</div>
    <div><span class="label">Heure début:</span> {{ $identification['started_at'] ?? 'N/A' }}</div>
    <div><span class="label">Heure fin:</span> {{ $identification['ended_at'] ?? 'N/A' }}</div>
  </div>

  <div class="block">
    <h3>2. Sections métier</h3>
    <p><span class="label">Synthèse PIP:</span> <span class="muted">{{ $sections['pip_summary'] ?? 'Non renseigné' }}</span></p>
    <p><span class="label">Risques / Opportunités:</span> <span class="muted">{{ $sections['risk_opportunity_summary'] ?? 'Non renseigné' }}</span></p>
    <p><span class="label">Objectifs / Activités / Projets:</span> <span class="muted">{{ $sections['objectives_projects_summary'] ?? 'Non renseigné' }}</span></p>
    <p><span class="label">Conformité / NC / Satisfaction:</span> <span class="muted">{{ $sections['compliance_nc_satisfaction_summary'] ?? 'Non renseigné' }}</span></p>
    @if(!empty($sections['management_duerp_display']))
      <p><span class="label">Leadership / DUERP:</span> <span class="muted">{{ $sections['management_duerp_display'] }}</span></p>
    @endif
  </div>

  @if(!empty($metrics))
    <div class="block">
      <h3>3. Indicateurs de synthèse</h3>
      @foreach($metrics as $metric)
        <span class="metric"><span class="label">{{ $metric['label'] ?? 'Indicateur' }}</span>: {{ $metric['value'] ?? 'N/A' }}</span>
      @endforeach
    </div>
  @endif

  <div class="block">
    <h3>4. Traçabilité incidents</h3>
    <p><span class="label">Incidents liés:</span> {{ $incidentTraceability['incidents_linked_count'] ?? 0 }}</p>
    <p><span class="label">NC issues d’incidents:</span> {{ $incidentTraceability['non_conformities_linked_count'] ?? 0 }}</p>
    <p><span class="label">Actions correctives liées:</span> {{ $incidentTraceability['linked_actions_count'] ?? 0 }}</p>
    <p><span class="label">Actions liées en retard:</span> {{ $incidentTraceability['linked_actions_overdue_count'] ?? 0 }}</p>

    @php($recentIncidents = $incidentTraceability['recent_incidents'] ?? [])
    @if(is_array($recentIncidents) && !empty($recentIncidents))
      <p class="label">Incidents récents liés:</p>
      <ul class="muted">
        @foreach($recentIncidents as $incident)
          <li>
            {{ $incident['title'] ?? ('Incident #' . ($incident['id'] ?? 'N/A')) }}
            - Statut: {{ $incident['status'] ?? 'N/A' }}
            - Échéance: {{ $incident['due_date'] ?? 'N/A' }}
          </li>
        @endforeach
      </ul>
    @endif
  </div>
</body>
</html>
