<template>
  <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
    <AppWidget
      clickable
      :icon="Lightbulb"
      title="Suggestions"
      :value="String(total)"
      variant="primary"
    />
    <AppWidget
      clickable
      :icon="Clock"
      title="À évaluer"
      :value="String(pending)"
      variant="warning"
      @click="emit('filter', 'À évaluer')"
    />
    <AppWidget
      clickable
      :icon="Wrench"
      title="En mise en œuvre"
      :value="String(implemented)"
      variant="info"
      @click="emit('filter', 'En mise en œuvre')"
    />
    <AppWidget
      clickable
      :icon="CheckCircle"
      title="Adoptées"
      :value="String(adopted)"
      variant="success"
      @click="emit('filter', 'Adoptée')"
    />
  </div>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import { CheckCircle, Clock, Lightbulb, Wrench } from 'lucide-vue-next'
  import AppWidget from '@/components/common/AppWidget.vue'

  const props = defineProps({
    total: {
      type: Number,
      required: true,
    },
    pending: {
      type: Number,
      required: true,
    },
    implemented: {
      type: Number,
      required: true,
    },
    adopted: {
      type: Number,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'filter', value: string): void
  }>()

  void props
</script>
