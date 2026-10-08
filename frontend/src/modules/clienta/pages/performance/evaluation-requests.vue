<template>
  <ClientALayout current-page="performance-surveillance">
    <v-container class="pa-6" fluid>
      <PageHeader icon="mdi-email-fast-outline" title="Demandes d'évaluation">
        <template #subtitle>
          Gérez vos demandes d'évaluation et suivez les réponses.
        </template>
        <template #actions>
          <v-btn color="secondary" prepend-icon="mdi-tune-vertical" variant="outlined" @click="goToCriteria">
            Définir les critères
          </v-btn>
          <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreateDialog">
            Nouvelle demande
          </v-btn>
        </template>
      </PageHeader>

      <!-- Statistiques -->
      <v-row class="mb-4 mt-2">
        <v-col cols="12" md="3">
          <v-card class="stats-card" rounded="xl">
            <v-card-text class="text-center">
              <v-icon class="mb-2" color="primary" size="32">mdi-email-outline</v-icon>
              <div class="text-h4">{{ stats.total_requests }}</div>
              <div class="text-caption text-medium-emphasis">Total demandes</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="3">
          <v-card class="stats-card" rounded="xl">
            <v-card-text class="text-center">
              <v-icon class="mb-2" color="success" size="32">mdi-check-circle</v-icon>
              <div class="text-h4">{{ stats.by_status?.completed || 0 }}</div>
              <div class="text-caption text-medium-emphasis">Complétées</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="3">
          <v-card class="stats-card" rounded="xl">
            <v-card-text class="text-center">
              <v-icon class="mb-2" color="info" size="32">mdi-percent</v-icon>
              <div class="text-h4">{{ stats.response_rate?.toFixed(0) || 0 }}%</div>
              <div class="text-caption text-medium-emphasis">Taux de réponse</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="3">
          <v-card class="stats-card" rounded="xl">
            <v-card-text class="text-center">
              <v-icon class="mb-2" color="warning" size="32">mdi-star</v-icon>
              <div class="text-h4">{{ stats.average_score?.toFixed(1) || '-' }}</div>
              <div class="text-caption text-medium-emphasis">Score moyen</div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Filtres -->
      <v-card class="filters-card mb-4" rounded="xl" variant="tonal">
        <v-card-text>
          <v-row>
            <v-col cols="12" md="4">
              <v-select
                v-model="filters.form_type"
                clearable
                density="compact"
                hide-details
                :items="formTypeOptions"
                label="Type de formulaire"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12" md="4">
              <v-select
                v-model="filters.status"
                clearable
                density="compact"
                hide-details
                :items="statusOptions"
                label="Statut"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field
                v-model="search"
                clearable
                density="compact"
                hide-details
                label="Rechercher..."
                prepend-inner-icon="mdi-magnify"
                variant="outlined"
              />
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>

      <!-- Liste des demandes -->
      <v-card class="data-card" rounded="xl">
        <v-data-table
          :headers="headers"
          hover
          :items="filteredRequests"
          :loading="loading"
          :search="search"
        >
          <template #[`item.form_type`]="{ item }">
            <v-chip :color="getFormTypeColor(item.form_type)" label size="small">
              {{ getFormTypeLabel(item.form_type) }}
            </v-chip>
          </template>

          <template #[`item.status`]="{ item }">
            <v-chip :color="getStatusColor(item.status)" size="small">
              <v-icon size="14" start>{{ getStatusIcon(item.status) }}</v-icon>
              {{ getStatusLabel(item.status) }}
            </v-chip>
          </template>

          <template #[`item.recipient`]="{ item }">
            <div>
              <div class="font-weight-medium">
                {{ item.recipient_name || (item.form_type === 'satisfaction_client' ? 'Lien public anonyme' : 'Non spécifié') }}
              </div>
              <div v-if="item.recipient_email" class="text-caption text-medium-emphasis">{{ item.recipient_email }}</div>
            </div>
          </template>

          <template #[`item.sent_at`]="{ item }">
            <span v-if="item.sent_at">{{ formatDate(item.sent_at) }}</span>
            <span v-else class="text-medium-emphasis">Non envoyée</span>
          </template>

          <template #[`item.response`]="{ item }">
            <template v-if="item.status === 'completed'">
              <v-chip color="success" size="small">
                <v-icon size="14" start>mdi-check</v-icon>
                Répondu
              </v-chip>
            </template>
            <template v-else-if="item.status === 'sent' || item.status === 'opened'">
              <v-chip color="warning" size="small">
                En attente
              </v-chip>
            </template>
            <template v-else>
              <span class="text-medium-emphasis">-</span>
            </template>
          </template>

          <template #[`item.actions`]="{ item }">
            <v-menu location="bottom end">
              <template #activator="{ props }">
                <v-btn icon="mdi-dots-vertical" size="small" variant="text" v-bind="props" />
              </template>
              <v-list density="compact">
                <v-list-item @click="viewRequest(item)">
                  <template #prepend>
                    <v-icon size="18">mdi-eye</v-icon>
                  </template>
                  <v-list-item-title>Voir détails</v-list-item-title>
                </v-list-item>

                <v-list-item
                  v-if="item.status === 'draft'"
                  @click="editRequest(item)"
                >
                  <template #prepend>
                    <v-icon size="18">mdi-pencil</v-icon>
                  </template>
                  <v-list-item-title>Modifier</v-list-item-title>
                </v-list-item>

                <v-list-item
                  v-if="(item.status === 'draft' || item.status === 'pending') && item.form_type !== 'satisfaction_client'"
                  @click="sendRequest(item)"
                >
                  <template #prepend>
                    <v-icon color="primary" size="18">mdi-send</v-icon>
                  </template>
                  <v-list-item-title>Envoyer</v-list-item-title>
                </v-list-item>

                <v-list-item
                  v-if="(item.status === 'sent' || item.status === 'opened') && item.form_type !== 'satisfaction_client'"
                  @click="sendReminder(item)"
                >
                  <template #prepend>
                    <v-icon color="warning" size="18">mdi-bell</v-icon>
                  </template>
                  <v-list-item-title>Relancer</v-list-item-title>
                </v-list-item>

                <v-list-item @click="copyLink(item)">
                  <template #prepend>
                    <v-icon size="18">mdi-content-copy</v-icon>
                  </template>
                  <v-list-item-title>Copier le lien</v-list-item-title>
                </v-list-item>

                <v-divider class="my-1" />

                <v-list-item
                  v-if="!['completed', 'cancelled'].includes(item.status)"
                  @click="cancelRequest(item)"
                >
                  <template #prepend>
                    <v-icon color="error" size="18">mdi-cancel</v-icon>
                  </template>
                  <v-list-item-title class="text-error">Annuler</v-list-item-title>
                </v-list-item>

                <v-list-item
                  v-if="item.status === 'draft'"
                  @click="confirmDelete(item)"
                >
                  <template #prepend>
                    <v-icon color="error" size="18">mdi-delete</v-icon>
                  </template>
                  <v-list-item-title class="text-error">Supprimer</v-list-item-title>
                </v-list-item>
              </v-list>
            </v-menu>
          </template>
        </v-data-table>
      </v-card>

      <!-- Dialog création/édition -->
      <v-dialog v-model="dialog.show" max-width="860" persistent>
        <v-card class="modern-modal request-modal">
          <v-card-title class="modal-title">
            <div class="modal-title-inner d-flex align-center justify-space-between">
              <div>
                <div class="text-overline mb-1">Formulaire d'évaluation</div>
                <div class="text-h6 font-weight-bold">
                  {{ dialog.mode === 'create' ? 'Nouvelle demande' : 'Modifier la demande' }}
                </div>
              </div>
              <v-chip color="primary" size="small" variant="flat">
                {{ getFormTypeLabel(form.form_type) }}
              </v-chip>
            </div>
            <v-stepper v-model="dialogStep" alt-labels class="mt-4 request-stepper" flat>
              <v-stepper-header>
                <v-stepper-item subtitle="Destinataire, type, titre" title="Informations" :value="1" />
                <v-divider />
                <v-stepper-item subtitle="Critères et messages" title="Contenu" :value="2" />
              </v-stepper-header>
            </v-stepper>
          </v-card-title>
          <v-card-text>
            <v-form ref="formRef" v-model="formValid">
              <v-window v-model="dialogStep">
                <v-window-item :value="1">
                  <v-row>
                    <v-col cols="12">
                      <v-text-field
                        v-model="form.title"
                        density="compact"
                        label="Titre de la demande *"
                        :rules="[v => !!v || 'Titre requis']"
                        variant="outlined"
                      />
                    </v-col>

                    <v-col cols="12" md="6">
                      <v-select
                        v-model="form.form_type"
                        density="compact"
                        :items="formTypeOptions"
                        label="Type de formulaire *"
                        :rules="[v => !!v || 'Type requis']"
                        variant="outlined"
                      />
                    </v-col>

                    <v-col cols="12" md="6">
                      <v-text-field
                        v-model="form.expires_at"
                        density="compact"
                        label="Date d'expiration"
                        type="date"
                        variant="outlined"
                      />
                    </v-col>

                    <v-col v-if="!isAnonymousSatisfactionForm" cols="12" md="6">
                      <v-text-field
                        v-model="form.recipient_email"
                        density="compact"
                        label="Email du destinataire *"
                        :rules="[
                          v => !!v || 'Email requis',
                          v => /.+@.+\..+/.test(v) || 'Email invalide'
                        ]"
                        type="email"
                        variant="outlined"
                      />
                    </v-col>

                    <v-col v-if="!isAnonymousSatisfactionForm" cols="12" md="6">
                      <v-text-field
                        v-model="form.recipient_name"
                        density="compact"
                        label="Nom du destinataire"
                        variant="outlined"
                      />
                    </v-col>
                  </v-row>
                </v-window-item>

                <v-window-item :value="2">
                  <v-row>
                    <v-col cols="12">
                      <v-select
                        v-model="form.criteria_ids"
                        chips
                        closable-chips
                        density="compact"
                        item-title="name"
                        item-value="id"
                        :items="availableCriteria"
                        label="Critères d'évaluation *"
                        :loading="loadingCriteria"
                        multiple
                        :rules="[v => v?.length > 0 || 'Au moins un critère requis']"
                        variant="outlined"
                      >
                        <template #chip="{ item, props }">
                          <v-chip v-bind="props" size="small">
                            {{ item.raw.name }}
                          </v-chip>
                        </template>
                      </v-select>
                    </v-col>

                    <v-col cols="12">
                      <v-textarea
                        v-model="form.custom_message"
                        density="compact"
                        hint="Ce message sera inclus dans l'email d'invitation"
                        label="Message personnalisé (optionnel)"
                        rows="3"
                        variant="outlined"
                      />
                    </v-col>

                    <v-col cols="12">
                      <v-textarea
                        v-model="form.instructions"
                        density="compact"
                        hint="Ces instructions seront affichées sur le formulaire"
                        label="Instructions pour l'évaluateur (optionnel)"
                        rows="2"
                        variant="outlined"
                      />
                    </v-col>
                  </v-row>
                </v-window-item>
              </v-window>
            </v-form>
          </v-card-text>
          <v-card-actions>
            <v-btn v-if="dialogStep > 1" variant="text" @click="dialogStep--">Retour</v-btn>
            <v-spacer />
            <v-btn v-if="dialogStep < 2" color="primary" variant="tonal" @click="dialogStep++">Continuer</v-btn>
            <v-btn variant="text" @click="closeDialog">Annuler</v-btn>
            <v-btn
              v-if="dialogStep === 2"
              color="primary"
              :loading="saving"
              variant="flat"
              @click="saveRequest"
            >
              {{ dialog.mode === 'create' ? 'Créer' : 'Enregistrer' }}
            </v-btn>
            <v-btn
              v-if="dialog.mode === 'create' && dialogStep === 2 && !isAnonymousSatisfactionForm"
              color="success"
              :loading="saving"
              variant="flat"
              @click="saveAndSend"
            >
              Créer et envoyer
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <!-- Dialog détails -->
      <v-dialog v-model="detailsDialog.show" max-width="800">
        <v-card v-if="detailsDialog.request">
          <v-card-title class="d-flex justify-space-between align-center">
            <span>{{ detailsDialog.request.title }}</span>
            <v-chip :color="getStatusColor(detailsDialog.request.status)" size="small">
              {{ getStatusLabel(detailsDialog.request.status) }}
            </v-chip>
          </v-card-title>
          <v-card-text>
            <v-row>
              <v-col cols="12" md="6">
                <v-list density="compact">
                  <v-list-item>
                    <template #prepend>
                      <v-icon>mdi-account</v-icon>
                    </template>
                    <v-list-item-title>Destinataire</v-list-item-title>
                    <v-list-item-subtitle>
                      {{ detailsDialog.request.recipient_name || detailsDialog.request.recipient_email || 'Lien public anonyme' }}
                    </v-list-item-subtitle>
                  </v-list-item>
                  <v-list-item>
                    <template #prepend>
                      <v-icon>mdi-tag</v-icon>
                    </template>
                    <v-list-item-title>Type</v-list-item-title>
                    <v-list-item-subtitle>
                      {{ getFormTypeLabel(detailsDialog.request.form_type) }}
                    </v-list-item-subtitle>
                  </v-list-item>
                  <v-list-item>
                    <template #prepend>
                      <v-icon>mdi-calendar</v-icon>
                    </template>
                    <v-list-item-title>Envoyée le</v-list-item-title>
                    <v-list-item-subtitle>
                      {{ detailsDialog.request.sent_at ? formatDate(detailsDialog.request.sent_at) : 'Non envoyée' }}
                    </v-list-item-subtitle>
                  </v-list-item>
                </v-list>
              </v-col>
              <v-col cols="12" md="6">
                <v-list density="compact">
                  <v-list-item>
                    <template #prepend>
                      <v-icon>mdi-clock</v-icon>
                    </template>
                    <v-list-item-title>Expire le</v-list-item-title>
                    <v-list-item-subtitle>
                      {{ detailsDialog.request.expires_at ? formatDate(detailsDialog.request.expires_at) : 'Pas d\'expiration' }}
                    </v-list-item-subtitle>
                  </v-list-item>
                  <v-list-item>
                    <template #prepend>
                      <v-icon>mdi-bell</v-icon>
                    </template>
                    <v-list-item-title>Relances</v-list-item-title>
                    <v-list-item-subtitle>
                      {{ detailsDialog.request.reminder_count }} relance(s)
                    </v-list-item-subtitle>
                  </v-list-item>
                  <v-list-item v-if="detailsDialog.request.completed_at">
                    <template #prepend>
                      <v-icon color="success">mdi-check-circle</v-icon>
                    </template>
                    <v-list-item-title>Complétée le</v-list-item-title>
                    <v-list-item-subtitle>
                      {{ formatDate(detailsDialog.request.completed_at) }}
                    </v-list-item-subtitle>
                  </v-list-item>
                </v-list>
              </v-col>
            </v-row>

            <v-divider class="my-4" />

            <div class="d-flex align-center justify-space-between mb-2">
              <h4>Lien du formulaire</h4>
              <v-btn prepend-icon="mdi-content-copy" size="small" variant="text" @click="copyLink(detailsDialog.request)">
                Copier
              </v-btn>
            </div>
            <v-text-field
              density="compact"
              hide-details
              :model-value="detailsDialog.request.public_url"
              readonly
              variant="outlined"
            />

            <!-- Réponses -->
            <template v-if="detailsDialog.responses?.length">
              <v-divider class="my-4" />
              <h4 class="mb-3">Réponses ({{ detailsDialog.responses.length }})</h4>
              <v-card
                v-for="response in detailsDialog.responses"
                :key="response.id"
                class="mb-2"
                variant="outlined"
              >
                <v-card-text>
                  <div class="d-flex justify-space-between align-center mb-2">
                    <span>{{ response.respondent_name || 'Anonyme' }}</span>
                    <v-chip color="primary" size="small">
                      Score: {{ response.overall_score?.toFixed(1) || '-' }}/5
                    </v-chip>
                  </div>
                  <div class="text-caption text-medium-emphasis">
                    Soumis le {{ formatDate(response.submitted_at) }}
                  </div>
                  <p v-if="response.global_comment" class="mt-2 text-body-2">
                    "{{ response.global_comment }}"
                  </p>

                  <v-divider class="my-3" />
                  <div v-if="response.score_rows?.length">
                    <div class="text-caption text-medium-emphasis mb-2">Choix par critère</div>
                    <div
                      v-for="row in response.score_rows"
                      :key="`${response.id}-${row.criterion_id}`"
                      class="criterion-row"
                    >
                      <div class="criterion-name">{{ row.criterion_name }}</div>
                      <div class="criterion-score">Score: {{ row.score ?? '-' }}</div>
                      <div v-if="row.comment" class="criterion-comment">
                        {{ row.comment }}
                      </div>
                    </div>
                  </div>
                </v-card-text>
              </v-card>
            </template>
          </v-card-text>
          <v-card-actions>
            <v-spacer />
            <v-btn variant="text" @click="detailsDialog.show = false">Fermer</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <!-- Snackbar -->
      <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="3000">
        {{ snackbar.text }}
        <template #actions>
          <v-btn variant="text" @click="snackbar.show = false">Fermer</v-btn>
        </template>
      </v-snackbar>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, reactive, ref, watch } from 'vue'
  import { useRouter } from 'vue-router'
  import evaluationCriteriaApi, { type EvaluationCriteria } from '@/api/services/evaluationCriteria'
  import evaluationRequestsApi, { type EvaluationRequest, type EvaluationResponse } from '@/api/services/evaluationRequests'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'

  const router = useRouter()

  const loading = ref(false)
  const saving = ref(false)
  const loadingCriteria = ref(false)
  const formValid = ref(false)
  const formRef = ref()
  const search = ref('')
  const dialogStep = ref(1)

  const requests = ref<EvaluationRequest[]>([])
  const availableCriteria = ref<EvaluationCriteria[]>([])
  const stats = ref<Record<string, any>>({})

  const filters = reactive({
    form_type: null as string | null,
    status: null as string | null,
  })

  const dialog = reactive({
    show: false,
    mode: 'create' as 'create' | 'edit',
    id: null as number | null,
  })

  const form = reactive({
    title: '',
    form_type: 'satisfaction_client' as EvaluationRequest['form_type'],
    recipient_email: '',
    recipient_name: '',
    criteria_ids: [] as number[],
    custom_message: '',
    instructions: '',
    expires_at: '',
  })

  const detailsDialog = reactive({
    show: false,
    request: null as EvaluationRequest | null,
    responses: [] as EvaluationResponse[],
  })

  const snackbar = reactive({
    show: false,
    text: '',
    color: 'success',
  })

  const headers = [
    { title: 'Titre', key: 'title' },
    { title: 'Type', key: 'form_type', width: 160 },
    { title: 'Destinataire', key: 'recipient', sortable: false },
    { title: 'Statut', key: 'status', width: 130 },
    { title: 'Envoyée le', key: 'sent_at', width: 130 },
    { title: 'Réponse', key: 'response', width: 120 },
    { title: '', key: 'actions', width: 60, sortable: false },
  ]

  const formTypeOptions = [
    { title: 'Satisfaction client', value: 'satisfaction_client' },
    { title: 'Satisfaction personnel', value: 'satisfaction_personnel' },
    { title: 'Performance personnel', value: 'performance_personnel' },
    { title: 'Évaluation auditeur', value: 'evaluation_auditeur' },
    { title: 'Satisfaction prestataires', value: 'satisfaction_fournisseur' },
    { title: 'Performance prestataires', value: 'performance_fournisseur' },
    { title: 'Audit interne', value: 'audit_interne' },
    { title: 'Personnalisé', value: 'custom' },
  ]

  const statusOptions = [
    { title: 'Brouillon', value: 'draft' },
    { title: 'En attente', value: 'pending' },
    { title: 'Envoyée', value: 'sent' },
    { title: 'Ouverte', value: 'opened' },
    { title: 'Complétée', value: 'completed' },
    { title: 'Expirée', value: 'expired' },
    { title: 'Annulée', value: 'cancelled' },
  ]

  const filteredRequests = computed(() => {
    let result = requests.value
    if (filters.form_type) {
      result = result.filter(r => r.form_type === filters.form_type)
    }
    if (filters.status) {
      result = result.filter(r => r.status === filters.status)
    }
    return result
  })

  const isAnonymousSatisfactionForm = computed(() => form.form_type === 'satisfaction_client')

  function getFormTypeColor (type: string): string {
    const colors: Record<string, string> = {
      satisfaction_client: 'primary',
      satisfaction_personnel: '#7c3aed',
      performance_personnel: '#1d4ed8',
      evaluation_personnel: '#2980b9',
      evaluation_auditeur: 'orange',
      satisfaction_fournisseur: '#0d9488',
      performance_fournisseur: '#0f766e',
      evaluation_fournisseur: 'teal',
      audit_interne: '#2980b9',
      custom: 'grey',
    }
    return colors[type] || 'grey'
  }

  function getFormTypeLabel (type: string): string {
    const option = formTypeOptions.find(o => o.value === type)
    return option?.title || type
  }

  function getStatusColor (status: string): string {
    const colors: Record<string, string> = {
      draft: 'grey',
      pending: 'blue-grey',
      sent: 'info',
      opened: 'warning',
      completed: 'success',
      expired: 'error',
      cancelled: 'grey-darken-1',
    }
    return colors[status] || 'grey'
  }

  function getStatusIcon (status: string): string {
    const icons: Record<string, string> = {
      draft: 'mdi-file-document-outline',
      pending: 'mdi-clock-outline',
      sent: 'mdi-email-check',
      opened: 'mdi-eye',
      completed: 'mdi-check-circle',
      expired: 'mdi-timer-off',
      cancelled: 'mdi-cancel',
    }
    return icons[status] || 'mdi-help-circle'
  }

  function getStatusLabel (status: string): string {
    const option = statusOptions.find(o => o.value === status)
    return option?.title || status
  }

  function formatDate (dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  }

  async function fetchRequests () {
    loading.value = true
    try {
      const { data } = await evaluationRequestsApi.getAll()
      requests.value = data.data
    } catch (error) {
      console.error('Error fetching requests:', error)
      showSnackbar('Erreur lors du chargement', 'error')
    } finally {
      loading.value = false
    }
  }

  async function fetchStatistics () {
    try {
      const { data } = await evaluationRequestsApi.getStatistics()
      stats.value = data
    } catch (error) {
      console.error('Error fetching statistics:', error)
    }
  }

  async function fetchCriteria () {
    loadingCriteria.value = true
    try {
      const data = await evaluationCriteriaApi.list({ form_type: form.form_type })
      availableCriteria.value = data.data || []
    } catch (error) {
      console.error('Error fetching criteria:', error)
    } finally {
      loadingCriteria.value = false
    }
  }

  watch(() => form.form_type, fetchCriteria)
  watch(() => form.form_type, value => {
    if (value === 'satisfaction_client') {
      form.recipient_email = ''
      form.recipient_name = ''
    }
  })

  function openCreateDialog () {
    dialog.mode = 'create'
    dialog.id = null
    dialogStep.value = 1
    resetForm()
    dialog.show = true
    fetchCriteria()
  }

  function editRequest (request: EvaluationRequest) {
    dialog.mode = 'edit'
    dialog.id = request.id
    dialogStep.value = 1
    form.title = request.title
    form.form_type = request.form_type
    form.recipient_email = request.recipient_email || ''
    form.recipient_name = request.recipient_name || ''
    form.criteria_ids = request.criteria_ids
    form.custom_message = request.custom_message || ''
    form.instructions = request.instructions || ''
    form.expires_at = request.expires_at?.split('T')[0] || ''
    dialog.show = true
    fetchCriteria()
  }

  function resetForm () {
    form.title = ''
    form.form_type = 'satisfaction_client'
    form.recipient_email = ''
    form.recipient_name = ''
    form.criteria_ids = []
    form.custom_message = ''
    form.instructions = ''
    form.expires_at = ''
  }

  function closeDialog () {
    dialog.show = false
    dialogStep.value = 1
    resetForm()
  }

  async function saveRequest (sendAfterSave = false) {
    if (!formRef.value) return
    const { valid } = await formRef.value.validate()
    if (!valid) return

    saving.value = true
    try {
      let request: EvaluationRequest
      const isAnonymousSatisfaction = form.form_type === 'satisfaction_client'
      const recipientPayload = isAnonymousSatisfaction
        ? {}
        : {
          recipient_email: form.recipient_email,
          recipient_name: form.recipient_name || undefined,
        }
      if (dialog.mode === 'create') {
        const { data } = await evaluationRequestsApi.create({
          title: form.title,
          form_type: form.form_type,
          ...recipientPayload,
          criteria_ids: form.criteria_ids,
          custom_message: form.custom_message || undefined,
          instructions: form.instructions || undefined,
          expires_at: form.expires_at || undefined,
        })
        request = data
        showSnackbar('Demande créée avec succès', 'success')
      } else {
        const { data } = await evaluationRequestsApi.update(dialog.id!, {
          title: form.title,
          form_type: form.form_type,
          ...recipientPayload,
          criteria_ids: form.criteria_ids,
          custom_message: form.custom_message || undefined,
          instructions: form.instructions || undefined,
          expires_at: form.expires_at || undefined,
        })
        request = data
        showSnackbar('Demande mise à jour', 'success')
      }

      if (sendAfterSave && request.form_type !== 'satisfaction_client') {
        await evaluationRequestsApi.send(request.id)
        showSnackbar('Demande envoyée', 'success')
      }

      closeDialog()
      fetchRequests()
      fetchStatistics()
    } catch (error: any) {
      console.error('Error saving request:', error)
      showSnackbar(error.response?.data?.message || 'Erreur lors de l\'enregistrement', 'error')
    } finally {
      saving.value = false
    }
  }

  async function saveAndSend () {
    await saveRequest(true)
  }

  async function sendRequest (request: EvaluationRequest) {
    try {
      await evaluationRequestsApi.send(request.id)
      showSnackbar('Demande envoyée avec succès', 'success')
      fetchRequests()
      fetchStatistics()
    } catch (error: any) {
      showSnackbar(error.response?.data?.message || 'Erreur lors de l\'envoi', 'error')
    }
  }

  async function sendReminder (request: EvaluationRequest) {
    try {
      await evaluationRequestsApi.sendReminder(request.id)
      showSnackbar('Relance envoyée', 'success')
      fetchRequests()
    } catch (error: any) {
      showSnackbar(error.response?.data?.message || 'Erreur lors de la relance', 'error')
    }
  }

  async function cancelRequest (request: EvaluationRequest) {
    try {
      await evaluationRequestsApi.cancel(request.id)
      showSnackbar('Demande annulée', 'info')
      fetchRequests()
      fetchStatistics()
    } catch (error: any) {
      showSnackbar(error.response?.data?.message || 'Erreur', 'error')
    }
  }

  async function confirmDelete (request: EvaluationRequest) {
    if (!confirm(`Supprimer la demande "${request.title}" ?`)) return
    try {
      await evaluationRequestsApi.delete(request.id)
      showSnackbar('Demande supprimée', 'success')
      fetchRequests()
      fetchStatistics()
    } catch (error: any) {
      showSnackbar(error.response?.data?.message || 'Erreur', 'error')
    }
  }

  async function viewRequest (request: EvaluationRequest) {
    detailsDialog.request = request
    detailsDialog.responses = []
    detailsDialog.show = true

    try {
      const { data } = await evaluationRequestsApi.getResponses(request.id)
      detailsDialog.responses = data.data
    } catch (error) {
      console.error('Error loading responses:', error)
    }
  }

  function copyLink (request: EvaluationRequest) {
    navigator.clipboard.writeText(request.public_url)
    showSnackbar('Lien copié !', 'success')
  }

  function showSnackbar (text: string, color: string) {
    snackbar.text = text
    snackbar.color = color
    snackbar.show = true
  }

  function goToCriteria () {
    const type = filters.form_type || form.form_type || 'satisfaction_client'
    router.push(`/company/performance/criteria?form_type=${type}`)
  }

  onMounted(() => {
    fetchRequests()
    fetchStatistics()
  })
