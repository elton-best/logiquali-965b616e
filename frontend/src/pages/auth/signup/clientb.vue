<template>
  <div class="signup-container">
    <!-- Animated Background -->
    <div class="animated-bg">
      <div class="shape shape-1" />
      <div class="shape shape-2" />
      <div class="shape shape-3" />
    </div>

    <v-container class="signup-wrapper" fluid>
      <v-row align="center" justify="center" no-gutters>
        <v-col cols="12" lg="6" md="8" xl="5">
          <div class="signup-content">
            <!-- Header -->
            <div class="signup-header" data-aos="fade-down">
              <div class="logo-badge">
                <v-icon color="white" size="32">mdi-account-circle</v-icon>
              </div>
              <h1 class="signup-title">Créez votre compte Client</h1>
              <p class="signup-subtitle">Inscrivez-vous en quelques minutes pour accéder à nos services</p>
            </div>

            <!-- Form Card -->
            <v-card class="form-card form-loader-scope" data-aos="fade-up" data-aos-delay="200" elevation="0">
              <div v-if="loading" class="form-loader-overlay">
                <UnifiedLoader
                  centered
                  message="Création du compte en cours..."
                  size="sm"
                  variant="spinner"
                />
              </div>
              <v-card-text class="pa-0">
                <v-form ref="formRef">
                  <!-- Personal Information -->
                  <div class="step-content">
                    <div class="step-header">
                      <v-icon class="step-icon" color="#4471c4" size="40">mdi-account-outline</v-icon>
                      <h2 class="step-title">Informations personnelles</h2>
                      <p class="step-description">Renseignez vos informations de contact</p>
                    </div>

                    <div class="form-section">
                      <div class="form-group">
                        <label class="form-label">
                          Nom complet
                          <span class="required">*</span>
                        </label>
                        <v-text-field
                          v-model="form.name"
                          class="modern-input"
                          density="comfortable"
                          :error-messages="errors.name"
                          hide-details="auto"
                          placeholder="Ex: Jean Dupont"
                          prepend-inner-icon="mdi-account-outline"
                          :rules="[rules.required]"
                          variant="outlined"
                        >
                          <template #append-inner>
                            <v-icon v-if="form.name && !errors.name" color="success" size="20">
                              mdi-check-circle
                            </v-icon>
                          </template>
                        </v-text-field>
                      </div>

                      <v-row>
                        <v-col cols="12" md="6">
                          <div class="form-group">
                            <label class="form-label">
                              Email
                              <span class="required">*</span>
                            </label>
                            <v-text-field
                              v-model="form.email"
                              class="modern-input"
                              density="comfortable"
                              :error-messages="errors.email"
                              hide-details="auto"
                              placeholder="jean@exemple.com"
                              prepend-inner-icon="mdi-email-outline"
                              :rules="[rules.required, rules.email]"
                              type="email"
                              variant="outlined"
                            >
                              <template #append-inner>
                                <v-icon v-if="form.email && rules.email(form.email) === true" color="success" size="20">
                                  mdi-check-circle
                                </v-icon>
                              </template>
                            </v-text-field>
                          </div>
                        </v-col>
                        <v-col cols="12" md="6">
                          <div class="form-group">
                            <label class="form-label">Téléphone</label>
                            <v-text-field
                              v-model="form.phone"
                              class="modern-input"
                              density="comfortable"
                              :error-messages="errors.phone"
                              hide-details="auto"
                              placeholder="+229 XX XX XX XX"
                              prepend-inner-icon="mdi-phone-outline"
                              variant="outlined"
                            />
                          </div>
                        </v-col>
                      </v-row>

                      <div class="divider-section">
                        <v-divider />
                        <span class="divider-text">Informations professionnelles (optionnel)</span>
                        <v-divider />
                      </div>

                      <div class="form-group">
                        <label class="form-label">Entreprise</label>
                        <v-text-field
                          v-model="form.company"
                          class="modern-input"
                          density="comfortable"
                          :error-messages="errors.company"
                          hide-details="auto"
                          placeholder="Nom de votre entreprise"
                          prepend-inner-icon="mdi-domain"
                          variant="outlined"
                        />
                      </div>

                      <div class="form-group">
                        <label class="form-label">Adresse</label>
                        <v-text-field
                          v-model="form.address"
                          class="modern-input"
                          density="comfortable"
                          :error-messages="errors.address"
                          hide-details="auto"
                          placeholder="Ex: 123 Avenue de la République"
                          prepend-inner-icon="mdi-map-marker-outline"
                          variant="outlined"
                        />
                      </div>

                      <v-row>
                        <v-col cols="12" md="6">
                          <div class="form-group">
                            <label class="form-label">Pays</label>
                            <v-autocomplete
                              v-model="form.country"
                              class="modern-input"
                              clearable
                              density="comfortable"
                              :error-messages="errors.country"
                              hide-details="auto"
                              :items="countries.map(c => c.name)"
                              placeholder="Sélectionnez un pays"
                              prepend-inner-icon="mdi-flag-outline"
                              variant="outlined"
                            />
                          </div>
                        </v-col>
                        <v-col cols="12" md="6">
                          <div class="form-group">
                            <label class="form-label">Ville</label>
                            <v-autocomplete
                              v-model="form.city"
                              class="modern-input"
                              clearable
                              density="comfortable"
                              :disabled="!form.country"
                              :error-messages="errors.city"
                              hide-details="auto"
                              :items="cities.map(c => c.name)"
                              placeholder="Sélectionnez une ville"
                              prepend-inner-icon="mdi-city-variant-outline"
                              variant="outlined"
                            />
                            <div v-if="!form.country" class="field-hint">Sélectionnez d'abord un pays</div>
                          </div>
                        </v-col>
                      </v-row>

                      <div class="security-section">
                        <div class="section-label">
                          <v-icon color="#4471c4" size="20">mdi-lock-outline</v-icon>
                          <span>Sécurité</span>
                        </div>

                        <div class="form-group">
                          <label class="form-label">
                            Mot de passe
                            <span class="required">*</span>
                          </label>
                          <v-text-field
                            v-model="form.password"
                            :append-inner-icon="showPassword ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"
                            class="modern-input"
                            density="comfortable"
                            :error-messages="errors.password"
                            hide-details="auto"
                            placeholder="••••••••"
                            prepend-inner-icon="mdi-lock-outline"
                            :rules="[rules.required, rules.minLength, rules.passwordStrength]"
                            :type="showPassword ? 'text' : 'password'"
                            variant="outlined"
                            @click:append-inner="showPassword = !showPassword"
                          />

                          <!-- Password Strength Indicator -->
                          <div v-if="form.password" class="password-strength">
                            <div class="strength-bar">
                              <div
                                class="strength-fill"
                                :class="passwordStrength.color"
                                :style="{ width: passwordStrength.value + '%' }"
                              />
                            </div>
                            <div class="strength-label" :class="passwordStrength.color">
                              {{ passwordStrength.label }}
                            </div>
                          </div>

                          <div class="field-hint">Minimum 8 caractères avec majuscules, minuscules et chiffres</div>
                        </div>

                        <div class="form-group">
                          <label class="form-label">
                            Confirmer le mot de passe
                            <span class="required">*</span>
                          </label>
                          <v-text-field
                            v-model="form.password_confirmation"
                            :append-inner-icon="showPasswordConfirm ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"
                            class="modern-input"
                            density="comfortable"
                            :error-messages="errors.password_confirmation"
                            hide-details="auto"
                            placeholder="••••••••"
                            prepend-inner-icon="mdi-lock-check-outline"
                            :rules="[rules.required, rules.passwordMatch]"
                            :type="showPasswordConfirm ? 'text' : 'password'"
                            variant="outlined"
                            @click:append-inner="showPasswordConfirm = !showPasswordConfirm"
                          >
                            <template #append-inner>
                              <v-icon
                                v-if="form.password_confirmation && form.password === form.password_confirmation"
                                color="success"
                                size="20"
                              >
                                mdi-check-circle
                              </v-icon>
                            </template>
                          </v-text-field>
                        </div>
                      </div>

                      <!-- Terms -->
                      <div class="terms-section">
                        <v-checkbox
                          v-model="acceptTerms"
                          class="terms-checkbox"
                          color="#4471c4"
                          hide-details
                        >
                          <template #label>
                            <div class="terms-label">
                              J'accepte les
                              <a class="terms-link" href="#" @click.prevent>conditions d'utilisation</a>
                              et la
                              <a class="terms-link" href="#" @click.prevent>politique de confidentialité</a>
                            </div>
                          </template>
                        </v-checkbox>
                      </div>
                    </div>

                    <div class="step-actions">
                      <v-btn
                        class="modern-btn primary-btn"
                        :disabled="!acceptTerms"
                        :loading="loading"
                        size="large"
                        @click="handleSubmit"
                      >
                        <v-icon class="mr-2">mdi-check-circle</v-icon>
                        Créer mon compte
                      </v-btn>
                    </div>
                  </div>
                </v-form>
              </v-card-text>
            </v-card>

            <!-- Footer Links -->
            <div class="signup-footer">
              <span>Vous avez déjà un compte ?</span>
              <a class="footer-link" @click="$router.push('/auth/login')">Se connecter</a>
            </div>
          </div>
        </v-col>
      </v-row>
    </v-container>
  </div>
