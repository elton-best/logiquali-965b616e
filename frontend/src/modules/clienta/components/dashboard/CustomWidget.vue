<template>
  <v-card
    class="custom-widget"
    :class="{ 'widget-draggable': draggable }"
    elevation="2"
    rounded="xl"
  >
    <v-card-title class="d-flex align-center justify-space-between pa-4">
      <div class="d-flex align-center gap-2">
        <v-icon v-if="icon" :color="iconColor">{{ icon }}</v-icon>
        <span class="text-subtitle-1 font-weight-bold">{{ title }}</span>
      </div>
      <div class="d-flex align-center gap-1">
        <v-btn
          v-if="refreshable"
          icon
          size="x-small"
          variant="text"
          @click="$emit('refresh')"
        >
          <v-icon size="18">mdi-refresh</v-icon>
        </v-btn>
        <v-menu>
          <template #activator="{ props: menuProps }">
            <v-btn
              icon
              size="x-small"
              variant="text"
              v-bind="menuProps"
            >
              <v-icon size="18">mdi-dots-vertical</v-icon>
            </v-btn>
          </template>
          <v-list density="compact">
            <v-list-item @click="$emit('configure')">
              <template #prepend>
                <v-icon size="18">mdi-cog</v-icon>
              </template>
              <v-list-item-title>Configurer</v-list-item-title>
            </v-list-item>
            <v-list-item @click="$emit('remove')">
              <template #prepend>
                <v-icon size="18">mdi-close</v-icon>
              </template>
              <v-list-item-title>Supprimer</v-list-item-title>
            </v-list-item>
          </v-list>
        </v-menu>
      </div>
    </v-card-title>
    <v-divider />
    <v-card-text class="pa-4">
      <slot />
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  defineProps<{
    title: string
    icon?: string
    iconColor?: string
    draggable?: boolean
    refreshable?: boolean
  }>()

  defineEmits<{
    refresh: []
    configure: []
    remove: []
  }>()
</script>

<style scoped>
.custom-widget {
  border: 1px solid rgba(148, 163, 184, 0.15);
  transition: all 0.2s ease;
}

.widget-draggable {
  cursor: move;
}

.widget-draggable:hover {
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
}
</style>
