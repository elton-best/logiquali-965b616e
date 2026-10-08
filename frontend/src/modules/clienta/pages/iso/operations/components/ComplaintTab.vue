<template>
  <v-card-text class="pa-6">
    <v-form @submit.prevent="emit('submit')">
      <v-row class="form-grid">
        <v-col cols="12">
          <v-card class="form-section" rounded="lg" variant="outlined">
            <div class="section-title">
              <span class="section-number">01</span>
              Référence & Canal
            </div>
            <v-card-text>
              <v-row dense>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="reclamationForm.reference"
                    label="Référence"
                    prepend-inner-icon="mdi-identifier"
                    readonly
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="reclamationForm.receivedDate"
                    label="Date de la plainte/réclamation *"
                    prepend-inner-icon="mdi-calendar"
                    type="date"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="reclamationForm.type"
                    item-title="title"
                    item-value="value"
                    :items="reclamationTypeOptions"
                    label="Type *"
                    prepend-inner-icon="mdi-tag-outline"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-switch
                    v-model="reclamationForm.wantsMail"
                    color="primary"
                    inset
                    label="Réponse souhaitée par mail"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="reclamationForm.dueDate"
                    label="Délai cible"
                    prepend-inner-icon="mdi-timer-sand"
                    type="date"
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
              <span class="section-number">02</span>
              Informations client
            </div>
            <v-card-text>
              <v-row dense>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="reclamationForm.customerName"
                    label="Nom / Structure *"
                    prepend-inner-icon="mdi-account"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="reclamationForm.customerPhone"
                    label="Téléphone"
                    prepend-inner-icon="mdi-phone-outline"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="reclamationForm.address"
                    label="Adresse"
                    prepend-inner-icon="mdi-map-marker-outline"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="reclamationForm.customerEmail"
                    label="Email"
                    prepend-inner-icon="mdi-email-outline"
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
              Détails de la réclamation
            </div>
            <v-card-text>
              <v-row dense>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="reclamationForm.title"
                    label="Objet *"
                    prepend-inner-icon="mdi-format-title"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-textarea
                    v-model="reclamationForm.description"
                    label="Description *"
                    prepend-inner-icon="mdi-text-box-outline"
                    rows="4"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-textarea
                    v-model="reclamationForm.requestedSolutions"
                    label="Proposition(s) de solution(s) souhaitée(s)"
                    prepend-inner-icon="mdi-lightbulb-on-outline"
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
              <span class="section-number">04</span>
              Analyse & Suivi
            </div>
            <v-card-text>
              <v-row dense>
                <v-col cols="12" md="6">
                  <v-textarea
                    v-model="reclamationForm.probableCauses"
                    label="Analyse - Cause(s) probable(s)"
                    prepend-inner-icon="mdi-magnify-expand"
                    rows="3"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="reclamationForm.processId"
                    clearable
                    item-title="title"
                    item-value="value"
                    :items="processOptions"
                    label="Processus concerné (pour les actions)"
                    prepend-inner-icon="mdi-source-branch"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="reclamationForm.assignedTo"
                    clearable
                    item-title="name"
                    item-value="id"
                    :items="userOptions"
                    label="Responsable"
                    prepend-inner-icon="mdi-account-check-outline"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="reclamationForm.status"
                    item-title="title"
                    item-value="value"
                    :items="reclamationStatusOptions"
                    label="Statut"
                    prepend-inner-icon="mdi-flag-outline"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12">
                  <div class="d-flex align-center justify-space-between mb-2 action-toolbar">
                    <div class="text-subtitle-2 font-weight-medium">Actions correctives / préventives *</div>
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
                    <v-col v-for="(action, index) in reclamationActions" :key="`reclam-action-${index}`" cols="12">
                      <v-card class="action-row" rounded="lg" variant="outlined">
                        <v-card-text>
                          <v-row dense>
                            <v-col cols="12" md="6">
                              <v-text-field
                                v-model="action.description"
                                label="Action à réaliser *"
                                prepend-inner-icon="mdi-tools"
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
                                v-if="reclamationActions.length > 1"
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
              </v-row>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <div class="d-flex justify-end mt-4">
        <v-btn
          color="primary"
          :loading="savingReclamation"
          prepend-icon="mdi-content-save"
          rounded="lg"
          type="submit"
        >
          Enregistrer la fiche réclamation
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
        Exporter la liste réclamations (XLSX)
      </v-btn>
    </div>

    <v-data-table
      :headers="reclamationHeaders"
      :items="reclamations"
      :loading="loadingReclamations"
    >
      <template #[`item.received_date`]="{ item }">
        {{ formatDate(item.received_date || item.created_at) }}
      </template>
      <template #[`item.actions`]="{ item }">
        <div class="d-flex justify-end ga-1">
          <v-btn icon="mdi-pencil-outline" size="small" variant="text" @click="emit('edit', item)" />
        </div>
      </template>
      <template #no-data>
        <div class="text-center py-6 text-medium-emphasis">Aucune fiche de plainte/reclamation enregistrée.</div>
      </template>
    </v-data-table>
  </v-card-text>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'

  defineProps({
    reclamationForm: {
      type: Object as PropType<Record<string, any>>,
      required: true,
    },
    reclamationActions: {
      type: Array as PropType<Array<Record<string, any>>>,
      required: true,
    },
    reclamationTypeOptions: {
      type: Array as PropType<Array<{ title: string, value: string }>>,
      required: true,
    },
    reclamationStatusOptions: {
      type: Array as PropType<Array<{ title: string, value: string }>>,
      required: true,
    },
    actionTypeOptions: {
      type: Array as PropType<Array<{ title: string, value: string }>>,
      required: true,
    },
    processOptions: {
      type: Array as PropType<Array<{ value: number, title: string }>>,
      required: true,
    },
    userOptions: {
      type: Array as PropType<Array<{ id: number, name: string }>>,
      required: true,
    },
    savingReclamation: {
      type: Boolean,
      required: true,
    },
    reclamationHeaders: {
      type: Array as PropType<Array<Record<string, any>>>,
      required: true,
    },
    reclamations: {
      type: Array as PropType<any[]>,
      required: true,
    },
    loadingReclamations: {
      type: Boolean,
      required: true,
    },
    formatDate: {
      type: Function as PropType<(date: string) => string>,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'submit'): void
    (event: 'add-action'): void
    (event: 'remove-action', index: number): void
    (event: 'export'): void
    (event: 'edit', item: any): void
  }>()
</script>
