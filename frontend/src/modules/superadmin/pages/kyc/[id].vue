<template>
  <SuperAdminLayout current-page="kyc">
    <v-container class="pa-6" fluid>
      <!-- Loading State -->
      <div v-if="loading">
        <v-skeleton-loader type="heading, list-item-three-line" />
        <v-skeleton-loader class="mt-4" type="card" />
      </div>

      <!-- Enterprise Details -->
      <div v-else-if="enterprise">
        <!-- Header -->
        <div class="d-flex align-center mb-6">
          <v-tooltip location="top" text="Retour">
            <template #activator="{ props: tooltipProps }">
              <v-btn
                v-bind="tooltipProps"
                class="mr-4"
                icon
                variant="text"
                @click="$router.back()"
              >
                <v-icon>mdi-arrow-left</v-icon>
              </v-btn>
            </template>
          </v-tooltip>

          <div class="flex-grow-1">
            <h1 class="text-h4 font-weight-bold text-primary mb-1">
              {{ enterprise.name }}
            </h1>
            <p class="text-subtitle-1 text-grey-darken-1">
              Demande d'inscription #{{ enterprise.id }}
            </p>
          </div>

          <v-chip
            class="mr-4"
            :color="getStatusColor(enterprise.status)"
            size="x-small"
            variant="tonal"
          >
            <v-icon start>{{ getStatusIcon(enterprise.status) }}</v-icon>
            {{ getStatusLabel(enterprise.status) }}
          </v-chip>

          <!-- Quick Actions -->
          <div class="d-flex align-center ga-2 flex-wrap">
            <v-btn
              v-if="enterprise.status === 'pending'"
              color="error"
              prepend-icon="mdi-close-circle-outline"
              variant="outlined"
              @click="rejectDialog = true"
            >
              Rejeter
            </v-btn>
            <v-btn
              v-if="enterprise.status === 'pending'"
              color="success"
              prepend-icon="mdi-check-circle-outline"
              variant="flat"
              @click="approveDialog = true"
            >
              Approuver
            </v-btn>
            <v-btn
              color="primary"
              prepend-icon="mdi-office-building-outline"
              variant="outlined"
              @click="goToCompanyDetail"
            >
              Fiche Entreprise
            </v-btn>
          </div>
        </div>

        <v-row>
          <!-- Main Content -->
          <v-col cols="12" lg="8">
            <!-- Informations Entreprise -->
            <v-card class="mb-6" elevation="2">
              <v-card-title class="d-flex align-center pa-6 pb-4">
                <v-avatar
                  class="mr-3"
                  color="primary"
                  size="40"
                  variant="tonal"
                >
                  <v-icon color="primary">mdi-office-building-outline</v-icon>
                </v-avatar>
                <span class="text-h6 font-weight-bold">Informations Entreprise</span>
              </v-card-title>

              <v-divider />

              <v-card-text class="pa-6">
                <v-row>
                  <v-col cols="12" md="6">
                    <div class="mb-5">
                      <p class="text-caption mb-1" style="color: rgb(var(--v-theme-on-surface-variant))">Raison sociale</p>
                      <p class="text-body-1 font-weight-medium">{{ enterprise.name }}</p>
                    </div>
                  </v-col>

                  <v-col cols="12" md="6">
                    <div class="mb-5">
                      <p class="text-caption mb-1" style="color: rgb(var(--v-theme-on-surface-variant))">Email</p>
                      <p class="text-body-1 font-weight-medium">{{ enterprise.email }}</p>
                    </div>
                  </v-col>

                  <v-col cols="12" md="6">
                    <div class="mb-5">
                      <p class="text-caption mb-1" style="color: rgb(var(--v-theme-on-surface-variant))">Numéro d'enregistrement</p>
                      <p class="text-body-1 font-weight-medium">{{ enterprise.registration_number || 'N/A' }}</p>
                    </div>
                  </v-col>

                  <v-col cols="12" md="6">
                    <div class="mb-5">
                      <p class="text-caption mb-1" style="color: rgb(var(--v-theme-on-surface-variant))">Secteur d'activité</p>
                      <p class="text-body-1 font-weight-medium">{{ enterprise.field || 'N/A' }}</p>
                    </div>
                  </v-col>

                  <v-col cols="12" md="6">
                    <div class="mb-5">
                      <p class="text-caption mb-1" style="color: rgb(var(--v-theme-on-surface-variant))">Date d'inscription</p>
                      <p class="text-body-1 font-weight-medium">{{ formatDate(enterprise.created_at) }}</p>
                    </div>
                  </v-col>
                </v-row>
              </v-card-text>
            </v-card>

            <!-- Documents -->
            <v-card class="mb-6" elevation="2">
              <v-card-title class="d-flex align-center pa-6 pb-4">
                <v-avatar
                  class="mr-3"
                  color="primary"
                  size="40"
                  variant="tonal"
                >
                  <v-icon color="primary">mdi-file-document-multiple-outline</v-icon>
                </v-avatar>
                <span class="text-h6 font-weight-bold">Documents Justificatifs</span>
              </v-card-title>

              <v-divider />

              <v-card-text class="pa-0">
                <v-list v-if="enterprise.documents && enterprise.documents.length > 0" class="py-0">
                  <v-list-item
                    v-for="(doc, index) in enterprise.documents"
                    :key="doc.id"
                    :class="{ 'border-t': index > 0 }"
                  >
                    <template #prepend>
                      <v-avatar
                        color="surface-variant"
                        size="48"
                        variant="tonal"
                      >
                        <v-icon :color="getDocumentIconColor(doc.file_extension)">
                          {{ getDocumentIcon(doc.file_extension) }}
                        </v-icon>
                      </v-avatar>
                    </template>

                    <v-list-item-title class="font-weight-medium mb-1">
                      {{ doc.name }}
                    </v-list-item-title>
                    <v-list-item-subtitle>
                      <div class="d-flex flex-column">
                        <span>{{ doc.file_size }} • {{ getDocumentTypeLabel(doc.document_type) }}</span>
                        <span v-if="doc.document_number" class="text-primary font-weight-medium mt-1">
                          N°: {{ doc.document_number }}
                        </span>
                      </div>
                    </v-list-item-subtitle>

                    <template #append>
                      <div class="d-flex align-center ga-2">
                        <v-btn
                          color="primary"
                          prepend-icon="mdi-eye-outline"
                          variant="text"
                          @click="openPreview(doc)"
                        >
                          Prévisualiser
                        </v-btn>
                        <v-btn
                          color="primary"
                          :href="doc.download_url"
                          prepend-icon="mdi-download-outline"
                          target="_blank"
                          variant="text"
                        >
                          Télécharger
                        </v-btn>
                      </div>
                    </template>
                  </v-list-item>
                </v-list>

                <div v-else class="pa-6">
                  <EmptyState
                    description="Aucun document n'a encore été ajouté."
                    icon="mdi-file-document-outline"
                    title="Aucun document disponible"
                  />
                </div>
              </v-card-text>
            </v-card>

            <!-- Rejection Reason (if rejected) -->
            <v-card
              v-if="enterprise.status === 'rejected' && enterprise.rejection_reason"
              class="mb-6"
              elevation="2"
              style="background: rgb(var(--v-theme-error-container))"
            >
              <v-card-title class="d-flex align-center pa-6 pb-4">
                <v-icon class="mr-3" color="error">mdi-alert-circle-outline</v-icon>
                <span class="text-h6 font-weight-bold">Raison du rejet</span>
              </v-card-title>

              <v-divider />

              <v-card-text class="pa-6">
                <p class="text-body-1">{{ enterprise.rejection_reason }}</p>
              </v-card-text>
            </v-card>
          </v-col>

          <!-- Sidebar -->
          <v-col cols="12" lg="4">
            <!-- Admin Info -->
            <v-card
              v-if="adminUser"
              class="mb-6"
              elevation="0"
              style="border: 1px solid rgb(var(--v-theme-outline-variant)); border-radius: 16px"
            >
              <v-card-title class="pa-6 pb-4">
                <span class="text-h6 font-weight-bold">Administrateur</span>
              </v-card-title>

              <v-divider />

              <v-card-text class="pa-6">
                <div class="d-flex align-center mb-4">
                  <v-avatar
                    class="mr-4"
                    color="primary"
                    size="56"
                  >
                    <span class="text-h6 font-weight-bold">
                      {{ getInitials(adminUser?.name || adminUser?.username || '') }}
                    </span>
                  </v-avatar>
                  <div>
                    <p class="text-body-1 font-weight-bold mb-1">
                      {{ adminUser?.name || adminUser?.username }}
                    </p>
                    <p class="text-body-2" style="color: rgb(var(--v-theme-on-surface-variant))">
                      {{ adminUser?.email }}
                    </p>
                    <p
                      v-if="adminRoleLabel"
                      class="text-caption text-medium-emphasis mt-1"
                    >
                      {{ adminRoleLabel }}
                    </p>
                  </div>
                </div>

                <v-divider class="my-4" />

                <div v-if="adminUser?.phone" class="d-flex align-center">
                  <v-icon class="mr-3" color="on-surface-variant" size="20">mdi-phone-outline</v-icon>
                  <span class="text-body-2">{{ adminUser.phone }}</span>
                </div>
              </v-card-text>
            </v-card>

            <!-- Sites Info -->
            <v-card
              v-if="enterprise.sites && enterprise.sites.length > 0"
              class="mb-6"
              elevation="0"
              style="border: 1px solid rgb(var(--v-theme-outline-variant)); border-radius: 16px"
            >
              <v-card-title class="pa-6 pb-4">
                <span class="text-h6 font-weight-bold">Sites</span>
              </v-card-title>

              <v-divider />

              <v-card-text class="pa-6">
                <div
                  v-for="(site, index) in enterprise.sites"
                  :key="site.id"
                  :class="{ 'mt-4 pt-4 border-t': index > 0 }"
                >
                  <div class="d-flex align-center mb-2">
                    <v-icon class="mr-2" color="primary" size="20">mdi-map-marker-outline</v-icon>
                    <span class="text-body-1 font-weight-medium">{{ site.name }}</span>
                    <v-chip
                      v-if="site.is_headquarter"
                      class="ml-2"
                      color="primary"
                      size="x-small"
                      variant="tonal"
                    >
                      Siège
                    </v-chip>
                  </div>
                  <p v-if="site.location" class="text-body-2 ml-7" style="color: rgb(var(--v-theme-on-surface-variant))">
                    {{ site.location }}
                  </p>
                </div>
              </v-card-text>
            </v-card>

            <!-- Timeline -->
            <v-card
              elevation="0"
              style="border: 1px solid rgb(var(--v-theme-outline-variant)); border-radius: 16px"
            >
              <v-card-title class="pa-6 pb-4">
                <span class="text-h6 font-weight-bold">Historique</span>
              </v-card-title>

              <v-divider />

              <v-card-text class="pa-6">
                <v-timeline align="start" density="compact" side="end" truncate-line="both">
                  <v-timeline-item
                    v-if="enterprise.updated_at && enterprise.status !== 'pending'"
                    dot-color="primary"
                    size="small"
                  >
                    <div class="mb-4">
                      <div class="text-body-2 font-weight-medium mb-1">
                        {{ getStatusLabel(enterprise.status) }}
                      </div>
                      <div class="text-caption" style="color: rgb(var(--v-theme-on-surface-variant))">
                        {{ formatDate(enterprise.updated_at) }}
                      </div>
                    </div>
                  </v-timeline-item>

                  <v-timeline-item
                    dot-color="surface-variant"
                    size="small"
                  >
                    <div>
                      <div class="text-body-2 font-weight-medium mb-1">
                        Demande créée
                      </div>
                      <div class="text-caption" style="color: rgb(var(--v-theme-on-surface-variant))">
                        {{ formatDate(enterprise.created_at) }}
                      </div>
                    </div>
                  </v-timeline-item>
                </v-timeline>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </div>

      <!-- Error State -->
      <div v-else class="mt-4">
        <EmptyState
          description="L'entreprise demandée est introuvable ou a été supprimée."
          icon="mdi-office-building-outline"
          title="Entreprise non trouvée"
        />
      </div>
    </v-container>

    <!-- Approve Dialog -->
    <v-dialog v-model="approveDialog" max-width="500">
      <v-card style="border-radius: 16px">
        <v-card-title class="pa-6 pb-4 d-flex align-center">
          <v-icon class="mr-3" color="success" size="32">mdi-check-circle-outline</v-icon>
          <span class="text-h6 font-weight-bold">Approuver l'entreprise</span>
        </v-card-title>

        <v-divider />

        <v-card-text class="pa-6">
          <p class="text-body-1 mb-4">
            Êtes-vous sûr de vouloir approuver <strong>{{ enterprise?.name }}</strong> ?
          </p>
          <p class="text-body-2" style="color: rgb(var(--v-theme-on-surface-variant))">
            L'entreprise aura accès à la plateforme et pourra commencer à utiliser les services.
          </p>
        </v-card-text>

        <v-divider />

        <v-card-actions class="pa-6 pt-4">
          <v-spacer />
          <v-btn
            :disabled="approving"
            variant="text"
            @click="approveDialog = false"
          >
            Annuler
          </v-btn>
          <v-btn
            color="success"
            :loading="approving"
            variant="flat"
            @click="handleApprove"
          >
            Approuver
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Reject Dialog -->
    <v-dialog v-model="rejectDialog" max-width="600">
      <v-card style="border-radius: 16px">
        <v-card-title class="pa-6 pb-4 d-flex align-center">
          <v-icon class="mr-3" color="error" size="32">mdi-close-circle-outline</v-icon>
          <span class="text-h6 font-weight-bold">Rejeter l'entreprise</span>
        </v-card-title>

        <v-divider />

        <v-card-text class="pa-6">
          <p class="text-body-1 mb-4">
            Veuillez indiquer la raison du rejet de <strong>{{ enterprise?.name }}</strong>
          </p>

          <v-textarea
            v-model="rejectReason"
            :error-messages="rejectReasonError"
            label="Raison du rejet"
            placeholder="Expliquez pourquoi cette demande est rejetée..."
            rows="4"
            variant="outlined"
            @input="rejectReasonError = ''"
          />
        </v-card-text>

        <v-divider />

        <v-card-actions class="pa-6 pt-4">
          <v-spacer />
          <v-btn
            :disabled="rejecting"
            variant="text"
            @click="rejectDialog = false"
          >
            Annuler
          </v-btn>
          <v-btn
            color="error"
            :loading="rejecting"
            variant="flat"
            @click="handleReject"
          >
            Rejeter
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="previewDialog" max-width="900">
      <v-card>
        <v-card-title class="d-flex align-center justify-space-between pa-6">
          <div class="d-flex align-center">
            <v-icon class="mr-2" color="primary">mdi-file-eye-outline</v-icon>
            <span class="text-h6 font-weight-bold">Prévisualisation</span>
          </div>
          <v-tooltip location="top" text="Fermer">
            <template #activator="{ props: tooltipProps }">
              <v-btn v-bind="tooltipProps" icon="mdi-close" variant="text" @click="previewDialog = false" />
            </template>
          </v-tooltip>
        </v-card-title>
        <v-divider />
        <v-card-text class="pa-6">
          <div v-if="!previewDoc" class="text-caption text-medium-emphasis">
            Aucun document sélectionné.
          </div>
          <div v-else>
            <div class="text-body-2 font-weight-medium mb-4">
              {{ previewDoc.name }}
            </div>
            <div v-if="isImage(previewDoc.file_extension)" class="preview-container">
              <img :alt="previewDoc.name" :src="previewDoc.preview_url || previewDoc.download_url">
            </div>
            <div v-else-if="isPdf(previewDoc.file_extension)" class="preview-container">
              <iframe :src="previewIframeSrc || previewDoc.preview_url || previewDoc.download_url" title="PDF preview"></iframe>
            </div>
            <EmptyState
              v-else
              description="Ce type de fichier ne peut pas être prévisualisé. Utilisez le bouton Télécharger."
              icon="mdi-file-document-outline"
              title="Prévisualisation indisponible"
            />
          </div>
        </v-card-text>
      </v-card>
    </v-dialog>
  </SuperAdminLayout>
