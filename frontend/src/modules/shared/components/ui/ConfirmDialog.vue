<template>
  <v-dialog
    v-model="isOpen"
    max-width="500"
    persistent
    :scrim="true"
  >
    <v-card elevation="8" rounded="xl">
      <!-- Icon Header -->
      <div class="text-center pt-8 pb-4">
        <v-avatar
          class="dialog-icon"
          :color="iconColor"
          size="64"
        >
          <v-icon :color="iconTextColor" size="32">
            {{ icon }}
          </v-icon>
        </v-avatar>
      </div>

      <!-- Content -->
      <v-card-title class="text-h5 font-weight-bold text-center px-6">
        {{ title }}
      </v-card-title>

      <v-card-text v-if="message" class="text-center text-body-1 px-6 pb-6">
        {{ message }}
      </v-card-text>

      <!-- Actions -->
      <v-card-actions class="px-6 pb-6 pt-2">
        <v-spacer />
        <v-btn
          v-if="!hideCancel"
          class="px-6"
          :color="cancelColor"
          size="large"
          variant="text"
          @click="handleCancel"
        >
          {{ cancelText }}
        </v-btn>
        <v-btn
          class="px-6"
          :color="confirmColor"
          :loading="loading"
          size="large"
          :variant="confirmVariant"
          @click="handleConfirm"
        >
          {{ confirmText }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  /**
   * Composant de dialogue de confirmation moderne
   *
   * @example
   * ```vue
   * <template>
   *   <v-btn @click="showConfirmation">Supprimer</v-btn>
   *   <ConfirmDialog
   *     v-model="dialogOpen"
   *     type="danger"
   *     title="Confirmer la suppression"
   *     message="Êtes-vous sûr de vouloir supprimer cet élément ?"
   *     @confirm="handleDelete"
   *   />
   * </template>
   *
   * <script setup>
   * import { ref } from 'vue'
   * import ConfirmDialog from '@/modules/shared/components/ui/ConfirmDialog.vue'
   *
   * const dialogOpen = ref(false)
   *
   * const showConfirmation = () => {
   *   dialogOpen.value = true
   * }
   *
   * const handleDelete = async () => {
   *   // Perform deletion
   *   await deleteItem()
   * }
   * ```
   */

  interface Props {
    modelValue: boolean
    /** Type de dialogue (définit l'icône et la couleur) */
    type?: 'info' | 'warning' | 'danger' | 'success'
    /** Titre du dialogue */
    title?: string
    /** Message du dialogue */
    message?: string
    /** Texte du bouton de confirmation */
    confirmText?: string
    /** Texte du bouton d'annulation */
    cancelText?: string
    /** Masquer le bouton d'annulation */
    hideCancel?: boolean
    /** État de chargement */
    loading?: boolean
    /** Couleur du bouton de confirmation */
    confirmColor?: string
    /** Couleur du bouton d'annulation */
    cancelColor?: string
    /** Variante du bouton de confirmation */
    confirmVariant?: 'text' | 'flat' | 'elevated' | 'tonal' | 'outlined' | 'plain'
  }

  const props = withDefaults(defineProps<Props>(), {
    type: 'info',
    title: 'Confirmation',
    message: '',
    confirmText: 'Confirmer',
    cancelText: 'Annuler',
    hideCancel: false,
    loading: false,
    confirmVariant: 'flat',
  })

  const emit = defineEmits<{
    'update:modelValue': [value: boolean]
    'confirm': []
    'cancel': []
  }>()

  const isOpen = computed({
    get: () => props.modelValue,
    set: value => emit('update:modelValue', value),
  })

  const icon = computed(() => {
    const icons = {
      info: 'mdi-information',
      warning: 'mdi-alert',
      danger: 'mdi-alert-octagon',
      success: 'mdi-check-circle',
    }
    return icons[props.type]
  })

  const iconColor = computed(() => {
    if (props.type === 'info') return 'info-lighten-4'
    if (props.type === 'warning') return 'warning-lighten-4'
    if (props.type === 'danger') return 'error-lighten-4'
    if (props.type === 'success') return 'success-lighten-4'
    return 'info-lighten-4'
  })

  const iconTextColor = computed(() => {
    if (props.type === 'info') return 'info-darken-2'
    if (props.type === 'warning') return 'warning-darken-2'
    if (props.type === 'danger') return 'error-darken-2'
    if (props.type === 'success') return 'success-darken-2'
    return 'info-darken-2'
  })

  const confirmColor = computed(() => {
    if (props.confirmColor) return props.confirmColor
    if (props.type === 'danger') return 'error'
    if (props.type === 'warning') return 'warning'
    if (props.type === 'success') return 'success'
    return 'primary'
  })

  const cancelColor = computed(() => {
    return props.cancelColor || 'grey'
  })

  function handleConfirm () {
    emit('confirm')
  }

  function handleCancel () {
    isOpen.value = false
    emit('cancel')
  }
</script>

<style scoped>
.dialog-icon {
  animation: iconScale 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes iconScale {
  0% {
    transform: scale(0);
  }
  50% {
    transform: scale(1.1);
  }
  100% {
    transform: scale(1);
  }
}

:deep(.v-overlay__scrim) {
  backdrop-filter: blur(4px);
}
</style>
