<template>
  <v-app class="role-workspace">
    <v-navigation-drawer
      v-model="drawer"
      class="role-sidebar"
      color="surface"
      elevation="0"
      :permanent="!isMobile"
      :temporary="isMobile"
      :width="288"
    >
      <div class="role-sidebar__brand">
        <div class="role-sidebar__brand-mark" :class="`role-sidebar__brand-mark--${accent}`">
          <v-icon size="21">{{ icon }}</v-icon>
        </div>
        <div class="min-w-0">
          <div class="role-sidebar__brand-name">{{ siteName }}</div>
          <div class="role-sidebar__brand-role">{{ roleLabel }}</div>
        </div>
      </div>

      <v-divider />

      <div class="role-sidebar__scroll">
        <div class="role-sidebar__section-label">Espace de travail</div>
        <v-list class="role-sidebar__nav" density="comfortable" nav>
          <v-list-item
            class="role-sidebar__item"
            :prepend-icon="dashboardIcon"
            :title="dashboardLabel"
            :to="dashboardPath"
          />

          <template v-for="group in navGroups" :key="group.label">
            <v-list-item
              v-if="group.to"
              class="role-sidebar__item"
              :prepend-icon="group.icon"
              :title="group.label"
              :to="group.to"
            />
            <v-list-group v-else :value="group.label">
              <template #activator="{ props }">
                <v-list-item
                  v-bind="props"
                  class="role-sidebar__item role-sidebar__group"
                  :prepend-icon="group.icon"
                  :title="group.label"
                />
              </template>
              <v-list-item
                v-for="item in group.children"
                :key="item.to"
                class="role-sidebar__item role-sidebar__item--child"
                :prepend-icon="item.icon"
                :title="item.label"
                :to="item.to"
              />
            </v-list-group>
          </template>
        </v-list>
      </div>

      <div class="role-sidebar__footer">
        <div class="role-user-card">
          <v-avatar :color="accent" size="36">
            <span class="text-caption font-weight-bold">{{ userInitials }}</span>
          </v-avatar>
          <div class="min-w-0">
            <div class="role-user-card__name">{{ userName }}</div>
            <div class="role-user-card__site">{{ roleLabel }}</div>
          </div>
          <v-icon class="ml-auto" color="grey" size="18">mdi-dots-horizontal</v-icon>
        </div>
      </div>
    </v-navigation-drawer>

    <v-app-bar class="role-app-bar" elevation="0" height="72">
      <v-app-bar-nav-icon v-if="isMobile" @click="drawer = !drawer" />
      <div class="role-app-bar__heading">
        <div class="role-app-bar__eyebrow">{{ roleLabel }}</div>
        <div class="role-app-bar__title">{{ pageTitle }}</div>
      </div>
      <v-spacer />
      <v-btn class="role-app-bar__icon" icon variant="text" aria-label="Notifications">
        <v-badge color="error" content="3" dot offset-x="3" offset-y="3">
          <v-icon size="20">mdi-bell-outline</v-icon>
        </v-badge>
      </v-btn>
      <v-btn class="role-app-bar__icon" icon variant="text" aria-label="Aide">
        <v-icon size="20">mdi-help-circle-outline</v-icon>
      </v-btn>
      <v-avatar class="ml-2" :color="accent" size="38">
        <span class="text-caption font-weight-bold">{{ userInitials }}</span>
      </v-avatar>
    </v-app-bar>

    <v-main class="role-main">
      <div class="role-page-shell">
        <div class="role-page-content">
          <slot />
        </div>
      </div>
    </v-main>
  </v-app>
</template>

<script setup lang="ts">
  import { computed, ref } from 'vue'
  import { useDisplay } from 'vuetify'
  import { useAuthStore } from '@/stores/auth'

  interface NavItem {
    label: string
    to: string
    icon?: string
  }

  interface NavGroup {
    label: string
    icon: string
    to?: string
    children?: NavItem[]
  }

  const props = withDefaults(defineProps<{
    roleLabel: string
    pageTitle: string
    icon: string
    accent: string
    dashboardPath: string
    dashboardLabel?: string
    dashboardIcon?: string
    navGroups: NavGroup[]
  }>(), {
    dashboardLabel: 'Tableau de bord',
    dashboardIcon: 'mdi-view-dashboard-outline',
  })

  const authStore = useAuthStore()
  const { mdAndDown } = useDisplay()
  const isMobile = computed(() => mdAndDown.value)
  const drawer = ref(!isMobile.value)
  const siteName = computed(() => authStore.user?.site?.name || 'Mon site')
  const userName = computed(() => authStore.userName || 'Utilisateur')
  const userInitials = computed(() => userName.value.split(' ').map(name => name[0]).join('').toUpperCase().slice(0, 2))

  // Keep props reactive in the template while making the public API explicit.
  void props
</script>

