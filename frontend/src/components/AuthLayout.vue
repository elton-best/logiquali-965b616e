<template>
  <div class="auth-layout">
    <!-- Left Panel - Branding -->
    <div class="brand-panel">
      <div class="brand-content">
        <!-- Logo & Title -->
        <div class="brand-header">
          <AppLogo clickable subtitle="Votre solution complète de gestion qualité" surface="dark" variant="full" />
          <p class="brand-tagline">{{ tagline }}</p>
        </div>

        <!-- Illustration -->
        <div class="illustration-section">
          <div class="floating-card card-1">
            <v-icon color="#4471c4" size="32">{{ feature1Icon }}</v-icon>
            <div class="card-text">
              <div class="card-title">{{ feature1Title }}</div>
              <div class="card-subtitle">{{ feature1Subtitle }}</div>
            </div>
          </div>

          <div class="floating-card card-2">
            <v-icon color="#10b981" size="32">{{ feature2Icon }}</v-icon>
            <div class="card-text">
              <div class="card-title">{{ feature2Title }}</div>
              <div class="card-subtitle">{{ feature2Subtitle }}</div>
            </div>
          </div>

          <div class="floating-card card-3">
            <v-icon color="#f59e0b" size="32">{{ feature3Icon }}</v-icon>
            <div class="card-text">
              <div class="card-title">{{ feature3Title }}</div>
              <div class="card-subtitle">{{ feature3Subtitle }}</div>
            </div>
          </div>

          <!-- Central Icon -->
          <div class="central-circle">
            <div class="circle-content">
              <v-icon color="white" size="80">{{ centralIcon }}</v-icon>
            </div>
          </div>
        </div>

        <!-- Stats -->
        <div class="stats-section">
          <div class="stat-item">
            <div class="stat-number">500+</div>
            <div class="stat-label">Entreprises</div>
          </div>
          <div class="stat-divider" />
          <div class="stat-item">
            <div class="stat-number">50K+</div>
            <div class="stat-label">Utilisateurs</div>
          </div>
          <div class="stat-divider" />
          <div class="stat-item">
            <div class="stat-number">99.9%</div>
            <div class="stat-label">Disponibilité</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Panel - Form -->
    <div class="form-panel">
      <div class="form-wrapper">
        <!-- Back Button -->
        <v-btn
          class="back-btn"
          icon
          size="small"
          variant="text"
          @click="$router.push('/')"
        >
          <v-icon>mdi-arrow-left</v-icon>
        </v-btn>

        <!-- Slot for form content -->
        <slot />

        <!-- Trust Badges -->
        <div class="trust-badges">
          <div class="badge-item">
            <v-icon color="#10b981" size="18">mdi-shield-check</v-icon>
            <span>Sécurisé SSL</span>
          </div>
          <div class="badge-item">
            <v-icon color="#4471c4" size="18">mdi-lock</v-icon>
            <span>RGPD Conforme</span>
          </div>
          <div class="badge-item">
            <v-icon color="#f59e0b" size="18">mdi-check-decagram</v-icon>
            <span>ISO Certifié</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import AppLogo from '@/components/branding/AppLogo.vue'

  interface Props {
    tagline?: string
    centralIcon?: string
    feature1Icon?: string
    feature1Title?: string
    feature1Subtitle?: string
    feature2Icon?: string
    feature2Title?: string
    feature2Subtitle?: string
    feature3Icon?: string
    feature3Title?: string
    feature3Subtitle?: string
  }

  withDefaults(defineProps<Props>(), {
    tagline: 'Votre solution complète de gestion qualité',
    centralIcon: 'mdi-account-circle',
    feature1Icon: 'mdi-shield-check',
    feature1Title: 'Sécurité Maximale',
    feature1Subtitle: 'Données cryptées',
    feature2Icon: 'mdi-chart-line',
    feature2Title: 'Suivi en Temps Réel',
    feature2Subtitle: 'Analytics avancés',
    feature3Icon: 'mdi-account-group',
    feature3Title: 'Collaboration',
    feature3Subtitle: 'Équipe connectée',
  })
