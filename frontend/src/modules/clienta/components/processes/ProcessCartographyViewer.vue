<template>
  <div class="process-cartography-viewer">
    <!-- Top Action Bar (if not hidden) -->
    <v-card v-if="!hideHeader" class="mb-4" elevation="0" rounded="xl" style="background: white; border: 1.5px solid #e2e8f0;">
      <v-card-text class="pa-4">
        <div class="d-flex flex-wrap justify-space-between align-center gap-3">
          <div class="d-flex align-center gap-3">
            <v-avatar color="primary" rounded="lg" size="44">
              <v-icon color="white" size="26">mdi-map-legend</v-icon>
            </v-avatar>
            <div>
              <div class="text-h6 font-weight-bold text-slate-800">
                Cartographie des Processus & Matrice d'Interactions
              </div>
              <div class="text-caption text-medium-emphasis">
                Système de Management de la Qualité (ISO 9001:2015 §4.4) — {{ enterpriseName }}
              </div>
              <div v-if="cartographyDocument" class="d-flex align-center gap-2 mt-1">
                <v-chip size="x-small" color="primary" label font-weight-bold>
                  <v-icon start size="12">mdi-file-certificate-outline</v-icon>
                  Réf Doc : {{ cartographyDocument.code }} (v{{ cartographyDocument.version || '1.0' }})
                </v-chip>
                <v-chip size="x-small" :color="getWorkflowColor(cartographyDocument.workflow_status)" variant="tonal">
                  {{ getWorkflowLabel(cartographyDocument.workflow_status) }}
                </v-chip>
              </div>
            </div>
          </div>

          <div class="d-flex flex-wrap align-center gap-2">
            <!-- Site Selector if sites available -->
            <v-select
              v-if="sites.length > 0"
              v-model="currentSiteId"
              density="compact"
              hide-details
              item-title="name"
              item-value="id"
              :items="sites"
              label="Site"
              prepend-inner-icon="mdi-domain"
              style="min-width: 180px; max-width: 220px;"
              variant="outlined"
              @update:model-value="loadData"
            />

            <!-- Toggle Interactions -->
            <v-btn
              :color="showInteractions ? 'primary' : 'grey-darken-1'"
              density="comfortable"
              prepend-icon="mdi-vector-polyline"
              :variant="showInteractions ? 'tonal' : 'outlined'"
              @click="showInteractions = !showInteractions"
            >
              {{ showInteractions ? 'Masquer interactions' : 'Afficher interactions' }}
            </v-btn>

            <!-- Codifier / Générer document SMQ -->
            <v-btn
              color="primary"
              density="comfortable"
              prepend-icon="mdi-file-cog-outline"
              variant="tonal"
              @click="openGenerationDialog('draft')"
            >
              {{ cartographyDocument ? 'Recodifier' : 'Codifier le Document SMQ' }}
            </v-btn>

            <!-- Soumettre pour vérification -->
            <v-btn
              v-if="cartographyDocument && cartographyDocument.workflow_status === 'draft'"
              color="amber-darken-3"
              density="comfortable"
              prepend-icon="mdi-check-decagram-outline"
              variant="flat"
              @click="verifyDialog = true"
            >
              Vérifier
            </v-btn>

            <!-- Export Modal Trigger (RT-01) with PDF & DOCX options -->
            <v-menu v-if="showExportButton">
              <template #activator="{ props: menuProps }">
                <v-btn
                  color="indigo-darken-1"
                  density="comfortable"
                  prepend-icon="mdi-file-download-outline"
                  append-icon="mdi-chevron-down"
                  variant="flat"
                  v-bind="menuProps"
                >
                  Télécharger Cartographie
                </v-btn>
              </template>
              <v-list density="compact" rounded="lg">
                <v-list-item
                  prepend-icon="mdi-file-pdf-box"
                  title="Format PDF (.pdf)"
                  subtitle="Tableau officiel & Schéma"
                  @click="openExportPreview('pdf')"
                />
                <v-list-item
                  prepend-icon="mdi-file-word"
                  title="Format Word (.docx)"
                  subtitle="Canevas modifiable 4 colonnes"
                  @click="openExportPreview('docx')"
                />
                <v-divider class="my-1" />
                <v-list-item
                  prepend-icon="mdi-file-image"
                  title="Image PNG (.png)"
                  subtitle="Schéma graphique haute définition"
                  @click="downloadDiagramImage('png')"
                />
                <v-list-item
                  prepend-icon="mdi-svg"
                  title="Vecteur SVG (.svg)"
                  subtitle="Schéma vectoriel éditable"
                  @click="downloadDiagramImage('svg')"
                />
              </v-list>
            </v-menu>

            <!-- Fullscreen -->
            <v-btn
              density="comfortable"
              icon="mdi-fullscreen"
              variant="outlined"
              @click="toggleFullscreen"
            />
          </div>
        </div>

        <!-- Filter tabs by ISO category -->
        <div class="mt-4 pt-3 border-t d-flex flex-wrap justify-space-between align-center gap-2">
          <v-tabs
            v-model="activeCategoryFilter"
            color="primary"
            density="compact"
            hide-slider
          >
            <v-tab class="text-none font-weight-bold" rounded="lg" value="all">
              <v-icon start size="18">mdi-view-dashboard-outline</v-icon>
              Tous les processus ({{ allProcesses.length }})
            </v-tab>
            <v-tab class="text-none font-weight-bold text-purple-darken-2" rounded="lg" value="management">
              <v-icon color="purple" start size="18">mdi-chart-line</v-icon>
              Management ({{ managementProcesses.length }})
            </v-tab>
            <v-tab class="text-none font-weight-bold text-green-darken-2" rounded="lg" value="realization">
              <v-icon color="green" start size="18">mdi-cog-sync</v-icon>
              Réalisation ({{ realizationProcesses.length }})
            </v-tab>
            <v-tab class="text-none font-weight-bold text-indigo-darken-2" rounded="lg" value="support">
              <v-icon color="indigo" start size="18">mdi-lifebuoy</v-icon>
              Support ({{ supportProcesses.length }})
            </v-tab>
          </v-tabs>

          <v-btn-toggle
            v-model="displayMode"
            color="primary"
            density="compact"
            mandatory
            variant="outlined"
          >
            <v-btn size="small" value="diagram">
              <v-icon start size="16">mdi-graph-outline</v-icon>
              Schéma interactif
            </v-btn>
            <v-btn size="small" value="table">
              <v-icon start size="16">mdi-table</v-icon>
              Tableau officiel
            </v-btn>
          </v-btn-toggle>
        </div>
      </v-card-text>
    </v-card>

    <!-- Loading State -->
    <v-card v-if="loading" class="pa-12 text-center" rounded="xl" variant="outlined">
      <v-progress-circular color="primary" indeterminate size="52" />
      <div class="mt-4 text-subtitle-1 font-weight-medium text-slate-600">
        Chargement de la cartographie et des interactions...
      </div>
    </v-card>

    <div v-else ref="cartographyRoot">
      <!-- 1. FOCUSED PROCESS SIPOC / INTERACTION CARD (if focused process exists) -->
      <v-card
        v-if="activeSelectedProcess"
        class="mb-4 focused-process-card"
        elevation="2"
        rounded="xl"
      >
        <v-card-title class="pa-4 d-flex align-center justify-space-between bg-slate-50 border-b">
          <div class="d-flex align-center gap-2">
            <v-chip :color="getCategoryColor(activeSelectedProcess.normalized_category)" font-weight-bold size="small">
              {{ getCategoryLabel(activeSelectedProcess.normalized_category) }}
            </v-chip>
            <span class="text-h6 font-weight-bold">{{ activeSelectedProcess.code }} — {{ activeSelectedProcess.title }}</span>
          </div>

          <div class="d-flex align-center gap-2">
            <v-btn
              color="primary"
              density="compact"
              prepend-icon="mdi-eye"
              variant="tonal"
              @click="router.push(`/company/context/management-system/${activeSelectedProcess.id}`)"
            >
              Fiche détaillée
            </v-btn>
            <v-btn
              density="compact"
              icon="mdi-close"
              variant="text"
              @click="clearSelectedProcess"
            />
          </div>
        </v-card-title>

        <v-card-text class="pa-4">
          <v-row>
            <!-- 1. En amont / Fournisseurs -->
            <v-col cols="12" md="4">
              <div class="interaction-col inbound-col pa-3 rounded-lg fill-height">
                <div class="d-flex align-center gap-2 mb-2 font-weight-bold text-blue-darken-3">
                  <v-icon color="blue-darken-2" size="20">mdi-arrow-down-left-bold</v-icon>
                  <span>1. EN AMONT (Fournisseurs & Entrées)</span>
                </div>
                <div class="text-caption text-slate-600 mb-3">
                  Données et livrables nécessaires au démarrage du processus :
                </div>

                <div v-if="upstreamProcesses.length > 0" class="d-flex flex-column gap-2">
                  <div
                    v-for="proc in upstreamProcesses"
                    :key="'up-' + proc.id"
                    class="partner-box partner-inbound pa-2 rounded cursor-pointer"
                    @click="selectProcess(proc)"
                  >
                    <div class="d-flex justify-space-between align-center">
                      <span class="font-weight-bold text-caption">{{ proc.code }}</span>
                      <v-chip size="x-small" variant="flat">{{ proc.title }}</v-chip>
                    </div>
                    <div class="text-caption text-slate-500 mt-1">
                      Pilote : {{ proc.pilot_name }}
                    </div>
                  </div>
                </div>
                <div v-else class="text-caption text-medium-emphasis font-italic pa-2">
                  Entrées directes : Exigences clients, stratégie et parties intéressées
                </div>

                <v-divider class="my-2" />
                <div class="text-caption text-slate-700">
                  <strong>Entrées types :</strong> {{ activeSelectedProcess.inputs_summary || 'Exigences et intrants' }}
                </div>
              </div>
            </v-col>

            <!-- 2. Cœur du Processus -->
            <v-col cols="12" md="4">
              <div class="interaction-col center-col pa-3 rounded-lg fill-height text-center">
                <div class="d-flex align-center justify-center gap-2 mb-2 font-weight-bold text-emerald-800">
                  <v-icon color="success" size="20">mdi-cog-sync</v-icon>
                  <span>2. ACTIVITÉS DU PROCESSUS</span>
                </div>

                <div class="pa-3 bg-white rounded-lg border shadow-xs mb-3 text-left">
                  <div class="text-subtitle-2 font-weight-bold mb-1">
                    {{ activeSelectedProcess.title }}
                  </div>
                  <div class="text-caption text-slate-600 mb-2">
                    <strong>Finalité :</strong> {{ activeSelectedProcess.finalite || 'Atteinte des objectifs du processus et satisfaction client.' }}
                  </div>
                  <div class="text-caption text-slate-600 mb-2">
                    <strong>Pilote :</strong> {{ activeSelectedProcess.pilot_name }} ({{ activeSelectedProcess.department || 'Qualité' }})
                  </div>
                  <div class="text-caption text-slate-600">
                    <strong>Activités clés :</strong> {{ activeSelectedProcess.activities_summary || 'Exécution des opérations et contrôles.' }}
                  </div>
                </div>

                <div class="d-flex justify-center gap-2">
                  <v-chip color="info" size="small" variant="tonal">
                    {{ activeSelectedProcess.indicators_count }} Indicateur(s)
                  </v-chip>
                  <v-chip color="warning" size="small" variant="tonal">
                    {{ activeSelectedProcess.risks_count }} Risque(s)
                  </v-chip>
                </div>
              </div>
            </v-col>

            <!-- 3. En aval / Clients -->
            <v-col cols="12" md="4">
              <div class="interaction-col outbound-col pa-3 rounded-lg fill-height">
                <div class="d-flex align-center gap-2 mb-2 font-weight-bold text-green-darken-3">
                  <v-icon color="green-darken-2" size="20">mdi-arrow-top-right-bold</v-icon>
                  <span>3. EN AVAL (Clients & Sorties)</span>
                </div>
                <div class="text-caption text-slate-600 mb-3">
                  Destinataires et bénéficiaires des livrables du processus :
                </div>

                <div v-if="downstreamProcesses.length > 0" class="d-flex flex-column gap-2">
                  <div
                    v-for="proc in downstreamProcesses"
                    :key="'down-' + proc.id"
                    class="partner-box partner-outbound pa-2 rounded cursor-pointer"
                    @click="selectProcess(proc)"
                  >
                    <div class="d-flex justify-space-between align-center">
                      <span class="font-weight-bold text-caption">{{ proc.code }}</span>
                      <v-chip size="x-small" variant="flat">{{ proc.title }}</v-chip>
                    </div>
                    <div class="text-caption text-slate-500 mt-1">
                      Pilote : {{ proc.pilot_name }}
                    </div>
                  </div>
                </div>
                <div v-else class="text-caption text-medium-emphasis font-italic pa-2">
                  Sorties directes : Livrables conformes aux clients et amélioration SMQ
                </div>

                <v-divider class="my-2" />
                <div class="text-caption text-slate-700">
                  <strong>Sorties types :</strong> {{ activeSelectedProcess.outputs_summary || 'Livrables et résultats conformes' }}
                </div>
              </div>
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>

      <!-- 2. INTERACTIVE DIAGRAM VIEW (ISO 9001:2015 §4.4) -->
      <v-card
        v-show="displayMode === 'diagram'"
        class="cartography-canvas-card pa-4 mb-4"
        elevation="0"
        rounded="xl"
      >
        <!-- Diagram Top Bar with direct image download buttons -->
        <div class="d-flex flex-wrap justify-space-between align-center mb-4 px-1 gap-2 border-b pb-3">
          <div class="text-subtitle-2 font-weight-bold text-slate-800 d-flex align-center gap-2">
            <v-icon color="primary" size="20">mdi-sitemap-outline</v-icon>
            <span>Schéma dynamique des flux & Interactions SMQ (ISO 9001:2015 §4.4)</span>
          </div>
          <div class="d-flex align-center gap-2">
            <v-btn
              color="primary"
              density="compact"
              prepend-icon="mdi-file-image"
              variant="tonal"
              @click="downloadDiagramImage('png')"
            >
              Télécharger l'image (PNG)
            </v-btn>
            <v-btn
              color="grey-darken-3"
              density="compact"
              prepend-icon="mdi-svg"
              variant="outlined"
              @click="downloadDiagramImage('svg')"
            >
              Télécharger en SVG
            </v-btn>
          </div>
        </div>

        <div class="cartography-flow-wrapper">
          <!-- Left Input Column: Client & Stakeholders Requirements -->
          <div class="flow-pillar pillar-input">
            <div class="pillar-header">
              <v-icon color="blue" size="28">mdi-account-group-outline</v-icon>
              <div class="pillar-title">ENTRÉES</div>
              <div class="pillar-subtitle">Exigences & Attentes</div>
            </div>
            <div class="pillar-body">
              <div class="input-badge">
                <v-icon color="blue-darken-2" size="14">mdi-check-circle</v-icon>
                <span>Clients & Marchés</span>
              </div>
              <div class="input-badge">
                <v-icon color="blue-darken-2" size="14">mdi-check-circle</v-icon>
                <span>Parties Intéressées</span>
              </div>
              <div class="input-badge">
                <v-icon color="blue-darken-2" size="14">mdi-check-circle</v-icon>
                <span>Normes ISO & Lois</span>
              </div>
            </div>
            <div class="flow-arrow-right">
              <v-icon color="blue" size="32">mdi-chevron-double-right</v-icon>
            </div>
          </div>

          <!-- Central Lanes: Management / Realization / Support -->
          <div class="flow-lanes">
            <!-- 1. MANAGEMENT LANE (HAUT) -->
            <div
              v-if="shouldShowCategory('management')"
              class="lane-container lane-mgmt mb-2"
            >
              <div class="lane-tag tag-mgmt">
                <v-icon color="purple-darken-1" size="16">mdi-chart-line</v-icon>
                <span>PROCESSUS DE MANAGEMENT / PILOTAGE ({{ managementProcesses.length }})</span>
              </div>

              <div class="lane-grid">
                <div
                  v-for="proc in managementProcesses"
                  :key="proc.id"
                  class="process-node node-mgmt"
                  :class="{
                    'node-active': isProcessActive(proc),
                    'node-upstream': isProcessUpstream(proc),
                    'node-downstream': isProcessDownstream(proc),
                  }"
                  @click="selectProcess(proc)"
                >
                  <div class="node-header">
                    <span class="node-code">{{ proc.code }}</span>
                    <v-badge
                      v-if="isProcessUpstream(proc)"
                      color="blue"
                      content="Amont"
                      inline
                    />
                    <v-badge
                      v-else-if="isProcessDownstream(proc)"
                      color="green"
                      content="Aval"
                      inline
                    />
                  </div>
                  <div class="node-title">{{ proc.title }}</div>
                  <div class="node-footer">
                    <v-icon size="12">mdi-account</v-icon>
                    <span>{{ proc.pilot_name }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Interaction link: Management -> Realization -->
            <div
              v-if="shouldShowCategory('management') && shouldShowCategory('realization') && showInteractions"
              class="interlane-interaction interlane-mgmt-to-real mb-2"
            >
              <div class="interlane-line" />
              <div class="interlane-pill pill-mgmt-real">
                <v-icon color="purple-darken-2" size="14">mdi-arrow-down-bold</v-icon>
                <span>Orientations, Politiques, Objectifs & Décisions de pilotage</span>
                <v-icon color="purple-darken-2" size="14">mdi-arrow-down-bold</v-icon>
              </div>
              <div class="interlane-line" />
            </div>

            <!-- 2. REALIZATION LANE (CENTRE) -->
            <div
              v-if="shouldShowCategory('realization')"
              class="lane-container lane-real mb-2"
            >
              <div class="lane-tag tag-real">
                <v-icon color="green-darken-1" size="16">mdi-cog-sync</v-icon>
                <span>PROCESSUS DE RÉALISATION / CHAÎNE DE VALEUR ({{ realizationProcesses.length }})</span>
              </div>

              <div class="lane-grid realization-flow">
                <template v-for="(proc, index) in realizationProcesses" :key="proc.id">
                  <div
                    class="process-node node-real"
                    :class="{
                      'node-active': isProcessActive(proc),
                      'node-upstream': isProcessUpstream(proc),
                      'node-downstream': isProcessDownstream(proc),
                    }"
                    @click="selectProcess(proc)"
                  >
                    <div class="node-header">
                      <span class="node-code">{{ proc.code }}</span>
                      <v-badge
                        v-if="isProcessUpstream(proc)"
                        color="blue"
                        content="Amont"
                        inline
                      />
                      <v-badge
                        v-else-if="isProcessDownstream(proc)"
                        color="green"
                        content="Aval"
                        inline
                      />
                    </div>
                    <div class="node-title">{{ proc.title }}</div>
                    <div class="node-footer">
                      <v-icon size="12">mdi-account</v-icon>
                      <span>{{ proc.pilot_name }}</span>
                    </div>
                  </div>

                  <!-- Flow connector between sequential operational processes -->
                  <div
                    v-if="index < realizationProcesses.length - 1"
                    class="chain-arrow"
                  >
                    <v-icon color="green" size="20">mdi-arrow-right-bold</v-icon>
                  </div>
                </template>
              </div>
            </div>

            <!-- Interaction link: Support <-> Realization -->
            <div
              v-if="shouldShowCategory('support') && shouldShowCategory('realization') && showInteractions"
              class="interlane-interaction interlane-sup-to-real mb-2"
            >
              <div class="interlane-line" />
              <div class="interlane-pill pill-sup-real">
                <v-icon color="indigo-darken-2" size="14">mdi-arrow-up-bold</v-icon>
                <span>Mise à disposition des Ressources, Moyens, Compétences & Outils SMQ</span>
                <v-icon color="indigo-darken-2" size="14">mdi-arrow-up-bold</v-icon>
              </div>
              <div class="interlane-line" />
            </div>

            <!-- 3. SUPPORT LANE (BAS) -->
            <div
              v-if="shouldShowCategory('support')"
              class="lane-container lane-sup"
            >
              <div class="lane-tag tag-sup">
                <v-icon color="indigo-darken-1" size="16">mdi-lifebuoy</v-icon>
                <span>PROCESSUS DE SUPPORT / RESSOURCES ({{ supportProcesses.length }})</span>
              </div>

              <div class="lane-grid">
                <div
                  v-for="proc in supportProcesses"
                  :key="proc.id"
                  class="process-node node-sup"
                  :class="{
                    'node-active': isProcessActive(proc),
                    'node-upstream': isProcessUpstream(proc),
                    'node-downstream': isProcessDownstream(proc),
                  }"
                  @click="selectProcess(proc)"
                >
                  <div class="node-header">
                    <span class="node-code">{{ proc.code }}</span>
                    <v-badge
                      v-if="isProcessUpstream(proc)"
                      color="blue"
                      content="Amont"
                      inline
                    />
                    <v-badge
                      v-else-if="isProcessDownstream(proc)"
                      color="green"
                      content="Aval"
                      inline
                    />
                  </div>
                  <div class="node-title">{{ proc.title }}</div>
                  <div class="node-footer">
                    <v-icon size="12">mdi-account</v-icon>
                    <span>{{ proc.pilot_name }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Inter-process system feedback line -->
            <div v-if="showInteractions" class="system-feedback-banner mt-3">
              <v-icon color="amber-darken-2" size="18">mdi-sync</v-icon>
              <span>Boucle de rétroaction ISO 9001 : Données de surveillance & Indicateurs &#10142; Revues de direction &#10142; Amélioration continue</span>
            </div>
          </div>

          <!-- Right Output Column: Customer Satisfaction & Delivered Value -->
          <div class="flow-pillar pillar-output">
            <div class="flow-arrow-left">
              <v-icon color="green" size="32">mdi-chevron-double-right</v-icon>
            </div>
            <div class="pillar-header">
              <v-icon color="green" size="28">mdi-emoticon-happy-outline</v-icon>
              <div class="pillar-title">SORTIES</div>
              <div class="pillar-subtitle">Satisfaction Client</div>
            </div>
            <div class="pillar-body">
              <div class="output-badge">
                <v-icon color="green-darken-2" size="14">mdi-star-check</v-icon>
                <span>Produits & Services</span>
              </div>
              <div class="output-badge">
                <v-icon color="green-darken-2" size="14">mdi-star-check</v-icon>
                <span>Clients Satisfaits</span>
              </div>
              <div class="output-badge">
                <v-icon color="green-darken-2" size="14">mdi-star-check</v-icon>
                <span>Conformité & Valeur</span>
              </div>
            </div>
          </div>
        </div>
      </v-card>

      <!-- 3. OFFICIAL SUMMARY TABLE VIEW (Compliant with Word Model) -->
      <v-card class="official-table-card" rounded="xl" variant="outlined">
        <v-card-title class="pa-4 d-flex flex-wrap justify-space-between align-center gap-3 bg-slate-50 border-b">
          <div class="d-flex align-center gap-2">
            <v-icon color="primary" size="22">mdi-table-headers-eye</v-icon>
            <span class="text-subtitle-1 font-weight-bold">
              Tableau des Processus du Système de Management
            </span>
          </div>

          <div class="d-flex align-center gap-2">
            <!-- Column visibility selector menu with checkboxes -->
            <v-menu :close-on-content-click="false">
              <template #activator="{ props: menuProps }">
                <v-btn
                  v-bind="menuProps"
                  color="grey-darken-2"
                  density="compact"
                  prepend-icon="mdi-view-column-outline"
                  variant="outlined"
                >
                  Colonnes ({{ visibleColumnCount }}/{{ tableColumns.length }})
                </v-btn>
              </template>
              <v-list class="py-2" density="compact" min-width="260">
                <v-list-item-title class="px-4 py-1 text-caption font-weight-bold text-uppercase text-medium-emphasis">
                  Colonnes à afficher
                </v-list-item-title>
                <v-list-item
                  v-for="col in tableColumns"
                  :key="col.key"
                  class="px-2"
                  density="compact"
                  @click="toggleCol(col)"
                >
                  <template #prepend>
                    <v-checkbox-btn
                      v-model="col.visible"
                      class="mr-2"
                      color="primary"
                      density="compact"
                      :disabled="col.visible && visibleColumnCount <= 1"
                    />
                  </template>
                  <v-list-item-title class="text-body-2">{{ col.label }}</v-list-item-title>
                </v-list-item>
                <v-divider class="my-1" />
                <div class="px-3 pt-1">
                  <v-btn
                    block
                    color="primary"
                    size="x-small"
                    variant="text"
                    @click="resetTableColumns"
                  >
                    Tout afficher
                  </v-btn>
                </div>
              </v-list>
            </v-menu>

            <v-text-field
              v-model="searchQuery"
              clearable
              density="compact"
              hide-details
              placeholder="Rechercher par nom, code ou responsable..."
              prepend-inner-icon="mdi-magnify"
              style="min-width: 260px;"
              variant="outlined"
            />
          </div>
        </v-card-title>

        <v-card-text class="pa-0">
          <v-table hover>
            <thead>
              <tr class="bg-slate-100">
                <th v-if="isColVisible('name')" class="font-weight-bold text-slate-800" style="width: 25%;">PROPOSITION DE NOM PROCESSUS</th>
                <th v-if="isColVisible('activities')" class="font-weight-bold text-slate-800" style="width: 32%;">ACTIVITÉS PRINCIPALES</th>
                <th v-if="isColVisible('observation')" class="font-weight-bold text-slate-800" style="width: 20%;">OBSERVATION</th>
                <th v-if="isColVisible('responsible')" class="font-weight-bold text-slate-800" style="width: 17%;">RESPONSABLE PAR DÉPARTEMENT</th>
                <th v-if="isColVisible('actions')" class="font-weight-bold text-slate-800 text-center" style="width: 6%;">ACTIONS</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="proc in filteredProcessesTable"
                :key="proc.id"
                :class="{ 'bg-blue-lighten-5': isProcessActive(proc) }"
              >
                <!-- 1. PROPOSITION DE NOM PROCESSUS -->
                <td v-if="isColVisible('name')" class="align-top py-3">
                  <div class="d-flex align-start gap-2">
                    <v-avatar :color="getCategoryColor(proc.normalized_category)" class="mt-1" size="28" variant="tonal">
                      <v-icon size="16">{{ getCategoryIcon(proc.normalized_category) }}</v-icon>
                    </v-avatar>
                    <div>
                      <div class="font-weight-bold text-slate-900 cursor-pointer text-hover-primary text-uppercase" @click="selectProcess(proc)">
                        {{ proc.title }}
                      </div>
                      <div class="d-flex align-center gap-1 mt-1">
                        <v-chip :color="getCategoryColor(proc.normalized_category)" font-weight-bold label size="x-small">
                          {{ getCategoryLabel(proc.normalized_category) }}
                        </v-chip>
                        <span class="text-caption text-slate-500 font-mono">
                          {{ proc.code }}
                        </span>
                      </div>
                    </div>
                  </div>
                </td>

                <!-- 2. ACTIVITÉS PRINCIPALES -->
                <td v-if="isColVisible('activities')" class="align-top py-3">
                  <div v-if="proc.activities_summary" class="text-body-2 text-slate-800" style="white-space: pre-line;">
                    {{ proc.activities_summary }}
                  </div>
                  <div v-else-if="proc.finalite" class="text-body-2 text-slate-700 font-italic">
                    {{ proc.finalite }}
                  </div>
                  <div v-else class="text-caption text-slate-400 font-italic">
                    Non renseigné
                  </div>
                </td>

                <!-- 3. OBSERVATION -->
                <td v-if="isColVisible('observation')" class="align-top py-3">
                  <div v-if="proc.observation" class="text-body-2 text-slate-800 bg-amber-lighten-5 pa-2 rounded border border-amber-lighten-3 d-flex align-start justify-space-between gap-1">
                    <span>{{ proc.observation }}</span>
                    <v-btn
                      class="flex-shrink-0"
                      color="amber-darken-3"
                      density="compact"
                      icon="mdi-pencil-outline"
                      size="x-small"
                      title="Modifier l'observation"
                      variant="text"
                      @click="openEditObservation(proc)"
                    />
                  </div>
                  <div v-else class="d-flex align-center gap-1">
                    <span class="text-caption text-slate-400 font-italic">—</span>
                    <v-btn
                      color="grey-darken-1"
                      density="compact"
                      icon="mdi-plus"
                      size="x-small"
                      title="Ajouter une observation"
                      variant="text"
                      @click="openEditObservation(proc)"
                    />
                  </div>
                </td>

                <!-- 4. RESPONSABLE PAR DÉPARTEMENT -->
                <td v-if="isColVisible('responsible')" class="align-top py-3">
                  <div class="font-weight-bold text-slate-800">
                    {{ proc.responsible_display || proc.pilot_name || 'Non défini' }}
                  </div>
                  <div v-if="proc.department" class="text-caption text-slate-500 mt-1">
                    <v-icon class="mr-1" size="12">mdi-domain</v-icon>{{ proc.department }}
                  </div>
                </td>

                <!-- 5. ACTIONS -->
                <td v-if="isColVisible('actions')" class="align-top py-3 text-center">
                  <div class="d-flex justify-center gap-1">
                    <v-btn
                      color="amber-darken-3"
                      density="comfortable"
                      icon="mdi-comment-edit-outline"
                      size="small"
                      title="Modifier l'observation"
                      variant="text"
                      @click="openEditObservation(proc)"
                    />
                    <v-btn
                      density="comfortable"
                      icon="mdi-eye"
                      size="small"
                      title="Voir le détail du processus"
                      variant="text"
                      @click="router.push(`/company/context/management-system/${proc.id}`)"
                    />
                    <v-btn
                      color="primary"
                      density="comfortable"
                      icon="mdi-vector-polyline"
                      size="small"
                      title="Isoler les interactions"
                      variant="text"
                      @click="selectProcess(proc)"
                    />
                  </div>
                </td>
              </tr>
              <tr v-if="filteredProcessesTable.length === 0">
                <td :colspan="visibleColumnCount" class="text-center py-6 text-slate-500 font-italic">
                  Aucun processus ne correspond aux critères de recherche.
                </td>
              </tr>
            </tbody>
          </v-table>
        </v-card-text>
      </v-card>
    </div>

    <!-- EDIT OBSERVATION DIALOG -->
    <v-dialog v-model="editObservationDialog" max-width="560">
      <v-card rounded="xl">
        <v-card-title class="pa-4 bg-slate-50 border-b d-flex align-center justify-space-between">
          <div class="d-flex align-center gap-2">
            <v-avatar color="amber-lighten-4" size="32">
              <v-icon color="amber-darken-3" size="20">mdi-comment-edit-outline</v-icon>
            </v-avatar>
            <div class="text-subtitle-1 font-weight-bold">
              Modifier l'observation du processus
            </div>
          </div>
          <v-btn density="comfortable" icon="mdi-close" variant="text" @click="editObservationDialog = false" />
        </v-card-title>
        <v-card-text class="pa-4">
          <div class="mb-3 text-body-2 font-weight-medium text-slate-700">
            Processus : <strong>{{ editingProcess?.code }} — {{ editingProcess?.title }}</strong>
          </div>
          <v-textarea
            v-model="editObservationText"
            density="comfortable"
            hide-details="auto"
            label="Observation (Cartographie des processus)"
            placeholder="Ex: Mise à jour avec intégration des activités d'externalisation, phase transitoire, etc."
            rows="4"
            variant="outlined"
          />
        </v-card-text>
        <v-card-actions class="pa-4 border-t bg-slate-50">
          <v-spacer />
          <v-btn variant="text" @click="editObservationDialog = false">Annuler</v-btn>
          <v-btn
            color="primary"
            :loading="savingObservation"
            variant="flat"
            @click="saveObservation"
          >
            Enregistrer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- GENERATED DOCUMENT CONFIG DIALOG (Codification par entreprise) -->
    <GeneratedDocumentConfigDialog
      v-model="generationDialog"
      :site-id="currentSiteId"
      :loading="exportLoading"
      title="Codification — Cartographie des Processus"
      @confirm="confirmGenerationConfig"
    />

    <!-- VERIFY WORKFLOW DIALOG -->
    <v-dialog v-model="verifyDialog" max-width="540">
      <v-card rounded="xl">
        <v-card-title class="pa-4 bg-amber-lighten-5 border-b d-flex align-center gap-2">
          <v-icon color="amber-darken-3">mdi-check-decagram-outline</v-icon>
          <span class="text-subtitle-1 font-weight-bold">Soumettre pour vérification</span>
        </v-card-title>
        <v-card-text class="pa-4">
          <div class="mb-3 text-body-2 text-slate-700">
            Vous êtes sur le point de soumettre la cartographie des processus pour vérification dans le workflow documentaire SMQ.
          </div>
          <div class="pa-3 bg-slate-50 rounded-lg border">
            <div><strong>Code :</strong> {{ cartographyDocument?.code }}</div>
            <div><strong>Version :</strong> {{ cartographyDocument?.version || '1.0' }}</div>
            <div><strong>Titre :</strong> {{ cartographyDocument?.title || 'Cartographie des Processus' }}</div>
          </div>
        </v-card-text>
        <v-card-actions class="pa-4 border-t">
          <v-spacer />
          <v-btn variant="text" @click="verifyDialog = false">Annuler</v-btn>
          <v-btn
            color="amber-darken-3"
            :loading="submittingVerification"
            variant="flat"
            @click="submitCartographyVerification"
          >
            Confirmer la soumission
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- RT-01 PREVIEW EXPORT MODAL -->
    <PreviewExportModal
      v-model="previewExportOpen"
      :blob="exportBlob"
      :blob-url="exportBlobUrl"
      :filename="exportFilename"
      :file-type="exportFormat"
      :loading="exportLoading"
      :metadata="exportMetadata"
      title="Cartographie des Processus & Matrice d'Interactions"
      @confirm-download="confirmDownload"
    />
  </div>
</template>

<script setup lang="ts">
  import { computed, nextTick, onMounted, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import PreviewExportModal, { type PreviewMetadata } from '@/modules/shared/components/PreviewExportModal.vue'
  import GeneratedDocumentConfigDialog, { type GeneratedDocumentContext } from '@/modules/clienta/components/documents/GeneratedDocumentConfigDialog.vue'
  import processService from '@/services/processService'
  import documentService from '@/services/documentService'
  import { useSiteStore } from '@/stores/siteStore'
  import { useToast } from '@/modules/shared/composables/useToast'

  const props = withDefaults(
    defineProps<{
      processId?: number | null
      siteId?: number | null
      hideHeader?: boolean
      showExportButton?: boolean
    }>(),
    {
      processId: null,
      siteId: null,
      hideHeader: false,
      showExportButton: true,
    },
  )

  const router = useRouter()
  const siteStore = useSiteStore()
  const toast = useToast()

  const loading = ref(false)
  const currentSiteId = ref<number | null>(props.siteId || null)
  const enterpriseName = ref('Système de Management de la Qualité')
  const siteName = ref('Principal')
  const allProcesses = ref<any[]>([])
  const interactions = ref<any[]>([])
  const activeCategoryFilter = ref('all')
  const displayMode = ref<'diagram' | 'table'>('diagram')
  const showInteractions = ref(true)
  const searchQuery = ref('')
  const cartographyRoot = ref<HTMLElement | null>(null)

  const selectedProcessState = ref<any | null>(null)

  // Document workflow & Codification state
  const cartographyDocument = ref<any | null>(null)
  const generationDialog = ref(false)
  const verifyDialog = ref(false)
  const submittingVerification = ref(false)

  // Export RT-01 state
  const exportFormat = ref<'pdf' | 'docx'>('pdf')
  const previewExportOpen = ref(false)
  const exportLoading = ref(false)
  const exportBlob = ref<Blob | null>(null)
  const exportBlobUrl = ref<string | null>(null)
  const exportFilename = ref('cartographie_processus.pdf')

  // Edit observation state
  const editObservationDialog = ref(false)
  const editingProcess = ref<any | null>(null)
  const editObservationText = ref('')
  const savingObservation = ref(false)

  // Table column visibility state
  interface TableColumn {
    key: 'name' | 'activities' | 'observation' | 'responsible' | 'actions'
    label: string
    visible: boolean
    width?: string
  }

  const tableColumns = ref<TableColumn[]>([
    { key: 'name', label: 'Proposition de nom processus', visible: true, width: '25%' },
    { key: 'activities', label: 'Activités principales', visible: true, width: '32%' },
    { key: 'observation', label: 'Observation', visible: true, width: '20%' },
    { key: 'responsible', label: 'Responsable par département', visible: true, width: '17%' },
    { key: 'actions', label: 'Actions', visible: true, width: '6%' },
  ])

  function isColVisible (key: string): boolean {
    const col = tableColumns.value.find(c => c.key === key)
    return col ? col.visible : true
  }

  const visibleColumnCount = computed(() => tableColumns.value.filter(c => c.visible).length)

  function toggleCol (col: TableColumn) {
    if (col.visible && visibleColumnCount.value <= 1) return
    col.visible = !col.visible
  }

  function resetTableColumns () {
    tableColumns.value.forEach(c => { c.visible = true })
  }

  const sites = computed(() => siteStore.sites || [])

  const managementProcesses = computed(() => {
    return allProcesses.value.filter(p => p.normalized_category === 'management')
  })

  const realizationProcesses = computed(() => {
    return allProcesses.value.filter(p => p.normalized_category === 'realization')
  })

  const supportProcesses = computed(() => {
    return allProcesses.value.filter(p => p.normalized_category === 'support')
  })

  const activeSelectedProcess = computed(() => {
    if (selectedProcessState.value) {
      return selectedProcessState.value
    }
    if (props.processId) {
      return allProcesses.value.find(p => Number(p.id) === Number(props.processId)) || null
    }
    return null
  })

  // Connected upstream processes (suppliers)
  const upstreamProcesses = computed(() => {
    if (!activeSelectedProcess.value) return []
    const currentId = activeSelectedProcess.value.id
    const currentCode = String(activeSelectedProcess.value.code || '').toUpperCase()

    // 1. Check explicit supplier_processes
    const explicitRefs = (activeSelectedProcess.value.supplier_processes || []).map((r: any) => String(r).toUpperCase())

    return allProcesses.value.filter(p => {
      if (p.id === currentId) return false
      if (explicitRefs.includes(String(p.id)) || explicitRefs.includes(String(p.code).toUpperCase()) || explicitRefs.includes(String(p.title).toUpperCase())) {
        return true
      }
      // Check from interaction matrix
      const hasInteraction = interactions.value.some(int => int.target_id === currentId && int.source_id === p.id)
      return hasInteraction
    })
  })

  // Connected downstream processes (clients)
  const downstreamProcesses = computed(() => {
    if (!activeSelectedProcess.value) return []
    const currentId = activeSelectedProcess.value.id
    const currentCode = String(activeSelectedProcess.value.code || '').toUpperCase()

    // 1. Check explicit client_processes
    const explicitRefs = (activeSelectedProcess.value.client_processes || []).map((r: any) => String(r).toUpperCase())

    return allProcesses.value.filter(p => {
      if (p.id === currentId) return false
      if (explicitRefs.includes(String(p.id)) || explicitRefs.includes(String(p.code).toUpperCase()) || explicitRefs.includes(String(p.title).toUpperCase())) {
        return true
      }
      // Check from interaction matrix
      const hasInteraction = interactions.value.some(int => int.source_id === currentId && int.target_id === p.id)
      return hasInteraction
    })
  })

  const filteredProcessesTable = computed(() => {
    let list = allProcesses.value

    if (activeCategoryFilter.value !== 'all') {
      list = list.filter(p => p.normalized_category === activeCategoryFilter.value)
    }

    if (searchQuery.value && searchQuery.value.trim()) {
      const q = searchQuery.value.toLowerCase().trim()
      list = list.filter(p =>
        (p.title && p.title.toLowerCase().includes(q)) ||
        (p.code && p.code.toLowerCase().includes(q)) ||
        (p.pilot_name && p.pilot_name.toLowerCase().includes(q)) ||
        (p.department && p.department.toLowerCase().includes(q)) ||
        (p.activities_summary && p.activities_summary.toLowerCase().includes(q)),
      )
    }

    return list
  })

  const exportMetadata = computed<PreviewMetadata>(() => {
    return {
      version: cartographyDocument.value?.version || '1.0',
      date: cartographyDocument.value?.effective_date || new Date().toLocaleDateString('fr-FR'),
      summaryItems: [
        { label: 'Entreprise / SMQ', value: enterpriseName.value },
        { label: 'Réf Document', value: cartographyDocument.value?.code || 'Non codifié (Brouillon)' },
        { label: 'Statut Workflow', value: getWorkflowLabel(cartographyDocument.value?.workflow_status) },
        { label: 'Total Processus', value: allProcesses.value.length },
        { label: 'Management', value: managementProcesses.value.length },
        { label: 'Réalisation', value: realizationProcesses.value.length },
        { label: 'Support', value: supportProcesses.value.length },
      ],
    }
  })

  onMounted(async () => {
    if (sites.value.length === 0) {
      await siteStore.fetchAll().catch(() => {})
    }
    await loadData()
  })

  async function loadData () {
    loading.value = true
    try {
      const res = await processService.getCartography(currentSiteId.value)
      const data = res.data || res
      enterpriseName.value = data.enterprise_name || 'Système de Management de la Qualité'
      siteName.value = data.site_name || 'Principal'
      allProcesses.value = data.all_processes || []
      interactions.value = data.interactions || []
      cartographyDocument.value = data.document || null

      // If initial processId given, set it
      if (props.processId) {
        const target = allProcesses.value.find(p => Number(p.id) === Number(props.processId))
        if (target) {
          selectedProcessState.value = target
        }
      }
    } catch (err) {
      console.error('Failed to load process cartography:', err)
    } finally {
      loading.value = false
    }
  }

  function getWorkflowColor (status?: string) {
    switch (status) {
      case 'approved':
      case 'published': return 'success'
      case 'under_review':
      case 'review': return 'info'
      case 'draft': return 'amber'
      default: return 'grey'
    }
  }

  function getWorkflowLabel (status?: string) {
    switch (status) {
      case 'approved':
      case 'published': return 'Validé / Approuvé'
      case 'under_review':
      case 'review': return 'En vérification'
      case 'draft': return 'Brouillon'
      default: return 'Non codifié'
    }
  }

  function openGenerationDialog (_type: string = 'draft') {
    generationDialog.value = true
  }

  async function confirmGenerationConfig (context: GeneratedDocumentContext) {
    exportLoading.value = true
    try {
      const payload = {
        site_id: currentSiteId.value || undefined,
        document_type_catalog_id: context.document_type_catalog_id,
        classification_level: context.classification_level || 'internal',
        confidentiality: context.confidentiality || 'internal',
        language: context.language || 'fr',
        review_frequency_months: context.review_frequency_months || 12,
        remarks: context.remarks || '',
      }

      const res = await processService.generateCartographyDraft(payload)
      const doc = res.data || res
      cartographyDocument.value = doc
      toast.success('Cartographie des processus codifiée avec succès.')
      generationDialog.value = false
      await loadData()
    } catch (err: any) {
      console.error('Failed to generate cartography document draft:', err)
      toast.error(err.response?.data?.message || 'Erreur lors de la codification du document.')
    } finally {
      exportLoading.value = false
    }
  }

  async function submitCartographyVerification () {
    if (!cartographyDocument.value?.id) return
    submittingVerification.value = true
    try {
      await documentService.verify(cartographyDocument.value.id, {
        comments: 'Soumission de la cartographie des processus pour vérification.',
      })
      toast.success('Document soumis pour vérification avec succès.')
      verifyDialog.value = false
      await loadData()
    } catch (err: any) {
      console.error('Failed to submit document for verification:', err)
      toast.error(err.response?.data?.message || 'Erreur lors de la soumission pour vérification.')
    } finally {
      submittingVerification.value = false
    }
  }

  function shouldShowCategory (category: string) {
    if (activeCategoryFilter.value === 'all') return true
    return activeCategoryFilter.value === category
  }

  function selectProcess (proc: any) {
    selectedProcessState.value = proc
  }

  function clearSelectedProcess () {
    selectedProcessState.value = null
  }

  function isProcessActive (proc: any) {
    return activeSelectedProcess.value && activeSelectedProcess.value.id === proc.id
  }

  function isProcessUpstream (proc: any) {
    return upstreamProcesses.value.some(p => p.id === proc.id)
  }

  function isProcessDownstream (proc: any) {
    return downstreamProcesses.value.some(p => p.id === proc.id)
  }

  function getCategoryColor (cat: string) {
    switch (cat) {
      case 'management': return 'purple'
      case 'realization': return 'green'
      case 'support': return 'indigo'
      default: return 'grey'
    }
  }

  function getCategoryLabel (cat: string) {
    switch (cat) {
      case 'management': return 'Management'
      case 'realization': return 'Réalisation'
      case 'support': return 'Support'
      default: return 'Opérationnel'
    }
  }

  function getCategoryIcon (cat: string) {
    switch (cat) {
      case 'management': return 'mdi-chart-line'
      case 'realization': return 'mdi-cog-sync'
      case 'support': return 'mdi-lifebuoy'
      default: return 'mdi-arrow-right-circle'
    }
  }

  function toggleFullscreen () {
    if (!cartographyRoot.value) return
    if (document.fullscreenElement) {
      document.exitFullscreen().catch(() => {})
    } else {
      cartographyRoot.value.requestFullscreen().catch(() => {})
    }
  }

  function openEditObservation (proc: any) {
    editingProcess.value = proc
    editObservationText.value = proc.observation || ''
    editObservationDialog.value = true
  }

  async function saveObservation () {
    if (!editingProcess.value) return
    savingObservation.value = true
    try {
      await processService.updateProcess(editingProcess.value.id, {
        observation: editObservationText.value,
      })
      editingProcess.value.observation = editObservationText.value
      const found = allProcesses.value.find(p => p.id === editingProcess.value.id)
      if (found) {
        found.observation = editObservationText.value
      }
      editObservationDialog.value = false
    } catch (err) {
      console.error('Failed to update observation:', err)
    } finally {
      savingObservation.value = false
    }
  }

  // --- RT-01 EXPORT PREVIEW FLOW ---
  async function openExportPreview (format: 'pdf' | 'docx' = 'pdf') {
    exportFormat.value = format
    previewExportOpen.value = true
    exportLoading.value = true
    const ext = format === 'pdf' ? 'pdf' : 'docx'
    exportFilename.value = `Cartographie_Processus_${new Date().toISOString().slice(0, 10)}.${ext}`

    try {
      const blob = format === 'pdf'
        ? await processService.exportCartographyPdfBlob(currentSiteId.value, true)
        : await processService.exportCartographyDocxBlob(currentSiteId.value)
      exportBlob.value = blob
      if (exportBlobUrl.value) {
        URL.revokeObjectURL(exportBlobUrl.value)
      }
      exportBlobUrl.value = URL.createObjectURL(blob)
    } catch (err) {
      console.error('Failed to generate cartography preview:', err)
    } finally {
      exportLoading.value = false
    }
  }

  function confirmDownload () {
    if (!exportBlob.value) return
    const url = window.URL.createObjectURL(exportBlob.value)
    const link = document.createElement('a')
    link.href = url
    link.download = exportFilename.value
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  }

  // --- DIAGRAM IMAGE / VECTOR EXPORT (PNG & SVG) ---
  function escapeXml (unsafe: string): string {
    return String(unsafe || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&apos;')
  }

  function truncateText (text: string, maxLen = 30): string {
    const s = String(text || '').trim()
    return s.length > maxLen ? `${s.slice(0, maxLen - 1)}…` : s
  }

  function buildCartographySvg (): string {
    const W = 1600
    const H = 950
    const enterprise = escapeXml(enterpriseName.value || 'Système de Management de la Qualité')
    const docCode = escapeXml(cartographyDocument.value?.code || 'SMQ-CART-001')
    const docVersion = escapeXml(cartographyDocument.value?.version || '1.0')
    const dateStr = new Date().toLocaleDateString('fr-FR')

    const mgmt = managementProcesses.value
    const real = realizationProcesses.value
    const sup = supportProcesses.value

    function renderLaneCards (procs: any[], startX: number, startY: number, laneW: number, cardH: number, colorTheme: { border: string, bg: string, tagBg: string, tagText: string }): string {
      if (procs.length === 0) {
        return `<text x="${startX + laneW / 2}" y="${startY + cardH / 2}" font-family="system-ui, -apple-system, sans-serif" font-size="13" font-style="italic" fill="#94a3b8" text-anchor="middle">Aucun processus défini dans cette catégorie</text>`
      }

      const cols = Math.min(Math.max(procs.length, 1), 5)
      const gap = 14
      const cardW = Math.max(140, Math.floor((laneW - (cols - 1) * gap) / cols))

      return procs.map((p, idx) => {
        const colIdx = idx % cols
        const rowIdx = Math.floor(idx / cols)
        const x = startX + colIdx * (cardW + gap)
        const y = startY + rowIdx * (cardH + gap)
        const title = escapeXml(truncateText(p.title || 'Processus', 32))
        const code = escapeXml(p.code || `PROC-${idx + 1}`)
        const pilot = escapeXml(truncateText(p.pilot_name || p.responsible_display || 'Pilote SMQ', 24))

        return `
          <g transform="translate(${x}, ${y})">
            <rect width="${cardW}" height="${cardH}" rx="8" fill="${colorTheme.bg}" stroke="${colorTheme.border}" stroke-width="1.5" filter="url(#dropShadow)" />
            <rect width="${cardW}" height="24" rx="8" fill="${colorTheme.tagBg}" />
            <rect width="${cardW}" height="8" y="16" fill="${colorTheme.tagBg}" />
            <text x="8" y="16" font-family="system-ui, -apple-system, sans-serif" font-size="11" font-weight="bold" fill="${colorTheme.tagText}">${code}</text>
            <text x="8" y="44" font-family="system-ui, -apple-system, sans-serif" font-size="12" font-weight="600" fill="#1e293b">${title}</text>
            <line x1="8" y1="${cardH - 20}" x2="${cardW - 8}" y2="${cardH - 20}" stroke="#e2e8f0" stroke-dasharray="2,2" />
            <text x="8" y="${cardH - 6}" font-family="system-ui, -apple-system, sans-serif" font-size="10" fill="#64748b">Pilote : ${pilot}</text>
          </g>
        `
      }).join('\n')
    }

    function renderRealizationCards (procs: any[], startX: number, startY: number, laneW: number, cardH: number): string {
      if (procs.length === 0) {
        return `<text x="${startX + laneW / 2}" y="${startY + cardH / 2}" font-family="system-ui, -apple-system, sans-serif" font-size="13" font-style="italic" fill="#94a3b8" text-anchor="middle">Aucun processus de réalisation défini</text>`
      }

      const count = procs.length
      const arrowW = 24
      const totalArrowW = (count - 1) * arrowW
      const cardW = Math.max(130, Math.floor((laneW - totalArrowW - (count - 1) * 12) / count))
      let curX = startX

      return procs.map((p, idx) => {
        const x = curX
        const y = startY
        const title = escapeXml(truncateText(p.title || 'Processus', 30))
        const code = escapeXml(p.code || `REAL-${idx + 1}`)
        const pilot = escapeXml(truncateText(p.pilot_name || p.responsible_display || 'Pilote', 22))

        let cardMarkup = `
          <g transform="translate(${x}, ${y})">
            <rect width="${cardW}" height="${cardH}" rx="8" fill="#ffffff" stroke="#16a34a" stroke-width="1.8" filter="url(#dropShadow)" />
            <rect width="${cardW}" height="24" rx="8" fill="#dcfce7" />
            <rect width="${cardW}" height="8" y="16" fill="#dcfce7" />
            <text x="8" y="16" font-family="system-ui, -apple-system, sans-serif" font-size="11" font-weight="bold" fill="#15803d">${code}</text>
            <text x="8" y="44" font-family="system-ui, -apple-system, sans-serif" font-size="12" font-weight="600" fill="#0f172a">${title}</text>
            <line x1="8" y1="${cardH - 20}" x2="${cardW - 8}" y2="${cardH - 20}" stroke="#e2e8f0" stroke-dasharray="2,2" />
            <text x="8" y="${cardH - 6}" font-family="system-ui, -apple-system, sans-serif" font-size="10" fill="#64748b">Pilote : ${pilot}</text>
          </g>
        `

        curX += cardW + 12

        if (idx < count - 1) {
          cardMarkup += `
            <g transform="translate(${curX}, ${y + cardH / 2 - 10})">
              <polygon points="0,4 12,10 0,16" fill="#16a34a" />
              <line x1="-6" y1="10" x2="6" y2="10" stroke="#16a34a" stroke-width="3" />
            </g>
          `
          curX += arrowW
        }

        return cardMarkup
      }).join('\n')
    }

    const mgmtCardsSvg = renderLaneCards(mgmt, 230, 150, 1100, 75, {
      border: '#a855f7',
      bg: '#ffffff',
      tagBg: '#f3e8ff',
      tagText: '#7e22ce',
    })

    const realCardsSvg = renderRealizationCards(real, 230, 395, 1100, 80)

    const supCardsSvg = renderLaneCards(sup, 230, 665, 1100, 75, {
      border: '#6366f1',
      bg: '#ffffff',
      tagBg: '#e0e7ff',
      tagText: '#4338ca',
    })

    return `<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${W} ${H}" width="${W}" height="${H}">
  <defs>
    <filter id="dropShadow" x="-10%" y="-10%" width="120%" height="120%">
      <feDropShadow dx="0" dy="2" stdDeviation="3" flood-color="#000000" flood-opacity="0.08" />
    </filter>
    <linearGradient id="headerGrad" x1="0%" y1="0%" x2="100%" y2="0%">
      <stop offset="0%" stop-color="#1e293b" />
      <stop offset="100%" stop-color="#334155" />
    </linearGradient>
    <linearGradient id="mgmtGrad" x1="0%" y1="0%" x2="100%" y2="0%">
      <stop offset="0%" stop-color="#faf5ff" />
      <stop offset="100%" stop-color="#f3e8ff" />
    </linearGradient>
    <linearGradient id="realGrad" x1="0%" y1="0%" x2="100%" y2="0%">
      <stop offset="0%" stop-color="#f0fdf4" />
      <stop offset="100%" stop-color="#dcfce7" />
    </linearGradient>
    <linearGradient id="supGrad" x1="0%" y1="0%" x2="100%" y2="0%">
      <stop offset="0%" stop-color="#eef2ff" />
      <stop offset="100%" stop-color="#e0e7ff" />
    </linearGradient>
    <marker id="arrowhead-blue" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
      <polygon points="0 0, 10 3.5, 0 7" fill="#2563eb" />
    </marker>
    <marker id="arrowhead-green" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
      <polygon points="0 0, 10 3.5, 0 7" fill="#16a34a" />
    </marker>
    <marker id="arrowhead-purple" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
      <polygon points="0 0, 10 3.5, 0 7" fill="#7e22ce" />
    </marker>
    <marker id="arrowhead-indigo" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
      <polygon points="0 0, 10 3.5, 0 7" fill="#4338ca" />
    </marker>
  </defs>

  <!-- Background -->
  <rect width="${W}" height="${H}" fill="#f8fafc" />
  <rect x="15" y="15" width="${W - 30}" height="${H - 30}" rx="16" fill="#ffffff" stroke="#cbd5e1" stroke-width="1.5" />

  <!-- Top Title Banner -->
  <rect x="25" y="25" width="${W - 50}" height="65" rx="10" fill="url(#headerGrad)" />
  <text x="45" y="52" font-family="system-ui, -apple-system, sans-serif" font-size="18" font-weight="bold" fill="#ffffff">
    CARTOGRAPHIE DES PROCESSUS DU SYSTÈME DE MANAGEMENT (ISO 9001:2015 §4.4)
  </text>
  <text x="45" y="74" font-family="system-ui, -apple-system, sans-serif" font-size="12" fill="#94a3b8">
    ${enterprise} &#8226; Éditée le ${dateStr}
  </text>
  <rect x="${W - 340}" y="37" width="300" height="40" rx="8" fill="#0f172a" stroke="#475569" stroke-width="1" />
  <text x="${W - 190}" y="55" font-family="system-ui, -apple-system, sans-serif" font-size="11" font-weight="bold" fill="#38bdf8" text-anchor="middle">
    ${docCode} (v${docVersion})
  </text>
  <text x="${W - 190}" y="70" font-family="system-ui, -apple-system, sans-serif" font-size="10" fill="#94a3b8" text-anchor="middle">
    Matrice d'interactions &amp; Chaîne de valeur
  </text>

  <!-- LEFT PILLAR: INPUTS -->
  <g transform="translate(30, 105)">
    <rect width="160" height="735" rx="12" fill="#eff6ff" stroke="#3b82f6" stroke-width="2" stroke-dasharray="6,4" />
    <rect x="10" y="15" width="140" height="50" rx="8" fill="#2563eb" />
    <text x="80" y="38" font-family="system-ui, -apple-system, sans-serif" font-size="14" font-weight="800" fill="#ffffff" text-anchor="middle">ENTRÉES</text>
    <text x="80" y="54" font-family="system-ui, -apple-system, sans-serif" font-size="10" fill="#bfdbfe" text-anchor="middle">Exigences &amp; Attentes</text>

    <!-- Inputs badges -->
    <g transform="translate(10, 85)">
      <rect width="140" height="42" rx="6" fill="#ffffff" stroke="#93c5fd" stroke-width="1" />
      <circle cx="16" cy="21" r="5" fill="#2563eb" />
      <text x="28" y="25" font-family="system-ui, -apple-system, sans-serif" font-size="10.5" font-weight="600" fill="#1e293b">Clients &amp; Marchés</text>
    </g>
    <g transform="translate(10, 140)">
      <rect width="140" height="42" rx="6" fill="#ffffff" stroke="#93c5fd" stroke-width="1" />
      <circle cx="16" cy="21" r="5" fill="#2563eb" />
      <text x="28" y="25" font-family="system-ui, -apple-system, sans-serif" font-size="10.5" font-weight="600" fill="#1e293b">Parties Intéressées</text>
    </g>
    <g transform="translate(10, 195)">
      <rect width="140" height="42" rx="6" fill="#ffffff" stroke="#93c5fd" stroke-width="1" />
      <circle cx="16" cy="21" r="5" fill="#2563eb" />
      <text x="28" y="25" font-family="system-ui, -apple-system, sans-serif" font-size="10.5" font-weight="600" fill="#1e293b">Exigences ISO 9001</text>
    </g>
    <g transform="translate(10, 250)">
      <rect width="140" height="42" rx="6" fill="#ffffff" stroke="#93c5fd" stroke-width="1" />
      <circle cx="16" cy="21" r="5" fill="#2563eb" />
      <text x="28" y="25" font-family="system-ui, -apple-system, sans-serif" font-size="10.5" font-weight="600" fill="#1e293b">Lois &amp; Règlements</text>
    </g>
    <g transform="translate(10, 305)">
      <rect width="140" height="42" rx="6" fill="#ffffff" stroke="#93c5fd" stroke-width="1" />
      <circle cx="16" cy="21" r="5" fill="#2563eb" />
      <text x="28" y="25" font-family="system-ui, -apple-system, sans-serif" font-size="10.5" font-weight="600" fill="#1e293b">Stratégie Entreprise</text>
    </g>

    <text x="80" y="440" font-family="system-ui, -apple-system, sans-serif" font-size="11" font-weight="600" fill="#2563eb" text-anchor="middle">Intrants &amp; Données</text>
    <text x="80" y="456" font-family="system-ui, -apple-system, sans-serif" font-size="10" fill="#64748b" text-anchor="middle">d'entrée du SMQ</text>
  </g>

  <!-- Arrow Input -> Realization -->
  <path d="M 195 435 L 215 435" stroke="#2563eb" stroke-width="4" marker-end="url(#arrowhead-blue)" fill="none" />

  <!-- 1. MANAGEMENT LANE -->
  <g transform="translate(215, 105)">
    <rect width="1130" height="150" rx="10" fill="url(#mgmtGrad)" stroke="#c084fc" stroke-width="1.5" />
    <rect x="15" y="10" width="450" height="26" rx="6" fill="#7e22ce" />
    <text x="25" y="27" font-family="system-ui, -apple-system, sans-serif" font-size="11.5" font-weight="bold" fill="#ffffff">
      PROCESSUS DE MANAGEMENT / PILOTAGE (${mgmt.length})
    </text>
    ${mgmtCardsSvg}
  </g>

  <!-- CONNECTOR MGMT -> REALIZATION -->
  <g transform="translate(215, 260)">
    <rect x="250" y="10" width="630" height="30" rx="15" fill="#f3e8ff" stroke="#a855f7" stroke-width="1" />
    <text x="565" y="29" font-family="system-ui, -apple-system, sans-serif" font-size="11" font-weight="bold" fill="#6b21a8" text-anchor="middle">
      &#9660; Orientations, Politiques, Objectifs &amp; Décisions de pilotage &#9660;
    </text>
    <path d="M 450 40 L 450 65" stroke="#7e22ce" stroke-width="2.5" marker-end="url(#arrowhead-purple)" fill="none" />
    <path d="M 680 40 L 680 65" stroke="#7e22ce" stroke-width="2.5" marker-end="url(#arrowhead-purple)" fill="none" />
  </g>

  <!-- 2. REALIZATION LANE -->
  <g transform="translate(215, 335)">
    <rect width="1130" height="200" rx="10" fill="url(#realGrad)" stroke="#86efac" stroke-width="1.8" />
    <rect x="15" y="12" width="470" height="26" rx="6" fill="#15803d" />
    <text x="25" y="29" font-family="system-ui, -apple-system, sans-serif" font-size="11.5" font-weight="bold" fill="#ffffff">
      PROCESSUS DE RÉALISATION / CHAÎNE DE VALEUR (${real.length})
    </text>
    ${realCardsSvg}
  </g>

  <!-- CONNECTOR SUPPORT -> REALIZATION -->
  <g transform="translate(215, 545)">
    <rect x="230" y="10" width="670" height="30" rx="15" fill="#e0e7ff" stroke="#818cf8" stroke-width="1" />
    <text x="565" y="29" font-family="system-ui, -apple-system, sans-serif" font-size="11" font-weight="bold" fill="#3730a3" text-anchor="middle">
      &#9650; Mise à disposition des Ressources, Moyens, Compétences &amp; Systèmes SMQ &#9650;
    </text>
    <path d="M 450 10 L 450 -15" stroke="#4338ca" stroke-width="2.5" marker-end="url(#arrowhead-indigo)" fill="none" />
    <path d="M 680 10 L 680 -15" stroke="#4338ca" stroke-width="2.5" marker-end="url(#arrowhead-indigo)" fill="none" />
  </g>

  <!-- 3. SUPPORT LANE -->
  <g transform="translate(215, 620)">
    <rect width="1130" height="150" rx="10" fill="url(#supGrad)" stroke="#a5b4fc" stroke-width="1.5" />
    <rect x="15" y="10" width="430" height="26" rx="6" fill="#4338ca" />
    <text x="25" y="27" font-family="system-ui, -apple-system, sans-serif" font-size="11.5" font-weight="bold" fill="#ffffff">
      PROCESSUS DE SUPPORT / RESSOURCES (${sup.length})
    </text>
    ${supCardsSvg}
  </g>

  <!-- Arrow Realization -> Output -->
  <path d="M 1348 435 L 1378 435" stroke="#16a34a" stroke-width="4" marker-end="url(#arrowhead-green)" fill="none" />

  <!-- RIGHT PILLAR: OUTPUTS -->
  <g transform="translate(1380, 105)">
    <rect width="180" height="735" rx="12" fill="#f0fdf4" stroke="#22c55e" stroke-width="2" stroke-dasharray="6,4" />
    <rect x="12" y="15" width="156" height="50" rx="8" fill="#16a34a" />
    <text x="90" y="38" font-family="system-ui, -apple-system, sans-serif" font-size="14" font-weight="800" fill="#ffffff" text-anchor="middle">SORTIES</text>
    <text x="90" y="54" font-family="system-ui, -apple-system, sans-serif" font-size="10" fill="#bbf7d0" text-anchor="middle">Satisfaction Client</text>

    <!-- Outputs badges -->
    <g transform="translate(12, 85)">
      <rect width="156" height="42" rx="6" fill="#ffffff" stroke="#86efac" stroke-width="1" />
      <polygon points="18,17 22,25 14,25" fill="#16a34a" />
      <text x="28" y="25" font-family="system-ui, -apple-system, sans-serif" font-size="10.5" font-weight="600" fill="#1e293b">Produits &amp; Services</text>
    </g>
    <g transform="translate(12, 140)">
      <rect width="156" height="42" rx="6" fill="#ffffff" stroke="#86efac" stroke-width="1" />
      <polygon points="18,17 22,25 14,25" fill="#16a34a" />
      <text x="28" y="25" font-family="system-ui, -apple-system, sans-serif" font-size="10.5" font-weight="600" fill="#1e293b">Clients Satisfaits</text>
    </g>
    <g transform="translate(12, 195)">
      <rect width="156" height="42" rx="6" fill="#ffffff" stroke="#86efac" stroke-width="1" />
      <polygon points="18,17 22,25 14,25" fill="#16a34a" />
      <text x="28" y="25" font-family="system-ui, -apple-system, sans-serif" font-size="10.5" font-weight="600" fill="#1e293b">Conformité &amp; Valeur</text>
    </g>
    <g transform="translate(12, 250)">
      <rect width="156" height="42" rx="6" fill="#ffffff" stroke="#86efac" stroke-width="1" />
      <polygon points="18,17 22,25 14,25" fill="#16a34a" />
      <text x="28" y="25" font-family="system-ui, -apple-system, sans-serif" font-size="10.5" font-weight="600" fill="#1e293b">Performance SMQ</text>
    </g>
    <g transform="translate(12, 305)">
      <rect width="156" height="42" rx="6" fill="#ffffff" stroke="#86efac" stroke-width="1" />
      <polygon points="18,17 22,25 14,25" fill="#16a34a" />
      <text x="28" y="25" font-family="system-ui, -apple-system, sans-serif" font-size="10.5" font-weight="600" fill="#1e293b">Bénéfices Durables</text>
    </g>

    <text x="90" y="440" font-family="system-ui, -apple-system, sans-serif" font-size="11" font-weight="600" fill="#16a34a" text-anchor="middle">Résultats &amp; Valeur</text>
    <text x="90" y="456" font-family="system-ui, -apple-system, sans-serif" font-size="10" fill="#64748b" text-anchor="middle">livrée au marché</text>
  </g>

  <!-- CONTINUOUS IMPROVEMENT FEEDBACK LOOP -->
  <g transform="translate(215, 785)">
    <rect width="1130" height="40" rx="8" fill="#fef3c7" stroke="#f59e0b" stroke-width="1.5" />
    <path d="M 30 20 L 45 20 M 45 20 L 38 14 M 45 20 L 38 26" stroke="#d97706" stroke-width="2" fill="none" />
    <text x="565" y="25" font-family="system-ui, -apple-system, sans-serif" font-size="11.5" font-weight="bold" fill="#92400e" text-anchor="middle">
      Boucle de Rétroaction ISO 9001 (§4.4 &amp; §10.2) : Données de surveillance, Audits &amp; Réclamations &#10142; Revues de direction &#10142; Amélioration continue
    </text>
  </g>

  <!-- Footer note -->
  <text x="800" y="870" font-family="system-ui, -apple-system, sans-serif" font-size="10.5" fill="#94a3b8" text-anchor="middle">
    Système de Management Intégré LOGIQUALI — Cartographie conforme aux référentiels ISO 9001:2015, ISO 14001:2015 &amp; ISO 45001:2018
  </text>
</svg>`
  }

  async function downloadDiagramImage (format: 'png' | 'svg' = 'png') {
    const svgString = buildCartographySvg()
    const fileName = `Cartographie_Processus_${new Date().toISOString().slice(0, 10)}.${format}`

    if (format === 'svg') {
      const blob = new Blob([svgString], { type: 'image/svg+xml;charset=utf-8' })
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = fileName
      document.body.appendChild(a)
      a.click()
      document.body.removeChild(a)
      URL.revokeObjectURL(url)
      toast.success('Schéma vectoriel SVG téléchargé avec succès.')
      return
    }

    try {
      // Use standard Base64 Data URI for guaranteed browser compatibility across all platforms
      const base64Svg = btoa(unescape(encodeURIComponent(svgString)))
      const dataUri = `data:image/svg+xml;base64,${base64Svg}`

      const img = new Image()

      img.onload = () => {
        try {
          const canvas = document.createElement('canvas')
          const scale = 2
          canvas.width = 1600 * scale
          canvas.height = 950 * scale
          const ctx = canvas.getContext('2d')
          if (!ctx) {
            toast.error('Impossible d\'initialiser le canvas graphique.')
            return
          }

          // Fill clean solid white background
          ctx.fillStyle = '#ffffff'
          ctx.fillRect(0, 0, canvas.width, canvas.height)

          ctx.scale(scale, scale)
          ctx.drawImage(img, 0, 0, 1600, 950)

          if (canvas.toBlob) {
            canvas.toBlob(blob => {
              if (blob) {
                const pngUrl = URL.createObjectURL(blob)
                const a = document.createElement('a')
                a.href = pngUrl
                a.download = fileName
                document.body.appendChild(a)
                a.click()
                document.body.removeChild(a)
                URL.revokeObjectURL(pngUrl)
                toast.success('Image PNG haute définition téléchargée avec succès.')
              } else {
                // Fallback to toDataURL
                const pngDataUrl = canvas.toDataURL('image/png')
                const a = document.createElement('a')
                a.href = pngDataUrl
                a.download = fileName
                document.body.appendChild(a)
                a.click()
                document.body.removeChild(a)
                toast.success('Image PNG téléchargée avec succès.')
              }
            }, 'image/png')
          } else {
            const pngDataUrl = canvas.toDataURL('image/png')
            const a = document.createElement('a')
            a.href = pngDataUrl
            a.download = fileName
            document.body.appendChild(a)
            a.click()
            document.body.removeChild(a)
            toast.success('Image PNG téléchargée avec succès.')
          }
        } catch (canvasErr) {
          console.error('Canvas conversion error:', canvasErr)
          toast.error('Erreur lors de la conversion PNG. Téléchargement SVG proposé...')
          downloadDiagramImage('svg')
        }
      }

      img.onerror = (e) => {
        console.error('SVG Image load error:', e)
        toast.error('Erreur de rendu de l\'image PNG. Téléchargement automatique en format SVG...')
        downloadDiagramImage('svg')
      }

      img.src = dataUri
    } catch (err) {
      console.error('PNG export error:', err)
      toast.error('Erreur lors de l\'export PNG.')
    }
  }
</script>

<style scoped>
.process-cartography-viewer {
  width: 100%;
}

.interlane-interaction {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 4px 0;
}

.interlane-line {
  flex: 1;
  height: 2px;
  background: repeating-linear-gradient(90deg, #cbd5e1 0, #cbd5e1 6px, transparent 6px, transparent 12px);
}

.interlane-pill {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 5px 14px;
  border-radius: 9999px;
  font-size: 11px;
  font-weight: 700;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.pill-mgmt-real {
  background: #faf5ff;
  border: 1.5px solid #d8b4fe;
  color: #7e22ce;
}

.pill-sup-real {
  background: #eef2ff;
  border: 1.5px solid #a5b4fc;
  color: #4338ca;
}

.focused-process-card {
  border: 2px solid #3b82f6 !important;
  background: #f8fafc;
}

.interaction-col {
  border: 1px solid #e2e8f0;
  background: #ffffff;
}

.inbound-col {
  border-left: 4px solid #3b82f6;
  background: #eff6ff;
}

.center-col {
  border-top: 4px solid #10b981;
  background: #f0fdf4;
}

.outbound-col {
  border-right: 4px solid #16a34a;
  background: #f0fdf4;
}

.partner-box {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  transition: all 0.2s ease;
}

.partner-box:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.partner-inbound {
  border-left: 3px solid #3b82f6;
}

.partner-outbound {
  border-left: 3px solid #16a34a;
}

/* FLOW DIAGRAM */
.cartography-canvas-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
}

.cartography-flow-wrapper {
  display: flex;
  align-items: stretch;
  gap: 12px;
  position: relative;
}

.flow-pillar {
  width: 140px;
  min-width: 130px;
  background: #ffffff;
  border-radius: 12px;
  border: 2px dashed #94a3b8;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  padding: 16px 8px;
  text-align: center;
  position: relative;
}

.pillar-input {
  border-color: #60a5fa;
  background: #f0f9ff;
}

.pillar-output {
  border-color: #4ade80;
  background: #f0fdf4;
}

.pillar-header {
  margin-bottom: 12px;
}

.pillar-title {
  font-weight: 800;
  font-size: 11px;
  letter-spacing: 0.5px;
  color: #1e293b;
}

.pillar-subtitle {
  font-size: 9.5px;
  color: #64748b;
}

.pillar-body {
  display: flex;
  flex-direction: column;
  gap: 6px;
  width: 100%;
}

.input-badge, .output-badge {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 9px;
  font-weight: 600;
  background: #ffffff;
  padding: 4px 6px;
  border-radius: 6px;
  border: 1px solid #cbd5e1;
}

.flow-arrow-right {
  position: absolute;
  right: -20px;
  top: 50%;
  transform: translateY(-50%);
  z-index: 2;
}

.flow-arrow-left {
  position: absolute;
  left: -20px;
  top: 50%;
  transform: translateY(-50%);
  z-index: 2;
}

.flow-lanes {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.lane-container {
  border-radius: 10px;
  padding: 10px 14px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
}

.lane-mgmt {
  background: #faf5ff;
  border: 1.5px solid #d8b4fe;
}

.lane-real {
  background: #f0fdf4;
  border: 1.5px solid #86efac;
}

.lane-sup {
  background: #eef2ff;
  border: 1.5px solid #a5b4fc;
}

.lane-tag {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
  text-transform: uppercase;
}

.tag-mgmt { color: #7e22ce; }
.tag-real { color: #15803d; }
.tag-sup { color: #4338ca; }

.lane-grid {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
}

.realization-flow {
  display: flex;
  flex-wrap: nowrap;
  overflow-x: auto;
  padding-bottom: 4px;
}

.chain-arrow {
  display: flex;
  align-items: center;
  justify-content: center;
}

.process-node {
  flex: 1;
  min-width: 140px;
  max-width: 220px;
  background: #ffffff;
  border-radius: 8px;
  padding: 8px 10px;
  border: 1px solid #cbd5e1;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
}

.process-node:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  border-color: #94a3b8;
}

.node-mgmt { border-top: 3px solid #9333ea; }
.node-real { border-top: 3px solid #16a34a; }
.node-sup { border-top: 3px solid #4f46e5; }

.node-active {
  box-shadow: 0 0 0 3px #f59e0b, 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
  border-color: #f59e0b !important;
  background: #fffbeb !important;
  transform: scale(1.02);
}

.node-upstream {
  box-shadow: 0 0 0 2px #3b82f6 !important;
  background: #eff6ff !important;
}

.node-downstream {
  box-shadow: 0 0 0 2px #10b981 !important;
  background: #f0fdf4 !important;
}

.node-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2px;
}

.node-code {
  font-weight: 800;
  font-size: 10px;
  color: #0f172a;
}

.node-title {
  font-weight: 600;
  font-size: 11px;
  color: #334155;
  line-height: 1.25;
  margin-bottom: 4px;
  min-height: 28px;
}

.node-footer {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 9.5px;
  color: #64748b;
  border-top: 1px dashed #e2e8f0;
  padding-top: 3px;
}

.system-feedback-banner {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 10.5px;
  font-weight: 600;
  color: #92400e;
  background: #fef3c7;
  padding: 6px 10px;
  border-radius: 6px;
  border: 1px dashed #f59e0b;
}

.text-hover-primary:hover {
  color: #2563eb !important;
  text-decoration: underline;
}

.official-table-card {
  background: #ffffff;
  border: 1.5px solid #e2e8f0;
}
</style>

