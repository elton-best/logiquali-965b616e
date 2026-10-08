<template>
  <v-dialog v-model="modelValue" max-width="900" scrollable>
    <v-card v-if="selectedSiteDetails" class="wizard-card">
      <v-card-title class="dialog-header">
        <div class="dialog-title-group">
          <v-icon color="primary">mdi-office-building</v-icon>
          <span>Détails du site</span>
        </div>
        <button class="btn-close" @click="emit('close')">
          <v-icon>mdi-close</v-icon>
        </button>
      </v-card-title>

      <v-divider />

      <v-card-text class="dialog-content details-content">
        <v-card class="mb-6" variant="tonal">
          <v-card-title class="d-flex align-center gap-2">
            <v-icon color="primary">mdi-information</v-icon>
            Informations générales
          </v-card-title>
          <v-divider />
          <v-card-text>
            <v-row>
              <v-col cols="12" md="6">
                <div class="mb-4">
                  <div class="text-caption text-medium-emphasis">Nom du site</div>
                  <div class="text-h6">{{ selectedSiteDetails.name }}</div>
                </div>
                <div class="mb-4">
                  <div class="text-caption text-medium-emphasis">Localisation</div>
                  <div class="text-body-1">{{ selectedSiteDetails.location }}</div>
                </div>
                <div class="mb-4">
                  <div class="text-caption text-medium-emphasis">Référence</div>
                  <div class="text-body-1">{{ selectedSiteDetails.ref || "Non définie" }}</div>
                </div>
              </v-col>
              <v-col cols="12" md="6">
                <div class="mb-4">
                  <div class="text-caption text-medium-emphasis">Type</div>
                  <v-chip :color="selectedSiteDetails.is_headquarter ? 'primary' : 'default'" size="small">
                    <v-icon start>
                      {{ selectedSiteDetails.is_headquarter ? "mdi-office-building-marker" : "mdi-office-building" }}
                    </v-icon>
                    {{ selectedSiteDetails.is_headquarter ? "Siège social" : "Site secondaire" }}
                  </v-chip>
                </div>
                <div class="mb-4">
                  <div class="text-caption text-medium-emphasis">Statut</div>
                  <v-chip :color="selectedSiteDetails.is_active ? 'success' : 'error'" size="small">
                    <v-icon start>
                      {{ selectedSiteDetails.is_active ? "mdi-check-circle" : "mdi-close-circle" }}
                    </v-icon>
                    {{ selectedSiteDetails.is_active ? "Actif" : "Inactif" }}
                  </v-chip>
                </div>
                <div class="mb-4">
                  <div class="text-caption text-medium-emphasis">Date de création</div>
                  <div class="text-body-1">
                    {{ formatDate(selectedSiteDetails.created_at) }}
                  </div>
                </div>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>

        <v-card class="mb-6" color="info" variant="tonal">
          <v-card-title class="d-flex align-center gap-2">
            <v-icon color="info">mdi-chart-bar</v-icon>
            Statistiques
          </v-card-title>
          <v-divider />
          <v-card-text>
            <v-row>
              <v-col cols="6" md="3">
                <div class="text-center">
                  <div class="text-h4 font-weight-bold text-info">
                    {{ selectedSiteDetails.users_count || 0 }}
                  </div>
                  <div class="text-caption text-medium-emphasis">Utilisateurs</div>
                </div>
              </v-col>
              <v-col cols="6" md="3">
                <div class="text-center">
                  <div class="text-h4 font-weight-bold text-info">
                    {{ siteDetailsLoading ? "..." : siteSubscription?.length || 0 }}
                  </div>
                  <div class="text-caption text-medium-emphasis">Abonnements</div>
                </div>
              </v-col>
              <v-col cols="6" md="3">
                <div class="text-center">
                  <div class="text-h4 font-weight-bold text-info">
                    {{ selectedSiteDetails.processes_count || 0 }}
                  </div>
                  <div class="text-caption text-medium-emphasis">Processus</div>
                </div>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>

        <v-card color="success" variant="tonal">
          <v-card-title class="d-flex align-center gap-2">
            <v-icon color="success">mdi-card-account-details</v-icon>
            Abonnement
          </v-card-title>
          <v-divider />
          <v-card-text>
            <div v-if="siteDetailsLoading" class="text-center py-6">
              <UnifiedLoader
                description="Récupération des informations d'abonnement du site"
                :show-skeleton="true"
                title="Chargement des abonnements..."
                variant="local"
              />
            </div>

            <div v-else-if="siteSubscription && siteSubscription.length > 0">
              <div v-for="(sub, index) in siteSubscription" :key="sub.id || index" class="mb-6">
                <v-card class="mb-4" variant="outlined">
                  <v-card-text>
                    <v-row>
                      <v-col cols="12" md="6">
                        <div class="mb-4">
                          <div class="text-caption text-medium-emphasis">Offre</div>
                          <div class="text-h6">{{ sub.offer_name || "Non définie" }}</div>
                          <div v-if="sub.norms_label" class="text-caption text-medium-emphasis mt-1">
                            Norme(s): {{ sub.norms_label }}
                          </div>
                        </div>
                        <div class="mb-4">
                          <div class="text-caption text-medium-emphasis">Date de début</div>
                          <div class="text-body-1">{{ formatDate(sub.start_date) }}</div>
                        </div>
                      </v-col>
                      <v-col cols="12" md="6">
                        <div class="mb-4">
                          <div class="text-caption text-medium-emphasis">Date d'expiration</div>
                          <div class="text-body-1">{{ formatDate(sub.expiration_date) }}</div>
                        </div>
                        <div class="mb-4">
                          <div class="text-caption text-medium-emphasis">Jours restants</div>
                          <v-chip :color="getDaysRemainingColor(sub.days_remaining)" size="small">
                            {{ sub.days_remaining }} jours
                          </v-chip>
                        </div>
                      </v-col>
                    </v-row>
                    <div class="mb-4">
                      <div class="text-caption text-medium-emphasis">Statut</div>
                      <v-chip class="mt-2" :color="sub.is_active ? 'success' : 'error'">
                        <v-icon start>
                          {{ sub.is_active ? "mdi-check-circle" : "mdi-close-circle" }}
                        </v-icon>
                        {{ sub.is_active ? "Actif" : "Inactif" }}
                      </v-chip>
                    </div>
                  </v-card-text>
                </v-card>
              </div>
            </div>

            <v-alert v-else type="warning" variant="tonal">
              Aucun abonnement actif pour ce site
            </v-alert>
          </v-card-text>
        </v-card>
      </v-card-text>

      <v-divider />

      <v-card-actions class="dialog-actions">
        <v-spacer />
        <button class="btn-secondary" @click="emit('close')">Fermer</button>
        <button v-if="canUpdateSite" class="btn-primary" @click="emit('edit')">
          <v-icon size="20">mdi-pencil</v-icon>
          Modifier le site
        </button>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import type { Site } from '@/services/siteService'
  import { computed } from 'vue'
  import UnifiedLoader from '@/modules/shared/components/ui/UnifiedLoader.vue'

  type SubscriptionSummary = {
    id?: number | string
    offer_name?: string
    norms_label?: string
    start_date?: string
    expiration_date?: string
    is_active?: boolean
    days_remaining?: number
  }

  const props = defineProps<{
    modelValue: boolean
    selectedSiteDetails: Site | null
    siteDetailsLoading: boolean
    siteSubscription: SubscriptionSummary[] | null
    canUpdateSite: boolean
    formatDate: (date: string | null | undefined) => string
    getDaysRemainingColor: (days: number) => string
  }>()

  const emit = defineEmits<{
    (event: 'update:modelValue', value: boolean): void
    (event: 'close'): void
    (event: 'edit'): void
  }>()

  const modelValue = computed({
    get: () => props.modelValue,
    set: value => emit('update:modelValue', value),
  })
