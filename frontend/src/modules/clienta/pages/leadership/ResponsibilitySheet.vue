<template>
  <LeadershipLayout>
    <HeroProgressRow>
      <HeroCard
        :badges="[
          {
            icon: 'mdi-account-tie',
            text: `${responsibilities.length} fiches`,
            variant: 'primary',
          },
          {
            icon: 'mdi-sitemap',
            text: 'Pilotes & Copilotes',
            variant: 'secondary',
          },
        ]"
        icon="mdi-account-star"
        icon-color="pink"
        subtitle="Définissez les responsabilités des pilotes et copilotes de processus"
        title="Fiche de responsabilité"
      >
        <template #actions>
          <v-btn
            color="pink"
            prepend-icon="mdi-plus"
            variant="flat"
            @click="addRow"
          >
            Ajouter
          </v-btn>
        </template>
      </HeroCard>

    </HeroProgressRow>

    <GlassCard>
      <h2 class="section-title">
        <span class="section-number">01</span>
        Tableau des responsabilités
      </h2>

      <v-card class="mb-4" rounded="xl" variant="tonal">
        <v-card-text class="pa-4">
          <v-row dense>
            <v-col cols="12" md="5">
              <v-text-field
                v-model="listFilters.search"
                clearable
                hide-details
                label="Rechercher un processus ou un rôle"
                prepend-inner-icon="mdi-magnify"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12" md="3">
              <v-select
                v-model="listFilters.level"
                hide-details
                :items="levelFilterOptions"
                label="Niveau"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12" md="2">
              <v-select
                v-model="listFilters.process"
                hide-details
                item-title="title"
                item-value="value"
                :items="processFilterOptions"
                label="Processus"
                variant="outlined"
              />
            </v-col>
            <v-col class="d-flex align-center justify-end" cols="12" md="2">
              <v-btn
                prepend-icon="mdi-refresh"
                variant="outlined"
                @click="resetListFilters"
              >
                Réinitialiser
              </v-btn>
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>

      <div class="table-container">
        <table class="responsibility-table">
          <thead>
            <tr>
              <th style="width: 25%">Processus</th>
              <th style="width: 20%">Niveau de responsabilité</th>
              <th style="width: 30%">Rôle et responsabilité</th>
              <th style="width: 20%">Livrables</th>
              <th style="width: 5%" />
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="entry in filteredResponsibilities"
              :key="entry.row.id"
              class="table-row"
            >
              <td>
                <select
                  :ref="el => setProcessFieldRef(el as HTMLSelectElement | null, entry.index)"
                  v-model="entry.row.processus_id"
                  class="table-input"
                >
                  <option value="">Sélectionner</option>
                  <option
                    v-for="proc in processus"
                    :key="proc.id"
                    :value="proc.id"
                  >
                    {{ proc.code }} - {{ proc.title }}
                  </option>
                </select>
              </td>
              <td>
                <select v-model="entry.row.niveau" class="table-input">
                  <option value="">Sélectionner</option>
                  <option value="pilote">Pilote</option>
                  <option value="copilote">Copilote</option>
                </select>
              </td>
              <td>
                <textarea
                  v-model="entry.row.role"
                  class="table-textarea"
                  placeholder="Décrivez le rôle..."
                  rows="2"
                />
              </td>
              <td>
                <textarea
                  v-model="entry.row.livrables"
                  class="table-textarea"
                  placeholder="Documents, résultats..."
                  rows="2"
                />
              </td>
              <td>
                <v-btn
                  color="error"
                  density="comfortable"
                  :disabled="responsibilities.length === 1"
                  icon="mdi-delete"
                  size="small"
                  variant="tonal"
                  @click="removeRow(entry.index)"
                />
              </td>
            </tr>
            <tr v-if="filteredResponsibilities.length === 0">
              <td class="empty-row" colspan="5">
                {{
                  responsibilities.length === 0
                    ? "Aucune ligne disponible"
                    : "Aucune ligne ne correspond aux filtres"
                }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="table-actions">
        <v-btn
          color="pink"
          prepend-icon="mdi-plus"
          variant="outlined"
          @click="addRow"
        >
          Ajouter une ligne
        </v-btn>
        <div class="action-buttons">
          <v-btn
            color="secondary"
            prepend-icon="mdi-eye"
            variant="outlined"
            @click="lastExportedDocumentId ? handlePreviewDraft() : openGenConfig('preview')"
          >
            Prévisualiser
          </v-btn>
          <v-btn
            color="secondary"
            prepend-icon="mdi-file-pdf-box"
            variant="outlined"
            @click="lastExportedDocumentId ? handleDownloadDraft() : openGenConfig('download')"
          >
            Exporter PDF
          </v-btn>
          <v-btn
            color="warning"
            :disabled="submittingForVerification || verificationSent"
            :loading="submittingForVerification"
            prepend-icon="mdi-shield-check"
            variant="outlined"
            @click="lastExportedDocumentId ? openVerifyDialog() : openGenConfig('verify')"
          >
            Vérifier document
          </v-btn>
          <v-btn
            color="pink"
            :loading="loading"
            prepend-icon="mdi-content-save"
            variant="flat"
            @click="saveResponsibilities"
          >
            Enregistrer
          </v-btn>
        </div>
      </div>

      <div v-if="savedMessage" class="save-message">
        <v-icon color="#22c55e" size="20">mdi-check-circle</v-icon>
        {{ savedMessage }}
      </div>
    </GlassCard>

    <GlassCard v-if="responsibilities.length > 0">
      <h2 class="section-title">
        <span class="section-number">02</span>
        Aperçu par responsable
      </h2>

      <div class="summary-grid">
        <div
          v-for="summary in responsibilitySummary"
          :key="summary.niveau"
          class="summary-card"
        >
          <div class="summary-header">
            <v-icon
              :color="summary.niveau === 'pilote' ? '#5b8dd9' : '#ec4899'"
              size="28"
            >
              {{
                summary.niveau === "pilote"
                  ? "mdi-account-star"
                  : "mdi-account-supervisor"
              }}
            </v-icon>
            <div class="summary-title">
              {{ summary.niveau === "pilote" ? "Pilotes" : "Copilotes" }}
            </div>
          </div>
          <div class="summary-count">{{ summary.count }} processus</div>
          <div class="summary-list">
            <div
              v-for="item in summary.items"
              :key="item.id"
              class="summary-item"
            >
              <v-icon color="#64748b" size="16">mdi-circle-small</v-icon>
              {{ item.processus }}
            </div>
          </div>
        </div>
      </div>
    </GlassCard>
    <!-- Config génération -->
    <GeneratedDocumentConfigDialog v-model="genDialog" :site-id="siteId" title="Paramètres de la fiche de responsabilité" @confirm="onGenConfirm" />

    <!-- Dialog vérification -->
    <v-dialog v-model="verifyDialog" max-width="560">
      <v-card rounded="xl">
        <v-card-title class="pa-4">Envoyer en vérification</v-card-title>
        <v-card-text><v-alert type="info" variant="tonal">Code : {{ exportedDocumentCode || '—' }}</v-alert></v-card-text>
        <v-card-actions class="pa-4">
          <v-spacer />
          <v-btn variant="text" @click="verifyDialog = false">Annuler</v-btn>
          <v-btn color="warning" :loading="submittingForVerification" @click="submitForVerification">Confirmer</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Dialog prévisualisation -->
    <v-dialog v-model="previewDialog" fullscreen>
      <v-card>
        <v-toolbar color="primary" density="compact">
          <v-toolbar-title>Prévisualisation — {{ previewFilename }}</v-toolbar-title>
          <v-spacer />
          <v-btn icon="mdi-close" @click="closePreview" />
        </v-toolbar>
        <iframe v-if="previewBlobUrl" :src="previewBlobUrl" style="width:100%; height:calc(100vh - 48px); border:none;" />
      </v-card>
    </v-dialog>
  </LeadershipLayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'
  import api from '@/api/client'
  import GlassCard from '@/components/leadership/GlassCard.vue'
  import HeroCard from '@/components/leadership/HeroCard.vue'
  import HeroProgressRow from '@/components/leadership/HeroProgressRow.vue'
  import LeadershipLayout from '@/components/leadership/LeadershipLayout.vue'
  import { focusTopInsertedField } from '@/composables/useDynamicTopInsertFocus'
  import { leadershipService } from '@/services/leadershipService'
  import { useDocumentFlow } from '@/modules/clienta/composables/useDocumentFlow'
  import { useAuthStore } from '@/stores/auth'
  import GeneratedDocumentConfigDialog from '@/modules/clienta/components/documents/GeneratedDocumentConfigDialog.vue'

  type Responsibility = {
    id: number | string // Peut être number (DB) ou string (temporaire)
    processus_id: string
    niveau: string
    role: string
    livrables: string
  }

  type ProcessOption = {
    id: number
    code: string
    title: string
  }

  const responsibilities = ref<Responsibility[]>([
    {
      id: `new_${Date.now()}`,
      processus_id: '',
      niveau: '',
      role: '',
      livrables: '',
    },
  ])

  const lastSavedAt = ref<Date | null>(null)
  const savedMessage = ref('')
  const loading = ref(false)
  const processFieldRefs = ref<Array<HTMLSelectElement | null>>([])

  const authStore = useAuthStore()
  const siteId = computed(() => authStore.currentSite?.id ?? null)
  const genCatalogId = ref<number | null>(null)
  const genProcessId = ref<number | null>(null)
  const genDialog = ref(false)
  const genPendingAction = ref<'download' | 'preview' | 'verify'>('download')

  function openGenConfig (action: 'download' | 'preview' | 'verify') {
    genPendingAction.value = action
    genDialog.value = true
  }

  function onGenConfirm (ctx: { document_type_catalog_id: number, process_id?: number | null }) {
    genCatalogId.value = ctx.document_type_catalog_id
    genProcessId.value = ctx.process_id ?? null
    if (genPendingAction.value === 'preview') handlePreviewDraft()
    else if (genPendingAction.value === 'verify') openVerifyDialog()
    else handleDownloadDraft()
  }

  const {
    exporting,
    submittingForVerification,
    verificationSent,
    lastExportedDocumentId,
    exportedDocumentCode,
    previewDialog,
    previewBlobUrl,
    previewFilename,
    verifyDialog,
    handlePreviewDraft,
    closePreview,
    handleDownloadDraft,
    openVerifyDialog,
    submitForVerification,
  } = useDocumentFlow(
    async () => {
      const response = await api.post('/responsibilities/generate-draft', {
        document_type_catalog_id: genCatalogId.value,
        process_id: genProcessId.value,
      })
      const data = response.data?.data ?? response.data
      const attrs = data?.attributes ?? data
      const id = Number(data?.id || attrs?.id || 0) || null
      const code = String(attrs?.code || '')
      return id ? { id, code } : null
    },
    () => `Fiche_Responsabilite_${new Date().toISOString().split('T')[0]}.pdf`,
  )
  const listFilters = ref({
    search: '',
    level: 'all',
    process: 'all',
  })

  const processus = ref<ProcessOption[]>([])

  const filledRows = computed(() => {
    return responsibilities.value.filter(
      r => r.processus_id && r.niveau && r.role.trim() && r.livrables.trim(),
    ).length
  })

  const levelFilterOptions = [
    { title: 'Tous les niveaux', value: 'all' },
    { title: 'Pilote', value: 'pilote' },
    { title: 'Copilote', value: 'copilote' },
  ]
  const processFilterOptions = computed(() => [
    { title: 'Tous les processus', value: 'all' },
    ...processus.value.map(proc => ({
      title: `${proc.code} - ${proc.title}`,
      value: String(proc.id),
    })),
  ])
  const filteredResponsibilities = computed(() => {
    const query = listFilters.value.search.trim().toLowerCase()

    return responsibilities.value
      .map((row, index) => ({ row, index }))
      .filter(({ row }) => {
        const processLabel = processus.value.find(
          p => p.id === Number(row.processus_id),
        )
        const matchesSearch
          = !query
            || [row.role, row.livrables, processLabel?.code, processLabel?.title]
              .map(value => String(value || '').toLowerCase())
              .some(value => value.includes(query))

        const matchesLevel
          = listFilters.value.level === 'all'
            || row.niveau === listFilters.value.level
        const matchesProcess
          = listFilters.value.process === 'all'
            || row.processus_id === listFilters.value.process

        return matchesSearch && matchesLevel && matchesProcess
      })
  })

  function resetListFilters () {
    listFilters.value = {
      search: '',
      level: 'all',
      process: 'all',
    }
  }

  const responsibilitySummary = computed(() => {
    const pilotes = responsibilities.value.filter(
      r => r.niveau === 'pilote' && r.processus_id,
    )
    const copilotes = responsibilities.value.filter(
      r => r.niveau === 'copilote' && r.processus_id,
    )

    return [
      {
        niveau: 'pilote',
        count: pilotes.length,
        items: pilotes.map(p => ({
          id: p.id,
          processus:
            processus.value.find(pr => pr.id === Number(p.processus_id))
              ?.code || 'N/A',
        })),
      },
      {
        niveau: 'copilote',
        count: copilotes.length,
        items: copilotes.map(p => ({
          id: p.id,
          processus:
            processus.value.find(pr => pr.id === Number(p.processus_id))
              ?.code || 'N/A',
        })),
      },
    ]
  })

  function parseCollection (payload: any): any[] {
    if (Array.isArray(payload?.data)) return payload.data
    if (Array.isArray(payload)) return payload
    return []
  }

  function normalizeProcess (item: any): ProcessOption | null {
    const attrs = item?.attributes ?? item
    const id = Number(item?.id ?? attrs?.id)
    if (!Number.isFinite(id) || id <= 0) return null

    return {
      id,
      code: attrs?.code || attrs?.ref || `PROC-${id}`,
      title: attrs?.title || attrs?.name || 'Processus',
    }
  }

  function extractPositiveId (value: unknown): number | null {
    const parsedValue = Number(value)
    if (!Number.isFinite(parsedValue) || parsedValue <= 0) {
      return null
    }
    return parsedValue
  }

  function normalizeResponsibility (item: any): Responsibility | null {
    const attrs = item?.attributes ?? item
    const processId = extractPositiveId(
      item?.relationships?.process?.id ?? attrs?.process_id,
    )
    const responsibilityId = extractPositiveId(item?.id ?? attrs?.id)

    if (!responsibilityId || !processId) return null

    return {
      id: responsibilityId,
      processus_id: String(processId),
      niveau: attrs?.level || '',
      role: attrs?.roles || '',
      livrables: attrs?.deliverables || '',
    }
  }

  function setProcessFieldRef (element: HTMLSelectElement | null, index: number) {
    processFieldRefs.value[index] = element
  }

  function addRow () {
    responsibilities.value.unshift({
      id: `new_${Date.now()}`,
      processus_id: '',
      niveau: '',
      role: '',
      livrables: '',
    })
    void focusTopInsertedField(processFieldRefs.value, 0)
  }

  function removeRow (index: number) {
    if (responsibilities.value.length > 1) {
      responsibilities.value.splice(index, 1)
    }
  }

  function hasRequiredResponsibilityFields (resp: Responsibility) {
    return Boolean(
      resp.processus_id && resp.niveau && resp.role && resp.livrables,
    )
  }

  function findMissingProcessId (): string | null {
    for (const resp of responsibilities.value) {
      const processId = Number(resp.processus_id)
      const processExists
        = Number.isFinite(processId) && processId > 0
          ? processus.value.find(p => p.id === processId)
          : null
      if (!processExists) {
        return String(resp.processus_id)
      }
    }

    return null
  }

  function buildResponsibilityPayload (resp: Responsibility) {
    return {
      process_id: Number(resp.processus_id),
      level: resp.niveau,
      roles: resp.role,
      deliverables: resp.livrables,
    }
  }

  function isNewResponsibilityRow (resp: Responsibility) {
    return typeof resp.id === 'string' && resp.id.startsWith('new_')
  }

  async function persistResponsibilityRow (resp: Responsibility) {
    const data = buildResponsibilityPayload(resp)
    console.log('📤 Envoi données:', data)

    if (isNewResponsibilityRow(resp)) {
      const result = await leadershipService.createResponsibility(data)
      resp.id = Number(result?.data?.id ?? result?.id ?? resp.id)
      console.log(`✅ Ligne ${resp.id} créée avec succès`)
      return
    }

    if (typeof resp.id === 'number') {
      await leadershipService.updateResponsibility(resp.id, data)
      console.log(`✅ Ligne ${resp.id} mise à jour avec succès`)
    }
  }

  function updateSaveMessage (successCount: number, errorCount: number) {
    if (errorCount === 0) {
      savedMessage.value = `✅ ${successCount} fiche(s) de responsabilité enregistrée(s) avec succès`
      return
    }

    if (successCount > 0) {
      savedMessage.value = `⚠️ ${successCount} succès, ${errorCount} erreur(s). Voir la console pour détails.`
      return
    }

    savedMessage.value = `❌ ${errorCount} erreur(s). Voir la console pour détails.`
  }

  function collectRowError (resp: Responsibility, error: any) {
    const errorMsg = error.response?.data?.message || error.message
    const validationErrors = error.response?.data?.errors

    if (validationErrors) {
      console.error(`❌ Erreur validation ligne ${resp.id}:`, validationErrors)
      return `Ligne ${resp.id}: ${JSON.stringify(validationErrors)}`
    }

    console.error(`❌ Erreur ligne ${resp.id}:`, errorMsg)
    return `Ligne ${resp.id}: ${errorMsg}`
  }

  async function saveResponsibilities () {
    loading.value = true
    try {
      const hasInvalidRows = responsibilities.value.some(
        resp => !hasRequiredResponsibilityFields(resp),
      )
      if (hasInvalidRows) {
        alert(
          '❌ Veuillez remplir tous les champs obligatoires (processus, niveau, rôle, livrables)',
        )
        return
      }

      const missingProcessId = findMissingProcessId()
      if (missingProcessId) {
        alert(
          `❌ Le processus sélectionné (ID: ${missingProcessId}) n'existe pas`,
        )
        return
      }

      let successCount = 0
      let errorCount = 0
      const errors: string[] = []

      for (const resp of responsibilities.value) {
        try {
          await persistResponsibilityRow(resp)
          successCount++
        } catch (error: any) {
          errorCount++
          errors.push(collectRowError(resp, error))
        }
      }

      lastSavedAt.value = new Date()
      updateSaveMessage(successCount, errorCount)

      // Afficher les erreurs dans une alerte si nécessaire
      if (errors.length > 0) {
        console.error('📋 Résumé des erreurs:', errors)
      }

      setTimeout(() => {
        savedMessage.value = ''
      }, 3000)
    } catch (error: any) {
      console.error('Erreur sauvegarde:', error)
      const errorMsg
        = error.response?.data?.message || error.message || 'Erreur inconnue'
      alert(`❌ Erreur lors de la sauvegarde: ${errorMsg}`)
    } finally {
      loading.value = false
    }
  }
  onMounted(async () => {
    try {
      // Charger les responsabilités existantes
      const response = await leadershipService.getResponsibilities()
      const responsibilityItems = parseCollection(response)
      const normalizedResponsibilities = responsibilityItems
        .map((item: any) => normalizeResponsibility(item))
        .filter(
          (item: Responsibility | null): item is Responsibility => item !== null,
        )

      if (normalizedResponsibilities.length > 0) {
        responsibilities.value = normalizedResponsibilities
      }

      // Charger les processus depuis l'API
      try {
        const processResponse = await leadershipService.getProcesses()
        const processItems = parseCollection(processResponse)
        const normalizedProcesses = processItems
          .map((item: any) => normalizeProcess(item))
          .filter(
            (item: ProcessOption | null): item is ProcessOption => item !== null,
          )

        processus.value = normalizedProcesses
        console.log('✅ Processus chargés:', processus.value.length)
      } catch (error) {
        console.warn('⚠️ Erreur chargement processus:', error)
      }
    } catch (error) {
      console.error('Erreur chargement:', error)
    }
  })
</script>

<style scoped>
.section-title {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 1.25rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 16px;
}

.section-number {
  display: grid;
  place-items: center;
  width: 36px;
  height: 36px;
  border-radius: 12px;
  background: linear-gradient(135deg, #ec4899 0%, #be185d 100%);
  color: white;
  font-size: 0.95rem;
  font-weight: 700;
}

.table-container {
  overflow-x: auto;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  margin-bottom: 16px;
}

.responsibility-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
}

.responsibility-table th {
  background: #f8fafc;
  padding: 12px 10px;
  text-align: left;
  font-size: 0.8125rem;
  font-weight: 700;
  color: #475569;
  border-bottom: 2px solid #e2e8f0;
}

.responsibility-table td {
  padding: 10px;
  border-bottom: 1px solid #e2e8f0;
  vertical-align: top;
}

.table-row:hover {
  background: #f8fafc;
}

.table-input,
.table-textarea {
  width: 100%;
  padding: 6px 10px;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 0.8125rem;
  color: #1e293b;
  transition: all 0.2s;
}

.table-input:focus,
.table-textarea:focus {
  outline: none;
  border-color: #4471c4;
  box-shadow: 0 0 0 2px rgba(68, 113, 196, 0.12);
}

.table-textarea {
  resize: vertical;
  font-family: inherit;
}

.table-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
}

.action-buttons {
  display: flex;
  gap: 10px;
}

.empty-row {
  text-align: center;
  color: #64748b;
  font-style: italic;
}

.save-message {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 12px;
  padding: 10px 12px;
  background: rgba(34, 197, 94, 0.1);
  border-radius: 8px;
  color: #16a34a;
  font-weight: 600;
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.summary-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 16px;
}

.summary-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 8px;
}

.summary-title {
  font-size: 1rem;
  font-weight: 700;
  color: #1e293b;
}

.summary-count {
  font-size: 0.8125rem;
  color: #64748b;
  margin-bottom: 10px;
}

.summary-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.summary-item {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 0.8125rem;
  color: #475569;
}

:deep(.hero-progress-row) {
  grid-template-columns: 1fr;
}

:deep(.meta-badges) {
  flex-wrap: nowrap;
  gap: 10px;
}

@media (max-width: 768px) {
  .table-actions {
    flex-direction: column;
    align-items: stretch;
  }
  .action-buttons {
    flex-direction: column;
  }
  .summary-grid {
    grid-template-columns: 1fr;
  }
}
</style>
