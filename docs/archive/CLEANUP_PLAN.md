# 🗑️ PLAN DE NETTOYAGE - FICHIERS INUTILES

**Date:** 9 février 2026  
**Objectif:** Nettoyer les fichiers de documentation temporaires/obsolètes

---

## 📊 ANALYSE

**Fichiers MD à la racine:** 98 fichiers  
**Fichiers temporaires identifiés:** ~50 fichiers

---

## 🔍 CATÉGORIES DE FICHIERS À SUPPRIMER

### 1. Sessions de développement (OLD - Historique)

```
SESSION_COMPLETE_02_FEV_2026.md
SESSION_COMPLETE_03_FEV_2026.md
SESSION_COMPLETE_04_FEV_2026.md
SESSION_INTEGRATION_VUE_09_FEV.md
SESSION1_NOTIFICATIONS_MVP_COMPLETE.md
SESSION3_V2_ACTIONS_COMPLETE.md
SESSION_NOTIFICATIONS_BROADCAST_04_FEV.md
SESSION_04_FEV_2026.md
```

### 2. Corrections/Bugfixes (OLD - Résolus)

```
CORRECTIONS_FINALES_02_FEV.md
CORRECTIONS_FINALES_03_FEV.md
CORRECTIONS_FINALES_04_FEV.md
CORRECTIONS_INSCRIPTION_FINALE.md
CORRECTIONS_NAVIGATION_ADMIN.md
CORRECTIONS_AUTH_DESIGN_LOGIN.md
CORRECTIONS_SITES_ABONNEMENTS_PROCESSUS_04_FEV.md
CORRECTION_403_AUDITS_04_FEV.md
CORRECTION_FRONTEND_PERMISSIONS.md
```

### 3. Debug/Fix (OLD - Résolus)

```
DEBUG_COLLABORATEURS_DECONNEXION.md
EMAIL_DEBUG_RESOLUTION.md
FIX_LOCK_SCREEN_API.md
FIX_NOTIFICATION_IMPORTS.md
FIX_USER_PERMISSIONS_FK.md
```

### 4. Améliorations (OLD - Implémentées)

```
AMELIORATIONS_AUTH_UI_SITES_04_FEV.md
AMELIORATIONS_AUTH_UI_SMI.md
```

### 5. Implémentations (OLD - Terminées)

```
IMPLEMENTATION_VERROUILLAGE_SESSION.md
IMPLEMENTATION_PROCESSUS_FINAL.md
IMPLEMENTATION_PROCESSUS_PROGRESS.md
VERROUILLAGE_SESSION_RESUME.md
STRUCTURE_VERROUILLAGE_SESSION.md
```

### 6. Tests (OLD - Archivés)

```
TESTS_BACKEND_FIXES_COMPLETE.md
TESTS_STATUS_RAPPORT.md
TEST_NOTIFICATIONS_RAPPORT_FINAL.md
TEST_NOTIFICATIONS_RESULTS.md
```

### 7. Analyses (OLD - Versions obsolètes)

```
ANALYSE_FICHE_PROCESSUS_OLD.md
```

### 8. Anciens plans (OLD - Remplacés)

```
PLAN_CORRECTIONS_FINALES.md
PLAN_HARMONISATION_NOTIFICATIONS.md
PLAN_INTEGRATION_VUE_LOGI.md (remplacé par ROADMAP)
```

### 9. Fichiers all_tests/ (OLD - Sessions passées)

```
all_tests/SESSION_*.md
all_tests/CORRECTIONS_*.md
all_tests/DEBUG_*.md
all_tests/FIX_*.md
```

---

## ✅ FICHIERS À CONSERVER (Documentation actuelle)

### Documentation Sprint actuel

```
✅ SPRINT_5_JOURS_PLAN_AGILE.md
✅ RESUME_SPRINT_5_JOURS.md
✅ INDEX_SPRINT_5_JOURS.md
✅ KANBAN_SPRINT_5_JOURS.md
✅ DEV1_FRONTEND_LEAD_TASKS.md
✅ DEV2_BACKEND_LEAD_TASKS.md
✅ DEV3_FULLSTACK_TASKS.md
✅ ROADMAP_4_MODULES_PRIORITAIRES.md
✅ TEMPLATE_REPLICATION_MODULES.md
```

### Documentation architecture/conception

```
✅ ANALYSE_COMPLETE_MODULES.md
✅ ANALYSE_INTEGRATION_LOGI_CLIENTA.md
✅ ANALYSE_FICHE_PROCESSUS.md
✅ CONCEPTION_MODULES_NC_ACTIONS_ISO.md
✅ QUESTIONS_DECISIONS_CLES.md
✅ RESUME_EXECUTIF_INTEGRATION.md
✅ RECOMMANDATIONS_TECHNIQUES_INTEGRATION.md
```

