<template>
  <SuperAdminLayout current-page="kyc">
    <v-container class="pa-6" fluid>
      <!-- Header -->
      <div class="d-flex align-center mb-6">
        <v-btn
          class="mr-4"
          icon
          size="large"
          variant="text"
          @click="$router.back()"
        >
          <v-icon>mdi-arrow-left</v-icon>
        </v-btn>

        <div class="flex-grow-1">
          <h1 class="text-h4 font-weight-bold mb-1">
            {{ kycData.company.name }}
          </h1>
          <p class="text-body-2 text-medium-emphasis">
            Demande d'inscription #{{ $route.params.id }}
          </p>
        </div>

        <v-chip
          class="mr-4"
          :color="getStatusColor(kycData.status)"
          size="large"
          variant="flat"
        >
          <v-icon start>{{ getStatusIcon(kycData.status) }}</v-icon>
          {{ getStatusLabel(kycData.status) }}
        </v-chip>

        <!-- Quick Actions -->
        <div v-if="kycData.status === 'pending'" class="d-flex gap-2">
          <v-btn
            color="error"
            prepend-icon="mdi-close-circle-outline"
            variant="outlined"
            @click="handleReject"
          >
            Rejeter
          </v-btn>
          <v-btn
            color="success"
            prepend-icon="mdi-check-circle-outline"
            variant="flat"
            @click="handleApprove"
          >
            Approuver
          </v-btn>
        </div>
      </div>

      <v-row>
        <!-- Main Content -->
        <v-col cols="12" lg="8">
          <!-- Section 1: Informations Entreprise -->
          <v-card
            class="mb-6 card-modern"
            elevation="0"
          >
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
                  <div class="info-item mb-5">
                    <p class="text-caption text-medium-emphasis mb-1">Raison sociale</p>
                    <p class="text-body-1 font-weight-medium">{{ kycData.company.name }}</p>
                  </div>
                </v-col>

                <v-col cols="12" md="6">
                  <div class="info-item mb-5">
                    <p class="text-caption text-medium-emphasis mb-1">Forme juridique</p>
                    <p class="text-body-1 font-weight-medium">{{ kycData.company.legalForm }}</p>
                  </div>
                </v-col>

                <v-col cols="12" md="6">
                  <div class="info-item mb-5">
                    <p class="text-caption text-medium-emphasis mb-1">RCCM</p>
                    <p class="text-body-1 font-weight-medium">{{ kycData.company.rccm }}</p>
                  </div>
                </v-col>

                <v-col cols="12" md="6">
                  <div class="info-item mb-5">
                    <p class="text-caption text-medium-emphasis mb-1">IFU</p>
                    <p class="text-body-1 font-weight-medium">{{ kycData.company.ifu }}</p>
                  </div>
                </v-col>

                <v-col cols="12" md="6">
                  <div class="info-item mb-5">
                    <p class="text-caption text-medium-emphasis mb-1">Secteur d'activité</p>
                    <p class="text-body-1 font-weight-medium">{{ kycData.company.sector }}</p>
                  </div>
                </v-col>

                <v-col cols="12" md="6">
                  <div class="info-item mb-5">
                    <p class="text-caption text-medium-emphasis mb-1">Date de création</p>
                    <p class="text-body-1 font-weight-medium">{{ formatDate(kycData.company.createdAt) }}</p>
                  </div>
                </v-col>

                <v-col cols="12">
                  <v-divider class="my-2" />
                </v-col>

                <v-col cols="12">
                  <div class="info-item">
                    <p class="text-caption text-medium-emphasis mb-1">Adresse complète</p>
                    <p class="text-body-1 font-weight-medium">{{ kycData.company.address }}</p>
                  </div>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <!-- Section 2: Identité du Gérant -->
          <v-card
            class="mb-6 card-modern"
            elevation="0"
          >
            <v-card-title class="d-flex align-center pa-6 pb-4">
              <v-avatar
                class="mr-3"
                color="success"
                size="40"
                variant="tonal"
              >
                <v-icon color="success">mdi-account-outline</v-icon>
              </v-avatar>
              <span class="text-h6 font-weight-bold">Identité du Gérant</span>
            </v-card-title>

            <v-divider />

            <v-card-text class="pa-6">
              <v-row>
                <v-col cols="12" md="6">
                  <div class="info-item mb-5">
                    <p class="text-caption text-medium-emphasis mb-1">Nom complet</p>
                    <p class="text-body-1 font-weight-medium">{{ kycData.manager.fullName }}</p>
                  </div>
                </v-col>

                <v-col cols="12" md="6">
                  <div class="info-item mb-5">
                    <p class="text-caption text-medium-emphasis mb-1">Email</p>
                    <p class="text-body-1 font-weight-medium">
                      <v-icon class="mr-1" size="small">mdi-email-outline</v-icon>
                      {{ kycData.manager.email }}
                    </p>
                  </div>
                </v-col>

                <v-col cols="12" md="6">
                  <div class="info-item mb-5">
                    <p class="text-caption text-medium-emphasis mb-1">Téléphone</p>
                    <p class="text-body-1 font-weight-medium">
                      <v-icon class="mr-1" size="small">mdi-phone-outline</v-icon>
                      {{ kycData.manager.phone }}
                    </p>
                  </div>
                </v-col>

                <v-col cols="12" md="6">
                  <div class="info-item mb-5">
                    <p class="text-caption text-medium-emphasis mb-1">Type de pièce</p>
                    <p class="text-body-1 font-weight-medium">{{ kycData.manager.idType }}</p>
                  </div>
                </v-col>

                <v-col cols="12" md="6">
                  <div class="info-item mb-5">
                    <p class="text-caption text-medium-emphasis mb-1">Numéro de pièce</p>
                    <p class="text-body-1 font-weight-medium">{{ kycData.manager.idNumber }}</p>
                  </div>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <!-- Section 3: Documents -->
          <v-card
            class="mb-6 card-modern"
            elevation="0"
          >
            <v-card-title class="d-flex align-center pa-6 pb-4">
              <v-avatar
                class="mr-3"
                color="warning"
                size="40"
                variant="tonal"
              >
                <v-icon color="warning">mdi-file-document-multiple-outline</v-icon>
              </v-avatar>
              <span class="text-h6 font-weight-bold">Documents justificatifs</span>
            </v-card-title>

            <v-divider />

            <v-card-text class="pa-6">
              <v-row>
                <v-col
                  v-for="doc in kycData.documents"
                  :key="doc.id"
                  cols="12"
                  md="4"
                >
                  <div class="document-card">
                    <!-- Document Preview -->
                    <div class="document-preview">
                      <v-img
                        v-if="doc.preview"
                        :alt="doc.name"
                        class="rounded-lg"
                        cover
                        height="180"
                        :src="doc.preview"
                      >
                        <template #placeholder>
                          <div class="d-flex align-center justify-center fill-height">
                            <v-progress-circular color="primary" indeterminate />
                          </div>
                        </template>
                      </v-img>

                      <div v-else class="document-placeholder">
                        <v-icon color="grey-lighten-1" size="64">mdi-file-document-outline</v-icon>
                      </div>

                      <!-- Overlay on hover -->
                      <div class="document-overlay">
                        <v-btn
                          color="white"
                          icon
                          size="large"
                          variant="flat"
                          @click="previewDocument(doc)"
                        >
                          <v-icon color="primary">mdi-eye-outline</v-icon>
                        </v-btn>
                      </div>
                    </div>

                    <!-- Document Info -->
                    <div class="pa-3">
                      <p class="text-body-2 font-weight-medium mb-2">{{ doc.name }}</p>
                      <div class="d-flex align-center justify-space-between">
                        <span class="text-caption text-medium-emphasis">{{ doc.size }}</span>
                        <v-btn
                          icon
                          size="small"
                          variant="text"
                          @click="downloadDocument(doc)"
                        >
                          <v-icon size="20">mdi-download-outline</v-icon>
                        </v-btn>
                      </div>
                    </div>
                  </div>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>
        </v-col>

        <!-- Sidebar -->
        <v-col cols="12" lg="4">
          <!-- Timeline -->
          <v-card
            class="mb-6 card-modern"
            elevation="0"
          >
            <v-card-title class="d-flex align-center pa-6 pb-4">
              <v-icon class="mr-2" color="info">mdi-timeline-clock-outline</v-icon>
              <span class="text-h6 font-weight-bold">Timeline</span>
            </v-card-title>

            <v-divider />

            <v-card-text class="pa-6">
              <v-timeline
                class="timeline-custom"
                density="compact"
                side="end"
              >
                <v-timeline-item
                  v-for="event in kycData.timeline"
                  :key="event.id"
                  :dot-color="event.color"
                  size="small"
                >
                  <template #icon>
                    <v-icon color="white" size="16">{{ event.icon }}</v-icon>
                  </template>

                  <div class="pb-4">
                    <p class="text-body-2 font-weight-medium mb-1">{{ event.title }}</p>
                    <p class="text-caption text-medium-emphasis mb-1">{{ event.description }}</p>
                    <p class="text-caption text-medium-emphasis">
                      <v-icon class="mr-1" size="14">mdi-clock-outline</v-icon>
                      {{ event.time }}
                    </p>
                  </div>
                </v-timeline-item>
              </v-timeline>
            </v-card-text>
          </v-card>

          <!-- Informations complémentaires -->
          <v-card
            class="mb-6 card-modern"
            elevation="0"
          >
            <v-card-title class="d-flex align-center pa-6 pb-4">
              <v-icon class="mr-2" color="info">mdi-information-outline</v-icon>
              <span class="text-h6 font-weight-bold">Informations</span>
            </v-card-title>

            <v-divider />

            <v-card-text class="pa-6">
              <div class="info-item mb-4">
                <p class="text-caption text-medium-emphasis mb-1">Date de soumission</p>
                <p class="text-body-2 font-weight-medium">{{ formatDate(kycData.submittedAt) }}</p>
              </div>

              <div class="info-item mb-4">
                <p class="text-caption text-medium-emphasis mb-1">Offre sélectionnée</p>
                <v-chip
                  class="font-weight-medium"
                  color="primary"
                  size="small"
                  variant="tonal"
                >
                  {{ kycData.selectedOffer }}
                </v-chip>
              </div>

              <div class="info-item mb-4">
                <p class="text-caption text-medium-emphasis mb-1">Nombre d'employés</p>
                <p class="text-body-2 font-weight-medium">{{ kycData.company.employeesCount }}</p>
              </div>

              <div class="info-item">
                <p class="text-caption text-medium-emphasis mb-1">Site web</p>
                <a
                  v-if="kycData.company.website"
                  class="text-body-2 text-primary font-weight-medium text-decoration-none"
                  :href="kycData.company.website"
                  target="_blank"
                >
                  {{ kycData.company.website }}
                  <v-icon class="ml-1" size="14">mdi-open-in-new</v-icon>
                </a>
                <p v-else class="text-body-2 text-medium-emphasis">Non renseigné</p>
              </div>
            </v-card-text>
          </v-card>

          <!-- Actions Card (si pending) -->
          <v-card
            v-if="kycData.status === 'pending'"
            class="card-modern"
            color="primary"
            elevation="0"
            variant="tonal"
          >
            <v-card-title class="pa-6 pb-4">
              <v-icon class="mr-2">mdi-hand-pointing-right</v-icon>
              <span class="text-h6 font-weight-bold">Actions requises</span>
            </v-card-title>

            <v-divider />

            <v-card-text class="pa-6">
              <p class="text-body-2 mb-4">
                Cette demande nécessite votre validation pour permettre à l'entreprise d'accéder à la plateforme.
              </p>

              <v-btn
                block
                class="mb-3"
                color="success"
                prepend-icon="mdi-check-circle-outline"
                size="large"
                variant="flat"
                @click="handleApprove"
              >
                Approuver la demande
              </v-btn>

              <v-btn
                block
                color="error"
                prepend-icon="mdi-close-circle-outline"
                size="large"
                variant="outlined"
                @click="handleReject"
              >
                Rejeter la demande
              </v-btn>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-container>

    <!-- Dialogs -->
    <!-- Preview Dialog -->
    <v-dialog v-model="previewDialog" max-width="800">
      <v-card>
        <v-card-title class="d-flex align-center justify-space-between">
          <span>{{ selectedDocument?.name }}</span>
          <v-btn icon variant="text" @click="previewDialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text class="pa-0">
          <v-img
            v-if="selectedDocument?.preview"
            :alt="selectedDocument.name"
            max-height="600"
            :src="selectedDocument.preview"
          />
        </v-card-text>
      </v-card>
    </v-dialog>

    <!-- Approve Dialog -->
    <v-dialog v-model="approveDialog" max-width="500">
      <v-card>
        <v-card-title class="text-h6 bg-success-lighten-5">
          <v-icon class="mr-2" color="success">mdi-check-circle</v-icon>
          Approuver la demande
        </v-card-title>
        <v-card-text class="pa-6">
          <p class="text-body-1 mb-4">
            Êtes-vous sûr de vouloir approuver cette demande d'inscription ?
          </p>
          <v-alert class="mb-4" color="info" density="compact" variant="tonal">
            L'entreprise recevra un email de confirmation et pourra accéder à la plateforme.
          </v-alert>
        </v-card-text>
        <v-card-actions class="pa-6 pt-0">
          <v-spacer />
          <v-btn variant="text" @click="approveDialog = false">
            Annuler
          </v-btn>
          <v-btn color="success" variant="flat" @click="confirmApprove">
            Confirmer l'approbation
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Reject Dialog -->
    <v-dialog v-model="rejectDialog" max-width="500">
      <v-card>
        <v-card-title class="text-h6 bg-error-lighten-5">
          <v-icon class="mr-2" color="error">mdi-close-circle</v-icon>
          Rejeter la demande
        </v-card-title>
        <v-card-text class="pa-6">
          <p class="text-body-1 mb-4">
            Êtes-vous sûr de vouloir rejeter cette demande d'inscription ?
          </p>
          <v-textarea
            v-model="rejectReason"
            label="Motif du rejet"
            placeholder="Expliquez la raison du rejet..."
            required
            rows="4"
            variant="outlined"
          />
        </v-card-text>
        <v-card-actions class="pa-6 pt-0">
          <v-spacer />
          <v-btn variant="text" @click="rejectDialog = false">
            Annuler
          </v-btn>
          <v-btn color="error" :disabled="!rejectReason" variant="flat" @click="confirmReject">
            Confirmer le rejet
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </SuperAdminLayout>
</template>

