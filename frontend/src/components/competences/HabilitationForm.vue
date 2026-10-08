<template>
  <v-dialog v-model="dialog" max-width="600px" persistent>
    <v-card>
      <v-card-title>
        {{ isEdit ? 'Modifier' : 'Nouvelle' }} habilitation
      </v-card-title>

      <v-form ref="form" @submit.prevent="save">
        <v-card-text>
          <v-row>
            <v-col cols="12">
              <v-autocomplete
                v-model="formData.user_id"
                item-title="name"
                item-value="id"
                :items="userStore.activeUsers"
                label="Employé"
                required
                :rules="[required]"
              />
            </v-col>

            <v-col cols="12">
              <v-autocomplete
                v-model="formData.competence_requise_id"
                item-title="name"
                item-value="id"
                :items="competenceStore.competencesRequises"
                label="Compétence requise"
                required
                :rules="[required]"
              />
            </v-col>

            <v-col cols="6">
              <v-select
                v-model="formData.level"
                :items="levels"
                label="Niveau"
                required
                :rules="[required]"
              />
            </v-col>

            <v-col cols="6">
              <v-select
                v-model="formData.status"
                :items="statuses"
                label="Statut"
                required
                :rules="[required]"
              />
            </v-col>

            <v-col cols="6">
              <v-text-field
                v-model="formData.obtained_date"
                label="Date d'obtention"
                required
                :rules="[required]"
                type="date"
              />
            </v-col>

            <v-col cols="6">
              <v-text-field
                v-model="formData.expiry_date"
                label="Date d'expiration"
                type="date"
              />
            </v-col>

            <v-col cols="12">
              <v-textarea
                v-model="formData.notes"
                label="Notes"
                rows="3"
              />
            </v-col>
          </v-row>
        </v-card-text>

        <v-card-actions>
          <v-spacer />
          <v-btn @click="close">Annuler</v-btn>
          <v-btn color="primary" :loading="habilitationStore.loading" type="submit">
            {{ isEdit ? 'Modifier' : 'Créer' }}
          </v-btn>
        </v-card-actions>
      </v-form>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref, watch } from 'vue'
  import { useCompetenceStore } from '@/stores/competenceStore'
  import { useHabilitationStore } from '@/stores/habilitationStore'
  import { useUserStore } from '@/stores/userStore'

  const props = defineProps<{
    modelValue: boolean
    item?: any
  }>()

  const emit = defineEmits<{
    'update:modelValue': [value: boolean]
    'saved': []
  }>()

  const habilitationStore = useHabilitationStore()
  const userStore = useUserStore()
  const competenceStore = useCompetenceStore()

  const dialog = computed({
    get: () => props.modelValue,
    set: value => emit('update:modelValue', value),
  })

  const form = ref()
  const isEdit = computed(() => !!props.item?.id)

  const formData = ref({
    user_id: null,
    competence_requise_id: null,
    level: 'base',
    status: 'active',
    obtained_date: '',
    expiry_date: '',
    notes: '',
  })

  const levels = [
    { title: 'Base', value: 'base' },
    { title: 'Intermédiaire', value: 'intermediaire' },
    { title: 'Avancé', value: 'avance' },
    { title: 'Expert', value: 'expert' },
  ]

  const statuses = [
    { title: 'Active', value: 'active' },
    { title: 'Expirée', value: 'expired' },
    { title: 'Suspendue', value: 'suspended' },
  ]

  const required = value => !!value || 'Champ requis'

  async function save () {
    const { valid } = await form.value.validate()
    if (!valid) return

    try {
      await (isEdit.value ? habilitationStore.update(props.item.id, formData.value) : habilitationStore.create(formData.value))
      emit('saved')
    } catch (error) {
      console.error('Error saving habilitation:', error)
    }
  }

  function close () {
    dialog.value = false
    resetForm()
  }

  function resetForm () {
    formData.value = {
      user_id: null,
      competence_requise_id: null,
      level: 'base',
      status: 'active',
      obtained_date: '',
      expiry_date: '',
      notes: '',
    }
  }

  watch(() => props.item, item => {
    if (item) {
      formData.value = { ...item }
    } else {
      resetForm()
    }
  }, { immediate: true })

  onMounted(async () => {
    await Promise.all([
      userStore.fetchUsers(),
      competenceStore.fetchCompetencesRequises(),
    ])
  })
</script>
