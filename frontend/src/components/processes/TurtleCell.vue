<template>
  <div
    :class="['turtle-cell rounded-lg p-4 border-2 transition-all hover:shadow-md', cellBorderColor]"
    :style="{ backgroundColor: cellBackgroundColor }"
  >
    <!-- Header -->
    <div class="flex items-center justify-between mb-3">
      <div class="flex items-center gap-2">
        <v-icon :color="color" size="24">{{ icon }}</v-icon>
        <div>
          <h3 class="font-bold text-sm">{{ title }}</h3>
          <p class="text-xs text-gray-600">{{ subtitle }}</p>
        </div>
      </div>
      <v-btn
        :color="color"
        icon="mdi-plus"
        size="x-small"
        variant="text"
        @click="handleAdd"
      />
    </div>

    <!-- Items List -->
    <div class="space-y-2 min-h-[120px]" :class="{ 'text-center': centered }">
      <div
        v-for="(item, index) in items"
        :key="index"
        class="flex items-center gap-2 bg-white/60 rounded px-2 py-1 group"
      >
        <v-icon :color="color" size="14">mdi-circle-small</v-icon>
        <span class="text-xs flex-1 text-gray-800">{{ item }}</span>
        <div class="opacity-0 group-hover:opacity-100 transition-opacity flex gap-1">
          <v-btn
            color="primary"
            icon="mdi-pencil"
            size="x-small"
            variant="text"
            @click="handleEdit(index)"
          />
          <v-btn
            color="red"
            icon="mdi-delete"
            size="x-small"
            variant="text"
            @click="handleRemove(index)"
          />
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="items.length === 0" class="text-center py-4">
        <v-icon class="opacity-30" :color="color" size="32">{{ icon }}</v-icon>
        <p class="text-xs text-gray-400 mt-1">Aucun élément</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  const props = defineProps<{
    title: string
    subtitle: string
    icon: string
    color: string
    items: string[]
    centered?: boolean
  }>()

  const emit = defineEmits(['add', 'remove', 'update'])

  const colorMap: Record<string, { bg: string, border: string }> = {
    blue: { bg: 'bg-blue-50', border: 'border-blue-300' },
    purple: { bg: 'bg-purple-50', border: 'border-purple-300' },
    green: { bg: 'bg-green-50', border: 'border-green-300' },
    orange: { bg: 'bg-orange-50', border: 'border-orange-300' },
    teal: { bg: 'bg-teal-50', border: 'border-teal-300' },
    indigo: { bg: 'bg-indigo-50', border: 'border-indigo-300' },
    red: { bg: 'bg-red-50', border: 'border-red-300' },
    cyan: { bg: 'bg-cyan-50', border: 'border-cyan-300' },
  }

  const cellBackgroundColor = computed(() => {
    return colorMap[props.color]?.bg || 'bg-gray-50'
  })

  const cellBorderColor = computed(() => {
    return colorMap[props.color]?.border || 'border-gray-300'
  })

  function handleAdd () {
    emit('add')
  }

  function handleRemove (index: number) {
    emit('remove', index)
  }

  function handleEdit (index: number) {
    const newValue = prompt('Modifier l\'élément :', props.items[index])
    if (newValue && newValue.trim()) {
      emit('update', { index, value: newValue.trim() })
    }
  }
</script>
