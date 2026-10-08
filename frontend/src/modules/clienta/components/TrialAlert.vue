<template>
  <v-alert
    v-if="showAlert"
    class="mb-4"
    closable
    :color="alertColor"
    :type="alertType"
    variant="tonal"
    @click:close="dismiss"
  >
    <template #prepend>
      <v-icon :icon="alertIcon" size="24" />
    </template>

    <div class="d-flex align-center justify-space-between">
      <div>
        <div class="text-subtitle-1 font-weight-bold mb-1">
          {{ alertTitle }}
        </div>
        <div class="text-body-2">
          {{ alertMessage }}
        </div>
      </div>

      <v-btn
        :color="alertColor"
        size="small"
        variant="elevated"
        @click="goToSubscription"
      >
        Renouveler
      </v-btn>
    </div>
  </v-alert>
</template>

<script setup lang="ts">
  import { computed } from 'vue'
  import { useRouter } from 'vue-router'
  import { useSubscriptionStore } from '@/stores/subscriptionStore'

  const router = useRouter()
  const subscriptionStore = useSubscriptionStore()

  const showAlert = computed(() => {
    return subscriptionStore.isInTrial && subscriptionStore.daysRemaining <= 7
  })

  const alertType = computed(() => {
    const days = subscriptionStore.daysRemaining
    if (days <= 3) return 'error'
    if (days <= 7) return 'warning'
    return 'info'
  })

  const alertColor = computed(() => {
    const days = subscriptionStore.daysRemaining
    if (days <= 3) return 'error'
    if (days <= 7) return 'warning'
    return 'info'
  })

  const alertIcon = computed(() => {
    const days = subscriptionStore.daysRemaining
    if (days <= 3) return 'mdi-alert-circle'
    return 'mdi-information'
  })

  const alertTitle = computed(() => {
    const days = subscriptionStore.daysRemaining
    if (days === 0) return 'Votre période d\'essai expire aujourd\'hui'
    if (days === 1) return 'Votre période d\'essai expire demain'
    return `Votre période d'essai expire dans ${days} jours`
  })

  const alertMessage = computed(() => {
    return 'Pour continuer à profiter de tous les modules, veuillez renouveler votre abonnement.'
  })

  function goToSubscription () {
    router.push('/company/subscription')
  }

  function dismiss () {
  // Optionnel: marquer l'alerte comme lue
  }
</script>
