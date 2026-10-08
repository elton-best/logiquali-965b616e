<template>
  <Teleport to="body">
    <Transition name="consent-banner">
      <div v-if="showBanner" class="consent-banner" :class="position">
        <VCard class="consent-card" elevation="8">
          <VCardText>
            <div class="d-flex align-center">
              <VIcon class="mr-4" color="primary" icon="mdi-cookie" size="32" />

              <div class="flex-grow-1">
                <div class="text-h6 mb-2">{{ title }}</div>
                <div class="text-body-2 text-grey-darken-1">
                  {{ message }}
                  <a v-if="policyUrl" class="text-primary" :href="policyUrl" target="_blank">
                    En savoir plus
                  </a>
                </div>

                <!-- Détails consentements -->
                <VExpandTransition>
                  <div v-if="showDetails" class="mt-4">
                    <VDivider class="mb-3" />

                    <div v-for="consent in consentTypes" :key="consent.type" class="mb-3">
                      <div class="d-flex align-center justify-space-between">
                        <div class="flex-grow-1 mr-4">
                          <div class="font-weight-medium">{{ consent.label }}</div>
                          <div class="text-caption text-grey">{{ consent.description }}</div>
                        </div>

                        <VSwitch
                          v-model="consent.enabled"
                          color="primary"
                          density="compact"
                          :disabled="consent.required"
                          hide-details
                        />
                      </div>
                    </div>
                  </div>
                </VExpandTransition>
              </div>
            </div>
          </VCardText>

          <VCardActions>
            <VBtn
              v-if="!showDetails"
              variant="text"
              @click="showDetails = true"
            >
              Personnaliser
            </VBtn>

            <VBtn
              v-else
              variant="text"
              @click="showDetails = false"
            >
              Masquer
            </VBtn>

            <VSpacer />

            <VBtn
              variant="outlined"
              @click="rejectAll"
            >
              Tout refuser
            </VBtn>

            <VBtn
              color="primary"
              variant="flat"
              @click="acceptSelected"
            >
              {{ showDetails ? 'Enregistrer mes choix' : 'Tout accepter' }}
            </VBtn>
          </VCardActions>
        </VCard>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
  import { onMounted, ref } from 'vue'
  import api from '@/api/client'

  interface ConsentType {
    type: string
    label: string
    description: string
    enabled: boolean
    required: boolean
  }

  interface Props {
    title?: string
    message?: string
    policyUrl?: string
    position?: 'bottom' | 'top'
    expiryDays?: number
    consentVersion?: string
  }

  const props = withDefaults(defineProps<Props>(), {
    title: 'Nous utilisons des cookies',
    message: 'Nous utilisons des cookies et technologies similaires pour améliorer votre expérience, personnaliser le contenu et analyser l\'utilisation du site.',
    policyUrl: '/politique-confidentialite',
    position: 'bottom',
    expiryDays: 365,
    consentVersion: '1.0',
  })

  const emit = defineEmits<{
    accepted: [consents: Record<string, boolean>]
    rejected: []
  }>()

  const showBanner = ref(false)
  const showDetails = ref(false)
  const USE_HTTPONLY_COOKIE_AUTH = String(import.meta.env.VITE_AUTH_USE_HTTPONLY_COOKIE ?? 'false') === 'true'

  const consentTypes = ref<ConsentType[]>([
    {
      type: 'cookies',
      label: 'Cookies essentiels',
      description: 'Nécessaires au fonctionnement du site (authentification, préférences)',
      enabled: true,
      required: true,
    },
    {
      type: 'analytics',
      label: 'Cookies analytiques',
      description: 'Nous aident à comprendre comment vous utilisez le site',
      enabled: true,
      required: false,
    },
    {
      type: 'marketing',
      label: 'Cookies marketing',
      description: 'Utilisés pour personnaliser les publicités',
      enabled: false,
      required: false,
    },
    {
      type: 'data_processing',
      label: 'Traitement des données',
      description: 'Consentement au traitement de vos données personnelles selon le RGPD',
      enabled: true,
      required: false,
    },
  ])

  onMounted(() => {
    checkExistingConsent()
  })

  async function checkExistingConsent () {
    try {
      const token = USE_HTTPONLY_COOKIE_AUTH
        ? null
        : localStorage.getItem('access_token')

      if (!USE_HTTPONLY_COOKIE_AUTH && !token) {
        // Utilisateur non connecté, vérifier localStorage
        const localConsent = localStorage.getItem('consent_given')
        if (localConsent) {
          showBanner.value = false
          return
        }
        showBanner.value = true
        return
      }

      // Utilisateur connecté, vérifier via API
      const response = await api.get<Record<string, { is_valid: boolean }>>('/consents')
      const payload = response?.data ?? response ?? {}

      // Vérifier si tous les types requis ont un consentement valide
      const hasValidConsent = consentTypes.value.every(type => {
        if (!type.required) return true
        const consent = payload[type.type]
        return Boolean(consent?.is_valid)
      })

      showBanner.value = !hasValidConsent
    } catch (error) {
      console.error('Error checking consent:', error)
      showBanner.value = true
    }
  }

  async function acceptSelected () {
    const consentsToSave = consentTypes.value
      .filter(c => c.enabled)
      .map(c => c.type)

    await saveConsents(consentsToSave, true)

    emit('accepted', Object.fromEntries(
      consentTypes.value.map(c => [c.type, c.enabled]),
    ))

    showBanner.value = false
  }

  async function rejectAll () {
    // Garder uniquement les cookies essentiels
    const essentialOnly = consentTypes.value
      .filter(c => c.required)
      .map(c => c.type)

    await saveConsents(essentialOnly, false)

    emit('rejected')

    showBanner.value = false
  }

  async function saveConsents (types: string[], accepted: boolean) {
    const token = USE_HTTPONLY_COOKIE_AUTH
      ? null
      : localStorage.getItem('access_token')

    for (const type of types) {
      try {
        const consentData = {
          consent_type: type,
          consent_given: accepted,
          consent_version: props.consentVersion,
          expiry_days: props.expiryDays,
          consent_text: `${props.title}. ${props.message}`,
        }

        await api.post('/consents', consentData)
      } catch (error) {
        console.error(`Error saving consent for ${type}:`, error)
      }
    }

    // Enregistrer aussi en localStorage pour utilisateurs non connectés
    if (!USE_HTTPONLY_COOKIE_AUTH && !token) {
      localStorage.setItem('consent_given', accepted ? 'true' : 'essential_only')
      localStorage.setItem('consent_date', new Date().toISOString())
    }
  }
</script>

<style scoped>
.consent-banner {
  position: fixed;
  left: 0;
  right: 0;
  z-index: 9999;
  padding: 1rem;
}

.consent-banner.bottom {
  bottom: 0;
}

.consent-banner.top {
  top: 0;
}

.consent-card {
  max-width: 900px;
  margin: 0 auto;
}

/* Animations */
.consent-banner-enter-active,
.consent-banner-leave-active {
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.consent-banner-enter-from {
  transform: translateY(100%);
  opacity: 0;
}

.consent-banner-leave-to {
  transform: translateY(100%);
  opacity: 0;
}

.consent-banner.top.consent-banner-enter-from,
.consent-banner.top.consent-banner-leave-to {
  transform: translateY(-100%);
}
</style>
