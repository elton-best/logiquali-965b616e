<template>
  <v-form ref="formRef" v-model="valid" @submit.prevent="emit('next')">
    <v-card flat>
      <v-card-text>
        <h3 class="text-h6 mb-4">Contact et sécurité</h3>
        <p class="text-body-2 text-medium-emphasis mb-6">
          Ces informations seront utilisées pour accéder à votre compte
        </p>

        <v-text-field
          v-model="email"
          class="mb-2"
          label="Adresse email professionnelle"
          prepend-inner-icon="mdi-email"
          required
          :rules="[rules.required, rules.email]"
          type="email"
          variant="outlined"
        />

        <v-text-field
          v-model="phone"
          class="mb-4"
          hint="Format international recommandé"
          label="Numéro de téléphone (optionnel)"
          persistent-hint
          placeholder="+229 XX XX XX XX"
          prepend-inner-icon="mdi-phone"
          type="tel"
          variant="outlined"
        />

        <v-text-field
          v-model="password"
          :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
          class="mb-2"
          counter
          label="Mot de passe"
          prepend-inner-icon="mdi-lock"
          required
          :rules="[rules.required, rules.minLength, rules.password]"
          :type="showPassword ? 'text' : 'password'"
          variant="outlined"
          @click:append-inner="showPassword = !showPassword"
        />

        <v-text-field
          v-model="confirmPassword"
          :append-inner-icon="showConfirmPassword ? 'mdi-eye-off' : 'mdi-eye'"
          class="mb-2"
          label="Confirmer le mot de passe"
          prepend-inner-icon="mdi-lock-check"
          required
          :rules="[rules.required, rules.passwordMatch]"
          :type="showConfirmPassword ? 'text' : 'password'"
          variant="outlined"
          @click:append-inner="showConfirmPassword = !showConfirmPassword"
        />

        <v-alert
          v-if="passwordStrength"
          class="mt-4"
          :color="passwordStrength.color"
          :icon="passwordStrength.icon"
          variant="tonal"
        >
          <div class="text-body-2">
            <strong>Force du mot de passe:</strong> {{ passwordStrength.text }}
          </div>
          <v-progress-linear
            class="mt-2"
            :color="passwordStrength.color"
            height="6"
            :model-value="passwordStrength.score"
            rounded
          />
        </v-alert>

        <v-card class="mt-4" color="info" variant="tonal">
          <v-card-text class="text-body-2">
            <div class="font-weight-bold mb-2">Critères du mot de passe:</div>
            <ul class="pl-4">
              <li :class="hasMinLength ? 'text-success' : ''">Au moins 8 caractères</li>
              <li :class="hasUpperCase ? 'text-success' : ''">Une lettre majuscule</li>
              <li :class="hasLowerCase ? 'text-success' : ''">Une lettre minuscule</li>
              <li :class="hasNumber ? 'text-success' : ''">Un chiffre</li>
              <li :class="hasSpecial ? 'text-success' : ''">Un caractère spécial (!@#$%^&*)</li>
            </ul>
          </v-card-text>
        </v-card>
      </v-card-text>
    </v-card>
  </v-form>
</template>

<script setup lang="ts">
  import type { ContactSecurity } from '@/types/kyc'
  import { computed, ref } from 'vue'

  const props = defineProps<{
    modelValue: ContactSecurity
  }>()

  const emit = defineEmits<{
    'update:modelValue': [value: ContactSecurity]
    'next': []
  }>()

  const formRef = ref()
  const valid = ref(false)
  const showPassword = ref(false)
  const showConfirmPassword = ref(false)

  const email = computed({
    get: () => props.modelValue.email,
    set: value => emit('update:modelValue', { ...props.modelValue, email: value }),
  })

  const phone = computed({
    get: () => props.modelValue.phone,
    set: value => emit('update:modelValue', { ...props.modelValue, phone: value }),
  })

  const password = computed({
    get: () => props.modelValue.password,
    set: value => emit('update:modelValue', { ...props.modelValue, password: value }),
  })

  const confirmPassword = computed({
    get: () => props.modelValue.confirmPassword,
    set: value => emit('update:modelValue', { ...props.modelValue, confirmPassword: value }),
  })

  const hasMinLength = computed(() => (props.modelValue.password?.length || 0) >= 8)
  const hasUpperCase = computed(() => /[A-Z]/.test(props.modelValue.password || ''))
  const hasLowerCase = computed(() => /[a-z]/.test(props.modelValue.password || ''))
  const hasNumber = computed(() => /[0-9]/.test(props.modelValue.password || ''))
  const hasSpecial = computed(() => /[!@#$%^&*(),.?":{}|<>]/.test(props.modelValue.password || ''))

  const passwordStrength = computed(() => {
    if (!props.modelValue.password) return null

    let score = 0
    if (hasMinLength.value) score += 20
    if (hasUpperCase.value) score += 20
    if (hasLowerCase.value) score += 20
    if (hasNumber.value) score += 20
    if (hasSpecial.value) score += 20

    if (score < 40) {
      return { score, color: 'error', text: 'Faible', icon: 'mdi-alert-circle' }
    } else if (score < 60) {
      return { score, color: 'warning', text: 'Moyen', icon: 'mdi-alert' }
    } else if (score < 100) {
      return { score, color: 'info', text: 'Bon', icon: 'mdi-check-circle' }
    } else {
      return { score, color: 'success', text: 'Excellent', icon: 'mdi-check-circle' }
    }
  })

  const rules = {
    required: (_value: string) => !!_value || 'Ce champ est requis',
    email: (_value: string) => {
      const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
      return pattern.test(_value) || 'Email invalide'
    },
    minLength: (_value: string) => {
      return (_value && _value.length >= 8) || 'Minimum 8 caractères requis'
    },
    password: (_value: string) => {
      if (!hasMinLength.value || !hasUpperCase.value || !hasLowerCase.value
        || !hasNumber.value || !hasSpecial.value) {
        return 'Le mot de passe doit respecter tous les critères'
      }
      return true
    },
    passwordMatch: (_value: string) => {
      return _value === props.modelValue.password || 'Les mots de passe ne correspondent pas'
    },
  }

  defineExpose({
    validate: () => formRef.value?.validate(),
    isValid: () => valid.value,
  })
</script>
