<template>
  <div class="cause-analysis-tool">
    <div class="tool-tabs">
      <button
        class="tool-tab"
        :class="{ 'tab-active': activeTab === '5why' }"
        @click="activeTab = '5why'"
      >
        5 Pourquoi
      </button>
      <button
        class="tool-tab"
        :class="{ 'tab-active': activeTab === 'ishikawa' }"
        @click="activeTab = 'ishikawa'"
      >
        Ishikawa (Arête de poisson)
      </button>
    </div>

    <!-- 5 Pourquoi -->
    <div v-show="activeTab === '5why'" class="analysis-content">
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Problème initial
        </label>
        <textarea
          v-model="fiveWhys.problem"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
          placeholder="Décrivez le problème..."
          rows="2"
        />
      </div>

      <div class="whys-chain">
        <div
          v-for="(why, index) in fiveWhys.whys"
          :key="index"
          class="why-item"
        >
          <div class="why-label">Pourquoi {{ index + 1 }} ?</div>
          <textarea
            v-model="fiveWhys.whys[index]"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
            :placeholder="`Réponse au pourquoi ${index + 1}...`"
            rows="2"
          />
        </div>
      </div>

      <div class="mt-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Cause racine identifiée
        </label>
        <textarea
          v-model="fiveWhys.rootCause"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-yellow-50 focus:ring-2 focus:ring-blue-500"
          placeholder="La cause racine du problème..."
          rows="2"
        />
      </div>
    </div>

    <!-- Ishikawa -->
    <div v-show="activeTab === 'ishikawa'" class="analysis-content">
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Effet / Problème
        </label>
        <input
          v-model="ishikawa.effect"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
          placeholder="L'effet ou le problème constaté..."
          type="text"
        >
      </div>

      <div class="ishikawa-categories">
        <div
          v-for="category in ishikawaCategories"
          :key="category.id"
          class="ishikawa-category"
        >
          <div class="category-header">
            <div class="category-icon" :style="{ backgroundColor: category.color }">
              {{ category.icon }}
            </div>
            <div class="category-name">{{ category.name }}</div>
          </div>

          <div class="category-causes">
            <div
              v-for="(cause, idx) in ishikawa.categories[category.id]"
              :key="idx"
              class="cause-item"
            >
              <input
                v-model="ishikawa.categories[category.id][idx]"
                class="cause-input"
                :placeholder="`Cause ${category.name.toLowerCase()}...`"
                type="text"
              >
              <button
                class="remove-cause"
                @click="removeCause(category.id, idx)"
              >
                ×
              </button>
            </div>

            <button
              class="add-cause-btn"
              @click="addCause(category.id)"
            >
              + Ajouter une cause
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="tool-actions">
      <button class="btn-secondary" @click="exportAnalysis">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
        </svg>
        Exporter
      </button>
      <button class="btn-primary" @click="saveAnalysis">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
        </svg>
        Enregistrer
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { reactive, ref, watch } from 'vue'

  interface Props {
    modelValue?: any
  }

  const props = defineProps<Props>()
  const emit = defineEmits<{
    'update:modelValue': [value: any]
    'save': [value: any]
  }>()

  const activeTab = ref<'5why' | 'ishikawa'>('5why')
  type IshikawaCategoryId = 'method' | 'material' | 'machine' | 'manpower' | 'environment' | 'management'

  // 5 Pourquoi
  const fiveWhys = reactive({
    problem: '',
    whys: ['', '', '', '', ''],
    rootCause: '',
  })

  // Ishikawa categories
  const ishikawaCategories: Array<{ id: IshikawaCategoryId, name: string, icon: string, color: string }> = [
    { id: 'method', name: 'Méthode', icon: '⚙️', color: '#3b82f6' },
    { id: 'material', name: 'Matière', icon: '📦', color: '#8b5cf6' },
    { id: 'machine', name: 'Machine', icon: '🔧', color: '#ec4899' },
    { id: 'manpower', name: 'Main d\'œuvre', icon: '👥', color: '#f59e0b' },
    { id: 'environment', name: 'Milieu', icon: '🌍', color: '#10b981' },
    { id: 'management', name: 'Management', icon: '📊', color: '#6366f1' },
  ]

  const ishikawa = reactive({
    effect: '',
    categories: {
      method: [''],
      material: [''],
      machine: [''],
      manpower: [''],
      environment: [''],
      management: [''],
    } as Record<IshikawaCategoryId, string[]>,
  })

  // Add cause to category
  function addCause (categoryId: IshikawaCategoryId) {
    ishikawa.categories[categoryId].push('')
  }

  // Remove cause from category
  function removeCause (categoryId: IshikawaCategoryId, index: number) {
    ishikawa.categories[categoryId].splice(index, 1)
    if (ishikawa.categories[categoryId].length === 0) {
      ishikawa.categories[categoryId].push('')
    }
  }

  // Save analysis
  function saveAnalysis () {
    const data = activeTab.value === '5why' ? fiveWhys : ishikawa
    emit('update:modelValue', { type: activeTab.value, data })
    emit('save', { type: activeTab.value, data })
  }

  // Export analysis
  function exportAnalysis () {
    const data = activeTab.value === '5why' ? fiveWhys : ishikawa
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `analyse-causes-${activeTab.value}-${Date.now()}.json`
    a.click()
    URL.revokeObjectURL(url)
  }

  // Watch for external changes
  watch(() => props.modelValue, newVal => {
    if (newVal) {
      if (newVal.type === '5why' && newVal.data) {
        Object.assign(fiveWhys, newVal.data)
        activeTab.value = '5why'
      } else if (newVal.type === 'ishikawa' && newVal.data) {
        Object.assign(ishikawa, newVal.data)
        activeTab.value = 'ishikawa'
      }
    }
  }, { immediate: true })
