import { ref } from 'vue'
import { paymentService } from '@/services/paymentService'

export function usePayment () {
  const loading = ref(false)
  const error = ref<string | null>(null)
  const stripe = ref<any>(null)
  const elements = ref<any>(null)
  const cardElement = ref<any>(null)

  const initStripe = async (publishableKey: string) => {
    loading.value = true
    error.value = null

    try {
      await paymentService.loadStripeScript()
      stripe.value = await paymentService.init(publishableKey)
      elements.value = paymentService.createElements()
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de l\'initialisation de Stripe'
      console.error('Stripe initialization error:', error_)
    } finally {
      loading.value = false
    }
  }

  const createCardElement = (elementId: string) => {
    if (!elements.value) {
      throw new Error('Stripe Elements not initialized')
    }

    const style = {
      base: {
        'fontSize': '16px',
        'color': '#32325d',
        'fontFamily': '"Roboto", sans-serif',
        '::placeholder': {
          color: '#aab7c4',
        },
      },
      invalid: {
        color: '#fa755a',
        iconColor: '#fa755a',
      },
    }

    cardElement.value = elements.value.create('card', { style })
    cardElement.value.mount(`#${elementId}`)

    return cardElement.value
  }

  const createPaymentMethod = async (billingDetails: any) => {
    if (!cardElement.value) {
      throw new Error('Card element not created')
    }

    loading.value = true
    error.value = null

    try {
      const result = await paymentService.createPaymentMethod(cardElement.value, billingDetails)

      if (result.error) {
        error.value = result.error.message
        throw new Error(result.error.message)
      }

      return result.token
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la création du moyen de paiement'
      throw error_
    } finally {
      loading.value = false
    }
  }

  return {
    loading,
    error,
    stripe,
    cardElement,
    initStripe,
    createCardElement,
    createPaymentMethod,
  }
}
