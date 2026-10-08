# ADDENDUM - PROJET MOBILE FLUTTER EXISTANT (LOGIQUALI-MOBILE)

Date: 20 avril 2026  
Contexte: Validation du projet mobile réel situé dans `/home/asus/Documents/projet/LOGIQUALI-MOBILE`

## 1) Ce que j’ai confirmé

- Le mobile existe déjà en Flutter (multi-plateforme Android/iOS/Web/Desktop).
- Fichiers clés trouvés:
  - `pubspec.yaml`
  - `lib/main.dart`
  - `lib/config/main_wrapper.dart`
  - `lib/config/api_config.dart`
  - `lib/Users/*` (auth, login, forgot)
  - `lib/features/*` (admin, auditor, employee, client_b, notifications, settings)
- Le projet a déjà:
  - Auth API (`/auth/login`, `/auth/mfa/verify`, `/auth/me`, etc.)
  - Gestion token via `shared_preferences`
  - Navigation par rôle via `MainWrapper` + `BottomNavigationBar`

## 2) Écrans déjà implémentés côté Flutter

### Rôles supportés

- `employee`
- `clientb`
- `auditor`
- `manager`
- `admin`

### Features présentes

- Admin:
  - `home_screen.dart`
  - `processus_screen.dart`
  - `complaints_screen.dart`
  - `reports_screen.dart`
- Auditor:
  - `home_screen.dart`
  - `audit_list_screen.dart`
  - `planing_screen.dart`
  - `quick_actions_screen.dart`
  - `reports_screen.dart`
- Employee:
  - `home_screen.dart`
  - `non_conformite_screen.dart`
  - `actions_list_screen.dart`
  - `pac_screen.dart`
  - `processus.dart`
  - `processus_details.dart`
- Client B:
  - `home_screen.dart`
  - `plaintes_screen.dart`
  - `reclamations_screen.dart`
  - `complaint_home_screen.dart`
  - `chatbot.dart`
- Transversal:
  - `notifications/notifications.dart`
  - `settings/setting_screen.dart`
  - `layouts/*` (appbar, drawer, bottom bar)

## 3) Écart vs application web actuelle

Le web est nettement plus riche, notamment sur Client A.

### Manques majeurs côté Flutter (par rapport au web)

- Dashboard entreprise avancé (KPI + widgets métiers).
- Risques & opportunités complets (liste, détail, actions par type, matrice, exports).
- Objectifs qualité complets (actions multi-lignes, export structuré).
- Audits internes ISO complets (programme -> planification -> audit -> évaluation auditeurs).
- Revue de direction (incluant synthèse SM ISO 9001 §9.3.2 b, c1, c2, c4, c7, e).
- Information documentée (inventaire, nomenclature, traçabilité codification).
- Module prestataires (tabs + procédures + dossiers).
- Notifications intelligentes avec redirections exactes.
- Gestion multi-site avancée (site selector global cohérent sur tous écrans).
- Permissions granulaires alignées avec le web.

## 4) Ce que cela implique pour le designer

Le designer ne doit pas partir de zéro:

- Réutiliser les patterns Flutter existants (BottomNavigationBar, cards, listes).
- Harmoniser avec les codes couleurs/style du web (bleu `#5B8DD9`, états success/warning/error).
- Passer d’une logique “écrans isolés” à une logique “parcours métier”.

## 5) Plan UX mobile recommandé (sur base Flutter actuelle)

## Sprint M1 (fondations)

- Refonte shell navigation:
  - Tabs: `Accueil`, `Planification`, `Exécution`, `Performance`, `Plus`
- Standardiser composants:
  - cards KPI, cards action, filtres en bottom sheet, états empty/error/loading
- Aligner thèmes/text styles avec design web

## Sprint M2 (coeur métier)

- Risques & opportunités (MVP complet mobile)
- Objectifs + actions associées
- Non-conformités + actions correctives

## Sprint M3 (performance ISO)

- Audits internes (programme + planification + suivi)
- Revue de direction:
  - écran accueil
  - écran synthèse SM (graphiques)

## Sprint M4 (support & documentaire)

- Information documentée (inventaire + nomenclature + historique)
- Prestataires (vue tabulaire mobile et formulaires stepper)
- Notifications/redirections

## 6) Spécification technique à préserver côté Flutter

- API base:
  - `lib/config/api_config.dart`
- Session/token:
  - `shared_preferences` (`auth_token`)
- Rôles:
  - `lib/utils/user_role.dart`
- Point d’orchestration:
  - `lib/config/main_wrapper.dart`

## 7) Recommandations immédiates (très concrètes)

1. Geler la structure actuelle des rôles et valider la matrice de permissions mobile cible.
2. Définir le MVP mobile officiel:
   - Dashboard + Risques + Objectifs + NC + Audits + Revue Direction.
3. Designer:
   - produire d’abord le “shell + 6 écrans maîtres” avant les variantes.
4. Dev Flutter:
   - créer un module `features/risk_opportunities` + `features/objectives` alignés API web.
5. QA:
   - écrire checklists par parcours (création, édition, statut, filtre, export).

---

## Référence

Ce document complète:

- `docs/generated/BRIEF_DESIGN_MOBILE_LOGIQUALI.md`
- `docs/generated/INVENTAIRE_ECRANS_LOGIQUALI.md`

