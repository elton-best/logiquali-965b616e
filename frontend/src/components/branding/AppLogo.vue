<template>
  <div
    class="app-logo"
    :class="[
      `app-logo--${variant}`,
      `app-logo--${surface}`,
      { 'app-logo--stacked': stacked, 'app-logo--clickable': clickable },
    ]"
  >
    <span v-if="framed" class="app-logo__frame">
      <img
        :alt="alt"
        class="app-logo__image"
        :src="logoSrc"
      >
    </span>
    <img
      v-else
      :alt="alt"
      class="app-logo__image"
      :src="logoSrc"
    >

    <div v-if="showText && variant === 'mark'" class="app-logo__text">
      <slot>
        <div class="app-logo__title">{{ title }}</div>
        <div v-if="subtitle" class="app-logo__subtitle">{{ subtitle }}</div>
      </slot>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  interface Props {
    variant?: 'mark' | 'full'
    surface?: 'light' | 'dark'
    title?: string
    subtitle?: string
    alt?: string
    showText?: boolean
    framed?: boolean
    stacked?: boolean
    clickable?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    variant: 'full',
    surface: 'light',
    title: 'BestQHSE',
    subtitle: '',
    alt: 'Logo BestQHSE',
    showText: false,
    framed: false,
    stacked: false,
    clickable: false,
  })

  const logoSrc = computed(() => props.variant === 'mark'
    ? '/branding/bestqhse-mark.png'
    : '/branding/bestqhse-full.png')
</script>

<style scoped>
.app-logo {
  display: inline-flex;
  align-items: center;
  gap: 0.85rem;
  min-width: 0;
}

.app-logo--stacked {
  flex-direction: column;
  gap: 0.65rem;
}

.app-logo--clickable {
  transition: transform 0.2s ease;
}

.app-logo--clickable:hover {
  transform: translateY(-1px);
}

.app-logo__frame {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.35rem;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.96);
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
}

.app-logo__image {
  display: block;
  height: auto;
  max-height: 100%;
  object-fit: contain;
}

.app-logo--mark .app-logo__image {
  width: 46px;
  min-width: 46px;
}

.app-logo--full .app-logo__image {
  width: 176px;
  min-width: 176px;
}

.app-logo--dark .app-logo__title,
.app-logo--dark .app-logo__subtitle {
  color: #fff;
}

.app-logo__text {
  min-width: 0;
}

.app-logo__title {
  font-size: 1rem;
  font-weight: 700;
  line-height: 1.1;
  color: #0f172a;
}

.app-logo__subtitle {
  margin-top: 0.18rem;
  font-size: 0.78rem;
  line-height: 1.2;
  color: rgba(15, 23, 42, 0.66);
}

.app-logo--stacked .app-logo__text {
  text-align: center;
}
</style>
