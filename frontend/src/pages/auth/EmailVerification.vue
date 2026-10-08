<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-white flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8 border border-gray-200 verification-loader-scope">
      <div v-if="loading" class="verification-loader-overlay">
        <UnifiedLoader
          centered
          message="Traitement en cours..."
          size="sm"
          variant="spinner"
        />
      </div>
      <EmailVerificationHeader
        v-model:email="email"
        v-model:is-editing="isEditing"
        :message="message"
        @update-email="handleUpdateEmail"
      />

      <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <h4 class="font-medium text-blue-900 mb-2">Instructions :</h4>
        <ol class="text-sm text-blue-700 space-y-1 list-decimal list-inside">
          <li>Ouvrez votre boîte de réception</li>
          <li>Cliquez sur le lien dans l'email</li>
          <li>Votre compte sera activé automatiquement</li>
        </ol>
      </div>

      <EmailVerificationActions
        :loading="loading"
        :resent="resent"
        @logout="logout"
        @resend="handleResend"
        @verify="handleVerify"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
  import { ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { useToast } from 'vue-toastification'
  import api from '@/api/client'
  import UnifiedLoader from '@/modules/shared/components/ui/UnifiedLoader.vue'
  import EmailVerificationActions from '@/pages/auth/components/email-verification/EmailVerificationActions.vue'
  import EmailVerificationHeader from '@/pages/auth/components/email-verification/EmailVerificationHeader.vue'

  const route = useRoute()
  const router = useRouter()
  const toast = useToast()

  const email = ref(route.query.email as string || localStorage.getItem('pendingVerificationEmail') || '')
  const resent = ref(false)
  const isEditing = ref(false)
  const loading = ref(false)
  const message = ref(route.query.message as string || 'Un email de vérification vous a été envoyé')

  async function handleResend () {
    try {
      loading.value = true
      await api.post('/auth/resend-verification')
      resent.value = true
      toast.success('Email de vérification renvoyé avec succès')
      setTimeout(() => {
        resent.value = false
      }, 3000)
    } catch (error: any) {
      toast.error(error?.response?.data?.message || 'Erreur lors de l\'envoi de l\'email')
    } finally {
      loading.value = false
    }
  }

  function handleUpdateEmail () {
    isEditing.value = false
    // Sauvegarder le nouvel email
    localStorage.setItem('pendingVerificationEmail', email.value)
    toast.info('Pour changer votre email, veuillez contacter le support')
  }

  function handleVerify () {
    router.push('/auth/login')
  }

  function logout () {
    localStorage.removeItem('pendingVerificationEmail')
    router.push('/auth/login')
  }
</script>

<style scoped>
.verification-loader-scope {
  position: relative;
}

.verification-loader-overlay {
  position: absolute;
  inset: 0;
  z-index: 5;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(2px);
  background: rgba(255, 255, 255, 0.72);
}
</style>