</template>

<script setup lang="ts">
  import type { RegisterClientRequest } from '@/types/api'
  import { computed, onMounted, reactive, ref, watch } from 'vue'
  import { useAuth } from '@/composables/useAuth'
  import { COUNTRY_OPTIONS } from '@/constants/countries'
  import UnifiedLoader from '@/modules/shared/components/ui/UnifiedLoader.vue'
  import { geoCatalogService } from '@/services/geoCatalogService'

  const { registerClient, loading } = useAuth()

  const formRef = ref()
  const showPassword = ref(false)
  const showPasswordConfirm = ref(false)
  const acceptTerms = ref(false)

  const form = reactive<RegisterClientRequest & { name?: string, company?: string, address?: string, city?: string, country?: string }>({
    username: '',
    email: '',
    password: '',
    password_confirmation: '',
    phone: '',
    name: '',
    company: '',
    address: '',
    city: '',
    country: '',
  })

  const errors = reactive<Record<string, string>>({})

  const countries = ref(COUNTRY_OPTIONS.map(country => ({
    isoCode: country.code,
    name: country.name,
  })))
  const cities = ref<Array<{ name: string }>>([])

  watch(() => form.country, async newCountry => {
    const country = countries.value.find(c => c.name === newCountry)
    form.city = ''
    if (!country) {
      cities.value = []
      return
    }

    cities.value = await geoCatalogService.getCitiesByCountry(country.isoCode)
  })

  onMounted(async () => {
    const remoteCountries = await geoCatalogService.getCountries()
    countries.value = remoteCountries.map(country => ({
      isoCode: country.code,
      name: country.name,
    }))
  })

  const rules = {
    required: (v: string) => !!v || 'Ce champ est requis',
    email: (v: string) => /.+@.+\..+/.test(v) || 'Email invalide',
    minLength: (v: string) => (v && v.length >= 8) || 'Minimum 8 caractères',
    passwordStrength: (v: string) => {
      if (!v) return true
      const hasUpper = /[A-Z]/.test(v)
      const hasLower = /[a-z]/.test(v)
      const hasNumber = /\d/.test(v)
      return (hasUpper && hasLower && hasNumber) || 'Majuscules, minuscules et chiffres requis'
    },
    passwordMatch: (v: string) => v === form.password || 'Les mots de passe ne correspondent pas',
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

    if (strength < 40) return { value: strength, label: 'Faible', color: 'weak' }
    if (strength < 70) return { value: strength, label: 'Moyen', color: 'medium' }
    return { value: strength, label: 'Fort', color: 'strong' }
  })

  async function handleSubmit () {
    const { valid } = await formRef.value.validate()
    if (!valid) return

    if (!acceptTerms.value) {
      errors.terms = 'Veuillez accepter les conditions'
      return
    }

    // Clear previous errors
    for (const key of Object.keys(errors)) delete errors[key]

    // Use name as username if username not provided
    if (!form.username && form.name) {
      form.username = form.name.toLowerCase().replace(/\s+/g, '')
    }

    try {
      await registerClient(form)
    } catch (error: any) {
      if (error.validationErrors) {
        for (const key of Object.keys(error.validationErrors)) {
          const messages = error.validationErrors[key]
          errors[key] = Array.isArray(messages) ? messages[0] : messages
        }
      }
    }
  }
