<template>
  <v-container class="audit-calendar pa-6" fluid>
    <v-row>
      <v-col cols="12">
        <v-card elevation="2">
          <v-card-title class="d-flex align-center justify-space-between">
            <div>
              <v-icon class="mr-2" size="32">mdi-calendar-month</v-icon>
              Planning des Audits {{ selectedYear }}
            </div>
            <div class="d-flex ga-2">
              <v-select
                v-model="selectedYear"
                density="compact"
                hide-details
                :items="years"
                style="width: 120px"
                variant="outlined"
              />
              <v-btn
                color="primary"
                prepend-icon="mdi-plus"
                @click="showWizard = true"
              >
                Nouvel audit
              </v-btn>
              <v-menu>
                <template #activator="{ props }">
                  <v-btn
                    icon
                    v-bind="props"
                  >
                    <v-icon>mdi-dots-vertical</v-icon>
                  </v-btn>
                </template>
                <v-list>
                  <v-list-item @click="exportCalendar('xlsx')">
                    <template #prepend>
                      <v-icon>mdi-microsoft-excel</v-icon>
                    </template>
                    <v-list-item-title>Exporter XLSX</v-list-item-title>
                  </v-list-item>
                  <v-list-item @click="exportCalendar('pdf')">
                    <template #prepend>
                      <v-icon>mdi-file-pdf-box</v-icon>
                    </template>
                    <v-list-item-title>Exporter PDF</v-list-item-title>
                  </v-list-item>
                  <v-list-item @click="printCalendar">
                    <template #prepend>
                      <v-icon>mdi-printer</v-icon>
                    </template>
                    <v-list-item-title>Imprimer</v-list-item-title>
                  </v-list-item>
                </v-list>
              </v-menu>
            </div>
          </v-card-title>

          <v-card-text>
            <!-- Filters -->
            <v-row class="mb-4">
              <v-col cols="12" md="3" sm="6">
                <v-select
                  v-model="filters.program"
                  clearable
                  density="compact"
                  hide-details
                  item-title="title"
                  item-value="id"
                  :items="programs"
                  label="Programme"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="3" sm="6">
                <v-select
                  v-model="filters.type"
                  clearable
                  density="compact"
                  hide-details
                  :items="auditTypes"
                  label="Type"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="3" sm="6">
                <v-select
                  v-model="filters.status"
                  clearable
                  density="compact"
                  hide-details
                  :items="statuses"
                  label="Statut"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="3" sm="6">
                <v-select
                  v-model="filters.qhse_axis"
                  clearable
                  density="compact"
                  hide-details
                  :items="qhseAxes"
                  label="Axe QHSE"
                  variant="outlined"
                />
              </v-col>
            </v-row>

            <!-- Legend -->
            <div class="d-flex flex-wrap ga-3 mb-4">
              <v-chip
                v-for="status in statuses"
                :key="status.value"
                :color="status.color"
                label
                size="small"
              >
                <v-icon class="mr-1" size="small">{{ status.icon }}</v-icon>
                {{ status.title }}
              </v-chip>
            </div>

            <!-- FullCalendar -->
            <FullCalendar
              ref="calendarRef"
              class="audit-fullcalendar"
              :options="calendarOptions"
            />
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Event Detail Dialog -->
    <v-dialog
      v-model="showEventDialog"
      max-width="600"
    >
      <v-card v-if="selectedEvent">
        <v-card-title class="d-flex align-center" :class="`bg-${selectedEvent.color}`">
          <v-icon class="mr-2">mdi-calendar-check</v-icon>
          {{ selectedEvent.title }}
        </v-card-title>

        <v-card-text class="pt-4">
          <v-list density="compact">
            <v-list-item>
              <template #prepend>
                <v-icon>mdi-identifier</v-icon>
              </template>
              <v-list-item-title>{{ selectedEvent.ref }}</v-list-item-title>
              <v-list-item-subtitle>Référence</v-list-item-subtitle>
            </v-list-item>

            <v-list-item>
              <template #prepend>
                <v-icon>mdi-calendar</v-icon>
              </template>
              <v-list-item-title>{{ formatDate(selectedEvent.start) }}</v-list-item-title>
              <v-list-item-subtitle>Date prévue</v-list-item-subtitle>
            </v-list-item>

            <v-list-item>
              <template #prepend>
                <v-icon>mdi-format-list-bulleted-type</v-icon>
              </template>
              <v-list-item-title>{{ selectedEvent.type }}</v-list-item-title>
              <v-list-item-subtitle>Type</v-list-item-subtitle>
            </v-list-item>

            <v-list-item>
              <template #prepend>
                <v-icon>mdi-account-star</v-icon>
              </template>
              <v-list-item-title>{{ selectedEvent.lead_auditor }}</v-list-item-title>
              <v-list-item-subtitle>Responsable d'audit</v-list-item-subtitle>
            </v-list-item>

            <v-list-item v-if="selectedEvent.scope">
              <template #prepend>
                <v-icon>mdi-radar</v-icon>
              </template>
              <v-list-item-title>{{ selectedEvent.scope }}</v-list-item-title>
              <v-list-item-subtitle>Périmètre</v-list-item-subtitle>
            </v-list-item>
          </v-list>

          <v-chip-group class="mt-4">
            <v-chip
              v-for="axis in selectedEvent.qhse_axes"
              :key="axis"
              :color="getAxisColor(axis)"
              size="small"
            >
              {{ axis }}
            </v-chip>
          </v-chip-group>
        </v-card-text>

        <v-card-actions>
          <v-spacer />
          <v-btn
            variant="text"
            @click="showEventDialog = false"
          >
            Fermer
          </v-btn>
          <v-btn
            color="primary"
            variant="elevated"
            @click="viewAuditDetail"
          >
            Voir détails
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Wizard Dialog -->
    <AuditWizard
      v-model="showWizard"
      :program-id="filters.program ?? undefined"
      @created="handleAuditCreated"
    />
  </v-container>
