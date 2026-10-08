<template>
  <v-dialog
    max-width="900"
    :model-value="modelValue"
    persistent
    scrollable
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <v-card>
      <v-card-title class="d-flex align-center pa-4 bg-primary">
        <v-icon start>mdi-clipboard-check-outline</v-icon>
        <span class="text-h5">Planifier un audit</span>
        <v-spacer />
        <v-btn icon="mdi-close" variant="text" @click="close" />
      </v-card-title>

      <v-divider />

      <!-- Stepper -->
      <v-stepper v-model="step" alt-labels :items="stepItems">
        <template #item.1>
          <v-card flat>
            <v-card-text>
              <h3 class="text-h6 mb-4">Informations générales</h3>

              <v-row>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="form.title"
                    density="comfortable"
                    label="Titre de l'audit *"
                    :rules="[required]"
                    variant="outlined"
                  />
                </v-col>

                <v-col cols="12" md="6">
                  <v-select
                    v-model="form.audit_type"
                    density="comfortable"
                    :items="auditTypes"
                    label="Type d'audit *"
                    :rules="[required]"
                    variant="outlined"
                  />
                </v-col>

                <v-col cols="12" md="6">
                  <v-select
                    v-model="form.site_id"
                    clearable
                    density="comfortable"
                    item-title="name"
                    item-value="id"
                    :items="sites"
                    label="Site"
                    variant="outlined"
                  />
                </v-col>

                <v-col cols="12" md="6">
                  <v-select
                    v-model="form.axes_qhse"
                    chips
                    density="comfortable"
                    :items="axesOptions"
                    label="Axes QHSE *"
                    multiple
                    :rules="[required]"
                    variant="outlined"
                  />
                </v-col>

                <v-col cols="12">
                  <v-textarea
                    v-model="form.objectives"
                    label="Objectifs"
                    rows="3"
                    variant="outlined"
                  />
                </v-col>

                <v-col cols="12">
                  <v-textarea
                    v-model="form.scope"
                    label="Périmètre"
                    rows="3"
                    variant="outlined"
                  />
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>
        </template>

        <template #item.2>
          <v-card flat>
            <v-card-text>
              <h3 class="text-h6 mb-4">Planning</h3>

              <v-row>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="form.planned_start_date"
                    density="comfortable"
                    label="Date de début *"
                    :rules="[required]"
                    type="date"
                    variant="outlined"
                  />
                </v-col>

                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="form.planned_end_date"
                    density="comfortable"
                    label="Date de fin *"
                    :rules="[required]"
                    type="date"
                    variant="outlined"
                  />
                </v-col>

                <v-col cols="12">
                  <v-alert density="compact" type="info" variant="tonal">
                    <template #prepend>
                      <v-icon>mdi-information</v-icon>
                    </template>
                    Durée estimée: {{ estimatedDuration }} jour(s)
                  </v-alert>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>
        </template>

        <template #item.3>
          <v-card flat>
            <v-card-text>
              <h3 class="text-h6 mb-4">Équipe d'audit</h3>

              <v-row>
                <v-col cols="12" md="6">
                  <v-autocomplete
                    v-model="form.lead_auditor_id"
                    density="comfortable"
                    item-title="name"
                    item-value="id"
                    :items="auditors"
                    label="Auditeur principal *"
                    :rules="[required]"
                    variant="outlined"
                  >
                    <template #item="{ props, item }">
                      <v-list-item v-bind="props">
                        <template #prepend>
                          <v-avatar color="primary">
                            <span>{{ getInitials(item.raw.name) }}</span>
                          </v-avatar>
                        </template>
                      </v-list-item>
                    </template>
                  </v-autocomplete>
                </v-col>

                <v-col cols="12" md="6">
                  <v-autocomplete
                    v-model="form.auditor_ids"
                    chips
                    density="comfortable"
                    item-title="name"
                    item-value="id"
                    :items="auditors"
                    label="Auditeurs complémentaires"
                    multiple
                    variant="outlined"
                  />
                </v-col>

                <v-col cols="12">
                  <v-autocomplete
                    v-model="form.auditee_ids"
                    chips
                    density="comfortable"
                    item-title="name"
                    item-value="id"
                    :items="users"
                    label="Audités"
                    multiple
                    variant="outlined"
                  />
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>
        </template>

        <template #item.4>
          <v-card flat>
            <v-card-text>
              <h3 class="text-h6 mb-4">Processus audités</h3>

              <v-row>
                <v-col cols="12">
                  <v-autocomplete
                    v-model="form.process_ids"
                    chips
                    density="comfortable"
                    item-title="name"
                    item-value="id"
                    :items="processes"
                    label="Sélectionner les processus"
                    multiple
                    variant="outlined"
                  >
                    <template #chip="{ props, item }">
                      <v-chip v-bind="props" :color="getProcessTypeColor(item.raw.type)">
                        {{ item.title }}
                      </v-chip>
                    </template>
                  </v-autocomplete>
                </v-col>

                <v-col cols="12">
                  <v-textarea
                    v-model="form.methodology"
                    hint="Décrivez la méthodologie d'audit (techniques, outils, etc.)"
                    label="Méthodologie"
                    rows="4"
                    variant="outlined"
                  />
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>
        </template>

        <template #item.5>
          <v-card flat>
            <v-card-text>
              <h3 class="text-h6 mb-4">Récapitulatif</h3>

              <v-list>
                <v-list-item>
                  <template #prepend>
                    <v-icon color="primary">mdi-file-document</v-icon>
                  </template>
                  <v-list-item-title>{{ form.title }}</v-list-item-title>
                  <v-list-item-subtitle>Titre</v-list-item-subtitle>
                </v-list-item>

                <v-list-item>
                  <template #prepend>
                    <v-icon color="primary">mdi-folder</v-icon>
                  </template>
                  <v-list-item-title>{{ getAuditTypeLabel(form.audit_type) }}</v-list-item-title>
                  <v-list-item-subtitle>Type</v-list-item-subtitle>
                </v-list-item>

                <v-list-item>
                  <template #prepend>
                    <v-icon color="primary">mdi-calendar-range</v-icon>
                  </template>
                  <v-list-item-title>
                    {{ formatDate(form.planned_start_date) }} → {{ formatDate(form.planned_end_date) }}
                  </v-list-item-title>
                  <v-list-item-subtitle>Période</v-list-item-subtitle>
                </v-list-item>

                <v-list-item>
                  <template #prepend>
                    <v-icon color="primary">mdi-account</v-icon>
                  </template>
                  <v-list-item-title>
                    {{ getAuditorName(form.lead_auditor_id) }}
                  </v-list-item-title>
                  <v-list-item-subtitle>Auditeur principal</v-list-item-subtitle>
                </v-list-item>

                <v-list-item v-if="form.process_ids && form.process_ids.length > 0">
                  <template #prepend>
                    <v-icon color="primary">mdi-sitemap</v-icon>
                  </template>
                  <v-list-item-title>
                    {{ form.process_ids.length }} processus
                  </v-list-item-title>
                  <v-list-item-subtitle>Périmètre</v-list-item-subtitle>
                </v-list-item>
              </v-list>

              <v-alert class="mt-4" type="success" variant="tonal">
                <template #prepend>
                  <v-icon>mdi-check-circle</v-icon>
                </template>
                Toutes les informations sont prêtes. Cliquez sur "Créer" pour planifier l'audit.
              </v-alert>
            </v-card-text>
          </v-card>
        </template>
      </v-stepper>

      <v-divider />

      <v-card-actions class="pa-4">
        <v-btn @click="close">Annuler</v-btn>
        <v-spacer />
        <v-btn
          v-if="step > 1"
          variant="outlined"
          @click="step--"
        >
          Précédent
        </v-btn>
        <v-btn
          v-if="step < 5"
          color="primary"
          :disabled="!isStepValid(step)"
          @click="step++"
        >
          Suivant
        </v-btn>
        <v-btn
          v-else
          color="success"
          :loading="loading"
          @click="submit"
        >
          Créer l'audit
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import type { Audit, CreateAuditPayload } from '@/types/audit'
  import { computed, onMounted, ref } from 'vue'
  import { useSnackbar } from '@/composables/useSnackbar'
  import { auditService } from '@/services/auditService'
  import processService from '@/services/processService'
  import { siteService } from '@/services/siteService'
  import { userService } from '@/services/userService'

  const _props = defineProps<{
    modelValue: boolean
  }>()

  const emit = defineEmits<{
    'update:modelValue': [value: boolean]
    'created': [audit: Audit]
  }>()

  const { showSuccess, showError } = useSnackbar()

  const step = ref(1)
  const loading = ref(false)
  const sites = ref<any[]>([])
  const auditors = ref<any[]>([])
  const users = ref<any[]>([])
  const processes = ref<any[]>([])

  const stepItems = [
    { title: 'Général', value: 1 },
    { title: 'Planning', value: 2 },
    { title: 'Équipe', value: 3 },
    { title: 'Processus', value: 4 },
    { title: 'Récapitulatif', value: 5 },
  ]

  const form = ref<CreateAuditPayload>({
    title: '',
    audit_type: 'internal_system',
    axes_qhse: [],
    planned_start_date: '',
    planned_end_date: '',
    lead_auditor_id: 0,
    auditor_ids: [],
    auditee_ids: [],
    process_ids: [],
    objectives: '',
    scope: '',
    methodology: '',
  })

  const auditTypes = [
    { title: 'Audit interne (système)', value: 'internal_system' },
    { title: 'Audit interne (processus)', value: 'internal_process' },
    { title: 'Audit externe (certification)', value: 'external_certification' },
    { title: 'Audit fournisseur', value: 'supplier' },
    { title: 'Audit thématique', value: 'thematic' },
  ]

  const axesOptions = [
    { title: 'Qualité (Q)', value: 'Q' },
    { title: 'Santé (H)', value: 'H' },
    { title: 'Sécurité (S)', value: 'S' },
    { title: 'Environnement (E)', value: 'E' },
  ]

  const estimatedDuration = computed(() => {
    if (!form.value.planned_start_date || !form.value.planned_end_date) return 0
    const start = new Date(form.value.planned_start_date)
    const end = new Date(form.value.planned_end_date)
    return Math.ceil((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24)) + 1
  })

  function required (v: any) {
    return !!v || 'Champ requis'
  }

  function isStepValid (currentStep: number): boolean {
    switch (currentStep) {
      case 1: {
        return !!(form.value.title && form.value.audit_type && form.value.axes_qhse.length > 0)
      }
      case 2: {
        return !!(form.value.planned_start_date && form.value.planned_end_date)
      }
      case 3: {
        return !!form.value.lead_auditor_id
      }
      default: {
        return true
      }
    }
  }

  async function submit () {
    loading.value = true
    try {
      const audit = await auditService.create(form.value)
      showSuccess('Audit créé avec succès')
      emit('created', audit)
      close()
    } catch (error) {
      showError('Erreur lors de la création de l\'audit')
      console.error(error)
    } finally {
      loading.value = false
    }
  }

  function close () {
    emit('update:modelValue', false)
    setTimeout(() => {
      step.value = 1
      resetForm()
    }, 300)
  }

  function resetForm () {
    form.value = {
      title: '',
      audit_type: 'internal_system',
      axes_qhse: [],
      planned_start_date: '',
      planned_end_date: '',
      lead_auditor_id: 0,
      auditor_ids: [],
      auditee_ids: [],
      process_ids: [],
      objectives: '',
      scope: '',
      methodology: '',
    }
  }

  function getInitials (name: string): string {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
  }

  function getProcessTypeColor (type: string): string {
    const colors: Record<string, string> = {
      management: 'primary',
      realization: 'success',
      support: 'info',
    }
    return colors[type] || 'grey'
  }

  function getAuditTypeLabel (type: string): string {
    const item = auditTypes.find(t => t.value === type)
    return item?.title || type
  }

  function formatDate (date: string): string {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR')
  }

  function getAuditorName (id: number): string {
    const auditor = auditors.value.find(a => a.id === id)
    return auditor?.name || '-'
  }

  onMounted(async () => {
    try {
      const [sitesRes, usersRes, processesRes] = await Promise.all([
        siteService.getAll(),
        userService.getAll(),
        processService.getProcesses(),
      ])

      sites.value = sitesRes.data || []
      users.value = usersRes.data || []
      auditors.value = users.value // Filtrer les auditeurs si besoin
      processes.value = processesRes.data || []
    } catch (error) {
      console.error('Erreur chargement données:', error)
    }
  })
</script>

<style scoped>
:deep(.v-stepper-header) {
  box-shadow: none;
}
</style>
