<template>
  <ClientALayout current-page="reclamations">
    <v-container class="pa-6" fluid>
      <PageHeader
        icon="mdi-file-alert"
        subtitle="Gestion et suivi des réclamations clients"
        title="Réclamations Clients"
      >
        <template #actions>
          <v-btn
            v-if="canCreateReclamation"
            color="primary"
            prepend-icon="mdi-plus"
            @click="openDialog()"
          >
            Nouvelle Réclamation
          </v-btn>
        </template>
      </PageHeader>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <AppWidget
          clickable
          :icon="FileText"
          title="Total"
          :value="reclamations.length"
          variant="primary"
          @click="statusFilter = ''"
        />
        <AppWidget
          clickable
          :icon="AlertCircle"
          title="Ouvertes"
          :value="openCount"
          variant="warning"
          @click="statusFilter = 'ouverte'"
        />
        <AppWidget
          clickable
          :icon="Clock"
          title="En cours"
          :value="inProgressCount"
          variant="info"
          @click="statusFilter = 'en_cours'"
        />
        <AppWidget
          clickable
          :icon="CheckCircle"
          title="Résolues"
          :value="closedCount"
          variant="success"
          @click="statusFilter = 'resolue'"
        />
      </div>

      <FilterCard>
        <v-row>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="search"
              clearable
              density="comfortable"
              hide-details
              placeholder="Rechercher..."
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-select
              v-model="statusFilter"
              clearable
              density="comfortable"
              hide-details
              :items="statusOptions"
              placeholder="Filtrer par statut"
              prepend-inner-icon="mdi-filter"
              variant="outlined"
            />
          </v-col>
        </v-row>
      </FilterCard>

      <v-row>
        <v-col cols="12">
          <v-card>
            <v-data-table
              class="elevation-1"
              :headers="headers"
              :items="filteredReclamations"
              :search="search"
            >
              <template #[`item.reference`]="{ item }">
                <span class="font-weight-bold text-primary">{{ item.reference }}</span>
              </template>
              <template #[`item.status`]="{ item }">
                <v-chip :color="getStatusColor(item.status)" size="small" variant="tonal">
                  {{ getStatusLabel(item.status) }}
                </v-chip>
              </template>
              <template #[`item.priority`]="{ item }">
                <v-chip :color="getPriorityColor(item.priority)" size="small">
                  {{ item.priority }}
                </v-chip>
              </template>
              <template #[`item.created_at`]="{ item }">
                {{ formatDate(item.created_at) }}
              </template>
              <template #[`item.actions`]="{ item }">
                <v-btn
                  v-if="canReadReclamation"
                  icon="mdi-eye"
                  size="small"
                  variant="text"
                  @click="viewReclamation(item)"
                />
                <v-btn
                  v-if="canUpdateReclamation"
                  icon="mdi-pencil"
                  size="small"
                  variant="text"
                  @click="openDialog(item)"
                />
                <v-btn
                  v-if="canDeleteReclamation"
                  color="error"
                  icon="mdi-delete"
                  size="small"
                  variant="text"
                  @click="confirmDelete(item)"
                />
              </template>
            </v-data-table>
          </v-card>
        </v-col>
      </v-row>

      <!-- Create/Edit Dialog -->
      <v-dialog v-model="dialog" max-width="900" persistent scrollable>
        <v-card>
          <v-card-title class="d-flex align-center">
            <span class="text-h5">{{ editMode ? 'Modifier' : 'Nouvelle' }} Réclamation</span>
            <v-spacer />
            <v-btn icon="mdi-close" variant="text" @click="dialog = false" />
          </v-card-title>
          <v-divider />
          <v-card-text class="pa-6">
            <v-form ref="formRef">
              <v-row>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="formData.client_name"
                    label="Nom du client *"
                    :rules="[rules.required]"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="formData.client_email"
                    label="Email du client"
                    :rules="[rules.email]"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="formData.client_phone"
                    label="Téléphone"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="formData.priority"
                    :items="priorityOptions"
                    label="Priorité *"
                    :rules="[rules.required]"
                  />
                </v-col>
                <v-col cols="12">
                  <v-text-field
                    v-model="formData.subject"
                    label="Objet de la réclamation *"
                    :rules="[rules.required]"
                  />
                </v-col>
                <v-col cols="12">
                  <v-textarea
                    v-model="formData.description"
                    label="Description détaillée *"
                    rows="4"
                    :rules="[rules.required]"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="formData.status"
                    :items="statusOptions"
                    label="Statut *"
                    :rules="[rules.required]"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <AppDatePickerField v-model="formData.response_deadline" label="Délai de réponse" mode="date" />
                </v-col>
              </v-row>
            </v-form>
          </v-card-text>
          <v-divider />
          <v-card-actions class="pa-4">
            <v-spacer />
            <v-btn variant="text" @click="dialog = false">Annuler</v-btn>
            <v-btn
              v-if="canCreateReclamation || canUpdateReclamation"
              color="primary"
              :loading="saving"
              @click="saveReclamation"
            >
              {{ editMode ? 'Mettre à jour' : 'Créer' }}
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <!-- Delete Confirmation -->
      <v-dialog v-if="canDeleteReclamation" v-model="deleteDialog" max-width="400">
        <v-card>
          <v-card-title>Confirmer la suppression</v-card-title>
          <v-card-text>
            Êtes-vous sûr de vouloir supprimer cette réclamation ?
          </v-card-text>
          <v-card-actions>
            <v-spacer />
            <v-btn variant="text" @click="deleteDialog = false">Annuler</v-btn>
            <v-btn color="error" :loading="deleting" @click="deleteReclamation">
              Supprimer
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { AlertCircle, CheckCircle, Clock, FileText } from 'lucide-vue-next'
  import { computed, onMounted, ref } from 'vue'
  import { useToast } from 'vue-toastification'
  import apiClient from '@/api/client'
  import AppWidget from '@/components/common/AppWidget.vue'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import FilterCard from '@/modules/clienta/components/FilterCard.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import { getNavigationPermissionSet } from '@/modules/clienta/utils/userPermissions'
  import { useAuthStore } from '@/stores/auth'
  import { isEnterpriseAdminUser } from '@/utils/accessControl'
  import { expandPermissionAliases } from '@/utils/permissions'

  const toast = useToast()
  const authStore = useAuthStore()

  const reclamations = ref<any[]>([])
  const loading = ref(false)
  const saving = ref(false)
  const deleting = ref(false)
  const dialog = ref(false)
  const deleteDialog = ref(false)
  const editMode = ref(false)
  const search = ref('')
  const statusFilter = ref('')
  const selectedItem = ref<any>(null)

  const formData = ref({
    client_name: '',
    client_email: '',
    client_phone: '',
    subject: '',
    description: '',
    priority: 'moyenne',
    status: 'ouverte',
    response_deadline: '',
  })

  const headers = [
    { title: 'Référence', key: 'reference', sortable: true },
    { title: 'Client', key: 'client_name', sortable: true },
    { title: 'Objet', key: 'subject', sortable: true },
    { title: 'Priorité', key: 'priority', sortable: true },
    { title: 'Statut', key: 'status', sortable: true },
    { title: 'Date', key: 'created_at', sortable: true },
    { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
  ] as const

  const statusOptions = [
    { title: 'Ouverte', value: 'ouverte' },
    { title: 'En cours', value: 'en_cours' },
    { title: 'Résolue', value: 'resolue' },
    { title: 'Fermée', value: 'fermee' },
  ]

  const priorityOptions = [
    { title: 'Faible', value: 'faible' },
    { title: 'Moyenne', value: 'moyenne' },
    { title: 'Haute', value: 'haute' },
    { title: 'Urgente', value: 'urgente' },
  ]

  const rules = {
    required: (v: any) => !!v || 'Ce champ est requis',
    email: (v: string) => !v || /.+@.+\..+/.test(v) || 'Email invalide',
  }

  const filteredReclamations = computed(() => {
    let filtered = reclamations.value
    if (statusFilter.value) {
      filtered = filtered.filter(r => r.status === statusFilter.value)
    }
    return filtered
  })

  const openCount = computed(() => reclamations.value.filter(r => r.status === 'ouverte').length)
  const inProgressCount = computed(() => reclamations.value.filter(r => r.status === 'en_cours').length)
  const closedCount = computed(() => reclamations.value.filter(r => r.status === 'resolue' || r.status === 'fermee').length)

  function canAccess (requiredPermissions: string[]): boolean {
    const currentUser = authStore.user as any
    if (currentUser?.user_type === 'super_admin' || isEnterpriseAdminUser(currentUser)) {
      return true
    }
    const permissions = getNavigationPermissionSet(currentUser)
    return requiredPermissions.some(permission =>
      expandPermissionAliases(permission).some(alias => permissions.has(alias)),
    )
  }

  const canReadReclamation = computed(() => canAccess(['realisation.sorties_non_conformes.manage_complaints']))
  const canCreateReclamation = computed(() => canAccess(['reclamations.create']))
  const canUpdateReclamation = computed(() => canAccess(['reclamations.update']))
  const canDeleteReclamation = computed(() => canAccess(['reclamations.delete']))

  onMounted(() => {
    loadReclamations()
  })

  async function loadReclamations () {
    loading.value = true
    try {
      const response = await apiClient.get('/reclamations')
      reclamations.value = response.data.data || []
    } catch {
      toast.error('Erreur lors du chargement des réclamations')
    } finally {
      loading.value = false
    }
  }

  function openDialog (item: any = null) {
    if (item && !canUpdateReclamation.value) {
      return
    }
    if (!item && !canCreateReclamation.value) {
      return
    }
    editMode.value = !!item
    if (item) {
      selectedItem.value = item
      formData.value = { ...item }
    } else {
      formData.value = {
        client_name: '',
        client_email: '',
        client_phone: '',
        subject: '',
        description: '',
        priority: 'moyenne',
        status: 'ouverte',
        response_deadline: '',
      }
    }
    dialog.value = true
  }

  async function saveReclamation () {
    if (editMode.value && !canUpdateReclamation.value) {
      return
    }
    if (!editMode.value && !canCreateReclamation.value) {
      return
    }
    saving.value = true
    try {
      if (editMode.value) {
        await apiClient.put(`/reclamations/${selectedItem.value.id}`, formData.value)
        toast.success('Réclamation mise à jour')
      } else {
        await apiClient.post('/reclamations', formData.value)
        toast.success('Réclamation créée')
      }
      dialog.value = false
      await loadReclamations()
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Erreur lors de l\'enregistrement')
    } finally {
      saving.value = false
    }
  }

  function confirmDelete (item: any) {
    if (!canDeleteReclamation.value) {
      return
    }
    selectedItem.value = item
    deleteDialog.value = true
  }

  async function deleteReclamation () {
    if (!canDeleteReclamation.value) {
      return
    }
    deleting.value = true
    try {
      await apiClient.delete(`/reclamations/${selectedItem.value.id}`)
      toast.success('Réclamation supprimée')
      deleteDialog.value = false
      await loadReclamations()
    } catch {
      toast.error('Erreur lors de la suppression')
    } finally {
      deleting.value = false
    }
  }

  function viewReclamation (_item: any) {
    // TODO: Navigate to detail page
    toast.info('Vue détaillée à venir')
  }

  function getPriorityColor (priority: string) {
    switch (priority) {
      case 'faible': { return 'grey'
      }
      case 'moyenne': { return 'blue'
      }
      case 'haute': { return 'orange'
      }
      case 'urgente': { return 'red'
      }
      default: { return 'grey'
      }
    }
  }

  function getStatusColor (status: string): string {
    switch (status) {
      case 'ouverte': { return 'warning'
      }
      case 'en_cours': { return 'info'
      }
      case 'resolue': { return 'success'
      }
      case 'fermee': { return 'grey'
      }
      default: { return 'grey'
      }
    }
  }

  function getStatusLabel (status: string): string {
    const option = statusOptions.find(s => s.value === status)
    return option?.title || status
  }

  function formatDate (dateString: string) {
    if (!dateString) return 'N/A'
    const date = new Date(dateString)
    return date.toLocaleDateString('fr-FR')
  }
</script>
