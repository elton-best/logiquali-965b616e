# Manuel d'utilisation — BestQHSE

Date : 26 mars 2026  
Version : 3.0  
Public : utilisateurs finaux (tous profils)

---

## 1. Introduction

### 1.1 Bienvenue dans BestQHSE

Bienvenue dans **BestQHSE**, votre plateforme de gestion QHSE (Qualité, Hygiène, Sécurité, Environnement) complète et intuitive.

Ce manuel vous guide pas à pas dans l'utilisation de toutes les fonctionnalités de BestQHSE, que vous soyez administrateur, responsable de site, auditeur ou simple utilisateur.

### 1.2 Pourquoi ce manuel

Ce document explique, de façon simple et pratique, ce que chaque type d'utilisateur doit faire dans BestQHSE.

Objectifs :

- ✅ Vous aider à savoir où cliquer
- ✅ Quoi saisir et comment
- ✅ Quand agir et réagir
- ✅ Comment suivre vos résultats QHSE
- ✅ Maîtriser les fonctionnalités avancées

### 1.3 À qui s'adresse ce manuel

Ce manuel s'adresse à :

- **Administrateurs Entreprise** : pilotage global du système QHSE
- **Responsables de Site** : gestion opérationnelle d'un site
- **Responsables Qualité/HSE** : animation des modules spécialisés
- **Auditeurs** : préparation et conduite des audits
- **Propriétaires de Processus** : pilotage de la performance
- **Team Leaders/Opérateurs** : exécution des actions terrain
- **Lecteurs** : consultation des données et rapports

---

## 2. Vue d'ensemble de l'application

### 2.1 Qu'est-ce que BestQHSE?

BestQHSE est une plateforme SaaS de pilotage QHSE qui couvre l'ensemble du système de management :

**Modules principaux** :

- 📋 **Contexte de l'organisme** : parties intéressées, enjeux, SWOT, PESTEL
- 🎯 **Planification** : risques, opportunités, objectifs, indicateurs
- 📚 **Gestion documentaire** : procédures, instructions, formulaires
- 🔍 **Audits** : planification, exécution, rapports
- ⚠️ **Non-conformités** : signalement, traitement, actions correctives
- 📊 **Tableaux de bord** : indicateurs, performance, pilotage
- 👥 **Ressources humaines** : fiches de poste, compétences, formations
- 🔄 **Amélioration continue** : actions, revues de direction

### 2.2 Navigation principale

**Interface utilisateur** :

- **Sidebar gauche** : accès aux modules et sous-modules
- **En-tête** : recherche globale, notifications, profil utilisateur
- **Sélecteur de site** : change le périmètre de travail (multi-sites)
- **Zone centrale** : formulaires, listes, tableaux, graphiques
- **Actions rapides** : boutons d'export, création, filtrage

### 2.3 Architecture technique

**Stack technologique** :

- **Backend** : Laravel 12 (PHP 8.2+) + PostgreSQL
- **Frontend** : Vue 3 + TypeScript + Vuetify 3
- **API** : REST JSON:API standard
- **Authentification** : Laravel Sanctum + MFA optionnel
- **Temps réel** : Laravel Reverb (WebSocket)
- **Exports** : Excel (PhpSpreadsheet), PDF (DomPDF), DOCX (PHPWord)

---

## 3. Premiers pas

### 3.1 Connexion à BestQHSE

**Étapes de connexion** :

1. **Accéder à la plateforme** : Ouvrez votre navigateur et allez sur l'URL fournie par votre administrateur
2. **Saisir vos identifiants** :
   - Email professionnel
   - Mot de passe sécurisé
3. **Validation MFA** (si activée) :
   - Saisir le code à 6 chiffres reçu par email/SMS
   - Ou utiliser votre application d'authentification (Google Authenticator, Authy)
4. **Sélection du site** : Choisissez le site sur lequel vous souhaitez travailler

**Chemin** : `/auth/login`

```
┌─────────────────────────────────────┐
│         BestQHSE                   │
│    Plateforme QHSE                  │
├─────────────────────────────────────┤
│  Email: [________________]          │
│  Mot de passe: [________]           │
│                                     │
│  [ ] Se souvenir de moi             │
│                                     │
│  [    SE CONNECTER    ]             │
│                                     │
│  Mot de passe oublié?               │
└─────────────────────────────────────┘
```

### 3.2 Vérifier le site actif

**Important** : Toujours confirmer le site sélectionné avant de créer/modifier des données.

**Comment changer de site** :

1. Cliquez sur le **sélecteur de site** en haut à droite
2. Choisissez le site dans la liste déroulante
3. Les données affichées se mettent à jour automatiquement

### 3.3 Découvrir le tableau de bord

Après connexion, vous arrivez sur votre **tableau de bord personnalisé** :

**Widgets disponibles** :

- 📊 **Indicateurs clés** : taux de conformité, objectifs, performance
- ⚠️ **Alertes** : actions en retard, audits à venir, NC ouvertes
- 📈 **Graphiques** : évolution des risques, tendances qualité
- 📋 **Tâches** : mes actions assignées, mes validations en attente
- 🔔 **Notifications** : dernières activités, changements importants

### 3.4 Utiliser les listes et filtres

