/**
 * Create Non-Conformity Page - Modern 2026 Design
 * Form to create a new non-conformity with advanced features
 */

<script setup lang="ts">
  import type { CreateNCDTO, NCSource, NCType } from '@/api/services/nonconformities.service'
  import {
    Activity,
    AlertCircle,
    AlertTriangle,
    ArrowLeft,
    FileText,
    MapPin,
    Plus,
    Save,
    Shield,
    Target,
    Upload,
    User,
    X,
  } from 'lucide-vue-next'
  import { computed, onMounted, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import AppDatePickerField from '@/components/common/AppDatePickerField.vue'
  import { useNonConformities } from '@/modules/clienta/composables/useNonConformities'
  import { useSites } from '@/modules/clienta/composables/useSites'
  import { useUsers } from '@/modules/clienta/composables/useUsers'
  import { useToast } from '@/modules/shared/composables/useToast'
  import { getErrorMessage } from '@/utils/errorMessage'

  const router = useRouter()
  const toast = useToast()
  const { createNonConformity, loading, error } = useNonConformities()
  const { sites, fetchSites } = useSites()
  const { users, fetchUsers } = useUsers()

  // Demo mode
  const isDemoMode = ref(true)

  const formData = ref<CreateNCDTO>({
    title: isDemoMode.value ? 'Équipements de protection non conformes' : '',
    description: isDemoMode.value ? 'Plusieurs casques de sécurité présentent des fissures et des sangles usées, ne garantissant plus la protection adéquate des travailleurs.' : '',
    type: 'majeure',
    source: 'interne',
    site_id: 0,
    detected_by: isDemoMode.value ? 'Marie Dupont' : '',
    detected_date: new Date().toISOString().split('T')[0] || '',
    severity: isDemoMode.value ? 4 : 3,
    immediate_action: isDemoMode.value ? 'Retrait immédiat des équipements défectueux et distribution de nouveaux casques de sécurité.' : '',
    due_date: '',
  })

  // Additional form fields not in DTO
  const rootCause = ref(isDemoMode.value ? 'Absence de contrôle périodique des EPI et stockage inapproprié exposant les équipements aux UV.' : '')
  const impact = ref(isDemoMode.value ? 'Risque élevé de blessures graves en cas d\'accident du travail' : '')
  const responsibleId = ref<number>(0)
  const correctiveAction = ref(isDemoMode.value ? 'Mise en place d\'un programme de contrôle mensuel des EPI et création d\'un registre de suivi.' : '')
  const preventiveAction = ref(isDemoMode.value ? 'Formation du personnel sur l\'inspection visuelle des EPI et installation d\'un local de stockage adapté.' : '')
  const tags = ref<string[]>(isDemoMode.value ? ['EPI', 'Sécurité', 'Urgent'] : [])
  const newTag = ref('')
  const photos = ref<File[]>([])

  const ncTypes: { value: NCType, label: string, description: string, icon: any, color: string }[] = [
    {
      value: 'mineure',
      label: 'Mineure',
      description: 'Impact limité, correction simple',
      icon: AlertCircle,
      color: 'warning',
    },
    {
      value: 'majeure',
      label: 'Majeure',
      description: 'Impact significatif, nécessite action',
      icon: AlertTriangle,
      color: 'orange',
    },
    {
      value: 'critique',
      label: 'Critique',
      description: 'Impact majeur, action urgente requise',
      icon: Shield,
      color: 'error',
    },
  ]

  const ncSources: { value: NCSource, label: string, icon: any }[] = [
    { value: 'audit', label: 'Audit', icon: FileText },
    { value: 'reclamation', label: 'Réclamation client', icon: User },
    { value: 'interne', label: 'Détection interne', icon: Activity },
  ]

  const severityLabels = [
    'Très faible',
    'Faible',
    'Moyen',
    'Élevé',
    'Très élevé',
  ]

  const severityColor = computed(() => {
    if (formData.value.severity <= 2) return 'success'
    if (formData.value.severity === 3) return 'warning'
    if (formData.value.severity === 4) return 'orange'
    return 'error'
  })

  function addTag () {
    if (newTag.value.trim() && !tags.value.includes(newTag.value.trim())) {
      tags.value.push(newTag.value.trim())
      newTag.value = ''
    }
  }

  function removeTag (tag: string) {
    tags.value = tags.value.filter(t => t !== tag)
  }

  function handleFileUpload (event: Event) {
    const target = event.target as HTMLInputElement
    if (target.files) {
      photos.value = [...photos.value, ...Array.from(target.files)]
    }
  }

  function removePhoto (index: number) {
    photos.value = photos.value.filter((_, i) => i !== index)
  }

  function getPhotoPreviewUrl (photo: File): string {
    return window.URL.createObjectURL(photo)
  }

  async function handleSubmit () {
    try {
      // Validate required fields
      if (!formData.value.title) {
        error.value = 'Le titre est requis.'
        return
      }
      if (!formData.value.description) {
        error.value = 'La description est requise.'
        return
      }
      if (!formData.value.site_id) {
        error.value = 'Le site est requis.'
        return
      }
      if (!formData.value.detected_by) {
        error.value = 'Le détecteur est requis.'
        return
      }

      error.value = null
      await createNonConformity(formData.value)
      toast.success('Non-conformité créée.')
      router.push('/company/nonconformities')
    } catch (error_) {
      console.error('Failed to create non-conformity:', error_)
      error.value = getErrorMessage(error_, 'Impossible de créer la non-conformité.')
      toast.error(error.value)
    }
  }

  function goBack () {
    router.push('/company/nonconformities')
  }

  onMounted(() => {
    fetchSites({ per_page: 100 })
    fetchUsers({ per_page: 100 })
  })
</script>

<template>
  <v-container class="pa-6" fluid>
    <v-row>
      <v-col cols="12">
        <!-- Header -->
        <div class="mb-6">
          <v-btn
            class="mb-4"
            color="default"
            variant="text"
            @click="goBack"
          >
            <ArrowLeft class="mr-2" :size="16" />
            Retour à la liste
          </v-btn>

          <div class="d-flex align-center mb-2">
            <AlertTriangle class="text-primary mr-3" :size="32" />
            <div>
              <h1 class="text-h4 font-weight-bold">Nouvelle non-conformité</h1>
              <p class="text-body-2 text-medium-emphasis">
                Créer une nouvelle non-conformité QHSE avec analyse complète
              </p>
            </div>
          </div>
        </div>

        <!-- Error Alert -->
        <v-alert
          v-if="error"
          class="mb-6"
          closable
          type="error"
          variant="tonal"
        >
          {{ error }}
        </v-alert>

        <!-- Form -->
        <v-form @submit.prevent="handleSubmit">
          <!-- Section 1: Identification -->
          <v-card class="mb-6" elevation="2">
            <v-card-title class="bg-surface-variant">
              <div class="d-flex align-center">
                <FileText class="mr-2" :size="20" />
                <span>Section 1 : Identification</span>
              </div>
            </v-card-title>

            <v-card-text class="pa-6">
              <v-row>
                <v-col cols="12">
                  <v-text-field
                    v-model="formData.title"
                    density="comfortable"
                    label="Titre"
                    placeholder="Ex: Équipements de protection non conformes"
                    required
                    :rules="[v => !!v || 'Le titre est requis']"
                    variant="outlined"
                  >
                    <template #prepend-inner>
                      <FileText class="text-medium-emphasis" :size="18" />
                    </template>
                  </v-text-field>
                </v-col>

                <v-col cols="12">
                  <v-textarea
                    v-model="formData.description"
                    label="Description"
                    placeholder="Décrivez la non-conformité en détail..."
                    required
                    rows="4"
                    :rules="[v => !!v || 'La description est requise']"
                    variant="outlined"
                  />
                </v-col>

                <v-col cols="12" md="6">
                  <label class="text-subtitle-2 font-weight-medium mb-3 d-block">
                    Type <span class="text-error">*</span>
                  </label>

                  <v-item-group v-model="formData.type" mandatory>
                    <v-row>
                      <v-col
                        v-for="type in ncTypes"
                        :key="type.value"
                        cols="12"
                      >
                        <v-item v-slot="{ isSelected, toggle }" :value="type.value">
                          <v-card
                            border
                            class="cursor-pointer"
                            :class="isSelected ? 'border-primary' : ''"
                            :color="isSelected ? `${type.color}-lighten-5` : ''"
                            flat
                            @click="toggle"
                          >
                            <v-card-text class="d-flex align-start pa-4">
                              <v-radio
                                class="mt-n1 mr-2"
                                color="primary"
                                :model-value="isSelected"
                                readonly
                              />
                              <component
                                :is="type.icon"
                                :class="`text-${type.color} mr-3`"
                                :size="24"
                              />
                              <div class="flex-grow-1">
                                <div class="font-weight-medium mb-1">{{ type.label }}</div>
                                <div class="text-caption text-medium-emphasis">
                                  {{ type.description }}
                                </div>
                              </div>
                            </v-card-text>
                          </v-card>
                        </v-item>
                      </v-col>
                    </v-row>
                  </v-item-group>
                </v-col>

                <v-col cols="12" md="6">
                  <v-select
                    v-model="formData.source"
                    density="comfortable"
                    item-title="label"
                    item-value="value"
                    :items="ncSources"
                    label="Source"
                    required
                    variant="outlined"
                  >
                    <template #prepend-inner>
                      <Activity class="text-medium-emphasis" :size="18" />
                    </template>
                    <template #item="{ props, item }">
                      <v-list-item v-bind="props">
                        <template #prepend>
                          <component :is="item.raw.icon" :size="20" />
                        </template>
                      </v-list-item>
                    </template>
                  </v-select>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <!-- Section 2: Détection -->
          <v-card class="mb-6" elevation="2">
            <v-card-title class="bg-surface-variant">
              <div class="d-flex align-center">
                <MapPin class="mr-2" :size="20" />
                <span>Section 2 : Détection</span>
              </div>
            </v-card-title>

            <v-card-text class="pa-6">
              <v-row>
                <v-col cols="12" md="6">
                  <v-select
                    v-model.number="formData.site_id"
                    density="comfortable"
                    item-title="name"
                    item-value="id"
                    :items="sites"
                    label="Site"
                    required
                    variant="outlined"
                  >
                    <template #prepend-inner>
                      <MapPin class="text-medium-emphasis" :size="18" />
                    </template>
                  </v-select>
                </v-col>

                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="formData.detected_by"
                    density="comfortable"
                    label="Détecté par"
                    placeholder="Nom de la personne"
                    required
                    variant="outlined"
                  >
                    <template #prepend-inner>
                      <User class="text-medium-emphasis" :size="18" />
                    </template>
                  </v-text-field>
                </v-col>

                <v-col cols="12" md="6">
                  <AppDatePickerField
                    v-model="formData.detected_date"
                    label="Date de détection"
                    mode="date"
                    required
                  />
                </v-col>

                <v-col cols="12" md="6">
                  <AppDatePickerField
                    v-model="formData.due_date"
                    label="Date d'échéance"
                    mode="date"
                  />
                </v-col>

                <!-- Severity Slider -->
                <v-col cols="12">
                  <label class="text-subtitle-2 font-weight-medium mb-4 d-block">
                    Niveau de sévérité : {{ formData.severity }}/5
                    <v-chip
                      class="ml-2"
                      :color="severityColor"
                      size="small"
                      variant="flat"
                    >
                      {{ severityLabels[formData.severity - 1] }}
                    </v-chip>
                  </label>

                  <v-slider
                    v-model="formData.severity"
                    class="mb-4"
                    :color="severityColor"
                    :max="5"
                    :min="1"
                    show-ticks="always"
                    :step="1"
                    thumb-label
                    tick-size="4"
                  >
                    <template #prepend>
                      <span class="text-caption">1 - Très faible</span>
                    </template>
                    <template #append>
                      <span class="text-caption">5 - Très élevé</span>
                    </template>
                  </v-slider>

                  <!-- Visual Severity Indicator -->
                  <v-row class="mt-2" dense>
                    <v-col
                      v-for="i in 5"
                      :key="i"
                      class="flex-grow-1"
                      cols="auto"
                    >
                      <div
                        :class="[
                          'rounded pa-3 transition-all',
                          i <= formData.severity
                            ? i <= 2
                              ? 'bg-success'
                              : i <= 3
                                ? 'bg-warning'
                                : i <= 4
                                  ? 'bg-orange'
                                  : 'bg-error'
                            : 'bg-surface-variant'
                        ]"
                      />
                    </v-col>
                  </v-row>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <!-- Section 3: Analyse -->
          <v-card class="mb-6" elevation="2">
            <v-card-title class="bg-surface-variant">
              <div class="d-flex align-center">
                <Activity class="mr-2" :size="20" />
                <span>Section 3 : Analyse</span>
              </div>
            </v-card-title>

            <v-card-text class="pa-6">
              <v-row>
                <v-col cols="12">
                  <v-textarea
                    v-model="rootCause"
                    label="Cause racine"
                    placeholder="Analysez la cause racine de la non-conformité..."
                    rows="3"
                    variant="outlined"
                  />
                </v-col>

                <v-col cols="12">
                  <v-textarea
                    v-model="impact"
                    label="Impact"
                    placeholder="Décrivez l'impact de cette non-conformité..."
                    rows="2"
                    variant="outlined"
                  />
                </v-col>

                <v-col cols="12">
                  <v-textarea
                    v-model="formData.immediate_action"
                    label="Action immédiate prise"
                    placeholder="Décrivez les mesures immédiates prises pour contenir la non-conformité..."
                    rows="3"
                    variant="outlined"
                  />
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <!-- Section 4: Actions -->
          <v-card class="mb-6" elevation="2">
            <v-card-title class="bg-surface-variant">
              <div class="d-flex align-center">
                <Target class="mr-2" :size="20" />
                <span>Section 4 : Actions correctives et préventives</span>
              </div>
            </v-card-title>

            <v-card-text class="pa-6">
              <v-row>
                <v-col cols="12" md="6">
                  <v-select
                    v-model.number="responsibleId"
                    density="comfortable"
                    item-title="name"
                    item-value="id"
                    :items="users"
                    label="Responsable"
                    variant="outlined"
                  >
                    <template #prepend-inner>
                      <User class="text-medium-emphasis" :size="18" />
                    </template>
                  </v-select>
                </v-col>

                <v-col cols="12">
                  <v-textarea
                    v-model="correctiveAction"
                    label="Action corrective"
                    placeholder="Décrivez les actions correctives à mettre en place..."
                    rows="3"
                    variant="outlined"
                  />
                </v-col>

                <v-col cols="12">
                  <v-textarea
                    v-model="preventiveAction"
                    label="Action préventive"
                    placeholder="Décrivez les actions préventives pour éviter la récurrence..."
                    rows="3"
                    variant="outlined"
                  />
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <!-- Photos Upload -->
          <v-card class="mb-6" elevation="2">
            <v-card-title class="bg-surface-variant">
              <div class="d-flex align-center">
                <Upload class="mr-2" :size="20" />
                <span>Photos</span>
              </div>
            </v-card-title>

            <v-card-text class="pa-6">
              <v-file-input
                accept="image/*"
                density="comfortable"
                label="Ajouter des photos"
                multiple
                prepend-icon=""
                variant="outlined"
                @change="handleFileUpload"
              >
                <template #prepend-inner>
                  <Upload class="text-medium-emphasis" :size="18" />
                </template>
              </v-file-input>

              <v-row v-if="photos.length > 0" class="mt-4">
                <v-col
                  v-for="(photo, index) in photos"
                  :key="index"
                  cols="6"
                  md="3"
                  sm="4"
                >
                  <v-card class="position-relative">
                    <v-img
                      aspect-ratio="1"
                      class="rounded"
                      cover
                      :src="getPhotoPreviewUrl(photo)"
                    />
                    <v-btn
                      class="position-absolute"
                      color="error"
                      icon
                      size="small"
                      style="top: 8px; right: 8px"
                      @click="removePhoto(index)"
                    >
                      <X :size="16" />
                    </v-btn>
                  </v-card>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <!-- Tags -->
          <v-card class="mb-6" elevation="2">
            <v-card-title class="bg-surface-variant">Tags</v-card-title>

            <v-card-text class="pa-6">
              <div class="d-flex gap-2 mb-4">
                <v-chip
                  v-for="tag in tags"
                  :key="tag"
                  closable
                  color="primary"
                  variant="flat"
                  @click:close="removeTag(tag)"
                >
                  {{ tag }}
                </v-chip>
              </div>

              <div class="d-flex gap-2">
                <v-text-field
                  v-model="newTag"
                  density="comfortable"
                  hide-details
                  label="Ajouter un tag"
                  variant="outlined"
                  @keyup.enter="addTag"
                />
                <v-btn
                  color="primary"
                  :disabled="!newTag.trim()"
                  @click="addTag"
                >
                  <Plus class="mr-1" :size="18" />
                  Ajouter
                </v-btn>
              </div>
            </v-card-text>
          </v-card>

          <!-- Footer Actions -->
          <v-card elevation="2">
            <v-card-actions class="pa-4 justify-end">
              <v-btn
                :disabled="loading"
                size="large"
                variant="text"
                @click="goBack"
              >
                Annuler
              </v-btn>
              <v-btn
                class="px-6"
                color="primary"
                :loading="loading"
                size="large"
                type="submit"
              >
                <Save class="mr-2" :size="18" />
                {{ loading ? 'Création...' : 'Créer la NC' }}
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-form>
      </v-col>
    </v-row>
  </v-container>
</template>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}

.bg-orange {
  background-color: rgb(var(--v-theme-orange));
}

.text-orange {
  color: rgb(var(--v-theme-orange));
}
</style>
