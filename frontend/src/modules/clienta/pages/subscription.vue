<template>
  <ClientALayout current-page="subscriptions">
    <div class="subscription-main">
      <v-container class="py-12">
        <div class="subscription-wrapper">
          <v-alert
            v-if="blockingMessage"
            class="mb-6 mx-auto"
            color="warning"
            max-width="900"
            rounded="xl"
            variant="tonal"
          >
            <v-alert-title>Action requise</v-alert-title>
            {{ blockingMessage }}
          </v-alert>

          <!-- Header -->
          <div class="text-center mb-10">
            <v-chip class="mb-4" color="primary" size="large" variant="tonal">
              <v-icon start>mdi-rocket-launch</v-icon>
              {{ isTrial ? 'Essai Gratuit' : 'Abonnement' }}
            </v-chip>
            <h1 class="text-h3 font-weight-bold mb-3" style="color: #1a202c;">
              Choisissez votre offre
            </h1>
            <p class="text-h6" style="color: #64748b;">
              {{ isTrial ? '🎁 Profitez de 90 jours d\'essai gratuit' : 'Sélectionnez les offres adaptées à vos besoins' }}
            </p>
          </div>

          <!-- Trial Alert -->
          <v-alert
            v-if="isTrial"
            class="mb-8 mx-auto"
            color="success"
            max-width="800"
            rounded="xl"
            variant="tonal"
          >
            <template #prepend>
              <v-icon size="32">mdi-gift-outline</v-icon>
            </template>
            <v-alert-title class="text-h6 mb-2">Période d'essai gratuite</v-alert-title>
            Vous bénéficiez de 90 jours d'essai gratuit. Aucun paiement ne sera effectué maintenant.
          </v-alert>

          <!-- Stepper Card -->
          <v-card class="stepper-card mx-auto" elevation="3" max-width="1200" rounded="xl">
            <v-stepper v-model="step" class="stepper-custom" elevation="0">
              <v-stepper-header class="px-6 py-4">
                <v-stepper-item color="primary" :complete="step > 1" title="Offres" :value="1">
                  <template #icon>
                    <v-icon>{{ step > 1 ? 'mdi-check' : 'mdi-package-variant' }}</v-icon>
                  </template>
                </v-stepper-item>
                <v-divider />
                <v-stepper-item color="primary" :complete="step > 2" title="Paiement" :value="2">
                  <template #icon>
                    <v-icon>{{ step > 2 ? 'mdi-check' : 'mdi-credit-card-outline' }}</v-icon>
                  </template>
                </v-stepper-item>
                <v-divider />
                <v-stepper-item color="success" title="Confirmation" :value="3">
                  <template #icon>
                    <v-icon>mdi-check-circle-outline</v-icon>
                  </template>
                </v-stepper-item>
              </v-stepper-header>

              <v-stepper-window>
                <!-- Step 1: Offers -->
                <v-stepper-window-item :value="1">
                  <v-card-text class="pa-8">
                    <v-row v-if="!loading" justify="center">
                      <v-col
                        v-for="offer in offers"
                        :key="offer.id"
                        cols="12"
                        md="4"
                        sm="6"
                      >
                        <OfferCard
                          :is-selected="selectedOffers.includes(offer.id)"
                          :offer="offer"
                          @select="toggleOffer(offer.id)"
                        />
                      </v-col>
                    </v-row>
                    <div v-else class="text-center pa-12">
                      <UnifiedLoader
                        description="Récupération du catalogue d'abonnement..."
                        title="Chargement des offres..."
                        variant="local"
                      />
                    </div>
                  </v-card-text>
                  <v-card-actions class="pa-8 pt-0">
                    <v-spacer />
                    <v-btn
                      color="primary"
                      :disabled="selectedOffers.length === 0"
                      elevation="2"
                      rounded="xl"
                      size="x-large"
                      @click="step = 2"
                    >
                      Continuer
                      <v-icon end>mdi-arrow-right</v-icon>
                    </v-btn>
                  </v-card-actions>
                </v-stepper-window-item>

                <!-- Step 2: Payment -->
                <v-stepper-window-item :value="2">
                  <v-card-text class="pa-8">
                    <v-row justify="center">
                      <v-col cols="12" md="7">
                        <PaymentMethodSelector
                          v-if="!isTrial"
                          v-model="paymentMethod"
                          @update:indicatif="indicatif = $event"
                          @update:phone="phoneNumber = $event"
                        />
                        <v-alert
                          v-else
                          class="pa-6"
                          color="info"
                          rounded="xl"
                          variant="tonal"
                        >
                          <template #prepend>
                            <v-icon size="32">mdi-information-outline</v-icon>
                          </template>
                          <v-alert-title class="text-h6 mb-2">Aucun paiement requis</v-alert-title>
                          Vous êtes en période d'essai gratuite. Cliquez sur "Confirmer" pour activer votre abonnement.
                        </v-alert>
                      </v-col>
                      <v-col cols="12" md="5">
                        <v-card class="summary-card" color="grey-lighten-5" elevation="0" rounded="xl">
                          <v-card-title class="pa-6 text-h5 font-weight-bold" style="color: #1a202c;">
                            Récapitulatif
                          </v-card-title>
                          <v-divider />
                          <v-card-text class="pa-6">
                            <div v-for="offerId in selectedOffers" :key="offerId" class="mb-4">
                              <div class="d-flex justify-space-between align-center">
                                <span class="text-body-1" style="color: #475569;">{{ getOfferName(offerId) }}</span>
                                <span class="text-h6 font-weight-bold" style="color: #1a202c;">{{ getOfferPrice(offerId) }} FCFA</span>
                              </div>
                            </div>
                            <v-divider class="my-4" />
                            <div class="d-flex justify-space-between align-center">
                              <span class="text-h5 font-weight-bold" style="color: #1a202c;">Total</span>
                              <span class="text-h4 font-weight-bold" style="color: #2563eb;">{{ totalPrice }} FCFA</span>
                            </div>
                            <div v-if="isTrial" class="text-center mt-4 pa-3" style="background: #dcfce7; border-radius: 12px;">
                              <v-icon color="success" size="24">mdi-gift</v-icon>
                              <span class="text-body-1 font-weight-medium ml-2" style="color: #16a34a;">Gratuit pendant 90 jours</span>
                            </div>
                          </v-card-text>
                        </v-card>
                      </v-col>
                    </v-row>
                  </v-card-text>
                  <v-card-actions class="pa-8 pt-0">
                    <v-btn rounded="xl" size="large" variant="outlined" @click="step = 1">
                      <v-icon start>mdi-arrow-left</v-icon>
                      Retour
                    </v-btn>
                    <v-spacer />
                    <v-btn
                      color="primary"
                      :disabled="!isTrial && !paymentMethod"
                      elevation="2"
                      :loading="submitting"
                      rounded="xl"
                      size="x-large"
                      @click="handleSubscribe"
                    >
                      {{ isTrial ? 'Confirmer' : 'Payer' }}
                      <v-icon end>{{ isTrial ? 'mdi-check' : 'mdi-credit-card' }}</v-icon>
                    </v-btn>
                  </v-card-actions>
                </v-stepper-window-item>

                <!-- Step 3: Confirmation -->
                <v-stepper-window-item :value="3">
                  <v-card-text class="pa-12 text-center">
                    <div class="success-animation mb-6">
                      <v-icon color="success" size="120">mdi-check-circle</v-icon>
                    </div>
                    <h2 class="text-h3 font-weight-bold mb-3" style="color: #1a202c;">
                      Souscription confirmée !
                    </h2>
                    <p class="text-h6 mb-8" style="color: #64748b;">
                      {{ isTrial ? 'Votre période d\'essai de 90 jours a démarré' : 'Votre paiement a été validé' }}
                    </p>
                    <v-btn
                      color="primary"
                      elevation="2"
                      rounded="xl"
                      size="x-large"
                      @click="goToDashboard"
                    >
                      Accéder au dashboard
                      <v-icon end>mdi-arrow-right</v-icon>
                    </v-btn>
                  </v-card-text>
                </v-stepper-window-item>
              </v-stepper-window>
            </v-stepper>
          </v-card>
        </div>
      </v-container>
    </div>

    <v-dialog
      v-model="paymentTrackingOpen"
      max-width="520"
      persistent
    >
      <v-card rounded="xl">
        <v-card-title class="d-flex align-center justify-space-between">
          <span>Paiement en cours</span>
          <v-chip :color="paymentTrackingColor" size="small" variant="tonal">
            {{ paymentTrackingStatusLabel }}
          </v-chip>
        </v-card-title>
        <v-card-text class="text-center py-8">
          <v-progress-circular
            :color="paymentTrackingColor"
            indeterminate
            size="72"
            width="7"
          />
          <div class="text-h6 mt-4 mb-2">{{ paymentTrackingTitle }}</div>
          <div class="text-body-2 text-medium-emphasis">
            {{ paymentTrackingMessage }}
          </div>
          <div class="text-caption text-medium-emphasis mt-3">
            Vérification {{ paymentTrackingAttempt }}/{{ paymentTrackingMaxAttempts }}
          </div>
        </v-card-text>
      </v-card>
    </v-dialog>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { useToast } from 'vue-toastification'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import UnifiedLoader from '@/modules/shared/components/ui/UnifiedLoader.vue'
  import { type Offer, subscriptionService } from '@/services/subscriptionService'
  import { getBlockingMessage } from '@/utils/blockingAccess'
  import OfferCard from '../components/subscription/OfferCard.vue'
  import PaymentMethodSelector from '../components/subscription/PaymentMethodSelector.vue'

  const router = useRouter()
  const route = useRoute()
  const toast = useToast()

  const step = ref(1)
  const loading = ref(false)
  const submitting = ref(false)
  const offers = ref<Offer[]>([])
  const selectedOffers = ref<number[]>([])
  const paymentMethod = ref('')
  const phoneNumber = ref('')
  const indicatif = ref('229')
  const isTrial = ref(false)
  const paymentReferenceId = ref('')
  const paymentTrackingOpen = ref(false)
  const paymentTrackingStatus = ref<'pending' | 'completed' | 'failed'>('pending')
  const paymentTrackingTitle = ref('Validation de votre paiement...')
  const paymentTrackingMessage = ref('Veuillez confirmer la transaction sur votre téléphone.')
  const paymentTrackingAttempt = ref(0)
  const paymentTrackingMaxAttempts = ref(20)
  const blockingMessage = computed(() => getBlockingMessage(
    typeof route.query.blocking === 'string' ? route.query.blocking : undefined,
  ))
  const paymentTrackingStatusLabel = computed(() => {
    if (paymentTrackingStatus.value === 'completed') return 'Confirmé'
    if (paymentTrackingStatus.value === 'failed') return 'Échoué'
    return 'En attente'
  })
  const paymentTrackingColor = computed(() => {
    if (paymentTrackingStatus.value === 'completed') return 'success'
    if (paymentTrackingStatus.value === 'failed') return 'error'
    return 'primary'
  })

  const totalPrice = computed(() => {
    return selectedOffers.value.reduce((sum, offerId) => {
      const offer = offers.value.find(o => o.id === offerId)
      return sum + (offer ? Number(offer.price) : 0)
    }, 0).toLocaleString('fr-FR')
  })

  function toggleOffer (offerId: number) {
    const index = selectedOffers.value.indexOf(offerId)
    if (index === -1) {
      selectedOffers.value.push(offerId)
    } else {
      selectedOffers.value.splice(index, 1)
    }
  }

  function getOfferName (offerId: number): string {
    return offers.value.find(o => o.id === offerId)?.name || ''
  }

  function getOfferPrice (offerId: number): string {
    const offer = offers.value.find(o => o.id === offerId)
    return offer ? Number(offer.price).toLocaleString('fr-FR') : '0'
  }

  async function handleSubscribe () {
    try {
      submitting.value = true
      const user = JSON.parse(localStorage.getItem('user') || '{}')
      const siteId = Number(localStorage.getItem('current_site_id') || user.site_id || 0)

      if (!siteId) {
        throw new Error('Aucun site actif sélectionné pour la souscription')
      }

      if (!isTrial.value && (!paymentMethod.value || !phoneNumber.value)) {
        throw new Error('Veuillez choisir un moyen de paiement et saisir votre numéro.')
      }

      const subscribeResult = await subscriptionService.subscribe({
        site_id: siteId,
        offer_ids: selectedOffers.value,
        payment_method: paymentMethod.value as any,
        phone_number: phoneNumber.value,
        is_trial: isTrial.value,
      })

      if (isTrial.value) {
        step.value = 3
        toast.success('Souscription d’essai activée !')
        return
      }

      const rawSubscriptions = Array.isArray(subscribeResult?.subscriptions?.data)
        ? subscribeResult.subscriptions.data
        : (Array.isArray(subscribeResult?.subscriptions)
          ? subscribeResult.subscriptions
          : [])

      const firstSubscription = rawSubscriptions[0]
      const subscriptionId = Number(firstSubscription?.id)
      if (!Number.isFinite(subscriptionId) || subscriptionId <= 0) {
        throw new Error('Souscription créée sans identifiant exploitable')
      }

      const paymentRequest = await subscriptionService.requestPayment({
        subscription_id: subscriptionId,
        payment_method: paymentMethod.value as any,
        phone_number: phoneNumber.value,
        indicatif: indicatif.value,
      })

      paymentReferenceId.value = String(paymentRequest?.data?.reference_id || '')
      if (!paymentReferenceId.value) {
        throw new Error('Référence de paiement manquante')
      }

      paymentTrackingStatus.value = 'pending'
      paymentTrackingTitle.value = 'Paiement initié'
      paymentTrackingMessage.value = 'Demande envoyée. Validation en cours...'
      paymentTrackingAttempt.value = 0
      paymentTrackingOpen.value = true

      await pollPaymentConfirmation(subscriptionId, paymentReferenceId.value)
      paymentTrackingStatus.value = 'completed'
      paymentTrackingTitle.value = 'Paiement confirmé'
      paymentTrackingMessage.value = 'Votre abonnement est maintenant actif.'
      await new Promise(resolve => setTimeout(resolve, 800))
      step.value = 3
      toast.success('Paiement confirmé. Souscription activée !')
    } catch (error: any) {
      paymentTrackingStatus.value = 'failed'
      paymentTrackingTitle.value = 'Paiement non confirmé'
      paymentTrackingMessage.value = error?.message || 'La transaction n’a pas pu être confirmée.'
      await new Promise(resolve => setTimeout(resolve, 800))

      const status = String(error?.paymentStatus || '').toLowerCase()
      if (status === 'timeout' || status === 'pending') {
        toast.warning(error?.message || 'Paiement en attente de confirmation.')
      } else {
        toast.error(error.response?.data?.message || error?.message || 'Erreur lors de la souscription')
      }
    } finally {
      paymentTrackingOpen.value = false
      submitting.value = false
    }
  }

  async function pollPaymentConfirmation (subscriptionId: number, referenceId: string) {
    const maxAttempts = 20
    const intervalMs = 3000
    paymentTrackingMaxAttempts.value = maxAttempts

    for (let attempt = 1; attempt <= maxAttempts; attempt += 1) {
      paymentTrackingAttempt.value = attempt
      const result = await subscriptionService.checkSubscriptionPayment({
        subscription_id: subscriptionId,
        reference_id: referenceId,
      })
      const status = String(result?.data?.payment_status || '').toLowerCase()

      if (status === 'completed') {
        return
      }
      if (status === 'failed') {
        const error = new Error('Le paiement a échoué. Veuillez réessayer.')
        ;(error as any).paymentStatus = 'failed'
        throw error
      }

      paymentTrackingMessage.value = 'Paiement en attente de confirmation... Vérifiez votre téléphone.'

      await new Promise(resolve => setTimeout(resolve, intervalMs))
    }

    const error = new Error('Paiement toujours en attente. Vérifiez sur votre téléphone puis réessayez.')
    ;(error as any).paymentStatus = 'timeout'
    throw error
  }

  async function goToDashboard () {
    try {
      // Attendre un peu pour que le backend finalise
      await new Promise(resolve => setTimeout(resolve, 500))

      // Rediriger sans reload
      await router.push('/company/dashboard')
    } catch (error) {
      console.error('Erreur redirection:', error)
      // Fallback avec reload si erreur
      window.location.href = '/company/dashboard'
    }
  }

  onMounted(async () => {
    try {
      loading.value = true
      offers.value = await subscriptionService.getOffers()
      const user = JSON.parse(localStorage.getItem('user') || '{}')
      const siteId = Number(localStorage.getItem('current_site_id') || user.site_id || 0)
      if (siteId) {
        const status = await subscriptionService.checkStatus(siteId)
        isTrial.value = !status.trialUsed
      }
    } catch {
      toast.error('Erreur lors du chargement des offres')
    } finally {
      loading.value = false
    }
  })
</script>

<style scoped>
.subscription-main {
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 50%, #f8fafc 100%);
  min-height: 100vh;
}

.subscription-wrapper {
  max-width: 1400px;
  margin: 0 auto;
}

.stepper-card {
  background: white;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
}

.stepper-custom :deep(.v-stepper-header) {
  box-shadow: none;
  border-bottom: 1px solid #e2e8f0;
}

.stepper-custom :deep(.v-stepper-item__avatar) {
  width: 48px;
  height: 48px;
  font-size: 20px;
}

.summary-card {
  border: 2px solid #e2e8f0;
  transition: all 0.3s ease;
}

.summary-card:hover {
  border-color: #cbd5e1;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
}

.success-animation {
  animation: scaleIn 0.5s ease-out;
}

@keyframes scaleIn {
  from {
    transform: scale(0);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}
</style>
