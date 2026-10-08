<template>
  <v-card elevation="2" rounded="lg">
    <v-card-title class="d-flex align-center justify-space-between">
      <span class="text-h6">Registre des fiches NC</span>
      <v-chip color="primary" variant="tonal">{{ items.length }} résultat(s)</v-chip>
    </v-card-title>

    <DataTable :headers="headers" :items="items" :items-per-page="10">
      <template #item.reference="{ item }">
        <button class="link-cell" :class="{ active: selectedId === item.id }" @click="emit('select', item.id)">
          {{ item.reference }}
        </button>
      </template>

      <template #item.type_constat="{ item }">
        <v-chip size="small" variant="tonal">{{ item.type_constat }}</v-chip>
      </template>

      <template #item.type_nc="{ item }">
        <v-chip
          :color="item.type_nc === 'N-C majeure' ? 'warning' : 'info'"
          size="small"
          variant="flat"
        >
          {{ item.type_nc }}
        </v-chip>
      </template>

      <template #item.status="{ item }">
        <v-chip :color="statusColor(item.status)" size="small" variant="flat">
          {{ statusLabel(item.status) }}
        </v-chip>
      </template>

      <template #item.deadline="{ item }">
        <span :class="{ 'text-error font-weight-bold': item.status !== 'closed' && isOverdue(item.deadline) }">
          {{ item.deadline }}
        </span>
      </template>

      <template #item.actions="{ item }">
        <div class="d-flex justify-end ga-1">
          <v-btn
            v-if="canReadNc"
            icon="mdi-eye-outline"
            size="small"
            variant="text"
            @click="emit('select', item.id)"
          />
          <v-btn
            v-if="canUpdateNc"
            icon="mdi-pencil-outline"
            size="small"
            variant="text"
            @click="emit('edit', item.id)"
          />
        </div>
      </template>
    </DataTable>
  </v-card>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import DataTable from '@/modules/clienta/components/DataTable.vue'

  defineProps({
    headers: {
      type: Array as PropType<Array<Record<string, any>>>,
      required: true,
    },
    items: {
      type: Array as PropType<any[]>,
      required: true,
    },
    selectedId: {
      type: Number as PropType<number | null>,
      required: true,
    },
    canReadNc: {
      type: Boolean,
      required: true,
    },
    canUpdateNc: {
      type: Boolean,
      required: true,
    },
    statusColor: {
      type: Function as PropType<(status: string) => string>,
      required: true,
    },
    statusLabel: {
      type: Function as PropType<(status: string) => string>,
      required: true,
    },
    isOverdue: {
      type: Function as PropType<(deadline: string) => boolean>,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'select', id: number): void
    (event: 'edit', id: number): void
  }>()
</script>

<style scoped>
.link-cell {
  color: rgb(var(--v-theme-primary));
  font-weight: 700;
  cursor: pointer;
  text-decoration: underline;
}

.link-cell.active {
  text-decoration-thickness: 3px;
}
</style>
