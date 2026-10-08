<template>
  <OperationsSubmoduleWorkspace
    :checklist="checklist"
    :deliverables="deliverables"
    hero-description="Structurez les exigences clients, normatives et reglementaires avant lancement et tout au long du cycle de vie."
    hero-title="Securiser les exigences produits et services"
    icon="mdi-clipboard-text-search-outline"
    module-key="product_service_requirements"
    :seed-items="seedItems"
    subtitle="Exigences et preuves de conformite"
    title="Exigences relatives aux produits et services"
  >
    <template #actions>
      <v-btn
        color="primary"
        prepend-icon="mdi-file-document-plus-outline"
        variant="tonal"
        @click="procedureDialog = true"
      >
        Ajouter une procédure
      </v-btn>
    </template>

    <template #procedures>
      <!-- <ProceduresPanel ref="proceduresPanel" /> -->
    </template>
  </OperationsSubmoduleWorkspace>

  <ProcedureUploadDialog v-model="procedureDialog" @created="proceduresPanel?.refresh()" />
</template>

<script setup lang="ts">
  import type ProceduresPanel from '@/modules/clienta/components/documents/ProceduresPanel.vue'
  import { ref } from 'vue'
  import ProcedureUploadDialog from '@/modules/clienta/components/documents/ProcedureUploadDialog.vue'
  import OperationsSubmoduleWorkspace from '@/modules/clienta/components/operations/OperationsSubmoduleWorkspace.vue'

  const procedureDialog = ref(false)
  const proceduresPanel = ref<InstanceType<typeof ProceduresPanel> | null>(null)

  const checklist = [
    'Recenser les exigences clients explicites et implicites.',
    'Verifier les obligations normatives et reglementaires applicables.',
    'Valider la faisabilite technique avant engagement.',
    'Tracer les modifications d\'exigences et leurs impacts.',
    'Confirmer l\'acceptation finale avec preuves documentees.',
  ]

  const deliverables = [
    'Matrice des exigences',
    'Analyse de faisabilite',
    'Historique des changements',
    'Preuves de validation client',
  ]

  const seedItems = [
    {
      reference: 'ISO8.2-REQ-001',
      action: 'Verifier les exigences contractuelles du nouveau service',
      process: 'Commercial / Qualite',
      owner: 'Responsable Commercial',
      status: 'planned',
      deadline: '',
      evidence: 'Compte-rendu de revue contractuelle',
    },
    {
      reference: 'ISO8.2-REQ-002',
      action: 'Valider les exigences reglementaires avant demarrage',
      process: 'Conformite / Operations',
      owner: 'Responsable Conformite',
      status: 'in_progress',
      deadline: '',
      evidence: 'Check-list reglementaire approuvee',
    },
  ]
</script>
