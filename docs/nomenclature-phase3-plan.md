# Phase 3 - Intégration Frontend/Backend & Workflow (Jours 8-10)

## Objectif
Intégrer le système de nomenclature flexible dans le workflow de création de documents et implémenter la génération automatique des codes.

---

## Jour 8 : Intégration dans le formulaire de création de documents

### Backend
- [ ] Modifier `DocumentController@store` pour utiliser `CodeGenerationService`
- [ ] Ajouter validation du `document_type_configuration_id`
- [ ] Implémenter la logique de génération de code automatique
- [ ] Gérer les cas d'erreur (configuration inactive, séquence épuisée)

### Frontend
- [ ] Modifier `DocumentForm.vue` pour charger les configurations actives
- [ ] Ajouter sélecteur de type de document (basé sur configurations)
- [ ] Afficher aperçu du code avant création
- [ ] Gérer les états de chargement et erreurs

---

## Jour 9 : Workflow de vérification et activation des codes

### Backend
- [ ] Créer `DocumentCodeWorkflowController` avec endpoints:
  - `POST /documents/{id}/verify-code` - Vérifier un code
  - `POST /documents/{id}/activate-code` - Activer un code
  - `POST /documents/{id}/release-code` - Libérer un code
  - `GET /documents/pending-verification` - Liste des codes à vérifier
  - `GET /documents/pending-approval` - Liste des codes à approuver

- [ ] Implémenter les permissions:
  - `verify_documents` - Pour les vérificateurs
  - `approve_documents` - Pour les approbateurs

### Frontend
- [ ] Créer page `DocumentCodeVerification.vue` pour les vérificateurs
- [ ] Créer page `DocumentCodeApproval.vue` pour les approbateurs
- [ ] Ajouter badges de statut dans la liste des documents
- [ ] Implémenter les actions de workflow (vérifier, approuver, rejeter)

---

## Jour 10 : Tests d'intégration et documentation

### Tests Backend
- [ ] Test de génération de code avec différentes structures
- [ ] Test de recyclage des numéros
- [ ] Test des portées de séquence
- [ ] Test du workflow complet (création → vérification → activation)
- [ ] Test des permissions

### Tests Frontend
- [ ] Test du wizard de configuration
- [ ] Test du drag & drop de structure
- [ ] Test de la création de document avec code auto
- [ ] Test du workflow de vérification/approbation

### Documentation
- [ ] Guide utilisateur pour configurer une nomenclature
- [ ] Guide administrateur pour gérer les configurations
- [ ] Documentation API des nouveaux endpoints
- [ ] Exemples de structures de code courantes

---

## Fichiers à créer/modifier

### Backend (Jour 8)
- `app/Http/Controllers/Api/DocumentController.php` (modifier)
- `app/Http/Requests/StoreDocumentRequest.php` (modifier)

### Backend (Jour 9)
- `app/Http/Controllers/Api/DocumentCodeWorkflowController.php` (créer)
- `app/Services/DocumentCodeWorkflowService.php` (créer)
- `routes/api.php` (modifier)

### Frontend (Jour 8)
- `frontend/src/components/documents/DocumentForm.vue` (modifier)
- `frontend/src/composables/useDocumentCreation.ts` (créer)

### Frontend (Jour 9)
- `frontend/src/pages/documents/CodeVerification.vue` (créer)
- `frontend/src/pages/documents/CodeApproval.vue` (créer)
- `frontend/src/api/documentWorkflow.ts` (créer)
- `frontend/src/composables/useDocumentWorkflow.ts` (créer)

---

## Critères de succès

✅ Un utilisateur peut créer une configuration de nomenclature via l'interface
✅ Un document créé reçoit automatiquement un code selon la configuration
✅ Les numéros de séquence sont correctement incrémentés et recyclés
✅ Le workflow de vérification/activation fonctionne
✅ Les permissions sont respectées
✅ La documentation est complète et à jour
