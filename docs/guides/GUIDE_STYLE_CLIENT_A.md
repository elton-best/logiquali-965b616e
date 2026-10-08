# 🎨 Guide de Style - Client A

## 📐 Système de Design

### Espacement

Utiliser un système d'espacement basé sur 4px :

```css
4px   → gap-1, pa-1, ma-1
8px   → gap-2, pa-2, ma-2
12px  → gap-3, pa-3, ma-3
16px  → gap-4, pa-4, ma-4
24px  → gap-6, pa-6, ma-6
32px  → gap-8, pa-8, ma-8
48px  → gap-12, pa-12, ma-12
```

**Exemple :**

```vue
<v-card class="pa-6 mb-4">
  <v-card-text class="pa-4">
    <!-- Contenu -->
  </v-card-text>
</v-card>
```

### Border-radius

3 tailles principales :

```css
8px   → rounded-lg    (petits éléments : chips, buttons)
12px  → rounded-xl    (cards, modals)
16px  → rounded-2xl   (hero sections, grandes cards)
```

**Exemple :**

```vue
<v-card rounded="xl">
  <v-btn rounded="lg">Action</v-btn>
</v-card>
```

### Shadows

Système progressif en 5 niveaux :

```css
--clienta-shadow-xs   → 0 1px 2px rgba(0, 0, 0, 0.04)
--clienta-shadow-sm   → 0 2px 4px rgba(0, 0, 0, 0.04)
--clienta-shadow-md   → 0 4px 8px rgba(0, 0, 0, 0.05)
--clienta-shadow-lg   → 0 8px 16px rgba(0, 0, 0, 0.06)
--clienta-shadow-xl   → 0 12px 24px rgba(0, 0, 0, 0.08)
```

**Utilisation :**

```vue
<style scoped>
.my-card {
  box-shadow: var(--clienta-shadow-sm);
}

.my-card:hover {
  box-shadow: var(--clienta-shadow-md);
}
</style>
```

## 🎨 Couleurs

### Palette Primaire

```vue
<!-- Bleu professionnel -->
<v-btn color="primary">Action</v-btn>
<v-chip color="primary" variant="tonal">Info</v-chip>

<!-- CSS -->
<style>
.my-element {
  color: var(--clienta-primary);
  background: var(--clienta-primary-50);
}
</style>
```

### Palette Sémantique

```vue
<!-- Success (Vert) -->
<v-chip color="success">Validé</v-chip>

<!-- Warning (Orange) -->
<v-chip color="warning">En attente</v-chip>

<!-- Error (Rouge) -->
<v-chip color="error">Erreur</v-chip>

<!-- Info (Bleu ciel) -->
<v-chip color="info">Information</v-chip>
```

### Textes

```vue
<!-- Texte principal -->
<h1 class="text-h4">Titre principal</h1>

<!-- Texte secondaire -->
<p class="text-body-2 text-medium-emphasis">Description</p>

<!-- Texte muted -->
<span class="text-caption">Détails</span>
```

## 🧩 Composants

### Cards

**Standard :**

```vue
<v-card elevation="2" rounded="xl">
  <v-card-title class="pa-6">
    <div class="d-flex align-center gap-2">
      <v-icon color="primary">mdi-chart-line</v-icon>
      <span class="text-h6 font-weight-bold">Titre</span>
    </div>
  </v-card-title>
  <v-divider />
  <v-card-text class="pa-6">
    Contenu
  </v-card-text>
</v-card>
```

**Avec hover :**

```vue
<v-card class="stat-card" elevation="2" hover rounded="xl">
  <!-- Contenu -->
</v-card>

<style scoped>
.stat-card {
  transition: all 0.25s ease;
  border: 1px solid var(--clienta-border-light);
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--clienta-shadow-lg) !important;
}
</style>
```

### Boutons

**Primaire :**

