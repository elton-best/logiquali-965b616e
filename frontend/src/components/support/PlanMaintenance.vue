<template>
  <div>
    <v-alert
      v-if="actionMessage"
      class="mb-4"
      closable
      :color="actionMessageType"
      variant="tonal"
      @click:close="actionMessage = ''"
    >
      {{ actionMessage }}
    </v-alert>

    <v-card class="section-banner mb-6" elevation="0" rounded="xl">
      <v-card-text class="pa-5 d-flex flex-wrap align-center justify-space-between ga-3">
        <div>
          <div class="section-banner__kicker">Ressources</div>
          <h2 class="section-banner__title">Plan de maintenance</h2>
          <p class="section-banner__subtitle">Planifiez, suivez et ajustez les interventions préventives, correctives et d’étalonnage.</p>
        </div>
        <v-chip color="teal" variant="tonal">Étape 3</v-chip>
      </v-card-text>
    </v-card>

    <v-card elevation="1" rounded="lg">
      <v-card-title class="pa-6 d-flex align-center">
        <v-icon color="primary" start>mdi-calendar-clock</v-icon>
        Plan de maintenance et d'étalonnage
        <v-spacer />
        <v-btn
          class="mr-2"
          color="primary"
          :loading="store.loading"
          prepend-icon="mdi-upload"
          variant="outlined"
          @click="showImportDialog = true"
        >
          Importer
        </v-btn>
        <v-btn
          class="mr-2"
          color="primary"
          :loading="exporting"
          prepend-icon="mdi-download"
          variant="outlined"
          @click="exportPlan"
        >
          Exporter
        </v-btn>
        <v-btn color="primary" prepend-icon="mdi-plus" @click="showAddDialog = true">
          Planifier
        </v-btn>
      </v-card-title>
      <v-divider />

      <v-card-text class="pa-6 pb-0">
        <v-row class="mb-2">
          <v-col cols="12" md="3">
            <v-card class="pa-4" color="primary" variant="tonal">
              <div class="text-caption">Total</div>
              <div class="text-h6 font-weight-bold">{{ maintenances.length }}</div>
            </v-card>
          </v-col>
          <v-col cols="12" md="3">
            <v-card class="pa-4" color="warning" variant="tonal">
              <div class="text-caption">Planifiées</div>
              <div class="text-h6 font-weight-bold">{{ plannedCount }}</div>
            </v-card>
          </v-col>
          <v-col cols="12" md="3">
            <v-card class="pa-4" color="info" variant="tonal">
              <div class="text-caption">Avec Alerte</div>
              <div class="text-h6 font-weight-bold">{{ alertCount }}</div>
            </v-card>
          </v-col>
          <v-col cols="12" md="3">
            <v-card class="pa-4" color="success" variant="tonal">
              <div class="text-caption">Réalisées</div>
              <div class="text-h6 font-weight-bold">{{ doneCount }}</div>
            </v-card>
          </v-col>
        </v-row>

        <v-row>
          <v-col cols="12" md="4">
            <v-text-field
              v-model="searchQuery"
              clearable
              density="comfortable"
              label="Rechercher (équipement, code, responsable)"
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-select
              v-model="statusFilter"
              clearable
              density="comfortable"
              :items="statusFilterItems"
              label="Filtrer par statut"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-select
              v-model="typeFilter"
              clearable
              density="comfortable"
              :items="typeFilterItems"
              label="Filtrer par type"
              variant="outlined"
            />
          </v-col>
        </v-row>
      </v-card-text>

      <v-card-text class="pa-0">
        <v-data-table
          class="elevation-0"
          :headers="headers"
          :items="filteredMaintenances"
          :items-per-page="15"
          :loading="store.loading"
        >
          <template #no-data>
            <div class="text-center pa-6">
              <v-icon color="grey-lighten-1" size="64">mdi-calendar-clock</v-icon>
              <p class="text-h6 mt-4">Aucune maintenance planifiée</p>
              <p class="text-body-2 text-medium-emphasis">Cliquez sur "Planifier" pour ajouter une maintenance</p>
            </div>
          </template>
          <template #item.equipement="{ item }">
            <div>
              <div class="font-weight-medium">{{ item.equipement?.nom_commun }}</div>
              <code class="text-caption text-medium-emphasis">{{ item.equipement?.code_complet }}</code>
            </div>
          </template>

          <template #item.type="{ item }">
            <v-chip :color="getTypeColor(item.type)" size="small" variant="tonal">
              {{ getTypeLabel(item.type) }}
            </v-chip>
          </template>

          <template #item.date_prevue="{ item }">
            {{ formatDate(item.date_prevue) }}
          </template>

          <template #item.statut="{ item }">
            <v-chip :color="getStatutColor(item.statut)" size="small" variant="tonal">
              {{ getStatutLabel(item.statut) }}
            </v-chip>
          </template>

          <template #item.alerte="{ item }">
            <v-chip
              v-if="item.niveau_alerte"
              :color="getAlerteColor(item.niveau_alerte)"
              size="small"
              variant="flat"
            >
              {{ getAlerteLabel(item.niveau_alerte) }}
            </v-chip>
          </template>

          <template #item.actions="{ item }">
            <v-btn
              class="mr-2"
              color="primary"
              icon="mdi-pencil"
              size="small"
              variant="text"
              @click="openEditDialog(item)"
            />
            <v-btn
              class="mr-2"
              color="success"
              :disabled="!item.suivi_active"
              prepend-icon="mdi-clipboard-check"
              size="small"
              variant="tonal"
              @click="openSuiviDialog(item)"
            >
              Suivi
            </v-btn>
            <v-btn
              color="error"
              icon="mdi-delete"
              size="small"
              variant="text"
              @click="openDeleteDialog(item)"
            />
          </template>
        </v-data-table>
      </v-card-text>
    </v-card>

    <AddMaintenanceDialog v-model="showAddDialog" @added="loadMaintenances" />
    <SuiviMaintenanceDialog v-model="showSuiviDialog" :maintenance="selectedMaintenance" @updated="loadMaintenances" />
    <ImportMaintenanceDialog v-model="showImportDialog" @imported="loadMaintenances" />

    <v-dialog v-model="showEditDialog" max-width="700">
      <v-card rounded="lg">
        <v-card-title class="pa-6">Modifier la maintenance</v-card-title>
        <v-divider />
        <v-card-text class="pa-6">
          <v-form @submit.prevent="saveEdit">
            <v-select
              v-model="editForm.type"
              class="mb-4"
              :items="[
                { value: 'preventive', title: 'Préventive' },
                { value: 'corrective', title: 'Corrective' },
                { value: 'etalonnage', title: 'Étalonnage' }
              ]"
              label="Type *"
              variant="outlined"
            />

            <v-text-field
              v-model="editForm.date_prevue"
              class="mb-4"
              label="Date prévue *"
              type="date"
              variant="outlined"
            />

            <v-select
              v-model="editForm.statut"
              class="mb-4"
              :items="[
                { value: 'planifie', title: 'Planifié' },
                { value: 'en_cours', title: 'En cours' },
                { value: 'realise', title: 'Réalisé' },
                { value: 'reporte', title: 'Reporté' },
                { value: 'annule', title: 'Annulé' }
              ]"
              label="Statut *"
              variant="outlined"
            />

            <v-text-field
              v-model="editForm.responsable"
              class="mb-4"
              label="Responsable"
              variant="outlined"
            />

            <v-textarea
              v-model="editForm.description"
              class="mb-4"
              label="Description"
              rows="3"
              variant="outlined"
            />

            <v-textarea
              v-model="editForm.observations"
              label="Observations"
              rows="3"
              variant="outlined"
            />

            <div class="d-flex justify-end gap-2 mt-4">
              <v-btn variant="outlined" @click="showEditDialog = false">Annuler</v-btn>
              <v-btn color="primary" :loading="loadingEdit" type="submit">Enregistrer</v-btn>
            </div>
          </v-form>
        </v-card-text>
      </v-card>
    </v-dialog>

    <v-dialog v-model="showDeleteDialog" max-width="520">
      <v-card rounded="lg">
        <v-card-title class="pa-6 d-flex align-center">
          <v-icon color="error" start>mdi-alert-circle-outline</v-icon>
          Confirmer la suppression
        </v-card-title>
        <v-divider />
        <v-card-text class="pa-6">
          <p class="mb-2">
            Supprimer cette maintenance
            <strong>{{ deletingMaintenance?.equipement?.nom_commun || '#' + deletingMaintenance?.id }}</strong>
            ?
          </p>
        </v-card-text>
        <v-card-actions class="pa-6 pt-0 justify-end">
          <v-btn variant="outlined" @click="showDeleteDialog = false">Annuler</v-btn>
          <v-btn color="error" :loading="loadingDelete" variant="flat" @click="confirmDelete">Supprimer</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup lang="ts">
  import type { Maintenance } from '@/services/supportService'
  import { computed, onMounted, ref } from 'vue'
  import { useSupportStore } from '@/stores/supportStore'
  import AddMaintenanceDialog from './AddMaintenanceDialog.vue'
  import ImportMaintenanceDialog from './ImportMaintenanceDialog.vue'
  import SuiviMaintenanceDialog from './SuiviMaintenanceDialog.vue'

  const store = useSupportStore()
  const showAddDialog = ref(false)
  const showSuiviDialog = ref(false)
  const showImportDialog = ref(false)
  const showEditDialog = ref(false)
  const showDeleteDialog = ref(false)
  const selectedMaintenance = ref<Maintenance | null>(null)
  const deletingMaintenance = ref<Maintenance | null>(null)
  const exporting = ref(false)
  const loadingEdit = ref(false)
  const loadingDelete = ref(false)
  const actionMessage = ref('')
  const actionMessageType = ref<'success' | 'error'>('success')
  const editingMaintenanceId = ref<number | null>(null)
  const searchQuery = ref('')
  const statusFilter = ref<string | null>(null)
  const typeFilter = ref<string | null>(null)
  const editForm = ref({
    type: 'preventive' as 'preventive' | 'corrective' | 'etalonnage',
    date_prevue: '',
    statut: 'planifie' as 'planifie' | 'en_cours' | 'realise' | 'reporte' | 'annule',
    responsable: '',
    description: '',
    observations: '',
  })

  const headers = [
    { title: 'Équipement', key: 'equipement', sortable: false },
    { title: 'Type', key: 'type', sortable: true },
    { title: 'Date prévue', key: 'date_prevue', sortable: true },
    { title: 'Statut', key: 'statut', sortable: true },
    { title: 'Alerte', key: 'alerte', sortable: false },
    { title: 'Responsable', key: 'responsable', sortable: true },
    { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
  ]

  const maintenances = computed(() => store.maintenances)
  const plannedCount = computed(() => maintenances.value.filter(item => item.statut === 'planifie').length)
  const alertCount = computed(() => maintenances.value.filter(item => Boolean(item.niveau_alerte)).length)
  const doneCount = computed(() => maintenances.value.filter(item => item.statut === 'realise').length)
  const statusFilterItems = [
    { title: 'Planifié', value: 'planifie' },
    { title: 'En cours', value: 'en_cours' },
    { title: 'Réalisé', value: 'realise' },
    { title: 'Reporté', value: 'reporte' },
    { title: 'Annulé', value: 'annule' },
  ]
  const typeFilterItems = [
    { title: 'Préventive', value: 'preventive' },
    { title: 'Corrective', value: 'corrective' },
    { title: 'Étalonnage', value: 'etalonnage' },
  ]
  const filteredMaintenances = computed(() => {
    const search = searchQuery.value.trim().toLowerCase()
    return maintenances.value.filter(item => {
      if (statusFilter.value && item.statut !== statusFilter.value) {
        return false
      }
      if (typeFilter.value && item.type !== typeFilter.value) {
        return false
      }
      if (!search) {
        return true
      }

      const haystack = [
        item.equipement?.nom_commun,
        item.equipement?.code_complet,
        item.responsable,
        item.description,
      ].map(value => String(value || '').toLowerCase())

      return haystack.some(value => value.includes(search))
    })
  })

  async function loadMaintenances () {
    await store.fetchMaintenances()
  }

  function openSuiviDialog (maintenance: Maintenance) {
    selectedMaintenance.value = maintenance
    showSuiviDialog.value = true
  }

  function openEditDialog (maintenance: Maintenance) {
    editingMaintenanceId.value = Number(maintenance.id)
    editForm.value = {
      type: maintenance.type,
      date_prevue: maintenance.date_prevue ? String(maintenance.date_prevue).slice(0, 10) : '',
      statut: maintenance.statut,
      responsable: maintenance.responsable || '',
      description: maintenance.description || '',
      observations: maintenance.observations || '',
    }
    showEditDialog.value = true
  }

  function openDeleteDialog (maintenance: Maintenance) {
    deletingMaintenance.value = maintenance
    showDeleteDialog.value = true
  }

  async function saveEdit () {
    if (!editingMaintenanceId.value) return

    loadingEdit.value = true
    try {
      await store.updateMaintenance(editingMaintenanceId.value, editForm.value)
      showEditDialog.value = false
      actionMessageType.value = 'success'
      actionMessage.value = 'Maintenance mise à jour avec succès.'
    } catch {
      actionMessageType.value = 'error'
      actionMessage.value = store.error || 'Erreur lors de la mise à jour de la maintenance.'
    } finally {
      loadingEdit.value = false
    }
  }

  async function confirmDelete () {
    if (!deletingMaintenance.value?.id) {
      showDeleteDialog.value = false
      return
    }

    loadingDelete.value = true
    try {
      await store.deleteMaintenance(Number(deletingMaintenance.value.id))
      actionMessageType.value = 'success'
      actionMessage.value = 'Maintenance supprimée avec succès.'
      showDeleteDialog.value = false
      deletingMaintenance.value = null
    } catch {
      actionMessageType.value = 'error'
      actionMessage.value = store.error || 'Suppression impossible pour cette maintenance.'
    } finally {
      loadingDelete.value = false
    }
  }

  const formatDate = (date: string) => new Date(date).toLocaleDateString('fr-FR')

  function getTypeColor (type: string) {
    const colors = { preventive: 'info', corrective: 'warning', etalonnage: 'purple' }
    return colors[type as keyof typeof colors] || 'default'
  }

  function getTypeLabel (type: string) {
    const labels = { preventive: 'Préventive', corrective: 'Corrective', etalonnage: 'Étalonnage' }
    return labels[type as keyof typeof labels] || type
  }

  function getStatutColor (statut: string) {
    const colors = {
      planifie: 'default',
      en_cours: 'info',
      realise: 'success',
      reporte: 'warning',
      annule: 'error',
    }
    return colors[statut as keyof typeof colors] || 'default'
  }

  function getStatutLabel (statut: string) {
    const labels = {
      planifie: 'Planifié',
      en_cours: 'En cours',
      realise: 'Réalisé',
      reporte: 'Reporté',
      annule: 'Annulé',
    }
    return labels[statut as keyof typeof labels] || statut
  }

  function getAlerteColor (niveau: string) {
    const colors = {
      depassee: 'error',
      aujourd_hui: 'warning',
      veille: 'warning',
      trois_jours: 'info',
      sept_jours: 'info',
    }
    return colors[niveau as keyof typeof colors] || 'default'
  }

  function getAlerteLabel (niveau: string) {
    const labels = {
      depassee: 'DÉPASSÉE',
      aujourd_hui: 'AUJOURD\'HUI',
      veille: 'DEMAIN',
      trois_jours: '3 JOURS',
      sept_jours: '7 JOURS',
    }
    return labels[niveau as keyof typeof labels] || niveau
  }

  async function exportPlan () {
    exporting.value = true
    try {
      await store.exportMaintenances()
    } catch (error) {
      console.error('Export error:', error)
    } finally {
      exporting.value = false
    }
  }

  onMounted(async () => {
    try {
      await store.fetchEquipements()
      await loadMaintenances()
    } catch (error) {
      console.error('Mount error:', error)
    }
  })
</script>

<style scoped>
.section-banner {
  border: 1px solid #d7e5df;
  background:
    radial-gradient(700px 140px at 10% -40%, rgba(20, 184, 166, 0.14), transparent 58%),
    linear-gradient(135deg, #f8fbfa 0%, #f6fbf8 100%);
}

.section-banner__kicker {
  font-size: 0.74rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #65756f;
}

.section-banner__title {
  margin: 0.25rem 0 0;
  font-size: 1.2rem;
  line-height: 1.25;
}

.section-banner__subtitle {
  margin: 0.35rem 0 0;
  font-size: 0.9rem;
  color: #5f6d68;
  max-width: 680px;
}
</style>
