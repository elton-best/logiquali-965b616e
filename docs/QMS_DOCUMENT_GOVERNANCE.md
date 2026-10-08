# Gouvernance documentaire QMS

## Source de verite

Le modele `Document` est la source de verite du systeme documentaire QMS.
Les anciens endpoints ou formats `DocumentInventory` ne doivent servir que de compatibilite transitoire et doivent mapper vers `Document`.

## Typologie documentaire

Les types documentaires sont portes par `DocumentTypeConfiguration`.
Chaque document doit conserver:

- `document_type_configuration_id`
- `nomenclature_template_id`
- `nomenclature_template_version`
- `code`
- `version`
- `status`
- `workflow_status`
- `site_id`
- `process_id` ou contexte processus equivalent

## Codification

Un seul code documentaire doit etre genere par document.
La generation canonique passe par le template actif associe a la configuration documentaire.
Les codes importes ou recycles doivent conserver leur tracabilite dans `metadata` et dans le pool ou l'historique de liberation quand un recyclage est effectue.

Regles minimales:

- ne pas generer un code depuis l'ancien modele `document_nomenclatures` pour les nouveaux parcours;
- lier le code au template actif utilise au moment de la generation;
- conserver la version du template dans le document;
- marquer les codes importes avec `code_status = imported` tant qu'ils n'ont pas ete verifies;
- ne rendre un code recyclable qu'apres liberation explicite et historisee.

## Workflow documentaire

Le cycle de vie attendu est:

1. `draft`
2. `pending_verification`
3. `pending_approval`
4. `approved`
5. `obsolete` ou `archived`

Chaque transition sensible doit produire un evenement d'audit avec l'utilisateur, la date, le statut precedent, le nouveau statut et le motif si disponible.

## Import et migration

Deux parcours existent et ne doivent pas etre confondus:

- `document-imports/*`: import fichier avec upload, validation, execution, rollback et historique `DocumentImport`;
- `document-import/*`: migration one-time de documents existants vers `DocumentTypeConfiguration`.

Les imports fichier doivent conserver les champs legacy dans `metadata.legacy_type` au lieu de les ecrire comme colonnes applicatives supprimees.

## Exports

Tout export documentaire QMS doit exposer les metadonnees suivantes:

- code
- titre
- version
- statut
- site
- processus
- type documentaire
- template de nomenclature et version
- auteur
- validateur ou approbateur
- date d'approbation
- date de prochaine revue si applicable
