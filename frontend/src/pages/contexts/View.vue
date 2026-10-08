<template>
  <v-container class="pa-6">
    <v-card v-if="context">
      <v-card-title class="d-flex align-center">
        <v-btn class="mr-3" icon @click="goBack">
          <v-icon>mdi-arrow-left</v-icon>
        </v-btn>
        {{ context.title }}
        <v-spacer />
        <v-chip class="mr-2" :color="context.type === 'swot' ? 'blue' : 'green'">
          {{ context.type.toUpperCase() }}
        </v-chip>
        <v-chip>{{ context.year }}</v-chip>
      </v-card-title>

      <v-divider />

      <v-card-text class="pa-6">
        <!-- Info Card -->
        <v-row>
          <v-col cols="12">
            <v-card class="mb-4" variant="outlined">
              <v-card-title class="text-subtitle-1">
                <v-icon class="mr-2">mdi-information</v-icon>
                Informations
              </v-card-title>
              <v-divider />
              <v-list density="compact">
                <v-list-item>
                  <v-list-item-title>Année</v-list-item-title>
                  <v-list-item-subtitle>{{ context.year }}</v-list-item-subtitle>
                </v-list-item>
                <v-list-item>
                  <v-list-item-title>Catégorie</v-list-item-title>
                  <v-list-item-subtitle>{{ getCategoryLabel(context.category) }}</v-list-item-subtitle>
                </v-list-item>
                <v-list-item v-if="context.description">
                  <v-list-item-title>Description</v-list-item-title>
                  <v-list-item-subtitle>{{ context.description }}</v-list-item-subtitle>
                </v-list-item>
                <v-list-item>
                  <v-list-item-title>Impact</v-list-item-title>
                  <v-list-item-subtitle>
                    <v-chip :color="getImpactColor(context.impact)" size="small">
                      {{ getImpactLabel(context.impact) }}
                    </v-chip>
                  </v-list-item-subtitle>
                </v-list-item>
              </v-list>
            </v-card>
          </v-col>
        </v-row>

        <!-- SWOT Matrix -->
        <v-row v-if="context.type === 'swot'">
          <v-col cols="12">
            <SwotMatrix
              :opportunities="context.swot_opportunities"
              :show-strategies="true"
              :strengths="context.swot_strengths"
              :threats="context.swot_threats"
              :weaknesses="context.swot_weaknesses"
              :year="context.year"
            />
          </v-col>
        </v-row>

        <!-- PESTEL Analysis -->
        <v-row v-if="context.type === 'pestel'">
          <v-col cols="12">
            <v-card>
              <v-card-title class="d-flex align-center">
                <v-icon class="mr-2" color="green">mdi-radar</v-icon>
                Analyse PESTEL
              </v-card-title>
              <v-divider />
              <v-card-text>
                <v-row>
                  <v-col cols="12" md="6">
                    <v-card color="blue-lighten-5" variant="outlined">
                      <v-card-title class="d-flex align-center">
                        <v-icon class="mr-2" color="blue">mdi-bank</v-icon>
                        Politique
                      </v-card-title>
                      <v-divider />
                      <v-card-text>
                        <p class="text-body-2" style="white-space: pre-line;">{{ context.pestel_political || 'Non renseigné' }}</p>
                      </v-card-text>
                    </v-card>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-card color="green-lighten-5" variant="outlined">
                      <v-card-title class="d-flex align-center">
                        <v-icon class="mr-2" color="green">mdi-currency-eur</v-icon>
                        Économique
                      </v-card-title>
                      <v-divider />
                      <v-card-text>
                        <p class="text-body-2" style="white-space: pre-line;">{{ context.pestel_economic || 'Non renseigné' }}</p>
                      </v-card-text>
                    </v-card>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-card color="purple-lighten-5" variant="outlined">
                      <v-card-title class="d-flex align-center">
                        <v-icon class="mr-2" color="purple">mdi-account-group</v-icon>
                        Social
                      </v-card-title>
                      <v-divider />
                      <v-card-text>
                        <p class="text-body-2" style="white-space: pre-line;">{{ context.pestel_social || 'Non renseigné' }}</p>
                      </v-card-text>
                    </v-card>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-card color="cyan-lighten-5" variant="outlined">
                      <v-card-title class="d-flex align-center">
                        <v-icon class="mr-2" color="cyan">mdi-cog-outline</v-icon>
                        Technologique
                      </v-card-title>
                      <v-divider />
                      <v-card-text>
                        <p class="text-body-2" style="white-space: pre-line;">{{ context.pestel_technological || 'Non renseigné' }}</p>
                      </v-card-text>
                    </v-card>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-card color="green-lighten-5" variant="outlined">
                      <v-card-title class="d-flex align-center">
                        <v-icon class="mr-2" color="green">mdi-leaf</v-icon>
                        Environnemental
                      </v-card-title>
                      <v-divider />
                      <v-card-text>
                        <p class="text-body-2" style="white-space: pre-line;">{{ context.pestel_environmental || 'Non renseigné' }}</p>
                      </v-card-text>
                    </v-card>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-card color="brown-lighten-5" variant="outlined">
                      <v-card-title class="d-flex align-center">
                        <v-icon class="mr-2" color="brown">mdi-gavel</v-icon>
                        Légal
                      </v-card-title>
                      <v-divider />
                      <v-card-text>
                        <p class="text-body-2" style="white-space: pre-line;">{{ context.pestel_legal || 'Non renseigné' }}</p>
                      </v-card-text>
                    </v-card>
                  </v-col>
                </v-row>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </v-card-text>

      <v-divider />

      <v-card-actions class="pa-4">
        <v-btn variant="text" @click="goBack">
          <v-icon left>mdi-arrow-left</v-icon>
          Retour
        </v-btn>
        <v-spacer />
        <v-btn color="primary" @click="editContext">
          <v-icon left>mdi-pencil</v-icon>
          Éditer
        </v-btn>
        <v-btn color="error" @click="deleteContext">
          <v-icon left>mdi-delete</v-icon>
          Supprimer
        </v-btn>
      </v-card-actions>
    </v-card>

    <v-card v-else>
      <v-card-text class="text-center pa-8">
        <v-progress-circular color="primary" indeterminate />
        <p class="mt-4">Chargement...</p>
      </v-card-text>
    </v-card>
  </v-container>
