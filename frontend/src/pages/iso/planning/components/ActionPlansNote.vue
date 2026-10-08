<template>
  <Card class="mb-5 note-card" variant="bordered">
    <div class="mb-3 flex items-center gap-2 text-sm font-medium text-slate-700">
      <v-icon color="primary" size="18">mdi-note-text-outline</v-icon>
      Note de planification
    </div>
    <v-textarea
      v-model="noteValue"
      auto-grow
      bg-color="white"
      density="comfortable"
      hide-details
      rows="2"
      variant="outlined"
    />
  </Card>
</template>

<script setup lang="ts">
  import { computed } from 'vue'
  import Card from '@/components/ui/Card.vue'

  const props = defineProps({
    note: {
      type: String,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'update:note', value: string): void
  }>()

  const noteValue = computed({
    get: () => props.note,
    set: value => emit('update:note', value),
  })
</script>
