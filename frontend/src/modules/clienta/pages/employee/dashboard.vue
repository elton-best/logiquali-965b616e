<template>
  <EmployeeLayout>
    <div class="role-dashboard">
      <div class="role-dashboard__header">
        <div>
          <div class="role-dashboard__eyebrow">Mon activité</div>
          <h1 class="role-dashboard__title">Bonjour, voici vos actions</h1>
          <p class="role-dashboard__subtitle">Priorisez les tâches à venir et gardez votre avancement à jour.</p>
        </div>
        <v-btn color="primary" prepend-icon="mdi-refresh" rounded="lg" variant="tonal" :loading="loading" @click="loadCollaboratorActions">
          Actualiser
        </v-btn>
      </div>

    <v-row class="role-dashboard__kpis mb-4">
      <v-col cols="12" md="3">
        <v-card color="primary" variant="tonal">
          <v-card-text class="text-center">
            <div class="text-h5 font-weight-bold">{{ summary?.stats.open_count || 0 }}</div>
            <div class="text-caption">Actions ouvertes</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3">
        <v-card color="error" variant="tonal">
          <v-card-text class="text-center">
            <div class="text-h5 font-weight-bold">{{ summary?.stats.overdue_count || 0 }}</div>
            <div class="text-caption">Actions en retard</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3">
        <v-card color="warning" variant="tonal">
          <v-card-text class="text-center">
            <div class="text-h5 font-weight-bold">{{ summary?.stats.due_soon_count || 0 }}</div>
            <div class="text-caption">À échéance proche</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3">
        <v-card color="success" variant="tonal">
          <v-card-text class="text-center">
            <div class="text-h5 font-weight-bold">{{ summary?.stats.completed_count || 0 }}</div>
            <div class="text-caption">Actions terminées</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-card title="Mes actions" variant="flat">
      <template #append>
        <v-btn
          :loading="loading"
          prepend-icon="mdi-refresh"
          size="small"
          variant="text"
          @click="loadCollaboratorActions"
        >
          Actualiser
        </v-btn>
      </template>

      <v-card-text>
        <v-alert
          v-if="error"
          class="mb-4"
          type="error"
          variant="tonal"
        >
          {{ error }}
        </v-alert>

        <div v-else-if="loading" class="d-flex justify-center py-6">
          <v-progress-circular color="primary" indeterminate />
        </div>

        <v-list v-else-if="actions.length > 0" density="comfortable">
          <v-list-item
            v-for="action in actions"
            :key="action.id"
            class="mb-2 rounded border"
          >
            <v-list-item-title class="d-flex align-center justify-space-between">
              <span class="font-weight-medium">{{ action.title }}</span>
              <v-chip
                :color="statusColor(action.status)"
                size="small"
                variant="tonal"
              >
                {{ statusLabel(action.status) }}
              </v-chip>
            </v-list-item-title>

            <v-list-item-subtitle>
              <div class="mb-2">
                {{ action.process?.title || action.process?.code || 'Sans processus' }}
                · Échéance: {{ formatDate(action.deadline) }}
              </div>
              <v-progress-linear
                color="primary"
                height="8"
                :model-value="action.progress || 0"
                rounded
              />
              <div class="d-flex align-center justify-space-between mt-1">
                <span class="text-caption">{{ action.progress || 0 }}%</span>
                <span class="text-caption text-medium-emphasis">
                  Maj: {{ formatDateTime(action.updated_at) }}
                </span>
              </div>
              <div
                v-if="latestNote(action)"
                class="text-caption text-medium-emphasis mt-1"
              >
                Dernière observation: {{ latestNote(action) }}
              </div>
            </v-list-item-subtitle>

            <template #append>
              <v-btn
                color="primary"
                size="small"
                variant="text"
                @click="openProgressDialog(action)"
              >
                Mettre à jour suivi
              </v-btn>
            </template>
          </v-list-item>
        </v-list>

        <div
          v-else
          class="text-center text-medium-emphasis py-6"
        >
          Aucune action assignée pour le moment.
        </div>
      </v-card-text>
    </v-card>

    <v-dialog
      v-model="progressDialog.open"
      max-width="520"
      persistent
    >
      <v-card>
        <v-card-title>Mettre à jour le suivi</v-card-title>
        <v-card-text>
          <div class="text-body-2 mb-3">{{ progressDialog.action?.title }}</div>
          <v-slider
            v-model="progressDialog.progress"
            class="mb-2"
            color="primary"
            :max="100"
            :min="0"
            step="5"
            thumb-label
          />
          <v-textarea
            v-model="progressDialog.notes"
            label="Observation"
            maxlength="1000"
            rows="3"
            variant="outlined"
          />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn
            variant="text"
            @click="closeProgressDialog"
          >
            Annuler
          </v-btn>
          <v-btn
            color="primary"
            :loading="progressDialog.saving"
            @click="submitProgress"
          >
            Enregistrer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    </div>
  </EmployeeLayout>
