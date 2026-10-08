<template>
  <ClientBLayout current-page="/clientb/notifications">
    <section class="notifications-page">
      <header class="notifications-hero">
        <div>
          <p class="hero-kicker">Communication client</p>
          <h1 class="hero-title">Centre de notifications</h1>
          <p class="hero-subtitle">
            Suivez les événements clés, traitez les alertes prioritaires et gardez une vue claire de vos réclamations.
          </p>
        </div>

        <div class="hero-actions">
          <v-btn
            color="primary"
            prepend-icon="mdi-check-all"
            rounded="lg"
            variant="flat"
            @click="markAllAsRead"
          >
            Tout marquer lu
          </v-btn>
          <v-btn
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
              <component :is="getTypeIcon(notif.type)" class="w-5 h-5" />
            </div>

            <div class="notif-body">
              <div class="notif-title-row">
                <h3>{{ notif.title }}</h3>
                <span class="notif-time">{{ formatTimeAgo(notif.createdAt) }}</span>
              </div>

              <p>{{ notif.message }}</p>

              <div class="notif-row-actions">
                <span class="notif-tag" :class="getTypeBadgeClass(notif.type)">{{ getTypeLabel(notif.type) }}</span>

                <v-btn
                  v-if="notif.actionUrl"
                  color="primary"
                  density="comfortable"
                  rounded="lg"
                  size="small"
                  variant="text"
                  @click="navigateTo(notif.actionUrl)"
                >
                  {{ notif.actionLabel || 'Voir' }}
                </v-btn>

                <v-spacer />

                <v-btn
                  icon="mdi-check"
                  size="x-small"
                  variant="text"
                  @click="markAsRead(notif.id)"
                />
                <v-btn
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
            block
            class="mb-2"
            prepend-icon="mdi-file-document-outline"
            rounded="lg"
            variant="tonal"
            @click="navigateTo('/clientb/complaints')"
          >
            Réclamations
          </v-btn>
          <v-btn
            block
            class="mb-2"
            prepend-icon="mdi-clipboard-check-outline"
            rounded="lg"
            variant="tonal"
            @click="navigateTo('/clientb/satisfaction-forms')"
          >
            Satisfaction
          </v-btn>
          <v-btn
            block
            prepend-icon="mdi-chart-line"
            rounded="lg"
            variant="tonal"
            @click="navigateTo('/clientb/dashboard')"
          >
            Tableau de bord
          </v-btn>
        </aside>
      </section>
    </section>
  </ClientBLayout>
</template>