Dans tous les écrans de liste, vous pouvez :

**Actions disponibles** :

- 🔍 **Rechercher** : barre de recherche globale
- 🎯 **Filtrer** : par statut, date, responsable, priorité
- 📊 **Trier** : cliquez sur les en-têtes de colonnes
- 👁️ **Voir le détail** : cliquez sur une ligne
- ✏️ **Modifier** : icône crayon (si autorisé)
- 🗑️ **Supprimer** : icône corbeille (si autorisé)
- 📥 **Exporter** : Excel, PDF, DOCX selon le module

### 3.5 Bonnes pratiques générales

**Pour bien utiliser BestQHSE** :

- ✅ Remplir les champs obligatoires dès la création
- ✅ Affecter un responsable et une échéance pour chaque action
- ✅ Éviter les doublons (vérifier avant de créer)
- ✅ Commenter les changements importants
- ✅ Joindre des preuves (photos, documents)
- ✅ Mettre à jour régulièrement l'avancement
- ✅ Clôturer avec vérification d'efficacité

---

## 4. Qui fait quoi par profil

## 4.1 Administrateur Entreprise

Mission principale : piloter le système QHSE de l'entreprise.

Actions clés :

- paramétrer l'organisation (sites, équipes, référents),
- organiser les modules et standards de travail,
- valider les contenus et arbitrer les priorités,
- suivre l'avancement global des plans d'action.

Rythme recommandé :

- quotidien : valider les éléments en attente,
- hebdomadaire : revue des risques, actions et audits,
- mensuel : revue de direction et performance.

---

## 4.2 Responsable de Site (Site Manager)

Mission principale : exécuter la démarche QHSE sur un site précis.

Actions clés :

- maintenir les données du site à jour,
- suivre les risques/opportunités et les actions du site,
- préparer les audits et la conformité locale,
- escalader les blocages à l'admin entreprise.

Rythme recommandé :

- quotidien : suivi des actions en retard,
- hebdomadaire : mise à jour indicateurs et risques,
- mensuel : bilan site + plan d'amélioration.

---

## 4.3 Responsable Qualité / HSE / Environnement

Mission principale : animer les sous-modules de spécialité et garantir la conformité.

Actions clés :

- enregistrer et évaluer les risques/opportunités,
- mettre à jour les objectifs et indicateurs,
- gérer les audits, non-conformités et actions correctives,
- maintenir les exigences réglementaires et preuves associées.

Rythme recommandé :

- quotidien : qualification des nouveaux événements,
- hebdomadaire : suivi des actions par type,
- mensuel : analyse des tendances et efficacité.

---

## 4.4 Propriétaire de Processus (Process Owner)

Mission principale : piloter la performance d'un processus.

Actions clés :

- documenter le processus et ses interactions,
- identifier risques et opportunités du processus,
- suivre les KPI et proposer des actions,
- tenir les procédures et preuves documentaires à jour.

---

## 4.5 Auditeur

Mission principale : préparer, exécuter et clôturer les audits.

Actions clés :

- planifier les audits,
- conduire l'évaluation terrain,
- consigner les constats et non-conformités,
- suivre les actions issues d'audit jusqu'à clôture.

---

## 4.6 Team Leader / Opérateur

Mission principale : exécuter les actions opérationnelles et remonter les informations terrain.

Actions clés :

- renseigner les tâches assignées,
- signaler les incidents ou écarts,
- joindre les preuves (photos, documents),
- mettre à jour l'état d'avancement.

---

## 4.7 Lecteur

Mission principale : consulter les données et suivre les résultats sans modification.

Actions clés :

- consulter les tableaux de bord,
- consulter documents publiés et indicateurs,
- exporter les vues autorisées.

---

## 5. Fonctionnalités principales à maîtriser

## 5.1 Risques et opportunités

1. Créer un risque ou une opportunité.
2. Renseigner les critères d'évaluation.
3. Ajouter les actions (préventives, correctives, maîtrise si applicable).
4. Affecter responsable + échéance.
5. Suivre priorité/criticité et statut.

## 5.2 Actions

1. Créer une action.
2. Choisir le type d'action.
3. Affecter un responsable.
4. Suivre progression et date cible.
5. Clôturer avec preuve.

## 5.3 Documents et procédures

1. Créer/importer le document.
2. Compléter les métadonnées.
3. Soumettre à validation.
4. Publier et diffuser.
5. Conserver l'historique des versions.

## 5.4 Audits et non-conformités

1. Planifier l'audit.
2. Enregistrer constats.
3. Ouvrir les non-conformités.
4. Déclencher les actions.
5. Vérifier l'efficacité puis clôturer.

---

## 6. Scénarios guidés (pas à pas)

## 6.1 Traiter un risque de bout en bout

1. Ouvrir `Planification > Risques et opportunités`.
2. Créer le risque et compléter l'évaluation.
3. Ajouter plusieurs actions (une ligne par action).
4. Affecter chaque action.
5. Suivre l'avancement jusqu'à clôture.
6. Exporter le registre pour revue.

## 6.2 Créer et publier une procédure