</script>

<style scoped lang="scss">
.auth-layout {
  display: flex;
  min-height: 100vh;
  background: #ffffff;
}

// ============= BRAND PANEL =============
.brand-panel {
  flex: 1;
  background: linear-gradient(135deg, #4471c4 0%, #2a4a8f 100%);
  padding: 3rem;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  position: relative;
  overflow: hidden;

  &::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 1px, transparent 1px);
    background-size: 30px 30px;
    animation: grid-move 20s linear infinite;
  }
}

@keyframes grid-move {
  0% { transform: translate(0, 0); }
  100% { transform: translate(30px, 30px); }
}

.brand-content {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  height: 100%;
}

.brand-header {
  margin-bottom: 2rem;
}

.logo-wrapper {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
}

.brand-name {
  color: white;
  font-size: 2rem;
  font-weight: 700;
  letter-spacing: -0.5px;
  margin: 0;
}

.brand-tagline {
  color: rgba(255, 255, 255, 0.9);
  font-size: 1.125rem;
  margin: 1rem 0 0;
  max-width: 400px;
}

// Illustration
.illustration-section {
  position: relative;
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}

.floating-card {
  position: absolute;
  background: white;
  border-radius: 16px;
  padding: 1.25rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
  animation: float 3s ease-in-out infinite;
}

.card-1 {
  top: 10%;
  left: 10%;
  animation-delay: 0s;
}

.card-2 {
  top: 50%;
  right: 10%;
  animation-delay: 1s;
}

.card-3 {
  bottom: 15%;
  left: 15%;
  animation-delay: 2s;
}

@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-20px); }
}

.card-text {
  flex: 1;
}

.card-title {
  font-weight: 600;
  font-size: 0.9375rem;
  color: #1a1a1a;
  margin-bottom: 0.25rem;
}

.card-subtitle {
  font-size: 0.8125rem;
  color: #64748b;
}

.central-circle {
  width: 200px;
  height: 200px;
  border-radius: 50%;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0.1));
  backdrop-filter: blur(10px);
  border: 2px solid rgba(255, 255, 255, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  animation: pulse 4s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% {
    transform: scale(1);
    box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.4);
  }
  50% {
    transform: scale(1.05);
    box-shadow: 0 0 0 20px rgba(255, 255, 255, 0);
  }
}

.circle-content {
  animation: rotate 20s linear infinite;
}

@keyframes rotate {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

// Stats
.stats-section {
  display: flex;
  gap: 2rem;
}

.stat-item {
  flex: 1;
}

.stat-number {
  color: white;
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
}

.stat-label {
  color: rgba(255, 255, 255, 0.8);
  font-size: 0.875rem;
}

.stat-divider {
  width: 1px;
  background: rgba(255, 255, 255, 0.2);
}

// ============= FORM PANEL =============
.form-panel {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  background: #fafafa;
  overflow-y: auto;
}

.form-wrapper {
  width: 100%;
  max-width: 600px;
  background: white;
  border-radius: 24px;
  padding: 2.5rem;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
  position: relative;
}

.back-btn {
  position: absolute;
  top: 1.5rem;
  left: 1.5rem;
}

// Trust Badges
.trust-badges {
  display: flex;
  justify-content: center;
  gap: 1.5rem;
  margin-top: 1.5rem;
  padding-top: 1.25rem;
  border-top: 1px solid #e2e8f0;
}

.badge-item {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  color: #64748b;
  font-size: 0.75rem;
  font-weight: 600;
}

// Responsive
@media (max-width: 1024px) {
  .brand-panel {
    display: none;
  }

  .form-panel {
    flex: 1;
  }
}

@media (max-width: 640px) {
  .form-panel {
    padding: 1rem;
  }

  .form-wrapper {
    padding: 2rem 1.5rem;
  }

  .trust-badges {
    flex-direction: column;
    gap: 0.75rem;
    align-items: center;
  }
}
</style>