</template>

<script setup lang="ts">
  import { computed, onMounted } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import SwotMatrix from '@/components/contexts/SwotMatrix.vue'
  import { useContextStore } from '@/stores/contextStore'

  const router = useRouter()
  const route = useRoute()
  const store = useContextStore()
  const routeId = computed(() => Number((route.params as any).id ?? 0))

  const context = computed<Record<string, any> | null>(() => {
    const found = store.contexts.find((c: any) => Number(c?.id) === routeId.value)
    return found ? (found as Record<string, any>) : null
  })

  function getCategoryLabel (category?: string) {
    const labels: Record<string, string> = {
      organizational: 'Organisationnel',
      strategic: 'Stratégique',
      operational: 'Opérationnel',
      market: 'Marché',
    }
    return labels[category || ''] || category || '-'
  }

  function getImpactLabel (impact?: string) {
    const labels: Record<string, string> = {
      positive: 'Positif',
      negative: 'Négatif',
      neutral: 'Neutre',
    }
    return labels[impact || ''] || impact || '-'
  }

  function getImpactColor (impact?: string) {
    const colors: Record<string, string> = {
      positive: 'green',
      negative: 'red',
      neutral: 'grey',
    }
    return colors[impact || ''] || 'grey'
  }

  const goBack = () => router.push('/contexts')
  const editContext = () => router.push(`/contexts/${routeId.value}/edit`)
  async function deleteContext () {
    if (confirm(`Supprimer "${context.value?.title}"?`)) {
      await store.deleteContext(routeId.value)
      goBack()
    }
  }

  onMounted(async () => {
    if (!context.value) {
      await store.fetchContexts()
    }
  })
</script>
