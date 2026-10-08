<template>
  <v-card class="requirements-card" elevation="0" rounded="xl">
    <v-card-text class="pa-6">
      <div class="d-flex align-center justify-space-between mb-4">
        <h3 class="text-h5 font-weight-bold d-flex align-center">
          <v-icon class="mr-2" color="primary">mdi-clipboard-check</v-icon>
          Exigences et Actions
        </h3>
        <v-chip color="primary" variant="tonal">
          {{ totalActions }} actions
        </v-chip>
      </div>

      <div v-for="(need, needIndex) in stakeholder.needs" :key="needIndex" class="mb-6">
        <v-card class="need-section" rounded="lg" variant="outlined">
          <v-card-text class="pa-4">
            <div class="need-header mb-3">
              <v-chip class="mr-2" color="warning" size="small">
                Besoin {{ needIndex + 1 }}
              </v-chip>
              <span class="font-weight-medium need-description">{{ need.description }}</span>
              <v-chip class="ml-2" color="warning" size="x-small" variant="outlined">
                Priorité: {{ getPriorityLabel(need.priority) }}
              </v-chip>
            </div>

            <div v-for="(req, reqIndex) in need.requirements" :key="reqIndex" class="mb-4">
              <v-card class="requirement-section" color="info" rounded="lg" variant="tonal">
                <v-card-text class="pa-3">
                  <div class="requirement-header mb-2">
                    <v-chip class="mr-2" color="info" size="x-small">
                      Exigence {{ reqIndex + 1 }}
                    </v-chip>
                    <span class="text-body-2 requirement-description">{{ req.description }}</span>
                    <v-chip class="ml-2" color="info" size="x-small" variant="outlined">
                      {{ getRequirementTypeLabel(req.type) }}
                    </v-chip>
                  </div>

                  <!-- Actions avec responsables et délais -->
                  <div v-if="req.actions && req.actions.length > 0" class="actions-list">
                    <div class="text-caption font-weight-bold mb-2 d-flex align-center">
                      <v-icon class="mr-1" color="success" size="16">mdi-check-circle</v-icon>
                      Actions à réaliser
                    </div>

                    <v-row dense>
                      <v-col
                        v-for="(action, actIndex) in req.actions"
                        :key="actIndex"
                        cols="12"
                      >
                        <v-card
                          class="action-card"
                          :class="getActionStatusClass(action.deadline)"
                          rounded="lg"
                          variant="outlined"
                        >
                          <v-card-text class="pa-3">
                            <div class="action-header">
                              <div class="action-content">
                                <div class="font-weight-medium text-body-2 mb-1 action-description">
                                  {{ action.description }}
                                </div>
                                <div class="action-meta">
                                  <div class="meta-item">
                                    <v-icon class="mr-1" color="primary" size="14">mdi-account</v-icon>
                                    <span class="text-caption text-wrap">{{ getResponsibleLabel(action) }}</span>
                                  </div>
                                  <div class="meta-item">
                                    <v-icon class="mr-1" :color="getDeadlineColor(action.deadline)" size="14">mdi-calendar</v-icon>
                                    <span class="text-caption" :class="getDeadlineTextClass(action.deadline)">
                                      {{ formatDate(action.deadline) }}
                                    </span>
                                  </div>
                                </div>
                              </div>
                              <v-chip
                                class="status-chip"
                                :color="getDeadlineColor(action.deadline)"
                                size="x-small"
                                variant="flat"
                              >
                                {{ getDeadlineStatus(action.deadline) }}
                              </v-chip>
                            </div>
                          </v-card-text>
                        </v-card>
                      </v-col>
                    </v-row>
                  </div>

                  <v-alert
                    v-else
                    class="mt-2"
                    density="compact"
                    type="warning"
                    variant="tonal"
                  >
                    <v-icon size="16" start>mdi-alert</v-icon>
                    Aucune action définie pour cette exigence
                  </v-alert>
                </v-card-text>
              </v-card>
            </div>
          </v-card-text>
        </v-card>
      </div>

      <v-alert v-if="!stakeholder.needs || stakeholder.needs.length === 0" rounded="lg" type="info" variant="tonal">
        <v-icon start>mdi-information</v-icon>
        Aucun besoin défini pour cette partie intéressée
      </v-alert>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  interface Action {
    description: string
    responsible?: string
    responsible_name?: string
    responsible_user_id?: number | null
    deadline: string
  }

  interface Requirement {
    description: string
    type: string
    actions: Action[]
  }

  interface Need {
    description: string
    priority: string
    requirements: Requirement[]
  }

  interface Stakeholder {
    needs: Need[]
  }

  interface CollaboratorOption {
    id: number
    label: string
  }

  const props = defineProps<{
    stakeholder: Stakeholder
    collaboratorOptions?: CollaboratorOption[]
  }>()

  const totalActions = computed(() => {
    return props.stakeholder.needs?.reduce((total, need) => {
      return total + need.requirements.reduce((reqTotal, req) => {
        return reqTotal + (req.actions?.length || 0)
      }, 0)
    }, 0) || 0
  })

  function formatDate (dateString: string) {
    if (!dateString) return 'Non défini'
    const date = new Date(dateString)
    return date.toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
    })
  }

  function getResponsibleLabel (action: Action) {
    const explicitLabel = String(action.responsible_name || action.responsible || '').trim()
    if (explicitLabel) {
      return explicitLabel
    }

    const responsibleId = Number(action.responsible_user_id || 0)
    if (responsibleId > 0) {
      const collaborator = (props.collaboratorOptions || []).find(option => option.id === responsibleId)
      if (collaborator) {
        return collaborator.label
      }
    }

    return 'Responsable non défini'
  }

  function getPriorityLabel (priority: string) {
    const normalized = String(priority || '').toLowerCase().trim()
    if (normalized === 'low') return 'Faible'
    if (normalized === 'high') return 'Élevée'
    return 'Moyenne'
  }

  function getRequirementTypeLabel (type: string) {
    const normalized = String(type || '').toLowerCase().trim()
    if (normalized === 'legal') return 'Légale'
    if (normalized === 'regulatory') return 'Réglementaire'
    if (normalized === 'contractual') return 'Contractuelle'
    return 'Autre'
  }

  function getDeadlineColor (deadline: string) {
    if (!deadline) return 'grey'

    const today = new Date()
    const deadlineDate = new Date(deadline)
    const diffDays = Math.ceil((deadlineDate.getTime() - today.getTime()) / (1000 * 3600 * 24))

    if (diffDays < 0) return 'error'
    if (diffDays <= 7) return 'warning'
    if (diffDays <= 30) return 'info'
    return 'success'
  }

  function getDeadlineStatus (deadline: string) {
    if (!deadline) return 'Non défini'

    const today = new Date()
    const deadlineDate = new Date(deadline)
    const diffDays = Math.ceil((deadlineDate.getTime() - today.getTime()) / (1000 * 3600 * 24))

    if (diffDays < 0) return 'Retard'
    if (diffDays <= 7) return 'Urgent'
    if (diffDays <= 30) return 'Proche'
    return 'À venir'
  }

  function getDeadlineTextClass (deadline: string) {
    const color = getDeadlineColor(deadline)
    return {
      'text-error': color === 'error',
      'text-warning': color === 'warning',
      'text-info': color === 'info',
      'text-success': color === 'success',
    }
  }

  function getActionStatusClass (deadline: string) {
    const color = getDeadlineColor(deadline)
    return {
      'border-error': color === 'error',
      'border-warning': color === 'warning',
      'border-info': color === 'info',
      'border-success': color === 'success',
    }
  }
