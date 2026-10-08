<template>
  <ClientALayout current-page="iso-improvement">
    <v-container class="continuous-page pa-4 pa-md-6" fluid>
      <PageHeader
        icon="mdi-lightbulb-on-outline"
        title="Amélioration continue"
      >
        <template #subtitle>
          Les collaborateurs peuvent proposer des améliorations pour le système de l’entreprise.
          Les responsables voient ensuite les suggestions selon leur périmètre: personnelles, site ou entreprise.
        </template>
        <template #actions>
          <v-btn
            color="primary"
            prepend-icon="mdi-plus"
            rounded="lg"
            size="large"
            @click="openCreateDialog"
          >
            Nouvelle proposition
          </v-btn>
        </template>
      </PageHeader>

      <v-card class="hero-card mb-6" elevation="0" rounded="xl">
        <v-card-text class="pa-5 pa-md-7">
          <div class="hero-grid">
            <div>
              <div class="hero-kicker mb-2">Pilotage des idées terrain</div>
              <h2 class="text-h4 font-weight-black mb-3">Centralisez les propositions d’amélioration du système</h2>
              <p class="text-body-1 text-medium-emphasis mb-4">
                Chaque idée est reliée à un site, à un processus et à son auteur. L’interface adapte automatiquement
                la visibilité selon le rôle connecté.
              </p>

              <div class="scope-toolbar">
                <v-chip-group v-model="selectedScope" column mandatory selected-class="scope-chip--active">
                  <v-chip
                    v-for="scope in availableScopes"
                    :key="scope.value"
                    class="scope-chip"
                    filter
                    rounded="lg"
                    :value="scope.value"
                    variant="outlined"
                  >
                    <v-icon class="mr-2" size="16">{{ scope.icon }}</v-icon>
                    {{ scope.label }}
                  </v-chip>
                </v-chip-group>
              </div>
            </div>

            <div class="hero-side">
              <div class="hero-side-card">
                <span>Visibilité active</span>
                <strong>{{ selectedScopeLabel }}</strong>
              </div>
              <div class="hero-side-card">
                <span>Site courant</span>
                <strong>{{ currentSiteName }}</strong>
              </div>
              <div class="hero-side-card">
                <span>Votre rôle</span>
                <strong>{{ roleSummary }}</strong>
              </div>
            </div>
          </div>
        </v-card-text>
      </v-card>

      <div class="stats-grid mb-6">
        <AppWidget
          clickable
          :icon="Lightbulb"
          title="Propositions"
          :value="String(suggestions.length)"
          variant="primary"
          @click="filters.status = 'Tous les statuts'"
        />
        <AppWidget
          clickable
          :icon="Clock"
          title="À valider (RQ)"
          :value="String(pendingCount)"
          variant="warning"
          @click="filters.status = 'En attente validation RQ'"
        />
        <AppWidget
          clickable
          :icon="Wrench"
          title="Validées / En cours"
          :value="String(inProgressCount)"
          variant="info"
          @click="filters.status = 'Validée - En mise en œuvre'"
        />
        <AppWidget
          clickable
          :icon="CheckCircle"
          title="Adoptées"
          :value="String(adoptedCount)"
          variant="success"
          @click="filters.status = 'Adoptée'"
        />
      </div>

      <v-card class="registry-shell mb-6" elevation="0" rounded="xl">
        <v-card-text class="pa-4 pa-md-5">
          <div class="filters-inline">
            <v-text-field
              v-model="filters.search"
              class="filter-field"
              density="comfortable"
              hide-details
              placeholder="Rechercher une proposition, un auteur, un processus..."
              prepend-inner-icon="mdi-magnify"
              rounded="lg"
              variant="outlined"
            />
            <v-select
              v-model="filters.status"
              class="filter-field"
              density="comfortable"
              hide-details
              :items="statusFilterOptions"
              prepend-inner-icon="mdi-traffic-light"
              rounded="lg"
              variant="outlined"
            />
            <v-select
              v-model="filters.process"
              class="filter-field"
              density="comfortable"
              hide-details
              :items="processFilterOptions"
              prepend-inner-icon="mdi-cog-outline"
              rounded="lg"
              variant="outlined"
            />
            <v-select
              v-model="filters.impact"
              class="filter-field"
              density="comfortable"
              hide-details
              :items="impactFilterOptions"
              prepend-inner-icon="mdi-chart-line"
              rounded="lg"
              variant="outlined"
            />
            <v-select
              v-if="canManageAll"
              v-model="filters.siteId"
              class="filter-field"
              clearable
              density="comfortable"
              hide-details
              item-title="title"
              item-value="value"
              :items="siteFilterOptions"
              prepend-inner-icon="mdi-domain"
              rounded="lg"
              variant="outlined"
            />
            <div class="filter-actions">
              <v-btn
                :disabled="!hasActiveFilters"
                prepend-icon="mdi-filter-off"
                rounded="lg"
                variant="outlined"
                @click="resetFilters"
              >
                Réinitialiser
              </v-btn>
              <v-btn prepend-icon="mdi-refresh" rounded="lg" variant="text" @click="loadSuggestions">
                Actualiser
              </v-btn>
            </div>
          </div>
        </v-card-text>
      </v-card>

      <v-row>
        <v-col cols="12" lg="8">
          <v-card class="registry-shell" elevation="0" rounded="xl">
            <v-card-title class="d-flex align-center justify-space-between flex-wrap ga-3">
              <div>
                <div class="text-h6 font-weight-bold">Registre des propositions</div>
                <div class="text-body-2 text-medium-emphasis">
                  {{ registrySummary }}
                </div>
              </div>
              <v-chip color="primary" rounded="lg" variant="tonal">
                {{ filteredSuggestions.length }} résultat(s)
              </v-chip>
            </v-card-title>

            <div class="table-scroll-shell">
              <DataTable :headers="tableHeaders" :items="filteredSuggestions" :items-per-page="10">
                <template #item.reference="{ item }">
                  <v-btn class="link-cell-btn" size="small" variant="text" @click="selectSuggestion(item.id)">
                    {{ item.reference }}
                  </v-btn>
                </template>

                <template #item.title="{ item }">
                  <div class="font-weight-medium">{{ item.title }}</div>
                  <div class="text-caption text-medium-emphasis clamp-2">{{ item.description }}</div>
                  <div v-if="item.normes && item.normes.length > 0" class="d-flex flex-wrap ga-1 mt-1">
                    <v-chip
                      v-for="norm in item.normes"
                      :key="norm"
                      color="primary"
                      size="x-small"
                      variant="tonal"
                    >
                      <v-icon icon="mdi-certificate-outline" size="12" class="mr-1" />
                      {{ norm }}
                    </v-chip>
                  </div>
                </template>

                <template #item.site_name="{ item }">
                  <v-chip size="small" variant="outlined">{{ item.site_name || '—' }}</v-chip>
                </template>

                <template #item.status="{ item }">
                  <v-chip :color="statusColor(item.status)" size="small" variant="tonal">
                    {{ statusLabel(item.status) }}
                  </v-chip>
                </template>

                <template #item.impact="{ item }">
                  <v-chip :color="impactColor(item.impact)" size="small" variant="tonal">
                    {{ item.impact }}
                  </v-chip>
                </template>

                <template #item.actions="{ item }">
                  <div class="table-actions">
                    <v-btn icon="mdi-eye-outline" size="small" variant="text" @click="selectSuggestion(item.id)" />
                    <v-btn
                      v-if="canEditSuggestion(item)"
                      icon="mdi-pencil-outline"
                      size="small"
                      variant="text"
                      @click="openEditDialog(item.id)"
                    />
                    <v-btn
                      v-if="canDeleteSuggestion(item)"
                      color="error"
                      icon="mdi-delete-outline"
                      size="small"
                      variant="text"
                      @click="deleteSuggestion(item.id)"
                    />
                  </div>
                </template>
              </DataTable>
            </div>

            <v-alert
              v-if="filteredSuggestions.length === 0"
              class="mx-4 mb-4"
              density="comfortable"
              type="info"
              variant="tonal"
            >
              {{ emptyStateMessage }}
            </v-alert>
          </v-card>
        </v-col>

        <v-col cols="12" lg="4">
          <v-card v-if="selectedSuggestion" class="detail-card sticky-card" elevation="0" rounded="xl">
            <v-card-text class="pa-5">
              <div class="d-flex align-center justify-space-between mb-4">
                <div>
                  <div class="text-overline text-medium-emphasis">Détail de la proposition</div>
                  <div class="text-h6 font-weight-bold">{{ selectedSuggestion.reference }}</div>
                </div>
                <v-chip :color="statusColor(selectedSuggestion.status)" rounded="lg" variant="tonal">
                  {{ statusLabel(selectedSuggestion.status) }}
                </v-chip>
              </div>

              <div class="detail-stack">
                <div class="detail-block">
                  <div class="detail-label">Titre</div>
                  <div class="detail-value">{{ selectedSuggestion.title }}</div>
                </div>
                <div class="detail-block">
                  <div class="detail-label">Description</div>
                  <div class="detail-value">{{ selectedSuggestion.description }}</div>
                </div>

                <div v-if="selectedSuggestion.normes && selectedSuggestion.normes.length > 0" class="detail-block">
                  <div class="detail-label">Norme(s) concernée(s) (RT-11)</div>
                  <div class="d-flex flex-wrap ga-1 mt-1">
                    <v-chip
                      v-for="norme in selectedSuggestion.normes"
                      :key="norme"
                      color="primary"
                      size="small"
                      variant="tonal"
                    >
                      <v-icon icon="mdi-certificate-outline" size="14" class="mr-1" />
                      {{ norme }}
                    </v-chip>
                  </div>
                </div>

                <div class="detail-grid">
                  <div class="detail-mini">
                    <span>Site</span>
                    <strong>{{ selectedSuggestion.site_name || '—' }}</strong>
                  </div>
                  <div class="detail-mini">
                    <span>Processus</span>
                    <strong>{{ selectedSuggestion.process }}</strong>
                  </div>
                  <div class="detail-mini">
                    <span>Auteur</span>
                    <strong>{{ selectedSuggestion.proposer }}</strong>
                  </div>
                  <div class="detail-mini">
                    <span>Date</span>
                    <strong>{{ selectedSuggestion.created_at }}</strong>
                  </div>
                  <div class="detail-mini">
                    <span>Impact</span>
                    <strong>{{ selectedSuggestion.impact }}</strong>
                  </div>
                  <div v-if="selectedSuggestion.assignee_name" class="detail-mini">
                    <span>Assignée à</span>
                    <strong>{{ selectedSuggestion.assignee_name }}</strong>
                  </div>
                </div>

                <div v-if="selectedSuggestion.validation_comment" class="detail-block">
                  <div class="detail-label">Décision du Responsable Qualité</div>
                  <div class="detail-value">{{ selectedSuggestion.validation_comment }}</div>
                </div>

                <div class="detail-block">
                  <div class="detail-label">Suivi / commentaire</div>
                  <div class="detail-value">{{ selectedSuggestion.follow_up || 'Aucun commentaire de suivi pour le moment.' }}</div>
                </div>
              </div>

              <!-- Bloc circuit de validation RQ (REQ-10-01 / RT-10) -->
              <v-card
                v-if="canManageAll && selectedSuggestion.status === 'pending'"
                class="mt-4 pa-4 bg-amber-lighten-5 rounded-lg border-warning"
                variant="outlined"
              >
                <div class="text-subtitle-2 font-weight-bold text-amber-darken-4 mb-1">
                  <v-icon class="mr-1" color="amber-darken-4" icon="mdi-shield-check" size="18" />
                  Circuit de validation Qualité (RQ)
                </div>
                <div class="text-caption text-medium-emphasis mb-3">
                  En tant que Responsable Qualité, validez cette proposition pour la planifier ou refusez-la avec justification.
                </div>
                <div class="d-flex ga-2 flex-wrap">
                  <v-btn
                    color="success"
                    prepend-icon="mdi-check-bold"
                    rounded="lg"
                    size="small"
                    @click="openValidationDialog('approved')"
                  >
                    Valider & assigner
                  </v-btn>
                  <v-btn
                    color="error"
                    prepend-icon="mdi-close-thick"
                    rounded="lg"
                    size="small"
                    variant="outlined"
                    @click="openValidationDialog('rejected')"
                  >
                    Refuser
                  </v-btn>
                </div>
              </v-card>

              <div class="detail-actions mt-5">
                <v-btn
                  v-if="canEditSuggestion(selectedSuggestion)"
                  color="primary"
                  prepend-icon="mdi-pencil-outline"
                  rounded="lg"
                  variant="tonal"
                  @click="openEditDialog(selectedSuggestion.id)"
                >
                  Modifier
                </v-btn>
                <v-btn
                  v-if="canDeleteSuggestion(selectedSuggestion)"
                  color="error"
                  prepend-icon="mdi-delete-outline"
                  rounded="lg"
                  variant="text"
                  @click="deleteSuggestion(selectedSuggestion.id)"
                >
                  Supprimer
                </v-btn>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <v-dialog v-model="createDialog" max-width="860">
        <v-card class="modal-card" rounded="xl">
          <v-card-title class="modal-header">
            <div>
              <div class="modal-title">Nouvelle proposition d’amélioration</div>
              <div class="modal-subtitle">Chaque collaborateur peut contribuer à l’amélioration du système.</div>
            </div>
            <v-btn icon="mdi-close" variant="text" @click="closeCreateDialog" />
          </v-card-title>
          <v-divider />
          <v-card-text class="pa-5 pa-md-6">
            <v-stepper v-model="stepper" alt-labels>
              <v-stepper-header>
                <v-stepper-item :complete="stepper > 1" icon="mdi-lightbulb-outline" title="Idée" :value="1" />
                <v-stepper-item :complete="stepper > 2" icon="mdi-account-outline" title="Auteur" :value="2" />
                <v-stepper-item :complete="stepper > 3" icon="mdi-check-decagram-outline" title="Suivi" :value="3" />
                <v-stepper-item icon="mdi-file-check-outline" title="Récapitulatif" :value="4" />
              </v-stepper-header>

              <v-stepper-window>
                <v-stepper-window-item :value="1">
                  <v-row>
                    <v-col cols="12" md="6">
                      <v-select
                        v-model="draft.site_id"
                        :disabled="!canManageAll"
                        item-title="title"
                        item-value="value"
                        :items="siteFilterOptions"
                        label="Site concerné"
                        rounded="lg"
                        variant="outlined"
                      />
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-select
                        v-model="draft.process_id"
                        item-title="title"
                        item-value="value"
                        :items="processSelectItems"
                        label="Processus concerné"
                        :loading="loadingReferences"
                        rounded="lg"
                        variant="outlined"
                      />
                    </v-col>
                    <v-col cols="12">
                      <v-text-field v-model="draft.title" label="Titre de la proposition" rounded="lg" variant="outlined" />
                    </v-col>
                    <v-col cols="12">
                      <v-select
                        v-model="draft.normes"
                        chips
                        closable-chips
                        :items="availableNormsOptions"
                        label="Norme(s) concernée(s) (RT-11)"
                        hint="Sélectionnez les normes applicables à cette proposition (multi-normes)"
                        multiple
                        persistent-hint
                        rounded="lg"
                        variant="outlined"
                      />
                    </v-col>
                    <v-col cols="12">
                      <v-textarea
                        v-model="draft.description"
                        label="Description de l’amélioration proposée"
                        rounded="lg"
                        rows="5"
                        variant="outlined"
                      />
                    </v-col>
                  </v-row>
                </v-stepper-window-item>

                <v-stepper-window-item :value="2">
                  <v-row>
                    <v-col cols="12" md="6">
                      <v-select
                        v-model="draft.proposer_id"
                        :disabled="!canManageAll"
                        :hint="currentUserHint"
                        item-title="title"
                        item-value="value"
                        :items="collaboratorSelectItems"
                        label="Collaborateur auteur"
                        :loading="loadingReferences"
                        persistent-hint
                        rounded="lg"
                        variant="outlined"
                      />
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-text-field v-model="draft.date" label="Date (JJ/MM/AAAA)" rounded="lg" variant="outlined" />
                    </v-col>
                    <v-col cols="12">
                      <v-alert density="comfortable" type="info" variant="tonal">
                        Les collaborateurs simples soumettent leurs propres propositions. Les responsables peuvent enregistrer une
                        suggestion pour un autre collaborateur si nécessaire.
                      </v-alert>
                    </v-col>
                  </v-row>
                </v-stepper-window-item>

                <v-stepper-window-item :value="3">
                  <v-row>
                    <v-col cols="12" md="6">
                      <v-select
                        v-model="draft.impact"
                        :items="['Faible', 'Moyen', 'Élevé']"
                        label="Impact attendu"
                        rounded="lg"
                        variant="outlined"
                      />
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-select
                        v-model="draft.status"
                        :disabled="!canManageAll"
                        item-title="title"
                        item-value="value"
                        :items="statusSelectOptions"
                        label="Statut initial"
                        rounded="lg"
                        variant="outlined"
                      />
                    </v-col>
                    <v-col cols="12">
                      <v-textarea
                        v-model="draft.follow_up"
                        :disabled="!canManageAll"
                        label="Commentaire de suivi"
                        rounded="lg"
                        rows="3"
                        variant="outlined"
                      />
                    </v-col>
                  </v-row>
                </v-stepper-window-item>

                <v-stepper-window-item :value="4">
                  <v-card class="recap-card" rounded="lg" variant="outlined">
                    <v-card-text>
                      <div class="detail-block">
                        <div class="detail-label">Site</div>
                        <div class="detail-value">{{ selectedDraftSiteName }}</div>
                      </div>
                      <div class="detail-block">
                        <div class="detail-label">Processus</div>
                        <div class="detail-value">{{ selectedProcessTitle }}</div>
                      </div>
                      <div v-if="draft.normes && draft.normes.length > 0" class="detail-block">
                        <div class="detail-label">Norme(s) concernée(s)</div>
                        <div class="d-flex flex-wrap ga-1 mt-1">
                          <v-chip v-for="norm in draft.normes" :key="norm" color="primary" size="small" variant="tonal">
                            {{ norm }}
                          </v-chip>
                        </div>
                      </div>
                      <div class="detail-block">
                        <div class="detail-label">Titre</div>
                        <div class="detail-value">{{ draft.title || '—' }}</div>
                      </div>
                      <div class="detail-block">
                        <div class="detail-label">Description</div>
                        <div class="detail-value">{{ draft.description || '—' }}</div>
                      </div>
                      <div class="detail-grid">
                        <div class="detail-mini"><span>Auteur</span><strong>{{ selectedProposerName }}</strong></div>
                        <div class="detail-mini"><span>Date</span><strong>{{ draft.date || '—' }}</strong></div>
                        <div class="detail-mini"><span>Impact</span><strong>{{ draft.impact }}</strong></div>
                        <div class="detail-mini"><span>Statut</span><strong>{{ statusLabel(draft.status) }}</strong></div>
                      </div>
                    </v-card-text>
                  </v-card>
                </v-stepper-window-item>
              </v-stepper-window>
            </v-stepper>
          </v-card-text>
          <v-divider />
          <v-card-actions class="pa-4">
            <v-btn :disabled="stepper === 1" variant="text" @click="stepper--">Précédent</v-btn>
            <v-spacer />
            <v-btn variant="text" @click="closeCreateDialog">Annuler</v-btn>
            <v-btn v-if="stepper < 4" color="primary" rounded="lg" @click="stepper++">Suivant</v-btn>
            <v-btn v-else color="primary" rounded="lg" @click="createSuggestion">Soumettre</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <v-dialog v-model="editDialog" max-width="820">
        <v-card class="modal-card" rounded="xl">
          <v-card-title class="modal-header">
            <div>
              <div class="modal-title">Mettre à jour la proposition</div>
              <div class="modal-subtitle">Ajustez le contenu ou le suivi selon votre périmètre.</div>
            </div>
            <v-btn icon="mdi-close" variant="text" @click="closeEditDialog" />
          </v-card-title>
          <v-divider />
          <v-card-text class="pa-5">
            <v-row>
              <v-col cols="12" md="6">
                <v-select
                  v-model="editDraft.process_id"
                  item-title="title"
                  item-value="value"
                  :items="processSelectItems"
                  label="Processus concerné"
                  rounded="lg"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="editDraft.date" label="Date (JJ/MM/AAAA)" rounded="lg" variant="outlined" />
              </v-col>
              <v-col cols="12">
                <v-text-field v-model="editDraft.title" label="Titre" rounded="lg" variant="outlined" />
              </v-col>
              <v-col cols="12">
                <v-select
                  v-model="editDraft.normes"
                  chips
                  closable-chips
                  :items="availableNormsOptions"
                  label="Norme(s) concernée(s) (RT-11)"
                  multiple
                  rounded="lg"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="editDraft.description"
                  label="Description"
                  rounded="lg"
                  rows="4"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="editDraft.impact"
                  :items="['Faible', 'Moyen', 'Élevé']"
                  label="Impact"
                  rounded="lg"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="editDraft.status"
                  :disabled="!canManageAll"
                  item-title="title"
                  item-value="value"
                  :items="statusSelectOptions"
                  label="Statut"
                  rounded="lg"
                  variant="outlined"
                />
              </v-col>
              <v-col v-if="canManageAll" cols="12">
                <v-select
                  v-model="editDraft.assigned_to"
                  clearable
                  item-title="title"
                  item-value="value"
                  :items="collaboratorSelectItems"
                  label="Responsable assigné (mise en œuvre)"
                  rounded="lg"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="editDraft.follow_up"
                  :disabled="!canManageAll"
                  label="Commentaire de suivi"
                  rounded="lg"
                  rows="3"
                  variant="outlined"
                />
              </v-col>
            </v-row>
          </v-card-text>
          <v-divider />
          <v-card-actions class="pa-4">
            <v-spacer />
            <v-btn variant="text" @click="closeEditDialog">Annuler</v-btn>
            <v-btn color="primary" rounded="lg" @click="saveEditedSuggestion">Enregistrer</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <!-- Dialogue de validation Responsable Qualité (REQ-10-01) -->
      <v-dialog v-model="validationDialog" max-width="640">
        <v-card class="modal-card" rounded="xl">
          <v-card-title class="modal-header">
            <div>
              <div class="modal-title">
                {{ validationDecision === 'approved' ? 'Validation et assignation de la proposition' : 'Refus de la proposition' }}
              </div>
              <div class="modal-subtitle">
                {{ validationDecision === 'approved' ? 'Confirmez la recevabilité de la proposition et désignez un responsable de mise en œuvre.' : 'Indiquez le motif pour lequel cette proposition n’est pas retenue.' }}
              </div>
            </div>
            <v-btn icon="mdi-close" variant="text" @click="validationDialog = false" />
          </v-card-title>
          <v-divider />
          <v-card-text class="pa-5">
            <v-alert
              class="mb-4"
              density="comfortable"
              :type="validationDecision === 'approved' ? 'success' : 'warning'"
              variant="tonal"
            >
              Proposition : <strong>{{ selectedSuggestion?.reference }} — {{ selectedSuggestion?.title }}</strong>
            </v-alert>

            <v-row>
              <v-col v-if="validationDecision === 'approved'" cols="12">
                <v-select
                  v-model="validationForm.assigned_to"
                  item-title="title"
                  item-value="value"
                  :items="collaboratorSelectItems"
                  label="Responsable de mise en œuvre (Assignation)"
                  rounded="lg"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="validationForm.comment"
                  :label="validationDecision === 'approved' ? 'Consignes / Avis du Responsable Qualité' : 'Motif du refus'"
                  :placeholder="validationDecision === 'approved' ? 'Ex: Proposition approuvée. À traiter dans le cadre du plan d’amélioration continue.' : 'Ex: Déjà pris en compte dans une action existante.'"
                  rounded="lg"
                  rows="3"
                  variant="outlined"
                />
              </v-col>
            </v-row>
          </v-card-text>
          <v-divider />
          <v-card-actions class="pa-4">
            <v-spacer />
            <v-btn variant="text" @click="validationDialog = false">Annuler</v-btn>
            <v-btn
              :color="validationDecision === 'approved' ? 'success' : 'error'"
              rounded="lg"
              @click="submitValidation"
            >
              {{ validationDecision === 'approved' ? 'Confirmer la validation' : 'Confirmer le refus' }}
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { CheckCircle, Clock, Lightbulb, Wrench } from 'lucide-vue-next'
  import { computed, onMounted, ref, watch } from 'vue'
  import api from '@/api/client'
  import AppWidget from '@/components/common/AppWidget.vue'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import DataTable from '@/modules/clienta/components/DataTable.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import { useToast } from '@/modules/shared/composables/useToast'
  import { improvementSuggestionService } from '@/services/improvementSuggestionService'
  import processService from '@/services/processService'
  import { useAuthStore } from '@/stores/auth'
  import { getErrorMessage } from '@/utils/errorMessage'

  type SuggestionStatus = 'pending' | 'in_progress' | 'adopted' | 'rejected'
  type VisibilityScope = 'mine' | 'site' | 'enterprise'

  interface SuggestionItem {
    id: number
    reference: string
    process: string
    process_id: number | null
    site_name: string
    site_id: number | null
    title: string
    description: string
    proposer: string
    proposer_id: number | null
    assigned_to?: number | null
    assignee_name?: string
    normes?: string[]
    validation_comment?: string
    impact: 'Faible' | 'Moyen' | 'Élevé'
    status: SuggestionStatus
    created_at: string
    proposed_at_iso?: string
    follow_up?: string
  }

  interface SelectItem {
    title: string
    value: number
  }

  const authStore = useAuthStore()
  const toast = useToast()

  const currentUserId = computed(() => authStore.user?.id ?? null)
  const currentSiteId = computed<number | null>(() => {
    const direct = authStore.currentSiteId
    if (typeof direct === 'number' && Number.isFinite(direct)) return direct
    const stored = Number(localStorage.getItem('current_site_id'))
    return Number.isFinite(stored) && stored > 0 ? stored : null
  })

  const currentSiteName = computed(() =>
    authStore.currentSite?.name
    || authStore.availableSites.find(site => site.id === currentSiteId.value)?.name
    || 'Site non sélectionné',
  )

  function hasRole (roleName: string): boolean {
    const user: any = authStore.user || {}
    const roleNames = Array.isArray(user.role_names) ? user.role_names : []
    if (roleNames.includes(roleName)) {
      return true
    }

    const roles = Array.isArray(user.roles) ? user.roles : []
    return roles.some((role: any) =>
      role === roleName || role?.name === roleName || role?.attributes?.name === roleName,
    )
  }

  const isSuperAdmin = computed(() => authStore.user?.user_type === 'super_admin')
  const canManageAll = computed(() => isSuperAdmin.value || hasRole('admin_entreprise') || hasRole('site_manager'))
  const roleSummary = computed(() => {
    if (isSuperAdmin.value) return 'Super administrateur'
    if (hasRole('admin_entreprise')) return 'Admin entreprise'
    if (hasRole('site_manager')) return 'Responsable de site'
    return 'Collaborateur'
  })

  const availableScopes = computed(() => {
    const scopes = [
      { label: 'Mes propositions', value: 'mine' as VisibilityScope, icon: 'mdi-account-outline' },
    ]

    if (canManageAll.value) {
      scopes.push({ label: 'Site sélectionné', value: 'site' as VisibilityScope, icon: 'mdi-domain' })
    }

    if (isSuperAdmin.value || hasRole('admin_entreprise')) {
      scopes.push({ label: 'Tous les sites', value: 'enterprise' as VisibilityScope, icon: 'mdi-office-building-outline' })
    }

    return scopes
  })

  const selectedScope = ref<VisibilityScope>('mine')
  const selectedScopeLabel = computed(() =>
    availableScopes.value.find(scope => scope.value === selectedScope.value)?.label || 'Mes propositions',
  )

  const processSelectItems = ref<SelectItem[]>([])
  const collaboratorSelectItems = ref<SelectItem[]>([])
  const processOptions = ref<string[]>([])
  const loadingReferences = ref(false)
  const suggestions = ref<SuggestionItem[]>([])
  const selectedId = ref<number | null>(null)
  const createDialog = ref(false)
  const editDialog = ref(false)
  const stepper = ref(1)

  const availableNormsOptions = [
    'ISO 9001:2015',
    'ISO 14001:2015',
    'ISO 45001:2018',
    'ISO 27001',
    'ISO 50001',
  ]

  const validationDialog = ref(false)
  const validationDecision = ref<'approved' | 'rejected'>('approved')
  const validationForm = ref({
    assigned_to: null as number | null,
    comment: '',
  })

  const filters = ref({
    search: '',
    status: 'Tous les statuts',
    process: 'Tous les processus',
    impact: 'Tous les impacts',
    siteId: null as number | null,
  })

  const draft = ref({
    site_id: currentSiteId.value as number | null,
    process_id: null as number | null,
    normes: ['ISO 9001:2015'] as string[],
    title: '',
    description: '',
    proposer_id: currentUserId.value as number | null,
    date: formatToday(),
    impact: 'Moyen' as 'Faible' | 'Moyen' | 'Élevé',
    status: 'pending' as SuggestionStatus,
    follow_up: '',
  })

  const editDraft = ref({
    id: null as number | null,
    reference: '',
    process_id: null as number | null,
    normes: [] as string[],
    proposer_id: null as number | null,
    assigned_to: null as number | null,
    title: '',
    description: '',
    date: formatToday(),
    impact: 'Moyen' as 'Faible' | 'Moyen' | 'Élevé',
    status: 'pending' as SuggestionStatus,
    follow_up: '',
  })

  const statusFilterOptions = ['Tous les statuts', 'En attente validation RQ', 'Validée - En mise en œuvre', 'Adoptée', 'Non retenue']
  const impactFilterOptions = ['Tous les impacts', 'Faible', 'Moyen', 'Élevé']
  const statusSelectOptions = [
    { title: 'En attente validation RQ', value: 'pending' },
    { title: 'Validée - En mise en œuvre', value: 'in_progress' },
    { title: 'Adoptée', value: 'adopted' },
    { title: 'Non retenue', value: 'rejected' },
  ]

  const siteFilterOptions = computed(() =>
    authStore.availableSites.map((site: any) => ({ title: site.name, value: Number(site.id) })),
  )

  const tableHeaders = computed(() => {
    const headers = [
      { title: 'Référence', key: 'reference', sortable: true },
      { title: 'Titre', key: 'title', sortable: true },
    ]

    if (selectedScope.value === 'enterprise') {
      headers.push({ title: 'Site', key: 'site_name', sortable: true })
    }

    headers.push(
      { title: 'Processus', key: 'process', sortable: true },
      { title: 'Proposé par', key: 'proposer', sortable: true },
      { title: 'Impact', key: 'impact', sortable: true },
      { title: 'Statut', key: 'status', sortable: true },
      { title: '', key: 'actions', sortable: false },
    )

    return headers
  })

  const filteredSuggestions = computed(() => suggestions.value.filter(item => {
    const q = filters.value.search.trim().toLowerCase()
    const matchSearch = q.length === 0
      || item.reference.toLowerCase().includes(q)
      || item.title.toLowerCase().includes(q)
      || item.description.toLowerCase().includes(q)
      || item.proposer.toLowerCase().includes(q)
      || item.process.toLowerCase().includes(q)
      || item.site_name.toLowerCase().includes(q)

    const matchStatus = filters.value.status === 'Tous les statuts' || statusLabel(item.status) === filters.value.status
    const matchProcess = filters.value.process === 'Tous les processus' || item.process === filters.value.process
    const matchImpact = filters.value.impact === 'Tous les impacts' || item.impact === filters.value.impact
    const matchSite = !filters.value.siteId || item.site_id === filters.value.siteId

    return matchSearch && matchStatus && matchProcess && matchImpact && matchSite
  }))

  const hasActiveFilters = computed(() => Boolean(
    filters.value.search.trim()
      || filters.value.status !== 'Tous les statuts'
    || filters.value.process !== 'Tous les processus'
      || filters.value.impact !== 'Tous les impacts'
    || filters.value.siteId,
  ))

  const selectedSuggestion = computed(() => {
    const pool = filteredSuggestions.value.length > 0 ? filteredSuggestions.value : suggestions.value
    return pool.find(item => item.id === selectedId.value) || pool[0] || null
  })

  const pendingCount = computed(() => suggestions.value.filter(item => item.status === 'pending').length)
  const inProgressCount = computed(() => suggestions.value.filter(item => item.status === 'in_progress').length)
  const adoptedCount = computed(() => suggestions.value.filter(item => item.status === 'adopted').length)
  const processFilterOptions = computed(() => ['Tous les processus', ...processOptions.value])

  const emptyStateMessage = computed(() => {
    if (suggestions.value.length === 0) {
      return selectedScope.value === 'mine'
        ? 'Vous n’avez encore soumis aucune proposition d’amélioration.'
        : 'Aucune proposition d’amélioration disponible pour ce périmètre.'
    }
    return 'Aucune proposition ne correspond aux filtres sélectionnés.'
  })

  const registrySummary = computed(() => {
    if (selectedScope.value === 'mine') {
      return 'Vous visualisez uniquement vos propositions personnelles.'
    }
    if (selectedScope.value === 'site') {
      return `Vue site: toutes les propositions liées au site ${currentSiteName.value}.`
    }
    return 'Vue entreprise: lecture transverse des propositions remontées sur les sites disponibles.'
  })

  const currentUserHint = computed(() => {
    const currentUser = authStore.user as any
    const label = currentUser?.name || currentUser?.username || currentUser?.email || ''
    return label
      ? `Par défaut: ${label}`
      : 'Sélectionnez le collaborateur qui propose la suggestion.'
  })

  const selectedProcessTitle = computed(() =>
    processSelectItems.value.find(item => item.value === draft.value.process_id)?.title || '—',
  )
  const selectedProposerName = computed(() =>
    collaboratorSelectItems.value.find(item => item.value === draft.value.proposer_id)?.title || '—',
  )
  const selectedDraftSiteName = computed(() =>
    siteFilterOptions.value.find(item => item.value === draft.value.site_id)?.title || currentSiteName.value,
  )

  function canEditSuggestion (item: SuggestionItem | null): boolean {
    if (!item) return false
    return canManageAll.value || item.proposer_id === currentUserId.value
  }

  function canDeleteSuggestion (item: SuggestionItem | null): boolean {
    return canEditSuggestion(item)
  }

  async function loadSuggestions (): Promise<void> {
    try {
      const params: Record<string, any> = {
        scope: selectedScope.value,
        per_page: 200,
      }

      if (selectedScope.value === 'site' && currentSiteId.value) {
        params.site_id = currentSiteId.value
      }

      if (selectedScope.value === 'enterprise' && filters.value.siteId) {
        params.site_id = filters.value.siteId
      }

      const rows = await improvementSuggestionService.list(params)
      suggestions.value = rows.map((row: any) => ({
        id: Number(row.id),
        reference: row.ref || `SUG-${row.id}`,
        process: row.process?.title || row.process?.name || 'Processus non défini',
        process_id: row.process_id ? Number(row.process_id) : null,
        site_name: row.site?.name || 'Site non défini',
        site_id: row.site_id ? Number(row.site_id) : null,
        title: row.title || '',
        description: row.description || '',
        normes: Array.isArray(row.normes) ? row.normes : [],
        proposer: row.proposer?.name || row.proposer?.email || 'Collaborateur non défini',
        proposer_id: row.proposer_id ? Number(row.proposer_id) : null,
        assigned_to: row.assigned_to ? Number(row.assigned_to) : null,
        assignee_name: row.assignee?.name || row.assignee?.email || '',
        validation_comment: row.validation_comment || '',
        impact: mapImpactToLabel(row.impact),
        status: row.status,
        created_at: row.proposed_at ? formatIsoDate(row.proposed_at) : formatIsoDate(row.created_at),
        proposed_at_iso: row.proposed_at || row.created_at,
        follow_up: row.follow_up || '',
      }))

      selectedId.value = suggestions.value[0]?.id ?? null
    } catch (error) {
      console.error('[ContinuousImprovement] load suggestions failed', error)
      toast.error(getErrorMessage(error, 'Impossible de charger les propositions d’amélioration.'))
      suggestions.value = []
      selectedId.value = null
    }
  }

  function openValidationDialog (decision: 'approved' | 'rejected'): void {
    validationDecision.value = decision
    validationForm.value = {
      assigned_to: selectedSuggestion.value?.assigned_to || collaboratorSelectItems.value[0]?.value || null,
      comment: '',
    }
    validationDialog.value = true
  }

  async function submitValidation (): Promise<void> {
    if (!selectedSuggestion.value) return
    try {
      await improvementSuggestionService.validate(selectedSuggestion.value.id, {
        decision: validationDecision.value,
        assigned_to: validationDecision.value === 'approved' ? validationForm.value.assigned_to : undefined,
        validation_comment: validationForm.value.comment || undefined,
      })
      toast.success(
        validationDecision.value === 'approved'
          ? 'Proposition validée par le RQ et planifiée pour mise en œuvre.'
          : 'Proposition refusée avec motif.',
      )
      validationDialog.value = false
      await loadSuggestions()
    } catch (error) {
      console.error('[ContinuousImprovement] validation failed', error)
      toast.error(getErrorMessage(error, 'Erreur lors de la validation de la proposition.'))
    }
  }

  async function loadDynamicReferences (siteId: number | null): Promise<void> {
    if (!siteId) {
      processSelectItems.value = []
      collaboratorSelectItems.value = []
      processOptions.value = []
      return
    }

    loadingReferences.value = true
    try {
      await Promise.all([
        loadProcesses(siteId),
        loadCollaborators(siteId),
      ])
    } catch (error) {
      console.error('[ContinuousImprovement] load references failed', error)
      toast.error(getErrorMessage(error, 'Impossible de charger les références du site.'))
    } finally {
      loadingReferences.value = false
    }
  }

  async function loadProcesses (siteId: number): Promise<void> {
    const response = await processService.getProcesses({ site_id: siteId }, 1, 200)
    const rows = Array.isArray(response?.data) ? response.data : []
    const mapped = rows
      .map((row: any) => ({ value: Number(row?.id || 0), title: String(row?.title || row?.name || row?.code || '') }))
      .filter((item: SelectItem) => item.value > 0 && item.title.length > 0)

    processSelectItems.value = mapped
    processOptions.value = mapped.map(item => item.title)

    if (!draft.value.process_id && mapped.length > 0) {
      draft.value.process_id = mapped[0].value
    }
  }

  async function loadCollaborators (siteId: number): Promise<void> {
    let rows: any[] = []
    try {
      const { data } = await api.get('/users/job-description-collaborators', { params: { site_id: siteId, per_page: 300 } })
      rows = extractRows(data)
    } catch {
      const { data } = await api.get('/users', { params: { site_id: siteId, per_page: 300 } })
      rows = extractRows(data)
    }

    collaboratorSelectItems.value = rows.map(normalizeUserRow).filter((item: SelectItem) => item.value > 0)

    if (!canManageAll.value) {
      draft.value.proposer_id = currentUserId.value
      return
    }

    if (currentUserId.value && collaboratorSelectItems.value.some(item => item.value === currentUserId.value)) {
      draft.value.proposer_id = currentUserId.value
    } else if (!draft.value.proposer_id && collaboratorSelectItems.value.length > 0) {
      draft.value.proposer_id = collaboratorSelectItems.value[0].value
    }
  }

  function extractRows (payload: any): any[] {
    if (Array.isArray(payload?.data)) return payload.data
    if (Array.isArray(payload)) return payload
    return []
  }

  function normalizeUserRow (raw: any): SelectItem {
    const attrs = raw?.attributes || raw || {}
    const id = Number(raw?.id || attrs?.id || 0)
    const fullName = String(
      attrs?.name
        || `${attrs?.first_name || ''} ${attrs?.last_name || ''}`.trim()
      || attrs?.email
        || `Collaborateur ${id}`,
    )

    return { value: id, title: fullName }
  }

  function selectSuggestion (id: number): void {
    selectedId.value = id
  }

  function openCreateDialog (): void {
    stepper.value = 1
    draft.value.site_id = currentSiteId.value
    createDialog.value = true
  }

  function closeCreateDialog (): void {
    createDialog.value = false
    stepper.value = 1
    resetDraft()
  }

  function openEditDialog (id: number): void {
    const item = suggestions.value.find(s => s.id === id)
    if (!item) return

    editDraft.value = {
      id: item.id,
      reference: item.reference,
      process_id: item.process_id,
      proposer_id: item.proposer_id,
      assigned_to: item.assigned_to ?? null,
      normes: Array.isArray(item.normes) ? [...item.normes] : [],
      title: item.title,
      description: item.description,
      date: item.created_at || formatToday(),
      impact: item.impact,
      status: item.status,
      follow_up: item.follow_up || '',
    }
    editDialog.value = true
  }

  function closeEditDialog (): void {
    editDialog.value = false
    editDraft.value = {
      id: null,
      reference: '',
      process_id: null,
      proposer_id: null,
      assigned_to: null,
      normes: [],
      title: '',
      description: '',
      date: formatToday(),
      impact: 'Moyen',
      status: 'pending',
      follow_up: '',
    }
  }

  function resetFilters (): void {
    filters.value = {
      search: '',
      status: 'Tous les statuts',
      process: 'Tous les processus',
      impact: 'Tous les impacts',
      siteId: null,
    }
  }

  async function createSuggestion (): Promise<void> {
    if (!draft.value.site_id || !draft.value.title || !draft.value.description) {
      toast.error('Le site, le titre et la description sont requis.')
      return
    }

    try {
      const payload = {
        site_id: draft.value.site_id,
        process_id: draft.value.process_id || null,
        proposer_id: canManageAll.value ? (draft.value.proposer_id || null) : currentUserId.value,
        title: draft.value.title,
        description: draft.value.description,
        normes: draft.value.normes && draft.value.normes.length > 0 ? draft.value.normes : ['ISO 9001:2015'],
        impact: mapImpactToApi(draft.value.impact),
        status: canManageAll.value ? draft.value.status : 'pending',
        follow_up: canManageAll.value ? (draft.value.follow_up || null) : null,
        proposed_at: parseFrDateToIso(draft.value.date),
      }

      const created = await improvementSuggestionService.create(payload as any)
      closeCreateDialog()
      await loadSuggestions()
      if (created?.id) {
        selectedId.value = Number(created.id)
      }
      toast.success('Proposition enregistrée et soumise au Responsable Qualité.')
    } catch (error) {
      console.error('[ContinuousImprovement] create suggestion failed', error)
      toast.error(getErrorMessage(error, 'Impossible d’enregistrer la proposition.'))
    }
  }

  async function saveEditedSuggestion (): Promise<void> {
    if (!editDraft.value.id || !editDraft.value.title || !editDraft.value.description) {
      toast.error('Titre et description sont requis.')
      return
    }

    try {
      await improvementSuggestionService.update(editDraft.value.id, {
        process_id: editDraft.value.process_id || null,
        proposer_id: canManageAll.value ? (editDraft.value.proposer_id || null) : undefined,
        assigned_to: canManageAll.value ? (editDraft.value.assigned_to || null) : undefined,
        normes: editDraft.value.normes || [],
        title: editDraft.value.title,
        description: editDraft.value.description,
        impact: mapImpactToApi(editDraft.value.impact),
        status: canManageAll.value ? editDraft.value.status : undefined,
        follow_up: canManageAll.value ? (editDraft.value.follow_up || null) : undefined,
        proposed_at: parseFrDateToIso(editDraft.value.date),
      } as any)

      const id = editDraft.value.id
      closeEditDialog()
      await loadSuggestions()
      if (id) {
        selectedId.value = id
      }
      toast.success('Proposition mise à jour.')
    } catch (error) {
      console.error('[ContinuousImprovement] update suggestion failed', error)
      toast.error(getErrorMessage(error, 'Impossible de mettre à jour la proposition.'))
    }
  }

  async function deleteSuggestion (id: number): Promise<void> {
    const item = suggestions.value.find(s => s.id === id)
    if (!item) return
    if (!confirm(`Supprimer la proposition ${item.reference} ?`)) return

    try {
      await improvementSuggestionService.remove(id)
      if (selectedId.value === id) {
        selectedId.value = null
      }
      await loadSuggestions()
      toast.success('Proposition supprimée.')
    } catch (error) {
      console.error('[ContinuousImprovement] delete suggestion failed', error)
      toast.error(getErrorMessage(error, 'Impossible de supprimer la proposition.'))
    }
  }

  function resetDraft (): void {
    draft.value = {
      site_id: currentSiteId.value,
      process_id: processSelectItems.value[0]?.value || null,
      normes: ['ISO 9001:2015'],
      title: '',
      description: '',
      proposer_id: currentUserId.value || collaboratorSelectItems.value[0]?.value || null,
      date: formatToday(),
      impact: 'Moyen',
      status: 'pending',
      follow_up: '',
    }
  }

  function statusLabel (status: SuggestionStatus): string {
    if (status === 'in_progress') return 'Validée - En mise en œuvre'
    if (status === 'adopted') return 'Adoptée'
    if (status === 'rejected') return 'Non retenue'
    return 'En attente validation RQ'
  }

  function statusColor (status: SuggestionStatus): string {
    if (status === 'in_progress') return 'info'
    if (status === 'adopted') return 'success'
    if (status === 'rejected') return 'error'
    return 'warning'
  }

  function impactColor (impact: 'Faible' | 'Moyen' | 'Élevé'): string {
    if (impact === 'Élevé') return 'error'
    if (impact === 'Moyen') return 'warning'
    return 'success'
  }

  function formatToday (): string {
    const d = new Date()
    const day = String(d.getDate()).padStart(2, '0')
    const month = String(d.getMonth() + 1).padStart(2, '0')
    const year = d.getFullYear()
    return `${day}/${month}/${year}`
  }

  function parseFrDateToIso (value: string): string {
    const match = value.match(/^(\d{2})\/(\d{2})\/(\d{4})$/)
    if (!match) return new Date().toISOString().slice(0, 10)
    const [, dd, mm, yyyy] = match
    return `${yyyy}-${mm}-${dd}`
  }

  function formatIsoDate (value?: string): string {
    if (!value) return formatToday()
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return formatToday()
    const day = String(date.getDate()).padStart(2, '0')
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const year = date.getFullYear()
    return `${day}/${month}/${year}`
  }

  function mapImpactToApi (impact: 'Faible' | 'Moyen' | 'Élevé'): 'low' | 'medium' | 'high' {
    if (impact === 'Faible') return 'low'
    if (impact === 'Élevé') return 'high'
    return 'medium'
  }

  function mapImpactToLabel (impact: 'low' | 'medium' | 'high' | string): 'Faible' | 'Moyen' | 'Élevé' {
    if (impact === 'low') return 'Faible'
    if (impact === 'high') return 'Élevé'
    return 'Moyen'
  }

  watch(availableScopes, scopes => {
    if (!scopes.some(scope => scope.value === selectedScope.value)) {
      selectedScope.value = scopes[0]?.value || 'mine'
    }
  }, { immediate: true })

  watch(() => authStore.currentSiteId, async () => {
    draft.value.site_id = currentSiteId.value
    await loadDynamicReferences(draft.value.site_id)
    await loadSuggestions()
  })

  watch(selectedScope, async () => {
    if (selectedScope.value !== 'enterprise') {
      filters.value.siteId = null
    }
    await loadSuggestions()
  })

  watch(() => draft.value.site_id, async siteId => {
    await loadDynamicReferences(siteId)
  })

  onMounted(async () => {
    resetDraft()
    await loadDynamicReferences(draft.value.site_id)
    await loadSuggestions()
  })
