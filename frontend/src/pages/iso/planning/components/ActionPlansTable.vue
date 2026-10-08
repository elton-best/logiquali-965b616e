<template>
  <Card class="plans-shell" padding="none" variant="bordered">
    <div
      class="shell-topbar flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
    >
      <div class="flex items-center gap-3 flex-wrap">
        <div class="text-sm text-slate-600">
          <span class="font-medium text-slate-800">{{
            activities.length
          }}</span>
          activité(s) •
          <span class="font-medium text-slate-800">{{
            subActivitiesCount
          }}</span>
          sous-activité(s)
        </div>
        <div class="flex gap-2">
          <v-btn
            class="collapse-all-btn"
            prepend-icon="mdi-chevron-up"
            size="small"
            variant="outlined"
            @click="emit('collapse-all')"
          >
            Réduire tout
          </v-btn>
          <v-btn
            class="expand-all-btn"
            prepend-icon="mdi-chevron-down"
            size="small"
            variant="outlined"
            @click="emit('expand-all')"
          >
            Développer tout
          </v-btn>
        </div>
      </div>

      <div class="flex flex-wrap gap-2">
        <v-btn
          color="secondary"
          prepend-icon="mdi-plus"
          rounded="lg"
          variant="tonal"
          @click="emit('add-activity')"
        >
          Ajouter une activité
        </v-btn>
        <v-btn
          color="success"
          :disabled="loading || exporting || !hasSite"
          :loading="exporting"
          prepend-icon="mdi-file-excel"
          rounded="lg"
          variant="tonal"
          @click="emit('export')"
        >
          Exporter Excel
        </v-btn>
        <v-btn
          color="primary"
          :disabled="!canSave"
          :loading="saving"
          prepend-icon="mdi-content-save"
          rounded="lg"
          @click="emit('save')"
        >
          Enregistrer
        </v-btn>
      </div>
    </div>

    <v-progress-linear v-if="loading" color="primary" indeterminate />

    <div class="overflow-x-auto plans-table-wrap">
      <table class="w-full min-w-[1980px] border-collapse text-sm">
        <thead class="bg-slate-100">
          <tr>
            <th
              class="sticky left-0 z-10 w-[620px] min-w-[620px] border-b border-r border-slate-200 bg-slate-100 px-3 py-3 text-left font-semibold text-slate-800"
            >
              Activité / Sous-activité
            </th>
            <th
              v-for="month in monthColumns"
              :key="month.key"
              class="month-head border-b border-r border-slate-200 px-2 py-3 text-center font-semibold text-slate-700"
            >
              {{ month.label }}
            </th>
            <th
              class="border-b border-r border-slate-200 px-3 py-3 text-center font-semibold text-slate-700"
            >
              Suivi
            </th>
            <th
              class="w-[360px] min-w-[360px] border-b border-r border-slate-200 px-3 py-3 text-left font-semibold text-slate-700"
            >
              Observations
            </th>
            <th
              class="border-b border-slate-200 px-3 py-3 text-center font-semibold text-slate-700"
            >
              Action
            </th>
          </tr>
        </thead>

        <tbody>
          <template
            v-for="(activity, activityIndex) in activities"
            :key="activity.id"
          >
            <tr class="activity-row">
              <td
                class="border-b border-slate-200 bg-slate-50 px-4 py-4"
                colspan="16"
              >
                <div class="activity-header">
                  <div class="activity-top">
                    <v-icon
                      class="text-primary"
                      size="22"
                    >mdi-folder-outline</v-icon>
                    <v-text-field
                      v-model="activity.label"
                      class="activity-field"
                      density="comfortable"
                      hide-details
                      placeholder="Intitulé de l'activité"
                      variant="outlined"
                    />
                    <v-chip color="primary" size="small" variant="flat">
                      {{ activity.subActivities.length }} sous-activité(s)
                    </v-chip>
                  </div>

                  <div class="activity-meta-grid">
                    <v-text-field
                      v-model="activity.startDate"
                      density="comfortable"
                      hide-details
                      label="Début"
                      type="date"
                      variant="outlined"
                      @update:model-value="emit('update-activity-schedule', activityIndex)"
                    />
                    <v-text-field
                      v-model.number="activity.plannedDays"
                      density="comfortable"
                      hide-details
                      label="Durée (jours)"
                      min="1"
                      type="number"
                      variant="outlined"
                      @update:model-value="emit('update-activity-schedule', activityIndex)"
                    />
                    <v-text-field
                      density="comfortable"
                      hide-details
                      label="Fin calculée"
                      :model-value="activity.endDate"
                      readonly
                      variant="outlined"
                    />
                    <v-select
                      v-model="activity.status"
                      density="comfortable"
                      hide-details
                      item-title="title"
                      item-value="value"
                      :items="activityStatusOptions"
                      label="Statut"
                      variant="outlined"
                      @update:model-value="emit('update-activity-status', { activityIndex, status: $event as ActivityStatus })"
                    />
                    <v-text-field
                      v-model="activity.responsible"
                      density="comfortable"
                      hide-details
                      label="Responsable"
                      placeholder="Nom du responsable"
                      variant="outlined"
                    />
                    <v-text-field
                      v-model="activity.contributors"
                      density="comfortable"
                      hide-details
                      label="Responsables impliqués"
                      placeholder="Participants ou services"
                      variant="outlined"
                    />
                    <v-text-field
                      v-model.number="activity.progress"
                      density="comfortable"
                      hide-details
                      label="Avancement (%)"
                      max="100"
                      min="0"
                      type="number"
                      variant="outlined"
                      @update:model-value="emit('update-activity-progress', { activityIndex, progress: Number($event) || 0 })"
                    />
                    <v-text-field
                      v-model="activity.report"
                      density="comfortable"
                      hide-details
                      label="Clôture / résultat"
                      placeholder="Résultat obtenu ou compte rendu"
                      variant="outlined"
                    />
                  </div>

                  <div class="activity-actions">
                    <div class="flex flex-wrap items-center gap-2">
                      <v-btn
                        color="secondary"
                        prepend-icon="mdi-plus"
                        size="small"
                        variant="tonal"
                        @click="emit('add-sub-activity', activityIndex)"
                      >
                        Ajouter sous-activité
                      </v-btn>
                      <v-btn
                        color="success"
                        prepend-icon="mdi-check-circle-outline"
                        size="small"
                        variant="tonal"
                        @click="emit('update-activity-status', { activityIndex, status: 'completed' })"
                      >
                        Marquer terminé
                      </v-btn>
                    </div>
                    <div class="flex items-center gap-2">
                      <v-btn
                        color="error"
                        :disabled="activities.length <= 1"
                        icon="mdi-delete-outline"
                        size="small"
                        variant="text"
                        @click="emit('remove-activity', activityIndex)"
                      />
                      <v-btn
                        class="toggle-btn"
                        color="primary"
                        :prepend-icon="
                          collapsedActivities.has(activity.id)
                            ? 'mdi-chevron-down'
                            : 'mdi-chevron-up'
                        "
                        size="small"
                        variant="tonal"
                        @click="emit('toggle-activity', activity.id)"
                      >
                        {{
                          collapsedActivities.has(activity.id)
                            ? "Afficher"
                            : "Masquer"
                        }}
                      </v-btn>
                    </div>
                  </div>
                </div>
              </td>
            </tr>

            <template v-if="!collapsedActivities.has(activity.id)">
              <tr
                v-for="(
                  subActivity, subActivityIndex
                ) in activity.subActivities"
                :key="subActivity.id"
                class="subactivity-row"
              >
                <td
                  class="sticky left-0 z-10 border-b border-r border-slate-200 bg-white px-2 py-2"
                >
                  <div class="flex items-center gap-2 pl-6">
                    <v-icon
                      class="text-slate-500"
                      size="17"
                    >mdi-subdirectory-arrow-right</v-icon>
                    <v-textarea
                      v-model="subActivity.label"
                      auto-grow
                      class="subactivity-field"
                      density="comfortable"
                      hide-details
                      placeholder="Intitulé de la sous-activité"
                      rows="2"
                      variant="outlined"
                    />
                  </div>
                </td>

                <td
                  v-for="month in monthColumns"
                  :key="`${subActivity.id}-${month.key}`"
                  class="month-cell border-b border-r border-slate-200 px-2 py-2 text-center"
                >
                  <input
                    :checked="subActivity.months[month.key]"
                    class="month-check h-4 w-4 cursor-pointer rounded border-slate-300 accent-blue-700"
                    type="checkbox"
                    @change="
                      emit('toggle-month', {
                        activityIndex,
                        subActivityIndex,
                        monthKey: month.key,
                      })
                    "
                  >
                </td>

                <td class="border-b border-r border-slate-200 px-2 py-2">
                  <v-select
                    v-model="subActivity.progress"
                    density="compact"
                    hide-details
                    item-title="title"
                    item-value="value"
                    :items="rowProgressOptions"
                    variant="outlined"
                  />
                </td>

                <td class="border-b border-r border-slate-200 px-2 py-2 align-top">
                  <v-textarea
                    v-model="subActivity.observation"
                    auto-grow
                    density="compact"
                    hide-details
                    placeholder="Commentaire"
                    rows="2"
                    variant="outlined"
                  />
                </td>

                <td class="border-b border-slate-200 px-2 py-2 text-center">
                  <v-btn
                    color="error"
                    :disabled="activity.subActivities.length <= 1"
                    icon="mdi-delete-outline"
                    size="small"
                    variant="text"
                    @click="
                      emit('remove-sub-activity', {
                        activityIndex,
                        subActivityIndex,
                      })
                    "
                  />
                </td>
              </tr>
            </template>
          </template>
        </tbody>
      </table>
    </div>
  </Card>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import Card from '@/components/ui/Card.vue'

  interface MonthColumn {
    key: string
    label: string
  }

  interface SmSubActivity {
    id: string
    label: string
    months: Record<string, boolean>
    progress: '' | 'en_cours' | 'fait'
    observation: string
  }

  type ActivityStatus = 'not_started' | 'in_progress' | 'completed'

  interface SmActivity {
    id: string
    label: string
    startDate: string
    plannedDays: number
    endDate: string
    status: ActivityStatus
    progress: number
    responsible: string
    contributors: string
    report: string
    subActivities: SmSubActivity[]
  }

  const props = defineProps({
    activities: {
      type: Array as PropType<SmActivity[]>,
      required: true,
    },
    subActivitiesCount: {
      type: Number,
      required: true,
    },
    monthColumns: {
      type: Array as PropType<MonthColumn[]>,
      required: true,
    },
    rowProgressOptions: {
      type: Array as PropType<ReadonlyArray<{ title: string, value: string }>>,
      required: true,
    },
    activityStatusOptions: {
      type: Array as PropType<ReadonlyArray<{ title: string, value: string }>>,
      required: true,
    },
    collapsedActivities: {
      type: Object as PropType<Set<string>>,
      required: true,
    },
    loading: {
      type: Boolean,
      default: false,
    },
    saving: {
      type: Boolean,
      default: false,
    },
    exporting: {
      type: Boolean,
      default: false,
    },
    canSave: {
      type: Boolean,
      default: false,
    },
    hasSite: {
      type: Boolean,
      default: false,
    },
  })

  const emit = defineEmits<{
    (e: 'collapse-all' | 'expand-all' | 'add-activity' | 'save' | 'export'): void
    (e: 'remove-activity' | 'add-sub-activity' | 'update-activity-schedule', index: number): void
    (
      e: 'remove-sub-activity',
      payload: { activityIndex: number, subActivityIndex: number },
    ): void
    (e: 'toggle-activity', id: string): void
    (
      e: 'toggle-month',
      payload: {
        activityIndex: number
        subActivityIndex: number
        monthKey: string
      },
    ): void
    (
      e: 'update-activity-status',
      payload: { activityIndex: number, status: ActivityStatus },
    ): void
    (
      e: 'update-activity-progress',
      payload: { activityIndex: number, progress: number },
    ): void
  }>()

  void props
