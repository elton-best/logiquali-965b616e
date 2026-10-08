declare global {
  interface Window {
    Stripe: any
  }
}

export const paymentService = {
  stripe: null as any,
  elements: null as any,

  /**
   * Initialize Stripe
   */
  async init (publishableKey: string) {
    if (!window.Stripe) {
      throw new Error('Stripe.js not loaded')
    }
    this.stripe = window.Stripe(publishableKey)
    return this.stripe
  },

  /**
   * Create Stripe Elements
   */
  createElements () {
    if (!this.stripe) {
      throw new Error('Stripe not initialized')
    }
    this.elements = this.stripe.elements()
    return this.elements
  },

  /**
   * Create payment method from card element
   */
  async createPaymentMethod (cardElement: any, billingDetails: any): Promise<{ token: string, error?: any }> {
    if (!this.stripe) {
      throw new Error('Stripe not initialized')
    }

    try {
      const { paymentMethod, error } = await this.stripe.createPaymentMethod({
        type: 'card',
        card: cardElement,
        billing_details: billingDetails,
      })

      if (error) {
        return { token: '', error }
      }

      return { token: paymentMethod.id }
    } catch (error) {
      return { token: '', error }
    }
  },

  /**
   * Load Stripe.js script
   */
  loadStripeScript (): Promise<void> {
    return new Promise((resolve, reject) => {
      if (window.Stripe) {
        resolve()
        return
      }

      const script = document.createElement('script')
      script.src = 'https://js.stripe.com/v3/'
      script.addEventListener('load', () => resolve())
      script.addEventListener('error', () => reject(new Error('Failed to load Stripe.js')))
      document.head.append(script)
    })
  },
}
