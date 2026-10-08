<template>
  <v-card>
    <v-card-title>Importer Inventaire Documentaire (XLSX)</v-card-title>

    <v-card-text>
      <!-- Site Selection -->
      <v-select
        v-model="selectedSiteId"
        class="mb-4"
        :disabled="loading"
        item-title="name"
        item-value="id"
        :items="sites"
        label="Site *"
      />

      <!-- File Upload -->
      <v-file-input
        v-model="file"
        accept=".xlsx,.xls"
        class="mb-4"
        :disabled="loading"
        label="Fichier XLSX *"
        prepend-icon="mdi-file-excel"
        :rules="fileRules"
        show-size
      />

      <!-- Format Instructions -->
      <v-alert class="mb-4" type="info" variant="tonal">
        <strong>Format attendu :</strong><br>
        • Sheet nommée "Inventaire"<br>
        • Colonnes : N°, Type, Nom, Code, Version, État, Observation<br>
        • Headers en ligne 1, données à partir ligne 2<br>
        • Types valides : politique, manuel, procedure, instruction, enregistrement, formulaire, autre
      </v-alert>

      <!-- Upload Results -->
      <v-alert
        v-if="results"
        class="mb-4"
        :type="results.errors.length > 0 ? 'error' : 'success'"
        variant="tonal"
      >
        <div class="font-weight-bold mb-2">{{ results.message }}</div>

        <div v-if="results.success > 0" class="mb-2">
          ✅ {{ results.success }} document(s) importé(s)
        </div>

        <!-- Errors List -->
        <div v-if="results.errors.length > 0">
          <div class="font-weight-bold mt-2 mb-1">Erreurs :</div>
          <v-list dense>
            <v-list-item
              v-for="(error, index) in results.errors"
              :key="index"
              class="text-caption"
            >
              <v-icon class="mr-2" color="error" small>mdi-alert-circle</v-icon>
              Ligne {{ error.line }}: {{ error.message }}
            </v-list-item>
          </v-list>
        </div>

        <!-- Created Documents -->
        <div v-if="results.created.length > 0" class="mt-2">
          <div class="font-weight-bold mb-1">Documents créés :</div>
          <v-chip
            v-for="doc in results.created"
            :key="doc.id"
            class="mr-1 mb-1"
            size="small"
          >
            {{ doc.code }}: {{ doc.name }}
          </v-chip>
        </div>
      </v-alert>

      <!-- Progress -->
      <v-progress-linear
        v-if="loading"
        class="mb-4"
        color="primary"
        indeterminate
      />
    </v-card-text>

    <v-card-actions>
      <v-spacer />
      <v-btn
        :disabled="loading"
        variant="text"
        @click="reset"
      >
        Réinitialiser
      </v-btn>
      <v-btn
        color="primary"
        :disabled="!canUpload"
        :loading="loading"
        @click="handleUpload"
      >
        <v-icon left>mdi-upload</v-icon>
        Importer
      </v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'
  import api from '@/api/client'

  interface Site {
    id: number
    name: string
  }

  interface ImportResults {
    success: number
    errors: Array<{
      line: number
      message: string
      data?: any
    }>
    created: Array<{
      line: number
      id: number
      code: string
      name: string
    }>
    message: string
  }

  const emit = defineEmits<{
    success: [results: ImportResults]
    error: [error: Error]
  }>()

  const sites = ref<Site[]>([])
  const selectedSiteId = ref<number | null>(null)
  const file = ref<File | null>(null)
  const loading = ref(false)
  const results = ref<ImportResults | null>(null)

  const fileRules = [
    (v: File | null) => !!v || 'Le fichier est requis',
    (v: File | null) => (!v || v.size < 5_000_000) || 'Le fichier doit faire moins de 5 Mo',
  ]

  const canUpload = computed(() => {
    return !!selectedSiteId.value && !!file.value && !loading.value
  })

  async function handleUpload () {
    if (!canUpload.value) return

    loading.value = true
    results.value = null

    try {
      const formData = new FormData()
      if (!file.value) return
      formData.append('file', file.value)
      formData.append('site_id', selectedSiteId.value!.toString())

      const response = await api.post('/documents/import-file', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      })

      results.value = response.data

      if (response.data.errors.length === 0) {
        emit('success', response.data)
      }
    } catch (error: any) {
      console.error('Import failed:', error)

      // Handle validation errors
      results.value = error.response?.data || {
        success: 0,
        errors: [{
          line: 0,
          message: error.message || 'Erreur inconnue',
        }],
        created: [],
        message: 'Échec de l\'import',
      }

      emit('error', error)
    } finally {
      loading.value = false
    }
  }

  function reset () {
    file.value = null
    results.value = null
  }

  async function loadSites () {
    try {
      const response = await api.get('/sites')
      sites.value = response.data.data || response.data

      // Auto-select first site if only one
      if (sites.value.length === 1) {
        const onlySite = sites.value[0]
        selectedSiteId.value = onlySite ? onlySite.id : null
      }
    } catch (error) {
      console.error('Failed to load sites:', error)
    }
  }

  onMounted(() => {
    loadSites()
  })
</script>
