<template>
  <div class="ressources-page">
    <section class="hero mb-6">
      <div class="hero__content">
        <div class="hero__eyebrow">Support QHSE</div>
        <h1 class="hero__title">Ressources</h1>
        <p class="hero__subtitle">Gestion de la codification, de l'inventaire des équipements et du plan de maintenance.</p>
      </div>
      <div class="hero__stats">
        <div class="stat-card">
          <div class="stat-card__label">Étape active</div>
          <div class="stat-card__value">{{ activeTabLabel }}</div>
        </div>
        <div class="stat-card">
          <div class="stat-card__label">Flux</div>
          <div class="stat-card__value">Codifier → Inventorier → Maintenir</div>
        </div>
      </div>
    </section>

    <div class="workspace">
      <v-tabs v-model="activeTab" class="workspace-tabs" color="teal" height="58">
        <v-tab v-for="tab in tabs" :key="tab.key" class="text-none font-weight-bold" :value="tab.key">
          <span class="workspace-step">{{ tab.step }}</span>
          <span>{{ tab.label }}</span>
        </v-tab>
      </v-tabs>

      <v-window v-model="activeTab" class="workspace__body">
        <v-window-item value="codification">
          <PlanCodification />
        </v-window-item>
        <v-window-item value="inventaire">
          <InventaireEquipements />
        </v-window-item>
        <v-window-item value="maintenance">
          <PlanMaintenance />
        </v-window-item>
      </v-window>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { computed, ref } from 'vue'
  import InventaireEquipements from '@/components/support/InventaireEquipements.vue'
  import PlanCodification from '@/components/support/PlanCodification.vue'
  import PlanMaintenance from '@/components/support/PlanMaintenance.vue'

  const activeTab = ref('codification')
  const tabs = [
    { key: 'codification', label: 'Plan de codification', step: '1' },
    { key: 'inventaire', label: 'Inventaire des équipements', step: '2' },
    { key: 'maintenance', label: 'Plan de maintenance', step: '3' },
  ] as const
  const activeTabLabel = computed(() => tabs.find(tab => tab.key === activeTab.value)?.label || '-')
</script>

<style scoped>
.ressources-page {
  --surface-1: #f6f8f7;
  --surface-2: #ffffff;
  --ink-1: #16221f;
  --ink-2: #5f6d68;
  --brand-1: #0f766e;
  --brand-2: #f59e0b;
  --line: #d9e2de;
}

.hero {
  position: relative;
  display: grid;
  grid-template-columns: 1.4fr 1fr;
  gap: 1rem;
  padding: 1.25rem;
  border-radius: 18px;
  border: 1px solid var(--line);
  background:
    radial-gradient(1200px 240px at 10% -30%, #d1fae5 10%, transparent 50%),
    radial-gradient(1000px 220px at 90% -40%, #fef3c7 8%, transparent 45%),
    linear-gradient(135deg, #f7fbfa 0%, #f8faf9 100%);
}

.hero__eyebrow {
  font-size: 0.75rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--ink-2);
  margin-bottom: 0.5rem;
}

.hero__title {
  margin: 0;
  font-size: clamp(1.4rem, 2vw, 2rem);
  color: var(--ink-1);
}

.hero__subtitle {
  margin: 0.5rem 0 0;
  color: var(--ink-2);
  line-height: 1.4;
}

.hero__stats {
  display: grid;
  gap: 0.75rem;
}

.stat-card {
  border: 1px solid var(--line);
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.9);
  padding: 0.8rem 0.9rem;
}

.stat-card__label {
  font-size: 0.72rem;
  color: var(--ink-2);
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.stat-card__value {
  margin-top: 0.25rem;
  font-size: 0.92rem;
  color: var(--ink-1);
  font-weight: 600;
}

.workspace {
  border: 1px solid var(--line);
  border-radius: 18px;
  overflow: hidden;
  background: var(--surface-2);
}

.workspace-tabs {
  background: var(--surface-1);
  border-bottom: 1px solid var(--line);
  padding: 0.35rem 0.5rem;
}

:deep(.workspace-tabs .v-tab) {
  border-radius: 10px;
  margin: 0.15rem 0.2rem;
}

.workspace-step {
  width: 20px;
  height: 20px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(15, 118, 110, 0.12);
  color: #0f766e;
  font-size: 0.72rem;
  font-weight: 700;
  margin-right: 0.45rem;
}

.workspace__body {
  padding: 1.2rem;
}

@media (max-width: 900px) {
  .hero {
    grid-template-columns: 1fr;
  }
}
</style>
