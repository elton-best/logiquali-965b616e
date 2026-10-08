<template>
  <ClientALayout current-page="performance-audits">
    <v-container class="pa-6" fluid>
      <PageHeader
        button-text="Planifier un Audit"
        subtitle="Pilotez vos audits internes, suivez leur avancement et accédez aux sous-modules depuis un seul écran."
        title="Audits Internes"
        @button-click="router.push('/company/audits/create')"
      />

      <v-card class="hero mb-6" elevation="0" rounded="xl">
        <v-card-text class="pa-6 pa-md-8">
          <div class="hero-grid">
            <div>
              <div class="text-overline hero-kicker mb-2">Pilotage</div>
              <h2 class="text-h4 text-md-h3 font-weight-black mb-2">
                Gouvernez la qualité des audits
              </h2>
              <p class="text-body-1 text-medium-emphasis mb-0">
                Centralisez vos audits, priorisez les actions et pilotez la conformité en continu.
              </p>
            </div>
            <div class="hero-badges">
              <div class="hero-badge">
                <span>Total audits</span>
                <strong>{{ stats.total }}</strong>
              </div>
              <div class="hero-badge">
                <span>En cours</span>
                <strong>{{ stats.in_progress }}</strong>
              </div>
              <div class="hero-badge">
                <span>Clôturés</span>
                <strong>{{ stats.completed }}</strong>
              </div>
            </div>
          </div>
        </v-card-text>
      </v-card>

      <div class="section-title">
        <v-icon color="primary" size="18">mdi-chart-box-outline</v-icon>
        <span>Indicateurs clés</span>
      </div>
      <v-row class="mb-6">
        <v-col cols="12" md="3" sm="6">
          <AppWidget
            icon="ClipboardCheck"
            title="Total"
            :value="stats.total"
            variant="audit"
            @click="applyQuickFilter('')"
          />
        </v-col>
        <v-col cols="12" md="3" sm="6">
          <AppWidget
            icon="Calendar"
            title="Planifiés"
            :value="stats.planned"
            variant="info"
            @click="applyQuickFilter('planned')"
          />
        </v-col>
        <v-col cols="12" md="3" sm="6">
          <AppWidget
            icon="Clock"
            title="En cours"
            :value="stats.in_progress"
            variant="warning"
            @click="applyQuickFilter('in_progress')"
          />
        </v-col>
        <v-col cols="12" md="3" sm="6">
          <AppWidget
            icon="CheckCheck"
            title="Terminés"
            :value="stats.completed"
            variant="success"
            @click="applyQuickFilter('closed')"
          />
        </v-col>
      </v-row>

      <div class="section-title">
        <v-icon color="primary" size="18">mdi-layers-outline</v-icon>
        <span>Accès rapide</span>
      </div>
      <v-row class="mb-6">
        <v-col cols="12" md="6">
          <v-card class="h-100 quick-link-card" hover rounded="xl" @click="router.push('/company/performance/audits/programme')">
            <v-card-text class="quick-link-body">
              <div class="quick-link-icon">
                <v-icon color="info" size="24">mdi-timeline-clock-outline</v-icon>
              </div>
              <div>
                <div class="text-subtitle-1 font-weight-bold">Programme d'Audit</div>
                <div class="text-caption text-medium-emphasis" />
                <div class="text-body-2 mt-2">
                  Structurez le programme annuel puis planifiez les audits associés.
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="6">
          <v-card class="h-100 quick-link-card" hover rounded="xl" @click="router.push('/company/performance/audits/evaluation')">
            <v-card-text class="quick-link-body">
              <div class="quick-link-icon">
                <v-icon color="success" size="24">mdi-account-check-outline</v-icon>
              </div>
              <div>
                <div class="text-subtitle-1 font-weight-bold">Évaluation des Auditeurs</div>
                <div class="text-body-2 mt-2">
                  Suivez les évaluations, les retours et l’amélioration des auditeurs.
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <div class="section-title">
        <v-icon color="primary" size="18">mdi-filter-cog-outline</v-icon>
        <span>Filtres & recherche</span>
      </div>
      <FilterCard
        v-model="filters"
        class="registry-shell"
        :filter-md="2"
        :filters="filterFields"
        :force-single-row="true"
        :reset-md="1"
        :search-md="2"
        @reset="resetFilters"
      />

      <div class="section-title">
        <v-icon color="primary" size="18">mdi-format-list-bulleted</v-icon>
        <span>Suivi des audits</span>
      </div>
      <div class="list-toolbar mt-4 mb-4">
        <div class="list-toolbar-copy">
          <div class="list-toolbar-title">Liste opérationnelle</div>
          <div class="list-toolbar-subtitle">
            Alternez entre une vue synthétique en cartes et une vue détaillée en tableau.
          </div>
        </div>
        <v-btn-toggle
          v-model="viewMode"
          color="primary"
          density="comfortable"
          divided
          mandatory
          variant="outlined"
        >
          <v-btn value="table">
            <v-icon size="18" start>mdi-table-large</v-icon>
            Tableau
          </v-btn>
          <v-btn value="cards">
            <v-icon size="18" start>mdi-view-grid-outline</v-icon>
            Cartes
          </v-btn>
        </v-btn-toggle>
      </div>

      <v-card class="mt-6 registry-shell" rounded="xl">
        <v-card-title class="d-flex align-center justify-space-between">
          <div class="list-header-copy">
            <div class="list-header-title">Liste des audits</div>
            <div class="list-header-subtitle">
              Une lecture métier claire des audits, du pilote, du programme annuel et de la disponibilité du rapport.
            </div>
          </div>
          <v-chip color="primary" size="small" variant="tonal">
            {{ filteredItems.length }} audits
          </v-chip>
        </v-card-title>
        <template v-if="viewMode === 'table'">
          <v-card-text class="pt-0">
            <div class="table-guide">
              <div class="table-guide-item">
                <span class="table-guide-label">Type d'audit</span>
                <span class="table-guide-value">Nature de la mission planifiée</span>
              </div>
              <div class="table-guide-item">
                <span class="table-guide-label">Pilote</span>
                <span class="table-guide-value">Auditeur principal responsable</span>
              </div>
              <div class="table-guide-item">
                <span class="table-guide-label">Rapport</span>
                <span class="table-guide-value">Téléchargeable dès validation</span>
              </div>
            </div>
          </v-card-text>
          <DataTable
            empty-message="Aucun audit trouvé"
            :headers="headers"
            :items="filteredItems"
            :loading="loading"
          >
            <template #item.reference="{ item }">
              <div class="stacked-cell">
                <span class="stacked-label">Référence</span>
                <span class="stacked-value strong">{{ item.reference || item.ref || 'Sans référence' }}</span>
              </div>
            </template>

            <template #item.title="{ item }">
              <div class="stacked-cell">
                <span class="stacked-label">Audit</span>
                <span class="stacked-value strong">{{ item.title || 'Audit sans titre' }}</span>
              </div>
            </template>

            <template #item.type="{ item }">
              <div class="stacked-cell">
                <span class="stacked-label">Type d'audit</span>
                <StatusChip :label="getTypeLabel(item.type)" size="small" />
              </div>
            </template>

            <template #item.status="{ item }">
              <div class="stacked-cell">
                <span class="stacked-label">Avancement</span>
                <StatusChip
                  :color="getStatusColor(item.status)"
                  :label="getStatusLabel(item.status)"
                  size="small"
                />
              </div>
            </template>

            <template #item.site="{ item }">
              <div class="stacked-cell">
                <span class="stacked-label">Site audité</span>
                <span class="stacked-value">{{ item.site?.name || '—' }}</span>
              </div>
            </template>

            <template #item.lead_auditor="{ item }">
              <div class="stacked-cell">
                <span class="stacked-label">Pilote</span>
                <span class="stacked-value">{{ getLeadAuditorName(item) }}</span>
              </div>
            </template>

            <template #item.program="{ item }">
              <div class="stacked-cell">
                <span class="stacked-label">Programme annuel</span>
                <span class="stacked-value">{{ getProgramTitle(item) }}</span>
              </div>
            </template>

            <template #item.planned_date="{ item }">
              <div class="stacked-cell">
                <span class="stacked-label">Date prévue</span>
                <span class="stacked-value">{{ formatDate(item.planned_date || item.audit_date) }}</span>
              </div>
            </template>

            <template #item.report="{ item }">
              <div class="stacked-cell">
                <span class="stacked-label">Rapport</span>
                <v-chip
                  :color="canDownloadReport(item) ? 'success' : 'default'"
                  size="small"
                  :variant="canDownloadReport(item) ? 'tonal' : 'outlined'"
                >
                  {{ canDownloadReport(item) ? 'Disponible' : 'En attente' }}
                </v-chip>
              </div>
            </template>

            <template #item.actions="{ item }">
              <div class="table-actions">
                <v-btn
                  icon="mdi-eye"
                  size="small"
                  variant="text"
                  @click="viewItem(item)"
                />
                <v-menu location="bottom end">
                  <template #activator="{ props }">
                    <v-btn
                      icon="mdi-progress-pencil"
                      size="small"
                      variant="text"
                      v-bind="props"
                    />
                  </template>
                  <v-list density="compact">
                    <v-list-item
                      v-for="statusAction in quickStatusActions"
                      :key="`${item.id}-${statusAction.value}`"
                      :active="item.status === statusAction.value"
                      @click="handleStatusChange(item, statusAction.value)"
                    >
                      <v-list-item-title>{{ statusAction.label }}</v-list-item-title>
                    </v-list-item>
                  </v-list>
                </v-menu>
                <v-btn
                  v-if="canExportReport(item)"
                  color="primary"
                  icon="mdi-file-pdf-box"
                  size="small"
                  variant="text"
                  @click="handleExportReport(item)"
                />
                <v-btn
                  color="warning"
                  :disabled="submittingForVerification"
                  icon="mdi-shield-check"
                  size="small"
                  variant="text"
                  @click="submitExportForVerification(item)"
                />
                <v-btn
                  v-if="canDownloadReport(item)"
                  color="success"
                  icon="mdi-download"
                  size="small"
                  variant="text"
                  @click="handleDownloadReport(item)"
                />
                <v-btn
                  icon="mdi-pencil"
                  size="small"
                  variant="text"
                  @click="editItem(item)"
                />
                <v-btn
                  color="error"
                  icon="mdi-delete"
                  size="small"
                  variant="text"
                  @click="deleteItem(item)"
                />
              </div>
            </template>
          </DataTable>
        </template>
        <template v-else>
          <v-card-text>
            <div v-if="filteredItems.length > 0" class="audit-cards-grid">
              <v-card
                v-for="item in filteredItems"
                :key="item.id"
                class="audit-card"
                elevation="0"
                rounded="xl"
              >
                <v-card-text class="pa-5">
                  <div class="audit-card-top">
                    <div>
                      <div class="audit-card-ref">{{ item.reference || item.ref || 'Sans référence' }}</div>
                      <div class="audit-card-title">{{ item.title || 'Audit sans titre' }}</div>
                    </div>
                    <StatusChip
                      :color="getStatusColor(item.status)"
                      :label="getStatusLabel(item.status)"
                      size="small"
                    />
                  </div>
                  <div class="audit-card-meta">
                    <div class="audit-card-meta-item">
                      <v-icon size="16">mdi-shape-outline</v-icon>
                      <span>{{ getTypeLabel(item.type) }}</span>
                    </div>
                    <div class="audit-card-meta-item">
                      <v-icon size="16">mdi-office-building-outline</v-icon>
                      <span>{{ item.site?.name || 'Site non défini' }}</span>
                    </div>
                    <div class="audit-card-meta-item">
                      <v-icon size="16">mdi-calendar-clock-outline</v-icon>
                      <span>{{ formatDate(item.planned_date || item.audit_date) }}</span>
                    </div>
                  </div>
                  <div class="audit-card-actions">
                    <v-btn
                      color="primary"
                      prepend-icon="mdi-eye"
                      rounded="lg"
                      variant="tonal"
                      @click="viewItem(item)"
                    >
                      Voir
                    </v-btn>
                    <v-menu location="bottom end">
                      <template #activator="{ props }">
                        <v-btn prepend-icon="mdi-progress-pencil" rounded="lg" variant="text" v-bind="props">
                          Statut
                        </v-btn>
                      </template>
                      <v-list density="compact">
                        <v-list-item
                          v-for="statusAction in quickStatusActions"
                          :key="`card-${item.id}-${statusAction.value}`"
                          :active="item.status === statusAction.value"
                          @click="handleStatusChange(item, statusAction.value)"
                        >
                          <v-list-item-title>{{ statusAction.label }}</v-list-item-title>
                        </v-list-item>
                      </v-list>
                    </v-menu>
                    <v-btn
                      v-if="canExportReport(item)"
                      color="primary"
                      prepend-icon="mdi-file-pdf-box"
                      rounded="lg"
                      variant="tonal"
                      @click="handleExportReport(item)"
                    >
                      Exporter PDF
                    </v-btn>
                    <v-btn
                      color="warning"
                      :disabled="submittingForVerification"
                      prepend-icon="mdi-shield-check"
                      rounded="lg"
                      variant="outlined"
                      @click="submitExportForVerification(item)"
                    >
                      Vérifier document
                    </v-btn>
                    <v-btn
                      v-if="canDownloadReport(item)"
                      color="success"
                      prepend-icon="mdi-download"
                      rounded="lg"
                      variant="text"
                      @click="handleDownloadReport(item)"
                    >
                      Rapport
                    </v-btn>
                    <v-btn prepend-icon="mdi-pencil" rounded="lg" variant="text" @click="editItem(item)">
                      Modifier
                    </v-btn>
                    <v-btn
                      color="error"
                      icon="mdi-delete"
                      rounded="lg"
                      variant="text"
                      @click="deleteItem(item)"
                    />
                  </div>
                </v-card-text>
              </v-card>
            </div>
            <div v-else class="empty-state">
              <v-icon color="primary" size="36">mdi-clipboard-search-outline</v-icon>
              <div class="empty-state-title">Aucun audit ne correspond aux filtres</div>
              <div class="empty-state-subtitle">Réinitialisez les filtres ou lancez une nouvelle planification.</div>
            </div>
          </v-card-text>
        </template>
      </v-card>

      <v-dialog v-model="showKpiDialog" max-width="960">
        <v-card class="modal-card" rounded="xl">
          <v-card-title class="modal-header d-flex align-center justify-space-between">
            <div class="modal-header-content d-flex align-center ga-3">
              <v-avatar color="primary" size="44" variant="tonal">
                <v-icon size="24">mdi-chart-box-outline</v-icon>
              </v-avatar>
              <div>
                <h2 class="modal-title">{{ selectedKpi.title }}</h2>
                <p class="modal-subtitle">{{ selectedKpi.subtitle }}</p>
              </div>
            </div>
            <v-btn icon="mdi-close" variant="text" @click="showKpiDialog = false" />
          </v-card-title>
          <v-divider />
          <v-card-text class="modal-body">
            <div class="modal-summary">
              <v-chip color="primary" variant="tonal">
                {{ kpiDialogItems.length }} audit(s)
              </v-chip>
              <span class="modal-summary-text">Le filtre de la liste principale a aussi été appliqué.</span>
            </div>
            <div v-if="kpiDialogItems.length > 0" class="audit-cards-grid compact">
              <v-card
                v-for="item in kpiDialogItems"
                :key="`kpi-${item.id}`"
                class="audit-card"
                elevation="0"
                rounded="xl"
              >
                <v-card-text class="pa-4">
                  <div class="audit-card-top">
                    <div>
                      <div class="audit-card-ref">{{ item.reference || item.ref || 'Sans référence' }}</div>
                      <div class="audit-card-title">{{ item.title || 'Audit sans titre' }}</div>
                    </div>
                    <StatusChip
                      :color="getStatusColor(item.status)"
                      :label="getStatusLabel(item.status)"
                      size="small"
                    />
                  </div>
                  <div class="audit-card-meta compact">
                    <div class="audit-card-meta-item">
                      <v-icon size="16">mdi-calendar-clock-outline</v-icon>
                      <span>{{ formatDate(item.planned_date || item.audit_date) }}</span>
                    </div>
                    <div class="audit-card-meta-item">
                      <v-icon size="16">mdi-office-building-outline</v-icon>
                      <span>{{ item.site?.name || 'Site non défini' }}</span>
                    </div>
                  </div>
                </v-card-text>
              </v-card>
            </div>
            <div v-else class="empty-state">
              <v-icon color="primary" size="36">mdi-clipboard-text-search-outline</v-icon>
              <div class="empty-state-title">Aucun audit dans cette catégorie</div>
            </div>
          </v-card-text>
          <v-card-actions class="modal-footer">
            <v-btn variant="text" @click="showKpiDialog = false">Fermer</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { Calendar, CheckCheck, ClipboardCheck, Clock } from 'lucide-vue-next'
  import { computed, onMounted, ref, watch } from 'vue'
  import { useRouter } from 'vue-router'
  import api from '@/api/client'
  import { AppWidget } from '@/components/common'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import DataTable from '@/modules/clienta/components/DataTable.vue'
  import FilterCard from '@/modules/clienta/components/FilterCard.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import StatusChip from '@/modules/clienta/components/StatusChip.vue'
  import { useAudits } from '@/modules/clienta/composables/useAudits'
  import { useSites } from '@/modules/clienta/composables/useSites'
  import { useToast } from '@/modules/shared/composables/useToast'
  import { getErrorMessage } from '@/utils/errorMessage'

  const router = useRouter()
  const toast = useToast()
  const {
    audits,
    loading,
    stats,
    fetchAudits,
    deleteAudit,
    generateReport,
    updateStatus,
    downloadReport,
  } = useAudits()

  const { sites, fetchSites } = useSites()
  const viewMode = ref<'table' | 'cards'>('table')
  const showKpiDialog = ref(false)
  const exportedDocsByAuditId = ref<Record<number, { id: number, code: string }>>({})
  const submittingForVerification = ref(false)
  const selectedKpi = ref({
    title: 'Vue détaillée',
    subtitle: 'Audits correspondants',
    status: '' as string,
  })

  const filters = ref({
    search: '',
    type: '',
    status: '',
    site_id: null as number | null,
  })

  const auditTypes = [
    { value: 'internal', label: 'Interne' },
    { value: 'external', label: 'Externe' },
    { value: 'certification', label: 'Certification' },
    { value: 'system', label: 'Système' },
    { value: 'process', label: 'Processus' },
    { value: 'product', label: 'Produit' },
    { value: 'supplier', label: 'Fournisseur' },
    { value: 'thematic', label: 'Thématique' },
    { value: 'surveillance', label: 'Surveillance' },
  ]

  const statuses = [
    { value: 'planned', label: 'Planifié', color: 'gray' },
    { value: 'in_progress', label: 'En cours', color: 'blue' },
    { value: 'report_draft', label: 'Rapport en rédaction', color: 'yellow' },
    { value: 'report_approved', label: 'Rapport approuvé', color: 'green' },
    { value: 'completed', label: 'Terminé', color: 'green' },
    { value: 'closed', label: 'Clôturé', color: 'gray' },
  ]

  const quickStatusActions = [
    { value: 'planned', label: 'Planifié' },
    { value: 'in_progress', label: 'En cours' },
    { value: 'report_draft', label: 'Rapport en rédaction' },
    { value: 'report_approved', label: 'Rapport approuvé' },
    { value: 'closed', label: 'Clôturé' },
  ]

  const filterFields = computed(() => [
    {
      key: 'type',
      type: 'select',
      label: 'Type',
      md: 2,
      items: auditTypes.map(type => ({ label: type.label, value: type.value })),
    },
    {
      key: 'status',
      type: 'select',
      label: 'Statut',
      md: 2,
      items: statuses.map(status => ({ label: status.label, value: status.value })),
    },
    {
      key: 'site_id',
      type: 'select',
      label: 'Site',
      md: 2,
      items: (sites.value || []).map(site => ({ label: site.name, value: site.id })),
    },
  ])

  const headers = [
    { key: 'reference', title: 'Référence', sortable: false },
    { key: 'title', title: 'Intitulé de l’audit', sortable: false },
    { key: 'type', title: 'Type d’audit', sortable: false },
    { key: 'site', title: 'Site audité', sortable: false },
    { key: 'lead_auditor', title: 'Pilote', sortable: false },
    { key: 'program', title: 'Programme', sortable: false },
    { key: 'status', title: 'Statut', sortable: false },
    { key: 'planned_date', title: 'Date prévue', sortable: false },
    { key: 'report', title: 'Rapport', sortable: false },
    { key: 'actions', title: 'Actions', sortable: false },
  ]

  const filteredItems = computed(() => {
    let filtered = audits.value

    if (filters.value.search.trim()) {
      const query = filters.value.search.trim().toLowerCase()
      filtered = filtered.filter((audit: any) =>
        String(audit.title || '').toLowerCase().includes(query)
        || String(audit.reference || audit.ref || '').toLowerCase().includes(query)
        || String(audit.scope || '').toLowerCase().includes(query)
        || String(audit.lead_auditor?.name || '').toLowerCase().includes(query),
      )
    }

    if (filters.value.type) {
      filtered = filtered.filter((audit: any) => audit.type === filters.value.type)
    }

    if (filters.value.status) {
      filtered = filtered.filter((audit: any) => audit.status === filters.value.status)
    }

    if (filters.value.site_id) {
      filtered = filtered.filter((audit: any) => Number(audit.site_id || audit.site?.id) === filters.value.site_id)
    }

    return filtered
  })

  const kpiDialogItems = computed(() => {
    if (!selectedKpi.value.status) return audits.value
    return audits.value.filter((audit: any) => audit.status === selectedKpi.value.status)
  })

  function applyQuickFilter (status: string) {
    filters.value.status = status
    selectedKpi.value = {
      title: status ? `Audits ${getStatusLabel(status).toLowerCase()}` : 'Tous les audits',
      subtitle: 'Détail rapide de la catégorie sélectionnée',
      status,
    }
    showKpiDialog.value = true
  }

  function resetFilters () {
    filters.value = {
      search: '',
      type: '',
      status: '',
      site_id: null,
    }
    fetchAudits()
  }

  let filterTimer: ReturnType<typeof setTimeout> | null = null
  watch(
    () => ({ ...filters.value }),
    () => {
      if (filterTimer) clearTimeout(filterTimer)
      filterTimer = setTimeout(() => {
        fetchAudits()
      }, 250)
    },
    { deep: true },
  )

  function getStatusColor (status: string) {
    return statuses.find(item => item.value === status)?.color || 'gray'
  }

  function getStatusLabel (status: string) {
    return statuses.find(item => item.value === status)?.label || status || '—'
  }

  function getTypeLabel (type: string) {
    return auditTypes.find(item => item.value === type)?.label || type || '—'
  }

  function formatDate (date?: string) {
    if (!date) return '—'
    return new Date(date).toLocaleDateString('fr-FR', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    })
  }

  function getLeadAuditorName (item: any) {
    return item?.leadAuditor?.name || item?.lead_auditor?.name || item?.leadAuditor?.full_name || 'Non défini'
  }

  function getProgramTitle (item: any) {
    return item?.program?.title || item?.audit_program?.title || 'Hors programme'
  }

  function viewItem (item: any) {
    router.push(`/company/audits/${item.id}`)
  }

  function editItem (item: any) {
    router.push(`/company/audits/${item.id}`)
  }

  function canDownloadReport (item: any) {
    return Boolean(
      item?.status === 'report_approved'
        || item?.status === 'closed'
      || item?.report_path
        || item?.global_report_path
      || item?.external_report_path,
    )
  }

  function canExportReport (item: any) {
    return ['in_progress', 'report_draft'].includes(String(item?.status || ''))
  }

  async function rememberGeneratedDocument (auditId: number, documentId: number | null | undefined) {
    if (!documentId) return

    const docResponse = await api.get(`/documents/${documentId}`)
    const code = String(docResponse.data?.data?.code || docResponse.data?.code || '')
    exportedDocsByAuditId.value[Number(auditId)] = { id: Number(documentId), code }
  }

  async function handleStatusChange (item: any, status: string) {
    if (!item?.id || item.status === status) return

    try {
      await updateStatus(item.id, status as any)
      await fetchAudits()
      toast.success(`Statut mis à jour : ${getStatusLabel(status)}.`)
    } catch (error) {
      console.error('Failed to update audit status:', error)
      toast.error(getErrorMessage(error, 'Impossible de mettre à jour le statut de l’audit.'))
    }
  }

  async function handleDownloadReport (item: any) {
    try {
      const ref = item.reference || item.ref || item.id
      await downloadReport(item.id, `rapport-audit-${ref}.pdf`)
    } catch (error) {
      console.error('Failed to download audit report:', error)
      toast.error(getErrorMessage(error, 'Impossible de télécharger le rapport de l’audit.'))
    }
  }

  async function handleExportReport (item: any) {
    if (!item?.id) return

    try {
      const result = await generateReport(item.id, 'pdf')
      await rememberGeneratedDocument(item.id, result.generatedDocumentId)
      await fetchAudits()
      toast.success('Rapport PDF généré en brouillon documentaire.')
    } catch (error) {
      console.error('Failed to export audit report:', error)
      toast.error(getErrorMessage(error, 'Impossible d’exporter le rapport d’audit.'))
    }
  }

  async function submitExportForVerification (item: any) {
    const exported = exportedDocsByAuditId.value[Number(item?.id)]
    if (!exported?.id || !exported?.code) {
      const result = await generateReport(item.id, 'pdf')
      await rememberGeneratedDocument(item.id, result.generatedDocumentId)
      await fetchAudits()
      if (!exportedDocsByAuditId.value[Number(item?.id)]?.id) {
        toast.error('Impossible de préparer le brouillon documentaire avant soumission.')
        return
      }
    }
    const documentToSubmit = exportedDocsByAuditId.value[Number(item?.id)]
    if (!documentToSubmit?.id || !documentToSubmit?.code) return

    try {
      submittingForVerification.value = true
      await api.post(`/documents/${documentToSubmit.id}/confirm-code`, {
        needs_verification: true,
        confirmed_code: documentToSubmit.code,
      })
      toast.success('Document envoyé pour vérification.')
      delete exportedDocsByAuditId.value[Number(item?.id)]
    } catch (error) {
      console.error('Failed to submit audit report for verification:', error)
      toast.error(getErrorMessage(error, 'Échec de la soumission pour vérification.'))
    } finally {
      submittingForVerification.value = false
    }
  }

  async function deleteItem (item: any) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cet audit ?')) return

    try {
      await deleteAudit(item.id)
      await fetchAudits()
      toast.success('Audit supprimé.')
    } catch (error) {
      console.error('Failed to delete audit:', error)
      toast.error(getErrorMessage(error, 'Impossible de supprimer cet audit.'))
    }
  }

  onMounted(async () => {
    await Promise.all([
      fetchAudits(),
      fetchSites(),
    ])
  })
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
  grid-template-columns: minmax(0, 1fr);
  gap: 24px;
}

