<template>
  <v-card>
    <v-card-title>Identité de marque</v-card-title>
    <v-card-text>
      <v-form ref="formRef" @submit.prevent="handleSubmit">
        <v-text-field
          v-model="form.slogan"
          :disabled="loading"
          label="Slogan"
          placeholder="Beyond your Goals…"
        />

        <v-divider class="my-4" />

        <h3 class="text-subtitle-1 mb-3">Couleurs de marque</h3>

        <v-row>
          <v-col cols="12" md="4">
            <v-text-field
              v-model="form.brand_colors.primary"
              :disabled="loading"
              label="Couleur primaire"
              type="color"
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-text-field
              v-model="form.brand_colors.secondary"
              :disabled="loading"
              label="Couleur secondaire"
              type="color"
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-text-field
              v-model="form.brand_colors.accent"
              :disabled="loading"
              label="Couleur d'accent"
              type="color"
            />
          </v-col>
        </v-row>

        <v-select
          v-model="form.header_font"
          :disabled="loading"
          :items="fontOptions"
          label="Police d'en-tête"
        />

        <v-btn class="mt-4" color="primary" :loading="loading" type="submit">
          Enregistrer
        </v-btn>
      </v-form>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import type { BrandColors, EnterpriseBranding } from '@/types/enterprise-config'
  import { reactive, ref, watch } from 'vue'
  import { useSnackbar } from '@/composables/useSnackbar'
  import { enterpriseConfigService } from '@/services/enterpriseConfigService'

  interface Props {
    enterpriseId: number
    initialData?: EnterpriseBranding
  }

  const props = defineProps<Props>()
  const emit = defineEmits<{ saved: [data: EnterpriseBranding] }>()

  const { showSuccess, showError } = useSnackbar()
  const loading = ref(false)
  const formRef = ref()

  interface BrandingForm {
    slogan: string
    brand_colors: Required<BrandColors>
    header_font: string
  }

  const form = reactive<BrandingForm>({
    slogan: props.initialData?.slogan || '',
    brand_colors: {
      primary: props.initialData?.brand_colors?.primary || '#1B5E96',
      secondary: props.initialData?.brand_colors?.secondary || '#FF6B00',
      accent: props.initialData?.brand_colors?.accent || '#4CAF50',
    },
    header_font: props.initialData?.header_font || 'Arial',
  })

  const fontOptions = ['Arial', 'Helvetica', 'Times New Roman', 'Courier', 'Verdana']

  watch(
    () => props.initialData,
    value => {
      if (!value) {
        return
      }

      form.slogan = value.slogan || ''
      form.brand_colors.primary = value.brand_colors?.primary || '#1B5E96'
      form.brand_colors.secondary = value.brand_colors?.secondary || '#FF6B00'
      form.brand_colors.accent = value.brand_colors?.accent || '#4CAF50'
      form.header_font = value.header_font || 'Arial'
    },
    { immediate: true, deep: true },
  )

  async function handleSubmit () {
    loading.value = true
    try {
      const data = await enterpriseConfigService.updateBranding(props.enterpriseId, form as EnterpriseBranding)
      showSuccess('Branding mis à jour avec succès')
      emit('saved', data)
    } catch {
      showError('Erreur lors de la mise à jour du branding')
    } finally {
      loading.value = false
    }
  }
</script>
