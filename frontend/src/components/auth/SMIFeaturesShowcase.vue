<template>
  <div class="smi-features-showcase">
    <!-- Main Features Grid -->
    <v-row class="mb-8">
      <v-col
        v-for="feature in mainFeatures"
        :key="feature.id"
        cols="12"
        md="3"
        sm="6"
      >
        <div class="feature-showcase-card hover-lift">
          <div class="feature-icon-wrapper" :style="{ background: feature.gradient }">
            <v-icon :color="feature.iconColor" size="32">{{ feature.icon }}</v-icon>
          </div>
          <h3 class="feature-showcase-title">{{ feature.title }}</h3>
          <p class="feature-showcase-description">{{ feature.description }}</p>
          <div v-if="feature.metric" class="feature-metric">
            <span class="metric-value">{{ feature.metric.value }}</span>
            <span class="metric-label">{{ feature.metric.label }}</span>
          </div>
        </div>
      </v-col>
    </v-row>

    <!-- ISO Certifications Row -->
    <div class="iso-certifications-row">
      <h3 class="section-title">Certifications ISO Supportées</h3>
      <div class="certifications-grid">
        <div
          v-for="cert in certifications"
          :key="cert.code"
          class="certification-card"
          :class="`certification-${cert.code.toLowerCase().replace(' ', '-')}`"
        >
          <v-icon :color="cert.color" size="28">{{ cert.icon }}</v-icon>
          <div class="cert-info">
            <div class="cert-code">{{ cert.code }}</div>
            <div class="cert-name">{{ cert.name }}</div>
          </div>
          <v-chip :color="cert.color" size="x-small" variant="flat">
            {{ cert.companies }}+ entreprises
          </v-chip>
        </div>
      </div>
    </div>

    <!-- Success Stories -->
    <div class="success-metrics mt-8">
      <div v-for="metric in successMetrics" :key="metric.label" class="metric-card">
        <div class="metric-icon">
          <v-icon :color="metric.color" size="24">{{ metric.icon }}</v-icon>
        </div>
        <div class="metric-value gradient-text-primary">{{ metric.value }}</div>
        <div class="metric-label">{{ metric.label }}</div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { ref } from 'vue'

  const mainFeatures = ref([
    {
      id: 1,
      icon: 'mdi-file-document-multiple-outline',
      iconColor: 'white',
      title: 'GED Intelligente',
      description: 'Gestion documentaire conforme ISO avec versioning automatique',
      gradient: 'linear-gradient(135deg, #4471c4, #2a4a8f)',
      metric: {
        value: '50K+',
        label: 'Documents gérés',
      },
    },
    {
      id: 2,
      icon: 'mdi-clipboard-check-multiple-outline',
      iconColor: 'white',
      title: 'Audits Automatisés',
      description: 'Planification et suivi des audits internes/externes',
      gradient: 'linear-gradient(135deg, #5b8dd9, #4471c4)',
      metric: {
        value: '15K+',
        label: 'Audits réalisés',
      },
    },
    {
      id: 3,
      icon: 'mdi-chart-timeline-variant',
      iconColor: 'white',
      title: 'Tableaux de Bord',
      description: 'Analytics temps réel de votre conformité',
      gradient: 'linear-gradient(135deg, #72a8ed, #5b8dd9)',
      metric: {
        value: '98%',
        label: 'Taux de conformité',
      },
    },
    {
      id: 4,
      icon: 'mdi-account-group-outline',
      iconColor: 'white',
      title: 'Collaboration',
      description: 'Travail d\'équipe simplifié et sécurisé',
      gradient: 'linear-gradient(135deg, #89bef5, #72a8ed)',
      metric: {
        value: '500+',
        label: 'Entreprises actives',
      },
    },
  ])

  const certifications = ref([
    {
      code: 'ISO 9001',
      name: 'Management Qualité',
      icon: 'mdi-certificate-outline',
      color: '#0066cc',
      companies: 350,
    },
    {
      code: 'ISO 14001',
      name: 'Management Environnemental',
      icon: 'mdi-leaf',
      color: '#00a651',
      companies: 280,
    },
    {
      code: 'ISO 45001',
      name: 'Santé & Sécurité',
      icon: 'mdi-shield-account',
      color: '#ffa500',
      companies: 220,
    },
  ])

  const successMetrics = ref([
    {
      icon: 'mdi-office-building-check',
      value: '500+',
      label: 'Entreprises certifiées',
      color: '#4471c4',
    },
    {
      icon: 'mdi-chart-line',
      value: '98%',
      label: 'Taux de réussite',
      color: '#00a651',
    },
    {
      icon: 'mdi-clock-fast',
      value: '-40%',
      label: 'Temps de certification',
      color: '#5b8dd9',
    },
    {
      icon: 'mdi-headset',
      value: '24/7',
      label: 'Support expert',
      color: '#ffa500',
    },
  ])
</script>

<style scoped lang="scss">
.smi-features-showcase {
  padding: 2rem 0;
}

.feature-showcase-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 2rem 1.5rem;
  height: 100%;
  text-align: center;
  transition: all 0.3s ease;

  &:hover {
    border-color: #4471c4;
    box-shadow: 0 8px 24px rgba(68, 113, 196, 0.15);
  }
}

.feature-icon-wrapper {
  width: 72px;
  height: 72px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem;
  box-shadow: 0 4px 16px rgba(68, 113, 196, 0.3);
}

.feature-showcase-title {
  font-size: 1.125rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 0.75rem;
}

.feature-showcase-description {
  font-size: 0.875rem;
  color: #64748b;
  line-height: 1.5;
  margin-bottom: 1rem;
}

.feature-metric {
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
  display: flex;
  align-items: baseline;
  justify-content: center;
  gap: 0.5rem;
}

.metric-value {
  font-size: 1.5rem;
  font-weight: 800;
  background: linear-gradient(135deg, #4471c4, #5b8dd9);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}

.metric-label {
  font-size: 0.75rem;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

// ISO Certifications
.iso-certifications-row {
  background: linear-gradient(135deg, rgba(68, 113, 196, 0.03), rgba(91, 141, 217, 0.05));
  border-radius: 20px;
  padding: 2rem;
  margin-top: 2rem;
}

.section-title {
  text-align: center;
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 1.5rem;
}

.certifications-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1rem;
}

.certification-card {
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  transition: all 0.3s ease;

  &:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
  }
}

.cert-info {
  flex: 1;
}

.cert-code {
  font-size: 1rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 0.25rem;
}

.cert-name {
  font-size: 0.8rem;
  color: #64748b;
}

// Success Metrics
.success-metrics {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
}

.metric-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 1.5rem;
  text-align: center;
  transition: all 0.3s ease;

  &:hover {
    border-color: #4471c4;
    box-shadow: 0 4px 16px rgba(68, 113, 196, 0.1);
    transform: translateY(-2px);
  }
}

.metric-icon {
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, rgba(68, 113, 196, 0.1), rgba(91, 141, 217, 0.15));
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1rem;
}

.gradient-text-primary {
  font-size: 2rem;
  font-weight: 800;
  background: linear-gradient(135deg, #4471c4, #5b8dd9);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  margin-bottom: 0.5rem;
}

@media (max-width: 768px) {
  .feature-showcase-card {
    padding: 1.5rem 1rem;
  }

  .iso-certifications-row {
    padding: 1.5rem 1rem;
  }

  .certifications-grid {
    grid-template-columns: 1fr;
  }
}
</style>
