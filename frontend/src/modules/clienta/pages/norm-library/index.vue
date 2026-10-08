<template>
  <ClientALayout>
    <PageHeader
      icon="mdi-book-open-page-variant-outline"
      :subtitle="pageSubtitle"
      title="Bibliothèque des normes"
    />

    <v-row>
      <v-col cols="12" lg="4">
        <v-card class="h-100" elevation="1">
          <v-card-title class="d-flex align-center justify-space-between">
            <span>Normes accessibles</span>
            <v-chip color="primary" size="small" variant="tonal">
              {{ norms.length }}
            </v-chip>
          </v-card-title>
          <v-divider />
          <v-card-text>
            <v-text-field
              v-model="normSearch"
              class="mb-3"
              clearable
              density="comfortable"
              hide-details
              label="Rechercher une norme"
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
            />

            <v-list
              v-if="filteredNorms.length > 0"
              class="pa-0"
              density="comfortable"
              nav
            >
              <v-list-item
                v-for="norm in filteredNorms"
                :key="norm.id"
                :active="selectedNormId === norm.id"
                rounded="lg"
                @click="selectNorm(norm.id)"
              >
                <template #prepend>
                  <v-icon color="primary">mdi-certificate-outline</v-icon>
                </template>
                <v-list-item-title>{{ norm.code }}</v-list-item-title>
                <v-list-item-subtitle>{{ norm.name }}</v-list-item-subtitle>
              </v-list-item>
            </v-list>

            <v-alert
              v-else-if="!loadingNorms"
              border="start"
              type="info"
              variant="tonal"
            >
              <div class="font-weight-medium">Aucune norme accessible pour le site courant.</div>
              <div class="text-body-2 mt-1">
                {{ emptyStateHint }}
              </div>
              <v-btn
                class="mt-3"
                color="primary"
                prepend-icon="mdi-credit-card-outline"
                to="/company/subscription"
                variant="flat"
              >
                Gérer l'abonnement
              </v-btn>
            </v-alert>

            <div v-else class="py-4 text-center">
              <v-progress-circular color="primary" indeterminate />
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" lg="8">
        <v-card class="h-100" elevation="1">
          <v-card-title class="d-flex align-center justify-space-between">
            <div>
              <div class="text-h6">
                {{ selectedNorm?.code || "Détail de la norme" }}
              </div>
              <div class="text-body-2 text-medium-emphasis">
                {{ selectedNorm?.name || "Sélectionnez une norme pour consulter sa structure" }}
              </div>
            </div>
            <v-chip
              v-if="selectedNormVersionLabel"
              color="primary"
              size="small"
              variant="tonal"
            >
              Version {{ selectedNormVersionLabel }}
            </v-chip>
          </v-card-title>
          <v-divider />
          <v-card-text>
            <div v-if="loadingNormDetails" class="py-8 text-center">
              <v-progress-circular color="primary" indeterminate />
            </div>

            <template v-else-if="selectedNorm?.has_pdf_document && selectedNorm?.pdf_file_url">
              <v-alert
                border="start"
                class="mb-4"
                color="primary"
                icon="mdi-file-pdf-box"
                variant="tonal"
              >
                Cette norme est disponible sous forme de fichier PDF importé par le super-admin.
              </v-alert>

              <div class="d-flex justify-end mb-4">
                <v-btn
                  color="primary"
                  :href="selectedNorm.pdf_file_url"
                  prepend-icon="mdi-open-in-new"
                  rel="noopener"
                  target="_blank"
                  variant="outlined"
                >
                  Ouvrir le PDF
                </v-btn>
              </div>

              <iframe
                class="norm-pdf-frame"
                :src="selectedNorm.pdf_file_url"
                title="Norme PDF"
              />
            </template>

            <template v-else>
              <v-text-field
                v-model="sectionSearch"
                class="mb-4"
                clearable
                density="comfortable"
                hide-details
                label="Rechercher dans la structure"
                prepend-inner-icon="mdi-magnify"
                variant="outlined"
              />

              <v-alert
                v-if="detailsError"
                border="start"
                type="error"
                variant="tonal"
              >
                {{ detailsError }}
              </v-alert>

              <v-alert
                v-else-if="selectedNormId && filteredSections.length === 0"
                border="start"
                type="info"
                variant="tonal"
              >
                Aucune section à afficher pour cette norme.
              </v-alert>

              <template v-else-if="filteredSections.length > 0">
                <div class="d-flex justify-end ga-2 mb-3">
                  <v-btn
                    color="primary"
                    prepend-icon="mdi-unfold-more-horizontal"
                    size="small"
                    variant="outlined"
                    @click="expandAll"
                  >
                    Tout ouvrir
                  </v-btn>
                  <v-btn
                    prepend-icon="mdi-unfold-less-horizontal"
                    size="small"
                    variant="outlined"
                    @click="collapseAll"
                  >
                    Tout fermer
                  </v-btn>
                </div>
                <NormSectionTreeReadOnly
                  :expanded-sections="expandedSections"
                  :sections="filteredSections"
                  @toggle-section="toggleSection"
                />
              </template>

              <v-alert
                v-else
                border="start"
                type="info"
                variant="tonal"
              >
                Sélectionnez une norme dans la colonne de gauche.
              </v-alert>
            </template>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </ClientALayout>
