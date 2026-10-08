<template>
  <RoleWorkspaceLayout accent="info" dashboard-path="/process/dashboard" icon="mdi-sync-circle" page-title="Dashboard Processus" role-label="Espace pilote processus" :nav-groups="navGroups">
    <slot />
  </RoleWorkspaceLayout>
</template>

<script setup lang="ts">
  import RoleWorkspaceLayout from './RoleWorkspaceLayout.vue'

  const navGroups = [
    { label: 'Mes processus', icon: 'mdi-sync-circle', children: [
      { label: "Vue d'ensemble", to: '/process/my-processes' },
      { label: 'Cartographie', to: '/process/cartography' },
      { label: 'Interactions', to: '/process/interactions' },
    ] },
    { label: 'Activités', icon: 'mdi-format-list-checks', children: [
      { label: 'Liste des activités', to: '/process/activities' },
      { label: 'Séquencement', to: '/process/activities/sequence' },
      { label: 'Entrées fournisseurs', to: '/process/activities/suppliers' },
      { label: 'Sorties clients', to: '/process/activities/clients' },
    ] },
    { label: 'Indicateurs', icon: 'mdi-chart-line', to: '/process/indicators' },
    { label: 'Risques processus', icon: 'mdi-alert-outline', to: '/process/risks' },
    { label: "Actions d'amélioration", icon: 'mdi-list-status', to: '/process/actions' },
    { label: 'Ressources', icon: 'mdi-briefcase-outline', to: '/process/resources' },
    { label: 'Documents', icon: 'mdi-folder-outline', to: '/process/documents' },
  ]
</script>
