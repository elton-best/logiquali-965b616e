Document System Reference
=========================

This document summarises the document codification, code-pool and RBAC behaviour relevant to Block G.

1) Status mapping
- brouillon ↔ draft
- en_revision ↔ pending_verification
- valide ↔ approved

2) Document code pool (app/Models/DocumentCodePool.php & app/Services/DocumentCodeRecyclingService.php)
- Table: document_code_pool
- Statuses: available, reserved, used
- reserveCode(code, siteId, documentType, documentId) — reserves or creates a reserved entry
- markCodeAsUsed(documentId) — mark reserved as used
- releaseCodeByDocumentId(documentId, reason) — release a reserved/used entry back to available
- getNextAvailableCode(siteId, type) — returns next available recycled code (does not reserve)
- cleanupExpiredReservations(hours) — releases stale reservations

3) Nomenclature / generation
- Nomenclature templates stored in nomenclature_templates (NomenclatureTemplate)
- Service: app/Services/NomenclatureTemplateService.php
  - generateNextCode(siteId, documentType, templateId, context) builds code using template parts
  - Uses DocumentCodePool when recycling available codes

4) RBAC & Seeder
- Unified seeder: database/seeders/UnifiedPermissionsSeeder.php
  - Seeds system & business permissions and uses RolePermissionBaselineService to seed baseline roles
- Role mutation protections: protected system roles (super_admin etc.) cannot be created by company admins

5) Block G changes (this commit)
- Added tests: backend/tests/Feature/BlockGIntegrationTest.php
  - test_lecteur_cannot_create_actions
  - test_site_manager_cannot_assign_site_manager_role
  - test_admin_entreprise_cannot_create_protected_role
  - test_effective_permissions_are_union_of_roles
- Added this DOCUMENT_SYSTEM_REFERENCE.md describing behaviours and how to run Block G tests

6) How to run the new tests locally
- Backend:
  cd backend
  ./vendor/bin/phpunit --filter BlockGIntegrationTest

Notes and next steps
- CI: ensure the new tests are included in the pipeline (they run with the existing phpunit job)
- Documentation: link this reference from PLAN_ACTION_GLOBAL.md and from docs/nomenclature-system-documentation.md if desired
- If tests fail in CI due to middleware or missing roles, run UnifiedPermissionsSeeder or adjust test to seed required permissions/roles