</template>

<script setup lang="ts">
  import type { Enterprise } from '@/types/api'
import { computed, onMounted, ref, watch } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { useToast } from '@/composables/useToast'
  import EmptyState from '@/modules/superadmin/components/EmptyState.vue'
  import SuperAdminLayout from '@/modules/superadmin/components/SuperAdminLayout.vue'
  import superAdminService from '@/services/superAdminService'

  const route = useRoute()
  const router = useRouter()
  const toast = useToast()

  const loading = ref(true)
  const enterprise = ref<Enterprise | null>(null)
  const approveDialog = ref(false)
  const rejectDialog = ref(false)
  const approving = ref(false)
  const rejecting = ref(false)
  const rejectReason = ref('')
  const rejectReasonError = ref('')
  const previewDialog = ref(false)
  const previewDoc = ref<any | null>(null)
  const previewIframeSrc = ref<string | null>(null)
  const adminUser = computed(() => {
    const admin = (enterprise.value as any)?.enterprise_admin
    if (admin) return admin
    return enterprise.value?.users?.[0] ?? null
  })
  const adminRoleLabel = computed(() => {
    const roles = (adminUser.value as any)?.role_names
    if (!roles || roles.length === 0) return null
    const primary = roles[0]
    if (primary === 'admin_entreprise') return 'Admin entreprise'
    return primary.replace(/_/g, ' ')
  })

  function getEnterpriseIdFromRoute (): number | null {
    const rawId = (route.params as Record<string, unknown>).id
    const idValue = typeof rawId === 'string' ? rawId : (Array.isArray(rawId) ? rawId[0] : null)
    if (!idValue) return null
    const parsed = Number.parseInt(idValue, 10)
    return Number.isNaN(parsed) ? null : parsed
  }

  onMounted(async () => {
    await loadEnterprise()
  })

  async function loadEnterprise () {
    loading.value = true
    try {
      const id = getEnterpriseIdFromRoute()
      if (!id) {
        toast.error('Identifiant entreprise invalide')
        return
      }
      enterprise.value = await superAdminService.getEnterprise(id)
    } catch (error: any) {
      toast.error(error?.response?.data?.message || 'Erreur lors du chargement de l’entreprise')
    } finally {
      loading.value = false
    }
  }

  async function handleApprove () {
    if (!enterprise.value) return

    approving.value = true
    try {
      const updated = await superAdminService.approveEnterprise(enterprise.value.id)
      enterprise.value = { ...enterprise.value, ...updated }
      toast.success('Entreprise approuvée')
      approveDialog.value = false
      emitKycRefresh('approved')
      router.push('/superadmin/dashboard?refresh=1')
    } catch (error: any) {
      toast.error(error?.response?.data?.message || 'Erreur lors de l’approbation')
    } finally {
      approving.value = false
    }
  }

  async function handleReject () {
    if (!enterprise.value) return

    // Validation
    if (!rejectReason.value || rejectReason.value.length < 10) {
      rejectReasonError.value = 'La raison doit contenir au moins 10 caractères'
      return
    }

    rejecting.value = true
    try {
      const updated = await superAdminService.rejectEnterprise(enterprise.value.id, rejectReason.value)
      enterprise.value = { ...enterprise.value, ...updated }
      toast.success('Entreprise rejetée')
      rejectDialog.value = false
      rejectReason.value = ''
      emitKycRefresh('rejected')
      router.push('/superadmin/dashboard?refresh=1')
    } catch (error: any) {
      toast.error(error?.response?.data?.message || 'Erreur lors du rejet')
    } finally {
      rejecting.value = false
    }
  }

  function emitKycRefresh (status: string) {
    if (typeof window === 'undefined') return
    try {
      localStorage.setItem('superadmin_kyc_refresh_pending', '1')
    } catch {}
    window.dispatchEvent(new CustomEvent('superadmin-kyc-updated', {
      detail: { id: enterprise.value?.id, status },
    }))
  }

  function getStatusColor (status: string): string {
    const colors: Record<string, string> = {
      pending: 'warning',
      active: 'success',
      approved: 'success',
      rejected: 'error',
      suspended: 'error',
    }
    return colors[status] || 'default'
  }

  function getStatusIcon (status: string): string {
    const icons: Record<string, string> = {
      pending: 'mdi-clock-outline',
      active: 'mdi-check-circle-outline',
      approved: 'mdi-check-circle-outline',
      rejected: 'mdi-close-circle-outline',
      suspended: 'mdi-pause-circle-outline',
    }
    return icons[status] || 'mdi-help-circle-outline'
  }

  function getStatusLabel (status: string): string {
    const labels: Record<string, string> = {
      pending: 'En attente',
      active: 'Active',
      approved: 'Approuvée',
      rejected: 'Rejetée',
      suspended: 'Suspendue',
    }
    return labels[status] || status
  }

  function getDocumentTypeLabel (type: string): string {
    const labels: Record<string, string> = {
      legal: 'Document légal',
      compliance: 'Document de conformité',
      other: 'Autre',
    }
    return labels[type] || type
  }

  function getDocumentIcon (extension?: string): string {
    if (!extension) return 'mdi-file-outline'
    const ext = extension.toLowerCase()
    if (ext === 'pdf') return 'mdi-file-pdf-box'
    if (['jpg', 'jpeg', 'png'].includes(ext)) return 'mdi-file-image-outline'
    return 'mdi-file-outline'
  }

  function getDocumentIconColor (extension?: string): string {
    if (!extension) return 'grey'
    const ext = extension.toLowerCase()
    if (ext === 'pdf') return 'error'
    if (['jpg', 'jpeg', 'png'].includes(ext)) return 'primary'
    return 'grey'
  }

  function isImage (ext?: string): boolean {
    if (!ext) return false
    return ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext.toLowerCase())
  }

  function isPdf (ext?: string): boolean {
    return (ext || '').toLowerCase() === 'pdf'
  }

  async function openPreview (doc: any) {
    // Reset previous object URL if any
    if (previewIframeSrc.value) {
      try { window.URL.revokeObjectURL(previewIframeSrc.value) } catch {}
      previewIframeSrc.value = null
    }

    previewDoc.value = doc

    // Try to fetch preview as blob for PDFs before opening the dialog to avoid automatic download
    try {
      if (isPdf(doc.file_extension) && doc.preview_url) {
        const previewResponse = await fetch(String(doc.preview_url), {
          method: 'GET',
          credentials: 'include',
        })
        if (!previewResponse.ok) {
          throw new Error(`Impossible de charger l'aperçu (${previewResponse.status})`)
        }
        const blobData = await previewResponse.blob()
        const contentType = previewResponse.headers.get('content-type') || 'application/pdf'
        const objectUrl = window.URL.createObjectURL(new Blob([blobData], { type: contentType }))
        previewIframeSrc.value = objectUrl
        previewDialog.value = true
        return
      }

      // Not a PDF or no preview URL: open dialog and let template use the URL
      previewIframeSrc.value = null
      previewDialog.value = true
    } catch (e) {
      // If fetching fails (CORS, network), fall back to opening with direct URL
      previewIframeSrc.value = null
      previewDialog.value = true
    }
  }

  watch(previewDialog, (val) => {
    if (!val && previewIframeSrc.value) {
      try { window.URL.revokeObjectURL(previewIframeSrc.value) } catch {}
      previewIframeSrc.value = null
    }
  })

  function getInitials (name: string): string {
    return name
      .split(' ')
      .map(word => word[0])
      .join('')
      .toUpperCase()
      .slice(0, 2)
  }

  function formatDate (date?: string): string {
    if (!date) return 'N/A'
    return new Date(date).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'long',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  }

  function goToCompanyDetail () {
    if (!enterprise.value) return
    router.push(`/superadmin/companies/${enterprise.value.id}`)
  }
</script>

<style scoped>
.border-t {
  border-top: 1px solid rgb(var(--v-theme-outline-variant));
}

.preview-container {
  width: 100%;
  max-height: 70vh;
  border: 1px solid rgba(var(--v-theme-outline), 0.12);
  border-radius: 12px;
  overflow: hidden;
  background: rgb(var(--v-theme-surface));
}

.preview-container img,
.preview-container iframe {
  width: 100%;
  height: 70vh;
  display: block;
}
</style>
