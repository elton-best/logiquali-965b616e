<template>
  <v-card>
    <v-card-title class="d-flex align-center justify-space-between">
      <span>Séquences du Processus</span>
      <v-btn
        color="primary"
        prepend-icon="mdi-plus"
        size="small"
        @click="addSequence"
      >
        Ajouter une séquence
      </v-btn>
    </v-card-title>

    <v-card-text>
      <v-alert v-if="sequences.length === 0" class="mb-4" type="info" variant="tonal">
        Aucune séquence définie. Ajoutez des séquences pour décrire le workflow de ce processus.
      </v-alert>

      <!-- Sequences List -->
      <draggable
        v-model="sequences"
        handle=".drag-handle"
        item-key="id"
        @end="onDragEnd"
      >
        <template #item="{ element, index }">
          <v-card class="mb-3" variant="outlined">
            <v-card-text>
              <div class="d-flex align-start gap-3">
                <!-- Drag Handle -->
                <v-icon class="drag-handle mt-2" style="cursor: move">
                  mdi-drag
                </v-icon>

                <!-- Order Badge -->
                <v-chip class="mt-1" color="primary" size="small">
                  {{ index + 1 }}
                </v-chip>

                <!-- Content -->
                <div class="flex-grow-1">
                  <v-row>
                    <!-- Input -->
                    <v-col cols="12" md="4">
                      <v-textarea
                        :ref="el => setSequenceInputRef(el, index)"
                        v-model="element.input_description"
                        density="compact"
                        hide-details
                        label="Entrées"
                        placeholder="Ex: Commande client, données..."
                        rows="2"
                        variant="outlined"
                      />
                    </v-col>

                    <!-- Activity -->
                    <v-col cols="12" md="4">
                      <v-textarea
                        v-model="element.activity_description"
                        density="compact"
                        hide-details
                        label="Activité"
                        placeholder="Ex: Analyser, traiter..."
                        rows="2"
                        variant="outlined"
                      />
                    </v-col>

                    <!-- Output -->
                    <v-col cols="12" md="4">
                      <v-textarea
                        v-model="element.output_description"
                        density="compact"
                        hide-details
                        label="Sorties"
                        placeholder="Ex: Rapport, produit fini..."
                        rows="2"
                        variant="outlined"
                      />
                    </v-col>

                    <!-- Responsible -->
                    <v-col cols="12" md="6">
                      <v-autocomplete
                        v-model="element.responsible_user_id"
                        clearable
                        density="compact"
                        hide-details
                        item-title="name"
                        item-value="id"
                        :items="users"
                        label="Responsable (optionnel)"
                        placeholder="Sélectionner un responsable"
                        variant="outlined"
                      />
                    </v-col>

                    <!-- Duration -->
                    <v-col cols="12" md="6">
                      <v-text-field
                        v-model.number="element.duration_minutes"
                        clearable
                        density="compact"
                        hide-details
                        label="Durée (minutes)"
                        placeholder="Ex: 30"
                        suffix="min"
                        type="number"
                        variant="outlined"
                      />
                    </v-col>
                  </v-row>

                  <!-- Documents (placeholder for future implementation) -->
                  <div v-if="element.documents && element.documents.length > 0" class="mt-2">
                    <v-chip-group>
                      <v-chip
                        v-for="doc in element.documents"
                        :key="doc.id"
                        closable
                        size="small"
                        @click:close="removeDocument(element, doc.id)"
                      >
                        <v-icon start>mdi-file-document</v-icon>
                        {{ doc.title }}
                      </v-chip>
                    </v-chip-group>
                  </div>
                </div>

                <!-- Actions -->
                <div class="d-flex flex-column gap-1">
                  <v-btn
                    v-if="element.id"
                    color="success"
                    icon="mdi-content-save"
                    size="x-small"
                    variant="text"
                    @click="saveSequence(element)"
                  />
                  <v-btn
                    color="error"
                    icon="mdi-delete"
                    size="x-small"
                    variant="text"
                    @click="deleteSequence(index, element)"
                  />
                </div>
              </div>
            </v-card-text>
          </v-card>
        </template>
      </draggable>

      <!-- Save All Button -->
      <v-btn
        v-if="hasUnsavedChanges"
        block
        class="mt-4"
        color="primary"
        prepend-icon="mdi-content-save-all"
        @click="saveAllSequences"
      >
        Enregistrer toutes les modifications
      </v-btn>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import type { ProcessSequence } from '@/services/processService'
  import { ref, watch } from 'vue'
  import draggable from 'vuedraggable'
  import { focusTopInsertedField } from '@/composables/useDynamicTopInsertFocus'

  interface Props {
    processId: number
    sequences: ProcessSequence[]
    users?: any[]
  }

  interface Emits {
    (e: 'update:sequences' | 'reorder', value: ProcessSequence[]): void
    (e: 'save' | 'delete', sequence: ProcessSequence): void
  }

  const props = withDefaults(defineProps<Props>(), {
    users: () => [],
  })

  const emit = defineEmits<Emits>()

  const sequences = ref<ProcessSequence[]>([...props.sequences])
  const hasUnsavedChanges = ref(false)
  const sequenceInputRefs = ref<any[]>([])

  // Watch for external changes
  watch(() => props.sequences, newVal => {
    sequences.value = [...newVal]
  }, { deep: true })

  // Watch for local changes
  watch(sequences, () => {
    hasUnsavedChanges.value = true
    emit('update:sequences', sequences.value)
  }, { deep: true })

  function setSequenceInputRef (element: any, index: number) {
    sequenceInputRefs.value[index] = element
  }

  function addSequence () {
    const newSeq: Partial<ProcessSequence> = {
      process_id: props.processId,
      sequence_order: 1,
      input_description: '',
      activity_description: '',
      output_description: '',
      responsible_user_id: undefined,
      duration_minutes: undefined,
    }
    sequences.value.unshift(newSeq as ProcessSequence)
    for (const [index, seq] of sequences.value.entries()) {
      seq.sequence_order = index + 1
    }
    void focusTopInsertedField(sequenceInputRefs.value, 0)
  }

  function saveSequence (sequence: ProcessSequence) {
    emit('save', sequence)
  }

  async function saveAllSequences () {
    for (const seq of sequences.value) {
      if (!seq.id || hasChanges(seq)) {
        emit('save', seq)
      }
    }
    hasUnsavedChanges.value = false
  }

  function deleteSequence (index: number, sequence: ProcessSequence) {
    if (sequence.id) {
      emit('delete', sequence)
    }
    sequences.value.splice(index, 1)
    // Reorder remaining sequences
    for (const [idx, seq] of sequences.value.entries()) {
      seq.sequence_order = idx + 1
    }
  }

  function onDragEnd () {
    // Update sequence orders
    for (const [index, seq] of sequences.value.entries()) {
      seq.sequence_order = index + 1
    }
    emit('reorder', sequences.value)
    hasUnsavedChanges.value = true
  }

  function removeDocument (sequence: ProcessSequence, docId: number) {
    if (sequence.documents) {
      sequence.documents = sequence.documents.filter(d => d.id !== docId)
    }
  }

  function hasChanges (sequence: ProcessSequence): boolean {
    const original = props.sequences.find(s => s.id === sequence.id)
    if (!original) return true
    return JSON.stringify(original) !== JSON.stringify(sequence)
  }
</script>

<style scoped>
.drag-handle {
  cursor: move;
}

.drag-handle:hover {
  color: rgb(var(--v-theme-primary));
}
</style>
