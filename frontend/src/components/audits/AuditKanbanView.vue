<template>
  <v-row>
    <v-col
      v-for="column in columns"
      :key="column.value"
      cols="12"
      md="3"
    >
      <v-card class="kanban-column" elevation="1">
        <v-card-title class="py-3 px-4 bg-grey-lighten-4">
          <div class="d-flex align-center justify-space-between">
            <div class="d-flex align-center">
              <v-icon class="mr-2" :color="column.color">{{ column.icon }}</v-icon>
              <span class="font-weight-bold">{{ column.title }}</span>
            </div>
            <v-chip :color="column.color" size="small" variant="flat">
              {{ getColumnCount(column.value) }}
            </v-chip>
          </div>
        </v-card-title>

        <v-divider />

        <div class="kanban-cards pa-2" style="min-height: 600px; max-height: 70vh; overflow-y: auto;">
          <draggable
            class="drag-area"
            :group="{ name: 'audits' }"
            item-key="id"
            :list="getAuditsForStatus(column.value)"
            @change="handleDrop($event, column.value)"
          >
            <template #item="{ element }">
              <v-card
                class="audit-card mb-3 cursor-pointer"
                elevation="2"
                hover
                @click="$emit('view-audit', element.id)"
              >
                <v-card-text class="pa-3">
                  <!-- Référence -->
                  <div class="d-flex align-center justify-space-between mb-2">
                    <v-chip color="primary" size="x-small" variant="outlined">
                      {{ element.ref }}
                    </v-chip>
                    <v-menu>
                      <template #activator="{ props: menuProps }">
                        <v-btn
                          icon="mdi-dots-vertical"
                          size="x-small"
                          variant="text"
                          v-bind="menuProps"
                          @click.stop
                        />
                      </template>
                      <v-list density="compact">
                        <v-list-item @click="$emit('view-audit', element.id)">
                          <template #prepend>
                            <v-icon size="small">mdi-eye</v-icon>
                          </template>
                          <v-list-item-title>Voir</v-list-item-title>
                        </v-list-item>
                      </v-list>
                    </v-menu>
                  </div>

                  <!-- Titre -->
                  <h4 class="text-body-2 font-weight-medium mb-2 line-clamp-2">
                    {{ element.title }}
                  </h4>

                  <!-- Type -->
                  <v-chip class="mb-2" size="x-small" variant="tonal">
                    {{ getTypeLabel(element.type) }}
                  </v-chip>

                  <!-- Date -->
                  <div class="text-caption text-medium-emphasis mb-2">
                    <v-icon class="mr-1" size="small">mdi-calendar</v-icon>
                    {{ formatDate(element.planned_date) }}
                  </div>

                  <!-- Axes QHSE -->
                  <div v-if="element.axes_qhse" class="d-flex gap-1 mb-2">
                    <v-chip
                      v-for="axis in element.axes_qhse"
                      :key="axis"
                      :color="getAxisColor(axis)"
                      size="x-small"
                      variant="flat"
                    >
                      {{ axis }}
                    </v-chip>
                  </div>

                  <!-- Conformité -->
                  <div v-if="element.conformity_rate != null" class="d-flex align-center justify-space-between">
                    <span class="text-caption">Conformité</span>
                    <v-progress-linear
                      class="flex-grow-1 ml-2"
                      :color="getConformityColor(element.conformity_rate)"
                      height="6"
                      :model-value="element.conformity_rate"
                      rounded
                      style="max-width: 100px;"
                    />
                    <span class="text-caption ml-1">{{ element.conformity_rate }}%</span>
                  </div>

                  <!-- Auditeur -->
                  <div v-if="element.lead_auditor" class="d-flex align-center mt-2">
                    <v-avatar class="mr-2" color="primary" size="24">
                      <span class="text-caption">{{ getInitials(element.lead_auditor.name) }}</span>
                    </v-avatar>
                    <span class="text-caption">{{ element.lead_auditor.name }}</span>
                  </div>
                </v-card-text>
              </v-card>
            </template>
          </draggable>

          <!-- Empty state -->
          <div v-if="getAuditsForStatus(column.value).length === 0" class="text-center pa-4">
            <v-icon color="grey-lighten-1" size="48">mdi-inbox</v-icon>
            <p class="text-caption text-grey-darken-1 mt-2">Aucun audit</p>
          </div>
        </div>
      </v-card>
    </v-col>
  </v-row>
</template>

<script setup lang="ts">
  import type { Audit } from '@/types/audit'
  import draggable from 'vuedraggable'

  const props = defineProps<{
    audits: Audit[]
    loading?: boolean
  }>()

  const emit = defineEmits<{
    'update-status': [auditId: number, newStatus: string]
    'view-audit': [auditId: number]
  }>()

  const columns = [
    {
      title: 'Planifié',
      value: 'planned',
      icon: 'mdi-calendar-clock',
      color: 'info',
    },
    {
      title: 'En cours',
      value: 'in_progress',
      icon: 'mdi-progress-clock',
      color: 'warning',
    },
    {
      title: 'Terminé',
      value: 'completed',
      icon: 'mdi-check-circle',
      color: 'success',
    },
    {
      title: 'En retard',
      value: 'overdue',
      icon: 'mdi-alert-circle',
      color: 'error',
    },
  ]

  function getAuditsForStatus (status: string) {
    return props.audits.filter(audit => audit.status === status)
  }

  function getColumnCount (status: string) {
    return getAuditsForStatus(status).length
  }

  function handleDrop (event: any, newStatus: string) {
    if (event.added) {
      const audit = event.added.element
      emit('update-status', audit.id, newStatus)
    }
  }

  function getInitials (name: string): string {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
  }

  function getTypeLabel (type: string): string {
    const labels: Record<string, string> = {
      internal: 'Interne',
      external: 'Externe',
      supplier: 'Fournisseur',
      certification: 'Certification',
    }
    return labels[type] || type
  }

  function formatDate (date: string): string {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' })
  }

  function getAxisColor (axis: string): string {
    const colors: Record<string, string> = {
      Q: 'blue',
      H: 'green',
      S: 'orange',
      E: 'teal',
    }
    return colors[axis] || 'grey'
  }

  function getConformityColor (rate: number): string {
    if (rate >= 90) return 'success'
    if (rate >= 75) return 'warning'
    return 'error'
  }
</script>

<style scoped>
.kanban-column {
  height: 100%;
}

.drag-area {
  min-height: 100px;
}

.audit-card {
  transition: all 0.3s;
  cursor: move;
}

.audit-card:hover {
  transform: translateY(-2px);
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.cursor-pointer {
  cursor: pointer;
}
</style>
