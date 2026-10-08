<template>
  <v-dialog v-model="isOpen" max-width="600" persistent>
    <v-card>
      <!-- Header -->
      <v-card-title class="d-flex align-center justify-space-between">
        <span>Enregistrer un transfert d'équipement</span>
        <button class="btn-close" :disabled="loading" @click="onClose">
          <v-icon>mdi-close</v-icon>
        </button>
      </v-card-title>

      <!-- Content -->
      <v-card-text>
        <div class="mb-4">
          <v-alert color="info" icon="mdi-information" variant="tonal">
            <strong>{{ equipement?.nom_commun }}</strong> <br>
            Code actuel:
            <span class="font-monospace">{{ equipement?.code_complet }}</span>
          </v-alert>
        </div>

        <!-- Selection fields -->
        <v-row>
          <v-col cols="12">
            <v-select
              v-model="formData.newSiteId"
              density="comfortable"
              :disabled="loading"
              item-title="name"
              item-value="id"
              :items="availableSites"
              label="Nouveau site"
              prepend-inner-icon="mdi-map-marker"
              variant="outlined"
            />
          </v-col>

          <v-col cols="12">
            <v-select
              v-model="formData.newLocalisationId"
              density="comfortable"
              :disabled="loading || !formData.newSiteId"
              item-title="libelle"
              item-value="id"
              :items="availableLocalisations"
              label="Nouvelle localisation"
              prepend-inner-icon="mdi-folder"
              variant="outlined"
            />
          </v-col>

          <v-col cols="12">
            <v-select
              v-model="formData.reasonCode"
              density="comfortable"
              :disabled="loading"
              item-title="name"
              item-value="code"
              :items="transferReasons"
              label="Raison du transfert"
              prepend-inner-icon="mdi-comment-question"
              variant="outlined"
            />
            <v-alert
              v-if="!loading && transferReasons.length === 0"
              class="mt-2"
              color="warning"
              variant="tonal"
            >
              Aucun motif de transfert n'est configuré pour votre entreprise.
            </v-alert>
            <p
              v-if="selectedReason"
              class="text-caption text-medium-emphasis mt-2 mb-0"
            >
              {{ selectedReason.description }}
            </p>
          </v-col>

          <v-col cols="12">
            <v-textarea
              v-model="formData.notes"
              clearable
              density="comfortable"
              :disabled="loading"
              hint="Observations supplémentaires (optionnel)"
              label="Notes"
              max-rows="3"
              rows="2"
              variant="outlined"
            />
          </v-col>
        </v-row>

        <!-- Error display -->
        <v-alert
          v-if="errorMessage"
          class="mt-4"
          color="error"
          icon="mdi-alert-circle"
          variant="tonal"
        >
          {{ errorMessage }}
        </v-alert>
      </v-card-text>

      <!-- Actions -->
      <v-card-actions class="d-flex justify-end gap-2">
        <v-btn :disabled="loading" variant="text" @click="onClose">
          Annuler
        </v-btn>

        <v-btn
          color="primary"
          :disabled="!isFormValid || loading"
          :loading="loading"
          @click="onSubmit"
        >
          Enregistrer le transfert
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import type {
    CodificationElement,
    Equipement,
  } from '@/services/supportService'
  import { computed, onMounted, ref, watch } from 'vue'
  import api from '@/api/client'
  import { useToast } from '@/modules/shared/composables/useToast'
  import {
    equipmentTransferService,
    type TransferReasonCode,
  } from '@/services/equipmentTransferService'

  type SiteOption = {
    id: number
    name: string
  }

  type SiteApiItem = {
    id?: number | string
    name?: string
    nom?: string
    attributes?: {
      name?: string
      nom?: string
    }
  }

  type SitesPayload = {
    data?: unknown
    sites?: unknown
  }

  type HttpLikeError = {
    message?: string
    response?: {
      data?: {
        message?: string
        errors?: Record<string, string[]>
      }
    }
  }

  const props = defineProps<{
    modelValue: boolean
    equipement: Equipement | null
    enterpriseId: number
    availableLocalisations: CodificationElement[]
  }>()

  const emit = defineEmits<{
    (event: 'update:modelValue', value: boolean): void
    (
      event: 'success',
      payload: { equipement_id: number, transfer_id: number },
    ): void
  }>()

  const toast = useToast()

  const isOpen = computed({
    get: () => props.modelValue,
    set: value => emit('update:modelValue', value),
  })

  const loading = ref(false)
  const errorMessage = ref('')
  const transferReasons = ref<TransferReasonCode[]>([])
  const availableSites = ref<SiteOption[]>([])

  const formData = ref({
    newSiteId: null as number | null,
    newLocalisationId: null as number | null,
    reasonCode: '' as string,
    notes: '' as string,
  })

  const selectedReason = computed(() => {
    return transferReasons.value.find(
      r => r.code === formData.value.reasonCode,
    )
  })

  const isFormValid = computed(() => {
    return (
      formData.value.newSiteId
      && formData.value.newLocalisationId
      && formData.value.reasonCode
    )
  })

  onMounted(() => {
    if (isOpen.value && props.enterpriseId > 0) {
      void loadSites()
      void loadTransferReasons()
    }
  })

  watch(isOpen, open => {
    if (open && props.enterpriseId > 0) {
      void loadSites()
      void loadTransferReasons()
    }
  })

  async function loadSites () {
    try {
      const response = await api.get('/sites', { params: { per_page: 200 } })
      const payload = response.data as unknown
      const objectPayload = (
        payload && typeof payload === 'object' ? payload : {}
      ) as SitesPayload

      let items: unknown[] = []
      if (Array.isArray(payload)) {
        items = payload
      } else if (Array.isArray(objectPayload.data)) {
        items = objectPayload.data
      } else if (Array.isArray(objectPayload.sites)) {
        items = objectPayload.sites
      }

      availableSites.value = items
        .map(item => {
          const site = item as SiteApiItem
          const rawName
            = site.name
              || site.nom
              || site.attributes?.name
              || site.attributes?.nom
              || ''

          return {
            id: Number(site.id),
            name: String(rawName),
          }
        })
        .filter(
          (item: SiteOption) =>
            Number.isFinite(item.id) && item.id > 0 && item.name,
        )

      if (availableSites.value.length === 0) {
        toast.warning('Aucun site disponible pour ce compte.')
      }
    } catch {
      toast.error('Impossible de charger la liste des sites.', 'Erreur')
    }
  }

  async function loadTransferReasons () {
    try {
      const reasons = await equipmentTransferService.getTransferReasons(
        props.enterpriseId,
      )
      transferReasons.value = reasons

      if (transferReasons.value.length === 0) {
        toast.warning(
          'Aucun motif de transfert configuré pour cette entreprise.',
        )
      }
    } catch {
      toast.error('Impossible de charger les motifs de transfert.', 'Erreur')
    }
  }

  function resetForm () {
    formData.value = {
      newSiteId: null,
      newLocalisationId: null,
      reasonCode: '',
      notes: '',
    }
    errorMessage.value = ''
  }

  function onClose () {
    isOpen.value = false
    resetForm()
  }

  async function onSubmit () {
    if (!props.equipement || !isFormValid.value) return

    loading.value = true
    try {
      const result = await equipmentTransferService.registerTransfer(
        props.equipement.id,
        formData.value.newSiteId!,
        formData.value.newLocalisationId!,
        formData.value.reasonCode,
        formData.value.notes || undefined,
      )

      toast.success(
        `Transfert enregistré pour ${props.equipement.nom_commun}`,
        'Succès',
        3500,
      )

      emit('success', {
        equipement_id: result.equipement_id,
        transfer_id: result.transfer_id,
      })

      onClose()
    } catch (error: unknown) {
      const candidate = error as HttpLikeError
      const firstErrorKey = Object.keys(
        candidate?.response?.data?.errors || {},
      )[0]
      const firstValidationMessage = firstErrorKey
        ? candidate?.response?.data?.errors?.[firstErrorKey]?.[0]
        : undefined
      const message
        = candidate?.response?.data?.message
          || firstValidationMessage
          || candidate?.message
          || 'Erreur lors de l\'enregistrement'

      errorMessage.value = String(message)
      toast.error(String(message), 'Erreur')
    } finally {
      loading.value = false
    }
  }
</script>

<style scoped lang="scss">
.btn-close {
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;

  &:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  &:hover:not(:disabled) {
    background-color: rgba(0, 0, 0, 0.04);
    border-radius: 4px;
  }
}

.font-monospace {
  font-family: "Courier New", monospace;
  font-size: 0.85em;
}
</style>
