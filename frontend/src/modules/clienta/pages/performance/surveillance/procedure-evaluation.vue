<template>
  <ClientALayout current-page="performance-surveillance">
    <v-container class="pa-6" fluid>
      <PageHeader icon="mdi-file-document-edit-outline" title="Procédure d'évaluation">
        <template #subtitle>
          Définition des règles, critères et fréquences d'évaluation.
        </template>
      </PageHeader>

      <v-card class="mt-6" rounded="xl">
        <v-card-text>
          <v-form @submit.prevent="saveProcedure">
            <v-row>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.reference" label="Référence" placeholder="PRC-EVAL-001" variant="outlined" />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.version" label="Version" placeholder="v1.0" variant="outlined" />
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="form.frequency"
                  :items="['Mensuelle', 'Trimestrielle', 'Semestrielle', 'Annuelle']"
                  label="Fréquence d'évaluation"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.approver" label="Validateur" variant="outlined" />
              </v-col>
              <v-col cols="12">
                <v-textarea v-model="form.scope" label="Périmètre" rows="2" variant="outlined" />
              </v-col>
              <v-col cols="12">
                <v-textarea v-model="form.criteria" label="Critères d'évaluation" rows="3" variant="outlined" />
              </v-col>
              <v-col cols="12">
                <v-textarea v-model="form.method" label="Méthode d'évaluation" rows="3" variant="outlined" />
              </v-col>
            </v-row>

            <div class="d-flex justify-end mt-4">
              <v-btn color="primary" type="submit">Enregistrer la procédure</v-btn>
            </div>
          </v-form>
        </v-card-text>
      </v-card>

      <v-card class="mt-6" rounded="xl">
        <v-card-title class="px-6 pt-6">Historique des procédures</v-card-title>
        <v-card-text>
          <v-data-table :headers="headers" :items="history" items-per-page="5" />
        </v-card-text>
      </v-card>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { reactive, ref } from 'vue'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import { useToast } from '@/modules/shared/composables/useToast'

  interface EvaluationProcedure {
    id: number
    reference: string
    version: string
    frequency: string
    approver: string
    scope: string
    criteria: string
    method: string
    updated_at: string
  }

  const toast = useToast()
  const storageKey = 'clienta_evaluation_procedures'
  const history = ref<EvaluationProcedure[]>([])

  const headers = [
    { title: 'Référence', key: 'reference' },
    { title: 'Version', key: 'version' },
    { title: 'Fréquence', key: 'frequency' },
    { title: 'Validateur', key: 'approver' },
    { title: 'Date MAJ', key: 'updated_at' },
  ]

  const form = reactive({
    reference: '',
    version: '',
    frequency: 'Trimestrielle',
    approver: '',
    scope: '',
    criteria: '',
    method: '',
  })

  function loadHistory () {
    try {
      const raw = localStorage.getItem(storageKey)
      history.value = raw ? JSON.parse(raw) : []
    } catch {
      history.value = []
    }
  }

  function persist () {
    localStorage.setItem(storageKey, JSON.stringify(history.value))
  }

  function saveProcedure () {
    if (!form.reference || !form.version || !form.approver) {
      toast.error('Référence, version et validateur sont requis.')
      return
    }

    const now = new Date()
    const item: EvaluationProcedure = {
      id: now.getTime(),
      reference: form.reference,
      version: form.version,
      frequency: form.frequency,
      approver: form.approver,
      scope: form.scope,
      criteria: form.criteria,
      method: form.method,
      updated_at: now.toISOString().slice(0, 10),
    }

    history.value.unshift(item)
    persist()
    toast.success('Procédure d’évaluation enregistrée.')
  }

  loadHistory()
</script>
