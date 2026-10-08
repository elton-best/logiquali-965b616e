<template>
  <div>
    <div class="d-flex justify-space-between align-center mb-4 flex-wrap ga-3 section-header-sticky">
      <div>
        <div class="text-h6 font-weight-bold">Planification des projets opérationnels</div>
        <div class="text-body-2 text-medium-emphasis">
          Architecture hiérarchique: Projet → Activités → Tâches, avec responsables par niveau.
        </div>
      </div>
      <v-btn color="primary" prepend-icon="mdi-plus" @click="emit('create-project')">
        Nouveau projet
      </v-btn>
    </div>
    <div class="d-flex justify-end mb-4">
      <v-btn-toggle
        v-model="projectsViewModeModel"
        color="primary"
        density="comfortable"
        mandatory
        variant="outlined"
      >
        <v-btn value="list">
          <v-icon class="mr-1" size="16">mdi-format-list-bulleted</v-icon>
          Liste
        </v-btn>
        <v-btn value="grid">
          <v-icon class="mr-1" size="16">mdi-view-grid-outline</v-icon>
          Grille
        </v-btn>
      </v-btn-toggle>
    </div>

    <v-row class="mb-4" dense>
      <v-col cols="12" md="3">
        <v-card class="project-kpi project-kpi--all" rounded="xl">
          <v-card-text>
            <div class="text-caption text-medium-emphasis">Total projets</div>
            <div class="text-h5 font-weight-bold">{{ projects.length }}</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3">
        <v-card class="project-kpi project-kpi--planned" rounded="xl">
          <v-card-text>
            <div class="text-caption text-medium-emphasis">Planifiés</div>
            <div class="text-h5 font-weight-bold">{{ projectCountByStatus('planned') }}</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3">
        <v-card class="project-kpi project-kpi--progress" rounded="xl">
          <v-card-text>
            <div class="text-caption text-medium-emphasis">En cours</div>
            <div class="text-h5 font-weight-bold">{{ projectCountByStatus('in_progress') }}</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3">
        <v-card class="project-kpi project-kpi--done" rounded="xl">
          <v-card-text>
            <div class="text-caption text-medium-emphasis">Terminés</div>
            <div class="text-h5 font-weight-bold">{{ projectCountByStatus('completed') }}</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-expansion-panels v-if="projects.length > 0 && projectsViewModeModel === 'list'" class="project-panels" variant="accordion">
      <v-expansion-panel v-for="project in projects" :key="project.id" rounded="lg">
        <v-expansion-panel-title>
          <div class="w-100 d-flex justify-space-between align-center flex-wrap ga-2">
            <div class="d-flex flex-wrap ga-2 align-center">
              <span class="font-weight-bold">{{ project.title }}</span>
              <v-chip size="x-small" variant="tonal">{{ formatStatusLabel(project.status) }}</v-chip>
              <v-chip :color="priorityColor(project.priority)" size="x-small" variant="flat">{{ formatPriorityLabel(project.priority) }}</v-chip>
              <v-chip size="x-small" variant="outlined">Chef: {{ project.project_manager?.name || 'Non assigné' }}</v-chip>
            </div>
            <div class="text-caption text-medium-emphasis">
              {{ project.activities?.length || 0 }} activité(s)
            </div>
          </div>
        </v-expansion-panel-title>
        <v-expansion-panel-text>
          <div class="d-flex justify-space-between align-center mb-3 flex-wrap ga-2">
            <div class="text-body-2 text-medium-emphasis">{{ project.description || 'Aucune description' }}</div>
            <div class="d-flex ga-2">
              <v-btn size="small" variant="outlined" @click="emit('edit-project', project)">Modifier</v-btn>
              <v-btn color="error" size="small" variant="text" @click="emit('delete-project', project.id)">Supprimer</v-btn>
            </div>
          </div>

          <v-divider class="mb-3" />

          <div class="d-flex justify-space-between align-center mb-2">
            <div class="text-subtitle-2">Activités du projet</div>
            <v-btn color="primary" size="small" variant="tonal" @click="emit('create-activity', project.id)">
              Ajouter activité
            </v-btn>
          </div>

          <v-alert v-if="!project.activities || project.activities.length === 0" density="compact" type="info" variant="tonal">
            Aucune activité pour ce projet.
          </v-alert>

          <v-expansion-panels v-else density="compact" variant="inset">
            <v-expansion-panel v-for="activity in project.activities" :key="activity.id">
              <v-expansion-panel-title>
                <div class="w-100 d-flex justify-space-between align-center flex-wrap ga-2">
                  <div class="d-flex flex-wrap ga-2 align-center">
                    <span>{{ activity.title }}</span>
                    <v-chip size="x-small" variant="tonal">{{ formatStatusLabel(activity.status) }}</v-chip>
                    <v-chip :color="priorityColor(activity.priority)" size="x-small" variant="flat">{{ formatPriorityLabel(activity.priority) }}</v-chip>
                    <v-chip size="x-small" variant="outlined">Resp: {{ activity.responsible_user?.name || 'Non assigné' }}</v-chip>
                  </div>
                  <div class="text-caption text-medium-emphasis">{{ activity.tasks?.length || 0 }} tâche(s)</div>
                </div>
              </v-expansion-panel-title>
              <v-expansion-panel-text>
                <div class="d-flex justify-space-between align-center mb-2 flex-wrap ga-2">
                  <div class="text-body-2 text-medium-emphasis">{{ activity.description || 'Aucune description' }}</div>
                  <div class="d-flex ga-2">
                    <v-btn size="x-small" variant="tonal" @click="emit('track-activity', activity)">Suivi</v-btn>
                    <v-btn size="x-small" variant="outlined" @click="emit('edit-activity', activity)">Modifier</v-btn>
                    <v-btn color="error" size="x-small" variant="text" @click="emit('delete-activity', activity.id)">Supprimer</v-btn>
                  </div>
                </div>

                <div class="d-flex justify-space-between align-center mb-2">
                  <div class="text-caption">Tâches associées</div>
                  <v-btn color="primary" size="x-small" variant="tonal" @click="emit('create-task', activity.id)">
                    Ajouter tâche
                  </v-btn>
                </div>

                <v-list v-if="activity.tasks && activity.tasks.length > 0" density="compact">
                  <v-list-item v-for="task in activity.tasks" :key="task.id" class="task-item">
                    <template #prepend>
                      <v-icon size="16">mdi-checkbox-marked-circle-outline</v-icon>
                    </template>
                    <v-list-item-title>{{ task.title }}</v-list-item-title>
                    <v-list-item-subtitle>
                      {{ formatStatusLabel(task.status) }} · {{ formatPriorityLabel(task.priority) }} · Resp: {{ task.responsible_user?.name || 'Non assigné' }}
                    </v-list-item-subtitle>
                    <template #append>
                      <div class="d-flex ga-1">
                        <v-btn icon="mdi-pencil" size="x-small" variant="text" @click="emit('edit-task', task)" />
                        <v-btn icon="mdi-chart-line" size="x-small" variant="text" @click="emit('track-task', task)" />
                        <v-btn
                          color="error"
                          icon="mdi-delete"
                          size="x-small"
                          variant="text"
                          @click="emit('delete-task', task.id)"
                        />
                      </div>
                    </template>
                  </v-list-item>
                </v-list>
                <v-alert v-else density="compact" type="info" variant="tonal">
                  Aucune tâche pour cette activité.
                </v-alert>
              </v-expansion-panel-text>
            </v-expansion-panel>
          </v-expansion-panels>
        </v-expansion-panel-text>
      </v-expansion-panel>
    </v-expansion-panels>

    <v-row v-else-if="projects.length > 0 && projectsViewModeModel === 'grid'" dense>
      <v-col
        v-for="project in projects"
        :key="project.id"
        cols="12"
        lg="4"
        md="6"
      >
        <v-card class="project-grid-card h-100" rounded="xl" variant="outlined">
          <v-card-text>
            <div class="d-flex justify-space-between align-center mb-2">
              <v-chip size="x-small" variant="tonal">{{ formatStatusLabel(project.status) }}</v-chip>
              <v-chip :color="priorityColor(project.priority)" size="x-small" variant="flat">{{ formatPriorityLabel(project.priority) }}</v-chip>
            </div>
            <div class="text-subtitle-1 font-weight-bold mb-1">{{ project.title }}</div>
            <div class="text-caption text-medium-emphasis mb-3">
              {{ project.description || 'Aucune description' }}
            </div>
            <div class="d-flex flex-wrap ga-2 mb-3">
              <v-chip size="x-small" variant="outlined">Chef: {{ project.project_manager?.name || 'Non assigné' }}</v-chip>
              <v-chip size="x-small" variant="outlined">{{ project.activities?.length || 0 }} activité(s)</v-chip>
              <v-chip size="x-small" variant="outlined">
                {{ (project.activities || []).reduce((total, activity) => total + (activity.tasks?.length || 0), 0) }} tâche(s)
              </v-chip>
            </div>
            <div class="d-flex ga-2">
              <v-btn size="small" variant="outlined" @click="emit('edit-project', project)">Modifier</v-btn>
              <v-btn color="error" size="small" variant="text" @click="emit('delete-project', project.id)">Supprimer</v-btn>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-alert v-else type="info" variant="tonal">Aucun projet opérationnel.</v-alert>
  </div>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import { computed } from 'vue'

  const props = defineProps({
    projects: {
      type: Array as PropType<Array<Record<string, any>>>,
      required: true,
    },
    projectsViewMode: {
      type: String as PropType<'list' | 'grid'>,
      required: true,
    },
    formatStatusLabel: {
      type: Function as PropType<(value: string | null | undefined) => string>,
      required: true,
    },
    formatPriorityLabel: {
      type: Function as PropType<(value: string | null | undefined) => string>,
      required: true,
    },
    priorityColor: {
      type: Function as PropType<(value: string | null | undefined) => string>,
      required: true,
    },
    projectCountByStatus: {
      type: Function as PropType<(value: string) => number>,
      required: true,
    },
  })

  const emit = defineEmits<{
    (e: 'update:projectsViewMode', value: 'list' | 'grid'): void
    (e: 'create-project'): void
    (e: 'edit-project', project: any): void
    (e: 'delete-project', id: number): void
    (e: 'create-activity', projectId: number): void
    (e: 'edit-activity', activity: any): void
    (e: 'delete-activity', id: number): void
    (e: 'create-task', activityId: number): void
    (e: 'edit-task', task: any): void
    (e: 'delete-task', id: number): void
    (e: 'track-activity', activity: any): void
    (e: 'track-task', task: any): void
  }>()

  const projectsViewModeModel = computed({
    get: () => props.projectsViewMode,
    set: (value: 'list' | 'grid') => emit('update:projectsViewMode', value),
  })