</template>

<script setup lang="ts">
  import type { CalendarOptions, EventClickArg } from '@fullcalendar/core'
  import frLocale from '@fullcalendar/core/locales/fr'
  import dayGridPlugin from '@fullcalendar/daygrid'
  import interactionPlugin from '@fullcalendar/interaction'
  import listPlugin from '@fullcalendar/list'
  import timeGridPlugin from '@fullcalendar/timegrid'
  import FullCalendar from '@fullcalendar/vue3'
  import { computed, onMounted, ref, watch } from 'vue'
  import { useRouter } from 'vue-router'
  import { useAuditProgramStore } from '@/stores/improvement/auditProgramStore'
  import { useAuditStore } from '@/stores/improvement/auditStore'
  import AuditWizard from './AuditWizard.vue'

  const router = useRouter()
  const auditStore = useAuditStore()
  const programStore = useAuditProgramStore()

  // State
  const calendarRef = ref<InstanceType<typeof FullCalendar>>()
  const showEventDialog = ref(false)
  const showWizard = ref(false)
  const selectedEvent = ref<any>(null)
  const selectedYear = ref(new Date().getFullYear())
  const programs = ref<any[]>([])

  const filters = ref({
    program: null as number | null,
    type: null,
    status: null,
    qhse_axis: null,
  })

  // Data
  const years = computed(() => {
    const current = new Date().getFullYear()
    return [current - 1, current, current + 1]
  })

  const auditTypes = [
    { value: 'internal', title: 'Interne' },
    { value: 'process', title: 'Processus' },
    { value: 'system', title: 'Système' },
    { value: 'product', title: 'Produit' },
    { value: 'supplier', title: 'Fournisseur' },
  ]

  const statuses = [
    { value: 'planned', title: 'Planifié', color: 'warning', icon: 'mdi-calendar-clock' },
    { value: 'in_progress', title: 'En cours', color: 'info', icon: 'mdi-play-circle' },
    { value: 'completed', title: 'Terminé', color: 'success', icon: 'mdi-check-circle' },
    { value: 'cancelled', title: 'Annulé', color: 'error', icon: 'mdi-cancel' },
  ]

  const qhseAxes = [
    { value: 'quality', title: 'Qualité', color: 'blue' },
    { value: 'health', title: 'Santé', color: 'green' },
    { value: 'safety', title: 'Sécurité', color: 'orange' },
    { value: 'environment', title: 'Environnement', color: 'teal' },
  ]

  // Calendar Options
  const calendarOptions = computed<CalendarOptions>(() => ({
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin, listPlugin],
    initialView: 'dayGridMonth',
    locale: frLocale,
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,listMonth',
    },
    buttonText: {
      today: 'Aujourd\'hui',
      month: 'Mois',
      week: 'Semaine',
      list: 'Liste',
    },
    events: filteredEvents.value,
    eventClick: handleEventClick,
    dateClick: handleDateClick,
    height: 'auto',
    editable: true,
    selectable: true,
    eventDrop: handleEventDrop,
    eventResize: handleEventResize,
    dayMaxEvents: 3,
    eventTimeFormat: {
      hour: '2-digit',
      minute: '2-digit',
      meridiem: false,
    },
  }))

  // Computed
  const filteredEvents = computed(() => {
    let events = auditStore.audits.map(audit => ({
      id: audit.id,
      title: audit.title,
      start: audit.planned_date,
      color: getStatusColor(audit.status),
      extendedProps: {
        ref: audit.ref,
        type: audit.type,
        status: audit.status,
        lead_auditor: audit.lead_auditor?.name || 'Non défini',
        scope: audit.scope,
        qhse_axes: audit.qhse_axes || ['quality'],
        program_id: (audit as { audit_program_id?: number, program_id?: number }).audit_program_id
          || (audit as { audit_program_id?: number, program_id?: number }).program_id,
      },
    }))

    if (filters.value.program) {
      events = events.filter(e => e.extendedProps.program_id === filters.value.program)
    }
    if (filters.value.type) {
      events = events.filter(e => e.extendedProps.type === filters.value.type)
    }
    if (filters.value.status) {
      events = events.filter(e => e.extendedProps.status === filters.value.status)
    }
    if (filters.value.qhse_axis) {
      events = events.filter(e =>
        e.extendedProps.qhse_axes.includes(filters.value.qhse_axis),
      )
    }

    return events
  })

  // Methods
  function getStatusColor (status: string): string {
    const colors: Record<string, string> = {
      planned: '#FFC107',
      in_progress: '#2196F3',
      completed: '#4CAF50',
      cancelled: '#F44336',
    }
    return colors[status] || '#9E9E9E'
  }

  function getAxisColor (axis: string): string {
    const colors: Record<string, string> = {
      quality: 'blue',
      health: 'green',
      safety: 'orange',
      environment: 'teal',
    }
    return colors[axis] || 'grey'
  }

  function handleEventClick (info: EventClickArg) {
    selectedEvent.value = {
      id: info.event.id,
      title: info.event.title,
      start: info.event.start,
      color: info.event.backgroundColor?.replace('#', '') || 'grey',
      ...info.event.extendedProps,
    }
    showEventDialog.value = true
  }

  function handleDateClick (_info: any) {
    // Ouvrir wizard avec date pré-sélectionnée
    showWizard.value = true
  }

  async function handleEventDrop (info: any) {
    // Mettre à jour la date de l'audit
    try {
      await auditStore.updateAudit(info.event.id, {
        planned_date: info.event.start.toISOString().split('T')[0],
      })
    } catch (error) {
      console.error('Erreur:', error)
      info.revert()
    }
  }

  async function handleEventResize (info: any) {
    // Gérer le redimensionnement si nécessaire
    console.log('Event resized:', info)
  }

  function viewAuditDetail () {
    if (selectedEvent.value) {
      router.push(`/company/audits/${selectedEvent.value.id}`)
      showEventDialog.value = false
    }
  }

  function formatDate (date: Date | string | null) {
    if (!date) return 'N/A'
    return new Date(date).toLocaleDateString('fr-FR', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    })
  }

  async function exportCalendar (format: 'xlsx' | 'pdf') {
    if (filters.value.program && format === 'xlsx') {
      await programStore.exportCalendar(filters.value.program)
    } else {
      // Export global
      console.log('Export global calendar:', format)
    }
  }

  function printCalendar () {
    window.print()
  }

  async function handleAuditCreated (auditId: number) {
    await loadData()
    router.push(`/company/audits/${auditId}`)
  }

  async function loadData () {
    await auditStore.fetchAudits({ year: selectedYear.value })
    await programStore.fetchPrograms({ year: selectedYear.value })
    programs.value = programStore.programs
  }

  // Watchers
  watch(selectedYear, () => {
    loadData()
  })

  watch(filters, () => {
    // Rafraîchir le calendrier
    calendarRef.value?.getApi().refetchEvents()
  }, { deep: true })

  onMounted(() => {
    loadData()
  })
</script>

<style scoped>
.audit-calendar {
  max-width: 1600px;
  margin: 0 auto;
}

:deep(.audit-fullcalendar) {
  --fc-border-color: #e0e0e0;
  --fc-button-bg-color: #1976D2;
  --fc-button-border-color: #1976D2;
  --fc-button-hover-bg-color: #1565C0;
  --fc-button-active-bg-color: #0D47A1;
  --fc-today-bg-color: rgba(25, 118, 210, 0.1);
}

:deep(.fc-event) {
  cursor: pointer;
  transition: transform 0.2s;
}

:deep(.fc-event:hover) {
  transform: scale(1.02);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

:deep(.fc-daygrid-event-dot) {
  border-color: currentColor !important;
}

@media print {
  .v-card-title .d-flex > div:last-child {
    display: none !important;
  }
}
</style>
