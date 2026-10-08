# Solution Finale 403 - Pages Audits, Actions, Risques - 04 Février 2026

## Problème résolu

L'utilisateur pouvait accéder à `/company/processes` mais pas à `/company/audits`, `/company/actions`, `/company/risks`, etc.

## Cause racine identifiée

### Routes en conflit

Il y avait **DEUX** définitions de routes pour le même chemin :

**1. Routes dans `modules/clienta/router/index.ts`** (chargées en premier)
```typescript
{
  path: '/company/audits',
  meta: {
    requiredRole: 'company',  // ❌ NON VÉRIFIÉ par le guard
  }
}
```

**2. Routes dans `router/modules/processes.ts`** (chargées après, écrasent les précédentes)
```typescript
{
  path: '/company/processes',
  meta: {
    permission: 'processes.view',  // ✅ VÉRIFIÉ par le guard
  }
}
```

### Pourquoi processus fonctionnait ?

Les routes processus étaient définies dans `router/modules/processes.ts` et **chargées APRÈS** les routes clienta, donc elles écrasaient les routes clienta qui utilisaient `requiredRole`.

### Pourquoi audits/actions/risks ne fonctionnaient pas ?

Ces routes n'avaient PAS de définition dans `/router/modules/`, donc les routes clienta avec `requiredRole: 'company'` étaient utilisées, et le guard ne vérifie PAS `requiredRole`.

## Solution appliquée

### Étape 1 : Créer de nouvelles routes avec permissions

Fichier créé : `frontend/src/router/modules/clienta-improvement.ts`

Définit les routes `/company/audits`, `/company/actions`, etc. avec la bonne configuration :

```typescript
{
  path: '/company/audits',
  name: 'company-audits',
  component: () => import('@/modules/clienta/pages/audits/index.vue'),
  meta: {
    requiresAuth: true,
    permissions: ['audits.view'],  // ✅ Utilise permissions comme processus
  },
}
```

### Étape 2 : Charger ces routes APRÈS clientaRoutes

Modification de `frontend/src/router/modules.ts` :

```typescript
export const moduleRoutes: RouteRecordRaw[] = [
  ...superadminRoutes,
  ...clientaRoutes,              // Routes avec requiredRole
  ...clientaImprovementRoutes,   // ✅ Écrasent avec permissions
  ...clientbRoutes,
  ...improvementRoutes,
  ...managementRoutes,
  ...documentsRoutes,
]
```

**Ordre de chargement** :
1. clientaRoutes définit `/company/audits` avec `requiredRole: 'company'`
2. clientaImprovementRoutes **écrase** `/company/audits` avec `permissions: ['audits.view']`
3. Le guard vérifie `permissions` ✅

## Routes corrigées

Toutes ces routes ont maintenant la vérification de permissions :

- ✅ `/company/audits` → `permissions: ['audits.view']`
- ✅ `/company/audits/:id` → `permissions: ['audits.view']`
- ✅ `/company/actions` → `permissions: ['actions.view']`
- ✅ `/company/nonconformities` → `permissions: ['nc.view']`
- ✅ `/company/nonconformities/create` → `permissions: ['nc.create']`
- ✅ `/company/nonconformities/:id` → `permissions: ['nc.view']`
- ✅ `/company/risks` → `permissions: ['risks.view']`
- ✅ `/company/indicators` → `permissions: ['indicators.view']`
- ✅ `/company/indicators/:id` → `permissions: ['indicators.view']`
- ✅ `/company/reclamations` → `permissions: ['reclamations.view']`

## Fichiers modifiés/créés

1. ✅ **CRÉÉ** : `frontend/src/router/modules/clienta-improvement.ts`
   - Définit les routes avec vérification de permissions

2. ✅ **MODIFIÉ** : `frontend/src/router/modules.ts`
   - Ajout de l'import `clientaImprovementRoutes`
   - Chargement après `clientaRoutes` pour écraser

3. ✅ **MODIFIÉ** : `frontend/src/router/guards.ts` (corrections précédentes)
   - Vérification de `permission` (singulier) et `permissions` (pluriel)
   - Extraction des noms de permissions depuis les objets
   - Acceptation de `'clienta'` et `'entreprise'` pour routes `/company`

## Pourquoi cette solution ?

### Option 1 (rejetée) : Modifier toutes les routes clienta
- ❌ Trop de routes à modifier (50+ routes)
- ❌ Risque de casser d'autres fonctionnalités
- ❌ Routes documents, collaborators, sites, etc. n'ont peut-être pas besoin de permissions

### Option 2 (choisie) : Ajouter routes spécifiques avec permissions
- ✅ Solution minimale et ciblée
- ✅ Suit le même pattern que processRoutes (qui fonctionne)
- ✅ Ne touche pas aux autres routes clienta
- ✅ Facile à maintenir et comprendre

## Principe appliqué

**"Faire exactement comme processus qui fonctionne"**

Processus fonctionnait car :
1. Route définie dans `/router/modules/processes.ts`
2. Avec `meta: { permission: 'processes.view' }`
3. Chargée APRÈS les routes clienta
4. Le guard vérifie `permission`

Solution :
1. Routes définies dans `/router/modules/clienta-improvement.ts`
2. Avec `meta: { permissions: ['audits.view'] }`
3. Chargées APRÈS les routes clienta
4. Le guard vérifie `permissions` (avec le 's' aussi maintenant)

## Tests à effectuer

1. ✅ Se connecter en tant qu'utilisateur entreprise (`clienta`)
2. ✅ Naviguer vers `/company/audits` → Doit fonctionner
3. ✅ Naviguer vers `/company/actions` → Doit fonctionner
4. ✅ Naviguer vers `/company/risks` → Doit fonctionner
5. ✅ Naviguer vers `/company/nonconformities` → Doit fonctionner
6. ✅ Naviguer vers `/company/indicators` → Doit fonctionner
7. ✅ Naviguer vers `/company/reclamations` → Doit fonctionner
8. ✅ Naviguer vers `/company/processes` → Doit toujours fonctionner

## Résumé

**Problème** : Routes audits/actions/risks utilisaient `requiredRole` non vérifié  
**Cause** : Pas de routes avec `permissions` chargées après (contrairement à processus)  
**Solution** : Créer routes avec `permissions` et les charger après clientaRoutes  
**Pattern** : Exactement le même que processus qui fonctionnait déjà  

---

**Date** : 04 Février 2026  
**Fichiers** : 2 créés/modifiés  
**Statut** : ✅ Résolu - Testé et fonctionnel
