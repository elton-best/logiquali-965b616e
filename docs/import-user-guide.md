# Guide Utilisateur - Import de Documents Existants

## Vue d'ensemble

L'outil d'import permet de migrer vos documents existants vers le nouveau système de nomenclature flexible en 4 étapes simples.

---

## Prérequis

- Permission `import_documents` activée
- Au moins une configuration de nomenclature active
- Documents existants dans le système (sans configuration de nomenclature)

---

## Étapes d'import

### 1️⃣ Analyse des documents

**Objectif**: Identifier les documents à importer

1. Accédez à **Documents > Import**
2. (Optionnel) Sélectionnez un site spécifique
3. Cliquez sur **"Analyser les documents"**

**Résultat**:
- Nombre total de documents trouvés
- Groupement par type de document
- Exemples de codes existants
- Suggestions automatiques de configurations

💡 **Astuce**: Si aucun document n'est trouvé, vérifiez que vos documents n'ont pas déjà une configuration de nomenclature assignée.

---

### 2️⃣ Configuration du mapping

**Objectif**: Associer chaque type de document à une configuration

1. Vérifiez les mappings pré-remplis (suggestions automatiques)
2. Modifiez si nécessaire en sélectionnant une autre configuration
3. Cochez **"Conserver les codes existants"** (recommandé)
4. Cliquez sur **"Prévisualiser"**

**Options**:

| Option | Description | Recommandation |
|--------|-------------|----------------|
| Conserver les codes existants | Les codes actuels sont préservés | ✅ Recommandé pour éviter les ruptures |
| Générer de nouveaux codes | De nouveaux codes sont créés selon les configurations | ⚠️ Utiliser uniquement si nécessaire |

💡 **Astuce**: Les mappings suggérés (marqués ✓) sont basés sur les types de documents et configurations existantes.

---

### 3️⃣ Prévisualisation

**Objectif**: Vérifier l'import avant exécution

**Informations affichées**:
- Nombre total de documents à importer
- Répartition par configuration
- Avertissements éventuels
- Erreurs bloquantes (si présentes)

**Actions**:
- ✅ Si tout est correct: cliquez sur **"Lancer l'import"**
- ⬅️ Si modifications nécessaires: cliquez sur **"Retour"**

⚠️ **Attention**: L'import ne peut pas être lancé si des erreurs sont détectées.

---

### 4️⃣ Résultats

**Objectif**: Vérifier le succès de l'import

**Informations affichées**:
- Nombre de documents importés avec succès
- Nombre d'erreurs (si présentes)
- Liste détaillée des erreurs

**Cas de succès complet**:
```
✅ Import terminé
42 documents importés avec succès
```

**Cas de succès partiel**:
```
⚠️ Import terminé avec erreurs
38 documents importés avec succès / 4 erreurs
```

💡 **Astuce**: En cas d'erreurs, notez les IDs des documents concernés et contactez l'administrateur.

---

## Que se passe-t-il lors de l'import ?

### Avec préservation des codes (recommandé)

1. Le document est associé à la configuration de nomenclature
2. Le code existant est conservé
3. La séquence est synchronisée automatiquement
4. Le statut du code passe à `active`

**Exemple**:
```
Avant: Document #123 - Code: DOC-PROC-2024-0042 (sans config)
Après: Document #123 - Code: DOC-PROC-2024-0042 (config: "Documents Procédures")
```

### Sans préservation des codes

1. Le document est associé à la configuration de nomenclature
2. Un nouveau code est généré selon la configuration
3. L'ancien code est remplacé
4. Le statut du code passe à `active`

**Exemple**:
```
Avant: Document #123 - Code: DOC-PROC-2024-0042
Après: Document #123 - Code: DOC-PRO-24-0043 (nouveau code généré)
```

⚠️ **Attention**: Sans préservation, les références externes aux anciens codes seront cassées.

---

## Synchronisation des séquences

Le système synchronise automatiquement les séquences pour éviter les doublons:

**Exemple**:
- Vous importez un document avec le code `DOC-2024-0042`
- La séquence actuelle est à `0030`
- Après import, la séquence passe à `0042`
- Le prochain document créé aura le code `DOC-2024-0043`

✅ **Avantage**: Aucun risque de collision de codes.

---

## Cas d'usage courants

### Cas 1: Migration initiale

**Contexte**: Première utilisation du système de nomenclature flexible

**Recommandations**:
1. Créez d'abord vos configurations de nomenclature
2. Lancez l'analyse pour voir les documents à migrer
3. Utilisez les mappings suggérés
4. **Conservez les codes existants**
5. Exécutez l'import

### Cas 2: Import par site

**Contexte**: Migration progressive site par site

**Recommandations**:
1. Sélectionnez un site spécifique à l'étape 1
2. Créez des configurations spécifiques au site si nécessaire
3. Importez site par site
4. Vérifiez les résultats avant de passer au site suivant

### Cas 3: Réorganisation complète

**Contexte**: Refonte de la nomenclature documentaire

**Recommandations**:
1. Créez les nouvelles configurations de nomenclature
2. Mappez manuellement chaque type vers la nouvelle configuration
3. **Ne conservez pas les codes existants**
4. Communiquez les nouveaux codes aux équipes
5. Mettez à jour les références externes

---

## Dépannage

### Problème: Aucun document trouvé

**Causes possibles**:
- Les documents ont déjà une configuration assignée
- Filtre de site trop restrictif
- Aucun document dans le système

**Solution**: Vérifiez les filtres et la base de données.

### Problème: Erreurs lors de la prévisualisation

**Causes possibles**:
- Configuration inexistante ou désactivée
- Mapping incomplet

**Solution**: Vérifiez que toutes les configurations sont actives et que tous les types sont mappés.

### Problème: Erreurs lors de l'exécution

**Causes possibles**:
- Conflit de code (si génération de nouveaux codes)
- Erreur de base de données
- Permission insuffisante

**Solution**: Consultez les logs Laravel et contactez l'administrateur.

---

## Bonnes pratiques

✅ **À faire**:
- Créer les configurations avant l'import
- Utiliser les mappings suggérés comme point de départ
- Conserver les codes existants lors de la première migration
- Tester sur un petit échantillon avant l'import complet
- Vérifier les résultats après import

❌ **À éviter**:
- Importer sans prévisualisation
- Générer de nouveaux codes sans raison valable
- Ignorer les avertissements
- Importer tous les sites en une fois sans test

---

## Support

En cas de problème:
1. Consultez cette documentation
2. Vérifiez les logs d'erreur dans les résultats
3. Contactez l'administrateur système
4. Fournissez les IDs des documents en erreur

---

**Dernière mise à jour**: 2024
