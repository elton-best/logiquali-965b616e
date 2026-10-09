# 🚀 Guide de démarrage rapide - BestQHSE Backend

## Installation et configuration

### 1. Cloner le projet

```bash
cd backend
composer install
```

### 2. Configuration de l'environnement

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Configurer la base de données dans .env

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=BestQHSE
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

### 4. Exécuter les migrations

```bash
php artisan migrate
```

### 5. Seeder les données (à venir)

```bash
php artisan db:seed
```

### 6. Lancer le serveur

```bash
php artisan serve
```

L'API sera disponible sur : `http://localhost:8000`

---

## 📚 Endpoints API disponibles

### Format de base

Tous les endpoints suivent le format JSON:API :

```
GET    /api/{resource}           - Liste paginée
POST   /api/{resource}           - Créer
GET    /api/{resource}/{id}      - Afficher
PUT    /api/{resource}/{id}      - Mettre à jour
DELETE /api/{resource}/{id}      - Supprimer
```

### Resources disponibles (15 complètes)

#### Gestion des utilisateurs et entreprises

- `/api/users`
- `/api/enterprises`
- `/api/sites`
- `/api/permissions`
- `/api/roles`

#### Gestion qualité

- `/api/processes`
- `/api/activities`
- `/api/risks`
- `/api/actions`
- `/api/audits`

#### Gestion documentaire

- `/api/documents`
- `/api/norms`
- `/api/articles`

#### Gestion commerciale

- `/api/offers`
- `/api/complaints`

---

## 🔍 Exemples d'utilisation

### 1. Lister les entreprises

```bash
curl -X GET http://localhost:8000/api/enterprises \
  -H "Accept: application/vnd.api+json"
```

**Réponse :**

```json
{
  "data": [
    {
      "id": 1,
      "type": "enterprises",
      "attributes": {
        "ref": "ENT-2026-001",
        "name": "Entreprise ABC",
        "email": "contact@abc.com",
        "status": "active",
        "created_at": "2026-01-15T15:00:00.000Z"
      }
    }
  ],
  "links": {
    "self": "http://localhost:8000/api/enterprises",
    "first": "http://localhost:8000/api/enterprises?page=1",
    "last": "http://localhost:8000/api/enterprises?page=5"
  },
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 95
  }
}
```

### 2. Créer un utilisateur

```bash
curl -X POST http://localhost:8000/api/users \
  -H "Content-Type: application/vnd.api+json" \
  -H "Accept: application/vnd.api+json" \
  -d '{
    "name": "John Doe",
    "username": "jdoe",
    "email": "john@example.com",
    "password": "SecurePass123!",
    "user_type": "user",
    "enterprise_id": 1,
    "site_id": 1,
    "is_active": true
  }'
```

### 3. Inclure les relations

```bash
curl -X GET "http://localhost:8000/api/processes?include=site,pilot,activities" \
  -H "Accept: application/vnd.api+json"
```

### 4. Filtrage et tri (à implémenter)

```bash
# Filtrer par statut
GET /api/audits?filter[status]=completed

# Trier par date
GET /api/audits?sort=-created_at

# Paginer
GET /api/audits?page[number]=2&page[size]=20
```

---

## 🔐 Authentification (à implémenter)

### Laravel Sanctum

```bash
# Login
POST /api/login
{
  "email": "user@example.com",
  "password": "password"
}

# Utiliser le token
GET /api/users
Authorization: Bearer {token}
```

---

## 📝 Structure des validations

### Règles communes

```php
// Création
'field' => 'required|string|max:255'
'foreign_id' => 'required|exists:table,id'
'enum' => 'required|in:value1,value2'
'boolean' => 'boolean'
'date' => 'nullable|date'

// Mise à jour
'field' => 'sometimes|string|max:255'
'unique' => 'sometimes|unique:table,field,' . $id
```

### Types de champs courants

- **ref** : Généré automatiquement
- **created_by, updated_by, deleted_by** : Remplis automatiquement
- **timestamps** : created_at, updated_at automatiques
- **soft_deletes** : deleted_at automatique

---

## 🎯 Génération automatique des références

Format : `PREFIX-YYYY-NNN`

### Exemples

- User : `USER-2026-001`
- Entreprise : `ENT-2026-042`
- Processus : `PROC-2026-015`
- Document : `DOC-2026-128`
- Audit : `AUD-2026-007`

### Comment ça marche ?

Le trait `HasReference` génère automatiquement :

1. Détecte le préfixe selon la table
2. Utilise l'année en cours
3. Trouve le dernier numéro de séquence de l'année
4. Incrémente et formate sur 3 chiffres

---

## 🛡️ Système de permissions

### Structure

```
88 permissions réparties en 15 modules
↓
Assignées à des rôles
↓
Rôles assignés aux utilisateurs
+
Permissions directes aux utilisateurs
```

### Vérifier une permission (à implémenter)

```php
if ($user->hasPermission('processes.create')) {
    // Autoriser l'action
}
```

---

## 🧪 Tests (à créer)

```bash
# Lancer tous les tests
php artisan test

# Tests spécifiques
php artisan test --filter UserTest
php artisan test --filter ProcessTest
```

---

## 📊 Commandes utiles

```bash
# Voir les routes
php artisan route:list

# Voir les migrations
php artisan migrate:status

# Rollback
php artisan migrate:rollback

# Fresh migration
php artisan migrate:fresh

# Fresh avec seed
php artisan migrate:fresh --seed

# Créer un model avec tout
php artisan make:model ModelName -mfcs

# Cache clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

---

## 🐛 Debug

### Activer le mode debug

```env
APP_DEBUG=true
APP_ENV=local
```

### Logs

```bash
tail -f storage/logs/laravel.log
```

### Tinker

```bash
php artisan tinker

# Exemples
User::count()
Enterprise::with('sites')->first()
Process::where('type', 'management')->get()
```

---

## 📦 Packages installés

- **laravel-json-api/laravel** : Support JSON:API
- **laravel/sanctum** : Authentification API
- **laravel/tinker** : REPL interactif

---

## ⚡ Performance

### Eager Loading

Toujours charger les relations nécessaires :

```php
// ❌ Mauvais (N+1 queries)
$users = User::all();
foreach ($users as $user) {
    echo $user->enterprise->name;
}

// ✅ Bon
$users = User::with('enterprise')->all();
foreach ($users as $user) {
    echo $user->enterprise->name;
}
```

### Cache (à configurer)

```php
Cache::remember('enterprises', 3600, function () {
    return Enterprise::all();
});
```

---

## 🔧 Troubleshooting

### Erreur : Class not found

```bash
composer dump-autoload
```

### Erreur : Migration already exists

```bash
# Supprimer la table migrations et recommencer
php artisan migrate:fresh
```

### Erreur : 419 CSRF Token

Pour les APIs, désactiver CSRF sur les routes API (déjà fait)

---

## 📞 Support

Pour toute question ou problème :

- Email : support@BestQHSE.com
- Documentation : /md/RECAP_PROJET.md
- Wiki : (à créer)
