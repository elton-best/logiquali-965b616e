<template>
  <v-card class="excel-importer" elevation="2">
    <v-card-text class="pa-6">
      <div class="text-center mb-4">
        <v-icon color="success" size="64">mdi-file-excel</v-icon>
        <h3 class="text-h6 mt-2">Importer depuis Excel</h3>
        <p class="text-body-2 text-grey-darken-1">
          Téléchargez votre plan de communication et sensibilisation
        </p>
        <v-btn
          class="mt-3"
          color="primary"
          prepend-icon="mdi-download"
          variant="tonal"
          @click="downloadTemplate"
        >
          Télécharger le template officiel
        </v-btn>
      </div>

      <v-select
        v-model="selectedYear"
        class="mb-3"
        density="comfortable"
        :items="yearOptions"
        label="Année du plan *"
        variant="outlined"
      />

      <v-file-input
        v-model="file"
        accept=".xlsx,.xls,.csv,.txt"
        :disabled="parsing"
        label="Sélectionner un fichier"
        :loading="parsing"
        prepend-icon="mdi-file-excel"
        variant="outlined"
        @update:model-value="handleFileSelect"
      />
      <v-alert
        v-if="error"
        class="mt-4"
        closable
        type="error"
        variant="tonal"
        @click:close="error = ''"
      >
        {{ error }}
      </v-alert>

      <v-alert
        v-if="success"
        class="mt-4"
        closable
        type="success"
        variant="tonal"
        @click:close="success = ''"
      >
        {{ success }}
      </v-alert>

      <v-alert v-if="yearWarning" class="mt-3" type="warning" variant="tonal">
        {{ yearWarning }}
      </v-alert>

      <v-alert v-if="selectedFile" class="mt-4" type="info" variant="tonal">
        Fichier prêt pour import: <strong>{{ selectedFile.name }}</strong>
      </v-alert>
    </v-card-text>

    <v-card-actions class="px-6 pb-6">
      <v-btn variant="text" @click="$emit('cancel')"> Annuler </v-btn>
      <v-spacer />
      <v-btn
        color="primary"
        :disabled="!selectedFile"
        variant="flat"
        @click="handleImport"
      >
        Importer le fichier
      </v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup lang="ts">
  import { computed, ref, watch } from 'vue'
  import { communicationApi } from '@/api/communications'

  const emit = defineEmits<{
    import: [
      payload: {
        year: number
        file: File
      },
    ]
    cancel: []
  }>()

  const file = ref<File[] | File | null>(null)
  const selectedFile = ref<File | null>(null)
  const parsing = ref(false)
  const error = ref('')
  const success = ref('')
  const selectedYear = ref<number | null>(new Date().getFullYear())
  const parsedYear = ref<number | null>(null)
  const yearWarning = ref('')

  const yearOptions = computed(() => {
    const currentYear = new Date().getFullYear()
    return Array.from({ length: 6 }).map((_, index) => currentYear - 1 + index)
  })

  async function handleFileSelect (files: File | File[] | null) {
    const selectedFiles = Array.isArray(files) ? files : (files ? [files] : [])
    if (selectedFiles.length === 0) return

    parsing.value = true
    error.value = ''
    success.value = ''

    try {
      selectedFile.value = selectedFiles[0] || null
      parsedYear.value = selectedYear.value

      success.value = `${selectedFile.value?.name || 'Fichier'} prêt pour l'import`
    } catch (error_: any) {
      error.value = 'Erreur lors de la lecture du fichier: ' + error_.message
    } finally {
      parsing.value = false
    }
  }

  function handleImport () {
    if (!selectedYear.value) {
      error.value = 'Veuillez sélectionner l\'année du plan.'
      return
    }
    if (!selectedFile.value) {
      error.value = 'Veuillez sélectionner un fichier.'
      return
    }
    emit('import', {
      year: selectedYear.value,
      file: selectedFile.value,
    })
  }

  watch(selectedYear, year => {
    if (!parsedYear.value || !year) {
      yearWarning.value = ''
      return
    }
    yearWarning.value
      = year === parsedYear.value
        ? ''
        : 'Année modifiée après lecture du fichier. Rechargez le fichier pour recalculer les périodes.'
  })

  async function downloadTemplate () {
    try {
      const blob = await communicationApi.downloadTemplate()
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = 'PLAN_DE_COMMUNICATION_ET_SENSIBILISATION.xlsx'
      document.body.append(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)
    } catch {
      error.value = 'Impossible de télécharger le template.'
    }
  }
</script>

<style scoped>
.excel-importer {
  border-radius: 16px;
  max-width: 800px;
  margin: 0 auto;
}

.preview-table {
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 8px;
  overflow: hidden;
}
</style>
