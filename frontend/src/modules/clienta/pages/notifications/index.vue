<template>
  <ClientALayout current-page="notifications">
    <section class="notifications-page">
      <header class="notifications-hero">
        <div>
          <p class="hero-kicker">Communication interne</p>
          <h1 class="hero-title">Centre de notifications</h1>
          <p class="hero-subtitle">Suivez les événements clés, traitez les alertes prioritaires et gardez une vue claire de l'activité.</p>
        </div>

        <div class="hero-actions">
          <v-btn
            v-if="canManageNotifications"
            color="primary"
            prepend-icon="mdi-check-all"
            rounded="lg"
            variant="flat"
            @click="markAllAsRead"
          >
            Tout marquer lu
          </v-btn>
          <v-btn
            v-if="canManageNotifications"
            color="error"
            prepend-icon="mdi-delete-outline"
            rounded="lg"
            variant="tonal"
            @click="clearAll"
          >
            Vider
          </v-btn>
        </div>
      </header>

      <section class="notifications-overview">
        <article class="overview-card">
          <p>Total</p>
          <strong>{{ notifications.length }}</strong>
        </article>
        <article class="overview-card overview-card--accent">
          <p>Non lues</p>
          <strong>{{ unreadCount }}</strong>
        </article>
        <article class="overview-card">
          <p>Prioritaires</p>
          <strong>{{ priorityCount }}</strong>
        </article>
        <article class="overview-card">
          <p>Dernières 24h</p>
          <strong>{{ recentCount }}</strong>
        </article>
      </section>

      <section class="notifications-toolbar">
        <div class="filter-pills">
          <button
            v-for="tab in tabs"
            :key="tab.value"
            :class="['pill', { 'pill--active': activeTab === tab.value }]"
            type="button"
            @click="activeTab = tab.value"
          >
            <span>{{ tab.label }}</span>
            <span v-if="tab.count > 0" class="pill-count">{{ tab.count }}</span>
          </button>
        </div>
      </section>

      <section class="notifications-layout">
        <article class="notifications-list">
          <div
            v-for="notif in filteredNotifications"
            :key="notif.id"
            :class="['notif-row', { 'notif-row--unread': !notif.read }]"
          >
            <div class="notif-icon" :class="getTypeClass(notif.type)">
              <component :is="getTypeIcon(notif.type, notif.data?.workflow_type)" class="w-5 h-5" />
            </div>

            <div class="notif-body">
              <div class="notif-title-row">
                <h3>{{ notif.title }}</h3>
                <span class="notif-time">{{ formatTimeAgo(notif.createdAt) }}</span>
              </div>

              <p>{{ notif.message }}</p>

              <div class="notif-row-actions">
                <span class="notif-tag" :class="getTypeBadgeClass(notif.type)">{{ getTypeLabel(notif.type, notif.data?.workflow_type) }}</span>

                <v-btn
                  v-if="notif.actionUrl && canNavigateTo(notif.actionUrl)"
                  color="primary"
                  density="comfortable"
                  rounded="lg"
                  size="small"
                  variant="text"
                  @click="navigateTo(notif.actionUrl)"
                >
                  {{ notif.actionLabel || 'Voir' }}
                </v-btn>

                <v-btn
                  v-if="isNomenclaturePropagationNotification(notif)"
                  color="success"
                  density="comfortable"
                  rounded="lg"
                  size="small"
                  variant="text"
                  @click="applyNomenclatureFromNotification(notif, 'skip')"
                >
                  Appliquer
                </v-btn>
                <v-btn
                  v-if="isNomenclaturePropagationNotification(notif)"
                  color="warning"
                  density="comfortable"
                  rounded="lg"
                  size="small"
                  variant="text"
                  @click="applyNomenclatureFromNotification(notif, 'override')"
                >
                  Forcer
                </v-btn>

                <v-spacer />

                <v-btn
                  v-if="canManageNotifications"
                  icon="mdi-check"
                  size="x-small"
                  variant="text"
                  @click="markAsRead(notif.id)"
                />
                <v-btn
                  v-if="canManageNotifications"
                  icon="mdi-close"
                  size="x-small"
                  variant="text"
                  @click="deleteNotification(notif.id)"
                />
              </div>
            </div>
          </div>

          <div v-if="filteredNotifications.length === 0" class="empty-state">
            <Bell class="w-12 h-12" />
            <p>Aucune notification pour ce filtre.</p>
          </div>
        </article>

        <aside class="notifications-side">
          <h2>Raccourcis</h2>
          <v-btn
            v-if="canNavigateTo('/company/documents')"
            block
            class="mb-2"
            prepend-icon="mdi-file-document-outline"
            rounded="lg"
            variant="tonal"
            @click="navigateTo('/company/documents')"
          >
            Documents
          </v-btn>
          <v-btn
            v-if="canNavigateTo('/company/audits')"
            block
            class="mb-2"
            prepend-icon="mdi-clipboard-check-outline"
            rounded="lg"
            variant="tonal"
            @click="navigateTo('/company/audits')"
          >
            Audits
          </v-btn>
          <v-btn
            v-if="canNavigateTo('/company/indicators')"
            block
            prepend-icon="mdi-chart-line"
            rounded="lg"
            variant="tonal"
            @click="navigateTo('/company/indicators')"
          >
            Indicateurs
          </v-btn>
        </aside>
      </section>
    </section>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { Bell } from 'lucide-vue-next'
  import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import api from '@/api/client'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import { resolveNotificationActionUrl } from '@/modules/clienta/utils/notificationRouteResolver'
  import { canonicalizeCompanyRoute } from '@/modules/clienta/utils/routeCanonicalizer'
  import { getNavigationPermissionSet } from '@/modules/clienta/utils/userPermissions'
  import { useAuthStore } from '@/stores/auth'
  import { isEnterpriseAdminUser } from '@/utils/accessControl'
  import { inferBlockingCodeFromUser, shouldBlockNotificationsForCode } from '@/utils/blockingAccess'
  import { expandPermissionAliases } from '@/utils/permissions'

  interface NotificationItem {
    id: number
    type: string
    title: string
    message: string
    read: boolean
    createdAt: string
    actionUrl?: string
    actionLabel?: string
    rawData?: any
    data?: {
      workflow_type?: string
      document_title?: string
      entity_id?: number
      document_code?: string
      document_version?: string
    }
  }

  const router = useRouter()
  const route = useRoute()
  const authStore = useAuthStore()

  const activeTab = ref('all')
  const loading = ref(false)
  const notifications = ref<NotificationItem[]>([])
  const refreshHandle = ref<number | null>(null)

  const blockingCode = computed(() => typeof route.query.blocking === 'string' ? route.query.blocking : '')
  const inferredBlockingCode = computed(() => inferBlockingCodeFromUser(authStore.user))
  const shouldSkipNotifications = computed(() =>
    shouldBlockNotificationsForCode(blockingCode.value || inferredBlockingCode.value || ''),
  )
  const canManageNotifications = computed(() => {
    const currentUser = authStore.user as any
    if (currentUser?.user_type === 'super_admin' || isEnterpriseAdminUser(currentUser)) {
      return true
    }
    const permissions = getNavigationPermissionSet(currentUser)
    return ['dashboard.read'].some(permission =>
      expandPermissionAliases(permission).some(alias => permissions.has(alias)),
    )
  })

  function canAccess (requiredPermissions: string[]): boolean {
    const currentUser = authStore.user as any
    if (currentUser?.user_type === 'super_admin' || isEnterpriseAdminUser(currentUser)) {
      return true
    }
    const permissions = getNavigationPermissionSet(currentUser)
    return requiredPermissions.some(permission =>
      expandPermissionAliases(permission).some(alias => permissions.has(alias)),
    )
  }

  function permissionsForPath (rawPath: string): string[] | null {
    const normalized = canonicalizeCompanyRoute(rawPath, rawPath)
    if (!normalized.startsWith('/company')) {
      return null
    }

    const [pathOnly] = normalized.split(/[?#]/)
    if (pathOnly.startsWith('/company/documents')) return ['support.documents.read']
    if (pathOnly.startsWith('/company/iso/audits') || pathOnly.startsWith('/company/audits')) return ['evaluation.audits.read']
    if (pathOnly.startsWith('/company/indicators')) return ['planification.objectifs.read']
    if (pathOnly.startsWith('/company/actions') || pathOnly.startsWith('/company/planning/actions')) return ['amelioration.non_conformites.read']
    if (pathOnly.startsWith('/company/nonconformities') || pathOnly.startsWith('/company/iso/nonconformities')) return ['amelioration.non_conformites.read']
    if (pathOnly.startsWith('/company/risks')) return ['planification.risques_opportunites.read']
    if (pathOnly.startsWith('/company/users')) {
      return ['leadership.roles_responsabilites.personnel.read', 'users.read', 'leadership.roles_responsabilites.personnel.read']
    }
    if (pathOnly.startsWith('/company/reports')) return ['dashboard.read']
    if (pathOnly.startsWith('/company/leadership/policy')) return ['leadership.politique.read']
    if (pathOnly.startsWith('/company/leadership/organization-chart') || pathOnly.startsWith('/company/leadership/organigramme')) {
      return ['leadership.roles_responsabilites.organigramme.read']
    }
    if (pathOnly.startsWith('/company/leadership/roles')) {
      return ['leadership.roles_responsabilites.fiche_responsabilite.read']
    }
    if (pathOnly.startsWith('/company/leadership/personnel')) {
      return ['leadership.roles_responsabilites.personnel.read', 'leadership.roles_responsabilites.personnel.read']
    }

    return []
  }

  function canNavigateTo (rawPath: string): boolean {
    const required = permissionsForPath(rawPath)
    if (required === null) {
      return true
    }
    if (required.length === 0) {
      return false
    }
    return canAccess(required)
  }

  notifications.value = [
    {
      id: 1,
      type: 'document',
      title: 'Nouveau document approuvé',
      message: 'Le document "Procédure contrôle qualité v2.1" a été approuvé par Marie Koffi',
      read: false,
      createdAt: new Date(Date.now() - 30 * 60 * 1000).toISOString(),
      actionUrl: '/company/documents/1',
      actionLabel: 'Voir le document',
    },
    {
      id: 2,
      type: 'action',
      title: 'Action en retard',
      message: 'L\'action corrective AC-2026-012 dépasse sa date d\'échéance',
      read: false,
      createdAt: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString(),
      actionUrl: '/company/actions/12',
      actionLabel: 'Voir l\'action',
    },
    {
      id: 3,
      type: 'audit',
      title: 'Audit planifié',
      message: 'Un nouvel audit interne est planifié pour le 25 janvier 2026',
      read: true,
      createdAt: new Date(Date.now() - 1 * 24 * 60 * 60 * 1000).toISOString(),
      actionUrl: '/company/audits/3',
      actionLabel: 'Voir l\'audit',
    },
    {
      id: 4,
      type: 'nc',
      title: 'Nouvelle non-conformité',
      message: 'NC-2026-015 a été créée suite à l\'audit du processus de production',
      read: true,
      createdAt: new Date(Date.now() - 2 * 24 * 60 * 60 * 1000).toISOString(),
      actionUrl: '/company/nonconformities/15',
      actionLabel: 'Voir la NC',
    },
    {
      id: 5,
      type: 'user',
      title: 'Nouveau collaborateur',
      message: 'Adou Jean-Paul a été ajouté à l\'équipe qualité',
      read: true,
      createdAt: new Date(Date.now() - 3 * 24 * 60 * 60 * 1000).toISOString(),
      actionUrl: '/company/users/5',
      actionLabel: 'Voir le profil',
    },
    {
      id: 6,
      type: 'kpi',
      title: 'Objectif KPI atteint',
      message: 'Le taux de conformité a atteint 98% ce mois-ci',
      read: true,
      createdAt: new Date(Date.now() - 5 * 24 * 60 * 60 * 1000).toISOString(),
      actionUrl: '/company/indicators',
      actionLabel: 'Voir les indicateurs',
    },
  ]

  const unreadCount = computed(() => notifications.value.filter(n => !n.read).length)
  const priorityCount = computed(() => notifications.value.filter(n => ['action', 'nc'].includes(n.type) && !n.read).length)
  const recentCount = computed(() => notifications.value.filter(n => (Date.now() - new Date(n.createdAt).getTime()) < 86_400_000).length)

  const tabs = computed(() => [
    { label: 'Toutes', value: 'all', count: notifications.value.length },
    { label: 'Non lues', value: 'unread', count: notifications.value.filter(n => !n.read).length },
    { label: 'Workflow', value: 'workflow', count: notifications.value.filter(n => n.data?.workflow_type).length },
    { label: 'Documents', value: 'document', count: notifications.value.filter(n => n.type === 'document').length },
    { label: 'Actions', value: 'action', count: notifications.value.filter(n => n.type === 'action').length },
  ])

  const filteredNotifications = computed(() => {
    if (activeTab.value === 'all') {
      return notifications.value
    }

    if (activeTab.value === 'unread') {
      return notifications.value.filter(n => !n.read)
    }

    if (activeTab.value === 'workflow') {
      return notifications.value.filter(n => n.data?.workflow_type)
    }

    return notifications.value.filter(n => n.type === activeTab.value)
  })

  async function loadNotifications () {
    if (shouldSkipNotifications.value) {
      notifications.value = []
      return
    }

    loading.value = true
    try {
      const response = await api.get('/notifications', {
        params: { unread_only: false, per_page: 100 },
        headers: { 'X-Skip-Error-Toast': 'true' },
      })

      const payload = response.data?.data
      const rows = Array.isArray(payload?.data)
        ? payload.data
        : (Array.isArray(payload) ? payload : [])

      notifications.value = rows.map((notification: any) => ({
        id: Number(notification.id),
        type: String(notification.type || notification.data?.type || 'system'),
        title: String(notification.title || notification.data?.title || 'Notification'),
        message: String(notification.message || notification.data?.message || ''),
        read: Boolean(notification.read_at),
        createdAt: String(notification.created_at || new Date().toISOString()),
        actionUrl: resolveNotificationActionUrl(notification),
        actionLabel: notification?.data?.action_label || undefined,
        rawData: notification?.data || {},
        data: {
          workflow_type: notification.data?.workflow_type,
          document_title: notification.data?.document_title,
          entity_id: notification.data?.entity_id,
          document_code: notification.data?.document_code,
          document_version: notification.data?.document_version,
        },
      }))
    } catch (error: any) {
      if (error?.response?.status === 423) {
        notifications.value = []
        return
      }
      notifications.value = []
      console.error('Erreur chargement notifications:', error)
    } finally {
      loading.value = false
    }
  }

  async function markAsRead (id: number) {
    const notif = notifications.value.find(n => n.id === id)
    if (!notif || notif.read || shouldSkipNotifications.value) {
      return
    }

    try {
      await api.post(`/notifications/${id}/mark-as-read`, undefined, {
        headers: { 'X-Skip-Error-Toast': 'true' },
      })
      notif.read = true
    } catch (error: any) {
      if (error?.response?.status === 423) {
        notifications.value = []
        return
      }
      console.error('Erreur marquage notification:', error)
    }
  }

  async function applyNomenclatureFromNotification (
    notif: NotificationItem,
    strategy: 'skip' | 'override',
  ) {
    const quick = notif?.rawData?.quick_action
    const configId = Number(quick?.configuration_id || 0)
    const targetSiteId = Number(quick?.target_site_id || 0)
    if (configId <= 0 || targetSiteId <= 0) {
      return
    }

    try {
      await api.post(`/document-type-configurations/${configId}/share-with-sites`, {
        site_ids: [targetSiteId],
        conflict_strategy: strategy,
      })
      await markAsRead(notif.id)
      await loadNotifications()
    } catch (error) {
      console.error('Propagation nomenclature impossible:', error)
    }
  }

  function isNomenclaturePropagationNotification (notif: NotificationItem): boolean {
    return notif?.rawData?.quick_action?.type === 'nomenclature_propagation'
  }

  async function markAllAsRead () {
    if (unreadCount.value === 0 || shouldSkipNotifications.value) {
      return
    }

    try {
      await api.post('/notifications/mark-all-as-read', undefined, {
        headers: { 'X-Skip-Error-Toast': 'true' },
      })
      notifications.value = notifications.value.map(n => ({ ...n, read: true }))
    } catch (error: any) {
      if (error?.response?.status === 423) {
        notifications.value = []
        return
      }
      console.error('Erreur marquage toutes notifications:', error)
    }
  }

  async function deleteNotification (id: number) {
    if (shouldSkipNotifications.value) {
      return
    }

    try {
      await api.delete(`/notifications/${id}`, {
        headers: { 'X-Skip-Error-Toast': 'true' },
      })
      notifications.value = notifications.value.filter(n => n.id !== id)
    } catch (error: any) {
      if (error?.response?.status === 423) {
        notifications.value = []
        return
      }
      console.error('Erreur suppression notification:', error)
    }
  }

  async function clearAll () {
    const ids = notifications.value.map(n => n.id)
    for (const id of ids) {
      await deleteNotification(id)
    }
  }

  function navigateTo (url: string) {
    const normalized = canonicalizeCompanyRoute(url, '/company/dashboard')
    if (!canNavigateTo(normalized)) {
      return
    }

    try {
      if (normalized.startsWith('/')) {
        router.push(normalized)
        return
      }

      const parsed = new URL(normalized, window.location.origin)
      const targetPath = `${parsed.pathname}${parsed.search}${parsed.hash}`
      if (parsed.origin === window.location.origin) {
        router.push(targetPath)
        return
      }

      window.open(parsed.toString(), '_blank', 'noopener,noreferrer')
    } catch (error) {
      console.warn('Lien de notification invalide:', normalized, error)
    }
  }

  function getTypeIcon (type: string, workflowType?: string) {
    const workflowIcons: Record<string, string> = {
      verification_request: 'mdi-eye-check',
      approval_request: 'mdi-pencil-check',
      rejection_decision_required: 'mdi-alert-octagon',
      publication: 'mdi-check-circle',
    }

    if (workflowType && workflowIcons[workflowType]) {
      return workflowIcons[workflowType]
    }

    const icons: Record<string, string> = {
      document: 'mdi-file-document-outline',
      action: 'mdi-clipboard-check-outline',
      audit: 'mdi-calendar-check-outline',
      nc: 'mdi-alert-circle-outline',
      user: 'mdi-account-plus-outline',
      kpi: 'mdi-chart-line',
      risk: 'mdi-alert-octagon-outline',
      system: 'mdi-cog-outline',
    }
    return icons[type] || 'mdi-bell'
  }

  function getTypeClass (type: string) {
    const classes: Record<string, string> = {
      document: 'type-document',
      action: 'type-action',
      audit: 'type-audit',
      nc: 'type-nc',
      user: 'type-user',
      kpi: 'type-kpi',
    }
    return classes[type] || 'type-default'
  }

  function getTypeBadgeClass (type: string) {
    const classes: Record<string, string> = {
      document: 'badge-document',
      action: 'badge-action',
      audit: 'badge-audit',
      nc: 'badge-nc',
      user: 'badge-user',
      kpi: 'badge-kpi',
    }
    return classes[type] || 'badge-default'
  }

  function getTypeLabel (type: string, workflowType?: string): string {
    const workflowLabels: Record<string, string> = {
      verification_request: '⏱️ Vérification requise',
      approval_request: '✏️ Approbation requise',
      rejection_decision_required: '❌ Décision de rejet',
      publication: '✅ Document publié',
    }

    if (workflowType && workflowLabels[workflowType]) {
      return workflowLabels[workflowType]
    }

    const labels: Record<string, string> = {
      document: 'Document',
      action: 'Action',
      audit: 'Audit',
      nc: 'Non-conformité',
      user: 'Utilisateur',
      kpi: 'Indicateur',
      risk: 'Risque',
      system: 'Système',
    }
    return labels[type] || type
  }

  function formatTimeAgo (date: string) {
    const seconds = Math.floor((Date.now() - new Date(date).getTime()) / 1000)

    if (seconds < 60) return 'À l\'instant'
    if (seconds < 3600) return `Il y a ${Math.floor(seconds / 60)} min`
    if (seconds < 86_400) return `Il y a ${Math.floor(seconds / 3600)} h`
    if (seconds < 604_800) return `Il y a ${Math.floor(seconds / 86_400)} j`

    return new Date(date).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'short',
    })
  }

  onMounted(() => {
    loadNotifications()
    refreshHandle.value = window.setInterval(() => {
      loadNotifications()
    }, 60_000)
  })

  watch(shouldSkipNotifications, skip => {
    if (skip) {
      notifications.value = []
      return
    }
    loadNotifications()
  })

  onUnmounted(() => {
    if (refreshHandle.value) {
      clearInterval(refreshHandle.value)
    }
  })
