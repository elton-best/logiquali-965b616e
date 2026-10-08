<template>
  <ClientALayout current-page="iso-operations">
    <PageHeader
      icon="mdi-account-tie-hat"
      subtitle="Base de données prestataires, contrats types et suivi des contrats signés"
      title="Gestion des prestataires"
    >
      <template #actions>
        <v-btn
          color="primary"
          prepend-icon="mdi-file-document-plus-outline"
          variant="tonal"
          @click="procedureDialog = true"
        >
          Ajouter procédure ISO
        </v-btn>
      </template>
    </PageHeader>

    <div class="provider-management-page">
      <ProviderHero @create="openCreateDialog" />

      <v-tabs v-model="activeTab" class="mb-3 modern-tabs provider-tabs" color="primary" grow>
        <v-tab v-for="tab in tabItems" :key="tab.value" :value="tab.value">
          <v-icon class="mr-2" size="18">{{ tab.icon }}</v-icon>
          <span>{{ tab.label }}</span>
        </v-tab>
      </v-tabs>
      <v-card class="tab-context mb-4" elevation="0" rounded="lg">
        <v-card-text class="d-flex align-center justify-space-between flex-wrap ga-3 py-4">
          <div>
            <div class="text-subtitle-1 font-weight-bold">{{ activeTabMeta.label }}</div>
            <div class="text-body-2 text-medium-emphasis">{{ activeTabMeta.description }}</div>
          </div>
          <div class="d-flex flex-wrap ga-2">
            <v-chip color="primary" size="small" variant="tonal">
              {{ activeTabMeta.metricLabel }}: {{ activeTabMeta.metricValue }}
            </v-chip>
            <template v-if="activeTab === 'providers'">
              <v-chip color="success" size="small" variant="tonal">
                Signés: {{ providerContractStats.signed }}
              </v-chip>
              <v-chip color="warning" size="small" variant="tonal">
                Brouillons: {{ providerContractStats.draft }}
              </v-chip>
            </template>
          </div>
        </v-card-text>
      </v-card>

      <v-window v-model="activeTab">
        <v-window-item value="providers">
          <v-card rounded="xl">
            <ProvidersToolbar
              v-model:search="search"
              :loading-export="loadingExport"
              @export="exportProvidersToExcel"
              @import="triggerImport"
              @refresh="loadProviders"
              @reset="resetSearch"
            />
            <input
              ref="importInput"
              accept=".xlsx,.xls,.csv,.txt"
              class="d-none"
              type="file"
              @change="onImportFileSelected"
            >

            <v-divider />

            <ProvidersTable
              :contract-status-color="contractStatusColor"
              :contract-status-label="contractStatusLabel"
              :format-provider-type="formatProviderType"
              :headers="headers"
              :items="providers"
              :loading="loadingProviders"
              @contract="goToContractTab"
              @delete="deleteProvider"
              @edit="openEditDialog"
              @view="openContractViewDialog"
            />
          </v-card>
        </v-window-item>

        <v-window-item value="dossier">
          <v-card class="mb-4 content-shell-card" rounded="xl" variant="tonal">
            <v-card-text class="glass-form-zone">
              <v-row dense>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="selectedDossierProviderId"
                    clearable
                    item-title="title"
                    item-value="value"
                    :items="providerSelectItems"
                    label="Choisir un prestataire *"
                    prepend-inner-icon="mdi-account-tie-hat"
                    variant="outlined"
                    @update:model-value="onDossierProviderSelected"
                  />
                </v-col>
                <v-col class="d-flex align-center" cols="12" md="6">
                  <v-chip color="primary" size="small" variant="tonal">
                    Dossier documentaire par prestataire
                  </v-chip>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>
          <v-alert
            class="mb-4"
            density="comfortable"
            icon="mdi-lightbulb-on-outline"
            type="success"
            variant="tonal"
          >
            Conseil: nommez chaque pièce de façon explicite (ex: Attestation fiscale 2026) pour simplifier les contrôles et audits.
          </v-alert>

          <v-alert
            v-if="!selectedDossierProvider"
            class="mb-4"
            density="comfortable"
            icon="mdi-information-outline"
            type="info"
            variant="tonal"
          >
            Sélectionnez un prestataire pour gérer son dossier (pièces administratives, contrats, attestations, etc.).
          </v-alert>

          <v-row v-else dense>
            <v-col cols="12" lg="5">
              <v-card rounded="lg" variant="outlined">
                <v-card-title class="text-subtitle-1">
                  Ajouter une pièce au dossier
                </v-card-title>
                <v-card-text class="glass-form-zone">
                  <v-text-field
                    v-model="dossierForm.title"
                    label="Titre (ex: Attestation fiscale)"
                    prepend-inner-icon="mdi-form-textbox"
                    variant="outlined"
                  />
                  <v-select
                    v-model="dossierForm.category"
                    class="mt-3"
                    :items="dossierCategoryItems"
                    label="Catégorie"
                    prepend-inner-icon="mdi-tag-outline"
                    variant="outlined"
                  />
                  <v-file-input
                    v-model="dossierForm.file"
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.png,.jpg,.jpeg,.webp,.txt"
                    class="mt-3"
                    label="Pièce jointe *"
                    prepend-icon="mdi-paperclip"
                    variant="outlined"
                  />
                  <v-textarea
                    v-model="dossierForm.note"
                    class="mt-3"
                    label="Note (optionnel)"
                    rows="2"
                    variant="outlined"
                  />
                  <div class="d-flex justify-end mt-3">
                    <v-btn
                      color="primary"
                      :loading="uploadingDossierFile"
                      prepend-icon="mdi-upload"
                      @click="uploadDossierFile"
                    >
                      Ajouter au dossier
                    </v-btn>
                  </div>
                </v-card-text>
              </v-card>
            </v-col>

            <v-col cols="12" lg="7">
              <v-card rounded="lg" variant="flat">
                <v-card-title class="d-flex align-center justify-space-between">
                  <span class="text-subtitle-1 font-weight-bold">
                    Dossier de {{ selectedDossierProvider.designation }}
                  </span>
                  <v-btn
                    color="secondary"
                    :loading="loadingDossierFiles"
                    prepend-icon="mdi-refresh"
                    size="small"
                    variant="tonal"
                    @click="loadDossierFiles(selectedDossierProvider.id)"
                  >
                    Actualiser
                  </v-btn>
                </v-card-title>
                <v-divider />
                <v-data-table
                  class="glass-table"
                  :headers="dossierHeaders"
                  item-value="id"
                  :items="dossierFiles"
                  :loading="loadingDossierFiles"
                  no-data-text="Aucune pièce dans le dossier."
                >
                  <template #item.title="{ item }">
                    <div>
                      <div class="font-weight-medium">{{ item.title || item.file_name }}</div>
                      <div class="text-caption text-medium-emphasis">{{ item.file_name }}</div>
                    </div>
                  </template>
                  <template #item.category="{ item }">
                    <v-chip size="small" variant="tonal">{{ item.category || 'piece' }}</v-chip>
                  </template>
                  <template #item.file_size="{ item }">
                    {{ formatFileSize(item.file_size) }}
                  </template>
                  <template #item.created_at="{ item }">
                    {{ formatArchiveDate(item.created_at) }}
                  </template>
                  <template #item.actions="{ item }">
                    <div class="d-flex ga-1 justify-end">
                      <v-btn
                        v-if="item.download_url"
                        :href="resolveContractFileUrl(item.download_url)"
                        icon="mdi-download"
                        size="small"
                        target="_blank"
                        title="Télécharger la pièce du dossier"
                        variant="text"
                      />
                      <v-btn
                        icon="mdi-delete-outline"
                        size="small"
                        title="Supprimer cette pièce du dossier"
                        variant="text"
                        @click="deleteDossierFile(item)"
                      />
                    </div>
                  </template>
                </v-data-table>
              </v-card>
            </v-col>
          </v-row>
        </v-window-item>

        <v-window-item value="contract">
          <ContractSelectorCard
            v-model:mode="contractInputMode"
            v-model:provider-id="selectedContractProviderId"
            :provider-items="providerSelectItems"
            @provider-change="onContractProviderSelected"
          />

          <v-alert
            v-if="!selectedProvider"
            class="mb-4"
            density="comfortable"
            icon="mdi-information-outline"
            type="info"
            variant="tonal"
          >
            Sélectionnez un prestataire pour gérer son contrat.
          </v-alert>

          <v-row v-else dense>
            <v-col v-if="contractInputMode === 'generate'" cols="12" lg="5">
              <v-card class="mb-3" rounded="lg" variant="tonal">
                <v-card-title class="text-subtitle-1">Informations clés</v-card-title>
                <v-card-text class="glass-form-zone">
                  <v-row dense>
                    <v-col cols="12" md="6">
                      <v-text-field v-model="contractForm.contract_reference" label="Référence contrat" variant="outlined" />
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-select v-model="contractForm.status" :items="contractStatusItems" label="Statut" variant="outlined" />
                    </v-col>
                    <v-col cols="12" md="6">
                      <AppDatePickerField v-model="contractForm.start_date" label="Date début" mode="date" />
                    </v-col>
                    <v-col cols="12" md="6">
                      <AppDatePickerField v-model="contractForm.end_date" label="Date fin" mode="date" />
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-text-field v-model.number="contractForm.amount" label="Montant" type="number" variant="outlined" />
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-text-field v-model="contractForm.currency" label="Devise" variant="outlined" />
                    </v-col>
                    <v-col cols="12">
                      <v-textarea v-model="contractForm.payment_terms" label="Conditions de paiement" rows="2" variant="outlined" />
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card>

              <v-card class="mb-3" rounded="lg" variant="outlined">
                <v-card-title class="text-subtitle-1">Parties contractantes</v-card-title>
                <v-card-text class="glass-form-zone">
                  <v-row dense>
                    <v-col cols="12">
                      <v-text-field label="Prestataire" :model-value="selectedProvider?.designation || ''" readonly variant="outlined" />
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-text-field v-model="contractForm.enterprise_representative_name" label="Nom représentant entreprise" variant="outlined" />
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-text-field v-model="contractForm.enterprise_representative_role" label="Fonction représentant entreprise" variant="outlined" />
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-text-field v-model="contractForm.provider_representative_name" label="Nom représentant prestataire" variant="outlined" />
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-text-field v-model="contractForm.provider_representative_role" label="Fonction représentant prestataire" variant="outlined" />
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card>

              <v-card class="mb-3" rounded="lg" variant="outlined">
                <v-card-title class="text-subtitle-1">Cadre d'exécution</v-card-title>
                <v-card-text class="glass-form-zone">
                  <v-row dense>
                    <v-col cols="12">
                      <v-textarea v-model="contractForm.service_summary" label="Objet / prestations attendues" rows="3" variant="outlined" />
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-text-field v-model="contractForm.execution_place" label="Lieu d'exécution" variant="outlined" />
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-text-field v-model="contractForm.governing_law" label="Droit applicable" variant="outlined" />
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-text-field v-model.number="contractForm.termination_notice_days" label="Préavis résiliation (jours)" type="number" variant="outlined" />
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card>

              <v-card rounded="lg" variant="outlined">
                <v-card-text class="d-flex align-center justify-space-between flex-wrap ga-2">
                  <v-switch
                    v-model="expertMode"
                    color="primary"
                    hide-details
                    inset
                    label="Mode expert (éditer le texte brut)"
                  />
                  <v-btn color="secondary" prepend-icon="mdi-file-document-refresh-outline" variant="tonal" @click="generateContractPreview">
                    Rafraîchir aperçu
                  </v-btn>
                </v-card-text>
              </v-card>
            </v-col>

            <v-col v-else cols="12" lg="5">
              <v-card class="mb-3" rounded="lg" variant="tonal">
                <v-card-title class="text-subtitle-1">Importer le contrat PDF existant</v-card-title>
                <v-card-text class="glass-form-zone">
                  <p class="text-body-2 text-medium-emphasis mb-3">
                    Téléversez le fichier PDF du contrat existant. Le document sera associé au prestataire sélectionné.
                  </p>
                  <div class="d-flex ga-3 align-center flex-wrap">
                    <v-file-input
                      v-model="signedContractFile"
                      accept=".pdf"
                      hide-details
                      label="Fichier contrat (PDF)"
                      prepend-icon="mdi-paperclip"
                      variant="outlined"
                    />
                    <v-btn color="secondary" prepend-icon="mdi-upload" variant="tonal" @click="uploadSignedContract">
                      Enregistrer PDF
                    </v-btn>
                  </div>
                </v-card-text>
              </v-card>
            </v-col>

            <v-col cols="12" lg="7">
              <v-card class="preview-shell" rounded="lg" variant="flat">
                <v-card-title class="d-flex justify-space-between align-center">
                  <span class="text-subtitle-1 font-weight-bold">Visualisation du contrat</span>
                  <v-chip color="primary" size="small" variant="tonal">
                    {{ availablePdfUrl ? 'PDF disponible' : 'Aperçu texte' }}
                  </v-chip>
                </v-card-title>
                <v-divider />
                <v-card-text v-if="availablePdfUrl" class="pdf-viewer-wrapper">
                  <iframe class="pdf-viewer" :src="availablePdfUrl" title="Aperçu du contrat PDF" />
                </v-card-text>
                <v-card-text v-else class="preview-panel">
                  <div class="preview-text">{{ renderedContractText }}</div>
                </v-card-text>
              </v-card>

              <v-expand-transition v-if="contractInputMode === 'generate'">
                <v-card v-if="expertMode" class="mt-3" rounded="lg" variant="outlined">
                  <v-card-title class="text-subtitle-2">Mode expert</v-card-title>
                  <v-card-text class="glass-form-zone">
                    <v-textarea
                      v-model="contractForm.template_content"
                      auto-grow
                      label="Template contrat"
                      min-rows="8"
                      variant="outlined"
                    />
                    <v-textarea
                      v-model="contractForm.filled_content"
                      auto-grow
                      class="mt-3"
                      label="Contenu généré (modifiable)"
                      min-rows="8"
                      variant="outlined"
                    />
                  </v-card-text>
                </v-card>
              </v-expand-transition>

              <v-card class="mt-3" rounded="lg" variant="outlined">
                <v-card-text class="glass-form-zone">
                  <v-textarea
                    v-model="archiveNote"
                    class="mb-3"
                    density="comfortable"
                    hide-details
                    label="Note d'archivage (optionnel)"
                    rows="2"
                    variant="outlined"
                  />
                  <div class="d-flex ga-3 align-center flex-wrap">
                    <v-btn
                      v-if="contractInputMode === 'generate'"
                      color="primary"
                      prepend-icon="mdi-content-save-outline"
                      @click="saveContract"
                    >
                      Enregistrer définitivement (génère le PDF)
                    </v-btn>
                    <v-btn
                      v-if="availablePdfUrl"
                      color="secondary"
                      :href="availablePdfUrl"
                      prepend-icon="mdi-download"
                      target="_blank"
                      variant="outlined"
                    >
                      Télécharger PDF
                    </v-btn>
                    <v-btn
                      color="warning"
                      :disabled="!canArchiveCurrentContract || archivingContract"
                      prepend-icon="mdi-archive-outline"
                      variant="tonal"
                      @click="archiveCurrentContract"
                    >
                      Archiver le contrat actif
                    </v-btn>
                  </div>
                </v-card-text>
              </v-card>

              <v-card class="mt-3" rounded="lg" variant="outlined">
                <v-card-title class="text-subtitle-2">Historique des contrats archivés</v-card-title>
                <v-card-text>
                  <v-alert
                    v-if="archivedContracts.length === 0"
                    density="comfortable"
                    icon="mdi-history"
                    type="info"
                    variant="tonal"
                  >
                    Aucun contrat archivé pour ce prestataire.
                  </v-alert>
                  <v-list v-else density="comfortable" lines="two">
                    <v-list-item v-for="archive in archivedContracts" :key="archive.id">
                      <template #title>
                        <span>{{ archive.contract_reference || 'Référence non renseignée' }}</span>
                      </template>
                      <template #subtitle>
                        <span>
                          Archivé le {{ formatArchiveDate(archive.archived_at) }}
                          <span v-if="archive.note"> - {{ archive.note }}</span>
                        </span>
                      </template>
                      <template #append>
                        <div class="d-flex ga-2">
                          <v-btn
                            v-if="archive.signed_file_url || archive.generated_file_url"
                            :href="resolveContractFileUrl(archive.signed_file_url || archive.generated_file_url || '')"
                            icon="mdi-download"
                            size="small"
                            target="_blank"
                            title="Télécharger le contrat archivé"
                            variant="text"
                          />
                        </div>
                      </template>
                    </v-list-item>
                  </v-list>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>
        </v-window-item>

        <v-window-item value="procedures">
          <v-card class="content-shell-card" rounded="xl">
            <v-card-text class="d-flex justify-space-between align-center flex-wrap ga-3">
              <div>
                <div class="text-subtitle-1 font-weight-bold">Procédures du sous-module</div>
                <div class="text-body-2 text-medium-emphasis">
                  Ajoutez et consultez les procédures liées à la gestion des prestataires.
                </div>
              </div>
              <v-btn
                color="primary"
                prepend-icon="mdi-file-document-plus-outline"
                variant="tonal"
                @click="procedureDialog = true"
              >
                Ajouter procédure
              </v-btn>
            </v-card-text>
            <v-divider />
            <v-alert
              class="ma-4"
              density="comfortable"
              icon="mdi-check-decagram-outline"
              type="info"
              variant="tonal"
            >
              Les procédures ajoutées ici sont synchronisées avec la bibliothèque documentaire et filtrées sur le site actif.
            </v-alert>
            <v-card-text class="procedures-glass-shell">
              <ProceduresPanel ref="proceduresPanel" />
            </v-card-text>
          </v-card>
        </v-window-item>
      </v-window>

      <ProviderDialog
        v-model="providerDialog"
        :editing-provider="editingProvider"
        :form="providerForm"
        :provider-type-items="providerTypeItems"
        @close="providerDialog = false"
        @save="saveProvider"
      />

      <ContractViewDialog
        v-model="contractViewDialog"
        :html="contractViewHtml"
        :provider="contractViewProvider"
        :url="contractViewUrl"
        @close="contractViewDialog = false"
      />

      <ProcedureUploadDialog v-model="procedureDialog" @created="proceduresPanel?.refresh()" />
    </div>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, reactive, ref } from 'vue'
  import {
    type ProviderContract,
    type ProviderContractArchiveEntry,
    type ProviderPartner,
    type ProviderPartnerFile,
    type ProviderPartnerPayload,
    providerPartnersService,
  } from '@/api/services/providerPartners.service'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import ProceduresPanel from '@/modules/clienta/components/documents/ProceduresPanel.vue'
  import ProcedureUploadDialog from '@/modules/clienta/components/documents/ProcedureUploadDialog.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import ContractSelectorCard from '@/modules/clienta/pages/iso/operations/components/ContractSelectorCard.vue'
  import ContractViewDialog from '@/modules/clienta/pages/iso/operations/components/ContractViewDialog.vue'
  import ProviderDialog from '@/modules/clienta/pages/iso/operations/components/ProviderDialog.vue'
  import ProviderHero from '@/modules/clienta/pages/iso/operations/components/ProviderHero.vue'
  import ProvidersTable from '@/modules/clienta/pages/iso/operations/components/ProvidersTable.vue'
  import ProvidersToolbar from '@/modules/clienta/pages/iso/operations/components/ProvidersToolbar.vue'
  import { useToast } from '@/modules/shared/composables/useToast'

  const toast = useToast()

  const activeTab = ref('providers')
  const loadingProviders = ref(false)
  const providers = ref<ProviderPartner[]>([])
  const loadingExport = ref(false)
  const search = ref('')
  const importInput = ref<HTMLInputElement | null>(null)
  const providerDialog = ref(false)
  const contractViewDialog = ref(false)
  const procedureDialog = ref(false)
  const proceduresPanel = ref<InstanceType<typeof ProceduresPanel> | null>(null)
  const contractInputMode = ref<'generate' | 'upload'>('generate')
  const expertMode = ref(false)
  const selectedContractProviderId = ref<number | null>(null)
  const selectedDossierProviderId = ref<number | null>(null)
  const editingProvider = ref<ProviderPartner | null>(null)
  const selectedProvider = ref<ProviderPartner | null>(null)
  const selectedDossierProvider = ref<ProviderPartner | null>(null)
  const selectedContract = ref<ProviderContract | null>(null)
  const dossierFiles = ref<ProviderPartnerFile[]>([])
  const loadingDossierFiles = ref(false)
  const uploadingDossierFile = ref(false)
  const contractViewProvider = ref<ProviderPartner | null>(null)
  const contractViewHtml = ref('')
  const contractViewUrl = ref('')
  const signedContractFile = ref<File | null>(null)
  const archiveNote = ref('')
  const archivingContract = ref(false)

  const providerTypeItems = [
    { title: 'Personne morale', value: 'personne_morale' },
    { title: 'Personne physique', value: 'personne_physique' },
    { title: 'Autre', value: 'autre' },
  ]

  const contractStatusItems = [
    { title: 'Brouillon', value: 'draft' },
    { title: 'Prêt', value: 'ready' },
    { title: 'Signé', value: 'signed' },
    { title: 'Archivé', value: 'archived' },
  ]

  const headers = [
    { title: 'Prestataire', key: 'designation' },
    { title: 'Type', key: 'provider_type' },
    { title: 'Offres', key: 'service_offers' },
    { title: 'Téléphones', key: 'phones' },
    { title: 'Email', key: 'email' },
    { title: 'IFU', key: 'ifu' },
    { title: 'Exp.', key: 'experience_years' },
    { title: 'Statut contrat', key: 'contract' },
    { title: 'Actions', key: 'actions', align: 'end' as const, sortable: false },
  ]

  const dossierHeaders = [
    { title: 'Pièce', key: 'title' },
    { title: 'Catégorie', key: 'category' },
    { title: 'Taille', key: 'file_size' },
    { title: 'Ajoutée le', key: 'created_at' },
    { title: 'Actions', key: 'actions', align: 'end' as const, sortable: false },
  ]

  const dossierCategoryItems = [
    { title: 'Pièce administrative', value: 'piece_administrative' },
    { title: 'Attestation', value: 'attestation' },
    { title: 'Contrat / Avenant', value: 'contrat' },
    { title: 'Certificat / Agrément', value: 'certificat' },
    { title: 'Autre', value: 'piece' },
  ]

  const providerForm = reactive<ProviderPartnerPayload>({
    designation: '',
    provider_type: 'personne_morale',
    legal_form: '',
    service_offers: '',
    phone_primary: '',
    phone_secondary: '',
    email: '',
    ifu: '',
    experience_years: null,
    evaluation_observation: '',
  })

  const templateForm = reactive({
    title: 'Contrat de prestation de services',
    content: '',
  })

  const dossierForm = reactive<{
    title: string
    category: string
    note: string
    file: File | null
  }>({
    title: '',
    category: 'piece_administrative',
    note: '',
    file: null,
  })

  const placeholders = [
    '{{enterprise_name}}',
    '{{enterprise_address}}',
    '{{provider_name}}',
    '{{provider_reference}}',
    '{{provider_type}}',
    '{{provider_email}}',
    '{{provider_phone}}',
    '{{provider_ifu}}',
    '{{service_offers}}',
    '{{contract_reference}}',
    '{{start_date}}',
    '{{end_date}}',
    '{{amount}}',
    '{{currency}}',
    '{{payment_terms}}',
    '{{today}}',
  ]

  const contractForm = reactive({
    contract_reference: '',
    template_title: '',
    template_content: '',
    filled_content: '',
    start_date: '',
    end_date: '',
    amount: null as number | null,
    currency: 'XOF',
    payment_terms: '',
    status: 'draft' as 'draft' | 'ready' | 'signed' | 'archived',
    service_summary: '',
    execution_place: '',
    governing_law: 'Droit en vigueur au lieu d\'exécution',
    termination_notice_days: 30,
    enterprise_representative_name: '',
    enterprise_representative_role: '',
    provider_representative_name: '',
    provider_representative_role: '',
  })

  const renderedContractText = computed(() => {
    const content = String(contractForm.filled_content || '').trim()
    if (!content) return 'Prévisualisation vide.'
    return content
  })

  const availablePdfUrl = computed(() => {
    if (selectedContract.value?.signed_file_url) return resolveContractFileUrl(selectedContract.value.signed_file_url)
    if (selectedContract.value?.generated_file_url) return resolveContractFileUrl(selectedContract.value.generated_file_url)
    return ''
  })

  const archivedContracts = computed<ProviderContractArchiveEntry[]>(() => {
    if (!Array.isArray(selectedContract.value?.history)) return []
    return selectedContract.value.history
  })

  const canArchiveCurrentContract = computed(() => {
    return Boolean(
      selectedContract.value?.signed_file_url
      || selectedContract.value?.generated_file_url
        || selectedContract.value?.filled_content
      || selectedContract.value?.contract_reference,
    )
  })

  const providerSelectItems = computed(() =>
    providers.value.map(provider => ({
      title: `${provider.designation} (${provider.reference})`,
      value: provider.id,
    })),
  )

  const tabItems = [
    { value: 'providers', label: 'Base Prestataires', icon: 'mdi-account-group-outline' },
    { value: 'dossier', label: 'Dossier Prestataire', icon: 'mdi-folder-multiple-outline' },
    { value: 'contract', label: 'Contrat Prestataire', icon: 'mdi-file-sign' },
    { value: 'procedures', label: 'Procédures', icon: 'mdi-file-document-multiple-outline' },
  ] as const

  const providerContractStats = computed(() => {
    const source = providers.value || []
    return {
      signed: source.filter(item => item.contract?.status === 'signed').length,
      draft: source.filter(item => !item.contract?.status || item.contract?.status === 'draft').length,
    }
  })

  const providerTypeStats = computed(() => {
    const source = providers.value || []
    return {
      personneMorale: source.filter(item => item.provider_type === 'personne_morale').length,
      personnePhysique: source.filter(item => item.provider_type === 'personne_physique').length,
    }
  })

  const activeTabMeta = computed(() => {
    if (activeTab.value === 'dossier') {
      return {
        label: 'Dossier prestataire',
        description: 'Centralisez les pièces administratives et justificatifs par prestataire.',
        metricLabel: 'Pièces',
        metricValue: dossierFiles.value.length,
      }
    }

    if (activeTab.value === 'contract') {
      return {
        label: 'Contrats prestataires',
        description: 'Importez ou générez les contrats, puis archivez les versions signées.',
        metricLabel: 'Contrats archivés',
        metricValue: archivedContracts.value.length,
      }
    }

    if (activeTab.value === 'procedures') {
      return {
        label: 'Procédures du sous-module',
        description: 'Retrouvez les procédures opérationnelles liées à la gestion des prestataires.',
        metricLabel: 'Statut',
        metricValue: 'Synchronisé',
      }
    }

    return {
      label: 'Base prestataires',
      description: 'Pilotez la base fournisseur, contacts, offres et statut contractuel.',
      metricLabel: 'Prestataires',
      metricValue: providers.value.length,
    }
  })

  function resetDossierForm () {
    dossierForm.title = ''
    dossierForm.category = 'piece_administrative'
    dossierForm.note = ''
    dossierForm.file = null
  }

  function formatFileSize (value?: number | null): string {
    const size = Number(value || 0)
    if (!Number.isFinite(size) || size <= 0) return '-'
    if (size < 1024) return `${size} o`
    if (size < 1024 * 1024) return `${(size / 1024).toFixed(1)} Ko`
    return `${(size / (1024 * 1024)).toFixed(1)} Mo`
  }

  function asString (value: unknown): string {
    return typeof value === 'string' ? value : ''
  }

  function asNumberOrNull (value: unknown): number | null {
    const parsed = Number(value)
    return Number.isFinite(parsed) ? parsed : null
  }

  function resolveContractFileUrl (rawUrl: string): string {
    const value = String(rawUrl || '').trim()
    if (!value) return ''
    if (/^https?:\/\//i.test(value)) return value

    const apiBase = String(import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api/v1')
      .replace(/\/api\/v1\/?$/, '')
      .replace(/\/+$/, '')

    if (value.startsWith('/storage/')) {
      return `${apiBase}${value}`
    }

    const normalizedPath = value
      .replace(/^\/+/, '')
      .replace(/^storage\//i, '')

    return `${apiBase}/storage/${normalizedPath}`
  }

  function recommendedTemplateContent (): string {
    return [
      'CONTRAT DE PRESTATION DE SERVICES',
      '',
      'Parties',
      'Entreprise: {{enterprise_name}}',
      'Adresse: {{enterprise_address}}',
      'Prestataire: {{provider_name}} (réf. {{provider_reference}}, IFU {{provider_ifu}})',
      '',
      'Article 1 - Objet',
      'Le présent contrat encadre la réalisation des prestations suivantes: {{service_offers}}.',
      '',
      'Article 2 - Référence et durée',
      'Référence contrat: {{contract_reference}}',
      'Période: du {{start_date}} au {{end_date}}.',
      '',
      'Article 3 - Conditions financières',
      'Montant convenu: {{amount}} {{currency}}.',
      'Conditions de paiement: {{payment_terms}}.',
      '',
      'Article 4 - Exigences qualité, HSE et conformité',
      '- Respect des exigences contractuelles, qualité et sécurité.',
      '- Respect des lois et réglementations applicables.',
      '- Alerte immédiate en cas de non-conformité ou incident.',
      '',
      'Article 5 - Confidentialité',
      'Les informations échangées restent strictement confidentielles.',
      '',
      'Article 6 - Contact opérationnel prestataire',
      '{{provider_phone}} / {{provider_email}}',
      '',
      'Fait le {{today}}',
      '',
      'Signature Entreprise: ____________________',
      'Signature Prestataire: ___________________',
    ].join('\n')
  }

  function contractStatusColor (status?: string) {
    if (status === 'signed') return 'success'
    if (status === 'ready') return 'info'
    if (status === 'archived') return 'grey'
    return 'warning'
  }

  function contractStatusLabel (status?: string): string {
    if (status === 'signed') return 'Signé'
    if (status === 'ready') return 'Prêt'
    if (status === 'archived') return 'Archivé'
    return 'Brouillon'
  }

  function formatArchiveDate (value?: string | null): string {
    if (!value) return '-'
    const date = new Date(value)
    return Number.isNaN(date.getTime()) ? value : date.toLocaleString('fr-FR')
  }

  function formatProviderType (type?: string): string {
    if (type === 'personne_morale') return 'Personne morale'
    if (type === 'personne_physique') return 'Personne physique'
    return 'Autre'
  }

  function resetProviderForm () {
    providerForm.designation = ''
    providerForm.provider_type = 'personne_morale'
    providerForm.legal_form = ''
    providerForm.service_offers = ''
    providerForm.phone_primary = ''
    providerForm.phone_secondary = ''
    providerForm.email = ''
    providerForm.ifu = ''
    providerForm.experience_years = null
    providerForm.evaluation_observation = ''
  }

  function applyProviderToForm (provider: ProviderPartner) {
    providerForm.designation = provider.designation
    providerForm.provider_type = provider.provider_type || 'personne_morale'
    providerForm.legal_form = provider.legal_form || ''
    providerForm.service_offers = provider.service_offers || ''
    providerForm.phone_primary = provider.phone_primary || ''
    providerForm.phone_secondary = provider.phone_secondary || ''
    providerForm.email = provider.email || ''
    providerForm.ifu = provider.ifu || ''
    providerForm.experience_years = provider.experience_years ?? null
    providerForm.evaluation_observation = provider.evaluation_observation || ''
  }

  function resetContractFormForProvider (provider: ProviderPartner) {
    contractForm.contract_reference = ''
    contractForm.template_title = templateForm.title
    contractForm.template_content = templateForm.content || recommendedTemplateContent()
    contractForm.filled_content = ''
    contractForm.start_date = ''
    contractForm.end_date = ''
    contractForm.amount = null
    contractForm.currency = 'XOF'
    contractForm.payment_terms = ''
    contractForm.status = 'draft'
    contractForm.service_summary = provider.service_offers || ''
    contractForm.execution_place = ''
    contractForm.governing_law = 'Droit en vigueur au lieu d\'exécution'
    contractForm.termination_notice_days = 30
    contractForm.enterprise_representative_name = ''
    contractForm.enterprise_representative_role = ''
    contractForm.provider_representative_name = provider.designation
    contractForm.provider_representative_role = ''
    expertMode.value = false
  }

  async function loadProviders () {
    loadingProviders.value = true
    try {
      const payload = await providerPartnersService.list({ search: search.value, per_page: 200 })
      providers.value = Array.isArray(payload.data) ? payload.data : []
    } catch {
      toast.error('Impossible de charger les prestataires.')
    } finally {
      loadingProviders.value = false
    }
  }

  async function loadDossierFiles (providerId: number) {
    loadingDossierFiles.value = true
    try {
      dossierFiles.value = await providerPartnersService.listFiles(providerId)
    } catch {
      dossierFiles.value = []
      toast.error('Impossible de charger le dossier prestataire.')
    } finally {
      loadingDossierFiles.value = false
    }
  }

  async function onDossierProviderSelected (providerId: number | null) {
    if (!providerId) {
      selectedDossierProvider.value = null
      dossierFiles.value = []
      resetDossierForm()
      return
    }

    const provider = providers.value.find(item => item.id === providerId) || null
    selectedDossierProvider.value = provider
    resetDossierForm()
    if (provider) {
      await loadDossierFiles(provider.id)
    }
  }

  async function uploadDossierFile () {
    if (!selectedDossierProvider.value) {
      toast.warning('Sélectionnez un prestataire.')
      return
    }
    if (!dossierForm.file) {
      toast.warning('Sélectionnez un fichier à ajouter.')
      return
    }

    uploadingDossierFile.value = true
    try {
      await providerPartnersService.uploadFile(selectedDossierProvider.value.id, {
        file: dossierForm.file,
        title: dossierForm.title || null,
        category: dossierForm.category || 'piece',
        note: dossierForm.note || null,
      })
      toast.success('Pièce ajoutée au dossier prestataire.')
      resetDossierForm()
      await loadDossierFiles(selectedDossierProvider.value.id)
    } catch {
      toast.error('Échec lors de l\'ajout de la pièce.')
    } finally {
      uploadingDossierFile.value = false
    }
  }

  async function deleteDossierFile (file: ProviderPartnerFile) {
    if (!selectedDossierProvider.value) return
    if (!confirm(`Supprimer la pièce "${file.title || file.file_name}" ?`)) return

    try {
      await providerPartnersService.removeFile(selectedDossierProvider.value.id, file.id)
      toast.success('Pièce supprimée du dossier.')
      await loadDossierFiles(selectedDossierProvider.value.id)
    } catch {
      toast.error('Suppression impossible.')
    }
  }

  async function resetSearch () {
    search.value = ''
    await loadProviders()
  }

  async function loadTemplate () {
    try {
      const template = await providerPartnersService.getTemplate()
      templateForm.title = template.title
      templateForm.content = template.content || recommendedTemplateContent()
    } catch {
      templateForm.content = recommendedTemplateContent()
      toast.error('Impossible de charger le canevas contrat.')
    }
  }

  function openCreateDialog () {
    editingProvider.value = null
    resetProviderForm()
    providerDialog.value = true
  }

  function openEditDialog (provider: ProviderPartner) {
    editingProvider.value = provider
    applyProviderToForm(provider)
    providerDialog.value = true
  }

  async function saveProvider () {
    if (!providerForm.designation?.trim()) {
      toast.error('La désignation est obligatoire.')
      return
    }

    try {
      if (editingProvider.value) {
        await providerPartnersService.update(editingProvider.value.id, providerForm)
        toast.success('Prestataire mis à jour.')
      } else {
        await providerPartnersService.create(providerForm)
        toast.success('Prestataire créé.')
      }

      providerDialog.value = false
      await loadProviders()
    } catch {
      toast.error('Échec lors de l\'enregistrement du prestataire.')
    }
  }

  async function deleteProvider (provider: ProviderPartner) {
    if (!confirm(`Supprimer le prestataire "${provider.designation}" ?`)) return

    try {
      await providerPartnersService.remove(provider.id)
      toast.success('Prestataire supprimé.')
      await loadProviders()
    } catch {
      toast.error('Suppression impossible.')
    }
  }

  function triggerImport () {
    importInput.value?.click()
  }

  async function exportProvidersToExcel () {
    loadingExport.value = true
    try {
      const blob = await providerPartnersService.exportXlsx({ search: search.value || undefined })
      const url = window.URL.createObjectURL(blob)
      const date = new Date().toISOString().slice(0, 10)
      const link = document.createElement('a')
      link.href = url
      link.download = `base_prestataires_${date}.xlsx`
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      window.URL.revokeObjectURL(url)
      toast.success('Export Excel généré.')
    } catch {
      toast.error('Échec de l\'export Excel des prestataires.')
    } finally {
      loadingExport.value = false
    }
  }

  async function onImportFileSelected (event: Event) {
    const target = event.target as HTMLInputElement
    const file = target.files?.[0]
    if (!file) return

    try {
      const result = await providerPartnersService.importFile(file)
      toast.success(`Import terminé: ${result.created} créés, ${result.updated} mis à jour.`)
      await loadProviders()
    } catch {
      toast.error('Échec de l\'import du fichier prestataires.')
    } finally {
      target.value = ''
    }
  }

  async function loadContractForProvider (provider: ProviderPartner) {
    selectedProvider.value = provider
    selectedContractProviderId.value = provider.id
    signedContractFile.value = null
    archiveNote.value = ''
    resetContractFormForProvider(provider)

    try {
      const contract = await providerPartnersService.getContract(provider.id)
      selectedContract.value = contract

      contractForm.contract_reference = contract.contract_reference || ''
      contractForm.template_title = contract.template_title || templateForm.title
      contractForm.template_content = contract.template_content || templateForm.content || recommendedTemplateContent()
      contractForm.filled_content = contract.filled_content || ''
      contractForm.start_date = contract.start_date || ''
      contractForm.end_date = contract.end_date || ''
      contractForm.amount = contract.amount ? Number(contract.amount) : null
      contractForm.currency = contract.currency || 'XOF'
      contractForm.payment_terms = contract.payment_terms || ''
      contractForm.status = contract.status || 'draft'

      const meta = (contract.meta || {}) as Record<string, unknown>
      contractForm.service_summary = asString(meta.service_summary) || provider.service_offers || ''
      contractForm.execution_place = asString(meta.execution_place)
      contractForm.governing_law = asString(meta.governing_law) || contractForm.governing_law
      contractForm.termination_notice_days = asNumberOrNull(meta.termination_notice_days) ?? 30
      contractForm.enterprise_representative_name = asString(meta.enterprise_representative_name)
      contractForm.enterprise_representative_role = asString(meta.enterprise_representative_role)
      contractForm.provider_representative_name = asString(meta.provider_representative_name) || provider.designation
      contractForm.provider_representative_role = asString(meta.provider_representative_role)
      expertMode.value = Boolean(meta.expert_mode)
      contractInputMode.value = contract.signed_file_url ? 'upload' : 'generate'

      if (contractInputMode.value === 'generate' && !contractForm.filled_content) {
        await generateContractPreview()
      }
    } catch {
      toast.error('Impossible de charger le contrat prestataire.')
    }
  }

  async function onContractProviderSelected (providerId: number | null) {
    if (!providerId) {
      selectedProvider.value = null
      selectedContract.value = null
      return
    }

    const provider = providers.value.find(item => item.id === providerId)
    if (!provider) return
    await loadContractForProvider(provider)
  }

  async function goToContractTab (provider: ProviderPartner) {
    activeTab.value = 'contract'
    await loadContractForProvider(provider)
  }

  async function goToDossierTab (provider: ProviderPartner) {
    activeTab.value = 'dossier'
    selectedDossierProviderId.value = provider.id
    selectedDossierProvider.value = provider
    await loadDossierFiles(provider.id)
  }

  async function openContractViewDialog (provider: ProviderPartner) {
    contractViewProvider.value = provider
    contractViewHtml.value = ''
    contractViewUrl.value = ''

    try {
      const contract = await providerPartnersService.getContract(provider.id)
      contractViewUrl.value = resolveContractFileUrl(contract.signed_file_url || contract.generated_file_url || '')

      if (!contractViewUrl.value) {
        const preview = await providerPartnersService.previewContract(provider.id, {
          content: contract.filled_content || contract.template_content || templateForm.content,
          draft: {
            contract_reference: contract.contract_reference || null,
            start_date: contract.start_date || null,
            end_date: contract.end_date || null,
            amount: contract.amount ? Number(contract.amount) : null,
            currency: contract.currency || 'XOF',
            payment_terms: contract.payment_terms || null,
          },
        })
        contractViewHtml.value = preview.rendered
          .replaceAll('&', '&amp;')
          .replaceAll('<', '&lt;')
          .replaceAll('>', '&gt;')
          .replaceAll('\n', '<br>')
      }

      contractViewDialog.value = true
    } catch {
      toast.error('Impossible de visualiser le contrat.')
    }
  }

  function buildAssistedTemplate (): string {
    const provider = selectedProvider.value
    const serviceLine = contractForm.service_summary?.trim() || provider?.service_offers || '{{service_offers}}'
    const location = contractForm.execution_place?.trim() || 'à préciser'
    const law = contractForm.governing_law?.trim() || 'droit applicable au lieu d\'exécution'
    const notice = contractForm.termination_notice_days || 30

    return [
      'CONTRAT DE PRESTATION DE SERVICES',
      '',
      'Parties',
      'Entreprise: {{enterprise_name}}',
      'Adresse: {{enterprise_address}}',
      'Prestataire: {{provider_name}} (réf. {{provider_reference}}, type {{provider_type}}, IFU {{provider_ifu}})',
      '',
      'Article 1 - Objet du contrat',
      `Le présent contrat a pour objet la réalisation des prestations suivantes: ${serviceLine}.`,
      '',
      'Article 2 - Référence et durée',
      'Référence contrat: {{contract_reference}}',
      'Période: du {{start_date}} au {{end_date}}.',
      `Lieu d'exécution: ${location}.`,
      '',
      'Article 3 - Obligations générales du Prestataire',
      '- Exécuter les prestations conformément aux exigences convenues.',
      '- Respecter les délais, normes qualité, règles HSE et obligations légales applicables.',
      '- Informer immédiatement l\'Entreprise de tout incident pouvant impacter la prestation.',
      '',
      'Article 4 - Conditions financières',
      'Montant: {{amount}} {{currency}}.',
      'Conditions de paiement: {{payment_terms}}',
      '',
      'Article 5 - Confidentialité',
      'Les parties s\'engagent à préserver la confidentialité des informations et documents échangés.',
      '',
      'Article 6 - Résiliation',
      `Chaque partie peut résilier le contrat avec un préavis de ${notice} jours, sous réserve des obligations en cours.`,
      '',
      'Article 7 - Litiges et droit applicable',
      `Le présent contrat est régi par ${law}. Les parties privilégient d'abord un règlement amiable.`,
      '',
      'Contact opérationnel prestataire: {{provider_phone}} / {{provider_email}}.',
      '',
      'Fait le {{today}}.',
      '',
      `Représentant Entreprise: ${contractForm.enterprise_representative_name || '____________________'} (${contractForm.enterprise_representative_role || '____________________'})`,
      `Représentant Prestataire: ${contractForm.provider_representative_name || '____________________'} (${contractForm.provider_representative_role || '____________________'})`,
    ].join('\n')
  }

  async function generateContractPreview () {
    if (!selectedProvider.value) return

    if (!expertMode.value) {
      contractForm.template_content = buildAssistedTemplate()
    }

    try {
      const preview = await providerPartnersService.previewContract(
        selectedProvider.value.id,
        {
          content: contractForm.template_content || templateForm.content,
          draft: {
            contract_reference: contractForm.contract_reference || null,
            start_date: contractForm.start_date || null,
            end_date: contractForm.end_date || null,
            amount: contractForm.amount,
            currency: contractForm.currency || 'XOF',
            payment_terms: contractForm.payment_terms || null,
          },
        },
      )

      contractForm.template_content = preview.template
      contractForm.filled_content = preview.rendered
    } catch {
      toast.error('Prévisualisation contrat impossible.')
    }
  }

  async function saveContract (): Promise<boolean> {
    if (!selectedProvider.value) return false

    if (contractInputMode.value === 'generate' && !contractForm.filled_content?.trim()) {
      await generateContractPreview()
    }

    try {
      let contract = await providerPartnersService.saveContract(selectedProvider.value.id, {
        contract_reference: contractForm.contract_reference || null,
        template_title: contractForm.template_title || templateForm.title,
        template_content: contractForm.template_content || templateForm.content,
        filled_content: contractForm.filled_content || null,
        start_date: contractForm.start_date || null,
        end_date: contractForm.end_date || null,
        amount: contractForm.amount,
        currency: contractForm.currency || 'XOF',
        payment_terms: contractForm.payment_terms || null,
        status: contractForm.status,
        meta: {
          service_summary: contractForm.service_summary || null,
          execution_place: contractForm.execution_place || null,
          governing_law: contractForm.governing_law || null,
          termination_notice_days: contractForm.termination_notice_days || null,
          enterprise_representative_name: contractForm.enterprise_representative_name || null,
          enterprise_representative_role: contractForm.enterprise_representative_role || null,
          provider_representative_name: contractForm.provider_representative_name || null,
          provider_representative_role: contractForm.provider_representative_role || null,
          expert_mode: expertMode.value,
        },
      })

      if (contractInputMode.value === 'generate') {
        contract = await providerPartnersService.generateContractPdf(selectedProvider.value.id)
      }

      selectedContract.value = contract
      toast.success(
        contractInputMode.value === 'generate'
          ? 'Contrat enregistré et PDF généré automatiquement.'
          : 'Contrat prestataire enregistré.',
      )
      await loadProviders()
      return true
    } catch {
      toast.error('Échec d\'enregistrement du contrat.')
      return false
    }
  }

  async function uploadSignedContract () {
    if (!selectedProvider.value || !signedContractFile.value) {
      toast.warning('Sélectionnez un fichier contrat signé.')
      return
    }

    try {
      const contract = await providerPartnersService.uploadSignedContract(
        selectedProvider.value.id,
        signedContractFile.value,
      )
      selectedContract.value = contract
      contractForm.status = contract.status
      contractInputMode.value = 'upload'
      toast.success('Contrat signé téléversé.')
      await loadProviders()
    } catch {
      toast.error('Échec du téléversement du contrat signé.')
    }
  }

  async function archiveCurrentContract () {
    if (!selectedProvider.value || !canArchiveCurrentContract.value) {
      toast.warning('Aucun contrat actif à archiver.')
      return
    }

    if (!confirm(`Archiver le contrat actuel de "${selectedProvider.value.designation}" ?`)) return

    archivingContract.value = true
    try {
      const contract = await providerPartnersService.archiveContract(selectedProvider.value.id, {
        note: archiveNote.value?.trim() || null,
      })

      selectedContract.value = contract
      archiveNote.value = ''
      signedContractFile.value = null
      contractInputMode.value = 'generate'
      resetContractFormForProvider(selectedProvider.value)
      toast.success('Contrat archivé. Vous pouvez maintenant importer un nouveau PDF ou générer un nouveau contrat.')
      await loadProviders()
    } catch {
      toast.error('Impossible d\'archiver ce contrat.')
    } finally {
      archivingContract.value = false
    }
  }

  onMounted(async () => {
    await Promise.all([loadProviders(), loadTemplate()])
  })
</script>

<style scoped>
.provider-management-page {
  --glass-bg: linear-gradient(135deg, rgba(255, 255, 255, 0.82), rgba(248, 252, 255, 0.66));
  --glass-border: rgba(148, 163, 184, 0.34);
  --glass-shadow: 0 14px 40px rgba(15, 23, 42, 0.09);
  --glass-inset: inset 0 1px 0 rgba(255, 255, 255, 0.72);
  --glass-primary-tint: rgba(8, 145, 178, 0.11);
  max-width: 1600px;
  margin: 0 auto;
}

:deep(.hero) {
  background: radial-gradient(circle at 15% 20%, rgba(14, 116, 144, 0.22), transparent 52%),
    linear-gradient(125deg, rgba(8, 145, 178, 0.16), rgba(30, 64, 175, 0.08));
  border: 1px solid rgba(14, 116, 144, 0.15);
}

:deep(.hero-kicker) {
  letter-spacing: 0.08em;
}

.modern-tabs :deep(.v-tab) {
  font-weight: 700;
  text-transform: none;
}

.provider-tabs {
  border: 1px solid var(--glass-border);
  border-radius: 14px;
  background: var(--glass-bg);
  box-shadow: var(--glass-shadow), var(--glass-inset);
  backdrop-filter: blur(12px);
  padding: 4px;
}

.provider-tabs :deep(.v-btn) {
  border-radius: 10px;
  min-height: 44px;
}

.provider-tabs :deep(.v-tab--selected) {
  background: var(--glass-primary-tint);
}

.tab-context {
  border: 1px solid var(--glass-border);
  background: linear-gradient(135deg, rgba(14, 116, 144, 0.12), rgba(59, 130, 246, 0.06));
  box-shadow: var(--glass-shadow), var(--glass-inset);
  backdrop-filter: blur(12px);
}

.content-shell-card {
  border: 1px solid var(--glass-border);
  background: var(--glass-bg);
  box-shadow: var(--glass-shadow), var(--glass-inset);
  backdrop-filter: blur(14px) saturate(118%);
}

.stat-mini-card {
  border: 1px solid rgba(8, 145, 178, 0.22);
  background: linear-gradient(145deg, rgba(236, 254, 255, 0.8), rgba(248, 250, 252, 0.64));
  box-shadow: var(--glass-shadow), var(--glass-inset);
  backdrop-filter: blur(12px);
}

.contract-dialog-shell {
  background: linear-gradient(180deg, rgba(248, 250, 252, 0.95), rgba(241, 245, 249, 0.95));
}

.preview-shell {
  border: 1px solid var(--glass-border);
  background: var(--glass-bg);
  box-shadow: var(--glass-shadow), var(--glass-inset);
  backdrop-filter: blur(12px);
}

:deep(.pdf-viewer-wrapper) {
  padding: 0;
  height: 680px;
}

:deep(.pdf-viewer) {
  width: 100%;
  height: 100%;
  border: 0;
}

:deep(.preview-panel) {
  min-height: 620px;
  line-height: 1.7;
  font-size: 0.94rem;
  color: #0f172a;
  background-image: linear-gradient(transparent 31px, rgba(15, 23, 42, 0.05) 32px);
  background-size: 100% 32px;
}

.preview-text {
  white-space: pre-wrap;
  overflow-wrap: anywhere;
}

.procedures-glass-shell {
  border-radius: 16px;
}

:deep(.provider-management-page .v-alert),
:deep(.provider-management-page .v-card[variant="outlined"]),
:deep(.provider-management-page .v-card[variant="tonal"]),
:deep(.provider-management-page .v-card[variant="flat"]) {
  border-color: var(--glass-border) !important;
  background: var(--glass-bg) !important;
  box-shadow: var(--glass-shadow), var(--glass-inset) !important;
  backdrop-filter: blur(12px) saturate(116%);
}

:deep(.provider-management-page .glass-form-zone .v-input),
:deep(.provider-management-page .provider-form-shell .v-input) {
  margin-bottom: 6px;
}

:deep(.provider-management-page .glass-form-zone .v-field),
:deep(.provider-management-page .provider-form-shell .v-field) {
  border-radius: 14px !important;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.82), rgba(239, 246, 255, 0.62)) !important;
  border: 1px solid rgba(148, 163, 184, 0.3) !important;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06), inset 0 1px 0 rgba(255, 255, 255, 0.78) !important;
  backdrop-filter: blur(8px);
  transition: box-shadow 0.2s ease, border-color 0.2s ease, background 0.2s ease;
}

