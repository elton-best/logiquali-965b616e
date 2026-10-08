<template>
  <ClientALayout current-page="risks-opportunities">
    <v-container class="pa-6" fluid>
      <PageHeader
        icon="mdi-shield-alert"
        subtitle="Pilotage des risques et opportunités du site"
        title="Risques & Opportunités"
      />

      <v-card class="hero-card mb-6" elevation="0" rounded="xl">
        <v-card-text class="pa-5">
          <div class="d-flex flex-wrap ga-3 justify-space-between align-start">
            <div class="hero-main">
              <div class="text-overline mb-2 hero-kicker">Planification ISO</div>
              <h2 class="text-h5 text-md-h4 font-weight-bold mb-2 hero-title">
                {{ viewMode === 'risque' ? 'Cartographie des risques' : 'Cartographie des opportunités' }}
              </h2>
              <p class="text-body-2 mb-4 hero-subtitle">
                Visualisez les priorités, suivez les responsables et pilotez l'état d'avancement en un coup d'oeil.
              </p>
              <div class="d-flex flex-wrap ga-2 mb-3">
                <v-chip
                  class="font-weight-medium"
                  color="white"
                  prepend-icon="mdi-counter"
                  size="small"
                  variant="flat"
                >
                  {{ modeItems.length }} élément(s)
                </v-chip>
                <v-chip
                  class="font-weight-medium"
                  color="white"
                  prepend-icon="mdi-alert-octagon-outline"
                  size="small"
                  variant="flat"
                >
                  {{ highPriorityCount }} priorité(s) élevée(s)
                </v-chip>
                <v-chip
                  class="font-weight-medium"
                  color="white"
                  prepend-icon="mdi-check-circle-outline"
                  size="small"
                  variant="flat"
                >
                  Traitement {{ completionRate }}%
                </v-chip>
              </div>
              <div class="d-flex flex-wrap ga-2">
                <v-btn
                  class="hero-action"
                  color="white"
                  prepend-icon="mdi-plus"
                  size="small"
                  @click="openCreateDialog"
                >
                  Ajouter {{ viewMode === 'risque' ? 'un risque' : 'une opportunité' }}
                </v-btn>
                <v-btn
                  class="hero-action"
                  color="white"
                  prepend-icon="mdi-file-excel"
                  size="small"
                  variant="outlined"
                  @click="handleExport"
                >
                  Export Excel
                </v-btn>
              </div>
            </div>
            <div class="hero-side">
              <div class="hero-side-label mb-2">Vue active</div>
              <v-chip
                class="mb-3 font-weight-medium"
                :color="viewMode === 'risque' ? 'error' : 'success'"
                size="small"
                variant="flat"
              >
                {{ viewMode === 'risque' ? 'Mode risques actif' : 'Mode opportunités actif' }}
              </v-chip>
              <v-sheet class="hero-score pa-3" rounded="lg">
                <div class="d-flex justify-space-between align-center mb-1">
                  <span class="text-caption text-medium-emphasis">{{ viewMode === 'risque' ? 'Criticité moyenne' : 'Priorité moyenne' }}</span>
                  <v-chip :color="scoreColor(Number(avgCriticite))" size="x-small" variant="tonal">{{ averagePriorityLabel }}</v-chip>
                </div>
                <div class="text-h5 font-weight-bold mb-2">{{ avgCriticite }}</div>
                <v-progress-linear
                  :color="scoreColor(Number(avgCriticite))"
                  height="8"
                  :model-value="Math.min(100, Math.round((Number(avgCriticite) / 16) * 100))"
                  rounded
                />
              </v-sheet>
            </div>
          </div>
        </v-card-text>
      </v-card>

      <v-row class="mb-6">
        <v-col cols="12" md="3" sm="6">
          <AppWidget
            icon="Info"
            title="Total affiché"
            :value="filteredItems.length"
            variant="primary"
          />
        </v-col>
        <v-col cols="12" md="3" sm="6">
          <AppWidget
            icon="AlertTriangle"
            title="Priorité élevée"
            :value="highPriorityCount"
            variant="warning"
          />
        </v-col>
        <v-col cols="12" md="3" sm="6">
          <AppWidget
            icon="Clock"
            title="En cours / surveillé"
            :value="inProgressCount"
            variant="info"
          />
        </v-col>
        <v-col cols="12" md="3" sm="6">
          <AppWidget
            icon="CheckCircle"
            title="Traité / clôturé"
            :value="closedCount"
            variant="success"
          />
        </v-col>
      </v-row>

      <v-card class="filters-card mb-6" elevation="0" rounded="xl">
        <v-card-text class="pa-4">
          <v-row align="center" class="filters-row" dense>
            <v-col cols="12" md="3">
              <v-btn-toggle
                v-model="viewMode"
                class="w-100"
                density="compact"
                mandatory
                rounded="lg"
              >
                <v-btn class="flex-grow-1" prepend-icon="mdi-alert-outline" size="small" value="risque">Risques</v-btn>
                <v-btn class="flex-grow-1" prepend-icon="mdi-lightbulb-outline" size="small" value="opportunite">Opportunités</v-btn>
              </v-btn-toggle>
            </v-col>
            <v-col cols="12" md="3">
              <v-text-field
                v-model="filters.search"
                class="filters-search"
                clearable
                density="compact"
                hide-details
                placeholder="Rechercher description, cause, processus..."
                prepend-inner-icon="mdi-magnify"
                variant="outlined"
                @click:clear="filters.search = ''"
              />
            </v-col>
            <v-col cols="12" md="2">
              <v-select
                v-model="filters.process_id"
                clearable
                density="compact"
                hide-details
                item-title="title"
                item-value="value"
                :items="processOptions"
                placeholder="Processus"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12" md="2">
              <v-select
                v-model="filters.status"
                clearable
                density="compact"
                hide-details
                item-title="label"
                item-value="value"
                :items="statusItems"
                placeholder="Statut"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12" md="2">
              <v-select
                v-model="filters.niveau"
                clearable
                density="compact"
                hide-details
                item-title="label"
                item-value="value"
                :items="niveauOptions"
                placeholder="Niveau"
                variant="outlined"
              />
            </v-col>
            <v-col class="d-flex justify-start align-center filters-actions" cols="12" md="2">
              <v-btn
                aria-label="Réinitialiser les filtres"
                class="filters-reset-btn"
                :disabled="!hasActiveFilters"
                icon="mdi-filter-off"
                size="small"
                variant="outlined"
                @click="resetFilters"
              />
              <v-btn-toggle
                v-model="viewType"
                class="filters-view-toggle"
                density="compact"
                mandatory
                rounded="lg"
              >
                <v-btn icon="mdi-view-grid-outline" size="small" value="grid" />
                <v-btn icon="mdi-format-list-bulleted" size="small" value="list" />
              </v-btn-toggle>
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>

      <v-card v-if="filteredItems.length === 0" class="empty-card mb-6" elevation="0" rounded="xl">
        <v-card-text class="text-center py-12">
          <v-avatar class="mb-4" color="primary" size="64" variant="tonal">
            <v-icon size="34">mdi-folder-search-outline</v-icon>
          </v-avatar>
          <h3 class="text-h6 font-weight-bold mb-2">Aucun résultat pour cette vue</h3>
          <p class="text-body-2 text-medium-emphasis mb-6">{{ emptyStateMessage }}</p>
          <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreateDialog">
            Ajouter {{ viewMode === 'risque' ? 'un risque' : 'une opportunité' }}
          </v-btn>
        </v-card-text>
      </v-card>

      <v-row v-else-if="viewType === 'grid'">
        <v-col
          v-for="item in filteredItems"
          :key="item.id"
          cols="12"
          lg="4"
          md="6"
        >
          <v-card class="h-100 risk-item-card" :class="viewMode === 'risque' ? 'risk-item-card--risk' : 'risk-item-card--opportunity'" rounded="xl">
            <v-card-text class="pa-5">
              <div class="d-flex justify-space-between align-center mb-3">
                <v-chip :color="viewMode === 'risque' ? 'error' : 'success'" size="small" variant="flat">
                  {{ viewMode === 'risque' ? 'RISQUE' : 'OPPORTUNITÉ' }} #{{ item.code }}
                </v-chip>
                <v-chip :color="statusColor(item.status)" size="small" variant="tonal">
                  {{ statusItems.find(s => s.value === item.status)?.label || item.status }}
                </v-chip>
              </div>

              <div class="text-caption text-medium-emphasis mb-2">Processus</div>
              <v-chip class="mb-4" color="primary" size="small" variant="tonal">{{ item.process_name }}</v-chip>

              <div class="text-body-1 font-weight-bold mb-3 clamp-two-lines">{{ item.description }}</div>

              <div class="risk-score-panel mb-4">
                <div class="d-flex align-center justify-space-between mb-2">
                  <span class="text-caption text-medium-emphasis">Score de priorité</span>
                  <v-chip :color="scoreColor(item.score)" size="small" variant="flat">{{ item.score }} / 16</v-chip>
                </div>
                <v-progress-linear
                  :color="scoreColor(item.score)"
                  height="9"
                  :model-value="Math.min(100, Math.round((Number(item.score || 0) / 16) * 100))"
                  rounded
                />
              </div>

              <div class="d-flex ga-2 mb-4 flex-wrap">
                <v-chip size="x-small" variant="tonal">Probabilité: {{ item.probabilite }}</v-chip>
                <v-chip size="x-small" variant="tonal">{{ viewMode === 'risque' ? 'Gravité' : 'Pertinence' }}: {{ item.gravite }}</v-chip>
                <v-chip :color="scoreColor(item.score)" size="x-small" variant="tonal">{{ priorityLabel(item.score) }}</v-chip>
              </div>

              <v-divider class="mb-3" />

              <div class="text-caption text-medium-emphasis mb-1">Responsable</div>
              <div class="text-body-2 mb-3">{{ item.responsable_name || 'Non assigné' }}</div>

              <div class="text-caption text-medium-emphasis mb-1">Actions</div>
              <template v-if="viewMode === 'risque'">
                <div class="action-type-chips mb-2">
                  <v-chip
                    v-for="summary in item.action_type_summaries"
                    :key="summary.key"
                    color="primary"
                    size="x-small"
                    variant="tonal"
                  >
                    {{ summary.label }} ({{ summary.count }})
                  </v-chip>
                </div>
                <ul v-if="item.action_type_summaries.length > 0" class="action-preview-list mb-4">
                  <template v-for="summary in item.action_type_summaries.slice(0, 3)" :key="`preview-${summary.key}`">
                    <li
                      v-for="(line, lineIndex) in summary.preview_list"
                      :key="`preview-${summary.key}-${lineIndex}`"
                      class="action-preview-item clamp-one-line"
                    >
                      <strong>{{ summary.label }}:</strong> {{ line }}
                    </li>
                    <li
                      v-if="summary.remaining > 0"
                      :key="`preview-${summary.key}-remaining`"
                      class="action-preview-item text-medium-emphasis"
                    >
                      +{{ summary.remaining }} autre(s) action(s) {{ summary.label.toLowerCase() }}
                    </li>
                  </template>
                </ul>
                <div v-else class="text-body-2 mb-4 text-medium-emphasis">Aucune action précisée.</div>
              </template>
              <template v-else>
                <div class="text-body-2 mb-1">{{ item.action_count || 0 }} action(s) planifiée(s)</div>
                <ul v-if="item.action_preview_list?.length > 0" class="action-preview-list mb-4">
                  <li
                    v-for="(line, idx) in item.action_preview_list"
                    :key="`opp-action-${item.id}-${idx}`"
                    class="action-preview-item clamp-one-line"
                  >
                    {{ line }}
                  </li>
                  <li v-if="item.action_remaining_count > 0" class="action-preview-item text-medium-emphasis">
                    +{{ item.action_remaining_count }} autre(s) action(s)
                  </li>
                </ul>
                <div v-else class="text-body-2 mb-4 text-medium-emphasis">Aucune action précisée.</div>
              </template>

              <div class="d-flex justify-space-between align-center">
                <span class="text-caption text-medium-emphasis">{{ item.delai_frequence || 'Délai non défini' }}</span>
                <div class="d-flex">
                  <v-btn icon="mdi-eye-outline" size="small" variant="text" @click="goToDetail(item.id)" />
                  <v-btn icon="mdi-pencil-outline" size="small" variant="text" @click="openEditDialog(item)" />
                  <v-btn
                    color="error"
                    icon="mdi-delete-outline"
                    size="small"
                    variant="text"
                    @click="removeItem(item)"
                  />
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <v-card v-else rounded="xl">
        <v-data-table
          class="elevation-0"
          :headers="tableHeaders"
          item-value="id"
          :items="filteredItems"
          :loading="loading"
        >
          <template #[`item.code`]="{ item }">
            <v-chip :color="viewMode === 'risque' ? 'error' : 'success'" size="small" variant="flat">#{{ item.code }}</v-chip>
          </template>
          <template #[`item.process_name`]="{ item }">
            <v-chip color="primary" size="small" variant="tonal">{{ item.process_name || '-' }}</v-chip>
          </template>
          <template #[`item.evaluation`]="{ item }">
            <div class="d-flex ga-1">
              <v-chip size="x-small" variant="tonal">P: {{ item.probabilite }}</v-chip>
              <v-chip size="x-small" variant="tonal">{{ viewMode === 'risque' ? 'G' : 'R' }}: {{ item.gravite }}</v-chip>
            </div>
          </template>
          <template #[`item.score`]="{ item }">
            <v-chip :color="scoreColor(item.score)" size="small" variant="tonal">{{ item.score }}</v-chip>
          </template>
          <template #[`item.status`]="{ item }">
            <v-chip :color="statusColor(item.status)" size="small" variant="tonal">
              {{ statusItems.find(s => s.value === item.status)?.label || item.status }}
            </v-chip>
          </template>
          <template #[`item.actions`]="{ item }">
            <div class="actions-cell">
              <v-btn icon="mdi-eye-outline" size="x-small" variant="text" @click="goToDetail(item.id)" />
              <v-btn icon="mdi-pencil-outline" size="x-small" variant="text" @click="openEditDialog(item)" />
              <v-btn
                color="error"
                icon="mdi-delete-outline"
                size="x-small"
                variant="text"
                @click="removeItem(item)"
              />
            </div>
          </template>
          <template #no-data>
            <div class="text-center py-8 text-medium-emphasis">{{ emptyStateMessage }}</div>
          </template>
        </v-data-table>
      </v-card>

      <v-dialog
        v-model="showDialog"
        class="risk-opportunity-dialog"
        max-width="920"
        persistent
        scrollable
      >
        <v-card class="risk-dialog-card" rounded="xl">
          <v-card-title class="dialog-title ro-dialog-header">
            <div class="ro-title-wrap">
              <div class="ro-title-icon">
                <v-icon :color="viewMode === 'risque' ? 'error' : 'success'" size="20">
                  {{ viewMode === 'risque' ? 'mdi-alert-outline' : 'mdi-lightbulb-on-outline' }}
                </v-icon>
              </div>
              <div>
                <div class="ro-title-main">
                  {{ editingItem?.id ? 'Modifier' : 'Créer' }} {{ viewMode === 'risque' ? 'un risque' : 'une opportunité' }}
                </div>
                <div class="ro-title-sub">
                  {{ viewMode === 'risque'
                    ? 'Définissez l’évaluation et organisez les actions par type.'
                    : 'Définissez l’évaluation et planifiez les actions de suivi.' }}
                </div>
              </div>
            </div>
            <v-btn
              class="ro-close-btn"
              icon="mdi-close"
              size="small"
              variant="text"
              @click="closeDialog"
            />
          </v-card-title>
          <v-card-text class="ro-dialog-content">
            <div class="ro-stepper-shell mb-4">
              <div class="ro-stepper-track">
                <div class="ro-stepper-progress" :style="{ width: `${dialogStepProgress}%` }" />
              </div>
              <div class="ro-stepper-items">
                <button
                  class="ro-step-card"
                  :class="{ active: dialogStep === 1, done: dialogStep > 1 }"
                  type="button"
                  @click="dialogStep = 1"
                >
                  <span class="ro-step-badge">
                    <v-icon size="16">{{ dialogStep > 1 ? 'mdi-check' : 'mdi-file-document-edit-outline' }}</v-icon>
                  </span>
                  <span class="ro-step-content">
                    <span class="ro-step-title">Informations de base</span>
                    <span class="ro-step-subtitle">Processus, description, évaluation</span>
                  </span>
                </button>
                <button
                  class="ro-step-card"
                  :class="{ active: dialogStep === 2 }"
                  type="button"
                  @click="goToActionsStep"
                >
                  <span class="ro-step-badge">
                    <v-icon size="16">mdi-format-list-checks</v-icon>
                  </span>
                  <span class="ro-step-content">
                    <span class="ro-step-title">Actions</span>
                    <span class="ro-step-subtitle">Planification et responsables</span>
                  </span>
                </button>
              </div>
            </div>

            <v-row v-if="dialogStep === 1" class="ro-form-grid">
              <v-col cols="12" md="6">
                <v-select
                  v-model="form.process_id"
                  item-title="title"
                  item-value="value"
                  :items="processOptions"
                  label="Processus *"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="form.status"
                  item-title="label"
                  item-value="value"
                  :items="statusItems"
                  label="Statut *"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12">
                <v-textarea v-model="form.description" :label="viewMode === 'risque' ? 'Description du risque *' : 'Description de l\'opportunité *'" rows="3" variant="outlined" />
              </v-col>
              <v-col v-if="viewMode === 'risque'" cols="12">
                <v-textarea v-model="form.cause" label="Causes profondes" rows="2" variant="outlined" />
              </v-col>
              <v-col cols="12" md="4">
                <v-select
                  v-model.number="form.probabilite"
                  item-title="label"
                  item-value="value"
                  :items="probabilityScaleOptions"
                  label="Probabilité *"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="4">
                <v-select
                  v-model.number="form.gravite"
                  item-title="label"
                  item-value="value"
                  :items="impactScaleOptions"
                  :label="viewMode === 'risque' ? 'Gravité *' : 'Pertinence *'"
                  variant="outlined"
                />
              </v-col>
              <v-col class="d-flex align-center" cols="12" md="4">
                <v-alert class="w-100 ro-score-alert" :type="scoreType(form.probabilite * form.gravite)" variant="tonal">
                  {{ viewMode === 'risque' ? 'Criticité' : 'Priorité' }}: <strong>{{ form.probabilite * form.gravite }}</strong>
                </v-alert>
              </v-col>
              <v-col cols="12">
                <v-alert class="ro-scale-alert" density="comfortable" type="info" variant="tonal">
                  {{ viewMode === 'risque'
                    ? 'Échelle risques: Probabilité (1 Rare, 2 Peu fréquent, 3 Fréquent, 4 Très fréquent) et Gravité (1 Faible, 2 Moyenne, 3 Élevée, 4 Très élevée).'
                    : 'Échelle opportunités: Probabilité (1 Incertain, 2 Peu probable, 3 Probable, 4 Très probable) et Pertinence (1 Faible, 2 Moins pertinent, 3 Pertinent, 4 Très pertinent).' }}
                </v-alert>
              </v-col>
            </v-row>

            <v-row v-else class="ro-form-grid">
              <v-col cols="12">
                <template v-if="viewMode === 'risque'">
                  <div class="ro-section-heading mb-3">
                    <v-icon color="primary" size="18">mdi-clipboard-check-outline</v-icon>
                    <span>Plan d'actions du risque</span>
                  </div>
                  <v-alert class="mb-3 ro-info-banner" density="comfortable" type="info" variant="tonal">
                    Définissez les actions préventives, correctives et de maîtrise.
                  </v-alert>

                  <v-card class="mb-3 ro-action-card" variant="outlined">
                    <v-card-title class="d-flex justify-space-between align-center ro-action-card-title">
                      <span class="text-subtitle-2">Actions préventives</span>
                      <v-btn
                        color="primary"
                        prepend-icon="mdi-plus"
                        size="small"
                        variant="tonal"
                        @click="addDialogAction('preventive')"
                      >
                        Ajouter
                      </v-btn>
                    </v-card-title>
                    <v-card-text>
                      <v-expansion-panels v-if="dialogActionsByType('preventive').length > 0" variant="accordion">
                        <v-expansion-panel
                          v-for="(entry, localIndex) in dialogActionsByType('preventive')"
                          :key="`preventive-${entry.index}`"
                        >
                          <v-expansion-panel-title>
                            <div class="d-flex align-center justify-space-between w-100">
                              <span class="font-weight-medium">Action préventive {{ localIndex + 1 }}</span>
                              <v-btn
                                color="error"
                                icon="mdi-delete"
                                size="x-small"
                                variant="text"
                                @click.stop="removeDialogAction(entry.index)"
                              />
                            </div>
                          </v-expansion-panel-title>
                          <v-expansion-panel-text>
                            <v-row>
                              <v-col cols="12">
                                <v-textarea v-model="entry.action.description" label="Description de l'action" rows="2" variant="outlined" />
                              </v-col>
                              <v-col cols="12" md="6">
                                <v-select
                                  v-model="entry.action.responsible_user_id"
                                  clearable
                                  item-title="title"
                                  item-value="value"
                                  :items="userOptions"
                                  label="Responsable"
                                  variant="outlined"
                                />
                              </v-col>
                              <v-col cols="12" md="6">
                                <v-select
                                  v-model="entry.action.implicated_user_ids"
                                  chips
                                  clearable
                                  item-title="title"
                                  item-value="value"
                                  :items="userOptions"
                                  label="Responsables impliqués"
                                  multiple
                                  variant="outlined"
                                />
                              </v-col>
                              <v-col cols="12">
                                <div class="d-flex align-center ga-2 mb-2">
                                  <v-select
                                    v-if="!entry.action.showDatePicker"
                                    v-model="entry.action.deadline_frequency"
                                    clearable
                                    item-title="label"
                                    item-value="value"
                                    :items="frequencyOptions"
                                    label="Fréquence"
                                    variant="outlined"
                                  />
                                  <AppDatePickerField
                                    v-else
                                    v-model="entry.action.deadline_frequency"
                                    label="Date limite"
                                    mode="date"
                                  />
                                  <v-btn :icon="entry.action.showDatePicker ? 'mdi-clock-outline' : 'mdi-calendar'" size="small" variant="tonal" @click="entry.action.showDatePicker = !entry.action.showDatePicker" />
                                </div>
                              </v-col>
                            </v-row>
                          </v-expansion-panel-text>
                        </v-expansion-panel>
                      </v-expansion-panels>
                      <v-alert v-else class="mt-2" type="info" variant="tonal">Aucune action préventive ajoutée.</v-alert>
                    </v-card-text>
                  </v-card>

                  <v-card class="mb-3 ro-action-card" variant="outlined">
                    <v-card-title class="d-flex justify-space-between align-center ro-action-card-title">
                      <span class="text-subtitle-2">Actions correctives</span>
                      <v-btn
                        color="primary"
                        prepend-icon="mdi-plus"
                        size="small"
                        variant="tonal"
                        @click="addDialogAction('corrective')"
                      >
                        Ajouter
                      </v-btn>
                    </v-card-title>
                    <v-card-text>
                      <v-expansion-panels v-if="dialogActionsByType('corrective').length > 0" variant="accordion">
                        <v-expansion-panel
                          v-for="(entry, localIndex) in dialogActionsByType('corrective')"
                          :key="`corrective-${entry.index}`"
                        >
                          <v-expansion-panel-title>
                            <div class="d-flex align-center justify-space-between w-100">
                              <span class="font-weight-medium">Action corrective {{ localIndex + 1 }}</span>
                              <v-btn
                                color="error"
                                icon="mdi-delete"
                                size="x-small"
                                variant="text"
                                @click.stop="removeDialogAction(entry.index)"
                              />
                            </div>
                          </v-expansion-panel-title>
                          <v-expansion-panel-text>
                            <v-row>
                              <v-col cols="12">
                                <v-textarea v-model="entry.action.description" label="Description de l'action" rows="2" variant="outlined" />
                              </v-col>
                              <v-col cols="12" md="6">
                                <v-select
                                  v-model="entry.action.responsible_user_id"
                                  clearable
                                  item-title="title"
                                  item-value="value"
                                  :items="userOptions"
                                  label="Responsable"
                                  variant="outlined"
                                />
                              </v-col>
                              <v-col cols="12" md="6">
                                <v-select
                                  v-model="entry.action.implicated_user_ids"
                                  chips
                                  clearable
                                  item-title="title"
                                  item-value="value"
                                  :items="userOptions"
                                  label="Responsables impliqués"
                                  multiple
                                  variant="outlined"
                                />
                              </v-col>
                              <v-col cols="12">
                                <div class="d-flex align-center ga-2 mb-2">
                                  <v-select
                                    v-if="!entry.action.showDatePicker"
                                    v-model="entry.action.deadline_frequency"
                                    clearable
                                    item-title="label"
                                    item-value="value"
                                    :items="frequencyOptions"
                                    label="Fréquence"
                                    variant="outlined"
                                  />
                                  <AppDatePickerField
                                    v-else
                                    v-model="entry.action.deadline_frequency"
                                    label="Date limite"
                                    mode="date"
                                  />
                                  <v-btn :icon="entry.action.showDatePicker ? 'mdi-clock-outline' : 'mdi-calendar'" size="small" variant="tonal" @click="entry.action.showDatePicker = !entry.action.showDatePicker" />
                                </div>
                              </v-col>
                            </v-row>
                          </v-expansion-panel-text>
                        </v-expansion-panel>
                      </v-expansion-panels>
                      <v-alert v-else class="mt-2" type="info" variant="tonal">Aucune action corrective ajoutée.</v-alert>
                    </v-card-text>
                  </v-card>

                  <v-card class="ro-action-card" variant="outlined">
                    <v-card-title class="d-flex justify-space-between align-center ro-action-card-title">
                      <span class="text-subtitle-2">Actions de maîtrise</span>
                      <v-btn
                        color="primary"
                        prepend-icon="mdi-plus"
                        size="small"
                        variant="tonal"
                        @click="addDialogAction('control')"
                      >
                        Ajouter
                      </v-btn>
                    </v-card-title>
                    <v-card-text>
                      <v-expansion-panels v-if="dialogActionsByType('control').length > 0" variant="accordion">
                        <v-expansion-panel
                          v-for="(entry, localIndex) in dialogActionsByType('control')"
                          :key="`control-${entry.index}`"
                        >
                          <v-expansion-panel-title>
                            <div class="d-flex align-center justify-space-between w-100">
                              <span class="font-weight-medium">Action de maîtrise {{ localIndex + 1 }}</span>
                              <v-btn
                                color="error"
                                icon="mdi-delete"
                                size="x-small"
                                variant="text"
                                @click.stop="removeDialogAction(entry.index)"
                              />
                            </div>
                          </v-expansion-panel-title>
                          <v-expansion-panel-text>
                            <v-row>
                              <v-col cols="12">
                                <v-textarea v-model="entry.action.description" label="Description de l'action" rows="2" variant="outlined" />
                              </v-col>
                              <v-col cols="12" md="6">
                                <v-select
                                  v-model="entry.action.responsible_user_id"
                                  clearable
                                  item-title="title"
                                  item-value="value"
                                  :items="userOptions"
                                  label="Responsable"
                                  variant="outlined"
                                />
                              </v-col>
                              <v-col cols="12" md="6">
                                <v-select
                                  v-model="entry.action.implicated_user_ids"
                                  chips
                                  clearable
                                  item-title="title"
                                  item-value="value"
                                  :items="userOptions"
                                  label="Responsables impliqués"
                                  multiple
                                  variant="outlined"
                                />
                              </v-col>
                              <v-col cols="12">
                                <div class="d-flex align-center ga-2 mb-2">
                                  <v-select
                                    v-if="!entry.action.showDatePicker"
                                    v-model="entry.action.deadline_frequency"
                                    clearable
                                    item-title="label"
                                    item-value="value"
                                    :items="frequencyOptions"
                                    label="Fréquence"
                                    variant="outlined"
                                  />
                                  <AppDatePickerField
                                    v-else
                                    v-model="entry.action.deadline_frequency"
                                    label="Date limite"
                                    mode="date"
                                  />
                                  <v-btn :icon="entry.action.showDatePicker ? 'mdi-clock-outline' : 'mdi-calendar'" size="small" variant="tonal" @click="entry.action.showDatePicker = !entry.action.showDatePicker" />
                                </div>
                              </v-col>
                            </v-row>
                          </v-expansion-panel-text>
                        </v-expansion-panel>
                      </v-expansion-panels>
                      <v-alert v-else class="mt-2" type="info" variant="tonal">Aucune action de maîtrise ajoutée.</v-alert>
                    </v-card-text>
                  </v-card>
                </template>

                <template v-else>
                  <div class="d-flex justify-space-between align-center mb-3 ro-opportunity-actions-header">
                    <div class="text-subtitle-1 font-weight-medium">Actions planifiées</div>
                    <v-btn
                      color="primary"
                      prepend-icon="mdi-plus"
                      size="small"
                      variant="tonal"
                      @click="addDialogAction()"
                    >Ajouter une action</v-btn>
                  </div>
                  <v-expansion-panels v-if="form.actions.length > 0" class="ro-action-panels" variant="accordion">
                    <v-expansion-panel v-for="(action, index) in form.actions" :key="index">
                      <v-expansion-panel-title>
                        <div class="d-flex align-center justify-space-between w-100">
                          <span class="font-weight-medium">Action {{ index + 1 }}</span>
                          <v-btn
                            color="error"
                            icon="mdi-delete"
                            size="x-small"
                            variant="text"
                            @click.stop="removeDialogAction(index)"
                          />
                        </div>
                      </v-expansion-panel-title>
                      <v-expansion-panel-text>
                        <v-row>
                          <v-col cols="12">
                            <v-textarea v-model="action.description" label="Description de l'action" rows="2" variant="outlined" />
                          </v-col>
                          <v-col cols="12" md="6">
                            <v-select
                              v-model="action.responsible_user_id"
                              clearable
                              item-title="title"
                              item-value="value"
                              :items="userOptions"
                              label="Responsable"
                              variant="outlined"
                            />
                          </v-col>
                          <v-col cols="12" md="6">
                            <v-select
                              v-model="action.implicated_user_ids"
                              chips
                              clearable
                              item-title="title"
                              item-value="value"
                              :items="userOptions"
                              label="Responsables impliqués"
                              multiple
                              variant="outlined"
                            />
                          </v-col>
                          <v-col cols="12">
                            <div class="d-flex align-center ga-2 mb-2">
                              <v-select
                                v-if="!action.showDatePicker"
                                v-model="action.deadline_frequency"
                                clearable
                                item-title="label"
                                item-value="value"
                                :items="frequencyOptions"
                                label="Fréquence"
                                variant="outlined"
                              />
                              <AppDatePickerField
                                v-else
                                v-model="action.deadline_frequency"
                                label="Date limite"
                                mode="date"
                              />
                              <v-btn :icon="action.showDatePicker ? 'mdi-clock-outline' : 'mdi-calendar'" size="small" variant="tonal" @click="action.showDatePicker = !action.showDatePicker" />
                            </div>
                          </v-col>
                        </v-row>
                      </v-expansion-panel-text>
                    </v-expansion-panel>
                  </v-expansion-panels>
                  <v-alert v-else class="mt-2 ro-info-banner" type="info" variant="tonal">Aucune action planifiée. Cliquez sur "Ajouter une action" pour commencer.</v-alert>
                </template>
              </v-col>
            </v-row>
          </v-card-text>
          <v-card-actions class="ro-dialog-actions">
            <v-spacer />
            <v-btn variant="text" @click="closeDialog">Annuler</v-btn>
            <v-btn v-if="dialogStep === 2" variant="outlined" @click="dialogStep = 1">Précédent</v-btn>
            <v-btn v-if="dialogStep === 1" color="primary" @click="goToActionsStep">Suivant</v-btn>
            <v-btn v-else color="primary" :loading="saving" @click="saveItem">Enregistrer</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-container>
  </ClientALayout>
