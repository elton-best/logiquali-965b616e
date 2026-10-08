<template>
  <ClientALayout current-page="reports">
    <v-container class="pa-6" fluid>
      <!-- Header -->
      <div class="d-flex justify-space-between align-center mb-6">
        <div>
          <h1 class="text-h4 font-weight-bold mb-2">Rapports QHSE</h1>
          <p class="text-body-2 text-medium-emphasis">Générez et consultez vos rapports</p>
        </div>
        <v-btn
          v-if="canCreateReports"
          color="primary"
          prepend-icon="mdi-plus"
          size="large"
          @click="openGenerateDialog"
        >
          Générer un rapport
        </v-btn>
      </div>

      <!-- Stats Cards -->
      <v-row class="mb-6">
        <v-col cols="12" md="3" sm="6">
          <v-card>
            <v-card-text>
              <div class="d-flex align-center">
                <v-avatar class="mr-3" color="primary" size="48">
                  <v-icon color="white">mdi-file-document-multiple</v-icon>
                </v-avatar>
                <div>
                  <div class="text-caption text-medium-emphasis">Total</div>
                  <div class="text-h5 font-weight-bold">{{ stats.total }}</div>
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="3" sm="6">
          <v-card>
            <v-card-text>
              <div class="d-flex align-center">
                <v-avatar class="mr-3" color="info" size="48">
                  <v-icon color="white">mdi-calendar-month</v-icon>
                </v-avatar>
                <div>
                  <div class="text-caption text-medium-emphasis">Ce mois</div>
                  <div class="text-h5 font-weight-bold">{{ stats.this_month }}</div>
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="3" sm="6">
          <v-card>
            <v-card-text>
              <div class="d-flex align-center">
                <v-avatar class="mr-3" color="success" size="48">
                  <v-icon color="white">mdi-download</v-icon>
                </v-avatar>
                <div>
                  <div class="text-caption text-medium-emphasis">Téléchargés</div>
                  <div class="text-h5 font-weight-bold">{{ stats.downloaded }}</div>
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="3" sm="6">
          <v-card>
            <v-card-text>
              <div class="d-flex align-center">
                <v-avatar class="mr-3" color="warning" size="48">
                  <v-icon color="white">mdi-clock-outline</v-icon>
                </v-avatar>
                <div>
                  <div class="text-caption text-medium-emphasis">En attente</div>
                  <div class="text-h5 font-weight-bold">{{ stats.pending }}</div>
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Quick Report Templates -->
      <v-row v-if="canCreateReports" class="mb-6">
        <v-col cols="12">
          <h3 class="text-h6 mb-4">📊 Modèles rapides</h3>
        </v-col>
        <v-col
          v-for="template in reportTemplates"
          :key="template.id"
          cols="12"
          md="4"
          sm="6"
        >
          <v-card hover @click="generateFromTemplate(template)">
            <v-card-text>
              <div class="d-flex align-center">
                <v-avatar class="mr-4" :color="template.color" size="56">
                  <v-icon color="white" size="32">{{ template.icon }}</v-icon>
                </v-avatar>
                <div class="flex-grow-1">
                  <h4 class="text-subtitle-1 font-weight-bold">{{ template.name }}</h4>
                  <p class="text-caption text-grey mb-0">{{ template.description }}</p>
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Filters -->
      <v-card class="mb-6">
        <v-card-text>
          <v-row>
            <v-col cols="12" md="3">
              <v-text-field
                v-model="filters.search"
                clearable
                density="compact"
                hide-details
                placeholder="Rechercher..."
                prepend-inner-icon="mdi-magnify"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12" md="3">
              <v-select
                v-model="filters.type"
                clearable
                density="compact"
                hide-details
                :items="typeFilters"
                label="Type"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12" md="3">
              <v-select
                v-model="filters.status"
                clearable
                density="compact"
                hide-details
                :items="statusFilters"
                label="Statut"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12" md="3">
              <v-select
                v-model="filters.period"
                clearable
                density="compact"
                hide-details
                :items="periodFilters"
                label="Période"
                variant="outlined"
              />
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>

      <!-- Table -->
      <v-card>
        <v-data-table
          class="elevation-1"
          :headers="headers as any"
          :items="filteredItems"
          :items-per-page="15"
          :loading="loading"
        >
          <template #item.type="{ item }">
            <v-chip :color="getTypeColor(item.type)" label size="small">
              {{ item.type }}
            </v-chip>
          </template>

          <template #item.status="{ item }">
            <v-chip :color="getStatusColor(item.status)" size="small" variant="tonal">
              {{ getStatusLabel(item.status) }}
            </v-chip>
          </template>

          <template #item.created_at="{ item }">
            {{ formatDate(item.created_at) }}
          </template>

          <template #item.actions="{ item }">
            <v-btn
              v-if="canReadReports"
              icon="mdi-eye"
              size="small"
              variant="text"
              @click="viewReport(item)"
            />
            <v-btn
              v-if="canReadReports"
              icon="mdi-download"
              size="small"
              variant="text"
              @click="downloadReport(item)"
            />
            <v-btn
              v-if="canReadReports"
              icon="mdi-share-variant"
              size="small"
              variant="text"
              @click="shareReport(item)"
            />
            <v-btn
              v-if="canDeleteReports"
              color="error"
              icon="mdi-delete"
              size="small"
              variant="text"
              @click="deleteReport(item)"
            />
          </template>
        </v-data-table>
      </v-card>

      <!-- Generate Dialog -->
      <v-dialog v-if="canCreateReports" v-model="generateDialog" max-width="700" persistent>
        <v-card>
          <v-card-title class="bg-primary text-white pa-4">
            <v-icon start>mdi-file-document-plus</v-icon>
            Générer un rapport
          </v-card-title>

          <v-card-text class="pa-6">
            <v-form ref="formRef" v-model="valid">
              <v-row>
                <v-col cols="12">
                  <v-select
                    v-model="reportForm.type"
                    density="comfortable"
                    :items="reportTypes"
                    label="Type de rapport *"
                    :rules="[rules.required]"
                    variant="outlined"
                  />
                </v-col>

                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="reportForm.start_date"
                    density="comfortable"
                    label="Date de début *"
                    :rules="[rules.required]"
                    type="date"
                    variant="outlined"
                  />
                </v-col>

                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="reportForm.end_date"
                    density="comfortable"
                    label="Date de fin *"
                    :rules="[rules.required]"
                    type="date"
                    variant="outlined"
                  />
                </v-col>

                <v-col cols="12">
                  <v-select
                    v-model="reportForm.site_ids"
                    chips
                    closable-chips
                    density="comfortable"
                    item-title="name"
                    item-value="id"
                    :items="sites"
                    label="Sites"
                    multiple
                    variant="outlined"
                  />
                </v-col>

                <v-col cols="12">
                  <v-select
                    v-model="reportForm.format"
                    density="comfortable"
                    :items="formatOptions"
                    label="Format *"
                    :rules="[rules.required]"
                    variant="outlined"
                  />
                </v-col>

                <v-col cols="12">
                  <v-checkbox
                    v-model="reportForm.include_charts"
                    color="primary"
                    hide-details
                    label="Inclure les graphiques"
                  />
                </v-col>

                <v-col cols="12">
                  <v-checkbox
                    v-model="reportForm.send_email"
                    color="primary"
                    hide-details
                    label="Envoyer par email une fois généré"
                  />
                </v-col>
              </v-row>
            </v-form>
          </v-card-text>

          <v-card-actions class="pa-4">
            <v-spacer />
            <v-btn variant="text" @click="closeGenerateDialog">Annuler</v-btn>
            <v-btn
              color="primary"
              :disabled="!valid"
              :loading="generating"
              @click="generateReport"
            >
              <v-icon start>mdi-cog</v-icon>
              Générer
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { format } from 'date-fns'
  import { fr } from 'date-fns/locale'
  import { computed, onMounted, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import { useToast } from 'vue-toastification'
  import api from '@/api/client'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import { getNavigationPermissionSet } from '@/modules/clienta/utils/userPermissions'
  import referenceDataService from '@/services/referenceDataService'
  import { useAuthStore } from '@/stores/auth'
  import { isEnterpriseAdminUser } from '@/utils/accessControl'
  import { expandPermissionAliases } from '@/utils/permissions'

  const router = useRouter()
  const toast = useToast()
  const authStore = useAuthStore()

  const loading = ref(false)
  const generating = ref(false)
  const generateDialog = ref(false)
  const formRef = ref()
  const valid = ref(false)
  const items = ref<any[]>([])
  const sites = ref<any[]>([])

  const filters = ref({
    search: '',
    type: null,
    status: null,
    period: null,
  })

  const reportForm = ref({
    type: '',
    start_date: '',
    end_date: '',
    site_ids: [],
    format: 'pdf',
    include_charts: true,
    send_email: false,
  })

  const reportTemplates = [
    {
      id: 'nc_monthly',
      name: 'Non-conformités mensuelles',
      description: 'Rapport mensuel des NC',
      icon: 'mdi-alert-circle',
      color: 'error',
      type: 'non_conformities',
    },
    {
      id: 'audit_quarterly',
      name: 'Audits trimestriels',
      description: 'Synthèse des audits du trimestre',
      icon: 'mdi-clipboard-check',
      color: 'info',
      type: 'audits',
    },
    {
      id: 'dashboard_monthly',
      name: 'Dashboard mensuel',
      description: 'Tableau de bord complet',
      icon: 'mdi-view-dashboard',
      color: 'primary',
      type: 'dashboard',
    },
    {
      id: 'kpi_monthly',
      name: 'Indicateurs mensuels',
      description: 'Suivi des KPIs QHSE',
      icon: 'mdi-chart-line',
      color: 'success',
      type: 'kpi',
    },
    {
      id: 'risks_annual',
      name: 'Cartographie des risques',
      description: 'Analyse annuelle des risques',
      icon: 'mdi-alert-octagon',
      color: 'warning',
      type: 'risks',
    },
    {
      id: 'compliance',
      name: 'Conformité réglementaire',
      description: 'État de conformité',
      icon: 'mdi-shield-check',
      color: 'purple',
      type: 'compliance',
    },
  ]

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

  const canReadReports = computed(() => canAccess(['dashboard.read']))
  const canCreateReports = computed(() => canAccess(['dashboard.read']))
  const canDeleteReports = computed(() => canAccess(['dashboard.read']))

  const reportTypes = [
    'Non-conformités',
    'Audits',
    'Dashboard',
    'Indicateurs',
    'Risques',
    'Conformité',
    'Actions correctives',
    'Documents',
  ]

  const formatOptions = [
    { title: 'PDF', value: 'pdf' },
    { title: 'Excel', value: 'xlsx' },
    { title: 'Word', value: 'docx' },
  ]

  const stats = computed(() => ({
    total: items.value.length,
    this_month: items.value.filter(i => isThisMonth(i.created_at)).length,
    downloaded: items.value.filter(i => i.download_count > 0).length,
    pending: items.value.filter(i => i.status === 'pending').length,
  }))

  const headers = [
    { title: 'Nom', key: 'name', sortable: true },
    { title: 'Type', key: 'type', sortable: true },
    { title: 'Période', key: 'period', sortable: true },
    { title: 'Statut', key: 'status', sortable: true },
    { title: 'Créé le', key: 'created_at', sortable: true },
    { title: 'Actions', key: 'actions', sortable: false, align: 'end' },
  ]

  const typeFilters = reportTypes
  const statusFilters = [
    { title: 'Généré', value: 'generated' },
    { title: 'En attente', value: 'pending' },
    { title: 'Erreur', value: 'error' },
  ]

  const periodFilters = [
    { title: 'Aujourd\'hui', value: 'today' },
    { title: 'Cette semaine', value: 'week' },
    { title: 'Ce mois', value: 'month' },
    { title: 'Ce trimestre', value: 'quarter' },
    { title: 'Cette année', value: 'year' },
  ]

  const filteredItems = computed(() => {
    let result = items.value

    if (filters.value.search) {
      const search = filters.value.search.toLowerCase()
      result = result.filter(item =>
        item.name?.toLowerCase().includes(search)
        || item.type?.toLowerCase().includes(search),
      )
    }

    if (filters.value.type) {
      result = result.filter(item => item.type === filters.value.type)
    }

    if (filters.value.status) {
      result = result.filter(item => item.status === filters.value.status)
    }

    return result
  })

  const rules = {
    required: (v: any) => !!v || 'Ce champ est requis',
  }

  function getTypeColor (type: string): string {
    const colors: Record<string, string> = {
      'Non-conformités': 'error',
      'Audits': 'info',
      'Dashboard': 'primary',
      'Indicateurs': 'success',
      'Risques': 'warning',
    }
    return colors[type] || 'grey'
  }

  function getStatusColor (status: string): string {
    const colors: Record<string, string> = {
      generated: 'success',
      pending: 'warning',
      error: 'error',
    }
    return colors[status] || 'grey'
  }

  function getStatusLabel (status: string): string {
    const labels: Record<string, string> = {
      generated: 'Généré',
      pending: 'En attente',
      error: 'Erreur',
    }
    return labels[status] || status
  }

  function formatDate (date: string): string {
    if (!date) return '-'
    try {
      return format(new Date(date), 'dd MMM yyyy', { locale: fr })
    } catch {
      return date
    }
  }

  function isThisMonth (date: string): boolean {
    const now = new Date()
    const d = new Date(date)
    return d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear()
  }

  async function loadData () {
    loading.value = true
    try {
      const response = await api.get('/reports', {
        headers: { 'X-Skip-Error-Toast': 'true' },
      })
      items.value = response.data.data || []
    } catch (error: any) {
      // Silently handle 404 - endpoint not implemented yet
      if (error.response?.status !== 404) {
        console.error('Erreur chargement rapports:', error)
      }
      items.value = []
    } finally {
      loading.value = false
    }
  }

  async function loadReferenceData () {
    try {
      const data = await referenceDataService.getAllReferenceData()
      sites.value = data.sites
    } catch (error) {
      console.error('Erreur chargement données:', error)
    }
  }

  function openGenerateDialog () {
    if (!canCreateReports.value) {
      return
    }
    generateDialog.value = true
  }

  function closeGenerateDialog () {
    generateDialog.value = false
    formRef.value?.reset()
  }

  function generateFromTemplate (template: any) {
    if (!canCreateReports.value) {
      return
    }
    const now = new Date()
    const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1)
    const endOfMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0)

    reportForm.value = {
      type: template.type,
      start_date: format(startOfMonth, 'yyyy-MM-dd'),
      end_date: format(endOfMonth, 'yyyy-MM-dd'),
      site_ids: [],
      format: 'pdf',
      include_charts: true,
      send_email: false,
    }

    generateDialog.value = true
  }

  async function generateReport () {
    if (!canCreateReports.value) {
      return
    }
    if (!valid.value) return

    generating.value = true
    try {
      await api.post('/reports/generate', reportForm.value)
      toast.success('Rapport en cours de génération')
      await loadData()
      closeGenerateDialog()
    } catch (error: any) {
      console.error('Erreur génération:', error)
      toast.error(error.response?.data?.message || 'Erreur lors de la génération')
    } finally {
      generating.value = false
    }
  }

  function viewReport (item: any) {
    if (!canReadReports.value) {
      return
    }
    router.push(`/company/reports/${item.id}`)
  }

  async function downloadReport (item: any) {
    if (!canReadReports.value) {
      return
    }
    try {
      const response = await api.get(`/reports/${item.id}/download`, {
        responseType: 'blob',
      })
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `${item.name}.${item.format}`)
      document.body.append(link)
      link.click()
      link.remove()
      toast.success('Rapport téléchargé')
    } catch {
      toast.error('Erreur lors du téléchargement')
    }
  }

  function shareReport (_item: any) {
    if (!canReadReports.value) {
      return
    }
    toast.info('Fonctionnalité de partage à venir')
  }

  async function deleteReport (item: any) {
    if (!canDeleteReports.value) {
      return
    }
    if (confirm(`Voulez-vous vraiment supprimer le rapport "${item.name}" ?`)) {
      try {
        await api.delete(`/reports/${item.id}`)
        toast.success('Rapport supprimé')
        await loadData()
      } catch {
        toast.error('Erreur lors de la suppression')
      }
    }
  }

  onMounted(() => {
    loadData()
    loadReferenceData()
  })
</script>
