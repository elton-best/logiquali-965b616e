<template>
  <ClientALayout current-page="processes">
    <v-container class="pa-6" fluid>
      <!-- Header -->
      <div class="d-flex align-center gap-3 mb-6">
        <v-btn
          icon="mdi-arrow-left"
          size="small"
          variant="text"
          @click="router.push('/company/context/management-system')"
        />
        <div>
          <h1 class="text-h4 font-weight-bold">Créer un Processus</h1>
          <p class="text-body-1 text-medium-emphasis">
            Suivez les étapes pour créer un nouveau processus
          </p>
        </div>
      </div>

      <!-- Wizard -->
      <v-card>
        <v-stepper v-model="currentStep" alt-labels :items="steps">
          <template #default>
            <v-stepper-window>
              <!-- Step 1: General Info -->
              <v-stepper-window-item :value="1">
                <v-card-text class="pa-6">
                  <h2 class="text-h5 mb-4">Informations Générales</h2>
                  <v-form ref="step1Form">
                    <v-row>
                      <v-col cols="12" md="6">
                        <v-text-field
                          v-model="formData.code"
                          disabled
                          hint="Code généré automatiquement"
                          label="Code du processus"
                          persistent-hint
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="12" md="6">
                        <v-select
                          v-model="formData.site_id"
                          :disabled="sites.length === 1"
                          :hint="
                            sites.length === 1
                              ? 'Site unique disponible'
                              : 'Sélectionnez le site pour ce processus'
                          "
                          item-title="name"
                          item-value="id"
                          :items="sites"
                          label="Site *"
                          persistent-hint
                          :rules="[rules.required]"
                          variant="outlined"
                        />
                      </v-col>

                      <v-col cols="12">
                        <v-text-field
                          v-model="formData.title"
                          label="Titre du processus *"
                          placeholder="Ex: Gestion des achats"
                          :rules="[rules.required]"
                          variant="outlined"
                        />
                      </v-col>

                      <v-col cols="12">
                        <v-textarea
                          v-model="formData.finalite"
                          label="Finalité *"
                          placeholder="Décrivez l'objectif et la raison d'être de ce processus..."
                          rows="4"
                          :rules="[rules.required]"
                          variant="outlined"
                        />
                      </v-col>

                      <v-col cols="12" md="6">
                        <v-select
                          v-model="formData.type"
                          :items="typeOptions"
                          label="Type de processus *"
                          :rules="[rules.required]"
                          variant="outlined"
                        >
                          <template #item="{ props, item }">
                            <v-list-item v-bind="props">
                              <template #prepend>
                                <v-icon :color="getTypeColor(item.value)">
                                  {{ getTypeIcon(item.value) }}
                                </v-icon>
                              </template>
                            </v-list-item>
                          </template>
                        </v-select>
                      </v-col>

                      <v-col cols="12" md="6">
                        <v-select
                          v-model="formData.level"
                          :items="levelOptions"
                          label="Niveau (optionnel)"
                          variant="outlined"
                        />
                      </v-col>

                      <v-col cols="12" md="6">
                        <v-autocomplete
                          v-model="formData.pilot_id"
                          clearable
                          :disabled="!formData.site_id"
                          :hint="
                            !formData.site_id
                              ? 'Sélectionnez d\'abord un site'
                              : ''
                          "
                          item-title="name"
                          item-value="id"
                          :items="users"
                          label="Pilote du processus *"
                          :loading="loadingCollaborators"
                          no-data-text="Aucun collaborateur disponible pour ce site"
                          persistent-hint
                          :rules="[rules.required]"
                          variant="outlined"
                        >
                          <template #item="{ props, item }">
                            <v-list-item
                              v-bind="props"
                              :subtitle="getUserSubtitle(item.raw)"
                            >
                              <v-list-item-title>{{
                                item.raw.name
                              }}</v-list-item-title>
                            </v-list-item>
                          </template>
                        </v-autocomplete>
                      </v-col>

                      <v-col cols="12" md="6">
                        <v-autocomplete
                          v-model="formData.copilot_id"
                          clearable
                          :disabled="!formData.site_id"
                          :hint="
                            !formData.site_id
                              ? 'Sélectionnez d\'abord un site'
                              : ''
                          "
                          item-title="name"
                          item-value="id"
                          :items="users"
                          label="Co-pilote (optionnel)"
                          :loading="loadingCollaborators"
                          no-data-text="Aucun co-pilote disponible pour ce site"
                          persistent-hint
                          :rules="[rules.differentPilotAndCopilot]"
                          variant="outlined"
                        >
                          <template #item="{ props, item }">
                            <v-list-item
                              v-bind="props"
                              :subtitle="getUserSubtitle(item.raw)"
                            >
                              <v-list-item-title>{{
                                item.raw.name
                              }}</v-list-item-title>
                            </v-list-item>
                          </template>
                        </v-autocomplete>
                      </v-col>

                      <v-col cols="12" md="6">
                        <v-combobox
                          v-model="formData.ressources"
                          chips
                          clearable
                          label="Ressources nécessaires (optionnel)"
                          multiple
                          variant="outlined"
                        />
                      </v-col>

                      <v-col cols="12" md="6">
                        <v-combobox
                          v-model="formData.methodes"
                          chips
                          clearable
                          label="Méthodes / Outils (optionnel)"
                          multiple
                          variant="outlined"
                        />
                      </v-col>

                      <v-col cols="12">
                        <v-combobox
                          v-model="formData.interfaces"
                          chips
                          clearable
                          label="Interfaces principales (optionnel)"
                          multiple
                          variant="outlined"
                        />
                      </v-col>
                    </v-row>
                  </v-form>
                </v-card-text>
              </v-stepper-window-item>

              <!-- Step 2: Classification QHSE -->
              <v-stepper-window-item :value="2">
                <v-card-text class="pa-6">
                  <h2 class="text-h5 mb-4">Classification QHSE</h2>
                  <v-form ref="step2Form">
                    <v-row>
                      <v-col cols="12">
                        <h3 class="text-h6 mb-3">Aspects concernés</h3>
                        <v-row>
                          <v-col cols="12" md="4">
                            <v-card
                              class="pa-4 cursor-pointer"
                              :class="{ 'bg-primary': formData.aspect_qualite }"
                              variant="outlined"
                              @click="
                                formData.aspect_qualite =
                                  !formData.aspect_qualite
                              "
                            >
                              <div class="d-flex align-center gap-3">
                                <v-icon
                                  :color="
                                    formData.aspect_qualite
                                      ? 'white'
                                      : 'primary'
                                  "
                                  size="32"
                                >
                                  mdi-quality-high
                                </v-icon>
                                <div>
                                  <div
                                    class="font-weight-bold"
                                    :class="{
                                      'text-white': formData.aspect_qualite,
                                    }"
                                  >
                                    Qualité
                                  </div>
                                  <div
                                    class="text-caption"
                                    :class="{
                                      'text-white': formData.aspect_qualite,
                                    }"
                                  >
                                    ISO 9001
                                  </div>
                                </div>
                                <v-spacer />
                                <v-icon
                                  v-if="formData.aspect_qualite"
                                  color="white"
                                >
                                  mdi-check-circle
                                </v-icon>
                              </div>
                            </v-card>
                          </v-col>

                          <v-col cols="12" md="4">
                            <v-card
                              class="pa-4 cursor-pointer"
                              :class="{
                                'bg-success': formData.aspect_environnement,
                              }"
                              variant="outlined"
                              @click="
                                formData.aspect_environnement =
                                  !formData.aspect_environnement
                              "
                            >
                              <div class="d-flex align-center gap-3">
                                <v-icon
                                  :color="
                                    formData.aspect_environnement
                                      ? 'white'
                                      : 'success'
                                  "
                                  size="32"
                                >
                                  mdi-leaf
                                </v-icon>
                                <div>
                                  <div
                                    class="font-weight-bold"
                                    :class="{
                                      'text-white':
                                        formData.aspect_environnement,
                                    }"
                                  >
                                    Environnement
                                  </div>
                                  <div
                                    class="text-caption"
                                    :class="{
                                      'text-white':
                                        formData.aspect_environnement,
                                    }"
                                  >
                                    ISO 14001
                                  </div>
                                </div>
                                <v-spacer />
                                <v-icon
                                  v-if="formData.aspect_environnement"
                                  color="white"
                                >
                                  mdi-check-circle
                                </v-icon>
                              </div>
                            </v-card>
                          </v-col>

                          <v-col cols="12" md="4">
                            <v-card
                              class="pa-4 cursor-pointer"
                              :class="{
                                'bg-error': formData.aspect_sante_securite,
                              }"
                              variant="outlined"
                              @click="
                                formData.aspect_sante_securite =
                                  !formData.aspect_sante_securite
                              "
                            >
                              <div class="d-flex align-center gap-3">
                                <v-icon
                                  :color="
                                    formData.aspect_sante_securite
                                      ? 'white'
                                      : 'error'
                                  "
                                  size="32"
                                >
                                  mdi-shield-check
                                </v-icon>
                                <div>
                                  <div
                                    class="font-weight-bold"
                                    :class="{
                                      'text-white':
                                        formData.aspect_sante_securite,
                                    }"
                                  >
                                    Santé & Sécurité
                                  </div>
                                  <div
                                    class="text-caption"
                                    :class="{
                                      'text-white':
                                        formData.aspect_sante_securite,
                                    }"
                                  >
                                    ISO 45001
                                  </div>
                                </div>
                                <v-spacer />
                                <v-icon
                                  v-if="formData.aspect_sante_securite"
                                  color="white"
                                >
                                  mdi-check-circle
                                </v-icon>
                              </div>
                            </v-card>
                          </v-col>
                        </v-row>
                      </v-col>

                      <v-col cols="12">
                        <v-alert
                          v-if="!hasAnyAspect"
                          type="warning"
                          variant="tonal"
                        >
                          Veuillez sélectionner au moins un aspect QHSE
                        </v-alert>
                      </v-col>

                      <v-col cols="12">
                        <v-autocomplete
                          v-model="formData.normes_iso"
                          chips
                          clearable
                          item-title="code"
                          item-value="code"
                          :items="availableNorms"
                          label="Normes ISO applicables *"
                          multiple
                          variant="outlined"
                        >
                          <template #chip="{ props, item }">
                            <v-chip
                              v-bind="props"
                              closable
                              color="purple"
                              size="small"
                            >
                              {{ item.raw.code }}
                            </v-chip>
                          </template>
                          <template #item="{ props, item }">
                            <v-list-item v-bind="props">
                              <v-list-item-title>{{
                                item.raw.code
                              }}</v-list-item-title>
                              <v-list-item-subtitle>{{
                                item.raw.title
                              }}</v-list-item-subtitle>
                            </v-list-item>
                          </template>
                        </v-autocomplete>
                      </v-col>

                      <v-col cols="12" md="6">
                        <v-autocomplete
                          v-model="formData.processus_fournisseur"
                          chips
                          clearable
                          hint="Processus qui fournissent des entrées à ce processus"
                          item-title="title"
                          item-value="id"
                          :items="availableProcesses"
                          label="Processus fournisseurs (optionnel)"
                          multiple
                          persistent-hint
                          variant="outlined"
                        >
                          <template #chip="{ props, item }">
                            <v-chip
                              v-bind="props"
                              closable
                              color="indigo"
                              size="small"
                            >
                              {{ item.raw.code }}
                            </v-chip>
                          </template>
                        </v-autocomplete>
                      </v-col>

                      <v-col cols="12" md="6">
                        <v-autocomplete
                          v-model="formData.processus_client"
                          chips
                          clearable
                          hint="Processus qui reçoivent des sorties de ce processus"
                          item-title="title"
                          item-value="id"
                          :items="availableProcesses"
                          label="Processus clients (optionnel)"
                          multiple
                          persistent-hint
                          variant="outlined"
                        >
                          <template #chip="{ props, item }">
                            <v-chip
                              v-bind="props"
                              closable
                              color="teal"
                              size="small"
                            >
                              {{ item.raw.code }}
                            </v-chip>
                          </template>
                        </v-autocomplete>
                      </v-col>
                    </v-row>
                  </v-form>
                </v-card-text>
              </v-stepper-window-item>

              <!-- Step 3: Sequences (REQUIRED) -->
              <v-stepper-window-item :value="3">
                <v-card-text class="pa-6">
                  <h2 class="text-h5 mb-4">Séquences du Processus *</h2>
                  <p class="text-body-2 text-medium-emphasis mb-4">
                    Ajoutez au moins une séquence pour décrire le workflow de
                    votre processus. Chaque séquence décrit une étape du
                    processus avec ses entrées, activités et sorties.
                  </p>

                  <v-btn
                    class="mb-4"
                    color="primary"
                    prepend-icon="mdi-plus"
                    variant="outlined"
                    @click="addSequence"
                  >
                    Ajouter une séquence
                  </v-btn>

                  <div v-if="formData.sequences.length > 0">
                    <v-card
                      v-for="(seq, index) in formData.sequences"
                      :key="index"
                      class="mb-3"
                      variant="outlined"
                    >
                      <v-card-text>
                        <div class="d-flex gap-3">
                          <v-chip color="primary" size="small">{{
                            index + 1
                          }}</v-chip>
                          <div class="flex-grow-1">
                            <v-row>
                              <v-col cols="12" md="4">
                                <v-textarea
                                  v-model="seq.input_description"
                                  density="compact"
                                  hide-details
                                  label="Entrées"
                                  rows="2"
                                  variant="outlined"
                                />
                              </v-col>
                              <v-col cols="12" md="4">
                                <v-textarea
                                  v-model="seq.activity_description"
                                  density="compact"
                                  hide-details
                                  label="Activité"
                                  rows="2"
                                  variant="outlined"
                                />
                              </v-col>
                              <v-col cols="12" md="4">
                                <v-textarea
                                  v-model="seq.output_description"
                                  density="compact"
                                  hide-details
                                  label="Sorties"
                                  rows="2"
                                  variant="outlined"
                                />
                              </v-col>
                            </v-row>
                          </div>
                          <v-btn
                            color="error"
                            icon="mdi-delete"
                            size="small"
                            variant="text"
                            @click="removeSequence(index)"
                          />
                        </div>
                      </v-card-text>
                    </v-card>
                  </div>

                  <v-alert v-else type="warning" variant="tonal">
                    Vous devez ajouter au moins une séquence pour continuer.
                    Cliquez sur "Ajouter une séquence" ci-dessus.
                  </v-alert>
                </v-card-text>
              </v-stepper-window-item>

              <!-- Step 4: Review -->
              <v-stepper-window-item :value="4">
                <v-card-text class="pa-6">
                  <h2 class="text-h5 mb-4">Révision et Validation</h2>
                  <p class="text-body-2 text-medium-emphasis mb-4">
                    Vérifiez les informations avant de créer le processus
                  </p>

                  <v-row>
                    <!-- General Info -->
                    <v-col cols="12" md="6">
                      <v-card variant="outlined">
                        <v-card-title class="bg-grey-lighten-4">
                          <v-icon class="mr-2">mdi-information</v-icon>
                          Informations Générales
                        </v-card-title>
                        <v-card-text>
                          <div class="mb-2">
                            <span class="text-caption text-medium-emphasis">Code:</span>
                            <div class="font-weight-bold">
                              {{ formData.code }}
                            </div>
                          </div>
                          <div class="mb-2">
                            <span class="text-caption text-medium-emphasis">Titre:</span>
                            <div class="font-weight-bold">
                              {{ formData.title }}
                            </div>
                          </div>
                          <div class="mb-2">
                            <span class="text-caption text-medium-emphasis">Type:</span>
                            <div>
                              <v-chip
                                :color="getTypeColor(formData.type)"
                                size="small"
                              >
                                {{ getTypeLabel(formData.type) }}
                              </v-chip>
                            </div>
                          </div>
                          <div class="mb-2">
                            <span class="text-caption text-medium-emphasis">Pilote:</span>
                            <div class="font-weight-bold">
                              {{ getUserName(formData.pilot_id) }}
                            </div>
                          </div>
                          <div class="mb-2">
                            <span class="text-caption text-medium-emphasis">Finalité:</span>
                            <div class="text-body-2">
                              {{ formData.finalite }}
                            </div>
                          </div>
                        </v-card-text>
                      </v-card>
                    </v-col>

                    <!-- QHSE Classification -->
                    <v-col cols="12" md="6">
                      <v-card variant="outlined">
                        <v-card-title class="bg-grey-lighten-4">
                          <v-icon class="mr-2">mdi-tag-multiple</v-icon>
                          Classification QHSE
                        </v-card-title>
                        <v-card-text>
                          <div class="mb-3">
                            <span class="text-caption text-medium-emphasis">Aspects:</span>
                            <div class="d-flex gap-2 mt-1">
                              <v-chip
                                v-if="formData.aspect_qualite"
                                color="primary"
                                size="small"
                              >
                                Qualité
                              </v-chip>
                              <v-chip
                                v-if="formData.aspect_environnement"
                                color="success"
                                size="small"
                              >
                                Environnement
                              </v-chip>
                              <v-chip
                                v-if="formData.aspect_sante_securite"
                                color="error"
                                size="small"
                              >
                                Santé & Sécurité
                              </v-chip>
                            </div>
                          </div>
                          <div
                            v-if="
                              formData.normes_iso &&
                                formData.normes_iso.length > 0
                            "
                            class="mb-3"
                          >
                            <span class="text-caption text-medium-emphasis">Normes ISO:</span>
                            <div class="d-flex gap-2 mt-1">
                              <v-chip
                                v-for="(norme, idx) in formData.normes_iso"
                                :key="idx"
                                color="purple"
                                size="small"
                              >
                                ISO {{ norme }}
                              </v-chip>
                            </div>
                          </div>
                          <div
                            v-if="
                              formData.processus_fournisseur.length > 0 ||
                                formData.processus_client.length > 0
                            "
                          >
                            <span class="text-caption text-medium-emphasis">Interactions:</span>
                            <div
                              v-if="formData.processus_fournisseur.length > 0"
                              class="text-body-2 mt-1"
                            >
                              <strong>Fournisseurs:</strong>
                              {{
                                getProcessNames(formData.processus_fournisseur)
                              }}
                            </div>
                            <div
                              v-if="formData.processus_client.length > 0"
                              class="text-body-2 mt-1"
                            >
                              <strong>Clients:</strong>
                              {{ getProcessNames(formData.processus_client) }}
                            </div>
                          </div>
                        </v-card-text>
                      </v-card>
                    </v-col>

                    <!-- Sequences Summary -->
                    <v-col v-if="formData.sequences.length > 0" cols="12">
                      <v-card variant="outlined">
                        <v-card-title class="bg-grey-lighten-4">
                          <v-icon class="mr-2">mdi-timeline</v-icon>
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
          </template>

          <template #actions="{ prev, next }">
            <v-divider />
            <v-card-actions class="pa-4">
              <v-btn v-if="currentStep > 1" variant="text" @click="prev">
                Précédent
              </v-btn>
              <v-spacer />
              <v-btn
                v-if="currentStep < 4"
                color="primary"
                @click="handleNext(next)"
              >
                Suivant
              </v-btn>
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
          </template>
        </v-stepper>
      </v-card>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import type { Process } from '@/services/processService'
  import { computed, onMounted, ref, watch } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import api from '@/api/client'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import userService from '@/services/userService'
  import { useProcessStore } from '@/stores/processStore'
  import { useSiteStore } from '@/stores/siteStore'

  type ProcessCategory = 'pilotage' | 'support' | 'operationnel'

  interface ProcessSequenceInput {
    sequence_order: number
    input_description: string
    activity_description: string
    output_description: string
  }

  interface ProcessCreateForm {
    code: string
    title: string
    finalite: string
    type: ProcessCategory | ''
    level: number
    pilot_id: number | null
    copilot_id: number | null
    site_id: number | null
    aspect_qualite: boolean
    aspect_environnement: boolean
    aspect_sante_securite: boolean
    normes_iso: number[]
    processus_fournisseur: number[]
    processus_client: number[]
    sequences: ProcessSequenceInput[]
    ressources: string[]
    methodes: string[]
    interfaces: string[]
    status: 'draft'
  }

  interface ProcessOption {
    id: number
    title: string
    code: string
    category: Process['category']
  }

  interface ProcessUserOption {
    id: number
    name: string
    email: string
    job_title?: string
  }

  const router = useRouter()
  const route = useRoute()
  const processStore = useProcessStore()
  const siteStore = useSiteStore()

  const loading = ref(false)
  const currentStep = ref(1)
  const step1Form = ref()
  const step2Form = ref()

  const formData = ref<ProcessCreateForm>({
    code: '',
    title: '',
    finalite: '',
    type: '',
    level: 1,
    pilot_id: null,
    copilot_id: null,
    site_id: null,
    aspect_qualite: false,
    aspect_environnement: false,
    aspect_sante_securite: false,
    normes_iso: [],
    processus_fournisseur: [],
    processus_client: [],
    sequences: [],
    ressources: [],
    methodes: [],
    interfaces: [],
    status: 'draft',
  })

  const steps = [
    { title: 'Informations', value: 1 },
    { title: 'Classification', value: 2 },
    { title: 'Séquences', value: 3 },
    { title: 'Révision', value: 4 },
  ]

  const typeOptions = [
    { title: 'Pilotage', value: 'pilotage' },
    { title: 'Support', value: 'support' },
    { title: 'Opérationnel', value: 'operationnel' },
  ]

  const levelOptions = [
    { title: 'Niveau 1 - Processus principal', value: 1 },
    { title: 'Niveau 2 - Sous-processus', value: 2 },
    { title: 'Niveau 3 - Activité détaillée', value: 3 },
  ]

  const rules = {
    required: (v: any) => !!v || 'Ce champ est requis',
    differentPilotAndCopilot: (v: number | null) => {
      if (!v || !formData.value.pilot_id) return true
      return (
        v !== formData.value.pilot_id
        || 'Le co-pilote doit être différent du pilote'
      )
    },
  }

  function getUserSubtitle (user: ProcessUserOption): string {
    return user.job_title || user.email || ''
  }

  const sites = computed(() => siteStore.sites)
  const users = ref<ProcessUserOption[]>([])
  const loadingCollaborators = ref(false)
  const availableNorms = ref<any[]>([])
  const availableProcesses = ref<ProcessOption[]>([])

  const hasAnyAspect = computed(() => {
    return (
      formData.value.aspect_qualite
      || formData.value.aspect_environnement
      || formData.value.aspect_sante_securite
    )
  })

  // Watcher pour charger les utilisateurs quand le site change
  watch(
    () => formData.value.site_id,
    async newSiteId => {
      if (newSiteId) {
        await loadUsersBySite(newSiteId)
      } else {
        users.value = []
      }
    },
  )

  onMounted(async () => {
    // Charger les sites, processus et normes
    await Promise.all([
      siteStore.fetchAll(),
      processStore.fetchProcesses().catch(() => {}), // Silent fail si pas de processus
      loadNorms(),
      loadProcesses(),
    ])
    // Générer automatiquement le code du processus
    await generateProcessCode()

    const querySiteId = Number(route.query.site_id)
    const storedSiteId = Number(localStorage.getItem('current_site_id'))
    const resolvedSiteId
      = Number.isFinite(querySiteId) && querySiteId > 0
        ? querySiteId
        : (Number.isFinite(storedSiteId) && storedSiteId > 0
          ? storedSiteId
          : null)

    if (resolvedSiteId) {
      formData.value.site_id = resolvedSiteId
    }
  })

  /**
   * Charger les normes ISO disponibles
   */
  async function loadNorms () {
    try {
      // Charger les normes depuis l'API
      const response = await api.get('/norms')
      if (response.data.success && response.data.data) {
        availableNorms.value = response.data.data.map((norm: any) => ({
          id: norm.id,
          code: norm.code,
          title: norm.title || norm.name,
          domain: norm.domain,
        }))
      }
    } catch (error) {
      console.error('Error loading norms:', error)
      // Fallback sur liste prédéfinie si erreur
      availableNorms.value = [
        { id: 1, code: 'ISO 9001:2015', title: 'Management de la qualité' },
        { id: 2, code: 'ISO 14001:2015', title: 'Management environnemental' },
        { id: 3, code: 'ISO 45001:2018', title: 'Santé et sécurité au travail' },
      ]
    }
  }

  /**
   * Charger les processus existants pour les interfaces
   */
  async function loadProcesses () {
    try {
      // Charger tous les processus actifs
      const processes = processStore.processes || []
      availableProcesses.value = processes.map(p => ({
        id: p.id,
        title: p.title,
        code: p.code,
        category: p.category,
      }))
    } catch (error) {
      console.error('Error loading processes:', error)
      availableProcesses.value = []
    }
  }

  /**
   * Charger les utilisateurs d'un site spécifique
   */
  async function loadUsersBySite (siteId: number) {
    loadingCollaborators.value = true
    try {
      const siteUsers = await userService.getBySite(siteId)

      users.value = siteUsers.map(user => {
        // Construire le nom d'affichage
        let displayName = ''

        if (user.name && user.name.trim()) {
          displayName = user.name
        } else if (user.username) {
          displayName = user.username
        } else if (user.email) {
          displayName = user.email
        }

        return {
          id: user.id,
          name: displayName,
          email: user.email,
          job_title: user.roles?.[0]?.name,
        }
      })
    } catch (error) {
      console.error('Error loading users for site:', error)
      users.value = []
    } finally {
      loadingCollaborators.value = false
    }
  }

  async function generateProcessCode () {
    try {
      // Format: PROC-YYYY-NNN (ex: PROC-2026-001)
      const year = new Date().getFullYear()
      const count = (processStore.processes?.length || 0) + 1
      const paddedCount = String(count).padStart(3, '0')
      formData.value.code = `PROC-${year}-${paddedCount}`
    } catch (error) {
      console.error('Error generating process code:', error)
      formData.value.code = 'PROC-AUTO'
    }
  }

  async function handleNext (nextFn: () => void) {
    let isValid = true

    switch (currentStep.value) {
      case 1: {
        const { valid } = await step1Form.value.validate()
        isValid = valid

        break
      }
      case 2: {
        isValid = hasAnyAspect.value
        if (!isValid) {
        // Toast déjà affiché par l'alerte
        }
        // Vérifier qu'au moins une norme ISO est sélectionnée
        if (
          isValid
          && (!formData.value.normes_iso || formData.value.normes_iso.length === 0)
        ) {
          alert('Veuillez sélectionner au moins une norme ISO applicable')
          isValid = false
        }

        break
      }
      case 3: {
        // Vérifier qu'au moins une séquence existe
        isValid = formData.value.sequences.length > 0
        if (!isValid) {
          alert('Veuillez ajouter au moins une séquence pour continuer')
        }

        break
      }
    // No default
    }

    if (isValid) {
      nextFn()
    }
  }

  function addSequence () {
    formData.value.sequences.push({
      sequence_order: formData.value.sequences.length + 1,
      input_description: '',
      activity_description: '',
      output_description: '',
    })
  }

  function removeSequence (index: number) {
    formData.value.sequences.splice(index, 1)
    // Reorder
    for (const [idx, seq] of formData.value.sequences.entries()) {
      seq.sequence_order = idx + 1
    }
  }

  async function createProcess () {
    loading.value = true
    try {
      // Mapper les champs frontend → backend
      const { type, finalite, sequences, ...rest } = formData.value
      const processData: Partial<Process> = {
        code: rest.code,
        title: rest.title,
        site_id: rest.site_id,
        pilot_id: rest.pilot_id ?? undefined,
        copilot_id: rest.copilot_id ?? undefined,
        level: rest.level,
        status: rest.status,
        aspect_qualite: rest.aspect_qualite,
        aspect_environnement: rest.aspect_environnement,
        aspect_sante_securite: rest.aspect_sante_securite,
        normes_iso: rest.normes_iso.map(String),
        category: type as Process['category'],
        purpose: finalite,
        finalite,
        sequences: sequences.length > 0 ? sequences : undefined,
      }

      await processStore.createProcess(processData)
      router.push('/company/context/management-system')
    } catch (error) {
      console.error('Error creating process:', error)
    } finally {
      loading.value = false
    }
  }

  function getTypeColor (type: string): string {
    switch (type) {
      case 'pilotage': {
        return 'purple'
      }
      case 'support': {
        return 'indigo'
      }
      case 'operationnel': {
        return 'teal'
      }
      default: {
        return 'grey'
      }
    }
  }

  function getTypeIcon (type: string): string {
    switch (type) {
      case 'pilotage': {
        return 'mdi-cog'
      }
      case 'support': {
        return 'mdi-handshake'
      }
      case 'operationnel': {
        return 'mdi-factory'
      }
      default: {
        return 'mdi-circle'
      }
    }
  }

  function getTypeLabel (type: string): string {
    const option = typeOptions.find(opt => opt.value === type)
    return option?.title || type
  }

  function getUserName (userId: number | null): string {
    if (!userId) return 'Non assigné'
    const user = users.value.find(u => u.id === userId)
    return user?.name || 'Non assigné'
  }

  function getProcessNames (processIds: number[]): string {
    if (!processIds || processIds.length === 0) return 'Aucun'
    const names = processIds.map(id => {
      const process = availableProcesses.value.find(p => p.id === id)
      return process ? process.code : `ID${id}`
    })
    return names.join(', ')
  }
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
  transition: all 0.2s ease;
}

.cursor-pointer:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}
</style>
