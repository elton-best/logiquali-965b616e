<template>
  <v-form ref="formRef" v-model="valid" @submit.prevent="emit('next')">
    <v-card flat>
      <v-card-text>
        <h3 class="text-h6 mb-4">Identité du gérant</h3>
        <p class="text-body-2 text-medium-emphasis mb-6">
          Informations du responsable légal de l'entreprise
        </p>

        <v-row>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="firstName"
              label="Prénom"
              prepend-inner-icon="mdi-account"
              required
              :rules="[rules.required]"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="lastName"
              label="Nom"
              prepend-inner-icon="mdi-account"
              required
              :rules="[rules.required]"
              variant="outlined"
            />
          </v-col>
        </v-row>

        <v-select
          v-model="idType"
          class="mb-2"
          :items="idTypes"
          label="Type de pièce d'identité"
          prepend-inner-icon="mdi-card-account-details"
          required
          :rules="[rules.required]"
          variant="outlined"
        />

        <v-text-field
          v-model="idNumber"
          class="mb-4"
          label="Numéro de la pièce d'identité"
          prepend-inner-icon="mdi-numeric"
          required
          :rules="[rules.required]"
          variant="outlined"
        />

        <v-file-input
          v-model="documents"
          accept="image/*,application/pdf"
          chips
          counter
          hint="Formats acceptés: PDF, JPG, PNG (max 5MB par fichier)"
          label="Documents d'identité"
          multiple
          persistent-hint
          prepend-icon="mdi-paperclip"
          required
          :rules="[rules.requiredFiles]"
          show-size
          variant="outlined"
        >
          <template #selection="{ fileNames }">
            <v-chip
              v-for="fileName in fileNames"
              :key="fileName"
              class="me-2"
              color="primary"
              size="small"
            >
              {{ fileName }}
            </v-chip>
          </template>
        </v-file-input>
      </v-card-text>
    </v-card>
  </v-form>
</template>

<script setup lang="ts">
  import type { ManagerIdentity } from '@/types/kyc'
  import { computed, ref } from 'vue'

  const props = defineProps<{
    modelValue: ManagerIdentity
  }>()

  const emit = defineEmits<{
    'update:modelValue': [value: ManagerIdentity]
    'next': []
  }>()

  const documents = computed({
    get: () => props.modelValue.documents,
    set: value => emit('update:modelValue', { ...props.modelValue, documents: value }),
  })

  const firstName = computed({
    get: () => props.modelValue.firstName,
    set: value => emit('update:modelValue', { ...props.modelValue, firstName: value }),
  })

  const idNumber = computed({
    get: () => props.modelValue.idNumber,
    set: value => emit('update:modelValue', { ...props.modelValue, idNumber: value }),
  })

  const idType = computed({
    get: () => props.modelValue.idType,
    set: value => emit('update:modelValue', { ...props.modelValue, idType: value }),
  })

  const lastName = computed({
    get: () => props.modelValue.lastName,
    set: value => emit('update:modelValue', { ...props.modelValue, lastName: value }),
  })

  const formRef = ref()
  const valid = ref(false)

  const idTypes = [
    { title: 'CNI - Carte Nationale d\'Identité', value: 'cni' },
    { title: 'Passeport', value: 'passeport' },
    { title: 'Permis de conduire', value: 'permis' },
  ]

  const rules = {
    required: (value: string) => !!value || 'Ce champ est requis',
    requiredFiles: (value: File[]) => (value && value.length > 0) || 'Au moins un document est requis',
  }

  defineExpose({
    validate: () => formRef.value?.validate(),
    isValid: () => valid.value,
  })
</script>