<script setup lang="ts">
  import { Bell } from 'lucide-vue-next'
  import { computed, onMounted, onUnmounted, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import api from '@/api/client'
  import ClientBLayout from '@/modules/clientb/components/ClientBLayout.vue'

  interface NotificationItem {
    id: number
    type: string
    title: string
    message: string
    read: boolean
    createdAt: string
    actionUrl?: string
    actionLabel?: string
  }

  const router = useRouter()

  const activeTab = ref('all')
  const loading = ref(false)
  const notifications = ref<NotificationItem[]>([])
  const refreshHandle = ref<number | null>(null)

  const unreadCount = computed(() => notifications.value.filter(n => !n.read).length)
  const priorityCount = computed(() => notifications.value.filter(n => ['action', 'nc'].includes(n.type) && !n.read).length)
  const recentCount = computed(() => notifications.value.filter(n => (Date.now() - new Date(n.createdAt).getTime()) < 86_400_000).length)

  const tabs = computed(() => [
    { label: 'Toutes', value: 'all', count: notifications.value.length },
    { label: 'Non lues', value: 'unread', count: notifications.value.filter(n => !n.read).length },
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

    return notifications.value.filter(n => n.type === activeTab.value)
  })

  async function loadNotifications () {
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
        actionUrl: resolveActionUrl(notification),
        actionLabel: notification?.data?.action_label || undefined,
      }))
    } catch (error) {
      notifications.value = []
      console.error('Erreur chargement notifications:', error)
    } finally {
      loading.value = false
    }
  }

  function resolveActionUrl (notification: any): string | undefined {
    const direct = typeof notification?.data?.action_url === 'string' && notification.data.action_url.trim().length > 0
      ? notification.data.action_url.trim()
      : (typeof notification?.data?.url === 'string' ? notification.data.url.trim() : '')

    if (direct) {
      return direct
    }

    const resourceType = notification?.data?.resource_type
    const resourceId = notification?.data?.resource_id
    if (!resourceType || !resourceId) {
      return undefined
    }

    const routes: Record<string, string> = {
      complaint: '/clientb/complaints',
      document: '/clientb/dashboard',
      action: '/clientb/dashboard',
    }

    const basePath = routes[resourceType]
    return basePath ? `${basePath}/${resourceId}` : undefined
  }

  async function markAsRead (id: number) {
    const notif = notifications.value.find(n => n.id === id)
    if (!notif || notif.read) {
      return
    }

    try {
      await api.post(`/notifications/${id}/mark-as-read`, undefined, {
        headers: { 'X-Skip-Error-Toast': 'true' },
      })
      notif.read = true
    } catch (error) {
      console.error('Erreur marquage notification:', error)
    }
  }

  async function markAllAsRead () {
    if (unreadCount.value === 0) {
      return
    }

    try {
      await api.post('/notifications/mark-all-as-read', undefined, {
        headers: { 'X-Skip-Error-Toast': 'true' },
      })
      notifications.value = notifications.value.map(n => ({ ...n, read: true }))
    } catch (error) {
      console.error('Erreur marquage toutes notifications:', error)
    }
  }

  async function deleteNotification (id: number) {
    try {
      await api.delete(`/notifications/${id}`, {
        headers: { 'X-Skip-Error-Toast': 'true' },
      })
      notifications.value = notifications.value.filter(n => n.id !== id)
    } catch (error) {
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
    if (url.startsWith('/')) {
      router.push(url)
      return
    }

    try {
      const parsed = new URL(url, window.location.origin)
      const targetPath = `${parsed.pathname}${parsed.search}${parsed.hash}`
      if (parsed.origin === window.location.origin) {
        router.push(targetPath)
        return
      }

      window.open(parsed.toString(), '_blank', 'noopener,noreferrer')
    } catch (error) {
      console.warn('Lien de notification invalide:', url, error)
    }
  }

  function getTypeIcon (type: string) {
    const icons: Record<string, string> = {
      document: 'mdi-file-document-outline',
      action: 'mdi-clipboard-check-outline',
      audit: 'mdi-calendar-check-outline',
      nc: 'mdi-alert-circle-outline',
      user: 'mdi-account-plus-outline',
      kpi: 'mdi-chart-line',
      risk: 'mdi-alert-octagon-outline',
      system: 'mdi-cog-outline',
      complaint: 'mdi-file-alert',
    }
    return icons[type] || Bell
  }

  function getTypeClass (type: string) {
    const classes: Record<string, string> = {
      document: 'type-document',
      action: 'type-action',
      audit: 'type-audit',
      nc: 'type-nc',
      user: 'type-user',
      kpi: 'type-kpi',
      complaint: 'type-nc',
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
      complaint: 'badge-nc',
    }
    return classes[type] || 'badge-default'
  }

  function getTypeLabel (type: string) {
    const labels: Record<string, string> = {
      document: 'Document',
      action: 'Action',
      audit: 'Audit',
      nc: 'Non-conformité',
      user: 'Utilisateur',
      kpi: 'Indicateur',
      risk: 'Risque',
      system: 'Système',
      complaint: 'Réclamation',
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
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.overview-card strong {
  font-size: 20px;
  color: #0f172a;
}

.overview-card--accent {
  border-color: rgba(14, 116, 144, 0.4);
  background: rgba(14, 116, 144, 0.08);
}

.notifications-toolbar {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: center;
}

.filter-pills {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.pill {
  border: 1px solid rgba(148, 163, 184, 0.3);
  background: #fff;
  border-radius: 999px;
  padding: 6px 14px;
  font-size: 13px;
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.pill--active {
  background: #0f766e;
  color: #fff;
  border-color: #0f766e;
}

.pill-count {
  background: rgba(15, 118, 110, 0.1);
  border-radius: 999px;
  padding: 0 8px;
  font-size: 11px;
}

.pill--active .pill-count {
  background: rgba(255, 255, 255, 0.2);
}

.notifications-layout {
  display: grid;
  grid-template-columns: minmax(0, 2.2fr) minmax(0, 0.8fr);
  gap: 16px;
}

.notifications-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.notif-row {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 14px;
  background: #fff;
  border: 1px solid rgba(148, 163, 184, 0.3);
  border-radius: 16px;
  padding: 16px;
  transition: box-shadow 0.2s ease, transform 0.2s ease;
}

.notif-row--unread {
  border-color: rgba(14, 116, 144, 0.4);
  box-shadow: 0 10px 24px rgba(15, 118, 110, 0.1);
}

.notif-row:hover {
  transform: translateY(-2px);
}

.notif-icon {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(148, 163, 184, 0.2);
  color: #0f172a;
}

.type-document {
  background: rgba(59, 130, 246, 0.15);
  color: #1d4ed8;
}

.type-action {
  background: rgba(16, 185, 129, 0.15);
  color: #059669;
}

.type-audit {
  background: rgba(245, 158, 11, 0.15);
  color: #b45309;
}

.type-nc {
  background: rgba(239, 68, 68, 0.15);
  color: #b91c1c;
}

.type-user {
  background: rgba(14, 116, 144, 0.15);
  color: #0f766e;
}

.type-kpi {
  background: rgba(99, 102, 241, 0.15);
  color: #4f46e5;
}

.type-default {
  background: rgba(148, 163, 184, 0.2);
  color: #475569;
}

.notif-body h3 {
  margin: 0;
  font-size: 16px;
}

.notif-body p {
  margin: 6px 0 0;
  color: #475569;
}

.notif-title-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
}

.notif-time {
  font-size: 12px;
  color: #64748b;
}

.notif-row-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 12px;
}

.notif-tag {
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  padding: 4px 8px;
  border-radius: 999px;
  background: rgba(148, 163, 184, 0.2);
}

.badge-document {
  background: rgba(59, 130, 246, 0.1);
  color: #1d4ed8;
}

.badge-action {
  background: rgba(16, 185, 129, 0.1);
  color: #059669;
}

.badge-audit {
  background: rgba(245, 158, 11, 0.1);
  color: #b45309;
}

.badge-nc {
  background: rgba(239, 68, 68, 0.1);
  color: #b91c1c;
}

.badge-user {
  background: rgba(14, 116, 144, 0.1);
  color: #0f766e;
}

.badge-kpi {
  background: rgba(99, 102, 241, 0.1);
  color: #4f46e5;
}

.badge-default {
  background: rgba(148, 163, 184, 0.2);
  color: #475569;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
  color: #64748b;
  border-radius: 16px;
  border: 1px dashed rgba(148, 163, 184, 0.3);
  background: rgba(248, 250, 252, 0.6);
}

.notifications-side {
  border: 1px solid rgba(148, 163, 184, 0.3);
  border-radius: 16px;
  padding: 16px;
  background: #fff;
  height: fit-content;
}

.notifications-side h2 {
  margin: 0 0 12px;
  font-size: 14px;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #475569;
}

@media (max-width: 960px) {
  .notifications-layout {
    grid-template-columns: 1fr;
  }

  .notifications-overview {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .notifications-hero {
    flex-direction: column;
  }
}

@media (max-width: 600px) {
  .notifications-overview {
    grid-template-columns: 1fr;
  }
}
</style>
