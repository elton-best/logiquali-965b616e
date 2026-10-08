<template>
  <v-card class="text-center pa-12" elevation="0">
    <v-icon
      class="mb-4 opacity-20"
      :color="iconColor"
      :icon="icon"
      size="120"
    />
    <h3 class="text-h5 font-weight-medium mb-3">
      {{ title }}
    </h3>
    <p class="text-body-1 text-medium-emphasis mb-6">
      {{ subtitle }}
    </p>
    <v-btn
      v-if="buttonText"
      :color="buttonColor"
      :prepend-icon="buttonIcon"
      rounded
      size="large"
      variant="flat"
      @click="$emit('action')"
    >
      {{ buttonText }}
    </v-btn>
  </v-card>
</template>

<script setup lang="ts">
  interface Props {
    icon?: string
    iconColor?: string
    title?: string
    subtitle?: string
    buttonText?: string
    buttonIcon?: string
    buttonColor?: string
  }

  withDefaults(defineProps<Props>(), {
    icon: 'mdi-clipboard-check-outline',
    iconColor: 'primary',
    title: 'Aucun audit trouvé',
    subtitle: 'Commencez par planifier votre premier audit QHSE',
    buttonText: 'Planifier un audit',
    buttonIcon: 'mdi-plus',
    buttonColor: 'primary',
  })

  defineEmits<{
    (e: 'action'): void
  }>()
</script>

<style scoped>
.v-icon {
  filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.05));
}
</style>
