<template>
  <v-card elevation="2">
    <v-card-title class="d-flex align-center justify-space-between pa-4">
      <div class="d-flex align-center">
        <v-btn icon size="small" variant="text" @click="previousMonth">
          <v-icon>mdi-chevron-left</v-icon>
        </v-btn>
        <h3 class="text-h6 mx-4">{{ currentMonthLabel }}</h3>
        <v-btn icon size="small" variant="text" @click="nextMonth">
          <v-icon>mdi-chevron-right</v-icon>
        </v-btn>
      </div>

      <div class="d-flex gap-2">
        <v-btn size="small" variant="outlined" @click="goToToday">
          Aujourd'hui
        </v-btn>
        <v-btn-toggle v-model="view" density="compact" mandatory>
          <v-btn size="small" value="month">Mois</v-btn>
          <v-btn size="small" value="week">Semaine</v-btn>
          <v-btn size="small" value="list">Liste</v-btn>
        </v-btn-toggle>
      </div>
    </v-card-title>

    <v-divider />

    <v-card-text class="pa-0">
      <!-- Vue Mois -->
      <div v-if="view === 'month'" class="calendar-month">
        <!-- En-têtes jours -->
        <div class="calendar-header">
          <div v-for="day in weekDays" :key="day" class="calendar-day-header">
            {{ day }}
          </div>
        </div>

        <!-- Grille calendrier -->
        <div class="calendar-grid">
          <div
            v-for="day in calendarDays"
            :key="day.date"
            :class="[
              'calendar-cell',
              {
                'current-month': day.currentMonth,
                'today': day.isToday,
                'has-events': day.events.length > 0
              }
            ]"
            @click="handleDayClick(day)"
          >
            <div class="day-number">{{ day.day }}</div>

            <div class="events-container">
              <div
                v-for="audit in day.events.slice(0, 3)"
                :key="audit.id"
                :class="['event-item', `status-${audit.status}`]"
                @click.stop="$emit('event-click', audit.id)"
              >
                <div class="event-title">{{ audit.code }} - {{ audit.title }}</div>
                <v-chip
                  class="event-status"
                  :color="getStatusColor(audit.status)"
                  size="x-small"
                  variant="flat"
                >
                  {{ getStatusLabel(audit.status) }}
                </v-chip>
              </div>

              <div v-if="day.events.length > 3" class="more-events">
                +{{ day.events.length - 3 }} autres
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Vue Semaine -->
      <div v-else-if="view === 'week'" class="calendar-week">
        <v-simple-table dense>
          <template #default>
            <thead>
              <tr>
                <th width="100">Heure</th>
                <th v-for="day in weekDays" :key="day">{{ day }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="hour in 10" :key="hour">
                <td class="text-caption">{{ (hour + 7) }}:00</td>
                <td v-for="day in 7" :key="day" class="week-cell">
                  <!-- Events pour cet horaire -->
                </td>
              </tr>
            </tbody>
          </template>
        </v-simple-table>
      </div>

      <!-- Vue Liste -->
      <div v-else class="calendar-list pa-4">
        <v-list>
          <template v-for="(group, date) in groupedAudits" :key="date">
            <v-list-subheader class="font-weight-bold">
              {{ formatDateLabel(date) }}
            </v-list-subheader>

            <v-list-item
              v-for="audit in group"
              :key="audit.id"
              class="mb-2"
              @click="$emit('event-click', audit.id)"
            >
              <template #prepend>
                <v-avatar :color="getStatusColor(audit.status)" size="40">
                  <v-icon color="white">mdi-clipboard-check</v-icon>
                </v-avatar>
              </template>

              <v-list-item-title class="font-weight-medium">
                {{ audit.code }} - {{ audit.title }}
              </v-list-item-title>

              <v-list-item-subtitle>
                <v-chip class="mr-2" :color="getStatusColor(audit.status)" size="small" variant="flat">
                  {{ getStatusLabel(audit.status) }}
                </v-chip>
                {{ audit.audit_type }} • {{ audit.lead_auditor?.name }}
              </v-list-item-subtitle>

              <template #append>
                <div class="text-caption text-medium-emphasis">
                  {{ formatTime(audit.planned_start_date) }}
                </div>
              </template>
            </v-list-item>
          </template>

          <v-list-item v-if="audits.length === 0">
            <div class="text-center pa-8">
              <v-icon color="grey-lighten-1" size="64">mdi-calendar-blank</v-icon>
              <p class="text-grey-darken-1 mt-4">Aucun audit prévu ce mois-ci</p>
            </div>
          </v-list-item>
        </v-list>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import type { Audit } from '@/types/audit'
  import { computed, ref } from 'vue'

  const props = defineProps<{
    audits: Audit[]
    loading?: boolean
  }>()

  const emit = defineEmits<{
    'event-click': [auditId: number]
    'date-click': [date: Date]
  }>()

  const currentDate = ref(new Date())
  const view = ref<'month' | 'week' | 'list'>('month')

  const weekDays = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']

  const currentMonthLabel = computed(() => {
    return currentDate.value.toLocaleDateString('fr-FR', {
      month: 'long',
      year: 'numeric',
    })
  })

  const calendarDays = computed(() => {
    const year = currentDate.value.getFullYear()
    const month = currentDate.value.getMonth()

    const firstDay = new Date(year, month, 1)
    // Début de la semaine contenant le 1er du mois
    const startDate = new Date(firstDay)
    const dayOfWeek = firstDay.getDay()
    startDate.setDate(firstDay.getDate() - (dayOfWeek === 0 ? 6 : dayOfWeek - 1))

    const days = []
    const currentDay = new Date(startDate)

    // Générer 42 jours (6 semaines)
    for (let i = 0; i < 42; i++) {
      const dateStr = currentDay.toISOString().split('T')[0] ?? ''
      const isCurrentMonth = currentDay.getMonth() === month
      const isToday = dateStr === (new Date().toISOString().split('T')[0] ?? '')

      days.push({
        date: dateStr,
        day: currentDay.getDate(),
        currentMonth: isCurrentMonth,
        isToday,
        events: getAuditsForDate(dateStr),
      })

      currentDay.setDate(currentDay.getDate() + 1)
    }

    return days
  })

  const groupedAudits = computed(() => {
    const groups: Record<string, Audit[]> = {}

    for (const audit of props.audits) {
      const date = audit.planned_start_date.split('T')[0] ?? ''
      if (!groups[date]) {
        groups[date] = []
      }
      groups[date].push(audit)
    }

    // Trier par date
    return Object.fromEntries(
      Object.entries(groups).toSorted(([a], [b]) => a.localeCompare(b)),
    )
  })

  function getAuditsForDate (date: string): Audit[] {
    return props.audits.filter(audit => {
      const auditDate = audit.planned_start_date.split('T')[0] ?? ''
      return auditDate === date
    })
  }

  function handleDayClick (day: any) {
    if (day.events.length > 0) {
    // Afficher modal avec tous les audits du jour
    } else {
      emit('date-click', new Date(day.date))
    }
  }

  function previousMonth () {
    const newDate = new Date(currentDate.value)
    newDate.setMonth(newDate.getMonth() - 1)
    currentDate.value = newDate
  }

  function nextMonth () {
    const newDate = new Date(currentDate.value)
    newDate.setMonth(newDate.getMonth() + 1)
    currentDate.value = newDate
  }

  function goToToday () {
    currentDate.value = new Date()
  }

  function getStatusColor (status: string): string {
    const colors: Record<string, string> = {
      planned: 'info',
      in_progress: 'warning',
      completed: 'success',
      overdue: 'error',
    }
    return colors[status] || 'grey'
  }

  function getStatusLabel (status: string): string {
    const labels: Record<string, string> = {
      planned: 'Planifié',
      in_progress: 'En cours',
      completed: 'Terminé',
      overdue: 'En retard',
    }
    return labels[status] || status
  }

  function formatDateLabel (date: string): string {
    return new Date(date).toLocaleDateString('fr-FR', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    })
  }

  function formatTime (datetime: string): string {
    return new Date(datetime).toLocaleTimeString('fr-FR', {
      hour: '2-digit',
      minute: '2-digit',
    })
  }