</script>

<style scoped lang="scss">
.signup-container {
  min-height: 100vh;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  position: relative;
  overflow-x: hidden;
}

// Animated Background
.animated-bg {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  overflow: hidden;
  z-index: 0;
}

.shape {
  position: absolute;
  border-radius: 50%;
  background: linear-gradient(135deg, rgba(68, 113, 196, 0.1) 0%, rgba(68, 113, 196, 0.05) 100%);
  animation: float 20s infinite ease-in-out;
}

.shape-1 {
  width: 500px;
  height: 500px;
  top: -10%;
  right: -10%;
  animation-delay: 0s;
}

.shape-2 {
  width: 400px;
  height: 400px;
  bottom: -10%;
  left: -10%;
  animation-delay: -7s;
}

.shape-3 {
  width: 300px;
  height: 300px;
  top: 40%;
  left: 60%;
  animation-delay: -14s;
}

@keyframes float {
  0%, 100% {
    transform: translate(0, 0) rotate(0deg);
  }
  25% {
    transform: translate(30px, -30px) rotate(5deg);
  }
  50% {
    transform: translate(-20px, 20px) rotate(-5deg);
  }
  75% {
    transform: translate(40px, 10px) rotate(3deg);
  }
}

.signup-wrapper {
  position: relative;
  z-index: 1;
  min-height: 100vh;
  display: flex;
  align-items: center;
  padding: 40px 20px;
}

.signup-content {
  width: 100%;
}

// Header
.signup-header {
  text-align: center;
  margin-bottom: 32px;
}