```vue
<v-btn
  color="primary"
  prepend-icon="mdi-plus"
  rounded="lg"
  size="large"
  variant="elevated"
>
  Créer
</v-btn>
```

**Secondaire :**

```vue
<v-btn color="primary" rounded="lg" variant="tonal">
  Annuler
</v-btn>
```

**Tertiaire :**

```vue
<v-btn color="primary" rounded="lg" variant="text">
  Voir plus
</v-btn>
```

### Chips

**Standard :**

```vue
<v-chip color="primary" size="small" variant="tonal">
  Actif
</v-chip>
```

**Avec icône :**

```vue
<v-chip color="success" prepend-icon="mdi-check" size="small" variant="flat">
  Validé
</v-chip>
```

### Tables

```vue
<v-table>
  <thead>
    <tr>
      <th>Nom</th>
      <th>Statut</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <tr v-for="item in items" :key="item.id">
      <td>{{ item.name }}</td>
      <td>
        <v-chip 
          :color="getStatusColor(item.status)" 
          size="small"
        >
          {{ item.status }}
        </v-chip>
      </td>
      <td>
        <v-btn 
          icon 
          size="small" 
          variant="text"
        >
          <v-icon>mdi-pencil</v-icon>
        </v-btn>
      </td>
    </tr>
  </tbody>
</v-table>
```

### Formulaires

```vue
<v-form>
  <v-row>
    <v-col cols="12" md="6">
      <v-text-field
        v-model="form.name"
        density="comfortable"
        label="Nom"
        placeholder="Entrez le nom"
        variant="outlined"
      />
    </v-col>
    
    <v-col cols="12" md="6">
      <v-select
        v-model="form.status"
        density="comfortable"
        :items="statusOptions"
        label="Statut"
        variant="outlined"
      />
    </v-col>
    
    <v-col cols="12">
      <v-textarea
        v-model="form.description"
        density="comfortable"
        label="Description"
        placeholder="Entrez la description"
        rows="3"
        variant="outlined"
      />
    </v-col>
  </v-row>
  
  <div class="d-flex gap-2 justify-end mt-4">
    <v-btn 
      color="primary" 
      rounded="lg"
      variant="text"
      @click="cancel"
    >
      Annuler
    </v-btn>
    <v-btn 
      color="primary" 
      rounded="lg"
      variant="elevated"
      @click="submit"
    >
      Enregistrer
    </v-btn>
  </div>
</v-form>
```

### Listes

```vue
<v-list>
  <v-list-item
    v-for="item in items"
    :key="item.id"
    class="mb-1"
  >
    <template #prepend>
      <v-avatar :color="item.color" size="40" variant="tonal">
        <v-icon :color="item.color">{{ item.icon }}</v-icon>
      </v-avatar>
    </template>
    
    <v-list-item-title class="font-weight-medium">
      {{ item.title }}
    </v-list-item-title>
    <v-list-item-subtitle class="text-caption">
      {{ item.subtitle }}
    </v-list-item-subtitle>
    
    <template #append>
      <v-chip 
        :color="item.chipColor" 
        size="small"
      >
        {{ item.badge }}
      </v-chip>
    </template>
  </v-list-item>
</v-list>
```

## 🎭 Animations

### Transitions

```vue
<style scoped>
.my-element {
  transition: all 0.2s ease;
}

.my-element:hover {
  transform: translateY(-2px);
}
</style>
```

### Animations CSS

```vue
<template>
  <div class="fade-in">Contenu animé</div>
</template>

<style scoped>
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.fade-in {
  animation: fadeIn 0.3s ease-out;
}
</style>
```

## 📱 Responsive

### Breakpoints Vuetify

```
xs: < 600px   (mobile)
sm: 600-960px (tablet)
md: 960-1280px (desktop)
lg: 1280-1920px (large desktop)
xl: > 1920px (extra large)
```

### Exemple

