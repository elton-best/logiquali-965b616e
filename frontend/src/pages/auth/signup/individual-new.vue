<template>
  <AuthLayout
    central-icon="mdi-account-circle"
    :feature1-icon="'mdi-speedometer'"
    :feature1-subtitle="'Rapide et simple'"
    :feature1-title="'Inscription Express'"
    :feature2-icon="'mdi-shield-lock'"
    :feature2-subtitle="'Données protégées'"
    :feature2-title="'100% Sécurisé'"
    :feature3-icon="'mdi-headset'"
    :feature3-subtitle="'24/7 disponible'"
    :feature3-title="'Support Dédié'"
    tagline="Créez votre compte et profitez de tous nos services"
  >
    <!-- Form Header -->
    <div class="form-header">
      <h2 class="form-title">Inscription Particulier</h2>
      <p class="form-subtitle">Rejoignez-nous en quelques minutes</p>
    </div>

    <!-- Form -->
    <v-form ref="formRef" class="signup-form" @submit.prevent="handleSubmit">
      <div v-if="loading" class="form-loader-overlay">
        <UnifiedLoader
          description="Création de votre compte particulier"
          :show-skeleton="true"
          title="Inscription en cours..."
          variant="local"
        />
      </div>

      <!-- Name -->
      <div class="form-group">
        <label class="input-label">
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

      <!-- Email & Phone -->
      <v-row>
        <v-col cols="12" md="6">
          <div class="form-group">
            <label class="input-label">
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
            <label class="input-label">Téléphone</label>
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

      <!-- Password -->
      <div class="form-group">
        <label class="input-label">
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
      </div>

      <!-- Password Confirmation -->
      <div class="form-group">
        <label class="input-label">
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

      <!-- Terms -->
      <div class="terms-section">
        <v-checkbox
          v-model="acceptTerms"
          class="terms-checkbox"
          color="#4471c4"
          hide-details
          :rules="[rules.acceptTerms]"
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

      <!-- Submit Button -->
      <v-btn
        block
        class="submit-btn"
        :disabled="!acceptTerms"
        :loading="loading"
        size="x-large"
        type="submit"
      >
        <v-icon class="mr-2">mdi-account-check</v-icon>
        Créer mon compte
      </v-btn>

      <!-- Login Link -->
      <div class="form-footer">
        <span>Vous avez déjà un compte ?</span>
        <a class="footer-link" @click="$router.push('/auth/login')">Se connecter</a>
      </div>
    </v-form>
  </AuthLayout>
</template>

<script setup lang="ts">
  import { computed, reactive, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import AuthLayout from '@/components/AuthLayout.vue'
  import { useAuth } from '@/composables/useAuth'
  import UnifiedLoader from '@/modules/shared/components/ui/UnifiedLoader.vue'

  const router = useRouter()
  const { registerClient } = useAuth()

  const formRef = ref()
  const loading = ref(false)
  const showPassword = ref(false)
  const showPasswordConfirm = ref(false)
  const acceptTerms = ref(false)

  const formData = reactive({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
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
      await registerClient({
        username: formData.name,
        email: formData.email,
        phone: formData.phone,
        password: formData.password,
        password_confirmation: formData.password_confirmation,
      })

      // Redirection gérée par le composable
      router.push({
        path: '/auth/signup-success',
        query: { email: formData.email, type: 'client' },
      })
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
.form-header {
  text-align: center;
  margin-bottom: 2rem;
  margin-top: 1rem;
}

.form-title {
  color: #1a1a1a;
  font-size: 1.75rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
  letter-spacing: -0.5px;
}

.form-subtitle {
  color: #64748b;
  font-size: 0.9375rem;
  margin: 0;
}

.signup-form {
  margin-bottom: 1.5rem;
  position: relative;
}

.form-loader-overlay {
  position: absolute;
  inset: 0;
  z-index: 4;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.78);
  backdrop-filter: blur(2px);
}

.form-group {
  margin-bottom: 1rem;
}

.input-label {
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

.modern-input {
  :deep(.v-field) {
    border-radius: 12px;
    border: 2px solid #e2e8f0;
    background: #fafafa;
    transition: all 0.3s;

    &:hover {
      border-color: #cbd5e1;
      background: white;
    }

    &.v-field--focused {
      border-color: #4471c4;
      background: white;
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
.terms-section {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1rem;
  margin-bottom: 1.25rem;
}

.terms-checkbox {
  :deep(.v-label) {
    font-size: 0.875rem;
    color: #475569;
  }
}

.terms-label {
  line-height: 1.6;
}

.terms-link {
  color: #4471c4;
  font-weight: 600;
  text-decoration: none;

  &:hover {
    text-decoration: underline;
  }
}

// Submit Button
.submit-btn {
  background: linear-gradient(135deg, #4471c4, #2a4a8f);
  color: white;
  border-radius: 12px;
  text-transform: none;
  font-weight: 600;
  font-size: 1rem;
  letter-spacing: 0.2px;
  box-shadow: 0 4px 14px rgba(68, 113, 196, 0.4);
  transition: all 0.3s;

  &:hover:not(:disabled) {
    box-shadow: 0 6px 20px rgba(68, 113, 196, 0.5);
    transform: translateY(-2px);
  }

  &:active {
    transform: translateY(0);
  }
}

// Footer
.form-footer {
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
</style>
