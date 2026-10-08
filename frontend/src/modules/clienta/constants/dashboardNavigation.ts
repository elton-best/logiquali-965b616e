export type DashboardQuickAction = {
  id: string
  title: string
  description: string
  icon: string
  color: string
  route: string
  requiredPermissions: string[]
}

export type DashboardStatRoute = {
  route: string
  permissions: string[]
}

export const DASHBOARD_QUICK_ACTIONS: DashboardQuickAction[] = [
  {
    id: 'my-actions',
    title: 'Mes actions',
    description: 'Suivre mes actions assignées',
    icon: 'mdi-format-list-checks',
    color: 'primary',
    route: '/company/my-actions',
    requiredPermissions: [],
  },
  {
    id: 'create-document',
    title: 'Créer document',
    description: 'Nouvelle procédure',
    icon: 'mdi-file-plus',
    color: 'primary',
    route: '/company/documents/create',
    requiredPermissions: ['support.documents.create'],
  },
  {
    id: 'report-nc',
    title: 'Signaler NC',
    description: 'Déclarer une NC',
    icon: 'mdi-alert-circle-outline',
    color: 'error',
    route: '/company/nonconformities/create',
    requiredPermissions: ['amelioration.non_conformites.create'],
  },
  {
    id: 'plan-audit',
    title: 'Planifier audit',
    description: 'Programmer un audit',
    icon: 'mdi-calendar-plus',
    color: 'info',
    route: '/company/performance/audits/programme',
    requiredPermissions: ['evaluation.audits.read'],
  },
  {
    id: 'add-collaborator',
    title: 'Ajouter collaborateur',
    description: 'Inviter un membre',
    icon: 'mdi-account-plus',
    color: 'success',
    route: '/company/leadership/personnel',
    requiredPermissions: ['leadership.roles_responsabilites.personnel.read', 'leadership.roles_responsabilites.personnel.read'],
  },
]

export const DASHBOARD_STAT_ROUTES: Record<number, DashboardStatRoute> = {
  1: { route: '/company/sites', permissions: ['sites.read'] },
  2: {
    route: '/company/users',
    permissions: ['leadership.roles_responsabilites.personnel.read', 'users.read', 'leadership.roles_responsabilites.personnel.read'],
  },
  3: { route: '/company/actions', permissions: ['amelioration.non_conformites.read'] },
  4: {
    route: '/company/nonconformities',
    permissions: ['amelioration.non_conformites.read'],
  },
  5: { route: '/company/audits', permissions: ['evaluation.audits.read'] },
  6: {
    route: '/company/context/management-system',
    permissions: ['evaluation.revue_processus.read'],
  },
}
