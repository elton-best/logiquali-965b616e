<template>
  <v-form ref="formRef" v-model="valid" @submit.prevent="emit('next')">
    <v-card flat>
      <v-card-text>
        <h3 class="text-h6 mb-4">Informations de l'entreprise</h3>
        <p class="text-body-2 text-medium-emphasis mb-6">
          Veuillez fournir les informations légales de votre entreprise et charger les documents requis
        </p>

        <v-text-field
          v-model="companyName"
          class="mb-2"
          label="Nom de l'entreprise"
          prepend-inner-icon="mdi-office-building"
          required
          :rules="[rules.required]"
          variant="outlined"
        />

        <v-select
          v-model="legalForm"
          class="mb-2"
          :items="legalForms"
          label="Forme juridique"
          prepend-inner-icon="mdi-domain"
          required
          :rules="[rules.required]"
          variant="outlined"
        />

        <!-- RCCM avec upload -->
        <v-text-field
          v-model="rccm"
          class="mb-2"
          hint="Registre de Commerce et du Crédit Mobilier"
          label="Numéro RCCM"
          persistent-hint
          prepend-inner-icon="mdi-file-document"
          required
          :rules="[rules.required]"
          variant="outlined"
        />

        <v-file-input
          v-model="rccmFile"
          accept="image/*,.pdf"
          class="mb-4"
          hint="Formats acceptés: PDF, JPG, PNG (Max 5MB)"
          label="Document RCCM"
          persistent-hint
          prepend-icon=""
          prepend-inner-icon="mdi-file-upload"
          required
          :rules="[rules.requiredFile]"
          show-size
          variant="outlined"
          @update:model-value="handleRccmUpload"
        >
          <template #append>
            <v-icon v-if="modelValue.rccmDocument" color="success">mdi-check-circle</v-icon>
          </template>
        </v-file-input>

        <!-- IFU avec upload -->
        <v-text-field
          v-model="ifu"
          class="mb-2"
          hint="Identifiant Fiscal Unique"
          label="Numéro IFU"
          persistent-hint
          prepend-inner-icon="mdi-file-certificate"
          required
          :rules="[rules.required]"
          variant="outlined"
        />

        <v-file-input
          v-model="ifuFile"
          accept="image/*,.pdf"
          class="mb-2"
          hint="Formats acceptés: PDF, JPG, PNG (Max 5MB)"
          label="Document IFU"
          persistent-hint
          prepend-icon=""
          prepend-inner-icon="mdi-file-upload"
          required
          :rules="[rules.requiredFile]"
          show-size
          variant="outlined"
          @update:model-value="handleIfuUpload"
        >
          <template #append>
            <v-icon v-if="modelValue.ifuDocument" color="success">mdi-check-circle</v-icon>
          </template>
        </v-file-input>
      </v-card-text>
    </v-card>
  </v-form>
</template>

<script setup lang="ts">
  import type { CompanyInfo } from '@/types/kyc'
  import { computed, ref } from 'vue'

  const props = defineProps<{
    modelValue: CompanyInfo
  }>()

  const emit = defineEmits<{
    'update:modelValue': [value: CompanyInfo]
    'next': []
  }>()

  const formRef = ref()
  const valid = ref(false)
  const rccmFile = ref<File | File[] | null>(null)
  const ifuFile = ref<File | File[] | null>(null)

  const companyName = computed({
    get: () => props.modelValue.companyName,
    set: value => emit('update:modelValue', { ...props.modelValue, companyName: value }),
  })

  const legalForm = computed({
    get: () => props.modelValue.legalForm,
    set: value => emit('update:modelValue', { ...props.modelValue, legalForm: value }),
  })

  const rccm = computed({
    get: () => props.modelValue.rccm,
    set: value => emit('update:modelValue', { ...props.modelValue, rccm: value }),
  })

  const ifu = computed({
    get: () => props.modelValue.ifu,
    set: value => emit('update:modelValue', { ...props.modelValue, ifu: value }),
  })

  const legalForms = [
    'SARL - Société à Responsabilité Limitée',
    'SA - Société Anonyme',
    'SAS - Société par Actions Simplifiée',
    'SASU - Société par Actions Simplifiée Unipersonnelle',
    'EURL - Entreprise Unipersonnelle à Responsabilité Limitée',
    'SNC - Société en Nom Collectif',
    'SCS - Société en Commandite Simple',
    'Entreprise Individuelle',
    'Autre',
  ]

  const rules = {
    required: (value: string) => !!value || 'Ce champ est requis',
    requiredFile: (value: File | File[] | null) => {
      if (!value) return 'Veuillez charger le document'
      if (Array.isArray(value)) return value.length > 0 || 'Veuillez charger le document'
      return true
    },
  }

  function handleRccmUpload (files: File | File[] | null) {
    if (!files) return
    const file = Array.isArray(files) ? files[0] : files
    if (!file) return
    emit('update:modelValue', { ...props.modelValue, rccmDocument: file })
  }

  function handleIfuUpload (files: File | File[] | null) {
    if (!files) return
    const file = Array.isArray(files) ? files[0] : files
    if (!file) return
    emit('update:modelValue', { ...props.modelValue, ifuDocument: file })
  }

  defineExpose({
    validate: () => formRef.value?.validate(),
    isValid: () => valid.value,
  })
</script>
