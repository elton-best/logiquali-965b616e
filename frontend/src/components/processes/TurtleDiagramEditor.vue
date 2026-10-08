<template>
  <div class="turtle-diagram-editor bg-white rounded-lg shadow-lg p-6">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-gray-800">
        Diagramme Tortue - {{ process?.title || 'Nouveau Processus' }}
      </h2>
      <div class="flex gap-2">
        <v-btn
          color="primary"
          prepend-icon="mdi-refresh"
          variant="outlined"
          @click="resetDiagram"
        >
          Réinitialiser
        </v-btn>
        <v-btn
          color="success"
          :loading="saving"
          prepend-icon="mdi-content-save"
          @click="saveDiagram"
        >
          Enregistrer
        </v-btn>
      </div>
    </div>

    <!-- Turtle Diagram 3x3 Grid -->
    <div class="turtle-grid grid grid-cols-3 gap-4 mb-8">
      <!-- Row 1: QUI / PROCESSUS / AVEC QUOI -->
      <TurtleCell
        color="blue"
        icon="mdi-account-group"
        :items="diagram.who"
        subtitle="Ressources Humaines"
        title="QUI ?"
        @add="addItem('who')"
        @remove="removeItem('who', $event)"
        @update="updateItem('who', $event)"
      />

      <TurtleCell
        :centered="true"
        color="purple"
        icon="mdi-cog"
        :items="diagram.process"
        subtitle="Activités Principales"
        title="PROCESSUS"
        @add="addItem('process')"
        @remove="removeItem('process', $event)"
        @update="updateItem('process', $event)"
      />

      <TurtleCell
        color="green"
        icon="mdi-toolbox"
        :items="diagram.with_what"
        subtitle="Ressources Matérielles"
        title="AVEC QUOI ?"
        @add="addItem('with_what')"
        @remove="removeItem('with_what', $event)"
        @update="updateItem('with_what', $event)"
      />

      <!-- Row 2: ENTREES / CENTER / SORTIES -->
      <TurtleCell
        color="orange"
        icon="mdi-arrow-right-thick"
        :items="diagram.inputs"
        subtitle="Inputs"
        title="ENTRÉES"
        @add="addItem('inputs')"
        @remove="removeItem('inputs', $event)"
        @update="updateItem('inputs', $event)"
      />

      <div class="flex items-center justify-center bg-gradient-to-br from-purple-100 to-blue-100 rounded-lg p-4 border-4 border-purple-300">
        <div class="text-center">
          <v-icon color="purple" size="48">mdi-timer-sand</v-icon>
          <p class="text-sm font-semibold text-gray-700 mt-2">Transformation</p>
          <p class="text-xs text-gray-500">Valeur Ajoutée</p>
        </div>
      </div>

      <TurtleCell
        color="teal"
        icon="mdi-check-circle"
        :items="diagram.outputs"
        subtitle="Outputs"
        title="SORTIES"
        @add="addItem('outputs')"
        @remove="removeItem('outputs', $event)"
        @update="updateItem('outputs', $event)"
      />

      <!-- Row 3: COMMENT / PILOTAGE / DOCUMENTS -->
      <TurtleCell
        color="indigo"
        icon="mdi-book-open-variant"
        :items="diagram.how"
        subtitle="Méthodes & Procédures"
        title="COMMENT ?"
        @add="addItem('how')"
        @remove="removeItem('how', $event)"
        @update="updateItem('how', $event)"
      />

      <TurtleCell
        color="red"
        icon="mdi-chart-line"
        :items="diagram.piloting"
        subtitle="Indicateurs & Mesures"
        title="PILOTAGE"
        @add="addItem('piloting')"
        @remove="removeItem('piloting', $event)"
        @update="updateItem('piloting', $event)"
      />

      <TurtleCell
        color="cyan"
        icon="mdi-file-document-multiple"
        :items="diagram.documents"
        subtitle="Références Documentaires"
        title="DOCUMENTS"
        @add="addItem('documents')"
        @remove="removeItem('documents', $event)"
        @update="updateItem('documents', $event)"
      />
    </div>

    <!-- Additional Sections -->
    <v-expansion-panels class="mb-4">
      <v-expansion-panel>
        <v-expansion-panel-title>
          <v-icon class="mr-2">mdi-alert-octagon</v-icon>
          Risques & Opportunités
        </v-expansion-panel-title>
        <v-expansion-panel-text>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <h4 class="font-semibold text-red-600 mb-2">Risques</h4>
              <v-chip
                v-for="(risk, index) in diagram.risks"
                :key="index"
                class="mr-2 mb-2"
                closable
                color="red"
                @click:close="removeRisk(index)"
              >
                {{ risk }}
              </v-chip>
              <v-btn
                color="red"
                size="small"
                variant="outlined"
                @click="addRisk"
              >
                + Ajouter Risque
              </v-btn>
            </div>
            <div>
              <h4 class="font-semibold text-green-600 mb-2">Opportunités</h4>
              <v-chip
                v-for="(opp, index) in diagram.opportunities"
                :key="index"
                class="mr-2 mb-2"
                closable
                color="green"
                @click:close="removeOpportunity(index)"
              >
                {{ opp }}
              </v-chip>
              <v-btn
                color="green"
                size="small"
                variant="outlined"
                @click="addOpportunity"
              >
                + Ajouter Opportunité
              </v-btn>
            </div>
          </div>
        </v-expansion-panel-text>
      </v-expansion-panel>

      <v-expansion-panel>
        <v-expansion-panel-title>
          <v-icon class="mr-2">mdi-link-variant</v-icon>
          Interactions avec Autres Processus
        </v-expansion-panel-title>
        <v-expansion-panel-text>
          <div class="space-y-2">
            <div v-for="(interaction, index) in diagram.interactions" :key="index" class="flex items-center gap-2">
              <v-icon>mdi-swap-horizontal</v-icon>
              <span>{{ interaction }}</span>
              <v-btn
                color="red"
                icon="mdi-delete"
                size="x-small"
                variant="text"
                @click="removeInteraction(index)"
              />
            </div>
            <v-btn
              size="small"
              variant="outlined"
              @click="addInteraction"
            >
              + Ajouter Interaction
            </v-btn>
          </div>
        </v-expansion-panel-text>
      </v-expansion-panel>
    </v-expansion-panels>

    <!-- Save Dialog -->
    <v-snackbar
      v-model="showSuccessSnackbar"
      color="success"
      :timeout="3000"
    >
      Diagramme Tortue enregistré avec succès !
    </v-snackbar>
  </div>