1. Ouvrir `Support > Documents` (ou module métier avec bouton `Ajouter procédure`).
2. Renseigner titre, code, processus lié.
3. Ajouter la pièce jointe.
4. Enregistrer.
5. Vérifier l'apparition dans la liste.
6. Soumettre et valider.

## 6.3 Préparer une revue de direction

1. Vérifier indicateurs, audits, non-conformités, actions.
2. Exporter les rapports nécessaires.
3. Consolider les décisions.
4. Enregistrer le plan d'actions de revue.

---

## 7. Résolution de problèmes courants (FAQ)

| Problème                          | Cause probable           | Solution                                       |
| --------------------------------- | ------------------------ | ---------------------------------------------- |
| Je ne vois pas un module          | Permission ou abonnement | Vérifier le rôle et l'offre active             |
| Je ne peux pas modifier une fiche | Droit en lecture seule   | Demander un rôle avec `create/update`          |
| Je ne vois pas mes données        | Mauvais site sélectionné | Changer le site actif en haut de la navigation |
| Une action n'apparaît pas         | Filtre actif             | Réinitialiser filtres/recherche                |
| Session expirée                   | Inactivité prolongée     | Se reconnecter                                 |

---

## 8. Bonnes pratiques d'utilisation

- travailler avec des intitulés explicites,
- utiliser les pièces jointes comme preuves,
- tenir les échéances réalistes,
- clôturer uniquement avec vérification d'efficacité,
- faire un point hebdomadaire d'équipe sur les actions en retard.

---

## 9. Support et escalade

En cas de blocage :

1. Capturer l'écran + message d'erreur.
2. Noter l'heure, le module et le site.
3. Contacter l'administrateur entreprise ou le support technique.

---

## 10. Résumé opérationnel

Pour bien utiliser BestQHSE :

- sélectionner le bon site,
- saisir des données complètes,
- affecter systématiquement responsables et délais,
- suivre les actions jusqu'à preuve de clôture,
- utiliser les exports pour piloter et décider.

---

## 4. Gestion des SIGLE et Codes d'\u00c9quipements

### 4.1 Qu'est-ce qu'un SIGLE?

Le **SIGLE** est un code d'identification unique de votre entreprise (1-10 caract\u00e8res alphanumériques).

**Exemples** :

- `ACME` pour ACME Corporation
- `CORP24` pour Corporate 2024
- `BEG` pour Best Experts Group

**Utilisation** :

- Pr\u00e9fixe tous les codes d'\u00e9quipements
- Permet l'identification rapide
- Facilite la tra\u00e7abilit\u00e9 multi-sites

### 4.2 Changer le SIGLE de l'entreprise

**Attention** : Cette action est **irr\u00e9vocable** et impacte tous les \u00e9quipements!

**Chemin** : Menu lat\u00e9ral \u2192 **Param\u00e8tres** \u2192 **Entreprise** \u2192 **Modifier SIGLE**

**\u00c9tapes** :

1. **Acc\u00e9der au formulaire** :

   ```
   \u250c\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2510
   \u2502  CHANGER LE SIGLE DE L'ENTREPRISE  \u2502
   \u251c\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2524
   \u2502  SIGLE actuel: ACME                \u2502
   \u2502  Nouveau SIGLE: [________]          \u2502
   \u2502                                     \u2502
   \u2502  Raison du changement:              \u2502
   \u2502  [_____________________________]   \u2502
   \u2502                                     \u2502
   \u2502  \u26a0\ufe0f  ATTENTION: Irr\u00e9vocable!        \u2502
   \u2502  2,847 \u00e9quipements seront recodifi\u00e9s \u2502
   \u2502                                     \u2502
   \u2502  [Annuler]  [Confirmer le changement]\u2502
   \u2514\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2518
   ```

2. **Validation** : Double confirmation requise
3. **Traitement** : 3-5 minutes pour la recodification
4. **Confirmation** : Notification de succ\u00e8s

**Impact** :

- Tous les codes d'\u00e9quipements sont mis \u00e0 jour
- L'historique conserve les anciens codes
- Les rapports sont r\u00e9g\u00e9n\u00e9r\u00e9s automatiquement

### 4.3 Transf\u00e9rer un \u00e9quipement

**Chemin** : Menu lat\u00e9ral \u2192 **\u00c9quipements** \u2192 **Transf\u00e9rer**

**\u00c9tapes** :

1. **S\u00e9lectionner l'\u00e9quipement** :
   - Rechercher par code ou nom
   - Cliquer sur l'\u00e9quipement

2. **Remplir le formulaire** :

   ```
   \u250c\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2510
   \u2502  TRANSF\u00c9RER UN \u00c9QUIPEMENT          \u2502
   \u251c\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2524
   \u2502  Code: ACME-TECH-0001               \u2502
   \u2502  Nom: Serveur Principal             \u2502
   \u2502                                     \u2502
   \u2502  Localisation actuelle: Entrep\u00f4t    \u2502
   \u2502  Nouvelle localisation: [Salle IT\u25bc] \u2502
   \u2502                                     \u2502
   \u2502  Motif du transfert:                \u2502
   \u2502  [Maintenance pr\u00e9ventive_______]   \u2502
   \u2502                                     \u2502
   \u2502  Date du transfert: [21/03/2026]    \u2502
   \u2502                                     \u2502
   \u2502  \u2611 J'ai v\u00e9rifi\u00e9 les informations     \u2502
   \u2502                                     \u2502
   \u2502  [Annuler]  [Enregistrer le transfert]\u2502
   \u2514\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2518
   ```

