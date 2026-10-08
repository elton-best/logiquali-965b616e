<template>
  <div class="nc-detail-page p-6">
    <div v-if="loading" class="flex items-center justify-center h-96">
      <div class="inline-block w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin" />
    </div>

    <div v-else-if="nc" class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <button class="p-2 hover:bg-gray-100 rounded-lg" @click="router.push('/improvement/non-conformities')">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M15 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
            </svg>
          </button>
          <div>
            <h1 class="text-3xl font-bold">{{ nc.title }}</h1>
            <p class="text-gray-600 mt-1">{{ nc.ref }}</p>
          </div>
        </div>
        <div class="flex gap-2">
          <StatusBadge module="nc" :status="nc.status" />
          <NCSeverityBadge :severity="nc.severity" />
          <NCTypeBadge :type="nc.type" />
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex gap-3">
        <button
          v-if="nc.status === 'nouveau'"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
          @click="analyzeNC"
        >
          Lancer l'analyse
        </button>
        <button
          v-if="nc.status === 'en_analyse'"
          class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700"
          @click="showAnalysis = true"
        >
          Compléter l'analyse 5M
        </button>
        <button
          v-if="nc.status === 'en_verification'"
          class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
          @click="verifyNC"
        >
          Vérifier l'efficacité
        </button>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left - Main Content -->
        <div class="lg:col-span-2 space-y-6">
          <BaseCard>
            <h2 class="text-lg font-semibold mb-3">Description</h2>
            <p class="text-gray-700 whitespace-pre-line">{{ nc.description }}</p>
          </BaseCard>

          <!-- Cause Analysis 5M -->
          <NCCauseAnalysis
            v-if="nc.cause_analysis || showAnalysis"
            v-model="analysisModel"
            :disabled="!showAnalysis"
          />
          <div v-if="showAnalysis" class="flex gap-2">
            <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700" @click="saveCauseAnalysis(analysisModel)">
              Enregistrer l'analyse
            </button>
            <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200" @click="showAnalysis = false">
              Annuler
            </button>
          </div>

          <!-- Associated Actions -->
          <BaseCard v-if="nc.actions && nc.actions.length > 0">
            <h2 class="text-lg font-semibold mb-3">Actions correctives ({{ nc.actions.length }})</h2>
            <div class="space-y-2">
              <div
                v-for="action in nc.actions"
                :key="action.id"
                class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 cursor-pointer"
                @click="router.push(`/improvement/actions/${action.id}`)"
              >
                <div>
                  <p class="font-medium">{{ action.title }}</p>
                  <p class="text-sm text-gray-600">Action #{{ action.id }}</p>
                </div>
                <StatusBadge module="action" size="sm" :status="action.status" />
              </div>
            </div>
          </BaseCard>
        </div>

        <!-- Right - Metadata -->
        <div class="space-y-6">
          <BaseCard>
            <h2 class="text-lg font-semibold mb-4">Informations</h2>
            <dl class="space-y-3">
              <div>
                <dt class="text-sm text-gray-600">Détectée par</dt>
                <dd class="text-sm font-medium mt-1">{{ nc.detected_by_user?.name || 'N/A' }}</dd>
              </div>
              <div>
                <dt class="text-sm text-gray-600">Date détection</dt>
                <dd class="text-sm font-medium mt-1">{{ formatDate(nc.detected_at) }}</dd>
              </div>
              <div>
                <dt class="text-sm text-gray-600">Date limite</dt>
                <dd :class="['text-sm font-medium mt-1', isOverdue ? 'text-red-600' : '']">
                  {{ formatDate(nc.deadline) }}
                </dd>
              </div>
              <div v-if="nc.process">
                <dt class="text-sm text-gray-600">Processus</dt>
                <dd class="text-sm font-medium mt-1">{{ nc.process.name }}</dd>
              </div>
              <div v-if="nc.audit">
                <dt class="text-sm text-gray-600">Audit lié</dt>
                <dd class="text-sm">
                  <router-link class="text-blue-600 hover:underline" :to="`/improvement/audits/${nc.audit_id}`">
                    {{ nc.audit.code }}
                  </router-link>
                </dd>
              </div>
            </dl>
          </BaseCard>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { storeToRefs } from 'pinia'
  import { computed, onMounted, ref, watch } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import BaseCard from '@/components/common/BaseCard.vue'
  import StatusBadge from '@/components/common/StatusBadge.vue'
  import NCCauseAnalysis from '@/components/nonconformities/NCCauseAnalysis.vue'
  import NCSeverityBadge from '@/components/nonconformities/NCSeverityBadge.vue'
  import NCTypeBadge from '@/components/nonconformities/NCTypeBadge.vue'
  import { useNonConformityStore } from '@/stores/nonConformityStore'

  const router = useRouter()
  const route = useRoute()
  const ncStore = useNonConformityStore()

  const { currentNC: nc, loading } = storeToRefs(ncStore)
  const showAnalysis = ref(false)
  const analysisModel = ref<any>({})

  const ncId = computed(() => Number((route.params as any).id))
  const isOverdue = computed(() => {
    if (!nc.value || nc.value.status === 'clos' || !nc.value.deadline) return false
    return new Date(nc.value.deadline) < new Date()
  })

  async function analyzeNC () {
    if (nc.value) {
      await ncStore.updateNC(nc.value.id, { status: 'en_analyse' })
      showAnalysis.value = true
      await loadNC()
    }
  }

  async function verifyNC () {
    if (nc.value) {
      await ncStore.verifyEffectiveness(nc.value.id, { is_effective: true })
      await loadNC()
    }
  }

  async function saveCauseAnalysis (analysis: any) {
    if (nc.value) {
      await ncStore.analyzeCauses(nc.value.id, {
        cause_analysis: analysis,
        root_cause_analysis: analysis?.root_cause,
      })
      showAnalysis.value = false
      await loadNC()
    }
  }

  async function loadNC () {
    await ncStore.fetchNCById(ncId.value)
  }

  function formatDate (date?: string | null): string {
    if (!date) return 'N/A'
    return new Date(date).toLocaleDateString('fr-FR')
  }

  watch(nc, value => {
    analysisModel.value = (value?.cause_analysis as any) || {}
  }, { immediate: true })

  onMounted(() => {
    loadNC()
  })
</script>
