<template>
  <div class="tabs-container">
    <button
      v-for="tab in tabs"
      :key="tab.value"
      :class="['tab-button', { active: modelValue === tab.value }]"
      @click="$emit('update:modelValue', tab.value)"
    >
      <v-icon size="20">{{ tab.icon }}</v-icon>
      {{ tab.label }}
    </button>
  </div>
</template>

<script setup lang="ts">
  interface Tab {
    value: string
    label: string
    icon: string
  }

  defineProps<{
    tabs: Tab[]
    modelValue: string
  }>()

  defineEmits<{
    'update:modelValue': [value: string]
  }>()
</script>

<style scoped>
.tabs-container {
  display: flex;
  gap: 8px;
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(20px);
  padding: 6px;
  border-radius: 16px;
  border: 1px solid rgba(255, 255, 255, 0.3);
}

.tab-button {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px 24px;
  border-radius: 12px;
  font-weight: 600;
  color: #64748b;
  background: transparent;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
}

.tab-button:hover {
  background: rgba(91, 141, 217, 0.1);
  color: #4a71b0;
}

.tab-button.active {
  background: linear-gradient(135deg, #5b8dd9 0%, #4a71b0 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(91, 141, 217, 0.3);
}
</style>
