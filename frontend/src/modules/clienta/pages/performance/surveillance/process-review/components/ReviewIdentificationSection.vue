<template>
  <v-card rounded="xl" variant="outlined">
    <v-card-title class="d-flex align-center ga-2">
      <v-icon color="primary">mdi-card-account-details-outline</v-icon>
      <span>Section 1 - Identification</span>
    </v-card-title>
    <v-card-text>
      <v-row dense>
        <v-col cols="12" md="6">
          <v-text-field
            label="Responsable Qualité (RQ)"
            :model-value="modelValue.rq_name"
            prepend-inner-icon="mdi-account-tie"
            :readonly="readonly"
            variant="outlined"
            @update:model-value="updateField('rq_name', $event)"
          />
        </v-col>
        <v-col cols="12" md="3">
          <v-checkbox
            density="compact"
            :disabled="readonly"
            hide-details
            label="Pilote présent"
            :model-value="modelValue.include_pilot"
            @update:model-value="updateField('include_pilot', $event)"
          />
        </v-col>
        <v-col cols="12" md="3">
          <v-checkbox
            density="compact"
            :disabled="readonly"
            hide-details
            label="Copilote présent"
            :model-value="modelValue.include_copilot"
            @update:model-value="updateField('include_copilot', $event)"
          />
        </v-col>
        <v-col cols="12" md="6">
          <v-select
            chips
            item-title="title"
            item-value="value"
            :items="userOptions"
            label="Présents"
            :model-value="modelValue.present_user_ids"
            multiple
            prepend-inner-icon="mdi-account-group-outline"
            :readonly="readonly"
            variant="outlined"
            @update:model-value="updateField('present_user_ids', $event)"
          />
        </v-col>
        <v-col cols="12" md="6">
          <v-text-field
            label="Autres présents"
            :model-value="modelValue.present_others"
            prepend-inner-icon="mdi-account-plus-outline"
            :readonly="readonly"
            variant="outlined"
            @update:model-value="updateField('present_others', $event)"
          />
        </v-col>
        <v-col cols="12" md="6">
          <v-text-field
            label="Période de couverture - Début"
            :model-value="modelValue.coverage_start"
            :readonly="readonly"
            type="date"
            variant="outlined"
            @update:model-value="updateField('coverage_start', $event)"
          />
        </v-col>
        <v-col cols="12" md="6">
          <v-text-field
            label="Période de couverture - Fin"
            :model-value="modelValue.coverage_end"
            :readonly="readonly"
            type="date"
            variant="outlined"
            @update:model-value="updateField('coverage_end', $event)"
          />
        </v-col>
        <v-col cols="12" md="6">
          <v-text-field
            label="Heure de démarrage (auto)"
            :model-value="modelValue.started_at"
            prepend-inner-icon="mdi-clock-start"
            readonly
            variant="outlined"
          />
        </v-col>
        <v-col cols="12" md="6">
          <v-text-field
            label="Heure de fin (auto)"
            :model-value="modelValue.ended_at"
            prepend-inner-icon="mdi-clock-end"
            readonly
            variant="outlined"
          />
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  interface IdentificationModel {
    rq_name: string
    include_pilot: boolean
    include_copilot: boolean
    present_user_ids: number[]
    present_others: string
    coverage_start: string
    coverage_end: string
    started_at: string
    ended_at: string
  }

  const props = defineProps<{
    modelValue: IdentificationModel
    userOptions: Array<{ title: string, value: number }>
    readonly?: boolean
  }>()

  const emit = defineEmits<{
    'update:modelValue': [value: IdentificationModel]
  }>()

  function updateField (key: keyof IdentificationModel, value: any) {
    emit('update:modelValue', { ...props.modelValue, [key]: value })
  }
</script>
