<template>
  <v-card class="gap-item" variant="outlined">
    <v-card-text>
      <div class="d-flex justify-space-between align-start mb-2">
        <div>
          <div class="text-subtitle-1 font-weight-medium">{{ item.name }}</div>
          <div class="text-caption text-medium-emphasis">{{ item.type }}</div>
        </div>
        <v-chip :color="statusColor" size="small">
          {{ statusText }}
        </v-chip>
      </div>

      <div class="d-flex align-center mb-2">
        <div class="mr-4">
          <div class="text-caption">Requis</div>
          <v-chip size="small" variant="outlined">
            {{ item.required_level }}
          </v-chip>
        </div>
        <div class="mr-4">
          <div class="text-caption">Actuel</div>
          <v-chip
            :color="item.current_level ? 'primary' : 'default'"
            size="small"
            :variant="item.current_level ? 'flat' : 'outlined'"
          >
            {{ item.current_level || 'N/A' }}
          </v-chip>
        </div>
        <div v-if="item.priority">
          <div class="text-caption">Priorité</div>
          <v-chip
            :color="priorityColor"
            size="small"
          >
            {{ item.priority }}
          </v-chip>
        </div>
      </div>

      <v-progress-linear
        class="mb-2"
        :color="progressColor"
        height="6"
        :model-value="progressValue"
        rounded
      />

      <div v-if="detailed && item.expiry_date" class="mb-2">
        <div class="text-caption">Expiration: {{ formatDate(item.expiry_date) }}</div>
      </div>

      <div v-if="item.gap_details" class="text-body-2 text-medium-emphasis">
        {{ item.gap_details }}
      </div>

      <div v-if="detailed && item.suggested_actions?.length" class="mt-3">
        <div class="text-caption font-weight-medium mb-1">Actions suggérées:</div>
        <v-list density="compact">
          <v-list-item
            v-for="(action, index) in item.suggested_actions"
            :key="index"
            class="px-0"
            :prepend-icon="getActionIcon(action.type)"
          >
            <v-list-item-title class="text-body-2">{{ action.title }}</v-list-item-title>
            <v-list-item-subtitle v-if="action.duration">
              Durée: {{ action.duration }}
            </v-list-item-subtitle>
          </v-list-item>
        </v-list>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  const props = defineProps<{
    item: any
    detailed?: boolean
  }>()

  const levelOrder = ['base', 'intermediaire', 'avance', 'expert']

  const statusColor = computed(() => {
    if (props.item.status === 'missing') return 'error'
    if (props.item.status === 'insufficient') return 'warning'
    if (props.item.status === 'expired') return 'error'
    return 'success'
  })

  const statusText = computed(() => {
    const statusMap = {
      missing: 'Manquante',
      insufficient: 'Insuffisant',
      expired: 'Expirée',
      adequate: 'Adéquat',
    }
    return statusMap[props.item.status] || props.item.status
  })

  const priorityColor = computed(() => {
    const colors = {
      obligatoire: 'error',
      recommandee: 'warning',
      optionnelle: 'info',
    }
    return colors[props.item.priority] || 'default'
  })

  const progressValue = computed(() => {
    if (!props.item.current_level) return 0

    const currentIndex = levelOrder.indexOf(props.item.current_level)
    const requiredIndex = levelOrder.indexOf(props.item.required_level)

    if (currentIndex >= requiredIndex) return 100

    return Math.max(0, (currentIndex + 1) / (requiredIndex + 1) * 100)
  })

  const progressColor = computed(() => {
    if (progressValue.value >= 100) return 'success'
    if (progressValue.value >= 50) return 'warning'
    return 'error'
  })

  function formatDate (date: string) {
    return new Date(date).toLocaleDateString('fr-FR')
  }

  function getActionIcon (type: string) {
    const icons = {
      formation: 'mdi-school',
      certification: 'mdi-certificate',
      experience: 'mdi-briefcase',
      mentoring: 'mdi-account-supervisor',
    }
    return icons[type] || 'mdi-arrow-right'
  }
</script>

<style scoped>
.gap-item {
  transition: all 0.2s;
}

.gap-item:hover {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}
</style>