</script>

<style scoped>
.cause-analysis-tool {
  @apply bg-white rounded-lg border border-gray-200 p-6;
}

.tool-tabs {
  @apply flex gap-2 mb-6 border-b border-gray-200;
}

.tool-tab {
  @apply px-4 py-2 text-sm font-medium text-gray-600 border-b-2 border-transparent hover:text-gray-900 hover:border-gray-300 transition-colors;
}

.tool-tab.tab-active {
  @apply text-blue-600 border-blue-600;
}

.analysis-content {
  @apply mt-4;
}

.whys-chain {
  @apply space-y-4;
}

.why-item {
  @apply relative;
}

.why-label {
  @apply text-sm font-semibold text-gray-700 mb-2;
}

.ishikawa-categories {
  @apply grid grid-cols-1 md:grid-cols-2 gap-6 mt-6;
}

.ishikawa-category {
  @apply border border-gray-200 rounded-lg p-4;
}

.category-header {
  @apply flex items-center gap-3 mb-4;
}

.category-icon {
  @apply w-10 h-10 rounded-full flex items-center justify-center text-white text-xl;
}

.category-name {
  @apply text-sm font-semibold text-gray-900;
}

.category-causes {
  @apply space-y-2;
}

.cause-item {
  @apply flex items-center gap-2;
}

.cause-input {
  @apply flex-1 border border-gray-300 rounded px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500;
}

.remove-cause {
  @apply w-6 h-6 flex items-center justify-center text-gray-400 hover:text-red-600 hover:bg-red-50 rounded;
}

.add-cause-btn {
  @apply w-full px-3 py-1.5 text-sm text-blue-600 border border-blue-300 border-dashed rounded hover:bg-blue-50 transition-colors;
}

.tool-actions {
  @apply flex justify-end gap-3 mt-6 pt-6 border-t border-gray-200;
}

.btn-primary {
  @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center;
}

.btn-secondary {
  @apply px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 flex items-center;
}
</style>
