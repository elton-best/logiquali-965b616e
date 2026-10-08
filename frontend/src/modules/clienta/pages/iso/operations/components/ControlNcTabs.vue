<template>
  <v-card class="control-nc-shell" rounded="xl">
    <v-tabs v-model="tabModel" class="modern-tabs" color="primary">
      <v-tab value="nc">
        <v-icon class="mr-2">mdi-clipboard-alert-outline</v-icon>
        Fiche Non-conformité
      </v-tab>
      <v-tab value="complaint">
        <v-icon class="mr-2">mdi-message-alert-outline</v-icon>
        Fiche Plaintes/Réclamations
      </v-tab>
    </v-tabs>

    <v-window v-model="tabModel">
      <v-window-item value="nc">
        <NonConformityTab
          :action-type-options="actionTypeOptions"
          :detection-source-options="detectionSourceOptions"
          :editing-nc-id="editingNcId"
          :finding-type-options="findingTypeOptions"
          :loading-nc="loadingNc"
          :nc-actions="ncActions"
          :nc-form="ncForm"
          :nc-headers="ncHeaders"
          :nc-status-options="ncStatusOptions"
          :nc-type-options="ncTypeOptions"
          :non-conformities="nonConformities"
          :process-options="processOptions"
          :saving-nc="savingNc"
          :status-color="statusColor"
          :status-label="statusLabel"
          :user-options="userOptions"
          @add-action="emit('add-nc-action')"
          @edit="emit('edit-nc', $event)"
          @export="emit('export-nc')"
          @remove-action="emit('remove-nc-action', $event)"
          @submit="emit('submit-nc')"
        />
      </v-window-item>

      <v-window-item value="complaint">
        <ComplaintTab
          :action-type-options="actionTypeOptions"
          :format-date="formatDate"
          :loading-reclamations="loadingReclamations"
          :process-options="processOptions"
          :reclamation-actions="reclamationActions"
          :reclamation-form="reclamationForm"
          :reclamation-headers="reclamationHeaders"
          :reclamation-status-options="reclamationStatusOptions"
          :reclamation-type-options="reclamationTypeOptions"
          :reclamations="reclamations"
          :saving-reclamation="savingReclamation"
          :user-options="userOptions"
          @add-action="emit('add-reclamation-action')"
          @edit="emit('edit-reclamation', $event)"
          @export="emit('export-reclamations')"
          @remove-action="emit('remove-reclamation-action', $event)"
          @submit="emit('submit-reclamation')"
        />
      </v-window-item>
    </v-window>
  </v-card>
</template>

<script setup lang="ts">
  import { computed, type PropType } from 'vue'
  import ComplaintTab from '@/modules/clienta/pages/iso/operations/components/ComplaintTab.vue'
  import NonConformityTab from '@/modules/clienta/pages/iso/operations/components/NonConformityTab.vue'

  const props = defineProps({
    modelValue: {
      type: String as PropType<'nc' | 'complaint'>,
      required: true,
    },
    actionTypeOptions: {
      type: Array as PropType<Array<{ title: string, value: string }>>,
      required: true,
    },
    detectionSourceOptions: {
      type: Array as PropType<Array<{ title: string, value: string }>>,
      required: true,
    },
    findingTypeOptions: {
      type: Array as PropType<Array<{ title: string, value: string }>>,
      required: true,
    },
    ncTypeOptions: {
      type: Array as PropType<Array<{ title: string, value: string }>>,
      required: true,
    },
    ncStatusOptions: {
      type: Array as PropType<Array<{ title: string, value: string }>>,
      required: true,
    },
    reclamationTypeOptions: {
      type: Array as PropType<Array<{ title: string, value: string }>>,
      required: true,
    },
    reclamationStatusOptions: {
      type: Array as PropType<Array<{ title: string, value: string }>>,
      required: true,
    },
    ncHeaders: {
      type: Array as PropType<Array<Record<string, any>>>,
      required: true,
    },
    reclamationHeaders: {
      type: Array as PropType<Array<Record<string, any>>>,
      required: true,
    },
    ncForm: {
      type: Object as PropType<Record<string, any>>,
      required: true,
    },
    reclamationForm: {
      type: Object as PropType<Record<string, any>>,
      required: true,
    },
    ncActions: {
      type: Array as PropType<Array<Record<string, any>>>,
      required: true,
    },
    reclamationActions: {
      type: Array as PropType<Array<Record<string, any>>>,
      required: true,
    },
    nonConformities: {
      type: Array as PropType<Array<Record<string, any>>>,
      required: true,
    },
    reclamations: {
      type: Array as PropType<Array<Record<string, any>>>,
      required: true,
    },
    processOptions: {
      type: Array as PropType<Array<{ value: number, title: string }>>,
      required: true,
    },
    userOptions: {
      type: Array as PropType<Array<{ id: number, name: string }>>,
      required: true,
    },
    editingNcId: {
      type: [Number, null] as PropType<number | null>,
      default: null,
    },
    loadingNc: {
      type: Boolean,
      default: false,
    },
    savingNc: {
      type: Boolean,
      default: false,
    },
    loadingReclamations: {
      type: Boolean,
      default: false,
    },
    savingReclamation: {
      type: Boolean,
      default: false,
    },
    statusColor: {
      type: Function as PropType<(status: 'open' | 'in_progress' | 'closed') => string>,
      required: true,
    },
    statusLabel: {
      type: Function as PropType<(status: 'open' | 'in_progress' | 'closed') => string>,
      required: true,
    },
    formatDate: {
      type: Function as PropType<(value: string) => string>,
      required: true,
    },
  })

  const emit = defineEmits<{
    (e: 'update:modelValue', value: 'nc' | 'complaint'): void
    (e: 'add-nc-action'): void
    (e: 'remove-nc-action', index: number): void
    (e: 'submit-nc'): void
    (e: 'edit-nc', id: number): void
    (e: 'export-nc'): void
    (e: 'add-reclamation-action'): void
    (e: 'remove-reclamation-action', index: number): void
    (e: 'submit-reclamation'): void
    (e: 'edit-reclamation', item: any): void
    (e: 'export-reclamations'): void
  }>()

  const tabModel = computed({
    get: () => props.modelValue,
    set: (value: 'nc' | 'complaint') => emit('update:modelValue', value),
  })

  void props