</template>
<script setup lang="ts">
  import { computed, reactive, ref, watch } from 'vue'
  import { useRouter } from 'vue-router'
  import { useDisplay } from 'vuetify'
  import * as XLSX from 'xlsx'
  import api from '@/api/client'
  import AppDatePickerField from '@/components/common/AppDatePickerField.vue'
  import AppWidget from '@/components/common/AppWidget.vue'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import { useToast } from '@/modules/shared/composables/useToast'
  import processService from '@/services/processService'
  import { useAuthStore } from '@/stores/auth'

  const toast = useToast()
  const router = useRouter()
  const authStore = useAuthStore()
  const { mdAndDown } = useDisplay()
  const isMobile = computed(() => mdAndDown.value)

  const loading = ref(false)
  const saving = ref(false)
  const showDialog = ref(false)
  const dialogStep = ref<1 | 2>(1)
  const dialogStepProgress = computed(() => (dialogStep.value === 1 ? 46 : 100))
  const viewMode = ref<'risque' | 'opportunite'>('risque')
  const viewType = ref<'grid' | 'list'>('grid')
  const editingItem = ref<any>(null)

  const tableHeaders = computed(() => (
    isMobile.value
      ? [
        { title: 'Code', key: 'code', sortable: true, width: '84px' },
        { title: 'Description', key: 'description', sortable: true },
        { title: 'Score', key: 'score', sortable: true, width: '88px' },
        { title: 'Statut', key: 'status', sortable: true, width: '110px' },
        { title: 'Actions', key: 'actions', sortable: false, width: '122px', align: 'end' as const },
      ]
      : [
        { title: 'Code', key: 'code', sortable: true, width: '92px' },
        { title: 'Processus', key: 'process_name', sortable: true, width: '150px' },
        { title: 'Description', key: 'description', sortable: true },
        { title: 'Évaluation', key: 'evaluation', sortable: false, width: '136px' },
        { title: viewMode.value === 'risque' ? 'Criticité' : 'Priorité', key: 'score', sortable: true, width: '90px' },
        { title: 'Responsable', key: 'responsable_name', sortable: true, width: '145px' },
        { title: 'Statut', key: 'status', sortable: true, width: '122px' },
        { title: 'Actions', key: 'actions', sortable: false, width: '122px', align: 'end' as const },
      ]
  ))

  const items = ref<any[]>([])
  const rawItems = ref<any[]>([])
  const processes = ref<Array<{ id: number, name?: string, title?: string }>>([])
  const users = ref<Array<{ id: number, name: string }>>([])
  const isPageLoading = ref(false)
  const loadedSiteId = ref<number | null>(null)

  const statusItems = [
    { value: 'identifie', label: 'Identifié' },
    { value: 'en_cours', label: 'En cours' },
    { value: 'traite', label: 'Traité' },
    { value: 'surveille', label: 'Surveillé' },
    { value: 'cloture', label: 'Clôturé' },
  ]

  const niveauOptions = [
    { label: 'Faible', value: 'faible' },
    { label: 'Moyen', value: 'moyen' },
    { label: 'Élevé', value: 'eleve' },
    { label: 'Critique', value: 'critique' },
  ]

  const frequencyOptions = [
    { label: 'En continu', value: 'En continu' },
    { label: 'Hebdomadaire', value: 'Hebdomadaire' },
    { label: 'Bihebdomadaire', value: 'Bihebdomadaire' },
    { label: 'Mensuel', value: 'Mensuel' },
    { label: 'Bimestriel', value: 'Bimestriel' },
    { label: 'Trimestriel', value: 'Trimestriel' },
    { label: 'Quadrimestriel', value: 'Quadrimestriel' },
    { label: 'Semestriel', value: 'Semestriel' },
    { label: 'Annuel', value: 'Annuel' },
    { label: 'Biennal', value: 'Biennal' },
  ]

  type RiskActionType = 'preventive' | 'corrective' | 'control'

  const probabilityScaleOptions = computed(() => {
    if (viewMode.value === 'risque') {
      return [
        { value: 4, label: 'Très fréquent = 4' },
        { value: 3, label: 'Fréquent = 3' },
        { value: 2, label: 'Peu fréquent = 2' },
        { value: 1, label: 'Rare = 1' },
      ]
    }

    return [
      { value: 4, label: 'Très probable = 4' },
      { value: 3, label: 'Probable = 3' },
      { value: 2, label: 'Peu probable = 2' },
      { value: 1, label: 'Incertain = 1' },
    ]
  })

  const impactScaleOptions = computed(() => {
    if (viewMode.value === 'risque') {
      return [
        { value: 4, label: 'Très élevée = 4' },
        { value: 3, label: 'Élevée = 3' },
        { value: 2, label: 'Moyenne = 2' },
        { value: 1, label: 'Faible = 1' },
      ]
    }

    return [
      { value: 4, label: 'Très pertinent = 4' },
      { value: 3, label: 'Pertinent = 3' },
      { value: 2, label: 'Moins pertinent = 2' },
      { value: 1, label: 'Faible = 1' },
    ]
  })

  const filters = reactive({
    search: '',
    process_id: null as number | null,
    status: null as string | null,
    niveau: null as string | null,
  })

  const form = reactive({
    process_id: null as number | null,
    description: '',
    cause: '',
    probabilite: 1,
    gravite: 1,
    status: 'identifie',
    actions: [] as Array<{
      action_type: RiskActionType | null
      description: string
      responsible_user_id: number | null
      implicated_user_ids: number[]
      deadline_frequency: string
      showDatePicker: boolean
    }>,
  })

  function getCurrentSiteId (): number | null {
    const stored = Number(localStorage.getItem('current_site_id'))
    return authStore.currentSiteId ?? (Number.isFinite(stored) ? stored : null)
  }

  const processOptions = computed(() => processes.value.map(process => ({
    title: process.title || process.name || `Processus #${process.id}`,
    value: process.id,
  })))

  const userOptions = computed(() => users.value.map((user: any) => ({
    title: user.name,
    value: user.id,
  })))

  function dialogActionsByType (type: RiskActionType) {
    return form.actions
      .map((action, index) => ({ action, index }))
      .filter(entry => entry.action.action_type === type)
  }

  function addDialogAction (type?: RiskActionType) {
    form.actions.push({
      action_type: viewMode.value === 'risque' ? (type || 'preventive') : null,
      description: '',
      responsible_user_id: null,
      implicated_user_ids: [],
      deadline_frequency: '',
      showDatePicker: false,
    })
  }

  function removeDialogAction (index: number) {
    form.actions.splice(index, 1)
  }

  const modeItems = computed(() => items.value.filter(item => item.type === viewMode.value))
  const highPriorityCount = computed(() => modeItems.value.filter(item => Number(item.score) >= 12).length)
  const inProgressCount = computed(() => modeItems.value.filter(item => ['en_cours', 'surveille'].includes(item.status)).length)
  const closedCount = computed(() => modeItems.value.filter(item => ['traite', 'cloture'].includes(item.status)).length)
  const completionRate = computed(() => {
    if (modeItems.value.length === 0) return 0
    return Math.round((closedCount.value / modeItems.value.length) * 100)
  })

  const avgCriticite = computed(() => {
    if (modeItems.value.length === 0) return 0
    const total = modeItems.value.reduce((sum, item) => sum + (item.score || 0), 0)
    return Math.round((total / modeItems.value.length) * 10) / 10
  })
  const averagePriorityLabel = computed(() => priorityLabel(Number(avgCriticite.value)))

  const filteredItems = computed(() => {
    const search = filters.search.trim().toLowerCase()
    return items.value
      .filter(item => item.type === viewMode.value)
      .filter(item => !filters.process_id || Number(item.process_id) === Number(filters.process_id))
      .filter(item => !filters.status || item.status === filters.status)
      .filter(item => !filters.niveau || item.niveau === filters.niveau)
      .filter(item => {
        if (!search) return true
        const text = `${item.description || ''} ${item.process_name || ''} ${item.cause || ''}`.toLowerCase()
        return text.includes(search)
      })
  })
  const hasActiveFilters = computed(() => {
    return Boolean(filters.search.trim() || filters.process_id || filters.status || filters.niveau)
  })
  const emptyStateMessage = computed(() => {
    if (modeItems.value.length === 0) {
      return viewMode.value === 'risque'
        ? 'Aucun risque enregistré pour ce site. Ajoutez votre premier risque.'
        : 'Aucune opportunité enregistrée pour ce site. Ajoutez votre première opportunité.'
    }

    return 'Ajustez les filtres ou créez un nouvel élément.'
  })

  function resetFilters () {
    filters.search = ''
    filters.process_id = null
    filters.status = null
    filters.niveau = null
  }

  function parsePlannedActions (value: any): any[] {
    if (!value) return []
    if (Array.isArray(value)) return value
    if (typeof value !== 'string') return []
    try {
      const parsed = JSON.parse(value)
      return Array.isArray(parsed) ? parsed : []
    } catch {
      return value
        .split(/\r\n|\r|\n/)
        .map(line => line.trim())
        .filter(Boolean)
        .map(line => ({ description: line }))
    }
  }

  function riskActionTypeLabel (type: string | null | undefined): string {
    if (type === 'preventive') return 'Préventive'
    if (type === 'corrective') return 'Corrective'
    if (type === 'control') return 'Maîtrise'
    return ''
  }

  function getActionDescription (action: any): string {
    return String(action?.description || action?.title || action?.action || '').trim()
  }

  function buildRiskActionTypeSummaries (plannedActions: any[]) {
    const grouped: Record<string, string[]> = {
      preventive: [],
      corrective: [],
      control: [],
    }

    for (const action of plannedActions) {
      const description = getActionDescription(action)
      if (!description) continue

      const key = (action?.action_type || 'preventive') as RiskActionType
      if (!grouped[key]) grouped[key] = []
      grouped[key].push(description)
    }

    const order: RiskActionType[] = ['preventive', 'corrective', 'control']
    return order
      .map(key => ({
        key,
        label: riskActionTypeLabel(key),
        count: grouped[key].length,
        preview_list: grouped[key].slice(0, 1),
        remaining: Math.max(0, grouped[key].length - 1),
      }))
      .filter(summary => summary.count > 0)
  }

  function resolveUserName (id: number | null | undefined): string {
    if (!id) return ''
    return users.value.find(user => user.id === id)?.name || ''
  }

  function uniqueNames (names: Array<string | undefined | null>): string[] {
    const unique = new Set<string>()
    for (const name of names) {
      if (name) unique.add(name)
    }
    return Array.from(unique)
  }

  function buildActionsText (plannedActions: any[]): string {
    const lines = plannedActions.map((action: any, index: number) => {
      const label = riskActionTypeLabel(action.action_type)
      const description = action.description || action.title || action.action || ''
      const parts = [`${label ? `[${label}] ` : ''}Action ${index + 1}: ${description}`]
      const responsibleName = resolveUserName(action.responsible_user_id)
      if (responsibleName) parts.push(`Responsable: ${responsibleName}`)

      if (Array.isArray(action.implicated_user_ids) && action.implicated_user_ids.length > 0) {
        const implNames = action.implicated_user_ids
          .map((id: number) => resolveUserName(id))
          .filter(Boolean)
          .join(', ')
        if (implNames) parts.push(`Impliqués: ${implNames}`)
      }

      if (action.deadline_frequency) parts.push(`Échéance: ${action.deadline_frequency}`)
      return parts.join(' | ')
    })
    return lines.join('\n')
  }

  function resolveResponsables (plannedActions: any[]): string {
    const names = plannedActions
      .map((action: any) => resolveUserName(action.responsible_user_id))
      .filter(Boolean)
    return uniqueNames(names).join(', ')
  }

  function resolveImplicated (plannedActions: any[]): string {
    const names = plannedActions
      .flatMap((action: any) => action.implicated_user_ids || [])
      .map((id: number) => resolveUserName(id))
      .filter(Boolean)
    return uniqueNames(names).join(', ')
  }

  function resolveDeadlines (plannedActions: any[]): string {
    return plannedActions
      .map((action: any) => action.deadline_frequency)
      .filter(Boolean)
      .join(', ')
  }

  function mapItem (item: any) {
    const process = item.process || {}
    const probabilite = Number(item.probabilite || 1)
    const gravite = Number(item.gravite || 1)
    const plannedActions = parsePlannedActions(item.planned_actions)

    const firstAction = plannedActions[0] || {}
    const responsibleId = Number(firstAction.responsible_user_id || 0)
    const responsibleName = resolveUserName(responsibleId)
    const implicatedIds = Array.isArray(firstAction.implicated_user_ids) ? firstAction.implicated_user_ids : []

    const allActionsText = buildActionsText(plannedActions)
    const allResponsibles = resolveResponsables(plannedActions)
    const allImplicated = resolveImplicated(plannedActions)
    const allDeadlines = resolveDeadlines(plannedActions)
    const actionDescriptions = plannedActions
      .map((action: any) => getActionDescription(action))
      .filter(Boolean)
    const actionTypeSummaries = item.type === 'risque'
      ? buildRiskActionTypeSummaries(plannedActions)
      : []

    return {
      id: Number(item.id),
      code: item.code || item.id,
      type: item.type,
      process_id: Number(item.process_id),
      process_name: process.title || process.name || `Processus #${item.process_id}`,
      description: item.description || item.title || '',
      cause: item.cause || '',
      probabilite,
      gravite,
      score: Number(item.criticite || probabilite * gravite),
      status: item.status || 'identifie',
      actions_prevues: allActionsText || firstAction.description || firstAction.title || firstAction.action || '',
      action_count: actionDescriptions.length,
      action_preview_list: actionDescriptions.slice(0, 2),
      action_remaining_count: Math.max(0, actionDescriptions.length - 2),
      action_type_summaries: actionTypeSummaries,
      responsible_user_id: responsibleId || null,
      responsable_name: allResponsibles || responsibleName,
      responsables_implique_ids: implicatedIds,
      responsables_implique_display: allImplicated,
      delai_frequence: allDeadlines || firstAction.deadline_frequency || '',
    }
  }

  function setSheetCellValue (
    sheet: XLSX.WorkSheet,
    address: string,
    value: string | number,
  ) {
    const existingCell = sheet[address]
    if (existingCell) {
      existingCell.v = value
      existingCell.t = typeof value === 'number' ? 'n' : 's'
      delete existingCell.w
      return
    }

    sheet[address] = {
      t: typeof value === 'number' ? 'n' : 's',
      v: value,
    } as XLSX.CellObject
  }

  function ensureSheetRange (sheet: XLSX.WorkSheet, row: number, col: number) {
    const range = sheet['!ref']
      ? XLSX.utils.decode_range(sheet['!ref'])
      : { s: { c: col, r: row }, e: { c: col, r: row } }

    range.s.r = Math.min(range.s.r, row)
    range.s.c = Math.min(range.s.c, col)
    range.e.r = Math.max(range.e.r, row)
    range.e.c = Math.max(range.e.c, col)

    sheet['!ref'] = XLSX.utils.encode_range(range)
  }

  function writeCell (
    sheet: XLSX.WorkSheet,
    rowIndex: number,
    colIndex: number,
    value: string | number | null | undefined = '',
  ) {
    const finalValue = value ?? ''
    const cellAddress = XLSX.utils.encode_cell({ r: rowIndex - 1, c: colIndex })
    setSheetCellValue(sheet, cellAddress, finalValue)
    ensureSheetRange(sheet, rowIndex - 1, colIndex)
  }

  function normalizeHexColor (value: string): string {
    return value.replace('#', '').toUpperCase()
  }

  function getScorePalette (value: number, type: 'risque' | 'opportunite') {
    if (type === 'opportunite') {
      if (value >= 12) {
        return { color: '#1565C0', text: '#FFFFFF' }
      }
      if (value >= 8) {
        return { color: '#00897B', text: '#FFFFFF' }
      }
      if (value >= 4) {
        return { color: '#7CB342', text: '#0F172A' }
      }

      return { color: '#8E24AA', text: '#FFFFFF' }
    }

    if (value >= 12) {
      return { color: '#D32F2F', text: '#FFFFFF' }
    }
    if (value >= 8) {
      return { color: '#F57C00', text: '#0F172A' }
    }
    if (value >= 4) {
      return { color: '#FBC02D', text: '#0F172A' }
    }

    return { color: '#2E7D32', text: '#FFFFFF' }
  }

  function applyFilledCellStyle (
    sheet: XLSX.WorkSheet,
    rowIndex: number,
    colIndex: number,
    backgroundColor: string,
    textColor: string,
  ) {
    const cellAddress = XLSX.utils.encode_cell({ r: rowIndex - 1, c: colIndex })
    const currentCell = (sheet[cellAddress] || {}) as XLSX.CellObject & { s?: Record<string, any> }

    sheet[cellAddress] = {
      ...currentCell,
      s: {
        ...currentCell.s,
        fill: {
          patternType: 'solid',
          fgColor: { rgb: normalizeHexColor(backgroundColor) },
          bgColor: { rgb: normalizeHexColor(backgroundColor) },
        },
        font: {
          ...currentCell.s?.font,
          bold: true,
          color: { rgb: normalizeHexColor(textColor) },
        },
        alignment: {
          ...currentCell.s?.alignment,
          horizontal: 'center',
          vertical: 'center',
        },
      },
    }
  }

  function cloneRowStyleFromTemplate (
    sheet: XLSX.WorkSheet,
    sourceRowIndex: number,
    targetRowIndex: number,
    fromColIndex: number,
    toColIndex: number,
  ) {
    for (let colIndex = fromColIndex; colIndex <= toColIndex; colIndex++) {
      const sourceAddress = XLSX.utils.encode_cell({ r: sourceRowIndex - 1, c: colIndex })
      const targetAddress = XLSX.utils.encode_cell({ r: targetRowIndex - 1, c: colIndex })
      const sourceCell = sheet[sourceAddress] as XLSX.CellObject | undefined
      const targetCell = sheet[targetAddress] as XLSX.CellObject | undefined

      if (!sourceCell) continue

      const clonedCell: XLSX.CellObject = {
        ...sourceCell,
        v: '',
        t: sourceCell.t || 's',
      }
      delete clonedCell.w

      sheet[targetAddress] = targetCell
        ? {
          ...targetCell,
          s: clonedCell.s,
          z: clonedCell.z,
          t: clonedCell.t,
          v: '',
        }
        : clonedCell

      ensureSheetRange(sheet, targetRowIndex - 1, colIndex)
    }
  }

  function mergeProcessCells (
    sheet: XLSX.WorkSheet,
    startRow: number,
    endRow: number,
  ) {
    if (endRow <= startRow) return

    const merges = sheet['!merges'] || []
    merges.push({
      s: { r: startRow - 1, c: 2 }, // C
      e: { r: endRow - 1, c: 2 }, // C
    })
    sheet['!merges'] = merges
  }

  function buildExportRowsByProcess () {
    const grouped = new Map<number, {
      processName: string
      risks: any[]
      opportunities: any[]
    }>()

    for (const rawItem of rawItems.value) {
      const processId = Number(rawItem.process_id || 0)
      const process = rawItem.process || {}
      const processName = process.title || process.name || `Processus #${processId}`

      if (!grouped.has(processId)) {
        grouped.set(processId, {
          processName,
          risks: [],
          opportunities: [],
        })
      }

      const group = grouped.get(processId)!

      let plannedActions = rawItem.planned_actions
      if (typeof plannedActions === 'string') {
        try {
          plannedActions = JSON.parse(plannedActions)
        } catch {
          plannedActions = []
        }
      }
      if (!Array.isArray(plannedActions)) plannedActions = []

      const typeOrder: Record<string, number> = {
        preventive: 1,
        corrective: 2,
        control: 3,
      }

      const actionsToExport = (plannedActions.length > 0 ? plannedActions : [{}])
        .map((action: any, idx: number) => ({ action, idx }))
        .toSorted((a, b) => {
          // Aligner les actions risques par type pour faciliter les fusions de cellules.
          if (rawItem.type !== 'risque') return a.idx - b.idx

          const aRank = typeOrder[a.action?.action_type] ?? 99
          const bRank = typeOrder[b.action?.action_type] ?? 99
          if (aRank !== bRank) return aRank - bRank

          return a.idx - b.idx
        })
        .map(entry => entry.action)

      for (const [actionIndex, action] of actionsToExport.entries()) {
        const responsibleName = action.responsible_user_id
          ? users.value.find(u => u.id === action.responsible_user_id)?.name || ''
          : (action.responsible || '')

        const implicatedNames = Array.isArray(action.implicated_user_ids)
          ? action.implicated_user_ids
            .map((id: number) => users.value.find(u => u.id === id)?.name)
            .filter(Boolean)
            .join(', ')
          : ''

        const actionTypeLabel = rawItem.type === 'risque'
          ? riskActionTypeLabel(action.action_type)
          : ''
        const actionDescription = action.description || action.title || action.action || ''
        const actionSummary = actionDescription
        const actionDeadline = action.deadline_frequency || action.due_date || ''

        const exportRow = {
          id: rawItem.id,
          code: rawItem.code || rawItem.id,
          type: rawItem.type,
          description: rawItem.description || rawItem.title || '',
          cause: rawItem.cause || '',
          probabilite: Number(rawItem.probabilite || 1),
          gravite: Number(rawItem.gravite || 1),
          score: Number(rawItem.criticite || (rawItem.probabilite * rawItem.gravite)),
          action_description: actionSummary,
          action_type_label: actionTypeLabel,
          action_responsible: responsibleName,
          action_implicated: implicatedNames,
          action_deadline: actionDeadline,
          actions_prevues: actionSummary,
          responsable_name: responsibleName,
          responsables_implique_display: implicatedNames,
          delai_frequence: actionDeadline,
          is_first_action: actionIndex === 0,
          actions_count: actionsToExport.length,
        }

        if (rawItem.type === 'risque') {
          group.risks.push(exportRow)
        } else if (rawItem.type === 'opportunite') {
          group.opportunities.push(exportRow)
        }
      }
    }

    return Array.from(grouped.values()).toSorted((a: any, b: any) => a.processName.localeCompare(b.processName))
  }

  async function loadExportWorkbook () {
    const response = await fetch('/plan-maitrise-risques-opportunites-template.xlsx')
    if (!response.ok) throw new Error('template_unavailable')

    const fileBuffer = await response.arrayBuffer()
    const workbook = XLSX.read(fileBuffer, { type: 'array', cellStyles: true })
    const sheetName = workbook.SheetNames[0]
    if (!sheetName) throw new Error('no_sheet_found')
    const sheet = workbook.Sheets[sheetName]
    if (!sheet) throw new Error('sheet_not_found')

    // Reconfiguration explicite du bandeau d'en-têtes pour afficher "Type d'action" avant "Actions" (risques).
    const staticMerges: any[] = [
      { s: { c: 3, r: 1 }, e: { c: 7, r: 2 } }, // Titre principal
      { s: { c: 1, r: 4 }, e: { c: 1, r: 5 } }, // N°
      { s: { c: 2, r: 4 }, e: { c: 2, r: 5 } }, // Processus
      { s: { c: 3, r: 4 }, e: { c: 3, r: 5 } }, // Risques
      { s: { c: 4, r: 4 }, e: { c: 4, r: 5 } }, // Causes
      { s: { c: 5, r: 4 }, e: { c: 7, r: 4 } }, // Eval risques
      { s: { c: 8, r: 4 }, e: { c: 8, r: 5 } }, // Type action risque
      { s: { c: 9, r: 4 }, e: { c: 9, r: 5 } }, // Actions risque
      { s: { c: 10, r: 4 }, e: { c: 10, r: 5 } }, // Responsable risque
      { s: { c: 11, r: 4 }, e: { c: 11, r: 5 } }, // Impliqués risque
      { s: { c: 12, r: 4 }, e: { c: 12, r: 5 } }, // Délai risque
      { s: { c: 13, r: 4 }, e: { c: 14, r: 4 } }, // Suivi risque
      { s: { c: 15, r: 4 }, e: { c: 15, r: 5 } }, // Opportunité
      { s: { c: 16, r: 4 }, e: { c: 18, r: 4 } }, // Eval opportunités
      { s: { c: 19, r: 4 }, e: { c: 19, r: 5 } }, // Actions opportunité
      { s: { c: 20, r: 4 }, e: { c: 20, r: 5 } }, // Responsable opportunité
      { s: { c: 21, r: 4 }, e: { c: 21, r: 5 } }, // Impliqués opportunité
      { s: { c: 22, r: 4 }, e: { c: 22, r: 5 } }, // Délai opportunité
      { s: { c: 23, r: 4 }, e: { c: 24, r: 4 } }, // Suivi opportunité
    ]
    sheet['!merges'] = staticMerges

    // Ligne 5 (titres de groupes)
    writeCell(sheet, 5, 1, 'N°')
    writeCell(sheet, 5, 2, 'PROCESSUS')
    writeCell(sheet, 5, 3, 'RISQUES')
    writeCell(sheet, 5, 4, 'CAUSES PROFONDES')
    writeCell(sheet, 5, 5, 'EVALUATION')
    writeCell(sheet, 5, 8, 'TYPE D\'ACTION')
    writeCell(sheet, 5, 9, 'ACTIONS')
    writeCell(sheet, 5, 10, 'RESPONSABLE')
    writeCell(sheet, 5, 11, 'RESPONSABLE(S) IMPLIQUES')
    writeCell(sheet, 5, 12, 'DELAI DE MISE EN ŒUVRE / FREQUENCE')
    writeCell(sheet, 5, 13, 'SUIVI')
    writeCell(sheet, 5, 15, 'OPPORTUNITES')
    writeCell(sheet, 5, 16, 'EVALUATION')
    writeCell(sheet, 5, 19, 'ACTIONS')
    writeCell(sheet, 5, 20, 'RESPONSABLE')
    writeCell(sheet, 5, 21, 'RESPONSABLE(S) IMPLIQUES')
    writeCell(sheet, 5, 22, 'DELAI DE MISE EN ŒUVRE / FREQUENCE')
    writeCell(sheet, 5, 23, 'SUIVI')

    // Ligne 6 (sous-entêtes)
    writeCell(sheet, 6, 5, 'Probabilité')
    writeCell(sheet, 6, 6, 'Gravité')
    writeCell(sheet, 6, 7, 'Criticité')
    writeCell(sheet, 6, 13, 'Efficacité des actions')
    writeCell(sheet, 6, 14, 'Commentaires')
    writeCell(sheet, 6, 16, 'Probabilité')
    writeCell(sheet, 6, 17, 'Pertinence')
    writeCell(sheet, 6, 18, 'Niveau de priorité')
    writeCell(sheet, 6, 23, 'Efficacité des actions')
    writeCell(sheet, 6, 24, 'Commentaires')

    const riskHeaderPalette = getScorePalette(12, 'risque')
    const opportunityHeaderPalette = getScorePalette(12, 'opportunite')
    applyFilledCellStyle(sheet, 6, 7, riskHeaderPalette.color, riskHeaderPalette.text)
    applyFilledCellStyle(sheet, 6, 18, opportunityHeaderPalette.color, opportunityHeaderPalette.text)

    return { workbook, sheet }
  }

  function fillExportRow (
    sheet: XLSX.WorkSheet,
    excelRow: number,
    sequence: number,
    processName: string,
    isFirstOfGroup: boolean,
    risk?: any,
    opportunity?: any,
  ) {
    const value = (source: any, key: string) => source && source[key] ? source[key] : ''

    cloneRowStyleFromTemplate(sheet, 7, excelRow, 1, 24)

    writeCell(sheet, excelRow, 1, sequence) // B - N°
    writeCell(sheet, excelRow, 2, isFirstOfGroup ? processName : '') // C - Processus
    writeCell(sheet, excelRow, 3, value(risk, 'description')) // D - Risque
    writeCell(sheet, excelRow, 4, value(risk, 'cause')) // E - Causes profondes
    writeCell(sheet, excelRow, 5, value(risk, 'probabilite')) // F
    writeCell(sheet, excelRow, 6, value(risk, 'gravite')) // G
    writeCell(sheet, excelRow, 7, value(risk, 'score')) // H
    writeCell(sheet, excelRow, 8, value(risk, 'action_type_label')) // I - Type d'action (risque)
    writeCell(sheet, excelRow, 9, value(risk, 'actions_prevues')) // J - Actions (risque)
    writeCell(sheet, excelRow, 10, value(risk, 'responsable_name')) // K
    writeCell(sheet, excelRow, 11, value(risk, 'responsables_implique_display')) // L
    writeCell(sheet, excelRow, 12, value(risk, 'delai_frequence')) // M
    writeCell(sheet, excelRow, 13, '') // N - Efficacité (non saisi ici)
    writeCell(sheet, excelRow, 14, '') // O - Commentaires (non saisi ici)

    writeCell(sheet, excelRow, 15, value(opportunity, 'description')) // P - Opportunité
    writeCell(sheet, excelRow, 16, value(opportunity, 'probabilite')) // Q
    writeCell(sheet, excelRow, 17, value(opportunity, 'gravite')) // R
    writeCell(sheet, excelRow, 18, value(opportunity, 'score')) // S
    writeCell(sheet, excelRow, 19, value(opportunity, 'actions_prevues')) // T
    writeCell(sheet, excelRow, 20, value(opportunity, 'responsable_name')) // U
    writeCell(sheet, excelRow, 21, value(opportunity, 'responsables_implique_display')) // V
    writeCell(sheet, excelRow, 22, value(opportunity, 'delai_frequence')) // W
    writeCell(sheet, excelRow, 23, '') // X - Efficacité (non saisi ici)
    writeCell(sheet, excelRow, 24, '') // Y - Commentaires (non saisi ici)

    if (risk?.score !== undefined && risk?.score !== null && risk?.score !== '') {
      const palette = getScorePalette(Number(risk.score), 'risque')
      applyFilledCellStyle(sheet, excelRow, 7, palette.color, palette.text)
    }

    if (opportunity?.score !== undefined && opportunity?.score !== null && opportunity?.score !== '') {
      const palette = getScorePalette(Number(opportunity.score), 'opportunite')
      applyFilledCellStyle(sheet, excelRow, 18, palette.color, palette.text)
    }
  }

  function mergeRiskActionTypeCells (
    sheet: XLSX.WorkSheet,
    startRow: number,
    endRow: number,
  ) {
    if (endRow <= startRow) return

    const merges = sheet['!merges'] || []
    merges.push({
      s: { r: startRow - 1, c: 8 }, // I
      e: { r: endRow - 1, c: 8 }, // I
    })
    sheet['!merges'] = merges
  }

  function mergeRiskCells (
    sheet: XLSX.WorkSheet,
    startRow: number,
    endRow: number,
  ) {
    if (endRow <= startRow) return

    const merges = sheet['!merges'] || []
    // D -> H : Risque + causes + évaluation (P/G/C) pour garder la lisibilité
    for (let col = 3; col <= 7; col++) {
      merges.push({
        s: { r: startRow - 1, c: col },
        e: { r: endRow - 1, c: col },
      })
    }
    sheet['!merges'] = merges
  }

  function mergeOpportunityCells (
    sheet: XLSX.WorkSheet,
    startRow: number,
    endRow: number,
  ) {
    if (endRow <= startRow) return

    const merges = sheet['!merges'] || []
    // P -> S : Opportunité + évaluation (Probabilité / Pertinence / Priorité)
    for (let col = 15; col <= 18; col++) {
      merges.push({
        s: { r: startRow - 1, c: col },
        e: { r: endRow - 1, c: col },
      })
    }
    sheet['!merges'] = merges
  }

  function fillSheetByProcessGroups (sheet: XLSX.WorkSheet, groupedRows: Array<any>) {
    let excelRow = 7
    let sequence = 1

    for (const group of groupedRows) {
      const processStartRow = excelRow
      const rowCount = Math.max(group.risks.length, group.opportunities.length, 1)
      let riskGroupStartRow: number | null = null
      let riskGroupId: number | null = null
      let opportunityGroupStartRow: number | null = null
      let opportunityGroupId: number | null = null
      let typeGroupStartRow: number | null = null
      let typeGroupRiskId: number | null = null
      let typeGroupLabel = ''

      const closeRiskGroup = (lastRow: number) => {
        if (riskGroupStartRow === null) return
        mergeRiskCells(sheet, riskGroupStartRow, lastRow)
        riskGroupStartRow = null
        riskGroupId = null
      }

      const closeOpportunityGroup = (lastRow: number) => {
        if (opportunityGroupStartRow === null) return
        mergeOpportunityCells(sheet, opportunityGroupStartRow, lastRow)
        opportunityGroupStartRow = null
        opportunityGroupId = null
      }

      const closeTypeGroup = (lastRow: number) => {
        if (typeGroupStartRow === null) return
        mergeRiskActionTypeCells(sheet, typeGroupStartRow, lastRow)
        typeGroupStartRow = null
        typeGroupRiskId = null
        typeGroupLabel = ''
      }

      for (let i = 0; i < rowCount; i++) {
        const riskRow = group.risks[i]
        const riskId = riskRow?.id ? Number(riskRow.id) : null
        const riskTypeLabel = riskRow?.action_type_label || ''
        const opportunityRow = group.opportunities[i]
        const opportunityId = opportunityRow?.id ? Number(opportunityRow.id) : null

        if (riskId) {
          if (riskGroupStartRow === null) {
            riskGroupStartRow = excelRow
            riskGroupId = riskId
          } else if (riskGroupId !== riskId) {
            closeRiskGroup(excelRow - 1)
            riskGroupStartRow = excelRow
            riskGroupId = riskId
          }
        } else {
          closeRiskGroup(excelRow - 1)
        }

        if (opportunityId) {
          if (opportunityGroupStartRow === null) {
            opportunityGroupStartRow = excelRow
            opportunityGroupId = opportunityId
          } else if (opportunityGroupId !== opportunityId) {
            closeOpportunityGroup(excelRow - 1)
            opportunityGroupStartRow = excelRow
            opportunityGroupId = opportunityId
          }
        } else {
          closeOpportunityGroup(excelRow - 1)
        }

        if (riskId && riskTypeLabel) {
          if (typeGroupStartRow === null) {
            typeGroupStartRow = excelRow
            typeGroupRiskId = riskId
            typeGroupLabel = riskTypeLabel
          } else if (typeGroupRiskId !== riskId || typeGroupLabel !== riskTypeLabel) {
            closeTypeGroup(excelRow - 1)
            typeGroupStartRow = excelRow
            typeGroupRiskId = riskId
            typeGroupLabel = riskTypeLabel
          }
        } else {
          closeTypeGroup(excelRow - 1)
        }

        fillExportRow(
          sheet,
          excelRow,
          sequence,
          group.processName,
          i === 0,
          group.risks[i],
          group.opportunities[i],
        )
        excelRow += 1
        sequence += 1
      }

      closeRiskGroup(excelRow - 1)
      closeOpportunityGroup(excelRow - 1)
      closeTypeGroup(excelRow - 1)
      mergeProcessCells(sheet, processStartRow, excelRow - 1)
    }
  }

  function downloadWorkbook (workbook: XLSX.WorkBook) {
    const exportBuffer = XLSX.write(workbook, {
      type: 'array',
      bookType: 'xlsx',
      cellStyles: true,
    })
    const blob = new Blob([exportBuffer], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `plan_risques_opportunites_${new Date().toISOString().slice(0, 10)}.xlsx`
    link.click()
    URL.revokeObjectURL(url)
  }

  async function handleExport () {
    try {
      const siteId = getCurrentSiteId()
      const response = await api.get('/risks-opportunities/export-xlsx', {
        params: siteId ? { site_id: siteId } : {},
        responseType: 'blob',
      })
      const blob = new Blob([response.data], {
        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      })
      const url = URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = `plan_risques_opportunites_${new Date().toISOString().slice(0, 10)}.xlsx`
      link.click()
      URL.revokeObjectURL(url)
      toast.success('Export Excel généré.')
    } catch (error) {
      console.error(error)
      toast.error('Impossible de générer l’export Excel.')
    }
  }

  function scoreColor (value: number) {
    return getScorePalette(value, viewMode.value).color
  }

  function scoreType (value: number): 'success' | 'error' | 'warning' | 'info' {
    if (viewMode.value === 'opportunite') {
      if (value >= 12) return 'info'
      if (value >= 8) return 'success'
      if (value >= 4) return 'info'
      return 'info'
    }

    if (value >= 12) return 'error'
    if (value >= 8) return 'warning'
    if (value >= 4) return 'info'
    return 'success'
  }

  function priorityLabel (value: number) {
    if (value >= 12) return 'Critique'
    if (value >= 8) return 'Élevée'
    if (value >= 4) return 'Moyenne'
    return 'Faible'
  }

  function statusColor (status: string) {
    if (status === 'cloture' || status === 'traite') return 'success'
    if (status === 'en_cours' || status === 'surveille') return 'warning'
    return 'info'
  }

  function goToDetail (id: number) {
    router.push(`/company/planning/risks-opportunities/${id}`)
  }

  async function fetchProcesses () {
    const siteId = getCurrentSiteId()
    if (!siteId) {
      processes.value = []
      return
    }

    const response = await processService.getProcesses({ site_id: siteId }, 1, 500)
    processes.value = response.data || []
  }

  async function fetchUsers () {
    const siteId = getCurrentSiteId()
    if (!siteId) {
      users.value = []
      return
    }

    const response = await api.get('/users', { params: { site_id: siteId, per_page: 300 } })
    const rows = response.data?.data || []
    users.value = rows.map((row: any) => ({
      id: Number(row.id),
      name: row.attributes?.name || row.name || row.attributes?.email || `Utilisateur #${row.id}`,
    }))
  }

  async function fetchItems () {
    const siteId = getCurrentSiteId()
    if (!siteId) {
      items.value = []
      rawItems.value = []
      return
    }

    loading.value = true
    try {
      const response = await api.get('/risks-opportunities', { params: { site_id: siteId } })
      const rows = response.data?.data || []
      rawItems.value = rows
      items.value = rows.map((item: any) => mapItem(item))
    } catch (error) {
      console.error(error)
      toast.error('Impossible de charger les risques et opportunités.')
      items.value = []
      rawItems.value = []
    } finally {
      loading.value = false
    }
  }

  async function loadPageData () {
    if (isPageLoading.value) return

    const siteId = getCurrentSiteId()
    if (!siteId) {
      loadedSiteId.value = null
      items.value = []
      processes.value = []
      users.value = []
      return
    }

    isPageLoading.value = true
    try {
      await Promise.all([fetchProcesses(), fetchUsers()])
      await fetchItems()
      loadedSiteId.value = siteId
    } finally {
      isPageLoading.value = false
    }
  }

  function resetForm () {
    form.process_id = null
    form.description = ''
    form.cause = ''
    form.probabilite = 1
    form.gravite = 1
    form.status = 'identifie'
    form.actions = []
  }

  function isBasicStepValid () {
    return Boolean(form.process_id && form.description.trim())
  }

  function goToActionsStep () {
    if (!isBasicStepValid()) {
      toast.error('Renseignez au moins le processus et la description avant de continuer.')
      dialogStep.value = 1
      return
    }
    dialogStep.value = 2
  }

  function closeDialog () {
    showDialog.value = false
    dialogStep.value = 1
  }

  function openCreateDialog () {
    editingItem.value = null
    resetForm()
    dialogStep.value = 1
    showDialog.value = true
  }

  async function openEditDialog (item: any) {
    editingItem.value = item
    form.process_id = item.process_id
    form.description = item.description
    form.cause = item.cause || ''
    form.probabilite = item.probabilite
    form.gravite = item.gravite
    form.status = item.status

    try {
      const response = await api.get(`/process-risks-opportunities/${item.id}`)
      const data = response.data?.data
      let plannedActions = data?.planned_actions || data?.attributes?.planned_actions

      if (typeof plannedActions === 'string') {
        plannedActions = JSON.parse(plannedActions)
      }

      form.actions = Array.isArray(plannedActions) && plannedActions.length > 0
        ? plannedActions.map((action: any) => ({
          action_type: item.type === 'risque'
            ? ((action.action_type as RiskActionType) || 'preventive')
            : null,
          description: action.description || action.title || action.action || '',
          responsible_user_id: action.responsible_user_id || null,
          implicated_user_ids: Array.isArray(action.implicated_user_ids) ? action.implicated_user_ids : [],
          deadline_frequency: action.deadline_frequency || '',
          showDatePicker: false,
        }))
        : []
    } catch (error) {
      console.error('Error loading actions:', error)
      form.actions = []
    }

    dialogStep.value = 1
    showDialog.value = true
  }

  async function saveItem () {
    if (!form.process_id || !form.description.trim()) {
      toast.error('Processus et description sont obligatoires.')
      return
    }

    saving.value = true
    try {
      const cleanActions = form.actions
        .map(action => {
          const description = (action.description || '').trim()
          if (!description) return null

          return {
            title: description.slice(0, 255),
            description,
            action: description,
            responsible_user_id: action.responsible_user_id,
            implicated_user_ids: action.implicated_user_ids,
            deadline_frequency: action.deadline_frequency,
            ...(viewMode.value === 'risque'
              ? { action_type: (action.action_type || 'preventive') as RiskActionType }
              : {}),
          }
        })
        .filter(Boolean)

      const payload = {
        type: viewMode.value,
        title: form.description.slice(0, 255),
        description: form.description,
        cause: viewMode.value === 'risque' ? (form.cause || null) : null,
        probabilite: Number(form.probabilite),
        gravite: Number(form.gravite),
        status: form.status,
        planned_actions: cleanActions,
      }

      await (editingItem.value?.id ? api.put(`/risks-opportunities/${editingItem.value.id}`, payload) : api.post(`/processes/${form.process_id}/risks-opportunities`, payload))

      closeDialog()
      await fetchItems()
      toast.success('Enregistrement effectué.')
    } catch (error) {
      console.error(error)
      toast.error('Erreur lors de l\'enregistrement.')
    } finally {
      saving.value = false
    }
  }

  async function removeItem (item: any) {
    if (!confirm('Supprimer cet élément ?')) return
    try {
      await api.delete(`/risks-opportunities/${item.id}`)
      await fetchItems()
      toast.success('Élément supprimé.')
    } catch (error) {
      console.error(error)
      toast.error('Erreur lors de la suppression.')
    }
  }

  watch(
    () => authStore.currentSiteId,
    async siteId => {
      if (!siteId) {
        await loadPageData()
        return
      }

      if (loadedSiteId.value === siteId) {
        return
      }

      await loadPageData()
    },
    { immediate: true },
  )
  watch(viewMode, mode => {
    if (mode === 'opportunite') form.cause = ''
  })
  watch(showDialog, value => {
    if (!value) dialogStep.value = 1
  })
</script>

<style scoped>
.hero-card {
  background: rgba(147, 197, 253, 0.35);
  backdrop-filter: blur(12px);
  color: rgb(var(--v-theme-on-surface));
  border: 1px solid rgba(59, 130, 246, 0.2);
  box-shadow: 0 4px 24px rgba(59, 130, 246, 0.12);
}

.hero-main {
  max-width: 720px;
}

.hero-kicker {
  opacity: 0.75;
  letter-spacing: 0.08em;
  color: rgb(var(--v-theme-on-surface));
  font-size: 0.7rem;
}

.hero-title {
  line-height: 1.15;
  color: rgb(var(--v-theme-on-surface));
}

.hero-subtitle {
  opacity: 0.8;
  max-width: 620px;
  color: rgb(var(--v-theme-on-surface));
}

.hero-action {
  font-weight: 700;
}

.hero-side {
  width: min(100%, 280px);
}

.hero-side-label {
  font-size: 0.75rem;
  opacity: 0.9;
  font-weight: 600;
}

.hero-score {
  background: rgba(255, 255, 255, 0.96);
  color: rgb(var(--v-theme-on-surface));
}

.filters-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
}

