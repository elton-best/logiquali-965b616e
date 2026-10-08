<template>
  <v-container class="fill-height pa-4" fluid>
    <v-row align="center" justify="center">
      <v-col cols="12" lg="8" md="10" xl="6">
        <!-- Header -->
        <div class="text-center mb-6">
          <v-avatar
            class="mb-4"
            :color="'rgb(var(--v-theme-primary))'"
            size="72"
            style="border-radius: 12px"
          >
            <v-icon color="white" size="40">mdi-domain</v-icon>
          </v-avatar>
          <h1 class="text-h4 font-weight-bold mb-2" :style="{ color: 'rgb(var(--v-theme-on-surface))' }">
            Inscription Entreprise
          </h1>
          <p class="text-body-1" :style="{ color: 'rgb(var(--v-theme-on-surface-variant))' }">
            Créez votre compte professionnel en quelques minutes
          </p>
        </div>

        <!-- Progress Stepper -->
        <v-card
          class="mb-4"
          :color="'rgb(var(--v-theme-surface))'"
          elevation="0"
          style="border-radius: 12px"
        >
          <v-card-text class="pa-4">
            <v-stepper
              v-model="currentStep"
              alt-labels
              elevation="0"
              hide-actions
            >
              <v-stepper-header>
                <v-stepper-item
                  :color="'rgb(var(--v-theme-primary))'"
                  :complete="currentStep > 1"
                  title="Entreprise"
                  value="1"
                >
                  <template #icon>
                    <v-icon>mdi-domain</v-icon>
                  </template>
                </v-stepper-item>
                <v-divider />
                <v-stepper-item
                  :color="'rgb(var(--v-theme-primary))'"
                  :complete="currentStep > 2"
                  title="Administrateur"
                  value="2"
                >
                  <template #icon>
                    <v-icon>mdi-account-tie</v-icon>
                  </template>
                </v-stepper-item>
                <v-divider />
                <v-stepper-item
                  :color="'rgb(var(--v-theme-primary))'"
                  :complete="currentStep > 3"
                  title="Documents"
                  value="3"
                >
                  <template #icon>
                    <v-icon>mdi-file-document-multiple</v-icon>
                  </template>
                </v-stepper-item>
              </v-stepper-header>
            </v-stepper>
          </v-card-text>
        </v-card>

        <!-- Main Form Card -->
        <v-card
          :color="'rgb(var(--v-theme-surface))'"
          elevation="0"
          style="border-radius: 12px"
        >
          <v-card-text class="pa-6 pa-md-8">
            <v-form ref="formRef" @submit.prevent="handleSubmit">
              <!-- Enterprise Information -->
              <div class="mb-6">
                <h3 class="text-h6 font-weight-bold mb-4">Informations de l'entreprise</h3>

                <v-text-field
                  v-model="form.enterprise_name"
                  class="mb-3"
                  density="comfortable"
                  :error-messages="errors.enterprise_name"
                  hint="Nom officiel tel qu'enregistré dans vos documents légaux"
                  label="Nom de l'entreprise"
                  persistent-hint
                  prepend-inner-icon="mdi-office-building"
                  :rules="[rules.required]"
                  style="border-radius: 12px"
                  variant="outlined"
                  @focus="currentStep = 1"
                />

                <v-row>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.registration_number"
                      density="comfortable"
                      :error-messages="errors.registration_number"
                      hint="Registre du Commerce et du Crédit Mobilier"
                      label="RCCM"
                      persistent-hint
                      prepend-inner-icon="mdi-file-document"
                      :rules="[rules.required]"
                      style="border-radius: 12px"
                      variant="outlined"
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.ifu"
                      counter="13"
                      density="comfortable"
                      :error-messages="errors.ifu"
                      hint="Identifiant Fiscal Unique (13 chiffres max)"
                      label="IFU"
                      maxlength="13"
                      persistent-hint
                      prepend-inner-icon="mdi-numeric"
                      :rules="[rules.ifu]"
                      style="border-radius: 12px"
                      type="tel"
                      variant="outlined"
                      @input="formatIFU"
                    />
                  </v-col>
                </v-row>

                <v-text-field
                  v-model="form.address"
                  class="mb-3"
                  density="comfortable"
                  :error-messages="errors.address"
                  label="Adresse complète"
                  prepend-inner-icon="mdi-map-marker"
                  :rules="[rules.required]"
                  style="border-radius: 12px"
                  variant="outlined"
                />

                <v-row>
                  <v-col cols="12" md="6">
                    <v-autocomplete
                      v-model="form.country"
                      clearable
                      density="comfortable"
                      :error-messages="errors.country"
                      :items="countries.map(c => c.name)"
                      label="Pays"
                      prepend-inner-icon="mdi-flag"
                      :rules="[rules.required]"
                      style="border-radius: 12px"
                      variant="outlined"
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-autocomplete
                      v-model="form.city"
                      clearable
                      density="comfortable"
                      :disabled="!form.country"
                      :error-messages="errors.city"
                      hint="Sélectionnez d'abord un pays"
                      :items="cities.map(c => c.name)"
                      label="Ville"
                      persistent-hint
                      prepend-inner-icon="mdi-city"
                      style="border-radius: 12px"
                      variant="outlined"
                    />
                  </v-col>
                </v-row>

                <v-select
                  v-model="form.field"
                  class="mb-3"
                  density="comfortable"
                  :error-messages="errors.field"
                  :items="activitySectors"
                  label="Secteur d'activité"
                  prepend-inner-icon="mdi-briefcase"
                  style="border-radius: 12px"
                  variant="outlined"
                />
              </div>

              <v-divider class="my-6" />

              <!-- Administrator Account -->
              <div class="mb-6">
                <h3 class="text-h6 font-weight-bold mb-4">Compte administrateur</h3>

                <v-text-field
                  v-model="form.username"
                  class="mb-3"
                  density="comfortable"
                  :error-messages="errors.username"
                  hint="Utilisé pour vous connecter à la plateforme"
                  label="Nom d'utilisateur"
                  persistent-hint
                  prepend-inner-icon="mdi-account"
                  :rules="[rules.required, rules.username]"
                  style="border-radius: 12px"
                  variant="outlined"
                  @focus="currentStep = 2"
                />

                <v-row>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.email"
                      density="comfortable"
                      :error-messages="errors.email"
                      hint="Utilisé pour les notifications importantes"
                      label="Email professionnel"
                      persistent-hint
                      prepend-inner-icon="mdi-email"
                      :rules="[rules.required, rules.email]"
                      style="border-radius: 12px"
                      type="email"
                      variant="outlined"
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.phone"
                      density="comfortable"
                      :error-messages="errors.phone"
                      label="Téléphone (optionnel)"
                      prepend-inner-icon="mdi-phone"
                      style="border-radius: 12px"
                      variant="outlined"
                    />
                  </v-col>
                </v-row>

                <v-text-field
                  v-model="form.password"
                  :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                  class="mb-3"
                  density="comfortable"
                  :error-messages="errors.password"
                  hint="Minimum 8 caractères, avec majuscules, minuscules et chiffres"
                  label="Mot de passe"
                  persistent-hint
                  prepend-inner-icon="mdi-lock"
                  :rules="[rules.required, rules.minLength, rules.passwordStrength]"
                  style="border-radius: 12px"
                  :type="showPassword ? 'text' : 'password'"
                  variant="outlined"
                  @click:append-inner="showPassword = !showPassword"
                  @input="checkPasswordStrength"
                />

                <!-- Password Strength Indicator -->
                <div v-if="form.password" class="mb-4">
                  <div class="d-flex align-center gap-2 mb-1">
                    <div class="text-caption">Force du mot de passe:</div>
                    <div class="text-caption font-weight-bold" :style="{ color: passwordStrength.color }">
                      {{ passwordStrength.label }}
                    </div>
                  </div>
                  <v-progress-linear
                    :color="passwordStrength.color"
                    height="6"
                    :model-value="passwordStrength.value"
                    style="border-radius: 12px"
                  />
                </div>

                <v-text-field
                  v-model="form.password_confirmation"
                  :append-inner-icon="showPasswordConfirm ? 'mdi-eye-off' : 'mdi-eye'"
                  density="comfortable"
                  :error-messages="errors.password_confirmation"
                  label="Confirmer le mot de passe"
                  prepend-inner-icon="mdi-lock-check"
                  :rules="[rules.required, rules.passwordMatch]"
                  style="border-radius: 12px"
                  :type="showPasswordConfirm ? 'text' : 'password'"
                  variant="outlined"
                  @click:append-inner="showPasswordConfirm = !showPasswordConfirm"
                />
              </div>

              <v-divider class="my-6" />

              <!-- Documents Upload -->
              <div class="mb-6">
                <h3 class="text-h6 font-weight-bold mb-4">Documents justificatifs</h3>

                <v-file-input
                  v-model="documents.logo"
                  accept="image/*"
                  class="mb-3"
                  density="comfortable"
                  :error-messages="errors.logo"
                  hint="Format: PNG, JPG ou JPEG. Taille recommandée: 512x512px"
                  label="Logo de l'entreprise (optionnel)"
                  persistent-hint
                  prepend-icon=""
                  prepend-inner-icon="mdi-image"
                  show-size
                  style="border-radius: 12px"
                  variant="outlined"
                  @focus="currentStep = 3"
                />

                <v-file-input
                  v-model="documents.rccm_document"
                  accept=".pdf,.jpg,.jpeg,.png"
                  class="mb-3"
                  density="comfortable"
                  :error-messages="errors.rccm_document"
                  hint="Document obligatoire - Formats: PDF, JPG, PNG"
                  label="Document RCCM *"
                  persistent-hint
                  prepend-icon=""
                  prepend-inner-icon="mdi-file-pdf-box"
                  :rules="[rules.requiredFile]"
                  show-size
                  style="border-radius: 12px"
                  variant="outlined"
                />

                <v-file-input
                  v-model="documents.ifu_document"
                  accept=".pdf,.jpg,.jpeg,.png"
                  class="mb-3"
                  density="comfortable"
                  :error-messages="errors.ifu_document"
                  hint="Document obligatoire - Formats: PDF, JPG, PNG"
                  label="Document IFU *"
                  persistent-hint
                  prepend-icon=""
                  prepend-inner-icon="mdi-file-pdf-box"
                  :rules="[rules.requiredFile]"
                  show-size
                  style="border-radius: 12px"
                  variant="outlined"
                />

                <v-file-input
                  v-model="documents.id_document"
                  accept=".pdf,.jpg,.jpeg,.png"
                  density="comfortable"
                  :error-messages="errors.id_document"
                  hint="Document obligatoire - Formats: PDF, JPG, PNG"
                  label="Pièce d'identité du représentant *"
                  persistent-hint
                  prepend-icon=""
                  prepend-inner-icon="mdi-card-account-details"
                  :rules="[rules.requiredFile]"
                  show-size
                  style="border-radius: 12px"
                  variant="outlined"
                />

                <v-alert
                  class="mt-4"
                  :color="'rgb(var(--v-theme-info))'"
                  style="border-radius: 12px"
                  variant="tonal"
                >
                  <div class="text-caption">
                    <v-icon class="mr-1" size="small">mdi-information</v-icon>
                    Formats acceptés: PDF, JPG, JPEG, PNG. Taille maximale: 5 Mo par fichier.
                  </div>
                </v-alert>
              </div>

              <!-- Terms and Conditions -->
              <v-checkbox
                v-model="acceptTerms"
                class="mb-4"
                density="comfortable"
                :error-messages="errors.accept_terms"
                :rules="[rules.acceptTerms]"
              >
                <template #label>
                  <div class="text-body-2">
                    J'accepte les
                    <a class="text-primary text-decoration-none" href="#" @click.prevent>
                      conditions d'utilisation
                    </a>
                    et la
                    <a class="text-primary text-decoration-none" href="#" @click.prevent>
                      politique de confidentialité
                    </a>
                  </div>
                </template>
              </v-checkbox>

              <!-- Submit Button -->
              <v-btn
                block
                class="text-none font-weight-bold"
                :color="'rgb(var(--v-theme-primary))'"
                :loading="loading"
                size="large"
                style="border-radius: 12px"
                type="submit"
              >
                <v-icon class="mr-2" left>mdi-check-circle</v-icon>
                Créer mon compte entreprise
              </v-btn>

              <!-- Login Link -->
              <div class="text-center mt-6">
                <span class="text-body-2" :style="{ color: 'rgb(var(--v-theme-on-surface-variant))' }">
                  Vous avez déjà un compte ?
                </span>
                <v-btn
                  class="text-none ml-1"
                  :color="'rgb(var(--v-theme-primary))'"
                  variant="text"
                  @click="$router.push('/auth/login')"
                >
                  Se connecter
                </v-btn>
              </div>
            </v-form>
          </v-card-text>
        </v-card>

        <!-- Help Card -->
        <v-card
          class="mt-4"
          :color="'rgb(var(--v-theme-info))'"
          elevation="0"
          style="border-radius: 12px"
          variant="tonal"
        >
          <v-card-text>
            <div class="d-flex align-start gap-3">
              <v-icon :color="'rgb(var(--v-theme-info))'">mdi-information</v-icon>
              <div>
                <div class="font-weight-bold mb-1">Besoin d'aide ?</div>
                <div class="text-body-2">
                  Notre équipe est disponible pour vous accompagner.
                  Contactez-nous à <a class="text-decoration-none" href="mailto:support@BestQHSE.com">support@BestQHSE.com</a>
                </div>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
  import type { ICity, ICountry, IState } from 'country-state-city'
  import type { RegisterEnterpriseRequest } from '@/types/api'
  import { City, Country, State } from 'country-state-city'
  import { computed, reactive, ref, watch } from 'vue'
  import { useAuth } from '@/composables/useAuth'

  const { registerEnterprise, loading } = useAuth()

  const formRef = ref()
  const currentStep = ref(1)
  const showPassword = ref(false)
  const showPasswordConfirm = ref(false)
  const acceptTerms = ref(false)

  const form = reactive<RegisterEnterpriseRequest>({
    enterprise_name: '',
    email: '',
    registration_number: '',
    address: '',
    username: '',
    password: '',
    password_confirmation: '',
    phone: '',
    ifu: '',
    city: '',
    country: '',
    field: '',
    documents: [],
  })

  // Country and City data
  const countries = ref<ICountry[]>(Country.getAllCountries())
  const states = ref<IState[]>([])
  const cities = ref<ICity[]>([])
  const selectedCountryCode = ref('')
  const selectedStateCode = ref('')

  // Watch country changes to update states/cities
  watch(() => form.country, newCountry => {
    const country = countries.value.find(c => c.name === newCountry)
    if (country) {
      selectedCountryCode.value = country.isoCode
      states.value = State.getStatesOfCountry(country.isoCode)
      cities.value = City.getCitiesOfCountry(country.isoCode) || []
      form.city = ''
      selectedStateCode.value = ''
    } else {
      states.value = []
      cities.value = []
    }
  })

  // Watch state changes to update cities
  watch(() => selectedStateCode.value, newState => {
    if (newState && selectedCountryCode.value) {
      cities.value = City.getCitiesOfState(selectedCountryCode.value, newState) || []
    }
  })

  const documents = reactive({
    logo: null as File | null,
    rccm_document: null as File | null,
    ifu_document: null as File | null,
    id_document: null as File | null,
  })

  const activitySectors = [
    'Agriculture',
    'Agroalimentaire',
    'Automobile',
    'BTP & Construction',
    'Commerce & Distribution',
    'Conseil & Services',
    'Éducation & Formation',
    'Énergie',
    'Finance & Assurance',
    'Immobilier',
    'Industrie',
    'Informatique & Technologies',
    'Santé',
    'Télécommunications',
    'Tourisme & Hôtellerie',
    'Transport & Logistique',
    'Autre',
  ]

  const errors = reactive<Record<string, string>>({})

  const rules = {
    required: (v: string) => !!v || 'Ce champ est requis',
    email: (v: string) => /.+@.+\..+/.test(v) || 'Email invalide',
    minLength: (v: string) => (v && v.length >= 8) || 'Minimum 8 caractères',
    username: (v: string) => /^[a-zA-Z0-9_]{3,20}$/.test(v) || 'Format invalide (3-20 caractères, lettres, chiffres et _)',
    ifu: (v: string) => {
      if (!v) return true
      if (!/^\d+$/.test(v)) return 'L\'IFU doit contenir uniquement des chiffres'
      if (v.length > 13) return 'L\'IFU ne peut pas dépasser 13 caractères'
      return true
    },
    requiredFile: (v: File | File[] | null) => !!v || 'Ce document est requis',
    passwordStrength: (v: string) => {
      if (!v) return true
      const hasUpper = /[A-Z]/.test(v)
      const hasLower = /[a-z]/.test(v)
      const hasNumber = /\d/.test(v)
      return (hasUpper && hasLower && hasNumber) || 'Doit contenir majuscules, minuscules et chiffres'
    },
    passwordMatch: (v: string) => v === form.password || 'Les mots de passe ne correspondent pas',
    acceptTerms: (v: boolean) => v || 'Vous devez accepter les conditions',
  }

  // Format IFU input to only accept numbers
  function formatIFU (event: Event) {
    const input = event.target as HTMLInputElement
    const value = input.value.replace(/\D/g, '') // Remove non-digits
    form.ifu = value.slice(0, 13) // Limit to 13 characters
  }

  const passwordStrength = computed(() => {
    const password = form.password
    if (!password) return { value: 0, label: '', color: '' }

    let strength = 0
    if (password.length >= 8) strength += 25
    if (password.length >= 12) strength += 25
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 25
    if (/\d/.test(password)) strength += 12.5
    if (/[@$!%*?&#]/.test(password)) strength += 12.5

    if (strength < 40) return { value: strength, label: 'Faible', color: 'error' }
    if (strength < 70) return { value: strength, label: 'Moyen', color: 'warning' }
    return { value: strength, label: 'Fort', color: 'success' }
  })

  function checkPasswordStrength () {
  // Trigger reactivity
  }

  async function handleSubmit () {
    const { valid } = await formRef.value.validate()
    if (!valid) return

    // Validate required documents
    if (!documents.rccm_document) {
      errors.rccm_document = 'Le document RCCM est requis'
      return
    }
    if (!documents.ifu_document) {
      errors.ifu_document = 'Le document IFU est requis'
      return
    }
    if (!documents.id_document) {
      errors.id_document = 'La pièce d\'identité est requise'
      return
    }

    // Clear previous errors
    for (const key of Object.keys(errors)) delete errors[key]

    // Prepare documents array
    const documentsArray: File[] = []
    if (documents.logo) documentsArray.push(documents.logo)
    if (documents.rccm_document) documentsArray.push(documents.rccm_document)
    if (documents.ifu_document) documentsArray.push(documents.ifu_document)
    if (documents.id_document) documentsArray.push(documents.id_document)

    form.documents = documentsArray

    try {
      await registerEnterprise(form)
    } catch (error: any) {
      if (error.validationErrors) {
        // Laravel validation errors come as arrays of messages
        for (const key of Object.keys(error.validationErrors)) {
          const messages = error.validationErrors[key]
          errors[key] = Array.isArray(messages) ? messages[0] : messages
        }
      }
    }
  }
</script>

<style scoped>
:deep(.v-field) {
  border-radius: 12px;
}

:deep(.v-input__details) {
  padding-top: 4px;
}
</style>
