<template>
  <v-dialog
    v-model="show"
    max-width="1000"
    persistent
    scrollable
  >
    <v-card>
      <v-card-title class="d-flex align-center justify-space-between bg-primary text-white">
        <div class="d-flex align-center gap-2">
          <v-icon>mdi-card-account-details</v-icon>
          <span>Gérer l'abonnement - {{ site?.name }}</span>
        </div>
        <v-btn
          icon="mdi-close"
          variant="text"
          @click="closeDialog"
        />
      </v-card-title>

      <v-card-text class="pa-6">
        <!-- Current Subscription -->
        <v-card
          v-if="currentSubscription"
          class="mb-6"
          :color="currentSubscription.is_active ? 'success' : 'warning'"
          variant="tonal"
        >
          <v-card-title>Abonnement actuel</v-card-title>
          <v-card-text>
            <v-row>
              <v-col cols="12" md="4">
                <div class="text-caption text-medium-emphasis">Offre</div>
                <div class="text-h6">{{ currentSubscription.offer?.name }}</div>
              </v-col>
              <v-col cols="12" md="4">
                <div class="text-caption text-medium-emphasis">Date d'expiration</div>
                <div class="text-body-1">{{ formatDate(currentSubscription.expiration_date) }}</div>
              </v-col>
              <v-col cols="12" md="4">
                <div class="text-caption text-medium-emphasis">Statut</div>
                <v-chip :color="currentSubscription.is_active ? 'success' : 'error'" size="small">
                  {{ currentSubscription.is_active ? 'Actif' : 'Expiré' }}
                </v-chip>
              </v-col>
            </v-row>
          </v-card-text>
          <v-card-actions>
            <v-btn
              color="primary"
              prepend-icon="mdi-refresh"
              variant="outlined"
              @click="renewCurrentSubscription"
            >
              Renouveler
            </v-btn>
          </v-card-actions>
        </v-card>

        <!-- No Subscription -->
        <v-alert
          v-else
          class="mb-6"
          color="warning"
          icon="mdi-alert"
          variant="tonal"
        >
          Ce site n'a pas d'abonnement actif. Choisissez une offre ci-dessous.
        </v-alert>

        <!-- Available Offers -->
        <h3 class="text-h6 mb-4">Offres disponibles</h3>

        <v-progress-circular
          v-if="loadingOffers"
          class="d-block mx-auto my-8"
          color="primary"
          indeterminate
        />

        <v-row v-else>
          <v-col
            v-for="offer in offers"
            :key="offer.id"
            cols="12"
            md="4"
          >
            <v-card
              class="h-100"
              :class="{'border-primary': selectedOfferIds.includes(offer.id)}"
              hover
              :variant="selectedOfferIds.includes(offer.id) ? 'outlined' : 'elevated'"
              @click="toggleOffer(offer.id)"
            >
              <v-card-title>{{ offer.name }}</v-card-title>
              <v-card-subtitle v-if="offer.description">
                {{ offer.description }}
              </v-card-subtitle>
              <v-card-text>
                <div class="text-h4 text-primary font-weight-bold">
                  {{ formatPrice(offer.price) }} FCFA
                </div>
                <div class="text-caption text-medium-emphasis">
                  Pour {{ offer.duration_months }} mois
                </div>
              </v-card-text>
              <v-card-actions>
                <v-btn
                  block
                  :color="selectedOfferIds.includes(offer.id) ? 'primary' : 'default'"
                  :variant="selectedOfferIds.includes(offer.id) ? 'flat' : 'outlined'"
                >
                  {{ selectedOfferIds.includes(offer.id) ? 'Sélectionné' : 'Sélectionner' }}
                </v-btn>
              </v-card-actions>
            </v-card>
          </v-col>
        </v-row>

        <!-- Payment Form -->
        <v-expand-transition>
          <v-card
            v-if="selectedOfferIds.length > 0 && !processingPayment"
            class="mt-6"
            variant="outlined"
          >
            <v-card-title class="bg-primary-darken-1 text-white">
              <v-icon start>mdi-credit-card</v-icon>
              Paiement de l'abonnement
            </v-card-title>
            <v-card-text class="pa-6">
              <v-alert
                class="mb-4"
                color="info"
                icon="mdi-information"
                variant="tonal"
              >
                <strong>Mode simulation :</strong> Aucun paiement réel ne sera effectué.
              </v-alert>

              <v-form ref="paymentForm">
                <v-select
                  v-model="paymentMethod"
                  item-title="label"
                  item-value="value"
                  :items="paymentMethods"
                  label="Méthode de paiement"
                  prepend-inner-icon="mdi-credit-card-outline"
                  variant="outlined"
                />

                <v-text-field
                  v-if="['mtn_momo', 'moov_money', 'coris_money', 'yas_money'].includes(paymentMethod)"
                  v-model="phoneNumber"
                  label="Numéro de téléphone"
                  placeholder="+229 XX XX XX XX"
                  prepend-inner-icon="mdi-phone"
                  type="tel"
                  variant="outlined"
                />

                <v-select
                  v-if="['mtn_momo', 'moov_money', 'coris_money', 'yas_money'].includes(paymentMethod)"
                  v-model="indicatif"
                  item-title="label"
                  item-value="value"
                  :items="indicatifOptions"
                  label="Indicatif"
                  prepend-inner-icon="mdi-flag-outline"
                  variant="outlined"
                />

                <v-text-field
                  v-model="transactionId"
                  hint="Généré automatiquement en mode simulation"
                  label="ID de transaction (optionnel)"
                  persistent-hint
                  prepend-inner-icon="mdi-identifier"
                  readonly
                  variant="outlined"
                />

                <v-divider class="my-4" />

                <div
                  v-for="offerId in selectedOfferIds"
                  :key="offerId"
                  class="d-flex justify-space-between align-center mb-2"
                >
                  <span class="text-body-2">{{ getOfferName(offerId) }}</span>
                  <span class="text-body-1 font-weight-medium">
                    {{ formatPrice(getOfferPrice(offerId)) }} FCFA
                  </span>
                </div>

                <v-divider class="my-4" />

                <div class="d-flex justify-space-between align-center">
                  <span class="text-h6">Total à payer:</span>
                  <span class="text-h5 text-primary font-weight-bold">
                    {{ formatPrice(totalPrice) }} FCFA
                  </span>
                </div>
              </v-form>
            </v-card-text>
          </v-card>
        </v-expand-transition>

        <!-- Processing Payment -->
        <v-card
          v-if="processingPayment"
          class="mt-6 text-center pa-8"
          variant="outlined"
        >
          <v-progress-circular
            class="mb-4"
            color="primary"
            indeterminate
            size="64"
          />
          <h3 class="text-h6 mb-2">Traitement du paiement...</h3>
          <p class="text-medium-emphasis">Veuillez patienter</p>
        </v-card>
      </v-card-text>

      <v-card-actions class="px-6 pb-6">
        <v-spacer />
        <v-btn
          variant="outlined"
          @click="closeDialog"
        >
          Annuler
        </v-btn>
        <v-btn
          color="primary"
          :disabled="selectedOfferIds.length === 0 || processingPayment"
          :loading="processingPayment"
          prepend-icon="mdi-check"
          @click="processPayment"
        >
          {{ currentSubscription ? 'Changer d\'abonnement' : 'S\'abonner' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

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
</template>

<script setup lang="ts">
  import { computed, onMounted, ref, watch } from 'vue'
  import { useToast } from '@/composables/useToast'
  import { type Offer, type Subscription, subscriptionService } from '@/services/subscriptionService'

  interface Props {
    modelValue: boolean
    site: any
    currentSubscription?: Subscription | null
  }

  const props = defineProps<Props>()
  const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'subscribed'): void
  }>()

  const toast = useToast()

  const show = computed({
    get: () => props.modelValue,
    set: value => emit('update:modelValue', value),
  })

  const offers = ref<Offer[]>([])
  const loadingOffers = ref(false)
  const selectedOfferIds = ref<number[]>([])
  const processingPayment = ref(false)
  const paymentTrackingOpen = ref(false)
  const paymentTrackingStatus = ref<'pending' | 'completed' | 'failed'>('pending')
  const paymentTrackingTitle = ref('Validation de votre paiement...')
  const paymentTrackingMessage = ref('Veuillez confirmer la transaction sur votre téléphone.')
  const paymentTrackingAttempt = ref(0)
  const paymentTrackingMaxAttempts = ref(20)

  const paymentMethod = ref('mtn_momo')
  const indicatif = ref('229')

  // Mobile Money fields
  const phoneNumber = ref('')

  const transactionId = ref('')

  const paymentMethods = [
    { label: 'MTN Mobile Money', value: 'mtn_momo' },
    { label: 'Moov Money', value: 'moov_money' },
    { label: 'Coris Money', value: 'coris_money' },
    { label: 'YAS Money', value: 'yas_money' },
  ]
  const indicatifOptions = [
    { label: '+229 (Bénin)', value: '229' },
    { label: '+228 (Togo)', value: '228' },
  ]
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

  watch(() => props.modelValue, newVal => {
    if (newVal) {
      loadOffers()
      generateTransactionId()
    }
  })

  async function loadOffers () {
    loadingOffers.value = true
    try {
      offers.value = await subscriptionService.getOffers()
    } catch (error: any) {
      console.error('Error loading offers:', error)
      toast.error(error.message || 'Erreur lors du chargement des offres')
    } finally {
      loadingOffers.value = false
    }
  }

  function toggleOffer (offerId: number) {
    if (selectedOfferIds.value.includes(offerId)) {
      selectedOfferIds.value = selectedOfferIds.value.filter(id => id !== offerId)
      return
    }

    selectedOfferIds.value.push(offerId)
  }

  function getOfferById (offerId: number): Offer | undefined {
    return offers.value.find(offer => offer.id === offerId)
  }

  function getOfferName (offerId: number): string {
    return getOfferById(offerId)?.name || 'Offre'
  }

  function getOfferPrice (offerId: number): number {
    return Number(getOfferById(offerId)?.price || 0)
  }

  const totalPrice = computed(() => selectedOfferIds.value
    .reduce((sum, offerId) => sum + getOfferPrice(offerId), 0))

  function generateTransactionId () {
    const timestamp = Date.now()
    const random = Math.floor(Math.random() * 10_000).toString().padStart(4, '0')
    transactionId.value = `TXN-${timestamp}-${random}`
  }

  async function processPayment () {
    if (selectedOfferIds.value.length === 0 || !props.site) return
    if (!phoneNumber.value) {
      toast.error('Veuillez renseigner le numéro de téléphone pour le paiement.')
      return
    }

    processingPayment.value = true
    try {
      const subscribeResult = await subscriptionService.subscribe({
        site_id: props.site.id,
        offer_ids: selectedOfferIds.value,
        payment_method: (paymentMethod.value || 'mtn_momo') as any,
        phone_number: phoneNumber.value || undefined,
      })

      const rawSubscriptions = Array.isArray(subscribeResult?.subscriptions?.data)
        ? subscribeResult.subscriptions.data
        : (Array.isArray(subscribeResult?.subscriptions)
          ? subscribeResult.subscriptions
          : [])

      const firstSubscription = rawSubscriptions[0]
      const subscriptionId = Number(firstSubscription?.id)
      if (!Number.isFinite(subscriptionId) || subscriptionId <= 0) {
        throw new Error('Souscription créée sans identifiant valide')
      }

      const paymentRequest = await subscriptionService.requestPayment({
        subscription_id: subscriptionId,
        payment_method: (paymentMethod.value || 'mtn_momo') as any,
        phone_number: phoneNumber.value || '',
        indicatif: indicatif.value,
      })

      const referenceId = String(paymentRequest?.data?.reference_id || '')
      if (!referenceId) {
        throw new Error('Référence de paiement manquante')
      }

      paymentTrackingStatus.value = 'pending'
      paymentTrackingTitle.value = 'Paiement initié'
      paymentTrackingMessage.value = 'Demande envoyée. Validation en cours...'
      paymentTrackingAttempt.value = 0
      paymentTrackingOpen.value = true

      await pollPaymentConfirmation(subscriptionId, referenceId)
      paymentTrackingStatus.value = 'completed'
      paymentTrackingTitle.value = 'Paiement confirmé'
      paymentTrackingMessage.value = 'Votre abonnement est maintenant actif.'
      await new Promise(resolve => setTimeout(resolve, 800))

      localStorage.setItem('current_site_id', String(props.site.id))
      localStorage.removeItem('cached_sub_modules')
      localStorage.removeItem('cached_sub_modules_timestamp')
      localStorage.removeItem('cached_sections')
      localStorage.removeItem('cached_sections_timestamp')
      window.dispatchEvent(new CustomEvent('company-site-changed', {
        detail: { siteId: props.site.id },
      }))
      window.dispatchEvent(new CustomEvent('company-access-changed', {
        detail: { reason: 'subscription-changed', siteId: props.site.id },
      }))

      toast.success('Abonnement souscrit avec succès !')
      emit('subscribed')
      closeDialog()
    } catch (error: any) {
      paymentTrackingStatus.value = 'failed'
      paymentTrackingTitle.value = 'Paiement non confirmé'
      paymentTrackingMessage.value = error?.message || 'La transaction n’a pas pu être confirmée.'
      await new Promise(resolve => setTimeout(resolve, 800))
      console.error('Subscription error:', error)
      const status = String(error?.paymentStatus || '').toLowerCase()
      if (status === 'timeout' || status === 'pending') {
        toast.warning(error?.message || 'Paiement en attente de confirmation.')
      } else {
        toast.error(error.response?.data?.message || error.message || 'Erreur lors de la souscription')
      }
    } finally {
      paymentTrackingOpen.value = false
      processingPayment.value = false
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
        const error = new Error('Le paiement a échoué.')
        ;(error as any).paymentStatus = 'failed'
        throw error
      }

      paymentTrackingMessage.value = 'Paiement en attente de confirmation... Vérifiez votre téléphone.'

      await new Promise(resolve => setTimeout(resolve, intervalMs))
    }

    const error = new Error('Paiement en attente de confirmation.')
    ;(error as any).paymentStatus = 'timeout'
    throw error
  }

  async function renewCurrentSubscription () {
    if (!props.currentSubscription) return

    processingPayment.value = true
    try {
      await subscriptionService.renewSubscription(props.currentSubscription.id)
      if (props.site?.id) {
        localStorage.setItem('current_site_id', String(props.site.id))
        window.dispatchEvent(new CustomEvent('company-site-changed', {
          detail: { siteId: props.site.id },
        }))
      }
      localStorage.removeItem('cached_sub_modules')
      localStorage.removeItem('cached_sub_modules_timestamp')
      localStorage.removeItem('cached_sections')
      localStorage.removeItem('cached_sections_timestamp')
      window.dispatchEvent(new CustomEvent('company-access-changed', {
        detail: { reason: 'subscription-renewed', siteId: props.site?.id },
      }))
      toast.success('Abonnement renouvelé avec succès !')
      emit('subscribed')
      closeDialog()
    } catch (error: any) {
      toast.error(error.message || 'Erreur lors du renouvellement')
    } finally {
      processingPayment.value = false
    }
  }

  function formatPrice (price: number | string) {
    const numeric = typeof price === 'string' ? Number(price) : price
    return new Intl.NumberFormat('fr-FR').format(Number.isFinite(numeric) ? numeric : 0)
  }

  function formatDate (date: string) {
    return new Date(date).toLocaleDateString('fr-FR')
  }

  function closeDialog () {
    show.value = false
    selectedOfferIds.value = []
  }

  onMounted(() => {
    if (props.modelValue) {
      loadOffers()
      generateTransactionId()
    }
  })
</script>