:deep(.provider-management-page .glass-form-zone .v-field--variant-outlined .v-field__outline),
:deep(.provider-management-page .provider-form-shell .v-field--variant-outlined .v-field__outline) {
  --v-field-border-opacity: 0 !important;
}

:deep(.provider-management-page .glass-form-zone .v-field__input),
:deep(.provider-management-page .provider-form-shell .v-field__input) {
  color: #0f172a !important;
  font-weight: 500;
}

:deep(.provider-management-page .glass-form-zone .v-label),
:deep(.provider-management-page .provider-form-shell .v-label) {
  color: #475569 !important;
  opacity: 0.95 !important;
}

:deep(.provider-management-page .glass-form-zone .v-field__prepend-inner .v-icon),
:deep(.provider-management-page .provider-form-shell .v-field__prepend-inner .v-icon),
:deep(.provider-management-page .glass-form-zone .v-input__prepend .v-icon),
:deep(.provider-management-page .provider-form-shell .v-input__prepend .v-icon) {
  color: #0369a1 !important;
  opacity: 0.9;
}

:deep(.provider-management-page .glass-form-zone .v-field:hover),
:deep(.provider-management-page .provider-form-shell .v-field:hover) {
  border-color: rgba(14, 116, 144, 0.42) !important;
  box-shadow: 0 10px 28px rgba(14, 116, 144, 0.12), inset 0 1px 0 rgba(255, 255, 255, 0.84) !important;
}

