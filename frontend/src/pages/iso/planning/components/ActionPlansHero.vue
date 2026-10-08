<template>
  <section class="plan-hero mb-6 rounded-3xl">
    <div class="hero-content">
      <div class="hero-header">
        <div class="hero-text-section">
          <div class="hero-badge">
            <v-icon size="14">mdi-chart-timeline-variant</v-icon>
            <span>PLANIFICATION STRATÉGIQUE</span>
          </div>
          <div class="hero-title-row">
            <h1 class="hero-title">Plans du Système de Management</h1>
            <div class="hero-chips">
              <v-chip
                class="status-chip"
                :color="planStatusColor(planStatusValue)"
                size="small"
                variant="flat"
              >
                <v-icon size="14" start>mdi-flag</v-icon>
                {{ planStatusTitle(planStatusValue) }}
              </v-chip>
              <v-chip
                class="year-chip"
                color="primary"
                size="small"
                variant="tonal"
              >
                <v-icon size="14" start>mdi-calendar-blank</v-icon>
                {{ selectedYearValue }}
              </v-chip>
            </div>
          </div>
          <p class="hero-description">
            Pilotez votre chronogramme annuel avec une vision claire : planifiez
            les activités, suivez l'avancement en temps réel et générez un plan
            d'action exploitable.
          </p>
        </div>

        <div class="hero-controls-section">
          <div class="controls-card">
            <div class="control-group">
              <label class="control-label">
                <v-icon class="mr-1" size="16">mdi-calendar</v-icon>
                Exercice
              </label>
              <v-text-field
                v-model.number="selectedYearValue"
                bg-color="white"
                class="control-input"
                density="comfortable"
                hide-details
                min="2020"
                type="number"
                variant="outlined"
              />
            </div>
            <div class="control-group">
              <label class="control-label">
                <v-icon class="mr-1" size="16">mdi-flag-outline</v-icon>
                Statut
              </label>
              <v-select
                v-model="planStatusValue"
                bg-color="white"
                class="control-input"
                density="comfortable"
                hide-details
                item-title="title"
                item-value="value"
                :items="planStatuses"
                variant="outlined"
              />
            </div>
            <div class="control-group">
              <label class="control-label">
                <v-icon class="mr-1" size="16">mdi-timeline-text-outline</v-icon>
                Format
              </label>
              <v-select
                v-model="planFormatValue"
                bg-color="white"
                class="control-input"
                density="comfortable"
                hide-details
                item-title="title"
                item-value="value"
                :items="planFormatOptions"
                variant="outlined"
              />
            </div>
            <div class="control-group">
              <label class="control-label">
                <v-icon class="mr-1" size="16">mdi-view-list-outline</v-icon>
                Vue
              </label>
              <v-select
                v-model="displayModeValue"
                bg-color="white"
                class="control-input"
                density="comfortable"
                hide-details
                item-title="title"
                item-value="value"
                :items="displayModeOptions"
                variant="outlined"
              />
            </div>
          </div>
        </div>
      </div>
      <v-progress-linear
        class="mt-2"
        color="primary"
        height="8"
        :model-value="completionRate"
        rounded
      />
    </div>
  </section>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import { computed } from 'vue'

  const props = defineProps({
    planStatus: {
      type: String,
      required: true,
    },
    planStatuses: {
      type: Array as PropType<ReadonlyArray<{ title: string, value: string }>>,
      required: true,
    },
    planFormat: {
      type: String,
      required: true,
    },
    planFormatOptions: {
      type: Array as PropType<ReadonlyArray<{ title: string, value: string }>>,
      required: true,
    },
    displayMode: {
      type: String,
      required: true,
    },
    displayModeOptions: {
      type: Array as PropType<ReadonlyArray<{ title: string, value: string }>>,
      required: true,
    },
    selectedYear: {
      type: Number,
      required: true,
    },
    completionRate: {
      type: Number,
      required: true,
    },
    planStatusColor: {
      type: Function as PropType<(status: string) => string>,
      required: true,
    },
    planStatusTitle: {
      type: Function as PropType<(status: string) => string>,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'update:planStatus' | 'update:planFormat' | 'update:displayMode', value: string): void
    (event: 'update:selectedYear', value: number): void
  }>()

  const planStatusValue = computed({
    get: () => props.planStatus,
    set: value => emit('update:planStatus', value),
  })

  const selectedYearValue = computed({
    get: () => props.selectedYear,
    set: value => emit('update:selectedYear', value),
  })

  const planFormatValue = computed({
    get: () => props.planFormat,
    set: value => emit('update:planFormat', value),
  })

  const displayModeValue = computed({
    get: () => props.displayMode,
    set: value => emit('update:displayMode', value),
  })
</script>
