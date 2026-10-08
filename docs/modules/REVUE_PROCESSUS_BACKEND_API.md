# Revue Processus - Contrat API Backend (Sprint 1 Dev 1)

Base URL (authentifie): `/api/v1`

## Endpoints workflow

1. `POST /processes/{process}/reviews`  
   Cree une revue et initialise automatiquement `status=in_progress` et `started_at`.
2. `PUT /processes/{process}/reviews/{review}`  
   Met a jour une revue non cloturee.
3. `POST /processes/{process}/reviews/{review}/close`  
   Cloture definitivement la revue (`status=completed`, `ended_at` auto).
4. `GET /processes/{process}/reviews`  
   Liste des revues du processus.
5. `GET /processes/{process}/reviews/{review}`  
   Detail d une revue.

## Regles d acces metier

- Auth requise (`auth:sanctum`) + middlewares metier plateforme.
- La section Identification (`participants`, `participants_presence`, `role_assignments`, `action_responsibles`, `other_participants`, `coverage_start_date`, `coverage_end_date`) est modifiable uniquement par le RQ (pilote du processus) ou un profil de gestion (`processes.manage` / `processes.update` / super admin).
- Une revue cloturee ne peut plus etre modifiee (retour `409`).

## Contrat de reponse stable

Toutes les reponses des endpoints ci-dessus retournent `data` avec la meme structure (resource `ProcessReviewResource`):

```json
{
  "data": {
    "id": 12,
    "process_id": 8,
    "title": "Revue de : Processus X",
    "type": "periodique",
    "review_date": "2026-04-17",
    "version_reviewed": "1.0",
    "status": "in_progress",
    "started_at": "2026-04-17T10:20:30.000000Z",
    "ended_at": null,
    "participants": [],
    "participants_presence": [],
    "role_assignments": {},
    "action_responsibles": [],
    "synthesis_data": {},
    "pip_data": {},
    "risk_data": {},
    "opportunity_data": {},
    "quality_objectives_data": {},
    "quality_activities_data": {},
    "operational_activities_data": {},
    "compliance_data": {},
    "non_conformity_data": {},
    "leadership_data": {},
    "duerp_data": {},
    "computed_metrics": {
      "risk_opportunity": {
        "risk_count": 2,
        "opportunity_count": 1,
        "risk_rate": 66.67,
        "opportunity_rate": 33.33
      },
      "non_conformity": {
        "nc_count": 1,
        "base_count": 2,
        "nc_rate": 50.0
      },
      "execution": {
        "planned_actions_count": 3,
        "execution_rate": 50.0
      },
      "objectives": {
        "objectives_count": 2,
        "performance_average": 65.0
      }
    },
    "conclusion": null,
    "created_at": "2026-04-17T10:20:30.000000Z",
    "updated_at": "2026-04-17T10:20:30.000000Z"
  }
}
```

## Payloads sectionnels - regles minimales

### Champs Identification

- `participants`: `array<int user_id>`
- `participants_presence`: `array<{user_id:int, present:bool}>`
- `role_assignments`: `{rq_user_id?:int, pilot_user_id?:int, copilot_user_id?:int}`
- `action_responsibles`: `array<{section:string, user_id:int}>`
- `coverage_start_date`: `date`
- `coverage_end_date`: `date >= coverage_start_date`

### Champs sectionnels metier

Si une section est fournie, elle doit etre un objet non vide et respecter un minimum:

- `synthesis_data`: `summary` (string non vide) requis
- `pip_data`: `summary` non vide ou `stakeholders` non vide
- `risk_data`: `items` non vide
- `opportunity_data`: `items` non vide
- `quality_objectives_data`: `items` non vide
- `quality_activities_data`: `items` non vide
- `operational_activities_data`: `items` non vide
- `compliance_data`: `summary` non vide ou `items` non vide ou `nc_rate`
- `non_conformity_data`: `items` non vide
- `leadership_data`: `summary` non vide ou `decisions` non vide
- `duerp_data`: `summary` non vide ou `updates` non vide
- `conclusion`: `string` (min 3 chars)

## Exemples payload

### Creation revue

```json
{
  "coverage_start_date": "2026-03-01",
  "coverage_end_date": "2026-03-31",
  "participants_presence": [
    { "user_id": 14, "present": true }
  ],
  "synthesis_data": {
    "summary": "Synthese de la periode et points clefs."
  }
}
```

### Mise a jour section risques

```json
{
  "risk_data": {
    "items": [
      { "title": "Rupture fournisseur", "level": 4 }
    ]
  }
}
```

### Cloture definitive

```json
{
  "report_pdf_path": "reports/process-review-001.pdf",
  "report_docx_path": "reports/process-review-001.docx"
}
```

## Erreurs API attendues

- `401` utilisateur non authentifie.
- `403` modification Identification sans droits RQ/gestion.
- `404` revue non liee au processus demande.
- `409` tentative de mise a jour/cloture d une revue deja cloturee.
- `422` payload invalide (types, dates, coherence sectionnelle).
