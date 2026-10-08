<template>
  <div class="signup-page">
    <!-- Left Side - Form -->
    <div class="signup-form-section">
      <div class="form-container">
        <!-- Logo & Back -->
        <div class="top-navigation">
          <v-btn
            icon
            size="small"
            variant="text"
            @click="$router.push('/auth/login')"
          >
            <v-icon>mdi-arrow-left</v-icon>
          </v-btn>
          <div class="logo-mini">
            <AppLogo variant="full" />
          </div>
        </div>

        <!-- Progress Stepper -->
        <div class="stepper-section">
          <div class="stepper-header">
            <h2 class="stepper-title">Inscription Entreprise</h2>
            <p class="stepper-subtitle">Étape {{ currentStep }} sur 3</p>
          </div>

          <div class="stepper-progress">
            <div class="progress-track">
              <div class="progress-fill" :style="{ width: ((currentStep - 1) / 2 * 100) + '%' }" />
            </div>
            <div class="step-indicators">
              <div
                v-for="step in 3"
                :key="step"
                class="step-indicator"
                :class="{
                  active: currentStep === step,
                  completed: currentStep > step
                }"
              >
                <v-icon v-if="currentStep > step" color="white" size="16">mdi-check</v-icon>
                <span v-else>{{ step }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Form Card -->
        <v-card class="form-card" elevation="0">
          <v-card-text class="pa-4">
            <div v-if="loading" class="form-loader-overlay">
              <UnifiedLoader
                description="Validation des informations et envoi des documents"
                :show-skeleton="true"
                title="Inscription entreprise en cours..."
                variant="local"
              />
            </div>

            <v-form ref="formRef">
              <v-window v-model="currentStep">
                <!-- Step 1: Informations entreprise -->
                <v-window-item :value="1">
                  <div class="step-content">
                    <h3 class="step-title">Informations de l'entreprise</h3>

                    <v-row>
                      <v-col cols="12" md="8">
                        <div class="form-field">
                          <label class="field-label">Nom de l'entreprise <span class="required">*</span></label>
                          <v-text-field
                            v-model="form.enterprise_name"
                            density="comfortable"
                            :error-messages="errors.enterprise_name"
                            hide-details="auto"
                            placeholder="Ex: Best Experts Group SARL"
                            prepend-inner-icon="mdi-domain"
                            :rules="[rules.required]"
                            variant="outlined"
                          />
                        </div>
                      </v-col>
                      <v-col cols="12" md="4">
                        <div class="form-field">
                          <label class="field-label">Sigle <span class="text-medium-emphasis">(recommandé)</span></label>
                          <v-text-field
                            v-model="form.sigle"
                            density="comfortable"
                            :error-messages="errors.sigle"
                            hide-details="auto"
                            maxlength="10"
                            placeholder="Ex: BEG"
                            prepend-inner-icon="mdi-tag"
                            variant="outlined"
                            @input="normalizeSigle(($event?.target as HTMLInputElement)?.value || '')"
                          />
                          <div class="text-caption text-medium-emphasis mt-1">
                            Utilisé pour la codification.
                          </div>
                        </div>
                      </v-col>
                    </v-row>

                    <v-row>
                      <v-col cols="12" md="6">
                        <div class="form-field">
                          <label class="field-label">Pays <span class="required">*</span></label>
                          <v-autocomplete
                            v-model="form.country"
                            clearable
                            density="comfortable"
                            :error-messages="errors.country"
                            hide-details="auto"
                            item-title="name"
                            item-value="code"
                            :items="countries"
                            placeholder="Sélectionnez un pays"
                            prepend-inner-icon="mdi-flag"
                            :rules="[rules.required]"
                            variant="outlined"
                          />
                        </div>
                      </v-col>
                      <v-col cols="12" md="6">
                        <div class="form-field">
                          <label class="field-label">Ville</label>
                          <v-autocomplete
                            v-model="form.city"
                            clearable
                            density="comfortable"
                            :disabled="!form.country"
                            :error-messages="errors.city"
                            hide-details="auto"
                            :items="cities.map(c => c.name)"
                            placeholder="Sélectionnez une ville"
                            prepend-inner-icon="mdi-city"
                            variant="outlined"
                          />
                        </div>
                      </v-col>
                    </v-row>

                    <v-row>
                      <v-col cols="12" md="6">
                        <div class="form-field">
                          <label class="field-label">Numéro RCCM <span class="required">*</span></label>
                          <!-- <v-text-field
                            v-model="form.rccm_number"
                            density="comfortable"
                            :error-messages="errors.rccm_number"
                            hide-details="auto"
                            placeholder="Ex: RB/COT/12-A-12345"
                            prepend-inner-icon="mdi-file-document"
                            :rules="[rules.required]"
                            variant="outlined"
                          /> -->
                          <v-text-field
                            v-model="form.rccm_number"
                            density="comfortable"
                            :disabled="!form.country"
                            :error-messages="errors.rccm_number"
                            hide-details="auto"
                            :placeholder="placeholderRCCM"
                            prepend-inner-icon="mdi-file-document"
                            :rules="[rules.required]"
                            variant="outlined"
                          />
                        </div>
                      </v-col>
                      <v-col cols="12" md="6">
                        <div class="form-field">
                          <label class="field-label">Attestation RCCM <span class="required">*</span></label>
                          <v-file-input
                            v-model="documents.rccm_document"
                            accept=".pdf,.jpg,.jpeg,.png"
                            density="comfortable"
                            :error-messages="errors.rccm_document"
                            hide-details="auto"
                            placeholder="Choisir le fichier"
                            prepend-icon=""
                            prepend-inner-icon="mdi-paperclip"
                            :rules="[rules.required]"
                            variant="outlined"
                          />
                        </div>
                      </v-col>
                    </v-row>

                    <v-row>
                      <v-col cols="12" md="6">
                        <div class="form-field">
                          <label class="field-label">Numéro IFU (13 chiffres) <span class="required">*</span></label>
                          <v-text-field
                            v-model="form.ifu_number"
                            :counter="maxLenIFU"
                            density="comfortable"
                            :disabled="!form.country"
                            :error-messages="errors.ifu_number"
                            hide-details="auto"
                            :maxlength="maxLenIFU"
                            :placeholder="placeholderIFU"
                            prepend-inner-icon="mdi-numeric"
                            :rules="[rules.required, rules.ifu]"
                            type="tel"
                            variant="outlined"
                            @input="formatIFU"
                          />
                        </div>
                      </v-col>
                      <v-col cols="12" md="6">
                        <div class="form-field">
                          <label class="field-label">Attestation IFU <span class="required">*</span></label>
                          <v-file-input
                            v-model="documents.ifu_document"
                            accept=".pdf,.jpg,.jpeg,.png"
                            density="comfortable"
                            :error-messages="errors.ifu_document"
                            hide-details="auto"
                            placeholder="Choisir le fichier"
                            prepend-icon=""
                            prepend-inner-icon="mdi-paperclip"
                            :rules="[rules.required]"
                            variant="outlined"
                          />
                        </div>
                      </v-col>
                    </v-row>

                    <v-row>
                      <v-col cols="12" md="6">
                        <div class="form-field">
                          <label class="field-label">Secteur d'activité <span class="required">*</span></label>
                          <v-select
                            v-model="form.field"
                            density="comfortable"
                            :error-messages="errors.field"
                            hide-details="auto"
                            :items="activitySectors"
                            placeholder="Sélectionnez votre secteur"
                            prepend-inner-icon="mdi-briefcase"
                            :rules="[rules.required]"
                            variant="outlined"
                          />
                        </div>
                      </v-col>
                      <v-col cols="12" md="6">
                        <div class="form-field">
                          <label class="field-label">Adresse <span class="required">*</span></label>
                          <v-text-field
                            v-model="form.address"
                            density="comfortable"
                            :error-messages="errors.address"
                            hide-details="auto"
                            placeholder="Ex: 123 Avenue de la République"
                            prepend-inner-icon="mdi-map-marker"
                            :rules="[rules.required]"
                            variant="outlined"
                          />
                        </div>
                      </v-col>
                    </v-row>
                  </div>
                </v-window-item>

                <!-- Step 2: Administrateur -->
                <v-window-item :value="2">
                  <div class="step-content">
                    <h3 class="step-title">Compte administrateur</h3>

                    <v-row>
                      <v-col cols="12" md="6">
                        <div class="form-field">
                          <label class="field-label">Nom <span class="required">*</span></label>
                          <v-text-field
                            v-model="form.last_name"
                            density="comfortable"
                            :error-messages="errors.last_name"
                            hide-details="auto"
                            placeholder="Nom de famille"
                            prepend-inner-icon="mdi-account"
                            :rules="[rules.required]"
                            variant="outlined"
                          />
                        </div>
                      </v-col>
                      <v-col cols="12" md="6">
                        <div class="form-field">
                          <label class="field-label">Prénom(s) <span class="required">*</span></label>
                          <v-text-field
                            v-model="form.first_name"
                            density="comfortable"
                            :error-messages="errors.first_name"
                            hide-details="auto"
                            placeholder="Prénom(s)"
                            prepend-inner-icon="mdi-account"
                            :rules="[rules.required]"
                            variant="outlined"
                          />
                        </div>
                      </v-col>
                    </v-row>

                    <v-row>
                      <v-col cols="12" md="6">
                        <div class="form-field">
                          <label class="field-label">Email du compte administrateur <span class="required">*</span></label>
                          <v-text-field
                            v-model="form.email"
                            density="comfortable"
                            :error-messages="errors.email"
                            hide-details="auto"
                            placeholder="admin@entreprise.com"
                            prepend-inner-icon="mdi-email"
                            :rules="[rules.required, rules.email]"
                            type="email"
                            variant="outlined"
                          />
                          <p class="field-hint">Utilisé pour la connexion de l'admin principal.</p>
                        </div>
                      </v-col>
                      <v-col cols="12" md="6">
                        <div class="form-field">
                          <label class="field-label">Email de l'entreprise (optionnel)</label>
                          <v-text-field
                            v-model="form.enterprise_email"
                            density="comfortable"
                            :error-messages="errors.enterprise_email"
                            hide-details="auto"
                            placeholder="contact@entreprise.com"
                            prepend-inner-icon="mdi-domain"
                            :rules="[rules.email]"
                            type="email"
                            variant="outlined"
                          />
                          <p class="field-hint">Pour l'identité et les communications officielles de l'entreprise.</p>
                        </div>
                      </v-col>
                    </v-row>

                    <v-row>
                      <v-col cols="12" md="6">
                        <div class="form-field">
                          <label class="field-label">Poste</label>
                          <v-text-field
                            v-model="form.job_title"
                            density="comfortable"
                            :error-messages="errors.job_title"
                            hide-details="auto"
                            placeholder="Ex: Admin entreprise"
                            prepend-inner-icon="mdi-briefcase-account"
                            variant="outlined"
                          />
                        </div>
                      </v-col>
                      <v-col cols="12" md="6">
                        <div class="form-field">
                          <label class="field-label">Téléphone</label>
                          <v-text-field
                            v-model="form.phone"
                            density="comfortable"
                            :error-messages="errors.phone"
                            hide-details="auto"
                            placeholder="+229 XX XX XX XX"
                            prepend-inner-icon="mdi-phone"
                            variant="outlined"
                          />
                        </div>
                      </v-col>
                    </v-row>

                    <v-row>
                      <v-col cols="12" md="6">
                        <div class="form-field">
                          <label class="field-label">Mot de passe <span class="required">*</span></label>
                          <v-text-field
                            v-model="form.password"
                            :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                            density="comfortable"
                            :error-messages="errors.password"
                            hide-details="auto"
                            placeholder="••••••••"
                            prepend-inner-icon="mdi-lock"
                            :rules="[rules.required, rules.minLength, rules.passwordStrength]"
                            :type="showPassword ? 'text' : 'password'"
                            variant="outlined"
                            @click:append-inner="showPassword = !showPassword"
                          />
                        </div>
                      </v-col>
                      <v-col cols="12" md="6">
                        <div class="form-field">
                          <label class="field-label">Confirmer mot de passe <span class="required">*</span></label>
                          <v-text-field
                            v-model="form.password_confirmation"
                            :append-inner-icon="showPasswordConfirm ? 'mdi-eye-off' : 'mdi-eye'"
                            density="comfortable"
                            :error-messages="errors.password_confirmation"
                            hide-details="auto"
                            placeholder="••••••••"
                            prepend-inner-icon="mdi-lock-check"
                            :rules="[rules.required, rules.passwordMatch]"
                            :type="showPasswordConfirm ? 'text' : 'password'"
                            variant="outlined"
                            @click:append-inner="showPasswordConfirm = !showPasswordConfirm"
                          />
                        </div>
                      </v-col>
                    </v-row>
                  </div>
                </v-window-item>

                <!-- Step 3: Pièce d'identité -->
                <v-window-item :value="3">
                  <div class="step-content">
                    <h3 class="step-title">Pièce d'identité de l'administrateur</h3>

                    <v-row>
                      <v-col cols="12" md="6">
                        <div class="form-field">
                          <label class="field-label">Type de pièce <span class="required">*</span></label>
                          <v-select
                            v-model="form.id_type"
                            density="comfortable"
                            :error-messages="errors.id_type"
                            hide-details="auto"
                            :items="idTypes"
                            placeholder="Sélectionnez le type"
                            prepend-inner-icon="mdi-card-account-details"
                            :rules="[rules.required]"
                            variant="outlined"
                          />
                        </div>
                      </v-col>
                      <v-col cols="12" md="6">
                        <div class="form-field">
                          <label class="field-label">Numéro de pièce <span class="required">*</span></label>
                          <v-text-field
                            v-model="form.id_number"
                            density="comfortable"
                            :error-messages="errors.id_number"
                            hide-details="auto"
                            placeholder="Ex: CI123456789"
                            prepend-inner-icon="mdi-identifier"
                            :rules="[rules.required]"
                            variant="outlined"
                          />
                        </div>
                      </v-col>
                    </v-row>

                    <div class="form-field">
                      <label class="field-label">Document de la pièce d'identité <span class="required">*</span></label>
                      <v-file-input
                        v-model="documents.id_document"
                        accept=".pdf,.jpg,.jpeg,.png"
                        density="comfortable"
                        :error-messages="errors.id_document"
                        hide-details="auto"
                        placeholder="Choisir le fichier"
                        prepend-icon=""
                        prepend-inner-icon="mdi-paperclip"
                        :rules="[rules.required]"
                        variant="outlined"
                      />
                      <p class="field-hint">Formats acceptés: PDF, JPG, PNG (Max 5MB)</p>
                    </div>

                    <div class="form-field">
                      <label class="field-label">Logo de l'entreprise (optionnel)</label>
                      <v-file-input
                        v-model="documents.logo"
                        accept="image/*"
                        density="comfortable"
                        hide-details="auto"
                        placeholder="Choisir le logo"
                        prepend-icon=""
                        prepend-inner-icon="mdi-image"
                        variant="outlined"
                      />
                    </div>

                    <v-checkbox
                      v-model="acceptTerms"
                      hide-details
                    >
                      <template #label>
                        <span class="terms-label">
                          J'accepte les <a href="/terms" target="_blank">conditions d'utilisation</a>
                          <span class="required">*</span>
                        </span>
                      </template>
                    </v-checkbox>
                  </div>
                </v-window-item>
              </v-window>
            </v-form>

            <!-- Actions -->
            <div class="form-actions">
              <v-btn
                v-if="currentStep > 1"
                class="action-btn"
                :disabled="loading"
                size="large"
                variant="outlined"
                @click="prevStep"
              >
                <v-icon class="mr-2">mdi-arrow-left</v-icon>
                Précédent
              </v-btn>
              <v-spacer />
              <v-btn
                v-if="currentStep < 3"
                class="action-btn primary-btn"
                color="primary"
                :disabled="loading"
                size="large"
                @click="nextStep"
              >
                Suivant
                <v-icon class="ml-2">mdi-arrow-right</v-icon>
              </v-btn>
              <v-btn
                v-else
                class="action-btn primary-btn"
                color="primary"
                :disabled="!acceptTerms"
                :loading="loading"
                size="large"
                @click="handleSubmit"
              >
                <v-icon class="mr-2">mdi-check-circle</v-icon>
                Créer mon compte
              </v-btn>
            </div>
          </v-card-text>
        </v-card>
      </div>
    </div>

    <!-- Right Side - Visual (Blue) -->
    <div class="signup-visual-section">
      <div class="visual-overlay">
        <div class="visual-content">
          <div class="visual-header">
            <v-icon color="white" size="64">mdi-domain</v-icon>
            <h2>Gestion qualité simplifiée</h2>
            <p>Rejoignez des centaines d'entreprises qui nous font confiance</p>
          </div>

          <div class="features-grid">
            <div class="feature-item">
              <v-icon color="white" size="32">mdi-check-circle</v-icon>
              <h4>Conformité ISO</h4>
              <p>Respectez les normes facilement</p>
            </div>
            <div class="feature-item">
              <v-icon color="white" size="32">mdi-chart-timeline</v-icon>
              <h4>Suivi en temps réel</h4>
              <p>Tableaux de bord intuitifs</p>
            </div>
            <div class="feature-item">
              <v-icon color="white" size="32">mdi-shield-check</v-icon>
              <h4>Sécurité garantie</h4>
              <p>Vos données sont protégées</p>
            </div>
            <div class="feature-item">
              <v-icon color="white" size="32">mdi-account-group</v-icon>
              <h4>Collaboration</h4>
              <p>Travaillez en équipe efficacement</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { RegisterEnterpriseRequest } from '@/types/api'
  import { computed, onMounted, reactive, ref, watch } from 'vue'
  import { useToast } from 'vue-toastification'
  import AppLogo from '@/components/branding/AppLogo.vue'
  import { useAuth } from '@/composables/useAuth'
  import { COUNTRY_OPTIONS } from '@/constants/countries'
  import UnifiedLoader from '@/modules/shared/components/ui/UnifiedLoader.vue'
  import { geoCatalogService, normalizeCountryCode } from '@/services/geoCatalogService'

  const { registerEnterprise, loading } = useAuth()
  const toast = useToast()

  const formRef = ref()
  const currentStep = ref(1)
  const showPassword = ref(false)
  const showPasswordConfirm = ref(false)
  const acceptTerms = ref(false)

  const form = reactive<RegisterEnterpriseRequest>({
    enterprise_name: '',
    sigle: '',
    email: '',
    enterprise_email: '',
    registration_number: '', // Pour le backend
    rccm_number: '',
    ifu_number: '',
    address: '',
    first_name: '',
    last_name: '',
    username: '',
    job_title: '',
    password: '',
    password_confirmation: '',
    phone: '',
    city: '',
    country: '',
    field: '',
    id_type: '',
    id_number: '',
  })

  const documents = reactive({
    logo: null as File | File[] | null,
    rccm_document: null as File | File[] | null,
    ifu_document: null as File | File[] | null,
    id_document: null as File | File[] | null,
  })

  const countries = ref(COUNTRY_OPTIONS.map(country => ({
    code: country.code,
    name: country.name,
  })))
  const cities = ref<Array<{ name: string }>>([])
  const selectedCountryCode = computed(() => normalizeCountryCode(form.country || ''))
  const selectedCountryName = computed(() => {
    const country = countries.value.find(c => c.code === selectedCountryCode.value)
    return country?.name || selectedCountryCode.value
  })

  watch(() => form.country, async newCountry => {
    form.city = ''
    const countryCode = normalizeCountryCode(newCountry || '')
    if (!countryCode) {
      cities.value = []
      return
    }

    cities.value = await geoCatalogService.getCitiesByCountry(countryCode)
  })

  onMounted(async () => {
    const remoteCountries = await geoCatalogService.getCountries()
    countries.value = remoteCountries.map(country => ({
      code: country.code,
      name: country.name,
    }))
  })

  // 1. Définition des placeholders dynamiques
  const placeholderRCCM = computed(() => {
    if (selectedCountryCode.value === 'BJ') return 'Ex: RB/COT/12-A-12345'
    if (selectedCountryCode.value === 'TG') return 'Ex: TG-LOM 2024 B 1234'
    return 'Saisissez le numéro RCCM'
  })

  const maxLenIFU = computed(() => {
    if (selectedCountryCode.value === 'BJ') return 13
    if (selectedCountryCode.value === 'TG') return 10
    return 20
  })

  const placeholderIFU = computed(() => {
    return `Format spécifique ${selectedCountryName.value || ''}`
  })

  const activitySectors = [
    'Agriculture', 'Agroalimentaire', 'Automobile', 'BTP & Construction',
    'Commerce & Distribution', 'Conseil & Services', 'Éducation & Formation',
    'Énergie', 'Finance & Assurance', 'Immobilier', 'Industrie',
    'Informatique & Technologies', 'Santé', 'Télécommunications',
    'Tourisme & Hôtellerie', 'Transport & Logistique', 'Autre',
  ]

  const idTypes = [
    { title: 'CNI - Carte Nationale d\'Identité', value: 'cni' },
    { title: 'Passeport', value: 'passeport' },
    { title: 'Permis de conduire', value: 'permis' },
  ]

  const errors = reactive<Record<string, string>>({})

  const rules = {
    required: (v: any) => !!v || 'Ce champ est requis',
    email: (v: string) => /.+@.+\..+/.test(v) || 'Email invalide',
    minLength: (v: string) => (v && v.length >= 8) || 'Minimum 8 caractères',
    ifu: (v: string) => {
      if (!v) return 'Ce champ est requis'
      if (!/^\d+$/.test(v)) return 'Uniquement des chiffres'

      // Validation spécifique au Bénin par exemple
      if (selectedCountryCode.value === 'BJ' && v.length !== 13) {
        return 'L\'IFU du Bénin doit comporter exactement 13 chiffres'
      }
      return true
    },
    passwordStrength: (v: string) => {
      if (!v) return true
      const hasUpper = /[A-Z]/.test(v)
      const hasLower = /[a-z]/.test(v)
      const hasNumber = /\d/.test(v)
      return (hasUpper && hasLower && hasNumber) || 'Majuscules, minuscules et chiffres requis'
    },
    passwordMatch: (v: string) => v === form.password || 'Les mots de passe ne correspondent pas',
  }

  function normalizeSigle (value: string) {
    form.sigle = value.toUpperCase().replace(/\s+/g, '').slice(0, 10)
  }

  function formatIFU (event: Event) {
    const input = event.target as HTMLInputElement
    const value = input.value.replace(/\D/g, '')
    form.ifu_number = value.slice(0, maxLenIFU.value)
  }

  function nextStep () {
    currentStep.value++
  }

  function prevStep () {
    currentStep.value--
  }

  // Helper pour extraire le fichier (gère les cas File | File[] | null)
  function extractFile (fileInput: File | File[] | null): File | null {
    if (!fileInput) return null
    if (Array.isArray(fileInput)) return fileInput[0] || null
    return fileInput
  }

  async function handleSubmit () {
    const { valid } = await formRef.value.validate()
    if (!valid) {
      toast.error('Veuillez remplir tous les champs obligatoires')
      return
    }

    // Vérifier que tous les documents requis sont présents
    const rccmFile = extractFile(documents.rccm_document)
    const ifuFile = extractFile(documents.ifu_document)
    const idFile = extractFile(documents.id_document)
    const logoFile = extractFile(documents.logo)

    if (!rccmFile) {
      toast.error('L\'attestation RCCM est requise')
      return
    }
    if (!ifuFile) {
      toast.error('L\'attestation IFU est requise')
      return
    }
    if (!idFile) {
      toast.error('Le document de la pièce d\'identité est requis')
      return
    }

    // Préparer les données avec documents séparés
    const submitData: RegisterEnterpriseRequest = {
      ...form,
      registration_number: form.rccm_number || form.registration_number || '', // Backend attend registration_number
      logo: logoFile ?? undefined,
      rccm_document: rccmFile,
      ifu_document: ifuFile,
      id_document: idFile,
    }

    try {
      await registerEnterprise(submitData)
    } catch (error: any) {
      if (error.validationErrors) {
        Object.assign(errors, error.validationErrors)
        // Afficher message d'erreur plus clair
        const firstError = Object.keys(error.validationErrors)[0]
        if (firstError) {
          const firstMessage = error.validationErrors[firstError]
          toast.error(`Erreur: ${Array.isArray(firstMessage) ? firstMessage[0] : firstMessage}`)
        }
      }
    }
  }
</script>

<style scoped lang="scss">
.signup-page {
  display: flex;
  min-height: 100vh;
  background: #f8fafc;
}

.signup-form-section {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  overflow-y: auto;

  @media (max-width: 1024px) {
    flex: none;
    width: 100%;
  }
}

.form-container {
  width: 100%;
  max-width: 700px;
}

.top-navigation {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 2rem;
}

.logo-mini {
  display: flex;
  align-items: center;
  gap: 0.5rem;

  .logo-text {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1a1a1a;
  }
}

.stepper-section {
  margin-bottom: 2rem;
}

.stepper-header {
  margin-bottom: 1.5rem;
}

.stepper-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1a1a1a;
  margin-bottom: 0.25rem;
}

