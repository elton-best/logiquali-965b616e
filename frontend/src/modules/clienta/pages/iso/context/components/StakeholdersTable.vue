<template>
  <DataTable
    class="stakeholders-table"
    empty-action-label="Nouvelle partie"
    empty-description="Commencez par identifier vos parties intéressées."
    empty-icon="mdi-account-group"
    empty-title="Aucune partie prenante"
    :headers="headers"
    :items="stakeholders"
    :items-per-page="itemsPerPage"
    @create="emit('add')"
  >
    <template #item.name="{ item }">
      <div class="row-clickable d-flex align-center gap-3" @click="emit('edit', item)">
        <v-avatar :color="getTypeColor(item.type)" size="36">
          <v-icon color="white" size="18">{{ getTypeIcon(item.type) }}</v-icon>
        </v-avatar>
        <span class="font-weight-bold">{{ item.name }}</span>
      </div>
    </template>
    <template #item.type="{ item }">
      <div class="row-clickable" @click="emit('edit', item)">
        <v-chip :color="getTypeColor(item.type)" size="small" variant="tonal">
          {{ typeLabel(item.type) }}
        </v-chip>
      </div>
    </template>
    <template #item.relevance_degree="{ item }">
      <div class="row-clickable" @click="emit('edit', item)">
        <v-chip :color="getInfluenceColor(item.relevance_degree)" size="small" variant="flat">
          {{ influenceLabel(item.relevance_degree) }}
        </v-chip>
      </div>
    </template>
    <template #item.actions_summary="{ item }">
      <div class="row-clickable" @click="emit('edit', item)">
        <div v-if="getStakeholderActions(item).length > 0">
          <div class="text-caption font-weight-bold mb-1">
            {{ getStakeholderActions(item).length }} actions
          </div>
          <div v-for="action in getStakeholderActions(item).slice(0, 1)" :key="action.id">
            <div class="text-body-2 mb-1">{{ truncate(action.description, 40) }}</div>
            <div class="d-flex align-center gap-2">
              <v-chip v-if="action.responsible" color="primary" size="x-small" variant="tonal">
                {{ action.responsible }}
              </v-chip>
              <v-chip v-if="action.deadline" :color="getDeadlineColor(action.deadline)" size="x-small" variant="flat">
                {{ formatDate(action.deadline) }}
              </v-chip>
            </div>
          </div>
          <div v-if="getStakeholderActions(item).length > 1" class="text-caption text-medium-emphasis mt-1">
            +{{ getStakeholderActions(item).length - 1 }} autres
          </div>
        </div>
        <div v-else class="text-body-2">{{ truncate(item.needs_expectations, 80) }}</div>
      </div>
    </template>
    <template #item.actions="{ item }">
      <div class="d-flex justify-end">
        <v-btn
          :color="getTypeColor(item.type)"
          icon="mdi-pencil"
          size="small"
          variant="tonal"
          @click.stop="emit('edit', item)"
        />
      </div>
    </template>
  </DataTable>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import DataTable from '@/modules/clienta/components/DataTable.vue'

  defineProps({
    headers: {
      type: Array as PropType<Array<Record<string, any>>>,
      required: true,
    },
    stakeholders: {
      type: Array as PropType<any[]>,
      required: true,
    },
    itemsPerPage: {
      type: Number,
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
.row-clickable {
  cursor: pointer;
}

:deep(.app-table table) {
  table-layout: fixed;
}

:deep(.app-table th),
:deep(.app-table td) {
  vertical-align: middle;
}

:deep(.app-table th) {
  white-space: nowrap;
}

:deep(.app-table th:nth-child(1)),
:deep(.app-table td:nth-child(1)) {
  width: 20%;
}

:deep(.app-table th:nth-child(2)),
:deep(.app-table td:nth-child(2)) {
  width: 12%;
}

:deep(.app-table th:nth-child(3)),
:deep(.app-table td:nth-child(3)) {
  width: 12%;
}

:deep(.app-table th:nth-child(4)),
:deep(.app-table td:nth-child(4)) {
  width: 40%;
}

:deep(.app-table th:nth-child(5)),
:deep(.app-table td:nth-child(5)) {
  width: 8%;
}
</style>
