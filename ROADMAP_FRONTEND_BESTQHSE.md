# 📌 GUIDE DE DÉVELOPPEMENT FRONTEND — BESTQHSE (LOGIQUALI)

> **Document destiné au développeur Frontend.**  
> Ce document synthétise **l'ensemble des exigences, composants et modifications d'interface** à réaliser sur l'application frontend Vue 3 / Vuetify, conformément au cahier des charges officiel.

---

## 📑 Sommaire
1. [Règles architecturales & Composants transversaux (Lot 2)](#1-règles-architecturales--composants-transversaux)
2. [Chapitre 4 — Contexte de l'organisme](#2-chapitre-4--contexte-de-lorganisme)
3. [Chapitre 5 — Leadership & Politique](#3-chapitre-5--leadership--politique)
4. [Chapitre 6 — Planification (R&O, DUERP, AES, Objectifs, Plan SM)](#4-chapitre-6--planification)
5. [Chapitre 7 — Support (Fiches de poste, Com, Documents)](#5-chapitre-7--support)
6. [Chapitre 8 — Réalisation (Urgences, Projets, Non-conformités)](#6-chapitre-8--réalisation)
7. [Chapitre 9 & 10 — Revues & Amélioration continue](#7-chapitre-9--10--revues--amélioration-continue)
8. [Matrice de recette Frontend (Checklist pour le développeur)](#8-matrice-de-recette-frontend)

---

## 1. Règles architecturales & Composants transversaux

### 1.1 — `RT-01` Prévisualisation obligatoire avant tout export
* **Fichier existant** : `@/modules/shared/components/PreviewExportModal.vue`
* **Règle** : **Aucun bouton « Télécharger » ou « Exporter » direct dans toute l'application.**  
  Tout clic sur un export (PDF, Word DOCX, Excel) doit ouvrir le composant `PreviewExportModal`, afficher les métadonnées et l'aperçu du document, puis déclencher le téléchargement uniquement sur clic du bouton de confirmation.

### 1.2 — `RT-04` Sélecteur de colonnes configurables
* **Fichier existant** : `@/components/common/AppTable.vue` et `@/modules/clienta/components/DataTable.vue`
* **Spécification** : 
  - Chaque tableau de données doit comporter un bouton de menu déroulant `Colonnes (visibles / total)`.
  - Cases à cocher interactives pour masquer ou afficher chaque colonne.
  - Bouton « Tout afficher » pour réinitialiser.
  - Sécurité : interdire de masquer la dernière colonne visible.
  - Mémoriser le choix de l'utilisateur dans le `localStorage`.

### 1.3 — `RT-05` Colonnes figées & barre de défilement en haut
* **Spécification** : Dans les tableaux larges (Objectifs qualité, DUERP, R&O) :
  - Les colonnes clés (Code, Libellé, Actions) doivent être figées (`position: sticky`).
  - **Afficher une barre de défilement horizontal en haut du tableau en plus de celle du bas** pour un confort de navigation optimal.

### 1.4 — `RT-08` Statuts cliquables
* **Spécification** : Dans toutes les listes et synthèses, cliquer sur un badge/chip de statut (ex : *En retard*, *En cours*, *Réalisé*, *Clôturé*) doit filtrer instantanément le tableau sur ce statut et mettre à jour le compteur.

### 1.5 — `RT-11` Sélecteur multi-normes dynamique
* **Composant à réutiliser** : `@/components/common/NormMultiSelect.vue` (ou champ de formulaire dédié).
* **Règle métier** :
  - Si l'entreprise n'a qu'**une seule norme active** : le champ est **masqué** et la norme est affectée automatiquement.
  - Si l'entreprise a **au moins 2 normes actives** : le champ de sélection multiple des normes devient **visible et obligatoire**.

### 1.6 — `RT-12` Bloc d'action unifié
* **Composant à réutiliser partout** : `@/components/common/StandardActionBlock.vue`
* **Champs obligatoires** :
  1. *Synthèse / Observations* (texte multiligne)
  2. *Décision / Action* (texte court ou titre d'action)
  3. *Responsable* (sélection d'un utilisateur)
  4. *Délai / Échéance* (date picker)
  5. *Statut* (À planifier / En cours / Réalisé / En retard)

### 1.7 — `RT-13` Espace personnel « Mes actions »
* **Page cible** : `@/modules/clienta/pages/actions/MyActions.vue`
* **Spécification** : Accessible depuis le tableau de bord ou la barre de navigation. Affiche **uniquement les actions assignées à l'utilisateur connecté**, tous modules confondus (DUERP, Risques, Objectifs, Revues, NC, Projets), triées par date d'échéance avec filtres par statut et alertes de retard.

---

## 2. Chapitre 4 — Contexte de l'organisme

### 4.1 Enjeux majeurs (`@/modules/clienta/pages/context/SWOTPestel.vue`)
- [ ] **REQ-4.1-01** : Refondre l'affichage des « Enjeux majeurs » : mise en page responsive, badges de criticité contrastés, tri automatique par priorité.
- [ ] **REQ-4.1-02** : Ajouter une carte statistique / graphique « Enjeux majeurs » sur le tableau de bord de la vue d'ensemble du contexte.
- [ ] **REQ-4.1-03** : Connecter le bouton d'export au modal de prévisualisation `RT-01`.

### 4.2 Parties intéressées / PIP (`@/modules/clienta/pages/context/Stakeholders.vue`)
- [ ] **REQ-4.2-01** : Bouton « Prévisualiser » fonctionnel pour les **3 modes de vue** (Grille, Tableau, Actions).
- [ ] **REQ-4.2-02** : Intégrer le sélecteur de colonnes `RT-04` sur le tableau des exigences.
- [ ] **REQ-4.2-03** : Afficher les badges d'échéances et de rappels sur les actions issues des parties intéressées.

### 4.3 Domaine d'application (`@/modules/clienta/pages/context/ApplicationScopeRecap.vue`)
- [ ] **REQ-4.3-01** : Corriger l'aération des textes et la mise en page.
- [ ] **REQ-4.3-02** : Dans le tableau récapitulatif, classer et ordonner strictement les processus selon la hiérarchie officielle :  
  `1. Management / Pilotage` ➔ `2. Réalisation / Chaîne de valeur` ➔ `3. Support / Ressources`.

### 4.4 Processus (`@/modules/clienta/components/processes/ProcessCartographyViewer.vue` & `ProcessDetails.vue`)
- [ ] **REQ-4.4-01 / 02** : Cartographie des processus par catégories ISO avec schéma interactif, visualisable en vue d'ensemble et dans l'onglet détail de chaque processus *(déjà intégré)*.
- [ ] **REQ-4.4-03** : Téléchargement de la fiche processus (PDF/DOCX) précédé de la prévisualisation *(déjà intégré)*.
- [ ] **REQ-4.4-05** : Saisie et restitution des **séquences opérationnelles** (étapes chronologiques) dans la fiche détaillée du processus.

---

## 3. Chapitre 5 — Leadership & Politique

### Politique (`@/modules/clienta/pages/leadership/Policy.vue`)
- [ ] **REQ-5.1-01** : **Titre du menu et de la page 100 % dynamique** :
  - 1 norme Qualité seule ➔ `Politique Qualité`
  - Qualité + Sécurité + Environnement ➔ `Politique QSE`
  - Avec Hygiène ➔ `Politique QHSE`
- [ ] **REQ-5.1-02** : **Remplacement du cadre des objectifs par les Axes Stratégiques** :
  - Supprimer l'ancien « Cadre de définition des objectifs ».
  - Afficher les **Axes stratégiques** issus de la politique d'entreprise.
  - Permettre de rattacher directement les objectifs annuels à ces axes stratégiques.

---

## 4. Chapitre 6 — Planification

### 6.1 Risques & Opportunités (`@/modules/clienta/pages/planning/RisksOpportunities.vue`)
- [ ] **REQ-6.1-01** : Intégrer les **3 modes de vue** (identiques à PIP) :
  1. `grid` : Vue Cartes / Grille synthétique
  2. `list` : Vue Tableau classique
  3. `actions` : Vue détaillée des plans d'action associés
- [ ] **REQ-6.1-02** : Statuts de criticité et d'avancement cliquables pour filtrer (`RT-08`).
- [ ] **REQ-6.1-03** : Séparer l'écran en deux sous-onglets distincts : **« Risques »** et **« Opportunités »**.
- [ ] **REQ-6.1-06** : Bouton d'aide contextuelle ouvrant le **Guide d'utilisation de la matrice des risques**.

### 6.1.1 Volet DUERP (Santé & Sécurité au Travail)
- [ ] **REQ-6.1-D01** : Paramétrage des **Unités de Travail (UT)** : l'entreprise choisit et nomme librement ses UT (par processus, par site ou par atelier).
- [ ] **REQ-6.1-D02** : Vue arborescente : `Unité de travail ➔ Familles de risques ➔ Risques identifiés`.
- [ ] **REQ-6.1-D03** : Types de risques éditables (ajout, modification, masquage) selon les permissions de l'utilisateur.
- [ ] **REQ-6.1-D04** : Échelles dynamiques de **Gravité** et de **Fréquence** (paramétrage des libellés et des coefficients par l'administrateur).
- [ ] **REQ-6.1-D05** : Saisie structurée des actions de prévention rattachées à chaque risque du DUERP.
- [ ] **REQ-6.1-D06** : Workflow de soumission : bouton « Soumettre le DUERP » (*Brouillon ➔ Vérification RQ ➔ Approbation CEO*).
- [ ] **Export DUERP (XLSX)** : Bouton « Exporter le DUERP » avec prévisualisation (`RT-01`) via l'endpoint backend `GET /api/v1/duerp/export-xlsx?site_id=...` (export complet par entreprise et par site au canevas officiel).
- [ ] **REQ-6.1-D08** : Onglet/Module dédié au **Traitement des accidents SST** (déclaration d'accident, analyse de causalité, actions de prévention immédiates et correctives).

### 6.1.2 Volet AES (Aspects Environnementaux Significatifs — ISO 14001)
- [ ] **REQ-6.1-E01** : Module AES : tableau avec colonnes *Activité / Poste | Aspect environnemental | Impact environnemental | Cotation | Significatif (Oui/Non) | Actions de maîtrise*.
- [ ] **Export AES (XLSX)** : Bouton « Exporter la base AES » avec prévisualisation (`RT-01`) via l'endpoint backend `GET /api/v1/environmental-aspects/export-xlsx?site_id=...` (export complet par entreprise et par site selon la matrice ISO 14001).
- [ ] **REQ-6.1-E02** : Guide d'aide contextuelle pour la cotation et l'évaluation des AES.

### 6.2 Objectifs Qualité & Taux d'atteinte (`@/modules/clienta/pages/planning/Objectives.vue`)
- [ ] **REQ-6.2-01** : Association obligatoire d'un objectif à une ou plusieurs normes (`RT-11`) et à un **Axe stratégique** (`REQ-5.1-02`).
- [ ] **REQ-6.2-02 / 03** : Grille mensuelle de Janvier à Décembre :
  - **Plafonnement strict à 100 %** : interdire toute valeur $> 100\,\%$, message d'erreur si l'utilisateur saisit $> 100$.
  - Bouton ou checkbox **« N/A » (Non applicable)** par mois : grise la cellule et l'exclut mathématiquement du calcul.
- [ ] **REQ-6.2-05** : **Colonne « Taux d'atteinte actuel »** :
  - Calcul dynamique : moyenne des mois $\le$ mois courant renseignés et non N/A.
  - Les mois futurs sont ignorés.
- [ ] **REQ-6.2-06** : **Verrouillage mensuel** : les champs de saisie des mois passés passent en lecture seule après le dernier jour du mois calendaire.
- [ ] **REQ-6.2-07** : 4 vues récapitulatives en bas de page :
  1. *Récapitulatif par processus*
  2. *Récapitulatif par axe stratégique*
  3. *Récapitulatif par norme*
  4. *Récapitulatif global du système*
- [ ] **REQ-6.2-08** : Colonnes figées (Titre, Processus, Taux actuel) + double scroll horizontal (`RT-05`).

### 6.3 Plan du Système de Management & Modifications (`@/modules/clienta/pages/plan-sm/index.vue`)
- [ ] **REQ-6.3-01 à 05** : Interface de **Demande de modification du système** :
  - Formulaire de soumission par l'initiateur (motif, périmètre, documents concernés).
  - Suivi du statut : *Brouillon ➔ Vérifié RQ ➔ Approuvé CEO*.
  - Règle d'affichage : l'ancienne version reste affichée tant que le CEO n'a pas validé.
- [ ] **REQ-6.3-06** : **Plan du SM** :
  - Hiérarchie à 3 niveaux : `Activité ➔ Sous-activité (obligatoire) ➔ Action`.
  - **Suppression définitive de la colonne de pondération / poids**.
  - Champs par action : *Libellé, Responsable, Délai, Statut*.
- [ ] **Export Plan du SM (XLSX)** : Bouton « Exporter le Plan du SM » avec prévisualisation (`RT-01`) via l'endpoint backend `GET /api/v1/sm-plan/export-xlsx?site_id=...&year=...` (export complet par entreprise, par site et par année au canevas officiel de Ben).
- [ ] **REQ-6.3-08** : Bouton « Replanifier » sur les actions en retard (accessible uniquement au responsable, N+1, RQ ou CEO).

---

## 5. Chapitre 7 — Support

### 7.2 Fiches de poste & Compétences (`@/modules/clienta/pages/competences/...`)
- [ ] **REQ-7.2-01** : Interface de création et d'édition des fiches de poste.
- [ ] **REQ-7.2-02** : Définition des indicateurs par fiche de poste (séparation nette entre *Compétences Générales* et *Compétences Techniques*).
- [ ] **REQ-7.2-03** : **Double signature électronique** : encadré visuel certifiant la signature du collaborateur ET la validation du CEO avec horodatage.
- [ ] **REQ-7.2-04** : Correction des éventuelles erreurs de chargement / console sur l'écran Compétences.

### 7.3 Communication & Sensibilisation (`@/modules/clienta/pages/communications/...`)
- [ ] **REQ-7.3-01** : Tableau du plan annuel de communication/sensibilisation.
- [ ] **REQ-7.3-02** : Possibilité de replanifier une action échue par les rôles habilités.

### 7.5 Gestion documentaire (`@/modules/clienta/pages/documents/...`)
- [ ] **REQ-7.5-01** : Respect du masque de codification paramétré par entreprise.
- [ ] **REQ-7.5-02** : Onglet dédié pour le RQ et le CEO : **« Documents en attente de vérification / approbation »**.
- [ ] **REQ-7.5-04** : Interface unifiée de dépôt de document : sélection du type (*Procédure, Protocole, Rapport d'activité*), pièce jointe, version, et soumission au workflow.

---

## 6. Chapitre 8 — Réalisation & Opérations

### 8.2 Situations d'urgence (`@/modules/clienta/pages/operations/Emergency.vue`)
- [ ] **REQ-8.2-01** : Tableau 4 colonnes standardisé :  
  `Situation d'urgence | Mesures de préparation & réponse | Responsable | Délai`  
  avec filtres et alertes d'échéances.

### 8.6 Projets (`@/modules/clienta/pages/operations/Projects.vue`)
- [ ] **REQ-8.6-01** : Rattachement de chaque projet à un ou plusieurs processus métier.
- [ ] **REQ-8.6-02** : Champs dédiés : sélection du **Responsable de projet** et multi-sélection de l'**Équipe projet** (`RT-14`).

### 8.7 Non-conformités & Réclamations (`@/modules/clienta/pages/nonconformities/...`)
- [ ] **REQ-8.7-01** : Fiche d'écart / Non-conformité complète :
  - *Description de l'écart, Origine, Processus lié, Norme(s) concernée(s), Criticité*
  - *Analyse des causes (ex: 5 Pourquoi / Ishikawa)*
  - *Correction immédiate & Action corrective pérenne*
  - *Responsable, Délai, Statut (En cours / Clôturée)*
  - *Champ de téléversement de la preuve de clôture*
- [ ] **REQ-8.7-02** : Module distinct de gestion des **Plaintes et Réclamations clients** avec historique et statut.

---

## 7. Chapitre 9 & 10 — Revues & Amélioration continue

### 9.2 Revue de Processus (`@/modules/clienta/pages/management-reviews/ProcessReview.vue`)
L'écran de la revue de processus doit afficher obligatoirement les **9 sections ordonnées** :
1. [ ] **Section 1 : Enjeux** (enjeux majeurs et contexte applicables au processus)
2. [ ] **Section 2 : Synthèse PIP** (synthèse des parties intéressées pertinentes du processus)
3. [ ] **Section 3 : Non-conformités & Réclamations** (compteurs cliquables : NC en cours, clôturées, plaintes clients)
4. [ ] **Section 4 : Objectifs & Activités** (activités réalisées vs non réalisées sur la période)
5. [ ] **Section 5 : Activités menées (Séquences)** (reprise des séquences de la fiche processus avec saisie d'une observation pour chaque séquence)
6. [ ] **Section 6 : Risques & Opportunités** (actions de la période, risques survenus, nouveaux risques déclarés)
7. [ ] **Section 7 : Besoins & Ressources** (bloc standard `RT-12` : Synthèse, Décision/Action, Responsable, Délai)
8. [ ] **Section 8 : Modifications à apporter au système** (besoins d'évolution du SMQ)
9. [ ] **Section 9 : Difficultés rencontrées & Suggestions** (propositions d'actions soumises au visa du RQ)

- [ ] **REQ-9.2-09 (Règle critique)** : **Verrouillage de clôture de la revue** : le bouton « Clôturer la revue » est **désactivé** tant que toutes les actions non réalisées n'ont pas fait l'objet d'une replanification explicite.
- [ ] **REQ-9.2-12** : Génération du **Rapport d'activité de la revue** avec prévisualisation préalable (`RT-01`).

### 9.3 Revue de Direction (`@/modules/clienta/pages/management-reviews/ManagementReview.vue`)
- [ ] **REQ-9.3-01** : Même structure consolidant l'ensemble des revues de processus de l'entreprise pour la période couverte.

### 10 Amélioration Continue (`@/modules/clienta/pages/improvement/...`)
- [ ] **REQ-10-01 / 02** : Formulaire de soumission de suggestion d'amélioration continue : choix de la norme concernée (`RT-11`) et envoi au circuit de validation du RQ.

---

## 8. Matrice de recette Frontend (Checklist pour le développeur)

| Exigence | Intitulé court | Statut à cocher |
|---|---|:---:|
| `RT-01` | Modal de prévisualisation avant tout export | [ ] |
| `RT-04` | Sélecteur de colonnes (cases à cocher dans les listes) | [ ] |
| `RT-05` | Colonnes figées + double barre de défilement horizontal | [ ] |
| `RT-08` | Chips de statut cliquables pour filtrer les listes | [ ] |
| `RT-11` | Sélecteur multi-normes masqué si 1 norme, actif si $\ge 2$ | [ ] |
| `RT-12` | Bloc d'action standardisé réutilisé sur tous les écrans | [ ] |
| `RT-13` | Page « Mes actions » consolidée sur le tableau de bord | [ ] |
| `RT-14` | Équipe projet et Responsable projet dans la fiche projet | [ ] |
| `REQ-4.1` | Affichage des enjeux majeurs et statistiques | [ ] |
| `REQ-4.3` | Ordonnancement strict : Management ➔ Réalisation ➔ Support | [ ] |
| `REQ-4.4` | Cartographie dynamique + Fiche processus téléchargeable | [ ] |
| `REQ-5.1` | Titre Politique dynamique + Remplacement par Axes Stratégiques | [ ] |
| `REQ-6.1` | R&O en 3 vues (Grille, Liste, Actions) + Guide d'aide | [ ] |
| `REQ-6.1-D`| DUERP : UT libres, échelles dynamiques, circuit RQ ➔ CEO | [ ] |
| `REQ-6.1-E`| Module AES avec cotation et guide | [ ] |
| `REQ-6.2` | Objectifs : plafonnement 100 %, option N/A, calcul taux actuel, verrouillage mensuel | [ ] |
| `REQ-6.3` | Plan du SM sans pondération (Activité ➔ Sous-activité ➔ Action) | [ ] |
| `REQ-7.2` | Fiches de poste avec double signature CEO / employé | [ ] |
| `REQ-7.5` | Écran de soumission de documents & liste d'approbation | [ ] |
| `REQ-8.2` | Tableau des situations d'urgence (4 colonnes) | [ ] |
| `REQ-8.7` | Fiche de non-conformité complète avec statut et preuve | [ ] |
| `REQ-9.2` | Revue processus en 9 sections + replanification obligatoire | [ ] |
| `REQ-10` | Suggestions d'amélioration avec sélection de norme | [ ] |

