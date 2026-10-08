<template>
  <transition name="cookie-slide">
    <div v-if="isVisible" class="cookie-overlay">
      <div class="cookie-consent-card">
        <div class="cookie-accent" />

        <div class="cookie-header">
          <div class="cookie-title-wrap">
            <div class="cookie-icon-wrap">
              <v-icon color="white" size="18">mdi-cookie-outline</v-icon>
            </div>
            <div>
              <p class="cookie-title">Gestion des cookies</p>
              <p class="cookie-subtitle">Votre confidentialité, vos choix</p>
            </div>
          </div>
          <v-chip color="primary" size="small" variant="flat">{{ appName }}</v-chip>
        </div>

        <div class="cookie-body">
          <p>
            Nous utilisons des cookies nécessaires au bon fonctionnement de la plateforme, et des cookies
            optionnels pour améliorer votre expérience (mesure d'audience et préférences).
          </p>
          <p>
            Vous pouvez accepter, refuser ou personnaliser ces cookies à tout moment. Consultez
            <RouterLink class="cookie-link" to="/politique-confidentialite">la politique de confidentialité</RouterLink>.
          </p>
        </div>

        <div class="cookie-actions">
          <v-btn
            class="action-btn"
            color="primary"
            size="small"
            variant="flat"
            @click="acceptAll"
          >
            Tout accepter
          </v-btn>
          <v-btn
            class="action-btn"
            color="primary"
            size="small"
            variant="outlined"
            @click="toggleOptions"
          >
            {{ optionsOpen ? 'Masquer les options' : 'Personnaliser' }}
          </v-btn>
          <v-btn
            class="action-btn"
            color="grey-darken-1"
            size="small"
            variant="text"
            @click="rejectAll"
          >
            Tout refuser
          </v-btn>
        </div>

        <div v-if="optionsOpen" class="cookie-options">
          <v-divider class="cookie-divider" />
          <div class="cookie-option-grid">
            <div class="cookie-option-item">
              <div class="cookie-option-head">
                <v-icon color="primary" size="18">mdi-chart-line</v-icon>
                <span>Mesure d'audience</span>
              </div>
              <v-checkbox
                v-model="preferences.analytics"
                color="primary"
                density="comfortable"
                hide-details
                label="Autoriser"
              />
            </div>

            <div class="cookie-option-item">
              <div class="cookie-option-head">
                <v-icon color="primary" size="18">mdi-tune-variant</v-icon>
                <span>Préférences</span>
              </div>
              <v-checkbox
                v-model="preferences.functional"
                color="primary"
                density="comfortable"
                hide-details
                label="Autoriser"
              />
            </div>

            <div class="cookie-option-item cookie-option-item--locked">
              <div class="cookie-option-head">
                <v-icon color="primary" size="18">mdi-shield-check</v-icon>
                <span>Cookies nécessaires</span>
              </div>
              <v-checkbox
                color="primary"
                density="comfortable"
                disabled
                hide-details
                label="Toujours actifs"
                :model-value="true"
              />
            </div>
          </div>

          <div class="cookie-options-footer">
            <v-btn color="primary" size="small" variant="flat" @click="saveCustomPreferences">
              Enregistrer mes choix
            </v-btn>
          </div>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup lang="ts">
  import { onMounted, reactive, ref } from 'vue'

  type CookieConsentChoice = 'accept_all' | 'reject_all' | 'custom'

  interface CookieConsentState {
    choice: CookieConsentChoice
    analytics: boolean
    functional: boolean
    necessary: true
    savedAt: string
  }

  const COOKIE_CONSENT_KEY = 'BestQHSE_cookie_consent_v1'
  const appName = String(import.meta.env.VITE_APP_NAME || 'BestQHSE').trim() || 'BestQHSE'

  const isVisible = ref(false)
  const optionsOpen = ref(false)
  const preferences = reactive({
    analytics: true,
    functional: true,
  })

  function readStoredConsent (): CookieConsentState | null {
    try {
      const raw = localStorage.getItem(COOKIE_CONSENT_KEY)
      if (!raw) return null
      return JSON.parse(raw) as CookieConsentState
    } catch {
      return null
    }
  }

  function saveStoredConsent (payload: Omit<CookieConsentState, 'savedAt'>) {
    const value: CookieConsentState = {
      ...payload,
      savedAt: new Date().toISOString(),
    }
    localStorage.setItem(COOKIE_CONSENT_KEY, JSON.stringify(value))
  }

  function hideBanner () {
    isVisible.value = false
    optionsOpen.value = false
  }

  function acceptAll () {
    saveStoredConsent({
      choice: 'accept_all',
      analytics: true,
      functional: true,
      necessary: true,
    })
    hideBanner()
  }

  function rejectAll () {
    saveStoredConsent({
      choice: 'reject_all',
      analytics: false,
      functional: false,
      necessary: true,
    })
    hideBanner()
  }

  function toggleOptions () {
    optionsOpen.value = !optionsOpen.value
  }

  function saveCustomPreferences () {
    saveStoredConsent({
      choice: 'custom',
      analytics: Boolean(preferences.analytics),
      functional: Boolean(preferences.functional),
      necessary: true,
    })
    hideBanner()
  }

  onMounted(() => {
    const stored = readStoredConsent()
    if (!stored?.choice) {
      isVisible.value = true
      return
    }

    preferences.analytics = Boolean(stored.analytics)
    preferences.functional = Boolean(stored.functional)
  })
</script>

<style scoped>
.cookie-overlay {
  position: fixed;
  inset: auto 0 0 0;
  z-index: 2500;
  padding: 16px;
  pointer-events: none;
}

.cookie-consent-card {
  position: relative;
  max-width: 980px;
  margin: 0 auto;
  padding: 18px 18px 14px;
  border-radius: 20px;
  border: 1px solid rgba(148, 163, 184, 0.32);
  background: linear-gradient(120deg, #ffffff 0%, #f8fbff 55%, #eef6ff 100%);
  backdrop-filter: blur(8px);
  box-shadow: 0 18px 44px rgba(15, 23, 42, 0.14);
  color: #0f172a;
  pointer-events: auto;
  overflow: hidden;
}

.cookie-accent {
  position: absolute;
  left: 0;
  right: 0;
  top: 0;
  height: 4px;
  background: linear-gradient(90deg, #2563eb, #38bdf8, #22c55e);
}

.cookie-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.cookie-title-wrap {
  display: flex;
  align-items: center;
  gap: 10px;
}

.cookie-icon-wrap {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  display: grid;
  place-items: center;
  background: rgba(59, 130, 246, 0.25);
  border: 1px solid rgba(96, 165, 250, 0.45);
}

.cookie-title {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
  line-height: 1.2;
}

.cookie-subtitle {
  margin: 2px 0 0;
  font-size: 0.78rem;
  color: #64748b;
}

.cookie-body {
  margin-top: 12px;
  font-size: 0.9rem;
  line-height: 1.5;
  color: #334155;
}

.cookie-body p {
  margin: 0;
}

.cookie-body p + p {
  margin-top: 6px;
}

.cookie-link {
  color: #1d4ed8;
  text-decoration: underline;
  text-underline-offset: 2px;
}

.cookie-actions {
  margin-top: 14px;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.action-btn {
  text-transform: none;
  letter-spacing: 0;
  font-weight: 600;
}

.cookie-options {
  margin-top: 10px;
}

.cookie-divider {
  border-color: rgba(148, 163, 184, 0.44);
}

.cookie-option-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
  margin-top: 10px;
}

.cookie-option-item {
  background: rgba(255, 255, 255, 0.82);
  border: 1px solid rgba(148, 163, 184, 0.34);
  border-radius: 12px;
  padding: 10px;
}

.cookie-option-item--locked {
  background: rgba(241, 245, 249, 0.95);
}

.cookie-option-head {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.84rem;
  font-weight: 600;
  color: #1e293b;
}

.cookie-options-footer {
  margin-top: 10px;
  display: flex;
  justify-content: flex-end;
}

:deep(.cookie-option-item .v-label) {
  color: #334155;
  opacity: 1;
}

.cookie-slide-enter-active,
.cookie-slide-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}

.cookie-slide-enter-from,
.cookie-slide-leave-to {
  opacity: 0;
  transform: translateY(20px);
}

@media (max-width: 960px) {
  .cookie-overlay {
    padding: 12px;
  }

  .cookie-consent-card {
    border-radius: 16px;
    padding: 16px 14px 12px;
  }

  .cookie-option-grid {
    grid-template-columns: 1fr;
  }
}
</style>
