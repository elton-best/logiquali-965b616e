<template>
  <v-dialog v-model="dialog" max-width="900" persistent>
    <template #activator="{ props: activatorProps }">
      <v-btn color="primary" v-bind="activatorProps">
        <v-icon start>mdi-plus</v-icon>
        Nouvelle non-conformité
      </v-btn>
    </template>

    <v-card>
      <v-card-title class="bg-primary text-white pa-4">
        <v-icon start>mdi-alert-circle</v-icon>
        {{ editMode ? 'Modifier' : 'Créer' }} une non-conformité
      </v-card-title>

      <v-card-text class="pa-6">
        <v-form ref="formRef" v-model="valid">
          <v-row>
            <!-- Titre -->
            <v-col cols="12">
              <v-text-field
                v-model="formData.title"
                density="comfortable"
                label="Titre *"
                :rules="[rules.required]"
                variant="outlined"
              />
            </v-col>

            <!-- Description -->
            <v-col cols="12">
              <v-textarea
                v-model="formData.description"
                density="comfortable"
                label="Description *"
                rows="4"
                :rules="[rules.required]"
                variant="outlined"
              />
            </v-col>

            <!-- Gravité et Type -->
            <v-col cols="12" md="6">
              <v-select
                v-model="formData.severity"
                density="comfortable"
                item-title="label"
                item-value="value"
                :items="severityOptions"
                label="Gravité *"
                :rules="[rules.required]"
                variant="outlined"
              >
                <template #item="{ props: optionProps, item: optionItem }">
                  <v-list-item v-bind="optionProps">
                    <template #prepend>
                      <v-chip :color="optionItem.raw.color" label size="small">
                        {{ optionItem.raw.label }}
                      </v-chip>
                    </template>
                  </v-list-item>
                </template>
              </v-select>
            </v-col>

            <v-col cols="12" md="6">
              <v-select
                v-model="formData.type"
                density="comfortable"
                :items="typeOptions"
                label="Type *"
                :rules="[rules.required]"
                variant="outlined"
              />
            </v-col>

            <!-- Site et Processus -->
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

            <v-col cols="12" md="6">
              <v-select
                v-model="formData.process_id"
                clearable
                density="comfortable"
                item-title="name"
                item-value="id"
                :items="processes"
                label="Processus"
                variant="outlined"
              />
            </v-col>

            <!-- Dates -->
            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.detected_date"
                density="comfortable"
                label="Date de détection *"
                :rules="[rules.required]"
                type="date"
                variant="outlined"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.due_date"
                density="comfortable"
                label="Date d'échéance"
                type="date"
                variant="outlined"
              />
            </v-col>

            <!-- Responsable -->
            <v-col cols="12" md="6">
              <v-autocomplete
                v-model="formData.responsible_user_id"
                clearable
                density="comfortable"
                item-title="name"
                item-value="id"
                :items="users"
                label="Responsable"
                variant="outlined"
              />
            </v-col>
          </v-row>
        </v-form>
      </v-card-text>

      <v-card-actions class="pa-4">
        <v-spacer />
        <v-btn variant="text" @click="closeDialog">
          Annuler
        </v-btn>
        <v-btn
          color="primary"
          :disabled="!valid"
          :loading="loading"
          @click="submit"
        >
          {{ editMode ? 'Modifier' : 'Créer' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import { computed, ref, watch } from 'vue'

  interface NonConformity {
    id?: number
    title: string
    description: string
    severity: string
    type: string
    site_id: number | null
    process_id?: number | null
    detected_date: string
    due_date?: string
    responsible_user_id?: number | null
  }

  interface Props {
    item?: NonConformity | null
    sites?: any[]
    processes?: any[]
    users?: any[]
  }

  const props = withDefaults(defineProps<Props>(), {
    item: null,
    sites: () => [],
    processes: () => [],
    users: () => [],
  })

  const emit = defineEmits<{
    submit: [data: NonConformity]
  }>()

  const dialog = ref(false)
  const formRef = ref()
  const valid = ref(false)
  const loading = ref(false)

  const formData = ref<NonConformity>({
    title: '',
    description: '',
    severity: 'medium',
    type: 'internal',
    site_id: null,
    process_id: null,
    detected_date: new Date().toISOString().split('T')[0] || '',
    due_date: '',
    responsible_user_id: null,
  })

  const editMode = computed(() => !!props.item)

  const severityOptions = [
    { value: 'low', label: 'Faible', color: 'success' },
    { value: 'medium', label: 'Moyenne', color: 'warning' },
    { value: 'high', label: 'Élevée', color: 'error' },
    { value: 'critical', label: 'Critique', color: 'error' },
  ]

  const typeOptions = [
    'Interne',
    'Client',
    'Fournisseur',
    'Audit',
    'Réclamation',
  ]

  const rules = {
    required: (v: any) => !!v || 'Ce champ est requis',
  }

  watch(() => props.item, newItem => {
    if (newItem) {
      formData.value = { ...newItem }
    }
  }, { immediate: true })

  function closeDialog () {
    dialog.value = false
    resetForm()
  }

  function resetForm () {
    formData.value = {
      title: '',
      description: '',
      severity: 'medium',
      type: 'internal',
      site_id: null,
      process_id: null,
      detected_date: new Date().toISOString().split('T')[0] || '',
      due_date: '',
      responsible_user_id: null,
    }
    formRef.value?.reset()
  }

  async function submit () {
    if (!valid.value) return

    loading.value = true
    try {
      emit('submit', formData.value)
      closeDialog()
    } finally {
      loading.value = false
    }
  }
</script>
