<template>
  <div class="p-3 rounded-lg border" :class="cardClasses">
    <div class="flex items-start justify-between gap-2">
      <p class="text-sm flex-1">{{ issue.description }}</p>
      <div class="flex gap-1">
        <v-btn
          icon="mdi-pencil"
          size="x-small"
          variant="text"
          @click="$emit('edit', issue)"
        />
        <v-btn
          color="error"
          icon="mdi-delete"
          size="x-small"
          variant="text"
          @click="$emit('delete', issue)"
        />
      </div>
    </div>
    <div v-if="issue.impact" class="mt-2">
      <Badge size="sm" :variant="getImpactVariant(issue.impact)">
        Impact: {{ issue.impact }}
      </Badge>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { computed } from 'vue'
  import Badge from '@/components/ui/Badge.vue'

  interface Props {
    issue: {
      id: number
      description: string
      impact?: string
      category: string
    }
    variant?: 'default' | 'success' | 'danger' | 'warning' | 'info'
  }

  const props = withDefaults(defineProps<Props>(), {
    variant: 'default',
  })

  defineEmits<{
    edit: [issue: any]
    delete: [issue: any]
  }>()

  const cardClasses = computed(() => {
    const variants = {
      default: 'bg-gray-50 border-gray-200',
      success: 'bg-green-50 border-green-200',
      danger: 'bg-red-50 border-red-200',
      warning: 'bg-orange-50 border-orange-200',
      info: 'bg-blue-50 border-blue-200',
    }
    return variants[props.variant]
  })

  function getImpactVariant (impact: string) {
    const variants: Record<string, any> = {
      low: 'success',
      medium: 'warning',
      high: 'danger',
    }
    return variants[impact] || 'default'
  }
</script>
