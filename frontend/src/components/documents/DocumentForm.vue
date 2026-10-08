<template>
  <v-dialog v-model="show" max-width="800" persistent>
    <v-card>
      <v-card-title class="d-flex justify-space-between align-center">
        <span>{{ isEdit ? 'Modifier le document' : 'Nouveau document' }}</span>
        <v-btn icon size="small" variant="text" @click="close">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-card-title>

      <v-card-text>
        <v-form ref="formRef" @submit.prevent="submit">
          <v-row>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.nom"
                label="Nom du document *"
                required
                :rules="[v => !!v || 'Nom requis']"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-select
                v-model="form.type"
                :items="typeOptions"
                label="Type *"
                required
                :rules="[v => !!v || 'Type requis']"
                @update:model-value="onTypeChange"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.code"
                :hint="codePreview ? `Aperçu: ${codePreview}` : 'Laissez vide pour génération automatique'"
                label="Code"
                :loading="loadingPreview"
                persistent-hint
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.version"
                label="Version"
                placeholder="1.0"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-select
                v-model="form.processus"
                :items="processOptions"
                label="Processus"
                @update:model-value="updateCodePreview"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-select
                v-model="form.statut"
                :items="statutOptions"
                label="Statut"
              />
            </v-col>

            <v-col cols="12">
              <v-textarea
                v-model="form.description"
                label="Description"
                rows="3"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-select
                v-model="form.etat"
                :items="etatOptions"
                label="État"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model.number="form.periodicite_revision"
                label="Périodicité de révision (mois)"
                type="number"
              />
            </v-col>

            <v-col cols="12">
              <v-file-input
                v-model="form.fichier"
                accept=".pdf,.doc,.docx,.xls,.xlsx"
                label="Fichier"
                prepend-icon="mdi-paperclip"
                show-size
              />
            </v-col>
          </v-row>
        </v-form>
      </v-card-text>

      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" @click="close">Annuler</v-btn>
        <v-btn color="primary" :loading="loading" @click="submit">
          {{ isEdit ? 'Mettre à jour' : 'Créer' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import { computed, ref, watch } from 'vue'
  import { apiClient } from '@/api/client'
  import { documentInventoryService } from '@/services/documentInventoryService'

  const props = defineProps<{
    modelValue: boolean
    item?: any
    siteId?: number
  }>()

  const emit = defineEmits<{
    'update:modelValue': [value: boolean]
    'saved': []
  }>()

  const show = computed({
    get: () => props.modelValue,
    set: value => emit('update:modelValue', value),
  })

  const formRef = ref()
  const loading = ref(false)
  const loadingPreview = ref(false)
  const codePreview = ref('')
  const isEdit = computed(() => !!props.item?.id)

  const form = ref({
    nom: '',
    type: 'PRC',
    code: '',
    version: '1.0',
    processus: '',
    statut: 'brouillon',
    description: '',
    etat: 'en_cours',
    periodicite_revision: null,
    fichier: null,
    date_creation: new Date().toISOString().split('T')[0],
  })

  const typeOptions = [
    { title: 'Politique (POL)', value: 'POL' },
    { title: 'Procédure (PRC)', value: 'PRC' },
    { title: 'Instruction (PRD)', value: 'PRD' },
    { title: 'Formulaire (FOR)', value: 'FOR' },
    { title: 'Enregistrement (ENR)', value: 'ENR' },
  ]

  const processOptions = [
    { title: 'Management (MAN)', value: 'MAN' },
    { title: 'Ressources Humaines (RH)', value: 'RH' },
    { title: 'Commercial (COM)', value: 'COM' },
    { title: 'Production (PRO)', value: 'PRO' },
    { title: 'Qualité (QUA)', value: 'QUA' },
  ]

  const statutOptions = [
    { title: 'brouillon en attente de vérification', value: 'brouillon' },
    { title: 'brouillon en cours de vérification', value: 'en_revision' },
    { title: 'validé - version 1', value: 'valide' },
  ]

  const etatOptions = [
    { title: 'À établir', value: 'a_etablir' },
    { title: 'En cours', value: 'en_cours' },
    { title: 'Terminé', value: 'termine' },
  ]

  watch(() => props.item, newItem => {
    if (newItem) {
      form.value = {
        nom: newItem.nom || '',
        type: newItem.type || 'PRC',
        code: newItem.code || '',
        version: newItem.version || '1.0',
        processus: newItem.processus || '',
        statut: newItem.statut || 'brouillon',
        description: newItem.description || '',
        etat: newItem.etat || 'en_cours',
        periodicite_revision: newItem.periodicite_revision || null,
        fichier: null,
        date_creation: newItem.date_creation || new Date().toISOString().split('T')[0],
      }
    } else {
      resetForm()
    }
  }, { immediate: true })

  watch(() => props.modelValue, newVal => {
    if (newVal && !isEdit.value) {
      updateCodePreview()
    }
  })

  function resetForm () {
    form.value = {
      nom: '',
      type: 'PRC',
      code: '',
      version: '1.0',
      processus: '',
      statut: 'brouillon',
      description: '',
      etat: 'en_cours',
      periodicite_revision: null,
      fichier: null,
      date_creation: new Date().toISOString().split('T')[0],
    }
    codePreview.value = ''
  }

  function onTypeChange () {
    updateCodePreview()
  }

  async function updateCodePreview () {
    if (isEdit.value || !form.value.type || form.value.code) {
      codePreview.value = ''
      return
    }

    loadingPreview.value = true
    try {
      const params: any = {
        type: form.value.type,
      }

      if (props.siteId) {
        params.site_id = props.siteId
      }

      if (form.value.processus) {
        params.processus = form.value.processus
      }

      const response = await apiClient.get('/documents/preview-code', { params })
      codePreview.value = response.data.data?.code || ''
    } catch (error) {
      console.error('Error loading code preview:', error)
      codePreview.value = ''
    } finally {
      loadingPreview.value = false
    }
  }

  async function submit () {
    const { valid } = await formRef.value.validate()
    if (!valid) return

    loading.value = true
    try {
      const formData = new FormData()
      for (const [key, value] of Object.entries(form.value)) {
        if (value !== null && value !== undefined) {
          if (key === 'fichier' && value instanceof File) {
            formData.append(key, value)
          } else if (typeof value !== 'object') {
            formData.append(key, String(value))
          }
        }
      }

      if (props.siteId) {
        formData.append('site_id', String(props.siteId))
      }

      await (isEdit.value ? documentInventoryService.update(props.item.id, formData) : documentInventoryService.create(formData))

      emit('saved')
      close()
    } finally {
      loading.value = false
    }
  }

  function close () {
    show.value = false
    resetForm()
  }
</script>