.stepper-subtitle {
  color: #64748b;
  font-size: 0.875rem;
}

.stepper-progress {
  position: relative;
}

.progress-track {
  height: 4px;
  background: #e2e8f0;
  border-radius: 4px;
  margin-bottom: 1rem;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #4471c4, #2a4a8f);
  border-radius: 4px;
  transition: width 0.3s ease;
}

.step-indicators {
  display: flex;
  justify-content: space-between;
}

.step-indicator {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: white;
  border: 2px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 0.875rem;
  color: #64748b;
  transition: all 0.3s ease;

  &.active {
    background: linear-gradient(135deg, #4471c4, #2a4a8f);
    border-color: #4471c4;
    color: white;
  }

  &.completed {
    background: #10b981;
    border-color: #10b981;
  }
}

.form-card {
  position: relative;
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
}

.form-loader-overlay {
  position: absolute;
  inset: 0;
  z-index: 6;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(3px);
}

.step-content {
  min-height: auto;
}

.step-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1a1a1a;
  margin-bottom: 0.5rem;
}

.form-field {
  margin-bottom: 0.6rem;
}

.field-label {
  display: block;
  font-size: 0.875rem;
  font-weight: 600;
  color: #334155;
  margin-bottom: 0.25rem;

  .required {
    color: #ef4444;
    margin-left: 0.25rem;
  }
}

