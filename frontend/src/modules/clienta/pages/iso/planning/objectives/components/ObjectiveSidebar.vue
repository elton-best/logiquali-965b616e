<template>
  <v-col cols="12" md="4">
    <v-card class="mb-4" elevation="2" rounded="lg">
      <v-card-title>Indicateur clé</v-card-title>
      <v-divider />
      <v-card-text class="text-center">
        <div
          class="kpi-circle-small mx-auto"
          :style="{ borderColor: getPerformanceColor(achievementRate) }"
        >
          <div>
            <div
              class="kpi-value-small"
              :style="{ color: getPerformanceColor(achievementRate) }"
            >
              {{ achievementRate }}%
            </div>
            <div class="text-caption text-medium-emphasis">Atteinte</div>
          </div>
        </div>
      </v-card-text>
    </v-card>

    <v-card class="mb-4" elevation="2" rounded="lg">
      <v-card-title>Indicateur lié</v-card-title>
      <v-divider />
      <v-card-text class="pa-3">
        <div class="text-caption text-medium-emphasis mb-1">Nom</div>
        <div class="text-body-2 font-weight-bold mb-2">
          {{ objective?.indicatorName || "-" }}
        </div>
        <div class="text-caption text-medium-emphasis mb-1">Unité</div>
        <div class="text-body-2 mb-2">
          {{ objective?.indicatorUnit || "-" }}
        </div>
        <div class="text-caption text-medium-emphasis mb-1">Formule</div>
        <div class="text-body-2" style="white-space: pre-line">
          {{ objective?.indicatorFormula || "-" }}
        </div>
      </v-card-text>
    </v-card>

    <v-card class="mb-4" elevation="2" rounded="lg">
      <v-card-title>Statistiques</v-card-title>
      <v-divider />
      <v-card-text class="pa-3">
        <div class="d-flex align-center justify-space-between mb-2">
          <span class="text-caption">Atteinte moyenne</span>
          <span class="text-h6 font-weight-bold">{{ achievementRate }}%</span>
        </div>
        <v-progress-linear
          :color="getPerformanceColor(achievementRate)"
          height="8"
          :model-value="achievementRate"
          rounded
        />
        <v-divider class="my-3" />
        <div class="d-flex align-center justify-space-between mb-2">
          <span class="text-caption">Minimum</span>
          <span class="text-body-1 font-weight-bold">{{ minRate }}%</span>
        </div>
        <div class="d-flex align-center justify-space-between">
          <span class="text-caption">Maximum</span>
          <span class="text-body-1 font-weight-bold">{{ maxRate }}%</span>
        </div>
      </v-card-text>
    </v-card>

    <v-card class="mb-4" elevation="2" rounded="lg">
      <v-card-title>Informations</v-card-title>
      <v-divider />
      <v-card-text class="pa-3">
        <div class="info-item">
          <v-icon color="primary">mdi-calendar-clock</v-icon>
          <div>
            <div class="text-caption text-medium-emphasis">Fréquence</div>
            <div class="text-body-2 font-weight-medium">
              {{ frequencyLabel(form?.measurement_frequency) }}
            </div>
          </div>
        </div>
        <v-divider class="my-3" />
        <div class="info-item">
          <v-icon color="primary">mdi-calendar-end</v-icon>
          <div>
            <div class="text-caption text-medium-emphasis">Échéance</div>
            <div class="text-body-2 font-weight-medium">
              {{ form?.target_date || "-" }}
            </div>
          </div>
        </div>
        <v-divider class="my-3" />
        <div class="info-item">
          <v-icon color="primary">mdi-bullseye-arrow</v-icon>
          <div>
            <div class="text-caption text-medium-emphasis">Cible</div>
            <div class="text-body-2 font-weight-medium">
              {{ form?.target_value ?? "-" }}
            </div>
          </div>
        </div>
      </v-card-text>
    </v-card>

    <v-card elevation="2" rounded="lg">
      <v-card-title class="d-flex align-center justify-space-between">
        <span>Détails complémentaires</span>
        <v-btn
          color="primary"
          icon="mdi-open-in-new"
          size="small"
          variant="text"
          @click="emit('advanced')"
        />
      </v-card-title>
      <v-divider />
      <v-card-text>
        <div class="text-caption text-medium-emphasis mb-1">Mode de calcul</div>
        <div class="text-body-2 mb-3" style="white-space: pre-line">
          {{ form?.calculation_mode || "Aucun mode de calcul." }}
        </div>
        <div class="text-caption text-medium-emphasis mb-1">Observation</div>
        <div class="text-body-2 mb-3">
          {{ form?.notes || "Aucune observation." }}
        </div>
        <div class="text-caption text-medium-emphasis mb-1">
          Ressources particulières
        </div>
        <div class="text-body-2 mb-3">
          {{ form?.special_resources || "Aucune ressource précisée." }}
        </div>
        <div class="text-caption text-medium-emphasis mt-3 mb-1">
          Actions détaillées
        </div>
        <div class="text-body-2">
          {{ form?.planned_actions?.length || 0 }} action(s)
        </div>
        <div v-if="form?.planned_actions?.length" class="mt-2">
          <v-list class="pa-0" density="compact">
            <v-list-item
              v-for="(action, index) in form.planned_actions.slice(0, 3)"
              :key="`preview-action-${index}`"
              class="px-0"
            >
              <template #prepend>
                <v-icon
                  color="primary"
                  size="18"
                >mdi-checkbox-marked-circle-outline</v-icon>
              </template>
              <v-list-item-title class="text-body-2 font-weight-medium">
                {{ action.title || `Action ${Number(index) + 1}` }}
              </v-list-item-title>
              <v-list-item-subtitle class="text-caption">
                {{ action.responsible || "Sans responsable" }} •
                {{ action.due_date || "Sans échéance" }}
              </v-list-item-subtitle>
            </v-list-item>
          </v-list>
          <div
            v-if="form.planned_actions.length > 3"
            class="text-caption text-medium-emphasis"
          >
            +{{ form.planned_actions.length - 3 }} autres actions...
          </div>
        </div>
        <v-btn
          block
          class="mt-2"
          color="primary"
          :loading="saving"
          prepend-icon="mdi-content-save"
          @click="emit('save')"
        >
          Enregistrer
        </v-btn>
      </v-card-text>
    </v-card>
  </v-col>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'

  const props = defineProps({
    objective: {
      type: Object as PropType<Record<string, any> | null>,
      required: true,
    },
    form: {
      type: Object as PropType<Record<string, any>>,
      required: true,
    },
    achievementRate: {
      type: Number,
      required: true,
    },
    minRate: {
      type: Number,
      required: true,
    },
    maxRate: {
      type: Number,
      required: true,
    },
    getPerformanceColor: {
      type: Function as PropType<(value: number) => string>,
      required: true,
    },
    frequencyLabel: {
      type: Function as PropType<(value: string) => string>,
      required: true,
    },
    saving: {
      type: Boolean,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'advanced'): void
    (event: 'save'): void
  }>()

  void props
</script>
