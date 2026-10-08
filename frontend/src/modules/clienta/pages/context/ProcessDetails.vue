<template>
  <ClientALayout current-page="management-system">
    <PageHeader
      icon="mdi-cog"
      subtitle="Configuration complète du processus"
      :title="processForm.name || 'Nouveau processus'"
    >
      <template #actions>
        <v-btn
          :loading="exporting"
          prepend-icon="mdi-file-plus"
          size="large"
          variant="tonal"
          @click="openGenerationDialog('draft')"
        >
          Générer brouillon
        </v-btn>
        <v-btn
          prepend-icon="mdi-map-marker-path"
          size="large"
          variant="outlined"
          @click="goToCartography"
        >
          Voir dans la cartographie
        </v-btn>
        <v-btn
          prepend-icon="mdi-vector-polyline"
          size="large"
          variant="outlined"
          @click="openCartographyModal"
        >
          Interactions
        </v-btn>
        <v-btn
          :disabled="exporting"
          :loading="exporting && detailsPreviewFileType === 'pdf'"
          prepend-icon="mdi-eye"
          size="large"
          variant="outlined"
          @click="handleExportProcessDetails('pdf')"
        >
          Prévisualiser
        </v-btn>
        <v-menu>
          <template #activator="{ props: menuProps }">
            <v-btn
              v-bind="menuProps"
              :disabled="exporting"
              :loading="exporting"
              prepend-icon="mdi-download"
              size="large"
              variant="outlined"
            >
              Télécharger fiche
              <v-icon end>mdi-chevron-down</v-icon>
            </v-btn>
          </template>
          <v-list density="compact">
            <v-list-item
              prepend-icon="mdi-file-pdf-box"
              title="Fiche Processus (PDF)"
              @click="handleExportProcessDetails('pdf')"
            />
            <v-list-item
              prepend-icon="mdi-file-word"
              title="Fiche Processus (Word .docx)"
              @click="handleExportProcessDetails('docx')"
            />
          </v-list>
        </v-menu>
        <v-btn
          color="warning"
          :disabled="submittingForVerification"
          :loading="submittingForVerification"
          prepend-icon="mdi-shield-check"
          size="large"
          variant="outlined"
          @click="openVerifyDialog"
        >
          Vérifier document
        </v-btn>
        <v-btn prepend-icon="mdi-arrow-left" size="large" variant="outlined" @click="handleBack">
          Retour
        </v-btn>
        <v-btn
          color="primary"
          :loading="saving"
          prepend-icon="mdi-content-save"
          size="large"
          @click="handleSave"
        >
          Enregistrer
        </v-btn>
      </template>
    </PageHeader>

    <!-- Stepper cliquable -->
    <ProcessStepper v-model="currentStep" :steps="steps" />

    <!-- Contenu des étapes -->
    <v-window v-model="currentStep" class="mb-6">
      <!-- Étape 1: Informations générales -->
      <v-window-item :value="1">
        <v-card elevation="0" rounded="xl" :style="`background: ${steps[0]!.gradient}`">
          <v-card-text class="pa-8">
            <div class="d-flex align-center mb-6">
              <v-avatar class="mr-4 elevation-4" :color="steps[0]!.color" size="64">
                <v-icon color="white" size="32">mdi-information</v-icon>
              </v-avatar>
              <div>
                <h2 class="text-h4 font-weight-bold">Informations générales</h2>
                <p class="text-body-1 text-medium-emphasis mb-0">Identité et finalité du processus</p>
              </div>
            </div>

            <v-row>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="processForm.name"
                  bg-color="white"
                  density="comfortable"
                  label="Nom du processus *"
                  placeholder="Ex: Gestion des commandes"
                  prepend-inner-icon="mdi-tag"
                  rounded="lg"
                  variant="solo"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="processForm.category"
                  bg-color="white"
                  density="comfortable"
                  item-title="title"
                  item-value="value"
                  :items="categories"
                  label="Catégorie *"
                  prepend-inner-icon="mdi-folder"
                  rounded="lg"
                  variant="solo"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-autocomplete
                  bg-color="white"
                  clearable
                  density="comfortable"
                  :disabled="usersLoading || users.length === 0"
                  item-title="name"
                  item-value="id"
                  :items="users"
                  label="Pilote du processus"
                  :loading="usersLoading"
                  :model-value="selectedPilotId"
                  prepend-inner-icon="mdi-account-star"
                  :return-object="false"
                  rounded="lg"
                  variant="solo"
                  @update:model-value="value => (selectedPilotId = normalizePilotId(value))"
                />
                <div v-if="!usersLoading && users.length === 0" class="text-caption text-medium-emphasis mt-2">
                  Aucun collaborateur disponible pour ce site.
                </div>
              </v-col>
              <v-col cols="12" md="6">
                <v-autocomplete
                  v-model="selectedCopilotIds"
                  bg-color="white"
                  chips
                  clearable
                  closable-chips
                  density="comfortable"
                  :disabled="usersLoading || users.length === 0"
                  item-title="name"
                  item-value="id"
                  :items="users"
                  label="Copilote(s) du processus"
                  :loading="usersLoading"
                  multiple
                  prepend-inner-icon="mdi-account-multiple-check"
                  :return-object="false"
                  rounded="lg"
                  variant="solo"
                />
                <div
                  v-if="!usersLoading && selectedCopilotNames.length > 0"
                  class="d-flex flex-wrap ga-2 mt-2"
                >
                  <v-chip
                    v-for="copilotName in selectedCopilotNames"
                    :key="copilotName"
                    color="info"
                    size="small"
                    variant="tonal"
                  >
                    {{ copilotName }}
                  </v-chip>
                </div>
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="processForm.purpose"
                  bg-color="white"
                  label="Finalité du processus"
                  placeholder="Décrivez la finalité et les objectifs du processus..."
                  prepend-inner-icon="mdi-text"
                  rounded="lg"
                  rows="4"
                  variant="solo"
                />
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="processForm.observation"
                  bg-color="white"
                  label="Observation (Cartographie des processus)"
                  placeholder="Ex: Mise à jour avec intégration des activités d'externalisation, phase transitoire, etc."
                  prepend-inner-icon="mdi-comment-text-outline"
                  rounded="lg"
                  rows="3"
                  variant="solo"
                />
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>
      </v-window-item>

      <!-- Étape 2: Séquences -->
      <v-window-item :value="2">
        <v-card elevation="0" rounded="xl" :style="`background: ${steps[1]!.gradient}`">
          <v-card-text class="pa-8">
            <div class="d-flex align-center justify-space-between mb-6">
              <div class="d-flex align-center">
                <v-avatar class="mr-4 elevation-4" :color="steps[1]!.color" size="64">
                  <v-icon color="white" size="32">mdi-timeline</v-icon>
                </v-avatar>
                <div>
                  <h2 class="text-h4 font-weight-bold">Séquences du processus</h2>
                  <p class="text-body-1 text-medium-emphasis mb-0">Workflow et étapes du processus</p>
                </div>
              </div>
              <v-btn :color="steps[1]!.color" prepend-icon="mdi-plus" size="large" @click="addSequence">
                Ajouter
              </v-btn>
            </div>

            <v-row class="mb-2">
              <v-col cols="12" md="4">
                <v-card class="sequence-stat-card" elevation="1" rounded="lg">
                  <v-card-text class="py-3 d-flex align-center justify-space-between">
                    <div>
                      <div class="text-caption text-medium-emphasis">Séquences</div>
                      <div class="text-h5 font-weight-bold">{{ processForm.sequences.length }}</div>
                    </div>
                    <v-avatar color="primary" size="36">
                      <v-icon color="white">mdi-layers-triple</v-icon>
                    </v-avatar>
                  </v-card-text>
                </v-card>
              </v-col>
              <v-col cols="12" md="4">
                <v-card class="sequence-stat-card" elevation="1" rounded="lg">
                  <v-card-text class="py-3 d-flex align-center justify-space-between">
                    <div>
                      <div class="text-caption text-medium-emphasis">Sous-activités</div>
                      <div class="text-h5 font-weight-bold">
                        {{ processForm.sequences.reduce((acc, seq) => acc + (seq.subActivities?.length || 0), 0) }}
                      </div>
                    </div>
                    <v-avatar color="indigo" size="36">
                      <v-icon color="white">mdi-format-list-bulleted-square</v-icon>
                    </v-avatar>
                  </v-card-text>
                </v-card>
              </v-col>
              <v-col cols="12" md="4">
                <v-card class="sequence-stat-card" elevation="1" rounded="lg">
                  <v-card-text class="py-3 d-flex align-center justify-space-between">
                    <div>
                      <div class="text-caption text-medium-emphasis">Flux documentés</div>
                      <div class="text-h5 font-weight-bold">
                        {{ processForm.sequences.filter(seq => (seq.inputs || seq.outputs || seq.activities)).length }}
                      </div>
                    </div>
                    <v-avatar color="teal" size="36">
                      <v-icon color="white">mdi-source-branch</v-icon>
                    </v-avatar>
                  </v-card-text>
                </v-card>
              </v-col>
            </v-row>

            <v-expansion-panels
              v-if="processForm.sequences.length > 0"
              v-model="openedSequencePanels"
              class="sequence-panels"
              multiple
              variant="accordion"
            >
              <v-expansion-panel
                v-for="(seq, index) in processForm.sequences"
                :key="seq.uid || seq.id || index"
                class="mb-3"
                elevation="1"
                rounded="xl"
                :value="index"
              >
                <v-expansion-panel-title>
                  <div class="d-flex align-center justify-space-between w-100 pr-2">
                    <div class="d-flex align-center ga-3">
                      <v-avatar class="text-white font-weight-bold" :color="steps[1]!.color" size="34">
                        {{ index + 1 }}
                      </v-avatar>
                      <div>
                        <div class="font-weight-bold text-body-1">Séquence {{ index + 1 }}</div>
                        <div class="text-caption text-medium-emphasis">
                          {{ seq.subActivities?.length || 0 }} sous-activité(s)
                        </div>
                      </div>
                    </div>
                    <div class="d-flex align-center ga-1">
                      <v-btn
                        :disabled="index === 0"
                        icon="mdi-arrow-up"
                        size="x-small"
                        variant="text"
                        @click.stop="moveSequence(index, 'up')"
                      />
                      <v-btn
                        :disabled="index === processForm.sequences.length - 1"
                        icon="mdi-arrow-down"
                        size="x-small"
                        variant="text"
                        @click.stop="moveSequence(index, 'down')"
                      />
                      <v-btn
                        color="error"
                        icon="mdi-delete"
                        size="x-small"
                        variant="tonal"
                        @click.stop="removeSequence(index)"
                      />
                    </div>
                  </div>
                </v-expansion-panel-title>

                <v-expansion-panel-text>
                  <div class="sequence-editor-grid">
                    <div class="sequence-block">
                      <div class="sequence-block-title">
                        <v-icon class="mr-1" size="16">mdi-arrow-collapse-right</v-icon>
                        Processus fournisseurs
                      </div>
                      <v-select
                        v-model="seq.supplierProcesses"
                        bg-color="white"
                        chips
                        closable-chips
                        density="comfortable"
                        hide-details
                        item-title="name"
                        item-value="name"
                        :items="getSequenceProcessItems(seq)"
                        multiple
                        rounded="lg"
                        variant="solo"
                      >
                        <template #chip="{ item, props }">
                          <v-chip v-bind="props" color="info" size="small">
                            {{ item.raw?.name || item.title }}
                          </v-chip>
                        </template>
                      </v-select>
                    </div>

                    <div class="sequence-block">
                      <div class="sequence-block-title">
                        <v-icon class="mr-1" size="16">mdi-database-import</v-icon>
                        Entrées
                      </div>
                      <v-textarea
                        v-model="seq.inputs"
                        bg-color="white"
                        density="comfortable"
                        hide-details
                        placeholder="Données, documents..."
                        rounded="lg"
                        rows="3"
                        variant="solo"
                      />
                    </div>

                    <div class="sequence-block sequence-block-activity">
                      <div class="sequence-block-title">
                        <v-icon class="mr-1" size="16">mdi-cog-transfer</v-icon>
                        Activité principale
                      </div>
                      <v-textarea
                        v-model="seq.activities"
                        bg-color="white"
                        class="mb-3"
                        density="comfortable"
                        hide-details
                        placeholder="Action principale réalisée..."
                        rounded="lg"
                        rows="3"
                        variant="solo"
                      />

                      <div class="subactivity-box">
                        <div class="d-flex align-center justify-space-between mb-2">
                          <span class="text-caption font-weight-medium">Sous-activités</span>
                          <v-chip color="primary" size="x-small" variant="tonal">
                            {{ seq.subActivities?.length || 0 }}
                          </v-chip>
                        </div>
                        <div class="d-flex ga-2 mb-2">
                          <v-text-field
                            v-model="seq.newSubActivity"
                            density="compact"
                            hide-details
                            placeholder="Ajouter une sous-activité"
                            variant="outlined"
                            @keydown.enter.prevent="addSubActivity(seq)"
                          />
                          <v-btn
                            color="primary"
                            prepend-icon="mdi-plus"
                            size="small"
                            variant="flat"
                            @click="addSubActivity(seq)"
                          >
                            Ajouter
                          </v-btn>
                        </div>
                        <div class="d-flex flex-wrap ga-2">
                          <v-chip
                            v-for="(sub, subIndex) in seq.subActivities"
                            :key="`${index}-sub-${subIndex}`"
                            closable
                            color="primary"
                            size="small"
                            @click:close="removeSubActivity(seq, subIndex)"
                          >
                            {{ sub }}
                          </v-chip>
                        </div>
                      </div>
                    </div>

                    <div class="sequence-block">
                      <div class="sequence-block-title">
                        <v-icon class="mr-1" size="16">mdi-database-export</v-icon>
                        Sorties
                      </div>
                      <v-textarea
                        v-model="seq.outputs"
                        bg-color="white"
                        density="comfortable"
                        hide-details
                        placeholder="Résultats, livrables..."
                        rounded="lg"
                        rows="3"
                        variant="solo"
                      />
                    </div>

                    <div class="sequence-block">
                      <div class="sequence-block-title">
                        <v-icon class="mr-1" size="16">mdi-arrow-collapse-left</v-icon>
                        Processus clients
                      </div>
                      <v-select
                        v-model="seq.clientProcesses"
                        bg-color="white"
                        chips
                        closable-chips
                        density="comfortable"
                        hide-details
                        item-title="name"
                        item-value="name"
                        :items="getSequenceProcessItems(seq)"
                        multiple
                        rounded="lg"
                        variant="solo"
                      >
                        <template #chip="{ item, props }">
                          <v-chip v-bind="props" color="success" size="small">
                            {{ item.raw?.name || item.title }}
                          </v-chip>
                        </template>
                      </v-select>
                    </div>
                  </div>
                </v-expansion-panel-text>
              </v-expansion-panel>
            </v-expansion-panels>

            <v-alert
              v-if="processForm.sequences.length === 0"
              class="mt-4"
              rounded="xl"
              type="info"
              variant="tonal"
            >
              <v-icon start>mdi-information</v-icon>
              Aucune séquence ajoutée. Cliquez sur "Ajouter" pour commencer.
            </v-alert>
          </v-card-text>
        </v-card>
      </v-window-item>

      <!-- Étape 3: Objectifs -->
      <v-window-item :value="3">
        <v-card elevation="0" rounded="xl" :style="`background: ${steps[2]!.gradient}`">
          <v-card-text class="pa-8">
            <div class="d-flex align-center justify-space-between mb-6">
              <div class="d-flex align-center">
                <v-avatar class="mr-4 elevation-4" :color="steps[2]!.color" size="64">
                  <v-icon color="white" size="32">mdi-target</v-icon>
                </v-avatar>
                <div>
                  <h2 class="text-h4 font-weight-bold">Objectifs et indicateurs</h2>
                  <p class="text-body-1 text-medium-emphasis mb-0">Mesure de la performance</p>
                </div>
              </div>
              <v-btn :color="steps[2]!.color" prepend-icon="mdi-plus" size="large" @click="addObjective">
                Ajouter
              </v-btn>
            </div>

            <v-row>
              <v-col v-for="(obj, index) in processForm.objectives" :key="index" cols="12" md="6">
                <v-card class="objective-card" elevation="2" rounded="xl" :style="`border-left: 4px solid ${steps[2]!.color}`">
                  <v-card-text class="pa-6">
                    <div class="d-flex align-center justify-space-between mb-4">
                      <v-chip class="font-weight-bold" :color="steps[2]!.color" size="large">
                        <v-icon start>mdi-bullseye-arrow</v-icon>
                        Objectif {{ index + 1 }}
                      </v-chip>
                      <v-btn
                        color="error"
                        icon="mdi-delete"
                        size="small"
                        variant="tonal"
                        @click="removeObjective(index)"
                      />
                    </div>

                    <v-text-field
                      v-model="obj.name"
                      bg-color="white"
                      class="mb-3"
                      density="comfortable"
                      label="Objectif"
                      placeholder="Ex: Réduire les délais"
                      prepend-inner-icon="mdi-flag"
                      rounded="lg"
                      variant="solo"
                    />
                    <v-text-field
                      v-model="obj.indicator"
                      bg-color="white"
                      density="comfortable"
                      label="Indicateur"
                      placeholder="Ex: Temps moyen de traitement"
                      prepend-inner-icon="mdi-chart-line"
                      rounded="lg"
                      variant="solo"
                    />
                  </v-card-text>
                </v-card>
              </v-col>
            </v-row>

            <v-alert
              v-if="processForm.objectives.length === 0"
              class="mt-4"
              rounded="xl"
              type="info"
              variant="tonal"
            >
              <v-icon start>mdi-information</v-icon>
              Aucun objectif défini. Cliquez sur "Ajouter" pour commencer.
            </v-alert>
          </v-card-text>
        </v-card>
      </v-window-item>

      <!-- Étape 4: Ressources -->
      <v-window-item :value="4">
        <v-card elevation="0" rounded="xl" :style="`background: ${steps[3]!.gradient}`">
          <v-card-text class="pa-8">
            <div class="d-flex align-center mb-6">
              <v-avatar class="mr-4 elevation-4" :color="steps[3]!.color" size="64">
                <v-icon color="white" size="32">mdi-toolbox</v-icon>
              </v-avatar>
              <div>
                <h2 class="text-h4 font-weight-bold">Ressources nécessaires</h2>
                <p class="text-body-1 text-medium-emphasis mb-0">Moyens humains, matériels et documentaires</p>
              </div>
            </div>

            <v-row>
              <v-col cols="12" md="6">
                <v-card class="resource-card" elevation="2" rounded="xl">
                  <v-card-text class="pa-6">
                    <div class="d-flex align-center mb-4">
                      <v-avatar class="mr-3" color="purple" size="48">
                        <v-icon color="white">mdi-account-group</v-icon>
                      </v-avatar>
                      <h3 class="text-h6 font-weight-bold">Ressources humaines</h3>
                    </div>
                    <v-combobox
                      v-model="processForm.resources.human"
                      bg-color="white"
                      chips
                      multiple
                      placeholder="Ajouter des ressources humaines..."
                      rounded="lg"
                      variant="solo"
                    />
                  </v-card-text>
                </v-card>
              </v-col>

              <v-col cols="12" md="6">
                <v-card class="resource-card" elevation="2" rounded="xl">
                  <v-card-text class="pa-6">
                    <div class="d-flex align-center mb-4">
                      <v-avatar class="mr-3" color="info" size="48">
                        <v-icon color="white">mdi-chip</v-icon>
                      </v-avatar>
                      <h3 class="text-h6 font-weight-bold">Ressources technologiques</h3>
                    </div>
                    <v-combobox
                      v-model="processForm.resources.technological"
                      bg-color="white"
                      chips
                      multiple
                      placeholder="Ajouter des ressources technologiques..."
                      rounded="lg"
                      variant="solo"
                    />
                  </v-card-text>
                </v-card>
              </v-col>

              <v-col cols="12" md="6">
                <v-card class="resource-card" elevation="2" rounded="xl">
                  <v-card-text class="pa-6">
                    <div class="d-flex align-center mb-4">
                      <v-avatar class="mr-3" color="success" size="48">
                        <v-icon color="white">mdi-toolbox</v-icon>
                      </v-avatar>
                      <h3 class="text-h6 font-weight-bold">Ressources matérielles</h3>
                    </div>
                    <v-combobox
                      v-model="processForm.resources.material"
                      bg-color="white"
                      chips
                      multiple
                      placeholder="Ajouter des ressources matérielles..."
                      rounded="lg"
                      variant="solo"
                    />
                  </v-card-text>
                </v-card>
              </v-col>

              <v-col cols="12" md="6">
                <v-card class="resource-card" elevation="2" rounded="xl">
                  <v-card-text class="pa-6">
                    <div class="d-flex align-center mb-4">
                      <v-avatar class="mr-3" color="warning" size="48">
                        <v-icon color="white">mdi-file-document</v-icon>
                      </v-avatar>
                      <h3 class="text-h6 font-weight-bold">Ressources documentaires</h3>
                    </div>
                    <v-combobox
                      v-model="processForm.resources.documentary"
                      bg-color="white"
                      chips
                      multiple
                      placeholder="Ajouter des ressources documentaires..."
                      rounded="lg"
                      variant="solo"
                    />
                  </v-card-text>
                </v-card>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>
      </v-window-item>

      <!-- Étape 5: Risques et Opportunités -->
      <v-window-item :value="5">
        <v-card elevation="0" rounded="xl" :style="`background: ${steps[4]!.gradient}`">
          <v-card-text class="pa-8">
            <div class="d-flex align-center mb-6">
              <v-avatar class="mr-4 elevation-4" :color="steps[4]!.color" size="64">
                <v-icon color="white" size="32">mdi-alert-octagon</v-icon>
              </v-avatar>
              <div>
                <h2 class="text-h4 font-weight-bold">Risques et Opportunités</h2>
                <p class="text-body-1 text-medium-emphasis mb-0">Identification et gestion des risques</p>
              </div>
            </div>

            <v-row>
              <v-col cols="12" md="6">
                <div class="d-flex align-center justify-space-between mb-4">
                  <h3 class="text-h5 font-weight-bold text-error d-flex align-center">
                    <v-icon class="mr-2">mdi-alert</v-icon>
                    Risques
                  </h3>
                  <v-btn color="error" prepend-icon="mdi-plus" @click="addRisk">
                    Ajouter
                  </v-btn>
                </div>

                <v-card
                  v-for="(risk, index) in processForm.risks"
                  :key="index"
                  class="mb-3"
                  elevation="2"
                  rounded="lg"
                  style="border-left: 4px solid #ef4444;"
                >
                  <v-card-text class="pa-4">
                    <div class="d-flex align-center justify-space-between mb-3">
                      <v-chip class="font-weight-bold" color="error" size="large">Risque {{ index + 1 }}</v-chip>
                      <v-btn
                        color="error"
                        icon="mdi-delete"
                        size="small"
                        variant="tonal"
                        @click="removeRisk(index)"
                      />
                    </div>
                    <v-textarea
                      v-model="risk.description"
                      bg-color="white"
                      class="mb-3"
                      label="Description du risque"
                      placeholder="Décrivez le risque..."
                      rounded="lg"
                      rows="3"
                      variant="solo"
                    />
                    <v-select
                      v-model="risk.norms"
                      bg-color="white"
                      chips
                      :disabled="availableNorms.length === 0"
                      :hint="availableNorms.length === 0 ? 'Aucune norme souscrite' : `${availableNorms.length} norme(s) disponible(s)`"
                      item-title="label"
                      item-value="value"
                      :items="availableNorms"
                      label="Normes liées"
                      :loading="availableNorms.length === 0"
                      multiple
                      persistent-hint
                      placeholder="Sélectionnez les normes ISO"
                      prepend-inner-icon="mdi-file-document"
                      rounded="lg"
                    >
                      <template #chip="{ item, props }">
                        <v-chip v-bind="props" color="error" size="small">
                          {{ item.title }}
                        </v-chip>
                      </template>
                    </v-select>
                  </v-card-text>
                </v-card>

                <v-alert v-if="processForm.risks.length === 0" rounded="lg" type="info" variant="tonal">
                  <v-icon size="small" start>mdi-information</v-icon>
                  Aucun risque identifié
                </v-alert>
              </v-col>

              <v-col cols="12" md="6">
                <div class="d-flex align-center justify-space-between mb-4">
                  <h3 class="text-h5 font-weight-bold text-success d-flex align-center">
                    <v-icon class="mr-2">mdi-lightbulb</v-icon>
                    Opportunités
                  </h3>
                  <v-btn color="success" prepend-icon="mdi-plus" @click="addOpportunity">
                    Ajouter
                  </v-btn>
                </div>

                <v-card
                  v-for="(opp, index) in processForm.opportunities"
                  :key="index"
                  class="mb-3"
                  elevation="2"
                  rounded="lg"
                  style="border-left: 4px solid #22c55e;"
                >
                  <v-card-text class="pa-4">
                    <div class="d-flex gap-2 align-start">
                      <v-chip class="mt-1" color="success" size="small">Opportunité {{ index + 1 }}</v-chip>
                      <div class="flex-grow-1">
                        <v-textarea
                          v-model="opp.description"
                          bg-color="white"
                          class="mb-3"
                          flat
                          hide-details
                          placeholder="Décrivez l'opportunité..."
                          rows="2"
                          variant="solo"
                        />
                        <v-select
                          v-model="opp.norms"
                          bg-color="white"
                          chips
                          :disabled="availableNorms.length === 0"
                          :hint="availableNorms.length === 0 ? 'Aucune norme souscrite' : `${availableNorms.length} norme(s) disponible(s)`"
                          item-title="label"
                          item-value="value"
                          :items="availableNorms"
                          label="Normes liées"
                          :loading="availableNorms.length === 0"
                          multiple
                          persistent-hint
                          placeholder="Sélectionnez les normes ISO"
                          prepend-inner-icon="mdi-file-document"
                          rounded="lg"
                        >
                          <template #chip="{ item, props }">
                            <v-chip v-bind="props" color="success" size="small">
                              {{ item.title }}
                            </v-chip>
                          </template>
                        </v-select>
                      </div>
                      <v-btn
                        class="mt-1"
                        color="error"
                        icon="mdi-delete"
                        size="small"
                        variant="text"
                        @click="removeOpportunity(index)"
                      />
                    </div>
                  </v-card-text>
                </v-card>

                <v-alert v-if="processForm.opportunities.length === 0" rounded="lg" type="info" variant="tonal">
                  <v-icon size="small" start>mdi-information</v-icon>
                  Aucune opportunité identifiée
                </v-alert>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>
      </v-window-item>

      <!-- Étape 6: Récapitulatif -->
      <v-window-item :value="6">
        <v-card elevation="0" rounded="xl" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);">
          <v-card-text class="pa-8">
            <div class="text-center mb-8">
              <v-avatar class="mb-4 elevation-8" color="success" size="80">
                <v-icon color="white" size="48">mdi-eye-check</v-icon>
              </v-avatar>
              <h2 class="text-h3 font-weight-bold mb-2">Récapitulatif du processus</h2>
              <p class="text-h6 text-medium-emphasis">Vérifiez toutes les informations avant l'enregistrement</p>
            </div>

            <!-- Informations générales -->
            <v-card class="mb-4" elevation="2" rounded="lg">
              <v-card-title class="d-flex align-center" style="background: linear-gradient(135deg, #5b8dd9 0%, #4a7bc8 100%); color: white;">
                <v-icon class="mr-2">mdi-information</v-icon>
                Informations générales
              </v-card-title>
              <v-card-text class="pa-4">
                <v-row>
                  <v-col cols="12" md="6">
                    <div class="mb-2">
                      <span class="text-caption text-medium-emphasis">Nom:</span>
                      <span class="font-weight-bold ml-2">{{ processForm.name || 'Non défini' }}</span>
                    </div>
                    <div class="mb-2">
                      <span class="text-caption text-medium-emphasis">Pilote:</span>
                      <span class="font-weight-bold ml-2">{{ selectedPilotDisplayName }}</span>
                    </div>
                    <div>
                      <span class="text-caption text-medium-emphasis">Co-pilote(s):</span>
                      <span class="font-weight-bold ml-2">{{ selectedCopilotDisplayText }}</span>
                    </div>
                  </v-col>
                  <v-col cols="12" md="6">
                    <div class="mb-2">
                      <span class="text-caption text-medium-emphasis">Catégorie:</span>
                      <v-chip class="ml-2" :color="processForm.category === 'management' ? 'primary' : processForm.category === 'realization' ? 'success' : 'info'" size="small">
                        {{ categories.find(c => c.value === processForm.category)?.title }}
                      </v-chip>
                    </div>
                    <div class="text-caption text-medium-emphasis mb-1">Finalité:</div>
                    <div class="text-body-2 recap-multiline mb-2">
                      {{ processForm.purpose || 'Non définie' }}
                    </div>
                    <div v-if="processForm.observation" class="text-caption text-medium-emphasis mb-1">Observation:</div>
                    <div v-if="processForm.observation" class="text-body-2 recap-multiline text-blue-grey-darken-2">
                      {{ processForm.observation }}
                    </div>
                  </v-col>
                </v-row>
              </v-card-text>
            </v-card>

            <!-- Séquences -->
            <v-card class="mb-4" elevation="2" rounded="lg">
              <v-card-title class="d-flex align-center" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: white;">
                <v-icon class="mr-2">mdi-timeline</v-icon>
                Séquences ({{ processForm.sequences.length }})
              </v-card-title>
              <v-card-text class="pa-4">
                <v-table v-if="processForm.sequences.length > 0" density="compact">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Processus fournisseurs</th>
                      <th>Entrées</th>
                      <th>Activités</th>
                      <th>Sous-activités</th>
                      <th>Sorties</th>
                      <th>Processus clients</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(seq, i) in processForm.sequences" :key="i">
                      <td>{{ i + 1 }}</td>
                      <td>{{ seq.supplierProcesses.length > 0 ? seq.supplierProcesses.join(', ') : '-' }}</td>
                      <td class="recap-cell-text">{{ seq.inputs || '-' }}</td>
                      <td class="recap-cell-text">{{ seq.activities || '-' }}</td>
                      <td class="recap-cell-text">{{ (seq.subActivities || []).length > 0 ? seq.subActivities.join(', ') : '-' }}</td>
                      <td class="recap-cell-text">{{ seq.outputs || '-' }}</td>
                      <td>{{ seq.clientProcesses.length > 0 ? seq.clientProcesses.join(', ') : '-' }}</td>
                    </tr>
                  </tbody>
                </v-table>
                <div v-else class="text-caption text-medium-emphasis">Aucune séquence</div>
              </v-card-text>
            </v-card>

            <v-row>
              <!-- Objectifs -->
              <v-col cols="12" md="6">
                <v-card class="mb-4" elevation="2" rounded="lg">
                  <v-card-title class="d-flex align-center" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white;">
                    <v-icon class="mr-2" size="small">mdi-target</v-icon>
                    <span class="text-body-1">Objectifs et indicateurs</span>
                  </v-card-title>
                  <v-card-text class="pa-3">
                    <v-table v-if="processForm.objectives.length > 0" density="compact">
                      <thead>
                        <tr>
                          <th style="width: 56px;">#</th>
                          <th>Objectif</th>
                          <th>Indicateur</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(obj, i) in processForm.objectives" :key="i">
                          <td>{{ i + 1 }}</td>
                          <td class="recap-cell-text">{{ obj.name || '-' }}</td>
                          <td class="recap-cell-text">{{ obj.indicator || 'Indicateur non défini' }}</td>
                        </tr>
                      </tbody>
                    </v-table>
                    <div v-else class="text-caption text-medium-emphasis">Aucun objectif</div>
                  </v-card-text>
                </v-card>
              </v-col>

              <!-- Ressources -->
              <v-col cols="12" md="6">
                <v-card class="mb-4" elevation="2" rounded="lg">
                  <v-card-title class="d-flex align-center" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white;">
                    <v-icon class="mr-2" size="small">mdi-toolbox</v-icon>
                    <span class="text-body-1">Ressources</span>
                  </v-card-title>
                  <v-card-text class="pa-3">
                    <div class="text-caption mb-3">
                      <strong>Humaines:</strong>
                      <div class="recap-multiline mt-1">{{ formatDisplayList(processForm.resources.human) }}</div>
                    </div>
                    <div class="text-caption mb-3">
                      <strong>Technologiques:</strong>
                      <div class="recap-multiline mt-1">{{ formatDisplayList(processForm.resources.technological) }}</div>
                    </div>
                    <div class="text-caption mb-3">
                      <strong>Matérielles:</strong>
                      <div class="recap-multiline mt-1">{{ formatDisplayList(processForm.resources.material) }}</div>
                    </div>
                    <div class="text-caption">
                      <strong>Documentaires:</strong>
                      <div class="recap-multiline mt-1">{{ formatDisplayList(processForm.resources.documentary) }}</div>
                    </div>
                  </v-card-text>
                </v-card>
              </v-col>

              <!-- Risques -->
              <v-col cols="12" md="6">
                <v-card class="mb-4" elevation="2" rounded="lg">
                  <v-card-title class="d-flex align-center" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white;">
                    <v-icon class="mr-2" size="small">mdi-alert</v-icon>
                    <span class="text-body-1">Risques ({{ processForm.risks.length }})</span>
                  </v-card-title>
                  <v-card-text class="pa-3">
                    <div v-if="processForm.risks.length > 0" class="d-flex flex-column ga-2">
                      <div v-for="(risk, i) in processForm.risks" :key="i" class="text-caption">
                        <div><strong>{{ i + 1 }}.</strong> {{ risk.description || '-' }}</div>
                        <div class="mt-1">
                          <v-chip
                            v-for="norm in risk.norms"
                            :key="`${i}-${norm}`"
                            class="mr-1 mb-1"
                            color="error"
                            size="x-small"
                            variant="tonal"
                          >
                            {{ norm }}
                          </v-chip>
                          <span v-if="risk.norms.length === 0" class="text-medium-emphasis">Aucune norme liée</span>
                        </div>
                      </div>
                    </div>
                    <div v-else class="text-caption text-medium-emphasis">Aucun risque</div>
                  </v-card-text>
                </v-card>
              </v-col>

              <!-- Opportunités -->
              <v-col cols="12" md="6">
                <v-card class="mb-4" elevation="2" rounded="lg">
                  <v-card-title class="d-flex align-center" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
                    <v-icon class="mr-2" size="small">mdi-lightbulb</v-icon>
                    <span class="text-body-1">Opportunités ({{ processForm.opportunities.length }})</span>
                  </v-card-title>
                  <v-card-text class="pa-3">
                    <div v-if="processForm.opportunities.length > 0" class="d-flex flex-column ga-1">
                      <div v-for="(opp, i) in processForm.opportunities" :key="i" class="text-caption">
                        <div><strong>{{ i + 1 }}.</strong> {{ opp.description || '-' }}</div>
                        <div class="mt-1">
                          <v-chip
                            v-for="norm in opp.norms"
                            :key="`${i}-${norm}`"
                            class="mr-1 mb-1"
                            color="success"
                            size="x-small"
                            variant="tonal"
                          >
                            {{ norm }}
                          </v-chip>
                          <span v-if="opp.norms.length === 0" class="text-medium-emphasis">Aucune norme liée</span>
                        </div>
                      </div>
                    </div>
                    <div v-else class="text-caption text-medium-emphasis">Aucune opportunité</div>
                  </v-card-text>
                </v-card>
              </v-col>
            </v-row>

            <v-alert class="mt-4" prominent type="success" variant="tonal">
              <v-icon size="large" start>mdi-check-circle</v-icon>
              <div>
                <div class="text-h6 font-weight-bold mb-1">Prêt à enregistrer</div>
                <div class="text-body-2">Cliquez sur "Enregistrer" pour sauvegarder le processus</div>
              </div>
            </v-alert>
          </v-card-text>
        </v-card>
      </v-window-item>
    </v-window>

    <!-- Navigation -->
    <ProcessNavigation
      :current-step="currentStep"
      :saving="saving"
      :steps="steps"
      :total-steps="6"
      @next="currentStep++"
      @prev="currentStep--"
      @save="handleSave"
    />

    <GeneratedDocumentConfigDialog
      v-model="generationDialog"
      :fixed-process-id="currentProcessId"
      :loading="exporting"
      :site-id="currentSiteId"
      @confirm="confirmGenerationConfig"
    />

    <v-dialog v-model="verifyDialog" max-width="560">
      <v-card rounded="xl">
        <v-card-title class="pa-4">Soumettre pour vérification</v-card-title>
        <v-card-text class="pa-4">
          <div class="text-body-2 mb-2">Code du document</div>
          <v-alert type="info" variant="tonal">{{ exportedDocumentCode || '—' }}</v-alert>
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-spacer />
          <v-btn variant="text" @click="verifyDialog = false">Annuler</v-btn>
          <v-btn color="warning" :loading="submittingForVerification" @click="submitForVerification">
            Confirmer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <PreviewExportModal
      v-model="detailsPreviewDialog"
      :blob="detailsPreviewBlob"
      :file-type="detailsPreviewFileType"
      :filename="detailsPreviewFilename"
      :loading="exporting"
      :metadata="{
        version: processForm.version || '1.0',
        summaryItems: [
          { label: 'Code', value: processForm.code || '-' },
          { label: 'Pilote', value: selectedPilotDisplayName || '-' },
          { label: 'Catégorie', value: processForm.category || '-' },
          { label: 'Séquences', value: processForm.sequences?.length || 0 },
        ],
      }"
      :title="detailsPreviewTitle"
      @download="toast.success('Téléchargement de la fiche processus démarré avec succès.')"
    />

    <!-- Dialog Cartographie & Interactions -->
    <v-dialog v-model="cartographyModal" max-width="1280" scrollable>
      <v-card rounded="xl">
        <v-card-title class="pa-4 d-flex justify-space-between align-center bg-slate-100">
          <div class="d-flex align-center gap-2">
            <v-icon color="primary">mdi-vector-polyline</v-icon>
            <span class="text-subtitle-1 font-weight-bold">Cartographie & Matrice d'Interactions</span>
          </div>
          <v-btn density="compact" icon="mdi-close" variant="text" @click="cartographyModal = false" />
        </v-card-title>
        <v-card-text class="pa-4">
          <ProcessCartographyViewer
            :hide-header="true"
            :process-id="currentProcessId"
          />
        </v-card-text>
      </v-card>
    </v-dialog>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref, watch } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import api from '@/api/client'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PreviewExportModal from '@/modules/shared/components/PreviewExportModal.vue'
  import ProcessCartographyViewer from '@/modules/clienta/components/processes/ProcessCartographyViewer.vue'
  import GeneratedDocumentConfigDialog from '@/modules/clienta/components/documents/GeneratedDocumentConfigDialog.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import ProcessNavigation from '@/modules/clienta/pages/iso/context/components/ProcessNavigation.vue'
  import ProcessStepper from '@/modules/clienta/pages/iso/context/components/ProcessStepper.vue'
  import { useToast } from '@/modules/shared/composables/useToast'
  import { useDocumentFlow } from '@/modules/clienta/composables/useDocumentFlow'
  import processService from '@/services/processService'
  import userService, { type User } from '@/services/userService'
  import { useAuthStore } from '@/stores/auth'

  const route = useRoute()
  const router = useRouter()
  const toast = useToast()
  const authStore = useAuthStore()
  const saving = ref(false)
  const cartographyModal = ref(false)

  function openCartographyModal () {
    cartographyModal.value = true
  }

  const generationDialog = ref(false)
  const generationAction = ref<'draft' | 'preview' | 'download' | 'verify'>('draft')
  type GeneratedDocumentContext = {
    document_type_catalog_id: number
    process_id?: number | null
    process_name?: string
    process_type?: string
    process_abbreviation?: string
  }

  const generationContext = ref<GeneratedDocumentContext | null>(null)
  const currentStep = ref(1)
  const availableProcesses = ref<Array<{ id: number | string, name: string }>>([])
  const availableNorms = ref<Array<{ value: string, label: string }>>([])
  const loadedObjectiveIds = ref<number[]>([])
  const loadedRiskIds = ref<number[]>([])
  const loadedOpportunityIds = ref<number[]>([])

  const currentSiteId = computed(() => {
    const storedSiteId = Number(localStorage.getItem('current_site_id'))
    return authStore.currentSiteId ?? (Number.isFinite(storedSiteId) ? storedSiteId : null)
  })

  const currentProcessId = computed(() => {
    const id = Number((route.params as { id?: string }).id)
    return Number.isFinite(id) && id > 0 ? id : null
  })
  const loadedOpportunityDescriptions = ref<string[]>([])
  const openedSequencePanels = ref<number[]>([])
  const users = ref<Array<{ id: number, name: string }>>([])
  const usersLoading = ref(false)
  const selectedPilotId = ref<number | null>(null)
  const selectedPilotName = ref<string | null>(null)
  const selectedCopilotIds = ref<number[]>([])
  const selectedPilotDisplayName = computed(() => {
    if (selectedPilotId.value) {
      const match = users.value.find(user => user.id === selectedPilotId.value)
      if (match?.name) return match.name
    }

    return selectedPilotName.value || 'Non défini'
  })
  const selectedCopilotNames = computed(() => {
    return selectedCopilotIds.value.map(copilotId => {
      const match = users.value.find(user => user.id === copilotId)
      return match?.name || `Copilote #${copilotId}`
    })
  })
  const selectedCopilotDisplayText = computed(() => {
    return selectedCopilotNames.value.length > 0
      ? selectedCopilotNames.value.join(', ')
      : 'Non défini'
  })

  const steps = [
    { label: 'Général', color: '#5b8dd9', gradient: 'linear-gradient(135deg, #5b8dd915 0%, #5b8dd905 100%)' },
    { label: 'Séquences', color: '#22c55e', gradient: 'linear-gradient(135deg, #22c55e15 0%, #22c55e05 100%)' },
    { label: 'Objectifs', color: '#3b82f6', gradient: 'linear-gradient(135deg, #3b82f615 0%, #3b82f605 100%)' },
    { label: 'Ressources', color: '#f59e0b', gradient: 'linear-gradient(135deg, #f59e0b15 0%, #f59e0b05 100%)' },
    { label: 'Risques', color: '#ef4444', gradient: 'linear-gradient(135deg, #ef444415 0%, #ef444405 100%)' },
    { label: 'Récapitulatif', color: '#10b981', gradient: 'linear-gradient(135deg, #10b98115 0%, #10b98105 100%)' },
  ]

  const categories = [
    { title: 'Management', value: 'management' },
    { title: 'Réalisation', value: 'realization' },
    { title: 'Support', value: 'support' },
  ]

  const processForm = ref({
    code: '',
    version: '1.0',
    name: '',
    category: 'management',
    purpose: '',
    observation: '',
    sequences: [] as Array<{ id?: number, uid: string, sequenceOrder: number, supplierProcesses: string[], inputs: string, activities: string, subActivities: string[], newSubActivity?: string, outputs: string, clientProcesses: string[] }>,
    objectives: [] as Array<{ id?: number, indicatorId?: number, name: string, indicator: string }>,
    resources: {
      human: [] as string[],
      technological: [] as string[],
      material: [] as string[],
      documentary: [] as string[],
    },
    risks: [] as Array<{ id?: number, description: string, norms: string[] }>,
    opportunities: [] as Array<{ id?: number, description: string, norms: string[] }>,
  })

  function getPilotName (_pilotId?: unknown): string {
    return selectedPilotDisplayName.value
  }

  function normalizeProcessCategory (raw?: string): string {
    if (!raw) return ''
    const value = raw.toLowerCase()
    if (value === 'pilotage' || value === 'management') return 'management'
    if (value === 'operationnel' || value === 'realization') return 'realization'
    if (value === 'support') return 'support'
    return value
  }

  function normalizeText (value: string) {
    return value.trim().toLocaleLowerCase()
  }

  function extractNormCode (value: string): string | null {
    const text = value.toUpperCase()
    const match = text.match(/(?:ISO[\s:-]*)?(\d{4,5})/)
    return match?.[1] || null
  }

  function normalizeNormsForSelect (values: string[]): string[] {
    if (!Array.isArray(values) || values.length === 0) return []
    if (availableNorms.value.length === 0) return Array.from(new Set(values.map(v => String(v).trim()).filter(Boolean)))

    const normalize = (v: string) => v.toLocaleLowerCase().replace(/\s+/g, ' ').trim()
    const mapped: string[] = []

    for (const raw of values) {
      const value = String(raw || '').trim()
      if (!value) continue

      const exact = availableNorms.value.find(n => normalize(n.value) === normalize(value))
      if (exact) {
        mapped.push(exact.value)
        continue
      }

      const byLabel = availableNorms.value.find(n => normalize(n.label) === normalize(value))
      if (byLabel) {
        mapped.push(byLabel.value)
        continue
      }

      const code = extractNormCode(value)
      if (code) {
        const byCode = availableNorms.value.find(n => extractNormCode(n.value) === code || extractNormCode(n.label) === code)
        if (byCode) {
          mapped.push(byCode.value)
          continue
        }
      }

      mapped.push(value)
    }

    return Array.from(new Set(mapped))
  }

  function normalizePilotId (value: unknown): number | null {
    if (value && typeof value === 'object' && 'id' in (value as any)) {
      const raw = Number((value as any).id)
      return Number.isFinite(raw) ? raw : null
    }
    const asNumber = Number(value)
    return Number.isFinite(asNumber) ? asNumber : null
  }

  function resolveUserDisplayName (raw: any): string {
    if (!raw) return ''
    if (typeof raw === 'string') return raw
    if (typeof raw === 'object') {
      const candidate = (raw as any).attributes ?? raw
      if (typeof candidate.name === 'string') {
        const name = candidate.name.trim()
        if (name) return name
      }
      const first = String(candidate.first_name || candidate.firstName || '').trim()
      const last = String(candidate.last_name || candidate.lastName || '').trim()
      const full = `${first} ${last}`.trim()
      if (full) return full
      const username = String(candidate.username || '').trim()
      if (username) return username
      const email = String(candidate.email || '').trim()
      if (email) return email
      if (typeof candidate.label === 'string') {
        const label = candidate.label.trim()
        if (label) return label
      }
    }
    return ''
  }

  async function loadUsersForSite (siteId: number) {
    usersLoading.value = true
    try {
      const siteUsers = await userService.getBySite(siteId)
      const mapped = siteUsers
        .map((user: User) => {
          const base = (user as any).attributes ?? user
          const id = Number((user as any).id ?? base.id)
          const email = String(base.email || (user as any).email || '')
          return {
            id,
            name: resolveUserDisplayName(base) || email,
          }
        })
        .filter(user => Number.isFinite(user.id))
      if (selectedPilotId.value) {
        const already = mapped.some(u => u.id === selectedPilotId.value)
        if (!already) {
          mapped.unshift({
            id: Number(selectedPilotId.value),
            name: selectedPilotName.value || `Pilote #${selectedPilotId.value}`,
          })
        }
        if (!selectedPilotName.value) {
          const match = mapped.find(u => u.id === selectedPilotId.value)
          if (match?.name) {
            selectedPilotName.value = match.name
          }
        }
      }
      users.value = mapped
    } catch (error) {
      console.error('[ProcessDetails] Failed to load users:', error)
      users.value = []
    } finally {
      usersLoading.value = false
    }
  }

  async function loadProcessesForSite () {
    const storedSiteId = Number(localStorage.getItem('current_site_id'))
    const siteId = authStore.currentSiteId ?? (Number.isFinite(storedSiteId) ? storedSiteId : null)
    if (!siteId) {
      availableProcesses.value = []
      return
    }

    try {
      const response = await processService.getProcesses({ site_id: siteId }, 1, 500)
      const currentProcessId = Number((route.params as { id?: string }).id)
      const apiProcesses = (response.data || []).map((p: any) => ({
        id: p.id,
        name: p.title || p.name || `Processus #${p.id}`,
      })).filter((p: any) => !Number.isFinite(currentProcessId) || Number(p.id) !== currentProcessId)

      let scopeProcesses: Array<{ id: number | string, name: string }> = []
      try {
        const scopeResponse = await api.get('/application-scopes', {
          params: { site_id: siteId, is_current: true },
        })
        const scope = scopeResponse.data?.data?.[0]?.attributes || scopeResponse.data?.data?.[0]
        const list = scope?.processes || []
        scopeProcesses = list.map((p: any, index: number) => ({
          id: `scope-${index}`,
          name: p.name || `Processus #${index + 1}`,
        }))
      } catch {
      // optional
      }

      const normalizeName = (value: string) => value.trim().toLocaleLowerCase()
      const merged: Array<{ id: number | string, name: string }> = [...apiProcesses]
      const seen = new Set(apiProcesses.map((p: any) => normalizeName(p.name)))
      for (const p of scopeProcesses) {
        const key = normalizeName(p.name)
        if (!seen.has(key)) {
          merged.push(p)
          seen.add(key)
        }
      }

      availableProcesses.value = merged
      await loadUsersForSite(siteId)
    } catch (error) {
      console.error('[ProcessDetails] Failed to load processes:', error)
      availableProcesses.value = []
      users.value = []
    }
  }

  async function loadNormsForSite () {
    const storedSiteId = Number(localStorage.getItem('current_site_id'))
    const siteId = authStore.currentSiteId ?? (Number.isFinite(storedSiteId) ? storedSiteId : null)

    if (!siteId) {
      availableNorms.value = []
      return
    }

    try {
      // Charger uniquement les abonnements du site sélectionné.
      const response = await api.get('/enterprise-subscriptions', { params: { site_id: siteId } })
      const subscriptions = response.data?.data || []
      const now = new Date()

      const getStatus = (sub: any) => String(sub?.attributes?.status ?? sub?.status ?? '').toLowerCase()
      const activeSubscriptions = subscriptions.filter((sub: any) => {
        const isActive = Boolean(sub?.attributes?.is_active ?? sub?.is_active)
        const expirationRaw = sub?.attributes?.expiration_date ?? sub?.expiration_date
        const status = getStatus(sub)

        if (status === 'cancelled' || status === 'expired') return false
        if (!isActive) return false
        if (!expirationRaw) return true
        const expiration = new Date(expirationRaw)
        return Number.isNaN(expiration.getTime()) || expiration > now
      })

      // Fallback pragmatique: si le filtrage "actif" ne remonte rien, on prend tous
      // les abonnements du site (évite "Aucune norme souscrite" à tort).
      const baseSubs = activeSubscriptions.length > 0 ? activeSubscriptions : subscriptions
      const norms = baseSubs.flatMap((sub: any) => extractNormsFromSubscription(sub))
      const seen = new Set<string>()
      const unresolvedNormIds = new Set<number>()

      const mappedFromSubscriptions = norms
        .map((norm: any) => {
          const attributes = norm?.attributes || norm
          const code = String(attributes?.code || '').trim()
          const name = String(attributes?.name || attributes?.title || '').trim()
          const value = code || name
          const normId = Number(norm?.id)

          if (!value && Number.isFinite(normId)) {
            unresolvedNormIds.add(normId)
            return null
          }
          if (!value) return null

          const key = value.toLowerCase()
          if (seen.has(key)) return null
          seen.add(key)
          return {
            value,
            label: code && name ? `${code} - ${name}` : value,
          }
        })
        .filter(Boolean) as Array<{ value: string, label: string }>

      if (mappedFromSubscriptions.length > 0 || unresolvedNormIds.size === 0) {
        availableNorms.value = mappedFromSubscriptions
        return
      }

      // Fallback: certaines réponses JSON:API ne contiennent que l'id.
      const normsResponse = await api.get('/norms')
      const allAccessibleNorms = normsResponse.data?.data || []
      const fallbackNorms = allAccessibleNorms
        .filter((norm: any) => unresolvedNormIds.has(Number(norm?.id)))
        .map((norm: any) => {
          const code = String(norm?.code || norm?.attributes?.code || '').trim()
          const name = String(norm?.name || norm?.attributes?.name || norm?.title || '').trim()
          const value = code || name
          if (!value) return null
          return {
            value,
            label: code && name ? `${code} - ${name}` : value,
          }
        })
        .filter(Boolean) as Array<{ value: string, label: string }>

      availableNorms.value = fallbackNorms
    } catch (error) {
      console.error('[ProcessDetails] Failed to load subscribed norms:', error)
      availableNorms.value = []
    }
  }

  function normalizeStringArray (value: any): string[] {
    if (!value) return []

    // Si c'est déjà un array
    if (Array.isArray(value)) {
      return value
        .map(v => {
          if (typeof v === 'string' || typeof v === 'number') {
            return String(v).trim()
          }
          if (v && typeof v === 'object') {
            return String(v.name || v.title || v.label || v.value || '').trim()
          }
          return ''
        })
        .filter(Boolean)
    }

    // Si c'est une string JSON
    if (typeof value === 'string') {
      // Essayer de parser comme JSON
      if (value.startsWith('[') || value.startsWith('{')) {
        try {
          const parsed = JSON.parse(value)
          if (Array.isArray(parsed)) {
            return normalizeStringArray(parsed)
          }
        } catch {
        // Pas du JSON valide
        }
      }
      // Sinon, split par virgule
      return value.split(',').map(v => v.trim()).filter(Boolean)
    }

    // Si c'est un objet (ex: JSONB sérialisé en objet indexé)
    if (value && typeof value === 'object') {
      if (Array.isArray(value.data)) {
        return normalizeStringArray(value.data)
      }

      const values = Object.values(value)
      if (values.length > 0) {
        return normalizeStringArray(values)
      }
    }

    return []
  }

  function formatDisplayList (values: string[]): string {
    const normalized = normalizeStringArray(values)
    return normalized.length > 0 ? normalized.join(', ') : 'Aucun élément renseigné'
  }

  function asArrayOrEmpty (value: unknown): any[] {
    return Array.isArray(value) ? value : []
  }

  function getValueByPath (source: any, path: string): unknown {
    return path
      .split('.')
      .reduce((current: any, segment: string) => (current == null ? undefined : current[segment]), source)
  }

  function extractNormsFromSubscription (sub: any): any[] {
    const candidatePaths = [
      'offer.norms',
      'attributes.offer.norms',
      'relationships.offer.relationships.norms.data',
      'relationships.offer.relationships.norms',
      'relationships.offer.data.relationships.norms.data',
      'relationships.offer.data.relationships.norms',
    ]

    for (const path of candidatePaths) {
      const values = asArrayOrEmpty(getValueByPath(sub, path))
      if (values.length > 0) return values
    }

    return []
  }

  function normalizeSequenceOrderValue (sequence: any) {
    const source = sequence?.attributes || sequence
    return Number(source?.sequence_order || 0)
  }

  function mapLoadedObjectives (data: any) {
    const objectives = data.relationships?.objectives || data.objectives || data.processObjectives || []
    return (Array.isArray(objectives) ? objectives : []).map((obj: any) => ({
      id: obj.id,
      indicatorId: obj.indicator_id || obj.indicator?.id,
      name: obj.title || obj.name || '',
      indicator: obj.indicator?.name || obj.indicator_name || obj.indicator || '',
    }))
  }

  function mapLoadedResources (data: any) {
    const rawResources = data.ressources || data.resources || {}
    const isResourceObject = rawResources && typeof rawResources === 'object' && !Array.isArray(rawResources)
    return {
      human: normalizeStringArray(isResourceObject ? rawResources.human : data.human_resources),
      technological: normalizeStringArray(isResourceObject ? rawResources.technological : data.technological_resources),
      material: normalizeStringArray(isResourceObject ? rawResources.material : data.material_resources),
      documentary: normalizeStringArray(
        isResourceObject
          ? rawResources.documentary
          : (Array.isArray(rawResources) ? rawResources : data.documentary_resources),
      ),
    }
  }

  function mapLoadedRisks (data: any) {
    const risksOpps = data.relationships?.risks_opportunities || data.risks_opportunities || data.risks || []
    return (Array.isArray(risksOpps) ? risksOpps : [])
      .filter((risk: any) => {
        const type = String(risk.type || risk.kind || 'risk')
        return type === 'risque' || type === 'risk'
      })
      .map((risk: any) => ({
        id: typeof risk.id === 'number' ? risk.id : Number(risk.id) || undefined,
        description: risk.description || risk.title || '',
        norms: normalizeNormsForSelect(normalizeStringArray(risk.norms || risk.normes || risk.normes_iso || risk.norms_iso)),
      }))
  }

  function mapLoadedOpportunities (data: any) {
    const risksOpps = data.relationships?.risks_opportunities || data.risks_opportunities || data.risks || []
    const opportunities = data.relationships?.opportunities || data.opportunities || []

    return [
      ...(Array.isArray(risksOpps) ? risksOpps : [])
        .filter((risk: any) => {
          const type = String(risk.type || risk.kind || '')
          return type === 'opportunite' || type === 'opportunity'
        })
        .map((risk: any) => ({
          id: typeof risk.id === 'number' ? risk.id : Number(risk.id) || undefined,
          description: risk.description || risk.title || '',
          norms: normalizeNormsForSelect(normalizeStringArray(risk.norms || risk.normes || risk.normes_iso || risk.norms_iso)),
        })),
      ...(Array.isArray(opportunities) ? opportunities : []).map((opportunity: any) => ({
        id: typeof opportunity?.id === 'number' ? opportunity.id : Number(opportunity?.id) || undefined,
        description: opportunity?.description || opportunity?.title || String(opportunity || ''),
        norms: normalizeNormsForSelect(normalizeStringArray(opportunity?.norms || opportunity?.normes || opportunity?.normes_iso || opportunity?.norms_iso)),
      })),
    ].filter((item: any) => Boolean(item?.description))
  }

  function normalizeSequenceSource (rawSeq: any) {
    return rawSeq?.attributes ? { id: rawSeq.id, ...rawSeq.attributes } : (rawSeq || {})
  }

  function normalizeSequenceText (source: any, ...keys: string[]): string {
    for (const key of keys) {
      const value = source[key]
      if (typeof value === 'string' && value.trim()) {
        return value
      }
    }
    return ''
  }

  function normalizeSequenceArray (source: any, ...keys: string[]): string[] {
    for (const key of keys) {
      const value = source[key]
      if (value !== undefined && value !== null) {
        return normalizeStringArray(value)
      }
    }
    return []
  }

  async function loadProcessDetails (processId: string | number) {
    try {
      const rawResponse = await api.get(`/processes/${processId}`)
      const rawData = rawResponse?.data?.data || rawResponse?.data || {}
      const data = rawData?.attributes
        ? { id: rawData.id, ...rawData.attributes, relationships: rawData.relationships }
        : rawData

      console.log('[ProcessDetails] Raw API response:', rawData)
      console.log('[ProcessDetails] Processed data:', data)

      processForm.value.code = data.code || ''
      processForm.value.version = data.version || '1.0'
      processForm.value.name = data.title || data.name || processForm.value.name
      processForm.value.category = normalizeProcessCategory(data.category || data.type || data.process_type) || processForm.value.category
      processForm.value.purpose = data.purpose || data.finalite || data.description || ''
      processForm.value.observation = data.observation || ''
      const pilotSource = data.pilot
        || data.relationships?.pilot?.attributes
        || data.relationships?.pilot?.data?.attributes
      selectedPilotId.value = normalizePilotId(data.pilot_id || pilotSource?.id || data.relationships?.pilot?.data?.id || null)
      selectedPilotName.value = resolveUserDisplayName(pilotSource)
        || null
      if (selectedPilotId.value) {
        const exists = users.value.some(u => u.id === selectedPilotId.value)
        if (!exists) {
          users.value = [{
            id: Number(selectedPilotId.value),
            name: selectedPilotName.value || `Pilote #${selectedPilotId.value}`,
          }, ...users.value]
        }
      }

      const copilotsFromRelations = Array.isArray(data.relationships?.copilots?.data)
        ? data.relationships.copilots.data.map((item: any) => Number(item?.id || item?.attributes?.id || item)).filter((id: number) => Number.isFinite(id))
        : []
      const copilotRelationEntries = Array.isArray(data.relationships?.copilots?.data)
        ? data.relationships.copilots.data
        : []
      const copilotsFromAttributes = Array.isArray(data.copilot_ids)
        ? data.copilot_ids.map(Number).filter((id: number) => Number.isFinite(id))
        : []
      const legacyCopilot = normalizePilotId(data.copilot_id)
      selectedCopilotIds.value = Array.from(new Set([
        ...copilotsFromRelations,
        ...copilotsFromAttributes,
        ...(legacyCopilot ? [legacyCopilot] : []),
      ]))

      for (const copilotId of selectedCopilotIds.value) {
        const exists = users.value.some(u => u.id === copilotId)
        if (!exists) {
          const relationMatch = copilotRelationEntries.find((item: any) => Number(item?.id || item?.attributes?.id || item) === copilotId)
          const relationName = resolveUserDisplayName(relationMatch?.attributes ?? relationMatch)
          users.value = [{ id: copilotId, name: relationName || `Copilote #${copilotId}` }, ...users.value]
        }
      }

      // Extraire séquences depuis toutes les formes de réponse supportées
      const sequences = extractSequencesFromProcessData(data)

      console.log('[ProcessDetails] Raw sequences:', sequences)

      processForm.value.sequences = (Array.isArray(sequences) ? sequences : [])
        .toSorted((a: any, b: any) => normalizeSequenceOrderValue(a) - normalizeSequenceOrderValue(b))
        .map((seq: any, idx: number) => normalizeSequenceForForm(seq, idx))
      openedSequencePanels.value = processForm.value.sequences.map((_, idx) => idx)

      processForm.value.objectives = mapLoadedObjectives(data)
      loadedObjectiveIds.value = processForm.value.objectives
        .map(o => o.id)
        .filter((id): id is number => typeof id === 'number')

      processForm.value.resources = mapLoadedResources(data)

      const loadedRisks = mapLoadedRisks(data)
      processForm.value.risks = loadedRisks
      loadedRiskIds.value = loadedRisks
        .map(r => r.id)
        .filter((id): id is number => typeof id === 'number')

      processForm.value.opportunities = mapLoadedOpportunities(data)
      loadedOpportunityDescriptions.value = processForm.value.opportunities.map(o => normalizeText(o.description)).filter(Boolean)
      loadedOpportunityIds.value = processForm.value.opportunities
        .map(o => o.id)
        .filter((id): id is number => typeof id === 'number')

      console.log('[ProcessDetails] Loaded data:', {
        sequences: processForm.value.sequences.length,
        objectives: processForm.value.objectives.length,
        risks: processForm.value.risks.length,
        opportunities: processForm.value.opportunities.length,
      })
    } catch (error) {
      console.error('[ProcessDetails] Failed to load process details:', error)
      toast.error('Erreur lors du chargement du processus')
    }
  }

  function extractSequencesFromProcessData (data: any): any[] {
    const relationships = data?.relationships || {}
    const sequenceCandidates = [
      data?.attributes?.sequences,
      data?.sequences,
      relationships?.sequences?.attributes,
      relationships?.sequences?.data,
      relationships?.sequences,
    ]

    for (const candidate of sequenceCandidates) {
      if (Array.isArray(candidate)) {
        return candidate
      }
      if (candidate && typeof candidate === 'object') {
        return Object.values(candidate)
      }
    }

    return []
  }

  function normalizeSequenceForForm (rawSeq: any, idx = 0) {
    const source = normalizeSequenceSource(rawSeq)

    console.log('[ProcessDetails] normalizeSequenceForForm INPUT:', { idx, rawSeq, sequence: source })

    const supplierProcesses = normalizeProcessSelections(
      normalizeSequenceArray(source, 'supplier_processes', 'supplierProcesses'),
    )

    const clientProcesses = normalizeProcessSelections(
      normalizeSequenceArray(source, 'client_processes', 'clientProcesses'),
    )

    const normalized = {
      id: Number(source.id) || undefined,
      uid: String(source.id || `seq-${idx}-${Date.now()}`),
      sequenceOrder: Number(source.sequence_order || source.sequenceOrder || idx + 1),
      supplierProcesses,
      inputs: normalizeSequenceText(source, 'input_description', 'inputs'),
      activities: normalizeSequenceText(source, 'activity_description', 'activities'),
      subActivities: normalizeSequenceArray(source, 'sub_activities', 'subActivities'),
      newSubActivity: '',
      outputs: normalizeSequenceText(source, 'output_description', 'outputs'),
      clientProcesses,
    }

    console.log('[ProcessDetails] normalizeSequenceForForm OUTPUT:', normalized)

    return normalized
  }

  function normalizeProcessSelections (values: string[]): string[] {
    const processById = new Map(
      (availableProcesses.value || []).map((p: any) => [String(p.id), String(p.name || '').trim()]),
    )
    const processNames = new Set(
      (availableProcesses.value || []).map((p: any) => String(p.name || '').trim().toLowerCase()),
    )

    const normalized = values.map(raw => {
      const value = String(raw || '').trim()
      if (!value) return ''
      if (processById.has(value)) {
        return processById.get(value) || value
      }
      if (processNames.has(value.toLowerCase())) {
        return value
      }
      return value
    }).filter(Boolean)

    return Array.from(new Set(normalized))
  }

  onMounted(async () => {
    await loadProcessesForSite()
    await loadNormsForSite()

    const processId = (route.params as { id?: string }).id
    if (processId && processId !== 'new') {
      await loadProcessDetails(processId)
    } else if (processId === 'new') {
      loadedObjectiveIds.value = []
      loadedRiskIds.value = []
      loadedOpportunityIds.value = []
      loadedOpportunityDescriptions.value = []
      const name = typeof route.query.name === 'string' ? route.query.name : ''
      const category = typeof route.query.category === 'string' ? route.query.category : ''
      if (name) {
        processForm.value.name = name
      }
      if (category) {
        const normalized = category === 'pilotage'
          ? 'management'
          : (category === 'operationnel'
            ? 'realization'
            : category)
        processForm.value.category = normalized
      }
    }
  })

  watch(() => authStore.currentSiteId, async () => {
    await loadProcessesForSite()
    await loadNormsForSite()
    processForm.value.risks = processForm.value.risks.map(risk => ({
      ...risk,
      norms: normalizeNormsForSelect(risk.norms || []),
    }))
    processForm.value.opportunities = processForm.value.opportunities.map(opportunity => ({
      ...opportunity,
      norms: normalizeNormsForSelect(opportunity.norms || []),
    }))
  })

  watch(selectedPilotId, value => {
    const normalized = normalizePilotId(value)
    if (normalized !== value) {
      selectedPilotId.value = normalized
    }
  })

  watch(selectedCopilotIds, value => {
    const normalized = Array.isArray(value)
      ? Array.from(new Set(value.map(Number).filter(v => Number.isFinite(v))))
      : []
    if (JSON.stringify(normalized) !== JSON.stringify(value)) {
      selectedCopilotIds.value = normalized
    }
  })

  function addSequence () {
    const order = processForm.value.sequences.length + 1
    processForm.value.sequences.push({
      uid: `new-${Date.now()}-${order}`,
      sequenceOrder: order,
      supplierProcesses: [],
      inputs: '',
      activities: '',
      subActivities: [],
      newSubActivity: '',
      outputs: '',
      clientProcesses: [],
    })
    openedSequencePanels.value = processForm.value.sequences.map((_, idx) => idx)
  }

  function removeSequence (index: number) {
    processForm.value.sequences.splice(index, 1)
    for (const [idx, seq] of processForm.value.sequences.entries()) {
      seq.sequenceOrder = idx + 1
    }
    openedSequencePanels.value = processForm.value.sequences.map((_, idx) => idx)
  }

  function moveSequence (index: number, direction: 'up' | 'down') {
    const targetIndex = direction === 'up' ? index - 1 : index + 1
    if (targetIndex < 0 || targetIndex >= processForm.value.sequences.length) return
    const temp = processForm.value.sequences[index]
    if (!temp) return
    processForm.value.sequences[index] = processForm.value.sequences[targetIndex]!
    processForm.value.sequences[targetIndex] = temp
    for (const [idx, seq] of processForm.value.sequences.entries()) {
      seq.sequenceOrder = idx + 1
    }
    openedSequencePanels.value = processForm.value.sequences.map((_, idx) => idx)
  }

  function addSubActivity (seq: { subActivities: string[], newSubActivity?: string }) {
    const value = String(seq.newSubActivity || '').trim()
    if (!value) return
    if (!Array.isArray(seq.subActivities)) {
      seq.subActivities = []
    }
    if (!seq.subActivities.includes(value)) {
      seq.subActivities.push(value)
    }
    seq.newSubActivity = ''
  }

  function removeSubActivity (seq: { subActivities: string[] }, subIndex: number) {
    if (!Array.isArray(seq.subActivities)) return
    seq.subActivities.splice(subIndex, 1)
  }

  function flushPendingSubActivities () {
    for (const seq of processForm.value.sequences) {
      const pending = String(seq.newSubActivity || '').trim()
      if (pending) {
        if (!Array.isArray(seq.subActivities)) {
          seq.subActivities = []
        }
        if (!seq.subActivities.includes(pending)) {
          seq.subActivities.push(pending)
        }
        seq.newSubActivity = ''
      }

      seq.subActivities = normalizeStringArray(seq.subActivities)
    }
  }

  function getSequenceProcessItems (seq: { supplierProcesses: string[], clientProcesses: string[] }) {
    const selected = [...(seq.supplierProcesses || []), ...(seq.clientProcesses || [])]
      .map(value => String(value || '').trim())
      .filter(Boolean)
      .map(name => ({ id: `saved-${name}`, name }))

    const base = (availableProcesses.value || [])
      .map(item => ({ id: item.id, name: String(item.name || '').trim() }))
      .filter(item => item.name)

    const merged = [...base]
    const seen = new Set(base.map(item => item.name.toLowerCase()))
    for (const item of selected) {
      const key = item.name.toLowerCase()
      if (!seen.has(key)) {
        merged.push(item)
        seen.add(key)
      }
    }

    return merged
  }

  function addObjective () {
    processForm.value.objectives.push({ name: '', indicator: '' })
  }

  function removeObjective (index: number) {
    processForm.value.objectives.splice(index, 1)
  }

  function addRisk () {
    processForm.value.risks.push({
      description: '',
      norms: [],
    })
  }

  function removeRisk (index: number) {
    processForm.value.risks.splice(index, 1)
  }

  function addOpportunity () {
    processForm.value.opportunities.push({
      description: '',
      norms: [],
    })
  }

  function removeOpportunity (index: number) {
    processForm.value.opportunities.splice(index, 1)
  }

  function handleBack () {
    router.push('/company/context/management-system')
  }

  function goToCartography () {
    const processId = (route.params as { id?: string }).id
    router.push({ path: '/company/processes/cartography', query: { process_id: processId } })
  }

  const detailsPreviewDialog = ref(false)
  const detailsPreviewBlob = ref<Blob | null>(null)
  const detailsPreviewFileType = ref<'pdf' | 'docx'>('pdf')
  const detailsPreviewFilename = ref('Fiche_Processus.pdf')
  const detailsPreviewTitle = ref('Prévisualisation — Fiche Processus')

  async function handleExportProcessDetails (format: 'pdf' | 'docx' = 'pdf') {
    const processId = (route.params as { id?: string }).id
    if (!processId || processId === 'new') {
      toast.error('Veuillez d\'abord enregistrer le processus')
      return
    }

    try {
      exporting.value = true
      detailsPreviewFileType.value = format
      const isPdf = format === 'pdf'
      const extension = isPdf ? 'pdf' : 'docx'
      const endpoint = isPdf
        ? `/processes/${processId}/export-pdf?preview=1`
        : `/processes/${processId}/export-docx`

      const response = await api.get(endpoint, {
        responseType: 'blob',
      })

      const generatedDocumentId = Number(response.headers?.['x-generated-document-id'] || 0)
      if (generatedDocumentId > 0) {
        lastExportedDocumentId.value = generatedDocumentId
        try {
          const docResponse = await api.get(`/documents/${generatedDocumentId}`)
          exportedDocumentCode.value = String(docResponse.data?.data?.code || docResponse.data?.code || '')
        } catch {
          // ignore
        }
      }

      const filename = `Fiche_Processus_${processForm.value.code || processForm.value.name || processId}.${extension}`
      const mimeType = isPdf
        ? 'application/pdf'
        : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'

      detailsPreviewBlob.value = new Blob([response.data], { type: mimeType })
      detailsPreviewFilename.value = filename
      detailsPreviewTitle.value = `Prévisualisation — Fiche Processus (${format.toUpperCase()})`
      detailsPreviewDialog.value = true
    } catch (err: any) {
      console.error('Export error:', err)
      toast.error('Erreur lors de l\'export de la fiche processus')
    } finally {
      exporting.value = false
    }
  }

  const {
    exporting, submittingForVerification, verificationSent,
    lastExportedDocumentId, exportedDocumentCode,
    previewDialog, previewBlobUrl, previewFilename,
    verifyDialog, ensureDraftReady, handlePreviewDraft, closePreview,
    handleDownloadDraft, openVerifyDialog, submitForVerification,
  } = useDocumentFlow(
    async () => {
      const processId = (route.params as { id?: string }).id
      if (!processId || processId === 'new') {
        toast.error('Veuillez d\'abord enregistrer le processus')
        return null
      }
      if (!generationContext.value) {
        generationContext.value = {
          document_type_catalog_id: 1,
          process_id: Number(processId),
          process_name: processForm.value.name,
          process_type: processForm.value.type || 'operationnel',
          process_abbreviation: processForm.value.abbreviation || '',
        }
      }
      const response = await api.post(`/processes/${processId}/generate-draft`, generationContext.value)
      const info = extractDocumentInfo(response.data)
      return info.id ? info as { id: number, code: string } : null
    },
    () => `Fiche_Processus_${processForm.value.name || (route.params as { id?: string }).id}_${new Date().toISOString().split('T')[0]}.pdf`,
  )

  watch(verificationSent, sent => {
    if (sent) { /* formulaire déjà sauvegardé, rien à faire */ }
  })

  function openGenerationDialog (action: 'draft' | 'preview' | 'download' | 'verify' = 'draft') {
    generationAction.value = action
    generationDialog.value = true
  }

  async function confirmGenerationConfig (context: GeneratedDocumentContext) {
    generationContext.value = context
    generationDialog.value = false
    if (generationAction.value === 'draft') {
      await generateDraft()
    } else if (generationAction.value === 'preview') {
      await handlePreviewDraft()
    } else if (generationAction.value === 'download') {
      await handleDownloadDraft()
    } else if (generationAction.value === 'verify') {
      const ready = await generateDraft()
      if (ready) {
        verifyDialog.value = true
      }
    }
  }

  function extractDocumentInfo (payload: any): { id: number | null, code: string } {
    const data = payload?.data ?? payload
    const attributes = data?.attributes ?? data
    const id = Number(data?.id || attributes?.id || 0) || null
    const code = String(attributes?.code || '')
    return { id, code }
  }

  async function persistObjectives (processId: number, isUpdate: boolean) {
    const persistenceErrors: string[] = []

    if (isUpdate) {
      const currentIds = new Set(
        processForm.value.objectives
          .map(o => o.id)
          .filter((id): id is number => typeof id === 'number'),
      )
      const toDelete = loadedObjectiveIds.value.filter(id => !currentIds.has(id))
      for (const objectiveId of toDelete) {
        try {
          await api.delete(`/processes/${processId}/objectives/${objectiveId}`)
        } catch (error) {
          console.warn('[ProcessDetails] Failed to delete removed objective:', objectiveId, error)
        }
      }
    }

    for (const obj of processForm.value.objectives) {
      if (!obj.name) continue

      try {
        if (obj.id) {
          await api.put(`/processes/${processId}/objectives/${obj.id}`, {
            title: obj.name,
            indicator_name: obj.indicator || obj.name,
            description: '',
            target_value: null,
            target_date: null,
          })
        } else {
          let indicatorId = obj.indicatorId
          if (!indicatorId) {
            const indicatorResponse = await api.post(`/processes/${processId}/indicators`, {
              name: obj.indicator || obj.name,
              type: 'performance',
              category: 'global',
              unit: '%',
            })
            indicatorId = indicatorResponse.data?.data?.id || indicatorResponse.data?.id
          }

          await api.post(`/processes/${processId}/objectives`, {
            title: obj.name,
            indicator_name: obj.indicator || obj.name,
            description: '',
            target_value: null,
            target_date: null,
            indicator_id: indicatorId || null,
          })
        }
      } catch (error) {
        console.warn('[ProcessDetails] Failed to persist objective:', obj.name, error)
        persistenceErrors.push(obj.name)
      }
    }

    if (persistenceErrors.length > 0) {
      throw new Error(`Impossible d'enregistrer ${persistenceErrors.length} objectif(s)`)
    }
  }

  async function persistRisks (processId: number, isUpdate: boolean) {
    if (isUpdate) {
      const currentIds = new Set(
        processForm.value.risks
          .map(r => r.id)
          .filter((id): id is number => typeof id === 'number'),
      )
      const toDelete = loadedRiskIds.value.filter(id => !currentIds.has(id))
      for (const riskId of toDelete) {
        try {
          await api.delete(`/risks-opportunities/${riskId}`)
        } catch (error) {
          console.warn('[ProcessDetails] Failed to delete removed risk:', riskId, error)
        }
      }
    }

    for (const risk of processForm.value.risks) {
      if (!risk.description) continue

      const payload = {
        type: 'risque',
        title: risk.description.slice(0, 255),
        description: risk.description,
        normes_iso: normalizeNormsForSelect(risk.norms || []),
        probabilite: 3,
        gravite: 3,
      }

      try {
        if (risk.id) {
          await api.put(`/risks-opportunities/${risk.id}`, payload)
        } else {
          const created = await api.post(`/processes/${processId}/risks-opportunities`, payload)
          const newId = Number(created.data?.data?.id || created.data?.id)
          if (Number.isFinite(newId)) {
            risk.id = newId
          }
        }
      } catch (error) {
        console.warn('[ProcessDetails] Failed to persist risk:', risk.description, error)
      }
    }

    loadedRiskIds.value = processForm.value.risks
      .map(r => r.id)
      .filter((id): id is number => typeof id === 'number')
  }

  async function handleSave () {
    saving.value = true
    try {
      flushPendingSubActivities()

      const storedSiteId = Number(localStorage.getItem('current_site_id'))
      const siteId = authStore.currentSiteId ?? (Number.isFinite(storedSiteId) ? storedSiteId : null)
      if (!siteId) {
        toast.error('Veuillez sélectionner un site.')
        return
      }

      console.log('[ProcessDetails] BEFORE MAPPING - processForm.value.sequences:', JSON.stringify(processForm.value.sequences, null, 2))

      const category = processForm.value.category === 'management'
        ? 'pilotage'
        : (processForm.value.category === 'realization'
          ? 'operationnel'
          : processForm.value.category)

      const payload: any = {
        site_id: siteId,
        title: processForm.value.name,
        category,
        purpose: processForm.value.purpose,
        observation: processForm.value.observation || null,
        pilot_id: selectedPilotId.value || null,
        copilot_ids: selectedCopilotIds.value,
        copilot_id: selectedCopilotIds.value[0] || null,
        ressources: {
          human: processForm.value.resources.human,
          technological: processForm.value.resources.technological,
          material: processForm.value.resources.material,
          documentary: processForm.value.resources.documentary,
        },
        sequences: processForm.value.sequences.map((seq, idx) => {
          const mapped = {
            sequence_order: idx + 1,
            input_description: seq.inputs || '',
            activity_description: seq.activities || '',
            sub_activities: normalizeStringArray(seq.subActivities).filter(Boolean),
            output_description: seq.outputs || '',
            supplier_processes: Array.isArray(seq.supplierProcesses) ? seq.supplierProcesses.filter(Boolean) : [],
            client_processes: Array.isArray(seq.clientProcesses) ? seq.clientProcesses.filter(Boolean) : [],
          }
          console.log('[ProcessDetails] Mapping sequence', idx, ':', {
            original: seq,
            mapped,
            inputs: seq.inputs,
            activities: seq.activities,
            outputs: seq.outputs,
          })
          return mapped
        }),
      }

      console.log('[ProcessDetails] Final payload sequences:', JSON.stringify(payload.sequences, null, 2))

      const processId = (route.params as { id?: string }).id
      if (processId && processId !== 'new') {
        await api.put(`/processes/${processId}`, payload)
        await persistObjectives(Number(processId), true)
        await persistRisks(Number(processId), true)
        await persistProcessOpportunities(Number(processId), true)
      } else {
        const created = await api.post('/processes', payload)
        const newId = created?.data?.id || created?.data?.data?.id
        if (newId) {
          await persistObjectives(Number(newId), false)
          await persistRisks(Number(newId), false)
          await persistProcessOpportunities(Number(newId), false)
        }
      }

      toast.success('Processus enregistré avec succès.')
      setTimeout(() => {
        router.push('/company/context/management-system')
      }, 1000)
    } catch (error) {
      console.error('Erreur sauvegarde:', error)
      toast.error('Erreur lors de l\'enregistrement.')
    } finally {
      saving.value = false
    }
  }

  async function persistProcessOpportunities (processId: number, onlyNew: boolean): Promise<void> {
    if (onlyNew) {
      const currentIds = new Set(
        processForm.value.opportunities
          .map(o => o.id)
          .filter((id): id is number => typeof id === 'number'),
      )
      const toDelete = loadedOpportunityIds.value.filter(id => !currentIds.has(id))
      for (const opportunityId of toDelete) {
        try {
          await api.delete(`/risks-opportunities/${opportunityId}`)
        } catch (error) {
          console.warn('[ProcessDetails] Failed to delete removed opportunity:', opportunityId, error)
        }
      }
    }

    for (const opportunity of processForm.value.opportunities) {
      const description = String(opportunity.description || '').trim()
      if (!description) continue

      const payload = {
        type: 'opportunite',
        title: description.slice(0, 255),
        description,
        normes_iso: normalizeNormsForSelect(opportunity.norms || []),
        probabilite: 3,
        gravite: 3,
      }

      if (opportunity.id) {
        await api.put(`/risks-opportunities/${opportunity.id}`, payload)
      } else {
        if (onlyNew && loadedOpportunityDescriptions.value.includes(normalizeText(description))) {
          continue
        }
        const created = await api.post(`/processes/${processId}/risks-opportunities`, payload)
        const newId = Number(created.data?.data?.id || created.data?.id)
        if (Number.isFinite(newId)) {
          opportunity.id = newId
        }
      }
    }

    loadedOpportunityDescriptions.value = processForm.value.opportunities.map(o => normalizeText(o.description)).filter(Boolean)
    loadedOpportunityIds.value = processForm.value.opportunities
      .map(o => o.id)
      .filter((id): id is number => typeof id === 'number')
  }
