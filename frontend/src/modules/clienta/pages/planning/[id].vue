<template>
  <ClientALayout current-page="risks-opportunities">
    <PageHeader
      icon="mdi-shield-alert"
      :subtitle="processName ? `Processus: ${processName}` : 'Détails du plan de maîtrise'"
      :title="pageTitle"
    >
      <template #actions>
        <v-btn prepend-icon="mdi-arrow-left" variant="outlined" @click="router.push('/company/planning/risks-opportunities')">
          Retour
        </v-btn>
        <v-btn
          color="primary"
          :loading="saving"
          prepend-icon="mdi-content-save"
          @click="handleSave"
        >
          Enregistrer
        </v-btn>
      </template>
    </PageHeader>

    <v-progress-linear v-if="loading" class="mb-4" color="primary" indeterminate />

    <v-alert v-if="error" class="mb-6" type="error" variant="tonal">
      {{ error }}
    </v-alert>

    <!-- Hero Card -->
    <v-card class="hero-detail-card mb-6" elevation="0" rounded="xl">
      <v-card-text class="pa-6">
        <div class="d-flex align-center justify-space-between flex-wrap gap-4">
          <div>
            <v-chip class="mb-3" :color="form.type === 'opportunite' ? 'success' : 'error'" size="small" variant="flat">
              {{ form.type === 'opportunite' ? 'OPPORTUNITÉ' : 'RISQUE' }} #{{ riskOpportunity?.code }}
            </v-chip>
            <h1 class="text-h4 font-weight-bold mb-2">{{ form.title || 'Sans titre' }}</h1>
            <p class="text-body-1 text-medium-emphasis">{{ processName || 'Processus non défini' }}</p>
          </div>
          <div class="score-display">
            <div class="text-caption text-medium-emphasis mb-2 text-center">
              {{ form.type === 'risque' ? 'Criticité' : 'Priorité' }}
            </div>
            <v-chip class="score-chip" :color="getCriticalityColor(criticite)" size="x-large">
              <span class="text-h4 font-weight-bold">{{ criticite }}</span>
              <span class="text-body-2 ml-1">/16</span>
            </v-chip>
            <div class="text-caption mt-2 text-center">{{ getNiveauLabel(criticite) }}</div>
          </div>
        </div>
      </v-card-text>
    </v-card>

    <v-row>
      <v-col cols="12" md="8">
        <v-card class="mb-6" elevation="2" rounded="xl">
          <v-card-title class="card-title-modern">
            <v-icon class="mr-2" color="primary">mdi-information-outline</v-icon>
            Informations principales
          </v-card-title>
          <v-card-text class="pa-6">
            <v-text-field
              v-model="form.title"
              density="comfortable"
              label="Titre *"
              placeholder="Titre court et explicite"
              prepend-inner-icon="mdi-format-title"
              variant="outlined"
            />
            <v-row>
              <v-col v-if="form.type === 'risque'" cols="12">
                <v-textarea
                  v-model="form.cause"
                  density="comfortable"
                  label="Causes profondes"
                  placeholder="Quelles sont les causes racines de ce risque?"
                  prepend-inner-icon="mdi-source-branch"
                  rows="3"
                  variant="outlined"
                />
              </v-col>
            </v-row>
            <v-textarea
              v-model="form.description"
              density="comfortable"
              :hint="form.type === 'risque' ? 'Optionnelle si les causes profondes sont déjà renseignées.' : undefined"
              :label="form.type === 'risque' ? 'Description détaillée' : 'Description détaillée *'"
              persistent-hint
              placeholder="Décrivez précisément le risque ou l'opportunité..."
              prepend-inner-icon="mdi-text"
              rows="3"
              variant="outlined"
            />
          </v-card-text>
        </v-card>

        <!-- <v-card class="mb-6" elevation="2" rounded="xl">
          <v-card-title class="card-title-modern">
            <v-icon class="mr-2" color="warning">mdi-shield-check</v-icon>
            Aspects QHSE concernés
          </v-card-title>
          <v-card-text class="pa-6">
            <v-row>
              <v-col cols="12" md="4">
                <v-card class="aspect-card" :class="{ 'aspect-active': form.aspect_qualite }" @click="form.aspect_qualite = !form.aspect_qualite">
                  <v-card-text class="text-center pa-4">
                    <v-icon :color="form.aspect_qualite ? 'primary' : 'grey'" size="32">mdi-quality-high</v-icon>
                    <div class="text-subtitle-1 font-weight-bold mt-2">Qualité</div>
                    <v-checkbox v-model="form.aspect_qualite" class="justify-center" color="primary" hide-details />
                  </v-card-text>
                </v-card>
              </v-col>
              <v-col cols="12" md="4">
                <v-card class="aspect-card" :class="{ 'aspect-active': form.aspect_environnement }" @click="form.aspect_environnement = !form.aspect_environnement">
                  <v-card-text class="text-center pa-4">
                    <v-icon :color="form.aspect_environnement ? 'success' : 'grey'" size="32">mdi-leaf</v-icon>
                    <div class="text-subtitle-1 font-weight-bold mt-2">Environnement</div>
                    <v-checkbox v-model="form.aspect_environnement" class="justify-center" color="success" hide-details />
                  </v-card-text>
                </v-card>
              </v-col>
              <v-col cols="12" md="4">
                <v-card class="aspect-card" :class="{ 'aspect-active': form.aspect_sante_securite }" @click="form.aspect_sante_securite = !form.aspect_sante_securite">
                  <v-card-text class="text-center pa-4">
                    <v-icon :color="form.aspect_sante_securite ? 'warning' : 'grey'" size="32">mdi-hospital-box</v-icon>
                    <div class="text-subtitle-1 font-weight-bold mt-2">Santé & Sécurité</div>
                    <v-checkbox v-model="form.aspect_sante_securite" class="justify-center" color="warning" hide-details />
                  </v-card-text>
                </v-card>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card> -->

        <v-card class="mb-6 evaluation-card" elevation="2" rounded="xl">
          <v-card-title class="card-title-modern">
            <v-icon class="mr-2" color="error">mdi-chart-box-outline</v-icon>
            Évaluation (Matrice Probabilité × {{ form.type === 'risque' ? 'Gravité' : 'Pertinence' }})
          </v-card-title>
          <v-card-text class="pa-6">
            <v-row class="mb-4">
              <v-col cols="12" md="4">
                <div class="evaluation-box">
                  <div class="text-caption text-medium-emphasis mb-2">Probabilité</div>
                  <v-slider
                    v-model="form.probabilite"
                    color="primary"
                    :max="4"
                    :min="1"
                    step="1"
                    thumb-label="always"
                    track-color="grey-lighten-2"
                  />
                  <div class="text-caption text-medium-emphasis">
                    {{ getProbabilityLabel(form.type, Number(form.probabilite || 1)) }}
                  </div>
                </div>
              </v-col>
              <v-col cols="12" md="4">
                <div class="evaluation-box">
                  <div class="text-caption text-medium-emphasis mb-2">
                    {{ form.type === 'risque' ? 'Gravité' : 'Pertinence' }}
                  </div>
                  <v-slider
                    v-model="form.gravite"
                    :color="form.type === 'risque' ? 'error' : 'success'"
                    :max="4"
                    :min="1"
                    step="1"
                    thumb-label="always"
                    track-color="grey-lighten-2"
                  />
                  <div class="text-caption text-medium-emphasis">
                    {{ getImpactLabel(form.type, Number(form.gravite || 1)) }}
                  </div>
                </div>
              </v-col>
              <v-col cols="12" md="4">
                <div class="result-box" :style="`border-color: ${getCriticalityColor(criticite)}`">
                  <div class="text-caption text-medium-emphasis mb-2">
                    {{ form.type === 'risque' ? 'Criticité' : 'Priorité' }}
                  </div>
                  <div class="d-flex align-center justify-center">
                    <span class="text-h3 font-weight-bold" :style="`color: ${getCriticalityColor(criticite)}`">{{ criticite }}</span>
                    <span class="text-h6 ml-2 text-medium-emphasis">/16</span>
                  </div>
                  <v-chip class="mt-2" :color="getCriticalityColor(criticite)" size="small">{{ getNiveauLabel(criticite) }}</v-chip>
                </div>
              </v-col>
            </v-row>

            <v-divider class="my-6" />

            <div class="text-subtitle-1 font-weight-bold mb-4">
              <v-icon class="mr-2" size="small">mdi-shield-refresh</v-icon>
              {{ form.type === 'risque' ? 'Évaluation résiduelle (après traitement)' : 'Priorité résiduelle (après exploitation)' }}
            </div>
            <v-row>
              <v-col cols="12" md="4">
                <v-select
                  v-model="form.probabilite_residuelle"
                  clearable
                  density="comfortable"
                  :items="[1, 2, 3, 4]"
                  label="Probabilité résiduelle"
                  prepend-inner-icon="mdi-numeric"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="4">
                <v-select
                  v-model="form.gravite_residuelle"
                  clearable
                  density="comfortable"
                  :items="[1, 2, 3, 4]"
                  :label="form.type === 'risque' ? 'Gravité résiduelle' : 'Pertinence résiduelle'"
                  prepend-inner-icon="mdi-numeric"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="4">
                <div v-if="criticiteResiduelle" class="result-box-small" :style="`border-color: ${getCriticalityColor(criticiteResiduelle)}`">
                  <div class="text-caption text-medium-emphasis mb-1">Résultat</div>
                  <v-chip :color="getCriticalityColor(criticiteResiduelle)" size="large">
                    <span class="text-h6 font-weight-bold">{{ criticiteResiduelle }}/16</span>
                  </v-chip>
                </div>
                <v-alert
                  v-else
                  class="ma-0"
                  density="compact"
                  type="info"
                  variant="tonal"
                >
                  Non calculée
                </v-alert>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>

        <v-card class="mb-6" elevation="2" rounded="xl">
          <v-card-title class="card-title-modern d-flex align-center justify-space-between">
            <div>
              <v-icon class="mr-2" color="success">mdi-clipboard-check-multiple-outline</v-icon>
              Actions prévues
            </div>
            <v-btn color="primary" prepend-icon="mdi-plus" size="small" @click="addAction">
              Ajouter une action
            </v-btn>
          </v-card-title>
          <v-card-text class="pa-6">
            <v-alert v-if="form.actions.length === 0" class="mb-4" type="info" variant="tonal">
              Aucune action définie. Cliquez sur "Ajouter une action" pour commencer.
            </v-alert>

            <v-expansion-panels v-else multiple>
              <v-expansion-panel
                v-for="(action, index) in form.actions"
                :key="index"
                elevation="1"
              >
                <v-expansion-panel-title>
                  <div class="d-flex align-center justify-space-between w-100">
                    <div class="d-flex align-center">
                      <v-chip class="mr-3" color="primary" size="small">Action {{ index + 1 }}</v-chip>
                      <v-chip
                        v-if="form.type === 'risque'"
                        class="mr-2"
                        color="warning"
                        size="x-small"
                        variant="tonal"
                      >
                        {{ actionTypeLabel(action.action_type) }}
                      </v-chip>
                      <span class="text-body-2">{{ action.description || 'Sans description' }}</span>
                    </div>
                    <v-btn
                      color="error"
                      icon="mdi-delete"
                      size="x-small"
                      variant="text"
                      @click.stop="removeAction(index)"
                    />
                  </div>
                </v-expansion-panel-title>
                <v-expansion-panel-text>
                  <v-select
                    v-if="form.type === 'risque'"
                    v-model="action.action_type"
                    class="mb-3"
                    clearable
                    density="comfortable"
                    :items="actionTypeOptions"
                    label="Type d'action"
                    prepend-inner-icon="mdi-shape-outline"
                    variant="outlined"
                  />
                  <v-textarea
                    v-model="action.description"
                    class="mb-3"
                    density="comfortable"
                    label="Description de l'action *"
                    placeholder="Décrivez l'action à mettre en œuvre"
                    prepend-inner-icon="mdi-text"
                    rows="2"
                    variant="outlined"
                  />
                  <v-row>
                    <v-col cols="12" md="6">
                      <v-select
                        v-model="action.responsible_user_id"
                        clearable
                        density="comfortable"
                        item-title="title"
                        item-value="value"
                        :items="userOptions"
                        label="Responsable de l'action"
                        prepend-inner-icon="mdi-account"
                        variant="outlined"
                      />
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-select
                        v-model="action.deadline_frequency"
                        clearable
                        density="comfortable"
                        :items="frequencyOptions"
                        label="Délai / Fréquence"
                        prepend-inner-icon="mdi-calendar-clock"
                        variant="outlined"
                      >
                        <template #append-inner>
                          <v-btn
                            icon="mdi-calendar"
                            size="x-small"
                            variant="text"
                            @click="action.showDatePicker = !action.showDatePicker"
                          />
                        </template>
                      </v-select>
                      <AppDatePickerField
                        v-if="action.showDatePicker"
                        v-model="action.deadline_frequency"
                        class="mt-2"
                        hint="Ou saisissez une date fixe"
                        label="Date fixe"
                        mode="date"
                        persistent-hint
                      />
                    </v-col>
                    <v-col cols="12">
                      <v-select
                        v-model="action.implicated_user_ids"
                        chips
                        clearable
                        density="comfortable"
                        hint="Personnes impliquées dans cette action (optionnel)"
                        item-title="title"
                        item-value="value"
                        :items="userOptions"
                        label="Responsables impliqués"
                        multiple
                        persistent-hint
                        prepend-inner-icon="mdi-account-multiple"
                        variant="outlined"
                      />
                    </v-col>
                  </v-row>
                </v-expansion-panel-text>
              </v-expansion-panel>
            </v-expansion-panels>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="4">
        <v-card class="mb-6" elevation="2" rounded="xl">
          <v-card-title class="card-title-modern">
            <v-icon class="mr-2" color="info">mdi-information-variant</v-icon>
            Suivi
          </v-card-title>
          <v-card-text class="pa-6">
            <v-select
              v-model="form.status"
              density="comfortable"
              :items="statusOptions"
              label="Statut *"
              prepend-inner-icon="mdi-flag"
              variant="outlined"
            />
            <AppDatePickerField
              v-model="form.target_date"
              label="Date cible de traitement"
              mode="date"
            />
          </v-card-text>
        </v-card>

        <v-card elevation="2" rounded="xl">
          <v-card-title class="card-title-modern">
            <v-icon class="mr-2">mdi-account-group</v-icon>
            Responsables des actions
          </v-card-title>
          <v-card-text class="pa-6">
            <v-alert v-if="actionResponsibles.length === 0" density="compact" type="info" variant="tonal">
              Aucun responsable assigné aux actions
            </v-alert>
            <v-list v-else density="compact">
              <v-list-item v-for="(resp, idx) in actionResponsibles" :key="idx" class="px-0">
                <template #prepend>
                  <v-avatar color="primary" size="32">
                    <v-icon size="18">mdi-account</v-icon>
                  </v-avatar>
                </template>
                <v-list-item-title class="font-weight-medium">{{ resp }}</v-list-item-title>
              </v-list-item>
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import api from '@/api/client'
  import AppDatePickerField from '@/components/common/AppDatePickerField.vue'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import { useToast } from '@/modules/shared/composables/useToast'
  import { useAuthStore } from '@/stores/auth'

  const route = useRoute()
  const router = useRouter()
  const toast = useToast()
  const authStore = useAuthStore()

  const riskOpportunity = ref<any>(null)
  const loading = ref(false)
  const saving = ref(false)
  const error = ref('')
  const users = ref<Array<{ id: number, name: string }>>([])
  type RiskActionType = 'preventive' | 'corrective' | 'control'

  const form = ref({
    type: 'risque',
    title: '',
    description: '',
    cause: '',
    aspect_qualite: false,
    aspect_environnement: false,
    aspect_sante_securite: false,
    probabilite: 1,
    gravite: 1,
    probabilite_residuelle: null as number | null,
    gravite_residuelle: null as number | null,
    actions: [] as Array<{
      action_type: RiskActionType | null
      description: string
      responsible_user_id: number | null
      implicated_user_ids: number[]
      deadline_frequency: string
      showDatePicker: boolean
    }>,
    status: 'identifie',
    target_date: '',
  })

  const statusOptions = [
    { title: 'Identifié', value: 'identifie' },
    { title: 'En cours', value: 'en_cours' },
    { title: 'Traité', value: 'traite' },
    { title: 'Surveillé', value: 'surveille' },
    { title: 'Clôturé', value: 'cloture' },
  ]

  const frequencyOptions = [
    { title: 'En continu', value: 'En continu' },
    { title: 'Hebdomadaire', value: 'Hebdomadaire' },
    { title: 'Bihebdomadaire', value: 'Bihebdomadaire' },
    { title: 'Mensuel', value: 'Mensuel' },
    { title: 'Bimestriel', value: 'Bimestriel' },
    { title: 'Trimestriel', value: 'Trimestriel' },
    { title: 'Quadrimestriel', value: 'Quadrimestriel' },
    { title: 'Semestriel', value: 'Semestriel' },
    { title: 'Annuel', value: 'Annuel' },
    { title: 'Biennal', value: 'Biennal' },
  ]

  const actionTypeOptions = [
    { title: 'Préventive', value: 'preventive' },
    { title: 'Corrective', value: 'corrective' },
    { title: 'Maîtrise', value: 'control' },
  ]

  const userOptions = computed(() => users.value.map((user: any) => ({
    title: user.name,
    value: user.id,
  })))

  const actionResponsibles = computed(() => {
    const responsibles = new Set<string>()
    for (const action of form.value.actions) {
      if (action.responsible_user_id) {
        const user = users.value.find(u => u.id === action.responsible_user_id)
        if (user) responsibles.add(user.name)
      }
    }
    return Array.from(responsibles)
  })

  const pageTitle = computed(() => {
    const label = form.value.type === 'opportunite' ? 'Opportunité' : 'Risque'
    return form.value.title ? `${label}: ${form.value.title}` : `${label} - Détails`
  })

  const processName = computed(() => riskOpportunity.value?.process?.title || riskOpportunity.value?.process?.name || '')
  const criticite = computed(() => Number(form.value.probabilite || 1) * Number(form.value.gravite || 1))
  const criticiteResiduelle = computed(() => {
    if (!form.value.probabilite_residuelle || !form.value.gravite_residuelle) return null
    return Number(form.value.probabilite_residuelle) * Number(form.value.gravite_residuelle)
  })

  function addAction () {
    form.value.actions.push({
      action_type: form.value.type === 'risque' ? 'preventive' : null,
      description: '',
      responsible_user_id: null,
      implicated_user_ids: [],
      deadline_frequency: '',
      showDatePicker: false,
    })
  }

  function removeAction (index: number) {
    form.value.actions.splice(index, 1)
  }

  function parseActionsFromLegacyFormat (actionsPrevues: string): Array<any> {
    if (!actionsPrevues) return []

    const lines = actionsPrevues.split('\n').filter(line => line.trim() && !line.startsWith('[META]'))
    if (lines.length === 0) return []

    // Essayer de parser le format META
    const metaLine = actionsPrevues.split('\n').find(line => line.startsWith('[META]'))
    if (metaLine) {
      try {
        const parsed = JSON.parse(metaLine.replace('[META]', '').trim())
        return [{
          action_type: 'preventive' as RiskActionType,
          description: lines.join('\n'),
          responsible_user_id: null,
          implicated_user_ids: Array.isArray(parsed?.responsables_implique_ids) ? parsed.responsables_implique_ids : [],
          deadline_frequency: parsed?.delai_frequence || '',
        }]
      } catch {}
    }

    // Format simple: une action par ligne
    return lines.map(line => ({
      action_type: 'preventive' as RiskActionType,
      description: line,
      responsible_user_id: null,
      implicated_user_ids: [],
      deadline_frequency: '',
    }))
  }

  function getCriticalityColor (value: number) {
    if (form.value.type === 'opportunite') {
      if (value >= 12) return '#1565C0'
      if (value >= 8) return '#00897B'
      if (value >= 4) return '#7CB342'
      return '#8E24AA'
    }

    if (value >= 12) return '#D32F2F'
    if (value >= 8) return '#F57C00'
    if (value >= 4) return '#FBC02D'
    return '#2E7D32'
  }

  function getNiveauLabel (value: number) {
    if (value >= 12) return 'Critique'
    if (value >= 8) return 'Élevé'
    if (value >= 4) return 'Moyen'
    return 'Faible'
  }

  function getProbabilityLabel (type: string, value: number): string {
    if (type === 'opportunite') {
      return ({
        4: 'Très probable',
        3: 'Probable',
        2: 'Peu probable',
        1: 'Incertain',
      } as Record<number, string>)[value] || 'Incertain'
    }

    return ({
      4: 'Très fréquent',
      3: 'Fréquent',
      2: 'Peu fréquent',
      1: 'Rare',
    } as Record<number, string>)[value] || 'Rare'
  }

  function getImpactLabel (type: string, value: number): string {
    if (type === 'opportunite') {
      return ({
        4: 'Très pertinent',
        3: 'Pertinent',
        2: 'Moins pertinent',
        1: 'Faible',
      } as Record<number, string>)[value] || 'Faible'
    }

    return ({
      4: 'Très élevée',
      3: 'Élevée',
      2: 'Moyenne',
      1: 'Faible',
    } as Record<number, string>)[value] || 'Faible'
  }

  function actionTypeLabel (type: RiskActionType | null | undefined): string {
    if (type === 'preventive') return 'Préventive'
    if (type === 'corrective') return 'Corrective'
    if (type === 'control') return 'Maîtrise'
    return 'Type non défini'
  }

  function hydrateForm (data: any) {
    const targetDate = data.target_date ? String(data.target_date).slice(0, 10) : ''

    let actions: Array<any> = []

    // Priorité 1: planned_actions (nouveau format JSON)
    if (data.planned_actions) {
      if (typeof data.planned_actions === 'string') {
        try {
          actions = JSON.parse(data.planned_actions)
        } catch (error_) {
          console.error('Error parsing planned_actions:', error_)
          actions = []
        }
      } else if (Array.isArray(data.planned_actions)) {
        actions = data.planned_actions
      }
    } else if (data.actions_prevues) {
      // Priorité 2: actions_prevues (ancien format texte)
      actions = parseActionsFromLegacyFormat(data.actions_prevues)
    }

    // S'assurer que c'est un tableau valide
    if (!Array.isArray(actions)) {
      actions = []
    }

    // Normaliser chaque action
    actions = actions.map(action => ({
      action_type: (data.type || 'risque') === 'risque'
        ? ((action.action_type as RiskActionType) || 'preventive')
        : null,
      description: action.description || '',
      responsible_user_id: action.responsible_user_id || null,
      implicated_user_ids: Array.isArray(action.implicated_user_ids) ? action.implicated_user_ids : [],
      deadline_frequency: action.deadline_frequency || '',
      showDatePicker: false,
    }))

    console.log('Hydrated actions:', actions)

    form.value = {
      type: data.type || 'risque',
      title: data.title || '',
      description: data.description || '',
      cause: data.cause || '',
      aspect_qualite: Boolean(data.aspect_qualite),
      aspect_environnement: Boolean(data.aspect_environnement),
      aspect_sante_securite: Boolean(data.aspect_sante_securite),
      probabilite: data.probabilite || 1,
      gravite: data.gravite || 1,
      probabilite_residuelle: data.probabilite_residuelle || null,
      gravite_residuelle: data.gravite_residuelle || null,
      actions,
      status: data.status || 'identifie',
      target_date: targetDate,
    }
  }

  async function fetchUsers () {
    const siteId = authStore.currentSiteId
    if (!siteId) {
      users.value = []
      return
    }

    try {
      const response = await api.get('/users', { params: { site_id: siteId, per_page: 300 } })
      const rows = response.data?.data || []
      users.value = rows.map((row: any) => ({
        id: Number(row.id),
        name: row.attributes?.name || row.name || row.attributes?.email || `Utilisateur #${row.id}`,
      }))
    } catch (error) {
      console.error('Error fetching users:', error)
      users.value = []
    }
  }

  async function fetchRiskOpportunity () {
    const id = Number((route.params as any).id)
    if (!Number.isFinite(id)) {
      error.value = 'Identifiant invalide.'
      return
    }

    loading.value = true
    error.value = ''
    try {
      const response = await api.get(`/risks-opportunities/${id}`)
      riskOpportunity.value = response.data?.data || response.data
      if (riskOpportunity.value) {
        hydrateForm(riskOpportunity.value)
      }
    } catch (error_) {
      console.error('[RiskOpportunityDetail] Failed to load', error_)
      error.value = 'Impossible de charger le détail.'
    } finally {
      loading.value = false
    }
  }

  async function handleSave () {
    const id = Number((route.params as any).id)
    if (!Number.isFinite(id)) return

    const hasDescription = Boolean(form.value.description?.trim())
    const hasCause = Boolean(form.value.cause?.trim())

    if (!form.value.title?.trim() || (form.value.type === 'risque' ? (!hasDescription && !hasCause) : !hasDescription)) {
      toast.error(form.value.type === 'risque'
        ? 'Le titre et au moins la description ou les causes profondes sont obligatoires.'
        : 'Le titre et la description sont obligatoires.')
      return
    }

    console.log('Saving actions:', form.value.actions)

    saving.value = true
    try {
      const payload = {
        title: form.value.title,
        description: form.value.description?.trim() || null,
        type: form.value.type,
        cause: form.value.type === 'risque' ? (form.value.cause || null) : null,
        aspect_qualite: form.value.aspect_qualite,
        aspect_environnement: form.value.aspect_environnement,
        aspect_sante_securite: form.value.aspect_sante_securite,
        probabilite: Number(form.value.probabilite || 1),
        gravite: Number(form.value.gravite || 1),
        probabilite_residuelle: form.value.probabilite_residuelle || null,
        gravite_residuelle: form.value.gravite_residuelle || null,
        planned_actions: JSON.stringify(form.value.actions),
        status: form.value.status || null,
        target_date: form.value.target_date || null,
      }
      const response = await api.put(`/risks-opportunities/${id}`, payload)
      riskOpportunity.value = response.data?.data || response.data
      hydrateForm(riskOpportunity.value)
      toast.success('Modifications enregistrées.')
    } catch (error_) {
      console.error('[RiskOpportunityDetail] Save failed', error_)
      toast.error('Erreur lors de l\'enregistrement.')
    } finally {
      saving.value = false
    }
  }

  onMounted(async () => {
    await fetchUsers()
    await fetchRiskOpportunity()
  })
