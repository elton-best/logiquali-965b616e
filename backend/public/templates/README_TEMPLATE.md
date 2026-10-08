# 📋 Template d'Import de Normes ISO

## 📄 Fichier : `norme_iso_template.csv`

Ce fichier sert de modèle pour importer une norme ISO complète dans BestQHSE via le SuperAdmin.

---

## 🎯 Structure du Fichier

### Colonnes Obligatoires

| Colonne           | Description                 | Exemple                                                      |
| ----------------- | --------------------------- | ------------------------------------------------------------ |
| **Type de ligne** | Type d'élément              | `Norme`, `Chapitre`, `Section`, `Sous-section`, `Paragraphe` |
| **Code**          | Numéro de référence         | `ISO 9001:2015`, `4.1`, `10.2.1`                             |
| **Titre**         | Intitulé de l'élément       | `Compréhension de l'organisme`                               |
| **Contenu**       | Texte complet de l'exigence | `L'organisme doit déterminer...`                             |
| **Notes**         | Remarques optionnelles      | `Nouvelle exigence v2015`                                    |

---

## 📖 Types de Ligne

### 1. **Norme** (1 seule ligne)

- **Code** : Référence ISO complète (ex: `ISO 9001:2015`, `ISO 14001:2015`)
- **Titre** : Nom complet de la norme
- **Exemple** :
    ```csv
    "Norme","ISO 9001:2015","Management de la qualité - Exigences","",""
    ```

### 2. **Chapitre** (niveau 1)

- **Code** : Numéro du chapitre (ex: `1`, `4`, `10`)
- **Titre** : Nom du chapitre
- **Exemple** :
    ```csv
    "Chapitre","4","Contexte de l'organisme","L'organisme doit déterminer...","Chapitre clé"
    ```

### 3. **Section** (niveau 2)

- **Code** : Numéro avec 1 décimale (ex: `4.1`, `9.2`, `10.3`)
- **Titre** : Nom de la section
- **Exemple** :
    ```csv
    "Section","4.1","Compréhension de l'organisme et de son contexte","Déterminer les enjeux...","Important"
    ```

### 4. **Sous-section** (niveau 3)

- **Code** : Numéro avec 2 décimales (ex: `4.2.1`, `10.2.1`)
- **Titre** : Nom de la sous-section
- **Exemple** :
    ```csv
    "Sous-section","4.2.1","Identification des parties intéressées","Lister toutes les parties...","Optionnel"
    ```

### 5. **Paragraphe** (niveau 4+)

- **Code** : Numéro avec 3+ décimales ou lettres (ex: `10.2.1.a`, `7.1.5.2`)
- **Titre** : Nom du paragraphe
- **Exemple** :
    ```csv
    "Paragraphe","10.2.1.a","Analyse de la cause racine","Identifier la cause par méthode 5 Pourquoi","Méthodologie"
    ```

---

## ✅ Règles de Formatage

### Hiérarchie Automatique

Le système reconstruit automatiquement la hiérarchie en fonction des codes :

- `4` → Chapitre 4
- `4.1` → Section 4.1 (parent : Chapitre 4)
- `4.2.1` → Sous-section 4.2.1 (parent : Section 4.2)

### Encodage

- **Format** : UTF-8 (pour les caractères accentués)
- **Séparateur** : Virgule `,`
- **Encadrement** : Guillemets doubles `"` pour tout texte avec virgules

### Contenu

- **Obligatoire** : Type, Code, Titre
- **Optionnel** : Contenu (peut être vide), Notes
- **Taille max** :
    - Titre : 500 caractères
    - Contenu : Illimité (TEXT)
    - Notes : 1000 caractères

---

## 🚀 Utilisation

### 1. **Télécharger le Template**

```
GET /api/v1/superadmin/norms/download-template
```

Retourne : `norme_iso_template.csv`

### 2. **Remplir le Fichier**

