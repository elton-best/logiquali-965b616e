<template>
  <v-dialog v-model="dialog" max-width="900" persistent>
    <template #activator="{ props: activatorProps }">
      <v-btn color="primary" v-bind="activatorProps">
        <v-icon start>mdi-plus</v-icon>
        Nouvel audit
      </v-btn>
    </template>

    <v-card>
      <v-card-title class="bg-primary text-white pa-4">
        <v-icon start>mdi-clipboard-check</v-icon>
        {{ editMode ? 'Modifier' : 'Planifier' }} un audit
      </v-card-title>

      <v-card-text class="pa-6">
        <v-form ref="formRef" v-model="valid">
          <v-row>
            <!-- Titre -->
            <v-col cols="12">
              <v-text-field
                v-model="formData.title"
                density="comfortable"
                label="Titre de l'audit *"
                :rules="[rules.required]"
                variant="outlined"
              />
            </v-col>

            <!-- Description -->
            <v-col cols="12">
              <v-textarea
                v-model="formData.description"
                density="comfortable"
                label="Description"
                rows="3"
                variant="outlined"
              />
            </v-col>

            <!-- Type et Norme -->
            <v-col cols="12" md="6">
              <v-select
                v-model="formData.type"
                density="comfortable"
                :items="auditTypes"
                label="Type d'audit *"
                :rules="[rules.required]"
                variant="outlined"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-select
                v-model="formData.norm_id"
                clearable
                density="comfortable"
                item-title="name"
                item-value="id"
                :items="norms"
                label="Norme de référence"
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
                v-model="formData.planned_start_date"
                density="comfortable"
                label="Date de début prévue *"
                :rules="[rules.required]"
                type="date"
                variant="outlined"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.planned_end_date"
                density="comfortable"
                label="Date de fin prévue *"
                :rules="[rules.required]"
                type="date"
                variant="outlined"
              />
            </v-col>

            <!-- Auditeur et Responsable -->
            <v-col cols="12" md="6">
              <v-autocomplete
                v-model="formData.lead_auditor_id"
                density="comfortable"
                item-title="name"
                item-value="id"
                :items="users"
                label="Auditeur principal *"
                :rules="[rules.required]"
                variant="outlined"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-autocomplete
                v-model="formData.responsible_user_id"
                clearable
                density="comfortable"
                item-title="name"
                item-value="id"
                :items="users"
                label="Responsable audité"
                variant="outlined"
              />
            </v-col>

            <!-- Équipe d'audit -->
            <v-col cols="12">
              <v-autocomplete
                v-model="formData.team_members"
                chips
                closable-chips
                density="comfortable"
                item-title="name"
                item-value="id"
                :items="users"
                label="Équipe d'audit"
                multiple
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
          {{ editMode ? 'Modifier' : 'Planifier' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import { computed, ref, watch } from 'vue'

  interface Audit {
    id?: number
    title: string
    description?: string
    type: string
    norm_id?: number | null
    site_id: number | null
    process_id?: number | null
    planned_start_date: string
    planned_end_date: string
    lead_auditor_id: number | null
    responsible_user_id?: number | null
    team_members?: number[]
  }

  interface Props {
    item?: Audit | null
    sites?: any[]
    processes?: any[]
    users?: any[]
    norms?: any[]
  }

  const props = withDefaults(defineProps<Props>(), {
    item: null,
    sites: () => [],
    processes: () => [],
    users: () => [],
    norms: () => [],
  })

  const emit = defineEmits<{
    submit: [data: Audit]
  }>()

  const dialog = ref(false)
  const formRef = ref()
  const valid = ref(false)
  const loading = ref(false)

  const formData = ref<Audit>({
    title: '',
    description: '',
    type: 'internal',
    norm_id: null,
    site_id: null,
    process_id: null,
    planned_start_date: '',
    planned_end_date: '',
    lead_auditor_id: null,
    responsible_user_id: null,
    team_members: [],
  })

  const editMode = computed(() => !!props.item)

  const auditTypes = [
    'Interne',
    'Externe',
    'Certification',
    'Fournisseur',
    'Tierce partie',
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
      type: 'internal',
      norm_id: null,
      site_id: null,
      process_id: null,
      planned_start_date: '',
      planned_end_date: '',
      lead_auditor_id: null,
      responsible_user_id: null,
      team_members: [],
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
