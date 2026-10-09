# 🚀 API Routes Documentation - BestQHSE

**Date de création :** 15 janvier 2026  
**Version API :** 1.0.0  
**Base URL :** `http://localhost:8000/api`

---

## 📋 Table des matières

1. [Vue d'ensemble](#vue-densemble)
2. [Format des réponses](#format-des-réponses)
3. [Endpoints disponibles](#endpoints-disponibles)
4. [Exemples de requêtes](#exemples-de-requêtes)
5. [Codes de statut HTTP](#codes-de-statut-http)
6. [Authentification](#authentification)

---

## 🎯 Vue d'ensemble

L'API BestQHSE suit la spécification **JSON:API** et expose 34 resources RESTful.

### Caractéristiques

- ✅ Format JSON:API standard
- ✅ Pagination automatique (20 items/page)
- ✅ Support des relations (`?include=`)
- ✅ Validation stricte des données
- ✅ Soft deletes
- ✅ Traçabilité complète (created_by, updated_by, deleted_by)

---

## 📝 Format des réponses

### Réponse simple (GET /api/users/1)

```json
{
  "data": {
    "id": 1,
    "type": "users",
    "attributes": {
      "ref": "USER-2026-001",
      "name": "John Doe",
      "email": "john@example.com",
      "created_at": "2026-01-15T15:00:00.000Z"
    },
    "relationships": {
      "enterprise": {
        "data": { "id": 1, "type": "enterprises" }
      }
    }
  },
  "jsonapi": {
    "version": "1.0"
  }
}
```

### Réponse collection (GET /api/users)

```json
{
  "data": [
    {
      "id": 1,
      "type": "users",
      "attributes": { ... }
    },
    {
      "id": 2,
      "type": "users",
      "attributes": { ... }
    }
  ],
  "links": {
    "self": "http://localhost:8000/api/users",
    "first": "http://localhost:8000/api/users?page=1",
    "last": "http://localhost:8000/api/users?page=5",
    "prev": null,
    "next": "http://localhost:8000/api/users?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 20,
    "to": 20,
    "total": 95
  },
  "jsonapi": {
    "version": "1.0"
  }
}
```

---

## 🔗 Endpoints disponibles

### Gestion des utilisateurs et entreprises

| Méthode | Endpoint                | Description                  |
| ------- | ----------------------- | ---------------------------- |
| GET     | `/api/users`            | Liste des utilisateurs       |
| POST    | `/api/users`            | Créer un utilisateur         |
| GET     | `/api/users/{id}`       | Afficher un utilisateur      |
| PUT     | `/api/users/{id}`       | Mettre à jour un utilisateur |
| DELETE  | `/api/users/{id}`       | Supprimer un utilisateur     |
| GET     | `/api/enterprises`      | Liste des entreprises        |
| POST    | `/api/enterprises`      | Créer une entreprise         |
| GET     | `/api/enterprises/{id}` | Afficher une entreprise      |
| PUT     | `/api/enterprises/{id}` | Mettre à jour une entreprise |
| DELETE  | `/api/enterprises/{id}` | Supprimer une entreprise     |
| GET     | `/api/sites`            | Liste des sites              |
| POST    | `/api/sites`            | Créer un site                |
| GET     | `/api/sites/{id}`       | Afficher un site             |
| PUT     | `/api/sites/{id}`       | Mettre à jour un site        |
| DELETE  | `/api/sites/{id}`       | Supprimer un site            |

### Gestion des normes et documents

| Méthode | Endpoint          | Description             |
| ------- | ----------------- | ----------------------- |
| GET     | `/api/norms`      | Liste des normes        |
| POST    | `/api/norms`      | Créer une norme         |
| GET     | `/api/norms/{id}` | Afficher une norme      |
| PUT     | `/api/norms/{id}` | Mettre à jour une norme |
| DELETE  | `/api/norms/{id}` | Supprimer une norme     |
| GET     | `/api/articles`   | Liste des articles      |
| POST    | `/api/articles`   | Créer un article        |
| GET     | `/api/documents`  | Liste des documents     |
| POST    | `/api/documents`  | Créer un document       |

### Gestion des processus qualité

| Méthode | Endpoint              | Description                |
| ------- | --------------------- | -------------------------- |
| GET     | `/api/processes`      | Liste des processus        |
| POST    | `/api/processes`      | Créer un processus         |
| GET     | `/api/processes/{id}` | Afficher un processus      |
| PUT     | `/api/processes/{id}` | Mettre à jour un processus |
| DELETE  | `/api/processes/{id}` | Supprimer un processus     |
| GET     | `/api/activities`     | Liste des activités        |
| POST    | `/api/activities`     | Créer une activité         |
| GET     | `/api/risks`          | Liste des risques          |
| POST    | `/api/risks`          | Créer un risque            |
| GET     | `/api/opportunities`  | Liste des opportunités     |
| POST    | `/api/opportunities`  | Créer une opportunité      |
| GET     | `/api/objectives`     | Liste des objectifs        |
| POST    | `/api/objectives`     | Créer un objectif          |

### Gestion des audits et conformité

| Méthode | Endpoint                | Description       |
| ------- | ----------------------- | ----------------- |
| GET     | `/api/audits`           | Liste des audits  |
| POST    | `/api/audits`           | Créer un audit    |
| GET     | `/api/non-conformities` | Liste des NC      |
| POST    | `/api/non-conformities` | Créer une NC      |
| GET     | `/api/actions`          | Liste des actions |
| POST    | `/api/actions`          | Créer une action  |

### Gestion commerciale

| Méthode | Endpoint                        | Description            |
| ------- | ------------------------------- | ---------------------- |
| GET     | `/api/offers`                   | Liste des offres       |
| POST    | `/api/offers`                   | Créer une offre        |
| GET     | `/api/complaints`               | Liste des réclamations |
| POST    | `/api/complaints`               | Créer une réclamation  |
| GET     | `/api/enterprise-subscriptions` | Liste des abonnements  |
| POST    | `/api/enterprise-subscriptions` | Créer un abonnement    |

### Gestion stratégique

| Méthode | Endpoint              | Description                   |
| ------- | --------------------- | ----------------------------- |
| GET     | `/api/stakeholders`   | Liste des parties intéressées |
| POST    | `/api/stakeholders`   | Créer une partie intéressée   |
| GET     | `/api/contexts`       | Liste des contextes           |
| POST    | `/api/contexts`       | Créer un contexte             |
| GET     | `/api/strategic-axes` | Liste des axes stratégiques   |
| POST    | `/api/strategic-axes` | Créer un axe stratégique      |
| GET     | `/api/dashboards`     | Liste des tableaux de bord    |
| POST    | `/api/dashboards`     | Créer un tableau de bord      |

### Management et satisfaction

| Méthode | Endpoint                    | Description                   |
| ------- | --------------------------- | ----------------------------- |
| GET     | `/api/management-reviews`   | Liste des revues de direction |
| POST    | `/api/management-reviews`   | Créer une revue de direction  |
| GET     | `/api/satisfaction-surveys` | Liste des enquêtes            |
| POST    | `/api/satisfaction-surveys` | Créer une enquête             |
| GET     | `/api/modifications`        | Liste des modifications       |
| POST    | `/api/modifications`        | Créer une modification        |
| GET     | `/api/plans`                | Liste des plans               |
| POST    | `/api/plans`                | Créer un plan                 |

### Permissions et rôles

| Méthode | Endpoint           | Description           |
| ------- | ------------------ | --------------------- |
| GET     | `/api/permissions` | Liste des permissions |
| POST    | `/api/permissions` | Créer une permission  |
| GET     | `/api/roles`       | Liste des rôles       |
| POST    | `/api/roles`       | Créer un rôle         |

### Ressources humaines

| Méthode | Endpoint                    | Description                |
| ------- | --------------------------- | -------------------------- |
| GET     | `/api/job-descriptions`     | Liste des fiches de poste  |
| POST    | `/api/job-descriptions`     | Créer une fiche de poste   |
| GET     | `/api/responsibilities`     | Liste des responsabilités  |
| POST    | `/api/responsibilities`     | Créer une responsabilité   |
| GET     | `/api/team-members`         | Liste des membres d'équipe |
| POST    | `/api/team-members`         | Créer un membre            |
| GET     | `/api/employee-evaluations` | Liste des évaluations      |
| POST    | `/api/employee-evaluations` | Créer une évaluation       |

### Ressources et interactions

| Méthode | Endpoint                       | Description            |
| ------- | ------------------------------ | ---------------------- |
| GET     | `/api/process-resources`       | Liste des ressources   |
| POST    | `/api/process-resources`       | Créer une ressource    |
| GET     | `/api/process-interactions`    | Liste des interactions |
| POST    | `/api/process-interactions`    | Créer une interaction  |
| GET     | `/api/applicable-requirements` | Liste des exigences    |
| POST    | `/api/applicable-requirements` | Créer une exigence     |

---

## 💡 Exemples de requêtes

### 1. Lister les entreprises

```bash
curl -X GET http://localhost:8000/api/enterprises \
  -H "Accept: application/vnd.api+json"
```

### 2. Créer un utilisateur

```bash
curl -X POST http://localhost:8000/api/users \
  -H "Content-Type: application/vnd.api+json" \
  -H "Accept: application/vnd.api+json" \
  -d '{
    "name": "Jane Doe",
    "username": "jdoe",
    "email": "jane@example.com",
    "password": "SecurePass123!",
    "user_type": "user",
    "enterprise_id": 1,
    "site_id": 1
  }'
```

### 3. Afficher un processus avec ses relations

```bash
curl -X GET "http://localhost:8000/api/processes/1?include=site,pilot,activities" \
  -H "Accept: application/vnd.api+json"
```

### 4. Mettre à jour un audit

```bash
curl -X PUT http://localhost:8000/api/audits/1 \
  -H "Content-Type: application/vnd.api+json" \
  -H "Accept: application/vnd.api+json" \
  -d '{
    "status": "completed",
    "actual_date": "2026-01-15"
  }'
```

### 5. Supprimer une action

```bash
curl -X DELETE http://localhost:8000/api/actions/5 \
  -H "Accept: application/vnd.api+json"
```

### 6. Pagination

```bash
# Page 2
curl -X GET "http://localhost:8000/api/users?page=2" \
  -H "Accept: application/vnd.api+json"
```

---

## 📊 Codes de statut HTTP

| Code | Description           | Utilisation                  |
| ---- | --------------------- | ---------------------------- |
| 200  | OK                    | Requête réussie (GET, PUT)   |
| 201  | Created               | Ressource créée (POST)       |
| 204  | No Content            | Suppression réussie (DELETE) |
| 400  | Bad Request           | Données invalides            |
| 401  | Unauthorized          | Non authentifié              |
| 403  | Forbidden             | Pas de permission            |
| 404  | Not Found             | Ressource inexistante        |
| 422  | Unprocessable Entity  | Erreur de validation         |
| 500  | Internal Server Error | Erreur serveur               |

---

## 🔐 Authentification

### Laravel Sanctum (à implémenter)

```bash
# Login
POST /api/login
{
  "email": "user@example.com",
  "password": "password"
}

# Response
{
  "token": "1|abcdef..."
}

# Utiliser le token
GET /api/users
Authorization: Bearer 1|abcdef...
```

---

## 🛠️ Paramètres de requête

### Pagination

```
?page=2          # Numéro de page
```

### Inclusion des relations

```
?include=site,pilot,activities
```

### Filtrage (à implémenter)

```
?filter[status]=active
?filter[type]=internal
```

### Tri (à implémenter)

```
?sort=-created_at    # Tri descendant
?sort=name          # Tri ascendant
```

---

## 📝 Headers requis

```
Content-Type: application/vnd.api+json
Accept: application/vnd.api+json
Authorization: Bearer {token}  (pour routes protégées)
```

---

## 🧪 Tester l'API

### Avec cURL

```bash
# Test simple
curl http://localhost:8000/api/users

# Test avec headers
curl -H "Accept: application/vnd.api+json" \
     http://localhost:8000/api/users
```

### Avec Postman

1. Importer la collection : `BestQHSE_API.postman_collection.json`
2. Configurer l'environnement : `BASE_URL=http://localhost:8000`
3. Tester les endpoints

### Avec Insomnia

1. Créer un nouveau workspace
2. Importer la collection
3. Tester les endpoints

---

## 🔍 Debugging

### Activer le mode debug

```env
APP_DEBUG=true
APP_ENV=local
```

### Voir les logs

```bash
tail -f storage/logs/laravel.log
```

### Lister toutes les routes

```bash
php artisan route:list --path=api
```

---

**Documentation générée le :** 15 janvier 2026 à 16:00 UTC  
**Version :** 1.0.0
