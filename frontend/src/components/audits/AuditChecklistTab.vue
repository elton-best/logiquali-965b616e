<template>
  <v-card-text class="pa-6">
    <div class="d-flex justify-space-between align-center mb-6">
      <h3 class="text-h6">Checklist d'audit</h3>
      <div class="d-flex gap-2">
        <v-btn color="secondary" size="small" variant="outlined" @click="generateChecklist">
          <v-icon start>mdi-auto-fix</v-icon>
          Générer automatiquement
        </v-btn>
        <v-btn color="primary" size="small" @click="showAddDialog = true">
          <v-icon start>mdi-plus</v-icon>
          Ajouter un item
        </v-btn>
      </div>
    </div>

    <!-- Stats -->
    <v-row class="mb-6">
      <v-col cols="3">
        <v-card color="primary" variant="tonal">
          <v-card-text class="text-center">
            <div class="text-h4 font-weight-bold">{{ stats.total }}</div>
            <div class="text-caption">Total items</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="3">
        <v-card color="success" variant="tonal">
          <v-card-text class="text-center">
            <div class="text-h4 font-weight-bold">{{ stats.conformes }}</div>
            <div class="text-caption">Conformes</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="3">
        <v-card color="error" variant="tonal">
          <v-card-text class="text-center">
            <div class="text-h4 font-weight-bold">{{ stats.nonConformes }}</div>
            <div class="text-caption">Non conformes</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="3">
        <v-card color="info" variant="tonal">
          <v-card-text class="text-center">
            <div class="text-h4 font-weight-bold">{{ stats.taux }}%</div>
            <div class="text-caption">Taux conformité</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Liste Checklist -->
    <v-expansion-panels>
      <v-expansion-panel
        v-for="section in groupedChecklist"
        :key="section.name"
      >
        <v-expansion-panel-title>
          <div class="d-flex align-center justify-space-between flex-grow-1 pr-4">
            <span class="font-weight-medium">{{ section.name }}</span>
            <v-chip size="small" variant="flat">
              {{ section.items.length }} items
            </v-chip>
          </div>
        </v-expansion-panel-title>

        <v-expansion-panel-text>
          <v-list>
            <v-list-item
              v-for="item in section.items"
              :key="item.id"
              class="mb-2"
            >
              <template #prepend>
                <v-checkbox
                  v-model="item.is_conformity"
                  color="success"
                  :false-value="false"
                  :indeterminate="item.is_conformity === null"
                  :true-value="true"
                  @change="updateItem(item)"
                />
              </template>

              <v-list-item-title class="mb-2">
                <span class="font-weight-medium">{{ item.question }}</span>
                <v-chip v-if="item.clause_iso" class="ml-2" size="x-small" variant="outlined">
                  {{ item.clause_iso }}
                </v-chip>
              </v-list-item-title>

              <v-list-item-subtitle v-if="item.expected_evidence" class="mb-2">
                <span class="text-caption">Preuve attendue : {{ item.expected_evidence }}</span>
              </v-list-item-subtitle>

              <div class="mt-2">
                <v-text-field
                  v-model="item.observation"
                  density="compact"
                  hide-details
                  label="Observation"
                  variant="outlined"
                  @blur="updateItem(item)"
                />
              </div>

              <template #append>
                <v-rating
                  v-model="item.rating"
                  density="compact"
                  length="5"
                  size="small"
                  @update:model-value="updateItem(item)"
                />
              </template>
            </v-list-item>
          </v-list>
        </v-expansion-panel-text>
      </v-expansion-panel>
    </v-expansion-panels>

    <!-- Empty state -->
    <div v-if="!checklistItems || checklistItems.length === 0" class="text-center pa-12">
      <v-icon color="grey-lighten-1" size="64">mdi-clipboard-list</v-icon>
      <h3 class="text-h6 mt-4 mb-2">Aucun item de checklist</h3>
      <p class="text-medium-emphasis mb-4">Générez une checklist automatique ou ajoutez des items manuellement</p>
      <v-btn color="primary" @click="generateChecklist">
        <v-icon start>mdi-auto-fix</v-icon>
        Générer checklist
      </v-btn>
    </div>

    <!-- Dialog génération -->
    <v-dialog v-model="showGenerateDialog" max-width="600">
      <v-card>
        <v-card-title>Générer une checklist automatique</v-card-title>
        <v-card-text>
          <v-select
            v-model="selectedClauses"
            chips
            :items="isoClauses"
            label="Clauses ISO à inclure"
            multiple
            variant="outlined"
          />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn @click="showGenerateDialog = false">Annuler</v-btn>
          <v-btn color="primary" :loading="generating" @click="confirmGenerate">Générer</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-card-text>
</template>

<script setup lang="ts">
  import { computed, ref } from 'vue'
  import { useSnackbar } from '@/composables/useSnackbar'
  import { auditService } from '@/services/auditService'

  interface ChecklistItem {
    id: number
    section?: string
    is_conformity: boolean | null
    question: string
    clause_iso?: string
    expected_evidence?: string
    observation?: string
    rating?: number
  }

  const props = defineProps<{
    auditId: number
    checklistItems?: ChecklistItem[]
  }>()

  const emit = defineEmits<{
    update: []
  }>()

  const { showSuccess, showError } = useSnackbar()

  const showGenerateDialog = ref(false)
  const showAddDialog = ref(false)
  const generating = ref(false)
  const selectedClauses = ref<string[]>([])

  const isoClauses = [
    { title: '4. Contexte de l\'organisme', value: '4' },
    { title: '5. Leadership', value: '5' },
    { title: '6. Planification', value: '6' },
    { title: '7. Support', value: '7' },
    { title: '8. Réalisation des activités opérationnelles', value: '8' },
    { title: '9. Évaluation des performances', value: '9' },
    { title: '10. Amélioration', value: '10' },
  ]

  const stats = computed(() => {
    const total = props.checklistItems?.length || 0
    const conformes = props.checklistItems?.filter(i => i.is_conformity === true).length || 0
    const nonConformes = props.checklistItems?.filter(i => i.is_conformity === false).length || 0
    const taux = total > 0 ? Math.round((conformes / total) * 100) : 0

    return { total, conformes, nonConformes, taux }
  })

  const groupedChecklist = computed((): Array<{ name: string, items: ChecklistItem[] }> => {
    if (!props.checklistItems) return []

    const groups = props.checklistItems.reduce((acc, item) => {
      const section = item.section || 'Autre'
      if (!acc[section]) {
        acc[section] = []
      }
      acc[section].push(item)
      return acc
    }, {} as Record<string, ChecklistItem[]>)

    return Object.entries(groups).map(([name, items]) => ({
      name,
      items,
    }))
  })

  function generateChecklist () {
    showGenerateDialog.value = true
  }

  async function confirmGenerate () {
    generating.value = true
    try {
      await auditService.generateChecklist(props.auditId, selectedClauses.value)
      showSuccess('Checklist générée')
      showGenerateDialog.value = false
      emit('update')
    } catch {
      showError('Erreur lors de la génération')
    } finally {
      generating.value = false
    }
  }

  async function updateItem (_item: any) {
    // Sauvegarder l'item
    emit('update')
  }
</script>
