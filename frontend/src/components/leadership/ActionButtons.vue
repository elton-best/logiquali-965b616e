<template>
  <div class="action-buttons">
    <button
      v-if="showSecondary"
      class="btn-secondary"
      @click="$emit('secondary')"
    >
      <v-icon v-if="secondaryIcon" size="20">{{ secondaryIcon }}</v-icon>
      {{ secondaryLabel }}
    </button>
    <button
      class="btn-primary"
      :disabled="primaryDisabled"
      @click="$emit('primary')"
    >
      {{ primaryLabel }}
      <v-icon v-if="primaryIcon" size="20">{{ primaryIcon }}</v-icon>
    </button>
  </div>
</template>

<script setup lang="ts">
  withDefaults(defineProps<{
    primaryLabel: string
    primaryIcon?: string
    primaryDisabled?: boolean
    secondaryLabel?: string
    secondaryIcon?: string
    showSecondary?: boolean
  }>(), {
    showSecondary: true,
  })

  defineEmits<{
    primary: []
    secondary: []
  }>()
</script>

<style scoped>
.action-buttons {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  padding-top: 32px;
  border-top: 2px solid #e2e8f0;
}

.btn-primary,
.btn-secondary {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 14px 28px;
  border-radius: 12px;
  font-weight: 600;
  font-size: 0.9375rem;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary {
  background: linear-gradient(135deg, #5b8dd9 0%, #4a71b0 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(91, 141, 217, 0.3);
}

.btn-primary:hover:not(:disabled) {
  box-shadow: 0 6px 20px rgba(91, 141, 217, 0.4);
  transform: translateY(-2px);
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-secondary {
  background: white;
  color: #64748b;
  border: 2px solid #e2e8f0;
}

.btn-secondary:hover {
  border-color: #cbd5e1;
  background: #f8fafc;
}

@media (max-width: 768px) {
  .action-buttons { flex-direction: column; }
}
</style>