3. **Confirmation** :

   ```
   \u2705 TRANSFERT ENREGISTR\u00c9
   R\u00e9f\u00e9rence: TRF-202603-00147
   Code pour suivi: ABC123XYZ

   Le transfert sera v\u00e9rifi\u00e9 par l'\u00e9quipe support
   (d\u00e9lai: 1-2 jours)

   [Imprimer re\u00e7u] [Voir historique] [Fermer]
   ```

### 4.4 Historique des transferts

**Chemin** : Menu lat\u00e9ral \u2192 **\u00c9quipements** \u2192 **Historique des transferts**

**Tableau des transferts** :

| Code \u00c9quipement | Motif              | De            | \u00c0   | Date       | Par      | Statut                   |
| -------------------- | ------------------ | ------------- | -------- | ---------- | -------- | ------------------------ |
| ACME-TECH-0001       | Maintenance        | Entrep\u00f4t | Salle IT | 2026-03-20 | Jean D.  | \u2705 V\u00e9rifi\u00e9 |
| ACME-TECH-0045       | R\u00e9affectation | Salle IT      | Bureau   | 2026-03-19 | Marie M. | \u23f3 En attente        |

**L\u00e9gende des statuts** :

- \u2705 **V\u00e9rifi\u00e9** : Approuv\u00e9 par l'\u00e9quipe support
- \u23f3 **En attente** : En cours de v\u00e9rification (1-2 jours)
- \u274c **Rejet\u00e9** : Erreur d\u00e9tect\u00e9e, contacter le support

---

## 5. Historique & Audit

### 5.1 Pourquoi l'historique?

BestQHSE enregistre **chaque action** pour :

- \u2705 **Conformit\u00e9 l\u00e9gale** : RGPD, audits financiers, ISO
- \u2705 **Tra\u00e7abilit\u00e9** : qui a fait quoi, quand, pourquoi
- \u2705 **S\u00e9curit\u00e9** : identifier les acc\u00e8s non autoris\u00e9s
- \u2705 **Analyse** : comprendre les tendances et am\u00e9liorations

### 5.2 Consulter l'historique complet

**Chemin** : Menu lat\u00e9ral \u2192 **Historique** \u2192 **Tous les changements**

**Filtres disponibles** :

- \ud83d\udcc5 **Plage de dates** : Derniers 7/30/90 jours, personnalis\u00e9
- \ud83d\udc64 **Modifi\u00e9 par** : Nom d'utilisateur ou \u00e9quipe
- \ud83c\udff7\ufe0f **Type d'action** : SIGLE change, Transfert, Permission, etc.
- \ud83c\udfea **Site/Localisation** : Filtrer par site sp\u00e9cifique

**R\u00e9sultat** :

```
21 mars 2026, 09:15 | SIGLE Change      | Jean Dupont   | ACME \u2192 CORP24 | Approuv\u00e9
20 mars 2026, 14:20 | Transfert         | Marie Martin  | \u00c9quip#0045    | V\u00e9rifi\u00e9
19 mars 2026, 11:05 | Permission Change | Admin Support | Role: Manager | Approuv\u00e9
```

**D\u00e9tail d'un changement** :

```
D\u00c9TAIL DU CHANGEMENT
\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500
Type:              SIGLE Change
Date/Heure:        21 mars 2026, 09:15:33 UTC
Modifi\u00e9 par:       Jean Dupont (jean.dupont@acme.com)
Entreprise:        ACME Corp
Site:              Si\u00e8ge Paris

Ancien SIGLE:      ACME
Nouveau SIGLE:     CORP24
Raison:            Fusion avec XYZ Inc.
\u00c9quipements affect\u00e9s: 2,847

M\u00e9tadonn\u00e9es:
  - IP source: 192.168.1.100
  - Navigateur: Chrome 120
  - Token: Valide & Expir\u00e9 apr\u00e8s 1h
```

### 5.3 Exporter l'historique

**Pour un rapport d'audit** :

1. Cliquez sur \ud83d\udce5 **Exporter**
2. Choisissez le format : PDF, CSV, Excel
3. S\u00e9lectionnez la plage de dates
4. Cochez **Inclure m\u00e9tadonn\u00e9es** (IP, navigateur)
5. Cliquez **Exporter**

```
Exporter l'historique
\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500
Format:           [PDF \u25bc]
Plage de dates:   [Du 01/01/2026 au 31/12/2026]
Type d'action:    [Tous \u25bc]
Inclure d\u00e9tails:  [\u2713] M\u00e9tadonn\u00e9es (IP, navigateur)

[Exporter] [Annuler]
```

---

## 6. Permissions & Acc\u00e8s

### 6.1 Comprendre vos permissions

Vos **permissions** d\u00e9finissent ce que vous **pouvez faire** dans BestQHSE. Elles d\u00e9pendent de votre **r\u00f4le** attribu\u00e9 par un administrateur.

**V\u00e9rifier mes permissions** :

