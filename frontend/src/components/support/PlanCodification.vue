<template>
  <div class="codification-shell">
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
      <v-card-text class="pa-5 d-flex flex-wrap align-center justify-space-between ga-3">
        <div>
          <div class="section-banner__kicker">Ressources</div>
          <h2 class="section-banner__title">Plan de codification</h2>
          <p class="section-banner__subtitle">Définissez les catégories et localisations utilisées pour générer les codes équipements.</p>
        </div>
        <v-chip color="teal" variant="tonal">Étape 1</v-chip>
      </v-card-text>
    </v-card>

    <!-- Codification Rule Card -->
    <v-card class="mb-6 section-card section-card--wide" elevation="1" rounded="lg">
      <v-card-title class="pa-6">
        <v-icon color="primary" start>mdi-information</v-icon>
        Règle de codification
      </v-card-title>
      <v-divider />
      <v-card-text class="pa-6">
        <v-alert class="mb-4" color="info" variant="tonal">
          <div class="text-h6 mb-2">Format de codification</div>
          <div class="text-body-1 font-weight-medium">BEG / Catégorie / Nom commun / Localisation / Indice / Année</div>
          <div class="text-body-2 mt-2">
            <v-chip class="mr-2" color="success" size="small">Exemple</v-chip>
            <code class="text-subtitle-1">BEG/INF/ECR/CEO/001/2025</code>
          </div>
        </v-alert>

        <v-row>
          <v-col cols="12" md="6">
            <v-card class="bg-surface-variant" elevation="0" rounded="lg">
              <v-card-text class="pa-4">
                <div class="text-subtitle-2 font-weight-bold mb-2">Nom commun de l'équipement</div>
                <p class="text-body-2">Les trois premières lettres du nom commun</p>
                <v-chip color="primary" size="small" variant="tonal">Exemple: Chaise → CHA</v-chip>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="12" md="6">
            <v-card class="bg-surface-variant" elevation="0" rounded="lg">
              <v-card-text class="pa-4">
                <div class="text-subtitle-2 font-weight-bold mb-2">Indice</div>
                <p class="text-body-2">Numéro de l'équipement en trois caractères</p>
                <v-chip color="secondary" size="small" variant="tonal">Exemple: 001, 002, 003...</v-chip>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- Categories Management -->
    <v-card class="mb-6 section-card" elevation="1" rounded="lg">
      <v-card-title class="pa-6 d-flex align-center">
        <v-icon color="primary" start>mdi-shape</v-icon>
        Catégories d'équipement
        <v-spacer />
        <v-btn color="primary" prepend-icon="mdi-plus" variant="tonal" @click="showAddCategoryDialog = true">
          Ajouter
        </v-btn>
      </v-card-title>
      <v-divider />
      <v-card-text class="pa-0">
        <v-data-table
          class="elevation-0"
          :headers="categoryHeaders"
          :items="categories"
          :items-per-page="10"
        >
          <template #item.code="{ item }">
            <v-chip color="primary" size="small" variant="flat">{{ item.code }}</v-chip>
          </template>
          <template #item.description="{ item }">
            <div class="text-caption">{{ item.description }}</div>
          </template>
          <template #item.actions="{ item }">
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
              @click="deleteElement(item.id ?? 0)"
            />
          </template>
        </v-data-table>
      </v-card-text>
    </v-card>

    <!-- Locations Management -->
    <v-card class="mb-6 section-card" elevation="1" rounded="lg">
      <v-card-title class="pa-6 d-flex align-center">
        <v-icon color="secondary" start>mdi-map-marker</v-icon>
        Localisations
        <v-spacer />
        <v-btn color="secondary" prepend-icon="mdi-plus" variant="tonal" @click="showAddLocationDialog = true">
          Ajouter
        </v-btn>
      </v-card-title>
      <v-divider />
      <v-card-text class="pa-0">
        <v-data-table
          class="elevation-0"
          :headers="locationHeaders"
          :items="localisations"
          :items-per-page="10"
        >
          <template #item.code="{ item }">
            <v-chip color="secondary" size="small" variant="flat">{{ item.code }}</v-chip>
          </template>
          <template #item.actions="{ item }">
            <v-btn
              color="secondary"
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
              @click="deleteElement(item.id ?? 0)"
            />
          </template>
        </v-data-table>
      </v-card-text>
    </v-card>

    <!-- Equipment States -->
    <v-card class="section-card" elevation="1" rounded="lg">
      <v-card-title class="pa-6">
        <v-icon color="info" start>mdi-state-machine</v-icon>
        États de l'équipement
      </v-card-title>
      <v-divider />
      <v-card-text class="pa-6">
        <v-list class="bg-transparent">
          <v-list-item>
            <template #prepend>
              <v-chip color="success" size="small">Très bon</v-chip>
            </template>
            <v-list-item-title>Équipement neuf</v-list-item-title>
          </v-list-item>
          <v-list-item>
            <template #prepend>
              <v-chip color="info" size="small">Bon</v-chip>
            </template>
            <v-list-item-title>Équipement ayant déjà fait l'objet de quelques maintenances correctives ou réparations</v-list-item-title>
          </v-list-item>
          <v-list-item>
            <template #prepend>
              <v-chip color="error" size="small">Mauvais</v-chip>
            </template>
            <v-list-item-title>Équipement dans un état nécessitant un renouvellement dans un court moyen terme</v-list-item-title>
          </v-list-item>
        </v-list>
      </v-card-text>
    </v-card>

    <!-- Add Category Dialog -->
    <v-dialog v-model="showAddCategoryDialog" max-width="600">
      <v-card rounded="lg">
        <v-card-title class="pa-6">Ajouter une catégorie</v-card-title>
        <v-divider />
        <v-card-text class="pa-6">
          <v-form @submit.prevent="addCategory">
            <v-text-field
              v-model="newCategory.code"
              class="mb-4"
              label="Code (jusqu'à 10 caractères) *"
              maxlength="10"
              variant="outlined"
            />
            <v-text-field
              v-model="newCategory.libelle"
              class="mb-4"
              label="Libellé *"
              variant="outlined"
            />
            <v-textarea
              v-model="newCategory.description"
              label="Description"
              rows="3"
              variant="outlined"
            />
            <div class="d-flex justify-end gap-2">
              <v-btn variant="outlined" @click="showAddCategoryDialog = false">Annuler</v-btn>
              <v-btn color="primary" type="submit">Ajouter</v-btn>
            </div>
          </v-form>
        </v-card-text>
      </v-card>
    </v-dialog>

    <!-- Add Location Dialog -->
    <v-dialog v-model="showAddLocationDialog" max-width="600">
      <v-card rounded="lg">
        <v-card-title class="pa-6">Ajouter une localisation</v-card-title>
        <v-divider />
        <v-card-text class="pa-6">
          <v-form @submit.prevent="addLocation">
            <v-text-field
              v-model="newLocation.code"
              class="mb-4"
              label="Code (jusqu'à 10 caractères) *"
              maxlength="10"
              variant="outlined"
            />
            <v-text-field
              v-model="newLocation.libelle"
              label="Libellé *"
              variant="outlined"
            />
            <div class="d-flex justify-end gap-2">
              <v-btn variant="outlined" @click="showAddLocationDialog = false">Annuler</v-btn>
              <v-btn color="secondary" type="submit">Ajouter</v-btn>
            </div>
          </v-form>
        </v-card-text>
      </v-card>
    </v-dialog>

    <!-- Edit Codification Dialog -->
    <v-dialog v-model="showEditDialog" max-width="600">
      <v-card rounded="lg">
        <v-card-title class="pa-6">Modifier l'élément</v-card-title>
        <v-divider />
        <v-card-text class="pa-6">
          <v-form @submit.prevent="saveEdit">
            <v-text-field
              v-model="editingElement.code"
              class="mb-4"
              label="Code *"
              maxlength="10"
              variant="outlined"
            />
            <v-text-field
              v-model="editingElement.libelle"
              class="mb-4"
              label="Libellé *"
              variant="outlined"
            />
            <v-textarea
              v-if="editingElement.type === 'categorie'"
              v-model="editingElement.description"
              label="Description"
              rows="3"
              variant="outlined"
            />
            <div class="d-flex justify-end gap-2">
              <v-btn variant="outlined" @click="showEditDialog = false">Annuler</v-btn>
              <v-btn color="primary" :loading="store.loading" type="submit">Enregistrer</v-btn>
            </div>
          </v-form>
        </v-card-text>
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
            Supprimer l'élément
            <strong>{{ deletingElement?.libelle }}</strong>
            ({{ deletingElement?.code }}) ?
          </p>
          <p class="text-body-2 text-medium-emphasis">
            Cette action est impossible si l'élément est déjà utilisé par un équipement.
          </p>
        </v-card-text>
        <v-card-actions class="pa-6 pt-0 justify-end">
          <v-btn variant="outlined" @click="showDeleteDialog = false">Annuler</v-btn>
          <v-btn color="error" :loading="store.loading" variant="flat" @click="confirmDelete">Supprimer</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup lang="ts">
  import type { CodificationElement } from '@/services/supportService'
  import { computed, onMounted, ref } from 'vue'
  import { useSupportStore } from '@/stores/supportStore'

  const store = useSupportStore()
  const loadError = ref('')
  const showAddCategoryDialog = ref(false)
  const showAddLocationDialog = ref(false)
  const showEditDialog = ref(false)
  const showDeleteDialog = ref(false)
  const actionMessage = ref('')
  const actionMessageType = ref<'success' | 'error'>('success')
  const deletingElement = ref<CodificationElement | null>(null)
  const editingElement = ref({
    id: 0,
    type: 'categorie' as 'categorie' | 'localisation',
    code: '',
    libelle: '',
    description: '',
  })

  const newCategory = ref({ code: '', libelle: '', description: '', type: 'categorie' as const, actif: true })
  const newLocation = ref({ code: '', libelle: '', type: 'localisation' as const, actif: true })

  const categoryHeaders = [
    { title: 'Code', key: 'code', sortable: true },
    { title: 'Libellé', key: 'libelle', sortable: true },
    { title: 'Description', key: 'description', sortable: false },
    { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
  ]

  const locationHeaders = [
    { title: 'Code', key: 'code', sortable: true },
    { title: 'Libellé', key: 'libelle', sortable: true },
    { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
  ]

  const categories = computed(() => store.categories)
  const localisations = computed(() => store.localisations)

  async function addCategory () {
    try {
      await store.createCodification(newCategory.value)
      newCategory.value = { code: '', libelle: '', description: '', type: 'categorie', actif: true }
      showAddCategoryDialog.value = false
      actionMessageType.value = 'success'
      actionMessage.value = 'Catégorie ajoutée avec succès.'
    } catch {
      actionMessageType.value = 'error'
      actionMessage.value = store.error || 'Erreur lors de la création de la catégorie.'
    }
  }

  async function addLocation () {
    try {
      await store.createCodification(newLocation.value)
      newLocation.value = { code: '', libelle: '', type: 'localisation', actif: true }
      showAddLocationDialog.value = false
      actionMessageType.value = 'success'
      actionMessage.value = 'Localisation ajoutée avec succès.'
    } catch {
      actionMessageType.value = 'error'
      actionMessage.value = store.error || 'Erreur lors de la création de la localisation.'
    }
  }

  function openEditDialog (item: CodificationElement) {
    editingElement.value = {
      id: Number(item.id),
      type: item.type,
      code: item.code,
      libelle: item.libelle,
      description: item.description || '',
    }
    showEditDialog.value = true
  }

  async function saveEdit () {
    if (!editingElement.value.id) {
      return
    }

    try {
      await store.updateCodification(editingElement.value.id, {
        code: editingElement.value.code,
        libelle: editingElement.value.libelle,
        description: editingElement.value.type === 'categorie' ? editingElement.value.description : undefined,
      })
      showEditDialog.value = false
      actionMessageType.value = 'success'
      actionMessage.value = 'Élément mis à jour avec succès.'
    } catch {
      actionMessageType.value = 'error'
      actionMessage.value = store.error || 'Erreur lors de la mise à jour de l’élément.'
    }
  }

  async function deleteElement (id: number) {
    deletingElement.value = [...categories.value, ...localisations.value].find(item => Number(item.id) === id) ?? null
    showDeleteDialog.value = true
  }

  async function confirmDelete () {
    if (!deletingElement.value?.id) {
      showDeleteDialog.value = false
      return
    }

    try {
      await store.deleteCodification(Number(deletingElement.value.id))
      actionMessageType.value = 'success'
      actionMessage.value = 'Élément supprimé avec succès.'
    } catch {
      actionMessageType.value = 'error'
      actionMessage.value = store.error || 'Suppression impossible: élément utilisé.'
    } finally {
      showDeleteDialog.value = false
      deletingElement.value = null
    }
  }

  onMounted(async () => {
    try {
      await store.fetchCodifications()
      loadError.value = ''
    } catch (error: any) {
      loadError.value = error?.response?.data?.message || 'Impossible de charger les éléments de codification.'
      console.error('Erreur chargement codifications:', error)
    }
  })
</script>

<style scoped>
.codification-shell {
  max-width: 1080px;
  margin: 0 auto;
  padding: 8px 8px 24px;
}

.section-card {
  width: min(100%, 980px);
  margin-left: auto;
  margin-right: auto;
  border: 1px solid #e2e8f0;
  background: #ffffff;
  box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
}

.section-card--wide {
  width: min(100%, 1020px);
}

:deep(.section-card .v-card-title) {
  background: linear-gradient(90deg, rgba(15, 118, 110, 0.06), rgba(255, 255, 255, 0));
}

.section-card::before {
  content: '';
  display: block;
  height: 4px;
  width: 100%;
  border-radius: 999px 999px 0 0;
  background: linear-gradient(90deg, #0f766e, #14b8a6, #5eead4);
}

.section-card :deep(.v-card-title) {
  padding-top: 22px;
}

.section-banner {
  border: 1px solid #d7e5df;
  background:
    radial-gradient(700px 140px at 10% -40%, rgba(20, 184, 166, 0.14), transparent 58%),
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

@media (max-width: 1280px) {
  .section-card {
    max-width: 100%;
  }
}

@media (max-width: 900px) {
  .codification-shell {
    padding: 0 4px 20px;
  }
}
</style>
