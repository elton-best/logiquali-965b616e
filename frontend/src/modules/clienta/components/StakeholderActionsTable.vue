<template>
  <v-card elevation="0" rounded="xl">
    <v-card-title class="pa-6 d-flex align-center justify-space-between">
      <div class="d-flex align-center">
        <v-icon class="mr-2" color="primary">mdi-format-list-checks</v-icon>
        <span class="text-h5 font-weight-bold">Tableau des Actions</span>
      </div>
      <v-chip color="primary" size="large" variant="tonal">
        {{ allActions.length }} actions
      </v-chip>
    </v-card-title>

    <v-divider />

    <v-card-text class="pa-0">
      <v-data-table
        class="actions-table"
        :headers="headers"
        item-key="id"
        :items="allActions"
        :items-per-page="10"
      >
        <template #item.stakeholder="{ item }">
          <div class="d-flex align-center">
            <v-avatar class="mr-2" :color="getTypeColor(item.stakeholderType)" size="32">
              <v-icon color="white" size="16">{{ getTypeIcon(item.stakeholderType) }}</v-icon>
            </v-avatar>
            <div>
              <div class="font-weight-medium">{{ item.stakeholderName }}</div>
              <v-chip :color="getTypeColor(item.stakeholderType)" size="x-small" variant="tonal">
                {{ typeLabel(item.stakeholderType) }}
              </v-chip>
            </div>
          </div>
        </template>

        <template #item.requirement="{ item }">
          <div class="requirement-cell">
            <div class="text-body-2 font-weight-medium mb-1">{{ item.requirementDescription }}</div>
            <v-chip color="info" size="x-small" variant="tonal">
              {{ item.requirementType }}
            </v-chip>
          </div>
        </template>

        <template #item.action="{ item }">
          <div class="action-cell">
            <div class="text-body-2">{{ item.actionDescription }}</div>
          </div>
        </template>

        <template #item.responsible="{ item }">
          <div v-if="item.responsible" class="d-flex align-center">
            <v-icon class="mr-1" color="primary" size="16">mdi-account</v-icon>
            <span class="text-body-2">{{ item.responsible }}</span>
          </div>
          <v-chip v-else color="grey" size="small" variant="tonal">
            Non assigné
          </v-chip>
        </template>

        <template #item.deadline="{ item }">
          <div v-if="item.deadline" class="d-flex align-center">
            <v-icon class="mr-1" :color="getDeadlineColor(item.deadline)" size="16">mdi-calendar</v-icon>
            <div>
              <div class="text-body-2" :style="`color: ${getDeadlineColor(item.deadline)}`">
                {{ formatDate(item.deadline) }}
              </div>
              <v-chip
                class="mt-1"
                :color="getDeadlineColor(item.deadline)"
                size="x-small"
                variant="flat"
              >
                {{ getDeadlineStatus(item.deadline) }}
              </v-chip>
            </div>
          </div>
          <v-chip v-else color="grey" size="small" variant="tonal">
            Non défini
          </v-chip>
        </template>

        <template #item.status="{ item }">
          <v-chip
            :color="getStatusColor(item.deadline)"
            size="small"
            variant="flat"
          >
            {{ getActionStatus(item.deadline) }}
          </v-chip>
        </template>

        <template #no-data>
          <div class="text-center pa-8">
            <v-icon class="mb-4" color="grey-lighten-2" size="64">mdi-clipboard-list-outline</v-icon>
            <div class="text-h6 text-medium-emphasis">Aucune action trouvée</div>
            <div class="text-body-2 text-medium-emphasis">Les actions apparaîtront ici une fois définies</div>
          </div>
        </template>
      </v-data-table>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  interface Stakeholder {
    id: string
    name: string
    type: string
    needs?: Array<{
      description: string
      requirements: Array<{
        description: string
        type: string
        actions: Array<{
          description: string
          responsible: string
          deadline: string
        }>
      }>
    }>
  }

  const props = defineProps<{
    stakeholders: Stakeholder[]
  }>()

  const headers = [
    { title: 'Partie intéressée', key: 'stakeholder', width: '200px' },
    { title: 'Exigence', key: 'requirement', width: '250px' },
    { title: 'Action', key: 'action', width: '300px' },
    { title: 'Responsable', key: 'responsible', width: '150px' },
    { title: 'Délai', key: 'deadline', width: '120px' },
    { title: 'Statut', key: 'status', width: '100px' },
  ]

  const allActions = computed(() => {
    const actions: any[] = []

    for (const stakeholder of props.stakeholders) {
      if (stakeholder.needs && Array.isArray(stakeholder.needs)) {
        for (const [needIndex, need] of stakeholder.needs.entries()) {
          if (need.requirements && Array.isArray(need.requirements)) {
            for (const [reqIndex, req] of need.requirements.entries()) {
              if (req.actions && Array.isArray(req.actions)) {
                for (const [actionIndex, action] of req.actions.entries()) {
                  actions.push({
                    id: `${stakeholder.id}-${needIndex}-${reqIndex}-${actionIndex}`,
                    stakeholderName: stakeholder.name,
                    stakeholderType: stakeholder.type,
                    needDescription: need.description,
                    requirementDescription: req.description,
                    requirementType: req.type,
                    actionDescription: action.description,
                    responsible: action.responsible,
                    deadline: action.deadline,
                  })
                }
              }
            }
          }
        }
      }
    }

    return actions
  })

  const stakeholderTypes = [
    { title: 'Client', value: 'client' },
    { title: 'Fournisseur', value: 'supplier' },
    { title: 'Partenaire', value: 'partner' },
    { title: 'Régulateur', value: 'regulator' },
    { title: 'Employé', value: 'employee' },
    { title: 'Actionnaire', value: 'shareholder' },
    { title: 'Autre', value: 'other' },
  ]

  function getTypeColor (type: string) {
    const colors: Record<string, string> = {
      client: '#5b8dd9', supplier: '#22c55e', partner: '#3b82f6',
      regulator: '#f59e0b', employee: '#a855f7', shareholder: '#f59e0b', other: '#64748b',
    }
    return colors[type] || '#64748b'
  }

  function getTypeIcon (type: string) {
    const icons: Record<string, string> = {
      client: 'mdi-account-star', supplier: 'mdi-truck', partner: 'mdi-handshake',
      regulator: 'mdi-gavel', employee: 'mdi-account-tie', shareholder: 'mdi-chart-line', other: 'mdi-dots-horizontal',
    }
    return icons[type] || 'mdi-account'
  }

  function typeLabel (type: string) {
    return stakeholderTypes.find(t => t.value === type)?.title || type
  }

  function formatDate (dateString: string) {
    if (!dateString) return 'Non défini'
    const date = new Date(dateString)
    return date.toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
    })
  }

  function getDeadlineColor (deadline: string) {
    if (!deadline) return '#64748b'

    const today = new Date()
    const deadlineDate = new Date(deadline)
    const diffDays = Math.ceil((deadlineDate.getTime() - today.getTime()) / (1000 * 3600 * 24))

    if (diffDays < 0) return '#ef4444'
    if (diffDays <= 7) return '#f59e0b'
    if (diffDays <= 30) return '#3b82f6'
    return '#22c55e'
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

  function getStatusColor (deadline: string) {
    return getDeadlineColor(deadline)
  }

  function getActionStatus (deadline: string) {
    return getDeadlineStatus(deadline)
  }
</script>

<style scoped>
.actions-table :deep(.v-data-table__td) {
  padding: 12px 16px;
  vertical-align: top;
}

.requirement-cell {
  max-width: 250px;
}

.action-cell {
  max-width: 300px;
  word-wrap: break-word;
}

.actions-table :deep(.v-data-table-header__content) {
  font-weight: 600;
  color: #1e293b;
}

.actions-table :deep(tr:hover) {
  background-color: rgba(91, 141, 217, 0.05) !important;
}
</style>
