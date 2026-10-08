<template>
  <v-card v-if="suggestion" class="sticky-card" elevation="2" rounded="lg">
    <v-card-title class="text-subtitle-1 font-weight-bold">{{ suggestion.reference }}</v-card-title>
    <v-card-text>
      <v-alert class="mb-3" density="comfortable" type="info" variant="tonal">
        Processus concerné: <strong>{{ suggestion.process }}</strong>
      </v-alert>

      <div class="section-title">Suggestion</div>
      <div class="detail-line"><strong>Titre:</strong> {{ suggestion.title }}</div>
      <div v-if="suggestion.normes && suggestion.normes.length > 0" class="detail-line mt-1">
        <strong>Norme(s):</strong>
        <div class="d-flex flex-wrap ga-1 mt-1">
          <v-chip v-for="norm in suggestion.normes" :key="norm" color="primary" size="x-small" variant="tonal">
            {{ norm }}
          </v-chip>
        </div>
      </div>
      <div class="detail-block"><strong>Description:</strong><br>{{ suggestion.description }}</div>

      <v-divider class="my-3" />

      <div class="section-title">Proposition collaborateur</div>
      <div class="detail-line"><strong>Proposé par:</strong> {{ suggestion.proposer }}</div>
      <div class="detail-line"><strong>Date:</strong> {{ suggestion.created_at }}</div>
      <div class="detail-line"><strong>Impact attendu:</strong> {{ suggestion.impact }}</div>

      <v-divider class="my-3" />

      <div class="section-title">Suivi</div>
      <div class="detail-line"><strong>Statut:</strong> {{ statusLabel(suggestion.status) }}</div>
      <div class="detail-block"><strong>Commentaire:</strong><br>{{ suggestion.follow_up || '—' }}</div>

      <div class="d-flex align-center justify-end ga-2 mt-4">
        <v-btn color="primary" prepend-icon="mdi-pencil-outline" variant="tonal" @click="emit('edit', suggestion.id)">
          Modifier
        </v-btn>
        <v-btn color="error" prepend-icon="mdi-delete-outline" variant="text" @click="emit('delete', suggestion.id)">
          Supprimer
        </v-btn>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'

  interface SuggestionItem {
    id: number
    reference: string
    process: string
    title: string
    description: string
    normes?: string[]
    proposer: string
    impact: string
    status: string
    created_at: string
    follow_up?: string
  }

  defineProps({
    suggestion: {
      type: Object as PropType<SuggestionItem | null>,
      required: true,
    },
    statusLabel: {
      type: Function as PropType<(value: string) => string>,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'edit', value: number): void
    (event: 'delete', value: number): void
  }>()
</script>
