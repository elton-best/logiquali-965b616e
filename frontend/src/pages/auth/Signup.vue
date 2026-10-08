<template>
  <v-app>
    <v-main class="bg-grey-lighten-4 signup-main">
      <div v-if="isNavigating" class="signup-loader-overlay">
        <UnifiedLoader
          description="Ouverture du formulaire d'inscription"
          title="Chargement..."
          variant="global"
        />
      </div>
      <v-container class="fill-height" fluid>
        <v-row align="center" justify="center">
          <v-col cols="12" lg="8" md="10">
            <SignupLandingHeader />

            <!-- User Type Selection Cards -->
            <v-row>
              <v-col cols="12" md="6">
                <v-card
                  :border="selectedType === 'company' ? 'primary md' : undefined"
                  :class="['cursor-pointer transition-all', selectedType === 'company' ? 'border-primary' : '']"
                  :elevation="selectedType === 'company' ? 8 : 2"
                  height="100%"
                  hover
                  rounded="lg"
                  @click="handleSelection('company')"
                >
                  <v-card-text class="pa-6">
                    <div class="text-center mb-4">
                      <v-avatar
                        class="mb-4 elevation-4"
                        :color="selectedType === 'company' ? 'primary' : 'grey-lighten-2'"
                        size="96"
                      >
                        <v-icon :color="selectedType === 'company' ? 'white' : 'grey'" size="56">
                          mdi-office-building-cog
                        </v-icon>
                      </v-avatar>
                      <h2 class="text-h4 font-weight-bold mb-2">Entreprise</h2>
                      <v-chip
                        v-if="selectedType === 'company'"
                        class="mb-3"
                        color="primary"
                        size="default"
                      >
                        <v-icon size="small" start>mdi-check-circle</v-icon>
                        Sélectionné
                      </v-chip>
                      <p class="text-subtitle-1 text-primary font-weight-medium mb-3">
                        Solution SMI Complète
                      </p>
                    </div>

                    <p class="text-body-1 mb-4 text-medium-emphasis">
                      Pilotez votre Système de Management Intégré et obtenez vos certifications ISO
                    </p>

                    <v-list class="bg-transparent" density="compact">
                      <v-list-item class="px-0" prepend-icon="mdi-check-circle">
                        <v-list-item-title class="text-body-2 font-weight-medium">
                          Gestion documentaire ISO 9001/14001/45001
                        </v-list-item-title>
                      </v-list-item>
                      <v-list-item class="px-0" prepend-icon="mdi-check-circle">
                        <v-list-item-title class="text-body-2 font-weight-medium">
                          Audits internes & externes automatisés
                        </v-list-item-title>
                      </v-list-item>
                      <v-list-item class="px-0" prepend-icon="mdi-check-circle">
                        <v-list-item-title class="text-body-2 font-weight-medium">
                          Tableaux de bord conformité en temps réel
                        </v-list-item-title>
                      </v-list-item>
                      <v-list-item class="px-0" prepend-icon="mdi-check-circle">
                        <v-list-item-title class="text-body-2 font-weight-medium">
                          Support expert dédié
                        </v-list-item-title>
                      </v-list-item>
                    </v-list>

                    <v-divider class="my-4" />

                    <v-alert
                      class="mt-4"
                      color="info"
                      density="comfortable"
                      icon="mdi-shield-account"
                      variant="tonal"
                    >
                      <div class="text-body-2 font-weight-medium">
                        <v-icon class="mr-1" size="small">mdi-clock-outline</v-icon>
                        Validation KYC sous 24-48h
                      </div>
                      <div class="text-caption mt-1">
                        Vérification conforme aux normes de sécurité
                      </div>
                    </v-alert>
                  </v-card-text>
                </v-card>
              </v-col>

              <v-col cols="12" md="6">
                <v-card
                  :border="selectedType === 'individual' ? 'primary md' : undefined"
                  :class="['cursor-pointer transition-all', selectedType === 'individual' ? 'border-primary' : '']"
                  :elevation="selectedType === 'individual' ? 8 : 2"
                  height="100%"
                  hover
                  rounded="lg"
                  @click="handleSelection('individual')"
                >
                  <v-card-text class="pa-6">
                    <div class="text-center mb-4">
                      <v-avatar
                        class="mb-4 elevation-4"
                        :color="selectedType === 'individual' ? 'success' : 'grey-lighten-2'"
                        size="96"
                      >
                        <v-icon :color="selectedType === 'individual' ? 'white' : 'grey'" size="56">
                          mdi-account-voice
                        </v-icon>
                      </v-avatar>
                      <h2 class="text-h4 font-weight-bold mb-2">Particulier</h2>
                      <v-chip
                        v-if="selectedType === 'individual'"
                        class="mb-3"
                        color="success"
                        size="default"
                      >
                        <v-icon size="small" start>mdi-check-circle</v-icon>
                        Sélectionné
                      </v-chip>
                      <p class="text-subtitle-1 text-success font-weight-medium mb-3">
                        Espace Feedback Client
                      </p>
                    </div>

                    <p class="text-body-1 mb-4 text-medium-emphasis">
                      Partagez votre expérience et contribuez à l'amélioration continue
                    </p>

                    <v-list class="bg-transparent" density="compact">
                      <v-list-item class="px-0" prepend-icon="mdi-message-alert">
                        <v-list-item-title class="text-body-2 font-weight-medium">
                          Déposer des réclamations en ligne
                        </v-list-item-title>
                      </v-list-item>
                      <v-list-item class="px-0" prepend-icon="mdi-chart-box-outline">
                        <v-list-item-title class="text-body-2 font-weight-medium">
                          Participer aux enquêtes de satisfaction
                        </v-list-item-title>
                      </v-list-item>
                      <v-list-item class="px-0" prepend-icon="mdi-eye-outline">
                        <v-list-item-title class="text-body-2 font-weight-medium">
                          Suivre le traitement de vos demandes
                        </v-list-item-title>
                      </v-list-item>
                      <v-list-item class="px-0" prepend-icon="mdi-bell-ring">
                        <v-list-item-title class="text-body-2 font-weight-medium">
                          Recevoir des notifications automatiques
                        </v-list-item-title>
                      </v-list-item>
                    </v-list>

                    <v-divider class="my-4" />

                    <v-alert
                      class="mt-4"
                      color="success"
                      density="comfortable"
                      icon="mdi-rocket-launch"
                      variant="tonal"
                    >
                      <div class="text-body-2 font-weight-medium">
                        <v-icon class="mr-1" size="small">mdi-email-fast</v-icon>
                        Activation instantanée
                      </div>
                      <div class="text-caption mt-1">
                        Vérifiez votre email et commencez immédiatement
                      </div>
                    </v-alert>
                  </v-card-text>
                </v-card>
              </v-col>
            </v-row>
            <!-- Divider -->
            <v-divider class="my-8" />

            <SignupLoginLink @login="$router.push('/auth/login')" />
          </v-col>
        </v-row>
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup lang="ts">
  import { ref } from 'vue'
  import { useRouter } from 'vue-router'
  import UnifiedLoader from '@/modules/shared/components/ui/UnifiedLoader.vue'
  import SignupLandingHeader from '@/pages/auth/components/SignupLandingHeader.vue'
  import SignupLoginLink from '@/pages/auth/components/SignupLoginLink.vue'

  const router = useRouter()
  const selectedType = ref<'company' | 'individual' | null>(null)
  const isNavigating = ref(false)

  // Une seule fonction qui gère directement la navigation
  async function handleSelection (type: 'company' | 'individual') {
    if (isNavigating.value) {
      return
    }

    selectedType.value = type
    isNavigating.value = true

    await (type === 'company' ? router.push('/auth/signup/company') : router.push('/auth/signup/individual'))
  }
</script>

<style scoped>
.signup-main {
  position: relative;
}

.signup-loader-overlay {
  position: absolute;
  inset: 0;
  z-index: 20;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(2px);
  background: rgba(248, 250, 252, 0.72);
}

.cursor-pointer {
  cursor: pointer;
}

.transition-all {
  transition: all 0.3s ease;
}
</style>
