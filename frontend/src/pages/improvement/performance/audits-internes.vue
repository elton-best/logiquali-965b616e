<template>
  <ClientALayout current-page="performance-audits">
    <v-container class="pa-6" fluid>
      <PageHeader icon="mdi-clipboard-check-outline" title="Audits Internes">
        <template #subtitle>
          Planification et évaluation des audits internes (M9-D1/D5)
        </template>
        <template #actions>
          <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreateDialog">
            Planifier un audit
          </v-btn>
        </template>
      </PageHeader>

      <v-row class="mt-6">
        <v-col cols="12" md="3" sm="6">
          <v-card rounded="xl">
            <v-card-text class="text-center">
              <v-icon color="primary" size="40">mdi-calendar-check</v-icon>
              <div class="text-h4 mt-2 font-weight-bold">{{ stats.planned }}</div>
              <div class="text-caption text-grey">Planifiés</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="3" sm="6">
          <v-card rounded="xl">
            <v-card-text class="text-center">
              <v-icon color="warning" size="40">mdi-progress-clock</v-icon>
              <div class="text-h4 mt-2 font-weight-bold">{{ stats.inProgress }}</div>
              <div class="text-caption text-grey">En cours</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="3" sm="6">
          <v-card rounded="xl">
            <v-card-text class="text-center">
              <v-icon color="success" size="40">mdi-check-circle</v-icon>
              <div class="text-h4 mt-2 font-weight-bold">{{ stats.completed }}</div>
              <div class="text-caption text-grey">Terminés</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="3" sm="6">
          <v-card rounded="xl">
            <v-card-text class="text-center">
              <v-icon color="info" size="40">mdi-chart-line</v-icon>
              <div class="text-h4 mt-2 font-weight-bold">{{ stats.conformityRate }}%</div>
              <div class="text-caption text-grey">Taux de conformité</div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <v-tabs v-model="activeTab" class="mt-6" color="primary">
        <v-tab value="planning">Planification</v-tab>
        <v-tab value="evaluation">Évaluation</v-tab>
        <v-tab value="program">Programme annuel</v-tab>
      </v-tabs>

      <v-window v-model="activeTab" class="mt-6">
        <!-- Planification -->
        <v-window-item value="planning">
          <v-card rounded="xl">
            <v-card-text>
              <v-data-table
                :headers="planningHeaders"
                :items="audits"
                items-per-page="10"
                :loading="loading"
              >
                <template #[`item.status`]="{ item }">
                  <v-chip :color="getStatusColor(item.status)" size="small" variant="tonal">
                    {{ getStatusLabel(item.status) }}
                  </v-chip>
                </template>
                <template #[`item.type`]="{ item }">
                  <v-chip color="primary" size="small" variant="outlined">
                    {{ item.type }}
                  </v-chip>
                </template>
                <template #[`item.actions`]="{ item }">
                  <v-btn icon="mdi-eye" size="small" variant="text" @click="viewAudit(item)" />
                  <v-btn icon="mdi-pencil" size="small" variant="text" @click="editAudit(item)" />
                  <v-btn icon="mdi-file-pdf-box" size="small" variant="text" @click="exportPdf(item.id)" />
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-window-item>

        <!-- Évaluation -->
        <v-window-item value="evaluation">
          <v-card rounded="xl">
            <v-card-text>
              <v-data-table
                :headers="evaluationHeaders"
                :items="evaluations"
                items-per-page="10"
                :loading="loading"
              >
                <template #[`item.conformity`]="{ item }">
                  <v-chip :color="getConformityColor(item.conformity)" size="small" variant="tonal">
                    {{ item.conformity }}%
                  </v-chip>
                </template>
                <template #[`item.nc_count`]="{ item }">
                  <v-chip :color="item.nc_count > 0 ? 'error' : 'success'" size="small" variant="tonal">
                    {{ item.nc_count }}
                  </v-chip>
                </template>
                <template #[`item.actions`]="{ item }">
                  <v-btn icon="mdi-eye" size="small" variant="text" @click="viewEvaluation(item)" />
                  <v-btn icon="mdi-file-document" size="small" variant="text" @click="viewReport(item)" />
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-window-item>

        <!-- Programme annuel -->
        <v-window-item value="program">
          <v-card rounded="xl">
            <v-card-title class="pa-6">Programme d'audit {{ currentYear }}</v-card-title>
            <v-card-text>
              <v-timeline side="end">
                <v-timeline-item
                  v-for="item in program"
                  :key="item.id"
                  :dot-color="item.completed ? 'success' : 'primary'"
                  size="small"
                >
                  <template #opposite>
                    <div class="text-caption">{{ item.date }}</div>
                  </template>
                  <v-card rounded="lg" variant="outlined">
                    <v-card-text>
                      <div class="font-weight-medium">{{ item.title }}</div>
                      <div class="text-caption text-grey mt-1">{{ item.scope }}</div>
                      <v-chip
                        v-if="item.completed"
                        class="mt-2"
                        color="success"
                        size="small"
                        variant="tonal"
                      >
                        Réalisé
                      </v-chip>
                    </v-card-text>
                  </v-card>
                </v-timeline-item>
              </v-timeline>
            </v-card-text>
          </v-card>
        </v-window-item>
      </v-window>

      <v-dialog v-model="showDialog" max-width="900">
        <v-card rounded="xl">
          <v-card-title class="pa-6">{{ editingId ? 'Modifier' : 'Planifier' }} un audit</v-card-title>
          <v-card-text class="px-6">
            <v-row>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.title" label="Titre" variant="outlined" />
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="form.type"
                  :items="['Processus', 'Produit', 'Système']"
                  label="Type"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.start_date" label="Date début" type="date" variant="outlined" />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.end_date" label="Date fin" type="date" variant="outlined" />
              </v-col>
              <v-col cols="12">
                <v-text-field v-model="form.scope" label="Périmètre" variant="outlined" />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.lead_auditor" label="Auditeur principal" variant="outlined" />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.team" label="Équipe d'audit" variant="outlined" />
              </v-col>
              <v-col cols="12">
                <v-textarea v-model="form.objectives" label="Objectifs" rows="3" variant="outlined" />
              </v-col>
            </v-row>
          </v-card-text>
          <v-card-actions class="px-6 pb-6">
            <v-spacer />
            <v-btn variant="text" @click="showDialog = false">Annuler</v-btn>
            <v-btn color="primary" :loading="saving" @click="save">Enregistrer</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, reactive, ref } from 'vue'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'

  const activeTab = ref('planning')
  const loading = ref(false)
  const saving = ref(false)
  const showDialog = ref(false)
  const editingId = ref<number | null>(null)

  const audits = ref<any[]>([])
  const evaluations = ref<any[]>([])
  const program = ref<any[]>([])

  const stats = ref({
    planned: 5,
    inProgress: 2,
    completed: 8,
    conformityRate: 92,
  })

  const currentYear = computed(() => new Date().getFullYear())

  const planningHeaders = [
    { title: 'Référence', key: 'reference' },
    { title: 'Titre', key: 'title' },
    { title: 'Type', key: 'type' },
    { title: 'Date début', key: 'start_date' },
    { title: 'Date fin', key: 'end_date' },
    { title: 'Statut', key: 'status' },
    { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
  ]

  const evaluationHeaders = [
    { title: 'Audit', key: 'audit_title' },
    { title: 'Date', key: 'date' },
    { title: 'Conformité', key: 'conformity' },
    { title: 'NC', key: 'nc_count' },
    { title: 'Observations', key: 'observations' },
    { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
  ]

  const form = reactive({
    title: '',
    type: '',
    start_date: '',
    end_date: '',
    scope: '',
    lead_auditor: '',
    team: '',
    objectives: '',
  })

  function getStatusColor (status: string) {
    const colors: Record<string, string> = {
      planned: 'primary',
      in_progress: 'warning',
      completed: 'success',
      cancelled: 'error',
    }
    return colors[status] || 'grey'
  }

  function getStatusLabel (status: string) {
    const labels: Record<string, string> = {
      planned: 'Planifié',
      in_progress: 'En cours',
      completed: 'Terminé',
      cancelled: 'Annulé',
    }
    return labels[status] || status
  }

  function getConformityColor (rate: number) {
    if (rate >= 90) return 'success'
    if (rate >= 70) return 'warning'
    return 'error'
  }

  function openCreateDialog () {
    editingId.value = null
    Object.assign(form, {
      title: '',
      type: '',
      start_date: '',
      end_date: '',
      scope: '',
      lead_auditor: '',
      team: '',
      objectives: '',
    })
    showDialog.value = true
  }

  function editAudit (item: any) {
    editingId.value = item.id
    Object.assign(form, item)
    showDialog.value = true
  }

  function viewAudit (item: any) {
    console.log('View audit:', item)
  }

  function viewEvaluation (item: any) {
    console.log('View evaluation:', item)
  }

  function viewReport (item: any) {
    console.log('View report:', item)
  }

  async function exportPdf (id: number) {
    console.log('Export PDF:', id)
  }

  async function save () {
    saving.value = true
    try {
      await new Promise(resolve => setTimeout(resolve, 1000))
      showDialog.value = false
    } finally {
      saving.value = false
    }
  }

  onMounted(() => {
    // Load data
  })
</script>
