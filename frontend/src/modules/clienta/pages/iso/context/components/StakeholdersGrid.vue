<template>
  <v-row>
    <v-col
      v-for="stakeholder in stakeholders"
      :key="stakeholder.id"
      cols="12"
      lg="4"
      md="6"
    >
      <v-card
        class="stakeholder-card"
        elevation="0"
        rounded="xl"
        :style="`border: 1px solid ${getTypeColor(stakeholder.type)}26`"
        @click="emit('edit', stakeholder)"
      >
        <div class="card-header pa-3" :style="`background: linear-gradient(135deg, ${getTypeColor(stakeholder.type)}12 0%, ${getTypeColor(stakeholder.type)}06 100%)`">
          <div class="d-flex align-center justify-space-between">
            <v-avatar class="elevation-2" :color="getTypeColor(stakeholder.type)" size="44">
              <v-icon color="white" size="24">{{ getTypeIcon(stakeholder.type) }}</v-icon>
            </v-avatar>
            <v-chip
              class="font-weight-bold"
              :color="getInfluenceColor(stakeholder.relevance_degree)"
              size="small"
              variant="flat"
            >
              {{ influenceLabel(stakeholder.relevance_degree) }}
            </v-chip>
          </div>
        </div>

        <v-card-text class="pa-3">
          <h3 class="text-subtitle-1 font-weight-bold mb-2">{{ stakeholder.name }}</h3>
          <v-chip
            class="mb-3"
            :color="getTypeColor(stakeholder.type)"
            size="small"
            variant="tonal"
          >
            {{ typeLabel(stakeholder.type) }}
          </v-chip>

          <div class="actions-preview mb-3">
            <div v-if="getStakeholderActions(stakeholder).length > 0" class="mb-2">
              <div class="d-flex align-center mb-2">
                <v-icon class="mr-1" color="success" size="16">mdi-check-circle</v-icon>
                <span class="text-caption font-weight-bold">{{ getStakeholderActions(stakeholder).length }} actions</span>
              </div>
              <div v-for="action in getStakeholderActions(stakeholder).slice(0, 2)" :key="action.id" class="mb-1">
                <v-card class="pa-2" rounded="lg" style="border-width: 1px;" variant="outlined">
                  <div class="text-caption">{{ truncate(action.description, 60) }}</div>
                  <div class="d-flex align-center gap-2 mt-1">
                    <div v-if="action.responsible" class="d-flex align-center">
                      <v-icon class="mr-1" color="primary" size="12">mdi-account</v-icon>
                      <span class="text-caption">{{ action.responsible }}</span>
                    </div>
                    <div v-if="action.deadline" class="d-flex align-center">
                      <v-icon class="mr-1" :color="getDeadlineColor(action.deadline)" size="12">mdi-calendar</v-icon>
                      <span class="text-caption" :style="`color: ${getDeadlineColor(action.deadline)}`">
                        {{ formatDate(action.deadline) }}
                      </span>
                    </div>
                  </div>
                </v-card>
              </div>
              <div v-if="getStakeholderActions(stakeholder).length > 2" class="text-caption text-medium-emphasis">
                +{{ getStakeholderActions(stakeholder).length - 2 }} autres actions
              </div>
            </div>
            <div v-else class="text-body-2 text-medium-emphasis">
              {{ truncate(stakeholder.needs_expectations, 100) }}
            </div>
          </div>

          <v-divider class="my-3" />

          <div class="d-flex align-center justify-space-between">
            <v-chip
              class="font-weight-bold"
              :color="getInfluenceColor(stakeholder.relevance_degree)"
              size="small"
              variant="flat"
            >
              {{ influenceLabel(stakeholder.relevance_degree) }}
            </v-chip>
            <v-btn
              :color="getTypeColor(stakeholder.type)"
              icon="mdi-pencil"
              size="small"
              variant="tonal"
              @click.stop="emit('edit', stakeholder)"
            />
          </div>
        </v-card-text>
      </v-card>
    </v-col>

    <v-col v-if="stakeholders.length === 0" cols="12">
      <EmptyState
        action-icon="mdi-plus"
        action-label="Ajouter la première partie"
        description="Commencez par identifier vos parties intéressées."
        icon="mdi-account-group"
        title="Aucun résultat"
        @action="emit('add')"
      />
    </v-col>
  </v-row>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import EmptyState from '@/modules/clienta/components/EmptyState.vue'

  defineProps({
    stakeholders: {
      type: Array as PropType<any[]>,
      required: true,
    },
    getTypeColor: {
      type: Function as PropType<(type: string) => string>,
      required: true,
    },
    getTypeIcon: {
      type: Function as PropType<(type: string) => string>,
      required: true,
    },
    getInfluenceColor: {
      type: Function as PropType<(value: string) => string>,
      required: true,
    },
    influenceLabel: {
      type: Function as PropType<(value: string) => string>,
      required: true,
    },
    typeLabel: {
      type: Function as PropType<(value: string) => string>,
      required: true,
    },
    getStakeholderActions: {
      type: Function as PropType<(stakeholder: any) => any[]>,
      required: true,
    },
    truncate: {
      type: Function as PropType<(value: string, max: number) => string>,
      required: true,
    },
    getDeadlineColor: {
      type: Function as PropType<(value: string) => string>,
      required: true,
    },
    formatDate: {
      type: Function as PropType<(value: string) => string>,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'edit', stakeholder: any): void
    (event: 'add'): void
  }>()
</script>

<style scoped>
.stakeholder-card {
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
}

.stakeholder-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
}

.card-header {
  transition: all 0.3s ease;
}

.stakeholder-card:hover .card-header {
  transform: scale(1.01);
}

.actions-preview {
  min-height: 72px;
}
</style>
