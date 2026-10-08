<template>
  <ClientALayout current-page="performance-revue">
    <v-container class="pa-6" fluid>
      <PageHeader icon="mdi-account-group" title="Revue de Direction">
        <template #subtitle>
          Rapports et invitations pour les revues de direction (M12-D2/D3)
        </template>
        <template #actions>
          <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreateDialog">
            Nouvelle revue
          </v-btn>
        </template>
      </PageHeader>

      <v-row class="mt-6">
        <v-col cols="12" md="4">
          <v-card rounded="xl">
            <v-card-text class="text-center">
              <v-icon color="primary" size="40">mdi-calendar-month</v-icon>
              <div class="text-h4 mt-2 font-weight-bold">{{ stats.scheduled }}</div>
              <div class="text-caption text-grey">Planifiées</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="4">
          <v-card rounded="xl">
            <v-card-text class="text-center">
              <v-icon color="success" size="40">mdi-check-circle</v-icon>
              <div class="text-h4 mt-2 font-weight-bold">{{ stats.completed }}</div>
              <div class="text-caption text-grey">Réalisées</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="4">
          <v-card rounded="xl">
            <v-card-text class="text-center">
              <v-icon color="warning" size="40">mdi-clipboard-text</v-icon>
              <div class="text-h4 mt-2 font-weight-bold">{{ stats.actions }}</div>
              <div class="text-caption text-grey">Actions décidées</div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <v-tabs v-model="activeTab" class="mt-6" color="primary">
        <v-tab value="reports">Rapports</v-tab>
        <v-tab value="invitations">Invitations</v-tab>
        <v-tab value="decisions">Décisions</v-tab>
      </v-tabs>

      <v-window v-model="activeTab" class="mt-6">
        <!-- Rapports -->
        <v-window-item value="reports">
          <v-card rounded="xl">
            <v-card-text>
              <v-data-table
                :headers="reportHeaders"
                :items="reports"
                items-per-page="10"
                :loading="loading"
              >
                <template #[`item.status`]="{ item }">
                  <v-chip :color="getStatusColor(item.status)" size="small" variant="tonal">
                    {{ getStatusLabel(item.status) }}
                  </v-chip>
                </template>
                <template #[`item.participants`]="{ item }">
                  <v-chip size="small" variant="outlined">
                    {{ item.participants }} participants
                  </v-chip>
                </template>
                <template #[`item.actions`]="{ item }">
                  <v-btn icon="mdi-eye" size="small" variant="text" @click="viewReport(item)" />
                  <v-btn icon="mdi-pencil" size="small" variant="text" @click="editReport(item)" />
                  <v-btn icon="mdi-file-pdf-box" size="small" variant="text" @click="exportReport(item.id)" />
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-window-item>

        <!-- Invitations -->
        <v-window-item value="invitations">
          <v-card rounded="xl">
            <v-card-title class="pa-6 d-flex align-center">
              <span>Gestion des invitations</span>
              <v-spacer />
              <v-btn color="primary" prepend-icon="mdi-email-send" size="small" @click="openInvitationDialog">
                Envoyer des invitations
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-data-table
                :headers="invitationHeaders"
                :items="invitations"
                items-per-page="10"
                :loading="loading"
              >
                <template #[`item.status`]="{ item }">
                  <v-chip :color="getInvitationStatusColor(item.status)" size="small" variant="tonal">
                    {{ getInvitationStatusLabel(item.status) }}
                  </v-chip>
                </template>
                <template #[`item.actions`]="{ item }">
                  <v-btn icon="mdi-email-sync" size="small" variant="text" @click="resendInvitation(item)" />
                  <v-btn
                    color="error"
                    icon="mdi-delete"
                    size="small"
                    variant="text"
                    @click="deleteInvitation(item)"
                  />
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-window-item>

        <!-- Décisions -->
        <v-window-item value="decisions">
          <v-card rounded="xl">
            <v-card-text>
              <v-data-table
                :headers="decisionHeaders"
                :items="decisions"
                items-per-page="10"
                :loading="loading"
              >
                <template #[`item.priority`]="{ item }">
                  <v-chip :color="getPriorityColor(item.priority)" size="small" variant="tonal">
                    {{ item.priority }}
                  </v-chip>
                </template>
                <template #[`item.status`]="{ item }">
                  <v-chip :color="getDecisionStatusColor(item.status)" size="small" variant="tonal">
                    {{ item.status }}
                  </v-chip>
                </template>
                <template #[`item.actions`]="{ item }">
                  <v-btn icon="mdi-eye" size="small" variant="text" @click="viewDecision(item)" />
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-window-item>
      </v-window>

      <!-- Dialog Création Revue -->
      <v-dialog v-model="showDialog" max-width="900">
        <v-card rounded="xl">
          <v-card-title class="pa-6">{{ editingId ? 'Modifier' : 'Créer' }} une revue de direction</v-card-title>
          <v-card-text class="px-6">
            <v-row>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.title" label="Titre" variant="outlined" />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.date" label="Date" type="date" variant="outlined" />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.time" label="Heure" type="time" variant="outlined" />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.location" label="Lieu" variant="outlined" />
              </v-col>
              <v-col cols="12">
                <v-textarea v-model="form.agenda" label="Ordre du jour" rows="4" variant="outlined" />
              </v-col>
              <v-col cols="12">
                <v-textarea v-model="form.objectives" label="Objectifs" rows="3" variant="outlined" />
              </v-col>
            </v-row>
          </v-card-text>
          <v-card-actions class="px-6 pb-6">
            <v-spacer />
            <v-btn variant="text" @click="showDialog = false">Annuler</v-btn>
            <v-btn color="primary" :loading="saving" @click="save">Enregistrer</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <!-- Dialog Invitations -->
      <v-dialog v-model="showInvitationDialog" max-width="700">
        <v-card rounded="xl">
          <v-card-title class="pa-6">Envoyer des invitations</v-card-title>
          <v-card-text class="px-6">
            <v-row>
              <v-col cols="12">
                <v-select
                  v-model="invitationForm.review_id"
                  item-title="title"
                  item-value="id"
                  :items="reports"
                  label="Revue de direction"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12">
                <v-combobox
                  v-model="invitationForm.emails"
                  chips
                  clearable
                  label="Emails des participants"
                  multiple
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12">
                <v-textarea v-model="invitationForm.message" label="Message personnalisé" rows="4" variant="outlined" />
              </v-col>
            </v-row>
          </v-card-text>
          <v-card-actions class="px-6 pb-6">
            <v-spacer />
            <v-btn variant="text" @click="showInvitationDialog = false">Annuler</v-btn>
            <v-btn color="primary" :loading="sending" @click="sendInvitations">Envoyer</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { onMounted, reactive, ref } from 'vue'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'

  const activeTab = ref('reports')
  const loading = ref(false)
  const saving = ref(false)
  const sending = ref(false)
  const showDialog = ref(false)
  const showInvitationDialog = ref(false)
  const editingId = ref<number | null>(null)

  const reports = ref<any[]>([])
  const invitations = ref<any[]>([])
  const decisions = ref<any[]>([])

  const stats = ref({
    scheduled: 3,
    completed: 12,
    actions: 24,
  })

  const reportHeaders = [
    { title: 'Référence', key: 'reference' },
    { title: 'Titre', key: 'title' },
    { title: 'Date', key: 'date' },
    { title: 'Participants', key: 'participants' },
    { title: 'Statut', key: 'status' },
    { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
  ]

  const invitationHeaders = [
    { title: 'Revue', key: 'review_title' },
    { title: 'Participant', key: 'participant_name' },
    { title: 'Email', key: 'email' },
    { title: 'Envoyé le', key: 'sent_at' },
    { title: 'Statut', key: 'status' },
    { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
  ]

  const decisionHeaders = [
    { title: 'Revue', key: 'review_title' },
    { title: 'Décision', key: 'decision' },
    { title: 'Responsable', key: 'responsible' },
    { title: 'Échéance', key: 'deadline' },
    { title: 'Priorité', key: 'priority' },
    { title: 'Statut', key: 'status' },
    { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
  ]

  const form = reactive({
    title: '',
    date: '',
    time: '',
    location: '',
    agenda: '',
    objectives: '',
  })

  const invitationForm = reactive({
    review_id: null as number | null,
    emails: [] as string[],
    message: '',
  })

  function getStatusColor (status: string) {
    const colors: Record<string, string> = {
      draft: 'grey',
      scheduled: 'primary',
      completed: 'success',
      cancelled: 'error',
    }
    return colors[status] || 'grey'
  }

  function getStatusLabel (status: string) {
    const labels: Record<string, string> = {
      draft: 'Brouillon',
      scheduled: 'Planifiée',
      completed: 'Réalisée',
      cancelled: 'Annulée',
    }
    return labels[status] || status
  }

  function getInvitationStatusColor (status: string) {
    const colors: Record<string, string> = {
      sent: 'info',
      accepted: 'success',
      declined: 'error',
      pending: 'warning',
    }
    return colors[status] || 'grey'
  }

  function getInvitationStatusLabel (status: string) {
    const labels: Record<string, string> = {
      sent: 'Envoyée',
      accepted: 'Acceptée',
      declined: 'Refusée',
      pending: 'En attente',
    }
    return labels[status] || status
  }

  function getPriorityColor (priority: string) {
    const colors: Record<string, string> = {
      high: 'error',
      medium: 'warning',
      low: 'info',
    }
    return colors[priority] || 'grey'
  }

  function getDecisionStatusColor (status: string) {
    const colors: Record<string, string> = {
      pending: 'warning',
      in_progress: 'info',
      completed: 'success',
    }
    return colors[status] || 'grey'
  }

  function openCreateDialog () {
    editingId.value = null
    Object.assign(form, {
      title: '',
      date: '',
      time: '',
      location: '',
      agenda: '',
      objectives: '',
    })
    showDialog.value = true
  }

  function openInvitationDialog () {
    Object.assign(invitationForm, {
      review_id: null,
      emails: [],
      message: '',
    })
    showInvitationDialog.value = true
  }

  function editReport (item: any) {
    editingId.value = item.id
    Object.assign(form, item)
    showDialog.value = true
  }

  function viewReport (item: any) {
    console.log('View report:', item)
  }

  function viewDecision (item: any) {
    console.log('View decision:', item)
  }

  async function exportReport (id: number) {
    console.log('Export report:', id)
  }

  async function resendInvitation (item: any) {
    console.log('Resend invitation:', item)
  }

  async function deleteInvitation (item: any) {
    if (confirm('Supprimer cette invitation ?')) {
      console.log('Delete invitation:', item)
    }
  }

  async function save () {
    saving.value = true
    try {
      await new Promise(resolve => setTimeout(resolve, 1000))
      showDialog.value = false
    } finally {
      saving.value = false
    }
  }

  async function sendInvitations () {
    sending.value = true
    try {
      await new Promise(resolve => setTimeout(resolve, 1000))
      showInvitationDialog.value = false
    } finally {
      sending.value = false
    }
  }

  onMounted(() => {
    // Load data
  })
</script>
