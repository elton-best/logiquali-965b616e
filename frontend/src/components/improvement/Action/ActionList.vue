<template>
  <div class="action-list-wrapper">
    <h3 class="text-lg font-semibold mb-4">Liste des Actions</h3>
    <ActionTimeline :actions="actions" @action-click="$emit('action-click', $event)" />
  </div>
</template>

<script setup lang="ts">
  import { ref } from 'vue'
  import { useActionStore } from '@/stores/improvement/actionStore'
  import ActionTimeline from '../shared/ActionTimeline.vue'

  defineEmits(['action-click'])
  const actionStore = useActionStore()
  const actions = ref<any[]>([])

  actionStore.fetchActions().then(() => {
    actions.value = actionStore.actions
  })
</script>
