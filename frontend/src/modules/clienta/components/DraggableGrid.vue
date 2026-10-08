<template>
  <draggable
    v-model="localWidgets"
    class="draggable-wrapper"
    :disabled="!editMode"
    handle=".drag-handle"
    item-key="id"
    @end="onDragEnd"
  >
    <template #item="{ element }">
      <div class="widget-item" :class="{ 'edit-mode': editMode }">
        <div v-if="editMode" class="drag-handle">
          <v-icon color="primary" size="20">mdi-drag-vertical</v-icon>
        </div>
        <slot :widget="element" />
      </div>
    </template>
  </draggable>
</template>

<script setup lang="ts">
  import { ref, watch } from 'vue'
  import draggable from 'vuedraggable'

  const props = defineProps<{
    widgets: any[]
    editMode: boolean
  }>()

  const emit = defineEmits<{
    'update:widgets': [widgets: any[]]
    'save': []
  }>()

  const localWidgets = ref([...props.widgets])

  watch(() => props.widgets, newVal => {
    localWidgets.value = [...newVal]
  }, { deep: true })

  watch(localWidgets, newVal => {
    emit('update:widgets', newVal)
  }, { deep: true })

  function onDragEnd () {
    emit('save')
  }
</script>

<style scoped>
.draggable-wrapper {
  width: 100%;
}

.widget-item {
  position: relative;
  transition: all 0.3s ease;
  margin-bottom: 0;
}

.widget-item.edit-mode {
  border: 2px dashed rgba(91, 141, 217, 0.3);
  border-radius: 12px;
  padding: 12px;
  margin-bottom: 16px;
  background: rgba(91, 141, 217, 0.02);
}

.drag-handle {
  position: absolute;
  top: 16px;
  right: 16px;
  cursor: move;
  z-index: 100;
  background: white;
  border-radius: 8px;
  padding: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  transition: all 0.2s ease;
}

.drag-handle:hover {
  background: rgba(91, 141, 217, 0.1);
  transform: scale(1.1);
}
</style>
