<template>
  <v-dialog max-width="800" :model-value="modelValue" persistent @update:model-value="$emit('update:modelValue', $event)">
    <v-card>
      <v-card-title class="bg-primary white--text">
        <v-icon start>mdi-flash</v-icon>
        Créer une action
        <v-spacer />
        <v-btn icon="mdi-close" variant="text" @click="close" />
      </v-card-title>
      <v-card-text class="pa-6">
        <v-row>
          <v-col cols="12"><v-text-field v-model="form.title" label="Titre *" variant="outlined" /></v-col>
          <v-col cols="6"><v-select v-model="form.type" :items="typeOptions" label="Type *" variant="outlined" /></v-col>
          <v-col cols="6"><v-select v-model="form.priority" :items="priorityOptions" label="Priorité *" variant="outlined" /></v-col>
          <v-col cols="12"><v-textarea v-model="form.description" label="Description" rows="3" variant="outlined" /></v-col>
          <v-col cols="6">
            <v-autocomplete
              v-model="form.responsible_id"
              item-title="name"
              item-value="id"
              :items="users"
              label="Responsable *"
              variant="outlined"
            />
          </v-col>
          <v-col cols="6"><v-text-field v-model="form.deadline" label="Échéance *" type="date" variant="outlined" /></v-col>
        </v-row>
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn @click="close">Annuler</v-btn>
        <v-btn color="primary" :loading="loading" @click="submit">Créer</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import { ref } from 'vue'
  import { actionService } from '@/services/actionService'

  defineProps<{
    modelValue: boolean
    users?: Array<{ id: number, name: string }>
  }>()
  const emit = defineEmits<{ 'update:modelValue': [v: boolean], 'created': [action: any] }>()

  const loading = ref(false)
  const form = ref({ title: '', type: 'corrective', priority: 'medium', description: '', responsible_id: null, deadline: '' })

  const typeOptions = [
    { title: 'Corrective', value: 'corrective' },
    { title: 'Préventive', value: 'preventive' },
    { title: 'Amélioration', value: 'improvement' },
  ]

  const priorityOptions = [
    { title: 'Critique', value: 'critical' },
    { title: 'Élevée', value: 'high' },
    { title: 'Moyenne', value: 'medium' },
    { title: 'Basse', value: 'low' },
  ]

  async function submit () {
    loading.value = true
    try {
      const action = await actionService.create(form.value as any)
      emit('created', action)
      close()
    } catch (error) {
      console.error(error)
    } finally {
      loading.value = false
    }
  }

  function close () {
    emit('update:modelValue', false)
    form.value = { title: '', type: 'corrective', priority: 'medium', description: '', responsible_id: null, deadline: '' }
  }
</script>
