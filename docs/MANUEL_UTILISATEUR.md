# BestQHSE — Manuel d'Utilisation Utilisateurs Finaux

**Version**: 1.0  
**Date**: 26 mars 2026  
**Public cible**: Personnel, RH, Responsables de site, Coordinateurs équipements

---

## Table des matières

1. [Introduction & Démarrage](#1-introduction--démarrage)
2. [Gestion du SIGLE Entreprise](#2-gestion-du-sigle-entreprise)
3. [Gestion des Transferts d'Équipements](#3-gestion-des-transferts-déquipements)
4. [Historique & Audit](#4-historique--audit)
5. [Permissions & Accès](#5-permissions--accès)
6. [FAQ & Dépannage](#6-faq--dépannage)
7. [Support](#7-support)

---

## 1. Introduction & Démarrage

### Qu'est-ce que BestQHSE ?

**BestQHSE** est une plateforme collaborative de gestion des données d'entreprise, spécialisée dans:

- La **gestion du SIGLE** (identifiant unique d'entreprise)
- Le **suivi des équipements** et leurs transferts entre sites
- L'**historique et audit** de tous les changements (conformité réglementaire)
- La **gestion des permissions** par rôle utilisateur

La plateforme garantit que **tous les changements sont tracés** pour la conformité légale et l'audit interne.

### Configuration requise

- **Navigateurs supportés**: Chrome/Edge 90+, Firefox 88+, Safari 14+
- **Résolution minimum**: 1024×768 (recommandé: 1920×1080)
- **Connexion Internet**: Stable (HTTPS obligatoire)
- **JavaScript**: Activé

### Connexion

1. Accédez à `https://BestQHSE.votreentreprise.com`
2. Entrez votre **email** et **mot de passe**
3. Si MFA activé: Entrez le code reçu par SMS/application
4. Cliquez **Se connecter**

**Oubli de mot de passe?** Cliquez "Mot de passe oublié" et suivez les instructions (e-mail de réinitialisation en 2-3 min).

### Orientation du Tableau de Bord

Après connexion, vous accédez au **dashboard** avec :

| Section                          | Fonction                                                  |
| -------------------------------- | --------------------------------------------------------- |
| **Barre de navigation** (gauche) | Menu principal: SIGLE, Équipements, Historique, Profil    |
| **Bandeau info** (haut)          | Notifications, profil utilisateur, déconnexion            |
| **Widgets principaux**           | Résumé: derniers changements, compteurs SIGLE/équipements |
| **Recherche rapide**             | Trouvez un équipement, un SIGLE, une localisation         |

### Navigation basique

- **Clic sur logo BestQHSE** = retour au dashboard
- **Menu latéral** = accès aux modules principaux
- **Paramètres ⚙️** (coin haut droit) = profil, préférences, déconnexion

---

## 2. Gestion du SIGLE Entreprise

### Qu'est-ce qu'un SIGLE?

Le **SIGLE** (Système d'Identification et Gestion des Localisations Entreprise) est:

- Un **code unique** d'identification de votre entreprise (ex: `ACME`, `CORP24`)
- Utilisé dans le **code complet** de chaque équipement
- Format: **Max 10 caractères alphanumériques**
- Inamovible (une fois défini, très rarement changé)

**Exemple de code équipement**:

```
ACME-TECH-0001-LOC-2024
│    │    │    │   │
└─ SIGLE
        └─────────── Reste du code (catégorie, localisation, année, etc.)
```

### Voir votre SIGLE actuel

**Chemin**: Menu latéral → **SIGLE** → **Vue actuelle**

Vous verrez:

- ✅ SIGLE actif
- 📅 Date de mise en place
- 👤 Dernière modification par (utilisateur)
- 🔍 Nombre d'équipements affectés

### Demander un changement de SIGLE

⚠️ **Les changements de SIGLE sont rares et nécessitent une approbation.**

**Étapes**:

#### Étape 1 — Accédez au formulaire

1. Menu latéral → **SIGLE** → **Demander un changement**
2. Cliquez le bouton **+ Nouveau changement**

#### Étape 2 — Entrez le nouveau SIGLE

```
┌─────────────────────────────────────┐
│ Nouveau SIGLE *                     │
│ [ Nouveau code _______ ]            │
│                                     │
│ Raison du changement (optionnel)   │
│ [ Fusio détails... _______________ ]│
│                                     │
│ Recodifier les équipements?        │
│ [✓] Oui, automatiquement            │
│ [ ] Non, le faire manuellement      │
│                                     │
│ [Annuler]  [Prévisualiser]          │
└─────────────────────────────────────┘

* Format: 1-10 caractères alphanumériques
```

**Explications des champs**:

- **Nouveau SIGLE**: Code de remplacement (ex: `NEWCORP`)
- **Raison**: Contexte optionnel (ex: "Fusion avec XYZ Inc.", "Réorganisation")
- **Recodifier les équipements**:
  - ✅ **Automatique**: Tous les codes équipements sont mis à jour aussitôt
  - ⚠️ **Manuel**: Vous gérez les changements manuellement (avancé)

#### Étape 3 — Prévisualiser l'impact

Cliquez **Prévisualiser** pour voir:

```
APERÇU DU CHANGEMENT
─────────────────────
SIGLE actuel:  ACME
Nouveau SIGLE: NEWCORP

Équipements affectés: 2,847
Exemples de codes:

AVANT                          APRÈS
────────────────────────────  ──────────────────────────
ACME-TECH-CAT-LOC-0001-2024   NEWCORP-TECH-CAT-LOC-0001-2024
ACME-TECH-CAT-LOC-0002-2024   NEWCORP-TECH-CAT-LOC-0002-2024
ACME-TECH-CAT-LOC-0003-2024   NEWCORP-TECH-CAT-LOC-0003-2024
(... 2,844 autres)

Temps estimé: 3-5 minutes
[Annuler] [Confirmer le changement]
```

**À vérifier avant de confirmer**:

- ✅ Les nouveaux codes sont corrects?
- ✅ Le nombre d'équipements est exact?
- ✅ Aucun doublon généré?

#### Étape 4 — Confirmation finale

Une case à cocher de **confirmation légale** apparaît:

```
☐ Je confirme que ce changement de SIGLE est autorisé
  et que tous les équipements doivent être recodifiés.

  Ce changement est irrévocable et sera enregistré
  dans l'historique audit (conformité légale).

[Annuler]  [Confirmer définitivement]
```

**⚠️ Important**: Une fois confirmé, le changement **ne peut pas être annulé** (contact support si urgence).

### Consulter l'historique des changements

**Chemin**: Menu latéral → **SIGLE** → **Historique**

Vous verrez un **tableau chronologique** de tous les SIGLE précédents:

| Date       | Ancien SIGLE | Nouveau SIGLE | Modifié par  | Équipements recod. | Statut      |
| ---------- | ------------ | ------------- | ------------ | ------------------ | ----------- |
| 2024-03-15 | ACME         | CORP24        | Jean Dupont  | 2,847              | ✅ Effectué |
| 2025-01-10 | CORP24       | NEWCORP       | Marie Martin | 2,850              | ✅ Effectué |

**Filtres disponibles**:

- 📅 Plage de dates
- 👤 Modifié par (utilisateur)
- 📊 Nombre d'équipements

---

## 3. Gestion des Transferts d'Équipements

### Qu'est-ce qu'un transfert d'équipement?

Un **transfert** enregistre le **changement de localisation physique** d'un équipement:

- D'une **localisation source** (ex: Entrepôt Paris)
- À une **localisation cible** (ex: Site Marseille)

Le système enregistre **qui**, **quand**, et **pourquoi** l'équipement a bougé (audit trail).

### Quand enregistrer un transfert?

✅ **Utilisez un transfert pour**:

- Relocalisation physique d'équipement
- Réaffectation à un autre département
- Envoi en maintenance externe
- Retour de maintenance
- Réaffectation à un nouvel utilisateur

❌ **N'utilisez PAS un transfert pour**:

- Changement de proprietaire (contact RH)
- Mise à la retraite (utilisez l'archivage)
- Simple changement de code (SIGLE) → utilisez recodification

### Accéder au module de transfert

**Chemin**: Menu latéral → **Équipements** → **Transferts**

Vous verrez:

1. **Liste des équipements** (avec localisation actuelle)
2. **Boutons d'action** (Enregistrer un transfert, Voir historique)
3. **Filtres** (par site, catégorie, état)

### Enregistrer un transfert (Pas à pas)

#### Étape 1 — Sélectionner l'équipement

```
Rechercher un équipement:
┌──────────────────────────────────────┐
│ 🔍 Tapez code ou nom équipement...   │
│                                      │
│ Résultats:                           │
│ ✓ ACME-TECH-0001 Serveur Dell        │
│   Localisation actuelle: Entrepôt    │
│                                      │
│ ✓ ACME-TECH-0002 Switch Cisco        │
│   Localisation actuelle: Salle IT    │
└──────────────────────────────────────┘

[ Sélectionner ]
```

#### Étape 2 — Choisir le motif de transfert

```
Motif du transfert *

[Sélectionner un motif ▼]
  • Relocalisation physique
  • Maintenance préventive
  • Maintenance corrective
  • Réaffectation utilisateur
  • Retour de maintenance externe
  • Autre (préciser)
```

#### Étape 3 — Sélectionner la localisation cible

```
Localisation source:        Localisation cible:
Entrepôt Paris           →  [Sélectionner ▼]
                            • Entrepôt Paris
                            • Entrepôt Marseille
                            • Salle IT Paris
                            • Salle IT Lyon
                            • Bureau Ventes
```

#### Étape 4 — Notes optionnelles

```
Notes supplémentaires (optionnel):
┌──────────────────────────────────────────┐
│ Ex: "Maintenance batterie, retour lundi"  │
│                                          │
│ ___________________________________      │
│ ___________________________________      │
└──────────────────────────────────────────┘

[Annuler]  [Vérifier] [Enregistrer]
```

#### Étape 5 — Vérification avant confirmation

**Écran de vérification** (dernière chance de corriger):

```
VÉRIFICATION DU TRANSFERT
─────────────────────────────
Équipement:        ACME-TECH-0001
Nom:               Serveur Dell
Localisation source: Entrepôt Paris
Localisation cible:  Salle IT Paris
Motif:             Maintenance préventive
Notes:             Remplacement disque dur
Date/Heure:        2026-03-26 14:35:22

[✓] J'ai vérifié les informations
    Les données ci-dessus sont exactes.

[Annuler]  [Enregistrer le transfert]
```

Cliquez **Enregistrer le transfert** → Confirmation ✅

```
✅ TRANSFERT ENREGISTRÉ
Référence: TRF-202603-00147
Code pour suivi: ABC123XYZ

Le transfert a été enregistré et sera vérifié
par l'équipe de support (délai: 1-2 jours).

[Imprimer reçu] [Voir historique] [Fermer]
```

### Voir l'historique des transferts

**Chemin**: Menu latéral → **Équipements** → **Historique des transferts**

Vous verrez un **tableau triable**:

| Code Équipement | Motif         | De       | À        | Date       | Par      | Statut        |
| --------------- | ------------- | -------- | -------- | ---------- | -------- | ------------- |
| ACME-TECH-0001  | Maintenance   | Entrepôt | Salle IT | 2026-03-20 | Jean D.  | ✅ Vérifié    |
| ACME-TECH-0045  | Réaffectation | Salle IT | Bureau   | 2026-03-19 | Marie M. | ⏳ En attente |

**Colonnes clés**:

- ✅ **Vérifié** = Approuvé par l'équipe support
- ⏳ **En attente** = En cours de vérification (1-2 jours)
- ❌ **Rejeté** = Erreur = Contacter support

---

## 4. Historique & Audit

### Pourquoi l'historique?

BestQHSE enregistre **chaque action** pour:

- ✅ **Conformité légale** (RGPD, audits financiers)
- ✅ **Traçabilité** (qui a fait quoi, quand, pourquoi)
- ✅ **Sécurité** (identifier les accès non autorisés)

### Consulter l'historique complet

**Chemin**: Menu latéral → **Historique** → **Tous les changements**

**Filtres disponibles**:

- 📅 **Plage de dates** (ex: "Derniers 30 jours")
- 👤 **Modifié par** (nom d'utilisateur)
- 🏷️ **Type d'action** (SIGLE change, Transfert, Permission change, etc.)
- 🏪 **Site/Localisation**

**Résultat**:

```
21 mars 2026, 09:15 | SIGLE Change      | Jean Dupont     | ACME → CORP24 | Approuvé
20 mars 2026, 14:20 | Transfert          | Marie Martin    | Équip#0045    | Vérifié
19 mars 2026, 11:05 | Permission Change  | Admin Support   | Role: Manager | Approuvé
```

**Cliquez sur une ligne** pour voir les **détails complets**:

```
DÉTAIL DU CHANGEMENT
──────────────────────
Type:              SIGLE Change
Date/Heure:        21 mars 2026, 09:15:33 UTC
Modifié par:       Jean Dupont (jean.dupont@acme.com)
Entreprise:        ACME Corp
Site:              Siège Paris

Ancien SIGLE:      ACME
Nouveau SIGLE:     CORP24
Raison:            Fusion avec XYZ Inc.
Équipements affectés: 2,847

Métadonnées:
  - IP source: 192.168.1.100
  - Navigateur: Chrome 120
  - Token: Valide & Expiré après 1h
```

### Exporter l'historique

Besoin d'un **rapport pour audit**?

**Cliquez**: 📥 **Exporter** (format disponible: PDF, CSV, Excel)

```
Exporter l'historique
─────────────────────
Format:           [PDF ▼]
Plage de dates:   [Du 01/01/2026 au 31/12/2026]
Type d'action:    [Tous ▼]
Inclure détails:  [✓] Métadonnées (IP, navigateur)

[Exporter] [Annuler]
```

---

## 5. Permissions & Accès

### Bah, c'est quoi mes permissions?

Vos **permissions** définissent ce que vous **pouvez faire** dans BestQHSE. Elles dépendent de votre **rôle** attribué par un administrateur.

**Cliquez sur ⓘ (info) en haut → Mes permissions**:

```
MES PERMISSIONS
──────────────────
Rôle:   Coordinateur Équipements

Actions autorisées:
  ✅ Voir tous les équipements
  ✅ Transférer des équipements
  ✅ Voir l'historique
  ✅ Exporter rapports
  ✅ Voir les changements SIGLE

Actions interdites:
  ❌ Créer/Modifier un SIGLE (admin seul)
  ❌ Modifier les permissions utilisateurs
  ❌ Archiver des équipements
```

### Rôles standards

| Rôle              | Créer SIGLE | Transférer Équip. | Voir Historique | Gérer Utilisateurs |
| ----------------- | ----------- | ----------------- | --------------- | ------------------ |
| **Visualiseur**   | ❌          | ❌                | ✅ (limité)     | ❌                 |
| **Coordinateur**  | ❌          | ✅                | ✅              | ❌                 |
| **Manager**       | ❌          | ✅                | ✅              | ❌                 |
| **Admin SIGLE**   | ✅          | ✅                | ✅              | ❌                 |
| **Admin Système** | ✅          | ✅                | ✅              | ✅                 |

### Demander plus de permissions

Si vous besoin **plus d'accès**:

1. **Écran paramètres** ⚙️ → **Demander des permissions supplémentaires**
2. **Formulaire**:
   ```
   Permission demandée:      [Sélectionner ▼]
   Justification commerciale: [Texte libre ________]
   Validité jusqu'au:        [Date ________]
   ```
3. **Submission** → Attente approbation admin (1-3 jours)

---

## 6. FAQ & Dépannage

### SIGLE — Questions fréquentes

**Q1: Pourquoi je ne peux pas modifier le SIGLE?**  
A: Seuls les **admins SIGLE** ont le droit. Contactez votre responsable informatique.

**Q2: Peut-on revenir à l'ancien SIGLE après changement?**  
A: ❌ **Non, c'est irrévocable**. Contact support si erreur critique.

**Q3: Combien de temps prend le changement de SIGLE?**  
A: Habituellement **3-5 minutes** pour la recodification (dépend du nombre d'équipements).

**Q4: Les anciens codes d'équipement sont-ils conservés?**  
A: ✅ **Oui**, ils sont archivés dans l'historique pour la traçabilité audit.

### Équipements — Questions fréquentes

**Q5: Pourquoi mon transfert is "En attente"?**  
A: L'équipe support **vérifie** les données. Délai normal: 1-2 jours.

**Q6: Qui peut transférer des équipements?**  
A: Utilisateurs avec rôle **Coordinateur** ou supérieur.

**Q7: Je me suis trompé sur un transfert. Peut-on l'annuler?**  
A: ✅ **Oui**, tant qu'il est **"En attente"** (contact support après vérification).

**Q8: Combien de transferts je peux faire par jour?**  
A: ∞ Illimité (mais throttlé à 60 req/minute pour éviter les abus).

### Historique & Audit

**Q9: Comment j'exporte un rapport d'audit pour mes auditeurs?**  
A: Menu → **Historique** → **Exporter** (PDF/CSV). Includes métadonnées légales.

**Q10: Qui peut accéder mon historique?**  
A: Vous-même + admins + auditeurs externes autorisés (selon rôle).

### Messages d'erreur courants

| Message                 | Cause                      | Solution                                        |
| ----------------------- | -------------------------- | ----------------------------------------------- |
| "Paramètres invalides"  | Données mal formatées      | Vérifiez longueur max codes (10 car.) et format |
| "Permission refusée"    | Rôle insuffisant           | Demandez accès (voir section 5)                 |
| "Ressource non trouvée" | Code équipement inexistant | Double-check le code, utilisez recherche        |
| "Rate limit exceeded"   | Trop de requêtes (>60/min) | Attendez 1 min, réessayez                       |

---

## 7. Support

### Besoin d'aide?

**Canaux de support**:

1. **😊 In-app Help** (question mark ⓘ coin haut-droit)
   - FAQ intégré
   - Tutoriels vidéo
   - Chat support (réponse: 1-2h)

2. **📧 Email Support**
   - `support@BestQHSE.com`
   - Temps de réponse: 24h (jours ouvrés)
   - Sujet requis: Indiquez votre entreprise + type d'issue

3. **☎️ Téléphone** (heures de bureau: 9h-18h CET)
   - `+33 1 XX XX XX XX`
   - Menu: Appuyez 1 pour SIGLE, 2 pour Équipements, 3 pour autre

4. **🏠 Centre d'aide en ligne**
   - `https://help.BestQHSE.com`
   - Actualité système, statut incidents

### Signaler un bug

Vous avez trouvé une erreur?

1. **Écran Paramètres** ⚙️ → **Signaler un bug**
2. **Remplissez le formulaire**:
   - Titre: "Transfert ne sauvegarde pas"
   - Description: Décrivez ce que vous avez fait
   - Étapes à reproduire: "1. Cliquez Transfert... 2. Remplissez... 3. Erreur!"
   - Capture d'écran: Attachez si possible

3. **Référence de ticket** = Reçue par email (traçage support)

### Demander une feature

Vous avez une idée d'amélioration?

1. **Paramètres** ⚙️ → **Demander une feature**
2. **Décrivez** l'idée et le **bénéfice**
3. **Vote** communautaire: D'autres utilisateurs peuvent +1 votre demande
4. **Priorisation**: Top features considérées pour prochaines releases

---

## Glossaire

- **SIGLE**: Code d'identification unique d'entreprise (1-10 caractères)
- **Code Complet**: Code total équipement incluant SIGLE (ex: `ACME-TECH-001`)
- **Localisation**: Site physique (Entrepôt, Bureau, Salle IT, etc.)
- **Transfert**: Changement de localisation physique d'équipement
- **Audit Trail**: Historique immuable de tous les changements (conformité)
- **Rôle**: Ensemble de permissions attribuées à un utilisateur
- **RGPD/Conformité**: Exigences légales de traçabilité des données
- **Token MFA**: Code de vérification pour authentification multi-facteurs

---

## Derniers mots

📌 **BestQHSE est conçu pour être simple et sûr.** Tous les changements sont tracés, approuvés, et auditables.

Si vous avez une question qui n'est pas dans ce manuel:

1. ✅ Consultez d'abord la **FAQ** ci-dessus
2. 💬 Utilisez le **chat support in-app**
3. 📧 **Emailez** support@BestQHSE.com

**Merci d'utiliser BestQHSE!** 🚀

---

_© 2026 BestQHSE. Tous droits réservés. Dernière mise à jour: 26 mars 2026_