:deep(.form-card .v-row) {
  row-gap: 8px;
}

:deep(.form-card .v-col) {
  padding-top: 6px !important;
  padding-bottom: 6px !important;
}

:deep(.form-card .v-field) {
  min-height: 44px;
}

.field-hint {
  font-size: 0.75rem;
  color: #94a3b8;
  margin-top: 0.25rem;
}

.terms-label {
  font-size: 0.875rem;
  color: #334155;

  a {
    color: #4471c4;
    text-decoration: none;

    &:hover {
      text-decoration: underline;
    }
  }
}

.form-actions {
  display: flex;
  gap: 1rem;
  margin-top: 2rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e2e8f0;
}

.action-btn {
  border-radius: 12px;
  font-weight: 600;
  text-transform: none;

  &.primary-btn {
    background: linear-gradient(135deg, #4471c4, #2a4a8f);
    box-shadow: 0 4px 14px rgba(68, 113, 196, 0.4);

    &:hover {
      box-shadow: 0 6px 20px rgba(68, 113, 196, 0.5);
    }
  }
}

// Right Visual Section (Blue)
.signup-visual-section {
  flex: 1;
  background: linear-gradient(135deg, #4471c4, #2a4a8f);
  position: relative;
  overflow: hidden;

  @media (max-width: 1024px) {
    display: none;
  }

  &::before {
    content: '';
    position: absolute;
    width: 600px;
    height: 600px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    top: -200px;
    right: -200px;
  }

  &::after {
    content: '';
    position: absolute;
    width: 400px;
    height: 400px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 50%;
    bottom: -100px;
    left: -100px;
  }
}

.visual-overlay {
  position: relative;
  z-index: 1;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 4rem;
}

.visual-content {
  text-align: center;
  color: white;
}

.visual-header {
  margin-bottom: 3rem;

  h2 {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 1.5rem 0 1rem;
  }

  p {
    font-size: 1.125rem;
    opacity: 0.9;
  }
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
}

.feature-item {
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(10px);
  border-radius: 16px;
  padding: 1.5rem;
  border: 1px solid rgba(255, 255, 255, 0.2);
  transition: transform 0.3s ease;

  &:hover {
    transform: translateY(-5px);
  }

  h4 {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0.75rem 0 0.5rem;
  }

  p {
    font-size: 0.875rem;
    opacity: 0.9;
    margin: 0;
  }
}
</style>
