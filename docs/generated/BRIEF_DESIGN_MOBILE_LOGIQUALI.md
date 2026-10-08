# BRIEF DESIGN MOBILE - LOGIQUALI

Date: 20 avril 2026  
Auteur: Analyse produit/UX à partir du code existant

## 1) Périmètre analysé (sources réelles)

Cette analyse s’appuie sur le code actuel:

- Web principal (Vue 3 + Vuetify):
  - `frontend/src/router/index.ts`
  - `frontend/src/modules/clienta/router/index.ts`
  - `frontend/src/modules/clientb/router/index.ts`
  - `frontend/src/modules/superadmin/router/index.ts`
  - `frontend/src/modules/clienta/pages/**`
  - `frontend/src/services/**`
- Design system web:
  - `frontend/src/modules/clienta/assets/clienta-theme.css`
  - `docs/guides/GUIDE_STYLE_CLIENT_A.md`
  - `docs/STYLE_GUIDE.md`
- Prototype mobile déjà existant (non natif):
  - `Logi/SMI/App.tsx`
  - `Logi/SMI/MainLayout.tsx`
  - `Logi/SMI/QHSESidebar.tsx`
  - `Logi/SMI/pages/Dashboard.tsx`
  - `Logi/SMI/pages/planification/RisquesPage.tsx`
  - `Logi/SMI/ObjectifsPage.tsx`
  - `Logi/SMI/pages/support/DocumentsPage.tsx`
  - `Logi/SMI/hooks/use-mobile.tsx`

## 2) Constat global (important pour le designer)

- Il n’existe pas encore d’application mobile native (pas de Flutter/React Native/Ionic/Capacitor dans le repo).
- Le web est très riche fonctionnellement (Client A majoritairement), avec un volume d’écrans élevé.
- Un prototype UI React (`Logi/SMI`) existe déjà avec 4 écrans principaux exploitables comme base visuelle:
  - Dashboard
  - Risques & opportunités
  - Objectifs
  - Information documentée
- Le prototype `Logi/SMI` est desktop-first avec sidebar gauche. Il faut le transformer en navigation mobile-first (bottom tabs + stacks).

## 3) Cartographie fonctionnelle web actuelle

## 3.1 Personae produits

- Super admin (`/superadmin/**`)
- Client A entreprise (`/company/**`) - coeur métier ISO 9001
- Client B (`/clientb/**`) - satisfaction, réclamations, support
- Public (landing, auth, formulaire public d’évaluation)

## 3.2 Domaines Client A à couvrir en mobile

1. Tableau de bord et actions rapides
2. Contexte de l’organisme
3. Leadership
4. Planification
5. Support
6. Réalisation opérationnelle
7. Évaluation des performances
8. Amélioration
9. Documents, rapports, notifications, paramètres

## 3.3 Écrans clés (Client A) à haute valeur mobile

- Dashboard entreprise
- Risques & opportunités (liste, détail, création, actions par type)
- Objectifs qualité (liste, détail, suivi actions)
- Actions (liste, détail, progression)
- Non-conformités (liste, création, workflow)
- Audits internes:
  - Hub audits
  - Programme d’audit
  - Planification
  - Détail audit
  - Évaluation auditeurs
- Revue de direction:
  - Accueil
  - Synthèse SM (ISO 9001 §9.3.2 b, c1, c2, c4, c7, e)
  - Rapports/invitations
- Fiches de satisfaction/performance
- Gestion documentaire (inventaire, nomenclature, upload, historique, exports)
- Gestion prestataires (vue onglets, procédures, dossiers)
- Processus:
  - Cartographie processus
  - Revue processus
  - Détail processus
- Communication, compétences/formation, équipements, réclamations
- Notifications (avec redirections contextuelles)
- Paramètres (profil, sécurité, configuration)

## 3.4 Client B (phase mobile secondaire)

- Dashboard
- Réclamations (CRUD)
- Formulaires de satisfaction
- Notifications
- Profil/support

## 4) Gap analysis: Web vs Prototype mobile existant

Couvert dans `Logi/SMI`:

- Dashboard analytics
- Risques (vue table/matrice)
- Objectifs
- Documents

Non couvert (gaps majeurs):

- Authentification complète (MFA, session, lock screen)
- Multi-site / contexte site actif
- Workflows métier avancés (NC, audits, revue direction, process review)
- Gestion des rôles/permissions
- Notifications + deep links
- Plans d’actions détaillés multi-origines
- Paramètres, sécurité, abonnement/paiement
- Écrans Client B et Superadmin

Conclusion:

- Le prototype donne une direction visuelle.
- La structure fonctionnelle mobile doit être redessinée autour des parcours métier réels du web.

## 5) Architecture d’information mobile recommandée (MVP v1)

## 5.1 Navigation mobile (recommandée)

- Bottom tabs (5 entrées):
  - Accueil
  - Planification
  - Exécution
  - Performance
  - Plus

## 5.2 Contenu de chaque tab

- Accueil:
  - KPI synthèse
  - Actions à traiter (mes actions)
  - Alertes critiques
  - Raccourcis (créer NC, créer risque, planifier audit, ajouter action)
- Planification:
  - Risques & opportunités
  - Objectifs
  - Plans du SM
  - Conformité (obligations)
- Exécution:
  - Processus/cartographie
  - Prestataires
  - Production/prestation
  - Documents opérationnels
- Performance:
  - Surveillance satisfaction/performance
  - Audits internes
  - Revue de direction (incl. synthèse SM)
