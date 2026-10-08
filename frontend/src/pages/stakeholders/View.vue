<template>
  <v-container class="pa-6">
    <v-card v-if="stakeholder">
      <v-card-title class="d-flex align-center">
        <v-btn class="mr-3" icon @click="goBack">
          <v-icon>mdi-arrow-left</v-icon>
        </v-btn>
        {{ stakeholder.name }}
        <v-spacer />
        <v-chip class="mr-2" :color="getRelevanceColor(stakeholder.relevance_degree)">
          {{ getRelevanceLabel(stakeholder.relevance_degree) }}
        </v-chip>
        <v-chip :color="getTypeColor(stakeholder.type)">
          {{ getTypeLabel(stakeholder.type) }}
        </v-chip>
      </v-card-title>

      <v-divider />

      <v-card-text class="pa-6">
        <v-row>
          <v-col cols="12" md="6">
            <div class="text-h6 mb-2">Informations</div>
            <v-list density="compact">
              <v-list-item>
                <template #prepend>
                  <v-icon>mdi-tag</v-icon>
                </template>
                <v-list-item-title>Type</v-list-item-title>
                <v-list-item-subtitle>{{ getTypeLabel(stakeholder.type) }}</v-list-item-subtitle>
              </v-list-item>
              <v-list-item>
                <template #prepend>
                  <v-icon>mdi-speedometer</v-icon>
                </template>
                <v-list-item-title>Pertinence</v-list-item-title>
                <v-list-item-subtitle>{{ getRelevanceLabel(stakeholder.relevance_degree) }}</v-list-item-subtitle>
              </v-list-item>
              <v-list-item v-if="stakeholder.responsible">
                <template #prepend>
                  <v-icon>mdi-account</v-icon>
                </template>
                <v-list-item-title>Responsable</v-list-item-title>
                <v-list-item-subtitle>{{ stakeholder.responsible }}</v-list-item-subtitle>
              </v-list-item>
              <v-list-item v-if="stakeholder.deadline">
                <template #prepend>
                  <v-icon>mdi-calendar</v-icon>
                </template>
                <v-list-item-title>Délai</v-list-item-title>
                <v-list-item-subtitle>{{ formatDate(stakeholder.deadline) }}</v-list-item-subtitle>
              </v-list-item>
            </v-list>
          </v-col>

          <v-col cols="12" md="6">
            <div class="text-h6 mb-2">Besoins et Attentes</div>
            <p class="text-body-1">{{ stakeholder.needs_expectations || 'Non renseigné' }}</p>
          </v-col>

          <v-col v-if="stakeholder.requirements" cols="12">
            <div class="text-h6 mb-2">Exigences</div>
            <p class="text-body-1">{{ stakeholder.requirements }}</p>
          </v-col>

          <v-col v-if="stakeholder.actions" cols="12">
            <div class="text-h6 mb-2">Actions Associées</div>
            <p class="text-body-1">{{ stakeholder.actions }}</p>
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
        <v-btn color="primary" @click="editStakeholder">
          <v-icon left>mdi-pencil</v-icon>
          Éditer
        </v-btn>
        <v-btn color="error" @click="deleteStakeholder">
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
  import { useStakeholderStore } from '@/stores/stakeholderStore'

  const router = useRouter()
  const route = useRoute()
  const store = useStakeholderStore()
  const stakeholderId = computed<number | null>(() => {
    const rawId = (route.params as Record<string, unknown>).id
    const idValue = typeof rawId === 'string' ? rawId : (Array.isArray(rawId) ? rawId[0] : null)
    if (!idValue) return null
    const parsed = Number.parseInt(idValue, 10)
    return Number.isNaN(parsed) ? null : parsed
  })

  const stakeholder = computed(() =>
    store.stakeholders.find(s => s.id === stakeholderId.value),
  )

  function getTypeLabel (type?: string) {
    if (!type) return 'N/A'
    const labels: Record<string, string> = {
      client: 'Client',
      fournisseur: 'Fournisseur',
      partenaire: 'Partenaire',
      autorite: 'Autorité',
      concurrent: 'Concurrent',
      interne: 'Interne',
    }
    return labels[type] || type
  }

  function getTypeColor (type?: string) {
    if (!type) return 'grey'
    const colors: Record<string, string> = {
      client: 'blue',
      fournisseur: 'green',
      partenaire: 'purple',
      autorite: 'orange',
      concurrent: 'red',
      interne: 'grey',
    }
    return colors[type] || 'grey'
  }

  function getRelevanceLabel (degree?: string) {
    if (!degree) return 'N/A'
    const labels: Record<string, string> = {
      high: 'Élevée',
      medium: 'Moyenne',
      low: 'Faible',
    }
    return labels[degree] || degree
  }

  function getRelevanceColor (degree?: string) {
    if (!degree) return 'grey'
    const colors: Record<string, string> = {
      high: 'red',
      medium: 'orange',
      low: 'green',
    }
    return colors[degree] || 'grey'
  }

  function formatDate (date: string) {
    if (!date) return 'N/A'
    return new Date(date).toLocaleDateString('fr-FR')
  }

  const goBack = () => router.push('/stakeholders')
  function editStakeholder () {
    if (!stakeholderId.value) return
    router.push(`/stakeholders/${stakeholderId.value}/edit`)
  }
  async function deleteStakeholder () {
    if (!stakeholderId.value) return
    if (confirm(`Supprimer "${stakeholder.value?.name}"?`)) {
      await store.deleteStakeholder(stakeholderId.value)
      goBack()
    }
  }

  onMounted(async () => {
    if (!stakeholder.value) {
      await store.fetchStakeholders()
    }
  })
</script>
