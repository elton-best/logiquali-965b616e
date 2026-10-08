<template>
  <v-form ref="formRef" v-model="valid" @submit.prevent="emit('next')">
    <v-card flat>
      <v-card-text>
        <h3 class="text-h6 mb-4">Siège social</h3>
        <p class="text-body-2 text-medium-emphasis mb-6">
          Adresse du siège principal de votre entreprise
        </p>

        <v-textarea
          v-model="address"
          class="mb-2"
          label="Adresse complète"
          prepend-inner-icon="mdi-map-marker"
          required
          rows="3"
          :rules="[rules.required]"
          variant="outlined"
        />

        <v-row>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="city"
              label="Ville"
              prepend-inner-icon="mdi-city"
              required
              :rules="[rules.required]"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="postalCode"
              label="Code postal (optionnel)"
              prepend-inner-icon="mdi-mailbox"
              variant="outlined"
            />
          </v-col>
        </v-row>

        <v-select
          v-model="country"
          class="mb-2"
          :items="countries"
          label="Pays"
          prepend-inner-icon="mdi-earth"
          required
          :rules="[rules.required]"
          variant="outlined"
        />

        <v-text-field
          v-model="gpsCoordinates"
          hint="Format: Latitude, Longitude"
          label="Coordonnées GPS (optionnel)"
          persistent-hint
          placeholder="6.3703° N, 2.3912° E"
          prepend-inner-icon="mdi-crosshairs-gps"
          variant="outlined"
        />
      </v-card-text>
    </v-card>
  </v-form>
</template>

<script setup lang="ts">
  import type { MainSite } from '@/types/kyc'
  import { computed, ref } from 'vue'

  const props = defineProps<{
    modelValue: MainSite
  }>()

  const emit = defineEmits<{
    'update:modelValue': [value: MainSite]
    'next': []
  }>()

  const address = computed({
    get: () => props.modelValue.address,
    set: value => emit('update:modelValue', { ...props.modelValue, address: value }),
  })

  const city = computed({
    get: () => props.modelValue.city,
    set: value => emit('update:modelValue', { ...props.modelValue, city: value }),
  })

  const country = computed({
    get: () => props.modelValue.country,
    set: value => emit('update:modelValue', { ...props.modelValue, country: value }),
  })

  const gpsCoordinates = computed({
    get: () => props.modelValue.gpsCoordinates,
    set: value => emit('update:modelValue', { ...props.modelValue, gpsCoordinates: value }),
  })

  const postalCode = computed({
    get: () => props.modelValue.postalCode,
    set: value => emit('update:modelValue', { ...props.modelValue, postalCode: value }),
  })

  const formRef = ref()
  const valid = ref(false)

  const countries = [
    'Bénin',
    'Burkina Faso',
    'Côte d\'Ivoire',
    'Guinée-Bissau',
    'Mali',
    'Niger',
    'Sénégal',
    'Togo',
    'France',
    'Belgique',
    'Suisse',
    'Canada',
    'Autre',
  ]

  const rules = {
    required: (value: string) => !!value || 'Ce champ est requis',
  }

  defineExpose({
    validate: () => formRef.value?.validate(),
    isValid: () => valid.value,
  })
</script>
