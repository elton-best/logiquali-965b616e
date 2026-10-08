<template>
  <v-card-text class="pa-6">
    <v-row>
      <v-col cols="12" md="8">
        <h3 class="text-h6 mb-4">Rapport d'audit</h3>

        <!-- Preview rapport -->
        <v-card class="mb-6" variant="outlined">
          <v-card-title class="bg-grey-lighten-4">
            <div class="d-flex align-center">
              <v-icon class="mr-2">mdi-file-document</v-icon>
              {{ audit.code }} - {{ audit.title }}
            </div>
          </v-card-title>

          <v-card-text class="pa-6">
            <!-- Section Informations générales -->
            <div class="mb-6">
              <h4 class="text-subtitle-1 font-weight-bold mb-3">1. Informations générales</h4>
              <v-simple-table density="compact">
                <tbody>
                  <tr>
                    <td class="font-weight-medium" width="30%">Référence</td>
                    <td>{{ audit.code }}</td>
                  </tr>
                  <tr>
                    <td class="font-weight-medium">Titre</td>
                    <td>{{ audit.title }}</td>
                  </tr>
                  <tr>
                    <td class="font-weight-medium">Type</td>
                    <td>{{ getTypeLabel(audit.audit_type) }}</td>
                  </tr>
                  <tr>
                    <td class="font-weight-medium">Site</td>
                    <td>{{ audit.site?.name || '-' }}</td>
                  </tr>
                  <tr>
                    <td class="font-weight-medium">Date planifiée</td>
                    <td>{{ formatDate(audit.planned_start_date) }} → {{ formatDate(audit.planned_end_date) }}</td>
                  </tr>
                  <tr v-if="audit.actual_start_date">
                    <td class="font-weight-medium">Date réelle</td>
                    <td>{{ formatDate(audit.actual_start_date) }} → {{ formatDate(audit.actual_end_date) }}</td>
                  </tr>
                  <tr>
                    <td class="font-weight-medium">Auditeur principal</td>
                    <td>{{ audit.lead_auditor?.name || '-' }}</td>
                  </tr>
                </tbody>
              </v-simple-table>
            </div>

            <!-- Section Objectifs -->
            <div v-if="audit.objectives" class="mb-6">
              <h4 class="text-subtitle-1 font-weight-bold mb-3">2. Objectifs</h4>
              <p class="text-body-2">{{ audit.objectives }}</p>
            </div>

            <!-- Section Périmètre -->
            <div v-if="audit.scope" class="mb-6">
              <h4 class="text-subtitle-1 font-weight-bold mb-3">3. Périmètre</h4>
              <p class="text-body-2">{{ audit.scope }}</p>
            </div>

            <!-- Section Résultats -->
            <div class="mb-6">
              <h4 class="text-subtitle-1 font-weight-bold mb-3">4. Résultats</h4>

              <v-row>
                <v-col cols="6" md="3">
                  <v-card color="error" variant="tonal">
                    <v-card-text class="text-center">
                      <div class="text-h4 font-weight-bold">{{ audit.findings_major || 0 }}</div>
                      <div class="text-caption">NC Majeures</div>
                    </v-card-text>
                  </v-card>
                </v-col>
                <v-col cols="6" md="3">
                  <v-card color="warning" variant="tonal">
                    <v-card-text class="text-center">
                      <div class="text-h4 font-weight-bold">{{ audit.findings_minor || 0 }}</div>
                      <div class="text-caption">NC Mineures</div>
                    </v-card-text>
                  </v-card>
                </v-col>
                <v-col cols="6" md="3">
                  <v-card color="info" variant="tonal">
                    <v-card-text class="text-center">
                      <div class="text-h4 font-weight-bold">{{ audit.findings_observation || 0 }}</div>
                      <div class="text-caption">Observations</div>
                    </v-card-text>
                  </v-card>
                </v-col>
                <v-col cols="6" md="3">
                  <v-card color="success" variant="tonal">
                    <v-card-text class="text-center">
                      <div class="text-h4 font-weight-bold">{{ audit.conformity_rate || 0 }}%</div>
                      <div class="text-caption">Conformité</div>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>
            </div>

            <!-- Section Conclusions -->
            <div v-if="audit.conclusions" class="mb-6">
              <h4 class="text-subtitle-1 font-weight-bold mb-3">5. Conclusions</h4>
              <p class="text-body-2">{{ audit.conclusions }}</p>
            </div>

            <!-- Section Recommandations -->
            <div v-if="audit.recommendations" class="mb-6">
              <h4 class="text-subtitle-1 font-weight-bold mb-3">6. Recommandations</h4>
              <p class="text-body-2">{{ audit.recommendations }}</p>
            </div>

            <!-- Signature -->
            <v-divider class="my-6" />
            <div class="text-right text-caption text-medium-emphasis">
              Rapport généré le {{ formatDate(new Date().toISOString()) }}
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Sidebar Actions -->
      <v-col cols="12" md="4">
        <v-card>
          <v-card-title>Actions</v-card-title>
          <v-card-text>
            <v-list>
              <v-list-item :disabled="!canGenerateReport" @click="generatePDF">
                <template #prepend>
                  <v-icon color="error">mdi-file-pdf-box</v-icon>
                </template>
                <v-list-item-title>Générer PDF</v-list-item-title>
                <v-list-item-subtitle>Format standard</v-list-item-subtitle>
              </v-list-item>

              <v-list-item :disabled="!canGenerateReport" @click="generateDOCX">
                <template #prepend>
                  <v-icon color="primary">mdi-file-word</v-icon>
                </template>
                <v-list-item-title>Générer DOCX</v-list-item-title>
                <v-list-item-subtitle>Format éditable</v-list-item-subtitle>
              </v-list-item>

              <v-divider class="my-2" />

              <v-list-item :disabled="!canGenerateReport" @click="sendEmail">
                <template #prepend>
                  <v-icon>mdi-email</v-icon>
                </template>
                <v-list-item-title>Envoyer par email</v-list-item-title>
              </v-list-item>

              <v-list-item :disabled="!canGenerateReport" @click="shareReport">
                <template #prepend>
                  <v-icon>mdi-share-variant</v-icon>
                </template>
                <v-list-item-title>Partager</v-list-item-title>
              </v-list-item>
            </v-list>

            <v-alert v-if="!canGenerateReport" class="mt-4" type="warning" variant="tonal">
              L'audit doit être terminé pour générer le rapport
            </v-alert>
          </v-card-text>
        </v-card>

        <!-- Template sélection -->
        <v-card class="mt-4">
          <v-card-title>Modèle de rapport</v-card-title>
          <v-card-text>
            <v-select
              v-model="selectedTemplate"
              density="compact"
              :items="templates"
              label="Sélectionner un modèle"
              variant="outlined"
            />
          </v-card-text>
        </v-card>

        <!-- Statistiques -->
        <v-card class="mt-4">
          <v-card-title>Statistiques</v-card-title>
          <v-card-text>
            <v-list density="compact">
              <v-list-item>
                <v-list-item-title>Items checklist</v-list-item-title>
                <template #append>
                  <span class="font-weight-bold">{{ audit.checklist_items?.length || 0 }}</span>
                </template>
              </v-list-item>

              <v-list-item>
                <v-list-item-title>Total constatations</v-list-item-title>
                <template #append>
                  <span class="font-weight-bold">{{ audit.findings?.length || 0 }}</span>
                </template>
              </v-list-item>

              <v-list-item>
                <v-list-item-title>NC générées</v-list-item-title>
                <template #append>
                  <span class="font-weight-bold">{{ audit.non_conformities?.length || 0 }}</span>
                </template>
              </v-list-item>

              <v-list-item>
                <v-list-item-title>Processus audités</v-list-item-title>
                <template #append>
                  <span class="font-weight-bold">{{ audit.processes?.length || 0 }}</span>
                </template>
              </v-list-item>
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-card-text>
</template>

