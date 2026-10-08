<template>
  <div class="audit-detail-page p-6">
    <div v-if="loading" class="flex justify-center h-96 items-center">
      <div class="inline-block w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin" />
    </div>

    <!-- Contenu -->
    <div v-else-if="audit">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <button class="p-2 hover:bg-gray-100 rounded-lg" @click="router.push('/improvement/audits')">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M15 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
            </svg>
          </button>
          <div>
            <h1 class="text-3xl font-bold">{{ audit.title }}</h1>
            <p class="text-gray-600 mt-1">{{ audit.ref }}</p>
          </div>
        </div>
        <div class="flex gap-2">
          <StatusBadge module="audit" :status="audit.status" />
          <AuditTypeBadge :type="audit.type" />
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex gap-3">
        <button
          v-if="audit.status === 'planned'"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
          @click="startAudit"
        >
          Démarrer l'audit
        </button>
        <button
          v-if="audit.status === 'in_progress'"
          class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700"
          @click="router.push(`/improvement/audits/${audit.id}/conduct`)"
        >
          Réaliser l'audit (terrain)
        </button>
        <button
          v-if="audit.status === 'in_progress'"
          class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
          @click="completeAudit"
        >
          Terminer l'audit
        </button>
        <button
          v-if="audit.status === 'completed'"
          class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
          @click="generateReport"
        >
          Générer le rapport
        </button>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
          <BaseCard>
            <h2 class="text-lg font-semibold mb-3">Informations générales</h2>
            <dl class="grid grid-cols-2 gap-4">
              <div>
                <dt class="text-sm text-gray-600">Date prévue</dt>
                <dd class="font-medium">{{ formatDate(audit.planned_date) }}</dd>
              </div>
            </dl>
          </BaseCard>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <BaseCard>
            <h2 class="text-lg font-semibold mb-4">Conformité</h2>
            <div class="text-center mb-4">
              <div class="text-4xl font-bold" :class="conformityColor">
                {{ audit.conformity_rate || 0 }}%
              </div>
              <p class="text-sm text-gray-600 mt-1">Taux de conformité</p>
            </div>
            <div class="w-full h-3 bg-gray-200 rounded-full overflow-hidden">
              <div
                :class="['h-full transition-all', conformityBarColor]"
                :style="{ width: `${audit.conformity_rate || 0}%` }"
              />
            </div>
          </BaseCard>

          <BaseCard>
            <h2 class="text-lg font-semibold mb-3">Axes QHSE</h2>
            <div class="flex flex-wrap gap-2">
              <span
                v-for="axis in audit.qhse_axes"
                :key="axis"
                class="px-3 py-1 bg-blue-100 text-blue-600 rounded-full text-sm"
              >
                {{ axisLabels[axis] }}
              </span>
            </div>
          </BaseCard>
        </div>
      </div>

      <!-- Stats Cards -->
      <v-row class="mt-6">
        <v-col cols="12" md="3" sm="6">
          <v-card elevation="2">
            <v-card-text>
              <div class="d-flex justify-space-between align-center">
                <div>
                  <div class="text-h4 font-weight-bold text-error">{{ audit.findings_major || 0 }}</div>
                  <div class="text-subtitle-2 text-medium-emphasis">NC majeures</div>
                </div>
                <v-avatar color="error" size="56">
                  <v-icon size="32">mdi-alert-circle</v-icon>
                </v-avatar>
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="3" sm="6">
          <v-card elevation="2">
            <v-card-text>
              <div class="d-flex justify-space-between align-center">
                <div>
                  <div class="text-h4 font-weight-bold text-warning">{{ audit.findings_minor || 0 }}</div>
                  <div class="text-subtitle-2 text-medium-emphasis">NC mineures</div>
                </div>
                <v-avatar color="warning" size="56">
                  <v-icon size="32">mdi-alert</v-icon>
                </v-avatar>
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="3" sm="6">
          <v-card elevation="2">
            <v-card-text>
              <div class="d-flex justify-space-between align-center">
                <div>
                  <div class="text-h4 font-weight-bold text-success">{{ audit.findings_positive || 0 }}</div>
                  <div class="text-subtitle-2 text-medium-emphasis">Points positifs</div>
                </div>
                <v-avatar color="success" size="56">
                  <v-icon size="32">mdi-check-circle</v-icon>
                </v-avatar>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Tabs -->
      <v-card elevation="2">
        <v-tabs v-model="tab" bg-color="primary" dark>
          <v-tab value="general">
            <v-icon start>mdi-information</v-icon>
            Général
          </v-tab>
          <v-tab value="checklist">
            <v-icon start>mdi-clipboard-list</v-icon>
            Checklist
          </v-tab>
          <v-tab value="findings">
            <v-icon start>mdi-alert-circle</v-icon>
            Constatations ({{ audit.findings?.length || 0 }})
          </v-tab>
          <v-tab value="team">
            <v-icon start>mdi-account-group</v-icon>
            Équipe
          </v-tab>
          <v-tab value="processes">
            <v-icon start>mdi-sitemap</v-icon>
            Processus
          </v-tab>
          <v-tab value="report">
            <v-icon start>mdi-file-document</v-icon>
            Rapport
          </v-tab>
        </v-tabs>

        <v-window v-model="tab">
          <!-- Onglet Général -->
          <v-window-item value="general">
            <v-card-text class="pa-6">
              <v-row>
                <v-col cols="12" md="6">
                  <h3 class="text-h6 mb-4">Informations générales</h3>

                  <v-list>
                    <v-list-item>
                      <template #prepend>
                        <v-icon>mdi-calendar</v-icon>
                      </template>
                      <v-list-item-title>Date planifiée</v-list-item-title>
                      <v-list-item-subtitle>
                        {{ formatDate(audit.planned_start_date) }} → {{ formatDate(audit.planned_end_date) }}
                      </v-list-item-subtitle>
                    </v-list-item>

                    <v-list-item v-if="audit.actual_start_date">
                      <template #prepend>
                        <v-icon>mdi-calendar-check</v-icon>
                      </template>
                      <v-list-item-title>Date réelle</v-list-item-title>
                      <v-list-item-subtitle>
                        {{ formatDate(audit.actual_start_date) }}
                        <span v-if="audit.actual_end_date"> → {{ formatDate(audit.actual_end_date) }}</span>
                      </v-list-item-subtitle>
                    </v-list-item>

                    <v-list-item v-if="audit.site">
                      <template #prepend>
                        <v-icon>mdi-map-marker</v-icon>
                      </template>
                      <v-list-item-title>Site</v-list-item-title>
                      <v-list-item-subtitle>{{ audit.site.name }}</v-list-item-subtitle>
                    </v-list-item>

                    <v-list-item v-if="audit.lead_auditor">
                      <template #prepend>
                        <v-avatar color="primary" size="32">
                          <span class="text-caption">{{ getInitials(audit.lead_auditor.name) }}</span>
                        </v-avatar>
                      </template>
                      <v-list-item-title>Auditeur principal</v-list-item-title>
                      <v-list-item-subtitle>{{ audit.lead_auditor.name }}</v-list-item-subtitle>
                    </v-list-item>
                  </v-list>
                </v-col>

                <v-col cols="12" md="6">
                  <h3 class="text-h6 mb-4">Objectifs</h3>
                  <p v-if="audit.objectives" class="text-body-1">{{ audit.objectives }}</p>
                  <p v-else class="text-medium-emphasis">Aucun objectif défini</p>

                  <h3 class="text-h6 mb-4 mt-6">Périmètre</h3>
                  <p v-if="audit.scope" class="text-body-1">{{ audit.scope }}</p>
                  <p v-else class="text-medium-emphasis">Aucun périmètre défini</p>

                  <h3 class="text-h6 mb-4 mt-6">Méthodologie</h3>
                  <p v-if="audit.methodology" class="text-body-1">{{ audit.methodology }}</p>
                  <p v-else class="text-medium-emphasis">Aucune méthodologie définie</p>
                </v-col>
              </v-row>
            </v-card-text>
          </v-window-item>

          <!-- Onglet Checklist -->
          <v-window-item value="checklist">
            <AuditChecklistTab
              :audit-id="audit.id"
              :checklist-items="audit.checklist_items"
              @update="loadAudit"
            />
          </v-window-item>

          <!-- Onglet Constatations -->
          <v-window-item value="findings">
            <AuditFindingsTab
              :audit-id="audit.id"
              :findings="audit.findings"
              @update="loadAudit"
            />
          </v-window-item>

          <!-- Onglet Équipe -->
          <v-window-item value="team">
            <v-card-text class="pa-6">
              <div class="d-flex justify-space-between align-center mb-4">
                <h3 class="text-h6">Équipe d'audit</h3>
                <v-btn color="primary" size="small">
                  <v-icon start>mdi-plus</v-icon>
                  Ajouter
                </v-btn>
              </div>

              <v-row>
                <v-col v-for="auditor in audit.auditors" :key="auditor.id" cols="12" md="6">
                  <v-card variant="outlined">
                    <v-card-text>
                      <div class="d-flex align-center">
                        <v-avatar class="mr-4" color="primary" size="48">
                          <span>{{ getInitials(auditor.name) }}</span>
                        </v-avatar>
                        <div class="flex-grow-1">
                          <div class="font-weight-medium">{{ auditor.name }}</div>
                          <div class="text-caption text-medium-emphasis">{{ auditor.email }}</div>
                          <v-chip v-if="auditor.pivot?.role" class="mt-1" size="x-small">
                            {{ auditor.pivot.role }}
                          </v-chip>
                        </div>
                      </div>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>
            </v-card-text>
          </v-window-item>

          <!-- Onglet Processus -->
          <v-window-item value="processes">
            <v-card-text class="pa-6">
              <h3 class="text-h6 mb-4">Processus audités</h3>

              <v-row>
                <v-col v-for="process in audit.processes" :key="process.id" cols="12" md="4">
                  <v-card variant="outlined">
                    <v-card-text>
                      <div class="d-flex align-center justify-space-between mb-2">
                        <v-chip :color="getProcessTypeColor(process.type)" size="small">
                          {{ process.type }}
                        </v-chip>
                        <v-chip
                          v-if="process.pivot?.risk_level"
                          :color="getRiskColor(process.pivot.risk_level)"
                          size="small"
                        >
                          {{ process.pivot.risk_level }}
                        </v-chip>
                      </div>
                      <h4 class="font-weight-medium">{{ process.name }}</h4>
                      <p class="text-caption text-medium-emphasis mt-1">{{ process.code }}</p>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>
            </v-card-text>
          </v-window-item>

          <!-- Onglet Rapport -->
          <v-window-item value="report">
            <AuditReportTab
              :audit="audit"
              @generate="generateReport"
            />
          </v-window-item>
        </v-window>
      </v-card>
    </div>

    <!-- Dialog Terminer l'audit -->
    <v-dialog v-model="showCompleteDialog" max-width="600">
      <v-card>
        <v-card-title>Terminer l'audit</v-card-title>
        <v-card-text>
          <v-text-field
            v-model="completeForm.actual_end_date"
            class="mb-4"
            label="Date de fin réelle"
            type="date"
            variant="outlined"
          />
          <v-textarea
            v-model="completeForm.conclusions"
            label="Conclusions *"
            rows="4"
            variant="outlined"
          />
          <v-textarea
            v-model="completeForm.recommendations"
            label="Recommandations"
            rows="3"
            variant="outlined"
          />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn @click="showCompleteDialog = false">Annuler</v-btn>
          <v-btn color="success" :loading="completing" @click="completeAudit">Terminer</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup lang="ts">
  import { storeToRefs } from 'pinia'
  import { computed, onMounted, ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import AuditTypeBadge from '@/components/audits/AuditTypeBadge.vue'
  import BaseCard from '@/components/common/BaseCard.vue'
  import StatusBadge from '@/components/common/StatusBadge.vue'
  import { useToast } from '@/modules/shared/composables/useToast'
  import { useAuditStore } from '@/stores/improvement/auditStore'

  const route = useRoute()
  const router = useRouter()
  const toast = useToast()
  const auditStore = useAuditStore()
  const { currentAudit, loading } = storeToRefs(auditStore)
  const audit = computed(() => currentAudit.value)
  const tab = ref('general')
  const showCompleteDialog = ref(false)
  const completing = ref(false)

  const completeForm = ref({
    actual_end_date: '',
    conclusions: '',
    recommendations: '',
  })

  const auditId = computed(() => Number((route.params as any)?.id ?? 0))

  const axisLabels: Record<string, string> = {
    quality: 'Qualité',
    environment: 'Environnement',
    health_safety: 'Santé & Sécurité',
    energy: 'Énergie',
  }

  const conformityColor = computed(() => {
    const rate = audit.value?.conformity_rate || 0
    if (rate >= 90) return 'text-green-600'
    if (rate >= 70) return 'text-orange-600'
    return 'text-red-600'
  })

  const conformityBarColor = computed(() => {
    const rate = audit.value?.conformity_rate || 0
    if (rate >= 90) return 'bg-green-500'
    if (rate >= 70) return 'bg-orange-500'
    return 'bg-red-500'
  })

  async function loadAudit () {
    await auditStore.fetchAudit(auditId.value)
  }

  async function startAudit () {
    if (audit.value) {
      await auditStore.startAudit(audit.value.id)
      await loadAudit()
    }
  }

  async function completeAudit () {
    if (!audit.value) return

    completing.value = true
    try {
      await auditStore.completeAudit(audit.value.id)
      showCompleteDialog.value = false
      await loadAudit()
      toast.success('Audit terminé avec succès')
    } catch {
      toast.error('Erreur lors de la finalisation')
    } finally {
      completing.value = false
    }
  }

  async function generateReport () {
    if (audit.value) {
      const blob = await auditStore.downloadReport(audit.value.id, 'pdf')
      const url = window.URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `Rapport_Audit_${audit.value.ref}.pdf`
      a.click()
      window.URL.revokeObjectURL(url)
    }
  }

  function getInitials (name: string): string {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
  }

  function formatDate (date: string | null): string {
    if (!date) return 'N/A'
    return new Date(date).toLocaleDateString('fr-FR')
  }

  function getProcessTypeColor (type: string) {
    const colors: Record<string, string> = {
      management: 'primary',
      support: 'info',
      realization: 'success',
    }
    return colors[type] || 'grey'
  }

  function getRiskColor (level: string) {
    const colors: Record<string, string> = {
      low: 'success',
      medium: 'warning',
      high: 'error',
    }
    return colors[level] || 'grey'
  }

  onMounted(() => {
    loadAudit()
  })
</script>

<style scoped>
.stat-card {
  transition: transform 0.2s;
}

.stat-card:hover {
  transform: translateY(-2px);
}
</style>
