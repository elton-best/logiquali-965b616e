<template>
  <v-dialog v-model="dialog" max-width="1000" persistent scrollable>
    <template #activator="{ props: activatorProps }">
      <slot name="activator" :props="activatorProps">
        <v-btn v-bind="activatorProps" :color="wizardType === 'swot' ? 'blue' : 'green'" size="large">
          <v-icon left>{{ wizardType === 'swot' ? 'mdi-chart-box-outline' : 'mdi-radar' }}</v-icon>
          {{ wizardType === 'swot' ? 'Créer Analyse SWOT' : 'Créer Analyse PESTEL' }}
        </v-btn>
      </slot>
    </template>

    <v-card>
      <v-card-title class="d-flex align-center pa-4">
        <v-icon class="mr-3" :color="wizardType === 'swot' ? 'blue' : 'green'" size="large">
          {{ wizardType === 'swot' ? 'mdi-chart-box-outline' : 'mdi-radar' }}
        </v-icon>
        <span class="text-h5">
          Analyse {{ wizardType === 'swot' ? 'SWOT' : 'PESTEL' }} - {{ currentYear }}
        </span>
      </v-card-title>

      <v-divider />

      <v-stepper v-model="step" alt-labels class="elevation-0">
        <v-stepper-header>
          <v-stepper-item title="Informations Générales" :value="1" />
          <v-divider />
          <v-stepper-item
            :title="wizardType === 'swot' ? 'Forces & Faiblesses' : 'Politique & Économique'"
            :value="2"
          />
          <v-divider />
          <v-stepper-item
            :title="wizardType === 'swot' ? 'Opportunités & Menaces' : 'Social & Technologique'"
            :value="3"
          />
          <v-divider v-if="wizardType === 'pestel'" />
          <v-stepper-item
            v-if="wizardType === 'pestel'"
            title="Environnement & Légal"
            :value="4"
          />
        </v-stepper-header>

        <v-divider />

        <v-card-text class="pa-6" style="max-height: 500px">
          <!-- Step 1: General Info -->
          <div v-show="step === 1">
            <v-alert class="mb-4" type="info" variant="tonal">
              <div class="text-body-2">
                <strong>{{ wizardType === 'swot' ? 'SWOT' : 'PESTEL' }}</strong>:
                {{ wizardType === 'swot'
                  ? 'Analysez vos Forces (Strengths), Faiblesses (Weaknesses), Opportunités (Opportunities) et Menaces (Threats)'
                  : 'Analysez les facteurs Politiques, Économiques, Sociaux, Technologiques, Environnementaux et Légaux'
                }}
              </div>
            </v-alert>

            <v-row>
              <v-col cols="12">
                <v-text-field
                  v-model="form.title"
                  density="comfortable"
                  hint="Ex: Analyse SWOT 2026, Analyse stratégique Q1 2026"
                  label="Titre de l'analyse *"
                  :rules="[rules.required]"
                  variant="outlined"
                />
              </v-col>

              <v-col cols="12" md="6">
                <v-text-field
                  v-model.number="form.year"
                  density="comfortable"
                  label="Année *"
                  :rules="[rules.required]"
                  type="number"
                  variant="outlined"
                />
              </v-col>

              <v-col cols="12" md="6">
                <v-select
                  v-model="form.category"
                  density="comfortable"
                  :items="categoryOptions"
                  label="Catégorie *"
                  :rules="[rules.required]"
                  variant="outlined"
                />
              </v-col>

              <v-col cols="12">
                <v-textarea
                  v-model="form.description"
                  density="comfortable"
                  hint="Décrivez le contexte de cette analyse"
                  label="Description / Contexte"
                  rows="4"
                  variant="outlined"
                />
              </v-col>
            </v-row>
          </div>

          <!-- Step 2: SWOT (Forces/Faiblesses) or PESTEL (Politique/Économique) -->
          <div v-show="step === 2">
            <v-row v-if="wizardType === 'swot'">
              <v-col cols="12" md="6">
                <div class="d-flex align-center mb-2">
                  <v-icon class="mr-2" color="green">mdi-shield-check</v-icon>
                  <span class="text-h6">Forces (Strengths)</span>
                </div>
                <v-textarea
                  v-model="form.swot_strengths"
                  density="comfortable"
                  hint="Une force par ligne. Ex:&#10;- Équipe qualifiée et expérimentée&#10;- Certification ISO 9001 obtenue&#10;- Processus bien documentés"
                  label="Forces internes *"
                  persistent-hint
                  rows="12"
                  :rules="[rules.required]"
                  variant="outlined"
                />
              </v-col>

              <v-col cols="12" md="6">
                <div class="d-flex align-center mb-2">
                  <v-icon class="mr-2" color="orange">mdi-alert</v-icon>
                  <span class="text-h6">Faiblesses (Weaknesses)</span>
                </div>
                <v-textarea
                  v-model="form.swot_weaknesses"
                  density="comfortable"
                  hint="Une faiblesse par ligne. Ex:&#10;- Manque de ressources formation&#10;- Système informatique obsolète&#10;- Turn-over élevé"
                  label="Faiblesses internes *"
                  persistent-hint
                  rows="12"
                  :rules="[rules.required]"
                  variant="outlined"
                />
              </v-col>
            </v-row>

            <v-row v-else>
              <v-col cols="12" md="6">
                <div class="d-flex align-center mb-2">
                  <v-icon class="mr-2" color="blue">mdi-bank</v-icon>
                  <span class="text-h6">Politique</span>
                </div>
                <v-textarea
                  v-model="form.pestel_political"
                  density="comfortable"
                  hint="Lois, réglementations, stabilité gouvernementale..."
                  label="Facteurs politiques *"
                  rows="10"
                  :rules="[rules.required]"
                  variant="outlined"
                />
              </v-col>

              <v-col cols="12" md="6">
                <div class="d-flex align-center mb-2">
                  <v-icon class="mr-2" color="green">mdi-currency-eur</v-icon>
                  <span class="text-h6">Économique</span>
                </div>
                <v-textarea
                  v-model="form.pestel_economic"
                  density="comfortable"
                  hint="Inflation, taux de change, croissance économique..."
                  label="Facteurs économiques *"
                  rows="10"
                  :rules="[rules.required]"
                  variant="outlined"
                />
              </v-col>
            </v-row>
          </div>

          <!-- Step 3: SWOT (Opportunités/Menaces) or PESTEL (Social/Techno) -->
          <div v-show="step === 3">
            <v-row v-if="wizardType === 'swot'">
              <v-col cols="12" md="6">
                <div class="d-flex align-center mb-2">
                  <v-icon class="mr-2" color="blue">mdi-trending-up</v-icon>
                  <span class="text-h6">Opportunités (Opportunities)</span>
                </div>
                <v-textarea
                  v-model="form.swot_opportunities"
                  density="comfortable"
                  hint="Une opportunité par ligne. Ex:&#10;- Nouveaux marchés émergents&#10;- Technologies innovantes disponibles&#10;- Partenariats stratégiques possibles"
                  label="Opportunités externes *"
                  persistent-hint
                  rows="12"
                  :rules="[rules.required]"
                  variant="outlined"
                />
              </v-col>

              <v-col cols="12" md="6">
                <div class="d-flex align-center mb-2">
                  <v-icon class="mr-2" color="red">mdi-alert-octagon</v-icon>
                  <span class="text-h6">Menaces (Threats)</span>
                </div>
                <v-textarea
                  v-model="form.swot_threats"
                  density="comfortable"
                  hint="Une menace par ligne. Ex:&#10;- Concurrence accrue&#10;- Évolution réglementaire défavorable&#10;- Risques géopolitiques"
                  label="Menaces externes *"
                  persistent-hint
                  rows="12"
                  :rules="[rules.required]"
                  variant="outlined"
                />
              </v-col>
            </v-row>

            <v-row v-else>
              <v-col cols="12" md="6">
                <div class="d-flex align-center mb-2">
                  <v-icon class="mr-2" color="purple">mdi-account-group</v-icon>
                  <span class="text-h6">Social</span>
                </div>
                <v-textarea
                  v-model="form.pestel_social"
                  density="comfortable"
                  hint="Démographie, culture, attitudes..."
                  label="Facteurs sociaux *"
                  rows="10"
                  :rules="[rules.required]"
                  variant="outlined"
                />
              </v-col>

              <v-col cols="12" md="6">
                <div class="d-flex align-center mb-2">
                  <v-icon class="mr-2" color="cyan">mdi-robot</v-icon>
                  <span class="text-h6">Technologique</span>
                </div>
                <v-textarea
                  v-model="form.pestel_technological"
                  density="comfortable"
                  hint="Innovations, automatisation, R&D..."
                  label="Facteurs technologiques *"
                  rows="10"
                  :rules="[rules.required]"
                  variant="outlined"
                />
              </v-col>
            </v-row>
          </div>

          <!-- Step 4: PESTEL only (Environnement/Légal) -->
          <div v-show="step === 4 && wizardType === 'pestel'">
            <v-row>
              <v-col cols="12" md="6">
                <div class="d-flex align-center mb-2">
                  <v-icon class="mr-2" color="green">mdi-leaf</v-icon>
                  <span class="text-h6">Environnemental</span>
                </div>
                <v-textarea
                  v-model="form.pestel_environmental"
                  density="comfortable"
                  hint="Climat, écologie, développement durable..."
                  label="Facteurs environnementaux *"
                  rows="12"
                  :rules="[rules.required]"
                  variant="outlined"
                />
              </v-col>

              <v-col cols="12" md="6">
                <div class="d-flex align-center mb-2">
                  <v-icon class="mr-2" color="brown">mdi-gavel</v-icon>
                  <span class="text-h6">Légal</span>
                </div>
                <v-textarea
                  v-model="form.pestel_legal"
                  density="comfortable"
                  hint="Lois, normes, réglementations spécifiques..."
                  label="Facteurs légaux *"
                  rows="12"
                  :rules="[rules.required]"
                  variant="outlined"
                />
              </v-col>
            </v-row>
          </div>
        </v-card-text>
      </v-stepper>

      <v-divider />

      <v-card-actions class="pa-4">
        <v-btn variant="text" @click="cancel">
          Annuler
        </v-btn>
        <v-spacer />
        <v-btn v-if="step > 1" variant="tonal" @click="step--">
          <v-icon left>mdi-chevron-left</v-icon>
          Précédent
        </v-btn>
        <v-btn
          v-if="step < maxSteps"
          color="primary"
          variant="elevated"
          @click="step++"
        >
          Suivant
          <v-icon right>mdi-chevron-right</v-icon>
        </v-btn>
        <v-btn
          v-if="step === maxSteps"
          color="success"
          :loading="saving"
          variant="elevated"
          @click="save"
        >
          <v-icon left>mdi-check</v-icon>
          Enregistrer
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import { computed, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import { useContextStore } from '@/stores/contextStore'

  const props = defineProps<{
    type: 'swot' | 'pestel'
  }>()

  const router = useRouter()
  const store = useContextStore()

  const dialog = ref(false)
  const step = ref(1)
  const saving = ref(false)

  const wizardType = computed(() => props.type)
  const currentYear = new Date().getFullYear()
  const maxSteps = computed(() => props.type === 'pestel' ? 4 : 3)

  const form = ref({
    title: `Analyse ${props.type.toUpperCase()} ${currentYear}`,
    year: currentYear,
    type: props.type,
    category: 'organizational',
    description: '',
    impact: 'neutral',
    site_id: 1,
    swot_strengths: '',
    swot_weaknesses: '',
    swot_opportunities: '',
    swot_threats: '',
    pestel_political: '',
    pestel_economic: '',
    pestel_social: '',
    pestel_technological: '',
    pestel_environmental: '',
    pestel_legal: '',
  })

  const categoryOptions = [
    { value: 'organizational', title: 'Organisationnel' },
    { value: 'strategic', title: 'Stratégique' },
    { value: 'operational', title: 'Opérationnel' },
    { value: 'market', title: 'Marché' },
  ]

  const rules = {
    required: (v: any) => !!v || 'Champ requis',
  }

  function cancel () {
    dialog.value = false
    step.value = 1
    resetForm()
  }

  function resetForm () {
    form.value = {
      title: `Analyse ${props.type.toUpperCase()} ${currentYear}`,
      year: currentYear,
      type: props.type,
      category: 'organizational',
      description: '',
      impact: 'neutral',
      site_id: 1,
      swot_strengths: '',
      swot_weaknesses: '',
      swot_opportunities: '',
      swot_threats: '',
      pestel_political: '',
      pestel_economic: '',
      pestel_social: '',
      pestel_technological: '',
      pestel_environmental: '',
      pestel_legal: '',
    }
  }

  async function save () {
    saving.value = true
    try {
      const result = await store.createContext(form.value)
      dialog.value = false
      step.value = 1
      resetForm()
      if (result?.id) {
        router.push(`/contexts/${result.id}`)
      }
    } finally {
      saving.value = false
    }
  }
</script>