.hero-badges {
  display: grid;
  gap: 12px;
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

.hero-kicker {
  color: rgba(15, 23, 42, 0.62);
}

.quick-link-card {
  border: 1px solid rgba(15, 23, 42, 0.08);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.quick-link-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
}

.quick-link-body {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 16px;
  align-items: start;
}

.quick-link-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: grid;
  place-items: center;
  background: rgba(15, 23, 42, 0.04);
}

.registry-shell {
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.95));
}

.list-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}

.list-toolbar-title {
  font-weight: 800;
  color: #0f172a;
}

.list-header-copy {
  display: grid;
  gap: 4px;
}

.list-header-title {
  font-weight: 800;
  color: #0f172a;
}

.list-header-subtitle {
  color: #64748b;
  font-size: 0.88rem;
}

.list-toolbar-subtitle {
  color: #64748b;
  font-size: 0.92rem;
}

.table-guide {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 12px;
  padding-bottom: 12px;
}

.table-guide-item {
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: rgba(248, 250, 252, 0.9);
  border-radius: 14px;
  padding: 10px 12px;
  display: grid;
  gap: 4px;
}

.table-guide-label {
  font-size: 0.75rem;
  font-weight: 800;
  letter-spacing: 0.03em;
  text-transform: uppercase;
  color: #0f172a;
}

