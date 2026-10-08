<template>
  <v-card elevation="0" rounded="xl" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);">
    <v-card-text class="pa-6">
      <div class="text-center mb-8">
        <v-avatar class="mb-4 elevation-8" color="success" size="64">
          <v-icon color="white" size="36">mdi-eye-check</v-icon>
        </v-avatar>
        <h2 class="text-h4 font-weight-bold mb-2">Récapitulatif</h2>
        <p class="text-body-1 text-medium-emphasis">Vérifiez toutes les informations avant l'enregistrement final</p>
      </div>

      <v-card class="mb-4" elevation="2" rounded="lg">
        <v-card-title class="d-flex align-center" style="background: linear-gradient(135deg, #5b8dd9 0%, #4a7bc8 100%); color: white;">
          <v-icon class="mr-2">mdi-file-multiple</v-icon>
          Documents référencés ({{ form.referencedDocuments.length }})
          <v-spacer />
          <v-btn
            icon="mdi-pencil"
            size="x-small"
            variant="text"
            @click="jumpToStep(1)"
          />
        </v-card-title>
        <v-card-text class="pa-4">
          <v-chip-group v-if="form.referencedDocuments.length > 0" column>
            <v-chip
              v-for="(doc, i) in form.referencedDocuments"
              :key="i"
              color="primary"
              size="small"
              variant="tonal"
            >
              {{ doc }}
            </v-chip>
          </v-chip-group>
          <div v-else class="text-caption text-medium-emphasis">Aucun document</div>
        </v-card-text>
      </v-card>

      <v-card class="mb-4" elevation="2" rounded="lg">
        <v-card-title class="d-flex align-center" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: white;">
          <v-icon class="mr-2">mdi-text-box</v-icon>
          Définition du domaine
          <v-spacer />
          <v-btn
            icon="mdi-pencil"
            size="x-small"
            variant="text"
            @click="jumpToStep(2)"
          />
        </v-card-title>
        <v-card-text class="pa-4">
          <div class="mb-3">
            <div class="text-caption text-medium-emphasis mb-1">Objet du document</div>
            <div class="text-body-2" style="max-height: 80px; overflow-y: auto; white-space: pre-wrap;">
              {{ form.documentObjective || 'Non défini' }}
            </div>
          </div>
          <v-divider class="my-3" />
          <div>
            <div class="text-caption text-medium-emphasis mb-1">Définition</div>
            <div class="text-body-2" style="max-height: 80px; overflow-y: auto; white-space: pre-wrap;">
              {{ form.scopeDefinition || 'Non défini' }}
            </div>
          </div>
        </v-card-text>
      </v-card>

      <v-card class="mb-4" elevation="2" rounded="lg">
        <v-card-title class="d-flex align-center" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white;">
          <v-icon class="mr-2">mdi-cogs</v-icon>
          Processus ({{ form.processes.length }})
          <v-spacer />
          <v-btn
            icon="mdi-pencil"
            size="x-small"
            variant="text"
            @click="jumpToStep(3)"
          />
        </v-card-title>
        <v-card-text class="pa-4">
          <v-list v-if="orderedProcesses.length > 0" density="compact">
            <v-list-item v-for="(p, i) in orderedProcesses" :key="`${p.type}-${p.name}-${i}`" class="mb-2">
              <template #prepend>
                <v-avatar :color="getProcessTypeColor(p.type)" size="32">
                  <v-icon color="white" size="16">mdi-cog</v-icon>
                </v-avatar>
              </template>
              <v-list-item-title class="font-weight-bold">{{ p.name }}</v-list-item-title>
              <v-list-item-subtitle>
                <v-chip :color="getProcessTypeColor(p.type)" size="x-small" variant="tonal">
                  {{ getProcessTypeLabel(p.type) }}
                </v-chip>
              </v-list-item-subtitle>
            </v-list-item>
          </v-list>
          <div v-else class="text-caption text-medium-emphasis">Aucun processus</div>
        </v-card-text>
      </v-card>

      <v-row>
        <v-col cols="12" md="6">
          <v-card class="mb-4" elevation="2" rounded="lg">
            <v-card-title class="d-flex align-center" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white;">
              <v-icon class="mr-2" size="small">mdi-package-variant</v-icon>
              <span class="text-body-1">Produits/Services ({{ form.productsServices.length }})</span>
              <v-spacer />
              <v-btn
                icon="mdi-pencil"
                size="x-small"
                variant="text"
                @click="jumpToStep(4)"
              />
            </v-card-title>
            <v-card-text class="pa-3">
              <div v-if="form.productsServices.length > 0" class="d-flex flex-wrap gap-1">
                <v-chip
                  v-for="(item, i) in form.productsServices"
                  :key="i"
                  color="warning"
                  size="x-small"
                  variant="tonal"
                >
                  {{ item }}
                </v-chip>
              </div>
              <div v-else class="text-caption text-medium-emphasis">Aucun</div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="6">
          <v-card class="mb-4" elevation="2" rounded="lg">
            <v-card-title class="d-flex align-center" style="background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%); color: white;">
              <v-icon class="mr-2" size="small">mdi-sitemap</v-icon>
              <span class="text-body-1">Unités ({{ form.organizationalUnits.length }})</span>
              <v-spacer />
              <v-btn
                icon="mdi-pencil"
                size="x-small"
                variant="text"
                @click="jumpToStep(5)"
              />
            </v-card-title>
            <v-card-text class="pa-3">
              <div v-if="form.organizationalUnits.length > 0" class="d-flex flex-wrap gap-1">
                <v-chip
                  v-for="(unit, i) in form.organizationalUnits"
                  :key="i"
                  color="purple"
                  size="x-small"
                  variant="tonal"
                >
                  {{ unit }}
                </v-chip>
              </div>
              <div v-else class="text-caption text-medium-emphasis">Aucune</div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="6">
          <v-card class="mb-4" elevation="2" rounded="lg">
            <v-card-title class="d-flex align-center" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); color: white;">
              <v-icon class="mr-2" size="small">mdi-map-marker</v-icon>
              <span class="text-body-1">Lieux ({{ form.locations.length }})</span>
              <v-spacer />
              <v-btn
                icon="mdi-pencil"
                size="x-small"
                variant="text"
                @click="jumpToStep(6)"
              />
            </v-card-title>
            <v-card-text class="pa-3">
              <div v-if="form.locations.length > 0" class="d-flex flex-wrap gap-1">
                <v-chip
                  v-for="(loc, i) in form.locations"
                  :key="i"
                  color="cyan"
                  size="x-small"
                  variant="tonal"
                >
                  {{ loc }}
                </v-chip>
              </div>
              <div v-else class="text-caption text-medium-emphasis">Aucun</div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="6">
          <v-card class="mb-4" elevation="2" rounded="lg">
            <v-card-title class="d-flex align-center" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white;">
              <v-icon class="mr-2" size="small">mdi-close-circle</v-icon>
              <span class="text-body-1">Exclusions</span>
              <v-spacer />
              <v-btn
                icon="mdi-pencil"
                size="x-small"
                variant="text"
                @click="jumpToStep(7)"
              />
            </v-card-title>
            <v-card-text class="pa-3">
              <div v-if="Object.keys(form.normExclusions).some(key => (form.normExclusions[key] || []).length > 0) || Object.keys(form.normExclusionsJustifications || {}).length > 0">
                <div v-for="norm in subscribedNorms" :key="normKey(norm)">
                  <div
                    v-if="getNormExcludedChapters(normKey(norm)).length > 0 || getNormJustification(normKey(norm))"
                    class="mb-2"
                  >
                    <div class="text-caption font-weight-bold">{{ normKey(norm) }}:</div>
                    <v-chip
                      v-for="(exc, i) in getNormExcludedChapters(normKey(norm))"
                      :key="i"
                      class="mr-1 mb-1"
                      color="error"
                      size="x-small"
                      variant="tonal"
                    >
                      {{ getNormExclusionLabel(normKey(norm), exc) }}
                    </v-chip>
                    <div class="text-caption mt-1">
                      <strong>Exclusion (texte):</strong>
                      <span class="ml-1">
                        {{ getNormJustification(normKey(norm)) || 'A completer' }}
                      </span>
                    </div>
                  </div>
                </div>
                <div v-if="form.scopeExclusions" class="mt-2 text-caption summary-long-text">
                  <strong>Autres:</strong> {{ form.scopeExclusions }}
                </div>
              </div>
              <div v-else-if="form.scopeExclusions" class="text-caption summary-long-text">
                {{ form.scopeExclusions }}
              </div>
              <div v-else class="text-caption text-medium-emphasis">Aucune exclusion</div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <v-alert class="mt-4" prominent type="success" variant="tonal">
        <v-icon size="large" start>mdi-check-circle</v-icon>
        <div>
          <div class="text-h6 font-weight-bold mb-1">Prêt à enregistrer</div>
          <div class="text-body-2">Cliquez sur "Enregistrer" pour sauvegarder le domaine d'application</div>
        </div>
      </v-alert>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import { computed } from 'vue'

  const emit = defineEmits<{
    'jump-to-step': [step: number]
  }>()

  const props = defineProps({
    form: {
      type: Object as PropType<any>,
      required: true,
    },
    subscribedNorms: {
      type: Array as PropType<any[]>,
      required: true,
    },
    getProcessTypeColor: {
      type: Function as PropType<(type: string) => string>,
      required: true,
    },
    getProcessTypeLabel: {
      type: Function as PropType<(type: string) => string>,
      required: true,
    },
    normKey: {
      type: Function as PropType<(norm: any) => string>,
      required: true,
    },
    getNormExcludedChapters: {
      type: Function as PropType<(normCode: string) => string[]>,
      required: true,
    },
    getNormExclusionLabel: {
      type: Function as PropType<(normCode: string, chapterCode: string) => string>,
      required: true,
    },
    getNormJustification: {
      type: Function as PropType<(normCode: string) => string>,
      required: true,
    },
  })

  function getProcessOrder (type: string): number {
    if (type === 'management') return 0
    if (type === 'realization') return 1
    if (type === 'support') return 2
    return 99
  }

  const orderedProcesses = computed(() => {
    const processes = Array.isArray(props.form?.processes) ? props.form.processes : []
    return [...processes].toSorted((a: any, b: any) => {
      const familyDiff = getProcessOrder(String(a?.type || '')) - getProcessOrder(String(b?.type || ''))
      if (familyDiff !== 0) return familyDiff
      return String(a?.name || '').localeCompare(String(b?.name || ''), 'fr', { sensitivity: 'base' })
    })
  })

  function jumpToStep (step: number) {
    emit('jump-to-step', step)
  }
</script>

<style scoped>
.summary-long-text {
  white-space: pre-wrap;
  overflow-wrap: anywhere;
}
</style>
