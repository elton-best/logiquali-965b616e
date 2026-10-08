<template>
  <div class="force-password-page">
    <v-container class="fill-height d-flex align-center justify-center" fluid>
      <v-card class="force-password-card" max-width="560" rounded="xl">
        <v-card-title class="text-h5 font-weight-bold pt-8 px-8">
          Changement de mot de passe requis
        </v-card-title>
        <v-card-subtitle class="px-8 pb-2">
          Pour sécuriser votre compte, vous devez définir un nouveau mot de passe avant de continuer.
        </v-card-subtitle>

        <v-card-text class="px-8 pb-8">
          <v-alert
            v-if="blockingMessage"
            class="mb-4"
            color="warning"
            icon="mdi-alert-circle-outline"
            variant="tonal"
          >
            {{ blockingMessage }}
          </v-alert>

          <v-alert
            v-if="errorMessage"
            class="mb-4"
            color="error"
            variant="tonal"
          >
            {{ errorMessage }}
          </v-alert>

          <v-form @submit.prevent="submit">
            <v-text-field
              v-model="password"
              autocomplete="new-password"
              class="mb-3"
              :disabled="saving"
              label="Nouveau mot de passe"
              minlength="8"
              :rules="[rules.required, rules.minLength]"
              type="password"
              variant="outlined"
            />

            <v-text-field
              v-model="passwordConfirmation"
              autocomplete="new-password"
              :disabled="saving"
              label="Confirmer le mot de passe"
              :rules="[rules.required, rules.matches]"
              type="password"
              variant="outlined"
            />

            <v-btn
              block
              class="mt-4"
              color="primary"
              :loading="saving"
              size="large"
              type="submit"
            >
              Enregistrer et continuer
            </v-btn>
          </v-form>
        </v-card-text>
      </v-card>
    </v-container>
  </div>
</template>

<script setup lang="ts">
  import { computed, ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import api from '@/api/client'
  import { useToast } from '@/modules/shared/composables/useToast'
  import { useAuthStore } from '@/stores/auth'
  import { getBlockingMessage } from '@/utils/blockingAccess'

  const route = useRoute()
  const router = useRouter()
  const toast = useToast()
  const authStore = useAuthStore()

  const password = ref('')
  const passwordConfirmation = ref('')
  const saving = ref(false)
  const errorMessage = ref('')

  const blockingMessage = computed(() => getBlockingMessage(
    typeof route.query.blocking === 'string' ? route.query.blocking : undefined,
  ))

  const rules = {
    required: (v: string) => !!String(v || '').trim() || 'Ce champ est requis',
    minLength: (v: string) => String(v || '').length >= 8 || 'Minimum 8 caractères',
    matches: (v: string) => String(v || '') === password.value || 'Les mots de passe ne correspondent pas',
  }

  async function submit () {
    if (saving.value) {
      return
    }

    if (!rules.required(password.value) || !rules.minLength(password.value)) {
      errorMessage.value = 'Le nouveau mot de passe doit contenir au moins 8 caractères.'
      return
    }

    if (!rules.required(passwordConfirmation.value) || !rules.matches(passwordConfirmation.value)) {
      errorMessage.value = 'La confirmation du mot de passe est invalide.'
      return
    }

    try {
      saving.value = true
      errorMessage.value = ''

      await api.put('/auth/profile', {
        password: password.value,
        password_confirmation: passwordConfirmation.value,
      })

      authStore.updateUser({
        must_change_password: false,
        password_changed_at: new Date().toISOString(),
      })

      toast.success('Mot de passe mis à jour avec succès.')
      await router.replace(authStore.getDashboardRoute())
    } catch (error: any) {
      errorMessage.value = error?.response?.data?.message || 'Impossible de mettre à jour le mot de passe.'
    } finally {
      saving.value = false
    }
  }
</script>

<style scoped>
.force-password-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #f7f9ff 0%, #eef3ff 100%);
}

.force-password-card {
  width: 100%;
}
</style>