.filters-row {
  flex-wrap: nowrap;
  gap: 10px;
}

.filters-actions {
  gap: 8px;
}

:deep(.filters-card .v-field) {
  background: #ffffff;
}

:deep(.filters-card .v-field__outline) {
  opacity: 1;
}

:deep(.filters-card .v-field__input),
:deep(.filters-card .v-field__input::placeholder),
:deep(.filters-card .v-select__selection-text) {
  font-size: 0.8rem;
}

:deep(.filters-reset-btn) {
  min-height: 32px;
  width: 32px;
  height: 32px;
  font-size: 0.78rem;
  white-space: nowrap;
  border-color: #94a3b8;
  color: #1f2937;
  background: #ffffff;
}

:deep(.filters-reset-btn .v-btn__content) {
  gap: 6px;
}

:deep(.filters-reset-btn .v-icon) {
  font-size: 18px;
}

:deep(.filters-view-toggle) {
  border: 1px solid #e2e8f0;
  background: #ffffff;
}

:deep(.filters-view-toggle .v-btn) {
  min-width: 34px;
}

.action-type-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  max-height: 52px;
  overflow: hidden;
}

.action-preview-list {
  list-style: none;
  margin: 0;
  padding-left: 0;
  display: grid;
  gap: 4px;
  max-height: 88px;
  overflow: auto;
}

