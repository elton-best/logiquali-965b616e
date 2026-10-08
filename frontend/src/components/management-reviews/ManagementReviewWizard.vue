<template>
  <v-dialog v-model="dialog" max-width="900px" persistent scrollable>
    <template #activator="{ props }">
      <slot name="activator" :props="props">
        <v-btn color="primary" v-bind="props">
          <v-icon left>mdi-plus</v-icon>
          Nouvelle Revue de Direction
        </v-btn>
      </slot>
    </template>

    <v-card>
      <v-card-title class="d-flex align-center pa-4">
        <v-icon class="mr-3" color="primary">mdi-clipboard-check</v-icon>
        <span>Créer une Revue de Direction</span>
        <v-spacer />
        <v-btn icon variant="text" @click="closeDialog">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-card-title>

      <v-divider />

      <v-stepper v-model="step" alt-labels class="elevation-0">
        <v-stepper-header>
          <v-stepper-item :color="step === 1 ? 'primary' : 'grey'" :complete="step > 1" title="Informations" :value="1">
            <template #icon><v-icon>mdi-information</v-icon></template>
          </v-stepper-item>
          <v-divider />
          <v-stepper-item :color="step === 2 ? 'primary' : 'grey'" :complete="step > 2" title="Participants" :value="2">
            <template #icon><v-icon>mdi-account-group</v-icon></template>
          </v-stepper-item>
          <v-divider />
          <v-stepper-item :color="step === 3 ? 'primary' : 'grey'" :complete="step > 3" title="KPI & Données" :value="3">
            <template #icon><v-icon>mdi-chart-line</v-icon></template>
          </v-stepper-item>
        </v-stepper-header>

        <v-card-text class="pa-6" style="min-height: 400px;">
          <!-- Step 1: General Info -->
          <div v-show="step === 1">
            <v-form ref="formStep1">
              <v-row>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="formData.year"
                    density="comfortable"
                    hint="Année de la revue de direction"
                    :items="years"
                    label="Année *"
                    persistent-hint
                    prepend-inner-icon="mdi-calendar"
                    :rules="[v => !!v || 'Année requise']"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="formData.quarter"
                    density="comfortable"
                    hint="Trimestre concerné"
                    :items="quarters"
                    label="Trimestre *"
                    persistent-hint
                    prepend-inner-icon="mdi-calendar-range"
                    :rules="[v => !!v || 'Trimestre requis']"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12">
                  <v-text-field
                    v-model="formData.title"
                    density="comfortable"
                    hint="Ex: Revue de Direction Q1 2026"
                    label="Titre de la revue *"
                    persistent-hint
                    prepend-inner-icon="mdi-text"
                    :rules="[v => !!v || 'Titre requis']"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="formData.planned_date"
                    density="comfortable"
                    hint="Date prévue de la revue"
                    label="Date planifiée *"
                    persistent-hint
                    prepend-inner-icon="mdi-calendar-clock"
                    :rules="[v => !!v || 'Date requise']"
                    type="date"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="formData.status"
                    density="comfortable"
                    :items="statuses"
                    label="Statut *"
                    prepend-inner-icon="mdi-progress-check"
                    :rules="[v => !!v || 'Statut requis']"
                    variant="outlined"
                  />
                </v-col>
              </v-row>
            </v-form>
          </div>

          <!-- Step 2: Participants -->
          <div v-show="step === 2">
            <v-form ref="formStep2">
              <v-row>
                <v-col cols="12">
                  <v-select
                    v-model="formData.chairman_id"
                    density="comfortable"
                    hint="Responsable de la revue de direction"
                    item-title="name"
                    item-value="id"
                    :items="users"
                    label="Président de séance *"
                    persistent-hint
                    prepend-inner-icon="mdi-account-star"
                    :rules="[v => !!v || 'Président requis']"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12">
                  <v-select
                    v-model="formData.participants"
                    chips
                    closable-chips
                    density="comfortable"
                    hint="Sélectionnez les participants à la revue"
                    item-title="name"
                    item-value="id"
                    :items="users"
                    label="Participants *"
                    multiple
                    persistent-hint
                    prepend-inner-icon="mdi-account-group"
                    :rules="[v => (v && v.length > 0) || 'Au moins 1 participant requis']"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12">
                  <v-textarea
                    v-model="formData.participants_notes"
                    density="comfortable"
                    hint="Informations complémentaires sur les participants"
                    label="Notes participants (optionnel)"
                    persistent-hint
                    prepend-inner-icon="mdi-note-text"
                    rows="3"
                    variant="outlined"
                  />
                </v-col>
              </v-row>
            </v-form>
          </div>

          <!-- Step 3: KPI & Data Selection -->
          <div v-show="step === 3">
            <v-form ref="formStep3">
              <v-row>
                <v-col cols="12">
                  <h3 class="text-h6 mb-3">
                    <v-icon class="mr-2" color="primary">mdi-chart-box</v-icon>
                    Sélection des KPI à inclure
                  </h3>
                </v-col>

                <!-- Quality KPI -->
                <v-col cols="12" md="6">
                  <v-checkbox
                    v-model="formData.include_quality_kpi"
                    color="primary"
                    density="comfortable"
                    hide-details
                    label="Taux de Qualité"
                  >
                    <template #label>
                      <div>
                        <div class="font-weight-medium">Taux de Qualité</div>
                        <div class="text-caption text-grey">Indicateurs conformité produits/services</div>
                      </div>
                    </template>
                  </v-checkbox>
                </v-col>

                <!-- Customer Satisfaction KPI -->
                <v-col cols="12" md="6">
                  <v-checkbox
                    v-model="formData.include_satisfaction_kpi"
                    color="primary"
                    density="comfortable"
                    hide-details
                    label="Satisfaction Client"
                  >
                    <template #label>
                      <div>
                        <div class="font-weight-medium">Satisfaction Client</div>
                        <div class="text-caption text-grey">Enquêtes et réclamations clients</div>
                      </div>
                    </template>
                  </v-checkbox>
                </v-col>

                <!-- Delivery KPI -->
                <v-col cols="12" md="6">
                  <v-checkbox
                    v-model="formData.include_delivery_kpi"
                    color="primary"
                    density="comfortable"
                    hide-details
                    label="Délais de Livraison"
                  >
                    <template #label>
                      <div>
                        <div class="font-weight-medium">Délais de Livraison</div>
                        <div class="text-caption text-grey">Performance respect des délais</div>
                      </div>
                    </template>
                  </v-checkbox>
                </v-col>

                <!-- NC Count KPI -->
                <v-col cols="12" md="6">
                  <v-checkbox
                    v-model="formData.include_nc_kpi"
                    color="primary"
                    density="comfortable"
                    hide-details
                    label="Non-Conformités"
                  >
                    <template #label>
                      <div>
                        <div class="font-weight-medium">Non-Conformités</div>
                        <div class="text-caption text-grey">Nombre et analyse des NC</div>
                      </div>
                    </template>
                  </v-checkbox>
                </v-col>

                <v-col class="mt-4" cols="12">
                  <v-divider />
                </v-col>

                <!-- Additional Data -->
                <v-col cols="12">
                  <h3 class="text-h6 mb-3">
                    <v-icon class="mr-2" color="primary">mdi-database</v-icon>
                    Données complémentaires
                  </h3>
                </v-col>

                <v-col cols="12" md="4">
                  <v-checkbox
                    v-model="formData.include_actions"
                    color="primary"
                    density="comfortable"
                    hide-details
                    label="Actions en cours"
                  />
                </v-col>

                <v-col cols="12" md="4">
                  <v-checkbox
                    v-model="formData.include_risks"
                    color="primary"
                    density="comfortable"
                    hide-details
                    label="Risques identifiés"
                  />
                </v-col>

                <v-col cols="12" md="4">
                  <v-checkbox
                    v-model="formData.include_objectives"
                    color="primary"
                    density="comfortable"
                    hide-details
                    label="Objectifs"
                  />
                </v-col>

                <v-col cols="12">
                  <v-textarea
                    v-model="formData.notes"
                    density="comfortable"
                    hint="Informations additionnelles pour cette revue"
                    label="Notes complémentaires (optionnel)"
                    persistent-hint
                    prepend-inner-icon="mdi-note"
                    rows="4"
                    variant="outlined"
                  />
                </v-col>
              </v-row>
            </v-form>
          </div>
        </v-card-text>

        <v-divider />

        <v-card-actions class="pa-4">
          <v-btn
            v-if="step > 1"
            variant="text"
            @click="step--"
          >
            <v-icon left>mdi-chevron-left</v-icon>
            Précédent
          </v-btn>
          <v-spacer />
          <v-btn variant="text" @click="closeDialog">Annuler</v-btn>
          <v-btn
            v-if="step < 3"
            color="primary"
            @click="nextStep"
          >
            Suivant
            <v-icon right>mdi-chevron-right</v-icon>
          </v-btn>
          <v-btn
            v-if="step === 3"
            color="success"
            :loading="loading"
            @click="save"
          >
            <v-icon left>mdi-check</v-icon>
            Créer la Revue
          </v-btn>
        </v-card-actions>
      </v-stepper>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import { reactive, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import { useToast } from 'vue-toastification'
  import { useManagementReviewStore } from '@/stores/managementReviewStore'

  const router = useRouter()
  const store = useManagementReviewStore()
  const toast = useToast()

  const dialog = ref(false)
  const step = ref(1)
  const loading = ref(false)

  const formStep1 = ref<any>(null)
  const formStep2 = ref<any>(null)
  const formStep3 = ref<any>(null)

  // Mock users - should be loaded from store
  const users = ref([
    { id: 1, name: 'Jean Dupont' },
    { id: 2, name: 'Marie Martin' },
    { id: 3, name: 'Pierre Durand' },
  ])

  const currentYear = new Date().getFullYear()
  const years = Array.from({ length: 5 }, (_, i) => currentYear - 2 + i)

  const quarters = [
    { title: 'Q1 (Janvier - Mars)', value: 'Q1' },
    { title: 'Q2 (Avril - Juin)', value: 'Q2' },
    { title: 'Q3 (Juillet - Septembre)', value: 'Q3' },
    { title: 'Q4 (Octobre - Décembre)', value: 'Q4' },
  ]

  const statuses = [
    { title: 'Planifiée', value: 'planned' },
    { title: 'En cours', value: 'in_progress' },
    { title: 'Terminée', value: 'completed' },
    { title: 'Reportée', value: 'reported' },
  ]

  const formData = reactive({
    year: currentYear,
    quarter: 'Q1',
    title: '',
    planned_date: '',
    status: 'planned',
    chairman_id: null,
    participants: [],
    participants_notes: '',
    include_quality_kpi: true,
    include_satisfaction_kpi: true,
    include_delivery_kpi: true,
    include_nc_kpi: true,
    include_actions: true,
    include_risks: true,
    include_objectives: true,
    notes: '',
  })

  async function nextStep () {
    let isValid = false

    if (step.value === 1 && formStep1.value) {
      const { valid } = await formStep1.value.validate()
      isValid = valid
    } else if (step.value === 2 && formStep2.value) {
      const { valid } = await formStep2.value.validate()
      isValid = valid
    }

    if (isValid) {
      step.value++
    }
  }

  async function save () {
    if (formStep3.value) {
      const { valid } = await formStep3.value.validate()
      if (!valid) return
    }

    loading.value = true
    try {
      const payload = {
        ...formData,
        site_id: 1, // TODO: Get from auth store
      }

      await store.createReview(payload as any)
      toast.success('Revue de direction créée avec succès')
      closeDialog()
      router.push('/management-reviews')
    } catch (error) {
      console.error('Error creating review:', error)
      toast.error('Erreur lors de la création de la revue')
    } finally {
      loading.value = false
    }
  }

  function closeDialog () {
    dialog.value = false
    step.value = 1
    if (formStep1.value) formStep1.value.reset()
    if (formStep2.value) formStep2.value.reset()
    if (formStep3.value) formStep3.value.reset()
  }
</script>
