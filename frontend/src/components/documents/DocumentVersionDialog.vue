<template>
  <v-dialog v-model="show" max-width="600" persistent>
    <v-card>
      <v-card-title class="d-flex justify-space-between align-center">
        <span>Nouvelle version</span>
        <v-btn icon size="small" variant="text" @click="close">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-card-title>

      <v-card-text>
        <v-form ref="formRef" @submit.prevent="submit">
          <v-text-field
            v-model="form.version"
            label="Numéro de version *"
            placeholder="2.0"
            required
            :rules="[v => !!v || 'Version requise']"
          />

          <v-textarea
            v-model="form.modifications"
            label="Description des modifications"
            rows="3"
          />

          <v-file-input
            v-model="form.fichier"
            accept=".pdf,.doc,.docx,.xls,.xlsx"
            label="Fichier *"
            prepend-icon="mdi-paperclip"
            required
            :rules="[v => !!v || 'Fichier requis']"
            show-size
          />
        </v-form>
      </v-card-text>

      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" @click="close">Annuler</v-btn>
        <v-btn color="primary" :loading="loading" @click="submit">
          Créer la version
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import { computed, ref } from 'vue'
  import { documentInventoryService } from '@/services/documentInventoryService'

  const props = defineProps<{
    modelValue: boolean
    document?: any
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

  const form = ref({
    version: '',
    modifications: '',
    fichier: null,
  })

  function resetForm () {
    form.value = {
      version: '',
      modifications: '',
      fichier: null,
    }
  }

  async function submit () {
    const { valid } = await formRef.value.validate()
    if (!valid) return

    loading.value = true
    try {
      const formData = new FormData()
      formData.append('version', form.value.version)
      if (form.value.modifications) {
        formData.append('modifications', form.value.modifications)
      }
      if (form.value.fichier) {
        formData.append('fichier', form.value.fichier)
      }

      await documentInventoryService.addVersion(props.document.id, formData)
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