.action-preview-item {
  font-size: 0.78rem;
  color: rgba(15, 23, 42, 0.82);
  line-height: 1.25;
}

.risk-dialog-card {
  border: 1px solid rgba(148, 163, 184, 0.32);
  background:
    radial-gradient(circle at top right, rgba(56, 189, 248, 0.1), transparent 36%),
    radial-gradient(circle at bottom left, rgba(59, 130, 246, 0.08), transparent 40%),
    linear-gradient(165deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.95));
}

.ro-dialog-header {
  position: relative;
  padding: 20px 22px !important;
  padding-right: 56px !important;
}

.ro-title-wrap {
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.ro-title-icon {
  width: 34px;
  height: 34px;
  display: grid;
  place-items: center;
  border-radius: 10px;
  border: 1px solid rgba(148, 163, 184, 0.35);
  background: rgba(241, 245, 249, 0.8);
}

.ro-title-main {
  font-size: 1.02rem;
  font-weight: 700;
  color: #0f172a;
}

.ro-title-sub {
  margin-top: 2px;
  font-size: 0.78rem;
  color: #64748b;
}

.ro-close-btn {
  position: absolute;
  top: 14px;
  right: 14px;
  margin-top: 0;
  align-self: auto;
}

.ro-dialog-content {
  padding: 18px 22px !important;
}

.ro-stepper-shell {
  padding: 12px;
  border: 1px solid rgba(148, 163, 184, 0.3);
  border-radius: 14px;
  background: linear-gradient(180deg, rgba(248, 250, 252, 0.85), rgba(255, 255, 255, 0.88));
}

.ro-stepper-track {
  height: 8px;
  border-radius: 999px;
  background: rgba(148, 163, 184, 0.22);
  overflow: hidden;
  margin-bottom: 12px;
}

.ro-stepper-progress {
  height: 100%;
  border-radius: inherit;
  background: linear-gradient(90deg, #3b82f6, #2563eb);
  transition: width 0.25s ease;
}

.ro-stepper-items {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
}

.ro-step-card {
  display: flex;
  align-items: center;
  gap: 10px;
  text-align: left;
  border: 1px solid rgba(148, 163, 184, 0.38);
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.94);
  padding: 10px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.ro-step-card:hover {
  border-color: rgba(59, 130, 246, 0.52);
  transform: translateY(-1px);
}

.ro-step-card.active {
  border-color: rgba(59, 130, 246, 0.95);
  box-shadow: 0 10px 20px rgba(37, 99, 235, 0.14);
  background: linear-gradient(135deg, rgba(219, 234, 254, 0.7), rgba(239, 246, 255, 0.95));
}

.ro-step-card.done {
  border-color: rgba(16, 185, 129, 0.58);
}

.ro-step-badge {
  width: 28px;
  height: 28px;
  flex-shrink: 0;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: rgba(226, 232, 240, 0.95);
  color: #334155;
}

.ro-step-card.active .ro-step-badge {
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  color: #fff;
}

.ro-step-card.done .ro-step-badge {
  background: linear-gradient(135deg, #10b981, #059669);
  color: #fff;
}

.ro-step-content {
  display: grid;
  gap: 2px;
}

.ro-step-title {
  font-size: 0.84rem;
  font-weight: 700;
  color: #0f172a;
}

.ro-step-subtitle {
  font-size: 0.72rem;
  color: #64748b;
}

.ro-form-grid :deep(.v-field) {
  border-radius: 12px;
}

.ro-form-grid :deep(.v-field__overlay) {
  background: rgba(255, 255, 255, 0.85);
}

.ro-form-grid :deep(.v-field--variant-outlined .v-field__outline) {
  color: rgba(148, 163, 184, 0.65) !important;
}

.ro-section-heading {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 0.95rem;
  font-weight: 700;
  color: #0f172a;
}

.ro-info-banner {
  border: 1px solid rgba(125, 211, 252, 0.55);
}

.ro-score-alert {
  border: 1px solid rgba(148, 163, 184, 0.45);
  border-radius: 12px;
}

.ro-scale-alert {
  border-radius: 12px;
}

.ro-action-card {
  border-radius: 14px !important;
  border-color: rgba(148, 163, 184, 0.42) !important;
  background: rgba(255, 255, 255, 0.72);
}

.ro-action-card-title {
  border-bottom: 1px solid rgba(226, 232, 240, 0.9);
  min-height: 56px;
}

.ro-action-panels :deep(.v-expansion-panel) {
  border-radius: 12px;
  border: 1px solid rgba(226, 232, 240, 0.95);
  background: rgba(255, 255, 255, 0.9);
}

.ro-action-panels :deep(.v-expansion-panel + .v-expansion-panel) {
  margin-top: 8px;
}

.ro-opportunity-actions-header {
  border: 1px solid rgba(226, 232, 240, 0.95);
  border-radius: 12px;
  padding: 10px 12px;
  background: rgba(255, 255, 255, 0.88);
}

.ro-dialog-actions {
  padding: 14px 22px 18px !important;
  border-top: 1px solid rgba(226, 232, 240, 0.95);
  background: linear-gradient(180deg, rgba(248, 250, 252, 0.74), rgba(255, 255, 255, 0.94));
}

.clamp-one-line {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

@media (max-width: 960px) {
  .filters-row {
    flex-wrap: wrap;
  }

  .ro-stepper-items {
    grid-template-columns: 1fr;
  }
}
</style>
