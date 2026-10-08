<template>
  <v-card rounded="xl" variant="outlined">
    <v-card-title class="d-flex align-center ga-2">
      <v-icon color="primary">mdi-view-list-outline</v-icon>
      <span>Sections 2 à 6 - Revue métier</span>
    </v-card-title>
    <v-card-text>
      <v-row dense>
        <v-col cols="12" md="6">
          <v-card class="h-100" rounded="lg" variant="tonal">
            <v-card-title class="text-subtitle-1">2. Synthèse PIP</v-card-title>
            <v-card-text>
              <div class="text-caption text-medium-emphasis mb-2">
                Total fiches: {{ metrics.pip_total }} • Terminées: {{ metrics.pip_completed }}
              </div>
              <v-textarea
                label="Synthèse"
                :model-value="modelValue.pip_summary"
                :readonly="readonly"
                rows="3"
                variant="outlined"
                @update:model-value="updateField('pip_summary', $event)"
              />
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="6">
          <v-card class="h-100" rounded="lg" variant="tonal">
            <v-card-title class="text-subtitle-1">3. Risques / Opportunités</v-card-title>
            <v-card-text>
              <div class="text-caption text-medium-emphasis mb-2">
                Risques: {{ metrics.risks_count }} • Opportunités: {{ metrics.opportunities_count }}
              </div>
              <v-textarea
                label="Analyse et décisions"
                :model-value="modelValue.risk_opportunity_summary"
                :readonly="readonly"
                rows="3"
                variant="outlined"
                @update:model-value="updateField('risk_opportunity_summary', $event)"
              />
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="6">
          <v-card class="h-100" rounded="lg" variant="tonal">
            <v-card-title class="text-subtitle-1">4. Objectifs / Activités / Projets</v-card-title>
            <v-card-text>
              <div class="text-caption text-medium-emphasis mb-2">
                Objectifs: {{ metrics.objectives_count }} • Taux moyen: {{ metrics.objective_rate }}%
              </div>
              <v-textarea
                label="Écarts, actions et priorités"
                :model-value="modelValue.objectives_projects_summary"
                :readonly="readonly"
                rows="3"
                variant="outlined"
                @update:model-value="updateField('objectives_projects_summary', $event)"
              />
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="6">
          <v-card class="h-100" rounded="lg" variant="tonal">
            <v-card-title class="text-subtitle-1">5. Conformité / NC / Satisfaction</v-card-title>
            <v-card-text>
              <div class="text-caption text-medium-emphasis mb-2">
                NC: {{ metrics.non_conformities_count }} • Satisfaction moyenne: {{ metrics.satisfaction_rate }}%
              </div>
              <v-textarea
                label="Constats et traitements"
                :model-value="modelValue.compliance_nc_satisfaction_summary"
                :readonly="readonly"
                rows="3"
                variant="outlined"
                @update:model-value="updateField('compliance_nc_satisfaction_summary', $event)"
              />
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12">
          <v-card class="h-100" rounded="lg" variant="tonal">
            <v-card-title class="text-subtitle-1">6. Leadership / DUERP (affichage)</v-card-title>
            <v-card-text>
              <v-alert
                v-if="!showManagementSection"
                density="comfortable"
                type="info"
                variant="tonal"
              >
                Cette section est visible uniquement pour le processus Management.
              </v-alert>
              <template v-else>
                <div class="text-caption text-medium-emphasis mb-2">
                  DUERP: {{ metrics.duerp_versions_count }} version(s) • Dangers: {{ metrics.duerp_dangers_count }} • Critiques: {{ metrics.duerp_unacceptable_count }}
                </div>
                <div class="text-caption text-medium-emphasis mb-2">
                  AES: {{ metrics.aes_total_count }} aspect(s) • Significatifs: {{ metrics.aes_significant_count }}
                </div>
                <div class="d-flex flex-wrap ga-2 mb-3">
                  <v-btn
                    v-for="link in quickLinks"
                    :key="link.label"
                    color="primary"
                    :prepend-icon="link.icon"
                    size="small"
                    :to="link.to"
                    variant="text"
                  >
                    {{ link.label }}
                  </v-btn>
                </div>
                <v-card class="mb-3" rounded="lg" variant="outlined">
                  <v-card-title class="text-subtitle-2">Données liées DUERP / AES</v-card-title>
                  <v-card-text>
                    <v-alert
                      v-if="linkedDataLoading"
                      density="comfortable"
                      type="info"
                      variant="tonal"
                    >
                      Chargement des données liées en cours...
                    </v-alert>
                    <v-alert
                      v-else-if="linkedDataError"
                      density="comfortable"
                      type="warning"
                      variant="tonal"
                    >
                      Impossible de charger les données liées DUERP/AES.
                    </v-alert>
                    <v-alert
                      v-else-if="linkedData.duerp_top.length === 0 && linkedData.aes_top.length === 0 && linkedData.incidents_top.length === 0"
                      density="comfortable"
                      type="info"
                      variant="tonal"
                    >
                      Aucune donnée DUERP/AES liée.
                    </v-alert>
                    <template v-else>
                      <div class="text-caption text-medium-emphasis mb-2">
                        Actions DUERP en retard: {{ linkedData.summary.duerp_overdue_actions }} • AES sans plan: {{ linkedData.summary.aes_missing_actions }}
                      </div>
                      <div class="text-caption text-medium-emphasis mb-2">
                        Incidents ouverts: {{ linkedData.summary.incidents_open_count }} • Incidents en retard: {{ linkedData.summary.incidents_overdue_count }}
                      </div>
                      <div class="text-caption text-medium-emphasis mb-2">
                        Actions incidents échues: {{ linkedData.summary.linked_incident_actions_overdue_count }}
                      </div>
                      <v-alert
                        v-for="alert in linkedData.summary.alerts"
                        :key="`summary-alert-${alert.code}`"
                        class="mb-2"
                        density="comfortable"
                        :type="mapAlertType(alert.severity)"
                        variant="tonal"
                      >
                        {{ alert.message }}
                      </v-alert>
                      <div class="text-body-2 font-weight-medium mb-1">Top dangers DUERP</div>
                      <v-list class="mb-2" density="compact" lines="two">
                        <v-list-item
                          v-for="danger in linkedData.duerp_top"
                          :key="`duerp-${danger.id}`"
                          :subtitle="danger.danger_description || 'Sans description'"
                          :title="danger.danger_type || `Danger #${danger.id}`"
                        >
                          <template #append>
                            <div class="d-flex align-center ga-2">
                              <v-chip size="x-small" variant="tonal">Criticité {{ danger.criticality_score }}</v-chip>
                              <v-chip
                                :color="danger.overdue_actions > 0 ? 'warning' : 'success'"
                                size="x-small"
                                variant="tonal"
                              >
                                Retard: {{ danger.overdue_actions }}
                              </v-chip>
                              <v-chip size="x-small" variant="tonal">
                                Actions: {{ danger.linked_actions_count || 0 }}
                              </v-chip>
                              <v-btn
                                :data-testid="`create-linked-action-duerp-${danger.id}`"
                                :disabled="readonly"
                                icon="mdi-plus-circle-outline"
                                size="x-small"
                                variant="text"
                                @click="emitCreateLinkedAction('duerp_danger', danger.id, danger.danger_type || `Danger #${danger.id}`, danger.danger_description || '')"
                              />
                              <v-btn
                                icon="mdi-open-in-new"
                                size="x-small"
                                :to="{ path: '/company/planning/duerp', query: toProcessQuery(danger.process_id) }"
                                variant="text"
                              />
                            </div>
                          </template>
                        </v-list-item>
                      </v-list>

                      <div class="text-body-2 font-weight-medium mb-1">Top AES significatifs</div>
                      <v-list density="compact" lines="two">
                        <v-list-item
                          v-for="aspect in linkedData.aes_top"
                          :key="`aes-${aspect.id}`"
                          :subtitle="aspect.type || 'Type non défini'"
                          :title="aspect.designation || `AES #${aspect.id}`"
                        >
                          <template #append>
                            <div class="d-flex align-center ga-2">
                              <v-chip size="x-small" variant="tonal">Criticité {{ aspect.criticite }}</v-chip>
                              <v-chip
                                :color="aspect.action_status === 'missing' ? 'warning' : 'success'"
                                size="x-small"
                                variant="tonal"
                              >
                                {{ aspect.action_status === 'missing' ? 'Plan manquant' : 'Plan défini' }}
                              </v-chip>
                              <v-chip size="x-small" variant="tonal">
                                Actions: {{ aspect.linked_actions_count || 0 }}
                              </v-chip>
                              <v-btn
                                :data-testid="`create-linked-action-aes-${aspect.id}`"
                                :disabled="readonly"
                                icon="mdi-plus-circle-outline"
                                size="x-small"
                                variant="text"
                                @click="emitCreateLinkedAction('aes_aspect', aspect.id, aspect.designation || `AES #${aspect.id}`, aspect.type || '')"
                              />
                              <v-btn
                                icon="mdi-open-in-new"
                                size="x-small"
                                :to="{ path: '/company/planning/aspects-environmentaux', query: toProcessQuery(aspect.process_id) }"
                                variant="text"
                              />
                            </div>
                          </template>
                        </v-list-item>
                      </v-list>

                      <div class="text-body-2 font-weight-medium mb-1">Incidents / AT récents</div>
                      <v-list density="compact" lines="two">
                        <v-list-item
                          v-for="incident in linkedData.incidents_top"
                          :key="`incident-${incident.id}`"
                          :subtitle="incident.category || 'Catégorie non définie'"
                          :title="incident.title || `Incident #${incident.id}`"
                        >
                          <template #append>
                            <div class="d-flex align-center ga-2">
                              <v-chip size="x-small" variant="tonal">{{ incident.severity || 'N/A' }}</v-chip>
                              <v-chip
                                :color="incident.is_overdue ? 'warning' : 'success'"
                                size="x-small"
                                variant="tonal"
                              >
                                {{ incident.is_overdue ? 'Échu' : 'Dans les délais' }}
                              </v-chip>
                              <v-chip size="x-small" variant="tonal">
                                Actions: {{ incident.linked_actions_count || 0 }}
                              </v-chip>
                              <v-btn
                                :data-testid="`create-linked-action-incident-${incident.id}`"
                                :disabled="readonly"
                                icon="mdi-plus-circle-outline"
                                size="x-small"
                                variant="text"
                                @click="emitCreateLinkedAction('reclamation', incident.id, incident.title || `Incident #${incident.id}`, incident.description || '')"
                              />
                              <v-btn
                                icon="mdi-open-in-new"
                                size="x-small"
                                :to="{ path: '/company/reclamations', query: { source: 'process-review', incident_id: String(incident.id) } }"
                                variant="text"
                              />
                            </div>
                          </template>
                        </v-list-item>
                      </v-list>
                    </template>
                  </v-card-text>
                </v-card>
                <v-textarea
                  label="Synthèse Leadership / DUERP"
                  :model-value="modelValue.management_duerp_display"
                  :readonly="readonly"
                  rows="3"
                  variant="outlined"
                  @update:model-value="updateField('management_duerp_display', $event)"
                />
              </template>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  interface ReviewSectionsModel {
    pip_summary: string
    risk_opportunity_summary: string
    objectives_projects_summary: string
    compliance_nc_satisfaction_summary: string
    management_duerp_display: string
  }

  interface ReviewMetrics {
    pip_total: number
    pip_completed: number
    risks_count: number
    opportunities_count: number
    objectives_count: number
    objective_rate: number
    non_conformities_count: number
    satisfaction_rate: number
    duerp_versions_count: number
    duerp_dangers_count: number
    duerp_unacceptable_count: number
    aes_total_count: number
    aes_significant_count: number
  }

  interface QuickLinkItem {
    label: string
    icon: string
    to: string | { path: string, query?: Record<string, string> }
  }

  interface LinkedDuerpItem {
    id: number
    process_id: number | null
    danger_type: string
    danger_description: string
    criticality_score: number
    overdue_actions: number
    linked_actions_count?: number
  }

  interface LinkedAesItem {
    id: number
    process_id: number | null
    designation: string
    type: string
    criticite: number
    action_status: 'missing' | 'defined' | string
    linked_actions_count?: number
  }

  interface LinkedDataPayload {
    duerp_top: LinkedDuerpItem[]
    aes_top: LinkedAesItem[]
    incidents_top: LinkedIncidentItem[]
    summary: {
      duerp_overdue_actions: number
      aes_missing_actions: number
      incidents_open_count: number
      incidents_overdue_count: number
      linked_incident_actions_overdue_count: number
      alerts: Array<{
        code: string
        severity: string
        message: string
      }>
    }
  }

  interface LinkedIncidentItem {
    id: number
    title: string
    category: string
    severity: string
    status: string
    due_date?: string | null
    description?: string
    is_overdue: boolean
    linked_actions_count?: number
  }

  const props = defineProps<{
    modelValue: ReviewSectionsModel
    metrics: ReviewMetrics
    quickLinks: QuickLinkItem[]
    linkedData: LinkedDataPayload
    linkedDataLoading: boolean
    linkedDataError: boolean
    showManagementSection: boolean
    readonly?: boolean
  }>()

  const emit = defineEmits<{
    'update:modelValue': [value: ReviewSectionsModel]
    'create-linked-action': [payload: {
      source_type: 'duerp_danger' | 'aes_aspect' | 'reclamation'
      source_id: number
      source_label: string
      source_hint: string
    }]
  }>()

  function updateField (key: keyof ReviewSectionsModel, value: any) {
    emit('update:modelValue', { ...props.modelValue, [key]: String(value ?? '') })
  }

  function emitCreateLinkedAction (
    sourceType: 'duerp_danger' | 'aes_aspect' | 'reclamation',
    sourceId: number,
    sourceLabel: string,
    sourceHint: string,
  ) {
    emit('create-linked-action', {
      source_type: sourceType,
      source_id: Number(sourceId),
      source_label: String(sourceLabel || '').trim(),
      source_hint: String(sourceHint || '').trim(),
    })
  }

  function toProcessQuery (processId: number | null) {
    if (!processId) {
      return {}
    }
    return { process_id: String(processId), source: 'process-review' }
  }

  function mapAlertType (severity: string): 'info' | 'success' | 'warning' | 'error' {
    const normalized = String(severity || '').toLowerCase()
    if (normalized === 'error' || normalized === 'critical') return 'error'
    if (normalized === 'warning') return 'warning'
    if (normalized === 'success') return 'success'
    return 'info'
  }
</script>
