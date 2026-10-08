<template>
  <v-container fluid>
    <v-row>
      <v-col cols="12">
        <!-- Header -->
        <div class="d-flex align-center mb-6">
          <v-btn
            class="mr-4"
            icon
            variant="text"
            @click="$router.back()"
          >
            <v-icon>mdi-arrow-left</v-icon>
          </v-btn>
          <div>
            <h1 class="text-h4 font-weight-bold">{{ subModuleTitle }}</h1>
            <p class="text-body-2 text-medium-emphasis mt-1">
              {{ module.charAt(0).toUpperCase() + module.slice(1) }} - Point {{ point }}
            </p>
          </div>
        </div>

        <!-- Loading -->
        <v-card v-if="loading" class="pa-8">
          <UnifiedLoader
            class="mx-auto"
            description="Vérification de la configuration du sous-module..."
            title="Chargement du module..."
            variant="local"
          />
        </v-card>

        <!-- Error -->
        <v-alert
          v-else-if="error"
          class="mb-4"
          type="error"
          variant="tonal"
        >
          {{ error }}
        </v-alert>

        <!-- Content -->
        <v-card v-else>
          <v-card-text class="pa-8">
            <div class="text-center py-12">
              <v-icon class="mb-4" color="primary" size="80">
                {{ subModuleIcon }}
              </v-icon>
              <h2 class="text-h5 mb-4">{{ subModuleTitle }}</h2>
              <p class="text-body-1 text-medium-emphasis mb-6">
                Ce module est en cours de développement.
              </p>
              <v-chip color="info" variant="tonal">
                Point ISO {{ point }}
              </v-chip>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'
  import { useRoute } from 'vue-router'
  import { useAccessCatalog } from '@/modules/clienta/composables/useAccessCatalog'
  import UnifiedLoader from '@/modules/shared/components/ui/UnifiedLoader.vue'

  const route = useRoute()
  const { fetchCatalog, subModules } = useAccessCatalog()
  const loading = ref(true)
  const error = ref<string | null>(null)
  const subModuleData = ref<any>(null)

  function getRouteParam (key: string): string {
    const raw = (route.params as Record<string, string | string[] | undefined>)[key]
    if (Array.isArray(raw)) return raw[0] ?? ''
    return raw ?? ''
  }

  const point = computed(() => {
    const module = getRouteParam('module')
    // Mapper les modules vers leurs points ISO
    const moduleToPoint: Record<string, string> = {
      context: '4',
      leadership: '5',
      planning: '6',
      support: '7',
      operation: '8',
      performance: '9',
      improvement: '10',
    }
    return moduleToPoint[module] || module
  })

  const module = computed(() => getRouteParam('module'))
  const submodule = computed(() => getRouteParam('submodule'))

  const subModuleTitle = computed(() => {
    if (!subModuleData.value) {
      // Fallback: formatter le nom depuis l'URL
      return submodule.value
        .split('-')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ')
    }
    return subModuleData.value.name
  })

  const subModuleIcon = computed(() => {
    if (!subModuleData.value) return 'mdi-file-document-outline'
    return subModuleData.value.icon || 'mdi-file-document-outline'
  })

  async function loadSubModuleData () {
    try {
      loading.value = true
      error.value = null

      await fetchCatalog()
      const currentSubModules = Array.isArray(subModules.value) ? subModules.value : []

      // Trouver le sous-module correspondant
      const foundSubModule = currentSubModules.find((sm: any) => {
        const routeMatch = sm.route?.includes(submodule.value)
        const codeMatch = sm.code === submodule.value
        return routeMatch || codeMatch
      })

      if (foundSubModule) {
        subModuleData.value = foundSubModule
      } else {
        error.value = 'Module non trouvé ou non accessible'
      }
    } catch (error_: any) {
      console.error('Erreur chargement sous-module:', error_)
      error.value = error_.response?.data?.message || 'Erreur lors du chargement du module'
    } finally {
      loading.value = false
    }
  }

  onMounted(() => {
    loadSubModuleData()
  })
</script>

<style scoped>
/* Styles additionnels si nécessaire */
</style>
