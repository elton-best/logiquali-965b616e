<template>
  <v-container class="fill-height pa-4" fluid>
    <v-row align="center" justify="center">
      <v-col cols="12" lg="6" md="8" xl="5">
        <!-- Header -->
        <div class="text-center mb-6">
          <v-avatar
            class="mb-4"
            :color="'rgb(var(--v-theme-info))'"
            size="72"
            style="border-radius: 12px"
          >
            <v-icon color="white" size="40">mdi-account</v-icon>
          </v-avatar>
          <h1 class="text-h4 font-weight-bold mb-2" :style="{ color: 'rgb(var(--v-theme-on-surface))' }">
            Inscription Particulier
          </h1>
          <p class="text-body-1" :style="{ color: 'rgb(var(--v-theme-on-surface-variant))' }">
            Créez votre compte personnel pour accéder à nos services
          </p>
        </div>

        <!-- Main Form Card -->
        <div class="individual-signup-loader-scope">
          <div v-if="loading" class="individual-signup-loader-overlay">
            <UnifiedLoader
              description="Création du compte en cours"
              title="Inscription en cours..."
              variant="local"
            />
          </div>
          <v-card
            :color="'rgb(var(--v-theme-surface))'"
            elevation="0"
            style="border-radius: 12px"
          >
            <v-card-text class="pa-6 pa-md-8">
              <v-form ref="formRef" @submit.prevent="handleSubmit">
                <!-- Personal Information -->
                <div class="mb-6">
                  <h3 class="text-h6 font-weight-bold mb-4">Informations personnelles</h3>

                  <v-text-field
                    v-model="form.username"
                    class="mb-3"
                    density="comfortable"
                    :error-messages="errors.username"
                    label="Nom d'utilisateur"
                    prepend-inner-icon="mdi-account"
                    :rules="[rules.required]"
                    style="border-radius: 12px"
                    variant="outlined"
                  />

                  <v-text-field
                    v-model="form.email"
                    class="mb-3"
                    density="comfortable"
                    :error-messages="errors.email"
                    label="Adresse email"
                    prepend-inner-icon="mdi-email"
                    :rules="[rules.required, rules.email]"
                    style="border-radius: 12px"
                    type="email"
                    variant="outlined"
                  />

                  <v-text-field
                    v-model="form.phone"
                    density="comfortable"
                    :error-messages="errors.phone"
                    label="Téléphone (optionnel)"
                    placeholder="+229 XX XX XX XX"
                    prepend-inner-icon="mdi-phone"
                    style="border-radius: 12px"
                    variant="outlined"
                  />
                </div>

                <v-divider class="my-6" />

                <!-- Password -->
                <div class="mb-6">
                  <h3 class="text-h6 font-weight-bold mb-4">Sécurité du compte</h3>

                  <v-text-field
                    v-model="form.password"
                    :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                    class="mb-3"
                    density="comfortable"
                    :error-messages="errors.password"
                    label="Mot de passe"
                    prepend-inner-icon="mdi-lock"
                    :rules="[rules.required, rules.minLength]"
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

                    <!-- Password Requirements -->
                    <div class="mt-3">
                      <div class="text-caption mb-1" :style="{ color: 'rgb(var(--v-theme-on-surface-variant))' }">
                        Le mot de passe doit contenir:
                      </div>
                      <div class="d-flex flex-column gap-1">
                        <div class="d-flex align-center gap-2">
                          <v-icon
                            :color="form.password.length >= 8 ? 'success' : 'grey'"
                            size="small"
                          >
                            {{ form.password.length >= 8 ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                          </v-icon>
                          <span class="text-caption">Au moins 8 caractères</span>
                        </div>
                        <div class="d-flex align-center gap-2">
                          <v-icon
                            :color="/[A-Z]/.test(form.password) && /[a-z]/.test(form.password) ? 'success' : 'grey'"
                            size="small"
                          >
                            {{ /[A-Z]/.test(form.password) && /[a-z]/.test(form.password) ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                          </v-icon>
                          <span class="text-caption">Lettres majuscules et minuscules</span>
                        </div>
                        <div class="d-flex align-center gap-2">
                          <v-icon
                            :color="/\d/.test(form.password) ? 'success' : 'grey'"
                            size="small"
                          >
                            {{ /\d/.test(form.password) ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                          </v-icon>
                          <span class="text-caption">Au moins un chiffre</span>
                        </div>
                        <div class="d-flex align-center gap-2">
                          <v-icon
                            :color="/[@$!%*?&amp;#]/.test(form.password) ? 'success' : 'grey'"
                            size="small"
                          >
                            {{ /[@$!%*?&amp;#]/.test(form.password) ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                          </v-icon>
                          <span class="text-caption">Au moins un caractère spécial (@$!%*?&amp;#)</span>
                        </div>
                      </div>
                    </div>
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
                  <v-icon class="mr-2" left>mdi-account-plus</v-icon>
                  Créer mon compte
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
        </div>

        <!-- Features Card -->
        <v-card
          class="mt-4"
          :color="'rgb(var(--v-theme-info))'"
          elevation="0"
          style="border-radius: 12px"
          variant="tonal"
        >
          <v-card-text>
            <div class="font-weight-bold mb-3">
              <v-icon class="mr-2" :color="'rgb(var(--v-theme-info))'">mdi-star</v-icon>
              Avec votre compte, vous pouvez:
            </div>
            <div class="d-flex flex-column gap-2">
              <div class="d-flex align-start gap-2">
                <v-icon :color="'rgb(var(--v-theme-info))'" size="small">mdi-check</v-icon>
                <span class="text-body-2">Déposer et suivre vos plaintes</span>
              </div>
              <div class="d-flex align-start gap-2">
                <v-icon :color="'rgb(var(--v-theme-info))'" size="small">mdi-check</v-icon>
                <span class="text-body-2">Répondre aux enquêtes de satisfaction</span>
              </div>
              <div class="d-flex align-start gap-2">
                <v-icon :color="'rgb(var(--v-theme-info))'" size="small">mdi-check</v-icon>
                <span class="text-body-2">Accéder à votre historique</span>
              </div>
              <div class="d-flex align-start gap-2">
                <v-icon :color="'rgb(var(--v-theme-info))'" size="small">mdi-check</v-icon>
                <span class="text-body-2">Recevoir des notifications en temps réel</span>
              </div>
            </div>
          </v-card-text>
        </v-card>

        <!-- Help Card -->
        <v-card
          class="mt-4"
          :color="'rgb(var(--v-theme-success))'"
          elevation="0"
          style="border-radius: 12px"
          variant="tonal"
        >
          <v-card-text>
            <div class="d-flex align-start gap-3">
              <v-icon :color="'rgb(var(--v-theme-success))'">mdi-help-circle</v-icon>
              <div>
                <div class="font-weight-bold mb-1">Besoin d'aide ?</div>
                <div class="text-body-2">
                  Contactez notre support à
                  <a class="text-decoration-none" href="mailto:support@BestQHSE.com">support@BestQHSE.com</a>
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
  import type { RegisterClientRequest } from '@/types/api'
  import { computed, reactive, ref } from 'vue'
  import { useAuth } from '@/composables/useAuth'
  import UnifiedLoader from '@/modules/shared/components/ui/UnifiedLoader.vue'

  const { registerClient, loading } = useAuth()

  const formRef = ref()
  const showPassword = ref(false)
  const showPasswordConfirm = ref(false)
  const acceptTerms = ref(false)

  const form = reactive<RegisterClientRequest>({
    username: '',
    email: '',
    password: '',
    password_confirmation: '',
    phone: '',
  })

  const errors = reactive<Record<string, string>>({})

  const rules = {
    required: (v: string) => !!v || 'Ce champ est requis',
    email: (v: string) => /.+@.+\..+/.test(v) || 'Email invalide',
    minLength: (v: string) => (v && v.length >= 8) || 'Minimum 8 caractères',
    passwordMatch: (v: string) => v === form.password || 'Les mots de passe ne correspondent pas',
    acceptTerms: (v: boolean) => v || 'Vous devez accepter les conditions',
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

    // Clear previous errors
    for (const key of Object.keys(errors)) delete errors[key]

    try {
      await registerClient(form)
    } catch (error: any) {
      if (error.validationErrors) {
        Object.assign(errors, error.validationErrors)
      }
    }
  }
</script>

<style scoped>
.individual-signup-loader-scope {
  position: relative;
}

.individual-signup-loader-overlay {
  position: absolute;
  inset: 0;
  z-index: 10;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(2px);
  background: rgba(255, 255, 255, 0.72);
  border-radius: 12px;
}

:deep(.v-field) {
  border-radius: 12px;
}

:deep(.v-input__details) {
  padding-top: 4px;
}
</style>
