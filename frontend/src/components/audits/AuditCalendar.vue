<template>
  <div class="audit-calendar">
    <FullCalendar ref="calendar" :options="calendarOptions" />
  </div>
</template>

<script setup lang="ts">
  import type { CalendarOptions, DateSelectArg, EventClickArg, EventInput } from '@fullcalendar/core'
  import type { AuditCalendarEvent } from '@/types/audit'
  import frLocale from '@fullcalendar/core/locales/fr'
  import dayGridPlugin from '@fullcalendar/daygrid'
  import interactionPlugin from '@fullcalendar/interaction'
  import timeGridPlugin from '@fullcalendar/timegrid'
  import FullCalendar from '@fullcalendar/vue3'
  import { computed, ref } from 'vue'

  interface Props {
    events: AuditCalendarEvent[]
    initialView?: 'dayGridMonth' | 'dayGridWeek' | 'timeGridWeek'
    editable?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    initialView: 'dayGridMonth',
    editable: false,
  })

  const emit = defineEmits<{
    'event-click': [event: AuditCalendarEvent]
    'date-select': [info: DateSelectArg]
    'event-drop': [event: AuditCalendarEvent, newStart: string, newEnd: string]
  }>()

  const calendar = ref<InstanceType<typeof FullCalendar> | null>(null)

  const calendarOptions = computed<CalendarOptions>(() => ({
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: props.initialView,
    locale: frLocale,

    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,dayGridWeek,timeGridWeek',
    },

    buttonText: {
      today: 'Aujourd\'hui',
      month: 'Mois',
      week: 'Semaine',
      day: 'Jour',
    },

    events: props.events.map(event => ({
      ...event,
      id: String(event.id),
    })) as EventInput[],

    editable: props.editable,
    selectable: props.editable,
    selectMirror: true,
    dayMaxEvents: true,

    weekends: true,
    navLinks: true,

    eventClick: handleEventClick,
    select: handleDateSelect,
    eventDrop: handleEventDrop,

    height: 'auto',

    eventTimeFormat: {
      hour: '2-digit',
      minute: '2-digit',
      hour12: false,
    },

    slotLabelFormat: {
      hour: '2-digit',
      minute: '2-digit',
      hour12: false,
    },
  }))

  function handleEventClick (info: EventClickArg) {
    const auditEvent = props.events.find(e => e.id === Number(info.event.id))
    if (auditEvent) {
      emit('event-click', auditEvent)
    }
  }

  function handleDateSelect (info: DateSelectArg) {
    emit('date-select', info)
  }

  function handleEventDrop (info: any) {
    const auditEvent = props.events.find(e => e.id === Number(info.event.id))
    if (auditEvent) {
      const newStart = info.event.start?.toISOString().split('T')[0] || auditEvent.start
      const newEnd = info.event.end?.toISOString().split('T')[0] || auditEvent.end
      emit('event-drop', auditEvent, newStart, newEnd)
    }
  }

  // Expose calendar API
  function getApi () {
    return calendar.value?.getApi()
  }

  function refetchEvents () {
    getApi()?.refetchEvents()
  }

  defineExpose({
    getApi,
    refetchEvents,
  })
</script>

<style>
/* FullCalendar custom styles */
.audit-calendar {
  --fc-border-color: #e5e7eb;
  --fc-button-bg-color: #3b82f6;
  --fc-button-border-color: #3b82f6;
  --fc-button-hover-bg-color: #2563eb;
  --fc-button-hover-border-color: #2563eb;
  --fc-button-active-bg-color: #1d4ed8;
  --fc-button-active-border-color: #1d4ed8;
  --fc-today-bg-color: rgba(59, 130, 246, 0.1);
}

.dark .audit-calendar {
  --fc-border-color: #374151;
  --fc-bg-event-color: #1f2937;
  --fc-bg-event-opacity: 1;
  --fc-neutral-bg-color: #1f2937;
  --fc-list-event-hover-bg-color: #374151;
}

.fc .fc-daygrid-day-number {
  padding: 8px;
}

.fc .fc-event {
  cursor: pointer;
  border-radius: 4px;
  padding: 2px 4px;
  font-size: 0.875rem;
}

.fc .fc-event:hover {
  opacity: 0.85;
}

.audit-event.audit-planned {
  border-left: 4px solid #3b82f6;
}

.audit-event.audit-in_progress {
  border-left: 4px solid #f59e0b;
}

.audit-event.audit-completed {
  border-left: 4px solid #10b981;
}

.audit-event.audit-cancelled {
  border-left: 4px solid #ef4444;
  opacity: 0.6;
  text-decoration: line-through;
}
</style>