</script>

<style scoped>
.notifications-page {
  padding: clamp(16px, 3vw, 28px);
  display: grid;
  gap: 16px;
}

.notifications-hero {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: flex-start;
  padding: 22px;
  border-radius: 18px;
  border: 1px solid rgba(148, 163, 184, 0.3);
  background: linear-gradient(125deg, rgba(14, 116, 144, 0.1), rgba(245, 158, 11, 0.08));
}

.hero-kicker {
  margin: 0;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #0f766e;
  font-weight: 700;
}

.hero-title {
  margin: 4px 0;
  font-size: clamp(24px, 3vw, 32px);
  line-height: 1.15;
}

.hero-subtitle {
  margin: 0;
  color: #475569;
  max-width: 680px;
}

.hero-actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.notifications-overview {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 10px;
}

.overview-card {
  border: 1px solid rgba(148, 163, 184, 0.28);
  border-radius: 14px;
  padding: 14px;
  background: #fff;
}

.overview-card p {
  margin: 0;
  color: #64748b;
  font-size: 12px;
}

.overview-card strong {
  display: block;
  margin-top: 4px;
  font-size: 24px;
  line-height: 1;
}

.overview-card--accent {
  border-color: rgba(14, 165, 233, 0.4);
  background: rgba(240, 249, 255, 0.9);
}

