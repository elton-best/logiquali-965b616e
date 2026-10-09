# Hotfix: TypeError dans DocumentWorkflowRateLimit Middleware

**Date:** 2026-04-29  
**Severity:** CRITICAL (bloquait tous les tests)  
**Status:** ✅ RÉSOLU

---

## Symptômes

Tous les tests de `DocumentWorkflowAuthorizationTest` échouaient avec :
```
Expected response status code [403] but received 500.
TypeError: App\Http\Middleware\DocumentWorkflowRateLimit::handle(): 
Return value must be of type Illuminate\Http\Response, 
Illuminate\Http\JsonResponse returned
```

---

## Cause Racine

Le middleware `DocumentWorkflowRateLimit` avait un type de retour trop strict :

```php
// ❌ AVANT (ligne 18)
use Illuminate\Http\Response;

public function handle(Request $request, Closure $next): Response
{
    // ...
    return response()->json(['message' => 'Unauthorized'], 401); // ← Retourne JsonResponse
}
```

**Problème :** 
- `response()->json()` retourne `Illuminate\Http\JsonResponse`
- Le type hint était `Illuminate\Http\Response` (classe spécifique)
- PHP 8.5 est strict sur les types de retour → TypeError

---

## Solution

Utiliser la classe parente commune `Symfony\Component\HttpFoundation\Response` + type union :

```php
// ✅ APRÈS
use Symfony\Component\HttpFoundation\Response;

public function handle(Request $request, Closure $next): Response|\Illuminate\Http\JsonResponse
{
    // ...
    return response()->json(['message' => 'Unauthorized'], 401); // ✅ OK
}
```

**Pourquoi ça marche :**
- `Symfony\Component\HttpFoundation\Response` est la classe parente de toutes les réponses HTTP
- Le type union `Response|\Illuminate\Http\JsonResponse` accepte explicitement les deux types
- Compatible avec PHP 8.0+ (union types)

---

## Fichiers Modifiés

```
backend/app/Http/Middleware/DocumentWorkflowRateLimit.php
  - Ligne 7: Import Symfony Response au lieu de Illuminate
  - Ligne 18: Type union Response|\Illuminate\Http\JsonResponse
```

---

## Tests de Validation

```bash
php artisan test tests/Feature/DocumentWorkflowAuthorizationTest.php
```

**Résultat :**
```
✅ 6 passed (7 assertions) in 20.44s
  ✓ only author can confirm code
  ✓ cross tenant access denied on confirm code
  ✓ author can confirm code
  ✓ verify requires permission
  ✓ approve requires permission
  ✓ code mismatch rejected
```

---

## Leçons Apprises

1. **Type Hints Stricts** : PHP 8.5 est plus strict sur les types de retour
2. **Middleware Return Types** : Toujours utiliser `Symfony\Component\HttpFoundation\Response` pour les middlewares
3. **Testing Environment** : Le middleware skip le rate limiting en test, mais le type hint était quand même vérifié
4. **Laravel Responses** : `response()->json()` retourne `JsonResponse`, pas `Response`

---

## Impact

- **Avant** : Tous les tests workflow échouaient avec 500 TypeError
- **Après** : 100% des tests passent (6/6)
- **Régression** : Aucune (changement de type compatible)

---

## Références

- [Laravel HTTP Responses](https://laravel.com/docs/11.x/responses)
- [Symfony HttpFoundation](https://symfony.com/doc/current/components/http_foundation.html)
- [PHP Union Types](https://www.php.net/manual/en/language.types.declarations.php#language.types.declarations.union)
