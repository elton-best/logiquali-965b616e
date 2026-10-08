# 🎨 Guide de Style UI/UX - BestQHSE

## Vue d'ensemble

Ce guide définit les principes de design, la palette de couleurs, la typographie et les composants pour créer une expérience utilisateur moderne, intuitive et élégante.

---

## 🎯 Principes de Design

### 1. Clarté et Simplicité

- **Hiérarchie visuelle claire** : Les éléments importants doivent se démarquer
- **Espaces blancs généreux** : Respiration entre les éléments (24px minimum)
- **Contenu centré** : Max-width de 1600px pour une lecture optimale

### 2. Cohérence

- **Composants réutilisables** : Même style pour les mêmes actions
- **Espacement systématique** : Multiples de 4px (4, 8, 12, 16, 24, 32, 48)
- **Animations fluides** : Transitions de 200-300ms avec cubic-bezier

### 3. Accessibilité

- **Contraste minimum** : 4.5:1 pour le texte normal, 3:1 pour le texte large
- **Taille des cibles** : Minimum 40px pour les éléments interactifs
- **Navigation au clavier** : Focus visible sur tous les éléments

---

## 🎨 Palette de Couleurs

### Couleurs Principales

```css
/* Primary - Bleu professionnel */
--primary: #5b8dd9;
--primary-light: #7ba5e3;
--primary-dark: #4a71b0;
--primary-bg: rgba(91, 141, 217, 0.1);

/* Success - Vert positif */
--success: #22c55e;
--success-light: #4ade80;
--success-dark: #16a34a;
--success-bg: rgba(34, 197, 94, 0.1);

/* Warning - Orange attention */
--warning: #f59e0b;
--warning-light: #fbbf24;
--warning-dark: #d97706;
--warning-bg: rgba(245, 158, 11, 0.1);

/* Error - Rouge alerte */
--error: #ef4444;
--error-light: #f87171;
--error-dark: #dc2626;
--error-bg: rgba(239, 68, 68, 0.1);

/* Info - Bleu ciel */
--info: #3b82f6;
--info-light: #60a5fa;
--info-dark: #2563eb;
--info-bg: rgba(59, 130, 246, 0.1);
```

### Couleurs Neutres

```css
/* Surfaces */
--surface: #ffffff;
--surface-variant: #f8fafc;
--surface-hover: #f1f5f9;

/* Texte */
--text-primary: #1e293b;
--text-secondary: #64748b;
--text-disabled: #94a3b8;

/* Bordures */
--border: #e2e8f0;
--border-light: #f1f5f9;
--divider: rgba(0, 0, 0, 0.12);
```

### Couleurs Sémantiques

```css
/* États */
--purple: #a855f7;
--amber: #f59e0b;
--teal: #14b8a6;
--pink: #ec4899;
--indigo: #6366f1;
```

---

## 📝 Typographie

### Famille de Polices

```css
--font-family:
  "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
--font-mono: "Fira Code", "Courier New", monospace;
```

### Échelle Typographique

```css
/* Titres */
--text-h1: 2.5rem; /* 40px */
--text-h2: 2rem; /* 32px */
--text-h3: 1.75rem; /* 28px */
--text-h4: 1.5rem; /* 24px */
--text-h5: 1.25rem; /* 20px */
--text-h6: 1.125rem; /* 18px */

/* Corps de texte */
--text-body-1: 1rem; /* 16px */
--text-body-2: 0.875rem; /* 14px */
--text-caption: 0.75rem; /* 12px */

/* Poids */
--font-light: 300;
--font-regular: 400;
--font-medium: 500;
--font-semibold: 600;
--font-bold: 700;
```

---

## 🔲 Composants

### Cartes (Cards)

```css
/* Style par défaut */
.v-card {
  border-radius: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
  transition: all 0.2s ease;
}

.v-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
  transform: translateY(-2px);
}

/* Variantes */
.v-card--elevated {
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
}

.v-card--outlined {
  border: 1px solid var(--border);
  box-shadow: none;
}
```

### Boutons

```css
/* Primary */
.v-btn--primary {
  background: linear-gradient(135deg, #5b8dd9 0%, #4a71b0 100%);
  box-shadow: 0 2px 8px rgba(91, 141, 217, 0.3);
}

/* Tonal */
.v-btn--tonal {
  background: var(--primary-bg);
  color: var(--primary);
}

/* Outlined */
.v-btn--outlined {
  border: 1.5px solid var(--border);
  background: transparent;
}

/* Tailles */
.v-btn--small {
  height: 32px;
  padding: 0 12px;
}
.v-btn--default {
  height: 40px;
  padding: 0 16px;
}
.v-btn--large {
  height: 48px;
  padding: 0 24px;
}
```

