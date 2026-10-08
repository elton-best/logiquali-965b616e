<template>
  <RoleWorkspaceLayout accent="purple" dashboard-path="/auditor/dashboard" icon="mdi-clipboard-check-outline" page-title="Dashboard Auditeur" role-label="Espace auditeur" :nav-groups="navGroups">
    <slot />
  </RoleWorkspaceLayout>
</template>

<script setup lang="ts">
  import RoleWorkspaceLayout from './RoleWorkspaceLayout.vue'

  const navGroups = [
    { label: 'Mes audits', icon: 'mdi-clipboard-check-outline', children: [
      { label: 'Audits planifiés', to: '/auditor/audits/planned' },
      { label: 'Audits en cours', to: '/auditor/audits/ongoing' },
      { label: 'Audits terminés', to: '/auditor/audits/completed' },
      { label: 'Créer un audit', to: '/auditor/audits/create' },
    ] },
    { label: "Programme d'audit", icon: 'mdi-calendar-outline', to: '/auditor/program' },
    { label: 'Constats', icon: 'mdi-file-document-edit-outline', children: [
      { label: 'Saisir un constat', to: '/auditor/findings/create' },
      { label: 'Conformités', to: '/auditor/findings/conformities' },
      { label: 'Non-conformités', to: '/auditor/findings/nonconformities' },
      { label: 'Opportunités', to: '/auditor/findings/opportunities' },
    ] },
    { label: "NC issues d'audits", icon: 'mdi-alert-octagon-outline', to: '/auditor/nc-from-audits' },
    { label: 'Suivi des actions', icon: 'mdi-list-status', to: '/auditor/actions' },
    { label: 'Référentiels', icon: 'mdi-book-open-outline', children: [
      { label: 'Processus', to: '/auditor/references/processes' },
      { label: 'Documents', to: '/auditor/references/documents' },
      { label: 'Exigences ISO', to: '/auditor/references/iso' },
    ] },
    { label: 'Statistiques', icon: 'mdi-chart-bar', to: '/auditor/statistics' },
  ]
</script>
