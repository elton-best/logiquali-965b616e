<template>
  <ClientALayout current-page="support">
    <v-container class="pa-6 resources-shell" fluid>
      <v-card class="hero-card mb-6" elevation="0" rounded="xl">
        <v-card-text class="pa-6">
          <div class="d-flex flex-wrap align-start justify-space-between ga-4">
            <div>
              <div class="hero-kicker mb-2">Support QHSE</div>
              <h1 class="text-h4 font-weight-bold mb-2">Ressources</h1>
              <p class="text-body-2 hero-subtitle mb-0">
                Pilotez la codification, l'inventaire équipements et les maintenances depuis une seule interface.
              </p>
            </div>

            <div class="hero-badges d-flex flex-column ga-2">
              <v-chip color="teal" size="small" variant="tonal">Étape active: {{ activeTabLabel }}</v-chip>
              <v-chip color="amber-darken-1" size="small" variant="tonal">Flux: Codifier → Inventorier → Maintenir</v-chip>
              <div class="d-flex flex-wrap ga-2 align-center">
                <v-chip color="indigo" size="small" variant="tonal">
                  Codification: {{ codificationLabel }}
                </v-chip>
                <v-btn
                  color="primary"
                  size="small"
                  variant="text"
                  @click="goToCodificationSettings"
                >
                  Modifier le mode
                </v-btn>
              </div>
            </div>
          </div>
        </v-card-text>
      </v-card>

      <v-card class="tabs-card mb-6" elevation="0" rounded="xl">
        <v-tabs v-model="activeTab" class="resource-tabs" color="teal" height="58">
          <v-tab class="text-none font-weight-bold" value="codification">
            <span class="tab-step">1</span>
            Plan de codification
          </v-tab>
          <v-tab class="text-none font-weight-bold" value="inventory">
            <span class="tab-step">2</span>
            Inventaire des équipements
          </v-tab>
          <v-tab class="text-none font-weight-bold" value="maintenance">
            <span class="tab-step">3</span>
            Plan de maintenance
          </v-tab>
        </v-tabs>
      </v-card>

      <Transition mode="out-in" name="resource-switch">
        <div :key="activeTab" class="resource-window">
          <PlanCodification v-if="activeTab === 'codification'" />
          <InventaireEquipements v-else-if="activeTab === 'inventory'" />
          <PlanMaintenance v-else-if="activeTab === 'maintenance'" />
        </div>
      </Transition>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import InventaireEquipements from '@/components/support/InventaireEquipements.vue'
  import PlanCodification from '@/components/support/PlanCodification.vue'
  import PlanMaintenance from '@/components/support/PlanMaintenance.vue'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import { useAuthStore } from '@/stores/auth'

  const router = useRouter()
  const authStore = useAuthStore()
  const activeTab = ref('codification')
  const tabLabels = {
    codification: 'Codification',
    inventory: 'Inventaire',
    maintenance: 'Maintenance',
  } as const
  const activeTabLabel = computed(() => tabLabels[activeTab.value as keyof typeof tabLabels] || '-')
  const codificationLabel = computed(() => {
    const mode = authStore.user?.enterprise?.codification_mode || 'standard'
    return mode === 'custom' ? 'Personnalisée' : 'Standard'
  })

  function goToCodificationSettings () {
    router.push({ path: '/company/settings', query: { tab: 'entreprise' } })
  }

</script>

<style scoped>
.resources-shell {
  --line-soft: #d8e4df;
}

.hero-card {
  border: 1px solid var(--line-soft);
  background:
    radial-gradient(1200px 220px at 8% -30%, rgba(20, 184, 166, 0.13), transparent 52%),
    radial-gradient(1000px 220px at 92% -40%, rgba(245, 158, 11, 0.14), transparent 46%),
    linear-gradient(135deg, #f9fbfa 0%, #f7fbfa 100%);
}

.hero-kicker {
  font-size: 0.74rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #5f6d68;
}

.hero-subtitle {
  max-width: 620px;
  color: #54645e;
}

.tabs-card {
  border: 1px solid var(--line-soft);
  background: #f5f8f7;
}

:deep(.resource-tabs .v-tab) {
  border-radius: 10px;
  margin: 0.45rem 0.25rem;
}

:deep(.resource-tabs .v-slide-group__content) {
  padding: 0.35rem 0.5rem;
}

.tab-step {
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

.resource-window {
  border-radius: 16px;
}

.resource-switch-enter-active,
.resource-switch-leave-active {
  transition: opacity 0.22s ease, transform 0.22s ease;
}

.resource-switch-enter-from,
.resource-switch-leave-to {
  opacity: 0;
  transform: translateY(6px);
}

@media (max-width: 900px) {
  .hero-badges {
    width: 100%;
  }
}
</style>
