<template>
  <ClientALayout current-page="indicators">
    <v-container class="pa-6" fluid>
      <PageHeader
        button-icon="mdi-plus"
        button-text="Nouvel indicateur"
        icon="mdi-chart-bar"
        subtitle="Suivi et analyse des indicateurs de performance"
        title="Indicateurs QHSE"
        @action="$router.push('/company/indicators/create')"
      />

      <div class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-6">
        <AppWidget
          clickable
          :icon="BarChart3"
          title="Total"
          :value="stats.total"
          variant="primary"
          @click="resetFilters"
        />
        <AppWidget
          clickable
          :icon="CheckCircle"
          title="Qualité"
          :value="stats.byCategory.qualite"
          variant="audit"
          @click="filters.category = 'qualite'"
        />
        <AppWidget
          clickable
          :icon="Droplet"
          title="Hygiène"
          :value="stats.byCategory.hygiene"
          variant="info"
          @click="filters.category = 'hygiene'"
        />
        <AppWidget
          clickable
          :icon="Shield"
          title="Sécurité"
          :value="stats.byCategory.securite"
          variant="error"
          @click="filters.category = 'securite'"
        />
        <AppWidget
          clickable
          :icon="Leaf"
          title="Environnement"
          :value="stats.byCategory.environnement"
          variant="success"
          @click="filters.category = 'environnement'"
        />
        <AppWidget
          clickable
          :icon="TrendingUp"
          title="Performance"
          :value="stats.byCategory.performance || 0"
          variant="info"
          @click="filters.category = 'performance'"
        />
      </div>

      <FilterCard>
        <v-row>
          <v-col cols="12" md="3">
            <v-text-field
              v-model="filters.search"
              density="comfortable"
              hide-details
              placeholder="Rechercher par code ou nom..."
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="filters.category"
              clearable
              density="comfortable"
              hide-details
              :items="categoryOptions"
              placeholder="Toutes les catégories"
              prepend-inner-icon="mdi-tag"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="filters.frequency"
              clearable
              density="comfortable"
              hide-details
              :items="frequencyOptions"
              placeholder="Toutes les fréquences"
              prepend-inner-icon="mdi-clock-outline"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="filters.status"
              clearable
              density="comfortable"
              hide-details
              :items="statusFilterOptions"
              placeholder="Tous les statuts"
              prepend-inner-icon="mdi-filter"
              variant="outlined"
            />
          </v-col>
        </v-row>
        <v-row class="mt-2">
          <v-col class="d-flex justify-end gap-2">
            <v-btn color="primary" variant="outlined" @click="applyFilters">Appliquer</v-btn>
            <v-btn color="grey" variant="outlined" @click="resetFilters">Réinitialiser</v-btn>
          </v-col>
        </v-row>
      </FilterCard>

      <div v-if="loading" class="py-12">
        <UnifiedLoader
          class="mx-auto"
          description="Récupération des indicateurs et statistiques..."
          title="Chargement des indicateurs..."
          variant="local"
        />
      </div>

      <v-row v-else>
        <v-col
          v-for="indicator in filteredIndicators"
          :key="indicator.id"
          cols="12"
          lg="4"
          md="6"
        >
          <v-card class="h-100" hover @click="$router.push(`/company/indicators/${indicator.id}`)">
            <v-card-text>
              <div class="d-flex align-center justify-space-between mb-3">
                <div>
                  <v-chip class="mb-1" :color="getCategoryColorVuetify(indicator.category)" label size="small">
                    {{ getCategoryLabel(indicator.category) }}
                  </v-chip>
                  <StatusChip :status="indicator.status" type="indicator" />
                </div>
                <v-icon color="grey-lighten-1">mdi-chart-line</v-icon>
              </div>
              <div class="text-h6 font-weight-bold mb-1">{{ indicator.code }}</div>
              <div class="text-body-2 text-medium-emphasis mb-4">{{ indicator.name }}</div>
              <v-divider class="my-3" />
              <div class="d-flex justify-space-between align-center">
                <div>
                  <div class="text-caption text-medium-emphasis">Objectif</div>
                  <div class="text-h6">{{ indicator.target_value }} {{ indicator.unit || '' }}</div>
                </div>
                <v-icon color="grey">mdi-trending-neutral</v-icon>
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col v-if="!loading && filteredIndicators.length === 0" cols="12">
          <v-card>
            <v-card-text class="text-center py-12">
              <v-icon color="grey-lighten-1" size="64">mdi-chart-bar-stacked</v-icon>
              <div class="text-h6 mt-4 mb-2">Aucun indicateur trouvé</div>
              <div class="text-body-2 text-medium-emphasis mb-4">Commencez par créer un nouvel indicateur</div>
              <v-btn color="primary" prepend-icon="mdi-plus" @click="$router.push('/company/indicators/create')">
                Créer un indicateur
              </v-btn>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import type { IndicatorCategory } from '@/api/services/indicators.service'
  import { BarChart3, CheckCircle, Droplet, Leaf, Shield, TrendingUp } from 'lucide-vue-next'
  import { computed, onMounted, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import AppWidget from '@/components/common/AppWidget.vue'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import FilterCard from '@/modules/clienta/components/FilterCard.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import StatusChip from '@/modules/clienta/components/StatusChip.vue'
  import { useIndicators } from '@/modules/clienta/composables/useIndicators'
  import UnifiedLoader from '@/modules/shared/components/ui/UnifiedLoader.vue'

  const _router = useRouter()
  const { indicators, loading, stats, fetchIndicators } = useIndicators()

  const filters = ref({
    category: '' as IndicatorCategory | '',
    frequency: '',
    status: '',
    search: '',
  })

  const categoryOptions = [
    { title: 'Qualité', value: 'qualite' },
    { title: 'Hygiène', value: 'hygiene' },
    { title: 'Sécurité', value: 'securite' },
    { title: 'Environnement', value: 'environnement' },
    { title: 'Performance', value: 'performance' },
  ]

  const frequencyOptions = [
    { title: 'Quotidien', value: 'daily' },
    { title: 'Hebdomadaire', value: 'weekly' },
    { title: 'Mensuel', value: 'monthly' },
    { title: 'Trimestriel', value: 'quarterly' },
    { title: 'Annuel', value: 'yearly' },
  ]

  const statusFilterOptions = [
    { title: 'Actif', value: 'active' },
    { title: 'Inactif', value: 'inactive' },
  ]

  const filteredIndicators = computed(() => {
    let result = indicators.value

    if (filters.value.category) {
      result = result.filter(i => i.category === filters.value.category)
    }
    if (filters.value.frequency) {
      result = result.filter(i => i.frequency === filters.value.frequency)
    }
    if (filters.value.status) {
      result = result.filter(i => i.status === filters.value.status)
    }
    if (filters.value.search) {
      const search = filters.value.search.toLowerCase()
      result = result.filter(
        i =>
          i.code.toLowerCase().includes(search)
          || i.name.toLowerCase().includes(search),
      )
    }

    return result
  })

  function getCategoryLabel (category: IndicatorCategory): string {
    const labels: Record<IndicatorCategory, string> = {
      qualite: 'Qualité',
      hygiene: 'Hygiène',
      securite: 'Sécurité',
      environnement: 'Environnement',
      performance: 'Performance',
    }
    return labels[category]
  }

  function getCategoryColorVuetify (category: IndicatorCategory): string {
    const colors: Record<IndicatorCategory, string> = {
      qualite: 'purple',
      hygiene: 'teal',
      securite: 'red',
      environnement: 'green',
      performance: 'blue',
    }
    return colors[category]
  }

  async function applyFilters () {
    await fetchIndicators({
      category: filters.value.category || undefined,
      frequency: filters.value.frequency as any,
      status: filters.value.status as any,
      search: filters.value.search || undefined,
    })
  }

  function resetFilters () {
    filters.value = {
      category: '',
      frequency: '',
      status: '',
      search: '',
    }
    fetchIndicators()
  }

  onMounted(() => {
    fetchIndicators()
  })
</script>
