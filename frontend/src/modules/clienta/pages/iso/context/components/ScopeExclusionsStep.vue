<template>
  <v-card elevation="0" rounded="xl" :style="`background: ${step?.gradient}`">
    <v-card-text class="pa-6">
      <div class="d-flex align-center mb-6">
        <v-avatar class="mr-4 elevation-4" :color="step?.color" size="48">
          <v-icon color="white" size="24">mdi-close-circle</v-icon>
        </v-avatar>
        <div>
          <h2 class="text-h5 font-weight-bold">Exclusions</h2>
          <p class="text-body-2 text-medium-emphasis mb-0">Activités et clauses ISO exclues</p>
        </div>
      </div>

      <v-textarea
        v-model="form.scopeExclusions"
        bg-color="white"
        class="mb-6"
        density="comfortable"
        label="Exclusions du domaine d'application"
        placeholder="Décrivez les exclusions du domaine d'application..."
        rounded="lg"
        rows="4"
        variant="solo"
      />

      <h3 class="text-h6 font-weight-bold mb-4">Exclusions par norme souscrite</h3>

      <UnifiedLoader
        v-if="loadingNorms"
        class="mb-4"
        description="Récupération des normes souscrites du site..."
        title="Chargement des normes..."
        variant="local"
      />

      <v-alert v-else-if="subscribedNorms.length === 0" class="mb-4" type="info" variant="tonal">
        <v-icon start>mdi-information</v-icon>
        Aucune norme souscrite. Les exclusions ne peuvent pas être définies.
      </v-alert>

      <div v-else>
        <v-expansion-panels
          v-for="norm in subscribedNorms"
          :key="norm.id"
          class="mb-4"
          :model-value="[0]"
          multiple
        >
          <v-expansion-panel>
            <v-expansion-panel-title>
              <div class="d-flex align-center">
                <v-icon class="mr-3" :color="step?.color">mdi-file-document</v-icon>
                <div>
                  <div class="font-weight-bold">
                    Exclusions par rapport à la norme {{ normKey(norm) }}
                  </div>
                  <div class="text-caption text-medium-emphasis">
                    {{ norm.chapters.length }} chapitre(s) enregistré(s) • {{ getNormExcludedChapters(normKey(norm)).length }} exclusion(s)
                  </div>
                </div>
              </div>
            </v-expansion-panel-title>
            <v-expansion-panel-text>
              <v-textarea
                class="mb-4"
                density="comfortable"
                :label="`Exclusions (texte libre) - ${normKey(norm)}`"
                :model-value="getNormJustification(normKey(norm))"
                placeholder="Décrivez les exclusions applicables à cette norme (chapitres optionnels)..."
                rows="3"
                variant="outlined"
                @update:model-value="setNormJustification(normKey(norm), $event)"
              />

              <v-alert class="mb-3" density="compact" type="info" variant="tonal">
                La sélection des chapitres ci-dessous est optionnelle.
              </v-alert>

              <v-alert
                v-if="norm.chapters.length === 0"
                class="mb-3"
                density="compact"
                type="warning"
                variant="tonal"
              >
                Aucun chapitre disponible pour cette norme
              </v-alert>

              <v-expansion-panels v-else variant="accordion">
                <v-expansion-panel v-for="chapter in norm.chapters" :key="chapter.id">
                  <v-expansion-panel-title>
                    <div class="d-flex align-center">
                      <v-checkbox
                        class="mr-2"
                        :color="step?.color"
                        density="compact"
                        hide-details
                        :model-value="isChapterExcluded(normKey(norm), chapterCodeOf(chapter))"
                        @click.stop
                        @update:model-value="toggleChapterExclusion(normKey(norm), chapterCodeOf(chapter))"
                      />
                      <div>
                        <span class="font-weight-bold">{{ chapterCodeOf(chapter) }}</span>
                        <span class="ml-2">{{ chapterTitleOf(chapter) }}</span>
                      </div>
                    </div>
                  </v-expansion-panel-title>
                  <v-expansion-panel-text v-if="chapter.subchapters && chapter.subchapters.length > 0">
                    <div class="ml-8">
                      <div v-for="subchapter in chapter.subchapters" :key="subchapter.id" class="mb-2">
                        <v-checkbox
                          :color="step?.color"
                          density="compact"
                          hide-details
                          :label="`${chapterCodeOf(subchapter)} - ${chapterTitleOf(subchapter)}`"
                          :model-value="isChapterExcluded(normKey(norm), chapterCodeOf(subchapter))"
                          @update:model-value="toggleChapterExclusion(normKey(norm), chapterCodeOf(subchapter))"
                        />
                      </div>
                    </div>
                  </v-expansion-panel-text>
                </v-expansion-panel>
              </v-expansion-panels>

            </v-expansion-panel-text>
          </v-expansion-panel>
        </v-expansion-panels>
      </div>

      <v-textarea
        v-if="hasAnyNormExclusion"
        v-model="form.isoExclusionsJustification"
        bg-color="white"
        class="mt-6"
        density="comfortable"
        label="Justification globale (optionnelle)"
        placeholder="Ajoutez une justification globale couvrant toutes les normes (optionnel)..."
        rounded="lg"
        rows="4"
        variant="solo"
      />
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import UnifiedLoader from '@/modules/shared/components/ui/UnifiedLoader.vue'

  defineProps({
    step: {
      type: Object as PropType<{ label: string, color: string, gradient: string }>,
      required: true,
    },
    form: {
      type: Object as PropType<{ scopeExclusions: string, isoExclusionsJustification: string }>,
      required: true,
    },
    subscribedNorms: {
      type: Array as PropType<any[]>,
      required: true,
    },
    loadingNorms: {
      type: Boolean,
      required: true,
    },
    hasAnyNormExclusion: {
      type: Boolean,
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
    isChapterExcluded: {
      type: Function as PropType<(normCode: string, chapterCode: string) => boolean>,
      required: true,
    },
    toggleChapterExclusion: {
      type: Function as PropType<(normCode: string, chapterCode: string) => void>,
      required: true,
    },
    chapterCodeOf: {
      type: Function as PropType<(chapter: any) => string>,
      required: true,
    },
    chapterTitleOf: {
      type: Function as PropType<(chapter: any) => string>,
      required: true,
    },
    getNormJustification: {
      type: Function as PropType<(normCode: string) => string>,
      required: true,
    },
    setNormJustification: {
      type: Function as PropType<(normCode: string, value: string) => void>,
      required: true,
    },
  })
</script>
