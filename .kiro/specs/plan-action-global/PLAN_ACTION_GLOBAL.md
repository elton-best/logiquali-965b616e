# PLAN D'ACTION GLOBAL LOGIQUALI

Mise a jour: 2026-05-19
Statut source: audit codebase backend/frontend (analyse statique)
Cadence imposee: 1 tache a la fois -> implementation -> tests unitaires -> tests manuels -> validation -> tache suivante.

## Legende Statut
- `FAIT`: implemente et visible dans le code
- `PARTIEL`: implemente en partie, ecarts restants
- `A FAIRE`: non implemente ou non trouve

## Ordre d'execution obligatoire
A -> B -> C -> D -> E -> F -> G

## Bloc A - Fondations techniques
| Tache | Statut | Notes |
|---|---|---|
| A1 - Handler global d'erreurs | FAIT | Interception QueryException/ValidationException documentaire/ErrorException dans `backend/bootstrap/app.php`. |
| A2 - Supprimer table `user_permissions` custom | FAIT | Migration de suppression presente + relation custom retiree du model User. |
| A3 - Supprimer champ `permissions` de UserController | FAIT | Validation et logique permissions directes retirees dans `UserController`. |

## Bloc B - Systeme documentaire unifie
| Tache | Statut | Notes |
|---|---|---|
| B1 - `DocumentTypeConfigurationBootstrapService` | FAIT | Service present, idempotent, branche observer + seeder. |
| B2 - `DocumentTypeResolver` | FAIT | Service present avec mapping principal. |
| B3 - Degrade gracieux CodeGeneration | FAIT | Fallback `[NON-CONFIGURE-*]` + `is_fallback` en place. |
| B4 - MAJ tous les controleurs export avec resolver | FAIT | Contrôleurs d'export alignés sur `DocumentTypeResolver` (incl. context, scope, stakeholders, policy, process, responsibility, risks, compliance, provider export, objectifs export). |
| B5 - Fix `ProcessController::exportDocx()` bug `$filePath` | FAIT | Generation fichier avant checks + verification existence + try/catch. |
| B6 - Unifier 2 systemes nomenclature | FAIT | Onglet legacy désactivé + message de remplacement vers Configuration avancée, libellés legacy retirés des points critiques UI. |
| B7 - Duplication Sensibilisation/Communication sidebar | FAIT | Routes distinctes + seeder aligne (`/support/awareness` et `/support/communication`). |

## Bloc C - RBAC & Permissions
| Tache | Statut | Notes |
|---|---|---|
| C1 - Supprimer roles legacy | FAIT | Migration de reassignment vers `lecteur` en place + references legacy retirees des policies/listeners/seeders/factory principal. |
| C2 - Supprimer alias permissions | FAIT | `permission_aliases` vide; normalisation alias simplifiee et checks canoniques valides par tests RBAC. |
| C3 - `RoleScopeService` roles systeme visibles | FAIT | Roles systeme inclus et pagination roles a 100. |
| C4 - `syncRoles()` -> roles multiples | FAIT | `addRole/removeRole` + routes API presentes; logique multi-roles active. |
| C5 - Creation/modif roles personnalises backend | FAIT | Validation enterprise_id, unicite, filtrage super_admin_only, sync permissions, blocage noms systeme. |
| C6 - Connecter UI roles a API | FAIT | `createRole`, `savePermissions`, `deleteRole` relies a l'API + gestion multi-roles utilisateur branchee. |
| C7 - Securite escalade horizontale + exposition API | FAIT | Blocage `site_manager` actif + UserResource limite `role_permissions` en liste et expose en detail/profil autorise. |
| C8 - Activer `enforce_active_scope` | FAIT | `AUTHZ_ENFORCE_ACTIVE_SCOPE=true` active sur `.env`, `.env.example`, `.env.testing`; tests de scope passes. |

