<template>
  <v-dialog v-model="isOpen" max-width="600">
    <v-card rounded="lg">
      <v-card-title class="pa-6 d-flex align-center">
        <v-icon color="primary" start>mdi-calendar-plus</v-icon>
        Planifier une maintenance
        <v-spacer />
        <v-btn icon="mdi-close" variant="text" @click="close" />
      </v-card-title>
      <v-divider />

      <v-card-text class="pa-6">
        <v-form @submit.prevent="handleSubmit">
          <v-select
            v-model="form.equipement_id"
            class="mb-4"
            item-title="nom_commun"
            item-value="id"
            :items="equipements"
            label="Équipement *"
            variant="outlined"
          >
            <template #item="{ props: optionProps, item }">
              <v-list-item v-bind="optionProps">
                <v-list-item-subtitle>{{ item.raw.code_complet }}</v-list-item-subtitle>
              </v-list-item>
            </template>
          </v-select>

          <v-select
            v-model="form.type"
            class="mb-4"
            :items="[
              { value: 'preventive', title: 'Préventive' },
              { value: 'corrective', title: 'Corrective' },
              { value: 'etalonnage', title: 'Étalonnage' }
            ]"
            label="Type *"
            variant="outlined"
          />

          <v-text-field
            v-model="form.date_prevue"
            class="mb-4"
            label="Date prévue *"
            type="date"
            variant="outlined"
          />

          <v-text-field
            v-model="form.responsable"
            class="mb-4"
            label="Responsable"
            variant="outlined"
          />

          <v-textarea
            v-model="form.description"
            label="Description"
            rows="3"
            variant="outlined"
          />

          <div class="d-flex justify-end gap-2 mt-4">
            <v-btn variant="outlined" @click="close">Annuler</v-btn>
            <v-btn color="primary" :loading="loading" type="submit">Planifier</v-btn>
          </div>
        </v-form>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import { computed, ref, watch } from 'vue'
  import { useSupportStore } from '@/stores/supportStore'

  const props = defineProps<{ modelValue: boolean }>()
  const emit = defineEmits<{ 'update:modelValue': [value: boolean], 'added': [] }>()

  const store = useSupportStore()
  const isOpen = ref(props.modelValue)
  const loading = ref(false)

  watch(() => props.modelValue, val => {
    isOpen.value = val
  })
  watch(isOpen, val => {
    emit('update:modelValue', val)
  })

  const form = ref({
    equipement_id: null,
    type: 'preventive',
    date_prevue: '',
    responsable: '',
    description: '',
  })

  const equipements = computed(() => store.equipementsActifs)

  function close () {
    isOpen.value = false
    form.value = { equipement_id: null, type: 'preventive', date_prevue: '', responsable: '', description: '' }
  }

  async function handleSubmit () {
    loading.value = true
    try {
      await store.createMaintenance(form.value as any)
      emit('added')
      close()
    } catch (error) {
      console.error(error)
    } finally {
      loading.value = false
    }
  }
</script>
