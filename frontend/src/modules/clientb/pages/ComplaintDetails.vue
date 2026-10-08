<template>
  <ClientBLayout current-page="/clientb/complaints">
    <!-- Loading -->
    <div v-if="loading" class="pa-4">
      <v-skeleton-loader type="article, article" />
    </div>

    <!-- Content -->
    <div v-else-if="complaint">
      <!-- Header -->
      <v-card class="mb-6" elevation="0">
        <v-card-text class="pa-6">
          <div class="d-flex align-center mb-4">
            <v-btn
              class="me-2"
              icon
              variant="text"
              @click="goBack"
            >
              <v-icon>mdi-arrow-left</v-icon>
            </v-btn>
            <div class="flex-grow-1">
              <div class="d-flex align-center gap-2 mb-2">
                <v-chip
                  :color="complaintService.getStatusColor(complaint.status)"
                  variant="tonal"
                >
                  <v-icon size="16" start>
                    {{ complaintService.getStatusIcon(complaint.status) }}
                  </v-icon>
                  {{ complaintService.getStatusLabel(complaint.status) }}
                </v-chip>
                <v-chip
                  :color="getPriorityColor(complaint.priority!)"
                  size="small"
                  variant="flat"
                >
                  {{ getPriorityLabel(complaint.priority!) }}
                </v-chip>
                <v-spacer />
                <div v-if="canEdit" class="d-flex gap-2">
                  <!-- <v-btn
                    color="primary"
                    prepend-icon="mdi-pencil"
                    variant="tonal"
                    @click="editComplaint"
                  >
                    Modifier
                  </v-btn> -->
                  <v-btn
                    color="error"
                    prepend-icon="mdi-delete"
                    variant="outlined"
                    @click="confirmDelete"
                  >
                    Supprimer
                  </v-btn>
                </div>
              </div>
              <h1 class="text-h4 font-weight-bold mb-1">
                {{ complaint.title }}
              </h1>
              <div class="d-flex align-center gap-3 text-body-2 text-medium-emphasis">
                <span>
                  <v-icon class="me-1" size="16">mdi-tag</v-icon>
                  {{ complaint.reference }}
                </span>
                <span>
                  <v-icon class="me-1" size="16">mdi-calendar</v-icon>
                  Créée le {{ formatDate(complaint.created_at) }}
                </span>
                <span v-if="complaint.category">
                  <v-icon class="me-1" size="16">mdi-folder</v-icon>
                  {{ complaint.category }}
                </span>
              </div>
            </div>
          </div>
        </v-card-text>
      </v-card>

      <v-row>
        <!-- Main Content -->
        <v-col cols="12" md="8">
          <!-- Description -->
          <v-card class="mb-6" elevation="0">
            <v-card-title class="pa-6 pb-4">
              <v-icon class="me-2">mdi-text</v-icon>
              Description
            </v-card-title>
            <v-divider />
            <v-card-text class="pa-6">
              <p class="text-body-1" style="white-space: pre-wrap;">
                {{ complaint.description }}
              </p>
            </v-card-text>
          </v-card>

          <!-- Responses / Recommendations -->
          <v-card v-if="complaint.recommandations" class="mb-6" elevation="0">
            <v-card-title class="pa-6 pb-4">
              <v-icon class="me-2" color="info">mdi-message-reply</v-icon>
              Réponses et Recommandations
            </v-card-title>
            <v-divider />
            <v-card-text class="pa-6">
              <div class="response-content">
                <p class="text-body-1" style="white-space: pre-wrap;">
                  {{ complaint.recommandations }}
                </p>
              </div>
            </v-card-text>
          </v-card>

          <!-- No Response Yet -->
          <v-card
            v-else
            class="mb-6"
            color="info"
            elevation="0"
            variant="tonal"
          >
            <v-card-text class="pa-6 text-center">
              <v-icon class="mb-3" color="info" size="48">
                mdi-clock-outline
              </v-icon>
              <h3 class="text-h6 font-weight-bold mb-2">
                En attente de réponse
              </h3>
              <p class="text-body-2">
                Votre réclamation est en cours de traitement. Nous vous répondrons dans les plus brefs délais.
              </p>
            </v-card-text>
          </v-card>
        </v-col>

        <!-- Sidebar -->
        <v-col cols="12" md="4">
          <!-- Timeline -->
          <v-card class="mb-4" elevation="0">
            <v-card-title class="pa-4">
              <v-icon class="me-2">mdi-timeline-clock</v-icon>
              Historique
            </v-card-title>
            <v-divider />
            <v-card-text class="pa-4">
              <v-timeline align="start" density="compact" side="end">
                <v-timeline-item
                  dot-color="primary"
                  fill-dot
                  size="small"
                >
                  <div class="d-flex flex-column">
                    <strong class="text-body-2">Réclamation créée</strong>
                    <span class="text-caption text-medium-emphasis">
                      {{ formatDateTime(complaint.created_at) }}
                    </span>
                  </div>
                </v-timeline-item>

                <v-timeline-item
                  v-if="complaint.updated_at !== complaint.created_at"
                  dot-color="info"
                  fill-dot
                  size="small"
                >
                  <div class="d-flex flex-column">
                    <strong class="text-body-2">Dernière mise à jour</strong>
                    <span class="text-caption text-medium-emphasis">
                      {{ formatDateTime(complaint.updated_at) }}
                    </span>
                  </div>
                </v-timeline-item>

                <v-timeline-item
                  v-if="complaint.status === 'resolved' || complaint.status === 'closed'"
                  :dot-color="complaint.status === 'resolved' ? 'success' : 'grey'"
                  fill-dot
                  size="small"
                >
                  <div class="d-flex flex-column">
                    <strong class="text-body-2">
                      {{ complaint.status === 'resolved' ? 'Résolue' : 'Fermée' }}
                    </strong>
                    <span class="text-caption text-medium-emphasis">
                      {{ formatDateTime(complaint.updated_at) }}
                    </span>
                  </div>
                </v-timeline-item>
              </v-timeline>
            </v-card-text>
          </v-card>

          <!-- Details Card -->
          <v-card class="mb-4" elevation="0">
            <v-card-title class="pa-4">
              <v-icon class="me-2">mdi-information</v-icon>
              Détails
            </v-card-title>
            <v-divider />
            <v-card-text class="pa-4">
              <v-list class="pa-0" density="compact">
                <v-list-item class="px-0">
                  <template #prepend>
                    <v-icon class="me-2" size="20">mdi-flag</v-icon>
                  </template>
                  <v-list-item-title class="text-body-2">
                    <strong>Priorité:</strong>
                    <v-chip
                      class="ms-2"
                      :color="getPriorityColor(complaint.priority!)"
                      size="x-small"
                    >
                      {{ getPriorityLabel(complaint.priority!) }}
                    </v-chip>
                  </v-list-item-title>
                </v-list-item>

                <v-list-item v-if="complaint.site_id" class="px-0">
                  <template #prepend>
                    <v-icon class="me-2" size="20">mdi-map-marker</v-icon>
                  </template>
                  <v-list-item-title class="text-body-2">
                    <strong>Site:</strong> {{ complaint.site?.name || 'N/A' }}
                  </v-list-item-title>
                </v-list-item>

                <v-list-item v-if="complaint.assigned_to" class="px-0">
                  <template #prepend>
                    <v-icon class="me-2" size="20">mdi-account</v-icon>
                  </template>
                  <v-list-item-title class="text-body-2">
                    <strong>Assigné à:</strong> ID {{ complaint.assigned_to }}
                  </v-list-item-title>
                </v-list-item>
              </v-list>
            </v-card-text>
          </v-card>

          <!-- Actions Card -->
          <v-card color="grey-lighten-4" elevation="0">
            <v-card-text class="pa-4">
              <div class="text-center">
                <v-icon class="mb-2" color="primary" size="32">
                  mdi-help-circle-outline
                </v-icon>
                <p class="text-body-2 font-weight-medium mb-3">
                  Besoin d'aide ?
                </p>
                <v-btn
                  block
                  color="primary"
                  prepend-icon="mdi-message"
                  size="small"
                  :to="{ path: '/clientb/support' }"
                  variant="tonal"
                >
                  Contacter le support
                </v-btn>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </div>

    <!-- Error State -->
    <v-card v-else color="error" elevation="0" variant="tonal">
      <v-card-text class="pa-12 text-center">
        <v-icon class="mb-4" color="error" size="64">
          mdi-alert-circle-outline
        </v-icon>
        <h2 class="text-h5 font-weight-bold mb-3">
          Réclamation introuvable
        </h2>
        <p class="text-body-1 mb-6">
          La réclamation demandée n'existe pas ou a été supprimée.
        </p>
        <v-btn
          color="primary"
          :to="{ path: '/clientb/complaints' }"
          variant="tonal"
        >
          Retour à mes réclamations
        </v-btn>
      </v-card-text>
    </v-card>

    <!-- Delete Confirmation Dialog -->
    <v-dialog v-model="deleteDialog" max-width="500">
      <v-card>
        <v-card-title class="text-h5 font-weight-bold pa-6">
          Confirmer la suppression
        </v-card-title>
        <v-card-text class="pa-6 pt-0">
          <p class="text-body-1">
            Êtes-vous sûr de vouloir supprimer cette réclamation ?
          </p>
          <p class="text-body-2 text-medium-emphasis mt-2">
            Cette action est irréversible.
          </p>
        </v-card-text>
        <v-card-actions class="pa-6 pt-0">
          <v-spacer />
          <v-btn
            :disabled="deleting"
            variant="text"
            @click="deleteDialog = false"
          >
            Annuler
          </v-btn>
          <v-btn
            color="error"
            :loading="deleting"
            variant="flat"
            @click="handleDelete"
          >
            Supprimer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </ClientBLayout>
