<template>
  <div class="audit-checklist">
    <h3 class="text-lg font-semibold mb-4">Checklist Audit</h3>

    <div class="space-y-3">
      <div v-for="item in checklistItems" :key="item.id" class="checklist-item">
        <div class="flex items-start gap-3">
          <input
            :checked="item.compliant"
            class="mt-1"
            type="checkbox"
            @change="onComplianceChange(item.id, $event)"
          >
          <div class="flex-1">
            <p class="font-medium">{{ item.requirement }}</p>
            <textarea
              v-model="item.observation"
              class="w-full mt-2 px-3 py-2 border rounded-lg text-sm"
              placeholder="Observations..."
              rows="2"
            />
          </div>
        </div>
      </div>
    </div>

    <button class="btn-primary mt-4" @click="saveChecklist">Enregistrer</button>
  </div>
</template>

<script setup lang="ts">
  import { ref } from 'vue'
  import { useAuditStore } from '@/stores/improvement/auditStore'

  interface Props { auditId: number }
  const props = defineProps<Props>()
  const emit = defineEmits(['updated'])

  const auditStore = useAuditStore()
  const checklistItems = ref([
    { id: 1, requirement: 'Documentation à jour', compliant: false, observation: '' },
    { id: 2, requirement: 'Procédures appliquées', compliant: false, observation: '' },
  ])

  function updateItem (id: number, compliant: boolean) {
    const item = checklistItems.value.find(i => i.id === id)
    if (item) item.compliant = compliant
  }

  function onComplianceChange (id: number, event: Event) {
    const target = event.target
    if (!(target instanceof HTMLInputElement)) return
    updateItem(id, target.checked)
  }

  async function saveChecklist () {
    await auditStore.updateAudit(props.auditId, { checklist: checklistItems.value })
    emit('updated')
  }
</script>

<style scoped>
.checklist-item { @apply p-3 bg-gray-50 rounded-lg; }
.btn-primary { @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700; }
</style>