</script>

<style scoped>
:deep(.stepper-container) {
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: relative;
}

:deep(.step-item) {
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  flex: 1;
  cursor: pointer;
  transition: all 0.3s ease;
}

:deep(.step-item:hover .step-circle) {
  transform: scale(1.1);
}

:deep(.step-circle) {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: bold;
  font-size: 20px;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 2;
}

:deep(.step-item.active .step-circle) {
  transform: scale(1.2);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
}

:deep(.step-number) {
  color: white;
  font-weight: bold;
}

:deep(.step-label) {
  margin-top: 12px;
  font-size: 14px;
  font-weight: 600;
  color: #64748b;
  transition: all 0.3s ease;
}

:deep(.step-item.active .step-label) {
  color: #1e293b;
  font-size: 15px;
}

:deep(.step-line) {
  position: absolute;
  top: 28px;
  left: 50%;
  width: 100%;
  height: 4px;
  background: #e2e8f0;
  z-index: 1;
  transition: all 0.3s ease;
}

:deep(.step-line.filled) {
  background: #22c55e;
}

.sequence-card,
.objective-card,
.resource-card {
  transition: all 0.2s ease;
  background: white;
}

.sequence-card:hover,
.objective-card:hover,
.resource-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
}

:deep(.navigation-card) {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  border: 2px solid #cbd5e1;
}

