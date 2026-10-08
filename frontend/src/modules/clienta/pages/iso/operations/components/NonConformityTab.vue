<template>
  <v-card-text class="pa-6">
    <v-form @submit.prevent="emit('submit')">
      <v-row class="form-grid">
        <v-col cols="12">
          <v-card class="form-section" rounded="lg" variant="outlined">
            <div class="section-title">
              <span class="section-number">01</span>
              Identification
            </div>
            <v-card-text>
              <v-row dense>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="ncForm.processId"
                    clearable
                    item-title="title"
                    item-value="value"
                    :items="processOptions"
                    label="Processus concerné *"
                    prepend-inner-icon="mdi-source-branch"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="ncForm.date"
                    label="Date d'ouverture *"
                    prepend-inner-icon="mdi-calendar-search"
                    type="date"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="ncForm.typeConstat"
                    item-title="title"
                    item-value="value"
                    :items="findingTypeOptions"
                    label="Type de constat *"
                    prepend-inner-icon="mdi-shape-outline"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="ncForm.sourceDetection"
                    item-title="title"
                    item-value="value"
                    :items="detectionSourceOptions"
                    label="Source de détection *"
                    prepend-inner-icon="mdi-radar"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="ncForm.typeNc"
                    item-title="title"
                    item-value="value"
                    :items="ncTypeOptions"
                    label="Type de NC *"
                    prepend-inner-icon="mdi-alert"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-select
                    v-if="editingNcId"
                    v-model="ncForm.status"
                    item-title="title"
                    item-value="value"
                    :items="ncStatusOptions"
                    label="Statut"
                    prepend-inner-icon="mdi-flag-outline"
                    variant="outlined"
                  />
                  <v-alert
                    v-else
                    density="comfortable"
                    icon="mdi-information-outline"
                    type="info"
                    variant="tonal"
                  >
                    Statut par défaut : Ouverte. Le statut évolue après création.
                  </v-alert>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12">
          <v-card class="form-section" rounded="lg" variant="outlined">
            <div class="section-title">
              <span class="section-number">02</span>
              Constat & Exigences
            </div>
            <v-card-text>
              <v-row dense>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="ncForm.requirement"
                    label="Exigence non respectée *"
                    prepend-inner-icon="mdi-file-document-outline"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-textarea
                    v-model="ncForm.description"
                    label="Description *"
                    prepend-inner-icon="mdi-text-box-outline"
                    rows="3"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-textarea
                    v-model="ncForm.cause"
                    label="Cause(s) de l'apparition *"
                    prepend-inner-icon="mdi-chart-timeline-variant"
                    rows="3"
                    variant="outlined"
                  />
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12">
          <v-card class="form-section" rounded="lg" variant="outlined">
            <div class="section-title">
              <span class="section-number">03</span>
              Plan d'action & Suivi
            </div>
            <v-card-text>
              <v-row dense>
                <v-col cols="12">
                  <div class="d-flex align-center justify-space-between mb-2 action-toolbar">
                    <div class="text-subtitle-2 font-weight-medium">Actions de traitement *</div>
                    <v-btn
                      color="primary"
                      prepend-icon="mdi-plus"
                      size="small"
                      variant="tonal"
                      @click="emit('add-action')"
                    >
                      Ajouter une action
                    </v-btn>
                  </div>
                  <v-row dense>
                    <v-col v-for="(action, index) in ncActions" :key="`nc-action-${index}`" cols="12">
                      <v-card class="action-row" rounded="lg" variant="outlined">
                        <v-card-text>
                          <v-row dense>
                            <v-col cols="12" md="6">
                              <v-text-field
                                v-model="action.description"
                                label="Action à réaliser *"
                                prepend-inner-icon="mdi-hammer-wrench"
                                variant="outlined"
                              />
                            </v-col>
                            <v-col cols="12" md="6">
                              <v-select
                                v-model="action.type"
                                item-title="title"
                                item-value="value"
                                :items="actionTypeOptions"
                                label="Type"
                                prepend-inner-icon="mdi-shape-outline"
                                variant="outlined"
                              />
                            </v-col>
                            <v-col cols="12" md="6">
                              <v-text-field
                                v-model="action.deadline"
                                label="Délai *"
                                prepend-inner-icon="mdi-calendar-clock-outline"
                                type="date"
                                variant="outlined"
                              />
                            </v-col>
                            <v-col cols="12" md="6">
                              <v-select
                                v-model="action.responsibleId"
                                clearable
                                item-title="name"
                                item-value="id"
                                :items="userOptions"
                                label="Responsable *"
                                prepend-inner-icon="mdi-account-outline"
                                variant="outlined"
                              />
                            </v-col>
                            <v-col class="d-flex align-center justify-end action-row-cta" cols="12">
                              <v-btn
                                v-if="ncActions.length > 1"
                                color="error"
                                prepend-icon="mdi-delete-outline"
                                size="small"
                                variant="text"
                                @click="emit('remove-action', index)"
                              >
                                Retirer
                              </v-btn>
                            </v-col>
                          </v-row>
                        </v-card-text>
                      </v-card>
                    </v-col>
                  </v-row>
                </v-col>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="ncForm.implicatedIds"
                    chips
                    clearable
                    item-title="name"
                    item-value="id"
                    :items="userOptions"
                    label="Collaborateurs impliqués"
                    multiple
                    prepend-inner-icon="mdi-account-group-outline"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-textarea
                    v-model="ncForm.result"
                    label="Résultat attendu / commentaire"
                    prepend-inner-icon="mdi-clipboard-check-outline"
                    rows="3"
                    variant="outlined"
                  />
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <div class="d-flex justify-end mt-4">
        <v-btn
          color="primary"
          :loading="savingNc"
          prepend-icon="mdi-content-save"
          rounded="lg"
          type="submit"
        >
          Enregistrer la fiche NC
        </v-btn>
      </div>
    </v-form>

    <v-divider class="my-6" />

    <div class="d-flex justify-end mb-3">
      <v-btn
        color="success"
        prepend-icon="mdi-file-excel"
        rounded="lg"
        variant="tonal"
        @click="emit('export')"
      >
        Exporter la liste NC (XLSX)
      </v-btn>
    </div>

    <v-data-table
      :headers="ncHeaders"
      :items="nonConformities"
      :loading="loadingNc"
    >
      <template #[`item.status`]="{ item }">
        <v-chip :color="statusColor(item.status)" size="small" variant="tonal">
          {{ statusLabel(item.status) }}
        </v-chip>
      </template>
      <template #[`item.actions`]="{ item }">
        <div class="d-flex justify-end ga-1">
          <v-btn icon="mdi-pencil-outline" size="small" variant="text" @click="emit('edit', item.id)" />
        </div>
      </template>
      <template #no-data>
        <div class="text-center py-6 text-medium-emphasis">Aucune fiche de non-conformité enregistrée.</div>
      </template>
    </v-data-table>
  </v-card-text>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'

  defineProps({
    ncForm: {
      type: Object as PropType<Record<string, any>>,
      required: true,
    },
    ncActions: {
      type: Array as PropType<Array<Record<string, any>>>,
      required: true,
    },
    processOptions: {
      type: Array as PropType<Array<{ value: number, title: string }>>,
      required: true,
    },
    findingTypeOptions: {
      type: Array as PropType<Array<{ title: string, value: string }>>,
      required: true,
    },
    detectionSourceOptions: {
      type: Array as PropType<Array<{ title: string, value: string }>>,
      required: true,
    },
    ncTypeOptions: {
      type: Array as PropType<Array<{ title: string, value: string }>>,
      required: true,
    },
    ncStatusOptions: {
      type: Array as PropType<Array<{ title: string, value: string }>>,
      required: true,
    },
    actionTypeOptions: {
      type: Array as PropType<Array<{ title: string, value: string }>>,
      required: true,
    },
    userOptions: {
      type: Array as PropType<Array<{ id: number, name: string }>>,
      required: true,
    },
    editingNcId: {
      type: Number as PropType<number | null>,
      default: null,
    },
    savingNc: {
      type: Boolean,
      required: true,
    },
    ncHeaders: {
      type: Array as PropType<Array<Record<string, any>>>,
      required: true,
    },
    nonConformities: {
      type: Array as PropType<any[]>,
      required: true,
    },
    loadingNc: {
      type: Boolean,
      required: true,
    },
    statusColor: {
      type: Function as PropType<(status: string) => string>,
      required: true,
    },
    statusLabel: {
      type: Function as PropType<(status: string) => string>,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'submit'): void
    (event: 'add-action'): void
    (event: 'remove-action', index: number): void
    (event: 'export'): void
    (event: 'edit', id: number): void
  }>()
</script>
