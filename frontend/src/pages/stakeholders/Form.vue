<template>
  <v-container class="pa-6">
    <v-card>
      <v-card-title class="d-flex align-center">
        <v-btn class="mr-3" icon @click="goBack">
          <v-icon>mdi-arrow-left</v-icon>
        </v-btn>
        {{ isEdit ? 'Éditer Partie Intéressée' : 'Nouvelle Partie Intéressée' }}
      </v-card-title>

      <v-divider />

      <v-card-text class="pa-6">
        <v-form ref="formRef" v-model="valid">
          <v-row>
            <!-- Name -->
            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.name"
                density="comfortable"
                label="Nom de la partie intéressée *"
                :rules="[rules.required]"
                variant="outlined"
              />
            </v-col>

            <!-- Type -->
            <v-col cols="12" md="6">
              <v-select
                v-model="form.type"
                density="comfortable"
                :items="typeOptions"
                label="Type *"
                :rules="[rules.required]"
                variant="outlined"
              />
            </v-col>

            <!-- Relevance Degree -->
            <v-col cols="12" md="6">
              <v-select
                v-model="form.relevance_degree"
                density="comfortable"
                :items="relevanceOptions"
                label="Degré de Pertinence *"
                :rules="[rules.required]"
                variant="outlined"
              >
                <template #item="{ props, item }">
                  <v-list-item v-bind="props">
                    <template #prepend>
                      <v-chip :color="getRelevanceColor(item.value)" size="small" />
                    </template>
                  </v-list-item>
                </template>
              </v-select>
            </v-col>

            <!-- Site (if multi-site) -->
            <v-col cols="12" md="6">
              <v-text-field
                v-model.number="form.site_id"
                density="comfortable"
                hint="ID du site (1 par défaut)"
                label="Site ID *"
                :rules="[rules.required]"
                type="number"
                variant="outlined"
              />
            </v-col>

            <!-- Needs & Expectations -->
            <v-col cols="12">
              <v-textarea
                v-model="form.needs_expectations"
                hint="Décrivez les besoins et attentes de cette partie"
                label="Besoins et Attentes *"
                rows="3"
                :rules="[rules.required]"
                variant="outlined"
              />
            </v-col>

            <!-- Requirements -->
            <v-col cols="12">
              <v-textarea
                v-model="form.requirements"
                hint="Exigences spécifiques à cette partie"
                label="Exigences"
                rows="3"
                variant="outlined"
              />
            </v-col>

            <!-- Actions -->
            <v-col cols="12" md="6">
              <v-textarea
                v-model="form.actions"
                hint="Actions à entreprendre"
                label="Actions Associées"
                rows="3"
                variant="outlined"
              />
            </v-col>

            <!-- Responsible -->
            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.responsible"
                density="comfortable"
                hint="Nom du responsable"
                label="Responsable"
                variant="outlined"
              />
            </v-col>

            <!-- Deadline -->
            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.deadline"
                density="comfortable"
                hint="Date limite pour les actions"
                label="Délai"
                type="date"
                variant="outlined"
              />
            </v-col>
          </v-row>
        </v-form>
      </v-card-text>

      <v-divider />

      <v-card-actions class="pa-4">
        <v-btn variant="text" @click="goBack">
          Annuler
        </v-btn>
        <v-spacer />
        <v-btn
          color="primary"
          :disabled="!valid"
          :loading="saving"
          @click="save"
        >
          <v-icon left>mdi-content-save</v-icon>
          {{ isEdit ? 'Mettre à jour' : 'Créer' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-container>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { type Stakeholder, useStakeholderStore } from '@/stores/stakeholderStore'

  const router = useRouter()
  const route = useRoute()
  const store = useStakeholderStore()

  const formRef = ref()
  const valid = ref(false)
  const saving = ref(false)

  interface StakeholderForm {
    name: string
    type: Stakeholder['type']
    relevance_degree: string
    site_id: number
    needs_expectations: string
    requirements: string
    actions: string
    responsible: string
    deadline: string
  }

  const stakeholderId = computed<number | null>(() => {
    const params = route.params as Record<string, unknown>
    const rawParam = params.id
    const rawId = Array.isArray(rawParam) ? rawParam[0] : rawParam
    const id = Number(rawId)
    return Number.isFinite(id) && id > 0 ? id : null
  })

  const isEdit = computed(() => stakeholderId.value !== null)

  const form = ref<StakeholderForm>({
    name: '',
    type: 'client',
    relevance_degree: 'medium',
    site_id: 1,
    needs_expectations: '',
    requirements: '',
    actions: '',
    responsible: '',
    deadline: '',
  })

  const typeOptions: Array<{ value: Stakeholder['type'], title: string }> = [
    { value: 'client', title: 'Client' },
    { value: 'supplier', title: 'Fournisseur' },
    { value: 'partner', title: 'Partenaire' },
    { value: 'regulator', title: 'Autorité' },
    { value: 'shareholder', title: 'Actionnaire' },
    { value: 'employee', title: 'Interne' },
    { value: 'other', title: 'Autre' },
  ]

  const relevanceOptions = [
    { value: 'high', title: 'Élevée' },
    { value: 'medium', title: 'Moyenne' },
    { value: 'low', title: 'Faible' },
  ]

  const rules = {
    required: (v: any) => !!v || 'Champ requis',
  }

  function getRelevanceColor (degree: string) {
    const colors: Record<string, string> = {
      high: 'red',
      medium: 'orange',
      low: 'green',
    }
    return colors[degree] || 'grey'
  }

  function goBack () {
    router.push('/stakeholders')
  }

  async function save () {
    if (!valid.value) return

    saving.value = true
    try {
      const payload: Partial<Stakeholder> = {
        name: form.value.name,
        type: form.value.type,
        relevance_degree: form.value.relevance_degree,
        site_id: form.value.site_id,
        needs_expectations: form.value.needs_expectations,
        requirements: form.value.requirements,
        actions: form.value.actions,
        deadline: form.value.deadline || undefined,
      }
      await (isEdit.value && stakeholderId.value !== null ? store.updateStakeholder(stakeholderId.value, payload) : store.createStakeholder(payload))
      goBack()
    } finally {
      saving.value = false
    }
  }

  function mapStakeholderToForm (stakeholder: Stakeholder) {
    return {
      name: stakeholder.name || '',
      type: stakeholder.type || 'client',
      relevance_degree: stakeholder.relevance_degree || 'medium',
      site_id: stakeholder.site_id || 1,
      needs_expectations: stakeholder.needs_expectations || '',
      requirements: stakeholder.requirements || '',
      actions: stakeholder.actions || '',
      responsible: '',
      deadline: stakeholder.deadline || '',
    }
  }

  function findCurrentStakeholder () {
    return store.stakeholders.find(s => s.id === stakeholderId.value)
  }

  async function loadStakeholder () {
    if (!isEdit.value || stakeholderId.value === null) {
      return
    }

    let stakeholder = findCurrentStakeholder()
    if (!stakeholder) {
      await store.fetchStakeholders()
      stakeholder = findCurrentStakeholder()
    }

    if (stakeholder) {
      form.value = mapStakeholderToForm(stakeholder)
    }
  }

  onMounted(() => {
    loadStakeholder()
  })
</script>
