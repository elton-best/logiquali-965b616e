<template>
  <v-dialog v-model="dialogModel" max-width="760" persistent>
    <v-card rounded="lg">
      <v-card-title class="d-flex align-center justify-space-between pa-6 bg-primary">
        <span class="text-h6 text-white">Nouveau volet / aspect</span>
        <v-btn color="white" icon="mdi-close" variant="text" @click="handleClose" />
      </v-card-title>
      <v-card-text class="pa-6">
        <v-row>
          <v-col cols="12">
            <v-text-field
              v-model="localAspectForm.name"
              density="comfortable"
              label="Nom du volet/aspect *"
              rounded="lg"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12">
            <v-textarea
              v-model="localAspectForm.description"
              density="comfortable"
              label="Description"
              rounded="lg"
              rows="2"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12">
            <v-select
              v-model="localAspectForm.norm_ids"
              chips
              closable-chips
              density="comfortable"
              item-title="title"
              item-value="value"
              :items="normOptions"
              label="Norme(s) liée(s)"
              multiple
              rounded="lg"
              variant="outlined"
            />
          </v-col>
        </v-row>
      </v-card-text>
      <v-divider />
      <v-card-actions class="pa-4">
        <v-spacer />
        <v-btn variant="text" @click="handleClose">Annuler</v-btn>
        <v-btn color="primary" :loading="saving" variant="elevated" @click="emit('save')">
          Enregistrer
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import { computed, ref, watch } from 'vue'

  const props = defineProps({
    modelValue: {
      type: Boolean,
      default: false,
    },
    aspectForm: {
      type: Object as PropType<Record<string, any>>,
      required: true,
    },
    normOptions: {
      type: Array as PropType<Array<{ title: string, value: number }>>,
      required: true,
    },
    saving: {
      type: Boolean,
      default: false,
    },
  })

  const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'update:aspectForm', value: Record<string, any>): void
    (e: 'save'): void
    (e: 'close'): void
  }>()

  const localAspectForm = ref<Record<string, any>>({ ...props.aspectForm })

  watch(() => props.aspectForm, newForm => {
    localAspectForm.value = { ...newForm }
  }, { deep: true })

  watch(localAspectForm, newForm => {
    emit('update:aspectForm', { ...newForm })
  }, { deep: true })

  const dialogModel = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit('update:modelValue', value),
  })

  function handleClose () {
    emit('update:modelValue', false)
    emit('close')
  }
</script>