<script setup lang="ts">
  import { ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { useToast } from '@/composables/useToast'
  import SuperAdminLayout from '@/modules/superadmin/components/SuperAdminLayout.vue'

  const route = useRoute()
  const _router = useRouter()
  const toast = useToast()

  // Dialogs
  const previewDialog = ref(false)
  const approveDialog = ref(false)
  const rejectDialog = ref(false)
  const rejectReason = ref('')
  const selectedDocument = ref<any>(null)

  // Mock KYC Data
  const kycData = ref({
    id: route.params.id,
    status: 'pending', // pending | approved | rejected
    submittedAt: '2026-01-15T10:30:00',
    selectedOffer: 'Pack Premium',

    company: {
      name: 'SOMDIAA Côte d\'Ivoire SA',
      legalForm: 'Société Anonyme (SA)',
      rccm: 'CI-ABJ-2015-B-12345',
      ifu: '0123456789012',
      sector: 'Industrie Agroalimentaire',
      createdAt: '2015-03-15',
      address: 'Zone Industrielle de Yopougon, Abidjan, Côte d\'Ivoire',
      employeesCount: '250-500',
      website: 'https://somdiaa.com',
    },

    manager: {
      fullName: 'Kouassi Jean-Baptiste',
      email: 'jb.kouassi@somdiaa.com',
      phone: '+225 07 08 09 10 11',
      idType: 'Carte Nationale d\'Identité',
      idNumber: 'CI2023AB123456',
    },

    documents: [
      {
        id: 1,
        name: 'RCCM',
        size: '2.3 MB',
        preview: 'https://images.unsplash.com/photo-1554224311-beee460c201a?w=400&h=300&fit=crop',
        url: '/documents/rccm.pdf',
      },
      {
        id: 2,
        name: 'IFU',
        size: '1.8 MB',
        preview: 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?w=400&h=300&fit=crop',
        url: '/documents/ifu.pdf',
      },
      {
        id: 3,
        name: 'Pièce d\'identité',
        size: '1.2 MB',
        preview: 'https://images.unsplash.com/photo-1589829545856-d10d557cf95f?w=400&h=300&fit=crop',
        url: '/documents/id.pdf',
      },
    ],

    timeline: [
      {
        id: 1,
        title: 'Demande soumise',
        description: 'Le formulaire d\'inscription a été complété',
        time: '15 Jan 2026, 10:30',
        color: 'primary',
        icon: 'mdi-file-document-edit',
      },
      {
        id: 2,
        title: 'Documents uploadés',
        description: '3 documents justificatifs ajoutés',
        time: '15 Jan 2026, 10:35',
        color: 'info',
        icon: 'mdi-upload',
      },
      {
        id: 3,
        title: 'En attente de validation',
        description: 'Demande assignée au Super Admin',
        time: '15 Jan 2026, 10:36',
        color: 'warning',
        icon: 'mdi-clock-outline',
      },
    ],
  })

  // Status helpers
  function getStatusColor (status: string) {
    const colors: Record<string, string> = {
      pending: 'warning',
      approved: 'success',
      rejected: 'error',
    }
    return colors[status] || 'grey'
  }

  function getStatusIcon (status: string) {
    const icons: Record<string, string> = {
      pending: 'mdi-clock-outline',
      approved: 'mdi-check-circle',
      rejected: 'mdi-close-circle',
    }
    return icons[status] || 'mdi-help-circle'
  }

  function getStatusLabel (status: string) {
    const labels: Record<string, string> = {
      pending: 'En attente',
      approved: 'Approuvée',
      rejected: 'Rejetée',
    }
    return labels[status] || 'Inconnu'
  }

  function formatDate (dateString: string) {
    const date = new Date(dateString)
    return new Intl.DateTimeFormat('fr-FR', {
      day: '2-digit',
      month: 'long',
      year: 'numeric',
    }).format(date)
  }

  // Document actions
  function previewDocument (doc: any) {
    selectedDocument.value = doc
    previewDialog.value = true
  }

  function downloadDocument (doc: any) {
    console.log('Downloading:', doc.name)
    // Simulate download
    window.open(doc.url, '_blank')
  }

  // Approval actions
  function handleApprove () {
    approveDialog.value = true
  }

  function handleReject () {
    rejectDialog.value = true
  }

  function confirmApprove () {
    console.log('Approving KYC request:', kycData.value.id)

    // Simulate API call
    kycData.value.status = 'approved'
    kycData.value.timeline.push({
      id: Date.now(),
      title: 'Demande approuvée',
      description: 'L\'entreprise peut maintenant accéder à la plateforme',
      time: new Date().toLocaleDateString('fr-FR'),
      color: 'success',
      icon: 'mdi-check-circle',
    })

    approveDialog.value = false

    // Show success message
    toast.success('Demande approuvée avec succès!')
  }

  function confirmReject () {
    if (!rejectReason.value.trim()) return

    console.log('Rejecting KYC request:', kycData.value.id, 'Reason:', rejectReason.value)

    // Simulate API call
    kycData.value.status = 'rejected'
    kycData.value.timeline.push({
      id: Date.now(),
      title: 'Demande rejetée',
      description: rejectReason.value,
      time: new Date().toLocaleDateString('fr-FR'),
      color: 'error',
      icon: 'mdi-close-circle',
    })

    rejectDialog.value = false
    rejectReason.value = ''

    // Show success message
    toast.success('Demande rejetée.')
  }
</script>

<style scoped>
/* Modern Card Styling */
.card-modern {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 12px;
  transition: all 0.3s ease;
}

.card-modern:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

/* Info Item Spacing */
.info-item {
  transition: all 0.2s ease;
}

.info-item:hover {
  transform: translateX(2px);
}

/* Document Card */
.document-card {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 12px;
  overflow: hidden;
  transition: all 0.3s ease;
  position: relative;
}

.document-card:hover {
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
  transform: translateY(-4px);
}

.document-preview {
  position: relative;
  overflow: hidden;
  background-color: rgb(var(--v-theme-surface-variant));
}

.document-placeholder {
  height: 180px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: rgb(var(--v-theme-surface-variant));
}

.document-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.document-card:hover .document-overlay {
  opacity: 1;
}

/* Timeline Custom */
.timeline-custom :deep(.v-timeline-item__body) {
  padding-bottom: 16px;
}

/* Gap utility (Vuetify might not have it) */
.gap-2 {
  gap: 8px;
}

/* Smooth transitions */
* {
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

/* Dark mode enhancements */
@media (prefers-color-scheme: dark) {
  .document-overlay {
    background-color: rgba(0, 0, 0, 0.8);
  }

  .card-modern {
    background-color: rgb(var(--v-theme-surface));
  }
}

/* Responsive adjustments */
@media (max-width: 960px) {
  .document-card {
    margin-bottom: 16px;
  }
}
</style>
