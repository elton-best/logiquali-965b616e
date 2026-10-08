<template>
  <v-container class="pa-6" fluid>
    <div class="mb-6">
      <v-btn
        prepend-icon="mdi-arrow-left"
        variant="text"
        @click="$router.back()"
      >
        Retour
      </v-btn>
    </div>

    <v-card class="mx-auto" elevation="2" max-width="1200">
      <v-card-title class="text-h5 pa-6 bg-primary text-white">
        <v-icon start>mdi-file-document-plus</v-icon>
        Nouveau document QHSE
      </v-card-title>

      <v-card-text class="pa-6">
        <v-form ref="formRef" v-model="formValid">
          <!-- Step 1: Informations de base -->
          <div class="mb-6">
            <h3 class="text-h6 mb-4">📋 Informations de base</h3>

            <v-row>
              <v-col cols="12" md="6">
                <v-select
                  v-model="formData.category_id"
                  density="comfortable"
                  item-title="name"
                  item-value="id"
                  :items="categories"
                  label="Catégorie QHSE *"
                  :rules="[rules.required]"
                  variant="outlined"
                  @update:model-value="onCategoryChange"
                >
                  <template #item="{ props, item }">
                    <v-list-item v-bind="props">
                      <template #prepend>
                        <v-chip
                          :color="getCategoryColor(item.raw.level)"
                          label
                          size="small"
                        >
                          Niveau {{ item.raw.level }}
                        </v-chip>
                      </template>
                    </v-list-item>
                  </template>
                </v-select>
              </v-col>

              <v-col cols="12" md="6">
                <v-text-field
                  v-model="formData.title"
                  counter="255"
                  density="comfortable"
                  label="Titre du document *"
                  :rules="[rules.required]"
                  variant="outlined"
                />
              </v-col>

              <v-col cols="12">
                <v-textarea
                  v-model="formData.description"
                  density="comfortable"
                  label="Description"
                  rows="3"
                  variant="outlined"
                />
              </v-col>

              <v-col cols="12" md="6">
                <v-text-field
                  v-model="formData.keywords"
                  density="comfortable"
                  hint="Ex: ISO 9001, qualité, achats"
                  label="Mots-clés (séparés par des virgules)"
                  persistent-hint
                  variant="outlined"
                />
              </v-col>

              <v-col cols="12" md="6">
                <v-combobox
                  v-model="formData.tags"
                  chips
                  closable-chips
                  density="comfortable"
                  label="Tags"
                  multiple
                  variant="outlined"
                />
              </v-col>
            </v-row>
          </div>

          <v-divider class="my-6" />

          <!-- Step 2: Fichier -->
          <div class="mb-6">
            <h3 class="text-h6 mb-4">📎 Fichier</h3>

            <v-file-input
              v-model="selectedFile"
              accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx"
              density="comfortable"
              label="Sélectionner un fichier"
              prepend-icon=""
              prepend-inner-icon="mdi-paperclip"
              :rules="[rules.fileSize]"
              show-size
              variant="outlined"
            >
              <template #selection="{ fileNames }">
                <v-chip
                  v-for="fileName in fileNames"
                  :key="fileName"
                  color="primary"
                  label
                >
                  {{ fileName }}
                </v-chip>
              </template>
            </v-file-input>

            <v-alert
              v-if="selectedPrimaryFile"
              class="mt-4"
              type="info"
              variant="tonal"
            >
              <div class="d-flex align-center">
                <v-icon start>mdi-information</v-icon>
                <div>
                  <strong>Fichier sélectionné:</strong> {{ selectedPrimaryFile.name }}<br>
                  <strong>Taille:</strong> {{ formatFileSize(selectedPrimaryFile.size) }}
                </div>
              </div>
            </v-alert>
          </div>

          <v-divider class="my-6" />

          <!-- Step 3: Processus & Dates -->
          <div class="mb-6">
            <h3 class="text-h6 mb-4">🔄 Processus & Dates</h3>

            <v-row>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="formData.effective_date"
                  density="comfortable"
                  label="Date d'effet"
                  type="date"
                  variant="outlined"
                />
              </v-col>

              <v-col cols="12" md="6">
                <v-text-field
                  v-model="formData.review_due_date"
                  density="comfortable"
                  label="Prochaine révision"
                  type="date"
                  variant="outlined"
                />
              </v-col>

              <v-col cols="12" md="6">
                <v-select
                  v-model="formData.language"
                  density="comfortable"
                  :items="languages"
                  label="Langue"
                  variant="outlined"
                />
              </v-col>

              <v-col cols="12" md="6">
                <v-text-field
                  v-model.number="formData.retention_period_years"
                  density="comfortable"
                  :hint="selectedCategoryRetention !== null ? `Recommandé: ${selectedCategoryRetention} ans` : ''"
                  label="Période de rétention (années)"
                  persistent-hint
                  type="number"
                  variant="outlined"
                />
              </v-col>
            </v-row>
          </div>

          <v-divider class="my-6" />

          <!-- Step 4: Confidentialité -->
          <div class="mb-6">
            <h3 class="text-h6 mb-4">🔒 Confidentialité</h3>

            <v-row>
              <v-col cols="12" md="6">
                <v-switch
                  v-model="formData.is_confidential"
                  color="primary"
                  hide-details
                  label="Document confidentiel"
                />
              </v-col>

              <v-col v-if="formData.is_confidential" cols="12" md="6">
                <v-select
                  v-model="formData.confidentiality_level"
                  density="comfortable"
                  :items="confidentialityLevels"
                  label="Niveau de confidentialité"
                  variant="outlined"
                />
              </v-col>
            </v-row>
          </div>

          <v-divider class="my-6" />

          <!-- Actions -->
          <div class="d-flex justify-end gap-3">
            <v-btn
              color="grey"
              variant="outlined"
              @click="$router.back()"
            >
              Annuler
            </v-btn>

            <v-btn
              color="primary"
              :disabled="!formValid || !selectedFile || selectedFile.length === 0"
              :loading="loading"
              @click="submitDocument"
            >
              <v-icon start>mdi-content-save</v-icon>
              Créer le document
            </v-btn>
          </div>
        </v-form>
      </v-card-text>
    </v-card>
  </v-container>