Cliquez sur \u2139\ufe0f (info) en haut \u2192 **Mes permissions**

```
MES PERMISSIONS
\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500
R\u00f4le:   Coordinateur \u00c9quipements

Actions autoris\u00e9es:
  \u2705 Voir tous les \u00e9quipements
  \u2705 Transf\u00e9rer des \u00e9quipements
  \u2705 Voir l'historique
  \u2705 Exporter rapports
  \u2705 Voir les changements SIGLE

Actions interdites:
  \u274c Cr\u00e9er/Modifier un SIGLE (admin seul)
  \u274c Modifier les permissions utilisateurs
  \u274c Archiver des \u00e9quipements
```

### 6.2 R\u00f4les standards

| R\u00f4le              | Cr\u00e9er SIGLE | Transf\u00e9rer \u00c9quip. | Voir Historique      | G\u00e9rer Utilisateurs |
| ---------------------- | ---------------- | --------------------------- | -------------------- | ----------------------- |
| **Visualiseur**        | \u274c           | \u274c                      | \u2705 (limit\u00e9) | \u274c                  |
| **Coordinateur**       | \u274c           | \u2705                      | \u2705               | \u274c                  |
| **Manager**            | \u274c           | \u2705                      | \u2705               | \u274c                  |
| **Admin SIGLE**        | \u2705           | \u2705                      | \u2705               | \u274c                  |
| **Admin Syst\u00e8me** | \u2705           | \u2705                      | \u2705               | \u2705                  |

### 6.3 Demander plus de permissions

Si vous avez besoin de **plus d'acc\u00e8s** :

1. **\u00c9cran param\u00e8tres** \u2699\ufe0f \u2192 **Demander des permissions suppl\u00e9mentaires**
2. **Remplir le formulaire** :
   ```
   Permission demand\u00e9e:      [S\u00e9lectionner \u25bc]
   Justification:            [Texte libre ________]
   Validit\u00e9 jusqu'au:        [Date ________]
   ```
3. **Soumission** \u2192 Attente approbation admin (1-3 jours)

---

## 7. Qui fait quoi par profil

### 7.1 Administrateur Entreprise

**Mission principale** : Piloter le système QHSE de l'entreprise.

**Actions clés** :

- 🏢 Paramétrer l'organisation (sites, équipes, référents)
- 📋 Organiser les modules et standards de travail
- ✅ Valider les contenus et arbitrer les priorités
- 📊 Suivre l'avancement global des plans d'action
- 🔐 Gérer les utilisateurs et permissions
- 📈 Piloter les indicateurs stratégiques

**Rythme recommandé** :

- **Quotidien** : Valider les éléments en attente, traiter les alertes
- **Hebdomadaire** : Revue des risques, actions et audits
- **Mensuel** : Revue de direction et analyse de performance
- **Trimestriel** : Bilan stratégique et ajustements

**Modules principaux** :

- Paramètres entreprise
- Gestion des sites
- Gestion des utilisateurs
- Tableaux de bord consolidés
- Revues de direction

---

### 7.2 Responsable de Site (Site Manager)

**Mission principale** : Exécuter la démarche QHSE sur un site précis.

**Actions clés** :

- 🏭 Maintenir les données du site à jour
- ⚠️ Suivre les risques/opportunités et les actions du site
- 🔍 Préparer les audits et la conformité locale
- 📢 Escalader les blocages à l'admin entreprise
- 👥 Coordonner les équipes terrain
- 📊 Produire les rapports site

**Rythme recommandé** :

- **Quotidien** : Suivi des actions en retard, incidents
- **Hebdomadaire** : Mise à jour indicateurs et risques
- **Mensuel** : Bilan site + plan d'amélioration
- **Trimestriel** : Revue de performance site

**Modules principaux** :

- Tableau de bord site
- Risques et opportunités (périmètre site)
- Actions et plans d'action
- Non-conformités site
- Indicateurs site

---

### 7.3 Responsable Qualité / HSE / Environnement

**Mission principale** : Animer les sous-modules de spécialité et garantir la conformité.

**Actions clés** :

- 📝 Enregistrer et évaluer les risques/opportunités
- 🎯 Mettre à jour les objectifs et indicateurs
- 🔍 Gérer les audits, non-conformités et actions correctives
- 📚 Maintenir les exigences réglementaires et preuves associées
- 📊 Analyser les tendances et proposer des améliorations
- 🎓 Former et sensibiliser les équipes

**Rythme recommandé** :

- **Quotidien** : Qualification des nouveaux événements
- **Hebdomadaire** : Suivi des actions par type
- **Mensuel** : Analyse des tendances et efficacité
- **Trimestriel** : Bilan conformité réglementaire

**Modules principaux** :

- Risques et opportunités
- Audits et non-conformités
- Gestion documentaire
- Indicateurs QHSE
- Plans d'action

---

### 7.4 Propriétaire de Processus (Process Owner)

**Mission principale** : Piloter la performance d'un processus.

**Actions clés** :

- 📋 Documenter le processus et ses interactions
- ⚠️ Identifier risques et opportunités du processus
- 📊 Suivre les KPI et proposer des actions
- 📚 Tenir les procédures et preuves documentaires à jour
- 🔄 Optimiser le processus en continu
- 👥 Coordonner les acteurs du processus

