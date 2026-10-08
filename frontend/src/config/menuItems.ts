import type { MenuItem } from '@/layouts/AppLayout.vue'

/**
 * Menu items for Client A (Full QHSE Platform Access)
 */
export const clientAMenu: MenuItem[] = [
  {
    icon: 'mdi-view-dashboard',
    label: 'Dashboard',
    route: '/dashboard',
  },
  {
    icon: 'mdi-account-group',
    label: 'Collaborateurs',
    route: '/collaborateurs',
  },
  {
    icon: 'mdi-office-building',
    label: 'Sites',
    route: '/sites',
  },
  {
    icon: 'mdi-file-document-multiple',
    label: 'Documents',
    route: '/documents',
    children: [
      {
        icon: 'mdi-folder',
        label: 'Tous les documents',
        route: '/documents/all',
      },
      {
        icon: 'mdi-file-upload',
        label: 'Mes envois',
        route: '/documents/uploads',
      },
      {
        icon: 'mdi-archive',
        label: 'Archives',
        route: '/documents/archives',
      },
    ],
  },
  {
    icon: 'mdi-shield-check',
    label: 'QHSE',
    route: '/qhse',
    children: [
      {
        icon: 'mdi-alert-circle',
        label: 'Non-conformités',
        route: '/qhse/non-conformities',
      },
      {
        icon: 'mdi-alert-octagon',
        label: 'Risques',
        route: '/qhse/risks',
      },
      {
        icon: 'mdi-clipboard-check',
        label: 'Audits',
        route: '/qhse/audits',
      },
      {
        icon: 'mdi-cog-outline',
        label: 'Processus',
        route: '/qhse/processes',
      },
      {
        icon: 'mdi-target',
        label: 'Objectifs',
        route: '/qhse/objectives',
      },
    ],
  },
  {
    icon: 'mdi-lifebuoy',
    label: 'Support',
    route: '/support',
    children: [
      {
        icon: 'mdi-package-variant',
        label: 'Ressources',
        route: '/support/ressources',
      },
      {
        icon: 'mdi-school',
        label: 'Compétences',
        route: '/support/competences',
      },
      {
        icon: 'mdi-bullhorn',
        label: 'Communication',
        route: '/support/communication',
      },
      {
        icon: 'mdi-file-document',
        label: 'Information documentée',
        route: '/support/information-documentee',
      },
    ],
  },
  {
    icon: 'mdi-chart-line',
    label: 'Statistiques',
    route: '/statistics',
  },
  {
    icon: 'mdi-cog',
    label: 'Paramètres',
    route: '/settings',
    children: [
      {
        icon: 'mdi-domain',
        label: 'Organisation',
        route: '/settings/organization',
      },
      {
        icon: 'mdi-account-multiple',
        label: 'Utilisateurs',
        route: '/settings/users',
      },
      {
        icon: 'mdi-shield-lock',
        label: 'Sécurité',
        route: '/settings/security',
      },
    ],
  },
]

/**
 * Menu items for Super Admin (Platform Management)
 */
