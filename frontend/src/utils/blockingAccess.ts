export type BlockingAccessCode
  = 'PASSWORD_CHANGE_REQUIRED'
    | 'SIGNATURE_UPLOAD_REQUIRED'
    | 'SUBSCRIPTION_REQUIRED'
    | 'SUBSCRIPTION_REQUIRED_FOR_SITE'
    | 'COMPANY_SETUP_REQUIRED'

interface BlockingRule {
  message: string
  resolvePath: (user: any) => string
}

const NOTIFICATION_BLOCKING_CODES = new Set<BlockingAccessCode>([
  'PASSWORD_CHANGE_REQUIRED',
  'SIGNATURE_UPLOAD_REQUIRED',
  'SUBSCRIPTION_REQUIRED',
  'SUBSCRIPTION_REQUIRED_FOR_SITE',
  'COMPANY_SETUP_REQUIRED',
])

function withBlockingQuery (path: string, code: BlockingAccessCode): string {
  const separator = path.includes('?') ? '&' : '?'
  return `${path}${separator}blocking=${encodeURIComponent(code)}`
}

function resolveCompanyPath (user: any, companyPath: string, code: BlockingAccessCode): string {
  return user?.user_type === 'company'
    ? withBlockingQuery(companyPath, code)
    : '/403'
}

function hasActiveSubscription (user: any): boolean {
  const fromSite = (user as any)?.site?.subscriptions
  if (Array.isArray(fromSite) && fromSite.some(subscription => Boolean(subscription?.is_active))) {
    return true
  }

  const enterprise = (user as any)?.enterprise
  const enterpriseSubscriptions = [
    ...(Array.isArray(enterprise?.subscriptions) ? enterprise.subscriptions : []),
    ...(Array.isArray(enterprise?.enterprise_subscriptions) ? enterprise.enterprise_subscriptions : []),
  ]

  return enterpriseSubscriptions.some(subscription => Boolean(subscription?.is_active))
}

const BLOCKING_RULES: Record<BlockingAccessCode, BlockingRule> = {
  PASSWORD_CHANGE_REQUIRED: {
    message: 'Vous devez changer votre mot de passe avant de continuer.',
    resolvePath: user => user?.user_type === 'super_admin'
      ? withBlockingQuery('/superadmin/profile', 'PASSWORD_CHANGE_REQUIRED')
      : withBlockingQuery('/auth/force-password-change', 'PASSWORD_CHANGE_REQUIRED'),
  },
  SIGNATURE_UPLOAD_REQUIRED: {
    message: 'Vous devez importer votre signature avant de continuer.',
    resolvePath: () => withBlockingQuery('/company/settings?tab=profil', 'SIGNATURE_UPLOAD_REQUIRED'),
  },
  SUBSCRIPTION_REQUIRED: {
    message: 'Souscription requise pour accéder à cette fonctionnalité.',
    resolvePath: user => resolveCompanyPath(user, '/company/subscription', 'SUBSCRIPTION_REQUIRED'),
  },
  SUBSCRIPTION_REQUIRED_FOR_SITE: {
    message: 'Souscription active requise pour le site courant.',
    resolvePath: user => resolveCompanyPath(user, '/company/subscription', 'SUBSCRIPTION_REQUIRED_FOR_SITE'),
  },
  COMPANY_SETUP_REQUIRED: {
    message: 'Veuillez compléter la configuration entreprise avant de continuer.',
    resolvePath: user => resolveCompanyPath(user, '/company/settings?tab=entreprise', 'COMPANY_SETUP_REQUIRED'),
  },
}

function isBlockingAccessCode (code: string | undefined): code is BlockingAccessCode {
  return Boolean(code && code in BLOCKING_RULES)
}

export function shouldBlockNotificationsForCode (code: string | undefined): boolean {
  if (!isBlockingAccessCode(code)) {
    return false
  }

  return NOTIFICATION_BLOCKING_CODES.has(code)
}

export function inferBlockingCodeFromUser (user: any): BlockingAccessCode | null {
  if (!user || user.user_type !== 'company') {
    return null
  }

  if ((user as any)?.must_change_password) {
    return 'PASSWORD_CHANGE_REQUIRED'
  }

  const enterprise = (user as any)?.enterprise
  const domaineValue = String(enterprise?.field || enterprise?.domaine_activite || '').trim()
  const domaineActiviteSet = Boolean(enterprise?.domaine_activite_set) || domaineValue.length > 0
  if (!domaineActiviteSet && !hasActiveSubscription(user)) {
    return 'COMPANY_SETUP_REQUIRED'
  }

  return null
}

export function getBlockingRedirectPath (code: string | undefined, user: any): string | null {
  if (!isBlockingAccessCode(code)) {
    return null
  }

  return BLOCKING_RULES[code].resolvePath(user)
}

export function getBlockingMessage (code: string | undefined): string | null {
  if (!isBlockingAccessCode(code)) {
    return null
  }

  return BLOCKING_RULES[code].message
}

export function getBlockingState (code: string | undefined, user: any): {
  code: BlockingAccessCode
  redirectPath: string
  message: string
} | null {
  if (!isBlockingAccessCode(code)) {
    return null
  }

  return {
    code,
    redirectPath: BLOCKING_RULES[code].resolvePath(user),
    message: BLOCKING_RULES[code].message,
  }
}