```vue
<v-row>
  <v-col 
    cols="12"      <!-- Mobile: pleine largeur -->
    sm="6"         <!-- Tablet: 50% -->
    md="4"         <!-- Desktop: 33% -->
    lg="3"         <!-- Large: 25% -->
  >
    <v-card>...</v-card>
  </v-col>
</v-row>
```

### Media Queries

```vue
<style scoped>
.my-element {
  padding: 24px;
}

@media (max-width: 960px) {
  .my-element {
    padding: 16px;
  }
}

@media (max-width: 600px) {
  .my-element {
    padding: 12px;
  }
}
</style>
```

## ♿ Accessibilité

### Contrastes

Respecter WCAG 2.1 Level AA :

- Texte normal : 4.5:1 minimum
- Texte large : 3:1 minimum
- Éléments UI : 3:1 minimum

### Focus

```vue
<style scoped>
.my-button:focus-visible {
  outline: 2px solid var(--clienta-primary);
  outline-offset: 2px;
}
</style>
```

### ARIA

```vue
<v-btn aria-label="Fermer le dialogue" icon @click="close">
  <v-icon>mdi-close</v-icon>
</v-btn>

<v-list aria-label="Menu principal" role="navigation">
  <v-list-item role="menuitem">...</v-list-item>
</v-list>
```

### Touch Targets

Minimum 40x40px pour les éléments interactifs :

```vue
<v-btn
  icon
  size="large"  <!-- 48x48px -->
>
  <v-icon>mdi-menu</v-icon>
</v-btn>
```

## 🔧 Bonnes Pratiques

### 1. Composition

```vue
<script setup lang="ts">
import { computed, ref } from "vue";

// Props
interface Props {
  title: string;
  items: Item[];
}

const props = defineProps<Props>();

// State
const loading = ref(false);

// Computed
const filteredItems = computed(() => {
  return props.items.filter((item) => item.active);
});

// Methods
function handleClick() {
  // ...
}
</script>
```

### 2. Nommage

```typescript
// Components: PascalCase
import StatCard from '@/components/StatCard.vue'

// Variables: camelCase
const userName = ref('')
const isLoading = ref(false)

// Constants: UPPER_SNAKE_CASE
const MAX_ITEMS = 100
const API_URL = '/api/v1'

// CSS classes: kebab-case
.stat-card { }
.quick-action-btn { }
```

### 3. Organisation des fichiers

```
components/
├── StatCard.vue
├── PageHeader.vue
└── processes/
    ├── ProcessCard.vue
    └── ProcessList.vue

pages/
├── dashboard.vue
├── sites/
│   ├── index.vue
│   └── [id].vue
└── settings.vue

assets/
├── clienta-theme.css
└── logo.svg
```

### 4. Performance

```vue
<!-- Lazy loading -->
<script setup lang="ts">
const ProcessWidget = defineAsyncComponent(
  () => import('@/components/ProcessWidget.vue')
)
</script>

<!-- v-show vs v-if -->
<div v-show="isVisible">  <!-- Garde dans le DOM -->
<div v-if="shouldRender">  <!-- Retire du DOM -->

<!-- Computed vs Methods -->
const fullName = computed(() => `${firstName} ${lastName}`)  <!-- Cached -->
function getFullName() { return `${firstName} ${lastName}` }  <!-- Recalculé -->
```

## 📚 Ressources

### Documentation

- [Vuetify 3](https://vuetifyjs.com/)
- [Vue 3](https://vuejs.org/)
- [TypeScript](https://www.typescriptlang.org/)

### Outils

- [Material Design Icons](https://pictogrammers.com/library/mdi/)
- [Color Contrast Checker](https://webaim.org/resources/contrastchecker/)
- [Can I Use](https://caniuse.com/)

### Inspiration

- [Dribbble](https://dribbble.com/)
- [Behance](https://www.behance.net/)
- [Awwwards](https://www.awwwards.com/)

---

**Version :** 2.0  
**Dernière mise à jour :** 2025  
**Mainteneur :** Équipe BestQHSE
