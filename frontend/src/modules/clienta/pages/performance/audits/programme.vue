<template>
  <ClientALayout current-page="performance-audits">
    <v-container class="pa-6" fluid>
      <PageHeader icon="mdi-timeline-clock-outline" title="Programme d'audit interne">
        <template #subtitle>
          Un programme d'audit est annuel et regroupe plusieurs plans d'audit rattachés au même site.
        </template>
        <template #actions>
          <v-btn color="primary" prepend-icon="mdi-plus" rounded="lg" @click="openCreateDialog">
            Nouveau programme annuel
          </v-btn>
        </template>
      </PageHeader>

      <v-card class="hero mb-6" elevation="0" rounded="xl">
        <v-card-text class="pa-6 pa-md-8">
          <div class="hero-grid">
            <div>
              <div class="text-overline hero-kicker mb-2">Programme annuel</div>
              <h2 class="text-h4 text-md-h3 font-weight-black mb-2">
                Structurez l'année d'audit
              </h2>
              <p class="text-body-1 text-medium-emphasis mb-0">
                Chaque programme annuel consolide les audits planifiés, leur avancement et les plans d'audit associés.
              </p>
            </div>
            <div class="hero-badges">
              <div class="hero-badge">
                <span>Programmes</span>
                <strong>{{ programs.length }}</strong>
              </div>
              <div class="hero-badge">
                <span>Plans rattachés</span>
                <strong>{{ totalLinkedAudits }}</strong>
              </div>
              <div class="hero-badge">
                <span>Année active</span>
                <strong>{{ filters.year || currentYear }}</strong>
              </div>
            </div>
          </div>
        </v-card-text>
      </v-card>

      <div class="section-title">
        <v-icon size="18">mdi-filter-cog-outline</v-icon>
        <span>Filtres du programme</span>
      </div>
      <v-card class="registry-shell mb-6" rounded="xl">
        <v-card-text>
          <div class="filters-inline">
            <v-select
              v-model="filters.year"
              clearable
              hide-details
              :items="yearOptions"
              label="Année du programme"
              rounded="lg"
              variant="outlined"
            />
            <v-select
              v-model="filters.status"
              clearable
              hide-details
              item-title="label"
              item-value="value"
              :items="statusOptions"
              label="Statut du programme"
              rounded="lg"
              variant="outlined"
            />
            <div class="filter-actions">
              <v-btn :loading="loading" prepend-icon="mdi-refresh" variant="outlined" @click="loadPrograms">
                Actualiser
              </v-btn>
            </div>
          </div>
        </v-card-text>
      </v-card>

      <div class="section-title">
        <v-icon size="18">mdi-view-list-outline</v-icon>
        <span>Programmes annuels</span>
      </div>

      <div v-if="loading" class="program-grid">
        <v-skeleton-loader v-for="n in 3" :key="n" type="card" />
      </div>

      <div v-else-if="programs.length > 0" class="program-grid">
        <v-card
          v-for="program in programs"
          :key="program.id"
          class="program-card"
          elevation="0"
          rounded="xl"
        >
          <v-card-text class="pa-5">
            <div class="program-card-top">
              <div>
                <div class="program-card-ref">{{ program.ref || `PROG-${program.year}` }}</div>
                <div class="program-card-title">{{ program.title || `Programme annuel ${program.year}` }}</div>
              </div>
              <v-chip :color="statusColor(program.status)" size="small" variant="tonal">
                {{ statusLabel(program.status) }}
              </v-chip>
            </div>

            <div class="program-card-meta">
              <div class="program-card-meta-item">
                <v-icon size="16">mdi-calendar-range</v-icon>
                <span>Année {{ program.year }}</span>
              </div>
              <div class="program-card-meta-item">
                <v-icon size="16">mdi-account-tie-outline</v-icon>
                <span>{{ program.program_manager?.name || 'Responsable non défini' }}</span>
              </div>
              <div class="program-card-meta-item">
                <v-icon size="16">mdi-domain</v-icon>
                <span>{{ program.site?.name || 'Site non défini' }}</span>
              </div>
            </div>

            <div v-if="program.objectives" class="program-card-objectives">
              {{ program.objectives }}
            </div>

            <div class="program-kpis">
              <div class="program-kpi">
                <span class="program-kpi-label">Audits planifiés</span>
                <strong>{{ program.planned_audits_count ?? program.audits?.length ?? 0 }}</strong>
              </div>
              <div class="program-kpi">
                <span class="program-kpi-label">Audits réalisés</span>
                <strong>{{ program.completed_audits_count ?? 0 }}</strong>
              </div>
              <div class="program-kpi">
                <span class="program-kpi-label">Taux de réalisation</span>
                <strong>{{ program.completion_rate ?? 0 }}%</strong>
              </div>
            </div>

            <div class="linked-header">
              <div>
                <div class="linked-title">Plans d'audit rattachés</div>
                <div class="linked-subtitle">
                  Le programme annuel regroupe les audits/planes affectés à cette année.
                </div>
              </div>
              <v-chip color="primary" size="small" variant="outlined">
                {{ program.audits?.length || 0 }} plan(s)
              </v-chip>
            </div>

            <div v-if="program.audits?.length" class="table-scroll-shell">
              <v-data-table
                class="linked-table"
                :headers="linkedAuditHeaders"
                hide-default-footer
                :items="program.audits"
                items-per-page="-1"
              >
                <template #[`item.type`]="{ item }">
                  <v-chip color="primary" size="small" variant="tonal">
                    {{ typeLabel(item.type) }}
                  </v-chip>
                </template>
                <template #[`item.planned_date`]="{ item }">
                  {{ formatDate(item.planned_date || item.audit_date) }}
                </template>
                <template #[`item.status`]="{ item }">
                  <v-chip :color="auditStatusColor(item.status)" size="small" variant="tonal">
                    {{ auditStatusLabel(item.status) }}
                  </v-chip>
                </template>
                <template #[`item.actions`]="{ item }">
                  <v-btn
                    color="primary"
                    prepend-icon="mdi-open-in-new"
                    rounded="lg"
                    size="small"
                    variant="text"
                    @click="router.push(`/company/audits/${item.id}`)"
                  >
                    Ouvrir
                  </v-btn>
                </template>
              </v-data-table>
            </div>
            <div v-else class="empty-linked">
              Aucun plan d'audit n'est encore rattaché à ce programme.
            </div>

            <div class="program-actions">
              <v-btn
                color="primary"
                prepend-icon="mdi-calendar-plus"
                rounded="lg"
                variant="tonal"
                @click="goToPlanningForProgram(program)"
              >
                Planifier un audit
              </v-btn>
              <v-btn
                prepend-icon="mdi-eye-outline"
                rounded="lg"
                variant="text"
                @click="openProgramDetails(program)"
              >
                Voir détails
              </v-btn>
              <v-btn
                prepend-icon="mdi-pencil-outline"
                rounded="lg"
                variant="text"
                @click="handleEditProgramClick(program)"
              >
                Modifier
              </v-btn>
              <v-btn
                v-if="program.status === 'draft'"
                color="success"
                prepend-icon="mdi-check-circle"
                rounded="lg"
                variant="tonal"
                @click="validateProgram(program.id)"
              >
                Valider
              </v-btn>
              <v-btn
                prepend-icon="mdi-download"
                rounded="lg"
                variant="outlined"
                @click="exportCalendar(program.id)"
              >
                Export XLSX
              </v-btn>
            </div>
          </v-card-text>
        </v-card>
      </div>

      <v-card v-else class="empty-state" elevation="0" rounded="xl">
        <v-card-text class="text-center py-12">
          <v-icon color="primary" size="72">mdi-calendar-check-outline</v-icon>
          <div class="empty-state-title mt-4">Aucun programme annuel d'audit</div>
          <div class="empty-state-subtitle mt-2">
            Créez un programme pour l'année, puis rattachez-y les plans d'audit.
          </div>
          <v-btn
            class="mt-6"
            color="primary"
            prepend-icon="mdi-plus"
            rounded="lg"
            @click="openCreateDialog"
          >
            Créer un programme annuel
          </v-btn>
        </v-card-text>
      </v-card>

      <v-dialog v-model="dialog" max-width="820">
        <v-card class="modal-card" rounded="xl">
          <v-card-title class="modal-header d-flex align-center justify-space-between">
            <div class="d-flex align-center ga-3">
              <v-avatar color="primary" size="44" variant="tonal">
                <v-icon size="24">mdi-calendar-check</v-icon>
              </v-avatar>
              <div>
                <div class="modal-title">Nouveau programme annuel d'audit</div>
                <div class="modal-subtitle">Un programme regroupe plusieurs plans d'audit sur une année.</div>
              </div>
            </div>
            <v-btn icon="mdi-close" variant="text" @click="dialog = false" />
          </v-card-title>
          <v-divider />
          <v-card-text class="modal-body">
            <v-row>
              <v-col cols="12" md="4">
                <v-select
                  v-model="formData.year"
                  :items="yearOptions"
                  label="Année *"
                  rounded="lg"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="8">
                <v-text-field
                  v-model="formData.title"
                  label="Titre du programme"
                  placeholder="Programme annuel d'audit 2026"
                  rounded="lg"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model.number="formData.planned_audits_count"
                  label="Nombre prévisionnel de plans d'audit"
                  min="0"
                  rounded="lg"
                  type="number"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  disabled
                  label="Site concerné"
                  :model-value="currentSiteLabel"
                  rounded="lg"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="formData.objectives"
                  label="Objectifs du programme"
                  placeholder="Exigences à couvrir, priorités, processus critiques, fréquence..."
                  rounded="lg"
                  rows="4"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="formData.scope"
                  label="Périmètre et champ"
                  placeholder="Ex: Toutes les activités du site principal"
                  rounded="lg"
                  rows="2"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-textarea
                  v-model="formData.program_risks"
                  label="Risques liés au programme"
                  rounded="lg"
                  rows="2"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-textarea
                  v-model="formData.program_opportunities"
                  label="Opportunités liées au programme"
                  rounded="lg"
                  rows="2"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field
                  v-model="formData.audit_language"
                  label="Langue d'audit"
                  rounded="lg"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="8">
                <v-text-field
                  v-model="formData.references"
                  label="Références"
                  rounded="lg"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="formData.internal_auditors"
                  label="Auditeurs internes pressentis"
                  rounded="lg"
                  rows="2"
                  variant="outlined"
                />
              </v-col>
            </v-row>
          </v-card-text>
          <v-card-actions class="modal-footer">
            <v-btn variant="text" @click="dialog = false">Annuler</v-btn>
            <v-btn color="primary" :loading="loading" rounded="lg" @click="saveProgram">
              Créer le programme
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <v-dialog v-model="editDialog" max-width="820">
        <v-card class="modal-card" rounded="xl">
          <v-card-title class="modal-header d-flex align-center justify-space-between">
            <div class="d-flex align-center ga-3">
              <v-avatar color="warning" size="44" variant="tonal">
                <v-icon size="24">mdi-pencil-outline</v-icon>
              </v-avatar>
              <div>
                <div class="modal-title">Modifier le programme annuel</div>
                <div class="modal-subtitle">Mettez à jour les informations utiles du programme d'audit.</div>
              </div>
            </div>
            <v-btn icon="mdi-close" variant="text" @click="editDialog = false" />
          </v-card-title>
          <v-divider />
          <v-card-text class="modal-body">
            <v-row>
              <v-col cols="12" md="6">
                <v-select
                  v-model="editForm.site_id"
                  item-title="title"
                  item-value="value"
                  :items="siteOptions"
                  label="Site concerné"
                  rounded="lg"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  disabled
                  label="Référence"
                  :model-value="editForm.ref"
                  rounded="lg"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12">
                <v-text-field
                  v-model="editForm.title"
                  label="Titre du programme"
                  rounded="lg"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model.number="editForm.planned_audits_count"
                  label="Nombre d'audits prévus"
                  min="0"
                  rounded="lg"
                  type="number"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  disabled
                  label="Année"
                  :model-value="String(editForm.year)"
                  rounded="lg"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="editForm.objectives"
                  label="Objectifs du programme"
                  rounded="lg"
                  rows="4"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="editForm.scope"
                  label="Périmètre et champ"
                  rounded="lg"
                  rows="2"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-textarea
                  v-model="editForm.program_risks"
                  label="Risques liés au programme"
                  rounded="lg"
                  rows="2"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-textarea
                  v-model="editForm.program_opportunities"
                  label="Opportunités liées au programme"
                  rounded="lg"
                  rows="2"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field
                  v-model="editForm.audit_language"
                  label="Langue d'audit"
                  rounded="lg"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="8">
                <v-text-field
                  v-model="editForm.references"
                  label="Références"
                  rounded="lg"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="editForm.internal_auditors"
                  label="Auditeurs internes pressentis"
                  rounded="lg"
                  rows="2"
                  variant="outlined"
                />
              </v-col>
            </v-row>
          </v-card-text>
          <v-card-actions class="modal-footer">
            <v-btn variant="text" @click="editDialog = false">Annuler</v-btn>
            <v-btn color="primary" :loading="loading" rounded="lg" @click="saveProgramChanges">
              Enregistrer les modifications
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <v-dialog v-model="detailsDialog" max-width="1080">
        <v-card class="modal-card" rounded="xl">
          <v-card-title class="modal-header d-flex align-center justify-space-between">
            <div class="d-flex align-center ga-3">
              <v-avatar color="primary" size="44" variant="tonal">
                <v-icon size="24">mdi-folder-star-outline</v-icon>
              </v-avatar>
              <div>
                <div class="modal-title">{{ selectedProgram?.title || 'Détail du programme annuel' }}</div>
                <div class="modal-subtitle">
                  {{ selectedProgram?.ref || '—' }} · {{ selectedProgram?.site?.name || currentSiteLabel }}
                </div>
              </div>
            </div>
            <v-btn icon="mdi-close" variant="text" @click="detailsDialog = false" />
          </v-card-title>
          <v-divider />
          <v-card-text class="modal-body">
            <v-row v-if="selectedProgram">
              <v-col cols="12" md="4">
                <div class="detail-kpi-card">
                  <span>Année</span>
                  <strong>{{ selectedProgram.year }}</strong>
                </div>
              </v-col>
              <v-col cols="12" md="4">
                <div class="detail-kpi-card">
                  <span>Plans prévus</span>
                  <strong>{{ selectedProgram.planned_audits_count ?? selectedProgram.audits?.length ?? 0 }}</strong>
                </div>
              </v-col>
              <v-col cols="12" md="4">
                <div class="detail-kpi-card">
                  <span>Plans rattachés</span>
                  <strong>{{ selectedProgram.audits?.length || 0 }}</strong>
                </div>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  disabled
                  label="Site concerné"
                  :model-value="selectedProgram.site?.name || 'Site non défini'"
                  rounded="lg"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  disabled
                  label="Responsable du programme"
                  :model-value="selectedProgram.program_manager?.name || 'Responsable non défini'"
                  rounded="lg"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12">
                <v-textarea
                  auto-grow
                  disabled
                  label="Objectifs"
                  :model-value="selectedProgram.objectives || 'Aucun objectif renseigné.'"
                  rounded="lg"
                  rows="3"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12">
                <v-textarea
                  auto-grow
                  disabled
                  label="Périmètre et champ"
                  :model-value="selectedProgram.scope || 'Aucun périmètre renseigné.'"
                  rounded="lg"
                  rows="2"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-textarea
                  auto-grow
                  disabled
                  label="Risques liés au programme"
                  :model-value="selectedProgramCriteria.program_risks || 'Non renseigné'"
                  rounded="lg"
                  rows="2"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-textarea
                  auto-grow
                  disabled
                  label="Opportunités liées au programme"
                  :model-value="selectedProgramCriteria.program_opportunities || 'Non renseigné'"
                  rounded="lg"
                  rows="2"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field
                  disabled
                  label="Langue d'audit"
                  :model-value="selectedProgramCriteria.audit_language || 'Français'"
                  rounded="lg"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="8">
                <v-text-field
                  disabled
                  label="Références"
                  :model-value="selectedProgramCriteria.references || 'ISO 9001:2015, ISO 19011:2018'"
                  rounded="lg"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12">
                <v-textarea
                  auto-grow
                  disabled
                  label="Auditeurs internes pressentis"
                  :model-value="selectedProgramCriteria.internal_auditors || 'Non renseigné'"
                  rounded="lg"
                  rows="2"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12">
                <div class="section-title mb-3">
                  <v-icon size="18">mdi-clipboard-text-clock-outline</v-icon>
                  <span>Audits associés au programme</span>
                </div>
                <div v-if="selectedProgram.audits?.length" class="table-scroll-shell">
                  <v-data-table
                    class="linked-table"
                    :headers="linkedAuditHeaders"
                    hide-default-footer
                    :items="selectedProgram.audits"
                    items-per-page="-1"
                  >
                    <template #[`item.type`]="{ item }">
                      <v-chip color="primary" size="small" variant="tonal">
                        {{ typeLabel(item.type) }}
                      </v-chip>
                    </template>
                    <template #[`item.planned_date`]="{ item }">
                      {{ formatDate(item.planned_date || item.audit_date) }}
                    </template>
                    <template #[`item.status`]="{ item }">
                      <v-chip :color="auditStatusColor(item.status)" size="small" variant="tonal">
                        {{ auditStatusLabel(item.status) }}
                      </v-chip>
                    </template>
                    <template #[`item.actions`]="{ item }">
                      <v-btn
                        color="primary"
                        prepend-icon="mdi-open-in-new"
                        rounded="lg"
                        size="small"
                        variant="text"
                        @click="router.push(`/company/audits/${item.id}`)"
                      >
                        Ouvrir
                      </v-btn>
                    </template>
                  </v-data-table>
                </div>
                <v-alert v-else density="comfortable" type="info" variant="tonal">
                  Aucun audit n'est encore rattaché à ce programme annuel.
                </v-alert>
              </v-col>
            </v-row>
          </v-card-text>
          <v-card-actions class="modal-footer">
            <v-btn
              v-if="selectedProgram"
              color="primary"
              prepend-icon="mdi-calendar-plus"
              rounded="lg"
              variant="tonal"
              @click="goToPlanningForProgram(selectedProgram)"
            >
              Planifier un audit
            </v-btn>
            <v-spacer />
            <v-btn variant="text" @click="detailsDialog = false">Fermer</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import type { AuditProgram } from '@/types/models/auditPrograms'
  import { computed, onMounted, reactive, ref, watch } from 'vue'
  import { useRouter } from 'vue-router'
  import { useToast } from 'vue-toastification'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import { useAuthStore } from '@/stores/auth'
  import { useAuditProgramStore } from '@/stores/improvement/auditProgramStore'
  import { getErrorMessage } from '@/utils/errorMessage'

  const authStore = useAuthStore()
  const programStore = useAuditProgramStore()
  const toast = useToast()
  const router = useRouter()

  const dialog = ref(false)
  const editDialog = ref(false)
  const detailsDialog = ref(false)
  const selectedProgram = ref<AuditProgram | null>(null)
  const currentYear = new Date().getFullYear()

  const filters = reactive({
    year: currentYear as number | null,
    status: '' as string,
  })

  const formData = reactive({
    year: currentYear,
    title: '',
    objectives: '',
    scope: '',
    program_risks: '',
    program_opportunities: '',
    audit_language: 'Français',
    internal_auditors: '',
    references: 'ISO 9001:2015, ISO 19011:2018',
    planned_audits_count: 0,
  })

  const editForm = reactive({
    id: null as number | null,
    ref: '',
    site_id: null as number | null,
    year: currentYear,
    title: '',
    objectives: '',
    scope: '',
    program_risks: '',
    program_opportunities: '',
    audit_language: 'Français',
    internal_auditors: '',
    references: 'ISO 9001:2015, ISO 19011:2018',
    planned_audits_count: 0,
  })

  const statusOptions = [
    { label: 'Brouillon', value: 'draft' },
    { label: 'Validé', value: 'validated' },
    { label: 'En cours', value: 'in_progress' },
    { label: 'Terminé', value: 'completed' },
    { label: 'Archivé', value: 'archived' },
  ]

  const linkedAuditHeaders = [
    { title: 'Référence du plan', key: 'ref' },
    { title: 'Intitulé de l\'audit', key: 'title' },
    { title: 'Type d\'audit', key: 'type' },
    { title: 'Date prévue', key: 'planned_date' },
    { title: 'Statut du plan', key: 'status' },
    { title: 'Actions', key: 'actions', sortable: false },
  ]

  const yearOptions = computed(() => {
    const years: number[] = []
    for (let offset = -1; offset <= 3; offset++) {
      years.push(currentYear + offset)
    }
    return years
  })

  const currentSiteId = computed(() =>
    authStore.currentSiteId ?? ((authStore.user as { site_id?: number } | null)?.site_id ?? null),
  )

  const currentSiteLabel = computed(() =>
    authStore.currentSite?.name || authStore.availableSites.find(s => s.id === currentSiteId.value)?.name || 'Site non sélectionné',
  )
  const siteOptions = computed(() =>
    authStore.availableSites.map(site => ({
      title: site.name,
      value: Number(site.id),
    })),
  )

  const programs = computed<AuditProgram[]>(() => programStore.programs)
  const loading = computed(() => programStore.loading)
  const selectedProgramCriteria = computed(() =>
    extractCriteriaMeta(selectedProgram.value?.risk_based_criteria),
  )
  const totalLinkedAudits = computed(() =>
    programs.value.reduce((sum, program) => sum + (Array.isArray(program.audits) ? program.audits.length : 0), 0),
  )

  function statusLabel (status?: string) {
    return statusOptions.find(option => option.value === status)?.label || status || '—'
  }

  function statusColor (status?: string) {
    const colors: Record<string, string> = {
      draft: 'warning',
      validated: 'info',
      in_progress: 'primary',
      completed: 'success',
      archived: 'grey',
    }
    return colors[status || ''] || 'grey'
  }

  function auditStatusLabel (status?: string) {
    const labels: Record<string, string> = {
      planned: 'Planifié',
      in_progress: 'En cours',
      report_draft: 'Rapport en rédaction',
      report_approved: 'Rapport approuvé',
      completed: 'Terminé',
      closed: 'Clôturé',
      cancelled: 'Annulé',
    }
    return labels[status || ''] || status || '—'
  }

  function auditStatusColor (status?: string) {
    const colors: Record<string, string> = {
      planned: 'info',
      in_progress: 'warning',
      report_draft: 'warning',
      report_approved: 'success',
      completed: 'success',
      closed: 'success',
      cancelled: 'error',
    }
    return colors[status || ''] || 'grey'
  }

  function typeLabel (type?: string) {
    const labels: Record<string, string> = {
      internal: 'Interne',
      external: 'Externe',
      certification: 'Certification',
      system: 'Système',
      process: 'Processus',
      product: 'Produit',
      supplier: 'Fournisseur',
      thematic: 'Thématique',
      surveillance: 'Surveillance',
    }
    return labels[type || ''] || type || '—'
  }

  function formatDate (value?: string) {
    if (!value) return '—'
    return new Date(value).toLocaleDateString('fr-FR')
  }

  function extractCriteriaMeta (riskBasedCriteria: unknown) {
    const data = (riskBasedCriteria && typeof riskBasedCriteria === 'object')
      ? (riskBasedCriteria as Record<string, any>)
      : {}

    return {
      program_risks: String(data.program_risks || ''),
      program_opportunities: String(data.program_opportunities || ''),
      audit_language: String(data.audit_language || 'Français'),
      internal_auditors: String(data.internal_auditors || ''),
      references: String(data.references || 'ISO 9001:2015, ISO 19011:2018'),
    }
  }

  function buildCriteriaMeta (source: {
    program_risks: string
    program_opportunities: string
    audit_language: string
    internal_auditors: string
    references: string
  }) {
    return {
      program_risks: source.program_risks?.trim() || null,
      program_opportunities: source.program_opportunities?.trim() || null,
      audit_language: source.audit_language?.trim() || null,
      internal_auditors: source.internal_auditors?.trim() || null,
      references: source.references?.trim() || null,
    }
  }

  async function loadPrograms () {
    if (!currentSiteId.value) return

    try {
      await programStore.fetchPrograms({
        site_id: currentSiteId.value,
        year: filters.year || undefined,
        status: filters.status || undefined,
        per_page: 50,
      })
    } catch (error) {
      toast.error(getErrorMessage(error, 'Impossible de charger les programmes d\'audit.'))
    }
  }

  function openCreateDialog () {
    if (!currentSiteId.value) {
      toast.error('Sélectionnez un site avant de créer un programme d\'audit.')
      return
    }

    formData.year = filters.year || currentYear
    formData.title = ''
    formData.objectives = ''
    formData.scope = ''
    formData.program_risks = ''
    formData.program_opportunities = ''
    formData.audit_language = 'Français'
    formData.internal_auditors = ''
    formData.references = 'ISO 9001:2015, ISO 19011:2018'
    formData.planned_audits_count = 0
    dialog.value = true
  }

  async function openProgramDetails (program: AuditProgram) {
    selectedProgram.value = program
    detailsDialog.value = true

    try {
      const freshProgram = await programStore.fetchProgram(program.id)
      selectedProgram.value = freshProgram
    } catch (error) {
      toast.error(getErrorMessage(error, 'Impossible de charger le détail du programme.'))
    }
  }

  function handleEditProgramClick (program: AuditProgram) {
    const criteriaMeta = extractCriteriaMeta(program.risk_based_criteria)
    editForm.id = program.id
    editForm.ref = program.ref || ''
    editForm.site_id = Number(program.site_id || currentSiteId.value || 0) || null
    editForm.year = program.year || currentYear
    editForm.title = program.title || ''
    editForm.objectives = program.objectives || ''
    editForm.scope = program.scope || ''
    editForm.program_risks = criteriaMeta.program_risks
    editForm.program_opportunities = criteriaMeta.program_opportunities
    editForm.audit_language = criteriaMeta.audit_language
    editForm.internal_auditors = criteriaMeta.internal_auditors
    editForm.references = criteriaMeta.references
    editForm.planned_audits_count = Number(program.planned_audits_count || 0)
    editDialog.value = true
  }

  function goToPlanningForProgram (program: AuditProgram) {
    detailsDialog.value = false
    router.push({
      path: '/company/audits/create',
      query: {
        program_id: String(program.id),
        site_id: String(program.site_id || currentSiteId.value || ''),
        year: String(program.year || currentYear),
      },
    })
  }

  async function saveProgram () {
    if (!currentSiteId.value) {
      toast.error('Sélectionnez un site avant de créer un programme d\'audit.')
      return
    }

    const currentUserId = Number((authStore.user as { id?: number } | null)?.id || 0)
    if (!currentUserId) {
      toast.error('Impossible d\'identifier le responsable du programme.')
      return
    }

    try {
      await programStore.createProgram({
        site_id: currentSiteId.value,
        year: formData.year,
        title: formData.title || `Programme annuel d'audit ${formData.year}`,
        objectives: formData.objectives || undefined,
        scope: formData.scope || undefined,
        risk_based_criteria: buildCriteriaMeta(formData),
        planned_audits_count: formData.planned_audits_count || 0,
        program_manager_id: currentUserId,
      })
      toast.success('Programme annuel d\'audit créé.')
      dialog.value = false
      await loadPrograms()
    } catch (error) {
      toast.error(getErrorMessage(error, 'Impossible de créer le programme d\'audit.'))
    }
  }

  async function validateProgram (id: number) {
    try {
      await programStore.validateProgram(id)
      toast.success('Programme validé.')
      await loadPrograms()
    } catch (error) {
      toast.error(getErrorMessage(error, 'Impossible de valider le programme.'))
    }
  }

  async function exportCalendar (id: number) {
    try {
      await programStore.exportCalendar(id)
      toast.success('Programme exporté.')
    } catch (error) {
      toast.error(getErrorMessage(error, 'Impossible d\'exporter le programme.'))
    }
  }

  async function saveProgramChanges () {
    if (!editForm.id) {
      toast.error('Programme introuvable.')
      return
    }

    try {
      await programStore.updateProgram(editForm.id, {
        site_id: editForm.site_id || undefined,
        title: editForm.title || `Programme annuel d'audit ${editForm.year}`,
        objectives: editForm.objectives || undefined,
        scope: editForm.scope || undefined,
        risk_based_criteria: buildCriteriaMeta(editForm),
        planned_audits_count: Number(editForm.planned_audits_count || 0),
      })
      toast.success('Programme mis à jour.')
      editDialog.value = false
      await loadPrograms()
    } catch (error) {
      toast.error(getErrorMessage(error, 'Impossible de modifier le programme d\'audit.'))
    }
  }

  watch(() => authStore.currentSiteId, loadPrograms)
  watch(() => filters.year, loadPrograms)
  watch(() => filters.status, loadPrograms)

  onMounted(loadPrograms)
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

