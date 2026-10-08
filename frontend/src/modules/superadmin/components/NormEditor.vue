<template>
  <v-dialog
    max-width="1400px"
    :model-value="modelValue"
    persistent
    scrollable
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <v-card style="height: 90vh">
      <!-- Header -->
      <v-card-title class="d-flex align-center justify-space-between pa-6 bg-primary">
        <div class="d-flex align-center">
          <v-icon class="mr-3" color="white" size="32">mdi-certificate</v-icon>
          <div>
            <h2 class="text-h5 font-weight-bold text-white">
              {{ editMode ? 'Modifier la norme' : 'Créer une norme ISO' }}
            </h2>
            <p class="text-caption text-white mb-0" style="opacity: 0.9">
              Structure hiérarchique conforme aux normes ISO
            </p>
          </div>
        </div>
        <v-btn
          color="white"
          icon="mdi-close"
          variant="text"
          @click="handleClose"
        />
      </v-card-title>

      <!-- Tabs -->
      <v-tabs v-model="activeTab" bg-color="surface-variant">
        <v-tab value="info">
          <v-icon start>mdi-information-outline</v-icon>
          Informations générales
        </v-tab>
        <v-tab value="structure">
          <v-icon start>mdi-file-tree</v-icon>
          Structure hiérarchique
        </v-tab>
        <v-tab value="import">
          <v-icon start>mdi-file-excel</v-icon>
          Importer depuis Excel
        </v-tab>
      </v-tabs>

      <v-divider />

      <!-- Content -->
      <v-card-text class="pa-0" style="height: calc(90vh - 200px); overflow-y: auto">
        <v-window v-model="activeTab">
          <!-- Tab 1: Informations générales -->
          <v-window-item value="info">
            <v-container class="pa-8">
              <v-row>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="norm.code"
                    density="comfortable"
                    :hint="editMode ? 'Le code est verrouillé après création.' : 'Format: ISO XXXX:YYYY'"
                    label="Code ISO *"
                    persistent-hint
                    placeholder="Ex: ISO 9001:2015"
                    prepend-inner-icon="mdi-barcode"
                    :readonly="editMode"
                    :rules="[rules.required]"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="norm.version"
                    density="comfortable"
                    label="Version *"
                    placeholder="Ex: 2015"
                    prepend-inner-icon="mdi-tag"
                    :rules="[rules.required]"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="norm.domain"
                    density="comfortable"
                    :items="domainOptions"
                    label="Domaine *"
                    prepend-inner-icon="mdi-application-brackets"
                    :rules="[rules.required]"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12">
                  <v-text-field
                    v-model="norm.name"
                    density="comfortable"
                    label="Nom de la norme *"
                    placeholder="Ex: Systèmes de management de la qualité"
                    prepend-inner-icon="mdi-format-title"
                    :rules="[rules.required]"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12">
                  <v-textarea
                    v-model="norm.description"
                    label="Description"
                    placeholder="Décrivez l'objectif et le domaine d'application de cette norme..."
                    prepend-inner-icon="mdi-text"
                    rows="4"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12">
                  <v-switch
                    v-model="norm.publish"
                    color="success"
                    hide-details
                    label="Publier immédiatement"
                  />
                </v-col>
              </v-row>

              <!-- Quick Info -->
              <v-alert
                class="mt-4"
                color="info"
                icon="mdi-information"
                variant="tonal"
              >
                <div class="text-body-2">
                  <strong>Structure ISO standard :</strong><br>
                  • 10 chapitres principaux (Domaine d'application → Amélioration)<br>
                  • Jusqu'à 6 niveaux de profondeur (7.2.1.a.i.1)<br>
                  • Support des exigences ("doit"), recommandations ("devrait") et notes
                </div>
              </v-alert>
            </v-container>
          </v-window-item>

          <!-- Tab 2: Structure hiérarchique -->
          <v-window-item value="structure">
            <v-container class="pa-6" fluid>
              <v-row>
                <!-- Toolbar -->
                <v-col cols="12">
                  <div class="d-flex align-center justify-space-between mb-4">
                    <div>
                      <h3 class="text-h6 font-weight-bold mb-1">Arborescence de la norme</h3>
                      <p class="text-caption text-medium-emphasis mb-0">
                        {{ norm.nodes.length }} élément(s) •
                        {{ countByType('chapter') }} chapitre(s) •
                        {{ countByType('requirement') }} exigence(s)
                      </p>
                    </div>
                    <div class="d-flex gap-2">
                      <v-btn
                        color="primary"
                        prepend-icon="mdi-plus"
                        variant="tonal"
                        @click="openNodeDialog('chapter')"
                      >
                        Ajouter un chapitre
                      </v-btn>
                      <v-btn
                        color="secondary"
                        prepend-icon="mdi-plus"
                        variant="tonal"
                        @click="openNodeDialog('annex')"
                      >
                        Ajouter une annexe
                      </v-btn>
                    </div>
                  </div>
                </v-col>

                <!-- Tree View -->
                <v-col cols="12">
                  <v-card variant="outlined">
                    <!-- Debug info -->
                    <v-alert
                      v-if="norm.nodes.length > 0"
                      class="ma-4"
                      color="info"
                      density="compact"
                      icon="mdi-information"
                      variant="tonal"
                    >
                      <div class="text-body-2">
                        <strong>Structure chargée:</strong> {{ norm.nodes.length }} élément(s) de niveau racine
                        <br>
                        <small>Les chapitres, sous-chapitres et autres sections sont organisés hiérarchiquement</small>
                      </div>
                    </v-alert>

                    <v-card-text v-if="norm.nodes.length === 0" class="pa-8 text-center">
                      <v-icon color="grey-lighten-1" size="64">mdi-file-tree-outline</v-icon>
                      <p class="text-h6 text-medium-emphasis mt-4 mb-2">
                        Aucune structure définie
                      </p>
                      <p class="text-body-2 text-disabled mb-4">
                        Commencez par ajouter un chapitre ou importez depuis Excel
                      </p>
                      <v-btn
                        color="primary"
                        prepend-icon="mdi-plus"
                        @click="openNodeDialog('chapter')"
                      >
                        Ajouter le premier chapitre
                      </v-btn>
                    </v-card-text>

                    <v-card-text v-else class="pa-4">
                      <v-alert
                        v-if="treeItems.length === 0"
                        class="mb-4"
                        color="warning"
                        icon="mdi-alert"
                        variant="tonal"
                      >
                        Structure chargée mais aucun élément à afficher
                      </v-alert>

                      <v-treeview
                        v-else
                        class="norm-tree"
                        density="compact"
                        item-title="displayTitle"
                        item-value="id"
                        :items="treeItems"
                        open-all
                      >
                        <template #prepend="{ item }">
                          <v-icon :color="getNormNodeColor(item.type)" size="20">
                            {{ getNormNodeIcon(item.type) }}
                          </v-icon>
                        </template>

                        <template #append="{ item }">
                          <div class="d-flex align-center gap-1">
                            <v-chip
                              v-if="item.isObligatory"
                              color="error"
                              size="x-small"
                              variant="flat"
                            >
                              DOIT
                            </v-chip>
                            <v-btn
                              icon="mdi-plus"
                              size="x-small"
                              variant="text"
                              @click.stop="openChildNodeDialog(item)"
                            />
                            <v-btn
                              icon="mdi-pencil"
                              size="x-small"
                              variant="text"
                              @click.stop="editNode(item)"
                            />
                            <v-btn
                              color="error"
                              icon="mdi-delete"
                              size="x-small"
                              variant="text"
                              @click.stop="deleteNode(item)"
                            />
                          </div>
                        </template>
                      </v-treeview>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>
            </v-container>
          </v-window-item>

          <!-- Tab 3: Import Excel -->
          <v-window-item value="import">
            <v-container class="pa-8">
              <v-row>
                <v-col cols="12">
                  <v-card class="mb-4" variant="outlined">
                    <v-card-text class="pa-6">
                      <div class="d-flex align-center mb-4">
                        <v-icon class="mr-3" color="success" size="32">mdi-file-excel</v-icon>
                        <div>
                          <h3 class="text-h6 font-weight-bold mb-1">
                            Importer une norme depuis Excel
                          </h3>
                          <p class="text-body-2 text-medium-emphasis mb-0">
                            Préparez votre fichier Excel selon le format requis
                          </p>
                        </div>
                      </div>

                      <!-- Download Template Button -->
                      <v-alert
                        class="mb-4"
                        density="compact"
                        type="info"
                        variant="tonal"
                      >
                        <div class="d-flex justify-space-between align-center">
                          <span class="text-body-2">
                            <v-icon class="mr-1" size="small">mdi-download</v-icon>
                            Besoin d'un modèle ? Téléchargez le template Excel pré-formaté
                          </span>
                          <v-btn
                            color="primary"
                            :loading="downloadingTemplate"
                            prepend-icon="mdi-file-download"
                            size="small"
                            variant="outlined"
                            @click="downloadTemplate"
                          >
                            Télécharger le modèle
                          </v-btn>
                        </div>
                      </v-alert>

                      <v-file-input
                        v-model="excelFile"
                        accept=".xlsx,.xls"
                        label="Sélectionner un fichier Excel"
                        :loading="importing"
                        prepend-icon="mdi-paperclip"
                        variant="outlined"
                        @change="handleFileChange"
                      >
                        <template #append>
                          <v-btn
                            color="primary"
                            :disabled="!hasExcelFile || importing"
                            :loading="importing"
                            @click="importFromExcel"
                          >
                            Importer
                          </v-btn>
                        </template>
                      </v-file-input>
                    </v-card-text>
                  </v-card>

                  <!-- Format Info -->
                  <v-expansion-panels>
                    <v-expansion-panel>
                      <v-expansion-panel-title>
                        <v-icon class="mr-2">mdi-information</v-icon>
                        Format du fichier Excel attendu
                      </v-expansion-panel-title>
                      <v-expansion-panel-text>
                        <v-table density="compact">
                          <thead>
                            <tr>
                              <th>Colonne</th>
                              <th>Description</th>
                              <th>Exemple</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr>
                              <td><strong>Code</strong></td>
                              <td>Code du nœud</td>
                              <td>7.2.1</td>
                            </tr>
                            <tr>
                              <td><strong>Type</strong></td>
                              <td>Type (chapter, requirement, note...)</td>
                              <td>requirement</td>
                            </tr>
                            <tr>
                              <td><strong>Titre</strong></td>
                              <td>Titre du nœud</td>
                              <td>Compétences</td>
                            </tr>
                            <tr>
                              <td><strong>Contenu</strong></td>
                              <td>Description complète</td>
                              <td>L'organisme doit...</td>
                            </tr>
                            <tr>
                              <td><strong>Parent</strong></td>
                              <td>Code du parent (optionnel)</td>
                              <td>7.2</td>
                            </tr>
                            <tr>
                              <td><strong>Obligatoire</strong></td>
                              <td>Oui/Non pour "doit/devrait"</td>
                              <td>Oui</td>
                            </tr>
                          </tbody>
                        </v-table>
                      </v-expansion-panel-text>
                    </v-expansion-panel>
                  </v-expansion-panels>
                </v-col>
              </v-row>
            </v-container>
          </v-window-item>
        </v-window>
      </v-card-text>

      <v-divider />

      <!-- Actions -->
      <v-card-actions class="pa-6">
        <v-spacer />
        <v-btn
          variant="text"
          @click="handleClose"
        >
          Annuler
        </v-btn>
        <v-btn
          color="primary"
          :loading="saving"
          variant="flat"
          @click="handleSave"
        >
          {{ editMode ? 'Mettre à jour' : 'Créer la norme' }}
        </v-btn>
      </v-card-actions>
    </v-card>

    <!-- Node Dialog -->
    <v-dialog
      v-model="nodeDialog"
      max-width="800px"
      scrollable
    >
      <v-card>
        <v-card-title class="bg-surface-variant pa-4">
          <div class="d-flex align-center">
            <v-icon class="mr-2" :color="selectedNodeType ? getNormNodeColor(selectedNodeType) : 'primary'">
              {{ selectedNodeType ? getNormNodeIcon(selectedNodeType) : 'mdi-file' }}
            </v-icon>
            {{ editingNode ? 'Modifier' : 'Ajouter' }} {{ selectedNodeType ? getNormNodeTypeLabel(selectedNodeType) : 'un élément' }}
          </div>
        </v-card-title>

        <v-divider />

        <v-card-text class="pa-6">
          <v-row>
            <v-col cols="12" md="6">
              <v-select
                v-model="selectedNodeType"
                density="comfortable"
                :disabled="editingNode !== null"
                item-title="label"
                item-value="value"
                :items="nodeTypeOptions"
                label="Type *"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="nodeForm.code"
                density="comfortable"
                hint="Numérotation hiérarchique"
                label="Code *"
                placeholder="Ex: 7.2.1"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12">
              <v-text-field
                v-model="nodeForm.title"
                density="comfortable"
                label="Titre *"
                placeholder="Ex: Compétences"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12">
              <v-textarea
                v-model="nodeForm.content"
                label="Contenu"
                placeholder="Décrivez l'exigence, la recommandation ou la note..."
                rows="4"
                variant="outlined"
              />
            </v-col>
            <v-col v-if="selectedNodeType === 'requirement'" cols="12">
              <v-switch
                v-model="nodeForm.isObligatory"
                color="error"
                hide-details
                label="Exigence obligatoire (utilise 'doit')"
              />
            </v-col>
          </v-row>
        </v-card-text>

        <v-divider />

        <v-card-actions class="pa-4">
          <v-spacer />
          <v-btn variant="text" @click="nodeDialog = false">Annuler</v-btn>
          <v-btn color="primary" variant="flat" @click="saveNode">
            {{ editingNode ? 'Mettre à jour' : 'Ajouter' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-dialog>

  <ConfirmDialog
    v-model="confirmDeleteDialog"
    confirm-label="Supprimer"
    impact="Impact: l'élément et ses sous-sections seront supprimés."
    :message="`Êtes-vous sûr de vouloir supprimer '${nodeToDelete?.code || ''} ${nodeToDelete?.title || ''}' ?`"
    title="Supprimer l'élément"
    @cancel="confirmDeleteDialog = false"
    @confirm="handleConfirmDelete"
  />

  <ConfirmDialog
    v-model="confirmCloseDialog"
    confirm-label="Fermer"
    message="Les modifications non sauvegardées seront perdues."
    title="Fermer l'éditeur"
    @cancel="confirmCloseDialog = false"
    @confirm="handleConfirmClose"
  />
</template>

<script setup lang="ts">
  import type { NormNode, NormNodeType, NormStructure } from '@/types/norms'
  import { computed, ref, watch } from 'vue'
  import { useToast } from '@/composables/useToast'
  import ConfirmDialog from '@/modules/superadmin/components/ConfirmDialog.vue'
  import {
    type NormImportFileModel,
    resolveNormImportFile,
    validateNormImportFile,
  } from '@/modules/superadmin/utils/normImportFile'
  import normService from '@/services/normService'
  import {
    createNormNode,
    getNormNodeColor,
    getNormNodeIcon,
    getNormNodeTypeLabel,
    validateHierarchy,
  } from '@/types/norms'

  const props = defineProps<{
    modelValue: boolean
    normData?: NormStructure | null
  }>()

  const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'save', norm: NormStructure): void
  }>()

  const toast = useToast()

  const editMode = computed(() => !!props.normData?.id)
  const activeTab = ref('info')
  const saving = ref(false)
  const importing = ref(false)
  const downloadingTemplate = ref(false)

  // Form data
  const norm = ref<NormStructure>({
    code: '',
    name: '',
    version: '',
    domain: 'quality',
    description: '',
    publish: true,
    nodes: [],
  })

  const domainOptions = [
    { title: 'Qualité (ISO 9001)', value: 'quality' },
    { title: 'Environnement (ISO 14001)', value: 'environment' },
    { title: 'Sécurité de l\'information (ISO 27001)', value: 'security' },
    { title: 'Sécurité alimentaire (ISO 22000)', value: 'food_safety' },
    { title: 'Système intégré (QHSE)', value: 'integrated' },
    { title: 'Autre', value: 'other' },
  ]

  // Node editing
  const nodeDialog = ref(false)
  const selectedNodeType = ref<NormNodeType>('chapter')
  const editingNode = ref<NormNode | null>(null)
  const parentNode = ref<NormNode | null>(null)
  const confirmDeleteDialog = ref(false)
  const confirmCloseDialog = ref(false)
  const nodeToDelete = ref<NormNode | null>(null)
  const nodeForm = ref({
    code: '',
    title: '',
    content: '',
    isObligatory: true,
  })

  // Excel import
  const excelFile = ref<NormImportFileModel>(null)
  const hasExcelFile = computed(() => !!resolveNormImportFile(excelFile.value))

  // Rules
  const rules = {
    required: (v: string) => !!v || 'Ce champ est requis',
  }

  // Node type options
  const nodeTypeOptions = [
    { label: 'Chapitre', value: 'chapter' },
    { label: 'Sous-chapitre', value: 'subchapter' },
    { label: 'Paragraphe', value: 'paragraph' },
    { label: 'Exigence', value: 'requirement' },
    { label: 'Recommandation', value: 'recommendation' },
    { label: 'Point', value: 'point' },
    { label: 'Sous-point', value: 'subpoint' },
    { label: 'Détail', value: 'detail' },
    { label: 'Note', value: 'note' },
    { label: 'Annexe', value: 'annex' },
  ]

  // Tree items for v-treeview
  const treeItems = computed(() => {
    return buildTreeItems(norm.value.nodes)
  })

  function buildTreeItems (nodes: NormNode[]): any[] {
    return nodes.map(node => ({
      ...node,
      displayTitle: `${node.code} ${node.title}`,
      children: node.children ? buildTreeItems(node.children) : [],
    }))
  }

  // Count nodes by type
  function countByType (type: NormNodeType): number {
    let count = 0

    function countRecursive (nodes: NormNode[]) {
      for (const node of nodes) {
        if (node.type === type) count++
        if (node.children) countRecursive(node.children)
      }
    }

    countRecursive(norm.value.nodes)
    return count
  }

  // Open node dialog
  function openNodeDialog (type: NormNodeType) {
    selectedNodeType.value = type
    editingNode.value = null
    parentNode.value = null
    nodeForm.value = {
      code: '',
      title: '',
      content: '',
      isObligatory: type === 'requirement',
    }
    nodeDialog.value = true
  }

  function openChildNodeDialog (parent: NormNode) {
    parentNode.value = parent
    selectedNodeType.value = 'subchapter'
    editingNode.value = null
    nodeForm.value = {
      code: '',
      title: '',
      content: '',
      isObligatory: false,
    }
    nodeDialog.value = true
  }

  function editNode (node: NormNode) {
    editingNode.value = node
    selectedNodeType.value = node.type
    nodeForm.value = {
      code: node.code,
      title: node.title,
      content: node.content,
      isObligatory: node.isObligatory || false,
    }
    nodeDialog.value = true
  }

  function saveNode () {
    if (!nodeForm.value.code || !nodeForm.value.title) {
      toast.error('Veuillez remplir tous les champs obligatoires')
      return
    }

    if (editingNode.value) {
      // Update existing node
      editingNode.value.code = nodeForm.value.code
      editingNode.value.title = nodeForm.value.title
      editingNode.value.content = nodeForm.value.content
      editingNode.value.isObligatory = nodeForm.value.isObligatory
      toast.success('Élément mis à jour')
    } else {
      // Create new node
      const newNode = createNormNode(
        selectedNodeType.value,
        nodeForm.value.code,
        nodeForm.value.title,
        parentNode.value?.id,
      )
      newNode.content = nodeForm.value.content
      newNode.isObligatory = nodeForm.value.isObligatory
      newNode.order = Math.max(
        (parentNode.value?.children?.length || (Math.max(norm.value.nodes.length, 0))) || 0,
        0,
      )

      if (parentNode.value) {
        if (!parentNode.value.children) parentNode.value.children = []
        parentNode.value.children.push(newNode)
      } else {
        norm.value.nodes.push(newNode)
      }

      toast.success('Élément ajouté')
    }

    // Force reactivity update by reassigning the nodes array
    norm.value.nodes = [...norm.value.nodes]
    nodeDialog.value = false
  }

  function deleteNode (node: NormNode) {
    nodeToDelete.value = node
    confirmDeleteDialog.value = true
  }

  function handleConfirmDelete () {
    const node = nodeToDelete.value
    if (!node) return

    function removeFromTree (nodes: NormNode[], targetId: string): boolean {
      for (let i = 0; i < nodes.length; i++) {
        const currentNode = nodes[i]
        if (!currentNode) continue

        if (currentNode.id === targetId) {
          nodes.splice(i, 1)
          return true
        }
        if (currentNode.children && removeFromTree(currentNode.children, targetId)) {
          return true
        }
      }
      return false
    }

    removeFromTree(norm.value.nodes, node.id)
    nodeToDelete.value = null
    confirmDeleteDialog.value = false
    toast.success('Élément supprimé')
  }

  // Excel import
  function handleFileChange () {
  // File selected
  }

  async function downloadTemplate () {
    downloadingTemplate.value = true
    try {
      const blob = await normService.downloadTemplate()

      // Create download link
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = 'norme_iso_template.xlsx'
      document.body.append(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)

      toast.success('Modèle téléchargé')
    } catch (error: any) {
      toast.error(error.message || 'Erreur lors du téléchargement du modèle')
    } finally {
      downloadingTemplate.value = false
    }
  }

  async function importFromExcel () {
    if (!norm.value.code || !norm.value.name || !norm.value.version || !norm.value.domain) {
      toast.error('Renseignez le code, le nom, la version et le domaine avant import.')
      activeTab.value = 'info'
      return
    }

    importing.value = true

    try {
      const file = resolveNormImportFile(excelFile.value)
      const fileError = validateNormImportFile(file)
      if (fileError) {
        toast.error(fileError)
        return
      }
      if (!file) {
        toast.error('Veuillez sélectionner un fichier Excel.')
        return
      }

      const action = editMode.value ? 'replace' : 'create'
      const result = await normService.importExcel(file, {
        code: norm.value.code,
        name: norm.value.name,
        description: norm.value.description || '',
        domain: norm.value.domain,
        version_code: norm.value.version,
        action,
      })

      const importedNorm = result.norm
      const sections = importedNorm.currentVersion?.sections || []
      norm.value = {
        id: importedNorm.id,
        code: importedNorm.code || norm.value.code,
        name: importedNorm.name || norm.value.name,
        version: importedNorm.currentVersion?.version_code || norm.value.version,
        domain: importedNorm.domain || norm.value.domain,
        description: importedNorm.description || norm.value.description,
        publish: importedNorm.status === 'published',
        nodes: mapSectionsToNodes(sections),
      }

      const validationWarnings = result.validation_errors?.length || 0
      toast.success(
        validationWarnings > 0
          ? `Import terminé avec ${validationWarnings} avertissement(s).`
          : 'Norme importée avec succès.',
      )
      activeTab.value = 'structure'
    } catch (error: any) {
      const status = error?.response?.status
      if (status === 409) {
        toast.error('Une norme avec ce code existe déjà. Ouvrez-la puis relancez en mode édition.')
      } else {
        toast.error(error?.response?.data?.message || 'Erreur lors de l\'import du fichier Excel')
      }
    } finally {
      importing.value = false
    }
  }

  function mapSectionsToNodes (sections: any[]): NormNode[] {
    return (sections || []).map((section: any) => {
      const detectedType = nodeTypeOptions.some(option => option.value === section.type)
        ? section.type as NormNodeType
        : 'paragraph'
      const node = createNormNode(
        detectedType,
        section.number || section.code || '',
        section.title || '',
        section.parent_id || null,
      )
      node.id = String(section.id || node.id)
      node.content = section.content || ''
      node.isObligatory = Boolean(section.is_obligatory || section.isObligatory)
      node.order = Number(section.order_index || section.order || 0)
      node.children = mapSectionsToNodes(section.children || [])
      return node
    })
  }

  // Save
  function handleSave () {
    if (!norm.value.code || !norm.value.name || !norm.value.version) {
      toast.error('Veuillez remplir tous les champs obligatoires')
      activeTab.value = 'info'
      return
    }

    // Validate hierarchy
    const errors: string[] = []
    for (const node of norm.value.nodes) {
      errors.push(...validateHierarchy(node))
    }

    if (errors.length > 0) {
      toast.error(`Erreurs de validation: ${errors.join(', ')}`)
      return
    }

    saving.value = true
    setTimeout(() => {
      emit('save', norm.value)
      saving.value = false
    }, 500)
  }

  function handleClose () {
    confirmCloseDialog.value = true
  }

  function handleConfirmClose () {
    confirmCloseDialog.value = false
    emit('update:modelValue', false)
  }

  // Watch for normData changes
  watch(() => props.normData, newData => {
    norm.value = newData
      ? {
        id: newData.id,
        code: newData.code || '',
        name: newData.name || '',
        version: newData.version || '',
        domain: newData.domain || 'quality',
        description: newData.description || '',
        publish: newData.publish ?? true,
        nodes: newData.nodes || [],
      }
      : {
        code: '',
        name: '',
        version: '',
        domain: 'quality',
        description: '',
        publish: true,
        nodes: [],
      }
  }, { immediate: true })
</script>

<style scoped>
.norm-tree :deep(.v-treeview-node) {
  padding: 8px 0;
}

.norm-tree :deep(.v-treeview-node__content) {
  align-items: center;
}
</style>