</template>

<script setup lang="ts">
  import type { CollaboratorActionSummary } from '@/services/dashboardService'
  import { reactive, ref } from 'vue'
  import { useToast } from 'vue-toastification'
  import { actionsService } from '@/api/services/actions.service'
  import EmployeeLayout from '@/modules/clienta/components/layouts/EmployeeLayout.vue'
  import { dashboardService } from '@/services/dashboardService'

  const toast = useToast()
  const loading = ref(false)
  const error = ref<string | null>(null)
  const summary = ref<CollaboratorActionSummary | null>(null)
  type CollaboratorAction = CollaboratorActionSummary['actions'][number]
  const actions = ref<CollaboratorAction[]>([])

  const progressDialog = reactive<{
    open: boolean
    action: CollaboratorAction | null
    progress: number
    notes: string
    saving: boolean
  }>({
    open: false,
    action: null,
    progress: 0,
    notes: '',
    saving: false,
  })

  function statusLabel (status: string): string {
    const labels: Record<string, string> = {
      in_progress: 'En cours',
      completed: 'Terminee',
      verified: 'Verifiee',
      closed: 'Cloturee',
      cancelled: 'Annulee',
      draft: 'Brouillon',
      planned: 'Planifiee',
      assigned: 'Assignee',
    }
    return labels[status] || status
  }

  function statusColor (status: string): string {
    const colors: Record<string, string> = {
      in_progress: 'primary',
      completed: 'success',
      verified: 'success',
      closed: 'success',
      cancelled: 'error',
      draft: 'grey',
      planned: 'warning',
      assigned: 'info',
    }
    return colors[status] || 'grey'
  }

  function formatDate (value?: string | null): string {
    if (!value) return 'Non definie'
    return new Date(value).toLocaleDateString('fr-FR')
  }

  function formatDateTime (value?: string | null): string {
    if (!value) return '-'
    return new Date(value).toLocaleString('fr-FR')
  }

  function latestNote (action: CollaboratorAction): string | null {
    const notes = Array.isArray(action.progress_notes) ? action.progress_notes : []
    if (notes.length === 0) {
      return null
    }
    const last = notes.at(-1)
    const author = last.user_name ? `${last.user_name} · ` : ''
    return `${author}${last.note}`
  }

  async function loadCollaboratorActions (): Promise<void> {
    loading.value = true
    error.value = null
    try {
      const data = await dashboardService.getCollaboratorActions({
        include_closed: false,
        per_page: 20,
      })
      summary.value = data
      actions.value = data.actions
    } catch (error_: any) {
      error.value = error_?.response?.data?.message || 'Impossible de charger vos actions.'
    } finally {
      loading.value = false
    }
  }

  function openProgressDialog (action: CollaboratorAction): void {
    progressDialog.action = action
    progressDialog.progress = Number(action.progress || 0)
    progressDialog.notes = ''
    progressDialog.open = true
  }

  function closeProgressDialog (): void {
    progressDialog.open = false
    progressDialog.action = null
    progressDialog.progress = 0
    progressDialog.notes = ''
  }

  async function submitProgress (): Promise<void> {
    if (!progressDialog.action) {
      return
    }

    progressDialog.saving = true
    try {
      await actionsService.updateProgress(progressDialog.action.id, {
        progress: progressDialog.progress,
        comments: progressDialog.notes.trim() || undefined,
      })
      await loadCollaboratorActions()
      closeProgressDialog()
      toast.success('Suivi mis à jour.')
    } catch (error_: any) {
      const message = error_?.response?.data?.message || 'Mise à jour du suivi impossible.'
      toast.error(message)
    } finally {
      progressDialog.saving = false
    }
  }

  loadCollaboratorActions()
</script>
