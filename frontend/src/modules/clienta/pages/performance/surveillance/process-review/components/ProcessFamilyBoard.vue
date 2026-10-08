<template>
  <v-row dense>
    <v-col
      v-for="family in families"
      :key="family.key"
      cols="12"
      md="4"
    >
      <v-card class="family-card h-100" rounded="xl" :style="{ borderTop: `4px solid ${family.color}` }" variant="outlined">
        <v-card-title class="d-flex align-center justify-space-between">
          <span>{{ family.label }}</span>
          <v-chip size="small" variant="tonal">{{ family.items.length }}</v-chip>
        </v-card-title>
        <v-card-text class="pt-0">
          <v-alert v-if="family.items.length === 0" density="compact" type="info" variant="tonal">
            Aucun processus dans cette famille.
          </v-alert>
          <v-list v-else class="pa-0" density="compact">
            <v-list-item
              v-for="process in family.items"
              :key="process.id"
              class="mb-1 rounded-lg process-item"
              :class="{ active: selectedProcessId === process.id }"
              @click="$emit('select', process)"
            >
              <template #prepend>
                <v-avatar :color="family.color" size="26">
                  <v-icon color="white" size="14">mdi-cog-outline</v-icon>
                </v-avatar>
              </template>
              <v-list-item-title class="text-body-2 font-weight-medium">
                {{ process.title }}
              </v-list-item-title>
              <v-list-item-subtitle>{{ process.code }}</v-list-item-subtitle>
            </v-list-item>
          </v-list>
        </v-card-text>
      </v-card>
    </v-col>
  </v-row>
</template>

<script setup lang="ts">
  interface ProcessItem {
    id: number
    title: string
    code: string
    category: string
  }

  interface FamilyGroup {
    key: string
    label: string
    color: string
    items: ProcessItem[]
  }

  defineProps<{
    families: FamilyGroup[]
    selectedProcessId: number | null
  }>()

  defineEmits<{
    select: [process: ProcessItem]
  }>()
</script>

<style scoped>
.family-card {
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.96));
}

.process-item {
  border: 1px solid rgba(148, 163, 184, 0.25);
  transition: all 0.18s ease;
  cursor: pointer;
}

.process-item.active {
  border-color: rgba(var(--v-theme-primary), 0.55);
  background: rgba(var(--v-theme-primary), 0.06);
}
</style>