.section-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 700;
  color: rgb(15 23 42);
  margin: 10px 0 14px;
}

.registry-shell,
.empty-state,
.modal-card {
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

.program-grid {
  display: grid;
  grid-template-columns: repeat(1, minmax(0, 1fr));
  gap: 18px;
}

.program-card {
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.95));
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
}

.program-card-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.program-card-ref {
  color: #64748b;
  font-size: 0.82rem;
  margin-bottom: 4px;
}

.program-card-title {
  color: #0f172a;
  font-size: 1.05rem;
  font-weight: 800;
}

.program-card-meta {
  display: grid;
  gap: 10px;
  margin-top: 14px;
}

.program-card-meta-item {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: #475569;
}

.program-card-objectives {
  margin-top: 16px;
  color: #475569;
  line-height: 1.6;
}

.program-kpis {
  display: grid;
  grid-template-columns: repeat(1, minmax(0, 1fr));
  gap: 12px;
  margin-top: 18px;
}

.program-kpi {
  border-radius: 14px;
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: rgba(255, 255, 255, 0.85);
  padding: 12px 14px;
}

.program-kpi-label {
  display: block;
  color: #64748b;
  font-size: 0.82rem;
  margin-bottom: 4px;
}

.linked-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-top: 22px;
  margin-bottom: 12px;
}

