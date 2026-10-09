# 📦 Guide d'Installation - BestQHSE

Guide complet d'installation et de configuration de BestQHSE.

---

## 📋 Table des matières

1. [Prérequis](#prérequis)
2. [Installation locale](#installation-locale)
3. [Configuration](#configuration)
4. [Base de données](#base-de-données)
5. [Vérification](#vérification)
6. [Problèmes courants](#problèmes-courants)

---

## 🔧 Prérequis

### Logiciels requis

| Logiciel   | Version minimale | Recommandé |
| ---------- | ---------------- | ---------- |
| PHP        | 8.2              | 8.3+       |
| Composer   | 2.0              | Latest     |
| PostgreSQL | 12               | 15+        |
| Node.js    | 18               | 20+ LTS    |
| Git        | 2.0              | Latest     |

### Extensions PHP requises

```bash
# Vérifier les extensions installées
php -m

# Extensions requises:
- OpenSSL
- PDO
- Mbstring
- Tokenizer
- XML
- Ctype
- JSON
- BCMath
- pgsql (pour PostgreSQL)
```

### Installer les extensions manquantes (Ubuntu/Debian)

```bash
sudo apt update
sudo apt install php8.2-cli php8.2-common php8.2-mbstring \
    php8.2-xml php8.2-curl php8.2-pgsql php8.2-zip php8.2-bcmath
```

---

## 🚀 Installation locale

### Étape 1: Cloner le projet

```bash
# Via HTTPS
git clone https://github.com/votre-org/BestQHSE.git
cd BestQHSE

# Ou via SSH
git clone git@github.com:votre-org/BestQHSE.git
cd BestQHSE
```

### Étape 2: Installer les dépendances PHP

```bash
cd backend
composer install
```

**Note:** Si vous rencontrez des problèmes de mémoire:

```bash
php -d memory_limit=-1 /usr/local/bin/composer install
```

### Étape 3: Copier le fichier d'environnement

```bash
cp .env.example .env
```

### Étape 4: Générer la clé d'application

```bash
php artisan key:generate
```

---

## ⚙️ Configuration

### Fichier .env

Éditez le fichier `.env` avec vos paramètres:

```env
# Application
APP_NAME=BestQHSE
APP_ENV=local
APP_KEY=base64:xxx... # Généré automatiquement
APP_DEBUG=true
APP_URL=http://localhost:8000

# Base de données PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=BestQHSE
DB_USERNAME=postgres
DB_PASSWORD=votre_mot_de_passe

# Super Admin
SUPER_ADMIN_NAME="Super Admin"
SUPER_ADMIN_USERNAME=superadmin
SUPER_ADMIN_EMAIL=admin@BestQHSE.com
SUPER_ADMIN_PASSWORD=SuperAdmin@2024!
SUPER_ADMIN_PHONE="+221 00 000 00 00"

# Sessions
SESSION_DRIVER=database
SESSION_LIFETIME=120

# Cache
CACHE_DRIVER=file
QUEUE_CONNECTION=sync

# Mail (optionnel)
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@BestQHSE.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### Configuration alternative avec MySQL

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=BestQHSE
DB_USERNAME=root
DB_PASSWORD=votre_mot_de_passe
```

### Configuration alternative avec SQLite

```env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database.sqlite
```

---

## 🗄️ Base de données

### Option 1: PostgreSQL (recommandé)

#### Installer PostgreSQL

```bash
# Ubuntu/Debian
sudo apt install postgresql postgresql-contrib

# macOS (Homebrew)
brew install postgresql
brew services start postgresql

# Windows
# Télécharger depuis https://www.postgresql.org/download/windows/
```

#### Créer la base de données

```bash
# Se connecter à PostgreSQL
sudo -u postgres psql

# Créer la base de données
CREATE DATABASE BestQHSE;

# Créer un utilisateur (optionnel)
CREATE USER BestQHSE_user WITH PASSWORD 'votre_mot_de_passe';
GRANT ALL PRIVILEGES ON DATABASE BestQHSE TO BestQHSE_user;

# Quitter
\q
```

### Option 2: MySQL/MariaDB

```bash
# Ubuntu/Debian
sudo apt install mysql-server

# Créer la base de données
mysql -u root -p
CREATE DATABASE BestQHSE CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

### Option 3: SQLite (développement uniquement)

```bash
# Créer le fichier de base de données
touch database/database.sqlite
```

---

## 🎬 Exécution des migrations et seeders

### Migrations

```bash
# Exécuter toutes les migrations
php artisan migrate

# Ou reset complet
php artisan migrate:fresh
```

**Résultat attendu:**

```
Migration table created successfully.
Migrating: 2014_10_12_000000_create_users_table
Migrated:  2014_10_12_000000_create_users_table (0.50ms)
Migrating: 2026_01_15_140353_update_users_table_add_BestQHSE_fields
Migrated:  2026_01_15_140353_update_users_table_add_BestQHSE_fields (0.30ms)
...
Migrating: 2026_01_15_145035_create_applicable_requirements_table
Migrated:  2026_01_15_145035_create_applicable_requirements_table (0.25ms)
```

### Seeders

```bash
# Seeder les données initiales
php artisan db:seed

# Ou avec les migrations
php artisan migrate:fresh --seed
```

**Résultat attendu:**

```
Seeding: Database\Seeders\PermissionsSeeder
88 permissions créées avec succès!
Seeded:  Database\Seeders\PermissionsSeeder (0.50s)

Seeding: Database\Seeders\SuperAdminSeeder
Super Admin créé avec toutes les permissions!
Email: admin@BestQHSE.com
Username: superadmin
Seeded:  Database\Seeders\SuperAdminSeeder (0.20s)
```

---

## ✅ Vérification

### Étape 1: Vérifier les migrations

```bash
php artisan migrate:status
```

Toutes les migrations doivent avoir le statut **Ran**.

### Étape 2: Vérifier les données seedées

```bash
php artisan tinker

# Dans Tinker:
>>> App\Models\Permission::count()
=> 88

>>> App\Models\User::where('user_type', 'admin')->first()->email
=> "admin@BestQHSE.com"

>>> exit
```

### Étape 3: Démarrer le serveur

```bash
php artisan serve
```

**Sortie attendue:**

```
INFO  Server running on [http://127.0.0.1:8000].

Press Ctrl+C to stop the server
```

### Étape 4: Tester l'API

```bash
# Dans un autre terminal
curl http://localhost:8000/api/permissions | jq '.meta'
```

**Réponse attendue:**

```json
{
  "current_page": 1,
  "per_page": 20,
  "total": 88
}
```

---

## 🧪 Tests automatisés

Le projet inclut des scripts de test dans `./local_tests/`:

### Test complet

```bash
# Depuis la racine du projet
./local_tests/05_test_full_workflow.sh
```

### Tests individuels

```bash
# Test des migrations
./local_tests/01_test_migrations.sh

# Test des seeders
./local_tests/02_test_seeders.sh

# Test des factories
./local_tests/03_test_factories.sh

# Test des endpoints API
# (Nécessite que le serveur soit démarré)
./local_tests/04_test_api_endpoints.sh
```

---

## 🐛 Problèmes courants

### Erreur: "Class not found"

```bash
# Solution: Regénérer l'autoload
composer dump-autoload
```

### Erreur: "SQLSTATE[HY000] [2002] Connection refused"

**Cause:** La base de données n'est pas démarrée

```bash
# PostgreSQL
sudo service postgresql start

# MySQL
sudo service mysql start
```

### Erreur: "Permission denied" sur storage

```bash
# Solution: Donner les permissions
chmod -R 775 storage bootstrap/cache
```

### Erreur: "419 CSRF Token Mismatch"

**Cause:** Token CSRF expiré (ne devrait pas arriver sur les routes API)

**Solution:** Les routes API sont exemptées de CSRF par défaut.

### Port 8000 déjà utilisé

```bash
# Utiliser un autre port
php artisan serve --port=8080
```

### Erreur de mémoire lors de composer install

```bash
# Augmenter la limite de mémoire
php -d memory_limit=-1 /usr/local/bin/composer install
```

---

## 🔒 Sécurité

### En production

1. **Ne jamais** mettre `APP_DEBUG=true`
2. **Ne jamais** commiter le fichier `.env`
3. **Toujours** utiliser HTTPS
4. **Toujours** définir des mots de passe forts
5. **Toujours** mettre à jour les dépendances

```bash
# Vérifier les vulnérabilités
composer audit

# Mettre à jour les dépendances
composer update
```

---

## 📦 Installation Docker (alternatif)

### Avec Docker Compose

```bash
# Construire les images
docker-compose build

# Démarrer les conteneurs
docker-compose up -d

# Exécuter les migrations
docker-compose exec app php artisan migrate

# Seeder les données
docker-compose exec app php artisan db:seed
```

Voir [DOCKER.md](../DOCKER.md) pour plus de détails.

---

## 🧪 Tests

### Exécuter les tests

```bash
# Lancer tous les tests
./local_tests/run_tests.sh

# Ou directement avec PHPUnit
php artisan test

# Tests spécifiques
php artisan test --filter UserTest

# Tests avec couverture
php artisan test --coverage

# Tests en parallèle
php artisan test --parallel
```

**Résultat attendu:**

```
   PASS  Tests\Unit\UserTest
  ✓ it has fillable attributes
  ✓ it casts email verified at to datetime
  ✓ it hides password in arrays
  ✓ it belongs to responsibility
  ✓ it uses auto reference trait
  ✓ it soft deletes

   PASS  Tests\Feature\UserControllerTest
  ✓ it can list users with pagination
  ✓ it can create a user
  ✓ it can show a user
  ✓ it can update a user
  ✓ it can soft delete a user
  ...

  Tests:    12 passed (24 assertions)
  Duration: 2.34s
```

### Tests API

```bash
# Tester les endpoints API
./local_tests/test_api_endpoints.sh
```

**Résultat attendu:**

```
========================================
   API ENDPOINT TESTS
========================================

Logging in...
Authenticated successfully

Testing: List all users
Endpoint: GET /api/users
Response: {
  "data": [...],
  "links": {...},
  "meta": {...}
}
...
```

---

## 🚀 Déploiement

### Préparer pour la production

```bash
# Optimiser l'autoloader
composer install --optimize-autoloader --no-dev

# Mettre en cache les configurations
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Générer la clé
php artisan key:generate

# Migrer la base de données
php artisan migrate --force
```

---

## 📞 Support

Si vous rencontrez des problèmes :

1. Vérifier les [problèmes courants](#problèmes-courants)
2. Consulter la [documentation](../md/)
3. Ouvrir une issue sur GitHub
4. Contacter le support: support@BestQHSE.com

---

**Dernière mise à jour:** 15 janvier 2026  
**Version:** 1.1.0
