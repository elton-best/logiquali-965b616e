<template>
  <div class="pa-6">
    <h1 class="text-h3 mb-6">🎨 UI/UX Components Demo</h1>

    <!-- Toast Notifications -->
    <v-card class="mb-6">
      <v-card-title class="bg-primary">
        <v-icon start>mdi-bell</v-icon>
        Toast Notifications
      </v-card-title>
      <v-card-text class="pa-6">
        <div class="d-flex flex-wrap gap-3">
          <v-btn color="success" @click="showSuccessToast">Success Toast</v-btn>
          <v-btn color="error" @click="showErrorToast">Error Toast</v-btn>
          <v-btn color="warning" @click="showWarningToast">Warning Toast</v-btn>
          <v-btn color="info" @click="showInfoToast">Info Toast</v-btn>
        </div>
      </v-card-text>
    </v-card>

    <!-- Skeleton Loaders -->
    <v-card class="mb-6">
      <v-card-title class="bg-primary">
        <v-icon start>mdi-loading</v-icon>
        Skeleton Loaders
      </v-card-title>
      <v-card-text class="pa-6">
        <v-btn class="mb-4" @click="toggleSkeletons">
          {{ showSkeletons ? 'Hide' : 'Show' }} Skeletons
        </v-btn>

        <v-tabs v-model="skeletonTab" class="mb-4">
          <v-tab value="card">Cards</v-tab>
          <v-tab value="table">Table</v-tab>
          <v-tab value="list">List</v-tab>
          <v-tab value="text">Text</v-tab>
        </v-tabs>

        <v-window v-model="skeletonTab">
          <v-window-item value="card">
            <SkeletonLoader v-if="showSkeletons" :count="3" type="card" />
            <div v-else class="text-center pa-8 bg-surface-variant rounded">
              <v-icon color="primary" size="48">mdi-check-circle</v-icon>
              <p class="text-h6 mt-4">Content Loaded!</p>
            </div>
          </v-window-item>

          <v-window-item value="table">
            <SkeletonLoader v-if="showSkeletons" :columns="4" :count="5" type="table" />
            <div v-else class="text-center pa-8 bg-surface-variant rounded">
              <v-icon color="primary" size="48">mdi-table</v-icon>
              <p class="text-h6 mt-4">Table Data Loaded!</p>
            </div>
          </v-window-item>

          <v-window-item value="list">
            <SkeletonLoader v-if="showSkeletons" :count="8" type="list" />
            <div v-else class="text-center pa-8 bg-surface-variant rounded">
              <v-icon color="primary" size="48">mdi-format-list-bulleted</v-icon>
              <p class="text-h6 mt-4">List Items Loaded!</p>
            </div>
          </v-window-item>

          <v-window-item value="text">
            <SkeletonLoader v-if="showSkeletons" :count="6" type="text" />
            <div v-else class="text-center pa-8 bg-surface-variant rounded">
              <v-icon color="primary" size="48">mdi-text</v-icon>
              <p class="text-h6 mt-4">Text Content Loaded!</p>
            </div>
          </v-window-item>
        </v-window>
      </v-card-text>
    </v-card>

    <!-- Confirmation Dialogs -->
    <v-card class="mb-6">
      <v-card-title class="bg-primary">
        <v-icon start>mdi-message-alert</v-icon>
        Confirmation Dialogs
      </v-card-title>
      <v-card-text class="pa-6">
        <div class="d-flex flex-wrap gap-3">
          <v-btn color="info" @click="infoDialog = true">Info Dialog</v-btn>
          <v-btn color="warning" @click="warningDialog = true">Warning Dialog</v-btn>
          <v-btn color="error" @click="dangerDialog = true">Danger Dialog</v-btn>
          <v-btn color="success" @click="successDialog = true">Success Dialog</v-btn>
        </div>
      </v-card-text>
    </v-card>

    <!-- Progress Indicators -->
    <v-card class="mb-6">
      <v-card-title class="bg-primary">
        <v-icon start>mdi-progress-check</v-icon>
        Progress Indicators
      </v-card-title>
      <v-card-text class="pa-6">
        <h3 class="mb-4">Linear Progress</h3>
        <ProgressIndicator
          class="mb-6"
          color="primary"
          show-label
          type="linear"
          :value="linearProgress"
        />

        <h3 class="mb-4">Circular Progress</h3>
        <div class="d-flex gap-6 mb-6">
          <ProgressIndicator
            show-label
            :size="100"
            type="circular"
            :value="circularProgress"
          />
          <ProgressIndicator
            color="success"
            show-label
            :size="80"
            type="circular"
            :value="75"
          />
        </div>

        <h3 class="mb-4">Step Progress</h3>
        <ProgressIndicator
          class="mb-4"
          :current-step="currentStep"
          show-step-labels
          :steps="['Informations', 'Documents', 'Paiement', 'Validation']"
          type="steps"
        />
        <div class="d-flex gap-2">
          <v-btn
            :disabled="currentStep === 0"
            @click="currentStep--"
          >
            Précédent
          </v-btn>
          <v-btn
            color="primary"
            :disabled="currentStep === 3"
            @click="currentStep++"
          >
            Suivant
          </v-btn>
        </div>

        <v-slider
          v-model="linearProgress"
          class="mt-6"
          label="Adjust Progress"
          max="100"
          min="0"
          step="1"
        />
      </v-card-text>
    </v-card>

    <!-- Smooth Scroll -->
    <v-card class="mb-6">
      <v-card-title class="bg-primary">
        <v-icon start>mdi-arrow-up-down</v-icon>
        Smooth Scroll
      </v-card-title>
      <v-card-text class="pa-6">
        <div class="d-flex flex-wrap gap-3">
          <v-btn @click="scrollToTop()">Scroll to Top</v-btn>
          <v-btn @click="scrollToElement('#bottom-section')">Scroll to Bottom</v-btn>
        </div>
      </v-card-text>
    </v-card>

    <!-- Debounced Search -->
    <v-card class="mb-6">
      <v-card-title class="bg-primary">
        <v-icon start>mdi-magnify</v-icon>
        Debounced Search
      </v-card-title>
      <v-card-text class="pa-6">
        <v-text-field
          v-model="searchQuery"
          clearable
          label="Type to search (500ms debounce)"
          :loading="isSearching"
          prepend-inner-icon="mdi-magnify"
          variant="outlined"
          @update:model-value="debouncedSearch"
        />
        <v-chip v-if="lastSearched" class="mt-2" color="primary">
          Last searched: {{ lastSearched }}
        </v-chip>
      </v-card-text>
    </v-card>

    <!-- Bottom Section for Scroll Demo -->
    <div id="bottom-section" class="pa-6 bg-primary rounded text-center">
      <v-icon color="white" size="64">mdi-flag-checkered</v-icon>
      <h2 class="text-h4 text-white mt-4">You've reached the bottom!</h2>
    </div>

    <!-- Confirmation Dialogs -->
    <ConfirmDialog
      v-model="infoDialog"
      message="Ceci est un dialogue d'information."
      title="Information"
      type="info"
      @confirm="handleConfirm('info')"
    />

    <ConfirmDialog
      v-model="warningDialog"
      message="Cette action nécessite votre attention."
      title="Attention"
      type="warning"
      @confirm="handleConfirm('warning')"
    />

    <ConfirmDialog
      v-model="dangerDialog"
      message="Cette action est irréversible. Êtes-vous sûr ?"
      title="Action Dangereuse"
      type="danger"
      @confirm="handleConfirm('danger')"
    />

    <ConfirmDialog
      v-model="successDialog"
      hide-cancel
      message="Opération réussie avec succès!"
      title="Félicitations"
      type="success"
      @confirm="handleConfirm('success')"
    />
  </div>
