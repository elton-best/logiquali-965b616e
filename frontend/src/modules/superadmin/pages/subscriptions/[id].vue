<template>
  <SuperAdminLayout current-page="subscriptions">
    <v-container class="pa-6" fluid>
      <v-row class="mb-6">
        <v-col cols="12">
          <div class="d-flex align-center justify-space-between flex-wrap ga-4">
            <div class="d-flex align-center">
              <v-btn class="mr-4" icon variant="text" @click="router.push('/superadmin/subscriptions')">
                <v-icon>mdi-arrow-left</v-icon>
              </v-btn>

              <v-avatar class="mr-4" color="primary" size="56" variant="tonal">
                <v-icon size="32">mdi-credit-card-check-outline</v-icon>
              </v-avatar>

              <div>
                <h1 class="text-h4 font-weight-bold text-primary mb-1">
                  {{ enterpriseName }}
                </h1>
                <p class="text-subtitle-1 text-grey-darken-1">
                  {{ siteName }} • {{ offerName }}
                </p>
              </div>
            </div>

            <v-chip :color="getStatusColor(subscription?.status)" size="small" variant="tonal">
              <v-icon start>{{ getStatusIcon(subscription?.status) }}</v-icon>
              {{ getStatusLabel(subscription?.status) }}
            </v-chip>
          </div>
        </v-col>
      </v-row>

      <v-skeleton-loader v-if="loading" type="article, card, card" />

      <v-alert
        v-else-if="!subscription"
        class="mb-6"
        type="error"
        variant="tonal"
      >
        Impossible de charger cet abonnement.
      </v-alert>

      <template v-else>
        <v-row>
          <v-col cols="12" md="7">
            <v-card class="mb-6" elevation="2">
              <v-card-title class="text-h6">Détails de l'abonnement</v-card-title>
              <v-divider />
              <v-card-text class="pa-6">
                <v-row>
                  <v-col cols="12" sm="6">
                    <p class="text-caption text-medium-emphasis mb-1">Entreprise</p>
                    <p class="text-body-1 font-weight-medium">{{ enterpriseName }}</p>
                  </v-col>
                  <v-col cols="12" sm="6">
                    <p class="text-caption text-medium-emphasis mb-1">Site</p>
                    <p class="text-body-1 font-weight-medium">{{ siteName }}</p>
                  </v-col>
                  <v-col cols="12" sm="6">
                    <p class="text-caption text-medium-emphasis mb-1">Offre</p>
                    <p class="text-body-1 font-weight-medium">{{ offerName }}</p>
                  </v-col>
                  <v-col cols="12" sm="6">
                    <p class="text-caption text-medium-emphasis mb-1">Prix</p>
                    <p class="text-body-1 font-weight-medium">{{ formatPrice(subscription.offer?.price) }}</p>
                  </v-col>
                  <v-col cols="12" sm="6">
                    <p class="text-caption text-medium-emphasis mb-1">Date de début</p>
                    <p class="text-body-1 font-weight-medium">{{ formatDate(subscription.start_date) }}</p>
                  </v-col>
                  <v-col cols="12" sm="6">
                    <p class="text-caption text-medium-emphasis mb-1">Date de fin</p>
                    <p class="text-body-1 font-weight-medium">{{ formatDate(endDate) }}</p>
                  </v-col>
                  <v-col cols="12" sm="6">
                    <p class="text-caption text-medium-emphasis mb-1">Temps restant</p>
                    <v-chip :color="daysRemainingColor" size="small" variant="tonal">
                      {{ daysRemainingLabel }}
                    </v-chip>
                  </v-col>
                  <v-col cols="12" sm="6">
                    <p class="text-caption text-medium-emphasis mb-1">Statut</p>
                    <v-chip :color="getStatusColor(subscription.status)" size="small" variant="tonal">
                      {{ getStatusLabel(subscription.status) }}
                    </v-chip>
                  </v-col>
                </v-row>
              </v-card-text>
            </v-card>

            <v-card elevation="2">
              <v-card-title class="text-h6">Normes couvertes</v-card-title>
              <v-divider />
              <v-card-text class="pa-6">
                <div v-if="(subscription.offer?.norms || []).length === 0" class="text-medium-emphasis">
                  Aucune norme rattachée à cette offre.
                </div>
                <div v-else class="d-flex flex-wrap ga-2">
                  <v-chip
                    v-for="norm in subscription.offer?.norms"
                    :key="norm.id"
                    color="primary"
                    size="small"
                    variant="tonal"
                  >
                    {{ norm.code }} - {{ norm.name }}
                  </v-chip>
                </div>
              </v-card-text>
            </v-card>
          </v-col>

          <v-col cols="12" md="5">
            <v-card class="mb-6" elevation="2">
              <v-card-title class="text-h6">Actions</v-card-title>
              <v-divider />
              <v-card-text class="pa-6">
                <v-btn
                  v-if="subscription.status === 'active'"
                  block
                  class="mb-3"
                  color="warning"
                  prepend-icon="mdi-pause-circle-outline"
                  @click="openSuspendDialog"
                >
                  Suspendre l'abonnement
                </v-btn>

                <v-btn
                  v-if="subscription.status === 'suspended'"
                  block
                  class="mb-3"
                  color="success"
                  prepend-icon="mdi-check-circle-outline"
                  @click="openReactivateDialog"
                >
                  Réactiver l'abonnement
                </v-btn>

                <v-btn
                  block
                  color="error"
                  prepend-icon="mdi-delete-outline"
                  variant="outlined"
                  @click="deleteDialog = true"
                >
                  Supprimer l'abonnement
                </v-btn>
              </v-card-text>
            </v-card>

            <v-alert type="info" variant="tonal">
              Les actions de renouvellement, changement de plan et historique de paiements ne sont pas encore disponibles côté API.
            </v-alert>
          </v-col>
        </v-row>
      </template>
    </v-container>

    <StatusDialog
      v-model="suspendDialog"
      v-model:reason="suspendReason"
      confirm-color="warning"
      confirm-label="Suspendre"
      icon="mdi-pause-circle-outline"
      :loading="submitting"
      :message="`Confirmez la suspension de <strong>${enterpriseName}</strong>.`"
      :reason-error="suspendReasonError"
      reason-label="Raison de la suspension *"
      reason-placeholder="Expliquez pourquoi cet abonnement est suspendu..."
      show-reason
      title="Suspendre l'abonnement"
      @cancel="suspendDialog = false"
      @confirm="handleSuspend"
    />

    <StatusDialog
      v-model="reactivateDialog"
      confirm-color="success"
      confirm-label="Réactiver"
      icon="mdi-check-circle-outline"
      :loading="submitting"
      :message="`Confirmez la réactivation de <strong>${enterpriseName}</strong>.`"
      title="Réactiver l'abonnement"
      @cancel="reactivateDialog = false"
      @confirm="handleReactivate"
    />

    <ConfirmDialog
      v-model="deleteDialog"
      confirm-label="Supprimer"
      impact="Impact: l'abonnement sera supprime et ne pourra pas etre reactive."
      :loading="submitting"
      message="Etes-vous sur de vouloir supprimer cet abonnement ?"
      title="Supprimer l'abonnement"
      @cancel="deleteDialog = false"
      @confirm="handleDelete"
    />
  </SuperAdminLayout>