**Rythme recommandé** :

- **Hebdomadaire** : Suivi des indicateurs processus
- **Mensuel** : Revue de processus et actions
- **Trimestriel** : Analyse d'efficacité et optimisation

**Modules principaux** :

- Cartographie des processus
- Risques processus
- Indicateurs processus
- Documents et procédures
- Actions d'amélioration

---

### 7.5 Auditeur

**Mission principale** : Préparer, exécuter et clôturer les audits.

**Actions clés** :

- 📅 Planifier les audits (internes, externes, certification)
- 🔍 Conduire l'évaluation terrain
- 📝 Consigner les constats et non-conformités
- ✅ Suivre les actions issues d'audit jusqu'à clôture
- 📊 Produire les rapports d'audit
- 🎓 Recommander des améliorations

**Rythme recommandé** :

- **Avant audit** : Préparation 1-2 semaines
- **Pendant audit** : Saisie en temps réel
- **Après audit** : Rapport sous 48h
- **Suivi** : Hebdomadaire jusqu'à clôture des NC

**Modules principaux** :

- Planification des audits
- Grilles d'audit
- Constats et non-conformités
- Rapports d'audit
- Suivi des actions correctives

---

### 7.6 Team Leader / Opérateur

**Mission principale** : Exécuter les actions opérationnelles et remonter les informations terrain.

**Actions clés** :

- ✅ Renseigner les tâches assignées
- ⚠️ Signaler les incidents ou écarts
- 📸 Joindre les preuves (photos, documents)
- 📊 Mettre à jour l'état d'avancement
- 💬 Commenter les difficultés rencontrées
- 🔔 Alerter en cas de blocage

**Rythme recommandé** :

- **Quotidien** : Mise à jour des tâches en cours
- **Hebdomadaire** : Bilan d'avancement
- **Immédiat** : Signalement des incidents

**Modules principaux** :

- Mes tâches
- Signalement d'incidents
- Actions assignées
- Documents de travail

---

### 7.7 Lecteur

**Mission principale** : Consulter les données et suivre les résultats sans modification.

**Actions clés** :

- 👁️ Consulter les tableaux de bord
- 📊 Consulter documents publiés et indicateurs
- 📥 Exporter les vues autorisées
- 📈 Suivre les tendances et résultats

**Rythme recommandé** :

- **À la demande** : Consultation selon besoins

**Modules principaux** :

- Tableaux de bord (lecture seule)
- Documents publiés
- Rapports et exports

---

## 8. Fonctionnalités principales à maîtriser

### 8.1 Risques et opportunités

**Objectif** : Identifier, évaluer et traiter les risques et opportunités.

**Étapes** :

1. **Créer un risque ou une opportunité**
   - Menu → Planification → Risques et opportunités
   - Cliquer sur **+ Nouveau risque** ou **+ Nouvelle opportunité**

2. **Renseigner les informations**

   ```
   ┌─────────────────────────────────────┐
   │  CRÉER UN RISQUE                    │
   ├─────────────────────────────────────┤
   │  Titre: [_______________________]   │
   │  Description: [________________]    │
   │  Processus lié: [Sélectionner ▼]    │
   │  Site: [Sélectionner ▼]             │
   │                                     │
   │  ÉVALUATION                         │
   │  Probabilité: [3 - Moyenne ▼]       │
   │  Impact: [4 - Élevé ▼]              │
   │  Criticité: 12 (Calculée auto)      │
   │                                     │
   │  Responsable: [Sélectionner ▼]      │
   │  Date d'identification: [21/03/26]  │
   │                                     │
   │  [Annuler]  [Enregistrer]           │
   └─────────────────────────────────────┘
   ```

3. **Ajouter les actions de traitement**
   - Actions préventives
   - Actions correctives
   - Actions de maîtrise

4. **Affecter responsable + échéance**
   - Pour chaque action
   - Définir les priorités

5. **Suivre l'avancement**
   - Mettre à jour le statut régulièrement
   - Joindre les preuves de traitement
   - Réévaluer après traitement

**Matrice de criticité** :

| Probabilité ↓ / Impact → | Faible (1) | Moyen (2) | Élevé (3) | Critique (4) |
| ------------------------ | ---------- | --------- | --------- | ------------ |
| **Rare (1)**             | 1 🟢       | 2 🟢      | 3 🟡      | 4 🟡         |
| **Peu probable (2)**     | 2 🟢       | 4 🟡      | 6 🟡      | 8 🟠         |
| **Probable (3)**         | 3 🟡       | 6 🟡      | 9 🟠      | 12 🔴        |
| **Très probable (4)**    | 4 🟡       | 8 🟠      | 12 🔴     | 16 🔴        |

**Légende** :

- 🟢 Risque acceptable (1-2)
- 🟡 Risque à surveiller (3-6)
- 🟠 Risque significatif (8-9)
- 🔴 Risque critique (12-16)

---

### 8.2 Actions

**Objectif** : Créer, suivre et clôturer les actions d'amélioration.

**Types d'actions** :