</template>

<script setup lang="ts">
  import type { Complaint } from '@/services/complaintService'
  import { computed, onMounted, ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { useToast } from 'vue-toastification'
  import { complaintService } from '@/services/complaintService'
  import ClientBLayout from '../components/ClientBLayout.vue'

  const route = useRoute()
  const router = useRouter()
  const toast = useToast()

  // State
  const loading = ref(true)
  const deleting = ref(false)
  const complaint = ref<Complaint | null>(null)
  const deleteDialog = ref(false)

  // Computed
  const canEdit = computed(() => {
    return complaint.value?.status === 'pending'
  })

  // Priority helpers
  function getPriorityColor (priority: string) {
    const colors: Record<string, string> = {
      low: 'success',
      medium: 'warning',
      high: 'error',
      urgent: 'error',
    }
    return colors[priority] || 'grey'
  }

  function getPriorityLabel (priority: string) {
    const labels: Record<string, string> = {
      low: 'Basse',
      medium: 'Moyenne',
      high: 'Haute',
      urgent: 'Urgente',
    }
    return labels[priority] || priority
  }

  // Format date
  function formatDate (dateString: string | undefined) {
    if (!dateString) return 'N/A'
    try {
      const date = new Date(dateString)
      if (Number.isNaN(date.getTime())) return 'N/A'
      return date.toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
      })
    } catch {
      return 'N/A'
    }
  }

  function formatDateTime (dateString: string | undefined) {
    if (!dateString) return 'N/A'
    try {
      const date = new Date(dateString)
      if (Number.isNaN(date.getTime())) return 'N/A'
      return date.toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      })
    } catch {
      return 'N/A'
    }
  }

  // Load complaint
  async function loadComplaint () {
    try {
      loading.value = true
      const id = Number.parseInt(String((route.params as Record<string, unknown>).id ?? ''))
      complaint.value = await complaintService.getComplaint(id)
    } catch (error) {
      console.error('Error loading complaint:', error)
      toast.error('Erreur lors du chargement de la réclamation')
    } finally {
      loading.value = false
    }
  }

  // Actions
  function goBack () {
    router.push('/clientb/complaints')
  }

  function confirmDelete () {
    deleteDialog.value = true
  }

  async function handleDelete () {
    if (!complaint.value) return

    try {
      deleting.value = true
      await complaintService.deleteComplaint(complaint.value.id)
      toast.success('Réclamation supprimée avec succès')
      router.push('/clientb/complaints')
    } catch (error) {
      console.error('Error deleting complaint:', error)
      toast.error('Erreur lors de la suppression de la réclamation')
    } finally {
      deleting.value = false
      deleteDialog.value = false
    }
  }

  // Initialize
  onMounted(() => {
    loadComplaint()
  })
</script>

<style scoped>
.response-content {
  background-color: rgba(var(--v-theme-info), 0.05);
  border-left: 4px solid rgb(var(--v-theme-info));
  padding: 16px;
  border-radius: 4px;
}
</style>