### Documentation modules livrés

```
✅ MODULE_AUDITS_LIVRAISON_COMPLETE.md
✅ LIVRAISON_MODULE_AUDITS.md
✅ LIVRAISON_MODULE_PROCESSUS.md
✅ README_AUDIT_MODULE.md
✅ README_NC_ACTIONS.md
```

### Documentation systèmes

```
✅ SYSTEME_NOTIFICATIONS_COMPLETE.md
✅ SYSTEME_PERMISSIONS.md
✅ USER_NOTIFICATIONS_SYSTEM.md
✅ NOTIFICATION_SYSTEM_FINAL_REPORT.md
```

### Guides

```
✅ GUIDE_TESTS_NOTIFICATIONS.md
✅ GUIDE_TEST_MODULE_AUDITS.md
✅ GUIDE_UTILISATION_MODULE_PROCESSUS.md
✅ SERVEURS_GUIDE_DEMARRAGE.md
```

### Index/État

```
✅ INDEX_DOCUMENTATION_INTEGRATION.md
✅ ETAT_PROJET_COMPLET_02_FEV_2026.md
✅ STATUS_MODULES_IMPLEMENTATION.md
```

---

## 🗑️ COMMANDE DE NETTOYAGE

**⚠️ ATTENTION: Cette commande supprimera définitivement les fichiers !**

```bash
# Créer un backup avant suppression
mkdir -p /tmp/backup_BestQHSE_md
cp -r /mnt/projets/Projets/Best_Experts_Group/BestQHSE/*.md /tmp/backup_BestQHSE_md/

# Supprimer fichiers temporaires (SESSION, CORRECTIONS, DEBUG, FIX)
cd /mnt/projets/Projets/Best_Experts_Group/BestQHSE

# Sessions
rm -f SESSION_COMPLETE_*.md
rm -f SESSION_INTEGRATION_*.md
rm -f SESSION_NOTIFICATIONS_*.md
rm -f SESSION1_*.md
rm -f SESSION3_*.md
rm -f SESSION_04_*.md

# Corrections
rm -f CORRECTIONS_*.md
rm -f CORRECTION_*.md

# Debug/Fix
rm -f DEBUG_*.md
rm -f FIX_*.md
rm -f EMAIL_DEBUG_*.md

# Améliorations
rm -f AMELIORATIONS_*.md

# Implémentations
rm -f IMPLEMENTATION_VERROUILLAGE_*.md
rm -f IMPLEMENTATION_PROCESSUS_*.md
rm -f VERROUILLAGE_SESSION_*.md
rm -f STRUCTURE_VERROUILLAGE_*.md

# Tests
rm -f TESTS_BACKEND_*.md
rm -f TESTS_STATUS_*.md
rm -f TEST_NOTIFICATIONS_*.md

# Analyses obsolètes
rm -f ANALYSE_FICHE_PROCESSUS_OLD.md

# Plans obsolètes
rm -f PLAN_CORRECTIONS_*.md
rm -f PLAN_HARMONISATION_*.md

# Fichiers all_tests/
rm -f all_tests/SESSION_*.md
rm -f all_tests/CORRECTIONS_*.md
rm -f all_tests/DEBUG_*.md
rm -f all_tests/FIX_*.md
rm -f all_tests/RAPPORT_*.md

# Fichiers backend/
rm -f backend/SESSION_*.md
rm -f backend/FINAL_*.md
```

---

## 📈 ESTIMATION GAIN D'ESPACE

**Avant nettoyage:** ~98 fichiers MD  
**Après nettoyage:** ~35 fichiers MD (documentation utile)  
**Fichiers supprimés:** ~63 fichiers  
**Gain:** ~65% de réduction

---

## ✅ VALIDATION

Fichiers à supprimer :

- [ ] Sessions de développement (8 fichiers)
- [ ] Corrections/Bugfixes (9 fichiers)
- [ ] Debug/Fix (5 fichiers)
- [ ] Améliorations (2 fichiers)
- [ ] Implémentations (5 fichiers)
- [ ] Tests (4 fichiers)
- [ ] Analyses obsolètes (1 fichier)
- [ ] Plans obsolètes (3 fichiers)
- [ ] all_tests/ (20+ fichiers)
- [ ] backend/ (6+ fichiers)

**Total estimé:** ~63 fichiers temporaires/obsolètes

---

**Status:** En attente validation avant suppression  
**Backup:** /tmp/backup_BestQHSE_md/