- 🔧 Action corrective (traiter une NC)
- 🛡️ Action préventive (éviter un risque)
- 📈 Action d'amélioration (optimiser)
- 🔍 Action d'audit (suite à constat)

**Étapes** :

1. **Créer une action**

   ```
   ┌─────────────────────────────────────┐
   │  CRÉER UNE ACTION                   │
   ├─────────────────────────────────────┤
   │  Type: [Corrective ▼]               │
   │  Titre: [_______________________]   │
   │  Description: [________________]    │
   │                                     │
   │  Origine:                           │
   │  ○ Risque  ○ NC  ○ Audit  ● Autre  │
   │                                     │
   │  Responsable: [Jean Dupont ▼]       │
   │  Date cible: [30/04/2026]           │
   │  Priorité: [Haute ▼]                │
   │                                     │
   │  Ressources nécessaires:            │
   │  [_____________________________]   │
   │                                     │
   │  [Annuler]  [Créer l'action]        │
   └─────────────────────────────────────┘
   ```

2. **Suivre la progression**
   - Mettre à jour le % d'avancement
   - Ajouter des commentaires
   - Joindre des preuves

3. **Clôturer avec preuve**
   - Vérifier l'efficacité
   - Joindre les documents justificatifs
   - Valider la clôture

**Statuts d'action** :

- 📝 **Planifiée** : Action créée, pas encore démarrée
- 🔄 **En cours** : Action en cours de réalisation
- ⏸️ **En attente** : Bloquée, en attente de ressources
- ✅ **Terminée** : Action réalisée, en attente de validation
- 🔒 **Clôturée** : Action validée et efficace

---

### 8.3 Documents et procédures

**Objectif** : Gérer le cycle de vie documentaire.

**Types de documents** :

- 📋 Procédures
- 📝 Instructions de travail
- 📄 Formulaires
- 📊 Enregistrements
- 📚 Manuels qualité

**Cycle de vie** :

```
Brouillon → Révision → Validation → Publication → Archivage
   ↓          ↓           ↓            ↓            ↓
 Édition   Relecture   Approbation  Diffusion   Obsolète
```

**Étapes** :

1. **Créer/importer le document**
   - Menu → Support → Documents
   - Cliquer **+ Nouveau document**
   - Télécharger le fichier (PDF, DOCX, XLSX)

2. **Compléter les métadonnées**

   ```
   ┌─────────────────────────────────────┐
   │  NOUVEAU DOCUMENT                   │
   ├─────────────────────────────────────┤
   │  Titre: [_______________________]   │
   │  Code: [PRO-001]                    │
   │  Type: [Procédure ▼]                │
   │  Processus lié: [Production ▼]      │
   │                                     │
   │  Version: [1.0]                     │
   │  Date d'application: [01/04/2026]   │
   │                                     │
   │  Rédacteur: [Marie Martin ▼]        │
   │  Vérificateur: [Jean Dupont ▼]      │
   │  Approbateur: [Admin ▼]             │
   │                                     │
   │  Fichier: [📎 Choisir un fichier]   │
   │                                     │
   │  [Annuler]  [Enregistrer]           │
   └─────────────────────────────────────┘
   ```

3. **Soumettre à validation**
   - Workflow automatique
   - Notifications aux valideurs

4. **Publier et diffuser**
   - Après validation
   - Notification aux utilisateurs concernés

5. **Conserver l'historique des versions**
   - Toutes les versions sont archivées
   - Traçabilité complète

---

### 8.4 Audits et non-conformités

**Objectif** : Planifier, exécuter et suivre les audits.

**Types d'audits** :

- 🏢 Audit interne
- 🔍 Audit externe
- 🏆 Audit de certification
- 🎯 Audit processus
- 🏭 Audit site

**Étapes** :

1. **Planifier l'audit**

   ```
   ┌─────────────────────────────────────┐
   │  PLANIFIER UN AUDIT                 │
   ├─────────────────────────────────────┤
   │  Type: [Interne ▼]                  │
   │  Référentiel: [ISO 9001:2015 ▼]     │
   │  Périmètre: [Site Paris ▼]          │
   │                                     │
   │  Date prévue: [15/04/2026]          │
   │  Durée: [2 jours]                   │
   │                                     │
   │  Auditeur(s):                       │
   │  [+ Ajouter un auditeur]            │
   │                                     │
   │  Audité(s):                         │
   │  [+ Ajouter un audité]              │
   │                                     │
   │  Objectifs:                         │
   │  [_____________________________]   │
   │                                     │
   │  [Annuler]  [Planifier]             │
   └─────────────────────────────────────┘
   ```

2. **Enregistrer les constats**
   - Pendant l'audit
   - Constats positifs et négatifs
   - Photos et preuves

3. **Ouvrir les non-conformités**
   - Pour chaque écart détecté
   - Classifier par gravité

4. **Déclencher les actions**
   - Actions correctives immédiates
   - Actions correctives à moyen terme
   - Actions préventives

5. **Vérifier l'efficacité puis clôturer**
   - Audit de suivi
   - Validation de l'efficacité
   - Clôture de l'audit

**Gravité des NC** :

- 🔴 **Majeure** : Non-respect d'une exigence critique
- 🟠 **Mineure** : Écart ponctuel, impact limité
- 🟡 **Observation** : Point d'amélioration, pas de NC

