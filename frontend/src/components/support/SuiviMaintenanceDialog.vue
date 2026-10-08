<template>
  <v-dialog v-model="isOpen" max-width="700" scrollable>
    <v-card v-if="maintenance" rounded="lg">
      <v-card-title class="pa-6 d-flex align-center">
        <v-icon color="primary" start>mdi-clipboard-check</v-icon>
        Suivi de maintenance
        <v-spacer />
        <v-btn icon="mdi-close" variant="text" @click="close" />
      </v-card-title>
      <v-divider />

      <v-card-text class="pa-6">
        <!-- Equipment Info -->
        <v-card class="mb-6 bg-surface-variant" elevation="0" rounded="lg">
          <v-card-text class="pa-4">
            <div class="text-subtitle-2 text-medium-emphasis mb-2">Équipement</div>
            <div class="font-weight-medium">{{ maintenance.equipement?.nom_commun }}</div>
            <code class="text-caption">{{ maintenance.equipement?.code_complet }}</code>
            <v-divider class="my-2" />
            <v-row dense>
              <v-col cols="6">
                <div class="text-caption text-medium-emphasis">Type</div>
                <div class="text-body-2">{{ maintenance.type }}</div>
              </v-col>
              <v-col cols="6">
                <div class="text-caption text-medium-emphasis">Date prévue</div>
                <div class="text-body-2">{{ formatDate(maintenance.date_prevue) }}</div>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>

        <!-- Form -->
        <v-form @submit.prevent="handleSubmit">
          <v-select
            v-model="form.action"
            class="mb-4"
            :items="[
              { value: 'realise', title: 'Maintenance effectuée' },
              { value: 'reporte', title: 'Maintenance reportée' }
            ]"
            label="Action *"
            variant="outlined"
          />

          <v-text-field
            v-model="form.date_action"
            class="mb-4"
            label="Date d'action *"
            type="date"
            variant="outlined"
          />

          <v-text-field
            v-if="form.action === 'reporte'"
            v-model="form.nouvelle_date_prevue"
            class="mb-4"
            label="Nouvelle date prévue *"
            type="date"
            variant="outlined"
          />

          <v-textarea
            v-model="form.commentaire"
            class="mb-4"
            label="Commentaire"
            rows="3"
            variant="outlined"
          />

          <v-file-input
            v-if="form.action === 'realise'"
            v-model="selectedFile"
            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
            class="mb-4"
            hint="PDF, Images, Word"
            label="Preuve (document)"
            persistent-hint
            prepend-icon="mdi-paperclip"
            variant="outlined"
          />

          <!-- History -->
          <div v-if="maintenance.suivis && maintenance.suivis.length > 0" class="mt-6">
            <h4 class="text-subtitle-1 mb-3">Historique des suivis</h4>
            <v-timeline density="compact" side="end">
              <v-timeline-item
                v-for="suivi in maintenance.suivis"
                :key="suivi.id"
                :dot-color="suivi.action === 'realise' ? 'success' : 'warning'"
                size="small"
              >
                <template #opposite>
                  <div class="text-caption">{{ formatDate(suivi.date_action) }}</div>
                </template>
                <v-card class="bg-surface-variant" elevation="0">
                  <v-card-text class="pa-3">
                    <v-chip class="mb-2" :color="suivi.action === 'realise' ? 'success' : 'warning'" size="x-small">
                      {{ suivi.action === 'realise' ? 'Réalisé' : 'Reporté' }}
                    </v-chip>
                    <div v-if="suivi.commentaire" class="text-body-2">{{ suivi.commentaire }}</div>
                    <div v-if="suivi.nouvelle_date_prevue" class="text-caption text-medium-emphasis mt-1">
                      Nouvelle date: {{ formatDate(suivi.nouvelle_date_prevue) }}
                    </div>
                  </v-card-text>
                </v-card>
              </v-timeline-item>
            </v-timeline>
          </div>

          <div class="d-flex justify-end gap-2 mt-6">
            <v-btn variant="outlined" @click="close">Annuler</v-btn>
            <v-btn color="primary" :loading="loading" type="submit">Enregistrer</v-btn>
          </div>
        </v-form>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import type { Maintenance } from '@/services/supportService'
  import { ref, watch } from 'vue'
  import { useSupportStore } from '@/stores/supportStore'

  const props = defineProps<{ modelValue: boolean, maintenance: Maintenance | null }>()
  const emit = defineEmits<{ 'update:modelValue': [value: boolean], 'updated': [] }>()

  const store = useSupportStore()
  const isOpen = ref(props.modelValue)
  const loading = ref(false)
  const selectedFile = ref<File | null>(null)

  watch(() => props.modelValue, val => {
    isOpen.value = val
  })
  watch(isOpen, val => {
    emit('update:modelValue', val)
  })

  const form = ref({
    action: 'realise',
    date_action: new Date().toISOString().split('T')[0] || '',
    nouvelle_date_prevue: '',
    commentaire: '',
  })

  function close () {
    isOpen.value = false
    form.value = { action: 'realise', date_action: new Date().toISOString().split('T')[0] || '', nouvelle_date_prevue: '', commentaire: '' }
    selectedFile.value = null
  }

  const formatDate = (date: string) => new Date(date).toLocaleDateString('fr-FR')

  async function handleSubmit () {
    if (!props.maintenance?.id) return

    loading.value = true
    try {
      const formData = new FormData()
      formData.append('action', form.value.action)
      formData.append('date_action', form.value.date_action)
      if (form.value.nouvelle_date_prevue) formData.append('nouvelle_date_prevue', form.value.nouvelle_date_prevue)
      if (form.value.commentaire) formData.append('commentaire', form.value.commentaire)
      if (selectedFile.value) formData.append('preuve', selectedFile.value)

      await store.suivreMaintenance(props.maintenance.id, formData)
      emit('updated')
      close()
    } catch (error) {
      console.error(error)
    } finally {
      loading.value = false
    }
  }
</script>
