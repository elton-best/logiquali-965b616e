<template>
  <div>
    <v-alert
      v-if="loadError"
      class="mb-4"
      closable
      color="error"
      variant="tonal"
      @click:close="loadError = ''"
    >
      {{ loadError }}
    </v-alert>
    <v-alert
      v-if="actionMessage"
      class="mb-4"
      closable
      :color="actionMessageType"
      variant="tonal"
      @click:close="actionMessage = ''"
    >
      {{ actionMessage }}
    </v-alert>

    <v-card class="section-banner mb-6" elevation="0" rounded="xl">
      <v-card-text
        class="pa-5 d-flex flex-wrap align-center justify-space-between ga-3"
      >
        <div>
          <div class="section-banner__kicker">Ressources</div>
          <h2 class="section-banner__title">Inventaire des équipements</h2>
          <p class="section-banner__subtitle">
            Créez, mettez à jour et importez les équipements en conservant la
            traçabilité des recodifications.
          </p>
        </div>
        <v-chip color="teal" variant="tonal">Étape 2</v-chip>
      </v-card-text>
    </v-card>

    <v-card class="mb-6" elevation="1" rounded="lg">
      <v-card-title class="pa-6 pb-4 d-flex align-center">
        <v-icon color="primary" start>mdi-package-variant-closed-plus</v-icon>
        Création d'équipement
        <v-spacer />
        <v-btn
          class="mr-2"
          color="primary"
          :prepend-icon="showCreateForm ? 'mdi-chevron-up' : 'mdi-plus'"
          variant="tonal"
          @click="showCreateForm = !showCreateForm"
        >
          {{ showCreateForm ? "Masquer le formulaire" : "Créer un équipement" }}
        </v-btn>
        <input
          ref="importInputRef"
          accept=".xlsx,.xls,.csv"
          class="d-none"
          type="file"
          @change="handleImportFileSelected"
        >
        <v-btn
          color="primary"
          :loading="importLoading"
          prepend-icon="mdi-upload"
          variant="outlined"
          @click="openImportDialog"
        >
          Importer Excel
        </v-btn>
      </v-card-title>
      <v-expand-transition>
        <v-card-text v-if="showCreateForm" class="pa-6 pt-0">
          <v-alert
            v-if="!isCustomCodification"
            class="mb-4"
            color="info"
            variant="tonal"
          >
            Remplissez les informations puis validez. Le code équipement sera
            généré automatiquement.
          </v-alert>
          <v-alert v-else class="mb-4" color="info" variant="tonal">
            Le mode de codification personnalisé est activé. Le code équipement
            est obligatoire.
          </v-alert>
          <v-alert
            v-if="form.necessite_maintenance && !form.frequence_maintenance_jours"
            class="mb-4"
            color="warning"
            variant="tonal"
          >
            Fréquence requise : aucun plan de maintenance ne sera créé tant que
            la fréquence n’est pas renseignée.
          </v-alert>
          <v-form @submit.prevent="handleSubmit">
            <v-row>
              <v-col v-if="isCustomCodification" cols="12" md="6">
                <v-text-field
                  v-model="form.code_complet"
                  density="comfortable"
                  hint="Mode personnalisé : renseignez votre code existant ici."
                  label="Code équipement *"
                  persistent-hint
                  :rules="[(v) => !!v || 'Requis']"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="form.categorie_id"
                  density="comfortable"
                  item-title="libelle"
                  item-value="id"
                  :items="categories"
                  label="Catégorie *"
                  :rules="[(v) => !!v || 'Requis']"
                  variant="outlined"
                >
                  <template #item="{ props, item }">
                    <v-list-item v-bind="props">
                      <template #prepend>
                        <v-chip color="primary" size="x-small" variant="flat">{{
                          item.raw.code
                        }}</v-chip>
                      </template>
                    </v-list-item>
                  </template>
                </v-select>
              </v-col>

              <v-col cols="12" md="6">
                <v-select
                  v-model="form.localisation_id"
                  density="comfortable"
                  item-title="libelle"
                  item-value="id"
                  :items="localisations"
                  label="Localisation *"
                  :rules="[(v) => !!v || 'Requis']"
                  variant="outlined"
                >
                  <template #item="{ props, item }">
                    <v-list-item v-bind="props">
                      <template #prepend>
                        <v-chip
                          color="secondary"
                          size="x-small"
                          variant="flat"
                        >{{ item.raw.code }}</v-chip>
                      </template>
                    </v-list-item>
                  </template>
                </v-select>
              </v-col>

              <v-col cols="12" md="6">
                <v-text-field
                  v-model="form.nom_commun"
                  density="comfortable"
                  label="Nom commun *"
                  :rules="[(v) => !!v || 'Requis']"
                  variant="outlined"
                />
              </v-col>

              <v-col cols="12" md="6">
                <v-text-field
                  v-model.number="form.annee_acquisition"
                  density="comfortable"
                  label="Année d'acquisition *"
                  :rules="[(v) => !!v || 'Requis']"
                  type="number"
                  variant="outlined"
                />
              </v-col>

              <v-col cols="12" md="4">
                <v-text-field
                  v-model="form.marque"
                  density="comfortable"
                  label="Marque"
                  variant="outlined"
                />
              </v-col>

              <v-col cols="12" md="4">
                <v-text-field
                  v-model="form.modele"
                  density="comfortable"
                  label="Modèle"
                  variant="outlined"
                />
              </v-col>

              <v-col cols="12" md="4">
                <v-text-field
                  v-model="form.numero_serie"
                  density="comfortable"
                  label="Numéro de série"
                  variant="outlined"
                />
              </v-col>

              <v-col cols="12" md="6">
                <v-select
                  v-model="form.etat"
                  density="comfortable"
                  :items="[
                    { value: 'tres_bon', title: 'Très bon (Neuf)' },
                    { value: 'bon', title: 'Bon' },
                    { value: 'mauvais', title: 'Mauvais' },
                  ]"
                  label="État *"
                  variant="outlined"
                />
              </v-col>

              <v-col cols="12" md="6">
                <v-text-field
                  v-model.number="form.valeur_acquisition"
                  density="comfortable"
                  label="Valeur d'acquisition"
                  prefix="FCFA"
                  type="number"
                  variant="outlined"
                />
              </v-col>

              <v-col cols="12">
                <v-checkbox
                  v-model="form.necessite_maintenance"
                  color="primary"
                  hide-details
                  label="Nécessite une maintenance"
                />
              </v-col>

              <v-col v-if="form.necessite_maintenance" cols="12" md="6">
                <v-text-field
                  v-model.number="form.frequence_maintenance_jours"
                  density="comfortable"
                  label="Fréquence de maintenance (jours)"
                  type="number"
                  variant="outlined"
                />
              </v-col>

              <v-col cols="12">
                <v-textarea
                  v-model="form.observations"
                  label="Observations"
                  rows="3"
                  variant="outlined"
                />
              </v-col>
            </v-row>

            <div class="d-flex justify-end gap-2 mt-4">
              <v-btn variant="outlined" @click="resetForm">Réinitialiser</v-btn>
              <v-btn
                color="primary"
                :loading="loading"
                prepend-icon="mdi-content-save"
                type="submit"
              >
                Enregistrer
              </v-btn>
            </div>
          </v-form>
        </v-card-text>
      </v-expand-transition>
    </v-card>

    <v-card v-if="importResult" class="mb-6" elevation="1" rounded="lg">
      <v-card-title class="pa-6 pb-4 d-flex align-center">
        <v-icon color="info" start>mdi-file-chart</v-icon>
        Rapport d'import
      </v-card-title>
      <v-divider />
      <v-card-text class="pa-6">
        <v-row class="mb-4">
          <v-col cols="12" md="3">
            <v-chip
              color="primary"
              variant="tonal"
            >Total: {{ importResult.summary.total }}</v-chip>
          </v-col>
          <v-col cols="12" md="3">
            <v-chip
              color="success"
              variant="tonal"
            >Créés: {{ importResult.summary.created }}</v-chip>
          </v-col>
          <v-col cols="12" md="3">
            <v-chip
              color="warning"
              variant="tonal"
            >Mis à jour: {{ importResult.summary.updated }}</v-chip>
          </v-col>
          <v-col cols="12" md="3">
            <v-chip
              color="error"
              variant="tonal"
            >Échecs: {{ importResult.summary.failed }}</v-chip>
          </v-col>
        </v-row>

        <v-data-table
          :headers="importHeaders"
          :items="importResult.report"
          :items-per-page="10"
        >
          <template #item.status="{ item }">
            <v-chip
              :color="
                item.status === 'created'
                  ? 'success'
                  : item.status === 'updated'
                    ? 'warning'
                    : 'error'
              "
              size="small"
              variant="tonal"
            >
              {{ item.status }}
            </v-chip>
          </template>
        </v-data-table>
      </v-card-text>
    </v-card>

    <!-- Equipment List -->
    <v-card elevation="1" rounded="lg">
      <v-card-title class="pa-6">
        <v-icon color="primary" start>mdi-format-list-bulleted</v-icon>
        Liste des équipements
        <v-spacer />
        <v-chip
          color="primary"
          variant="tonal"
        >{{ equipements?.length || 0 }} équipements</v-chip>
      </v-card-title>
      <v-divider />
      <v-card-text class="pa-6 pb-0">
        <v-row class="mb-2">
          <v-col cols="12" md="3">
            <v-card class="pa-4" color="primary" variant="tonal">
              <div class="text-caption">Total</div>
              <div class="text-h6 font-weight-bold">
                {{ equipements.length }}
              </div>
            </v-card>
          </v-col>
          <v-col cols="12" md="3">
            <v-card class="pa-4" color="success" variant="tonal">
              <div class="text-caption">Très bon</div>
              <div class="text-h6 font-weight-bold">
                {{ stateCounts.tres_bon }}
              </div>
            </v-card>
          </v-col>
          <v-col cols="12" md="3">
            <v-card class="pa-4" color="info" variant="tonal">
              <div class="text-caption">Bon</div>
              <div class="text-h6 font-weight-bold">{{ stateCounts.bon }}</div>
            </v-card>
          </v-col>
          <v-col cols="12" md="3">
            <v-card class="pa-4" color="error" variant="tonal">
              <div class="text-caption">Mauvais</div>
              <div class="text-h6 font-weight-bold">
                {{ stateCounts.mauvais }}
              </div>
            </v-card>
          </v-col>
        </v-row>

        <v-row>
          <v-col cols="12" md="4">
            <v-text-field
              v-model="searchQuery"
              clearable
              density="comfortable"
              label="Rechercher (code, nom, série)"
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-select
              v-model="etatFilter"
              clearable
              density="comfortable"
              :items="etatFilterItems"
              label="Filtrer par état"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-select
              v-model="maintenanceFilter"
              clearable
              density="comfortable"
              :items="maintenanceFilterItems"
              label="Filtrer par maintenance"
              variant="outlined"
            />
          </v-col>
        </v-row>
      </v-card-text>

      <v-card-text class="pa-0">
        <v-data-table
          class="elevation-0"
          :headers="headers"
          :items="filteredEquipements"
          :items-per-page="10"
        >
          <template #item.code_complet="{ item }">
            <code class="text-caption">{{ item.code_complet }}</code>
          </template>

          <template #item.etat="{ item }">
            <v-chip
              :color="getEtatColor(item.etat)"
              size="small"
              variant="tonal"
            >
              {{ getEtatLabel(item.etat) }}
            </v-chip>
          </template>

          <template #item.actions="{ item }">
            <v-btn
              v-if="canRegisterTransfer"
              color="teal"
              icon="mdi-swap-horizontal"
              size="small"
              title="Enregistrer un transfert"
              variant="text"
              @click="openTransferDialog(item)"
            />
            <v-btn
              v-if="canViewTransferHistory"
              color="info"
              icon="mdi-history"
              size="small"
              title="Historique des transferts"
              variant="text"
              @click="openTransferHistoryDialog(item)"
            />
            <v-btn
              color="primary"
              icon="mdi-pencil"
              size="small"
              variant="text"
              @click="openEditDialog(item)"
            />
            <v-btn
              color="error"
              icon="mdi-delete"
              size="small"
              variant="text"
              @click="deleteEquipement(Number(item.id))"
            />
          </template>
        </v-data-table>
      </v-card-text>
    </v-card>

    <!-- Edit Equipment Dialog -->
    <v-dialog v-model="showEditDialog" max-width="900">
      <v-card rounded="lg">
        <v-card-title class="pa-6">Modifier l'équipement</v-card-title>
        <v-divider />
        <v-card-text class="pa-6">
          <v-form @submit.prevent="saveEdit">
            <v-row>
              <v-col v-if="isCustomCodification" cols="12" md="6">
                <v-text-field
                  v-model="editForm.code_complet"
                  label="Code équipement *"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="editForm.categorie_id"
                  item-title="libelle"
                  item-value="id"
                  :items="categories"
                  label="Catégorie *"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="editForm.localisation_id"
                  item-title="libelle"
                  item-value="id"
                  :items="localisations"
                  label="Localisation *"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="editForm.nom_commun"
                  label="Nom commun *"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model.number="editForm.annee_acquisition"
                  label="Année d'acquisition *"
                  type="number"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field
                  v-model="editForm.marque"
                  label="Marque"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field
                  v-model="editForm.modele"
                  label="Modèle"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field
                  v-model="editForm.numero_serie"
                  label="Numéro de série"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="editForm.etat"
                  :items="[
                    { value: 'tres_bon', title: 'Très bon (Neuf)' },
                    { value: 'bon', title: 'Bon' },
                    { value: 'mauvais', title: 'Mauvais' },
                  ]"
                  label="État *"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model.number="editForm.valeur_acquisition"
                  label="Valeur d'acquisition"
                  prefix="FCFA"
                  type="number"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12">
                <v-checkbox
                  v-model="editForm.necessite_maintenance"
                  color="primary"
                  hide-details
                  label="Nécessite une maintenance"
                />
              </v-col>
              <v-col v-if="editForm.necessite_maintenance" cols="12" md="6">
                <v-text-field
                  v-model.number="editForm.frequence_maintenance_jours"
                  label="Fréquence de maintenance (jours)"
                  type="number"
                  variant="outlined"
                />
              </v-col>
              <v-col v-if="editForm.necessite_maintenance && !editForm.frequence_maintenance_jours" cols="12">
                <v-alert color="warning" variant="tonal">
                  Fréquence requise : aucun plan de maintenance ne sera créé tant
                  que la fréquence n’est pas renseignée.
                </v-alert>
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="editForm.observations"
                  label="Observations"
                  rows="3"
                  variant="outlined"
                />
              </v-col>
            </v-row>
            <div class="d-flex justify-end gap-2 mt-4">
              <v-btn
                variant="outlined"
                @click="showEditDialog = false"
              >Annuler</v-btn>
              <v-btn
                color="primary"
                :loading="loadingEdit"
                type="submit"
              >Enregistrer</v-btn>
            </div>
          </v-form>
        </v-card-text>
      </v-card>
    </v-dialog>

    <!-- Equipment Transfer Modal -->
    <EquipmentTransferModal
      v-if="canRegisterTransfer"
      v-model="showTransferDialog"
      :available-localisations="localisations"
      :enterprise-id="currentEnterpriseId"
      :equipement="selectedTransferEquipement"
      @success="handleTransferSuccess"
    />

    <!-- Equipment Transfer History Dialog -->
    <v-dialog v-model="showTransferHistoryDialog" max-width="900">
      <v-card rounded="lg">
        <v-card-title class="pa-6 d-flex align-center">
          <v-icon color="info" start>mdi-history</v-icon>
          Historique des transferts
          <v-spacer />
          <span
            v-if="selectedTransferEquipement"
            class="text-caption text-medium-emphasis"
          >
            {{ selectedTransferEquipement.nom_commun }}
          </span>
        </v-card-title>
        <v-divider />
        <v-card-text class="pa-6">
          <EquipmentTransferHistory
            v-if="selectedTransferEquipement?.id"
            :equipement-id="Number(selectedTransferEquipement.id)"
          />
        </v-card-text>
        <v-card-actions class="pa-6 pt-0 justify-end">
          <v-btn
            variant="outlined"
            @click="showTransferHistoryDialog = false"
          >Fermer</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Delete Confirmation Dialog -->
    <v-dialog v-model="showDeleteDialog" max-width="520">
      <v-card rounded="lg">
        <v-card-title class="pa-6 d-flex align-center">
          <v-icon color="error" start>mdi-alert-circle-outline</v-icon>
          Confirmer la suppression
        </v-card-title>
        <v-divider />
        <v-card-text class="pa-6">
          <p class="mb-2">
            Supprimer l'équipement
            <strong>{{ deletingEquipement?.nom_commun }}</strong>
            ({{ deletingEquipement?.code_complet }}) ?
          </p>
        </v-card-text>
        <v-card-actions class="pa-6 pt-0 justify-end">
          <v-btn
            variant="outlined"
            @click="showDeleteDialog = false"
          >Annuler</v-btn>
          <v-btn
            color="error"
            :loading="loadingDelete"
            variant="flat"
            @click="confirmDeleteEquipement"
          >Supprimer</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup lang="ts">
  import type {
    Equipement,
    EquipementImportResult,
  } from '@/services/supportService'
  import { computed, onMounted, ref } from 'vue'
  import EquipmentTransferHistory from '@/modules/clienta/pages/support/components/EquipmentTransferHistory.vue'
  import EquipmentTransferModal from '@/modules/clienta/pages/support/components/EquipmentTransferModal.vue'
  import { useAuthStore } from '@/stores/auth'
  import { useSupportStore } from '@/stores/supportStore'
  import {
    expandPermissionAliases,
    normalizePermissionList,
  } from '@/utils/permissions'

  const authStore = useAuthStore()
  const store = useSupportStore()
  const loading = ref(false)
  const loadingEdit = ref(false)
  const loadingDelete = ref(false)
  const importLoading = ref(false)
  const loadError = ref('')
  const actionMessage = ref('')
  const actionMessageType = ref<'success' | 'error'>('success')
  const importInputRef = ref<HTMLInputElement | null>(null)
  const importResult = ref<EquipementImportResult | null>(null)
  const showEditDialog = ref(false)
  const showDeleteDialog = ref(false)
  const showTransferDialog = ref(false)
  const showTransferHistoryDialog = ref(false)
  const showCreateForm = ref(false)
  const deletingEquipement = ref<Equipement | null>(null)
  const selectedTransferEquipement = ref<Equipement | null>(null)
  const editingEquipementId = ref<number | null>(null)
  const searchQuery = ref('')
  const etatFilter = ref<string | null>(null)
  const maintenanceFilter = ref<boolean | null>(null)
  const codificationMode = computed(
    () => authStore.user?.enterprise?.codification_mode || 'standard',
  )
  const isCustomCodification = computed(
    () => codificationMode.value === 'custom',
  )
  const currentEnterpriseId = computed(() =>
    Number(authStore.user?.enterprise?.id || 0),
  )
  const permissionSet = computed(() => getEffectivePermissionSet())
  const canViewTransferHistory = computed(() =>
    canAccessMenuItem(['support.ressources.read']),
  )
  const canRegisterTransfer = computed(() =>
    canAccessMenuItem(['support.ressources.transfer', 'support.ressources.update']),
  )

  const form = ref({
    code_complet: '',
    categorie_id: null,
    localisation_id: null,
    nom_commun: '',
    annee_acquisition: new Date().getFullYear(),
    marque: '',
    modele: '',
    numero_serie: '',
    etat: 'bon',
    valeur_acquisition: null,
    observations: '',
    necessite_maintenance: false,
    frequence_maintenance_jours: null,
    actif: true,
  })
  const editForm = ref({
    code_complet: '',
    categorie_id: null as number | null,
    localisation_id: null as number | null,
    nom_commun: '',
    annee_acquisition: new Date().getFullYear(),
    marque: '',
    modele: '',
    numero_serie: '',
    etat: 'bon' as 'tres_bon' | 'bon' | 'mauvais',
    valeur_acquisition: null as number | null,
    observations: '',
    necessite_maintenance: false,
    frequence_maintenance_jours: null as number | null,
    actif: true,
  })

  const headers = [
    { title: 'Code', key: 'code_complet', sortable: true },
    { title: 'Nom', key: 'nom_commun', sortable: true },
    { title: 'Catégorie', key: 'categorie.libelle', sortable: true },
    { title: 'Localisation', key: 'localisation.libelle', sortable: true },
    { title: 'État', key: 'etat', sortable: true },
    { title: 'Année', key: 'annee_acquisition', sortable: true },
    { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
  ]

  const importHeaders = [
    { title: 'Ligne', key: 'row', sortable: true },
    { title: 'Statut', key: 'status', sortable: true },
    { title: 'Code', key: 'code_complet', sortable: false },
    { title: 'Message', key: 'message', sortable: false },
  ]

  const categories = computed(() => store.categories)
  const localisations = computed(() => store.localisations)
  const equipements = computed(() => store.equipements)
  const etatFilterItems = [
    { title: 'Très bon', value: 'tres_bon' },
    { title: 'Bon', value: 'bon' },
    { title: 'Mauvais', value: 'mauvais' },
  ]
  const maintenanceFilterItems = [
    { title: 'Maintenance requise', value: true },
    { title: 'Sans maintenance', value: false },
  ]
  const stateCounts = computed(() => ({
    tres_bon: equipements.value.filter(item => item.etat === 'tres_bon').length,
    bon: equipements.value.filter(item => item.etat === 'bon').length,
    mauvais: equipements.value.filter(item => item.etat === 'mauvais').length,
  }))
  const filteredEquipements = computed(() => {
    const search = searchQuery.value.trim().toLowerCase()
    return equipements.value.filter(item => {
      if (etatFilter.value && item.etat !== etatFilter.value) {
        return false
      }
      if (
        maintenanceFilter.value !== null
        && Boolean(item.necessite_maintenance) !== maintenanceFilter.value
      ) {
        return false
      }
      if (!search) {
        return true
      }

      const haystack = [
        item.code_complet,
        item.nom_commun,
        item.numero_serie,
        item.marque,
        item.modele,
      ].map(value => String(value || '').toLowerCase())

      return haystack.some(value => value.includes(search))
    })
  })

  function resetForm () {
    form.value = {
      code_complet: '',
      categorie_id: null,
      localisation_id: null,
      nom_commun: '',
      annee_acquisition: new Date().getFullYear(),
      marque: '',
      modele: '',
      numero_serie: '',
      etat: 'bon',
      valeur_acquisition: null,
      observations: '',
      necessite_maintenance: false,
      frequence_maintenance_jours: null,
      actif: true,
    }
  }

  async function handleSubmit () {
    loading.value = true
    try {
      await store.createEquipement(form.value as any)
      resetForm()
      showCreateForm.value = false
      actionMessageType.value = 'success'
      actionMessage.value = 'Équipement créé avec succès.'
    } catch {
      actionMessageType.value = 'error'
      actionMessage.value
        = store.error || 'Erreur lors de la création de l’équipement.'
    } finally {
      loading.value = false
    }
  }

  function openEditDialog (item: Equipement) {
    editingEquipementId.value = Number(item.id)
    editForm.value = {
      code_complet: item.code_complet || '',
      categorie_id: Number(item.categorie_id),
      localisation_id: Number(item.localisation_id),
      nom_commun: item.nom_commun || '',
      annee_acquisition: Number(item.annee_acquisition),
      marque: item.marque || '',
      modele: item.modele || '',
      numero_serie: item.numero_serie || '',
      etat: item.etat,
      valeur_acquisition: item.valeur_acquisition ?? null,
      observations: item.observations || '',
      necessite_maintenance: Boolean(item.necessite_maintenance),
      frequence_maintenance_jours: item.frequence_maintenance_jours ?? null,
      actif: Boolean(item.actif),
    }
    showEditDialog.value = true
  }

  async function saveEdit () {
    if (!editingEquipementId.value) {
      return
    }

    const current = equipements.value.find(
      eq => Number(eq.id) === editingEquipementId.value,
    )
    const oldCode = current?.code_complet

    loadingEdit.value = true
    try {
      const updated = await store.updateEquipement(
        editingEquipementId.value,
        editForm.value as any,
      )
      showEditDialog.value = false
      actionMessageType.value = 'success'
      actionMessage.value
        = oldCode && updated?.code_complet && oldCode !== updated.code_complet
          ? `Équipement mis à jour. Code recodifié: ${oldCode} → ${updated.code_complet}`
          : 'Équipement mis à jour avec succès.'
    } catch {
      actionMessageType.value = 'error'
      actionMessage.value
        = store.error || 'Erreur lors de la mise à jour de l’équipement.'
    } finally {
      loadingEdit.value = false
    }
  }

  function openImportDialog () {
    importInputRef.value?.click()
  }

  async function handleImportFileSelected (event: Event) {
    const input = event.target as HTMLInputElement
    const selectedFile = input.files?.[0]
    if (!selectedFile) {
      return
    }

    importLoading.value = true
    try {
      importResult.value = await store.importEquipements(selectedFile)
      await store.fetchCodifications()
      loadError.value = ''
    } catch (error: any) {
      loadError.value
        = error?.response?.data?.message
          || 'Erreur lors de l\'import des équipements.'
      console.error('Import error:', error)
    } finally {
      importLoading.value = false
      input.value = ''
    }
  }

  async function deleteEquipement (id: number) {
    deletingEquipement.value
      = equipements.value.find(item => Number(item.id) === id) ?? null
    showDeleteDialog.value = true
  }

  function openTransferDialog (item: Equipement) {
    if (!canRegisterTransfer.value) {
      actionMessageType.value = 'error'
      actionMessage.value
        = 'Action non autorisée. Vous n\'avez pas les permissions nécessaires.'
      return
    }

    selectedTransferEquipement.value = item
    showTransferDialog.value = true
  }

  function openTransferHistoryDialog (item: Equipement) {
    if (!canViewTransferHistory.value) {
      actionMessageType.value = 'error'
      actionMessage.value
        = 'Action non autorisée. Vous n\'avez pas les permissions nécessaires.'
      return
    }

    selectedTransferEquipement.value = item
    showTransferHistoryDialog.value = true
  }

  function hasRole (roleName: string): boolean {
    const roleNames = Array.isArray((authStore.user as any)?.role_names)
      ? (authStore.user as any).role_names
      : []
    if (roleNames.includes(roleName)) {
      return true
    }

    const roles = (authStore.user as any)?.roles
    if (!Array.isArray(roles)) {
      return false
    }

    return roles.some((role: any) => {
      if (typeof role === 'string') {
        return role === roleName
      }
      if (typeof role?.name === 'string') {
        return role.name === roleName
      }
      if (typeof role?.attributes?.name === 'string') {
        return role.attributes.name === roleName
      }
      return false
    })
  }

  function getEffectivePermissionSet (): Set<string> {
    const user: any = authStore.user || {}
    const fromDirect = Array.isArray(user.permissions) ? user.permissions : []
    const fromEffective = Array.isArray(user.effective_permissions)
      ? user.effective_permissions
      : []
    const fromActiveScoped = Array.isArray(user.active_scoped_permissions)
      ? user.active_scoped_permissions
      : []
    const fromRolePermissions = Array.isArray(user.roles)
      ? user.roles.flatMap((role: any) =>
        Array.isArray(role?.permissions)
          ? role.permissions
          : (Array.isArray(role?.relationships?.permissions)
            ? role.relationships.permissions
            : []),
      )
      : []

    const permissionNames = [
      ...fromDirect,
      ...fromEffective,
      ...fromActiveScoped,
      ...fromRolePermissions,
    ]
      .map((permission: any) => {
        if (typeof permission === 'string') {
          return permission
        }
        if (typeof permission?.name === 'string') {
          return permission.name
        }
        if (typeof permission?.attributes?.name === 'string') {
          return permission.attributes.name
        }
        return null
      })
      .filter(Boolean) as string[]

    return new Set(normalizePermissionList(permissionNames))
  }

  function canAccessMenuItem (requiredPermissions: string[]): boolean {
    const userType = authStore.user?.user_type
    if (userType === 'super_admin' || hasRole('admin_entreprise')) {
      return true
    }

    const permissions = permissionSet.value
    return requiredPermissions.some(permission =>
      expandPermissionAliases(permission).some(alias => permissions.has(alias)),
    )
  }

  async function handleTransferSuccess () {
    await store.fetchEquipements()
    actionMessageType.value = 'success'
    actionMessage.value = 'Transfert enregistré avec succès.'
  }

  async function confirmDeleteEquipement () {
    if (!deletingEquipement.value?.id) {
      showDeleteDialog.value = false
      return
    }

    loadingDelete.value = true
    try {
      await store.deleteEquipement(Number(deletingEquipement.value.id))
      showDeleteDialog.value = false
      actionMessageType.value = 'success'
      actionMessage.value = 'Équipement supprimé avec succès.'
      deletingEquipement.value = null
    } catch {
      actionMessageType.value = 'error'
      actionMessage.value
        = store.error || 'Suppression impossible pour cet équipement.'
    } finally {
      loadingDelete.value = false
    }
  }

  function getEtatColor (etat: string) {
    const colors = { tres_bon: 'success', bon: 'info', mauvais: 'error' }
    return colors[etat as keyof typeof colors] || 'default'
  }

  function getEtatLabel (etat: string) {
    const labels = { tres_bon: 'Très bon', bon: 'Bon', mauvais: 'Mauvais' }
    return labels[etat as keyof typeof labels] || etat
  }

  onMounted(async () => {
    try {
      await store.fetchCodifications()
      await store.fetchEquipements()
      loadError.value = ''
    } catch (error) {
      loadError.value
        = 'Impossible de charger les codifications ou les équipements.'
      console.error('Mount error:', error)
    }
  })
</script>

<style scoped>
.section-banner {
  border: 1px solid #d7e5df;
  background:
    radial-gradient(
      700px 140px at 10% -40%,
      rgba(20, 184, 166, 0.14),
      transparent 58%
    ),
    linear-gradient(135deg, #f8fbfa 0%, #f6fbf8 100%);
}

.section-banner__kicker {
  font-size: 0.74rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #65756f;
}

.section-banner__title {
  margin: 0.25rem 0 0;
  font-size: 1.2rem;
  line-height: 1.25;
}

.section-banner__subtitle {
  margin: 0.35rem 0 0;
  font-size: 0.9rem;
  color: #5f6d68;
  max-width: 680px;
}
</style>
