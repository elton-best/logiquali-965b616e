<script setup lang="ts">
  import { computed } from 'vue'
  import { cn } from './utils'

  interface TableProps {
    class?: string
  }

  const props = defineProps<TableProps>()

  const tableClass = computed(() =>
    cn('data-table', props.class),
  )
</script>

<template>
  <div class="table-container" data-slot="table-container">
    <table :class="tableClass" data-slot="table">
      <slot />
    </table>
  </div>
</template>

<style scoped>
.table-container {
  position: relative;
  width: 100%;
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: var(--font-size-sm);
}

.data-table :deep(thead) {
  background: var(--bg-secondary);
  border-bottom: 2px solid var(--border-color);
}

.data-table :deep(th) {
  padding: var(--spacing-4) var(--spacing-6);
  text-align: left;
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-bold);
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.data-table :deep(tbody tr) {
  border-bottom: 1px solid var(--border-color);
  transition: all var(--transition-base);
}

.data-table :deep(tbody tr:hover) {
  background: var(--bg-secondary);
}

.data-table :deep(td) {
  padding: var(--spacing-4) var(--spacing-6);
  font-size: var(--font-size-sm);
  color: var(--text-primary);
}
</style>
