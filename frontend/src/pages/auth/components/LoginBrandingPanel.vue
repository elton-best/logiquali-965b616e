<template>
  <v-col class="d-none d-lg-flex branding-panel" cols="12" lg="6">
    <div class="branding-content">
      <div class="logo-card" data-aos="fade-down">
        <AppLogo framed stacked surface="dark" variant="full" />
        <p class="brand-subtitle">Solution Complète de Gestion SMI</p>
        <div class="brand-badges">
          <span class="mini-badge">ISO 9001</span>
          <span class="mini-badge">ISO 14001</span>
          <span class="mini-badge">ISO 45001</span>
        </div>
      </div>

      <div class="features-grid" data-aos="fade-up" data-aos-delay="200">
        <div class="feature-card">
          <div class="feature-icon">
            <v-icon color="white" size="28">mdi-certificate-outline</v-icon>
          </div>
          <h3 class="feature-title">ISO 9001, 14001, 45001</h3>
          <p class="feature-text">Conformité multi-normes garantie</p>
        </div>

        <div class="feature-card">
          <div class="feature-icon">
            <v-icon color="white" size="28">mdi-file-document-multiple-outline</v-icon>
          </div>
          <h3 class="feature-title">GED Intelligente</h3>
          <p class="feature-text">Documents versionnés et tracés</p>
        </div>

        <div class="feature-card">
          <div class="feature-icon">
            <v-icon color="white" size="28">mdi-chart-line</v-icon>
          </div>
          <h3 class="feature-title">Pilotage Temps Réel</h3>
          <p class="feature-text">Indicateurs et audits consolidés</p>
        </div>

        <div class="feature-card">
          <div class="feature-icon">
            <v-icon color="white" size="28">mdi-account-group</v-icon>
          </div>
          <h3 class="feature-title">Collaboration Équipe</h3>
          <p class="feature-text">Rôles et permissions maîtrisés</p>
        </div>
      </div>

      <div class="trust-badge" data-aos="fade-up" data-aos-delay="400">
        <v-icon color="white" size="20">mdi-office-building-check</v-icon>
        <span>+500 entreprises certifiées ISO</span>
      </div>

      <div class="stats-row" data-aos="fade-up" data-aos-delay="500">
        <div class="stat-item">
          <div class="stat-number">15K+</div>
          <div class="stat-label">Audits réalisés</div>
        </div>
        <div class="stat-divider" />
        <div class="stat-item">
          <div class="stat-number">98%</div>
          <div class="stat-label">Taux de réussite</div>
        </div>
        <div class="stat-divider" />
        <div class="stat-item">
          <div class="stat-number">24/7</div>
          <div class="stat-label">Support expert</div>
        </div>
      </div>
    </div>
  </v-col>
</template>

<script setup lang="ts">
  import AppLogo from '@/components/branding/AppLogo.vue'
</script>
