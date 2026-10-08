<template>
  <RoleWorkspaceLayout accent="success" dashboard-path="/quality/dashboard" icon="mdi-shield-check-outline" page-title="Dashboard Qualité" role-label="Espace responsable qualité" :nav-groups="navGroups">
    <slot />
  </RoleWorkspaceLayout>
</template>

<script setup lang="ts">
  import RoleWorkspaceLayout from './RoleWorkspaceLayout.vue'

  const navGroups = [
    { label: 'Audits', icon: 'mdi-clipboard-check-outline', children: [
      { label: "Programme d'audit", to: '/quality/audits/program' },
      { label: 'Audits planifiés', to: '/quality/audits' },
      { label: 'Constats', to: '/quality/audits/findings' },
    ] },
    { label: 'Non-conformités', icon: 'mdi-alert-octagon-outline', to: '/quality/nonconformities' },
    { label: 'Actions', icon: 'mdi-list-status', to: '/quality/actions' },
    { label: 'Risques & opportunités', icon: 'mdi-alert-outline', to: '/quality/risks' },
    { label: 'Indicateurs qualité', icon: 'mdi-chart-line', to: '/quality/indicators' },
    { label: 'Documents qualité', icon: 'mdi-folder-outline', to: '/quality/documents' },
    { label: 'Réclamations', icon: 'mdi-phone-alert-outline', to: '/quality/complaints' },
    { label: 'Revues de direction', icon: 'mdi-account-tie-outline', to: '/quality/reviews' },
  ]
</script>
