<template>
  <div class="signup-container">
    <!-- Animated Background -->
    <div class="animated-bg">
      <div class="shape shape-1" />
      <div class="shape shape-2" />
    </div>

    <v-container class="signup-wrapper" fluid>
      <v-row align="center" justify="center" no-gutters>
        <v-col cols="12" lg="5" md="7" xl="4">
          <div class="signup-content">
            <SignupHeader />

            <!-- Form Card -->
            <v-card class="form-card" data-aos="fade-up" elevation="0">
              <v-card-text class="pa-6 pa-md-8">
                <v-form ref="formRef" @submit.prevent="handleSubmit">
                  <!-- Personal Info Section -->
                  <div class="form-section">
                    <div class="section-header">
                      <v-icon color="#4471c4" size="20">mdi-account-outline</v-icon>
                      <span class="section-title">Informations personnelles</span>
                    </div>

                    <div class="form-group">
                      <label class="form-label">
                        Nom complet
                        <span class="required">*</span>
                      </label>
                      <v-text-field
                        v-model="formData.name"
                        class="modern-input"
                        density="comfortable"
                        :error-messages="errors.name"
                        hide-details="auto"
                        placeholder="Jean Dupont"
                        prepend-inner-icon="mdi-account-outline"
                        :rules="[rules.required]"
                        variant="outlined"
                      >
                        <template #append-inner>
                          <v-icon v-if="formData.name && !errors.name" color="success" size="20">
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
                            v-model="formData.email"
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
                              <v-icon v-if="formData.email && rules.email(formData.email) === true" color="success" size="20">
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
                            v-model="formData.phone"
                            class="modern-input"
                            density="comfortable"
                            :error-messages="errors.phone"
                            hide-details="auto"
                            placeholder="+229 XX XX XX XX"
                            prepend-inner-icon="mdi-phone-outline"
                            :rules="[rules.phone]"
                            variant="outlined"
                          />
                        </div>
                      </v-col>
                    </v-row>
                  </div>

                  <!-- Professional Info Section (Optional) -->
                  <div class="form-section">
                    <div class="section-header">
                      <v-icon color="#64748b" size="20">mdi-briefcase-outline</v-icon>
                      <span class="section-title">Informations professionnelles</span>
                      <v-chip class="ml-auto" color="info" size="x-small" variant="flat">Optionnel</v-chip>
                    </div>

                    <div class="form-group">
                      <label class="form-label">Entreprise</label>
                      <v-text-field
                        v-model="formData.company"
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
                        v-model="formData.address"
                        class="modern-input"
                        density="comfortable"
                        :error-messages="errors.address"
                        hide-details="auto"
                        placeholder="123 Avenue de la République"
                        prepend-inner-icon="mdi-map-marker-outline"
                        variant="outlined"
                      />
                    </div>

                    <v-row>
                      <v-col cols="12" md="6">
                        <div class="form-group">
                          <label class="form-label">Pays</label>
                          <v-autocomplete
                            v-model="formData.country"
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
                            v-model="formData.city"
                            class="modern-input"
                            clearable
                            density="comfortable"
                            :disabled="!formData.country"
                            :error-messages="errors.city"
                            hide-details="auto"
                            :items="cities.map(c => c.name)"
                            placeholder="Sélectionnez une ville"
                            prepend-inner-icon="mdi-city-variant-outline"
                            variant="outlined"
                          />
                        </div>
                      </v-col>
                    </v-row>
                  </div>

                  <!-- Security Section -->
                  <div class="form-section security-section">
                    <div class="section-header">
                      <v-icon color="#4471c4" size="20">mdi-lock-outline</v-icon>
                      <span class="section-title">Sécurité</span>
                    </div>

                    <div class="form-group">
                      <label class="form-label">
                        Mot de passe
                        <span class="required">*</span>
                      </label>
                      <v-text-field
                        v-model="formData.password"
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
                      <div v-if="formData.password" class="password-strength">
                        <div class="strength-bar">
                          <div
                            class="strength-fill"
                            :class="passwordStrength.color"
                            :style="{ width: passwordStrength.value + '%' }"
                          />
                        </div>
                        <div class="d-flex justify-space-between align-center">
                          <div class="strength-label" :class="passwordStrength.color">
                            {{ passwordStrength.label }}
                          </div>
                          <div class="password-requirements">
                            <v-icon :color="formData.password.length >= 8 ? 'success' : 'grey'" size="14">
                              {{ formData.password.length >= 8 ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                            </v-icon>
                            <span class="requirement-text">8+ caractères</span>
                          </div>
                        </div>
                      </div>

                      <div class="field-hint">Min. 8 caractères avec majuscules, minuscules et chiffres</div>
                    </div>

                    <div class="form-group">
                      <label class="form-label">
                        Confirmer le mot de passe
                        <span class="required">*</span>
                      </label>
                      <v-text-field
                        v-model="formData.password_confirmation"
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
                            v-if="formData.password_confirmation && formData.password === formData.password_confirmation"
                            color="success"
                            size="20"
                          >
                            mdi-check-circle
                          </v-icon>
                        </template>
                      </v-text-field>
                    </div>
                  </div>

                  <SignupTerms v-model="acceptTerms" :rules="[rules.acceptTerms]" />

                  <!-- Submit Button -->
                  <v-btn
                    block
                    class="modern-btn primary-btn"
                    :disabled="!acceptTerms"
                    :loading="loading"
                    size="x-large"
                    type="submit"
                  >
                    <v-icon class="mr-2">mdi-account-check</v-icon>
                    Créer mon compte
                  </v-btn>

                  <!-- Login Link -->
                  <div class="signup-footer">
                    <span>Vous avez déjà un compte ?</span>
                    <a class="footer-link" @click="router.push('/auth/login')">Se connecter</a>
                  </div>
                </v-form>
              </v-card-text>
            </v-card>

            <SignupHelpCard />
          </div>
        </v-col>
      </v-row>
    </v-container>
  </div>
</template>

<script setup lang="ts">
  import { computed, onMounted, reactive, ref, watch } from 'vue'
  import { useRouter } from 'vue-router'
  import { COUNTRY_OPTIONS } from '@/constants/countries'
  import SignupHeader from '@/pages/auth/components/signup-clientb/SignupHeader.vue'
  import SignupHelpCard from '@/pages/auth/components/signup-clientb/SignupHelpCard.vue'
  import SignupTerms from '@/pages/auth/components/signup-clientb/SignupTerms.vue'
  import { geoCatalogService } from '@/services/geoCatalogService'
  import { useAuthStore } from '@/stores/auth'

  const router = useRouter()
  const authStore = useAuthStore()

  const formRef = ref()
  const loading = ref(false)
  const showPassword = ref(false)
  const showPasswordConfirm = ref(false)
  const acceptTerms = ref(false)

  const formData = reactive({
    name: '',
    email: '',
    phone: '',
    company: '',
    address: '',
    city: '',
    country: '',
    password: '',
    password_confirmation: '',
  })

  const countries = ref(COUNTRY_OPTIONS.map(country => ({
    isoCode: country.code,
    name: country.name,
  })))
  const cities = ref<Array<{ name: string }>>([])

  watch(() => formData.country, async newCountry => {
    const country = countries.value.find(c => c.name === newCountry)
    formData.city = ''
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

  const errors = reactive<Record<string, string>>({})

  const rules = {
    required: (v: string) => !!v || 'Ce champ est requis',
    email: (v: string) => /.+@.+\..+/.test(v) || 'Email invalide',
    minLength: (v: string) => (v && v.length >= 8) || 'Minimum 8 caractères',
    phone: (v: string) => !v || /^[\d\s\-+()]{8,}$/.test(v) || 'Numéro invalide',
    passwordStrength: (v: string) => {
      if (!v) return true
      const hasUpper = /[A-Z]/.test(v)
      const hasLower = /[a-z]/.test(v)
      const hasNumber = /\d/.test(v)
      return (hasUpper && hasLower && hasNumber) || 'Majuscules, minuscules et chiffres requis'
    },
    passwordMatch: (v: string) => v === formData.password || 'Les mots de passe ne correspondent pas',
    acceptTerms: (v: boolean) => v || 'Vous devez accepter les conditions',
  }

  const passwordStrength = computed(() => {
    const password = formData.password
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
      alert('Veuillez accepter les conditions')
      return
    }

    // Clear previous errors
    for (const key of Object.keys(errors)) delete errors[key]

    loading.value = true
    try {
      await authStore.register({
        name: formData.name,
        email: formData.email,
        phone: formData.phone,
        company: formData.company,
        address: formData.address,
        city: formData.city,
        country: formData.country,
        password: formData.password,
        password_confirmation: formData.password_confirmation,
        role: 'client_b',
      } as any)

      router.push('/clientb/dashboard')
    } catch (error: any) {
      console.error('Registration failed:', error)
      if (error.validationErrors) {
        for (const key of Object.keys(error.validationErrors)) {
          const messages = error.validationErrors[key]
          errors[key] = Array.isArray(messages) ? messages[0] : messages
        }
      } else {
        alert(error?.message || 'Erreur lors de l\'inscription')
      }
    } finally {
      loading.value = false
    }
  }
</script>

<style scoped lang="scss">
.signup-container {
  min-height: 100vh;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  position: relative;
  overflow: hidden;
}

// Animated Background
.animated-bg {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  overflow: hidden;
  pointer-events: none;
  z-index: 0;
}

.shape {
  position: absolute;
  background: linear-gradient(135deg, rgba(68, 113, 196, 0.08), rgba(42, 74, 143, 0.08));
  border-radius: 50%;
  animation: float 20s ease-in-out infinite;

  &.shape-1 {
    width: 350px;
    height: 350px;
    top: -120px;
    right: -100px;
    animation-delay: 0s;
  }

  &.shape-2 {
    width: 250px;
    height: 250px;
    bottom: -80px;
    left: -60px;
    animation-delay: 3s;
  }
}

@keyframes float {
  0%, 100% {
    transform: translateY(0) rotate(0deg);
  }
  50% {
    transform: translateY(-30px) rotate(180deg);
  }
}

// Main Layout
.signup-wrapper {
  position: relative;
  z-index: 1;
  padding: 3rem 1rem;
  min-height: 100vh;
}

.signup-content {
  max-width: 600px;
  margin: 0 auto;
}

// Header
:deep(.signup-header) {
  text-align: center;
  margin-bottom: 2.5rem;
}

:deep(.logo-badge) {
  width: 72px;
  height: 72px;
  background: linear-gradient(135deg, #4471c4, #2a4a8f);
  border-radius: 18px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1.25rem;
  box-shadow: 0 8px 24px rgba(68, 113, 196, 0.3);
  animation: float-soft 3s ease-in-out infinite;
}

@keyframes float-soft {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-8px); }
}

:deep(.signup-title) {
  color: #1a1a1a;
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
  letter-spacing: -0.5px;
}

:deep(.signup-subtitle) {
  color: #64748b;
  font-size: 1rem;
  line-height: 1.6;
}

// Form Card
.form-card {
  background: white;
  border-radius: 20px;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
  margin-bottom: 1.5rem;
}

// Form Section
.form-section {
  margin-bottom: 2rem;

  &.security-section {
    background: linear-gradient(135deg, rgba(68, 113, 196, 0.03), rgba(42, 74, 143, 0.03));
    border: 1px solid rgba(68, 113, 196, 0.1);
    border-radius: 16px;
    padding: 1.5rem;
  }
}

.section-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1.25rem;
}

.section-title {
  color: #334155;
  font-weight: 600;
  font-size: 0.9375rem;
  flex: 1;
}

.form-group {
  margin-bottom: 1.25rem;
}

.form-label {
  display: block;
  color: #334155;
  font-size: 0.875rem;
  font-weight: 600;
  margin-bottom: 0.5rem;

  .required {
    color: #ef4444;
    margin-left: 0.25rem;
  }
}

.field-hint {
  color: #94a3b8;
  font-size: 0.75rem;
  margin-top: 0.375rem;
}

.modern-input {
  :deep(.v-field) {
    border-radius: 12px;
    border: 2px solid #e2e8f0;
    background: white;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);

    &:hover {
      border-color: #cbd5e1;
    }

    &.v-field--focused {
      border-color: #4471c4;
      box-shadow: 0 0 0 4px rgba(68, 113, 196, 0.1);
    }

    &.v-field--error {
      border-color: #ef4444;
      animation: shake 0.5s;
    }
  }

  :deep(.v-field__input) {
    padding: 0.75rem 1rem;
    font-size: 0.9375rem;
  }
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  10%, 30%, 50%, 70%, 90% { transform: translateX(-4px); }
  20%, 40%, 60%, 80% { transform: translateX(4px); }
}

// Password Strength
.password-strength {
  margin-top: 0.75rem;
}

.strength-bar {
  height: 6px;
  background: #f1f5f9;
  border-radius: 6px;
  overflow: hidden;
  margin-bottom: 0.5rem;
}

.strength-fill {
  height: 100%;
  transition: width 0.3s ease, background-color 0.3s ease;
  border-radius: 6px;

  &.weak {
    background: linear-gradient(90deg, #ef4444, #dc2626);
  }

  &.medium {
    background: linear-gradient(90deg, #f59e0b, #d97706);
  }

  &.strong {
    background: linear-gradient(90deg, #10b981, #059669);
  }
}

.strength-label {
  font-size: 0.75rem;
  font-weight: 600;

  &.weak { color: #ef4444; }
  &.medium { color: #f59e0b; }
  &.strong { color: #10b981; }
}

.password-requirements {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.requirement-text {
  font-size: 0.75rem;
  color: #94a3b8;
}

// Terms
:deep(.terms-section) {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1rem;
  margin-bottom: 1.5rem;
}

:deep(.terms-checkbox) {
  :deep(.v-label) {
    font-size: 0.875rem;
    color: #475569;
  }
}

:deep(.terms-label) {
  line-height: 1.6;
}

:deep(.terms-link) {
  color: #4471c4;
  font-weight: 600;
  text-decoration: none;

  &:hover {
    text-decoration: underline;
  }
}

// Button
.modern-btn {
  border-radius: 12px;
  text-transform: none;
  font-weight: 600;
  font-size: 1rem;
  letter-spacing: 0.2px;
  transition: all 0.3s ease;

  &.primary-btn {
    background: linear-gradient(135deg, #4471c4, #2a4a8f);
    color: white;
    box-shadow: 0 4px 14px rgba(68, 113, 196, 0.4);

    &:hover:not(:disabled) {
      box-shadow: 0 6px 20px rgba(68, 113, 196, 0.5);
      transform: translateY(-2px);
    }

    &:active {
      transform: translateY(0);
    }
  }
}

// Footer
.signup-footer {
  text-align: center;
  color: #64748b;
  font-size: 0.9375rem;
  margin-top: 1.5rem;

  span {
    margin-right: 0.5rem;
  }
}

.footer-link {
  color: #4471c4;
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;

  &:hover {
    text-decoration: underline;
  }
}

// Help Card
:deep(.help-card) {
  display: flex;
  align-items: center;
  gap: 1rem;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1rem 1.25rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

:deep(.help-text) {
  flex: 1;
  display: flex;
  flex-direction: column;
}

:deep(.help-title) {
  color: #334155;
  font-weight: 600;
  font-size: 0.875rem;
  margin-bottom: 0.125rem;
}

:deep(.help-subtitle) {
  color: #64748b;
  font-size: 0.75rem;
}

// Responsive
@media (max-width: 768px) {
  .signup-title {
    font-size: 1.75rem;
  }

  .form-card {
    :deep(.v-card-text) {
      padding: 1.5rem !important;
    }
  }
}
</style>
