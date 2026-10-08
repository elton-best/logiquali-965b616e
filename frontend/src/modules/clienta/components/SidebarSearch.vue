<template>
  <!-- Quick Search -->
  <div v-if="!collapsed" class="pa-4">
    <v-text-field
      v-model="searchValue"
      class="search-field"
      density="compact"
      hide-details
      placeholder="Rechercher... (⌘K)"
      prepend-inner-icon="mdi-magnify"
      readonly
      rounded
      variant="outlined"
      @click="$emit('open-command-palette')"
    />
  </div>
  <div v-else class="pa-2 text-center">
    <v-btn
      icon
      size="small"
      variant="text"
      @click="$emit('open-command-palette')"
    >
      <v-icon>mdi-magnify</v-icon>
    </v-btn>
  </div>
</template>

<script setup lang="ts">
  import { ref } from 'vue'

  defineProps<{
    collapsed: boolean
  }>()

  defineEmits<{
    'open-command-palette': []
  }>()

  const searchValue = ref('')
</script>

<style scoped>
.search-field {
  transition: all 0.3s ease;
}

.search-field:focus-within {
  transform: scale(1.02);
}
</style>
