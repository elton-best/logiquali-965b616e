<template>
  <div class="step-2-process-mapping">
    <v-card class="mb-4" elevation="0" rounded="lg">
      <v-card-title class="d-flex align-center gap-2">
        <v-icon color="primary">mdi-network</v-icon>
        <span>Étape 2: Sélection et mapping des processus</span>
      </v-card-title>
      <v-card-text>
        <p class="text-body-2 text-medium-emphasis mb-4">
          Sélectionnez les processus pour lesquels vous souhaitez définir une nomenclature.
          Vous pouvez créer une nomenclature globale ou par processus.
        </p>

        <v-alert
          v-if="!isValid"
          class="mb-4"
          density="comfortable"
          type="warning"
        >
          ⚠️ Vous devez sélectionner au moins 1 processus pour continuer
        </v-alert>

        <!-- Process Selection -->
        <v-card class="mb-4" elevation="0" rounded="lg" variant="tonal">
          <v-card-title class="text-subtitle-2">
            🎯 Processus disponibles
          </v-card-title>
          <v-card-text>
            <div class="d-flex gap-2 flex-wrap">
              <v-btn
                v-for="process in processes"
                :key="process.id"
                :color="selectedProcess?.id === process.id ? 'primary' : 'default'"
                size="large"
                :variant="selectedProcess?.id === process.id ? 'tonal' : 'outlined'"
                @click="selectedProcess = process"
              >
                {{ getProcessTitle(process) }}
              </v-btn>
            </div>

            <v-divider class="my-4" />

            <div class="mb-3">
              <v-checkbox
                v-model="useGlobalNomenclature"
                label="✓ Créer une nomenclature globale (applicable à tous les processus)"
              />
            </div>
          </v-card-text>
        </v-card>

        <!-- Selected Process Details -->
        <v-card
          v-if="selectedProcess"
          class="mb-4"
          elevation="0"
          rounded="lg"
        >
          <v-card-title class="text-subtitle-2">
            📋 Détails du processus sélectionné
          </v-card-title>
          <v-card-text>
            <v-row dense>
              <v-col cols="12" md="6">
                <div class="text-caption text-medium-emphasis">ID</div>
                <div class="font-weight-bold">{{ selectedProcess.id }}</div>
              </v-col>
              <v-col cols="12" md="6">
                <div class="text-caption text-medium-emphasis">Titre</div>
                <div class="font-weight-bold">{{ getProcessTitle(selectedProcess) }}</div>
              </v-col>
              <v-col v-if="selectedProcess.code" cols="12" md="6">
                <div class="text-caption text-medium-emphasis">Code</div>
                <div class="font-weight-bold">{{ selectedProcess.code }}</div>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>

        <!-- Mapped Processes -->
        <v-card elevation="0" rounded="lg">
          <v-card-title class="text-subtitle-2">
            ✅ Processus mappés ({{ processMapping.size }})
          </v-card-title>
          <v-card-text>
            <v-table
              v-if="processMapping.size > 0"
              class="mapped-table"
              density="comfortable"
            >
              <thead>
                <tr>
                  <th>ID Processus</th>
                  <th>Nom</th>
                  <th>Statut de mapping</th>
                  <th class="text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="[processId, mapping] in processMapping" :key="processId">
                  <td class="font-weight-bold">{{ processId }}</td>
                  <td>{{ getProcessTitleById(processId) }}</td>
                  <td>
                    <v-chip
                      color="success"
                      size="small"
                      variant="tonal"
                    >
                      ✓ Mapped
                    </v-chip>
                  </td>
                  <td class="text-right">
                    <v-btn
                      color="error"
                      icon="mdi-delete-outline"
                      size="small"
                      variant="text"
                      @click="unmapProcess(processId)"
                    />
                  </td>
                </tr>
              </tbody>
            </v-table>
            <div v-else class="text-center py-6 text-medium-emphasis">
              Aucun processus mappé pour l'instant.
              <br>
              <v-btn
                color="primary"
                size="small"
                variant="text"
                @click="mapCurrentProcess"
              >
                Mapper le premier processus →
              </v-btn>
            </div>
          </v-card-text>
        </v-card>

        <!-- Action Button -->
        <v-card class="mt-4" elevation="0" rounded="lg" variant="tonal">
          <v-card-text>
            <v-btn
              v-if="selectedProcess && !isMapped(selectedProcess.id)"
              block
              color="primary"
              prepend-icon="mdi-plus-circle-outline"
              variant="tonal"
              @click="mapCurrentProcess"
            >
              Ajouter ce processus au mapping
            </v-btn>
            <div v-else-if="selectedProcess && isMapped(selectedProcess.id)" class="text-success">
              ✓ Ce processus est déjà mappé
            </div>
          </v-card-text>
        </v-card>
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup lang="ts">
  import { computed, ref } from 'vue'

  interface Props {
    processes: any[]
    selectedProcess: any | null
    processMapping: Map<number, any>
    isValid: boolean
  }

  interface Emits {
    (e: 'update:selectedProcess', value: any): void
    (e: 'update:processMapping', value: Map<number, any>): void
  }

  const props = withDefaults(defineProps<Props>(), {
    processes: () => [],
    selectedProcess: null,
    isValid: false,
  })

  const emit = defineEmits<Emits>()

  // State
  const useGlobalNomenclature = ref(false)

  // Computed
  const selectedProcess = computed({
    get: () => props.selectedProcess,
    set: value => emit('update:selectedProcess', value),
  })

  const processMapping = computed({
    get: () => props.processMapping,
    set: value => emit('update:processMapping', value),
  })

  // Methods
  function getProcessTitle (process: any) {
    return (
      process.title
      || process.name
      || process.nom
      || process.attributes?.title
      || process.attributes?.name
      || process.attributes?.nom
      || `Processus #${process.id}`
    )
  }

  function getProcessTitleById (id: number) {
    const process = props.processes.find(p => Number(p.id) === id)
    return process ? getProcessTitle(process) : `Processus #${id}`
  }

  function isMapped (processId: number): boolean {
    return processMapping.value.has(processId)
  }

  function mapCurrentProcess () {
    if (!selectedProcess.value) return

    const processId = selectedProcess.value.id
    if (!isMapped(processId)) {
      const newMapping = new Map(processMapping.value)
      newMapping.set(processId, {
        processId,
        title: getProcessTitle(selectedProcess.value),
        status: 'pending',
      })
      processMapping.value = newMapping
    }
  }

  function unmapProcess (processId: number) {
    const newMapping = new Map(processMapping.value)
    newMapping.delete(processId)
    processMapping.value = newMapping
  }
</script>

<style scoped lang="scss">
.step-2-process-mapping {
  :deep(.mapped-table) {
    background-color: transparent;

    tbody tr {
      &:hover {
        background-color: rgba(0, 0, 0, 0.02);
      }
    }
  }
}
</style>