</script>

<style scoped>
  .stats-card {
    border: 1px solid rgba(15, 23, 42, 0.08);
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.96));
  }

  .filters-card,
  .data-card {
    border: 1px solid rgba(15, 23, 42, 0.08);
  }

  .modern-modal {
    border: 1px solid rgba(15, 23, 42, 0.1);
    backdrop-filter: blur(10px);
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.96));
  }

  .modal-title {
    border-bottom: 1px solid rgba(15, 23, 42, 0.08);
    padding: 0;
    background:
      radial-gradient(130% 180% at 0% -10%, rgba(14, 116, 144, 0.16), transparent 60%),
      radial-gradient(120% 160% at 100% 0%, rgba(59, 130, 246, 0.14), transparent 55%),
      linear-gradient(135deg, rgba(255, 255, 255, 0.96), rgba(248, 250, 252, 0.96));
  }

  .modal-title-inner {
    width: 100%;
    padding: 20px 24px;
  }

  .request-modal {
    border: 1px solid rgba(15, 23, 42, 0.1);
  }

  .request-stepper {
    background: transparent;
    border-top: 1px solid rgba(15, 23, 42, 0.08);
    padding: 12px 20px 16px;
  }

  .criterion-row {
    padding: 8px 10px;
    border: 1px solid rgba(148, 163, 184, 0.22);
    border-radius: 8px;
    margin-bottom: 8px;
    background: rgba(248, 250, 252, 0.65);
  }

  .criterion-name {
    font-weight: 600;
    color: #0f172a;
  }

  .criterion-score {
    font-size: 0.82rem;
    color: #475569;
    margin-top: 2px;
  }

  .criterion-comment {
    font-size: 0.82rem;
    color: #334155;
    margin-top: 4px;
  }
</style>
