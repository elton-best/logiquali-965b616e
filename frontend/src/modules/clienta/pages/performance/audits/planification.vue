<template>
  <ClientALayout current-page="performance-audits">
    <v-container class="pa-6" fluid>
      <PageHeader icon="mdi-calendar-check" title="Planification des audits">
        <template #subtitle>
          M9-D1 - Procédure de gestion des audits qualité internes
        </template>
        <template #actions>
          <v-btn color="primary" prepend-icon="mdi-plus" @click="openDialog">
            Nouveau plan d'audit
          </v-btn>
        </template>
      </PageHeader>

      <v-card class="hero mb-6" elevation="0" rounded="xl">
        <v-card-text class="pa-6 pa-md-8">
          <div class="hero-grid">
            <div>
              <div class="text-overline hero-kicker mb-2">Planification</div>
              <h2 class="text-h4 text-md-h3 font-weight-black mb-2">
                Calendrier des audits
              </h2>
              <p class="text-body-1 text-medium-emphasis mb-0">
                Pilotez les dates prévues, le périmètre et les auditeurs responsables.
              </p>
            </div>
            <div class="hero-badges">
              <div class="hero-badge">
                <span>Plans</span>
                <strong>{{ filteredItems.length }}</strong>
              </div>
              <div class="hero-badge">
                <span>En cours</span>
                <strong>{{ stats.inProgress }}</strong>
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
        <v-icon size="18">mdi-filter-cog-outline</v-icon>
        <span>Filtres & recherche</span>
      </div>
      <v-card class="filters-card registry-shell" rounded="xl" variant="tonal">
        <v-card-text>
          <div class="filters-inline">
            <v-text-field
              v-model="filters.search"
              clearable
              hide-details
              label="Rechercher (référence, processus, auditeur)"
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
            />
            <v-select
              v-model="filters.status"
              clearable
              hide-details
              :items="statusOptions"
              label="Statut"
              variant="outlined"
            />
            <div class="filter-actions">
              <v-btn
                :disabled="!hasActiveFilters"
                prepend-icon="mdi-filter-off"
                variant="outlined"
                @click="resetFilters"
              >
                Réinitialiser
              </v-btn>
            </div>
          </div>
        </v-card-text>
      </v-card>

      <v-row>
        <v-col cols="12">
          <div class="section-title">
            <v-icon size="18">mdi-format-list-bulleted</v-icon>
            <span>Plans d'audit</span>
          </div>
          <v-card class="mt-4 registry-shell" rounded="xl">
            <v-card-title class="d-flex align-center justify-space-between">
              <span>Plans d'audit</span>
              <v-chip color="primary" size="small" variant="tonal">
                {{ filteredItems.length }} plans
              </v-chip>
            </v-card-title>
            <v-card-text>
              <div class="table-guide">
                <v-chip size="small" variant="outlined">Processus = périmètre audité</v-chip>
                <v-chip size="small" variant="outlined">Auditeur = responsable désigné</v-chip>
                <v-chip size="small" variant="outlined">Actions = accès direct au détail</v-chip>
              </div>
              <div class="table-scroll-shell">
                <v-data-table class="custom-table" :headers="headers" :items="filteredItems" :loading="loading">
                  <template #[`item.program`]="{ item }">
                    <div class="cell-ellipsis" :title="item.program">
                      {{ item.program }}
                    </div>
                  </template>
                  <template #[`item.process`]="{ item }">
                    <div class="cell-ellipsis" :title="item.process">
                      {{ item.process }}
                    </div>
                  </template>
                  <template #[`item.status`]="{ item }">
                    <v-chip :color="statusColor(item.status)" size="small" variant="tonal">
                      {{ statusLabel(item.status) }}
                    </v-chip>
                  </template>
                  <template #[`item.actions`]="{ item }">
                    <v-btn icon="mdi-eye-outline" size="small" variant="text" @click="openAudit(item.id)" />
                  </template>
                </v-data-table>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref, watch } from 'vue'
  import { useRouter } from 'vue-router'
  import { auditsService } from '@/api/services/audits.service'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import { useToast } from '@/modules/shared/composables/useToast'
  import { useAuthStore } from '@/stores/auth'
  import { useAuditProgramStore } from '@/stores/improvement/auditProgramStore'
  import { getErrorMessage } from '@/utils/errorMessage'

  interface PlanAuditItem {
    id: number
    reference: string
    program: string
    planned_date: string
    process: string
    auditor: string
    status: string
  }

  const router = useRouter()
  const toast = useToast()
  const authStore = useAuthStore()
  const auditProgramStore = useAuditProgramStore()
  const loading = ref(false)
  const items = ref<PlanAuditItem[]>([])
  const filters = ref({
    search: '',
    status: null as string | null,
  })
  const statusOptions = [
    { title: 'Planifié', value: 'planned' },
    { title: 'En cours', value: 'in_progress' },
    { title: 'Rapport en rédaction', value: 'report_draft' },
    { title: 'Rapport approuvé', value: 'report_approved' },
    { title: 'Terminé', value: 'completed' },
    { title: 'Clôturé', value: 'closed' },
  ]
  const stats = computed(() => {
    return {
      inProgress: items.value.filter(item => item.status === 'in_progress').length,
      completed: items.value.filter(item => item.status === 'completed' || item.status === 'closed').length,
    }
  })
  const hasActiveFilters = computed(() => Boolean(filters.value.search.trim() || filters.value.status))
  const headers = [
    { title: 'Référence', key: 'reference' },
    { title: 'Programme', key: 'program' },
    { title: 'Date prévue', key: 'planned_date' },
    { title: 'Processus', key: 'process' },
    { title: 'Auditeur', key: 'auditor' },
    { title: 'Statut', key: 'status' },
    { title: 'Actions', key: 'actions', sortable: false },
  ]

  const filteredItems = computed(() => {
    const query = filters.value.search.trim().toLowerCase()
    return items.value.filter(item => {
      if (filters.value.status && item.status !== filters.value.status) return false
      if (!query) return true
      const haystack = `${item.reference} ${item.program} ${item.process} ${item.auditor}`.toLowerCase()
      return haystack.includes(query)
    })
  })

  function extractProcessLabel (process: any) {
    const title = String(process?.title || process?.name || process?.attributes?.title || process?.attributes?.name || '').trim()
    const code = String(process?.code || process?.attributes?.code || '').trim()
    if (code && title) return `${code} - ${title}`
    return title || code || ''
  }

  function formatProcesses (rawProcesses: any) {
    if (!Array.isArray(rawProcesses) || rawProcesses.length === 0) {
      return '—'
    }

    const labels = rawProcesses
      .map((process: any) => extractProcessLabel(process))
      .filter(Boolean)

    return labels.length > 0 ? labels.join(', ') : '—'
  }

  function formatDate (value?: string) {
    if (!value) return '—'
    return new Date(value).toLocaleDateString('fr-FR')
  }

  function statusLabel (status?: string) {
    const map: Record<string, string> = {
      planned: 'Planifié',
      in_progress: 'En cours',
      report_draft: 'Rapport en rédaction',
      report_approved: 'Rapport approuvé',
      completed: 'Terminé',
      closed: 'Clôturé',
    }
    return map[status || ''] || (status || '—')
  }

  function statusColor (status?: string) {
    const map: Record<string, string> = {
      planned: 'info',
      in_progress: 'warning',
      report_draft: 'warning',
      report_approved: 'success',
      completed: 'success',
      closed: 'success',
    }
    return map[status || ''] || 'default'
  }

  async function loadPlans () {
    loading.value = true
    try {
      const response = await auditsService.getAudits({
        per_page: 200,
        site_id: authStore.currentSiteId || undefined,
      })

      const rows = Array.isArray((response as any)?.data) ? (response as any).data : []
      items.value = rows.map((row: any) => ({
        id: Number(row.id),
        reference: row.reference || row.ref || `AUD-${row.id}`,
        program: row.program?.title || row.audit_program?.title || 'Programme non défini',
        planned_date: formatDate(row.planned_date || row.audit_date),
        process: formatProcesses(row.processes),
        auditor: row.lead_auditor?.name || row.leadAuditor?.name || '—',
        status: row.status || 'planned',
      }))
    } catch (error) {
      toast.error(getErrorMessage(error, 'Impossible de charger les plans d’audit.'))
      items.value = []
    } finally {
      loading.value = false
    }
  }

  async function openDialog () {
    const siteId = Number(authStore.currentSiteId || 0)
    const year = new Date().getFullYear()

    if (!siteId) {
      toast.error('Sélectionnez d\'abord un site.')
      return
    }

    try {
      await auditProgramStore.fetchPrograms({
        site_id: siteId,
        year,
        per_page: 100,
      })

      if (auditProgramStore.programs.length === 0) {
        toast.warning('Créez d\'abord un programme annuel d\'audit avant de planifier un audit.')
        router.push('/company/performance/audits/programme')
        return
      }
    } catch (error) {
      toast.error(getErrorMessage(error, 'Impossible de vérifier les programmes d\'audit.'))
      return
    }

    const preferredProgram = auditProgramStore.programs.find(program => program.status === 'validated')
      || auditProgramStore.programs[0]

    router.push({
      path: '/company/audits/create',
      query: {
        program_id: String(preferredProgram?.id || ''),
        site_id: String(siteId),
        year: String(year),
      },
    })
  }

  function openAudit (id: number) {
    router.push(`/company/audits/${id}`)
  }

  function resetFilters () {
    filters.value = {
      search: '',
      status: null,
    }
  }

  onMounted(loadPlans)
  watch(() => authStore.currentSiteId, loadPlans)
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

.registry-shell {
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.95));
}

.filters-inline {
  display: grid;
  grid-template-columns: repeat(1, minmax(0, 1fr));
  gap: 12px;
}

.filter-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
}

.table-guide {
  display: flex;
  align-items: center;
  gap: 8px;
  padding-bottom: 12px;
  flex-wrap: wrap;
}

.table-scroll-shell {
  overflow-x: auto;
}

.custom-table :deep(th:last-child),
.custom-table :deep(td:last-child) {
  white-space: nowrap;
  min-width: 110px;
}

.cell-ellipsis {
  max-width: 260px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 700;
  color: rgb(15 23 42);
  margin: 10px 0 14px;
}

@media (min-width: 960px) {
  .hero-grid {
    grid-template-columns: 2fr 1fr;
    align-items: center;
  }

  .filters-inline {
    grid-template-columns: minmax(0, 2fr) minmax(180px, 0.9fr) auto;
    align-items: center;
  }
}
</style>
