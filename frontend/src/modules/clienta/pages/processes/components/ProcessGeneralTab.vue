<template>
  <v-card-text class="pa-6">
    <v-row>
      <!-- Left Column -->
      <v-col cols="12" md="6">
        <!-- Finalité -->
        <div class="mb-6">
          <h3 class="text-h6 mb-3">
            <v-icon class="mr-2">mdi-bullseye-arrow</v-icon>
            Finalité
          </h3>
          <v-card variant="outlined">
            <v-card-text>
              {{ process.finalite || 'Non définie' }}
            </v-card-text>
          </v-card>
        </div>

        <!-- Type & Category -->
        <div class="mb-6">
          <h3 class="text-h6 mb-3">Classification</h3>
          <v-row dense>
            <v-col cols="6">
              <v-card variant="outlined">
                <v-card-text>
                  <div class="text-caption text-medium-emphasis">Type</div>
                  <v-chip class="mt-1" :color="getTypeColor(process.category)" size="small">
                    {{ getTypeLabel(process.category) }}
                  </v-chip>
                </v-card-text>
              </v-card>
            </v-col>
            <v-col cols="6">
              <v-card variant="outlined">
                <v-card-text>
                  <div class="text-caption text-medium-emphasis">Niveau</div>
                  <div class="font-weight-bold mt-1">Niveau {{ process.level }}</div>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>
        </div>

        <!-- Aspects QHSE -->
        <div class="mb-6">
          <h3 class="text-h6 mb-3">Aspects QHSE</h3>
          <div class="d-flex gap-2">
            <v-chip
              v-if="process.aspect_qualite"
              color="primary"
              prepend-icon="mdi-quality-high"
              size="small"
            >
              Qualité
            </v-chip>
            <v-chip
              v-if="process.aspect_environnement"
              color="success"
              prepend-icon="mdi-leaf"
              size="small"
            >
              Environnement
            </v-chip>
            <v-chip
              v-if="process.aspect_sante_securite"
              color="error"
              prepend-icon="mdi-shield-check"
              size="small"
            >
              Santé & Sécurité
            </v-chip>
          </div>
        </div>
      </v-col>

      <!-- Right Column -->
      <v-col cols="12" md="6">
        <!-- Workflow Actions -->
        <ProcessWorkflowActions
          class="mb-6"
          :current-user="currentUser"
          :process="process"
          @edit="emit('edit')"
          @reject="emit('reject', $event)"
          @validate="emit('validate', $event)"
          @verify="emit('verify', $event)"
        />

        <!-- Workflow Stepper -->
        <ProcessWorkflowStepper class="mb-6" :process="process" />

        <!-- Pilot & Co-pilot -->
        <div class="mb-6">
          <h3 class="text-h6 mb-3">
            <v-icon class="mr-2">mdi-account-star</v-icon>
            Responsabilités
          </h3>
          <v-row dense>
            <v-col cols="12">
              <v-card variant="outlined">
                <v-card-text>
                  <div class="d-flex align-center gap-3">
                    <v-avatar color="primary" size="48">
                      <v-icon>mdi-account</v-icon>
                    </v-avatar>
                    <div class="flex-grow-1">
                      <div class="text-caption text-medium-emphasis">Pilote</div>
                      <v-select
                        v-model="pilotValue"
                        density="compact"
                        :disabled="usersLoading || savingPilot || users.length === 0"
                        hide-details="auto"
                        item-title="name"
                        item-value="id"
                        :items="users"
                        label="Sélectionner un collaborateur"
                        :loading="usersLoading || savingPilot"
                        variant="outlined"
                      />
                      <div v-if="!usersLoading && users.length === 0" class="text-caption text-medium-emphasis mt-1">
                        Aucun collaborateur disponible pour ce site.
                      </div>
                      <div class="text-caption text-medium-emphasis mt-1">
                        {{ process.pilot?.name || 'Non assigné' }}
                      </div>
                    </div>
                  </div>
                </v-card-text>
              </v-card>
            </v-col>
            <v-col v-if="process.copilot" cols="12">
              <v-card variant="outlined">
                <v-card-text>
                  <div class="d-flex align-center gap-3">
                    <v-avatar color="info" size="48">
                      <v-icon>mdi-account</v-icon>
                    </v-avatar>
                    <div>
                      <div class="text-caption text-medium-emphasis">Co-pilote</div>
                      <div class="font-weight-bold">
                        {{ process.copilot.name }}
                      </div>
                    </div>
                  </div>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>
        </div>

        <!-- Interactions -->
        <div class="mb-6">
          <h3 class="text-h6 mb-3">
            <v-icon class="mr-2">mdi-swap-horizontal</v-icon>
            Interactions
          </h3>
          <v-card variant="outlined">
            <v-card-text>
              <div v-if="process.interfaces && process.interfaces.length > 0">
                <v-chip-group column>
                  <v-chip v-for="(iface, index) in process.interfaces" :key="index" size="small">
                    {{ iface }}
                  </v-chip>
                </v-chip-group>
              </div>
              <div v-else class="text-medium-emphasis">
                Aucune interface définie
              </div>
            </v-card-text>
          </v-card>
        </div>

        <!-- Normes ISO -->
        <div v-if="process.normes_iso && process.normes_iso.length > 0">
          <h3 class="text-h6 mb-3">
            <v-icon class="mr-2">mdi-certificate</v-icon>
            Normes ISO
          </h3>
          <div class="d-flex gap-2">
            <v-chip v-for="(norme, index) in process.normes_iso" :key="index" color="purple" size="small">
              ISO {{ norme }}
            </v-chip>
          </div>
        </div>
      </v-col>
    </v-row>
  </v-card-text>
</template>

<script setup lang="ts">
  import { computed } from 'vue'
  import ProcessWorkflowActions from '@/modules/clienta/components/processes/ProcessWorkflowActions.vue'
  import ProcessWorkflowStepper from '@/modules/clienta/components/processes/ProcessWorkflowStepper.vue'

  type ProcessData = {
    finalite?: string
    category?: string
    level?: string | number
    aspect_qualite?: boolean
    aspect_environnement?: boolean
    aspect_sante_securite?: boolean
    pilot?: { name?: string } | null
    copilot?: { name?: string } | null
    interfaces?: string[]
    normes_iso?: Array<string | number>
  }

  const props = defineProps<{
    process: ProcessData
    users: Array<{ id: number, name: string }>
    usersLoading: boolean
    savingPilot: boolean
    selectedPilotId: number | null
    currentUser: any
    getTypeColor: (type?: string) => string
    getTypeLabel: (type?: string) => string
  }>()

  const emit = defineEmits<{
    (event: 'update:selectedPilotId', value: number | null): void
    (event: 'edit'): void
    (event: 'verify', comment?: string): void
    (event: 'validate', comment?: string): void
    (event: 'reject', reason: string): void
  }>()

  const pilotValue = computed({
    get: () => props.selectedPilotId,
    set: value => emit('update:selectedPilotId', value),
  })
</script>
