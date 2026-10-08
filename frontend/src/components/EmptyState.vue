<template>
  <div class="text-center pa-12">
    <v-icon class="mb-4" :color="iconColor" :size="iconSize">
      {{ icon }}
    </v-icon>

    <h3 class="text-h6 font-weight-bold mb-2">{{ title }}</h3>

    <p class="text-body-2 text-medium-emphasis mb-4">{{ subtitle }}</p>

    <slot name="actions">
      <v-btn
        v-if="actionLabel"
        :color="actionColor"
        :prepend-icon="actionIcon"
        size="large"
        variant="flat"
        @click="$emit('action')"
      >
        {{ actionLabel }}
      </v-btn>
    </slot>
  </div>
</template>

<script setup lang="ts">
  interface Props {
    icon?: string
    iconSize?: string | number
    iconColor?: string
    title?: string
    subtitle?: string
    actionLabel?: string
    actionIcon?: string
    actionColor?: string
  }

  withDefaults(defineProps<Props>(), {
    icon: 'mdi-inbox',
    iconSize: 64,
    iconColor: 'grey-lighten-1',
    title: 'Aucune donnée',
    subtitle: 'Aucun élément à afficher pour le moment',
    actionLabel: '',
    actionIcon: 'mdi-plus',
    actionColor: 'primary',
  })

  defineEmits<{
    action: []
  }>()
</script>
