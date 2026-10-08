<template>
  <div class="role-section">
    <div class="role-info">
      <v-icon color="info">mdi-information</v-icon>
      <span>Sélectionnez le rôle approprié pour ce collaborateur.</span>
    </div>

    <v-alert
      v-if="roles.length === 0"
      class="mb-4"
      density="comfortable"
      type="warning"
      variant="tonal"
    >
      Aucun rôle n'est éligible pour le site sélectionné. Vérifiez les
      normes actives du site.
    </v-alert>

    <div class="roles-grid">
      <label
        v-for="role in roles"
        :key="role.value"
        class="role-card"
        :class="{ selected: modelValue === role.value }"
      >
        <input
          v-model="modelValue"
          class="role-radio"
          type="radio"
          :value="role.value"
        >
        <div class="role-content">
          <v-icon :color="role.color" size="32">{{ role.icon }}</v-icon>
          <div class="role-label">{{ role.label }}</div>
          <div class="role-description">{{ role.description }}</div>
        </div>
      </label>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  type RoleOption = {
    value: string
    label: string
    description: string
    icon: string
    color: string
  }

  const props = defineProps<{
    modelValue: string
    roles: RoleOption[]
  }>()

  const emit = defineEmits<{
    (event: 'update:modelValue', value: string): void
  }>()

  const modelValue = computed({
    get: () => props.modelValue,
    set: value => emit('update:modelValue', value),
  })
</script>

<style scoped>
.role-section {
  padding: 12px 0;
}

.role-info {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 12px;
  background: rgba(91, 141, 217, 0.05);
  border-radius: 8px;
  margin-bottom: 12px;
  font-size: 0.8125rem;
  color: #666;
}

.roles-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 12px;
}

.role-card {
  position: relative;
  padding: 14px;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  background: white;
  cursor: pointer;
  transition: all 0.3s ease;
}

.role-card:hover {
  border-color: #5b8dd9;
  box-shadow: 0 4px 12px rgba(91, 141, 217, 0.15);
  transform: translateY(-2px);
}

.role-card.selected {
  border-color: #5b8dd9;
  background: rgba(91, 141, 217, 0.05);
  box-shadow: 0 4px 12px rgba(91, 141, 217, 0.2);
}

.role-radio {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.role-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 8px;
}

.role-label {
  font-weight: 600;
  font-size: 0.9rem;
  color: #333;
  margin-top: 4px;
}

.role-description {
  font-size: 0.8125rem;
  color: #666;
  line-height: 1.4;
}

@media (max-width: 768px) {
  .roles-grid {
    grid-template-columns: 1fr;
  }
}
</style>