</template>

<script setup lang="ts">
  import { ref } from 'vue'
  import ConfirmDialog from '@/modules/shared/components/ui/ConfirmDialog.vue'
  import ProgressIndicator from '@/modules/shared/components/ui/ProgressIndicator.vue'
  import SkeletonLoader from '@/modules/shared/components/ui/SkeletonLoader.vue'
  import { useDebounce } from '@/modules/shared/composables/useDebounce'
  import { useSmoothScroll } from '@/modules/shared/composables/useSmoothScroll'
  import { useToast } from '@/modules/shared/composables/useToast'

  const toast = useToast()
  const { scrollToTop, scrollToElement } = useSmoothScroll()

  // Toast demos
  function showSuccessToast () {
    toast.success('Opération réussie avec succès!', 'Succès')
  }

  function showErrorToast () {
    toast.error('Une erreur s\'est produite', 'Erreur')
  }

  function showWarningToast () {
    toast.warning('Attention à cette action!', 'Avertissement')
  }

  function showInfoToast () {
    toast.info('Voici une information utile', 'Information')
  }

  // Skeleton demos
  const showSkeletons = ref(true)
  const skeletonTab = ref('card')

  function toggleSkeletons () {
    showSkeletons.value = !showSkeletons.value
    if (showSkeletons.value) {
      setTimeout(() => {
        showSkeletons.value = false
      }, 2000)
    }
  }

  // Dialog demos
  const infoDialog = ref(false)
  const warningDialog = ref(false)
  const dangerDialog = ref(false)
  const successDialog = ref(false)

  function handleConfirm (type: string) {
    toast.success(`${type.charAt(0).toUpperCase() + type.slice(1)} confirmé!`)
  }

  // Progress demos
  const linearProgress = ref(65)
  const circularProgress = ref(80)
  const currentStep = ref(1)

  // Search demo
  const searchQuery = ref('')
  const isSearching = ref(false)
  const lastSearched = ref('')

  function performSearch () {
    if (!searchQuery.value.trim()) {
      isSearching.value = false
      return
    }
    isSearching.value = true
    setTimeout(() => {
      lastSearched.value = searchQuery.value
      isSearching.value = false
      toast.info(`Recherche effectuée pour: "${searchQuery.value}"`)
    }, 1000)
  }

  const debouncedSearch = useDebounce(performSearch, 500)
</script>

<style scoped>
.gap-3 {
  gap: 12px;
}

.gap-6 {
  gap: 24px;
}
</style>