:deep(.provider-management-page .glass-form-zone .v-field--focused),
:deep(.provider-management-page .provider-form-shell .v-field--focused) {
  border-color: rgba(2, 132, 199, 0.58) !important;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(224, 242, 254, 0.7)) !important;
  box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.16), 0 14px 30px rgba(14, 116, 144, 0.16) !important;
}

:deep(.provider-management-page .v-data-table.glass-table),
:deep(.provider-management-page .procedures-glass-shell .modern-table) {
  border: 1px solid var(--glass-border);
  border-radius: 14px;
  overflow: hidden;
  background: rgba(255, 255, 255, 0.66);
  box-shadow: var(--glass-shadow), var(--glass-inset);
  backdrop-filter: blur(10px);
}

:deep(.provider-management-page .v-data-table.glass-table .v-table__wrapper > table thead tr),
:deep(.provider-management-page .procedures-glass-shell .modern-table .v-table__wrapper > table thead tr) {
  background: linear-gradient(135deg, rgba(8, 145, 178, 0.16), rgba(59, 130, 246, 0.1));
}

:deep(.provider-management-page .v-data-table.glass-table .v-table__wrapper > table thead th),
:deep(.provider-management-page .procedures-glass-shell .modern-table .v-table__wrapper > table thead th) {
  color: #0f172a;
  font-weight: 700;
  border-bottom: 1px solid rgba(148, 163, 184, 0.34);
}

