# Checklist de Retrait Final des Endpoints Legacy

Date de référence: 1 mars 2026  
Statut: En dépréciation progressive (compatibilité active)

## 1) Endpoints legacy concernés

### Risques / Opportunités
- Legacy:
  - `GET /api/v1/process-risks-opportunities`
  - `GET /api/v1/process-risks-opportunities/{riskOpportunity}`
  - `PUT /api/v1/process-risks-opportunities/{riskOpportunity}`
  - `DELETE /api/v1/process-risks-opportunities/{riskOpportunity}`
- Canonique:
  - `GET /api/v1/risks-opportunities`
  - `GET /api/v1/risks-opportunities/{riskOpportunity}`
  - `PUT /api/v1/risks-opportunities/{riskOpportunity}`
  - `DELETE /api/v1/risks-opportunities/{riskOpportunity}`

### Habilitations
- Legacy:
  - `GET /api/v1/habilitations/alertes`
- Canonique:
  - `GET /api/v1/habilitations/expires-soon`

## 2) État actuel (déjà implémenté)

- Les routes legacy existent encore et répondent normalement.
- Les routes legacy exposent des headers de dépréciation via middleware:
  - `Deprecation: true`
  - `X-API-Deprecated: true`
  - `X-API-Replacement: ...`
  - `Sunset: ...` (31 décembre 2026)
- Le frontend est migré vers les endpoints canoniques.

## 3) Ordre d’exécution recommandé (sans régression)

1. Phase de monitoring (maintenant -> date de retrait)
- Surveiller l’usage des endpoints legacy dans les logs d’accès/API.
- Confirmer qu’aucun client interne (front, script, intégration partenaire) ne les appelle encore.

2. Gate de décision (J-14 avant retrait)
- Critère de feu vert:
  - `0` appel legacy sur une fenêtre glissante de 14 jours.
- Si des appels persistent:
  - identifier le client;
  - corriger la source;
  - redémarrer une fenêtre de 14 jours.

3. Retrait effectif (après fenêtre verte)
- Supprimer uniquement les alias legacy dans `backend/routes/api.php`.
- Conserver les endpoints canoniques inchangés.

4. Stabilisation post-retrait (J+7)
- Surveiller erreurs 404/405 API.
- Si incident: rollback ciblé des routes supprimées (pas de rollback global de release).

## 4) Commandes de vérification avant retrait

Depuis la racine projet:

```bash
php -l backend/routes/api.php
cd frontend && npm run type-check
cd frontend && npm run test -- --run
```

Vérification manuelle des headers de dépréciation (environnement local API):

```bash
curl -i http://localhost:8000/api/v1/process-risks-opportunities
curl -i http://localhost:8000/api/v1/habilitations/alertes
```

Attendu:
- présence de `Deprecation: true`
- présence de `X-API-Replacement`

## 5) Plan de rollback minimal

Si régression après suppression:

1. Réintroduire uniquement les routes legacy supprimées.
2. Garder les routes canoniques inchangées.
3. Conserver middleware de dépréciation sur les routes réintroduites.
4. Identifier le consommateur legacy restant et planifier sa migration.

## 6) Définition de fin de migration

La migration est considérée terminée quand:
- aucun appel legacy observé pendant 14 jours;
- alias retirés;
- aucune erreur post-retrait pendant 7 jours.