.table-guide-value {
  font-size: 0.9rem;
  color: #64748b;
}

.audit-cards-grid {
  display: grid;
  grid-template-columns: repeat(1, minmax(0, 1fr));
  gap: 16px;
}

.audit-cards-grid.compact {
  grid-template-columns: repeat(1, minmax(0, 1fr));
}

.audit-card {
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.95));
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
}

.audit-card-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.audit-card-ref {
  color: #64748b;
  font-size: 0.82rem;
  margin-bottom: 4px;
}

.audit-card-title {
  color: #0f172a;
  font-size: 1.02rem;
  font-weight: 800;
}

.audit-card-meta {
  display: grid;
  gap: 10px;
  margin-top: 16px;
}

.audit-card-meta.compact {
  margin-top: 12px;
}

.audit-card-meta-item {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: #475569;
  font-size: 0.92rem;
}

.audit-card-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  justify-content: flex-end;
  margin-top: 18px;
  flex-wrap: wrap;
}

.table-actions {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  white-space: nowrap;
}

.stacked-cell {
  display: grid;
  gap: 4px;
  min-width: 120px;
}

.stacked-label {
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.02em;
  text-transform: uppercase;
  color: #94a3b8;
}

.stacked-value {
  color: #334155;
  font-size: 0.92rem;
}

.stacked-value.strong {
  color: #0f172a;
  font-weight: 700;
}

.empty-state {
  padding: 32px 18px;
  text-align: center;
  color: #64748b;
}

.empty-state-title {
  margin-top: 12px;
  color: #0f172a;
  font-weight: 800;
}

.empty-state-subtitle {
  margin-top: 6px;
}

.modal-card {
  border: 1px solid rgba(15, 23, 42, 0.08);
  overflow: hidden;
}

.modal-header {
  padding: 20px 24px;
}

.modal-title {
  font-size: 1.1rem;
  font-weight: 800;
  color: #0f172a;
}

.modal-subtitle {
  margin-top: 2px;
  color: #64748b;
  font-size: 0.92rem;
}

.modal-body {
  padding: 20px 24px;
}

.modal-footer {
  padding: 12px 24px 20px;
}

.modal-summary {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.modal-summary-text {
  color: #64748b;
  font-size: 0.9rem;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 700;
  margin: 18px 0 12px;
  color: #0f172a;
}

@media (max-width: 600px) {
  .section-title {
    margin-top: 14px;
  }
}

@media (min-width: 960px) {
  .hero-grid {
    grid-template-columns: 2fr 1fr;
    align-items: center;
  }

  .audit-cards-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
</style>