.logo-badge {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #4471c4 0%, #5a8fd9 100%);
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 24px;
  box-shadow: 0 8px 24px rgba(68, 113, 196, 0.3);
}

.signup-title {
  font-size: 32px;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 12px;
  line-height: 1.2;
}

.signup-subtitle {
  font-size: 16px;
  color: #64748b;
  margin: 0;
}

// Form Card
.form-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-radius: 24px;
  border: 1px solid rgba(255, 255, 255, 0.8);
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.form-loader-scope {
  position: relative;
}

.form-loader-overlay {
  position: absolute;
  inset: 0;
  z-index: 5;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(2px);
  background: rgba(255, 255, 255, 0.72);
}

.step-content {
  padding: 48px 40px;

  @media (max-width: 768px) {
    padding: 32px 24px;
  }
}

.step-header {
  text-align: center;
  margin-bottom: 40px;
}

.step-icon {
  margin-bottom: 16px;
}

.step-title {
  font-size: 28px;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 8px;
}

.step-description {
  font-size: 15px;
  color: #64748b;
  margin: 0;
}

// Form Sections
.form-section {
  margin-bottom: 0;
}

.form-group {
  margin-bottom: 24px;
}

.form-label {
  display: block;
  font-size: 14px;
  font-weight: 600;
  color: #334155;
  margin-bottom: 8px;

  .required {
    color: #ef4444;
    margin-left: 4px;
  }
}

.modern-input {
  :deep(.v-field) {
    border-radius: 12px;
    font-size: 15px;
  }

  :deep(.v-field--focused) {
    box-shadow: 0 0 0 3px rgba(68, 113, 196, 0.1);
  }
}

.field-hint {
  font-size: 13px;
  color: #64748b;
  margin-top: 6px;
}

// Divider Section
.divider-section {
  display: flex;
  align-items: center;
  gap: 16px;
  margin: 32px 0;

  .divider-text {
    font-size: 13px;
    font-weight: 500;
    color: #94a3b8;
    white-space: nowrap;
  }
}

// Security Section
.security-section {
  margin-top: 32px;
  padding-top: 32px;
  border-top: 1px solid #e2e8f0;
}

.section-label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 15px;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 24px;
}

// Password Strength
.password-strength {
  margin-top: 8px;
}

.strength-bar {
  height: 4px;
  background: #e2e8f0;
  border-radius: 2px;
  overflow: hidden;
  margin-bottom: 8px;
}

.strength-fill {
  height: 100%;
  transition: all 0.3s ease;
  border-radius: 2px;

  &.weak {
    background: #ef4444;
  }

  &.medium {
    background: #f59e0b;
  }

  &.strong {
    background: #10b981;
  }
}

.strength-label {
  font-size: 13px;
  font-weight: 600;

  &.weak {
    color: #ef4444;
  }

  &.medium {
    color: #f59e0b;
  }

  &.strong {
    color: #10b981;
  }
}

// Terms
.terms-section {
  margin-top: 32px;
  padding: 20px;
  background: #f8fafc;
  border-radius: 12px;
}

.terms-checkbox {
  :deep(.v-label) {
    opacity: 1;
  }
}

.terms-label {
  font-size: 14px;
  color: #475569;
  line-height: 1.6;
}

.terms-link {
  color: #4471c4;
  text-decoration: none;
  font-weight: 600;
  transition: color 0.2s;

  &:hover {
    color: #365a9e;
    text-decoration: underline;
  }
}

// Actions
.step-actions {
  margin-top: 32px;
  display: flex;
  gap: 16px;
}

.modern-btn {
  border-radius: 12px;
  text-transform: none;
  font-weight: 600;
  letter-spacing: 0;
  padding: 0 32px;

  &.primary-btn {
    background: linear-gradient(135deg, #4471c4 0%, #5a8fd9 100%);
    color: white;
    flex: 1;

    &:hover {
      box-shadow: 0 8px 24px rgba(68, 113, 196, 0.3);
    }
  }
}

// Footer
.signup-footer {
  text-align: center;
  margin-top: 24px;
  font-size: 15px;
  color: #64748b;

  .footer-link {
    color: #4471c4;
    font-weight: 600;
    margin-left: 8px;
    text-decoration: none;
    cursor: pointer;
    transition: color 0.2s;

    &:hover {
      color: #365a9e;
      text-decoration: underline;
    }
  }
}

// Animations
[data-aos] {
  &[data-aos="fade-down"] {
    animation: fadeDown 0.6s ease-out;
  }

  &[data-aos="fade-up"] {
    animation: fadeUp 0.6s ease-out;
  }
}

@keyframes fadeDown {
  from {
    opacity: 0;
    transform: translateY(-30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes fadeUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
