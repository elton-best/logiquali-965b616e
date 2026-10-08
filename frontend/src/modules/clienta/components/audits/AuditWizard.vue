<template>
  <v-dialog
    v-model="internalDialog"
    max-width="1200"
    persistent
    scrollable
  >
    <v-card>
      <v-card-title class="bg-primary text-white d-flex align-center">
        <v-icon class="mr-2">mdi-clipboard-check</v-icon>
        Assistant de création d'audit
        <v-spacer />
        <v-btn
          icon
          variant="text"
          @click="handleClose"
        >
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-card-title>

      <v-card-text class="pa-0">
        <v-stepper
          v-model="step"
          alt-labels
          class="elevation-0"
          :items="steps"
        >
          <!-- Step 1: Informations générales -->
          <v-stepper-window-item :value="1">
            <v-container>
              <h2 class="text-h6 mb-4">
                <v-icon class="mr-2">mdi-information</v-icon>
                Informations générales
              </h2>

              <v-row>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="form.audit_program_id"
                    item-title="title"
                    item-value="id"
                    :items="programs"
                    label="Programme d'audits *"
                    prepend-icon="mdi-folder-star"
                    :rules="[rules.required]"
                    variant="outlined"
                  />
                </v-col>

                <v-col cols="12" md="6">
                  <v-select
                    v-model="form.type"
                    :items="auditTypes"
                    label="Type d'audit *"
                    prepend-icon="mdi-format-list-bulleted-type"
                    :rules="[rules.required]"
                    variant="outlined"
                  />
                </v-col>

                <v-col cols="12">
                  <v-text-field
                    v-model="form.title"
                    counter="255"
                    label="Titre de l'audit *"
                    prepend-icon="mdi-format-title"
                    :rules="[rules.required]"
                    variant="outlined"
                  />
                </v-col>

                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="form.planned_date"
                    label="Date prévue *"
                    prepend-icon="mdi-calendar"
                    :rules="[rules.required]"
                    type="date"
                    variant="outlined"
                  />
                </v-col>

                <v-col cols="12" md="6">
                  <v-select
                    v-model="form.quarter"
                    :items="[1, 2, 3, 4]"
                    label="Trimestre"
                    prepend-icon="mdi-calendar-range"
                    variant="outlined"
                  />
                </v-col>

                <v-col cols="12" md="6">
                  <v-select
                    v-model="form.frequency"
                    :items="frequencies"
                    label="Fréquence"
                    prepend-icon="mdi-calendar-clock"
                    variant="outlined"
                  />
                </v-col>

                <v-col cols="12" md="6">
                  <v-select
                    v-model="form.lead_auditor_id"
                    item-title="name"
                    item-value="id"
                    :items="auditors"
                    label="Responsable d'audit *"
                    prepend-icon="mdi-account-star"
                    :rules="[rules.required]"
                    variant="outlined"
                  />
                </v-col>
              </v-row>
            </v-container>
          </v-stepper-window-item>

          <!-- Step 2: Périmètre et objectifs -->
          <v-stepper-window-item :value="2">
            <v-container>
              <h2 class="text-h6 mb-4">
                <v-icon class="mr-2">mdi-target</v-icon>
                Périmètre et objectifs
              </h2>

              <v-row>
                <v-col cols="12">
                  <v-textarea
                    v-model="form.scope"
                    hint="Domaines, processus, sites, départements concernés"
                    label="Périmètre de l'audit *"
                    persistent-hint
                    prepend-icon="mdi-radar"
                    rows="3"
                    :rules="[rules.required]"
                    variant="outlined"
                  />
                </v-col>

                <v-col cols="12">
                  <v-textarea
                    v-model="form.objectives"
                    hint="Ce que l'audit doit vérifier, améliorer ou certifier"
                    label="Objectifs de l'audit *"
                    persistent-hint
                    prepend-icon="mdi-bullseye-arrow"
                    rows="3"
                    :rules="[rules.required]"
                    variant="outlined"
                  />
                </v-col>

                <v-col cols="12">
                  <v-select
                    v-model="form.process_ids"
                    chips
                    closable-chips
                    item-title="name"
                    item-value="id"
                    :items="processes"
                    label="Processus concernés"
                    multiple
                    prepend-icon="mdi-sitemap"
                    variant="outlined"
                  />
                </v-col>

                <v-col cols="12">
                  <v-textarea
                    v-model="form.risk_based_criteria"
                    hint="Justification de la priorisation selon l'approche risques (ISO §6.1)"
                    label="Critères basés sur les risques"
                    persistent-hint
                    prepend-icon="mdi-alert-rhombus"
                    rows="2"
                    variant="outlined"
                  />
                </v-col>

                <v-col cols="12">
                  <v-select
                    v-model="form.qhse_axes"
                    chips
                    :items="qhseAxes"
                    label="Axes QHSE *"
                    multiple
                    prepend-icon="mdi-shield-check"
                    :rules="[rules.required]"
                    variant="outlined"
                  >
                    <template #selection="{ item }">
                      <v-chip :color="item.raw.color" size="small">
                        {{ item.title }}
                      </v-chip>
                    </template>
                  </v-select>
                </v-col>
              </v-row>
            </v-container>
          </v-stepper-window-item>

          <!-- Step 3: Équipe d'audit -->
          <v-stepper-window-item :value="3">
            <v-container>
              <h2 class="text-h6 mb-4">
                <v-icon class="mr-2">mdi-account-group</v-icon>
                Équipe d'audit
              </h2>

              <v-alert
                class="mb-6"
                type="info"
                variant="tonal"
              >
                <strong>ISO 9001:2015 §7.2 - Compétence :</strong>
                Les auditeurs doivent être compétents et indépendants du domaine audité.
              </v-alert>

              <v-row>
                <v-col cols="12">
                  <v-select
                    v-model="form.auditor_ids"
                    chips
                    closable-chips
                    item-title="name"
                    item-value="id"
                    :items="auditors"
                    label="Auditeurs *"
                    multiple
                    prepend-icon="mdi-account-multiple"
                    :rules="[rules.required]"
                    variant="outlined"
                  >
                    <template #item="{ props: optionProps, item }">
                      <v-list-item v-bind="optionProps">
                        <template #prepend>
                          <v-avatar :color="item.raw.qualified ? 'success' : 'warning'">
                            <v-icon>
                              {{ item.raw.qualified ? 'mdi-check' : 'mdi-alert' }}
                            </v-icon>
                          </v-avatar>
                        </template>
                        <template #append>
                          <v-chip
                            v-if="item.raw.independent"
                            color="success"
                            size="x-small"
                          >
                            Indépendant
                          </v-chip>
                        </template>
                      </v-list-item>
                    </template>
                  </v-select>
                </v-col>

                <v-col cols="12">
                  <v-select
                    v-model="form.auditee_ids"
                    chips
                    closable-chips
                    item-title="name"
                    item-value="id"
                    :items="auditees"
                    label="Audités *"
                    multiple
                    prepend-icon="mdi-account-tie"
                    :rules="[rules.required]"
                    variant="outlined"
                  />
                </v-col>

                <v-col cols="12">
                  <v-checkbox
                    v-model="sendInvitationsNow"
                    color="primary"
                    label="Envoyer les invitations immédiatement par email"
                  />
                </v-col>
              </v-row>
            </v-container>
          </v-stepper-window-item>

          <!-- Step 4: Checklist et planification -->
          <v-stepper-window-item :value="4">
            <v-container>
              <h2 class="text-h6 mb-4">
                <v-icon class="mr-2">mdi-playlist-check</v-icon>
                Checklist et planification
              </h2>

              <v-row>
                <v-col cols="12">
                  <v-checkbox
                    v-model="generateChecklistAuto"
                    color="primary"
                    label="Générer automatiquement la checklist depuis les clauses ISO"
                  />
                </v-col>

                <v-col v-if="generateChecklistAuto" cols="12">
                  <v-select
                    v-model="selectedIsoClauses"
                    chips
                    closable-chips
                    :items="isoClauses"
                    label="Clauses ISO à inclure"
                    multiple
                    prepend-icon="mdi-format-list-numbered"
                    variant="outlined"
                  />
                </v-col>

                <v-col cols="12">
                  <v-checkbox
                    v-model="includeProcessRisks"
                    color="primary"
                    label="Inclure les risques liés aux processus dans la checklist"
                  />
                </v-col>

                <v-col cols="12" md="6">
                  <v-text-field
                    v-model.number="form.estimated_duration_hours"
                    label="Durée estimée (heures)"
                    max="40"
                    min="1"
                    prepend-icon="mdi-clock-outline"
                    type="number"
                    variant="outlined"
                  />
                </v-col>

                <v-col cols="12" md="6">
                  <v-select
                    v-model="form.notification_settings"
                    chips
                    :items="notificationOptions"
                    label="Rappels automatiques"
                    multiple
                    prepend-icon="mdi-bell"
                    variant="outlined"
                  />
                </v-col>
              </v-row>
            </v-container>
          </v-stepper-window-item>

          <!-- Step 5: Récapitulatif -->
          <v-stepper-window-item :value="5">
            <v-container>
              <h2 class="text-h6 mb-4">
                <v-icon class="mr-2">mdi-file-document-check</v-icon>
                Récapitulatif
              </h2>

              <v-card class="mb-4" variant="outlined">
                <v-card-title class="bg-grey-lighten-4">
                  Informations générales
                </v-card-title>
                <v-card-text>
                  <v-row dense>
                    <v-col cols="6"><strong>Type:</strong></v-col>
                    <v-col cols="6">{{ form.type }}</v-col>
                    <v-col cols="6"><strong>Titre:</strong></v-col>
                    <v-col cols="6">{{ form.title }}</v-col>
                    <v-col cols="6"><strong>Date prévue:</strong></v-col>
                    <v-col cols="6">{{ form.planned_date }}</v-col>
                    <v-col cols="6"><strong>Responsable:</strong></v-col>
                    <v-col cols="6">{{ getAuditorName(form.lead_auditor_id) }}</v-col>
                  </v-row>
                </v-card-text>
              </v-card>

              <v-card class="mb-4" variant="outlined">
                <v-card-title class="bg-grey-lighten-4">
                  Périmètre
                </v-card-title>
                <v-card-text>
                  <p><strong>Objectifs:</strong> {{ form.objectives }}</p>
                  <p><strong>Périmètre:</strong> {{ form.scope }}</p>
                  <p><strong>Axes QHSE:</strong> {{ form.qhse_axes?.join(', ') }}</p>
                </v-card-text>
              </v-card>

              <v-card variant="outlined">
                <v-card-title class="bg-grey-lighten-4">
                  Équipe
                </v-card-title>
                <v-card-text>
                  <p><strong>Auditeurs:</strong> {{ form.auditor_ids?.length || 0 }} personnes</p>
                  <p><strong>Audités:</strong> {{ form.auditee_ids?.length || 0 }} personnes</p>
                  <v-alert
                    v-if="sendInvitationsNow"
                    density="compact"
                    type="info"
                    variant="tonal"
                  >
                    Les invitations seront envoyées après la création
                  </v-alert>
                </v-card-text>
              </v-card>
            </v-container>
          </v-stepper-window-item>
        </v-stepper>
      </v-card-text>

      <v-divider />

      <v-card-actions class="pa-4">
        <v-btn
          v-if="step > 1"
          variant="text"
          @click="step--"
        >
          <v-icon class="mr-2">mdi-chevron-left</v-icon>
          Précédent
        </v-btn>
        <v-spacer />
        <v-btn
          variant="text"
          @click="handleClose"
        >
          Annuler
        </v-btn>
        <v-btn
          v-if="step < 5"
          color="primary"
          variant="elevated"
          @click="step++"
        >
          Suivant
          <v-icon class="ml-2">mdi-chevron-right</v-icon>
        </v-btn>
        <v-btn
          v-else
          color="success"
          :loading="loading"
          variant="elevated"
          @click="handleSubmit"
        >
          <v-icon class="mr-2">mdi-check</v-icon>
          Créer l'audit
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'
  import { useAuditProgramStore } from '@/stores/improvement/auditProgramStore'
  import { useAuditStore } from '@/stores/improvement/auditStore'

  // Props
  const props = defineProps<{
    modelValue: boolean
    programId?: number
  }>()

  // Emits
  const emit = defineEmits<{
    'update:modelValue': [value: boolean]
    'created': [auditId: number]
  }>()

  const auditStore = useAuditStore()
  const programStore = useAuditProgramStore()

  // State
  const step = ref(1)
  const loading = ref(false)
  const sendInvitationsNow = ref(false)
  const generateChecklistAuto = ref(true)
  const includeProcessRisks = ref(true)
  const selectedIsoClauses = ref<string[]>(['4.4', '6.1', '8.1', '9.1', '9.2', '10.2'])
  const programs = ref<any[]>([])
  const auditors = ref<any[]>([])
  const auditees = ref<any[]>([])
  const processes = ref<any[]>([])

  const form = ref({
    audit_program_id: props.programId || null,
    site_id: 1,
    type: 'internal',
    title: '',
    planned_date: '',
    quarter: null,
    frequency: 'annual',
    lead_auditor_id: null,
    scope: '',
    objectives: '',
    risk_based_criteria: '',
    qhse_axes: ['quality'],
    auditor_ids: [],
    auditee_ids: [],
    process_ids: [],
    estimated_duration_hours: 4,
    notification_settings: ['7_days_before', '1_day_before'],
  })

  // Data
  const steps = [
    { title: 'Informations', value: 1 },
    { title: 'Périmètre', value: 2 },
    { title: 'Équipe', value: 3 },
    { title: 'Planification', value: 4 },
    { title: 'Récapitulatif', value: 5 },
  ]

  const auditTypes = [
    { value: 'internal', title: 'Interne' },
    { value: 'process', title: 'Processus' },
    { value: 'system', title: 'Système' },
    { value: 'product', title: 'Produit' },
    { value: 'supplier', title: 'Fournisseur' },
    { value: 'surveillance', title: 'Surveillance' },
  ]

  const frequencies = [
    { value: 'annual', title: 'Annuel' },
    { value: 'semi_annual', title: 'Semestriel' },
    { value: 'quarterly', title: 'Trimestriel' },
    { value: 'monthly', title: 'Mensuel' },
    { value: 'on_demand', title: 'À la demande' },
  ]

  const qhseAxes = [
    { value: 'quality', title: 'Qualité', color: 'blue' },
    { value: 'health', title: 'Santé', color: 'green' },
    { value: 'safety', title: 'Sécurité', color: 'orange' },
    { value: 'environment', title: 'Environnement', color: 'teal' },
  ]

  const isoClauses = [
    '4.1', '4.2', '4.3', '4.4',
    '5.1', '5.2', '5.3',
    '6.1', '6.2', '6.3',
    '7.1', '7.2', '7.3', '7.4', '7.5',
    '8.1', '8.2', '8.3', '8.4', '8.5', '8.6', '8.7',
    '9.1', '9.2', '9.3',
    '10.1', '10.2', '10.3',
  ]

  const notificationOptions = [
    { value: '7_days_before', title: '7 jours avant' },
    { value: '3_days_before', title: '3 jours avant' },
    { value: '1_day_before', title: '1 jour avant' },
    { value: 'on_day', title: 'Le jour même' },
  ]

  const rules = {
    required: (v: any) => !!v || 'Champ requis',
  }

  // Computed
  const internalDialog = computed({
    get: () => props.modelValue,
    set: val => emit('update:modelValue', val),
  })

  // Methods
  function getAuditorName (id: number | null) {
    if (!id) return 'Non défini'
    return auditors.value.find(a => a.id === id)?.name || 'Inconnu'
  }

  async function handleSubmit () {
    loading.value = true
    try {
      const audit = await auditStore.createAudit(form.value)

      // Générer checklist si demandé
      if (generateChecklistAuto.value && selectedIsoClauses.value.length > 0) {
        await auditStore.generateChecklist(
          audit.id,
          selectedIsoClauses.value,
          includeProcessRisks.value,
        )
      }

      // Envoyer invitations si demandé
      if (sendInvitationsNow.value) {
        await auditStore.sendInvitations(audit.id)
      }

      emit('created', audit.id)
      internalDialog.value = false
      resetForm()
    } catch (error) {
      console.error('Erreur:', error)
    } finally {
      loading.value = false
    }
  }

  function handleClose () {
    internalDialog.value = false
    resetForm()
  }

  function resetForm () {
    step.value = 1
    form.value = {
      audit_program_id: props.programId || null,
      site_id: 1,
      type: 'internal',
      title: '',
      planned_date: '',
      quarter: null,
      frequency: 'annual',
      lead_auditor_id: null,
      scope: '',
      objectives: '',
      risk_based_criteria: '',
      qhse_axes: ['quality'],
      auditor_ids: [],
      auditee_ids: [],
      process_ids: [],
      estimated_duration_hours: 4,
      notification_settings: ['7_days_before', '1_day_before'],
    }
  }

  async function loadData () {
    // Charger programmes
    await programStore.fetchPrograms()
    programs.value = programStore.programs

    // Charger auditeurs, audités, processus (mock pour l'instant)
    auditors.value = [
      { id: 1, name: 'Jean Dupont', qualified: true, independent: true },
      { id: 2, name: 'Marie Martin', qualified: true, independent: true },
      { id: 3, name: 'Pierre Durand', qualified: false, independent: true },
    ]

    auditees.value = [
      { id: 4, name: 'Sophie Bernard' },
      { id: 5, name: 'Luc Moreau' },
    ]

    processes.value = [
      { id: 1, name: 'Achats' },
      { id: 2, name: 'Production' },
      { id: 3, name: 'Ventes' },
    ]
  }

  onMounted(() => {
    loadData()
  })
</script>

<style scoped>
.v-stepper {
  box-shadow: none !important;
}
</style>