</template>

<script setup lang="ts">
  import type { Norm, NormSection } from '@/types/api'
  import { computed, onMounted, ref, watch } from 'vue'
  import api from '@/api/client'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import NormSectionTreeReadOnly from '@/modules/clienta/components/NormSectionTreeReadOnly.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import { useAccessCatalog } from '@/modules/clienta/composables/useAccessCatalog'
  import { useToast } from '@/modules/shared/composables/useToast'
  import { useAuthStore } from '@/stores/auth'

  const toast = useToast()
  const authStore = useAuthStore()
  const {
    norms: catalogNorms,
    meta: catalogMeta,
    fetchCatalog,
    clearCatalogCache,
  } = useAccessCatalog()

  const loadingNorms = ref(false)
  const loadingNormDetails = ref(false)
  const selectedNormId = ref<number | null>(null)
  const selectedNorm = ref<Norm | null>(null)
  const flatSections = ref<NormSection[]>([])
  const detailsError = ref('')
  const normSearch = ref('')
  const sectionSearch = ref('')
  const expandedSections = ref<Set<number>>(new Set())
  const pageSubtitle = computed(() => {
    const siteName = authStore.currentSite?.name || 'Site non sélectionné'
    return `Lecture seule • Site courant: ${siteName}`
  })
  const emptyStateHint = computed(() => {
    const activeSubscriptions = Number(catalogMeta.value?.active_subscriptions_count || 0)
    const linkedNorms = Number(catalogMeta.value?.linked_norms_count || 0)

    if (activeSubscriptions <= 0) {
      return 'Ce site ne dispose d’aucun abonnement actif. Souscrivez une offre pour activer la bibliothèque.'
    }

    if (linkedNorms <= 0) {
      return 'Aucune norme n’est liée à l’offre active de ce site.'
    }

    return 'Les normes liées existent mais ne sont pas encore visibles pour ce site. Vérifiez le rattachement offre/norme et le contexte de site actif.'
  })
  const norms = computed<Norm[]>(() =>
    (catalogNorms.value || []).map(norm => ({
      id: norm.id,
      code: norm.code,
      name: norm.name,
      title: norm.name,
      domain: norm.domain || '',
      status: (norm as any).status || 'draft',
    }) as Norm),
  )

  const filteredNorms = computed(() => {
    const query = normSearch.value.trim().toLowerCase()
    if (!query) return norms.value
    return norms.value.filter(norm => {
      const code = String(norm.code || '').toLowerCase()
      const name = String(norm.name || '').toLowerCase()
      return code.includes(query) || name.includes(query)
    })
  })

  const selectedNormVersionLabel = computed(() => {
    const legacyCurrentVersion = (selectedNorm.value as any)?.current_version
    return (
      selectedNorm.value?.currentVersion?.version_code
      || legacyCurrentVersion?.version_code
      || selectedNorm.value?.versions?.[0]?.version_code
      || ''
    )
  })

  const filteredSections = computed(() => {
    const query = sectionSearch.value.trim().toLowerCase()
    if (!query) return treeSections.value
    return filterSections(treeSections.value, query)
  })

  onMounted(async () => {
    await loadNorms()
  })

  watch(
    () => authStore.currentSiteId,
    async () => {
      clearCatalogCache()
      await loadNorms()
    },
  )

  async function loadNorms () {
    loadingNorms.value = true
    try {
      await fetchCatalog(true)

      const resolvedSiteId = Number(catalogMeta.value?.site_id || 0)
      const resolvedSiteName = String(catalogMeta.value?.site_name || '').trim()
      const hasCurrentSite = !!authStore.currentSiteId && !!authStore.currentSite
      if (!hasCurrentSite && resolvedSiteId > 0) {
        const existingSites = Array.isArray(authStore.availableSites)
          ? authStore.availableSites
          : []
        if (!existingSites.some(site => Number(site?.id) === resolvedSiteId)) {
          authStore.setAvailableSites([
            ...existingSites,
            { id: resolvedSiteId, name: resolvedSiteName || `Site ${resolvedSiteId}` },
          ])
        }
        authStore.setCurrentSite(resolvedSiteId)
        return
      }

      if (norms.value.length > 0) {
        await selectNorm(norms.value[0].id)
      } else {
        selectedNormId.value = null
        selectedNorm.value = null
        flatSections.value = []
        detailsError.value = ''
        expandedSections.value.clear()
        treeSections.value = []
      }
    } catch (error: any) {
      toast.error(
        error?.response?.data?.message || 'Impossible de charger les normes disponibles.',
      )
    } finally {
      loadingNorms.value = false
    }
  }

  async function selectNorm (normId: number) {
    selectedNormId.value = normId
    loadingNormDetails.value = true
    detailsError.value = ''
    try {
      const response = await api.get<{ data?: Norm }>(`/norms/${normId}`, {
        headers: { 'X-Skip-Error-Toast': 'true' },
      })
      selectedNorm.value = response.data?.data || null
      if (selectedNorm.value?.has_pdf_document && selectedNorm.value?.pdf_file_url) {
        flatSections.value = []
        treeSections.value = []
      } else {
        const rootSections = resolveNormSections(selectedNorm.value)
        flatSections.value = flattenSections(rootSections)
        treeSections.value = rootSections
      }

      if (!selectedNorm.value?.has_pdf_document && flatSections.value.length === 0) {
        const chaptersResponse = await api.get<{ data?: NormSection[] }>(`/norms/${normId}/chapters`, {
          headers: { 'X-Skip-Error-Toast': 'true' },
        })
        const chapterSections = Array.isArray(chaptersResponse.data?.data)
          ? chaptersResponse.data?.data || []
          : []
        flatSections.value = flattenSections(chapterSections)
        treeSections.value = chapterSections
      }
      expandedSections.value = new Set(flatSections.value.slice(0, 8).map(section => section.id))
    } catch (error: any) {
      selectedNorm.value = null
      flatSections.value = []
      detailsError.value
        = error?.response?.data?.message || 'Impossible de charger la structure de la norme.'
      expandedSections.value.clear()
      treeSections.value = []
    } finally {
      loadingNormDetails.value = false
    }
  }

  function toggleSection (sectionId: number) {
    if (expandedSections.value.has(sectionId)) {
      expandedSections.value.delete(sectionId)
      return
    }
    expandedSections.value.add(sectionId)
  }

  const treeSections = ref<NormSection[]>([])

  function expandAll () {
    const ids = new Set<number>()
    const collect = (sections: NormSection[]) => {
      for (const section of sections) {
        ids.add(section.id)
        if (Array.isArray(section.children) && section.children.length > 0) {
          collect(section.children)
        }
      }
    }
    collect(treeSections.value)
    expandedSections.value = ids
  }

  function collapseAll () {
    expandedSections.value.clear()
  }

  function filterSections (sections: NormSection[], query: string): NormSection[] {
    const result: NormSection[] = []
    for (const section of sections) {
      const number = String(section.number || '').toLowerCase()
      const title = String(section.title || '').toLowerCase()
      const content = String(section.content || '').toLowerCase()
      const matches = number.includes(query) || title.includes(query) || content.includes(query)
      const children = Array.isArray(section.children) ? filterSections(section.children, query) : []
      if (matches || children.length > 0) {
        result.push({
          ...section,
          children,
        })
      }
    }
    return result
  }

  function flattenSections (
    sections: NormSection[],
    output: NormSection[] = [],
  ): NormSection[] {
    for (const section of sections) {
      output.push(section)
      if (Array.isArray(section.children) && section.children.length > 0) {
        flattenSections(section.children, output)
      }
    }
    return output
  }

  function resolveNormSections (norm: Norm | null): NormSection[] {
    if (!norm) return []

    const legacyCurrentVersion = (norm as any)?.current_version as any
    const currentSections = (norm.currentVersion?.sections || legacyCurrentVersion?.sections || []) as NormSection[]
    if (Array.isArray(currentSections) && currentSections.length > 0) {
      return currentSections
    }

    const versions = Array.isArray(norm.versions) ? norm.versions : []
    const fallbackVersion = versions.find(version => Array.isArray(version.sections) && version.sections.length > 0)
    return (fallbackVersion?.sections || []) as NormSection[]
  }
</script>

<style scoped>
.section-content {
  white-space: pre-wrap;
  line-height: 1.4;
}

.norm-pdf-frame {
  width: 100%;
  min-height: 78vh;
  border: 1px solid rgb(var(--v-theme-outline-variant));
  border-radius: 12px;
  background: white;
}

</style>
