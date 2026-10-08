<template>
  <div>
    <h3 class="text-h6 mb-6">Informations de l'entreprise</h3>

    <v-card class="mb-6" variant="outlined">
      <v-card-text>
        <div class="d-flex align-center gap-4">
          <v-avatar color="grey-lighten-2" rounded="lg" size="80">
            <v-img v-if="companyLogoUrl" :src="companyLogoUrl" />
            <v-icon v-else color="grey" size="40">mdi-domain</v-icon>
          </v-avatar>
          <div class="flex-grow-1">
            <div class="text-subtitle-1 font-weight-medium">
              Logo de l'entreprise
            </div>
            <div class="text-caption text-medium-emphasis mb-2">
              Format SVG, PNG. Max 500KB. Dimensions recommandées: 200x200px
            </div>
            <input
              ref="companyLogoInput"
              accept="image/png,image/jpeg,image/jpg,image/svg+xml"
              class="d-none"
              type="file"
              @change="emit('logo-selected', $event)"
            >
            <v-btn
              v-if="canUpdateSettings"
              :loading="logoUploading"
              prepend-icon="mdi-upload"
              size="small"
              variant="outlined"
              @click="companyLogoInput?.click()"
            >
              Télécharger un logo
            </v-btn>
          </div>
        </div>
      </v-card-text>
    </v-card>

    <v-card class="mb-6" variant="outlined">
      <v-card-text>
        <v-alert
          v-if="!String(companyForm.domaineActivite || '').trim()"
          class="mb-4"
          color="warning"
          icon="mdi-alert-circle-outline"
          variant="tonal"
        >
          Le domaine d'activité est obligatoire pour finaliser l'accès
          entreprise.
        </v-alert>

        <v-alert
          v-if="!String(companyForm.sigle || '').trim()"
          class="mb-4"
          color="info"
          icon="mdi-tag-outline"
          variant="tonal"
        >
          Le sigle de l'entreprise est recommandé pour la codification des équipements.
          Vous pouvez l’ajouter à tout moment.
        </v-alert>

        <v-row>
          <v-col cols="12">
            <v-text-field
              v-model="companyForm.name"
              density="comfortable"
              label="Nom de l'entreprise"
              prepend-inner-icon="mdi-domain"
              :readonly="!canUpdateSettings"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="6">
            <div class="d-flex gap-2 align-end">
              <div class="flex-grow-1">
                <v-text-field
                  v-model="companyForm.sigle"
                  density="comfortable"
                  label="Sigle de l'entreprise"
                  maxlength="10"
                  prepend-inner-icon="mdi-tag"
                  :readonly="true"
                  variant="outlined"
                />
              </div>
              <v-btn
                v-if="canUpdateSettings"
                icon="mdi-pencil"
                size="small"
                variant="outlined"
                @click="showSigleModal = true"
              />
            </div>
          </v-col>
          <v-col cols="12" md="6">
            <v-select
              v-model="companyForm.codification_mode"
              density="comfortable"
              :items="[
                { title: standardCodificationLabel, value: 'standard' },
                { title: 'Code personnalisé', value: 'custom' },
              ]"
              label="Mode de codification équipements"
              prepend-inner-icon="mdi-barcode"
              :readonly="!canUpdateSettings"
              variant="outlined"
            />
          </v-col>
          <v-col v-if="companyForm.codification_mode === 'standard'" cols="12">
            <v-switch
              v-model="companyForm.recode_equipements"
              color="warning"
              inset
              label="Recoder tous les équipements existants avec le nouveau sigle"
              :readonly="!canUpdateSettings"
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-text-field
              v-if="!isFranceCountry"
              v-model="companyForm.rccm_number"
              density="comfortable"
              label="RCCM"
              prepend-inner-icon="mdi-card-account-details"
              :readonly="!canUpdateSettings"
              variant="outlined"
            />
            <v-text-field
              v-else
              v-model="companyForm.siret"
              density="comfortable"
              label="SIRET"
              prepend-inner-icon="mdi-card-account-details"
              :readonly="!canUpdateSettings"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-text-field
              v-if="!isFranceCountry"
              v-model="companyForm.ifu_number"
              density="comfortable"
              label="IFU"
              prepend-inner-icon="mdi-receipt-text"
              :readonly="!canUpdateSettings"
              variant="outlined"
            />
            <v-text-field
              v-else
              v-model="companyForm.vat_number"
              density="comfortable"
              label="N° TVA"
              prepend-inner-icon="mdi-receipt-text"
              :readonly="!canUpdateSettings"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12">
            <v-text-field
              v-model="companyForm.address"
              density="comfortable"
              label="Adresse"
              prepend-inner-icon="mdi-map-marker"
              :readonly="!canUpdateSettings"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-autocomplete
              v-model="companyForm.city"
              clearable
              density="comfortable"
              :disabled="!companyForm.country"
              :items="availableCities.map((city) => city.name)"
              label="Ville"
              :readonly="!canUpdateSettings"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-text-field
              v-model="companyForm.postalCode"
              density="comfortable"
              label="Code postal"
              :readonly="!canUpdateSettings"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-autocomplete
              v-model="companyForm.country"
              clearable
              density="comfortable"
              item-title="name"
              item-value="code"
              :items="availableCountries"
              label="Pays"
              :readonly="!canUpdateSettings"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="companyForm.phone"
              density="comfortable"
              label="Téléphone"
              prepend-inner-icon="mdi-phone"
              :readonly="!canUpdateSettings"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="companyForm.email"
              density="comfortable"
              label="Email"
              prepend-inner-icon="mdi-email"
              :readonly="!canUpdateSettings"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-select
              v-model="companyForm.domaineActivite"
              density="comfortable"
              :items="activityDomains"
              label="Domaine d'activité"
              prepend-inner-icon="mdi-briefcase-outline"
              :readonly="!canUpdateSettings"
              variant="outlined"
            />
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- Sigle History Section -->
    <v-card v-if="companyForm.id" class="mb-6" variant="outlined">
      <v-card-title>Historique du sigle</v-card-title>
      <v-card-text>
        <SigleHistoryList :enterprise-id="companyForm.id" />
      </v-card-text>
    </v-card>

    <!-- Sigle Change Modal -->
    <SigleChangeConfirmation
      v-model="showSigleModal"
      :current-sigle="companyForm.sigle || ''"
      :enterprise-id="companyForm.id || 0"
      :enterprise-mode="companyForm.codification_mode || 'standard'"
      @success="onSigleChangeSuccess"
    />

    <div class="d-flex justify-end">
      <v-btn
        v-if="canUpdateSettings"
        color="primary"
        :disabled="!isCompanyFormValid"
        :loading="companySaving"
        prepend-icon="mdi-content-save"
        size="large"
        @click="emit('save')"
      >
        Enregistrer les modifications
      </v-btn>
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import { ref } from 'vue'
  import { useToast } from '@/modules/shared/composables/useToast'
  import SigleChangeConfirmation from './SigleChangeConfirmation.vue'
  import SigleHistoryList from './SigleHistoryList.vue'

  const props = defineProps({
    canUpdateSettings: {
      type: Boolean,
      required: true,
    },
    companyForm: {
      type: Object as PropType<Record<string, any>>,
      required: true,
    },
    companyLogoUrl: {
      type: String,
      required: true,
    },
    logoUploading: {
      type: Boolean,
      required: true,
    },
    availableCountries: {
      type: Array as PropType<Array<{ code: string, name: string }>>,
      required: true,
    },
    availableCities: {
      type: Array as PropType<Array<{ name: string }>>,
      required: true,
    },
    activityDomains: {
      type: Array as PropType<string[]>,
      required: true,
    },
    isFranceCountry: {
      type: Boolean,
      required: true,
    },
    isCompanyFormValid: {
      type: Boolean,
      required: true,
    },
    companySaving: {
      type: Boolean,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'save'): void
    (event: 'logo-selected', fileEvent: Event): void
  }>()

  const companyLogoInput = ref<HTMLInputElement | null>(null)
  const appName = String(import.meta.env.VITE_APP_NAME || 'BestQHSE').trim() || 'BestQHSE'
  const standardCodificationLabel = `Standard ${appName}`

  // Sigle management
  const showSigleModal = ref(false)
  const toast = useToast()

  function onSigleChangeSuccess (payload: {
    old_sigle: string
    new_sigle: string
  }) {
    // Update the form with new sigle
    props.companyForm.sigle = payload.new_sigle
    toast.success(
      `Sigle modifié : ${payload.old_sigle} → ${payload.new_sigle}`,
      'Succès',
      3500,
    )
  }
</script>
