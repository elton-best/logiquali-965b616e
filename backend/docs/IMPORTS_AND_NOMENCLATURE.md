# Imports et Nomenclature — Référence Opérationnelle

## 1) Import nomenclature (règles de code)
- Objectif: définir comment les codes documentaires sont générés pour un site (et extensible entreprise).
- Entrées: configuration documentaire active par type (`DocumentTypeConfiguration`) + structure (`CodeStructurePart`) et/ou template publié.
- Prérequis:
  - utilisateur autorisé (`configure_nomenclature`),
  - site ciblé valide et dans le périmètre entreprise,
  - structure de code publiée et active.
- Validation:
  - présence d’au moins une partie `sequence`,
  - cohérence des longueurs/séparateurs,
  - scope compatible (site/entreprise).
- Effet:
  - les nouveaux documents utilisent exclusivement la génération backend.

## 2) Import inventaire documentaire (documents)
- Objectif: importer des documents métier dans l’inventaire.
- Endpoint principal: `/api/v1/document-imports/*`.
- Prérequis:
  - permission d’import,
  - fichier CSV/XLSX valide,
  - prévisualisation validée.
- Mode d’exécution actuel: **partiel contrôlé**.
  - Les lignes valides sont importées.
  - Les lignes invalides sont rejetées.
- Contrat d’exécution:
  - `preview_confirmed=true`
  - `conflict_strategy=skip_invalid|stop_on_error`
  - `code_generation_mode=nomenclature_only`
  - `target_scope=site`
- Rapport d’erreurs:
  - ligne,
  - cause,
  - `correction_attendue`.
- Observabilité:
  - `correlation_id` par exécution,
  - compteurs `imported/failed/rejected`,
  - `import_stats` persisté.

## 3) Import référentiels/support
- Objectif: importer des référentiels de support (ex: collaborateurs, communications, etc.) hors inventaire documentaire.
- Règle produit:
  - ces imports ne remplacent pas l’import inventaire documentaire,
  - chaque flux conserve ses validations métier dédiées.
- Recommandation:
  - maintenir des templates distincts par import,
  - messages d’erreur contextualisés par domaine.

## Nomenclature obligatoire (règle centrale)
- Aucune création de document sans configuration active pour `(site, type)`.
- Le type doit être reconnu à la création/import.
- En mode standard, le code manuel est refusé.
- La génération de code est backend uniquement.

## Cycle de vie des codes
- États fonctionnels:
  - `reserved` → `used` ou `released`.
- Rejet pour code incorrect:
  - le code est libéré (`released`) et réattribuable.
- Réattribution:
  - FIFO sur les codes libérés éligibles.
- Invariant:
  - un code `used` n’est jamais recyclé.

## Sécurité et permissions
- Front:
  - onglets/actions Vérification/Approbation masqués sans permission.
- Back:
  - contrôle d’accès conservé (403 en accès direct non autorisé).
- Multi-tenant:
  - contrôles site/entreprise appliqués côté API.

