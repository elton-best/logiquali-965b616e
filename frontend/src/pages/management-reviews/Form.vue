<template>
  <div class="management-review-form-page pa-6">
    <div class="d-flex align-center mb-6">
      <v-btn
        icon="mdi-arrow-left"
        variant="text"
        @click="$router.back()"
      />
      <h1 class="text-h4 ml-3">
        {{ isEdit ? 'Modifier Revue de Direction' : 'Planifier Revue de Direction' }}
      </h1>
    </div>

    <v-card class="mb-6">
      <v-card-text>
        <v-alert class="mb-4" type="info">
          <strong>ISO 9001:2015 - Clause 9.3:</strong>
          La direction doit, à intervalles planifiés, revoir le système de management de la qualité
          de l'organisme pour s'assurer qu'il demeure pertinent, adéquat et efficace.
        </v-alert>

        <p class="text-body-1 mb-4">
          Cette revue de direction permettra d'examiner les performances du SMI QHSE et de prendre
          les décisions stratégiques pour l'amélioration continue.
        </p>

        <div v-if="!isEdit" class="d-flex justify-center">
          <v-btn
            color="primary"
            prepend-icon="mdi-calendar-plus"
            size="x-large"
            @click="showWizard = true"
          >
            Démarrer l'Assistant
          </v-btn>
        </div>

        <div v-else>
          <p class="text-body-2 text-grey-darken-1">
            Chargement de la revue...
          </p>
        </div>
      </v-card-text>
    </v-card>

    <!-- Management Review Wizard -->
    <ManagementReviewWizard
      v-if="showWizard"
      v-model="showWizard"
      :review-id="reviewId"
      @cancelled="handleCancelled"
      @saved="handleSaved"
    />
  </div>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import ManagementReviewWizard from '@/components/management-reviews/ManagementReviewWizard.vue'

  const route = useRoute()
  const router = useRouter()

  const showWizard = ref(false)
  const reviewId = computed(() => {
    const rawId = (route.params as Record<string, unknown>).id
    const idValue = typeof rawId === 'string' ? rawId : (Array.isArray(rawId) ? rawId[0] : null)
    if (!idValue) return null
    const parsed = Number.parseInt(idValue, 10)
    return Number.isNaN(parsed) ? null : parsed
  })
  const isEdit = computed(() => !!reviewId.value)

  onMounted(() => {
    // If editing, open wizard automatically
    if (isEdit.value) {
      showWizard.value = true
    }
  })

  function handleSaved (review: any) {
    showWizard.value = false
    router.push(`/company/management-reviews/${review.id}`)
  }

  function handleCancelled () {
    showWizard.value = false
    router.back()
  }
</script>

<style scoped>
.management-review-form-page {
  max-width: 1200px;
  margin: 0 auto;
}
</style>
