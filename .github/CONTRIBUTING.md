# 🤝 Guide de Contribution - BestQHSE

> Guide pour contribuer au projet BestQHSE de manière efficace et collaborative

---

## 🌿 Stratégie Git Flow

### **Branches Principales**

```
main        → Production (protégée, déploiement auto)
develop     → Intégration continue (protégée, tests requis)
```

### **Branches de Travail**

| Type        | Nom                   | Usage                         | Exemple                         |
| ----------- | --------------------- | ----------------------------- | ------------------------------- |
| **Feature** | `feature/description` | Nouvelle fonctionnalité       | `feature/swot-analysis`         |
| **Bugfix**  | `bugfix/description`  | Correction bug                | `bugfix/stakeholder-validation` |
| **Hotfix**  | `hotfix/description`  | Correction urgente production | `hotfix/security-patch`         |
| **Chore**   | `chore/description`   | Maintenance, dépendances      | `chore/update-dependencies`     |

---

## 🔄 Workflow de Développement

### **1. Créer une Feature**

```bash
# Mettre à jour develop
git checkout develop
git pull origin develop

# Créer votre branche
git checkout -b feature/ma-fonctionnalite

# Travailler...
git add .
git commit -m "feat(module): description claire"

# Pousser régulièrement
git push origin feature/ma-fonctionnalite
```

### **2. Créer une Pull Request**

1. Pusher votre branche sur GitHub
2. Aller sur https://github.com/Begtech24/BestQHSE/pulls
3. Cliquer "New Pull Request"
4. Base: `develop` ← Compare: `feature/ma-fonctionnalite`
5. Remplir le template (auto-chargé)
6. Demander review à 1+ collègue
7. Attendre validation CI (tests passent ✅)

### **3. Après Merge**

```bash
# Supprimer branche locale
git checkout develop
git pull origin develop
git branch -d feature/ma-fonctionnalite

# Supprimer branche remote
git push origin --delete feature/ma-fonctionnalite
```

---

## 📝 Convention de Commits (Conventional Commits)

### **Format Standard**

```
<type>(<scope>): <description>

[corps optionnel]

[footer optionnel]
```

### **Types**

| Type       | Usage                    | Exemple                                      |
| ---------- | ------------------------ | -------------------------------------------- |
| `feat`     | Nouvelle fonctionnalité  | `feat(client-a): add SWOT matrix`            |
| `fix`      | Correction de bug        | `fix(api): resolve 403 on contexts endpoint` |
| `docs`     | Documentation uniquement | `docs: update installation guide`            |
| `style`    | Formatage (pas de code)  | `style: fix indentation`                     |
| `refactor` | Refactorisation          | `refactor(auth): simplify token validation`  |
| `test`     | Tests                    | `test(context): add unit tests for SWOT`     |
| `chore`    | Maintenance              | `chore: update dependencies`                 |
| `perf`     | Performance              | `perf(api): optimize database queries`       |
| `ci`       | CI/CD                    | `ci: add GitHub Actions workflow`            |

### **Scopes Principaux**

```
client-a, client-b, company, superadmin    → Modules métier
api, backend, frontend, ui                  → Couches techniques
auth, permissions, notifications            → Systèmes transverses
db, migrations                              → Base de données
docs                                        → Documentation
```

### **Exemples Concrets**

```bash
# ✅ BON
git commit -m "feat(client-a): add SWOT analysis page

Implements interactive SWOT matrix with drag-and-drop.
Users can now create, edit, and delete SWOT items.

Closes #42"

# ✅ BON (commit simple)
git commit -m "fix(api): resolve 403 error on stakeholders endpoint"

# ✅ BON (breaking change)
git commit -m "feat(auth)!: migrate to Laravel Sanctum

BREAKING CHANGE: Old JWT tokens are no longer valid.
Users need to re-authenticate."

# ❌ MAUVAIS
git commit -m "update"
git commit -m "fix bug"
git commit -m "WIP"
git commit -m "[] commit"
```

---

## ✅ Checklist Avant PR

Avant de créer une Pull Request, vérifier :

### **Code**

