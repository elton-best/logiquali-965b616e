<template>
  <v-card>
    <v-card-title>Informations légales</v-card-title>
    <v-card-text>
      <v-form ref="formRef" @submit.prevent="handleSubmit">
        <v-select
          v-model="selectedCountry"
          :disabled="loading"
          item-title="name"
          item-value="code"
          :items="countries"
          label="Pays"
          @update:model-value="onCountryChange"
        />

        <v-divider class="my-4" />

        <div v-if="selectedCountry === 'BJ'">
          <h3 class="text-subtitle-1 mb-3">Informations légales - Bénin</h3>
          <v-text-field v-model="form.rccm_number" :disabled="loading" label="RCCM" />
          <v-text-field v-model="form.ifu_number" :disabled="loading" label="IFU" />
          <v-text-field v-model="form.cnss_number" :disabled="loading" label="CNSS" />
          <v-text-field v-model="form.fodefca_number" :disabled="loading" label="FODEFCA" />
        </div>

        <div v-else-if="selectedCountry === 'FR'">
          <h3 class="text-subtitle-1 mb-3">Informations légales - France</h3>
          <v-text-field v-model="form.siret" :disabled="loading" label="SIRET" />
          <v-text-field v-model="form.rcs" :disabled="loading" label="RCS" />
          <v-text-field v-model="form.vat_number" :disabled="loading" label="TVA Intracommunautaire" />
          <v-text-field v-model="form.ape_code" :disabled="loading" label="Code APE" />
        </div>

        <div v-else>
          <h3 class="text-subtitle-1 mb-3">Informations légales - Général</h3>
          <v-text-field v-model="form.trade_register_number" :disabled="loading" label="Registre du commerce" />
          <v-text-field v-model="form.tax_id" :disabled="loading" label="Numéro fiscal" />
        </div>

        <v-divider class="my-4" />

        <v-text-field v-model="form.legal_form" :disabled="loading" label="Forme juridique" placeholder="SARL, SAS, SA..." />
        <v-text-field v-model="form.share_capital" :disabled="loading" label="Capital social" placeholder="50 000 €" />

        <v-btn class="mt-4" color="primary" :loading="loading" type="submit">
          Enregistrer
        </v-btn>
      </v-form>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import type { EnterpriseLegalInfo } from '@/types/enterprise-config'
  import { reactive, ref, watch } from 'vue'
  import { useSnackbar } from '@/composables/useSnackbar'
  import { enterpriseConfigService } from '@/services/enterpriseConfigService'

  interface Props {
    enterpriseId: number
    initialData?: EnterpriseLegalInfo
    currentCountry?: string
  }

  const props = defineProps<Props>()
  const emit = defineEmits<{ saved: [data: EnterpriseLegalInfo] }>()

  const { showSuccess, showError } = useSnackbar()
  const loading = ref(false)
  const formRef = ref()
  const selectedCountry = ref('BJ')

  const form = reactive<EnterpriseLegalInfo>({
    rccm_number: '',
    ifu_number: '',
    cnss_number: '',
    fodefca_number: '',
    siret: '',
    rcs: '',
    vat_number: '',
    ape_code: '',
    legal_form: '',
    share_capital: '',
    trade_register_number: '',
    tax_id: '',
  })

  const countries = [
    { code: 'BJ', name: 'Bénin' },
    { code: 'FR', name: 'France' },
    { code: 'SN', name: 'Sénégal' },
    { code: 'CI', name: 'Côte d\'Ivoire' },
    { code: 'TG', name: 'Togo' },
    { code: 'MA', name: 'Maroc' },
    { code: 'TN', name: 'Tunisie' },
    { code: 'DZ', name: 'Algérie' },
    { code: 'CM', name: 'Cameroun' },
    { code: 'GA', name: 'Gabon' },
    { code: 'OTHER', name: 'Autre' },
  ]

  function onCountryChange () {
  // Reset fields when country changes
  }

  function normalizeCountryCode (value?: string) {
    if (!value) return 'BJ'

    const normalized = value.trim().toUpperCase()
    if (normalized === 'BENIN' || normalized === 'BÉNIN') return 'BJ'
    if (normalized === 'FRANCE') return 'FR'
    if (normalized.length === 2) return normalized
    return 'BJ'
  }

  function resolveCountryFromData (data?: EnterpriseLegalInfo) {
    const payload = data as any
    if (payload?.country) {
      return normalizeCountryCode(payload.country)
    }
    if (payload?.rccm_number || payload?.ifu_number || payload?.cnss_number || payload?.fodefca_number) {
      return 'BJ'
    }
    return normalizeCountryCode(props.currentCountry)
  }

  watch(
    () => props.initialData,
    value => {
      Object.assign(form, value || {})
      selectedCountry.value = resolveCountryFromData(value || undefined)
    },
    { immediate: true, deep: true },
  )

  watch(
    () => props.currentCountry,
    value => {
      if (value) {
        selectedCountry.value = normalizeCountryCode(value)
      }
    },
    { immediate: true },
  )

  async function handleSubmit () {
    loading.value = true
    try {
      const data = await enterpriseConfigService.updateLegalInfo(props.enterpriseId, {
        ...form,
        country: selectedCountry.value,
      } as any)
      showSuccess('Informations légales mises à jour')
      emit('saved', data)
    } catch {
      showError('Erreur lors de la mise à jour')
    } finally {
      loading.value = false
    }
  }
</script>
