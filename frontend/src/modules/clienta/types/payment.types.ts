export interface PaymentMethod {
  id: string
  type: 'card' | 'bank_account'
  card?: CardDetails
  billing_details: BillingDetails
}

export interface CardDetails {
  brand: string
  last4: string
  exp_month: number
  exp_year: number
}

export interface BillingDetails {
  name: string
  email?: string
  phone?: string
  address: Address
}

export interface Address {
  line1: string
  line2?: string
  city: string
  state?: string
  postal_code: string
  country: string
}

export interface StripeElements {
  cardNumber: any
  cardExpiry: any
  cardCvc: any
}

export interface PaymentError {
  type: string
  code: string
  message: string
}
