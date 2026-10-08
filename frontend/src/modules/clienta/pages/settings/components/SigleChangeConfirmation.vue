<template>
  <v-dialog v-model="isOpen" max-width="700" persistent>
    <v-card>
      <!-- Header -->
      <v-card-title class="d-flex align-center justify-space-between">
        <span>Modifier le sigle de l'entreprise</span>
        <button class="btn-close" :disabled="loading" @click="onClose">
          <v-icon>mdi-close</v-icon>
        </button>
      </v-card-title>

      <!-- Content -->
      <v-card-text>
        <!-- Step 1: Input new sigle -->
        <div v-if="step === 'input'">
          <v-text-field
            v-model="newSigle"
            density="comfortable"
            :disabled="loading"
            hint="Alphanumeric, max 10 characters"
            label="Nouveau sigle"
            maxlength="10"
            prepend-inner-icon="mdi-tag"
            variant="outlined"
          />
          <v-text-field
            v-model="reason"
            class="mt-4"
            clearable
            density="comfortable"
            :disabled="loading"
            hint="Optionnel - raison du changement"
            label="Raison du changement"
            prepend-inner-icon="mdi-comment"
            variant="outlined"
          />
          <v-checkbox
            v-if="enterpriseMode === 'standard'"
            v-model="recodeEquipements"
            class="mt-4"
            color="warning"
            :disabled="loading"
            label="Recoder tous les équipements existants avec le nouveau sigle"
          />
        </div>

        <!-- Step 2: Preview results -->
        <div v-else-if="step === 'preview'">
          <div class="mb-6">
            <v-alert color="info" icon="mdi-information" variant="tonal">
              <strong>Impact de cette modification :</strong>
            </v-alert>
          </div>

          <div class="mb-4">
            <div class="text-caption text-medium-emphasis">Sigle actuel</div>
            <div class="text-h6 font-weight-medium">
              {{ previewData?.current_sigle || "(aucun)" }}
            </div>
          </div>

          <v-divider class="my-4" />

          <div class="mb-4">
            <div class="text-caption text-medium-emphasis">Nouveau sigle</div>
            <div class="text-h6 font-weight-medium">
              {{ previewData?.new_sigle }}
            </div>
          </div>

          <v-divider class="my-4" />

          <div class="mb-4">
            <div class="text-caption text-medium-emphasis">
              Équipements affectés
            </div>
            <div class="text-h6 font-weight-medium">
              {{ previewData?.equipements_affected ?? 0 }} équipements
            </div>
          </div>

          <!-- Warnings -->
          <v-alert
            v-for="(warning, idx) in previewData?.warnings"
            :key="idx"
            class="mt-4"
            color="warning"
            icon="mdi-alert-circle-outline"
            variant="tonal"
          >
            {{ warning }}
          </v-alert>

          <!-- Equipment sample -->
          <div v-if="previewData?.equipment_sample?.length" class="mt-6">
            <div class="text-caption text-medium-emphasis mb-2">
              Exemple des 5 premiers équipements :
            </div>
            <v-table class="sample-table" density="compact">
              <tbody>
                <tr>
                  <th>Équipement</th>
                  <th>Code actuel</th>
                  <th>Nouveau code</th>
                </tr>
                <tr v-for="item in previewData.equipment_sample" :key="item.id">
                  <td>{{ item.nom_commun }}</td>
                  <td class="code-cell">{{ item.current_code }}</td>
                  <td class="code-cell">{{ item.new_code }}</td>
                </tr>
              </tbody>
            </v-table>
          </div>

          <!-- Confirmation checkbox -->
          <v-checkbox
            v-model="isConfirmed"
            class="mt-6"
            color="primary"
            :disabled="loading"
          >
            <template #label>
              Je confirme vouloir modifier le sigle de l'entreprise.
              <span v-if="recodeEquipements" class="font-weight-medium">
                Tous les équipements seront recodifiés.
              </span>
            </template>
          </v-checkbox>
        </div>

        <!-- Error state -->
        <div v-else-if="step === 'error'">
          <v-alert color="error" icon="mdi-alert-circle" variant="tonal">
            <strong>Erreur lors de la preview :</strong>
            <div class="mt-2">{{ errorMessage }}</div>
          </v-alert>
        </div>
      </v-card-text>

      <!-- Actions -->
      <v-card-actions class="d-flex justify-end gap-2">
        <v-btn :disabled="loading" variant="text" @click="onClose">
          Annuler
        </v-btn>

        <v-btn
          v-if="step === 'input'"
          color="primary"
          :disabled="!newSigle || loading"
          :loading="loading"
          @click="onPreview"
        >
          Aperçu
        </v-btn>

        <v-btn
          v-if="step === 'preview'"
          color="warning"
          :disabled="!isConfirmed || loading"
          :loading="loading"
          prepend-icon="mdi-check-circle"
          @click="onConfirm"
        >
          Confirmer le changement
        </v-btn>

        <v-btn
          v-if="step === 'error'"
          color="primary"
          :disabled="loading"
          @click="goToInput"
        >
          Recommencer
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import { computed, ref } from 'vue'
  import { useToast } from '@/modules/shared/composables/useToast'
  import {
    enterpriseSigleService,
    type SiglePreviewResponse,
  } from '@/services/enterpriseSigleService'

  const props = defineProps<{
    modelValue: boolean
    enterpriseId: number
    currentSigle: string
    enterpriseMode: 'standard' | 'custom'
  }>()

  const emit = defineEmits<{
    (event: 'update:modelValue', value: boolean): void
    (event: 'success', payload: { old_sigle: string, new_sigle: string }): void
  }>()

  const toast = useToast()

  const isOpen = computed({
    get: () => props.modelValue,
    set: value => emit('update:modelValue', value),
  })

  const step = ref<'input' | 'preview' | 'error'>('input')
  const loading = ref(false)
  const newSigle = ref('')
  const reason = ref('')
  const recodeEquipements = ref(false)
  const isConfirmed = ref(false)
  const previewData = ref<SiglePreviewResponse | null>(null)
  const errorMessage = ref('')

  type HttpLikeError = {
    message?: string
    response?: {
      data?: {
        message?: string
        errors?: {
          new_sigle?: string[]
        }
      }
    }
  }

  function extractErrorMessage (error: unknown): string {
    const candidate = error as HttpLikeError

    return (
      candidate?.response?.data?.errors?.new_sigle?.[0]
      || candidate?.response?.data?.message
      || candidate?.message
      || 'Erreur lors de la vérification'
    )
  }

  function resetForm () {
    newSigle.value = ''
    reason.value = ''
    recodeEquipements.value = false
    isConfirmed.value = false
    previewData.value = null
    errorMessage.value = ''
    step.value = 'input'
  }

  function onClose () {
    isOpen.value = false
    resetForm()
  }

  function goToInput () {
    step.value = 'input'
    errorMessage.value = ''
  }

  async function onPreview () {
    if (!newSigle.value) {
      toast.error('Entrez un nouveau sigle')
      return
    }

    loading.value = true
    try {
      const data = await enterpriseSigleService.previewSigleChange(
        props.enterpriseId,
        newSigle.value.toUpperCase(),
      )

      if (!data.can_proceed) {
        errorMessage.value
          = data.warnings[0] || 'Impossible de procéder au changement'
        step.value = 'error'
        throw new Error(errorMessage.value)
      }

      previewData.value = data
      step.value = 'preview'
    } catch (error: unknown) {
      errorMessage.value = extractErrorMessage(error)
      step.value = 'error'
    } finally {
      loading.value = false
    }
  }

  async function onConfirm () {
    if (!isConfirmed.value) {
      toast.warning('Veuillez confirmer l\'opération')
      return
    }

    loading.value = true
    try {
      const result = await enterpriseSigleService.updateSigle(
        props.enterpriseId,
        newSigle.value.toUpperCase(),
        reason.value || undefined,
        recodeEquipements.value,
      )

      toast.success(
        `Sigle modifié avec succès : ${result.old_sigle} → ${result.new_sigle}`,
        'Succès',
        3500,
      )

      emit('success', {
        old_sigle: result.old_sigle,
        new_sigle: result.new_sigle,
      })

      onClose()
    } catch (error: unknown) {
      const candidate = error as HttpLikeError
      const message
        = candidate?.response?.data?.message
          || candidate?.message
          || 'Erreur lors de la mise à jour'

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

.sample-table {
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 4px;
  font-size: 0.875rem;

  th {
    background-color: rgba(0, 0, 0, 0.04);
    font-weight: 600;
    padding: 8px;
  }

  td {
    padding: 8px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.04);

    &:last-child {
      border-bottom: none;
    }
  }
}

.code-cell {
  font-family: "Courier New", monospace;
  font-size: 0.8rem;
  word-break: break-all;
}
</style>