</script>

<style scoped>
.control-nc-shell {
  width: 100%;
  border: 1px solid rgba(15, 23, 42, 0.08);
  box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.98));
}

.modern-tabs {
  border-bottom: 1px solid rgba(15, 23, 42, 0.08);
}

:deep(.intro-card) {
  border: 1px solid rgba(15, 23, 42, 0.12);
  background: linear-gradient(120deg, rgba(91, 141, 217, 0.08), rgba(255, 255, 255, 0.9));
}

:deep(.list-card) {
  border: 1px solid rgba(15, 23, 42, 0.1);
  background: #ffffff;
  box-shadow: 0 18px 38px rgba(15, 23, 42, 0.08);
  overflow: hidden;
}

:deep(.list-card-head) {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 18px 20px;
  background: linear-gradient(90deg, rgba(91, 141, 217, 0.1), rgba(91, 141, 217, 0.02));
}

:deep(.list-title) {
  font-weight: 700;
  color: #0f172a;
  font-size: 1.05rem;
}

:deep(.list-subtitle) {
  color: rgba(15, 23, 42, 0.7);
  font-size: 0.9rem;
  margin-top: 4px;
}

:deep(.list-actions) {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
}

:deep(.list-table thead th) {
  font-weight: 600;
  color: rgba(15, 23, 42, 0.7);
  background: rgba(248, 250, 252, 0.8);
}

:deep(.list-table tbody tr) {
  transition: background 0.2s ease;
}

:deep(.list-table tbody tr:hover) {
  background: rgba(91, 141, 217, 0.08);
}

:deep(.list-table tbody tr:nth-child(2n)) {
  background: rgba(248, 250, 252, 0.6);
}

:deep(.intro-card-body) {
  display: grid;
  gap: 10px;
}

:deep(.intro-title) {
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 700;
  color: #0f172a;
}

:deep(.intro-lines) {
  display: grid;
  gap: 6px;
  color: rgba(15, 23, 42, 0.78);
  font-size: 0.92rem;
}

:deep(.intro-actions) {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

:deep(.form-grid) {
  gap: 12px;
}

:deep(.form-section) {
  border-color: rgba(15, 23, 42, 0.12);
  background: linear-gradient(180deg, rgba(250, 252, 255, 0.98), rgba(255, 255, 255, 0.98));
  box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06);
  overflow: hidden;
}

:deep(.section-title) {
  display: flex;
  align-items: center;
  gap: 16px;
  font-size: 1.1rem;
  font-weight: 700;
  color: #1e293b;
  padding: 16px 18px;
  background: linear-gradient(90deg, rgba(91, 141, 217, 0.12), rgba(91, 141, 217, 0.02));
  border-bottom: 1px solid rgba(15, 23, 42, 0.06);
}

:deep(.section-number) {
  display: grid;
  place-items: center;
  width: 38px;
  height: 38px;
  border-radius: 10px;
  background: linear-gradient(135deg, #5b8dd9 0%, #4a71b0 100%);
  color: #fff;
  font-size: 1rem;
  font-weight: 700;
}

:deep(.action-row) {
  border-color: rgba(15, 23, 42, 0.12);
  background: rgba(255, 255, 255, 0.95);
  box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
}

:deep(.action-toolbar) {
  flex-wrap: wrap;
  gap: 8px;
}

:deep(.action-row-cta) {
  justify-content: flex-end;
}

@media (max-width: 600px) {
  :deep(.list-card-head) {
    flex-direction: column;
    align-items: flex-start;
  }

  :deep(.action-row-cta) {
    justify-content: flex-start;
  }

  .section-title {
    padding: 12px 14px;
  }
}
</style>