<script setup lang="ts">
  import type { Audit } from '@/types/audit'
  import type { AuditType } from '@/types/shared'
  import { computed, ref } from 'vue'
  import { useSnackbar } from '@/composables/useSnackbar'

  const props = defineProps<{
    audit: Audit
  }>()

  const emit = defineEmits<{
    generate: [format: 'pdf' | 'docx']
  }>()

  const { showInfo } = useSnackbar()

  const selectedTemplate = ref('standard')

  const templates = [
    { title: 'Standard ISO', value: 'standard' },
    { title: 'Détaillé', value: 'detailed' },
    { title: 'Synthèse', value: 'summary' },
    { title: 'Exécutif', value: 'executive' },
  ]

  const canGenerateReport = computed(() => {
    return props.audit.status === 'completed'
  })

  function generatePDF () {
    emit('generate', 'pdf')
  }

  function generateDOCX () {
    emit('generate', 'docx')
  }

  function sendEmail () {
    showInfo('Fonctionnalité en développement')
  }

  function shareReport () {
    showInfo('Fonctionnalité en développement')
  }

  function getTypeLabel (type: AuditType): string {
    const labels: Record<AuditType, string> = {
      internal_process: 'Processus',
      internal_system: 'Système',
      internal_product: 'Produit',
      internal_thematic: 'Thématique',
      supplier: 'Fournisseur',
      external_supplier: 'Fournisseur',
      external_certification: 'Certification',
      external_surveillance: 'Surveillance',
      thematic: 'Thématique',
    }
    return labels[type] || type
  }

  function formatDate (date: string | undefined): string {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR')
  }
</script>