</script>

<style scoped>
.hero-detail-card {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.08) 0%, rgba(147, 197, 253, 0.12) 100%);
  border: 1px solid rgba(59, 130, 246, 0.2);
}

.score-display {
  text-align: center;
}

.score-chip {
  padding: 16px 24px !important;
  height: auto !important;
}

.card-title-modern {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-bottom: 1px solid rgba(0, 0, 0, 0.08);
  font-weight: 600;
}

.aspect-card {
  cursor: pointer;
  transition: all 0.3s ease;
  border: 2px solid transparent;
}

.aspect-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.aspect-active {
  border-color: rgb(var(--v-theme-primary));
  background: rgba(var(--v-theme-primary), 0.04);
}

.evaluation-box {
  padding: 16px;
  background: rgba(var(--v-theme-surface), 0.5);
  border-radius: 12px;
  border: 1px solid rgba(0, 0, 0, 0.08);
}

.result-box {
  padding: 20px;
  background: white;
  border-radius: 16px;
  border: 3px solid;
  text-align: center;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.result-box-small {
  padding: 16px;
  background: white;
  border-radius: 12px;
  border: 2px solid;
  text-align: center;
}

.evaluation-card {
  background: linear-gradient(180deg, #ffffff 0%, #fafbfc 100%);
}
</style>