- Respecter l'ordre hiérarchique (chapitre avant sections)
- Numéroter correctement (pas de sauts)
- Remplir les contenus réglementaires

### 3. **Importer**

```
POST /api/v1/superadmin/norms/import
Content-Type: multipart/form-data

{
  "file": norme_iso_9001.csv,
  "replace_existing": false
}
```

### 4. **Vérifier**

```
GET /api/v1/superadmin/norms/{id}/sections
```

Affiche la structure importée avec hiérarchie.

---

## 📊 Exemple Complet : ISO 9001 Structure

```csv
"Type de ligne","Code","Titre","Contenu","Notes"
"Norme","ISO 9001:2015","Systèmes de management de la qualité - Exigences","","Version 2015"
"Chapitre","0","Introduction","La norme spécifie les exigences relatives au SMQ...","Préambule"
"Chapitre","1","Domaine d'application","Spécifie les exigences pour un SMQ...","Scope"
"Chapitre","4","Contexte de l'organisme","Déterminer les enjeux...","Fondamental"
"Section","4.1","Compréhension de l'organisme et de son contexte","Enjeux externes et internes...","SWOT"
"Section","4.2","Compréhension des besoins et attentes des parties intéressées","Identifier les parties...","Cartographie"
"Section","4.3","Détermination du domaine d'application du SMQ","Définir les limites...","Périmètre"
"Section","4.4","Système de management de la qualité et ses processus","Établir, documenter, mettre en œuvre...","Approche processus"
"Sous-section","4.4.1","Généralités","L'organisme doit déterminer les processus...","ISO 9001:2015"
"Sous-section","4.4.2","Informations documentées","Conserver les informations...","Documentation"
"Chapitre","5","Leadership","Direction et engagement...","Direction"
...
```

---

## ⚠️ Erreurs Courantes

| Erreur                  | Cause                               | Solution                 |
| ----------------------- | ----------------------------------- | ------------------------ |
| **Hiérarchie invalide** | Section 4.2 avant 4.1               | Trier par code croissant |
| **Code en double**      | Deux lignes avec `4.1`              | Vérifier unicité         |
| **Parent manquant**     | `4.2.1` sans `4.2`                  | Créer section parente    |
| **Type invalide**       | `"Chapter"` au lieu de `"Chapitre"` | Utiliser types FR        |
| **Encodage**            | Caractères �                        | Enregistrer en UTF-8     |

---

## 🔄 Mise à Jour d'une Norme

Pour mettre à jour une norme existante :

1. **Export actuel** :

    ```
    GET /api/v1/superadmin/norms/{id}/export
    ```

2. **Modifier le fichier** (ajouts/corrections)

3. **Ré-importer avec remplacement** :
    ```
    POST /api/v1/superadmin/norms/import
    {
      "file": norme_modifiee.csv,
      "replace_existing": true,
      "norm_id": 123
    }
    ```

---

## 📚 Normes Supportées

- ✅ **ISO 9001** : Qualité
- ✅ **ISO 14001** : Environnement
- ✅ **ISO 45001** : Santé et Sécurité
- ✅ **ISO 27001** : Sécurité de l'information
- ✅ **Autres normes ISO** avec structure similaire

---

## 💡 Conseils

1. **Commencer simple** : Importer d'abord chapitres, puis sections
2. **Valider progressivement** : Tester avec 1 chapitre avant import complet
3. **Sauvegarder** : Garder CSV source pour futures modifications
4. **Versionner** : Nommer fichiers `ISO_9001_2015_v1.csv`, `v2`, etc.
5. **Tester** : Vérifier via interface avant déploiement entreprises

---

## 🆘 Support

En cas de problème d'import :

- Vérifier logs : `storage/logs/laravel.log`
- Ligne d'erreur indiquée dans réponse API
- Contact : support@BestQHSE.com

---

**Version** : 1.0  
**Date** : Janvier 2026  
**Compatibilité** : BestQHSE v1.0+