:deep(.provider-management-page .v-data-table.glass-table .v-table__wrapper > table tbody tr),
:deep(.provider-management-page .procedures-glass-shell .modern-table .v-table__wrapper > table tbody tr) {
  background: rgba(255, 255, 255, 0.28);
  transition: background-color 0.2s ease;
}

:deep(.provider-management-page .v-data-table.glass-table .v-table__wrapper > table tbody tr:hover),
:deep(.provider-management-page .procedures-glass-shell .modern-table .v-table__wrapper > table tbody tr:hover) {
  background: rgba(186, 230, 253, 0.24);
}

:deep(.provider-management-page .v-list) {
  border: 1px solid var(--glass-border);
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.64);
  backdrop-filter: blur(10px);
}

.provider-dialog-card {
  border: 1px solid var(--glass-border);
  background: linear-gradient(145deg, rgba(255, 255, 255, 0.92), rgba(248, 252, 255, 0.82));
  box-shadow: 0 24px 58px rgba(15, 23, 42, 0.18);
  overflow: hidden;
}

.provider-dialog-hero {
  padding: 20px 24px 16px;
  border-bottom: 1px solid rgba(148, 163, 184, 0.24);
  background:
    radial-gradient(circle at top right, rgba(14, 165, 233, 0.16), transparent 46%),
    radial-gradient(circle at top left, rgba(16, 185, 129, 0.12), transparent 45%),
    linear-gradient(160deg, rgba(248, 252, 255, 0.96), rgba(241, 245, 249, 0.92));
}