- Plus:
  - Documents
  - Non-conformités
  - Actions
  - Notifications
  - Sites
  - Profil/paramètres
  - Support

## 5.3 Principes de simplification UX mobile

- Une seule action primaire visible par écran.
- Filtre compact en bottom sheet, pas en barre horizontale surchargée.
- Listes orientées cartes (badge statut, échéance, responsable).
- Modaux longs remplacés par steppers full-screen.
- Tableaux complexes transformés en vues “summary + drill-down”.

## 6) Parcours UX à maquetter en priorité (ordre design)

1. Auth + sélection site + dashboard
2. Risques/opportunités:
   - Liste
   - Détail
   - Création en 2 étapes (infos de base, actions)
3. Objectifs:
   - Liste
   - Détail
   - Ajout action liée
4. Audits:
   - Programme
   - Planification
   - Détail audit
5. Revue de direction:
   - Accueil
   - Synthèse SM avec graphiques
6. Documents:
   - Inventaire
   - Détail document
   - Upload
7. Notifications:
   - Liste
   - Redirection contextuelle

## 7) Spécification design à transmettre au designer

## 7.1 Design tokens (hérités du web)

Palette de base (déjà utilisée):

- Primary: `#5B8DD9`
- Success: `#22C55E`
- Warning: `#F59E0B`
- Error: `#EF4444`
- Info: `#3B82F6`
- Texte principal: `#1E293B`
- Fond secondaire: `#F8FAFC`

Typo:

- Sans-serif moderne (Inter côté web)
- Hiérarchie claire: Title, subtitle, body, caption

Rayons:

- 8px, 12px, 16px

Espacement:

- grille 4px
- padding écran mobile recommandé: 16px

## 7.2 Composants mobiles indispensables

- App shell mobile:
  - Top app bar
  - Bottom tab bar
  - FAB contextuel
- Cards KPI
- Cards list item métier (titre, badges, échéance, responsable)
- Filter sheet
- Search bar sticky
- Stepper full-screen (création/édition)
- Modal confirmation (danger/success)
- Empty states par module
- Error state avec retry
- Skeleton loaders
- Toast/snackbar

## 7.3 États à designer systématiquement

- Loading initial
- Chargement partiel (section)
- Vide sans données
- Vide filtré (aucun résultat)
- Erreur réseau
- Erreur permission (403)
- Succès création/modification
- Mode offline dégradé (à minima visuel)

## 8) Règles métier qui impactent directement le design

- Multi-site:
  - le site actif impacte la donnée partout
  - prévoir un switch site visible et rapide
- Permissions:
  - certaines actions invisibles selon rôle
  - prévoir états lecture seule
- Workflows:
  - risques/objectifs/NC/audits ont des statuts et transitions
  - l’UI doit afficher clairement le statut courant et les actions possibles
- Export:
  - export Excel/PDF fréquent
  - prévoir CTA export et feedback de génération

## 9) Recommandations spécifiques à la “Revue Processus” mobile

Pour rester aligné avec les travaux récents:

- Écran d’entrée:
  - processus groupés en 3 familles/couleurs
- Détail “Revue de : [Nom Processus]”
- Section 1 Identification:
  - RQ (édition conditionnelle)
  - pilote/copilote
  - participants + autres
  - période couverte
  - horaires en lecture
- Sections métier:
  - PIP
  - Risques/Opportunités
  - Objectifs/Activités/Projets
  - Conformité/NC/Satisfaction
  - Leadership/DUERP
- CTA:
  - Voir rapport
  - Clôturer revue

## 10) Livrables attendus du designer (checklist)

- Architecture d’information mobile validée
- User flows (happy path + erreur) sur:
  - Dashboard
  - Risques
  - Objectifs
  - Audits
  - Revue direction
  - Documents
- Design system mobile (tokens + composants)
- Kit d’écrans:
  - 25 à 40 écrans MVP
  - variantes dark/light si prévu
- Prototype interactif (Figma)
- Documentation handoff dev:
  - marges/spacing
  - états
  - transitions
  - règles responsive

## 11) Plan de production design proposé (3 sprints)

Sprint Design 1:

- IA mobile
- Shell navigation
- Dashboard + Risques + Objectifs

Sprint Design 2:

- Audits + Revue direction (incl. synthèse SM)
- Documents + Notifications

Sprint Design 3:

- Modules secondaires (prestataires, communication, compétences, paramétrage)
- Client B mobile
- Harmonisation + QA design

## 12) Préconisation de scope (pragmatique)

MVP mobile recommandé:

- Client A seulement
- 6 domaines prioritaires:
  - Dashboard
  - Risques/opportunités
  - Objectifs/actions
  - Audits
  - Revue direction
  - Documents/notifications

Phase 2:

- Client B
- Superadmin (ou web-only selon stratégie)

---

## Annexe A - Routes de référence utiles au design

- Client A: `frontend/src/modules/clienta/router/index.ts`
- Client B: `frontend/src/modules/clientb/router/index.ts`
- Superadmin: `frontend/src/modules/superadmin/router/index.ts`
- Public: `frontend/src/router/index.ts`

## Annexe B - Prototype mobile existant

- Entrée: `Logi/SMI/App.tsx`
- Layout: `Logi/SMI/MainLayout.tsx`
- Menu: `Logi/SMI/QHSESidebar.tsx`
- Écrans actuels:
  - `Logi/SMI/pages/Dashboard.tsx`
  - `Logi/SMI/pages/planification/RisquesPage.tsx`
  - `Logi/SMI/ObjectifsPage.tsx`
  - `Logi/SMI/pages/support/DocumentsPage.tsx`
