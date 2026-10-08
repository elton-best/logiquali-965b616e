<template>
  <v-card>
    <v-card-title class="d-flex justify-space-between align-center">
      <span>Matrice de Compétences</span>
      <div class="d-flex gap-2">
        <v-select
          v-model="selectedJobDescription"
          density="compact"
          item-title="title"
          item-value="id"
          :items="userStore.jobDescriptions"
          label="Poste"
          style="min-width: 200px"
          @update:model-value="loadMatrix"
        />
        <v-btn color="primary" :disabled="!selectedUser" @click="generateTrainingPlan">
          Plan de formation
        </v-btn>
      </div>
    </v-card-title>

    <v-data-table
      class="competence-matrix"
      :headers="headers"
      item-key="user_id"
      :items="matrixData"
      :loading="competenceStore.loading"
    >
      <template #item.user="{ item }">
        <div class="d-flex align-center">
          <v-avatar class="mr-2" size="32">
            {{ item.user.name.charAt(0) }}
          </v-avatar>
          <span>{{ item.user.name }}</span>
        </div>
      </template>

      <template v-for="competence in competences" :key="competence.id" #[`item.competence_${competence.id}`]="{ item }">
        <CompetenceCell
          :required-competence="competence"
          :user-competence="getUserCompetence(item, competence.id)"
          @click="openGapAnalysis(item.user, competence)"
        />
      </template>

      <template #item.completion="{ item }">
        <div class="d-flex align-center">
          <v-progress-circular
            :color="getCompletionColor(item.completion_rate)"
            :model-value="item.completion_rate"
            size="40"
          >
            {{ Math.round(item.completion_rate) }}%
          </v-progress-circular>
        </div>
      </template>

      <template #item.actions="{ item }">
        <v-btn icon size="small" @click="viewGapAnalysis(item.user)">
          <v-icon>mdi-chart-line</v-icon>
        </v-btn>
      </template>
    </v-data-table>

    <GapAnalysisDialog
      v-model="showGapAnalysis"
      :competence="selectedCompetence"
      :user="selectedUser"
    />
  </v-card>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'
  import { useCompetenceStore } from '@/stores/competenceStore'
  import { useUserStore } from '@/stores/userStore'
  import CompetenceCell from './CompetenceCell.vue'
  import GapAnalysisDialog from './GapAnalysisDialog.vue'

  const competenceStore = useCompetenceStore()
  const userStore = useUserStore()

  const selectedJobDescription = ref(null)
  const showGapAnalysis = ref(false)
  const selectedUser = ref(null)
  const selectedCompetence = ref(null)

  const headers = computed(() => [
    { title: 'Employé', key: 'user', width: 200 },
    ...(competenceStore.matrixData?.competences || []).map(comp => ({
      title: comp.name,
      key: `competence_${comp.id}`,
      width: 120,
      sortable: false,
    })),
    { title: 'Complétude', key: 'completion', width: 100 },
    { title: 'Actions', key: 'actions', width: 80, sortable: false },
  ])

  const matrixData = computed<any[]>(() => competenceStore.matrixData?.users || [])
  const competences = computed<any[]>(() => competenceStore.matrixData?.competences || [])

  async function loadMatrix () {
    await competenceStore.fetchMatrix(selectedJobDescription.value)
  }

  function getUserCompetence (userItem: any, competenceId: number) {
    return userItem.competences.find(c => c.competence_requise_id === competenceId)
  }

  function getCompletionColor (rate: number) {
    if (rate >= 80) return 'success'
    if (rate >= 60) return 'warning'
    return 'error'
  }

  function openGapAnalysis (user: any, competence: any) {
    selectedUser.value = user
    selectedCompetence.value = competence
    showGapAnalysis.value = true
  }

  function viewGapAnalysis (user: any) {
    selectedUser.value = user
    selectedCompetence.value = null
    showGapAnalysis.value = true
  }

  async function generateTrainingPlan () {
    if (!selectedUser.value) return

    try {
      const plan = await competenceStore.generateTrainingPlan(selectedUser.value.id)
      console.log('Training plan generated:', plan)
    } catch (error) {
      console.error('Error generating training plan:', error)
    }
  }

  onMounted(async () => {
    await Promise.all([
      userStore.fetchJobDescriptions(),
      loadMatrix(),
    ])
  })
</script>

<style scoped>
.competence-matrix :deep(.v-data-table__td) {
  padding: 8px !important;
}
</style>
