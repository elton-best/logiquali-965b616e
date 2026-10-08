<template>
  <v-container class="audit-program-list pa-6" fluid>
    <!-- Header -->
    <v-row align="center" class="mb-6">
      <v-col cols="12" md="6">
        <h1 class="text-h4 font-weight-bold">
          <v-icon class="mr-2" size="32">mdi-calendar-check</v-icon>
          Programmes d'Audits
        </h1>
        <p class="text-body-2 text-grey mt-2">
          Planification annuelle des audits internes conformes ISO 9001:2015 §9.2
        </p>
      </v-col>
      <v-col class="text-md-right" cols="12" md="6">
        <v-btn
          color="primary"
          prepend-icon="mdi-plus"
          size="large"
          @click="openCreateDialog"
        >
          Nouveau Programme
        </v-btn>
      </v-col>
    </v-row>

    <!-- Filters -->
    <v-card class="mb-6 filters-card" elevation="0" outlined>
      <v-card-text>
        <v-row>
          <v-col cols="12" md="3">
            <v-select
              v-model="filters.year"
              clearable
              :items="yearOptions"
              label="Année"
              prepend-inner-icon="mdi-calendar"
              @update:model-value="loadPrograms"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="filters.status"
              clearable
              :items="statusOptions"
              label="Statut"
              prepend-inner-icon="mdi-filter"
              @update:model-value="loadPrograms"
            />
          </v-col>
          <v-col class="d-flex align-center justify-end" cols="12" md="6">
            <v-chip-group>
              <v-chip
                :color="filters.status === '' ? 'primary' : 'default'"
                @click="filters.status = ''; loadPrograms()"
              >
                Tous ({{ programs.length }})
              </v-chip>
              <v-chip
                :color="filters.status === 'draft' ? 'warning' : 'default'"
                @click="filters.status = 'draft'; loadPrograms()"
              >
                Brouillons ({{ draftPrograms.length }})
              </v-chip>
              <v-chip
                :color="filters.status === 'in_progress' ? 'info' : 'default'"
                @click="filters.status = 'in_progress'; loadPrograms()"
              >
                En cours ({{ activePrograms.length }})
              </v-chip>
            </v-chip-group>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- Programs Grid -->
    <v-row v-if="!loading && programs.length > 0">
      <v-col
        v-for="program in programs"
        :key="program.id"
        cols="12"
        lg="4"
        md="6"
      >
        <v-card
          class="program-card"
          :class="{ 'border-primary': program.status === 'validated' }"
          elevation="2"
          hover
          rounded="xl"
          @click="viewProgram(program.id)"
        >
          <v-card-title class="d-flex align-center">
            <v-icon class="mr-2" :color="getStatusColor(program.status)">
              {{ getStatusIcon(program.status) }}
            </v-icon>
            <span class="text-truncate">{{ program.title }}</span>
          </v-card-title>

          <v-card-subtitle>
            <v-chip class="mr-2" :color="getStatusColor(program.status)" size="small">
              {{ program.status_label }}
            </v-chip>
            <v-chip color="grey" size="small" variant="outlined">
              {{ program.ref }}
            </v-chip>
          </v-card-subtitle>

          <v-card-text>
            <v-row dense>
              <v-col cols="6">
                <div class="text-caption text-grey">Audits planifiés</div>
                <div class="text-h6">{{ program.planned_audits_count }}</div>
              </v-col>
              <v-col cols="6">
                <div class="text-caption text-grey">Réalisés</div>
                <div class="text-h6 text-success">{{ program.completed_audits_count }}</div>
              </v-col>
              <v-col cols="6">
                <div class="text-caption text-grey">Taux réalisation</div>
                <v-progress-linear
                  class="mt-1"
                  :color="(program.completion_rate ?? 0) >= 80 ? 'success' : 'warning'"
                  height="8"
                  :model-value="program.completion_rate ?? 0"
                  rounded
                />
                <div class="text-caption mt-1">{{ program.completion_rate }}%</div>
              </v-col>
              <v-col cols="6">
                <div class="text-caption text-grey">Conformité</div>
                <div class="text-h6" :class="getConformityColor(program.conformity_rate)">
                  {{ program.conformity_rate ?? 'N/A' }}{{ program.conformity_rate ? '%' : '' }}
                </div>
              </v-col>
            </v-row>

            <v-divider class="my-3" />

            <div class="d-flex align-center text-caption text-grey">
              <v-icon class="mr-1" size="small">mdi-account</v-icon>
              {{ program.program_manager?.name || 'Non assigné' }}
            </div>
          </v-card-text>

          <v-card-actions>
            <v-btn
              v-if="program.status === 'draft'"
              color="success"
              size="small"
              variant="text"
              @click.stop="validateProgram(program.id)"
            >
              <v-icon start>mdi-check-circle</v-icon>
              Valider
            </v-btn>
            <v-btn
              size="small"
              variant="text"
              @click.stop="exportCalendar(program.id)"
            >
              <v-icon start>mdi-download</v-icon>
              Export XLSX
            </v-btn>
            <v-spacer />
            <v-btn
              icon
              size="small"
              @click.stop="openDeleteDialog(program)"
            >
              <v-icon>mdi-delete</v-icon>
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>

    <!-- Loading -->
    <v-row v-if="loading">
      <v-col
        v-for="i in 3"
        :key="i"
        cols="12"
        lg="4"
        md="6"
      >
        <v-skeleton-loader type="card" />
      </v-col>
    </v-row>

    <!-- Empty State -->
    <v-card v-if="!loading && programs.length === 0" class="pa-12 text-center" elevation="0">
      <v-icon color="grey-lighten-2" size="120">mdi-calendar-check-outline</v-icon>
      <h2 class="text-h5 mt-6 mb-2">Aucun programme d'audits</h2>
      <p class="text-body-2 text-grey mb-6">
        Créez votre premier programme annuel pour planifier vos audits internes
      </p>
      <v-btn color="primary" size="large" @click="openCreateDialog">
        <v-icon start>mdi-plus</v-icon>
        Créer un programme
      </v-btn>
    </v-card>

    <!-- Create/Edit Dialog -->
    <v-dialog v-model="dialog" max-width="800">
      <v-card class="modal-card" rounded="xl">
        <v-card-title class="modal-title d-flex align-center justify-space-between">
          <div class="d-flex align-center ga-3">
            <v-avatar color="primary" size="40" variant="tonal">
              <v-icon>mdi-calendar-check</v-icon>
            </v-avatar>
            <div>
              <div class="text-subtitle-1 font-weight-bold">
                {{ editingProgram ? 'Modifier' : 'Nouveau' }} Programme d'Audits
              </div>
              <div class="text-caption text-medium-emphasis">Cadrez la planification annuelle.</div>
            </div>
          </div>
          <v-btn icon="mdi-close" variant="text" @click="dialog = false" />
        </v-card-title>
        <v-divider />
        <v-card-text>
          <v-form ref="formRef" v-model="formValid">
            <v-row>
              <v-col cols="12" md="6">
                <v-select
                  v-model="formData.year"
                  :items="futureYears"
                  label="Année *"
                  required
                  :rules="[v => !!v || 'Année requise']"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="formData.planned_audits_count"
                  label="Nombre d'audits planifiés"
                  min="1"
                  type="number"
                />
              </v-col>
              <v-col cols="12">
                <v-text-field
                  v-model="formData.title"
                  label="Titre (optionnel)"
                  placeholder="Programme Audits Internes 2026"
                />
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="formData.objectives"
                  label="Objectifs"
                  placeholder="Vérifier la conformité aux exigences ISO 9001:2015..."
                  rows="3"
                />
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>
        <v-card-actions class="px-6 pb-6">
          <v-spacer />
          <v-btn @click="dialog = false">Annuler</v-btn>
          <v-btn color="primary" :loading="loading" @click="saveProgram">
            {{ editingProgram ? 'Modifier' : 'Créer' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup lang="ts">
  import type { AuditProgram } from '@/types/models/auditPrograms'
  import { computed, onMounted, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import { useToast } from 'vue-toastification'
  import { useAuditProgramStore } from '@/stores/improvement/auditProgramStore'
  import { getErrorMessage } from '@/utils/errorMessage'

  const router = useRouter()
  const store = useAuditProgramStore()
  const toast = useToast()

  // State
  const loading = ref(false)
  const dialog = ref(false)
  const formValid = ref(false)
  const formRef = ref()
  const editingProgram = ref<AuditProgram | null>(null)

  const filters = ref({
    year: new Date().getFullYear(),
    status: '',
    per_page: 20,
  })

  const formData = ref({
    year: new Date().getFullYear(),
    title: '',
    objectives: '',
    planned_audits_count: 12,
    program_manager_id: 0, // À définir selon user connecté
    site_id: 0, // À définir selon site actif
  })

  // Computed
  const programs = computed(() => store.programs)
  const activePrograms = computed(() => store.activePrograms)
  const draftPrograms = computed(() => store.draftPrograms)

  const yearOptions = computed(() => {
    const years = []
    for (let i = -2; i <= 2; i++) {
      years.push(new Date().getFullYear() + i)
    }
    return years
  })

  const futureYears = computed(() => {
    const years = []
    for (let i = 0; i <= 3; i++) {
      years.push(new Date().getFullYear() + i)
    }
    return years
  })

  const statusOptions = [
    { title: 'Brouillon', value: 'draft' },
    { title: 'Validé', value: 'validated' },
    { title: 'En cours', value: 'in_progress' },
    { title: 'Terminé', value: 'completed' },
  ]

  // Methods
  async function loadPrograms () {
    loading.value = true
    try {
      await store.fetchPrograms(filters.value)
    } catch (error: any) {
      toast.error(getErrorMessage(error, 'Impossible de charger les programmes d’audit.'))
    } finally {
      loading.value = false
    }
  }

  function openCreateDialog () {
    editingProgram.value = null
    formData.value = {
      year: new Date().getFullYear(),
      title: '',
      objectives: '',
      planned_audits_count: 12,
      program_manager_id: 0,
      site_id: 0,
    }
    dialog.value = true
  }

  async function saveProgram () {
    if (!formRef.value?.validate()) return

    try {
      if (editingProgram.value) {
        await store.updateProgram(editingProgram.value.id, formData.value)
        toast.success('Programme modifié')
      } else {
        await store.createProgram(formData.value)
        toast.success('Programme créé')
      }
      dialog.value = false
      await loadPrograms()
    } catch (error: any) {
      toast.error(getErrorMessage(error, 'Impossible d’enregistrer le programme.'))
    }
  }

  async function validateProgram (id: number) {
    try {
      await store.validateProgram(id)
      toast.success('Programme validé')
      await loadPrograms()
    } catch (error: any) {
      toast.error(getErrorMessage(error, 'Impossible de valider le programme.'))
    }
  }

  async function exportCalendar (id: number) {
    try {
      await store.exportCalendar(id)
      toast.success('Calendrier exporté')
    } catch (error: any) {
      toast.error(getErrorMessage(error, 'Impossible d’exporter le calendrier.'))
    }
  }

  function viewProgram (id: number) {
    router.push(`/quality/audits/program/${id}`)
  }

  function getStatusColor (status: string) {
    const colors: Record<string, string> = {
      draft: 'warning',
      validated: 'info',
      in_progress: 'primary',
      completed: 'success',
      archived: 'grey',
    }
    return colors[status] || 'grey'
  }

  function getStatusIcon (status: string) {
    const icons: Record<string, string> = {
      draft: 'mdi-pencil',
      validated: 'mdi-check-circle',
      in_progress: 'mdi-play-circle',
      completed: 'mdi-check-all',
      archived: 'mdi-archive',
    }
    return icons[status] || 'mdi-help-circle'
  }

  function getConformityColor (rate?: number) {
    if (!rate) return 'text-grey'
    if (rate >= 90) return 'text-success'
    if (rate >= 75) return 'text-info'
    if (rate >= 60) return 'text-warning'
    return 'text-error'
  }

  function openDeleteDialog (program: AuditProgram) {
    // TODO: Implémenter dialog de confirmation
    console.log('Delete', program)
  }

  onMounted(() => {
    loadPrograms()
  })
</script>

<style scoped>
.audit-program-list {
  max-width: 1400px;
  margin: 0 auto;
}

.border-primary {
  border: 2px solid rgb(var(--v-theme-primary));
}

.filters-card {
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.95));
}

.program-card {
  border: 1px solid rgba(15, 23, 42, 0.08);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.program-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
}

.modal-card {
  border: 1px solid rgba(15, 23, 42, 0.08);
}

.modal-title {
  padding: 20px 24px;
}
</style>
