<template>
  <div
    class="competence-cell"
    :class="cellClass"
    @click="$emit('click')"
  >
    <div class="level-indicator">
      <v-icon :color="iconColor" size="16">
        {{ statusIcon }}
      </v-icon>
      <span class="level-text">{{ levelText }}</span>
    </div>

    <div v-if="userCompetence?.expiry_date" class="expiry-info">
      <v-chip
        :color="expiryColor"
        size="x-small"
        variant="flat"
      >
        {{ expiryText }}
      </v-chip>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  const props = defineProps<{
    userCompetence?: any
    requiredCompetence: any
  }>()

  defineEmits<{
    click: []
  }>()

  const levelOrder = ['base', 'intermediaire', 'avance', 'expert']

  const hasCompetence = computed(() => !!props.userCompetence)

  const meetsRequirement = computed(() => {
    if (!hasCompetence.value) return false

    const userLevel = levelOrder.indexOf(props.userCompetence.level_acquired)
    const requiredLevel = levelOrder.indexOf(props.requiredCompetence.level_required)

    return userLevel >= requiredLevel
  })

  const isExpired = computed(() => {
    if (!props.userCompetence?.expiry_date) return false
    return new Date(props.userCompetence.expiry_date) < new Date()
  })

  const isExpiringSoon = computed(() => {
    if (!props.userCompetence?.expiry_date) return false
    const days = Math.ceil((new Date(props.userCompetence.expiry_date).getTime() - Date.now()) / (1000 * 60 * 60 * 24))
    return days <= 30 && days > 0
  })

  const cellClass = computed(() => ({
    'has-competence': hasCompetence.value,
    'meets-requirement': meetsRequirement.value,
    'expired': isExpired.value,
    'expiring-soon': isExpiringSoon.value,
    'missing': !hasCompetence.value,
    'clickable': true,
  }))

  const statusIcon = computed(() => {
    if (!hasCompetence.value) return 'mdi-close'
    if (isExpired.value) return 'mdi-alert-circle'
    if (meetsRequirement.value) return 'mdi-check-circle'
    return 'mdi-minus-circle'
  })

  const iconColor = computed(() => {
    if (!hasCompetence.value) return 'error'
    if (isExpired.value) return 'error'
    if (meetsRequirement.value) return 'success'
    return 'warning'
  })

  const levelText = computed(() => {
    if (!hasCompetence.value) return 'N/A'
    return props.userCompetence.level_acquired.charAt(0).toUpperCase()
  })

  const expiryColor = computed(() => {
    if (isExpired.value) return 'error'
    if (isExpiringSoon.value) return 'warning'
    return 'success'
  })

  const expiryText = computed(() => {
    if (!props.userCompetence?.expiry_date) return ''

    const days = Math.ceil((new Date(props.userCompetence.expiry_date).getTime() - Date.now()) / (1000 * 60 * 60 * 24))

    if (days < 0) return 'Expiré'
    if (days <= 30) return `${days}j`

    return new Date(props.userCompetence.expiry_date).toLocaleDateString('fr-FR', {
      month: 'short',
      year: '2-digit',
    })
  })
</script>

<style scoped>
.competence-cell {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 60px;
  padding: 4px;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s;
}

.competence-cell.clickable:hover {
  background-color: rgba(0, 0, 0, 0.04);
}

.competence-cell.missing {
  background-color: rgba(244, 67, 54, 0.1);
}

.competence-cell.meets-requirement {
  background-color: rgba(76, 175, 80, 0.1);
}

.competence-cell.expired {
  background-color: rgba(244, 67, 54, 0.2);
}

.competence-cell.expiring-soon {
  background-color: rgba(255, 152, 0, 0.1);
}

.level-indicator {
  display: flex;
  align-items: center;
  gap: 4px;
}

.level-text {
  font-size: 12px;
  font-weight: 500;
}

.expiry-info {
  margin-top: 2px;
}
</style>