</script>

<style scoped>
.requirements-card {
  background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
}

.need-section {
  border: 2px solid #fbbf2420;
  background: linear-gradient(135deg, #fbbf2405 0%, #ffffff 100%);
}

.requirement-section {
  background: linear-gradient(135deg, #3b82f615 0%, #ffffff 100%);
}

.action-card {
  transition: all 0.2s ease;
  border-width: 2px;
}

.action-card:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.border-error {
  border-color: #ef4444 !important;
}

.border-warning {
  border-color: #f59e0b !important;
}

.border-info {
  border-color: #3b82f6 !important;
}

.border-success {
  border-color: #22c55e !important;
}

.actions-list {
  background: rgba(255, 255, 255, 0.7);
  border-radius: 8px;
  padding: 12px;
  margin-top: 8px;
}

.need-header,
.requirement-header,
.action-header,
.action-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.need-description,
.requirement-description,
.action-description,
.action-content {
  min-width: 0;
}

.action-header {
  justify-content: space-between;
  align-items: flex-start;
}

.action-content {
  flex: 1 1 320px;
}

.meta-item {
  display: inline-flex;
  align-items: center;
  min-width: 0;
}

.status-chip {
  flex-shrink: 0;
}

.text-wrap {
  overflow-wrap: anywhere;
}
</style>
