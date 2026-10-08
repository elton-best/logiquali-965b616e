<template>
  <ClientALayout current-page="swot-pestel">
    <v-container class="pa-6" fluid>
      <PageHeader
        icon="mdi-matrix"
        subtitle="Analyser le contexte interne et externe de l'organisme"
        title="Analyse SWOT / PESTEL"
      >
        <template #actions>
          <v-btn color="primary" :loading="saving" prepend-icon="mdi-content-save" @click="handleSave">
            Enregistrer
          </v-btn>
          <v-btn
            color="secondary"
            :loading="exporting"
            prepend-icon="mdi-file-plus"
            variant="tonal"
            @click="openGenerationDialog('draft')"
          >
            Générer brouillon
          </v-btn>
          <v-btn
            color="secondary"
            :disabled="exporting"
            prepend-icon="mdi-eye"
            variant="text"
            @click="handlePreviewDraft"
          >
            Prévisualiser
          </v-btn>
          <v-btn
            color="secondary"
            :disabled="exporting"
            prepend-icon="mdi-download"
            variant="text"
            @click="handleDownloadDraft"
          >
            Télécharger
          </v-btn>
          <v-btn
            color="warning"
            :disabled="submittingForVerification"
            :loading="submittingForVerification"
            prepend-icon="mdi-shield-check"
            variant="outlined"
            @click="openVerifyDialog"
          >
            Vérifier document
          </v-btn>
        </template>
      </PageHeader>

      <v-card class="mb-4" elevation="0" rounded="xl" style="background: white; border: 2px solid #e2e8f0;">
        <v-tabs v-model="activeTab" bg-color="transparent" class="px-4" color="primary">
          <v-tab class="text-none font-weight-bold" value="internal">
            <v-icon start>mdi-office-building</v-icon>
            Enjeux internes
          </v-tab>
          <v-tab class="text-none font-weight-bold" value="external">
            <v-icon start>mdi-earth</v-icon>
            Enjeux externes
          </v-tab>
          <v-tab class="text-none font-weight-bold" value="major">
            <v-icon start>mdi-star</v-icon>
            Enjeux majeurs
          </v-tab>
          <v-tab class="text-none font-weight-bold" value="summary">
            <v-icon start>mdi-eye</v-icon>
            Vue d'ensemble
          </v-tab>
        </v-tabs>
      </v-card>

      <v-window v-model="activeTab">
        <!-- Enjeux internes -->
        <v-window-item value="internal">
          <v-row>
            <v-col v-for="criterion in internalCriteria" :key="criterion.value" cols="12">
              <v-card elevation="0" rounded="xl" style="border: 2px solid #e2e8f0; overflow: hidden;">
                <div class="pa-6 d-flex align-center" :style="`background: linear-gradient(135deg, ${criterion.color}15 0%, ${criterion.color}05 100%)`">
                  <v-avatar class="mr-4 elevation-4" :color="criterion.color" size="56">
                    <v-icon color="white" size="28">{{ criterion.icon }}</v-icon>
                  </v-avatar>
                  <h3 class="text-h5 font-weight-bold">{{ criterion.title }}</h3>
                </div>
                <v-divider />
                <v-card-text class="pa-6">
                  <v-row>
                    <v-col cols="12" md="6">
                      <div class="d-flex align-center justify-space-between mb-4">
                        <div class="text-subtitle-1 font-weight-bold text-success d-flex align-center">
                          <v-icon class="mr-2" size="20">mdi-check-circle</v-icon>
                          Favorable (Forces)
                        </div>
                        <v-btn
                          color="success"
                          icon="mdi-plus"
                          size="small"
                          variant="tonal"
                          @click="addItem('internal', criterion.value, 'favorable')"
                        />
                      </div>
                      <v-card
                        v-for="(item, index) in form.internal[criterion.value].favorable"
                        :key="index"
                        class="mb-3 hover-lift"
                        rounded="lg"
                        style="border-left: 4px solid #22c55e;"
                        variant="outlined"
                      >
                        <v-card-text class="pa-3">
                          <div class="d-flex gap-2">
                            <v-chip class="mt-1" color="success" size="small">{{ index + 1 }}</v-chip>
                            <v-textarea
                              v-model="form.internal[criterion.value].favorable[index]"
                              class="flex-grow-1"
                              density="compact"
                              flat
                              hide-details
                              placeholder="Décrivez un aspect favorable..."
                              rows="2"
                              variant="solo"
                            />
                            <v-btn
                              class="mt-1"
                              color="error"
                              icon="mdi-delete"
                              size="small"
                              variant="text"
                              @click="removeItem('internal', criterion.value, 'favorable', index)"
                            />
                          </div>
                        </v-card-text>
                      </v-card>
                      <v-alert
                        v-if="form.internal[criterion.value].favorable.length === 0"
                        density="compact"
                        rounded="lg"
                        type="info"
                        variant="tonal"
                      >
                        <v-icon size="small" start>mdi-information</v-icon>
                        Cliquez sur + pour ajouter
                      </v-alert>
                    </v-col>
                    <v-col cols="12" md="6">
                      <div class="d-flex align-center justify-space-between mb-4">
                        <div class="text-subtitle-1 font-weight-bold text-error d-flex align-center">
                          <v-icon class="mr-2" size="20">mdi-alert-circle</v-icon>
                          Défavorable (Faiblesses)
                        </div>
                        <v-btn
                          color="error"
                          icon="mdi-plus"
                          size="small"
                          variant="tonal"
                          @click="addItem('internal', criterion.value, 'unfavorable')"
                        />
                      </div>
                      <v-card
                        v-for="(item, index) in form.internal[criterion.value].unfavorable"
                        :key="index"
                        class="mb-3 hover-lift"
                        rounded="lg"
                        style="border-left: 4px solid #ef4444;"
                        variant="outlined"
                      >
                        <v-card-text class="pa-3">
                          <div class="d-flex gap-2">
                            <v-chip class="mt-1" color="error" size="small">{{ index + 1 }}</v-chip>
                            <v-textarea
                              v-model="form.internal[criterion.value].unfavorable[index]"
                              class="flex-grow-1"
                              density="compact"
                              flat
                              hide-details
                              placeholder="Décrivez un aspect défavorable..."
                              rows="2"
                              variant="solo"
                            />
                            <v-btn
                              class="mt-1"
                              color="error"
                              icon="mdi-delete"
                              size="small"
                              variant="text"
                              @click="removeItem('internal', criterion.value, 'unfavorable', index)"
                            />
                          </div>
                        </v-card-text>
                      </v-card>
                      <v-alert
                        v-if="form.internal[criterion.value].unfavorable.length === 0"
                        density="compact"
                        rounded="lg"
                        type="info"
                        variant="tonal"
                      >
                        <v-icon size="small" start>mdi-information</v-icon>
                        Cliquez sur + pour ajouter
                      </v-alert>
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>
        </v-window-item>

        <!-- Enjeux externes -->
        <v-window-item value="external">
          <v-row>
            <v-col v-for="criterion in externalCriteria" :key="criterion.value" cols="12">
              <v-card elevation="0" rounded="xl" style="border: 2px solid #e2e8f0; overflow: hidden;">
                <div class="pa-6 d-flex align-center" :style="`background: linear-gradient(135deg, ${criterion.color}15 0%, ${criterion.color}05 100%)`">
                  <v-avatar class="mr-4 elevation-4" :color="criterion.color" size="56">
                    <v-icon color="white" size="28">{{ criterion.icon }}</v-icon>
                  </v-avatar>
                  <h3 class="text-h5 font-weight-bold">{{ criterion.title }}</h3>
                </div>
                <v-divider />
                <v-card-text class="pa-6">
                  <v-row>
                    <v-col cols="12" md="6">
                      <div class="d-flex align-center justify-space-between mb-4">
                        <div class="text-subtitle-1 font-weight-bold text-info d-flex align-center">
                          <v-icon class="mr-2" size="20">mdi-rocket-launch</v-icon>
                          Favorable (Opportunités)
                        </div>
                        <v-btn
                          color="info"
                          icon="mdi-plus"
                          size="small"
                          variant="tonal"
                          @click="addItem('external', criterion.value, 'favorable')"
                        />
                      </div>
                      <v-card
                        v-for="(item, index) in form.external[criterion.value].favorable"
                        :key="index"
                        class="mb-3 hover-lift"
                        rounded="lg"
                        style="border-left: 4px solid #3b82f6;"
                        variant="outlined"
                      >
                        <v-card-text class="pa-3">
                          <div class="d-flex gap-2">
                            <v-chip class="mt-1" color="info" size="small">{{ index + 1 }}</v-chip>
                            <v-textarea
                              v-model="form.external[criterion.value].favorable[index]"
                              class="flex-grow-1"
                              density="compact"
                              flat
                              hide-details
                              placeholder="Décrivez une opportunité..."
                              rows="2"
                              variant="solo"
                            />
                            <v-btn
                              class="mt-1"
                              color="error"
                              icon="mdi-delete"
                              size="small"
                              variant="text"
                              @click="removeItem('external', criterion.value, 'favorable', index)"
                            />
                          </div>
                        </v-card-text>
                      </v-card>
                      <v-alert
                        v-if="form.external[criterion.value].favorable.length === 0"
                        density="compact"
                        rounded="lg"
                        type="info"
                        variant="tonal"
                      >
                        <v-icon size="small" start>mdi-information</v-icon>
                        Cliquez sur + pour ajouter
                      </v-alert>
                    </v-col>
                    <v-col cols="12" md="6">
                      <div class="d-flex align-center justify-space-between mb-4">
                        <div class="text-subtitle-1 font-weight-bold text-warning d-flex align-center">
                          <v-icon class="mr-2" size="20">mdi-lightning-bolt</v-icon>
                          Défavorable (Menaces)
                        </div>
                        <v-btn
                          color="warning"
                          icon="mdi-plus"
                          size="small"
                          variant="tonal"
                          @click="addItem('external', criterion.value, 'unfavorable')"
                        />
                      </div>
                      <v-card
                        v-for="(item, index) in form.external[criterion.value].unfavorable"
                        :key="index"
                        class="mb-3 hover-lift"
                        rounded="lg"
                        style="border-left: 4px solid #f59e0b;"
                        variant="outlined"
                      >
                        <v-card-text class="pa-3">
                          <div class="d-flex gap-2">
                            <v-chip class="mt-1" color="warning" size="small">{{ index + 1 }}</v-chip>
                            <v-textarea
                              v-model="form.external[criterion.value].unfavorable[index]"
                              class="flex-grow-1"
                              density="compact"
                              flat
                              hide-details
                              placeholder="Décrivez une menace..."
                              rows="2"
                              variant="solo"
                            />
                            <v-btn
                              class="mt-1"
                              color="error"
                              icon="mdi-delete"
                              size="small"
                              variant="text"
                              @click="removeItem('external', criterion.value, 'unfavorable', index)"
                            />
                          </div>
                        </v-card-text>
                      </v-card>
                      <v-alert
                        v-if="form.external[criterion.value].unfavorable.length === 0"
                        density="compact"
                        rounded="lg"
                        type="info"
                        variant="tonal"
                      >
                        <v-icon size="small" start>mdi-information</v-icon>
                        Cliquez sur + pour ajouter
                      </v-alert>
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>
        </v-window-item>

        <!-- Enjeux majeurs -->
        <v-window-item value="major">
          <v-card elevation="0" rounded="xl" style="background: linear-gradient(135deg, #f59e0b15 0%, #f59e0b05 100%)">
            <v-card-text class="pa-8">
              <div class="d-flex align-center justify-space-between mb-6">
                <div class="d-flex align-center">
                  <v-avatar class="mr-4 elevation-4" color="warning" size="64">
                    <v-icon color="white" size="32">mdi-star</v-icon>
                  </v-avatar>
                  <div>
                    <h2 class="text-h4 font-weight-bold">Enjeux majeurs</h2>
                    <p class="text-body-1 text-medium-emphasis mb-0">Identifiez les enjeux les plus critiques pour votre organisation</p>
                  </div>
                </div>
                <v-btn color="warning" prepend-icon="mdi-plus" size="large" @click="addMajorIssue">
                  Ajouter
                </v-btn>
              </div>

              <v-row>
                <v-col v-for="(issue, index) in form.majorIssues" :key="index" cols="12">
                  <v-card class="hover-lift" elevation="2" rounded="lg" style="border-left: 4px solid #f59e0b">
                    <v-card-text class="pa-4">
                      <div class="d-flex gap-3">
                        <v-chip class="mt-1" color="warning" size="small">{{ index + 1 }}</v-chip>
                        <v-textarea
                          v-model="form.majorIssues[index]"
                          class="flex-grow-1"
                          flat
                          hide-details
                          placeholder="Décrivez un enjeu majeur en tenant compte de l'analyse SWOT/PESTEL..."
                          rows="4"
                          variant="solo"
                        />
                        <v-btn
                          class="mt-1"
                          color="error"
                          icon="mdi-delete"
                          size="small"
                          variant="text"
                          @click="removeMajorIssue(index)"
                        />
                      </div>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>

              <v-alert
                v-if="form.majorIssues.length === 0"
                class="mt-4"
                rounded="lg"
                type="info"
                variant="tonal"
              >
                <v-icon start>mdi-information</v-icon>
                Aucun enjeu majeur identifié. Cliquez sur "Ajouter" pour commencer.
              </v-alert>
            </v-card-text>
          </v-card>
        </v-window-item>

        <!-- Vue d'ensemble -->
        <v-window-item class="summary-overview" value="summary">
          <v-card elevation="0" rounded="xl" style="background: linear-gradient(135deg, #6366f115 0%, #6366f105 100%)">
            <v-card-text class="pa-8">
              <!-- Statistiques -->
              <v-card class="mb-6 summary-stats" elevation="2" rounded="lg" style="background: linear-gradient(135deg, #6366f115 0%, #ffffff 100%)">
                <v-card-text class="pa-6">
                  <h3 class="text-h5 font-weight-bold mb-4">
                    <v-icon class="mr-2" color="indigo">mdi-chart-bar</v-icon>
                    Statistiques
                  </h3>
                  <v-row>
                    <v-col cols="6" md="3">
                      <v-card class="text-center pa-4" variant="outlined">
                        <div class="text-h3 font-weight-bold text-success">{{ totalStrengths }}</div>
                        <div class="text-caption text-medium-emphasis">Forces</div>
                      </v-card>
                    </v-col>
                    <v-col cols="6" md="3">
                      <v-card class="text-center pa-4" variant="outlined">
                        <div class="text-h3 font-weight-bold text-error">{{ totalWeaknesses }}</div>
                        <div class="text-caption text-medium-emphasis">Faiblesses</div>
                      </v-card>
                    </v-col>
                    <v-col cols="6" md="3">
                      <v-card class="text-center pa-4" variant="outlined">
                        <div class="text-h3 font-weight-bold text-info">{{ totalOpportunities }}</div>
                        <div class="text-caption text-medium-emphasis">Opportunités</div>
                      </v-card>
                    </v-col>
                    <v-col cols="6" md="3">
                      <v-card class="text-center pa-4" variant="outlined">
                        <div class="text-h3 font-weight-bold text-warning">{{ totalThreats }}</div>
                        <div class="text-caption text-medium-emphasis">Menaces</div>
                      </v-card>
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card>

              <div class="d-flex align-center mb-6">
                <v-avatar class="mr-4 elevation-4" color="indigo" size="64">
                  <v-icon color="white" size="32">mdi-eye</v-icon>
                </v-avatar>
                <div>
                  <h2 class="text-h4 font-weight-bold">Vue d'ensemble de l'analyse</h2>
                  <p class="text-body-1 text-medium-emphasis mb-0">Récapitulatif de tous les enjeux identifiés</p>
                </div>
              </div>

              <!-- Statistiques Synthétiques (REQ-4.1-02) -->
              <v-row class="mb-6" dense>
                <v-col cols="12" sm="6" md="3">
                  <v-card class="elevation-1" color="success" rounded="xl" variant="tonal">
                    <v-card-text class="d-flex align-center justify-space-between pa-4">
                      <div>
                        <div class="text-caption text-medium-emphasis font-weight-bold">Forces (Internes)</div>
                        <div class="text-h4 font-weight-bold text-success">{{ totalStrengths }}</div>
                      </div>
                      <v-avatar color="success" size="48" variant="flat">
                        <v-icon color="white">mdi-shield-check</v-icon>
                      </v-avatar>
                    </v-card-text>
                  </v-card>
                </v-col>
                <v-col cols="12" sm="6" md="3">
                  <v-card class="elevation-1" color="error" rounded="xl" variant="tonal">
                    <v-card-text class="d-flex align-center justify-space-between pa-4">
                      <div>
                        <div class="text-caption text-medium-emphasis font-weight-bold">Faiblesses (Internes)</div>
                        <div class="text-h4 font-weight-bold text-error">{{ totalWeaknesses }}</div>
                      </div>
                      <v-avatar color="error" size="48" variant="flat">
                        <v-icon color="white">mdi-alert-circle</v-icon>
                      </v-avatar>
                    </v-card-text>
                  </v-card>
                </v-col>
                <v-col cols="12" sm="6" md="3">
                  <v-card class="elevation-1" color="info" rounded="xl" variant="tonal">
                    <v-card-text class="d-flex align-center justify-space-between pa-4">
                      <div>
                        <div class="text-caption text-medium-emphasis font-weight-bold">Enjeux Externes</div>
                        <div class="text-h4 font-weight-bold text-info">{{ totalOpportunities + totalThreats }}</div>
                      </div>
                      <v-avatar color="info" size="48" variant="flat">
                        <v-icon color="white">mdi-earth</v-icon>
                      </v-avatar>
                    </v-card-text>
                  </v-card>
                </v-col>
                <v-col cols="12" sm="6" md="3">
                  <v-card class="elevation-2" rounded="xl" style="background: linear-gradient(135deg, #f59e0b20, #f59e0b10); border: 2px solid #f59e0b;">
                    <v-card-text class="d-flex align-center justify-space-between pa-4">
                      <div>
                        <div class="text-caption text-amber-darken-4 font-weight-bold">Enjeux Majeurs</div>
                        <div class="text-h4 font-weight-bold text-warning">{{ form.majorIssues.length }}</div>
                      </div>
                      <v-avatar color="warning" size="48" variant="flat">
                        <v-icon color="white">mdi-star</v-icon>
                      </v-avatar>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>

              <!-- Enjeux Internes -->
              <v-card class="mb-6 summary-section-card" elevation="2" rounded="lg">
                <v-card-title class="pa-4 bg-success text-white">
                  <v-icon class="mr-2">mdi-office-building</v-icon>
                  Enjeux Internes (Forces & Faiblesses)
                </v-card-title>
                <v-card-text class="pa-4">
                  <v-row>
                    <v-col v-for="criterion in internalCriteria" :key="criterion.value" cols="12" md="6">
                      <v-card class="mb-3 summary-item-card" rounded="lg" variant="outlined">
                        <v-card-title class="pa-3 d-flex align-center" :style="`background: ${criterion.color}15`">
                          <v-icon class="mr-2" :color="criterion.color">{{ criterion.icon }}</v-icon>
                          <span class="font-weight-bold">{{ criterion.title }}</span>
                        </v-card-title>
                        <v-card-text class="pa-3">
                          <div v-if="form.internal[criterion.value].favorable.length > 0" class="mb-3">
                            <div class="text-subtitle-2 font-weight-bold text-success mb-2">
                              <v-icon class="mr-1" size="16">mdi-check-circle</v-icon>
                              Forces ({{ form.internal[criterion.value].favorable.length }})
                            </div>
                            <ul class="pl-4">
                              <li v-for="(item, idx) in form.internal[criterion.value].favorable" :key="idx" class="text-body-2 mb-1">
                                {{ item || '(Vide)' }}
                              </li>
                            </ul>
                          </div>
                          <div v-if="form.internal[criterion.value].unfavorable.length > 0">
                            <div class="text-subtitle-2 font-weight-bold text-error mb-2">
                              <v-icon class="mr-1" size="16">mdi-alert-circle</v-icon>
                              Faiblesses ({{ form.internal[criterion.value].unfavorable.length }})
                            </div>
                            <ul class="pl-4">
                              <li v-for="(item, idx) in form.internal[criterion.value].unfavorable" :key="idx" class="text-body-2 mb-1">
                                {{ item || '(Vide)' }}
                              </li>
                            </ul>
                          </div>
                          <v-alert
                            v-if="form.internal[criterion.value].favorable.length === 0 && form.internal[criterion.value].unfavorable.length === 0"
                            class="text-caption"
                            density="compact"
                            type="info"
                            variant="tonal"
                          >
                            Aucune donnée saisie
                          </v-alert>
                        </v-card-text>
                      </v-card>
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card>

              <!-- Enjeux Externes -->
              <v-card class="mb-6 summary-section-card" elevation="2" rounded="lg">
                <v-card-title class="pa-4 bg-info text-white">
                  <v-icon class="mr-2">mdi-earth</v-icon>
                  Enjeux Externes (Opportunités & Menaces)
                </v-card-title>
                <v-card-text class="pa-4">
                  <v-row>
                    <v-col v-for="criterion in externalCriteria" :key="criterion.value" cols="12" md="6">
                      <v-card class="mb-3 summary-item-card" rounded="lg" variant="outlined">
                        <v-card-title class="pa-3 d-flex align-center" :style="`background: ${criterion.color}15`">
                          <v-icon class="mr-2" :color="criterion.color">{{ criterion.icon }}</v-icon>
                          <span class="font-weight-bold">{{ criterion.title }}</span>
                        </v-card-title>
                        <v-card-text class="pa-3">
                          <div v-if="form.external[criterion.value].favorable.length > 0" class="mb-3">
                            <div class="text-subtitle-2 font-weight-bold text-info mb-2">
                              <v-icon class="mr-1" size="16">mdi-rocket-launch</v-icon>
                              Opportunités ({{ form.external[criterion.value].favorable.length }})
                            </div>
                            <ul class="pl-4">
                              <li v-for="(item, idx) in form.external[criterion.value].favorable" :key="idx" class="text-body-2 mb-1">
                                {{ item || '(Vide)' }}
                              </li>
                            </ul>
                          </div>
                          <div v-if="form.external[criterion.value].unfavorable.length > 0">
                            <div class="text-subtitle-2 font-weight-bold text-warning mb-2">
                              <v-icon class="mr-1" size="16">mdi-lightning-bolt</v-icon>
                              Menaces ({{ form.external[criterion.value].unfavorable.length }})
                            </div>
                            <ul class="pl-4">
                              <li v-for="(item, idx) in form.external[criterion.value].unfavorable" :key="idx" class="text-body-2 mb-1">
                                {{ item || '(Vide)' }}
                              </li>
                            </ul>
                          </div>
                          <v-alert
                            v-if="form.external[criterion.value].favorable.length === 0 && form.external[criterion.value].unfavorable.length === 0"
                            class="text-caption"
                            density="compact"
                            type="info"
                            variant="tonal"
                          >
                            Aucune donnée saisie
                          </v-alert>
                        </v-card-text>
                      </v-card>
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card>

              <!-- Synthèses -->
              <v-card class="mb-6" elevation="2" rounded="lg">
                <v-card-title class="pa-4 bg-purple text-white">
                  <v-icon class="mr-2">mdi-file-document-outline</v-icon>
                  Synthèses
                </v-card-title>
                <v-card-text class="pa-4">
                  <v-row>
                    <v-col cols="12" md="6">
                      <h4 class="text-h6 font-weight-bold mb-3">
                        <v-icon class="mr-2" color="success">mdi-office-building</v-icon>
                        Enjeux internes
                      </h4>
                      <div v-if="form.synthesis.internal" class="text-body-2 pa-3 bg-grey-lighten-5 rounded">
                        {{ form.synthesis.internal }}
                      </div>
                      <v-alert v-else density="compact" type="info" variant="tonal">
                        Aucune synthèse rédigée
                      </v-alert>
                    </v-col>
                    <v-col cols="12" md="6">
                      <h4 class="text-h6 font-weight-bold mb-3">
                        <v-icon class="mr-2" color="info">mdi-earth</v-icon>
                        Enjeux externes
                      </h4>
                      <div v-if="form.synthesis.external" class="text-body-2 pa-3 bg-grey-lighten-5 rounded">
                        {{ form.synthesis.external }}
                      </div>
                      <v-alert v-else density="compact" type="info" variant="tonal">
                        Aucune synthèse rédigée
                      </v-alert>
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card>

              <!-- Enjeux Majeurs (REQ-4.1-01) -->
              <v-card class="summary-section-card mb-6" elevation="2" rounded="xl" style="border: 1px solid #f59e0b40;">
                <v-card-title class="pa-4 bg-warning text-white d-flex align-center justify-space-between">
                  <div class="d-flex align-center">
                    <v-icon class="mr-2">mdi-star</v-icon>
                    <span>Enjeux Majeurs Stratégiques ({{ form.majorIssues.length }})</span>
                  </div>
                  <v-chip class="font-weight-bold" color="white" size="small" variant="flat">
                    Impact Critique
                  </v-chip>
                </v-card-title>
                <v-card-text class="pa-4">
                  <v-row v-if="form.majorIssues.length > 0" dense>
                    <v-col
                      v-for="(issue, index) in form.majorIssues"
                      :key="index"
                      cols="12"
                      md="6"
                    >
                      <v-card
                        class="pa-4 h-100"
                        rounded="lg"
                        style="border-left: 5px solid #f59e0b; background-color: #fffdfa;"
                        variant="outlined"
                      >
                        <div class="d-flex align-center justify-space-between mb-2">
                          <v-chip class="font-weight-bold" color="warning" size="small">
                            Enjeu majeur #{{ index + 1 }}
                          </v-chip>
                          <span class="text-caption font-weight-bold text-amber-darken-3">Priorité Stratégique</span>
                        </div>
                        <div class="text-body-2 font-weight-medium text-wrap" style="line-height: 1.5; word-break: break-word;">
                          {{ issue || '(Enjeu non décrit)' }}
                        </div>
                      </v-card>
                    </v-col>
                  </v-row>
                  <v-alert v-else rounded="lg" type="info" variant="tonal">
                    Aucun enjeu majeur identifié
                  </v-alert>
                </v-card-text>
              </v-card>

            </v-card-text>
          </v-card>
        </v-window-item>
      </v-window>

      <GeneratedDocumentConfigDialog
        v-model="generationDialog"
        :loading="exporting"
        :site-id="resolveSiteId()"
        @confirm="confirmGenerationConfig"
      />

      <v-dialog v-model="verifyDialog" max-width="560">
        <v-card rounded="xl">
          <v-card-title class="pa-4">Soumettre pour vérification</v-card-title>
          <v-card-text class="pa-4">
            <div class="text-body-2 mb-2">Code du document</div>
            <v-alert type="info" variant="tonal">{{ exportedDocumentCode || '—' }}</v-alert>
            <v-alert v-if="!lastExportedDocumentId" class="mt-3" type="warning" variant="tonal">
              Aucun brouillon n’a encore été généré. La confirmation générera le brouillon pour le workflow sans lancer de téléchargement.
            </v-alert>
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

      <!-- Dialog prévisualisation PDF -->
      <v-dialog v-model="previewDialog" fullscreen>
        <v-card>
          <v-toolbar color="primary" density="compact">
            <v-toolbar-title>Prévisualisation — {{ previewFilename }}</v-toolbar-title>
            <v-spacer />
            <v-btn icon="mdi-close" @click="closePreview" />
          </v-toolbar>
          <iframe v-if="previewBlobUrl" :src="previewBlobUrl" style="width:100%; height:calc(100vh - 48px); border:none;" />
        </v-card>
      </v-dialog>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref, watch } from 'vue'
  import api from '@/api/client'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import GeneratedDocumentConfigDialog from '@/modules/clienta/components/documents/GeneratedDocumentConfigDialog.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import { useDocumentFlow } from '@/modules/clienta/composables/useDocumentFlow'
  import { useToast } from '@/modules/shared/composables/useToast'
  import { useAuthStore } from '@/stores/auth'

  const toast = useToast()
  const authStore = useAuthStore()
  const activeTab = ref('internal')
  const saving = ref(false)
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
  const currentId = ref<number | null>(null)

  const {
    exporting, submittingForVerification, verificationSent,
    lastExportedDocumentId, exportedDocumentCode,
    previewDialog, previewBlobUrl, previewFilename,
    verifyDialog, ensureDraftReady, handlePreviewDraft, closePreview,
    handleDownloadDraft, openVerifyDialog, submitForVerification,
  } = useDocumentFlow(
    async () => {
      const siteId = resolveSiteId()
      if (!siteId) { toast.error('Veuillez sélectionner un site.'); return null }
      if (!generationContext.value) { openGenerationDialog('draft'); return null }
      const response = await api.post('/contexts/generate-draft', { site_id: siteId, ...generationContext.value })
      const info = extractDocumentInfo(response.data)
      return info.id ? info as { id: number, code: string } : null
    },
    () => `Contexte_Organisme_${authStore.currentSite?.name || 'Site'}_${new Date().toISOString().split('T')[0]}.pdf`,
  )

  const internalCriteria = [
    { title: 'Culture', value: 'culture' as const, icon: 'mdi-account-heart', color: 'purple' },
    { title: 'Valeurs', value: 'values' as const, icon: 'mdi-star', color: 'amber' },
    { title: 'Performance', value: 'performance' as const, icon: 'mdi-chart-line', color: 'success' },
    { title: 'Connaissance', value: 'knowledge' as const, icon: 'mdi-school-outline', color: 'info' },
    { title: 'Autres', value: 'other_internal' as const, icon: 'mdi-dots-horizontal', color: 'grey' },
  ]

  const externalCriteria = [
    { title: 'Politique', value: 'political' as const, icon: 'mdi-bank', color: 'primary' },
    { title: 'Économique', value: 'economic' as const, icon: 'mdi-currency-usd', color: 'success' },
    { title: 'Social', value: 'social' as const, icon: 'mdi-account-group', color: 'info' },
    { title: 'Technologique', value: 'technological' as const, icon: 'mdi-chip', color: 'purple' },
    { title: 'Légal', value: 'legal' as const, icon: 'mdi-gavel', color: 'orange' },
  ]

  type InternalCriterion = 'culture' | 'values' | 'performance' | 'knowledge' | 'other_internal'
  type ExternalCriterion = 'political' | 'economic' | 'social' | 'technological' | 'legal'

  interface CriterionData {
    favorable: string[]
    unfavorable: string[]
  }

  interface FormData {
    internal: Record<InternalCriterion, CriterionData>
    external: Record<ExternalCriterion, CriterionData>
    synthesis: {
      internal: string
      external: string
    }
    majorIssues: string[]
  }

  const form = ref<FormData>({
    internal: {
      culture: { favorable: [], unfavorable: [] },
      values: { favorable: [], unfavorable: [] },
      performance: { favorable: [], unfavorable: [] },
      knowledge: { favorable: [], unfavorable: [] },
      other_internal: { favorable: [], unfavorable: [] },
    },
    external: {
      political: { favorable: [], unfavorable: [] },
      economic: { favorable: [], unfavorable: [] },
      social: { favorable: [], unfavorable: [] },
      technological: { favorable: [], unfavorable: [] },
      legal: { favorable: [], unfavorable: [] },
    },
    synthesis: {
      internal: '',
      external: '',
    },
    majorIssues: [],
  })

  const totalStrengths = computed(() => {
    return Object.values(form.value.internal).reduce((sum, criterion) => sum + criterion.favorable.filter(Boolean).length, 0)
  })

  const totalWeaknesses = computed(() => {
    return Object.values(form.value.internal).reduce((sum, criterion) => sum + criterion.unfavorable.filter(Boolean).length, 0)
  })

  const totalOpportunities = computed(() => {
    return Object.values(form.value.external).reduce((sum, criterion) => sum + criterion.favorable.filter(Boolean).length, 0)
  })

  const totalThreats = computed(() => {
    return Object.values(form.value.external).reduce((sum, criterion) => sum + criterion.unfavorable.filter(Boolean).length, 0)
  })

  function resolveSiteId (): number | null {
    const storedSiteId = Number(localStorage.getItem('current_site_id'))
    const siteId = authStore.currentSiteId ?? (Number.isFinite(storedSiteId) ? storedSiteId : null)
    return Number.isFinite(Number(siteId)) ? Number(siteId) : null
  }

  async function loadContextForSite () {
    const siteId = resolveSiteId()
    if (!siteId) return

    try {
      const { data } = await api.get('/contexts', {
        params: { site_id: siteId, type: 'swot_pestel' },
      })
      const record = data?.data?.[0]
      if (record?.attributes) {
        currentId.value = record.id
        const attrs = record.attributes

        // Charger les données depuis les champs JSON
        try {
          if (attrs.swot_weaknesses) {
            const internalData = JSON.parse(attrs.swot_weaknesses)
            if (internalData && typeof internalData === 'object') {
              form.value.internal = { ...form.value.internal, ...internalData }
            }
          }
          if (attrs.swot_opportunities) {
            const externalData = JSON.parse(attrs.swot_opportunities)
            if (externalData && typeof externalData === 'object') {
              form.value.external = { ...form.value.external, ...externalData }
            }
          }
          if (attrs.swot_threats) {
            const majorIssues = JSON.parse(attrs.swot_threats)
            if (Array.isArray(majorIssues)) {
              form.value.majorIssues = majorIssues
            }
          }
        } catch (error) {
          console.error('Erreur parsing JSON:', error)
        }

        // Charger les synthèses
        form.value.synthesis.internal = attrs.swot_strengths || ''
        form.value.synthesis.external = attrs.pestel_political || ''
      }
    } catch (error) {
      console.error('Erreur chargement:', error)
    }
  }

  watch(() => authStore.currentSiteId, () => { verificationSent.value = false })

  onMounted(loadContextForSite)
  watch(() => authStore.currentSiteId, loadContextForSite)

  async function handleSave () {
    const siteId = resolveSiteId()
    if (!siteId) {
      toast.error('Veuillez sélectionner un site.')
      return
    }

    saving.value = true
    try {
      const payload = {
        site_id: siteId,
        type: 'swot_pestel',
        title: 'Analyse SWOT/PESTEL',
        swot_strengths: form.value.synthesis.internal,
        swot_weaknesses: JSON.stringify(form.value.internal),
        swot_opportunities: JSON.stringify(form.value.external),
        swot_threats: JSON.stringify(form.value.majorIssues),
        pestel_political: form.value.synthesis.external,
      }

      if (currentId.value) {
        await api.put(`/contexts/${currentId.value}`, payload)
      } else {
        const { data } = await api.post('/contexts', payload)
        currentId.value = data?.data?.id || null
      }
      toast.success('Analyse enregistrée.')
    } catch (error) {
      console.error('Erreur sauvegarde:', error)
      toast.error('Erreur lors de l\'enregistrement.')
    } finally {
      saving.value = false
    }
  }

  function addItem (category: 'internal' | 'external', criterion: string, type: 'favorable' | 'unfavorable') {
    if (category === 'internal') {
      form.value.internal[criterion as InternalCriterion][type].push('')
    } else {
      form.value.external[criterion as ExternalCriterion][type].push('')
    }
  }

  function removeItem (category: 'internal' | 'external', criterion: string, type: 'favorable' | 'unfavorable', index: number) {
    if (category === 'internal') {
      form.value.internal[criterion as InternalCriterion][type].splice(index, 1)
    } else {
      form.value.external[criterion as ExternalCriterion][type].splice(index, 1)
    }
  }

  function addMajorIssue () {
    form.value.majorIssues.push('')
  }

  function removeMajorIssue (index: number) {
    form.value.majorIssues.splice(index, 1)
  }

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
      const result = await generateDraft()
      if (result) {
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

  async function generateDraft (): Promise<{ id: number, code: string } | null> {
    const siteId = resolveSiteId()
    if (!siteId) {
      toast.error('Veuillez sélectionner un site.')
      return null
    }
    if (!generationContext.value) {
      openGenerationDialog('draft')
      return null
    }

    exporting.value = true
    try {
      const response = await api.post('/contexts/generate-draft', {
        site_id: siteId,
        ...generationContext.value,
      })
      const info = extractDocumentInfo(response.data)
      if (info.id) {
        toast.success('Brouillon généré')
        return info as { id: number, code: string }
      }
      toast.error('Impossible de générer le brouillon.')
      return null
    } catch (error: any) {
      console.error('Erreur export:', error)
      toast.error('Erreur lors de la génération du brouillon')
      return null
    } finally {
      exporting.value = false
    }
  }
</script>

<style scoped>
.summary-overview :deep(.v-card-text) {
  padding: 16px;
}

.summary-overview :deep(.text-h4) {
  font-size: 1.35rem;
  line-height: 1.8rem;
}

.summary-overview :deep(.text-h5) {
  font-size: 1.05rem;
  line-height: 1.4rem;
}

.summary-overview :deep(.text-h6) {
  font-size: 0.95rem;
  line-height: 1.3rem;
}

.summary-overview :deep(.text-body-1) {
  font-size: 0.9rem;
}

.summary-overview :deep(.text-body-2) {
  font-size: 0.85rem;
}

.summary-overview :deep(.text-h3) {
  font-size: 1.55rem;
}

.summary-overview :deep(.summary-stats .v-card-text) {
  padding: 12px;
}

.summary-overview :deep(.summary-stats .v-card) {
  box-shadow: none;
}

.summary-overview :deep(.summary-stats .text-h3) {
  font-size: 1.25rem;
}

.summary-overview :deep(.summary-stats .text-caption) {
  font-size: 0.7rem;
}

.summary-overview :deep(.summary-stats .v-card .v-card-text) {
  padding: 10px;
}

.summary-overview :deep(.summary-section-card) {
  margin-bottom: 12px !important;
}

.summary-overview :deep(.summary-section-card .v-card-title) {
  padding: 10px 14px;
  font-size: 0.9rem;
}

.summary-overview :deep(.summary-section-card .v-card-text) {
  padding: 10px 14px;
}

.summary-overview :deep(.summary-item-card) {
  margin-bottom: 8px;
}

.summary-overview :deep(.summary-item-card .v-card-title) {
  padding: 8px 10px;
  font-size: 0.85rem;
}

.summary-overview :deep(.summary-item-card .v-card-text) {
  padding: 8px 10px;
}
.hover-lift {
  transition: all 0.2s ease;
}

.hover-lift:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}
</style>
