<template>
  <ClientALayout current-page="processes">
    <PageHeader
      icon="mdi-sitemap"
      subtitle="Créer un nouveau processus en 4 étapes"
      title="Nouveau Processus"
    >
      <template #actions>
        <v-btn
          prepend-icon="mdi-arrow-left"
          variant="outlined"
          @click="router.push('/company/context/management-system')"
        >
          Retour
        </v-btn>
      </template>
    </PageHeader>

    <v-card elevation="0" rounded="xl" style="border: 2px solid #e2e8f0">
      <v-stepper v-model="currentStep" alt-labels flat>
        <v-stepper-header>
          <v-stepper-item
            color="primary"
            :complete="currentStep > 1"
            title="Informations"
            :value="1"
          >
            <template #icon>
              <v-icon>mdi-information</v-icon>
            </template>
          </v-stepper-item>
          <v-divider />
          <v-stepper-item
            color="success"
            :complete="currentStep > 2"
            title="Classification"
            :value="2"
          >
            <template #icon>
              <v-icon>mdi-tag-multiple</v-icon>
            </template>
          </v-stepper-item>
          <v-divider />
          <v-stepper-item
            color="info"
            :complete="currentStep > 3"
            title="Séquences"
            :value="3"
          >
            <template #icon>
              <v-icon>mdi-timeline</v-icon>
            </template>
          </v-stepper-item>
          <v-divider />
          <v-stepper-item color="purple" title="Validation" :value="4">
            <template #icon>
              <v-icon>mdi-check-circle</v-icon>
            </template>
          </v-stepper-item>
        </v-stepper-header>

        <v-stepper-window>
          <!-- Étape 1: Informations -->
          <v-stepper-window-item :value="1">
            <v-card-text class="pa-8">
              <div
                class="mb-6 pa-4"
                style="
                  background: rgba(25, 118, 210, 0.08);
                  border-radius: 12px;
                  border-left: 4px solid #1976d2;
                "
              >
                <div class="d-flex align-center gap-2 mb-2">
                  <v-icon color="primary">mdi-lightbulb-on</v-icon>
                  <span class="font-weight-bold">Guide pratique</span>
                </div>
                <p class="text-body-2 mb-0">
                  Définissez le titre, la finalité et les responsables du
                  processus.
                </p>
              </div>

              <v-form ref="step1Form">
                <v-row>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="formData.code"
                      density="comfortable"
                      disabled
                      label="Code du processus"
                      variant="outlined"
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="formData.site_id"
                      density="comfortable"
                      item-title="name"
                      item-value="id"
                      :items="sites"
                      label="Site *"
                      :rules="[rules.required]"
                      variant="outlined"
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-text-field
                      v-model="formData.title"
                      density="comfortable"
                      label="Titre du processus *"
                      :rules="[rules.required]"
                      variant="outlined"
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-textarea
                      v-model="formData.finalite"
                      label="Finalité *"
                      rows="3"
                      :rules="[rules.required]"
                      variant="outlined"
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="formData.type"
                      density="comfortable"
                      :items="typeOptions"
                      label="Type de processus *"
                      :rules="[rules.required]"
                      variant="outlined"
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-autocomplete
                      v-model="formData.pilot_id"
                      density="comfortable"
                      :disabled="!formData.site_id"
                      item-title="name"
                      item-value="id"
                      :items="users"
                      label="Pilote *"
                      :rules="[rules.required]"
                      variant="outlined"
                    />
                  </v-col>
                </v-row>
              </v-form>
            </v-card-text>
          </v-stepper-window-item>

          <!-- Étape 2: Classification -->
          <v-stepper-window-item :value="2">
            <v-card-text class="pa-8">
              <div
                class="mb-6 pa-4"
                style="
                  background: rgba(76, 175, 80, 0.08);
                  border-radius: 12px;
                  border-left: 4px solid #4caf50;
                "
              >
                <div class="d-flex align-center gap-2 mb-2">
                  <v-icon color="success">mdi-lightbulb-on</v-icon>
                  <span class="font-weight-bold">Guide pratique</span>
                </div>
                <p class="text-body-2 mb-0">
                  Sélectionnez les aspects QHSE et les normes ISO applicables.
                </p>
              </div>

              <v-row>
                <v-col cols="12" md="4">
                  <v-card
                    class="pa-4 cursor-pointer"
                    :style="
                      formData.aspect_qualite
                        ? 'background: #1976d2; color: white;'
                        : ''
                    "
                    variant="outlined"
                    @click="formData.aspect_qualite = !formData.aspect_qualite"
                  >
                    <div class="text-center">
                      <v-icon
                        :color="formData.aspect_qualite ? 'white' : 'primary'"
                        size="48"
                      >mdi-quality-high</v-icon>
                      <div class="text-h6 mt-2">Qualité</div>
                      <div class="text-caption">ISO 9001</div>
                    </div>
                  </v-card>
                </v-col>
                <v-col cols="12" md="4">
                  <v-card
                    class="pa-4 cursor-pointer"
                    :style="
                      formData.aspect_environnement
                        ? 'background: #4caf50; color: white;'
                        : ''
                    "
                    variant="outlined"
                    @click="
                      formData.aspect_environnement =
                        !formData.aspect_environnement
                    "
                  >
                    <div class="text-center">
                      <v-icon
                        :color="
                          formData.aspect_environnement ? 'white' : 'success'
                        "
                        size="48"
                      >mdi-leaf</v-icon>
                      <div class="text-h6 mt-2">Environnement</div>
                      <div class="text-caption">ISO 14001</div>
                    </div>
                  </v-card>
                </v-col>
                <v-col cols="12" md="4">
                  <v-card
                    class="pa-4 cursor-pointer"
                    :style="
                      formData.aspect_sante_securite
                        ? 'background: #f44336; color: white;'
                        : ''
                    "
                    variant="outlined"
                    @click="
                      formData.aspect_sante_securite =
                        !formData.aspect_sante_securite
                    "
                  >
                    <div class="text-center">
                      <v-icon
                        :color="
                          formData.aspect_sante_securite ? 'white' : 'error'
                        "
                        size="48"
                      >mdi-shield-check</v-icon>
                      <div class="text-h6 mt-2">Santé & Sécurité</div>
                      <div class="text-caption">ISO 45001</div>
                    </div>
                  </v-card>
                </v-col>
                <v-col cols="12">
                  <v-autocomplete
                    v-model="formData.normes_iso"
                    chips
                    item-title="code"
                    item-value="code"
                    :items="availableNorms"
                    label="Normes ISO *"
                    multiple
                    variant="outlined"
                  />
                </v-col>
              </v-row>
            </v-card-text>
          </v-stepper-window-item>

          <!-- Étape 3: Séquences -->
          <v-stepper-window-item :value="3">
            <v-card-text class="pa-8">
              <div
                class="mb-6 pa-4"
                style="
                  background: rgba(33, 150, 243, 0.08);
                  border-radius: 12px;
                  border-left: 4px solid #2196f3;
                "
              >
                <div class="d-flex align-center gap-2 mb-2">
                  <v-icon color="info">mdi-lightbulb-on</v-icon>
                  <span class="font-weight-bold">Guide pratique</span>
                </div>
                <p class="text-body-2 mb-0">
                  Ajoutez les séquences du processus avec leurs entrées,
                  activités et sorties.
                </p>
              </div>

              <v-btn
                class="mb-4"
                color="primary"
                prepend-icon="mdi-plus"
                @click="addSequence"
              >
                Ajouter une séquence
              </v-btn>

              <v-card
                v-for="(seq, index) in formData.sequences"
                :key="index"
                class="mb-3"
                variant="outlined"
              >
                <v-card-text>
                  <div class="d-flex justify-space-between align-center mb-3">
                    <v-chip
                      color="primary"
                      size="small"
                    >Séquence {{ index + 1 }}</v-chip>
                    <v-btn
                      color="error"
                      icon="mdi-delete"
                      size="small"
                      variant="text"
                      @click="removeSequence(index)"
                    />
                  </div>
                  <v-row>
                    <v-col cols="12" md="4">
                      <v-textarea
                        v-model="seq.input_description"
                        density="compact"
                        label="Entrées"
                        rows="2"
                        variant="outlined"
                      />
                    </v-col>
                    <v-col cols="12" md="4">
                      <v-textarea
                        v-model="seq.activity_description"
                        density="compact"
                        label="Activité"
                        rows="2"
                        variant="outlined"
                      />
                    </v-col>
                    <v-col cols="12" md="4">
                      <v-textarea
                        v-model="seq.output_description"
                        density="compact"
                        label="Sorties"
                        rows="2"
                        variant="outlined"
                      />
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card>

              <v-alert
                v-if="formData.sequences.length === 0"
                type="warning"
                variant="tonal"
              >
                Ajoutez au moins une séquence pour continuer.
              </v-alert>
            </v-card-text>
          </v-stepper-window-item>

          <!-- Étape 4: Validation -->
          <v-stepper-window-item :value="4">
            <v-card-text class="pa-8">
              <div
                class="mb-6 pa-4"
                style="
                  background: rgba(156, 39, 176, 0.08);
                  border-radius: 12px;
                  border-left: 4px solid #9c27b0;
                "
              >
                <div class="d-flex align-center gap-2 mb-2">
                  <v-icon color="purple">mdi-check-circle</v-icon>
                  <span class="font-weight-bold">Validation</span>
                </div>
                <p class="text-body-2 mb-0">
                  Vérifiez les informations avant de créer le processus.
                </p>
              </div>

              <v-row>
                <v-col cols="12" md="6">
                  <v-card variant="outlined">
                    <v-card-title style="background: rgba(25, 118, 210, 0.08)">
                      Informations Générales
                    </v-card-title>
                    <v-card-text>
                      <div class="mb-2">
                        <span class="text-caption">Titre:</span>
                        <div class="font-weight-bold">{{ formData.title }}</div>
                      </div>
                      <div class="mb-2">
                        <span class="text-caption">Type:</span>
                        <div>
                          <v-chip size="small">{{
                            getTypeLabel(formData.type)
                          }}</v-chip>
                        </div>
                      </div>
                      <div class="mb-2">
                        <span class="text-caption">Pilote:</span>
                        <div class="font-weight-bold">
                          {{ getUserName(formData.pilot_id) }}
                        </div>
                      </div>
                    </v-card-text>
                  </v-card>
                </v-col>
                <v-col cols="12" md="6">
                  <v-card variant="outlined">
                    <v-card-title style="background: rgba(76, 175, 80, 0.08)">
                      Classification QHSE
                    </v-card-title>
                    <v-card-text>
                      <div class="d-flex gap-2 mb-2">
                        <v-chip
                          v-if="formData.aspect_qualite"
                          color="primary"
                          size="small"
                        >Qualité</v-chip>
                        <v-chip
                          v-if="formData.aspect_environnement"
                          color="success"
                          size="small"
                        >Environnement</v-chip>
                        <v-chip
                          v-if="formData.aspect_sante_securite"
                          color="error"
                          size="small"
                        >Santé & Sécurité</v-chip>
                      </div>
                      <div class="mb-2">
                        <span class="text-caption">Normes ISO:</span>
                        <div class="d-flex gap-1 flex-wrap mt-1">
                          <v-chip
                            v-for="norm in formData.normes_iso"
                            :key="norm"
                            size="small"
                          >{{ norm }}</v-chip>
                        </div>
                      </div>
                    </v-card-text>
                  </v-card>
                </v-col>
                <v-col cols="12">
                  <v-card variant="outlined">
                    <v-card-title style="background: rgba(33, 150, 243, 0.08)">
                      Séquences ({{ formData.sequences.length }})
                    </v-card-title>
                    <v-card-text>
                      <v-chip-group column>
                        <v-chip
                          v-for="(seq, idx) in formData.sequences"
                          :key="idx"
                          size="small"
                        >
                          Séquence {{ idx + 1 }}
                        </v-chip>
                      </v-chip-group>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>
            </v-card-text>
          </v-stepper-window-item>
        </v-stepper-window>

        <v-divider />
        <v-card-actions class="pa-4">
          <v-btn v-if="currentStep > 1" @click="currentStep--">Précédent</v-btn>
          <v-spacer />
          <v-btn
            v-if="currentStep < 4"
            color="primary"
            @click="handleNext"
          >Suivant</v-btn>
          <v-btn
            v-else
            color="success"
            :loading="loading"
            prepend-icon="mdi-check"
            @click="createProcess"
          >
            Créer le processus
          </v-btn>
        </v-card-actions>
      </v-stepper>
    </v-card>
  </ClientALayout>