</template>

<script setup lang="ts">
  import type { CreateDocumentPayload } from '@/types/document'
  import { storeToRefs } from 'pinia'
  import { computed, onMounted, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import { useDocumentStore } from '@/stores/documentStore'

  const router = useRouter()
  const documentStore = useDocumentStore()
  const { categories, loading } = storeToRefs(documentStore)

  const formRef = ref()
  const formValid = ref(false)
  const selectedFile = ref<File[]>([])
  const selectedPrimaryFile = computed(() => selectedFile.value[0] ?? null)

  const formData = ref({
    category_id: null as number | null,
    title: '',
    description: '',
    keywords: '',
    tags: [] as string[],
    effective_date: '',
    review_due_date: '',
    language: 'fr',
    retention_period_years: null as number | null,
    is_confidential: false,
    confidentiality_level: 'internal',
  })

  const selectedCategory = computed(() =>
    categories.value.find(c => c.id === formData.value.category_id),
  )
  const selectedCategoryRetention = computed(() => {
    const category = selectedCategory.value as Record<string, unknown> | undefined
    const retention = category?.retention_period_years
    return typeof retention === 'number' ? retention : null
  })

  const languages = [
    { title: 'Français', value: 'fr' },
    { title: 'Anglais', value: 'en' },
    { title: 'Espagnol', value: 'es' },
  ]

  const confidentialityLevels = [
    { title: 'Public', value: 'public' },
    { title: 'Interne', value: 'internal' },
    { title: 'Confidentiel', value: 'confidential' },
    { title: 'Strictement confidentiel', value: 'strictly_confidential' },
  ]

  const rules = {
    required: (v: any) => !!v || 'Ce champ est requis',
    fileSize: (files: File[]) => {
      if (!files || files.length === 0) return true
      const firstFile = files[0]
      if (!firstFile) return true
      const maxSize = 100 * 1024 * 1024 // 100MB
      return firstFile.size <= maxSize || 'Le fichier ne doit pas dépasser 100 MB'
    },
  }

  function getCategoryColor (level?: number) {
    const colors = ['blue', 'green', 'orange', 'purple', 'red']
    if (!level || level < 1) return 'grey'
    return colors[level - 1] || 'grey'
  }

  function onCategoryChange (categoryId?: number) {
    if (!categoryId) return
    const category = categories.value.find(c => c.id === categoryId)
    if (category) {
      const categoryData = category as Record<string, unknown>
      const retention = categoryData.retention_period_years
      formData.value.retention_period_years = typeof retention === 'number' ? retention : null
    }
  }

  function formatFileSize (bytes: number): string {
    if (bytes === 0) return '0 B'
    const k = 1024
    const sizes = ['B', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i]
  }

  async function submitDocument () {
    const file = selectedPrimaryFile.value
    if (!formValid.value || !file) {
      return
    }

    try {
      const payload: CreateDocumentPayload = {
        title: formData.value.title,
        type: 'other',
        category_id: formData.value.category_id || undefined,
        description: formData.value.description || undefined,
        keywords: formData.value.keywords || undefined,
        tags: formData.value.tags.length > 0 ? formData.value.tags : undefined,
        effective_date: formData.value.effective_date || undefined,
        review_due_date: formData.value.review_due_date || undefined,
        language: formData.value.language || undefined,
        retention_period_years: formData.value.retention_period_years || undefined,
        is_confidential: formData.value.is_confidential,
        confidentiality_level: formData.value.is_confidential
          ? formData.value.confidentiality_level
          : undefined,
        site_id: 1,
      }
      await documentStore.uploadDocument(file, payload)
      router.push('/company/documents')
    } catch (error) {
      console.error('Error creating document:', error)
    }
  }

  onMounted(async () => {
    await documentStore.fetchCategories()
  })
</script>
