<template>
  <v-card>
    <v-card-title>Historique des Versions</v-card-title>

    <v-card-text>
      <v-timeline density="compact" side="end">
        <v-timeline-item
          v-for="version in versions"
          :key="version.id"
          :dot-color="getVersionColor(version.status)"
          size="small"
        >
          <template #opposite>
            <span class="text-caption text-medium-emphasis">
              {{ formatDate(version.version_date) }}
            </span>
          </template>

          <v-card variant="outlined">
            <v-card-title class="d-flex align-center justify-space-between">
              <div class="d-flex align-center gap-2">
                <span class="text-h6">Version {{ version.version_number }}</span>
                <v-chip
                  v-if="version.is_current"
                  color="primary"
                  size="x-small"
                  variant="flat"
                >
                  Actuelle
                </v-chip>
                <v-chip
                  :color="getVersionColor(version.status)"
                  size="small"
                >
                  {{ getStatusLabel(version.status) }}
                </v-chip>
              </div>

              <!-- Actions -->
              <div v-if="canManageVersions" class="d-flex gap-1">
                <v-btn
                  v-if="version.status === 'draft' && canVerify"
                  color="info"
                  prepend-icon="mdi-check"
                  size="small"
                  variant="tonal"
                  @click="verifyVersion(version)"
                >
                  Vérifier
                </v-btn>
                <v-btn
                  v-if="version.status === 'verified' && canApprove"
                  color="success"
                  prepend-icon="mdi-check-all"
                  size="small"
                  variant="tonal"
                  @click="approveVersion(version)"
                >
                  Approuver
                </v-btn>
              </div>
            </v-card-title>

            <v-card-text>
              <!-- Changes Description -->
              <div class="mb-3">
                <span class="text-caption text-medium-emphasis">Modifications :</span>
                <p class="text-body-2 mt-1">{{ version.changes_description }}</p>
              </div>

              <!-- Workflow Info -->
              <v-row dense>
                <!-- Author -->
                <v-col cols="12" md="4">
                  <div class="d-flex align-center gap-2">
                    <v-icon size="small">mdi-account-edit</v-icon>
                    <div>
                      <div class="text-caption text-medium-emphasis">Auteur</div>
                      <div class="text-body-2">{{ version.author?.name || 'N/A' }}</div>
                    </div>
                  </div>
                </v-col>

                <!-- Verifier -->
                <v-col cols="12" md="4">
                  <div class="d-flex align-center gap-2">
                    <v-icon size="small">mdi-account-check</v-icon>
                    <div>
                      <div class="text-caption text-medium-emphasis">Vérificateur</div>
                      <div class="text-body-2">
                        {{ version.verifier?.name || '-' }}
                        <span v-if="version.verified_at" class="text-caption">
                          ({{ formatDate(version.verified_at) }})
                        </span>
                      </div>
                    </div>
                  </div>
                </v-col>

                <!-- Approver -->
                <v-col cols="12" md="4">
                  <div class="d-flex align-center gap-2">
                    <v-icon size="small">mdi-account-star</v-icon>
                    <div>
                      <div class="text-caption text-medium-emphasis">Approbateur</div>
                      <div class="text-body-2">
                        {{ version.approver?.name || '-' }}
                        <span v-if="version.approved_at" class="text-caption">
                          ({{ formatDate(version.approved_at) }})
                        </span>
                      </div>
                    </div>
                  </div>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>
        </v-timeline-item>
      </v-timeline>

      <!-- Empty State -->
      <v-alert v-if="versions.length === 0" type="info" variant="tonal">
        Aucune version disponible
      </v-alert>

      <!-- Create Version Button -->
      <v-btn
        v-if="canCreateVersion"
        block
        class="mt-4"
        color="primary"
        prepend-icon="mdi-plus"
        variant="outlined"
        @click="showCreateDialog = true"
      >
        Créer une nouvelle version
      </v-btn>
    </v-card-text>

    <!-- Create Version Dialog -->
    <v-dialog v-model="showCreateDialog" max-width="600">
      <v-card>
        <v-card-title>Créer une nouvelle version</v-card-title>
        <v-card-text>
          <v-textarea
            v-model="newVersionDescription"
            autofocus
            label="Description des modifications"
            placeholder="Décrivez les changements apportés dans cette version..."
            rows="4"
            variant="outlined"
          />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn @click="showCreateDialog = false">Annuler</v-btn>
          <v-btn
            color="primary"
            :disabled="!newVersionDescription.trim()"
            @click="createVersion"
          >
            Créer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-card>
</template>

<script setup lang="ts">
  import type { ProcessVersion } from '@/services/processService'
  import { ref } from 'vue'

  interface Props {
    processId: number
    versions: ProcessVersion[]
    canVerify?: boolean
    canApprove?: boolean
    canCreateVersion?: boolean
    canManageVersions?: boolean
  }

  interface Emits {
    (e: 'verify' | 'approve', version: ProcessVersion): void
    (e: 'create', description: string): void
    (e: 'refresh'): void
  }

  withDefaults(defineProps<Props>(), {
    canVerify: false,
    canApprove: false,
    canCreateVersion: true,
    canManageVersions: true,
  })

  const emit = defineEmits<Emits>()

  const showCreateDialog = ref(false)
  const newVersionDescription = ref('')

  function getVersionColor (status: string): string {
    switch (status) {
      case 'draft': { return 'grey'
      }
      case 'verified': { return 'info'
      }
      case 'approved': { return 'success'
      }
      default: { return 'grey'
      }
    }
  }

  function getStatusLabel (status: string): string {
    switch (status) {
      case 'draft': { return 'Brouillon'
      }
      case 'verified': { return 'Vérifiée'
      }
      case 'approved': { return 'Approuvée'
      }
      default: { return status
      }
    }
  }

  function formatDate (date: string | undefined): string {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  }

  function verifyVersion (version: ProcessVersion) {
    emit('verify', version)
  }

  function approveVersion (version: ProcessVersion) {
    emit('approve', version)
  }

  function createVersion () {
    if (newVersionDescription.value.trim()) {
      emit('create', newVersionDescription.value)
      showCreateDialog.value = false
      newVersionDescription.value = ''
    }
  }
</script>
