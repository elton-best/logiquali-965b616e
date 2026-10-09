# 🧪 Scripts de Test - BestQHSE Backend

Ce dossier contient tous les scripts de test pour le backend BestQHSE.

## 📋 Liste des scripts

### 1️⃣ `01_test_migrations.sh`

**Objectif:** Tester l'exécution de toutes les migrations

**Ce qui est testé:**

- Reset complet de la base de données
- Exécution de toutes les migrations
- Vérification du statut des migrations

**Commande:**

```bash
./local_tests/01_test_migrations.sh
```

---

### 2️⃣ `02_test_seeders.sh`

**Objectif:** Tester le seeding des données initiales

**Ce qui est testé:**

- Création des 88 permissions
- Création du Super Admin
- Vérification des données créées

**Commande:**

```bash
./local_tests/02_test_seeders.sh
```

---

### 3️⃣ `03_test_factories.sh`

**Objectif:** Tester la génération de données avec Faker

**Ce qui est testé:**

- Génération d'entreprises
- Génération de sites
- Génération d'utilisateurs
- Génération de processus
- Génération de documents
- Génération d'audits
- Génération de risques
- Génération d'actions
- Génération de non-conformités

**Commande:**

```bash
./local_tests/03_test_factories.sh
```

---

### 4️⃣ `04_test_api_endpoints.sh`

**Objectif:** Tester les endpoints de l'API avec cURL

**Ce qui est testé:**

- GET /api/users
- GET /api/enterprises
- GET /api/sites
- GET /api/processes
- GET /api/documents
- GET /api/audits
- GET /api/permissions

**Prérequis:**

- Le serveur doit être démarré (`php artisan serve`)
- `jq` doit être installé pour le parsing JSON

**Commande:**

```bash
# Démarrer le serveur
cd backend && php artisan serve &

# Exécuter les tests
./local_tests/04_test_api_endpoints.sh
```

---

### 5️⃣ `05_test_full_workflow.sh`

**Objectif:** Exécuter tous les tests dans l'ordre

**Ce qui est testé:**

- Tous les tests précédents en séquence
- Workflow complet de A à Z

**Commande:**

```bash
./local_tests/05_test_full_workflow.sh
```

---

## 🚀 Démarrage rapide

### Test rapide (migrations + seeders)

```bash
./local_tests/01_test_migrations.sh
./local_tests/02_test_seeders.sh
```

### Test complet

```bash
./local_tests/05_test_full_workflow.sh
```

---

## 📝 Notes importantes

1. **Base de données:** Les scripts utilisent `migrate:fresh` qui **supprime toutes les données**
2. **Environnement:** Assurez-vous d'être dans l'environnement de développement
3. **Permissions:** Les scripts doivent être exécutables (`chmod +x`)
4. **Variables d'environnement:** Le fichier `.env` doit être configuré

---

## 🛠️ Prérequis

- PHP >= 8.2
- Composer
- Laravel 12
- Base de données configurée (PostgreSQL/MySQL/SQLite)
- `jq` pour les tests API (optionnel)

---

## 📊 Résultats attendus

### Migrations

- ✅ 36 migrations exécutées
- ✅ Toutes les tables créées

### Seeders

- ✅ 88 permissions créées
- ✅ 1 Super Admin créé
- ✅ Toutes les permissions assignées au Super Admin

### Factories

- ✅ 5 entreprises
- ✅ 10 sites
- ✅ 20 utilisateurs
- ✅ 15 processus
- ✅ 30 documents
- ✅ 10 audits
- ✅ 25 risques
- ✅ 40 actions
- ✅ 20 non-conformités

### API

- ✅ Toutes les routes accessibles
- ✅ Format JSON:API respecté
- ✅ Pagination fonctionnelle (20 items/page)

---

**Créé le:** 15 janvier 2026  
**Auteur:** Équipe BestQHSE
