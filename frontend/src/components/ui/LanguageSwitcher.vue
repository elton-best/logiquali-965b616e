<script setup lang="ts">
  import { computed } from 'vue'
  import { useI18n } from 'vue-i18n'

  const { locale } = useI18n()
  const supportedLocales = [
    { value: 'fr', label: 'Français' },
    { value: 'en', label: 'English' },
  ] as const

  const currentLanguageLabel = computed(() => {
    return supportedLocales.find(item => item.value === locale.value)?.label || 'Français'
  })

  function changeLocale (newLocale: 'fr' | 'en') {
    locale.value = newLocale
    localStorage.setItem('locale', newLocale)
    document.documentElement.setAttribute('lang', newLocale)
  }
</script>

<template>
  <v-menu location="bottom end" offset="8">
    <template #activator="{ props }">
      <v-btn
        v-bind="props"
        prepend-icon="mdi-translate"
        size="small"
        variant="text"
      >
        {{ currentLanguageLabel }}
      </v-btn>
    </template>

    <v-list density="compact" min-width="160">
      <v-list-item
        v-for="item in supportedLocales"
        :key="item.value"
        :active="locale === item.value"
        @click="changeLocale(item.value)"
      >
        <template #prepend>
          <v-icon v-if="locale === item.value" color="primary">mdi-check</v-icon>
          <v-icon v-else>mdi-web</v-icon>
        </template>
        <v-list-item-title>{{ item.label }}</v-list-item-title>
      </v-list-item>
    </v-list>
  </v-menu>
</template>
