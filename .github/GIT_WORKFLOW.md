# 🌿 Git Workflow - Guide Rapide Sprint

> Guide simplifié pour l'équipe pendant le sprint

---

## 🚀 Quick Start

### **Démarrer une tâche**

```bash
# 1. Mettre à jour develop (ou votre branche de base)
git checkout develop
git pull origin develop

# 2. Créer votre branche feature
git checkout -b feature/swot-export-pdf

# 3. Travailler normalement
# ... coder ...
```

### **Sauvegarder son travail**

```bash
# 1. Voir ce qui a changé
git status

# 2. Ajouter les fichiers
git add .

# 3. Commit avec message clair
git commit -m "feat(client-a): add PDF export for SWOT analysis"

# 4. Pousser sur GitHub
git push origin feature/swot-export-pdf
```

### **Daily Sync (chaque soir 17h)**

```bash
# 1. Récupérer le travail des autres
git checkout develop
git pull origin develop

# 2. Intégrer dans votre branche
git checkout feature/swot-export-pdf
git merge develop

# 3. Résoudre conflits si besoin
# ... éditer fichiers en conflit ...
git add .
git commit -m "merge: sync with develop"

# 4. Pousser
git push origin feature/swot-export-pdf
```

---

## 📝 Messages de Commit

### **Format Simple**

```
<type>: <description courte>

[description longue optionnelle]
```

### **Types**

| Emoji | Type | Usage |
|-------|------|-------|
| ✨ | `feat` | Nouvelle fonctionnalité |
| 🐛 | `fix` | Correction bug |
| 📝 | `docs` | Documentation |
| 🎨 | `style` | Formatage, UI |
| ♻️ | `refactor` | Refactorisation |
| ✅ | `test` | Tests |
| 🔧 | `chore` | Maintenance |

### **Exemples**

```bash
# ✅ BON
git commit -m "feat: add SWOT matrix component"
git commit -m "fix: resolve 403 error on contexts API"
git commit -m "docs: update installation guide"

# ❌ ÉVITER
git commit -m "update"
git commit -m "fix"
git commit -m "WIP"
```

---

## 🔧 Commandes Utiles

### **Annuler des changements**

```bash
# Annuler fichiers non commités
git checkout -- fichier.js

# Annuler dernier commit (garde changements)
git reset HEAD~1

# Annuler tout depuis dernier pull
git reset --hard origin/develop
```

### **Stash (mettre de côté temporairement)**

```bash
# Sauvegarder changements en cours
git stash

# Récupérer changements sauvegardés
git stash pop

# Voir liste stash
git stash list
```

### **Voir l'historique**

```bash
# Log simple
git log --oneline -10

# Log graphique
git log --graph --oneline --all -20

# Voir diff avant commit
git diff
```

---

## 🚨 Résolution Conflits

### **Si conflit lors du merge**

```bash
# 1. Git vous avertit
git merge develop
# → CONFLICT in fichier.vue

# 2. Ouvrir fichier en conflit
# Vous verrez:
<<<<<<< HEAD
  Votre code
=======
  Code de develop
>>>>>>> develop

# 3. Choisir quelle version garder (ou combiner)
# Supprimer les marqueurs <<<, ===, >>>

# 4. Marquer comme résolu
git add fichier.vue

# 5. Terminer le merge
git commit -m "merge: resolve conflicts with develop"
```

### **En cas de doute**

```bash
# Annuler le merge en cours
git merge --abort

# Demander de l'aide à l'équipe !
```

---

## 🤝 Collaboration Sprint

### **Règles d'équipe**

1. **Daily sync 17h**
   - Tout le monde merge develop dans sa branche
   - Résolution conflits ensemble si besoin

2. **Commits réguliers**
   - Commiter toutes les 1-2h de travail
   - Ne PAS attendre la fin de la journée

3. **Communication**
   - Si gros changement → prévenir équipe Slack/Teams
   - Si conflit compliqué → appeler les autres

4. **Code review rapide**
   - Review PR en <2h si possible
   - Feedback constructif

---

## 📋 Checklist Daily

**Chaque matin :**
- [ ] `git checkout develop && git pull`
- [ ] `git checkout ma-branche && git merge develop`

**Pendant la journée :**
- [ ] Commits réguliers (1-2h)
- [ ] Messages commit clairs

**Chaque soir (17h) :**
- [ ] `git push origin ma-branche`
- [ ] Sync avec develop
- [ ] Résoudre conflits si besoin

---

## 🆘 En Cas de Problème

### **"J'ai tout cassé"**
```bash
# Revenir à l'état d'origine
git reset --hard origin/ma-branche
```

### **"J'ai commit sur la mauvaise branche"**
```bash
# Annuler dernier commit
git reset HEAD~1

# Changer de branche
git checkout bonne-branche

# Recommiter
git add .
git commit -m "mon message"
```

### **"Je ne comprends pas le conflit"**
```bash
# Annuler le merge
git merge --abort

# Demander de l'aide à l'équipe !
```

---

## 🎯 Résumé Ultra-Rapide

```bash
# Démarrer
git checkout -b feature/ma-tache

# Travailler
git add .
git commit -m "feat: description"
git push origin feature/ma-tache

# Daily sync (17h)
git checkout develop && git pull
git checkout feature/ma-tache && git merge develop
git push

# Créer PR sur GitHub quand terminé
```

---

**Questions ?** Consulter [CONTRIBUTING.md](CONTRIBUTING.md) ou demander à l'équipe !

**Version :** 1.0 Sprint  
**Dernière MAJ :** 11 février 2026
