<template>
  <div class="history-timeline">
    <v-progress-circular
      v-if="loading"
      indeterminate
      size="32"
    />

    <div v-else-if="history.length === 0" class="no-history">
      <p>Aucun historique disponible</p>
    </div>

    <div v-else class="timeline">
      <div v-for="(entry, index) in history" :key="index" class="timeline-item">
        <div class="timeline-marker">
          <v-icon v-if="entry.status_old && entry.status_old !== entry.status_new" size="20">
            mdi-sync
          </v-icon>
          <v-icon v-else size="20">
            mdi-circle-small
          </v-icon>
        </div>

        <div class="timeline-content">
          <div class="timeline-header">
            <span class="timestamp">{{ formatDateTime(entry.changed_at) }}</span>
            <span class="changed-by">par {{ entry.changed_by }}</span>
          </div>

          <div class="timeline-changes">
            <div v-if="entry.status_old && entry.status_old !== entry.status_new" class="change-item">
              <span class="label">Statut :</span>
              <span class="old-value">{{ getStatusLabel(entry.status_old) }}</span>
              <v-icon small>mdi-arrow-right</v-icon>
              <span class="new-value">{{ getStatusLabel(entry.status_new) }}</span>
            </div>

            <div v-if="entry.rate_old || entry.rate_old === 0" class="change-item">
              <span class="label">Taux :</span>
              <span class="old-value">{{ entry.rate_old }}%</span>
              <v-icon small>mdi-arrow-right</v-icon>
              <span class="new-value">{{ entry.rate_new }}%</span>
            </div>

            <div v-if="entry.notes" class="change-item notes">
              <span class="label">Notes :</span>
              <span class="notes-text">{{ entry.notes }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
  import { onMounted, ref } from 'vue'
  import { useMyTasks } from '@/composables/useMyTasks'
  import { useNotification } from '@/plugins/notification'

  const props = defineProps({
    taskType: {
      type: String,
      required: true,
    },
    taskId: {
      type: Number,
      required: true,
    },
  })

  const notification = useNotification()
  const { fetchTaskHistory } = useMyTasks()

  const loading = ref(false)
  const history = ref([])

  function formatDateTime (dateTime) {
    const date = new Date(dateTime)
    return date.toLocaleDateString('fr-FR', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  }

  function getStatusLabel (status) {
    const map = {
      non_demarre: 'Non démarré',
      en_cours: 'En cours',
      termine: 'Terminé',
    }
    return map[status] || status
  }

  async function loadHistory () {
    loading.value = true
    try {
      history.value = await fetchTaskHistory(props.taskType, props.taskId)
    } catch (error) {
      notification.error(error?.message || 'Erreur lors du chargement')
    } finally {
      loading.value = false
    }
  }

  onMounted(() => {
    loadHistory()
  })
</script>

<style scoped lang="scss">
.history-timeline {
  width: 100%;

  .no-history {
    text-align: center;
    padding: 20px;
    color: rgba(0, 0, 0, 0.5);
  }

  .timeline {
    position: relative;
    padding: 0 20px;

    &::before {
      content: '';
      position: absolute;
      left: 35px;
      top: 0;
      bottom: 0;
      width: 2px;
      background: rgba(0, 0, 0, 0.1);
    }
  }

  .timeline-item {
    display: flex;
    margin-bottom: 20px;
    position: relative;

    .timeline-marker {
      position: absolute;
      left: 12px;
      top: 4px;
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: white;
      border-radius: 50%;
      border: 2px solid rgba(0, 0, 0, 0.1);

      :deep(.v-icon) {
        color: #1976d2;
      }
    }

    .timeline-content {
      margin-left: 70px;
      padding: 12px;
      background: rgba(0, 0, 0, 0.02);
      border-radius: 4px;
      flex: 1;

      .timeline-header {
        display: flex;
        gap: 12px;
        margin-bottom: 8px;
        font-size: 12px;

        .timestamp {
          font-weight: 600;
          color: rgba(0, 0, 0, 0.8);
        }

        .changed-by {
          color: rgba(0, 0, 0, 0.6);
        }
      }

      .timeline-changes {
        display: flex;
        flex-direction: column;
        gap: 6px;
        font-size: 13px;

        .change-item {
          display: flex;
          align-items: center;
          gap: 8px;

          .label {
            font-weight: 500;
            color: rgba(0, 0, 0, 0.7);
          }

          .old-value {
            color: #d32f2f;
            text-decoration: line-through;
          }

          .new-value {
            color: #388e3c;
            font-weight: 500;
          }

          :deep(.v-icon) {
            color: rgba(0, 0, 0, 0.4);
          }

          &.notes {
            flex-direction: column;
            align-items: flex-start;

            .notes-text {
              margin-left: 0;
              padding: 8px;
              background: white;
              border-left: 3px solid #1976d2;
              border-radius: 2px;
              font-style: italic;
              color: rgba(0, 0, 0, 0.7);
            }
          }
        }
      }
    }
  }
}
</style>