<style scoped>
.role-workspace {
  background: var(--color-paper, #f8fafc);
  color: var(--color-ink, #0f172a);
}

.role-sidebar {
  border-right: 1px solid var(--border-line, rgba(226, 232, 240, 0.9)) !important;
}

.role-sidebar__brand {
  min-height: 92px;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 20px 22px;
}

.role-sidebar__brand-mark {
  width: 40px;
  height: 40px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
  border-radius: 12px;
  color: #fff;
  background: rgb(var(--v-theme-primary));
}

.role-sidebar__brand-mark--success { background: rgb(var(--v-theme-success)); }
.role-sidebar__brand-mark--warning { background: rgb(var(--v-theme-warning)); }
.role-sidebar__brand-mark--error { background: rgb(var(--v-theme-error)); }
.role-sidebar__brand-mark--info { background: rgb(var(--v-theme-info)); }
.role-sidebar__brand-mark--grey { background: #64748b; }
.role-sidebar__brand-mark--purple { background: #7c3aed; }
.role-sidebar__brand-mark--orange { background: #ea580c; }

.role-sidebar__brand-name,
.role-user-card__name {
  overflow: hidden;
  color: var(--color-ink, #0f172a);
  font-family: var(--font-display, 'Sora', sans-serif);
  font-size: 0.9rem;
  font-weight: 800;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.role-sidebar__brand-role,
.role-user-card__site {
  margin-top: 2px;
  overflow: hidden;
  color: #64748b;
  font-size: 0.72rem;
  font-weight: 600;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.role-sidebar__scroll {
  height: calc(100vh - 178px);
  overflow-y: auto;
  padding: 18px 12px 12px;
}

.role-sidebar__section-label {
  padding: 0 12px 9px;
  color: #94a3b8;
  font-size: 0.65rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.role-sidebar__nav :deep(.v-list-item) {
  min-height: 42px;
  margin: 3px 0;
  border-radius: 10px;
  color: #475569;
  font-size: 0.84rem;
  font-weight: 650;
}

.role-sidebar__nav :deep(.v-list-item:hover) {
  background: rgba(var(--v-theme-primary), 0.06);
  color: rgb(var(--v-theme-primary));
}

.role-sidebar__nav :deep(.v-list-item--active) {
  background: rgb(var(--v-theme-primary));
  color: #fff;
  box-shadow: 0 6px 14px rgba(var(--v-theme-primary), 0.22);
}

.role-sidebar__nav :deep(.v-list-item--active .v-icon),
.role-sidebar__nav :deep(.v-list-item--active .v-list-item-title) {
  color: inherit;
}

.role-sidebar__nav :deep(.role-sidebar__item--child) {
  min-height: 36px;
  margin-left: 12px;
  padding-left: 18px !important;
  font-size: 0.8rem;
}

.role-sidebar__nav :deep(.v-list-group__items) {
  border-left: 1px solid #e2e8f0;
  margin-left: 22px;
}

.role-sidebar__footer {
  position: absolute;
  right: 0;
  bottom: 0;
  left: 0;
  padding: 12px 14px 16px;
  border-top: 1px solid var(--border-line, rgba(226, 232, 240, 0.9));
  background: rgba(255, 255, 255, 0.9);
}

.role-user-card {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 10px;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #fff;
}

.role-app-bar {
  border-bottom: 1px solid var(--border-line, rgba(226, 232, 240, 0.9)) !important;
  background: rgba(255, 255, 255, 0.92) !important;
  backdrop-filter: blur(12px);
}

.role-app-bar__heading { line-height: 1.15; }
.role-app-bar__eyebrow {
  color: #94a3b8;
  font-size: 0.65rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}
.role-app-bar__title {
  margin-top: 3px;
  color: var(--color-ink, #0f172a);
  font-family: var(--font-display, 'Sora', sans-serif);
  font-size: 1rem;
  font-weight: 800;
}
.role-app-bar__icon { color: #64748b; }

.role-main { background: var(--color-paper, #f8fafc); }
.role-page-shell { min-height: calc(100vh - 72px); }
.role-page-content {
  width: 100%;
  max-width: 1560px;
  margin: 0 auto;
  padding: 30px 32px 44px;
  animation: role-fade-in 0.28s ease-out;
}

.role-page-content :deep(.v-card) {
  border: 1px solid var(--border-line, rgba(226, 232, 240, 0.9));
  border-radius: 16px !important;
  box-shadow: var(--shadow-card, 0 4px 18px rgba(15, 23, 42, 0.05));
}

.role-page-content :deep(.v-card-title) {
  padding: 20px 22px 12px;
  color: var(--color-ink, #0f172a);
  font-family: var(--font-display, 'Sora', sans-serif);
  font-size: 1rem;
  font-weight: 800;
}

.role-page-content :deep(.v-card-text) { padding: 18px 22px 22px; }
.role-page-content :deep(.v-list-item) { border-radius: 10px; }

.role-page-content :deep(.role-dashboard__header) {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 28px;
}

.role-page-content :deep(.role-dashboard__eyebrow) {
  margin-bottom: 6px;
  color: rgb(var(--v-theme-primary));
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.role-page-content :deep(.role-dashboard__title) {
  margin: 0;
  color: var(--color-ink, #0f172a);
  font-family: var(--font-display, 'Sora', sans-serif);
  font-size: clamp(1.45rem, 2vw, 2rem);
  font-weight: 800;
  letter-spacing: -0.035em;
}

.role-page-content :deep(.role-dashboard__subtitle) {
  max-width: 720px;
  margin: 7px 0 0;
  color: #64748b;
  font-size: 0.92rem;
}

.role-page-content :deep(.role-dashboard__kpis) {
  margin-bottom: 24px;
}

@media (max-width: 680px) {
  .role-page-content :deep(.role-dashboard__header) {
    align-items: flex-start;
    flex-direction: column;
    margin-bottom: 20px;
  }
}

@keyframes role-fade-in {
  from { opacity: 0; transform: translateY(6px); }
  to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 960px) {
  .role-page-content { padding: 24px 20px 36px; }
}

@media (max-width: 600px) {
  .role-page-content { padding: 18px 14px 28px; }
}
</style>
