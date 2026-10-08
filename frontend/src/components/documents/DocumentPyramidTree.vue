<template>
  <div class="document-pyramid-tree bg-gray-50 p-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-800">Pyramide Documentaire</h1>
        <p class="text-gray-600 mt-1">Structure N1 → N5 - ISO 9001:2015 clause 7.5</p>
      </div>
      <div class="flex gap-2">
        <v-btn
          color="primary"
          prepend-icon="mdi-file-plus"
          @click="createDocument"
        >
          Nouveau Document
        </v-btn>
        <v-btn
          color="success"
          prepend-icon="mdi-file-excel"
          variant="outlined"
          @click="exportComplete"
        >
          Export Complet
        </v-btn>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-5 gap-4 mb-6">
      <div
        v-for="level in pyramidLevels"
        :key="level.code"
        class="bg-white rounded-lg shadow p-5 cursor-pointer hover:shadow-lg transition-shadow"
        :class="selectedLevel === level.code ? 'ring-2 ring-blue-500' : ''"
        @click="selectLevel(level.code)"
      >
        <div class="flex items-center justify-between mb-3">
          <div
            class="w-12 h-12 rounded-full flex items-center justify-center font-bold text-white"
            :style="{ backgroundColor: level.color }"
          >
            {{ level.code }}
          </div>
          <v-chip :color="level.chipColor" size="small">
            {{ level.stats.total }}
          </v-chip>
        </div>
        <p class="text-sm font-semibold text-gray-800">{{ level.name }}</p>
        <div class="flex items-center gap-2 mt-2 text-xs text-gray-600">
          <v-icon color="green" size="small">mdi-check-circle</v-icon>
          <span>{{ level.stats.approved }} approuvés</span>
        </div>
      </div>
    </div>

    <!-- Pyramid Visualization -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
      <h3 class="text-lg font-semibold mb-6">Visualisation Pyramidale</h3>

      <div class="pyramid-container">
        <div
          v-for="(level, index) in pyramidLevels"
          :key="level.code"
          class="pyramid-level"
          :style="{ width: `${100 - (index * 15)}%` }"
          @click="selectLevel(level.code)"
        >
          <div
            class="pyramid-block"
            :style="{ backgroundColor: level.color }"
          >
            <div class="flex items-center justify-between px-6 py-4">
              <div class="text-white">
                <span class="font-bold text-lg">{{ level.code }}</span>
                <span class="ml-3 text-sm">{{ level.name }}</span>
              </div>
              <div class="text-white text-right">
                <div class="text-2xl font-bold">{{ level.stats.total }}</div>
                <div class="text-xs opacity-90">{{ level.stats.approved }} / {{ level.stats.total }} approuvés</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Level Details -->
    <div v-if="selectedLevel" class="grid grid-cols-3 gap-6 mb-6">
      <!-- Documents List -->
      <div class="col-span-2 bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold">Documents {{ selectedLevelData.name }}</h3>
          <div class="flex gap-2">
            <v-btn
              color="primary"
              prepend-icon="mdi-download"
              size="small"
              variant="outlined"
              @click="exportLevel(selectedLevel)"
            >
              Export {{ selectedLevel }}
            </v-btn>
            <v-btn
              color="success"
              prepend-icon="mdi-file-plus"
              size="small"
              @click="createDocumentLevel(selectedLevel)"
            >
              Créer {{ selectedLevel }}
            </v-btn>
          </div>
        </div>

        <v-table class="border" density="compact">
          <thead>
            <tr class="bg-gray-100">
              <th class="text-left">Code</th>
              <th class="text-left">Nom</th>
              <th class="text-left">Version</th>
              <th class="text-center">Statut</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="doc in levelDocuments"
              :key="doc.id"
              class="hover:bg-gray-50"
            >
              <td class="text-sm font-mono">{{ doc.code }}</td>
              <td class="text-sm">{{ doc.title }}</td>
              <td class="text-sm">v{{ doc.version }}</td>
              <td class="text-center">
                <v-chip
                  :color="getStatusColor(doc.status)"
                  size="x-small"
                >
                  {{ getStatusLabel(doc.status) }}
                </v-chip>
              </td>
              <td class="text-center">
                <v-btn
                  icon="mdi-eye"
                  size="x-small"
                  variant="text"
                  @click="viewDocument(doc.id)"
                />
                <v-btn
                  icon="mdi-download"
                  size="x-small"
                  variant="text"
                  @click="downloadDocument(doc.id)"
                />
              </td>
            </tr>
          </tbody>
        </v-table>

        <div v-if="levelDocuments.length === 0" class="text-center py-8 text-gray-500">
          <v-icon color="grey-lighten-1" size="64">mdi-file-document-outline</v-icon>
          <p class="mt-2">Aucun document {{ selectedLevelData.name }}</p>
          <v-btn
            class="mt-3"
            color="primary"
            size="small"
            @click="createDocumentLevel(selectedLevel)"
          >
            Créer le premier document
          </v-btn>
        </div>
      </div>

      <!-- Level Stats & Templates -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Statistiques {{ selectedLevel }}</h3>

        <div class="space-y-4">
          <!-- Progress Ring -->
          <div class="text-center">
            <div class="inline-block">
              <svg class="transform -rotate-90" height="120" width="120">
                <circle
                  cx="60"
                  cy="60"
                  fill="none"
                  r="50"
                  stroke="#E5E7EB"
                  stroke-width="10"
                />
                <circle
                  cx="60"
                  cy="60"
                  fill="none"
                  r="50"
                  :stroke="selectedLevelData.color"
                  :stroke-dasharray="`${getApprovedPercentage(selectedLevelData.stats)} 314`"
                  stroke-linecap="round"
                  stroke-width="10"
                />
              </svg>
              <div class="mt-2">
                <p class="text-2xl font-bold">{{ getApprovedPercentage(selectedLevelData.stats) }}%</p>
                <p class="text-sm text-gray-600">validé - version 1</p>
              </div>
            </div>
          </div>

          <!-- Stats Breakdown -->
          <div class="space-y-2">
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600">Total documents</span>
              <span class="font-semibold">{{ selectedLevelData.stats.total }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600">validé - version 1</span>
              <span class="font-semibold text-green-600">{{ selectedLevelData.stats.approved }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600">brouillon en attente de vérification</span>
              <span class="font-semibold text-blue-600">{{ selectedLevelData.stats.draft }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600">Obsolètes</span>
              <span class="font-semibold text-red-600">{{ selectedLevelData.stats.obsolete }}</span>
            </div>
          </div>

          <v-divider />

          <!-- Templates -->
          <div>
            <p class="text-sm font-semibold mb-2">Templates disponibles</p>
            <div class="space-y-2">
              <v-btn
                v-for="template in levelTemplates"
                :key="template.code"
                block
                prepend-icon="mdi-file-download"
                size="small"
                variant="outlined"
                @click="downloadTemplate(template.code)"
              >
                {{ template.name }}
              </v-btn>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Export Options Dialog -->
    <v-dialog v-model="exportDialog" max-width="500">
      <v-card>
        <v-card-title>Exporter la Pyramide</v-card-title>
        <v-card-text>
          <v-radio-group v-model="exportFormat">
            <v-radio label="Excel complet (tous niveaux)" value="complete" />
            <v-radio :label="`Excel niveau ${selectedLevel} uniquement`" value="level" />
            <v-radio label="PDF inventaire" value="pdf" />
          </v-radio-group>

          <v-select
            v-model="exportSite"
            class="mt-4"
            clearable
            :items="sites"
            label="Site (optionnel)"
          />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn @click="exportDialog = false">Annuler</v-btn>
          <v-btn color="primary" @click="confirmExport">Télécharger</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
  import { computed, ref } from 'vue'
  import { useRouter } from 'vue-router'

  const router = useRouter()

  // Data
  const selectedLevel = ref('N1')
  const exportDialog = ref(false)
  const exportFormat = ref('complete')
  const exportSite = ref(null)

  const sites = ref([
    { title: 'Tous les sites', value: null },
    { title: 'Siège Social', value: 1 },
    { title: 'Site Production', value: 2 },
  ])

  const pyramidLevels = ref([
    {
      code: 'N1',
      name: 'Politiques',
      color: '#EF4444',
      chipColor: 'red',
      stats: { total: 3, approved: 3, draft: 0, obsolete: 0 },
    },
    {
      code: 'N2',
      name: 'Manuels',
      color: '#F97316',
      chipColor: 'orange',
      stats: { total: 2, approved: 2, draft: 0, obsolete: 0 },
    },
    {
      code: 'N3',
      name: 'Procédures',
      color: '#EAB308',
      chipColor: 'amber',
      stats: { total: 24, approved: 18, draft: 5, obsolete: 1 },
    },
    {
      code: 'N4',
      name: 'Instructions',
      color: '#84CC16',
      chipColor: 'lime',
      stats: { total: 45, approved: 32, draft: 10, obsolete: 3 },
    },
    {
      code: 'N5',
      name: 'Enregistrements',
      color: '#3B82F6',
      chipColor: 'blue',
      stats: { total: 78, approved: 45, draft: 28, obsolete: 5 },
    },
  ])

  const documentsData = ref({
    N1: [
      { id: 1, code: 'POL-QUA-001', title: 'Politique Qualité QHSE', version: '2.0', status: 'approved' },
      { id: 2, code: 'POL-ENV-001', title: 'Politique Environnement', version: '1.5', status: 'approved' },
      { id: 3, code: 'POL-SEC-001', title: 'Politique Sécurité', version: '1.0', status: 'approved' },
    ],
    N2: [
      { id: 4, code: 'MAN-SMI-001', title: 'Manuel SMI', version: '3.0', status: 'approved' },
      { id: 5, code: 'MAN-QUA-001', title: 'Manuel Qualité', version: '2.2', status: 'approved' },
    ],
    N3: [
      { id: 6, code: 'PRD-GED-001', title: 'Procédure Gestion Documentaire', version: '1.8', status: 'approved' },
      { id: 7, code: 'PRD-AUD-001', title: 'Procédure Audits Internes', version: '2.0', status: 'approved' },
      { id: 8, code: 'PRD-NC-001', title: 'Procédure Non-Conformités', version: '1.5', status: 'draft' },
    ],
    N4: [],
    N5: [],
  })

  const templatesData = ref({
    N1: [
      { code: 'politique-qualite', name: 'Politique Qualité DOCX' },
    ],
    N2: [
      { code: 'manuel-qualite', name: 'Manuel Qualité DOCX' },
    ],
    N3: [
      { code: 'procedure-type', name: 'Procédure Type DOCX' },
    ],
    N4: [
      { code: 'instruction-type', name: 'Instruction Type DOCX' },
    ],
    N5: [
      { code: 'fiche-type', name: 'Fiche Enregistrement XLSX' },
    ],
  })

  // Computed
  const selectedLevelData = computed(() => {
    return pyramidLevels.value.find(l => l.code === selectedLevel.value) || pyramidLevels.value[0]
  })

  const levelDocuments = computed(() => {
    return documentsData.value[selectedLevel.value] || []
  })

  const levelTemplates = computed(() => {
    return templatesData.value[selectedLevel.value] || []
  })

  // Methods
  function selectLevel (level) {
    selectedLevel.value = level
  }

  function getApprovedPercentage (stats) {
    if (stats.total === 0) return 0
    return Math.round((stats.approved / stats.total) * 100)
  }

  function getStatusColor (status) {
    const colors = {
      approved: 'success',
      draft: 'primary',
      in_review: 'warning',
      obsolete: 'error',
    }
    return colors[status] || 'grey'
  }

  function getStatusLabel (status) {
    const labels = {
      approved: 'validé - version 1',
      draft: 'brouillon en attente de vérification',
      in_review: 'brouillon en cours de vérification',
      obsolete: 'Obsolète',
    }
    return labels[status] || status
  }

  function createDocument () {
    router.push('/documents/create')
  }

  function createDocumentLevel (level) {
    router.push(`/documents/create?level=${level}`)
  }

  function viewDocument (id) {
    router.push(`/documents/${id}`)
  }

  function downloadDocument (id) {
    console.log('Download document:', id)
  // TODO: API call
  }

  function exportLevel (_level) {
    exportDialog.value = true
    exportFormat.value = 'level'
  }

  function exportComplete () {
    exportDialog.value = true
    exportFormat.value = 'complete'
  }

  function confirmExport () {
    console.log('Export:', exportFormat.value, 'Site:', exportSite.value)
    // TODO: API call based on format
    if (exportFormat.value === 'complete') {
    // Call /api/v1/documents/pyramide/export-complete
    } else if (exportFormat.value === 'level') {
    // Call /api/v1/documents/pyramide/export/{level}
    }
    exportDialog.value = false
  }

  function downloadTemplate (templateCode) {
    console.log('Download template:', templateCode)
  // TODO: API call to /api/v1/documents/templates/{templateCode}
  }
</script>

<style scoped>
.document-pyramid-tree {
  min-height: calc(100vh - 64px);
}

.pyramid-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
  padding: 20px 0;
}

.pyramid-level {
  cursor: pointer;
  transition: all 0.3s ease;
}

.pyramid-level:hover {
  transform: scale(1.02);
}

.pyramid-block {
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  transition: box-shadow 0.3s ease;
}

.pyramid-block:hover {
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}
</style>
