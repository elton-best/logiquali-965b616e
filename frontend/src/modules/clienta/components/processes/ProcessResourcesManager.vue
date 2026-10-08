<template>
  <v-card>
    <v-card-title>Ressources du Processus</v-card-title>

    <v-card-text>
      <v-tabs v-model="activeTab" class="mb-4">
        <v-tab value="human">
          <v-icon start>mdi-account-group</v-icon>
          Humaines
        </v-tab>
        <v-tab value="technological">
          <v-icon start>mdi-cog</v-icon>
          Technologiques
        </v-tab>
        <v-tab value="documentary">
          <v-icon start>mdi-file-document</v-icon>
          Documentaires
        </v-tab>
        <v-tab value="material">
          <v-icon start>mdi-package-variant</v-icon>
          Matérielles
        </v-tab>
      </v-tabs>

      <v-window v-model="activeTab">
        <!-- Human Resources -->
        <v-window-item value="human">
          <div class="d-flex justify-end mb-3">
            <v-btn
              color="primary"
              prepend-icon="mdi-plus"
              size="small"
              @click="openDialog('human')"
            >
              Ajouter une ressource humaine
            </v-btn>
          </div>

          <v-row v-if="resources.human.length > 0">
            <v-col
              v-for="resource in resources.human"
              :key="resource.id"
              cols="12"
              md="6"
            >
              <v-card variant="outlined">
                <v-card-text>
                  <div class="d-flex align-center justify-space-between mb-2">
                    <div class="d-flex align-center gap-2">
                      <v-avatar color="primary" size="40">
                        <v-icon>mdi-account</v-icon>
                      </v-avatar>
                      <div>
                        <div class="font-weight-bold">{{ resource.name }}</div>
                        <div class="text-caption text-medium-emphasis">
                          {{ resource.role }}
                        </div>
                      </div>
                    </div>
                    <div class="d-flex gap-1">
                      <v-btn
                        icon="mdi-pencil"
                        size="x-small"
                        variant="text"
                        @click="editResource('human', resource)"
                      />
                      <v-btn
                        color="error"
                        icon="mdi-delete"
                        size="x-small"
                        variant="text"
                        @click="deleteResource('human', resource)"
                      />
                    </div>
                  </div>
                  <div v-if="resource.competences" class="text-body-2 mb-2">
                    <strong>Compétences:</strong> {{ resource.competences }}
                  </div>
                  <div v-if="resource.availability" class="text-caption">
                    <v-icon size="small">mdi-clock</v-icon>
                    {{ resource.availability }}
                  </div>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>
          <v-alert v-else type="info" variant="tonal">
            Aucune ressource humaine définie
          </v-alert>
        </v-window-item>

        <!-- Technological Resources -->
        <v-window-item value="technological">
          <div class="d-flex justify-end mb-3">
            <v-btn
              color="primary"
              prepend-icon="mdi-plus"
              size="small"
              @click="openDialog('technological')"
            >
              Ajouter une ressource technologique
            </v-btn>
          </div>

          <v-row v-if="resources.technological.length > 0">
            <v-col
              v-for="resource in resources.technological"
              :key="resource.id"
              cols="12"
              md="6"
            >
              <v-card variant="outlined">
                <v-card-text>
                  <div class="d-flex align-center justify-space-between mb-2">
                    <div class="d-flex align-center gap-2">
                      <v-avatar color="info" size="40">
                        <v-icon>mdi-laptop</v-icon>
                      </v-avatar>
                      <div>
                        <div class="font-weight-bold">{{ resource.name }}</div>
                        <div class="text-caption text-medium-emphasis">
                          {{ resource.type }}
                        </div>
                      </div>
                    </div>
                    <div class="d-flex gap-1">
                      <v-btn
                        icon="mdi-pencil"
                        size="x-small"
                        variant="text"
                        @click="editResource('technological', resource)"
                      />
                      <v-btn
                        color="error"
                        icon="mdi-delete"
                        size="x-small"
                        variant="text"
                        @click="deleteResource('technological', resource)"
                      />
                    </div>
                  </div>
                  <div v-if="resource.version" class="text-body-2 mb-1">
                    <strong>Version:</strong> {{ resource.version }}
                  </div>
                  <div v-if="resource.description" class="text-caption">
                    {{ resource.description }}
                  </div>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>
          <v-alert v-else type="info" variant="tonal">
            Aucune ressource technologique définie
          </v-alert>
        </v-window-item>

        <!-- Documentary Resources -->
        <v-window-item value="documentary">
          <div class="d-flex justify-end mb-3">
            <v-btn
              color="primary"
              prepend-icon="mdi-plus"
              size="small"
              @click="openDialog('documentary')"
            >
              Ajouter un document
            </v-btn>
          </div>

          <v-list v-if="resources.documentary.length > 0">
            <v-list-item
              v-for="resource in resources.documentary"
              :key="resource.id"
              class="mb-2"
            >
              <template #prepend>
                <v-avatar color="success">
                  <v-icon>mdi-file-document</v-icon>
                </v-avatar>
              </template>

              <v-list-item-title>{{ resource.name }}</v-list-item-title>
              <v-list-item-subtitle>
                {{ resource.reference }} - {{ resource.type }}
              </v-list-item-subtitle>

              <template #append>
                <div class="d-flex gap-1">
                  <v-btn
                    icon="mdi-eye"
                    size="x-small"
                    variant="text"
                    @click="viewDocument(resource)"
                  />
                  <v-btn
                    icon="mdi-pencil"
                    size="x-small"
                    variant="text"
                    @click="editResource('documentary', resource)"
                  />
                  <v-btn
                    color="error"
                    icon="mdi-delete"
                    size="x-small"
                    variant="text"
                    @click="deleteResource('documentary', resource)"
                  />
                </div>
              </template>
            </v-list-item>
          </v-list>
          <v-alert v-else type="info" variant="tonal">
            Aucune ressource documentaire définie
          </v-alert>
        </v-window-item>

        <!-- Material Resources -->
        <v-window-item value="material">
          <div class="d-flex justify-end mb-3">
            <v-btn
              color="primary"
              prepend-icon="mdi-plus"
              size="small"
              @click="openDialog('material')"
            >
              Ajouter une ressource matérielle
            </v-btn>
          </div>

          <v-row v-if="resources.material.length > 0">
            <v-col
              v-for="resource in resources.material"
              :key="resource.id"
              cols="12"
              md="6"
            >
              <v-card variant="outlined">
                <v-card-text>
                  <div class="d-flex align-center justify-space-between mb-2">
                    <div class="d-flex align-center gap-2">
                      <v-avatar color="warning" size="40">
                        <v-icon>mdi-toolbox</v-icon>
                      </v-avatar>
                      <div>
                        <div class="font-weight-bold">{{ resource.name }}</div>
                        <div class="text-caption text-medium-emphasis">
                          {{ resource.category }}
                        </div>
                      </div>
                    </div>
                    <div class="d-flex gap-1">
                      <v-btn
                        icon="mdi-pencil"
                        size="x-small"
                        variant="text"
                        @click="editResource('material', resource)"
                      />
                      <v-btn
                        color="error"
                        icon="mdi-delete"
                        size="x-small"
                        variant="text"
                        @click="deleteResource('material', resource)"
                      />
                    </div>
                  </div>
                  <div v-if="resource.quantity" class="text-body-2 mb-1">
                    <strong>Quantité:</strong> {{ resource.quantity }}
                  </div>
                  <div v-if="resource.location" class="text-caption">
                    <v-icon size="small">mdi-map-marker</v-icon>
                    {{ resource.location }}
                  </div>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>
          <v-alert v-else type="info" variant="tonal">
            Aucune ressource matérielle définie
          </v-alert>
        </v-window-item>
      </v-window>
    </v-card-text>

    <!-- Dialog for Add/Edit -->
    <v-dialog v-model="showDialog" max-width="600">
      <v-card>
        <v-card-title>
          {{ editingResource ? 'Modifier' : 'Ajouter' }} - {{ getResourceTypeLabel(dialogType) }}
        </v-card-title>

        <v-card-text>
          <v-form ref="formRef">
            <!-- Common Fields -->
            <v-text-field
              v-model="formData.name"
              label="Nom *"
              :rules="[rules.required]"
              variant="outlined"
            />

            <!-- Human Specific -->
            <template v-if="dialogType === 'human'">
              <v-text-field
                v-model="formData.role"
                class="mt-3"
                label="Rôle/Fonction"
                variant="outlined"
              />
              <v-textarea
                v-model="formData.competences"
                class="mt-3"
                label="Compétences"
                rows="2"
                variant="outlined"
              />
              <v-text-field
                v-model="formData.availability"
                class="mt-3"
                label="Disponibilité"
                placeholder="Ex: Temps plein, 50%..."
                variant="outlined"
              />
            </template>

            <!-- Technological Specific -->
            <template v-if="dialogType === 'technological'">
              <v-text-field
                v-model="formData.type"
                class="mt-3"
                label="Type"
                placeholder="Ex: Logiciel, Équipement..."
                variant="outlined"
              />
              <v-text-field
                v-model="formData.version"
                class="mt-3"
                label="Version"
                variant="outlined"
              />
              <v-textarea
                v-model="formData.description"
                class="mt-3"
                label="Description"
                rows="2"
                variant="outlined"
              />
            </template>

            <!-- Documentary Specific -->
            <template v-if="dialogType === 'documentary'">
              <v-text-field
                v-model="formData.reference"
                class="mt-3"
                label="Référence"
                variant="outlined"
              />
              <v-select
                v-model="formData.type"
                class="mt-3"
                :items="['Procédure', 'Instruction', 'Formulaire', 'Guide', 'Autre']"
                label="Type de document"
                variant="outlined"
              />
            </template>

            <!-- Material Specific -->
            <template v-if="dialogType === 'material'">
              <v-text-field
                v-model="formData.category"
                class="mt-3"
                label="Catégorie"
                placeholder="Ex: Équipement, Fourniture..."
                variant="outlined"
              />
              <v-text-field
                v-model="formData.quantity"
                class="mt-3"
                label="Quantité"
                type="number"
                variant="outlined"
              />
              <v-text-field
                v-model="formData.location"
                class="mt-3"
                label="Localisation"
                variant="outlined"
              />
            </template>
          </v-form>
        </v-card-text>

        <v-card-actions>
          <v-spacer />
          <v-btn @click="closeDialog">Annuler</v-btn>
          <v-btn color="primary" @click="saveResource">
            {{ editingResource ? 'Modifier' : 'Ajouter' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-card>
</template>

<script setup lang="ts">
  import { ref } from 'vue'

  interface Resource {
    [key: string]: any
    id?: number
    name: string
  }

  interface Props {
    processId: number
    resources: {
      human: Resource[]
      technological: Resource[]
      documentary: Resource[]
      material: Resource[]
    }
  }

  interface Emits {
    (e: 'add' | 'update' | 'delete', type: string, resource: Resource): void
  }

  defineProps<Props>()
  const emit = defineEmits<Emits>()

  const activeTab = ref('human')
  const showDialog = ref(false)
  const dialogType = ref<string>('human')
  const editingResource = ref<Resource | null>(null)
  const formRef = ref()

  const formData = ref<Resource>({
    name: '',
  })

  const rules = {
    required: (v: any) => !!v || 'Ce champ est requis',
  }

  function getResourceTypeLabel (type: string): string {
    const labels: Record<string, string> = {
      human: 'Ressource Humaine',
      technological: 'Ressource Technologique',
      documentary: 'Ressource Documentaire',
      material: 'Ressource Matérielle',
    }
    return labels[type] || type
  }

  function viewDocument (resource: Resource) {
    // TODO: Implement document viewer
    console.log('View document:', resource)
  }

  function openDialog (type: string) {
    dialogType.value = type
    editingResource.value = null
    formData.value = { name: '' }
    showDialog.value = true
  }

  function editResource (type: string, resource: Resource) {
    dialogType.value = type
    editingResource.value = resource
    formData.value = { ...resource }
    showDialog.value = true
  }

  function deleteResource (type: string, resource: Resource) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette ressource ?')) {
      emit('delete', type, resource)
    }
  }

  async function saveResource () {
    const { valid } = await formRef.value.validate()
    if (!valid) return

    if (editingResource.value) {
      emit('update', dialogType.value, { ...editingResource.value, ...formData.value })
    } else {
      emit('add', dialogType.value, formData.value)
    }

    closeDialog()
  }

  function closeDialog () {
    showDialog.value = false
    editingResource.value = null
    formData.value = { name: '' }
    formRef.value?.resetValidation()
  }
</script>
