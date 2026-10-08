export interface Module {
  id: number
  identifier: string
  name: string
  description?: string
  category: 'qualite' | 'environnement' | 'sst' | 'support'
  route: string
  icon: string
  order: number
  is_active: boolean
}

export interface SubModule {
  id: number
  module_id: number
  module_name: string
  module_code: string
  name: string
  code: string
  description?: string
  icon: string
  route: string
  order: number
  is_active: boolean
  has_permission?: boolean
  permissions?: Record<string, boolean>
}

export interface SubModuleSection {
  id: number
  sub_module_id: number
  sub_module_code: string
  sub_module_name: string
  module_code: string
  module_name: string
  name: string
  code: string
  description?: string
  icon: string
  route: string
  order: number
  is_active: boolean
  has_permission?: boolean
  permissions?: Record<string, boolean>
}

export interface SubModuleWithSections extends SubModule {
  sections: SubModuleSection[]
}

export interface ModuleWithPermissions extends Module {
  permissions: {
    read: boolean
    create: boolean
    update: boolean
    delete: boolean
    validate: boolean
  }
}

export interface Subscription {
  id: number
  site_id: number
  offer_id: number
  start_date: string
  expiration_date: string
  is_active: boolean
  is_trial: boolean
  trial_ends_at: string | null
  status: 'trial' | 'active' | 'expired' | 'cancelled'
  payment_status: 'pending' | 'completed' | 'failed' | null
  offer?: {
    id: number
    name: string
    price: number
    duration_months: number
    norms: Array<{
      id: number
      code: string
      name: string
    }>
  }
}

export interface SubscriptionStatus {
  subscription: Subscription
  days_remaining: number
  is_trial: boolean
  is_expired: boolean
}

export interface TrialAlert {
  id: string
  type: string
  data: {
    type: 'trial_expiring' | 'trial_expired'
    subscription_id: number
    days_remaining?: number
    trial_ends_at: string
    message: string
  }
  read_at: string | null
  created_at: string
}