---

## 9. Scénarios guidés (pas à pas)

### 9.1 Traiter un risque de bout en bout

**Scénario** : Identifier et traiter un risque de panne machine.

**Étapes détaillées** :

1. **Ouvrir le module**
   - Menu → Planification → Risques et opportunités
   - Cliquer **+ Nouveau risque**

2. **Créer le risque**
   - Titre : "Risque de panne machine de production"
   - Description : "La machine X a 15 ans, risque de panne imminente"
   - Processus : Production
   - Site : Usine Lyon

3. **Évaluer le risque**
   - Probabilité : 4 (Très probable)
   - Impact : 4 (Critique - arrêt production)
   - Criticité : 16 (Risque critique 🔴)

4. **Ajouter les actions**

   **Action 1 - Préventive** :
   - Titre : "Maintenance préventive renforcée"
   - Responsable : Technicien maintenance
   - Échéance : 30/03/2026
   - Priorité : Haute

   **Action 2 - Corrective** :
   - Titre : "Commander pièces de rechange critiques"
   - Responsable : Responsable achats
   - Échéance : 15/04/2026
   - Priorité : Haute

   **Action 3 - Maîtrise** :
   - Titre : "Planifier remplacement machine"
   - Responsable : Directeur production
   - Échéance : 31/12/2026
   - Priorité : Moyenne

5. **Suivre l'avancement**
   - Tableau de bord → Mes risques
   - Mettre à jour chaque action
   - Joindre les preuves (bons de commande, rapports maintenance)

6. **Réévaluer après traitement**
   - Nouvelle probabilité : 2 (Peu probable)
   - Nouvel impact : 3 (Élevé)
   - Nouvelle criticité : 6 (Risque à surveiller 🟡)

7. **Exporter le registre**
   - Bouton **Exporter**
   - Format : Excel
   - Pour revue de direction

---

### 9.2 Créer et publier une procédure

**Scénario** : Créer une procédure de contrôle qualité.

**Étapes détaillées** :

1. **Accéder au module**
   - Menu → Support → Documents
   - Ou depuis un processus : Bouton **+ Ajouter procédure**

2. **Créer le document**
   - Cliquer **+ Nouveau document**
   - Type : Procédure
   - Code : PRO-QUA-001
   - Titre : "Procédure de contrôle qualité produits finis"

3. **Renseigner les métadonnées**
   - Processus lié : Contrôle qualité
   - Version : 1.0
   - Date d'application : 01/05/2026
   - Rédacteur : Responsable qualité
   - Vérificateur : Manager qualité
   - Approbateur : Directeur qualité

4. **Télécharger le fichier**
   - Cliquer **📎 Choisir un fichier**
   - Sélectionner le PDF de la procédure
   - Vérifier l'aperçu

5. **Enregistrer en brouillon**
   - Cliquer **Enregistrer**
   - Statut : 📝 Brouillon

6. **Soumettre à validation**
   - Cliquer **Soumettre pour validation**
   - Notifications envoyées automatiquement
   - Statut : 🔄 En révision

7. **Workflow de validation**
   - Vérificateur reçoit notification
   - Vérifie et approuve ou rejette
   - Si approuvé → Approbateur reçoit notification
   - Approbateur valide
   - Statut : ✅ Validé

8. **Publication**
   - Cliquer **Publier**
   - Notification à tous les utilisateurs concernés
   - Statut : 📢 Publié
   - Document accessible dans la bibliothèque

9. **Vérification**
   - Aller dans Documents publiés
   - Vérifier la présence de PRO-QUA-001
   - Télécharger pour vérifier

---

### 9.3 Préparer une revue de direction

**Scénario** : Préparer la revue de direction trimestrielle.

**Étapes détaillées** :

1. **Collecter les données**

   **Indicateurs** :
   - Menu → Tableaux de bord → Indicateurs
   - Exporter les KPI du trimestre

   **Audits** :
   - Menu → Audits → Liste des audits
   - Filtrer : Trimestre Q1 2026
   - Exporter le rapport

   **Non-conformités** :
   - Menu → Non-conformités
   - Filtrer : Statut ouvert + Q1 2026
   - Exporter la liste

   **Actions** :
   - Menu → Actions
   - Filtrer : Toutes actions Q1 2026
   - Exporter avec statuts

2. **Analyser les tendances**
   - Graphiques d'évolution
   - Comparaison avec objectifs
   - Identification des écarts

3. **Préparer le support**
   - Créer présentation PowerPoint
   - Intégrer les exports
   - Ajouter analyses et recommandations

4. **Enregistrer la revue dans BestQHSE**
   - Menu → Revues de direction
   - Cliquer **+ Nouvelle revue**
   - Date : 30/03/2026
   - Participants : [Liste]
   - Ordre du jour : [Détails]

5. **Pendant la revue**
   - Présenter les résultats
   - Discuter des écarts
   - Décider des actions

6. **Enregistrer les décisions**
   - Dans la fiche revue
   - Créer les actions décidées
   - Affecter responsables et échéances

7. **Diffuser le compte-rendu**
   - Joindre le CR à la revue
   - Publier
   - Notification aux participants

---
