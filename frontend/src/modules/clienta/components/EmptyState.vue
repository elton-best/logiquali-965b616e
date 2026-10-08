<template>
  <AppEmptyState
    :description="description"
    :icon="mappedIcon"
    :title="title"
  >
    <template v-if="actionLabel || $slots.action" #action>
      <slot name="action">
        <AppButton
          v-if="actionLabel"
          :variant="mappedVariant"
          @click="$emit('action')"
        >
          <component :is="mappedActionIcon" v-if="mappedActionIcon" class="w-4 h-4 mr-2" />
          {{ actionLabel }}
        </AppButton>
      </slot>
    </template>
  </AppEmptyState>
</template>

<script setup lang="ts">
  import { AlertCircle, FileText, PackageOpen, Plus, Users } from 'lucide-vue-next'
  import { computed } from 'vue'
  import AppButton from '@/components/common/AppButton.vue'
  import AppEmptyState from '@/components/common/AppEmptyState.vue'

  interface Props {
    icon?: string
    iconColor?: string
    iconSize?: number
    title: string
    description?: string
    actionLabel?: string
    actionIcon?: string
    actionColor?: string
    actionVariant?: 'flat' | 'outlined' | 'text' | 'elevated' | 'tonal' | 'plain'
    cardClass?: string
  }

  const props = withDefaults(defineProps<Props>(), {
    icon: 'mdi-package-variant',
    iconColor: 'grey-lighten-1',
    iconSize: 64,
    actionColor: 'primary',
    actionVariant: 'flat',
    cardClass: '',
  })

  defineEmits<{
    (e: 'action'): void
  }>()

  // Map MDI icons to Lucide
  const iconMap: Record<string, any> = {
    'mdi-package-variant': PackageOpen,
    'mdi-file-document': FileText,
    'mdi-account-group': Users,
    'mdi-alert-circle': AlertCircle,
  }

  const mappedIcon = computed(() => iconMap[props.icon] || PackageOpen)

  const actionIconMap: Record<string, any> = {
    'mdi-plus': Plus,
  }

  const mappedActionIcon = computed(() => props.actionIcon ? actionIconMap[props.actionIcon] : null)

  // Map Vuetify variants to AppButton variants
  const variantMap: Record<string, 'primary' | 'secondary' | 'outline' | 'ghost'> = {
    flat: 'primary',
    elevated: 'primary',
    tonal: 'secondary',
    outlined: 'outline',
    text: 'ghost',
    plain: 'ghost',
  }

  const mappedVariant = computed(() => variantMap[props.actionVariant] || 'primary')
</script>