export const superAdminMenu: MenuItem[] = [
  {
    icon: 'mdi-view-dashboard',
    label: 'Dashboard',
    route: '/admin/dashboard',
  },
  {
    icon: 'mdi-domain',
    label: 'Tenants',
    route: '/admin/tenants',
    children: [
      {
        icon: 'mdi-format-list-bulleted',
        label: 'Tous les tenants',
        route: '/admin/tenants/all',
      },
      {
        icon: 'mdi-plus-circle',
        label: 'Nouveau tenant',
        route: '/admin/tenants/new',
      },
      {
        icon: 'mdi-cog-outline',
        label: 'Configuration',
        route: '/admin/tenants/config',
      },
    ],
  },
  {
    icon: 'mdi-shield-account',
    label: 'KYC & Vérifications',
    route: '/admin/kyc',
    children: [
      {
        icon: 'mdi-clock-outline',
        label: 'En attente',
        route: '/admin/kyc/pending',
      },
      {
        icon: 'mdi-check-circle',
        label: 'Validés',
        route: '/admin/kyc/approved',
      },
      {
        icon: 'mdi-close-circle',
        label: 'Rejetés',
        route: '/admin/kyc/rejected',
      },
    ],
  },
  {
    icon: 'mdi-account-multiple',
    label: 'Utilisateurs',
    route: '/admin/users',
    children: [
      {
        icon: 'mdi-account-group',
        label: 'Tous les utilisateurs',
        route: '/admin/users/all',
      },
      {
        icon: 'mdi-account-star',
        label: 'Admins',
        route: '/admin/users/admins',
      },
      {
        icon: 'mdi-account-lock',
        label: 'Bloqués',
        route: '/admin/users/blocked',
      },
    ],
  },
  {
    icon: 'mdi-chart-box',
    label: 'Analytics',
    route: '/admin/analytics',
    children: [
      {
        icon: 'mdi-chart-line',
        label: 'Vue d\'ensemble',
        route: '/admin/analytics/overview',
      },
      {
        icon: 'mdi-account-details',
        label: 'Par tenant',
        route: '/admin/analytics/by-tenant',
      },
      {
        icon: 'mdi-finance',
        label: 'Facturation',
        route: '/admin/analytics/billing',
      },
    ],
  },
  {
    icon: 'mdi-email',
    label: 'Communications',
    route: '/admin/communications',
    children: [
      {
        icon: 'mdi-email-send',
        label: 'Emails',
        route: '/admin/communications/emails',
      },
      {
        icon: 'mdi-bell',
        label: 'Notifications',
        route: '/admin/communications/notifications',
      },
      {
        icon: 'mdi-bullhorn',
        label: 'Annonces',
        route: '/admin/communications/announcements',
      },
    ],
  },
  {
    icon: 'mdi-cog',
    label: 'Settings',
    route: '/admin/settings',
    children: [
      {
        icon: 'mdi-wrench',
        label: 'Système',
        route: '/admin/settings/system',
      },
      {
        icon: 'mdi-shield-lock',
        label: 'Sécurité',
        route: '/admin/settings/security',
      },
      {
        icon: 'mdi-backup-restore',
        label: 'Sauvegardes',
        route: '/admin/settings/backups',
      },
    ],
  },
]

/**
 * Menu items for Client B (Simplified - Réclamations focused)
 */
export const clientBMenu: MenuItem[] = [
  {
    icon: 'mdi-view-dashboard',
    label: 'Dashboard',
    route: '/dashboard',
  },
  {
    icon: 'mdi-message-alert',
    label: 'Réclamations',
    route: '/reclamations',
    children: [
      {
        icon: 'mdi-format-list-bulleted',
        label: 'Toutes',
        route: '/reclamations/all',
      },
      {
        icon: 'mdi-plus-circle',
        label: 'Nouvelle réclamation',
        route: '/reclamations/new',
      },
      {
        icon: 'mdi-clock-outline',
        label: 'En cours',
        route: '/reclamations/in-progress',
      },
      {
        icon: 'mdi-check-circle',
        label: 'Résolues',
        route: '/reclamations/resolved',
      },
    ],
  },
  {
    icon: 'mdi-file-document',
    label: 'Documents',
    route: '/documents',
  },
  {
    icon: 'mdi-chart-bar',
    label: 'Rapports',
    route: '/reports',
  },
  {
    icon: 'mdi-cog',
    label: 'Settings',
    route: '/settings',
    children: [
      {
        icon: 'mdi-account',
        label: 'Mon profil',
        route: '/settings/profile',
      },
      {
        icon: 'mdi-account-multiple',
        label: 'Utilisateurs',
        route: '/settings/users',
      },
    ],
  },
]

/**
 * Menu items for users without active subscription (limited access)
 */
export const noSubscriptionMenu: MenuItem[] = [
  {
    icon: 'mdi-office-building',
    label: 'Sites',
    route: '/sites',
  },
  {
    icon: 'mdi-credit-card',
    label: 'Abonnement',
    route: '/subscription',
  },
]

/**
 * Get menu items based on user type
 */
export function getMenuItems (userType: 'clienta' | 'super_admin' | 'client_b'): MenuItem[] {
  const menuMap = {
    clienta: clientAMenu,
    client_a: clientAMenu,
    super_admin: superAdminMenu,
    client_b: clientBMenu,
  }

  return menuMap[userType] || clientAMenu
}

/**
 * Get menu items based on subscription status
 * Returns limited menu for users without active subscription
 */
export function getMenuItemsBySubscription (
  userType: 'clienta' | 'super_admin' | 'client_b',
  hasActiveSubscription: boolean,
): MenuItem[] {
  // Super admin always has full access
  if (userType === 'super_admin') {
    return superAdminMenu
  }

  // Users without active subscription get limited menu
  if (!hasActiveSubscription) {
    return noSubscriptionMenu
  }

  // Users with active subscription get full menu
  return getMenuItems(userType)
}

export default {
  clientAMenu,
  superAdminMenu,
  clientBMenu,
  noSubscriptionMenu,
  getMenuItems,
  getMenuItemsBySubscription,
}