</template>

<script setup lang="ts">
  import { onMounted, reactive, ref } from 'vue'
  import TurtleCell from './TurtleCell.vue'

  interface TurtleDiagramData {
    who: string[]
    with_what: string[]
    process: string[]
    inputs: string[]
    outputs: string[]
    how: string[]
    piloting: string[]
    documents: string[]
    risks: string[]
    opportunities: string[]
    interactions: string[]
  }

  const props = defineProps<{
    processId?: number
    process?: any
  }>()

  const emit = defineEmits(['saved', 'updated'])

  const saving = ref(false)
  const showSuccessSnackbar = ref(false)

  const diagram = reactive<TurtleDiagramData>({
    who: ['Responsable Qualité', 'Équipe Production'],
    with_what: ['ERP', 'Machines CNC', 'Outils mesure'],
    process: ['Planification', 'Exécution', 'Contrôle', 'Amélioration'],
    inputs: ['Commandes clients', 'Matières premières', 'Spécifications'],
    outputs: ['Produits conformes', 'Rapports qualité', 'Factures'],
    how: ['Procédure PRD-001', 'ISO 9001:2015', 'Plan qualité'],
    piloting: ['Taux conformité 95%', 'Délai livraison <5j', 'NC <2/mois'],
    documents: ['Manuel Qualité', 'Registre NC', 'Plans contrôle'],
    risks: ['Retard fournisseur', 'Panne machine', 'Erreur spécification'],
    opportunities: ['Automatisation', 'Formation équipe', 'Nouveau marché'],
    interactions: ['Achats → Matières', 'Production → Livraison', 'Qualité → Amélioration'],
  })

  function addItem (section: keyof TurtleDiagramData) {
    const newItem = prompt(`Ajouter un élément à "${getSectionTitle(section)}"`)
    if (newItem && newItem.trim()) {
      diagram[section].push(newItem.trim())
    }
  }

  function removeItem (section: keyof TurtleDiagramData, index: number) {
    diagram[section].splice(index, 1)
  }

  function updateItem (section: keyof TurtleDiagramData, { index, value }: { index: number, value: string }) {
    diagram[section][index] = value
  }

  function addRisk () {
    const risk = prompt('Nouveau risque :')
    if (risk && risk.trim()) {
      diagram.risks.push(risk.trim())
    }
  }

  function removeRisk (index: number) {
    diagram.risks.splice(index, 1)
  }

  function addOpportunity () {
    const opp = prompt('Nouvelle opportunité :')
    if (opp && opp.trim()) {
      diagram.opportunities.push(opp.trim())
    }
  }

  function removeOpportunity (index: number) {
    diagram.opportunities.splice(index, 1)
  }

  function addInteraction () {
    const interaction = prompt('Nouvelle interaction (ex: Processus A → Processus B) :')
    if (interaction && interaction.trim()) {
      diagram.interactions.push(interaction.trim())
    }
  }

  function removeInteraction (index: number) {
    diagram.interactions.splice(index, 1)
  }

  function getSectionTitle (section: keyof TurtleDiagramData): string {
    const titles: Record<keyof TurtleDiagramData, string> = {
      who: 'QUI',
      with_what: 'AVEC QUOI',
      process: 'PROCESSUS',
      inputs: 'ENTRÉES',
      outputs: 'SORTIES',
      how: 'COMMENT',
      piloting: 'PILOTAGE',
      documents: 'DOCUMENTS',
      risks: 'RISQUES',
      opportunities: 'OPPORTUNITÉS',
      interactions: 'INTERACTIONS',
    }
    return titles[section]
  }

  function resetDiagram () {
    if (confirm('Réinitialiser le diagramme ? Toutes les modifications non sauvegardées seront perdues.')) {
      for (const key of Object.keys(diagram)) {
        diagram[key as keyof TurtleDiagramData] = []
      }
    }
  }

  async function saveDiagram () {
    saving.value = true
    try {
      // Simulate API call
      await new Promise(resolve => setTimeout(resolve, 1000))

      // TODO: Implement actual API call
      // await api.post(`/processes/${props.processId}/turtle-diagram`, diagram)

      showSuccessSnackbar.value = true
      emit('saved', diagram)
      emit('updated', diagram)
    } catch (error) {
      console.error('Erreur lors de la sauvegarde du diagramme :', error)
      alert('Erreur lors de la sauvegarde')
    } finally {
      saving.value = false
    }
  }

  onMounted(() => {
    // TODO: Load existing diagram data if processId provided
    if (props.processId) {
    // await loadDiagram(props.processId)
    }
  })
</script>

<style scoped>
.turtle-grid {
  min-height: 600px;
}
</style>
