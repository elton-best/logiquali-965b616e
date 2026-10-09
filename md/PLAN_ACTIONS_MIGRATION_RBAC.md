# Plan d'actions complet - RBAC canonique sans régression

## Objectif

Mettre en place un système de permissions **solide, fiable et très fiable** :

- permissions via **rôles uniquement** (suppression des permissions directes),
- alignement strict **menu + guard + route + API** sur une permission canonique unique,
- application homogène du **scope** (entreprise/site/subscription),
- migration progressive avec **mode shadow + rollback**.

---

## Principe directeur (priorité absolue)

1. **Une permission canonique unique par capacité métier**.
2. **Même règle d'autorisation** sur toute la chaîne : menu, guard, route, backend.
3. **Scope homogène** partout : `autorisé = permission canonique du rôle ET scope valide`.
4. Fin des exceptions implicites et des alias permanents.

---

## 1) Cadrage canonique

1. Définir la grammaire unique de permission (format final obligatoire).
2. Inventorier les permissions existantes + aliases legacy.
3. Produire la table de mapping officielle `legacy -> canonique`.
4. Bloquer toute nouvelle permission hors canon.

---

## 2) Inventaire des surfaces d'accès

1. Cartographier toutes les entrées menu (modules, sous-modules, sections).
2. Cartographier toutes les routes protégées (meta permissions).
3. Cartographier tous les contrôles backend (policies, middleware, controllers).
4. Construire la matrice de cohérence : `Menu <-> Route <-> API <-> Permission`.

---

## 3) Alignement du menu (UX déterministe)

1. Rendre la visibilité menu dépendante de la permission canonique.
2. Supprimer les règles parallèles non canoniques.
3. Garantir le contrat UX : **visible = ouvrable**.
4. Ajouter un état explicite si le scope bloque l'accès.

---

## 4) Alignement du guard (source unique)

1. Uniformiser le guard pour vérifier uniquement le canonique.
2. Encadrer les aliases en mode transitoire (loggés et expirants).
3. Utiliser un seul set de permissions de navigation cohérent.
4. Normaliser les motifs de refus : permission, scope, abonnement.

---

## 5) Alignement route/API backend

1. Harmoniser meta frontend et règles backend sur les mêmes permissions.
2. Retirer les divergences sémantiques entre modules équivalents.
3. Standardiser policies/middlewares/controllers.
4. Auditer les routes critiques (Personnel, Performance, Amélioration, Security Logs).

---

## 6) Scope homogène (entreprise / site / subscription)

1. Définir un ordre unique d'évaluation du scope.
2. Appliquer la même logique dans menu, guard et backend.
3. Verrouiller les cas sensibles (site_manager vs admin_entreprise).
4. Journaliser les refus liés au scope avec motif structuré.

---

## 7) Règle métier prioritaire : actions assignées

### 7.1 Garantie fonctionnelle

1. Tout utilisateur avec des actions assignées doit pouvoir les **voir**.
2. Tout utilisateur avec des actions assignées doit pouvoir les **traiter** selon workflow.
3. L'accès doit rester limité à ses actions assignées (pas d'élargissement global).

### 7.2 Gouvernance sécurité

1. Implémenter cette règle comme policy métier explicite (ownership/assignment).
2. Tracer l'origine de l'accès : rôle standard vs règle d'assignation.
3. Empêcher tout contournement vers des actions non assignées.

### 7.3 UX/UI

1. Distinguer clairement "Mes actions" des vues globales.
2. Afficher des messages explicites en cas de refus hors périmètre.
3. Conserver des interactions homogènes (états, feedback, erreurs).

---

## 8) Interface Logs (prise en compte explicite)

### 8.1 RBAC

1. Permission canonique : `security_audit.read`.
2. Aligner menu + guard + route + backend sur cette permission.

### 8.2 Backend

1. Deny by default hors profils autorisés.
2. Validation stricte des filtres.
3. Rate limiting dédié.
4. Scope entreprise/site systématique.
5. Réduction de l'exposition des données sensibles.

### 8.3 UX

1. État "aucun accès" lisible.
2. Filtres cohérents et non ambigus.
3. Retours d'erreur actionnables.

---

## 9) Migration des utilisateurs existants (sans casse)

1. Identifier les permissions directes en base.
2. Transformer ces droits en rôles équivalents.
3. Assigner les rôles avant suppression des droits directs.
4. Vérifier l'équivalence d'accès utilisateur par utilisateur.
5. Supprimer ensuite les permissions directes.

---

## 10) Déploiement progressif (shadow + rollback)

1. Exécuter ancien et nouveau moteur en parallèle (shadow mode).
2. Logger toutes les divergences de décision.
3. Corriger jusqu'à convergence stable.
4. Basculer par cohortes.
5. Activer globalement après stabilisation.
6. Désactiver définitivement l'ancien modèle.

---

## 11) Tests et validation

1. Unit tests : normalisation canonique, guards, policies.
2. Intégration : routes/API par profil et par scope.
3. E2E : parcours critiques (Personnel, Performance, Amélioration, Logs, Mes actions).
4. Non-régression UX : visibilité menu, accès route, messages.
5. UAT métier avant généralisation.

---

## 12) Rollback maîtrisé

1. Snapshot DB avant chaque vague.
2. Feature flag de retour arrière.
3. Procédure de restauration rôles/assignations.
4. Critères de rollback immédiat documentés.
5. Communication opérationnelle préparée.

---

## Critères de succès (Go / No-Go)

1. 0 permission directe active en base.
2. 100% des décisions d'accès sur permissions canoniques + scope.
3. 0 mismatch entre menu, guard, route et API.
4. Règle "actions assignées visibles et traitables" validée sans fuite de périmètre.
5. 0 régression sur parcours critiques.
6. Audit trail complet des refus et des changements RBAC.