.linked-title {
  font-weight: 800;
  color: #0f172a;
}

.linked-subtitle {
  color: #64748b;
  font-size: 0.9rem;
}

.table-scroll-shell {
  overflow-x: auto;
}

.linked-table :deep(th:last-child),
.linked-table :deep(td:last-child) {
  white-space: nowrap;
}

.empty-linked {
  color: #64748b;
  padding: 14px 0 4px;
}

.program-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  flex-wrap: wrap;
  margin-top: 20px;
}

.detail-kpi-card {
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: rgba(255, 255, 255, 0.9);
  border-radius: 14px;
  padding: 14px 16px;
  display: grid;
  gap: 4px;
}

.detail-kpi-card span {
  color: #64748b;
  font-size: 0.85rem;
}

.detail-kpi-card strong {
  color: #0f172a;
  font-size: 1.2rem;
  font-weight: 800;
}

.empty-state-title {
  font-size: 1.1rem;
  font-weight: 800;
  color: #0f172a;
}

.empty-state-subtitle {
  color: #64748b;
}

.modal-header {
  padding: 20px 24px;
}

.modal-title {
  color: #0f172a;
  font-size: 1.1rem;
  font-weight: 800;
}

.modal-subtitle {
  color: #64748b;
  font-size: 0.92rem;
}

.modal-body {
  padding: 20px 24px;
}

.modal-footer {
  padding: 12px 24px 20px;
}

@media (min-width: 960px) {
  .hero-grid {
    grid-template-columns: 2fr 1fr;
    align-items: center;
  }

  .filters-inline {
    grid-template-columns: minmax(0, 1fr) minmax(220px, 0.9fr) auto;
    align-items: center;
  }

  .program-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .program-kpis {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}
</style>
