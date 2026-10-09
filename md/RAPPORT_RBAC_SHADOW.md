# Rapport RBAC Shadow — Suivi des divergences

## 1) Périmètre du rapport

- Date de collecte:
- Environnement: (local / staging / prod)
- Fenêtre analysée: (24h / 7j / 14j / 30j)
- Version applicative / commit:
- Responsable collecte:

---

## 2) Résumé exécutif

- Total divergences:
- Permission:
- Scope:
- Subscription:
- Modules les plus impactés:
- Impact métier estimé:

---

## 3) Top permissions divergentes

| Permission | Nombre | Module | Criticité | Commentaire |
|---|---:|---|---|---|
|  |  |  |  |  |
|  |  |  |  |  |
|  |  |  |  |  |

---

## 4) Détails des cas à corriger (prioritaires)

> Remplir 1 bloc par divergence importante.

### Cas #1

- Type divergence: (permission / scope / subscription)
- Profil impacté: (rôle, user_type, enterprise, site)
- Route UI:
- Endpoint API:
- Attendu:
- Observé:
- Fréquence:
- Criticité: (Haute / Moyenne / Basse)
- Preuve (timestamp + extrait):
- Décision cible canonique:

### Cas #2

- Type divergence:
- Profil impacté:
- Route UI:
- Endpoint API:
- Attendu:
- Observé:
- Fréquence:
- Criticité:
- Preuve (timestamp + extrait):
- Décision cible canonique:

### Cas #3

- Type divergence:
- Profil impacté:
- Route UI:
- Endpoint API:
- Attendu:
- Observé:
- Fréquence:
- Criticité:
- Preuve (timestamp + extrait):
- Décision cible canonique:

---

## 5) Timeline des divergences

| Jour | Nombre divergences | Tendance |
|---|---:|---|
|  |  |  |
|  |  |  |
|  |  |  |

---

## 6) Décisions de rollout (Go / No-Go)

- Cohorte candidate:
- Pré-requis validés:
- Risques restants:
- Décision: (GO / NO-GO)
- Justification:

---

## 7) Plan de correction validé

| Priorité | Correction | Zone | Responsable | Statut |
|---|---|---|---|---|
| P1 |  | Frontend / Backend / RBAC |  | À faire |
| P1 |  | Frontend / Backend / RBAC |  | À faire |
| P2 |  | Frontend / Backend / RBAC |  | À faire |

---

## Comment récupérer les données (simple)

1. Ouvrir **Journal de sécurité** (`/company/security-audit-logs`).
2. Regarder le bloc **Divergences RBAC (shadow)**.
3. Choisir la fenêtre (24h, 7j, 14j, 30j).
4. Reporter:
   - Total divergences
   - Répartition Permission / Scope / Subscription
   - Top permissions divergentes
5. Compléter les cas prioritaires avec les incidents vus (visible mais 403, etc.).

---

## Format de retour recommandé

- Remettre ce fichier complété tel quel.
- Ajouter en annexe les captures/logs si nécessaire.
