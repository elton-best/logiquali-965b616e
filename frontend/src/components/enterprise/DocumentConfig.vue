<template>
  <v-card>
    <v-card-title>Configuration des documents</v-card-title>
    <v-card-text>
      <v-form @submit.prevent="handleSubmit">
        <h3 class="text-subtitle-1 mb-3">En-tête</h3>
        <v-row>
          <v-col cols="12" md="4">
            <v-switch v-model="form.header.show_logo" label="Afficher le logo" />
          </v-col>
          <v-col cols="12" md="4">
            <v-switch v-model="form.header.show_slogan" label="Afficher le slogan" />
          </v-col>
          <v-col cols="12" md="4">
            <v-switch v-model="form.header.show_certifications" label="Afficher les certifications" />
          </v-col>
        </v-row>

        <v-divider class="my-4" />

        <h3 class="text-subtitle-1 mb-3">Pied de page</h3>
        <v-row>
          <v-col cols="12" md="4">
            <v-switch v-model="form.footer.show_contact" label="Afficher les contacts" />
          </v-col>
          <v-col cols="12" md="4">
            <v-switch v-model="form.footer.show_legal_info" label="Afficher le légal" />
          </v-col>
          <v-col cols="12" md="4">
            <v-switch v-model="form.footer.show_page_numbers" label="Numérotation des pages" />
          </v-col>
          <v-col cols="12" md="4">
            <v-switch v-model="form.footer.show_qr_code" label="Afficher QR code (PDF)" />
          </v-col>
        </v-row>

        <v-text-field
          v-model="form.footer.custom_text"
          label="Texte personnalisé (optionnel)"
          placeholder="Ex: Document interne - diffusion contrôlée"
        />

        <v-btn color="primary" :loading="loading" type="submit">Enregistrer</v-btn>
      </v-form>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import type { HeaderFooterConfig } from '@/types/enterprise-config'
  import { reactive, ref, watch } from 'vue'
  import { useSnackbar } from '@/composables/useSnackbar'
  import { enterpriseConfigService } from '@/services/enterpriseConfigService'

  interface Props {
    enterpriseId: number
    initialData?: HeaderFooterConfig
  }

  const props = defineProps<Props>()
  const emit = defineEmits<{ saved: [] }>()
  const { showSuccess, showError } = useSnackbar()
  const loading = ref(false)

  const form = reactive<HeaderFooterConfig>({
    header: {
      show_logo: props.initialData?.header?.show_logo ?? true,
      show_slogan: props.initialData?.header?.show_slogan ?? true,
      show_certifications: props.initialData?.header?.show_certifications ?? true,
      layout: props.initialData?.header?.layout ?? 'professional',
    },
    footer: {
      show_contact: props.initialData?.footer?.show_contact ?? true,
      show_social: props.initialData?.footer?.show_social ?? false,
      show_legal_info: props.initialData?.footer?.show_legal_info ?? true,
      show_page_numbers: props.initialData?.footer?.show_page_numbers ?? true,
      show_qr_code: props.initialData?.footer?.show_qr_code ?? true,
      custom_text: props.initialData?.footer?.custom_text ?? '',
    },
    document_types: props.initialData?.document_types ?? {},
  })

  function applyHeaderConfig (value: HeaderFooterConfig) {
    form.header.show_logo = value.header?.show_logo ?? true
    form.header.show_slogan = value.header?.show_slogan ?? true
    form.header.show_certifications = value.header?.show_certifications ?? true
    form.header.layout = value.header?.layout ?? 'professional'
  }

  function applyFooterConfig (value: HeaderFooterConfig) {
    form.footer.show_contact = value.footer?.show_contact ?? true
    form.footer.show_social = value.footer?.show_social ?? false
    form.footer.show_legal_info = value.footer?.show_legal_info ?? true
    form.footer.show_page_numbers = value.footer?.show_page_numbers ?? true
    form.footer.show_qr_code = value.footer?.show_qr_code ?? true
    form.footer.custom_text = value.footer?.custom_text ?? ''
  }

  function applyInitialData (value: HeaderFooterConfig) {
    applyHeaderConfig(value)
    applyFooterConfig(value)
    form.document_types = value.document_types ?? {}
  }

  watch(
    () => props.initialData,
    value => {
      if (!value) {
        return
      }
      applyInitialData(value)
    },
    { immediate: true, deep: true },
  )

  async function handleSubmit () {
    loading.value = true
    try {
      await enterpriseConfigService.updateDocumentConfig(props.enterpriseId, { header_footer_config: form })
      showSuccess('Configuration des documents mise à jour')
      emit('saved')
    } catch {
      showError('Erreur lors de la mise à jour de la configuration des documents')
    } finally {
      loading.value = false
    }
  }
</script>
