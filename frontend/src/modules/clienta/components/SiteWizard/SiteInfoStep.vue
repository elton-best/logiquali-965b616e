<template>
  <div class="form-section">
    <div class="form-grid">
      <div class="form-field">
        <label class="field-label required">Nom du site</label>
        <input
          v-model="modelValue.name"
          class="field-input"
          placeholder="Ex: Site de production Cotonou"
          type="text"
        >
      </div>

      <div class="form-field full-width">
        <label class="field-label required">Adresse complète</label>
        <textarea
          v-model="modelValue.address"
          class="field-textarea"
          placeholder="Numéro, rue, bâtiment"
          rows="2"
        />
      </div>

      <div class="form-field">
        <label class="field-label required">Pays</label>
        <select v-model="modelValue.country" class="field-input">
          <option value="">Sélectionner un pays</option>
          <option v-for="country in countries" :key="country.code" :value="country.code">
            {{ country.name }}
          </option>
        </select>
      </div>

      <div class="form-field">
        <label class="field-label required">Ville</label>
        <select v-model="modelValue.city" class="field-input" :disabled="!modelValue.country">
          <option value="">
            {{ modelValue.country ? 'Sélectionner une ville' : 'Sélectionnez d\'abord un pays' }}
          </option>
          <option v-for="city in cities" :key="city.name" :value="city.name">
            {{ city.name }}
          </option>
        </select>
      </div>

      <div class="form-field">
        <label class="field-label">Téléphone</label>
        <input
          v-model="modelValue.phone"
          class="field-input"
          placeholder="+229 01 02 03 04 05"
          type="tel"
        >
      </div>

      <div class="form-field">
        <label class="field-label">Email</label>
        <input
          v-model="modelValue.email"
          class="field-input"
          placeholder="site@entreprise.com"
          type="email"
        >
      </div>

      <div class="form-field">
        <label class="field-label">Type de site</label>
        <label class="check-row">
          <input v-model="modelValue.is_headquarter" type="checkbox">
          <span>Siège social</span>
        </label>
      </div>

      <div class="form-field">
        <label class="field-label">Statut</label>
        <label class="check-row">
          <input v-model="modelValue.is_active" type="checkbox">
          <span>Site actif</span>
        </label>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref, watch } from 'vue'
  import { COUNTRY_OPTIONS } from '@/constants/countries'
  import { geoCatalogService, normalizeCountryCode } from '@/services/geoCatalogService'

  interface SiteInfo {
    name: string
    address: string
    country: string
    city: string
    phone?: string
    email?: string
    is_headquarter: boolean
    is_active: boolean
  }

  interface Props {
    modelValue: SiteInfo
  }

  const props = defineProps<Props>()
  const emit = defineEmits<{
    (e: 'update:modelValue', value: SiteInfo): void
  }>()

  const modelValue = computed({
    get: () => props.modelValue,
    set: value => emit('update:modelValue', value),
  })

  const countries = ref(
    COUNTRY_OPTIONS.map(country => ({
      code: country.code,
      name: country.name,
    })),
  )
  const cities = ref<Array<{ name: string }>>([])

  watch(() => modelValue.value.country, async (newCountryCode, oldCountryCode) => {
    const normalized = normalizeCountryCode(String(newCountryCode || ''))
    const previous = normalizeCountryCode(String(oldCountryCode || ''))

    if (!normalized) {
      cities.value = []
      modelValue.value.city = ''
      return
    }

    if (normalized !== previous) {
      modelValue.value.city = ''
    }

    cities.value = await geoCatalogService.getCitiesByCountry(normalized)
  }, { immediate: true })

  onMounted(async () => {
    const remoteCountries = await geoCatalogService.getCountries()
    if (remoteCountries.length > 0) {
      countries.value = remoteCountries.map(country => ({
        code: country.code,
        name: country.name,
      }))
    }
  })

</script>

<style scoped>
.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.form-field {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-field.full-width {
  grid-column: span 2;
}

.field-label {
  font-size: 0.85rem;
  font-weight: 700;
  color: #334155;
}

.field-label.required::after {
  content: " *";
  color: #ef4444;
}

.field-input,
.field-textarea {
  width: 100%;
  border: 1px solid #d6dee8;
  border-radius: 10px;
  padding: 10px 12px;
  font-size: 0.92rem;
  background: #fff;
  color: #0f172a;
}

.field-input:disabled {
  background: #f8fafc;
  color: #94a3b8;
}

.field-input:focus,
.field-textarea:focus {
  outline: none;
  border-color: #5b8dd9;
  box-shadow: 0 0 0 2px rgba(91, 141, 217, 0.2);
}

.check-row {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: #334155;
  font-weight: 600;
}

@media (max-width: 900px) {
  .form-grid {
    grid-template-columns: 1fr;
  }

  .form-field.full-width {
    grid-column: auto;
  }
}
</style>