.sequence-stat-card {
  border: 1px solid #e2e8f0;
  background: #ffffffcc;
  backdrop-filter: blur(6px);
}

.sequence-panels :deep(.v-expansion-panel-title) {
  min-height: 72px;
}

.sequence-editor-grid {
  display: grid;
  gap: 12px;
  grid-template-columns: repeat(1, minmax(0, 1fr));
}

.sequence-block {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 12px;
}

.sequence-block-title {
  display: flex;
  align-items: center;
  font-size: 12px;
  font-weight: 700;
  color: #334155;
  margin-bottom: 8px;
  letter-spacing: 0.02em;
  text-transform: uppercase;
}

.sequence-block-activity {
  border-color: #bfdbfe;
  background: linear-gradient(180deg, #eff6ff 0%, #f8fafc 100%);
}

.subactivity-box {
  border: 1px dashed #93c5fd;
  border-radius: 10px;
  padding: 10px;
  background: #ffffff;
}

.recap-multiline {
  white-space: pre-wrap;
  word-break: break-word;
}

.recap-cell-text {
  min-width: 220px;
  white-space: normal;
  word-break: break-word;
  vertical-align: top;
}

@media (min-width: 960px) {
  .sequence-editor-grid {
    grid-template-columns: 1.1fr 1.2fr 1.7fr 1.2fr 1.1fr;
  }
}
</style>
