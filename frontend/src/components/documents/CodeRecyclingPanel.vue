<template>
  <div>
    <!-- Tabs -->
    <v-tabs v-model="tab" class="mb-6" color="primary">
      <v-tab value="available">
        Codes disponibles
        <v-chip v-if="availableCodes.length > 0" class="ml-2" color="primary" size="x-small">
          {{ availableCodes.length }}
        </v-chip>
      </v-tab>
      <v-tab value="release">Libérer un code</v-tab>
      <v-tab value="history">Historique</v-tab>
    </v-tabs>

    <v-tabs-window v-model="tab">

      <!-- ── Onglet 1 : Codes disponibles ── -->
      <v-tabs-window-item value="available">
        <div class="d-flex justify-end mb-4">
          <v-btn
            :loading="loadingAvailable"
            prepend-icon="mdi-refresh"
            size="small"
            variant="outlined"
            @click="loadAvailableCodes"
          >
            Actualiser
          </v-btn>
        </div>

        <div v-if="loadingAvailable" class="d-flex justify-center py-8">
          <v-progress-circular color="primary" indeterminate />
        </div>

        <v-alert
          v-else-if="availableCodes.length === 0"
          density="compact"
          type="info"
          variant="tonal"
        >
          Aucun code disponible pour recyclage.
        </v-alert>

        <v-table v-else class="rounded border" density="comfortable">
          <thead>
            <tr>
              <th>Code</th>
              <th>Raison de libération</th>
              <th>Libéré le</th>
              <th class="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in availableCodes" :key="item.code">
              <td>
                <code class="text-body-2 font-weight-medium">{{ item.code }}</code>
              </td>
              <td>
                <v-chip :color="reasonColor(item.reason)" size="x-small" variant="tonal">
                  {{ reasonLabel(item.reason) }}
                </v-chip>
              </td>
              <td class="text-body-2 text-medium-emphasis">
                {{ formatDate(item.released_at) }}
              </td>
              <td class="text-right">
                <v-btn
                  icon="mdi-history"
                  size="x-small"
                  variant="text"
                  @click="openHistory(item.code)"
                />
              </td>
            </tr>
          </tbody>
        </v-table>
      </v-tabs-window-item>

      <!-- ── Onglet 2 : Libérer un code ── -->
      <v-tabs-window-item value="release">
        <v-card max-width="520" variant="outlined">
          <v-card-text>
            <p class="text-body-2 text-medium-emphasis mb-4">
              Libérez manuellement un code pour le rendre disponible au recyclage.
            </p>

            <v-text-field
              v-model="releaseForm.code"
              class="mb-2"
              density="comfortable"
              :error="availabilityError"
              :hint="availabilityHint"
              label="Code à libérer *"
              :loading="checkingCode"
              persistent-hint
              placeholder="ex: PRC-2024-001"
              variant="outlined"
              @update:model-value="onCodeInput"
            >
              <template #append-inner>
                <v-icon
                  v-if="codeChecked"
                  :color="codeAvailable ? 'success' : 'error'"
                >
                  {{ codeAvailable ? 'mdi-check-circle' : 'mdi-close-circle' }}
                </v-icon>
              </template>
            </v-text-field>

            <v-select
              v-model="releaseForm.reason"
              class="mb-4"
              density="comfortable"
              :items="reasonOptions"
              label="Raison *"
              variant="outlined"
            />

            <v-alert
              v-if="releaseSuccess"
              class="mb-4"
              density="compact"
              type="success"
              variant="tonal"
            >
              Code <strong>{{ releaseSuccess }}</strong> libéré avec succès.
            </v-alert>

            <v-btn
              color="primary"
              :disabled="!releaseForm.code || !releaseForm.reason || !codeAvailable"
              :loading="releasing"
              @click="doRelease"
            >
              Libérer le code
            </v-btn>
          </v-card-text>
        </v-card>
      </v-tabs-window-item>

      <!-- ── Onglet 3 : Historique ── -->
      <v-tabs-window-item value="history">
        <div class="d-flex gap-3 mb-4">
          <v-text-field
            v-model="historyCode"
            density="comfortable"
            hide-details
            label="Code à rechercher"
            placeholder="ex: PRC-2024-001"
            style="max-width: 320px;"
            variant="outlined"
            @keyup.enter="loadHistory"
          />
          <v-btn
            color="primary"
            :disabled="!historyCode"
            :loading="loadingHistory"
            variant="outlined"
            @click="loadHistory"
          >
            Rechercher
          </v-btn>
        </div>

        <div v-if="loadingHistory" class="d-flex justify-center py-8">
          <v-progress-circular color="primary" indeterminate />
        </div>

        <v-alert
          v-else-if="historySearched && history.length === 0"
          density="compact"
          type="info"
          variant="tonal"
        >
          Aucun historique pour ce code.
        </v-alert>

        <v-table v-else-if="history.length > 0" class="rounded border" density="comfortable">
          <thead>
            <tr>
              <th>Code</th>
              <th>Raison</th>
              <th>Libéré le</th>
              <th>Réutilisé le</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(entry, i) in history" :key="i">
              <td><code class="text-body-2">{{ entry.code }}</code></td>
              <td>
                <v-chip :color="reasonColor(entry.reason)" size="x-small" variant="tonal">
                  {{ reasonLabel(entry.reason) }}
                </v-chip>
              </td>
              <td class="text-body-2 text-medium-emphasis">{{ formatDate(entry.released_at) }}</td>
              <td class="text-body-2 text-medium-emphasis">
                <span v-if="entry.reused_at">{{ formatDate(entry.reused_at) }}</span>
                <v-chip v-else color="success" size="x-small" variant="tonal">Disponible</v-chip>
              </td>
            </tr>
          </tbody>
        </v-table>
      </v-tabs-window-item>

    </v-tabs-window>

    <!-- Dialog historique depuis la liste -->
    <v-dialog v-model="historyDialog" max-width="600">
      <v-card>
        <v-card-title class="d-flex justify-space-between align-center">
          <span>Historique — <code>{{ historyDialogCode }}</code></span>
          <v-btn icon size="small" variant="text" @click="historyDialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text>
          <div v-if="loadingDialogHistory" class="d-flex justify-center py-4">
            <v-progress-circular color="primary" indeterminate />
          </div>
          <v-table v-else density="compact">
            <thead>
              <tr>
                <th>Raison</th>
                <th>Libéré le</th>
                <th>Réutilisé le</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(entry, i) in dialogHistory" :key="i">
                <td>
                  <v-chip :color="reasonColor(entry.reason)" size="x-small" variant="tonal">
                    {{ reasonLabel(entry.reason) }}
                  </v-chip>
                </td>
                <td class="text-body-2">{{ formatDate(entry.released_at) }}</td>
                <td class="text-body-2">
                  <span v-if="entry.reused_at">{{ formatDate(entry.reused_at) }}</span>
                  <v-chip v-else color="success" size="x-small" variant="tonal">Disponible</v-chip>
                </td>
              </tr>
            </tbody>
          </v-table>
        </v-card-text>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup lang="ts">
  import { onMounted, ref } from 'vue'
  import { type CodeHistoryEntry, documentCodeRecyclingApi, type ReleasedCode } from '@/api/documentCodeRecycling'

  const tab = ref('available')

  // ── Codes disponibles ──
  const availableCodes = ref<ReleasedCode[]>([])
  const loadingAvailable = ref(false)

  async function loadAvailableCodes () {
    loadingAvailable.value = true
    try {
      const res = await documentCodeRecyclingApi.getAvailableCodes()
      availableCodes.value = res.data.codes
    } finally {
      loadingAvailable.value = false
    }
  }

  // ── Libération ──
  const releaseForm = ref({ code: '', reason: '' as 'rejected' | 'deleted' | 'manual' | '' })
  const releasing = ref(false)
  const releaseSuccess = ref<string | null>(null)
  const checkingCode = ref(false)
  const codeChecked = ref(false)
  const codeAvailable = ref(false)
  const availabilityHint = ref('')
  const availabilityError = ref(false)

  let checkTimeout: ReturnType<typeof setTimeout> | null = null

  function onCodeInput () {
    codeChecked.value = false
    availabilityHint.value = ''
    availabilityError.value = false
    releaseSuccess.value = null
    if (checkTimeout) clearTimeout(checkTimeout)
    if (!releaseForm.value.code.trim()) return
    checkTimeout = setTimeout(checkCode, 500)
  }

  async function checkCode () {
    checkingCode.value = true
    try {
      const res = await documentCodeRecyclingApi.checkAvailability(releaseForm.value.code.trim())
      codeAvailable.value = res.data.available
      codeChecked.value = true
      if (res.data.available) {
        availabilityHint.value = 'Ce code est disponible pour libération.'
        availabilityError.value = false
      } else {
        availabilityHint.value = 'Ce code est actuellement utilisé par un document actif.'
        availabilityError.value = true
      }
    } catch {
      codeChecked.value = false
    } finally {
      checkingCode.value = false
    }
  }

  async function doRelease () {
    if (!releaseForm.value.code || !releaseForm.value.reason) return
    releasing.value = true
    try {
      await documentCodeRecyclingApi.releaseCode({
        code: releaseForm.value.code.trim(),
        reason: releaseForm.value.reason,
      })
      releaseSuccess.value = releaseForm.value.code
      releaseForm.value = { code: '', reason: '' }
      codeChecked.value = false
      availabilityHint.value = ''
      await loadAvailableCodes()
    } finally {
      releasing.value = false
    }
  }

  // ── Historique (onglet) ──
  const historyCode = ref('')
  const history = ref<CodeHistoryEntry[]>([])
  const loadingHistory = ref(false)
  const historySearched = ref(false)

  async function loadHistory () {
    if (!historyCode.value.trim()) return
    loadingHistory.value = true
    historySearched.value = false
    try {
      const res = await documentCodeRecyclingApi.getCodeHistory(historyCode.value.trim())
      history.value = res.data.history
      historySearched.value = true
    } finally {
      loadingHistory.value = false
    }
  }

  // ── Historique (dialog depuis liste) ──
  const historyDialog = ref(false)
  const historyDialogCode = ref('')
  const dialogHistory = ref<CodeHistoryEntry[]>([])
  const loadingDialogHistory = ref(false)

  async function openHistory (code: string) {
    historyDialogCode.value = code
    historyDialog.value = true
    loadingDialogHistory.value = true
    try {
      const res = await documentCodeRecyclingApi.getCodeHistory(code)
      dialogHistory.value = res.data.history
    } finally {
      loadingDialogHistory.value = false
    }
  }

  // ── Helpers ──
  const reasonOptions = [
    { title: 'Rejet', value: 'rejected' },
    { title: 'Suppression', value: 'deleted' },
    { title: 'Manuel', value: 'manual' },
  ]

  function reasonLabel (reason: string): string {
    return { rejected: 'Rejet', deleted: 'Suppression', manual: 'Manuel' }[reason] ?? reason
  }

  function reasonColor (reason: string): string {
    return { rejected: 'error', deleted: 'warning', manual: 'info' }[reason] ?? 'default'
  }

  function formatDate (date: string): string {
    return new Date(date).toLocaleDateString('fr-FR', {
      day: '2-digit', month: 'short', year: 'numeric',
      hour: '2-digit', minute: '2-digit',
    })
  }

  onMounted(loadAvailableCodes)
</script>
