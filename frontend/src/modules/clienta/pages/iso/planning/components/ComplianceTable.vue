<template>
  <v-card elevation="2" rounded="lg">
    <v-table>
      <thead>
        <tr>
          <th>Réf</th>
          <th>Volet / Aspect</th>
          <th>Norme(s)</th>
          <th>Texte réglementaire</th>
          <th>Conformité</th>
          <th>Nb actions</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in items" :key="item.id">
          <td>{{ item.ref || '-' }}</td>
          <td>{{ item.aspect?.name || '-' }}</td>
          <td>
            <div class="d-flex flex-wrap ga-1">
              <v-chip
                v-for="norm in (item.aspect?.norms || [])"
                :key="`norm-${item.id}-${norm.id}`"
                size="x-small"
                variant="tonal"
              >
                {{ norm.code }}
              </v-chip>
            </div>
          </td>
          <td class="text-wrap-cell">{{ item.regulatory_reference }}</td>
          <td>
            <v-chip :color="complianceColor(item.compliance_status)" size="small" variant="tonal">
              {{ complianceLabel(item.compliance_status) }}
            </v-chip>
          </td>
          <td>{{ item.actions?.length || 0 }}</td>
          <td class="text-no-wrap">
            <v-btn icon="mdi-pencil" size="small" variant="text" @click="emit('edit', item)" />
            <v-btn
              color="error"
              icon="mdi-delete"
              size="small"
              variant="text"
              @click="emit('remove', item)"
            />
          </td>
        </tr>
      </tbody>
    </v-table>
    <v-card-text v-if="items.length === 0" class="text-center text-medium-emphasis py-8">
      {{ emptyStateMessage }}
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'

  defineProps({
    items: {
      type: Array as PropType<Array<Record<string, any>>>,
      required: true,
    },
    complianceLabel: {
      type: Function as PropType<(value: string) => string>,
      required: true,
    },
    complianceColor: {
      type: Function as PropType<(value: string) => string>,
      required: true,
    },
    emptyStateMessage: {
      type: String,
      required: true,
    },
  })

  const emit = defineEmits<{
    (e: 'edit', item: any): void
    (e: 'remove', item: any): void
  }>()

  void emit
</script>

<style scoped>
.text-wrap-cell {
  max-width: 360px;
  white-space: normal;
  line-height: 1.3;
}
</style>
