<template>
  <ClientALayout current-page="application-scope">
    <PageHeader
      icon="mdi-clipboard-check"
      subtitle="Vue d'ensemble des processus et du périmètre"
      title="Récapitulatif du Domaine d'Application"
    >
      <template #actions>
        <v-btn color="primary" prepend-icon="mdi-download" @click="exportRecap">
          Exporter
        </v-btn>
      </template>
    </PageHeader>

    <v-row>
      <v-col v-if="loading" cols="12">
        <v-card>
          <v-card-text class="pa-8">
            <v-progress-linear color="primary" indeterminate />
          </v-card-text>
        </v-card>
      </v-col>

      <v-col v-if="!loading && !hasSelectedSite" cols="12">
        <v-alert type="warning" variant="tonal">
          Veuillez sélectionner un site pour afficher le récapitulatif.
        </v-alert>
      </v-col>

      <!-- Statistiques -->
      <v-col
        v-for="stat in stats"
        v-if="!loading && hasSelectedSite"
        :key="stat.title"
        cols="12"
        md="3"
      >
        <v-card>
          <v-card-text>
            <div class="text-h3 font-weight-bold" :style="`color: ${stat.color}`">
              {{ stat.value }}
            </div>
            <div class="text-subtitle-2 text-medium-emphasis">{{ stat.title }}</div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Domaine d'application -->
      <v-col v-if="!loading && hasSelectedSite" cols="12">
        <v-card>
          <v-card-title class="d-flex align-center">
            <v-icon class="mr-2">mdi-domain</v-icon>
            Domaine d'Application
            <v-spacer />
            <v-btn
              color="primary"
              size="small"
              to="/company/iso/context/application-scope"
              variant="text"
            >
              Ouvrir les détails
            </v-btn>
          </v-card-title>
          <v-card-text v-if="scope">
            <v-row>
              <v-col cols="12">
                <div class="mb-2"><strong>Objectif du document:</strong></div>
                <div class="text-body-2 pre-wrap mb-3">{{ scope.document_objective || '-' }}</div>

                <div class="mb-2"><strong>Définition du domaine d'application:</strong></div>
                <div class="text-body-2 pre-wrap mb-3">{{ scope.scope_definition || '-' }}</div>

                <div class="mb-2"><strong>Références documentaires:</strong></div>
                <div class="mb-3">
                  <div
                    v-for="(item, index) in toArray(scope.referenced_documents)"
                    :key="`ref-${index}`"
                    class="long-list-item"
                  >
                    {{ item }}
                  </div>
                  <span v-if="toArray(scope.referenced_documents).length === 0" class="text-body-2">-</span>
                </div>
              </v-col>

              <v-col cols="12" md="6">
                <div class="mb-2"><strong>Produits / Services:</strong></div>
                <div class="mb-3">
                  <div
                    v-for="(item, index) in toArray(scope.products_services)"
                    :key="`ps-${index}`"
                    class="long-list-item"
                  >
                    {{ item }}
                  </div>
                  <span v-if="toArray(scope.products_services).length === 0" class="text-body-2">-</span>
                </div>
              </v-col>

              <v-col cols="12" md="6">
                <div class="mb-2"><strong>Unités organisationnelles:</strong></div>
                <div class="mb-3">
                  <div
                    v-for="(item, index) in toArray(scope.organizational_units)"
                    :key="`ou-${index}`"
                    class="long-list-item"
                  >
                    {{ item }}
                  </div>
                  <span v-if="toArray(scope.organizational_units).length === 0" class="text-body-2">-</span>
                </div>
              </v-col>

              <v-col cols="12" md="6">
                <div class="mb-2"><strong>Sites / Lieux:</strong></div>
                <div class="mb-3">
                  <div
                    v-for="(item, index) in toArray(scope.locations)"
                    :key="`loc-${index}`"
                    class="long-list-item"
                  >
                    {{ item }}
                  </div>
                  <span v-if="toArray(scope.locations).length === 0" class="text-body-2">-</span>
                </div>
              </v-col>

              <v-col cols="12" md="6">
                <div class="mb-2"><strong>Exclusions du domaine:</strong></div>
                <div class="text-body-2 pre-wrap mb-3">{{ scope.scope_exclusions || '-' }}</div>
              </v-col>

              <v-col cols="12" md="6">
                <div class="mb-2"><strong>Exclusions ISO:</strong></div>
                <div class="text-body-2 pre-wrap mb-1">{{ scope.iso_exclusions || '-' }}</div>
                <div class="mb-2"><strong>Justification ISO:</strong></div>
                <div class="text-body-2 pre-wrap">{{ scope.iso_exclusions_justification || '-' }}</div>
              </v-col>
            </v-row>
          </v-card-text>
          <v-card-text v-else>
            <v-alert type="info" variant="tonal">
              Aucun domaine d'application courant pour ce site.
            </v-alert>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Processus par catégorie -->
      <v-col v-if="!loading && hasSelectedSite" cols="12">
        <v-card>
          <v-card-title class="d-flex align-center justify-space-between flex-wrap gap-2">
            <span>Processus Enregistrés du SMI</span>
            <v-chip color="primary" size="small" variant="tonal">
              Classement : Management → Réalisation → Support
            </v-chip>
          </v-card-title>
          <v-card-text>
            <v-tabs v-model="tab" color="primary">
              <v-tab value="management">
                <v-icon start color="green">mdi-chart-line</v-icon>
                1. Management ({{ getProcessesByCategory('management').length }})
              </v-tab>
              <v-tab value="realization">
                <v-icon start color="orange">mdi-cog</v-icon>
                2. Réalisation ({{ getProcessesByCategory('realization').length }})
              </v-tab>
              <v-tab value="support">
                <v-icon start color="purple">mdi-lifebuoy</v-icon>
                3. Support ({{ getProcessesByCategory('support').length }})
              </v-tab>
            </v-tabs>

            <v-window v-model="tab" class="mt-4">
              <v-window-item v-for="category in ['management', 'realization', 'support']" :key="category" :value="category">
                <v-expansion-panels v-if="getProcessesByCategory(category).length > 0">
                  <v-expansion-panel
                    v-for="process in getProcessesByCategory(category)"
                    :key="process.id"
                  >
                    <v-expansion-panel-title>
                      <div class="d-flex align-center w-100">
                        <v-avatar class="mr-3" :color="getCategoryColor(category)" size="40">
                          <v-icon color="white">{{ getCategoryIcon(category) }}</v-icon>
                        </v-avatar>
                        <div class="flex-grow-1">
                          <div class="font-weight-bold">
                            <router-link
                              class="process-link"
                              :to="`/company/context/management-system/${process.id}`"
                              @click.stop
                            >
                              {{ process.title }}
                            </router-link>
                          </div>
                          <div class="text-caption text-medium-emphasis">{{ process.purpose || 'Aucune finalité' }}</div>
                        </div>
                        <v-chip class="mr-2" :color="getStatusColor(process.status)" size="small">
                          {{ process.status }}
                        </v-chip>
                      </div>
                    </v-expansion-panel-title>
                    <v-expansion-panel-text>
                      <v-row>
                        <!-- Informations générales -->
                        <v-col cols="12">
                          <v-card color="primary" variant="tonal">
                            <v-card-title class="text-body-1">
                              <v-icon class="mr-2" size="small">mdi-information</v-icon>
                              Informations Générales
                            </v-card-title>
                            <v-card-text>
                              <div class="mb-2"><strong>Catégorie:</strong> {{ process.category }}</div>
                              <div class="mb-2"><strong>Finalité:</strong> {{ process.purpose || 'Non définie' }}</div>
                            </v-card-text>
                          </v-card>
                        </v-col>

                        <!-- Séquences détaillées -->
                        <v-col v-if="process.sequences && process.sequences.length > 0" cols="12">
                          <v-card color="success" variant="tonal">
                            <v-card-title class="text-body-1">
                              <v-icon class="mr-2" size="small">mdi-timeline</v-icon>
                              Séquences ({{ process.sequences.length }})
                            </v-card-title>
                            <v-card-text>
                              <v-table density="compact">
                                <thead>
                                  <tr>
                                    <th class="text-left" style="width: 15%;">Fournisseurs</th>
                                    <th class="text-left" style="width: 20%;">Entrées</th>
                                    <th class="text-left" style="width: 20%;">Activités</th>
                                    <th class="text-left" style="width: 20%;">Sorties</th>
                                    <th class="text-left" style="width: 15%;">Clients</th>
                                  </tr>
                                </thead>
                                <tbody>
                                  <tr v-for="(seq, idx) in process.sequences" :key="idx">
                                    <td>
                                      <v-chip
                                        v-for="(sup, i) in getSuppliers(seq)"
                                        :key="i"
                                        class="mb-1 mr-1"
                                        color="info"
                                        size="x-small"
                                      >
                                        {{ sup }}
                                      </v-chip>
                                      <span v-if="getSuppliers(seq).length === 0" class="text-caption text-medium-emphasis">-</span>
                                    </td>
                                    <td>
                                      <div class="text-caption">{{ seq.input_description || seq.inputs || '-' }}</div>
                                    </td>
                                    <td>
                                      <div class="text-caption font-weight-bold">{{ seq.activity_description || seq.activities || '-' }}</div>
                                    </td>
                                    <td>
                                      <div class="text-caption">{{ seq.output_description || seq.outputs || '-' }}</div>
                                    </td>
                                    <td>
                                      <v-chip
                                        v-for="(cli, i) in getClients(seq)"
                                        :key="i"
                                        class="mb-1 mr-1"
                                        color="success"
                                        size="x-small"
                                      >
                                        {{ cli }}
                                      </v-chip>
                                      <span v-if="getClients(seq).length === 0" class="text-caption text-medium-emphasis">-</span>
                                    </td>
                                  </tr>
                                </tbody>
                              </v-table>
                            </v-card-text>
                          </v-card>
                        </v-col>

                        <!-- Objectifs -->
                        <v-col v-if="process.objectives && process.objectives.length > 0" cols="12" md="6">
                          <v-card color="info" variant="tonal">
                            <v-card-title class="text-body-1">
                              <v-icon class="mr-2" size="small">mdi-target</v-icon>
                              Objectifs ({{ process.objectives.length }})
                            </v-card-title>
                            <v-card-text>
                              <v-list density="compact">
                                <v-list-item v-for="(obj, i) in process.objectives" :key="i">
                                  <v-list-item-title class="text-caption">{{ obj.title || obj.name }}</v-list-item-title>
                                  <v-list-item-subtitle class="text-caption">{{ obj.indicator?.name || obj.indicator }}</v-list-item-subtitle>
                                </v-list-item>
                              </v-list>
                            </v-card-text>
                          </v-card>
                        </v-col>

                        <!-- Ressources -->
                        <v-col v-if="hasResources(process)" cols="12" md="6">
                          <v-card color="warning" variant="tonal">
                            <v-card-title class="text-body-1">
                              <v-icon class="mr-2" size="small">mdi-toolbox</v-icon>
                              Ressources
                            </v-card-title>
                            <v-card-text>
                              <div v-if="getResources(process, 'human').length > 0" class="mb-2">
                                <strong class="text-caption">Humaines:</strong>
                                <v-chip v-for="(r, i) in getResources(process, 'human')" :key="i" class="ml-1" size="x-small">{{ r }}</v-chip>
                              </div>
                              <div v-if="getResources(process, 'technological').length > 0" class="mb-2">
                                <strong class="text-caption">Technologiques:</strong>
                                <v-chip v-for="(r, i) in getResources(process, 'technological')" :key="i" class="ml-1" size="x-small">{{ r }}</v-chip>
                              </div>
                              <div v-if="getResources(process, 'material').length > 0" class="mb-2">
                                <strong class="text-caption">Matérielles:</strong>
                                <v-chip v-for="(r, i) in getResources(process, 'material')" :key="i" class="ml-1" size="x-small">{{ r }}</v-chip>
                              </div>
                              <div v-if="getResources(process, 'documentary').length > 0">
                                <strong class="text-caption">Documentaires:</strong>
                                <v-chip v-for="(r, i) in getResources(process, 'documentary')" :key="i" class="ml-1" size="x-small">{{ r }}</v-chip>
                              </div>
                            </v-card-text>
                          </v-card>
                        </v-col>

                        <!-- Risques -->
                        <v-col v-if="process.risks_opportunities && getRisks(process).length > 0" cols="12" md="6">
                          <v-card color="error" variant="tonal">
                            <v-card-title class="text-body-1">
                              <v-icon class="mr-2" size="small">mdi-alert</v-icon>
                              Risques ({{ getRisks(process).length }})
                            </v-card-title>
                            <v-card-text>
                              <v-list density="compact">
                                <v-list-item v-for="(risk, i) in getRisks(process)" :key="i">
                                  <v-list-item-title class="text-caption">{{ risk.description || risk.title }}</v-list-item-title>
                                </v-list-item>
                              </v-list>
                            </v-card-text>
                          </v-card>
                        </v-col>

                        <!-- Opportunités -->
                        <v-col v-if="process.risks_opportunities && getOpportunities(process).length > 0" cols="12" md="6">
                          <v-card color="success" variant="tonal">
                            <v-card-title class="text-body-1">
                              <v-icon class="mr-2" size="small">mdi-lightbulb</v-icon>
                              Opportunités ({{ getOpportunities(process).length }})
                            </v-card-title>
                            <v-card-text>
                              <v-list density="compact">
                                <v-list-item v-for="(opp, i) in getOpportunities(process)" :key="i">
                                  <v-list-item-title class="text-caption">{{ opp.description || opp.title }}</v-list-item-title>
                                </v-list-item>
                              </v-list>
                            </v-card-text>
                          </v-card>
                        </v-col>
                      </v-row>

                      <v-divider class="my-4" />
                      <v-btn color="primary" :to="`/company/context/management-system/${process.id}`" variant="tonal">
                        Voir détails complets
                      </v-btn>
                    </v-expansion-panel-text>
                  </v-expansion-panel>
                </v-expansion-panels>
                <v-alert v-else type="info" variant="tonal">
                  Aucun processus dans cette catégorie
                </v-alert>
              </v-window-item>
            </v-window>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref, watch } from 'vue'
  import api from '@/api/client'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import { useToast } from '@/modules/shared/composables/useToast'
  import processService from '@/services/processService'
  import { useAuthStore } from '@/stores/auth'

  const authStore = useAuthStore()
  const toast = useToast()
  const tab = ref('management')
  const scope = ref<any>(null)
  const processes = ref<any[]>([])
  const loading = ref(false)

  const hasSelectedSite = computed(() => {
    const storedSiteId = Number(localStorage.getItem('current_site_id'))
    const siteId = authStore.currentSiteId ?? (Number.isFinite(storedSiteId) ? storedSiteId : null)
    return Boolean(siteId)
  })

  const orderedCategories = [
    { key: 'management', title: 'Processus de Management', icon: 'mdi-chart-line', color: '#4CAF50', bgColor: '#e8f5e9' },
    { key: 'realization', title: 'Processus de Réalisation', icon: 'mdi-cog', color: '#FF9800', bgColor: '#fff3e0' },
    { key: 'support', title: 'Processus de Support', icon: 'mdi-lifebuoy', color: '#9C27B0', bgColor: '#f3e5f5' },
  ]

  const stats = computed(() => [
    { title: 'Total Processus', value: processes.value.length, color: '#1976D2' },
    { title: 'Management', value: getProcessesByCategory('management').length, color: '#4CAF50' },
    { title: 'Réalisation', value: getProcessesByCategory('realization').length, color: '#FF9800' },
    { title: 'Support', value: getProcessesByCategory('support').length, color: '#9C27B0' },
  ])

  function getProcessesByCategory (category: string) {
    const categoryMap: Record<string, string[]> = {
      management: ['pilotage', 'management'],
      realization: ['operationnel', 'realization'],
      support: ['support'],
    }
    return processes.value
      .filter((p: any) => {
        const normalized = normalizeProcessCategory(p)
        return categoryMap[category]?.includes(normalized)
      })
      .toSorted((a: any, b: any) => getProcessTitle(a).localeCompare(getProcessTitle(b), 'fr', { sensitivity: 'base' }))
  }

  function getProcessTitle (process: any): string {
    return String(process?.title || process?.name || '').trim()
  }

  function normalizeProcessCategory (process: any): string {
    const raw = String(
      process?.category
      || process?.type
        || process?.process_type
      || process?.attributes?.category
        || process?.attributes?.type
      || process?.attributes?.process_type
        || '',
    ).trim().toLowerCase()

    if (['management', 'pilotage', 'direction', 'strategique', 'stratégique'].includes(raw)) return 'pilotage'
    if (['realization', 'realisation', 'réalisation', 'operationnel', 'opérationnel', 'production'].includes(raw)) return 'operationnel'
    if (['support', 'supporting', 'soutien'].includes(raw)) return 'support'
    return raw
  }

  function getCategoryColor (category: string) {
    const colors: Record<string, string> = {
      management: 'green',
      realization: 'orange',
      support: 'purple',
    }
    return colors[category] || 'grey'
  }

  function getCategoryIcon (category: string) {
    const icons: Record<string, string> = {
      management: 'mdi-chart-line',
      realization: 'mdi-cog',
      support: 'mdi-lifebuoy',
    }
    return icons[category] || 'mdi-circle'
  }

  function getStatusColor (status: string) {
    const colors: Record<string, string> = {
      draft: 'grey',
      active: 'success',
      inactive: 'warning',
    }
    return colors[status] || 'grey'
  }

  function toArray (value: any): string[] {
    return normalizeArray(value)
  }

  function normalizeArray (value: any): string[] {
    if (!value) return []
    if (Array.isArray(value)) return value.filter(Boolean)
    if (typeof value === 'string') {
      try {
        const parsed = JSON.parse(value)
        return Array.isArray(parsed) ? parsed : []
      } catch {
        return value.split(',').map(v => v.trim()).filter(Boolean)
      }
    }
    return []
  }

  function getSuppliers (seq: any): string[] {
    return normalizeArray(seq.supplier_processes || seq.supplierProcesses)
  }

  function getClients (seq: any): string[] {
    return normalizeArray(seq.client_processes || seq.clientProcesses)
  }

  function hasResources (process: any): boolean {
    const _res = process.ressources || process.resources || {}
    return ['human', 'technological', 'material', 'documentary'].some(type =>
      getResources(process, type).length > 0,
    )
  }

  function getResources (process: any, type: string): string[] {
    const res = process.ressources || process.resources || {}
    return normalizeArray(res[type])
  }

  function getRisks (process: any): any[] {
    const items = process.risks_opportunities || []
    return items.filter((r: any) =>
      (r.type || r.kind) === 'risque' || (r.type || r.kind) === 'risk',
    )
  }

  function getOpportunities (process: any): any[] {
    const items = process.risks_opportunities || []
    return items.filter((r: any) =>
      (r.type || r.kind) === 'opportunite' || (r.type || r.kind) === 'opportunity',
    )
  }

  async function loadData () {
    loading.value = true
    try {
      const siteId = authStore.currentSiteId || Number(localStorage.getItem('current_site_id'))
      if (!siteId) {
        scope.value = null
        processes.value = []
        return
      }

      // Charger le domaine d'application
      const scopeResponse = await api.get('/application-scopes', {
        params: { site_id: siteId, is_current: true },
      })
      scope.value = scopeResponse.data?.data?.[0]?.attributes || scopeResponse.data?.data?.[0]

      // Charger les processus avec toutes les relations
      const processResponse = await processService.getProcesses({ site_id: siteId }, 1, 100)
      const processData = processResponse.data || []

      // Charger les détails complets pour chaque processus
      processes.value = await Promise.all(
        processData.map(async (p: any) => {
          try {
            const details = await processService.getProcess(p.id)
            const raw = details.data || details
            return raw?.attributes ? { id: raw.id, ...raw.attributes, relationships: raw.relationships } : raw
          } catch {
            return p?.attributes ? { id: p.id, ...p.attributes, relationships: p.relationships } : p
          }
        }),
      )

      processes.value = [...processes.value].toSorted((a: any, b: any) => {
        const order = (category: string) => {
          if (category === 'pilotage') return 0
          if (category === 'operationnel') return 1
          if (category === 'support') return 2
          return 99
        }
        const byFamily = order(normalizeProcessCategory(a)) - order(normalizeProcessCategory(b))
        if (byFamily !== 0) return byFamily
        return getProcessTitle(a).localeCompare(getProcessTitle(b), 'fr', { sensitivity: 'base' })
      })
    } catch (error) {
      console.error('Erreur chargement:', error)
      toast.error('Erreur lors du chargement des données')
    } finally {
      loading.value = false
    }
  }

  async function exportRecap () {
    toast.info('Export en cours de développement')
  }

  onMounted(() => {
    void loadData()
  })

  watch(() => authStore.currentSiteId, () => {
    void loadData()
  })
</script>

<style scoped>
  .pre-wrap {
    white-space: pre-wrap;
    word-break: break-word;
    overflow-wrap: anywhere;
  }

  .long-list-item {
    margin-bottom: 6px;
    padding: 6px 8px;
    border-left: 3px solid rgba(25, 118, 210, 0.5);
    background: rgba(25, 118, 210, 0.06);
    border-radius: 4px;
    white-space: pre-wrap;
    word-break: break-word;
    overflow-wrap: anywhere;
    line-height: 1.4;
  }

  .process-link {
    color: inherit;
    text-decoration: none;
  }

  .process-link:hover {
    text-decoration: underline;
  }
</style>
