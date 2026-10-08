<template>
  <RoleWorkspaceLayout accent="primary" dashboard-path="/site/dashboard" icon="mdi-map-marker-outline" page-title="Dashboard Site" role-label="Espace responsable de site" :nav-groups="navGroups">
    <slot />
  </RoleWorkspaceLayout>
</template>

<script setup lang="ts">
  import RoleWorkspaceLayout from './RoleWorkspaceLayout.vue'

  const navGroups = [
    { label: 'Équipe du site', icon: 'mdi-account-group-outline', to: '/site/team' },
    { label: 'Processus', icon: 'mdi-sync-circle', children: [
      { label: 'Cartographie', to: '/site/processes/cartography' },
      { label: 'Liste des processus', to: '/site/processes' },
    ] },
    { label: 'Audits', icon: 'mdi-clipboard-check-outline', to: '/site/audits' },
    { label: 'Non-conformités', icon: 'mdi-alert-octagon-outline', to: '/site/nonconformities' },
    { label: 'Actions', icon: 'mdi-list-status', to: '/site/actions' },
  ]
</script>
