<template>
  <v-card>
    <v-card-title class="d-flex align-center">
      <v-icon class="mr-2" color="blue">mdi-chart-box-outline</v-icon>
      <span>Matrice SWOT</span>
      <v-spacer />
      <v-chip color="blue">{{ year }}</v-chip>
    </v-card-title>

    <v-divider />

    <v-card-text class="pa-4">
      <v-row>
        <!-- Top Row: Strengths & Weaknesses (Internal) -->
        <v-col class="text-center py-2" cols="12">
          <v-chip color="grey" size="large" variant="flat">
            <strong>FACTEURS INTERNES</strong>
          </v-chip>
        </v-col>

        <v-col cols="12" md="6">
          <v-card color="green-lighten-5" elevation="2">
            <v-card-title class="d-flex align-center green--text">
              <v-icon class="mr-2" color="green">mdi-shield-check</v-icon>
              <strong>FORCES (Strengths)</strong>
            </v-card-title>
            <v-divider />
            <v-card-text class="pa-4">
              <ul class="swot-list">
                <li v-for="(item, index) in strengthsList" :key="index">
                  {{ item }}
                </li>
              </ul>
              <p v-if="strengthsList.length === 0" class="text-grey text-center">
                Aucune force identifiée
              </p>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="6">
          <v-card color="orange-lighten-5" elevation="2">
            <v-card-title class="d-flex align-center orange--text">
              <v-icon class="mr-2" color="orange">mdi-alert</v-icon>
              <strong>FAIBLESSES (Weaknesses)</strong>
            </v-card-title>
            <v-divider />
            <v-card-text class="pa-4">
              <ul class="swot-list">
                <li v-for="(item, index) in weaknessesList" :key="index">
                  {{ item }}
                </li>
              </ul>
              <p v-if="weaknessesList.length === 0" class="text-grey text-center">
                Aucune faiblesse identifiée
              </p>
            </v-card-text>
          </v-card>
        </v-col>

        <!-- Bottom Row: Opportunities & Threats (External) -->
        <v-col class="text-center py-2" cols="12">
          <v-chip color="grey" size="large" variant="flat">
            <strong>FACTEURS EXTERNES</strong>
          </v-chip>
        </v-col>

        <v-col cols="12" md="6">
          <v-card color="blue-lighten-5" elevation="2">
            <v-card-title class="d-flex align-center blue--text">
              <v-icon class="mr-2" color="blue">mdi-trending-up</v-icon>
              <strong>OPPORTUNITÉS (Opportunities)</strong>
            </v-card-title>
            <v-divider />
            <v-card-text class="pa-4">
              <ul class="swot-list">
                <li v-for="(item, index) in opportunitiesList" :key="index">
                  {{ item }}
                </li>
              </ul>
              <p v-if="opportunitiesList.length === 0" class="text-grey text-center">
                Aucune opportunité identifiée
              </p>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="6">
          <v-card color="red-lighten-5" elevation="2">
            <v-card-title class="d-flex align-center red--text">
              <v-icon class="mr-2" color="red">mdi-alert-octagon</v-icon>
              <strong>MENACES (Threats)</strong>
            </v-card-title>
            <v-divider />
            <v-card-text class="pa-4">
              <ul class="swot-list">
                <li v-for="(item, index) in threatsList" :key="index">
                  {{ item }}
                </li>
              </ul>
              <p v-if="threatsList.length === 0" class="text-grey text-center">
                Aucune menace identifiée
              </p>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Strategic Actions -->
      <v-row v-if="showStrategies" class="mt-4">
        <v-col cols="12">
          <v-divider class="mb-4" />
          <div class="text-h6 mb-4 text-center">Stratégies Recommandées</div>
        </v-col>

        <v-col cols="12" md="6">
          <v-card variant="outlined">
            <v-card-title class="text-subtitle-1">
              <v-icon class="mr-2" color="success">mdi-bullseye-arrow</v-icon>
              Stratégies SO (Forces + Opportunités)
            </v-card-title>
            <v-card-text>
              <p class="text-body-2">Utiliser les forces pour maximiser les opportunités</p>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="6">
          <v-card variant="outlined">
            <v-card-title class="text-subtitle-1">
              <v-icon class="mr-2" color="warning">mdi-shield-alert</v-icon>
              Stratégies ST (Forces + Menaces)
            </v-card-title>
            <v-card-text>
              <p class="text-body-2">Utiliser les forces pour contrer les menaces</p>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="6">
          <v-card variant="outlined">
            <v-card-title class="text-subtitle-1">
              <v-icon class="mr-2" color="info">mdi-chart-line-variant</v-icon>
              Stratégies WO (Faiblesses + Opportunités)
            </v-card-title>
            <v-card-text>
              <p class="text-body-2">Corriger les faiblesses pour saisir les opportunités</p>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="6">
          <v-card variant="outlined">
            <v-card-title class="text-subtitle-1">
              <v-icon class="mr-2" color="error">mdi-shield-off</v-icon>
              Stratégies WT (Faiblesses + Menaces)
            </v-card-title>
            <v-card-text>
              <p class="text-body-2">Minimiser les faiblesses face aux menaces (défensif)</p>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  const props = defineProps<{
    strengths?: string
    weaknesses?: string
    opportunities?: string
    threats?: string
    year?: number
    showStrategies?: boolean
  }>()

  function parseMultiline (text: string | undefined): string[] {
    if (!text) return []
    return text
      .split('\n')
      .map(line => line.trim().replace(/^[-*•]\s*/, ''))
      .filter(line => line.length > 0)
  }

  const strengthsList = computed(() => parseMultiline(props.strengths))
  const weaknessesList = computed(() => parseMultiline(props.weaknesses))
  const opportunitiesList = computed(() => parseMultiline(props.opportunities))
  const threatsList = computed(() => parseMultiline(props.threats))
</script>

<style scoped>
.swot-list {
  list-style-type: none;
  padding-left: 0;
}

.swot-list li {
  padding: 8px 0;
  border-bottom: 1px solid rgba(0, 0, 0, 0.08);
  position: relative;
  padding-left: 24px;
}

.swot-list li:last-child {
  border-bottom: none;
}

.swot-list li::before {
  content: "•";
  position: absolute;
  left: 8px;
  font-weight: bold;
  font-size: 1.2em;
}
</style>
