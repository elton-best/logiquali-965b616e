<template>
  <RoleWorkspaceLayout accent="orange" dashboard-path="/employee/dashboard" icon="mdi-account-outline" page-title="Mon dashboard" role-label="Espace collaborateur" :nav-groups="navGroups">
    <slot />
  </RoleWorkspaceLayout>
</template>

<script setup lang="ts">
  import RoleWorkspaceLayout from './RoleWorkspaceLayout.vue'

  const navGroups = [
    { label: 'Mes actions', icon: 'mdi-format-list-checks', children: [
      { label: 'Actions en cours', to: '/employee/actions/ongoing' },
      { label: 'Actions terminées', to: '/employee/actions/completed' },
      { label: 'Historique', to: '/employee/actions/history' },
    ] },
    { label: 'Mes formations', icon: 'mdi-school-outline', children: [
      { label: 'Formations suivies', to: '/employee/trainings/completed' },
      { label: 'Formations planifiées', to: '/employee/trainings/planned' },
      { label: 'Mes certificats', to: '/employee/trainings/certificates' },
    ] },
    { label: 'Documents', icon: 'mdi-folder-outline', children: [
      { label: 'Documents qualité', to: '/employee/documents/quality' },
      { label: 'Procédures', to: '/employee/documents/procedures' },
      { label: 'Instructions', to: '/employee/documents/instructions' },
    ] },
    { label: 'Processus', icon: 'mdi-sync-circle', to: '/employee/processes' },
    { label: 'Signaler une NC', icon: 'mdi-alert-octagon-outline', to: '/employee/report-nc' },
    { label: 'Communications', icon: 'mdi-message-outline', to: '/employee/communications' },
    { label: 'Mes indicateurs', icon: 'mdi-chart-line', to: '/employee/indicators' },
    { label: 'Mon profil', icon: 'mdi-account-circle-outline', children: [
      { label: 'Informations', to: '/employee/profile/info' },
      { label: 'Mes compétences', to: '/employee/profile/skills' },
      { label: 'Mes habilitations', to: '/employee/profile/certifications' },
    ] },
  ]
</script>