</script>

<style scoped>
.project-kpi {
  border: 1px solid rgba(15, 23, 42, 0.1);
}

.project-kpi--all {
  background: linear-gradient(120deg, rgba(10, 132, 255, 0.1), rgba(10, 132, 255, 0.02));
}

.project-kpi--planned {
  background: linear-gradient(120deg, rgba(255, 193, 7, 0.12), rgba(255, 193, 7, 0.02));
}

.project-kpi--progress {
  background: linear-gradient(120deg, rgba(255, 87, 34, 0.12), rgba(255, 87, 34, 0.02));
}

.project-kpi--done {
  background: linear-gradient(120deg, rgba(76, 175, 80, 0.12), rgba(76, 175, 80, 0.02));
}

.project-panels :deep(.v-expansion-panel) {
  border: 1px solid rgba(15, 23, 42, 0.08);
}

.task-item {
  border-radius: 10px;
  transition: background-color 0.2s ease;
}

.task-item:hover {
  background: rgba(10, 132, 255, 0.06);
}

.project-grid-card {
  border-color: rgba(15, 23, 42, 0.1);
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.project-grid-card:hover {
  border-color: rgba(68, 113, 196, 0.45);
  box-shadow: 0 12px 28px rgba(68, 113, 196, 0.14);
  transform: translateY(-2px);
}
</style>