</script>

<style scoped>
.plans-shell {
  border-color: rgb(203 213 225 / 0.95) !important;
  box-shadow: 0 18px 36px rgb(15 23 42 / 0.08);
  overflow: hidden;
}

.shell-topbar {
  border-bottom: 1px solid rgb(226 232 240 / 0.95);
  background: linear-gradient(
    180deg,
    rgb(248 250 252 / 0.95) 0%,
    rgb(241 245 249 / 0.88) 100%
  );
}

.plans-table-wrap {
  background:
    radial-gradient(
      circle at top right,
      rgb(219 234 254 / 0.44),
      transparent 34%
    ),
    linear-gradient(180deg, rgb(255 255 255 / 0.98), rgb(248 250 252 / 0.95));
}

.plans-table-wrap :deep(thead th) {
  position: sticky;
  top: 0;
  z-index: 3;
  box-shadow: inset 0 -1px 0 rgb(226 232 240 / 1);
}

.month-head {
  min-width: 56px;
}

.month-cell {
  min-width: 56px;
  background-color: rgb(248 250 252 / 0.62);
}

.activity-row {
  background: linear-gradient(
    90deg,
    rgb(241 245 249 / 0.95),
    rgb(239 246 255 / 0.85)
  );
  transition: all 0.2s ease;
}

