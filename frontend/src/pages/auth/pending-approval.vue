<template>
  <v-container class="d-flex align-center justify-center" fill-height>
    <PendingApprovalCard
      :enterprise="enterprise"
      :status-message="statusMessage"
      @logout="handleLogout"
      @reapply="handleReapply"
    />
  </v-container>
</template>

<script setup lang="ts">
  import { computed, onMounted } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { useToast } from '@/modules/shared/composables/useToast'
  import PendingApprovalCard from '@/pages/auth/components/signup-status/PendingApprovalCard.vue'
  import { useAuthStore } from '@/stores/auth'

  const router = useRouter()
  const route = useRoute()
  const authStore = useAuthStore()
  const toast = useToast()

  const enterprise = computed(() => authStore.user?.enterprise)
  const status = computed(() => route.query.status as string || enterprise.value?.status || 'pending')
  const customMessage = computed(() => route.query.message as string || '')

  const statusMessage = computed(() => {
    // Si un message personnalisé est fourni, l'utiliser
    if (customMessage.value) {
      let title = 'Accès en attente'
      switch (status.value) {
        case 'pending': {
          title = 'Inscription en cours de validation'

          break
        }
        case 'rejected': {
          title = 'Inscription rejetée'

          break
        }
        case 'suspended': {
          title = 'Compte suspendu'

          break
        }
      // No default
      }
      return {
        title,
        message: customMessage.value,
      }
    }

    switch (status.value) {
      case 'pending': {
        return {
          title: 'Inscription en cours de validation',
          message: 'Votre demande d\'inscription est actuellement en cours de validation par nos équipes. Vous recevrez une notification par email dès que votre compte sera approuvé.',
        }
      }
      case 'rejected': {
        return {
          title: 'Inscription rejetée',
          message: 'Votre demande d\'inscription a été rejetée. Veuillez consulter la raison ci-dessous et modifier votre inscription si nécessaire.',
        }
      }
      case 'suspended': {
        return {
          title: 'Compte suspendu',
          message: 'Votre compte a été temporairement suspendu. Veuillez consulter la raison ci-dessous et contacter notre support pour plus d\'informations.',
        }
      }
      default: {
        return {
          title: 'Accès en attente',
          message: 'Votre compte n\'est pas encore activé. Veuillez patienter.',
        }
      }
    }
  })

  onMounted(() => {
    // If enterprise is active, redirect to dashboard
    if (enterprise.value?.status === 'active') {
      router.push('/company/dashboard')
    }
  })

  function handleLogout () {
    authStore.clearAuth()
    toast.success('Déconnexion réussie')
    router.push('/')
  }

  function handleReapply () {
    // TODO: Implement reapply logic
    toast.info('Fonctionnalité bientôt disponible')
  }
</script>

<style scoped>
.gap-4 {
  gap: 1rem;
}
</style>
