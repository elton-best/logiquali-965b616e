<template>
  <v-card-text class="pa-6">
    <div class="d-flex justify-space-between align-center mb-6">
      <h3 class="text-h6">Constatations d'audit</h3>
      <v-btn color="primary" size="small" @click="showAddDialog = true">
        <v-icon start>mdi-plus</v-icon>
        Ajouter une constatation
      </v-btn>
    </div>

    <!-- Filtres -->
    <v-row class="mb-4">
      <v-col cols="12" md="3">
        <v-select
          v-model="filters.severity"
          clearable
          density="compact"
          hide-details
          :items="severityOptions"
          label="Gravité"
          variant="outlined"
        />
      </v-col>
      <v-col cols="12" md="3">
        <v-select
          v-model="filters.status"
          clearable
          density="compact"
          hide-details
          :items="statusOptions"
          label="Statut"
          variant="outlined"
        />
      </v-col>
    </v-row>

    <!-- Liste des constatations -->
    <v-row>
      <v-col
        v-for="finding in filteredFindings"
        :key="finding.id"
        cols="12"
        md="6"
      >
        <v-card class="finding-card" variant="outlined">
          <v-card-title class="d-flex align-center justify-space-between">
            <div class="d-flex align-center">
              <v-chip
                class="mr-2"
                :color="getSeverityColor(finding.severity)"
                size="small"
                variant="flat"
              >
                {{ getSeverityLabel(finding.severity) }}
              </v-chip>
              <span class="text-body-2">{{ finding.reference }}</span>
            </div>
            <v-menu>
              <template #activator="{ props: menuProps }">
                <v-btn icon="mdi-dots-vertical" size="small" variant="text" v-bind="menuProps" />
              </template>
              <v-list>
                <v-list-item @click="editFinding(finding)">
                  <template #prepend>
                    <v-icon>mdi-pencil</v-icon>
                  </template>
                  <v-list-item-title>Modifier</v-list-item-title>
                </v-list-item>
                <v-list-item @click="deleteFinding(finding)">
                  <template #prepend>
                    <v-icon>mdi-delete</v-icon>
                  </template>
                  <v-list-item-title>Supprimer</v-list-item-title>
                </v-list-item>
              </v-list>
            </v-menu>
          </v-card-title>

          <v-card-text>
            <h4 class="font-weight-medium mb-2">{{ finding.title }}</h4>
            <p class="text-body-2 text-medium-emphasis mb-3">{{ finding.description }}</p>

            <v-divider class="my-3" />

            <div v-if="finding.clause_iso" class="text-caption mb-2">
              <strong>Clause ISO:</strong> {{ finding.clause_iso }}
            </div>

            <div v-if="finding.evidence" class="text-caption mb-2">
              <strong>Preuves:</strong> {{ finding.evidence }}
            </div>

            <div v-if="finding.recommendation" class="text-caption mb-2">
              <strong>Recommandation:</strong> {{ finding.recommendation }}
            </div>

            <div class="d-flex align-center justify-space-between mt-3">
              <v-chip :color="getStatusColor(finding.status)" size="small" variant="flat">
                {{ getStatusLabel(finding.status) }}
              </v-chip>

              <div class="d-flex gap-2">
                <v-chip
                  v-if="finding.nc_generated"
                  color="warning"
                  prepend-icon="mdi-alert"
                  size="small"
                >
                  NC générée
                </v-chip>

                <v-chip
                  v-if="finding.action_required"
                  color="error"
                  prepend-icon="mdi-check-circle"
                  size="small"
                >
                  Action requise
                </v-chip>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Empty state -->
    <div v-if="!findings || findings.length === 0" class="text-center pa-12">
      <v-icon color="grey-lighten-1" size="64">mdi-alert-circle-outline</v-icon>
      <h3 class="text-h6 mt-4 mb-2">Aucune constatation</h3>
      <p class="text-medium-emphasis mb-4">Ajoutez des constatations suite à l'audit terrain</p>
      <v-btn color="primary" @click="showAddDialog = true">
        <v-icon start>mdi-plus</v-icon>
        Ajouter une constatation
      </v-btn>
    </div>

    <!-- Dialog Ajout/Édition -->
    <v-dialog v-model="showAddDialog" max-width="800" scrollable>
      <v-card>
        <v-card-title>
          {{ editingFinding ? 'Modifier' : 'Ajouter' }} une constatation
        </v-card-title>

        <v-card-text>
          <v-row>
            <v-col cols="12" md="6">
              <v-select
                v-model="form.severity"
                :items="severityOptions"
                label="Gravité *"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12" md="6">
              <v-select
                v-model="form.category"
                :items="categoryOptions"
                label="Catégorie *"
                variant="outlined"
              />
            </v-col>

            <v-col cols="12">
              <v-text-field
                v-model="form.title"
                label="Titre *"
                variant="outlined"
              />
            </v-col>

            <v-col cols="12">
              <v-textarea
                v-model="form.description"
                label="Description *"
                rows="4"
                variant="outlined"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.clause_iso"
                label="Clause ISO"
                placeholder="Ex: 7.1.6, 8.5.1"
                variant="outlined"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.location"
                label="Localisation"
                variant="outlined"
              />
            </v-col>

            <v-col cols="12">
              <v-textarea
                v-model="form.evidence"
                label="Preuves objectives"
                rows="2"
                variant="outlined"
              />
            </v-col>

            <v-col cols="12">
              <v-textarea
                v-model="form.recommendation"
                label="Recommandation"
                rows="2"
                variant="outlined"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-switch
                v-model="form.action_required"
                color="primary"
                label="Action requise"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-switch
                v-model="form.nc_generated"
                color="warning"
                label="Générer une NC"
              />
            </v-col>
          </v-row>
        </v-card-text>

        <v-card-actions>
          <v-spacer />
          <v-btn @click="closeDialog">Annuler</v-btn>
          <v-btn color="primary" :loading="saving" @click="saveFinding">
            {{ editingFinding ? 'Modifier' : 'Ajouter' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-card-text>
</template>

<script setup lang="ts">
  import type { AuditFinding, CreateAuditFindingPayload } from '@/types/audit'
  import type { FindingSeverity } from '@/types/shared'
  import { computed, ref } from 'vue'
  import { useSnackbar } from '@/composables/useSnackbar'
  import { auditFindingService } from '@/services/auditFindingService'

  const props = defineProps<{
    auditId: number
    findings?: AuditFinding[]
  }>()

  const emit = defineEmits<{
    update: []
  }>()

  const { showSuccess, showError } = useSnackbar()

  const showAddDialog = ref(false)
  const editingFinding = ref<AuditFinding | null>(null)
  const saving = ref(false)
  interface FindingForm extends Partial<CreateAuditFindingPayload> {
    nc_generated?: boolean
  }

  const filters = ref({
    severity: null as FindingSeverity | null,
    status: null as string | null,
  })

  const form = ref<FindingForm>({
    severity: 'mineur',
    category: 'non_conformite',
    title: '',
    description: '',
    clause_iso: '',
    location: '',
    evidence: '',
    recommendation: '',
    action_required: false,
    nc_generated: false,
  })

  const severityOptions = [
    { title: 'NC Majeure', value: 'majeur' },
    { title: 'NC Mineure', value: 'mineur' },
    { title: 'Observation', value: 'observation' },
    { title: 'Opportunité', value: 'opportunite' },
  ]

  const statusOptions = [
    { title: 'Ouverte', value: 'open' },
    { title: 'En cours', value: 'in_progress' },
    { title: 'Résolue', value: 'resolved' },
    { title: 'Vérifiée', value: 'verified' },
    { title: 'Fermée', value: 'closed' },
  ]

  const categoryOptions = [
    { title: 'Non-conformité', value: 'non_conformite' },
    { title: 'Observation', value: 'observation' },
    { title: 'Bonne pratique', value: 'bonne_pratique' },
    { title: 'Opportunité', value: 'opportunite' },
  ]

  const filteredFindings = computed(() => {
    if (!props.findings) return []

    return props.findings.filter(f => {
      if (filters.value.severity && f.severity !== filters.value.severity) return false
      if (filters.value.status && f.status !== filters.value.status) return false
      return true
    })
  })

  function getSeverityColor (severity: FindingSeverity): string {
    const colors: Record<FindingSeverity, string> = {
      majeur: 'error',
      mineur: 'warning',
      observation: 'info',
      opportunite: 'success',
    }
    return colors[severity] || 'grey'
  }

  function getSeverityLabel (severity: FindingSeverity): string {
    const labels: Record<FindingSeverity, string> = {
      majeur: 'NC Majeure',
      mineur: 'NC Mineure',
      observation: 'Observation',
      opportunite: 'Opportunité',
    }
    return labels[severity] || severity
  }

  function getStatusColor (status: string): string {
    const colors: Record<string, string> = {
      open: 'error',
      in_progress: 'warning',
      resolved: 'info',
      verified: 'success',
      closed: 'grey',
    }
    return colors[status] || 'grey'
  }

  function getStatusLabel (status: string): string {
    const labels: Record<string, string> = {
      open: 'Ouverte',
      in_progress: 'En cours',
      resolved: 'Résolue',
      verified: 'Vérifiée',
      closed: 'Fermée',
    }
    return labels[status] || status
  }

  function editFinding (finding: any) {
    editingFinding.value = finding
    form.value = { ...finding }
    showAddDialog.value = true
  }

  async function saveFinding () {
    saving.value = true
    try {
      const { nc_generated: _ncGenerated, ...payload } = form.value
      if (editingFinding.value) {
        await auditFindingService.update(editingFinding.value.id, payload)
        showSuccess('Constatation modifiée')
      } else {
        const createPayload: CreateAuditFindingPayload = {
          severity: form.value.severity || 'mineur',
          category: form.value.category || 'non_conformite',
          title: form.value.title || '',
          description: form.value.description || '',
          evidence: form.value.evidence,
          requirement: form.value.requirement,
          recommendation: form.value.recommendation,
          location: form.value.location,
          responsible_id: form.value.responsible_id,
          action_required: Boolean(form.value.action_required),
          action_deadline: form.value.action_deadline,
          audit_id: props.auditId,
          detected_at: new Date().toISOString(),
        }
        await auditFindingService.create(createPayload)
        showSuccess('Constatation ajoutée')
      }
      closeDialog()
      emit('update')
    } catch {
      showError('Erreur lors de la sauvegarde')
    } finally {
      saving.value = false
    }
  }

  async function deleteFinding (finding: any) {
    if (!confirm('Supprimer cette constatation ?')) return

    try {
      await auditFindingService.delete(finding.id)
      showSuccess('Constatation supprimée')
      emit('update')
    } catch {
      showError('Erreur lors de la suppression')
    }
  }

  function closeDialog () {
    showAddDialog.value = false
    editingFinding.value = null
    form.value = {
      severity: 'mineur',
      category: 'non_conformite',
      title: '',
      description: '',
      clause_iso: '',
      location: '',
      evidence: '',
      recommendation: '',
      action_required: false,
      nc_generated: false,
    }
  }
</script>

<style scoped>
.finding-card {
  transition: all 0.3s;
}

.finding-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
</style>