## Bloc D - Corrections critiques modules
| Tache | Statut | Notes |
|---|---|---|
| D1 - Fiche de poste SQL `activities` NOT NULL | FAIT | Migration nullable ajoutee + garde defensive controller pour creation sans activites. |
| D2 - Mes Taches: processus affiche en JSON | FAIT | `normalizeTask()` retourne `process_name`, chargement relation `process` ajoute, affichage frontend lisible. |
| D3 - Fiche responsabilite export casse | FAIT | Endpoints export PDF + DOCX implementes, routees API branchees, bouton frontend PDF connecte. |
| D4 - Prestataires: ajout documents casse | FAIT | Flux dossier fichiers conserve et fiabilise + export liste prestataires deplace cote backend (endpoint xlsx) et branche au frontend. |
| D5 - Uniformiser libelles/UI/fautes/datepickers | FAIT | Perimetre cible (Non-conformites, Objectifs, Plan d'action/planning) uniformise: libelles statuts harmonises et champs date migrés vers `AppDatePickerField`. |

## Bloc E - Fonctionnalites manquantes modules
| Tache | Statut | Notes |
|---|---|---|
| E1 - Objectifs QHSE logique actions + suivi | FAIT | Actions liées aux objectifs synchronisées, statuts et taux automatiques, suivi branché, export avec code documentaire, et test unitaire E1 de non-régression ajouté (`ObjectiveServiceE1Test`). |
| E2 - Plans SM suivi + archivage + responsables | FAIT | Suivi activites/taches connecte a Mes Taches (incl. preselection), responsables et impliques exposes backend/frontend, notifications d'assignation branchees, archivage annuel operationnel via commande + scheduler. |
| E3 - Formations refonte complete | FAIT | Champ cout retire du flux principal, distinction ponctuelle/recurrente implementee (UI + validations backend), selection multi-collaborateurs avec filtres/recherche/selection groupee operationnelle, bouton Suivi connecte a Mes Taches (grille+table, preselection), synchro auto `Formation -> TrainingPlan` fiabilisee (create/update/delete/import/complete/reschedule/cancel), notifications responsables/participants actives via `FormationObserver`, et tests de non-regression E3 ajoutes. |
| E4 - Communication meme logique E3 | FAIT | Alignement E3 appliqué: règles ponctuelle/récurrente (`period_mode`), synchro auto `Communication -> CommunicationPlan`, import robuste, suivi/statuts connectés, tests workflow/import verts. |
| E5 - Audits programme auto + eval auditeurs | FAIT | Programme annuel auto declenche a la creation d'audit interne; endpoints dedies evaluation auditeur (criteres + creation demande liee a l audit) implementes; connexion explicite TaskTracking/notifications du flux audit finalisee (creation/demarrage/finalisation/closure/annulation); tests backend associes verts. |
| E6 - Non-conformites plan d'action connecte | FAIT | Flux NC connecté explicitement à TaskTracking (création/assignation/statuts/analyse/validation/vérification/clôture), notifications parties prenantes ajoutées, couverture Mes tâches validée, tests feature/service verts. |
| E7 - Maitrise sorties non conformes suivi connecte | FAIT | Flux réclamations/incidents connecté à TaskTracking (création/assignation/analyse/réponse/clôture), notifications parties prenantes ajoutées, exposition Mes tâches ajoutée (`type=reclamation`), mapping statuts DB normalisé, test feature E7 vert. |
| E8 - Planification operationnelle onglets + suivi | FAIT | Onglet actions consolidees alimente depuis l'aggregation des actions systeme + synchronisation TaskTracking sur activites/taches operationnelles (create/update) validee par test feature `OperationalPlanningE8Test`. |
| E9 - Surveillance/evaluation criteres + liens publics | FAIT | Critères personnalisés opérationnels via API, lien public d'évaluation validé (affichage + soumission), et tableau de bord statistiques des soumissions validé par test d'intégration `SurveillanceEvaluationE9WorkflowTest`. |

## Bloc F - UX/UI & Qualite transversale
| Tache | Statut | Notes |
|---|---|---|
| F1 - Refondre `UserPermissionsManager.vue` + `permissionLabels.ts` | A FAIRE | `permissionLabels.ts` non trouve; refonte cible non complete. |
| F2 - Descriptions lisibles roles + modale diff permissions | A FAIRE | `role_catalog` enrichi et modal diff non confirmes dans le scope demande. |
| F3 - Uniformiser exports PDF/Excel/Word | PARTIEL | Pieces en place, normalisation globale header code/branding/filename non confirmee partout. |
| F4 - Verification/Approbation dans sidebar selon permissions | PARTIEL | Permissions presentes, verification exhaustive role/UI/API reste a verrouiller. |

## Bloc G - Tests integration & documentation finale
| Tache | Statut | Notes |
|---|---|---|
| G1 - `RbacPermissionsTest.php` scenarios cibles | A FAIRE | Fichier cible non trouve. |
| G2 - `DocumentCodeGenerationTest.php` scenarios cibles | A FAIRE | Fichier cible non trouve. |
| G3 - MAJ `UnifiedPermissionsSeeder` | PARTIEL | Backfill 3 roles coeur OK; nettoyage complet legacy restant a finir. |
| G4 - MAJ docs reference (`RBAC_REFERENCE.md`, `DOCUMENT_SYSTEM_REFERENCE.md`, plan) | PARTIEL | Ce plan est a jour; autres docs de reference restent a produire/mettre a jour. |

## Synthese Execution
- Fondations critiques (A) sont operationnelles.
- Systeme documentaire (B) et RBAC (C) sont majoritairement en place mais necessitent un lot de fermeture.
- Le plus gros reste est dans D/E/F/G (corrections modules, completion fonctionnelle, integration tests, documentation finale).

## Priorites suivantes (ordre recommande)
1. D1 - verrouiller le fix `activities` (migration nullable + test unitaire + test manuel).
2. D2 - corriger `MyTasks` (`process_name` + eager loading + filtre frontend).
3. D3 - restaurer export Fiche de responsabilite.
4. B4 - fermer la couverture exports manquants (Objective/Responsibility/Provider selon mapping final).
5. C8 - activer et valider `AUTHZ_ENFORCE_ACTIVE_SCOPE` sur env de test.
6. G1/G2 - ecrire les tests d'integration cibles avant poursuite massive de E/F.

## Suivi Refonte Permissions (P0/P1)
| Priorite | Statut | Notes |
|---|---|---|
| P0 - Matrice permissions↔normes↔modules | FAIT | Table `permission_norm_mappings` en place, service de résolution branché sur API active, validation d'assignation active (middleware + endpoint de sync rôles), tests `PermissionNormMappingServiceTest` et `PermissionAssignmentValidationTest` verts. |
| P1 - Extension granularité sous-module/section | FAIT | Migration `sub_module_id/section_id` en place, seeder enrichi (scope explicite + fallback), projection API active expose `sub_module_*` / `section_*`, mappings explicites renforcés sur permissions critiques (`*.manage`, `processes.*`, `*_documents`), tests unit + feature dédiés verts (service, validation assignation, projection active, seeder idempotent). |

### Cloture P1 (Done)
- Scope data: table `permission_norm_mappings` supporte `module_id`, `sub_module_id`, `section_id`.
- Scope seeding: mappings explicites par permission + fallback alias + idempotence couverte.
- Scope API: `/api/v1/available-permissions/active` retourne la projection granulaire (norme/module/sous-module/section).
- Scope validation RBAC: assignation de permissions filtrée par normes souscrites (site courant).
- Scope tests: jeux de tests unit/feature verts sur service, assignation, projection active et seeder.

## Cloture P6 (Backend)
- Immutabilité stricte après validation appliquée:
  - modification du code interdite,
  - suppression interdite,
  - upload de nouvelle version interdit,
  - recyclage/libération du code interdit via service et API.
- Export inventaire verrouillé sur documents validés.
- Couverture de non-régression ajoutée et verte sur:
  - `DocumentCodeImmutabilityTest`
  - `DocumentCodePoolWorkflowTest`
  - `DocumentCodeRecyclingImmutabilityTest`
  - `DocumentInventoryExportWorkflowGateTest`

## Baseline CI non-régression documentaire
- Exécuter à chaque PR:
  - `php artisan test --filter=DocumentCodeImmutabilityTest`
  - `php artisan test --filter=DocumentCodePoolWorkflowTest`
  - `php artisan test --filter=DocumentCodeRecyclingImmutabilityTest`
  - `php artisan test --filter=DocumentInventoryExportWorkflowGateTest`
  - `php artisan test --filter=DocumentWorkflowEnhancedTest`
- Critère d’acceptation:
  - 0 régression sur workflow Vérifier -> Valider -> Exporter
  - 0 régression sur immutabilité post-validation
  - 0 permission critique non mappée dans `permissions:audit-matrix --fail-on-critical`





http://localhost:3000/superadmin/kyc/1 

-> prévisualition pdf ne passe pas ; ça veut enrégistrer automatiquement

http://localhost:3000/superadmin/kyc

-> entreprise approuvée mais il faut actualiser la page avant que les cartes ne se mettent à jour

http://localhost:3000/superadmin/profile

-> photo de profil disparait même après avoir été enrégistrée
-> alertes système se décoche même après avoir été coché. De plus à quoi sert ce bouton ?

http://localhost:3000/company/documents/verification & http://localhost:3000/company/documents/approbation

-> corriger "No data available" et "Items per page:" qui sont anglais par l'équivalent en français

http://localhost:3000/company/leadership/organization-chart

-> impossible de supprimer le document uploadé (The route api/v1/org-chart/1 could not be found.  Suppression impossible: identifiant invalide ou document déjà supprimé)
-> aperçu du document ne marche pas en pdf. Je n'ai pas pu vérifier en jpeg ou png puisque je ne pouvais supprimer le doc

http://localhost:3000/company/leadership/personnel

-> dans la modale de création d'un collaborateur, le calendrier est en anglais et donc le format de date aussi. Peut-il être en français par défaut ? ou peut-on avoir la possibilité de choisir le format de la date ?
-> quand je crée le rôle personnalisé, au moment d'attribuer les permissions j'ai le message suivant : Permissions héritées du rôle: 0. Permissions directes personnalisées: 0. Permissions effectives estimées: 0. Permissions actives selon site/souscription: 0.
Les permissions ne sont donc pas récupérées et donc pas disponibles pour être attribuées.


-> Quand je survole les bouton de la sidebar, il y a une sorte de bouton noir qui apprait et que je ne veux plus voir apparaitre
