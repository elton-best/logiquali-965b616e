<template>
  <ClientALayout current-page="performance-surveillance">
    <v-container class="surveillance-page pa-6" fluid>
      <PageHeader
        icon="mdi-clipboard-text-outline"
        subtitle="Accès rapide aux évaluations PIP, performance et configuration des critères."
        title="Évaluations PIP"
      />

      <v-card class="hub-card" elevation="0" rounded="xl">
        <v-card-text class="pa-6 pa-md-8">
          <div class="hub-header mb-6">
            <div>
              <div class="text-overline hub-kicker mb-2">Point d'entrée unique</div>
              <h2 class="text-h5 text-md-h4 font-weight-black mb-2">Gestion des évaluations PIP</h2>
              <p class="text-body-1 text-medium-emphasis mb-0">
                Utilisez ce panneau unique pour ouvrir les interfaces de satisfaction/performance, générer des liens d'évaluation et configurer les critères.
              </p>
            </div>
            <v-chip color="primary" size="small" variant="tonal">
              Site actif: {{ currentSiteLabel }}
            </v-chip>
          </div>

          <v-row class="mb-2" dense>
            <v-col cols="12" md="4">
              <v-btn
                block
                class="hub-action-btn"
                color="primary"
                prepend-icon="mdi-account-star-outline"
                size="large"
                @click="goTo('/company/performance/surveillance/client')"
              >
                Satisfaction Client
              </v-btn>
            </v-col>
            <v-col cols="12" md="4">
              <v-btn
                block
                class="hub-action-btn"
                color="primary"
                prepend-icon="mdi-account-heart-outline"
                size="large"
                @click="goTo('/company/performance/surveillance/personnel')"
              >
                Satisfaction Personnel
              </v-btn>
            </v-col>
            <v-col cols="12" md="4">
              <v-btn
                block
                class="hub-action-btn"
                color="primary"
                prepend-icon="mdi-truck-check-outline"
                size="large"
                @click="goTo('/company/performance/surveillance/prestataires')"
              >
                Satisfaction Prestataires
              </v-btn>
            </v-col>
          </v-row>

          <v-divider class="my-4" />

          <v-row class="mb-2" dense>
            <v-col cols="12" md="6">
              <v-btn
                block
                class="hub-action-btn"
                color="indigo"
                prepend-icon="mdi-chart-line"
                size="large"
                variant="tonal"
                @click="goTo('/company/performance/surveillance/performance-personnel')"
              >
                Performance Personnel
              </v-btn>
            </v-col>
            <v-col cols="12" md="6">
              <v-btn
                block
                class="hub-action-btn"
                color="teal"
                prepend-icon="mdi-chart-box-outline"
                size="large"
                variant="tonal"
                @click="goTo('/company/performance/surveillance/performance-prestataires')"
              >
                Performance Prestataires
              </v-btn>
            </v-col>
          </v-row>

          <v-divider class="my-4" />

          <div class="d-flex flex-wrap ga-3 justify-end">
            <v-btn color="primary" prepend-icon="mdi-clipboard-text-search-outline" variant="outlined" @click="goTo('/company/performance/surveillance/process-review')">
              Revue Processus
            </v-btn>
            <v-btn color="secondary" prepend-icon="mdi-tune-vertical" variant="outlined" @click="goTo('/company/performance/criteria')">
              Configurer les critères
            </v-btn>
            <v-btn color="info" prepend-icon="mdi-link-variant" variant="outlined" @click="goTo('/company/performance/evaluation-requests')">
              Gérer les liens générés
            </v-btn>
          </div>
        </v-card-text>
      </v-card>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed } from 'vue'
  import { useRouter } from 'vue-router'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import { useAuthStore } from '@/stores/auth'

  const router = useRouter()
  const authStore = useAuthStore()

  const currentSiteLabel = computed(() => {
    const name = String(authStore.currentSite?.name || '').trim()
    if (name) return name

    const siteId = Number(authStore.currentSiteId || 0)
    if (Number.isFinite(siteId) && siteId > 0) {
      return `Site #${siteId}`
    }

    return 'Non sélectionné'
  })

  function goTo (path: string) {
    router.push(path)
  }
</script>

<style scoped>
  .surveillance-page {
    background:
      radial-gradient(circle at 95% 5%, rgba(14, 165, 233, 0.08), transparent 35%),
      radial-gradient(circle at 8% 22%, rgba(15, 23, 42, 0.05), transparent 45%);
  }

  .hub-card {
    border: 1px solid rgba(15, 23, 42, 0.08);
    background:
      radial-gradient(1200px 320px at 0% 0%, rgba(10, 132, 255, 0.12), transparent 65%),
      radial-gradient(1000px 260px at 100% 0%, rgba(5, 150, 105, 0.08), transparent 60%),
      linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.96));
  }

  .hub-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    flex-wrap: wrap;
  }

  .hub-kicker {
    color: rgba(15, 23, 42, 0.6);
  }

  .hub-action-btn {
    min-height: 54px;
    border-radius: 14px;
  }
</style>
