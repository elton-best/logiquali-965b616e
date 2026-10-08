<template>
  <div v-bind="$attrs">
    <v-row>
      <!-- Total Processus -->
      <v-col cols="12" md="3" sm="6">
        <v-card
          class="stat-card cursor-pointer"
          variant="outlined"
          @click="emit('filter', {})"
        >
          <v-card-text class="d-flex align-center">
            <v-avatar class="mr-3" color="primary" size="48">
              <v-icon size="28">mdi-sitemap</v-icon>
            </v-avatar>
            <div>
              <div class="text-h5 font-weight-bold">{{ stats.total || 0 }}</div>
              <div class="text-caption text-medium-emphasis">Total Processus</div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Brouillons -->
      <v-col cols="12" md="3" sm="6">
        <v-card
          class="stat-card cursor-pointer"
          variant="outlined"
          @click="emit('filter', { status: 'draft' })"
        >
          <v-card-text class="d-flex align-center">
            <v-avatar class="mr-3" color="grey" size="48">
              <v-icon size="28">mdi-file-document-outline</v-icon>
            </v-avatar>
            <div>
              <div class="text-h5 font-weight-bold">{{ stats.by_status?.draft || 0 }}</div>
              <div class="text-caption text-medium-emphasis">Brouillons</div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- En révision -->
      <v-col cols="12" md="3" sm="6">
        <v-card
          class="stat-card cursor-pointer"
          variant="outlined"
          @click="emit('filter', { status: 'in_review' })"
        >
          <v-card-text class="d-flex align-center">
            <v-avatar class="mr-3" color="orange" size="48">
              <v-icon size="28">mdi-eye-check</v-icon>
            </v-avatar>
            <div>
              <div class="text-h5 font-weight-bold">{{ stats.by_status?.in_review || 0 }}</div>
              <div class="text-caption text-medium-emphasis">En révision</div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Actifs -->
      <v-col cols="12" md="3" sm="6">
        <v-card
          class="stat-card cursor-pointer"
          variant="outlined"
          @click="emit('filter', { status: 'active' })"
        >
          <v-card-text class="d-flex align-center">
            <v-avatar class="mr-3" color="success" size="48">
              <v-icon size="28">mdi-check-circle</v-icon>
            </v-avatar>
            <div>
              <div class="text-h5 font-weight-bold">{{ stats.by_status?.active || 0 }}</div>
              <div class="text-caption text-medium-emphasis">Actifs</div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Process Types Distribution -->
    <v-row v-if="showTypeDistribution" class="mt-2">
      <v-col cols="12" md="3" sm="6">
        <v-card
          class="stat-card cursor-pointer"
          variant="outlined"
          @click="emit('filter', { category: 'pilotage' })"
        >
          <v-card-text class="text-center">
            <v-icon class="mb-2" color="purple" size="32">mdi-cog</v-icon>
            <div class="text-h6 font-weight-bold">{{ stats.by_category?.pilotage || 0 }}</div>
            <div class="text-caption text-medium-emphasis">Pilotage</div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="3" sm="6">
        <v-card
          class="stat-card cursor-pointer"
          variant="outlined"
          @click="emit('filter', { category: 'support' })"
        >
          <v-card-text class="text-center">
            <v-icon class="mb-2" color="indigo" size="32">mdi-handshake</v-icon>
            <div class="text-h6 font-weight-bold">{{ stats.by_category?.support || 0 }}</div>
            <div class="text-caption text-medium-emphasis">Support</div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="3" sm="6">
        <v-card
          class="stat-card cursor-pointer"
          variant="outlined"
          @click="emit('filter', { category: 'operationnel' })"
        >
          <v-card-text class="text-center">
            <v-icon class="mb-2" color="teal" size="32">mdi-factory</v-icon>
            <div class="text-h6 font-weight-bold">{{ stats.by_category?.operationnel || 0 }}</div>
            <div class="text-caption text-medium-emphasis">Opérationnel</div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="3" sm="6">
        <v-card
          class="stat-card cursor-pointer"
          variant="outlined"
          @click="emit('filter', { category: 'amelioration' })"
        >
          <v-card-text class="text-center">
            <v-icon class="mb-2" color="blue" size="32">mdi-trending-up</v-icon>
            <div class="text-h6 font-weight-bold">{{ stats.by_category?.amelioration || 0 }}</div>
            <div class="text-caption text-medium-emphasis">Amélioration</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Révisions -->
    <v-row v-if="stats.reviews" class="mt-2">
      <v-col cols="12" md="6">
        <v-card variant="outlined">
          <v-card-text class="d-flex align-center">
            <v-avatar class="mr-3" color="error" size="48">
              <v-icon size="28">mdi-alert-circle</v-icon>
            </v-avatar>
            <div>
              <div class="text-h5 font-weight-bold">{{ stats.reviews.due || 0 }}</div>
              <div class="text-caption text-medium-emphasis">Révisions en retard</div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="6">
        <v-card variant="outlined">
          <v-card-text class="d-flex align-center">
            <v-avatar class="mr-3" color="warning" size="48">
              <v-icon size="28">mdi-calendar-clock</v-icon>
            </v-avatar>
            <div>
              <div class="text-h5 font-weight-bold">{{ stats.reviews.upcoming || 0 }}</div>
              <div class="text-caption text-medium-emphasis">Révisions à venir (30j)</div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script setup lang="ts">
  interface ProcessStats {
    total?: number
    by_status?: {
      draft?: number
      in_review?: number
      validated?: number
      active?: number
      obsolete?: number
    }
    by_category?: {
      pilotage?: number
      support?: number
      operationnel?: number
      amelioration?: number
    }
    by_level?: {
      1?: number
      2?: number
      3?: number
    }
    reviews?: {
      due?: number
      upcoming?: number
    }
  }

  interface Props {
    stats: ProcessStats
    showTypeDistribution?: boolean
  }

  withDefaults(defineProps<Props>(), {
    showTypeDistribution: true,
  })

  const emit = defineEmits<{
    (e: 'filter', filters: any): void
  }>()
</script>

<style scoped>
.stat-card {
  transition: all 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.cursor-pointer {
  cursor: pointer;
}
</style>