</template>

<script setup lang="ts">
  import type { Enterprise, Site, Subscription } from '@/types/api'
  import { computed, onMounted, ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { useToast } from '@/composables/useToast'
  import { useActionLock } from '@/modules/shared/composables/useActionLock'
  import ConfirmDialog from '@/modules/superadmin/components/ConfirmDialog.vue'
  import StatusDialog from '@/modules/superadmin/components/StatusDialog.vue'
  import SuperAdminLayout from '@/modules/superadmin/components/SuperAdminLayout.vue'
  import superAdminService from '@/services/superAdminService'

  const route = useRoute()
  const router = useRouter()
  const toast = useToast()
  const { run: runLocked } = useActionLock()

  const loading = ref(true)
  const submitting = ref(false)
  const subscription = ref<Subscription | null>(null)

  const suspendDialog = ref(false)
  const reactivateDialog = ref(false)
  const deleteDialog = ref(false)
  const suspendReason = ref('')
  const suspendReasonError = ref('')

  const subscriptionId = computed(() => {
    const params = route.params as Record<string, string | string[] | undefined>
    const routeId = params.id
    const normalizedId = Array.isArray(routeId) ? routeId[0] : routeId
    if (!normalizedId) return null

    const parsedId = Number(normalizedId)
    return Number.isFinite(parsedId) ? parsedId : null
  })

  const typedSite = computed(() => {
    return (subscription.value?.site || null) as (Site & { enterprise?: Enterprise }) | null
  })

  const enterpriseName = computed(() => typedSite.value?.enterprise?.name || 'Entreprise non renseignée')
  const siteName = computed(() => subscription.value?.site?.name || 'Site non renseigné')
  const offerName = computed(() => subscription.value?.offer?.name || 'Offre non renseignée')
  const endDate = computed(() => subscription.value?.expiration_date || subscription.value?.end_date || '')

  const daysRemaining = computed(() => {
    if (!endDate.value) return null

    const end = new Date(endDate.value)
    if (Number.isNaN(end.getTime())) return null

    const now = new Date()
    return Math.ceil((end.getTime() - now.getTime()) / (1000 * 60 * 60 * 24))
  })

  const daysRemainingLabel = computed(() => {
    if (daysRemaining.value === null) return 'N/A'
    if (daysRemaining.value < 0) return 'Expiré'
    if (daysRemaining.value === 0) return 'Expire aujourd\'hui'
    return `${daysRemaining.value} jour${daysRemaining.value > 1 ? 's' : ''}`
  })

  const daysRemainingColor = computed(() => {
    if (daysRemaining.value === null) return 'default'
    if (daysRemaining.value < 0) return 'error'
    if (daysRemaining.value <= 7) return 'warning'
    if (daysRemaining.value <= 30) return 'info'
    return 'success'
  })

  onMounted(async () => {
    await loadSubscription()
  })

  async function loadSubscription () {
    if (!subscriptionId.value) {
      toast.error('Identifiant d\'abonnement invalide.')
      router.push('/superadmin/subscriptions')
      return
    }

    loading.value = true
    try {
      subscription.value = await superAdminService.getSubscription(subscriptionId.value)
    } catch (error: any) {
      toast.error(error?.response?.data?.message || 'Impossible de charger le détail de cet abonnement.')
      subscription.value = null
    } finally {
      loading.value = false
    }
  }

  function openSuspendDialog () {
    suspendReason.value = ''
    suspendReasonError.value = ''
    suspendDialog.value = true
  }

  function openReactivateDialog () {
    reactivateDialog.value = true
  }

  async function handleSuspend () {
    if (!subscription.value) return

    if (!suspendReason.value || suspendReason.value.length < 10) {
      suspendReasonError.value = 'La raison doit contenir au moins 10 caractères'
      return
    }

    await runLocked('subscription-detail-suspend', async () => {
      submitting.value = true
      try {
        await superAdminService.suspendSubscription(subscription.value!.id, suspendReason.value)
        toast.success('Abonnement suspendu.')
        suspendDialog.value = false
        await loadSubscription()
      } catch (error: any) {
        toast.error(error?.response?.data?.message || 'Suspension impossible pour cet abonnement.')
      } finally {
        submitting.value = false
      }
    })
  }

  async function handleReactivate () {
    if (!subscription.value) return

    await runLocked('subscription-detail-reactivate', async () => {
      submitting.value = true
      try {
        await superAdminService.reactivateSubscription(subscription.value!.id)
        toast.success('Abonnement réactivé.')
        reactivateDialog.value = false
        await loadSubscription()
      } catch (error: any) {
        toast.error(error?.response?.data?.message || 'Réactivation impossible pour cet abonnement.')
      } finally {
        submitting.value = false
      }
    })
  }

  async function handleDelete () {
    if (!subscription.value) return

    await runLocked('subscription-detail-delete', async () => {
      submitting.value = true
      try {
        await superAdminService.deleteSubscription(subscription.value!.id)
        toast.success('Abonnement supprimé.')
        deleteDialog.value = false
        router.push('/superadmin/subscriptions')
      } catch (error: any) {
        toast.error(error?.response?.data?.message || 'Suppression impossible pour cet abonnement.')
      } finally {
        submitting.value = false
      }
    })
  }

  function getStatusColor (status?: string): string {
    const colors: Record<string, string> = {
      active: 'success',
      suspended: 'warning',
      expired: 'error',
      cancelled: 'error',
    }
    return status ? (colors[status] || 'default') : 'default'
  }

  function getStatusLabel (status?: string): string {
    const labels: Record<string, string> = {
      active: 'Actif',
      suspended: 'Suspendu',
      expired: 'Expiré',
      cancelled: 'Annulé',
    }
    return status ? (labels[status] || status) : 'N/A'
  }

  function getStatusIcon (status?: string): string {
    const icons: Record<string, string> = {
      active: 'mdi-check-circle',
      suspended: 'mdi-pause-circle',
      expired: 'mdi-alert-circle',
      cancelled: 'mdi-close-circle',
    }
    return status ? (icons[status] || 'mdi-help-circle') : 'mdi-help-circle'
  }

  function formatDate (date?: string): string {
    if (!date) return 'N/A'

    const parsed = new Date(date)
    if (Number.isNaN(parsed.getTime())) return 'N/A'

    return parsed.toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'long',
      year: 'numeric',
    })
  }

  function formatPrice (price?: number): string {
    if (!price) return 'N/A'

    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: 'XOF',
      minimumFractionDigits: 0,
    }).format(price)
  }
</script>