</template>

<script setup lang="ts">
  import type { Process } from '@/services/processService'
  import { computed, onMounted, ref, watch } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import api from '@/api/client'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import { useProcessStore } from '@/stores/processStore'
  import { useSiteStore } from '@/stores/siteStore'

  const router = useRouter()
  const route = useRoute()
  const siteStore = useSiteStore()
  const processStore = useProcessStore()

  const loading = ref(false)
  const currentStep = ref(1)
  const step1Form = ref()

  const formData = ref({
    code: '',
    title: '',
    finalite: '',
    type: '',
    pilot_id: null,
    copilot_id: null,
    site_id: null,
    aspect_qualite: false,
    aspect_environnement: false,
    aspect_sante_securite: false,
    normes_iso: [],
    sequences: [],
    status: 'draft',
  })

  const typeOptions = [
    { title: 'Pilotage', value: 'pilotage' },
    { title: 'Support', value: 'support' },
    { title: 'Opérationnel', value: 'operationnel' },
  ]

  const rules = {
    required: (v: any) => !!v || 'Ce champ est requis',
  }

  const sites = computed(() => siteStore.sites)
  const users = ref([])
  const availableNorms = ref([])

  const hasAnyAspect = computed(() => {
    return (
      formData.value.aspect_qualite
      || formData.value.aspect_environnement
      || formData.value.aspect_sante_securite
    )
  })

  watch(
    () => formData.value.site_id,
    async newSiteId => {
      if (newSiteId) {
        await loadUsersBySite(newSiteId)
      }
    },
  )

  onMounted(async () => {
    await siteStore.fetchAll()
    await loadNorms()
    await generateProcessCode()

    const querySiteId = Number(route.query.site_id)
    if (querySiteId) {
      formData.value.site_id = querySiteId
    }
  })

  async function loadNorms () {
    try {
      const response = await api.get('/norms')
      availableNorms.value = response.data?.data || []
    } catch {
      availableNorms.value = [
        { code: 'ISO 9001:2015', title: 'Management de la qualité' },
        { code: 'ISO 14001:2015', title: 'Management environnemental' },
        { code: 'ISO 45001:2018', title: 'Santé et sécurité' },
      ]
    }
  }

  async function loadUsersBySite (siteId: number) {
    try {
      const response = await api.get('/users/job-description-collaborators', {
        params: { site_id: siteId },
      })
      users.value = (response.data?.data || []).map((user: any) => ({
        id: user.id,
        name: user.full_name || user.name || user.email,
      }))
    } catch {
      users.value = []
    }
  }

  async function generateProcessCode () {
    const year = new Date().getFullYear()
    const uniqueSuffix = Date.now().toString().slice(-6)
    formData.value.code = `PROC-${year}-${uniqueSuffix}`
  }

  async function handleNext () {
    let isValid = true

    switch (currentStep.value) {
      case 1: {
        const { valid } = await step1Form.value.validate()
        isValid = valid

        break
      }
      case 2: {
        isValid = hasAnyAspect.value && formData.value.normes_iso.length > 0

        break
      }
      case 3: {
        isValid = formData.value.sequences.length > 0

        break
      }
    // No default
    }

    if (isValid) {
      currentStep.value++
    }
  }

  function addSequence () {
    formData.value.sequences.push({
      sequence_order: formData.value.sequences.length + 1,
      input_description: '',
      activity_description: '',
      output_description: '',
      sub_activities: [],
      responsible_user_id: null,
      supplier_processes: [],
      client_processes: [],
    })
  }

  function removeSequence (index: number) {
    formData.value.sequences.splice(index, 1)
  }

  async function createProcess () {
    loading.value = true
    try {
      const processData = {
        ...formData.value,
        category: formData.value.type as Process['category'],
        purpose: formData.value.finalite,
        status: formData.value.status as Process['status'],
      }
      await processStore.createProcess(processData)
      router.push('/company/context/management-system')
    } catch (error) {
      console.error('Error creating process:', error)
    } finally {
      loading.value = false
    }
  }

  function getTypeLabel (type: string): string {
    const option = typeOptions.find(opt => opt.value === type)
    return option?.title || type
  }

  function getUserName (userId: number | null): string {
    if (!userId) return 'Non assigné'
    const user = users.value.find((u: any) => u.id === userId)
    return user?.name || 'Non assigné'
  }
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
  transition: all 0.2s ease;
}

.cursor-pointer:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}
</style>