</script>

<style scoped>
  .continuous-page {
    --card-border: rgba(15, 23, 42, 0.08);
  }

  .hero-card,
  .registry-shell,
  .detail-card,
  .modal-card {
    border: 1px solid var(--card-border);
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.95));
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.05);
  }

  .hero-card {
    background:
      radial-gradient(1200px 420px at 0% -10%, rgba(10, 132, 255, 0.16), transparent 62%),
      radial-gradient(900px 320px at 100% 0%, rgba(16, 185, 129, 0.16), transparent 60%),
      linear-gradient(135deg, rgba(17, 24, 39, 0.02), rgba(255, 255, 255, 0.95));
  }

  .hero-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.5fr) minmax(280px, 0.8fr);
    gap: 24px;
    align-items: start;
  }

  .hero-kicker {
    color: rgb(8, 94, 172);
    font-size: 0.76rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
  }

  .scope-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
  }

  .scope-chip {
    border-width: 1px;
  }

  .hero-side {
    display: grid;
    gap: 12px;
  }

  .hero-side-card {
    padding: 16px 18px;
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.82);
    border: 1px solid rgba(255, 255, 255, 0.7);
    display: flex;
    justify-content: space-between;
    gap: 12px;
  }

  .hero-side-card span,
  .detail-label,
  .detail-mini span {
    color: rgba(15, 23, 42, 0.65);
  }

  .hero-side-card strong,
  .detail-value,
  .detail-mini strong {
    color: rgb(15, 23, 42);
    font-weight: 800;
  }

  .stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
  }

  .filters-inline {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 12px;
    align-items: center;
  }

  .filter-field {
    min-width: 0;
  }

  .filter-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    flex-wrap: wrap;
  }

  .table-scroll-shell {
    overflow-x: auto;
  }

  .table-actions {
    display: flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
  }

  .clamp-2 {
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
    overflow: hidden;
  }

  .sticky-card {
    position: sticky;
    top: 20px;
  }

  .detail-stack {
    display: grid;
    gap: 16px;
  }

  .detail-block {
    display: grid;
    gap: 6px;
  }

  .detail-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
  }

  .detail-mini {
    padding: 12px;
    border-radius: 16px;
    background: rgba(15, 23, 42, 0.03);
    display: grid;
    gap: 4px;
  }

  .detail-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    flex-wrap: wrap;
  }

  .modal-header {
    padding: 20px 24px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
  }

  .modal-title {
    font-size: 1.15rem;
    font-weight: 800;
    color: rgb(15, 23, 42);
  }

  .modal-subtitle {
    color: rgba(15, 23, 42, 0.65);
    font-size: 0.92rem;
  }

  .recap-card {
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  }

  .link-cell-btn {
    color: rgb(var(--v-theme-primary));
    font-weight: 700;
    text-decoration: underline;
  }

  @media (max-width: 1264px) {
    .hero-grid,
    .stats-grid {
      grid-template-columns: 1fr;
    }

    .filters-inline {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }

  @media (max-width: 960px) {
    .filters-inline,
    .detail-grid {
      grid-template-columns: 1fr;
    }

    .filter-actions {
      justify-content: flex-start;
    }
  }
</style>