</script>

<style scoped>
.wizard-card {
  position: relative;
  border-radius: 18px;
  overflow: hidden;
}

.dialog-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
  border-bottom: 1px solid #e2e8f0;
  font-size: 1.05rem;
  font-weight: 700;
  color: #0f172a;
}

.dialog-title-group {
  display: inline-flex;
  align-items: center;
  gap: 10px;
}

.btn-close {
  border: none;
  background: transparent;
  color: #64748b;
  cursor: pointer;
  width: 34px;
  height: 34px;
  border-radius: 8px;
  display: grid;
  place-items: center;
}

.btn-close:hover {
  background: #f1f5f9;
  color: #1e293b;
}

.dialog-content {
  max-height: 70vh;
  overflow-y: auto;
  padding: 18px 22px;
}

.details-content {
  padding: 22px;
}

.dialog-actions {
  padding: 14px 20px;
  border-top: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  gap: 10px;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background: linear-gradient(135deg, #5b8dd9 0%, #4a71b0 100%);
  color: #fff;
  border: none;
  padding: 10px 16px;
  border-radius: 10px;
  font-weight: 700;
  cursor: pointer;
  transition:
    transform 0.15s ease,
    box-shadow 0.15s ease;
}

.btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 8px 20px rgba(74, 113, 176, 0.25);
}

.btn-primary:disabled {
  opacity: 0.55;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.btn-secondary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background: #fff;
  color: #334155;
  border: 1px solid #d0d7e2;
  padding: 10px 14px;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-secondary:hover {
  background: #f8fafc;
  border-color: #b8c4d6;
}
</style>
