<template>
  <RoleWorkspaceLayout accent="grey" dashboard-path="/viewer/dashboard" icon="mdi-eye-outline" page-title="Dashboard" role-label="Espace consultation" :nav-groups="navGroups">
    <slot />
  </RoleWorkspaceLayout>
</template>

<script setup lang="ts">
  import RoleWorkspaceLayout from './RoleWorkspaceLayout.vue'

  const navGroups = [
    { label: 'Processus', icon: 'mdi-sync-circle', to: '/viewer/processes' },
    { label: 'Documents', icon: 'mdi-folder-outline', to: '/viewer/documents' },
    { label: 'Indicateurs', icon: 'mdi-chart-line', to: '/viewer/indicators' },
  ]
</script>
