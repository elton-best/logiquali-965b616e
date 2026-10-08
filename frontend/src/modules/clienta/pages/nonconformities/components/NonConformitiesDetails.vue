<template>
  <v-card v-if="selectedNc" class="sticky-card" elevation="2" rounded="lg">
    <v-card-title class="text-subtitle-1 font-weight-bold">
      <div class="d-flex align-center justify-space-between w-100">
        <span>Fiche {{ selectedNc.reference }}</span>
        <v-btn
          v-if="canUpdateNc"
          color="primary"
          prepend-icon="mdi-pencil-outline"
          size="small"
          variant="tonal"
          @click="emit('edit', selectedNc.id)"
        >
          Modifier
        </v-btn>
      </div>
    </v-card-title>

    <v-card-text>
      <v-alert class="mb-3" density="comfortable" type="info" variant="tonal">
        Processus concerné: <strong>{{ selectedNc.process }}</strong>
      </v-alert>

      <div class="section-title">I. Identification</div>
      <div class="detail-line">
        <strong>Date d'ouverture:</strong> {{ selectedNc.date }}
      </div>
      <div class="detail-line">
        <strong>Type de constat:</strong> {{ selectedNc.type_constat }}
      </div>
      <div class="detail-line">
        <strong>Source de détection:</strong> {{ selectedNc.source_detection }}
      </div>
      <div class="detail-line">
        <strong>Type de NC:</strong> {{ selectedNc.type_nc }}
      </div>
      <div class="detail-line">
        <strong>Exigence non respectée:</strong> {{ selectedNc.requirement }}
      </div>
      <div class="detail-block">
        <strong>Description:</strong><br>{{ selectedNc.description }}
      </div>

      <v-divider class="my-3" />

      <div class="section-title">II. Traitement</div>
      <div class="detail-block">
        <strong>Cause(s):</strong><br>{{ selectedNc.cause }}
      </div>
      <div class="detail-block">
        <strong>Actions de traitement:</strong>
        <div v-if="selectedNc.actions?.length" class="mt-2">
          <div
            v-for="(action, index) in selectedNc.actions"
            :key="`sel-action-${index}`"
            class="action-preview"
          >
            <div class="action-index">{{ Number(index) + 1 }}</div>
            <div class="action-body">
              <div class="action-title">{{ action.description }}</div>
              <div class="action-meta">
                <span>{{ action.typeLabel }}</span>
                <span>•</span>
                <span>{{ action.responsibleName }}</span>
                <span>•</span>
                <span>Délai: {{ action.deadline || "—" }}</span>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="text-medium-emphasis mt-1">
          Plan d’action à définir après création.
        </div>
      </div>

      <v-divider class="my-3" />

      <div class="section-title">III. Résultats et clôture</div>
      <div class="detail-block">
        <strong>Résultat:</strong><br>{{ selectedNc.result }}
      </div>
      <div class="detail-line">
        <strong>Statut:</strong> {{ statusLabel(selectedNc.status) }}
      </div>
      <div class="detail-line">
        <strong>Date de clôture:</strong> {{ selectedNc.closure_date || "—" }}
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'

  defineProps({
    selectedNc: {
      type: Object as PropType<any | null>,
      required: true,
    },
    canUpdateNc: {
      type: Boolean,
      required: true,
    },
    statusLabel: {
      type: Function as PropType<(status: string) => string>,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'edit', id: number): void
  }>()
</script>

<style scoped>
.section-title {
  font-weight: 700;
  margin-bottom: 8px;
  color: rgb(var(--v-theme-primary));
}

.detail-line {
  margin-bottom: 6px;
  font-size: 0.92rem;
}

.detail-block {
  margin-bottom: 10px;
  font-size: 0.92rem;
  line-height: 1.45;
}

.sticky-card {
  position: sticky;
  top: 20px;
}

.action-preview {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 12px;
  padding: 10px 12px;
  border: 1px solid rgba(148, 163, 184, 0.35);
  border-radius: 10px;
  background: #fff;
  margin-bottom: 8px;
}

.action-index {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  background: rgba(91, 141, 217, 0.12);
  color: #4a71b0;
  display: grid;
  place-items: center;
  font-weight: 700;
  font-size: 0.85rem;
}

.action-title {
  font-weight: 600;
  color: #1e293b;
}

.action-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  color: #64748b;
  font-size: 0.85rem;
  margin-top: 2px;
}
</style>
