<template>
  <div class="animate-pulse">
    <!-- Card Skeleton -->
    <div v-if="type === 'card'" class="bg-white rounded-lg shadow p-6">
      <div class="flex items-center space-x-4 mb-4">
        <div class="rounded-full bg-gray-200 h-12 w-12" />
        <div class="space-y-2 flex-1">
          <div class="h-4 bg-gray-200 rounded w-3/4" />
          <div class="h-3 bg-gray-200 rounded w-1/2" />
        </div>
      </div>
      <div class="space-y-3">
        <div class="h-3 bg-gray-200 rounded" />
        <div class="h-3 bg-gray-200 rounded w-5/6" />
        <div class="h-3 bg-gray-200 rounded w-4/6" />
      </div>
    </div>

    <!-- Table Skeleton -->
    <div v-else-if="type === 'table'" class="bg-white rounded-lg shadow overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200">
        <div class="h-6 bg-gray-200 rounded w-1/4" />
      </div>
      <div class="divide-y divide-gray-200">
        <div v-for="i in rows" :key="i" class="px-6 py-4 flex items-center space-x-4">
          <div class="h-4 bg-gray-200 rounded w-1/6" />
          <div class="h-4 bg-gray-200 rounded w-1/4" />
          <div class="h-4 bg-gray-200 rounded w-1/5" />
          <div class="h-4 bg-gray-200 rounded w-1/6" />
          <div class="h-4 bg-gray-200 rounded w-1/8" />
        </div>
      </div>
    </div>

    <!-- List Skeleton -->
    <div v-else-if="type === 'list'" class="space-y-4">
      <div v-for="i in rows" :key="i" class="flex items-center space-x-4 p-4 bg-white rounded-lg shadow">
        <div class="rounded-full bg-gray-200 h-10 w-10" />
        <div class="space-y-2 flex-1">
          <div class="h-4 bg-gray-200 rounded w-3/4" />
          <div class="h-3 bg-gray-200 rounded w-1/2" />
        </div>
        <div class="h-8 bg-gray-200 rounded w-20" />
      </div>
    </div>

    <!-- Stats Skeleton -->
    <div v-else-if="type === 'stats'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div v-for="i in 4" :key="i" class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="p-3 rounded-full bg-gray-200 h-12 w-12" />
          <div class="ml-4 space-y-2 flex-1">
            <div class="h-3 bg-gray-200 rounded w-3/4" />
            <div class="h-6 bg-gray-200 rounded w-1/2" />
          </div>
        </div>
      </div>
    </div>

    <!-- Form Skeleton -->
    <div v-else-if="type === 'form'" class="bg-white rounded-lg shadow p-6 space-y-6">
      <div class="h-6 bg-gray-200 rounded w-1/3" />
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div v-for="i in 6" :key="i" class="space-y-2">
          <div class="h-4 bg-gray-200 rounded w-1/4" />
          <div class="h-10 bg-gray-200 rounded" />
        </div>
      </div>
      <div class="flex justify-end space-x-4">
        <div class="h-10 bg-gray-200 rounded w-20" />
        <div class="h-10 bg-gray-200 rounded w-24" />
      </div>
    </div>

    <!-- Chart Skeleton -->
    <div v-else-if="type === 'chart'" class="bg-white rounded-lg shadow p-6">
      <div class="h-6 bg-gray-200 rounded w-1/3 mb-6" />
      <div class="h-64 bg-gray-200 rounded" />
    </div>

    <!-- Custom Skeleton -->
    <div v-else class="space-y-4">
      <div v-for="i in rows" :key="i" class="h-4 bg-gray-200 rounded" :class="getRandomWidth()" />
    </div>
  </div>
</template>

<script setup lang="ts">
  interface Props {
    type?: 'card' | 'table' | 'list' | 'stats' | 'form' | 'chart' | 'custom'
    rows?: number
  }

  const _props = withDefaults(defineProps<Props>(), {
    type: 'custom',
    rows: 3,
  })

  function getRandomWidth () {
    const widths = ['w-full', 'w-5/6', 'w-4/6', 'w-3/4', 'w-2/3', 'w-1/2']
    return widths[Math.floor(Math.random() * widths.length)]
  }
</script>
