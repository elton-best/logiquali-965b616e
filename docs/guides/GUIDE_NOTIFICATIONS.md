# Guide de Gestion des Notifications

## 🔔 Systèmes de Notifications

### 1. **Toasts Frontend** (Notifications temporaires)
- **Localisation**: `frontend/src/modules/shared/stores/toastStore.ts`
- **Usage**: Messages éphémères (succès, erreur, warning, info)
- **Durée**: 3-5 secondes
- **Déduplication**: Automatique (500ms)

### 2. **Notifications Backend** (Persistantes)
- **Localisation**: `backend/app/Notifications/`
- **Usage**: Notifications importantes (email + database)
- **Persistance**: Base de données
- **Déduplication**: Automatique (5 minutes)

---

## ✅ Bonnes Pratiques

### Éviter les Notifications Multiples

#### 1. Désactiver les toasts automatiques si gestion manuelle

```typescript
import api from '@/services/api'
import { withoutErrorToast } from '@/services/apiHelpers'

// Désactiver le toast d'erreur automatique
try {
  await api.post('/endpoint', data, withoutErrorToast())
} catch (error) {
  // Gérer l'erreur manuellement
  toast.error('Message personnalisé')
}
```

#### 2. Utiliser les toasts pour les actions utilisateur

```typescript
import { useToast } from '@/modules/shared/composables/useToast'

const toast = useToast()

// ✅ BON: Toast pour feedback immédiat
async function saveData() {
  try {
    await api.post('/save', data)
    toast.success('Données enregistrées')
  } catch (error) {
    // L'intercepteur gère déjà l'erreur
  }
}
```

#### 3. Utiliser les notifications backend pour les événements importants

```php
// ✅ BON: Notification persistante pour événements critiques
$user->notify(new CompanyApprovedNotification($enterprise));
```

---

## 🚫 Anti-Patterns à Éviter

### ❌ Double notification

```typescript
// ❌ MAUVAIS: Double notification
try {
  await api.post('/save', data)
  toast.success('Sauvegardé') // Toast manuel
  // + L'intercepteur affiche déjà un toast
} catch (error) {
  toast.error('Erreur') // Toast manuel
  // + L'intercepteur affiche déjà un toast
}
```

### ❌ Notification backend + toast pour la même action

```php
// ❌ MAUVAIS: Notification backend
$user->notify(new DataSavedNotification());

// + Toast frontend dans le même flux
return response()->json(['message' => 'Données sauvegardées']);
```

---

## 🎯 Quand Utiliser Quoi ?

| Cas d'usage | Solution | Exemple |
|-------------|----------|---------|
| Feedback immédiat action utilisateur | Toast frontend | Sauvegarde, suppression |
| Erreur API | Toast automatique (intercepteur) | 401, 403, 500 |
| Événement important | Notification backend | Approbation entreprise |
| Rappel/Deadline | Notification backend + Email | Audit à venir |
| Validation formulaire | Message inline | Erreurs champs |

---

## 🔧 Configuration

### Durée des toasts

```typescript
// frontend/src/modules/shared/composables/useToast.ts
toast.success('Message', undefined, 3000) // 3 secondes
toast.error('Erreur', undefined, 5000)    // 5 secondes
```

### Déduplication

```typescript
// frontend/src/modules/shared/stores/toastStore.ts
// Fenêtre de déduplication: 500ms (configurable)
if (lastShown && now - lastShown < 500) {
  return // Ignorer le doublon
}
```

```php
// backend/app/Traits/CreatesUserNotification.php
// Fenêtre de déduplication: 5 minutes (configurable)
->where('created_at', '>', now()->subMinutes(5))
```

---

## 🐛 Debugging

### Vérifier les notifications multiples

```bash
# Console navigateur
# Filtrer par "toast" ou "notification"

# Backend logs
tail -f storage/logs/laravel.log | grep "Notification"
```

### Désactiver temporairement les toasts

```typescript
// Dans api.ts, commenter l'appel toast dans l'intercepteur
// if (!skipToast) {
//   const toast = useToast()
//   toast.error(...)
// }
```

---

## 📊 Métriques

- **Déduplication frontend**: 500ms
- **Déduplication backend**: 5 minutes
- **Durée toast par défaut**: 5 secondes
- **Max toasts simultanés**: Illimité (mais déduplication active)
