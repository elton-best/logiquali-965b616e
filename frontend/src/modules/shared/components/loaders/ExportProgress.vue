<template>
  <v-dialog max-width="500" :model-value="props.show" persistent @update:model-value="onUpdateShow">
    <v-card>
      <v-card-title>{{ title }}</v-card-title>
      <v-card-text>
        <v-progress-linear
          color="primary"
          height="25"
          :indeterminate="indeterminate"
          :model-value="progress"
        >
          <template #default="{ value }">
            <strong>{{ Math.ceil(value) }}%</strong>
          </template>
        </v-progress-linear>
        <p class="text-center mt-4">{{ message }}</p>
      </v-card-text>
      <v-card-actions v-if="cancellable">
        <v-spacer />
        <v-btn @click="$emit('cancel')">Annuler</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  interface Props {
    show: boolean
    progress?: number
    title?: string
    message?: string
    indeterminate?: boolean
    cancellable?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    progress: 0,
    title: 'Export en cours',
    message: 'Veuillez patienter...',
    indeterminate: false,
    cancellable: false,
  })

  const emit = defineEmits<{
    'cancel': []
    'update:show': [value: boolean]
  }>()

  function onUpdateShow (value: boolean) {
    emit('update:show', value)
  }
</script>
