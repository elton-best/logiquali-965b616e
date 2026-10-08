<template>
  <ClientALayout current-page="reports">
    <v-container class="pa-6" fluid>
      <!-- Header with Back Button -->
      <div class="mb-6">
        <v-btn
          class="mb-4"
          prepend-icon="mdi-arrow-left"
          variant="text"
          @click="$router.push('/company/reports')"
        >
          Retour aux rapports
        </v-btn>

        <div class="d-flex justify-space-between align-center">
          <div>
            <h1 class="text-h4 font-weight-bold mb-2">{{ report?.name || 'Chargement...' }}</h1>
            <p class="text-body-2 text-medium-emphasis">
              Généré le {{ formatDate(report?.created_at) }}
            </p>
          </div>

          <div class="d-flex gap-2">
            <v-btn
              v-if="canReadReports"
              color="primary"
              :loading="downloading"
              prepend-icon="mdi-download"
              @click="downloadReport"
            >
              Télécharger
            </v-btn>
            <v-btn
              v-if="canReadReports"
              color="info"
              prepend-icon="mdi-share-variant"
              @click="shareReport"
            >
              Partager
            </v-btn>
            <v-btn
              v-if="canDeleteReports"
              color="error"
              prepend-icon="mdi-delete"
              @click="deleteReport"
            >
              Supprimer
            </v-btn>
          </div>
        </div>
      </div>

      <v-row v-if="loading">
        <v-col cols="12">
          <UnifiedLoader
            class="mx-auto"
            description="Récupération du contenu et des statistiques du rapport..."
            title="Chargement du rapport..."
            variant="local"
          />
        </v-col>
      </v-row>

      <v-row v-else-if="report">
        <!-- Report Information Card -->
        <v-col cols="12" md="8">
          <v-card class="mb-6">
            <v-card-title class="bg-primary text-white">
              <v-icon start>mdi-information</v-icon>
              Informations du rapport
            </v-card-title>
            <v-card-text class="pa-6">
              <v-row>
                <v-col cols="12" sm="6">
                  <div class="mb-4">
                    <div class="text-caption text-medium-emphasis mb-1">Type de rapport</div>
                    <v-chip :color="getTypeColor(report.type)" label size="small">
                      {{ report.type }}
                    </v-chip>
                  </div>
                </v-col>

                <v-col cols="12" sm="6">
                  <div class="mb-4">
                    <div class="text-caption text-medium-emphasis mb-1">Statut</div>
                    <v-chip :color="getStatusColor(report.status)" size="small" variant="tonal">
                      {{ getStatusLabel(report.status) }}
                    </v-chip>
                  </div>
                </v-col>

                <v-col cols="12" sm="6">
                  <div class="mb-4">
                    <div class="text-caption text-medium-emphasis mb-1">Format</div>
                    <div class="text-body-1 font-weight-medium">{{ report.format?.toUpperCase() }}</div>
                  </div>
                </v-col>

                <v-col cols="12" sm="6">
                  <div class="mb-4">
                    <div class="text-caption text-medium-emphasis mb-1">Généré par</div>
                    <div class="text-body-1 font-weight-medium">
                      {{ report.user?.name || report.user?.username || 'N/A' }}
                    </div>
                  </div>
                </v-col>

                <v-col cols="12" sm="6">
                  <div class="mb-4">
                    <div class="text-caption text-medium-emphasis mb-1">Date de création</div>
                    <div class="text-body-1 font-weight-medium">{{ formatDateTime(report.created_at) }}</div>
                  </div>
                </v-col>

                <v-col cols="12" sm="6">
                  <div class="mb-4">
                    <div class="text-caption text-medium-emphasis mb-1">Dernière modification</div>
                    <div class="text-body-1 font-weight-medium">{{ formatDateTime(report.updated_at) }}</div>
                  </div>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <!-- Report Parameters Card -->
          <v-card v-if="report.parameters">
            <v-card-title class="bg-secondary text-white">
              <v-icon start>mdi-cog</v-icon>
              Paramètres de génération
            </v-card-title>
            <v-card-text class="pa-6">
              <v-list>
                <v-list-item
                  v-for="(value, key) in report.parameters"
                  :key="key"
                  class="px-0"
                >
                  <template #prepend>
                    <v-icon color="primary">mdi-chevron-right</v-icon>
                  </template>
                  <v-list-item-title class="text-capitalize">
                    {{ formatParameterKey(String(key)) }}
                  </v-list-item-title>
                  <v-list-item-subtitle>
                    {{ formatParameterValue(value) }}
                  </v-list-item-subtitle>
                </v-list-item>
              </v-list>
            </v-card-text>
          </v-card>
        </v-col>

        <!-- Actions & Quick Info Sidebar -->
        <v-col cols="12" md="4">
          <v-card class="mb-4">
            <v-card-title class="bg-info text-white">
              <v-icon start>mdi-flash</v-icon>
              Actions rapides
            </v-card-title>
            <v-card-text class="pa-4">
              <v-list>
                <v-list-item v-if="canReadReports" @click="downloadReport">
                  <template #prepend>
                    <v-icon color="primary">mdi-download</v-icon>
                  </template>
                  <v-list-item-title>Télécharger le rapport</v-list-item-title>
                </v-list-item>

                <v-list-item v-if="canReadReports" @click="shareReport">
                  <template #prepend>
                    <v-icon color="info">mdi-share-variant</v-icon>
                  </template>
                  <v-list-item-title>Partager le rapport</v-list-item-title>
                </v-list-item>

                <v-list-item v-if="canUpdateReports" @click="regenerateReport">
                  <template #prepend>
                    <v-icon color="success">mdi-refresh</v-icon>
                  </template>
                  <v-list-item-title>Régénérer</v-list-item-title>
                </v-list-item>

                <v-divider class="my-2" />

                <v-list-item v-if="canDeleteReports" @click="deleteReport">
                  <template #prepend>
                    <v-icon color="error">mdi-delete</v-icon>
                  </template>
                  <v-list-item-title class="text-error">Supprimer le rapport</v-list-item-title>
                </v-list-item>
              </v-list>
            </v-card-text>
          </v-card>

          <!-- Stats Card -->
          <v-card>
            <v-card-title class="bg-success text-white">
              <v-icon start>mdi-chart-box</v-icon>
              Statistiques
            </v-card-title>
            <v-card-text class="pa-4">
              <div class="mb-4">
                <div class="d-flex justify-space-between align-center mb-2">
                  <span class="text-caption text-medium-emphasis">Téléchargements</span>
                  <span class="text-h6 font-weight-bold">{{ report.downloads_count || 0 }}</span>
                </div>
                <v-progress-linear
                  color="success"
                  height="8"
                  :model-value="Math.min(report.downloads_count || 0, 100)"
                  rounded
                />
              </div>

              <div class="mb-4">
                <div class="d-flex justify-space-between align-center mb-2">
                  <span class="text-caption text-medium-emphasis">Partages</span>
                  <span class="text-h6 font-weight-bold">{{ report.shares_count || 0 }}</span>
                </div>
                <v-progress-linear
                  color="info"
                  height="8"
                  :model-value="Math.min(report.shares_count || 0, 100)"
                  rounded
                />
              </div>

              <v-divider class="my-4" />

              <div class="text-center">
                <div class="text-caption text-medium-emphasis mb-1">Taille du fichier</div>
                <div class="text-h6 font-weight-bold">
                  {{ report.file_size ? formatFileSize(report.file_size) : 'N/A' }}
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <v-row v-else>
        <v-col cols="12">
          <v-alert prominent type="error">
            <v-alert-title>Rapport introuvable</v-alert-title>
            Ce rapport n'existe pas ou a été supprimé.
          </v-alert>
        </v-col>
      </v-row>

      <!-- Delete Confirmation Dialog -->
      <v-dialog v-if="canDeleteReports" v-model="deleteDialog" max-width="500">
        <v-card>
          <v-card-title class="bg-error text-white">
            <v-icon start>mdi-alert</v-icon>
            Confirmer la suppression
          </v-card-title>
          <v-card-text class="pa-6">
            <p class="mb-0">
              Êtes-vous sûr de vouloir supprimer le rapport <strong>{{ report?.name }}</strong> ?
              Cette action est irréversible.
            </p>
          </v-card-text>
          <v-card-actions class="pa-4">
            <v-spacer />
            <v-btn variant="text" @click="deleteDialog = false">Annuler</v-btn>
            <v-btn color="error" :loading="deleting" variant="flat" @click="confirmDelete">
              Supprimer
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { useToast } from 'vue-toastification'
  import api from '@/api/client'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import { getNavigationPermissionSet } from '@/modules/clienta/utils/userPermissions'
  import UnifiedLoader from '@/modules/shared/components/ui/UnifiedLoader.vue'
  import { useAuthStore } from '@/stores/auth'
  import { isEnterpriseAdminUser } from '@/utils/accessControl'
  import { expandPermissionAliases } from '@/utils/permissions'

  const route = useRoute()
  const router = useRouter()
  const toast = useToast()
  const authStore = useAuthStore()

  const report = ref<any>(null)
  const loading = ref(true)
  const downloading = ref(false)
  const regenerating = ref(false)
  const deleting = ref(false)
  const deleteDialog = ref(false)
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
  const canUpdateReports = computed(() => canAccess(['reports.update']))
  const canDeleteReports = computed(() => canAccess(['dashboard.read']))

  const reportId = (() => {
    const value = (route.params as Record<string, string | string[] | undefined>).id
    if (Array.isArray(value)) return value[0] || ''
    return value || ''
  })()

  async function loadReport () {
    loading.value = true
    try {
      const response = await api.get(`/reports/${reportId}`)
      report.value = response.data.data || response.data
    } catch (error: any) {
      console.error('Error loading report:', error)
      toast.error('Erreur lors du chargement du rapport')
    } finally {
      loading.value = false
    }
  }

  async function downloadReport () {
    if (!canReadReports.value) {
      return
    }
    downloading.value = true
    try {
      const response = await api.get(`/reports/${reportId}/download`, {
        responseType: 'blob',
      })
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `${report.value.name}.${report.value.format}`)
      document.body.append(link)
      link.click()
      link.remove()
      toast.success('Rapport téléchargé avec succès')
    } catch (error: any) {
      console.error('Error downloading report:', error)
      toast.error('Erreur lors du téléchargement du rapport')
    } finally {
      downloading.value = false
    }
  }

  function shareReport () {
    if (!canReadReports.value) {
      return
    }
    toast.info('Fonctionnalité de partage à venir')
  }

  async function regenerateReport () {
    if (!canUpdateReports.value) {
      return
    }
    regenerating.value = true
    try {
      await api.post('/reports/generate', {
        type: report.value.type,
        format: report.value.format,
        ...report.value.parameters,
      })
      toast.success('Rapport en cours de régénération')
      await loadReport()
    } catch (error: any) {
      console.error('Error regenerating report:', error)
      toast.error('Erreur lors de la régénération du rapport')
    } finally {
      regenerating.value = false
    }
  }

  function deleteReport () {
    if (!canDeleteReports.value) {
      return
    }
    deleteDialog.value = true
  }

  async function confirmDelete () {
    if (!canDeleteReports.value) {
      return
    }
    deleting.value = true
    try {
      await api.delete(`/reports/${reportId}`)
      toast.success('Rapport supprimé avec succès')
      router.push('/company/reports')
    } catch (error: any) {
      console.error('Error deleting report:', error)
      toast.error('Erreur lors de la suppression du rapport')
    } finally {
      deleting.value = false
      deleteDialog.value = false
    }
  }

  function formatDate (date: string | undefined) {
    if (!date) return 'N/A'
    return new Date(date).toLocaleDateString('fr-FR', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    })
  }

  function formatDateTime (date: string | undefined) {
    if (!date) return 'N/A'
    return new Date(date).toLocaleString('fr-FR', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  }

  function formatParameterKey (key: string): string {
    return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
  }

  function formatParameterValue (value: any): string {
    if (Array.isArray(value)) return value.join(', ')
    if (typeof value === 'object') return JSON.stringify(value)
    if (typeof value === 'boolean') return value ? 'Oui' : 'Non'
    return String(value)
  }

  function formatFileSize (bytes: number): string {
    if (bytes === 0) return '0 Bytes'
    const k = 1024
    const sizes = ['Bytes', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
  }

  function getTypeColor (type: string): string {
    const colors: Record<string, string> = {
      'non-conformity': 'error',
      'audit': 'info',
      'performance': 'success',
      'safety': 'warning',
    }
    return colors[type] || 'primary'
  }

  function getStatusColor (status: string): string {
    const colors: Record<string, string> = {
      pending: 'warning',
      generated: 'success',
      error: 'error',
    }
    return colors[status] || 'grey'
  }

  function getStatusLabel (status: string): string {
    const labels: Record<string, string> = {
      pending: 'En attente',
      generated: 'Généré',
      error: 'Erreur',
    }
    return labels[status] || status
  }

  onMounted(() => {
    loadReport()
  })
</script>

<style scoped>
.gap-2 {
  gap: 0.5rem;
}
</style>
