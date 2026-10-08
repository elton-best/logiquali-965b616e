<template>
  <div class="space-y-6">
    <div>
      <h3 class="font-medium mb-3">Éléments disponibles</h3>
      <div class="grid grid-cols-2 gap-2">
        <button
          v-for="partType in availablePartTypes"
          :key="partType.value"
          class="px-3 py-2 border rounded text-sm hover:bg-blue-50 hover:border-blue-300 text-left"
          @click="addPart(partType.value)"
        >
          <div class="font-medium">{{ partType.label }}</div>
          <div class="text-xs text-gray-500">{{ partType.description }}</div>
        </button>
      </div>
    </div>

    <div>
      <h3 class="font-medium mb-3">Structure du code (glisser pour réorganiser)</h3>
      <div v-if="localStructure.length === 0" class="p-8 border-2 border-dashed rounded text-center text-gray-500">
        Ajoutez des éléments pour construire la structure
      </div>
      <div v-else class="space-y-2">
        <div
          v-for="(part, idx) in localStructure"
          :key="idx"
          class="flex items-center gap-3 p-3 bg-gray-50 rounded border cursor-move hover:bg-gray-100"
          draggable="true"
          @dragover.prevent
          @dragstart="dragStart(idx)"
          @drop="drop(idx)"
        >
          <span class="text-gray-400">⋮⋮</span>
          <div class="flex-1">
            <div class="font-medium text-sm">{{ getPartLabel(part.part_type) }}</div>
            <div v-if="part.part_type === 'separator'" class="text-xs text-gray-600">
              Séparateur: <input
                v-model="part.separator"
                class="w-16 px-1 border rounded"
                maxlength="3"
                placeholder="-"
                type="text"
              >
            </div>
            <div v-if="part.part_type === 'custom'" class="text-xs text-gray-600">
              Valeur: <input
                v-model="part.custom_value"
                class="w-32 px-1 border rounded"
                maxlength="10"
                placeholder="Texte"
                type="text"
              >
            </div>
            <div v-if="part.part_type === 'sequence'" class="text-xs text-gray-600 space-x-2">
              <label>Longueur:
                <input
                  v-model.number="part.length"
                  class="w-16 px-1 border rounded"
                  max="10"
                  min="1"
                  type="number"
                >
              </label>
              <label>Portée:
                <select v-model="part.sequence_scope" class="px-1 border rounded text-xs">
                  <option value="global">Globale</option>
                  <option value="by_type">Par type</option>
                  <option value="by_type_process">Par type + processus</option>
                  <option value="by_type_year">Par type + année</option>
                  <option value="by_type_process_year">Par type + processus + année</option>
                  <option value="by_type_process_year_month">Par type + processus + année + mois</option>
                </select>
              </label>
            </div>
          </div>
          <button class="text-red-600 hover:text-red-800 text-sm" @click="removePart(idx)">
            Supprimer
          </button>
        </div>
      </div>
    </div>

    <div v-if="localStructure.length > 0" class="bg-blue-50 p-4 rounded">
      <div class="font-medium text-sm mb-2">Aperçu rapide :</div>
      <div class="font-mono text-lg">{{ quickPreview }}</div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { CodeStructurePart } from '@/api/documentTypeConfiguration'
  import { computed, ref, watch } from 'vue'

  const props = defineProps<{
    modelValue: CodeStructurePart[]
    typeCode: string
  }>()

  const emit = defineEmits<{
    'update:modelValue': [value: CodeStructurePart[]]
  }>()

  const availablePartTypes: Array<{ value: CodeStructurePart['part_type'], label: string, description: string }> = [
    { value: 'type_code', label: 'Code type', description: 'POL, PRC, FOR...' },
    { value: 'process_code', label: 'Code processus', description: 'PLT, QAM, RH...' },
    { value: 'year', label: 'Année', description: '2024' },
    { value: 'month', label: 'Mois', description: '01-12' },
    { value: 'sequence', label: 'Numéro séquentiel', description: '001, 002...' },
    { value: 'separator', label: 'Séparateur', description: '-, _, /' },
    { value: 'custom', label: 'Texte personnalisé', description: 'Valeur fixe' },
  ]

  const localStructure = ref<CodeStructurePart[]>([...props.modelValue])
  const draggedIndex = ref<number | null>(null)

  function getPartLabel (partType: string): string {
    return availablePartTypes.find(p => p.value === partType)?.label || partType
  }

  function addPart (partType: CodeStructurePart['part_type']) {
    const newPart: CodeStructurePart = {
      part_type: partType,
      order: localStructure.value.length,
    }

    if (partType === 'sequence') {
      newPart.length = 3
      newPart.sequence_scope = 'by_type_process_year'
      newPart.padding_char = '0'
    } else if (partType === 'separator') {
      newPart.separator = '-'
    }

    localStructure.value.push(newPart)
    updateOrders()
  }

  function removePart (index: number) {
    localStructure.value.splice(index, 1)
    updateOrders()
  }

  function dragStart (index: number) {
    draggedIndex.value = index
  }

  function drop (targetIndex: number) {
    if (draggedIndex.value === null) return
    const [removed] = localStructure.value.splice(draggedIndex.value, 1)
    localStructure.value.splice(targetIndex, 0, removed)
    draggedIndex.value = null
    updateOrders()
  }

  function updateOrders () {
    for (const [idx, part] of localStructure.value.entries()) {
      part.order = idx
    }
    emit('update:modelValue', localStructure.value)
  }

  const quickPreview = computed(() => {
    return localStructure.value.map(part => {
      if (part.part_type === 'type_code') return props.typeCode || 'XXX'
      if (part.part_type === 'process_code') return 'PPP'
      if (part.part_type === 'year') return 'YYYY'
      if (part.part_type === 'month') return 'MM'
      if (part.part_type === 'sequence') return '0'.repeat(part.length || 3)
      if (part.part_type === 'separator') return part.separator || '-'
      if (part.part_type === 'custom') return part.custom_value || 'XXX'
      return ''
    }).join('')
  })

  watch(() => props.modelValue, newVal => {
    localStructure.value = [...newVal]
  }, { deep: true })
</script>