- [ ] Code linté (Pint backend / ESLint frontend)
- [ ] Pas de `console.log()` oubliés
- [ ] Pas de code commenté inutile
- [ ] Variables sensibles dans `.env` (jamais en dur)
- [ ] Pas d'imports inutilisés

### **Tests**

- [ ] Tests unitaires passent localement
- [ ] Tests E2E passent (si modifs UI)
- [ ] Testé manuellement sur navigateurs (Chrome, Firefox)

### **Documentation**

- [ ] README mis à jour si nécessaire
- [ ] Commentaires ajoutés pour code complexe
- [ ] Types TypeScript corrects

### **Git**

- [ ] Commits suivent Conventional Commits
- [ ] Branche à jour avec `develop`
- [ ] Pas de merge conflicts

---

## 👀 Code Review Guidelines

### **Pour le Reviewer**

**Checker :**

- ✅ Code respecte les conventions du projet
- ✅ Logique métier correcte
- ✅ Pas de bugs évidents
- ✅ Pas de failles sécurité
- ✅ Performance acceptable
- ✅ Tests couvrent les cas importants

**Feedback constructif :**

```
# ✅ BON
"Bonne idée ! Par contre, cette requête pourrait être optimisée
avec un eager loading. Suggestion :
Context::with('stakeholders')->get()"

# ❌ MAUVAIS
"C'est nul, refais tout"
```

### **Pour l'Auteur**

**Répondre aux commentaires :**

- ✅ Accepter les critiques constructives
- ✅ Expliquer vos choix si nécessaire
- ✅ Faire les corrections demandées
- ✅ Remercier les reviewers

---

## 🚀 Merge vers Production

### **Process Release**

1. **Créer Release Branch**

```bash
git checkout develop
git pull origin develop
git checkout -b release/v1.4.0
```

2. **Préparer Release**

```bash
# Bump version
# backend/composer.json, frontend/package.json

# Mettre à jour CHANGELOG.md
echo "## [1.4.0] - 2026-02-12
### Added
- Module Context ISO (SWOT, PESTEL, Stakeholders)
" >> docs/md/CHANGELOG.md

git commit -am "chore: bump version to 1.4.0"
```

3. **Créer PR vers `main`**

- Review complète par lead dev
- Tests CI passent ✅
- Demo validée par PO

4. **Après Merge**

```bash
git checkout main
git pull origin main
git tag v1.4.0
git push origin v1.4.0

# Merger dans develop aussi
git checkout develop
git merge main
git push origin develop
```

---

## 🔧 Commandes Utiles

### **Tests**

```bash
# Backend
cd backend
php artisan test
php artisan test --filter ContextTest

# Frontend
cd frontend
npm run test
npm run test:coverage

# Lint
cd backend && ./vendor/bin/pint
cd frontend && npm run lint
```

### **Git**

```bash
# Stash temporaire
git stash
git stash pop

# Rebase interactif (nettoyer commits)
git rebase -i HEAD~3

# Amend dernier commit
git commit --amend

# Cherry-pick un commit
git cherry-pick abc123

# Voir diff avant commit
git diff --staged
```

### **Debug**

```bash
# Logs backend
tail -f backend/storage/logs/laravel.log

# Logs frontend (console navigateur)
# Ou npm run dev pour voir logs Vite

# Database
psql -U postgres -d BestQHSE
```

---

## 📞 Support & Questions

- **Documentation :** [docs/INDEX.md](../docs/INDEX.md)
- **Sprint en cours :** [docs/sprint/](../docs/sprint/)
- **Architecture :** [docs/architecture/](../docs/architecture/)

---

## 🎯 Résumé Quick Start

```bash
# 1. Clone & setup
git clone <repo>
cd BestQHSE
./start-all-services.sh

# 2. Créer feature
git checkout develop
git checkout -b feature/ma-feature

# 3. Travailler
# ... code ...
git add .
git commit -m "feat(scope): description"
git push origin feature/ma-feature

# 4. Créer PR sur GitHub
# 5. Review + Merge
# 6. Supprimer branche

git checkout develop
git pull
git branch -d feature/ma-feature
```

---

**Version :** 1.0  
**Dernière mise à jour :** 11 février 2026  
**Contributeurs :** Équipe BestQHSE
