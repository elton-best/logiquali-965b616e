<template>
  <v-card elevation="2" rounded="lg">
    <v-table>
      <thead>
        <tr>
          <th>N°</th>
          <th>Processus</th>
          <th>Objectif</th>
          <th>Axe</th>
          <th>Indicateur</th>
          <th>Fréquence</th>
          <th>Taux d'atteinte</th>
          <th>Nb actions</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="(obj, idx) in objectives"
          :key="obj.id"
          class="list-row"
          @click="emit('open', obj)"
        >
          <td>{{ idx + 1 }}</td>
          <td>
            <v-chip color="primary" size="small" variant="tonal">
              {{ obj.processus }}
            </v-chip>
          </td>
          <td class="font-weight-medium">{{ obj.titre }}</td>
          <td>
            <v-chip v-if="obj.axesStrategiques.length > 0" :color="getAxeColor(obj.axeStrategique)" size="small" variant="flat">
              {{ axisDisplayLabel(obj.axeStrategique) }}
            </v-chip>
          </td>
          <td>{{ obj.indicateur }}</td>
          <td>
            <v-chip size="small" variant="outlined">{{ obj.frequence }}</v-chip>
          </td>
          <td>
            <div class="d-flex align-center" style="gap: 8px;">
              <v-progress-linear
                :color="getPerformanceColor(obj.tauxAtteinte)"
                height="8"
                :model-value="obj.tauxAtteinte"
                rounded
                style="width: 100px;"
              />
              <span class="font-weight-bold" :style="{ color: getPerformanceColor(obj.tauxAtteinte) }">
                {{ obj.tauxAtteinte }}%
              </span>
            </div>
          </td>
          <td>{{ obj.actions.length }} action(s)</td>
          <td class="actions-cell">
            <v-btn
              icon="mdi-pencil"
              size="small"
              variant="text"
              @click.stop="emit('edit', obj)"
            />
            <v-btn
              color="error"
              icon="mdi-delete"
              size="small"
              variant="text"
              @click.stop="emit('delete', obj.id)"
            />
          </td>
        </tr>
      </tbody>
    </v-table>
  </v-card>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'

  defineProps({
    objectives: {
      type: Array as PropType<Array<Record<string, any>>>,
      required: true,
    },
    getAxeColor: {
      type: Function as PropType<(axe: string) => string>,
      required: true,
    },
    axisDisplayLabel: {
      type: Function as PropType<(axe: string) => string>,
      required: true,
    },
    getPerformanceColor: {
      type: Function as PropType<(value: number) => string>,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'open', objective: any): void
    (event: 'edit', objective: any): void
    (event: 'delete', id: number): void
  }>()
</script>

<style scoped>
.list-row {
  cursor: pointer;
  transition: background-color 0.2s;
}

.list-row:hover {
  background-color: rgba(0, 0, 0, 0.02);
}

.actions-cell {
  white-space: nowrap;
}
</style>
