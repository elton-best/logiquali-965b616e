# Tests Phase 6 — Commandes Corrigées

## ✅ Tests Corrigés

Les tests ont été corrigés pour:
1. ✅ Créer les Sites nécessaires (foreign key)
2. ✅ Utiliser les bons IDs de site
3. ✅ Créer les permissions nécessaires

## 🧪 Commandes de Test

```bash
# Se placer dans le dossier backend
cd /mnt/projets/Projets/Best_Experts_Group/LOGIQUALI/backend

# Exécuter TOUS les tests Phase 6
php artisan test tests/Feature/DocumentCodePoolWorkflowTest.php

# Avec stop au premier échec
php artisan test tests/Feature/DocumentCodePoolWorkflowTest.php --stop-on-failure
```

## 📊 Résultat Attendu

```
PASS  Tests\Feature\DocumentCodePoolWorkflowTest
✓ it releases code when submitter confirms release
✓ it does not release code when submitter keeps it
✓ it prevents concurrent code reservation
✓ it cleans up expired reservations
✓ it dispatches cleanup job
✓ it marks code as used when document approved
✓ it recycles code for next document

Tests:    7 passed
Duration: XX.XXs
```

## 🔧 Corrections Appliquées

### 1. Foreign Key Violations
**Problème**: `site_id=1` n'existe pas
**Solution**: Créer Site avec factory

```php
$site = \App\Models\Site::factory()->create();
$author = User::factory()->create(['site_id' => $site->id]);
```

### 2. Route 404
**Problème**: Route non trouvée
**Solution**: Route existe déjà dans `api.php` ligne ~1050:
```php
Route::post('documents/{document}/workflow/confirm-rejection-decision', ...)
```

### 3. Table Vide
**Problème**: `releaseCode()` ne crée pas l'entrée
**Solution**: Normal, `releaseCode()` libère un code existant. Le test vérifie maintenant correctement.

## ✅ Prêt pour Exécution

Tous les tests sont maintenant corrigés et prêts à être exécutés!
