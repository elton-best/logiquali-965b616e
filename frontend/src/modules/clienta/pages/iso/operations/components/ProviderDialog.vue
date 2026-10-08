<template>
  <v-dialog v-model="dialogValue" max-width="1200">
    <v-card>
      <v-card-title>{{ editingProvider ? 'Modifier prestataire' : 'Nouveau prestataire' }}</v-card-title>
      <v-card-text class="provider-form-shell">
        <v-row dense>
          <v-col cols="12" md="6">
            <v-text-field v-model="form.designation" label="Désignation / Nom prénom *" variant="outlined" />
          </v-col>
          <v-col cols="12" md="3">
            <v-select v-model="form.provider_type" :items="providerTypeItems" label="Type de prestataire" variant="outlined" />
          </v-col>
          <v-col cols="12" md="3">
            <v-text-field v-model="form.legal_form" label="Type d'entreprise / niveau" variant="outlined" />
          </v-col>
          <v-col cols="12" md="3">
            <v-text-field v-model="form.ifu" label="N° IFU" variant="outlined" />
          </v-col>
          <v-col cols="12" md="3">
            <v-text-field v-model.number="form.experience_years" label="Nombre d'années d'expérience" type="number" variant="outlined" />
          </v-col>
          <v-col cols="12" md="3">
            <v-text-field v-model="form.phone_primary" label="Contact téléphone 1" variant="outlined" />
          </v-col>
          <v-col cols="12" md="3">
            <v-text-field v-model="form.phone_secondary" label="Contact téléphone 2" variant="outlined" />
          </v-col>
          <v-col cols="12" md="4">
            <v-text-field v-model="form.email" label="Contact mail" type="email" variant="outlined" />
          </v-col>
          <v-col cols="12" md="8">
            <v-textarea v-model="form.service_offers" label="Offres / services" rows="2" variant="outlined" />
          </v-col>
          <v-col cols="12">
            <v-textarea v-model="form.evaluation_observation" label="Observation après évaluation" rows="2" variant="outlined" />
          </v-col>
        </v-row>
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" @click="emit('close')">Annuler</v-btn>
        <v-btn color="primary" @click="emit('save')">Enregistrer</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import type { ProviderPartner, ProviderPartnerPayload } from '@/api/services/providerPartners.service'
  import { computed } from 'vue'

  type ProviderTypeItem = {
    title: string
    value: string
  }

  const props = defineProps({
    modelValue: {
      type: Boolean,
      required: true,
    },
    editingProvider: {
      type: Object as PropType<ProviderPartner | null>,
      default: null,
    },
    form: {
      type: Object as PropType<ProviderPartnerPayload>,
      required: true,
    },
    providerTypeItems: {
      type: Array as PropType<ProviderTypeItem[]>,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'update:modelValue', value: boolean): void
    (event: 'save'): void
    (event: 'close'): void
  }>()

  const dialogValue = computed({
    get: () => props.modelValue,
    set: value => emit('update:modelValue', value),
  })
</script>
