<template>
  <v-card class="hero mb-6" elevation="0" rounded="xl">
    <v-card-text class="pa-6 pa-md-8">
      <div class="hero-grid">
        <div>
          <h2 class="text-h4 font-weight-black mb-2">Registre des protocoles opérationnels</h2>
          <p class="text-body-1 text-medium-emphasis mb-0">
            Processus, procédures et activités sont déjà en base. Cette interface sert à rattacher et piloter les protocoles.
          </p>
        </div>
        <div class="hero-badges">
          <div class="hero-badge">
            <span>Processus</span>
            <strong>{{ processCount }}</strong>
          </div>
          <div class="hero-badge">
            <span>Procédures</span>
            <strong>{{ procedureCount }}</strong>
          </div>
          <div class="hero-badge">
            <span>Activités</span>
            <strong>{{ activityCount }}</strong>
          </div>
          <div class="hero-badge">
            <span>Protocoles</span>
            <strong>{{ protocolCount }}</strong>
          </div>
        </div>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  defineProps<{
    processCount: number
    procedureCount: number
    activityCount: number
    protocolCount: number
  }>()
</script>

<style scoped>
.hero {
  background:
    radial-gradient(1200px 460px at 0% -10%, rgba(10, 132, 255, 0.18), transparent 62%),
    radial-gradient(900px 360px at 100% 0%, rgba(5, 150, 105, 0.17), transparent 60%),
    linear-gradient(135deg, rgba(17, 24, 39, 0.02), rgba(255, 255, 255, 0.8));
  border: 1px solid rgba(15, 23, 42, 0.08);
}

.hero-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 20px;
  align-items: center;
}

.hero-badges {
  display: grid;
  grid-template-columns: repeat(2, minmax(120px, 1fr));
  gap: 10px;
}

.hero-badge {
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: rgba(255, 255, 255, 0.84);
  border-radius: 12px;
  padding: 10px 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.hero-badge span {
  font-size: 12px;
  color: rgba(15, 23, 42, 0.62);
}

.hero-badge strong {
  font-size: 20px;
  color: #0f172a;
}

@media (max-width: 960px) {
  .hero-grid {
    grid-template-columns: 1fr;
  }

  .hero-badges {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
</style>
