<template>
  <div class="step-1-document-types">
    <v-card class="mb-4" elevation="0" rounded="lg">
      <v-card-title class="d-flex align-center gap-2">
        <v-icon color="primary">mdi-file-document-outline</v-icon>
        <span>Étape 1: Configuration des types documentaires</span>
      </v-card-title>
      <v-card-text>
        <p class="text-body-2 text-medium-emphasis mb-4">
          Les types documentaires définissent les catégories de documents que vous allez gérer
          (ex: POL = Politiques, PRC = Procédures, etc.).
        </p>

        <v-alert
          v-if="!isValid"
          class="mb-4"
          density="comfortable"
          type="warning"
        >
          ⚠️ Vous devez avoir au moins 1 type documentaire pour continuer
        </v-alert>

        <!-- Action Buttons -->
        <div class="mb-4">
          <v-btn
            color="primary"
            prepend-icon="mdi-plus"
            variant="tonal"
            @click="startCreate"
          >
            Ajouter un type
          </v-btn>
        </div>

        <!-- Types Table -->
        <v-table class="types-table" density="comfortable">
          <thead>
            <tr>
              <th>Nom</th>
              <th>Abréviation</th>
              <th>Ordre d'affichage</th>
              <th>Statut</th>
              <th class="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="type in documentTypes" :key="type.id">
              <td class="font-weight-medium">{{ type.name }}</td>
              <td>
                <v-chip color="info" size="small" variant="tonal">
                  {{ type.abbreviation }}
                </v-chip>
              </td>
              <td>{{ type.display_order }}</td>
              <td>
                <v-chip
                  :color="type.is_active ? 'success' : 'grey'"
                  size="small"
                  variant="tonal"
                >
                  {{ type.is_active ? '✓ Actif' : '✗ Inactif' }}
                </v-chip>
              </td>
              <td class="text-right">
                <v-btn
                  color="primary"
                  icon="mdi-pencil-outline"
                  size="small"
                  variant="text"
                  @click="startEdit(type)"
                />
                <v-btn
                  color="error"
                  icon="mdi-delete-outline"
                  size="small"
                  variant="text"
                  @click="deleteType(type.id)"
                />
              </td>
            </tr>
            <tr v-if="documentTypes.length === 0">
              <td class="text-center py-6 text-medium-emphasis" colspan="5">
                Aucun type documentaire défini.
                <br>
                <v-btn
                  color="primary"
                  size="small"
                  variant="text"
                  @click="startCreate"
                >
                  Créer le premier type →
                </v-btn>
              </td>
            </tr>
          </tbody>
        </v-table>
      </v-card-text>
    </v-card>

    <!-- Dialog Créer/Éditer -->
    <v-dialog v-model="showDialog" max-width="500px">
      <v-card>
        <v-card-title>
          {{ editingType ? 'Éditer le type' : 'Créer un nouveau type' }}
        </v-card-title>
        <v-card-text>
          <v-form ref="form" @submit.prevent="saveType">
            <v-text-field
              v-model="formData.name"
              class="mb-3"
              density="comfortable"
              label="Nom du type"
              placeholder="ex: Politiques"
              :rules="[v => !!v || 'Le nom est requis']"
              variant="outlined"
            />

            <v-text-field
              v-model="formData.abbreviation"
              class="mb-3"
              counter
              density="comfortable"
              label="Abréviation (2-5 caractères)"
              maxlength="5"
              placeholder="POL"
              :rules="[
                v => !!v || 'L\'abréviation est requise',
                v => v.length >= 2 || 'Au minimum 2 caractères',
              ]"
              variant="outlined"
            />

            <v-text-field
              v-model.number="formData.display_order"
              class="mb-3"
              density="comfortable"
              label="Ordre d'affichage"
              type="number"
              variant="outlined"
            />

            <v-textarea
              v-model="formData.description"
              class="mb-3"
              density="comfortable"
              label="Description (optionnel)"
              placeholder="Description du type..."
              rows="3"
              variant="outlined"
            />

            <v-switch
              v-model="formData.is_active"
              color="success"
              label="Actif"
            />
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="showDialog = false">Annuler</v-btn>
          <v-btn
            color="primary"
            @click="saveType"
          >
            Enregistrer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup lang="ts">
  import type { DocumentTypeCatalog } from '../../../types/document.types'
  import { computed, ref } from 'vue'

  interface Props {
    documentTypes: DocumentTypeCatalog[]
    isValid: boolean
  }

  interface Emits {
    (e: 'update:documentTypes', value: DocumentTypeCatalog[]): void
  }

  const props = withDefaults(defineProps<Props>(), {
    documentTypes: () => [],
    isValid: false,
  })

  const emit = defineEmits<Emits>()

  // State
  const showDialog = ref(false)
  const editingType = ref<DocumentTypeCatalog | null>(null)
  const form = ref()
  const formData = ref({
    name: '',
    abbreviation: '',
    description: '',
    display_order: 0,
    is_active: true,
  })

  // Computed
  const documentTypes = computed({
    get: () => props.documentTypes,
    set: value => emit('update:documentTypes', value),
  })

  // Methods
  function startCreate () {
    editingType.value = null
    formData.value = {
      name: '',
      abbreviation: '',
      description: '',
      display_order: (props.documentTypes.length + 1) * 10,
      is_active: true,
    }
    showDialog.value = true
  }

  function startEdit (type: DocumentTypeCatalog) {
    editingType.value = type
    formData.value = {
      name: type.name,
      abbreviation: type.abbreviation,
      description: type.description || '',
      display_order: type.display_order,
      is_active: type.is_active,
    }
    showDialog.value = true
  }

  async function saveType () {
    if (!form.value || !(await form.value.validate()).valid) {
      return
    }

    if (editingType.value) {
      // Edit existing
      const updated = {
        ...editingType.value,
        ...formData.value,
      }
      const index = documentTypes.value.findIndex(t => t.id === editingType.value!.id)
      if (index !== -1) {
        const newTypes = [...documentTypes.value]
        newTypes[index] = updated
        documentTypes.value = newTypes
      }
    } else {
      // Create new
      const newType: DocumentTypeCatalog = {
        id: Math.max(...documentTypes.value.map(t => t.id || 0), 0) + 1,
        name: formData.value.name,
        abbreviation: formData.value.abbreviation,
        description: formData.value.description,
        display_order: formData.value.display_order,
        is_active: formData.value.is_active,
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString(),
      }
      documentTypes.value = [...documentTypes.value, newType]
    }

    showDialog.value = false
  }

  function deleteType (id: number) {
    documentTypes.value = documentTypes.value.filter(t => t.id !== id)
  }
</script>

<style scoped lang="scss">
.step-1-document-types {
  :deep(.types-table) {
    background-color: transparent;

    tbody tr {
      &:hover {
        background-color: rgba(0, 0, 0, 0.02);
      }
    }
  }
}
</style>
