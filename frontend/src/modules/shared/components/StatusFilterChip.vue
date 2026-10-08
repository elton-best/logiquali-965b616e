<template>
  <v-chip
    class="status-filter-chip mr-2 mb-2 cursor-pointer font-weight-medium transition-all"
    :class="{
      'active-filter-chip': active,
      'inactive-filter-chip': !active,
    }"
    :color="color"
    :prepend-icon="active ? (icon || 'mdi-check-circle') : icon"
    role="button"
    :size="size"
    tabindex="0"
    :variant="active ? 'flat' : 'tonal'"
    @click="handleClick"
    @keydown.enter.prevent="handleClick"
    @keydown.space.prevent="handleClick"
  >
    <span>{{ label }}</span>
    <span
      v-if="typeof count === 'number'"
      class="ml-2 px-1.5 py-0.5 rounded-pill text-caption font-weight-bold status-count-badge"
      :class="active ? 'bg-white text-dark' : 'bg-surface-variant'"
    >
      {{ count }}
    </span>
  </v-chip>
</template>

<script setup lang="ts">
const props = withDefaults(
  defineProps<{
    status: string
    label: string
    count?: number
    active?: boolean
    color?: string
    icon?: string
    size?: 'small' | 'default' | 'x-small'
  }>(),
  {
    count: undefined,
    active: false,
    color: 'primary',
    icon: undefined,
    size: 'default',
  },
)

const emit = defineEmits<{
  click: [status: string]
  'update:active': [active: boolean]
}>()

function handleClick() {
  emit('update:active', !props.active)
  emit('click', props.status)
}
</script>

<style scoped>
.status-filter-chip {
  user-select: none;
  transition: all 0.2s ease-in-out;
}

.status-filter-chip:hover {
  transform: translateY(-1px);
  filter: brightness(0.95);
}

.status-count-badge {
  font-size: 0.72rem;
  line-height: 1;
}

.active-filter-chip {
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.12);
}
</style>

