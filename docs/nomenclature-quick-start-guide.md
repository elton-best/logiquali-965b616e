# Guide de Démarrage Rapide - Nomenclature Flexible

## Pour les Administrateurs

### 1. Créer votre première configuration

#### Étape 1 : Accéder à la configuration
```
Menu → Documents → Configuration des nomenclatures
```

#### Étape 2 : Créer une nouvelle configuration
1. Cliquer sur **"+ Nouvelle configuration"**
2. Remplir les informations de base :
   - **Code** : POL (pour Politique)
   - **Libellé** : Politique
   - **Description** : Documents de politique qualité

#### Étape 3 : Construire la structure
Glisser-déposer les éléments dans l'ordre souhaité :

**Exemple simple :**
```
[Code type] - [Numéro séquentiel]
→ POL-001, POL-002, POL-003...
```

**Configuration :**
1. Ajouter **"Code type"**
2. Ajouter **"Séparateur"** → Configurer : `-`
3. Ajouter **"Numéro séquentiel"** → Configurer :
   - Longueur : 3
   - Portée : Par type

**Exemple avancé :**
```
[Code type] / [Code processus] / [Année] / [Numéro]
→ PRC/QUA/2026/001
```

**Configuration :**
1. Ajouter **"Code type"**
2. Ajouter **"Séparateur"** → `/`
3. Ajouter **"Code processus"**
4. Ajouter **"Séparateur"** → `/`
5. Ajouter **"Année"**
6. Ajouter **"Séparateur"** → `/`
7. Ajouter **"Numéro séquentiel"** → Configurer :
   - Longueur : 3
   - Portée : Par type + processus + année

#### Étape 4 : Valider et activer
1. Vérifier l'aperçu du code généré
2. Cocher **"Configuration active"**
3. Cliquer sur **"Enregistrer"**

### 2. Gérer les configurations existantes

#### Activer/Désactiver
```
Liste → Bouton "Activer" ou "Désactiver"
```
⚠️ Une seule configuration active par type de document

#### Modifier
```
Liste → Bouton "Modifier" → Wizard
```

#### Dupliquer
```
Liste → Bouton "Dupliquer"
→ Saisir nouveau code et libellé
```
💡 Utile pour créer des variantes

#### Supprimer
```
Liste → Bouton "Supprimer"
```
⚠️ Impossible si des documents utilisent cette configuration

---

## Pour les Utilisateurs

### 1. Créer un document avec code automatique

#### Étape 1 : Ouvrir le formulaire
```
Menu → Documents → Nouveau document
```

#### Étape 2 : Remplir les informations
1. **Nom** : Saisir le nom du document
2. **Type** : Sélectionner le type (POL, PRC, FOR, etc.)
3. **Processus** : Sélectionner le processus lié

#### Étape 3 : Observer l'aperçu du code
Le champ **"Code"** affiche automatiquement un aperçu :
```
Aperçu: PRC/QUA/2026/001
```

💡 **Astuce :** Laissez le champ vide pour génération automatique

#### Étape 4 : Compléter et créer
1. Remplir les autres champs (description, fichier, etc.)
2. Cliquer sur **"Créer"**

Le code est généré automatiquement et réservé !

### 2. Forcer un code manuel (optionnel)

Si vous devez utiliser un code spécifique :
1. Saisir le code dans le champ **"Code"**
2. L'aperçu disparaît
3. Le code saisi sera utilisé

⚠️ Vérifier que le code n'existe pas déjà

---

## Pour les Vérificateurs

### Vérifier les codes en attente

#### Étape 1 : Accéder à la liste
```
Menu → Documents → Workflow → Vérification des codes
```

#### Étape 2 : Consulter les documents
Liste des documents avec :
- Code généré
- Titre
- Type
- Auteur
- Date de création

#### Étape 3 : Vérifier
1. Cliquer sur **"Vérifier"** pour un document
2. Confirmer l'action

Le document passe en attente d'approbation.

---

## Pour les Approbateurs

### Approuver ou rejeter les codes

#### Étape 1 : Accéder à la liste
```
Menu → Documents → Workflow → Approbation des codes
```

#### Étape 2 : Consulter les documents vérifiés
Liste des documents avec :
- Code généré
- Titre
- Type
- Vérificateur
- Date de vérification

#### Étape 3 : Décider

**Option A : Approuver**
1. Cliquer sur **"Approuver"**
2. Confirmer l'action
→ Le code devient **actif** et définitif