</script>

<style scoped>
.calendar-month {
  padding: 16px;
}

.calendar-header {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 8px;
  margin-bottom: 8px;
}

.calendar-day-header {
  text-align: center;
  font-weight: 600;
  padding: 8px;
  color: rgb(var(--v-theme-on-surface));
}

.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 8px;
}

.calendar-cell {
  min-height: 120px;
  border: 1px solid rgb(var(--v-theme-surface-variant));
  border-radius: 8px;
  padding: 8px;
  cursor: pointer;
  transition: all 0.2s;
  background: white;
}

.calendar-cell:hover {
  background: rgb(var(--v-theme-surface));
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.calendar-cell:not(.current-month) {
  opacity: 0.3;
}

.calendar-cell.today {
  border-color: rgb(var(--v-theme-primary));
  border-width: 2px;
  background: rgb(var(--v-theme-primary-lighten-5));
}

.day-number {
  font-weight: 600;
  margin-bottom: 4px;
  font-size: 14px;
}

.events-container {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.event-item {
  padding: 4px 6px;
  border-radius: 4px;
  font-size: 11px;
  cursor: pointer;
  transition: all 0.2s;
  border-left: 3px solid;
}

.event-item.status-planned {
  background: rgb(var(--v-theme-info-lighten-4));
  border-color: rgb(var(--v-theme-info));
}

.event-item.status-in_progress {
  background: rgb(var(--v-theme-warning-lighten-4));
  border-color: rgb(var(--v-theme-warning));
}

.event-item.status-completed {
  background: rgb(var(--v-theme-success-lighten-4));
  border-color: rgb(var(--v-theme-success));
}

.event-item:hover {
  transform: scale(1.05);
  z-index: 10;
}

.event-title {
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 2px;
}

.event-status {
  margin-top: 2px;
}

.more-events {
  font-size: 10px;
  color: rgb(var(--v-theme-primary));
  font-weight: 600;
  padding: 2px 4px;
  text-align: center;
}

.calendar-week {
  overflow-x: auto;
}

.week-cell {
  min-width: 100px;
  min-height: 80px;
  border: 1px solid rgb(var(--v-theme-surface-variant));
}

.calendar-list {
  max-height: 70vh;
  overflow-y: auto;
}
</style>