.activity-row:hover {
  background: linear-gradient(
    90deg,
    rgb(226 232 240 / 0.95),
    rgb(219 234 254 / 0.85)
  );
}

.subactivity-row {
  transition: all 0.2s ease;
  animation: slideIn 0.2s ease-out;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-4px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.subactivity-row:hover {
  background-color: rgb(241 245 249 / 0.8);
}

.month-check {
  transform: scale(1.05);
  transition: transform 0.15s ease;
}

.month-check:hover {
  transform: scale(1.12);
}

.activity-field :deep(.v-field),
.subactivity-field :deep(.v-field) {
  border-radius: 12px;
  background-color: rgb(255 255 255 / 0.97);
}

.activity-field {
  flex: 1;
  min-width: 0;
}

.activity-field :deep(.v-field__input) {
  min-height: 48px !important;
  font-size: 1rem !important;
  font-weight: 600 !important;
  padding: 12px 16px !important;
  line-height: 1.5 !important;
}

.activity-field :deep(.v-field) {
  background-color: rgb(255 255 255 / 0.98);
}

.subactivity-field :deep(.v-field__input) {
  min-height: 72px !important;
  font-size: 0.95rem !important;
  font-weight: 550 !important;
  padding: 10px 12px !important;
  line-height: 1.4 !important;
  align-items: flex-start !important;
  overflow: visible !important;
}

.subactivity-field :deep(textarea),
.plans-table-wrap :deep(textarea) {
  overflow: hidden !important;
  resize: none !important;
}

.collapse-btn {
  transition: all 0.2s ease;
  box-shadow: 0 1px 3px rgb(0 0 0 / 0.1);
}

.activity-header {
  display: flex;
  flex-direction: column;
  align-items: stretch;
  gap: 1rem;
  width: 100%;
}

.activity-top {
  display: flex;
  align-items: center;
  gap: 1rem;
  width: 100%;
  min-width: 0;
  flex-wrap: wrap;
}

.activity-meta-grid {
  display: grid;
  grid-template-columns: repeat(8, minmax(0, 1fr));
  gap: 0.75rem;
}

.activity-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.toggle-btn {
  min-width: 110px;
  font-weight: 600;
}

.collapse-btn:hover {
  transform: scale(1.05);
  box-shadow: 0 2px 6px rgb(0 0 0 / 0.15);
}

.collapse-all-btn,
.expand-all-btn {
  font-size: 0.8125rem;
  font-weight: 600;
  border-color: rgb(203 213 225);
  transition: all 0.2s ease;
}

.collapse-all-btn:hover,
.expand-all-btn:hover {
  border-color: rgb(148 163 184);
  background-color: rgb(248 250 252);
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgb(0 0 0 / 0.1);
}

@media (max-width: 1400px) {
  .activity-meta-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (max-width: 960px) {
  .activity-meta-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
</style>