.notifications-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.filter-pills {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.pill {
  border: 1px solid rgba(148, 163, 184, 0.35);
  border-radius: 999px;
  padding: 7px 14px;
  background: #fff;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
  cursor: pointer;
}

.pill--active {
  border-color: rgba(14, 116, 144, 0.55);
  background: rgba(236, 254, 255, 0.95);
  color: #0f766e;
}

.pill-count {
  font-size: 11px;
  border-radius: 999px;
  padding: 2px 6px;
  background: rgba(148, 163, 184, 0.2);
}

.notifications-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 260px;
  gap: 12px;
}

.notifications-list {
  display: grid;
  gap: 8px;
}

.notif-row {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 12px;
  border: 1px solid rgba(148, 163, 184, 0.28);
  border-radius: 14px;
  padding: 12px;
  background: #fff;
}

.notif-row--unread {
  border-color: rgba(14, 116, 144, 0.55);
  background: linear-gradient(180deg, rgba(240, 249, 255, 0.95), #fff 60%);
}

.notif-icon {
  width: 38px;
  height: 38px;
  border-radius: 11px;
  display: inline-grid;
  place-items: center;
}

.type-document { background: #dbeafe; color: #1d4ed8; }
.type-action { background: #ffedd5; color: #c2410c; }
.type-audit { background: #dcfce7; color: #15803d; }
.type-nc { background: #fee2e2; color: #b91c1c; }
.type-user { background: #ede9fe; color: #1abc9c; }
.type-kpi { background: #e0f2fe; color: #0369a1; }
.type-default { background: #e2e8f0; color: #334155; }

.notif-body {
  min-width: 0;
}

.notif-title-row {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 10px;
}

.notif-title-row h3 {
  margin: 0;
  font-size: 15px;
  line-height: 1.25;
}

.notif-time {
  font-size: 12px;
  color: #64748b;
  white-space: nowrap;
}

.notif-body p {
  margin: 6px 0 8px;
  font-size: 13px;
  color: #475569;
}

.notif-row-actions {
  display: flex;
  align-items: center;
  gap: 6px;
}

.notif-tag {
  font-size: 11px;
  font-weight: 700;
  border-radius: 999px;
  padding: 3px 8px;
}

.badge-document { background: #dbeafe; color: #1d4ed8; }
.badge-action { background: #ffedd5; color: #c2410c; }
.badge-audit { background: #dcfce7; color: #15803d; }
.badge-nc { background: #fee2e2; color: #b91c1c; }
.badge-user { background: #ede9fe; color: #16a085; }
.badge-kpi { background: #e0f2fe; color: #0369a1; }
.badge-default { background: #e2e8f0; color: #334155; }

.notifications-side {
  border: 1px solid rgba(148, 163, 184, 0.28);
  border-radius: 14px;
  padding: 12px;
  background: rgba(255, 255, 255, 0.95);
  height: fit-content;
}

.notifications-side h2 {
  margin: 2px 0 10px;
  font-size: 14px;
  color: #0f172a;
}

.empty-state {
  border: 1px dashed rgba(148, 163, 184, 0.45);
  border-radius: 14px;
  padding: 24px;
  color: #64748b;
  display: grid;
  gap: 8px;
  place-items: center;
  text-align: center;
}

@media (max-width: 1100px) {
  .notifications-overview {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .notifications-layout {
    grid-template-columns: minmax(0, 1fr);
  }
}

@media (max-width: 760px) {
  .notifications-hero {
    flex-direction: column;
  }

  .hero-actions {
    width: 100%;
  }

  .hero-actions :deep(.v-btn) {
    flex: 1;
  }

  .notifications-overview {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .notif-title-row {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