**Option B : Rejeter**
1. Cliquer sur **"Rejeter"**
2. Confirmer l'action
→ Le code est **libéré** et recyclé pour réutilisation

---

## Comprendre les portées de séquence

### Globale
```
Séquence unique pour tous les documents
POL-001, PRC-002, FOR-003...
```

### Par type
```
Séquence par type de document
POL-001, POL-002, PRC-001, PRC-002...
```

### Par type + processus
```
Séquence par type ET processus
PRC/QUA/001, PRC/QUA/002
PRC/RH/001, PRC/RH/002
```

### Par type + année
```
Séquence réinitialisée chaque année
POL-2025-001, POL-2025-002
POL-2026-001, POL-2026-002
```

### Par type + processus + année
```
Séquence par type, processus ET année
PRC/QUA/2025/001, PRC/QUA/2025/002
PRC/QUA/2026/001, PRC/QUA/2026/002
```

### Par type + processus + année + mois
```
Séquence réinitialisée chaque mois
PRC/QUA/2026/01/001
PRC/QUA/2026/02/001
```

---

## Recyclage des codes

### Comment ça marche ?

Quand un code est **rejeté** :
1. Le numéro est libéré
2. Il est ajouté à la liste des numéros disponibles
3. À la prochaine génération, le plus petit numéro disponible est réutilisé

**Exemple :**
```
Codes générés : 001, 002, 003, 004, 005
Code 003 rejeté → Libéré
Prochain code généré : 003 (recyclé)
Puis : 006, 007, 008...
```

### Avantages
- ✅ Pas de trous dans la numérotation
- ✅ Optimisation de l'espace
- ✅ Traçabilité complète

---

## Bonnes pratiques

### Pour les Administrateurs

1. **Planifier la structure** avant de créer
   - Définir les besoins métier
   - Choisir la portée appropriée
   - Prévoir l'évolution

2. **Tester avant d'activer**
   - Créer en mode inactif
   - Vérifier l'aperçu
   - Tester avec un document

3. **Documenter les choix**
   - Utiliser le champ description
   - Expliquer la logique de numérotation

4. **Une seule configuration active par type**
   - Éviter les conflits
   - Désactiver l'ancienne avant d'activer la nouvelle

### Pour les Utilisateurs

1. **Laisser le code vide** pour génération automatique
   - Plus rapide
   - Évite les erreurs
   - Garantit l'unicité

2. **Vérifier l'aperçu** avant de créer
   - S'assurer que le code correspond aux attentes
   - Vérifier le processus sélectionné

3. **Ne pas modifier le code** après création
   - Le code est unique et traçable
   - Modifications = perte de traçabilité

### Pour les Vérificateurs/Approbateurs

1. **Vérifier rapidement**
   - Ne pas laisser les codes en attente
   - Objectif : < 24h

2. **Rejeter si nécessaire**
   - Mieux vaut rejeter qu'approuver un code incorrect
   - Le code sera recyclé automatiquement

3. **Communiquer les rejets**
   - Informer l'auteur du document
   - Expliquer la raison du rejet

---

## Dépannage

### Le code n'est pas généré automatiquement

**Causes possibles :**
- Aucune configuration active pour ce type
- Configuration incomplète
- Erreur de connexion

**Solution :**
1. Vérifier qu'une configuration est active
2. Contacter l'administrateur
3. Saisir un code manuellement en attendant

### L'aperçu ne se met pas à jour

**Causes possibles :**
- Type ou processus non sélectionné
- Problème de connexion réseau

**Solution :**
1. Vérifier la sélection du type
2. Rafraîchir la page
3. Réessayer

### Code déjà utilisé

**Causes possibles :**
- Code saisi manuellement déjà existant
- Problème de synchronisation

**Solution :**
1. Laisser le champ vide pour génération automatique
2. Choisir un autre code manuel
3. Contacter l'administrateur

### Document bloqué en vérification

**Causes possibles :**
- Vérificateur absent
- Oubli de vérification

**Solution :**
1. Relancer le vérificateur
2. Contacter l'administrateur pour réassignation
3. Délai maximum : 7 jours

---

## Raccourcis clavier (à venir)

```
Ctrl + N  → Nouvelle configuration
Ctrl + S  → Sauvegarder
Ctrl + D  → Dupliquer
Esc       → Fermer le wizard
```

---

## Support

**Questions fréquentes :** Consulter la documentation complète  
**Problème technique :** Contacter le support IT  
**Demande de formation :** Contacter le responsable qualité

---

**Version:** 1.0.0  
**Dernière mise à jour:** 2026-04-30