### Champs de Formulaire

```css
.v-text-field,
.v-textarea,
.v-select {
  border-radius: 8px;
}

.v-text-field--outlined {
  border: 1.5px solid var(--border);
  transition: border-color 0.2s ease;
}

.v-text-field--outlined:focus-within {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px var(--primary-bg);
}
```

### Chips

```css
.v-chip {
  border-radius: 8px;
  font-weight: 500;
  padding: 0 12px;
  height: 28px;
}

.v-chip--small {
  height: 24px;
  padding: 0 8px;
  font-size: 0.75rem;
}
```

---

## 📐 Système d'Espacement

### Échelle d'Espacement

```css
--spacing-xs: 4px;
--spacing-sm: 8px;
--spacing-md: 12px;
--spacing-base: 16px;
--spacing-lg: 24px;
--spacing-xl: 32px;
--spacing-2xl: 48px;
--spacing-3xl: 64px;
```

### Utilisation

- **Marges internes (padding)** : 16px, 24px pour les cartes
- **Marges externes (margin)** : 8px, 16px, 24px entre les éléments
- **Gaps** : 12px, 16px pour les grilles et flex

---

## 🌓 Ombres (Shadows)

### Système d'Ombres

```css
/* Extra Small - Hover subtil */
--shadow-xs: 0 1px 2px rgba(0, 0, 0, 0.05);

/* Small - Cartes au repos */
--shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.08);

/* Medium - Cartes élevées */
--shadow-md: 0 4px 12px rgba(0, 0, 0, 0.12);

/* Large - Modals et dialogs */
--shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.15);

/* Extra Large - Éléments flottants */
--shadow-xl: 0 16px 48px rgba(0, 0, 0, 0.2);
```

---

## 🎭 Animations

### Transitions Standard

```css
/* Rapide - Hover, focus */
--transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);

/* Normal - Changements d'état */
--transition-normal: 200ms cubic-bezier(0.4, 0, 0.2, 1);

/* Lent - Animations complexes */
--transition-slow: 300ms cubic-bezier(0.4, 0, 0.2, 1);
```

### Animations Clés

```css
/* Fade In */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Slide In */
@keyframes slideIn {
  from {
    transform: translateX(-20px);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

/* Scale In */
@keyframes scaleIn {
  from {
    transform: scale(0.95);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}
```

---

## 🎯 Border Radius

```css
--radius-sm: 8px; /* Petits éléments (chips, badges) */
--radius-md: 12px; /* Boutons, inputs */
--radius-lg: 16px; /* Cartes */
--radius-xl: 24px; /* Modals, grandes cartes */
--radius-full: 9999px; /* Éléments circulaires */
```

---

## 📱 Responsive Design

### Breakpoints

```css
--breakpoint-xs: 0px;
--breakpoint-sm: 600px;
--breakpoint-md: 960px;
--breakpoint-lg: 1280px;
--breakpoint-xl: 1920px;
```

### Règles Responsive

- **Mobile First** : Design d'abord pour mobile
- **Touch Targets** : Minimum 44x44px sur mobile
- **Espacement adaptatif** : Réduire les marges sur mobile
- **Navigation** : Hamburger menu < 960px

---

## 🎨 Thème Sombre (Dark Mode)

### Couleurs Adaptées

```css
/* Mode sombre */
--surface-dark: #1e293b;
--surface-variant-dark: #0f172a;
--text-primary-dark: #f1f5f9;
--text-secondary-dark: #cbd5e1;
--border-dark: #334155;
```

---

## ✅ Checklist UI/UX

### Avant de Publier

- [ ] Contraste des couleurs vérifié (WCAG AA)
- [ ] Navigation au clavier fonctionnelle
- [ ] Responsive testé sur mobile/tablette/desktop
- [ ] Animations fluides (60fps)
- [ ] États de chargement présents
- [ ] Messages d'erreur clairs
- [ ] Feedback visuel sur les actions
- [ ] Cohérence des espacements
- [ ] Icônes alignées et cohérentes
- [ ] Typographie hiérarchisée

---

## 🚀 Bonnes Pratiques

### Performance

- Utiliser `elevation="0"` pour les cartes sans ombre
- Préférer `variant="tonal"` aux boutons pleins
- Lazy loading des images et composants lourds

### Accessibilité

- Labels explicites sur tous les champs
- Aria-labels sur les icônes seules
- Focus visible sur tous les éléments interactifs

### Cohérence

- Réutiliser les composants existants
- Respecter la palette de couleurs
- Suivre le système d'espacement

---

**Version** : 1.0  
**Dernière mise à jour** : 2024  
**Maintenu par** : Équipe BestQHSE