.provider-dialog-kicker {
  letter-spacing: 0.08em;
  font-weight: 700;
  color: #0369a1;
}

.provider-dialog-title {
  font-size: clamp(1.2rem, 1.8vw, 1.45rem);
  font-weight: 800;
  color: #0f172a;
}

.provider-dialog-subtitle {
  color: #475569;
  line-height: 1.5;
}

.provider-dialog-body {
  padding-top: 18px;
}

.provider-section-card {
  border: 1px solid rgba(148, 163, 184, 0.28) !important;
  background: linear-gradient(145deg, rgba(255, 255, 255, 0.86), rgba(241, 245, 249, 0.66)) !important;
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8), 0 10px 26px rgba(15, 23, 42, 0.08) !important;
  backdrop-filter: blur(10px);
}

.provider-side-card {
  border: 1px solid rgba(148, 163, 184, 0.34) !important;
  background: linear-gradient(150deg, rgba(255, 255, 255, 0.88), rgba(224, 242, 254, 0.46)) !important;
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8), 0 10px 22px rgba(15, 23, 42, 0.08) !important;
}

.provider-tips-list {
  margin: 0;
  padding-left: 18px;
  color: #1e293b;
  line-height: 1.6;
}

.provider-tips-list li + li {
  margin-top: 8px;
}

.provider-dialog-actions {
  padding: 14px 20px 18px;
  border-top: 1px solid rgba(148, 163, 184, 0.22);
  background: linear-gradient(180deg, rgba(248, 250, 252, 0.72), rgba(255, 255, 255, 0.9));
}

@media (max-width: 960px) {
  :deep(.pdf-viewer-wrapper) {
    height: 420px;
  }

  :deep(.preview-panel) {
    min-height: 360px;
  }
}
</style>
