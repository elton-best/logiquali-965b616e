/** * Audit Details Page * View and manage a single audit with findings */

<script setup lang="ts">
  import {

    AlertTriangle,
    ArrowLeft,
    Building,
    Calendar,
    CheckCircle,
    ClipboardCheck,
    Clock,
    Download,

    FileText,
    Info,
    Plus,
    Trash2,
    TrendingUp,
    Upload,
    Users,
  } from 'lucide-vue-next'
  import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import api from '@/api/client'
  import { auditsService, type AuditStatus, type CreateFindingDTO } from '@/api/services/audits.service'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import { useAudits } from '@/modules/clienta/composables/useAudits'
  import { useToast } from '@/modules/shared/composables/useToast'
  import { getErrorMessage } from '@/utils/errorMessage'
  import DocumentFlowButton from '@/components/documents/DocumentFlowButton.vue'

  const router = useRouter()
  const route = useRoute()
  const toast = useToast()
  const {
    currentAudit,
    findings,
    loading,
    fetchAudit,
    fetchFindings,
    addFinding,
    generateReport,
    approveReport,
    updateStatus,
    downloadReport,
    uploadExternalReport,
    completeAudit,
    deleteAudit,
  } = useAudits()

  const auditId = computed(() => Number((route.params as { id: string }).id))
  const showFindingModal = ref(false)
  const externalReportInput = ref<HTMLInputElement | null>(null)
  const externalReportVersion = ref<number | null>(null)
  const reportPreviewUrl = ref<string | null>(null)
  const reportPreviewLoading = ref(false)

  const auditGenerateDraftFn = async () => {
    const result = await generateReport(auditId.value, 'pdf')
    if (!result.generatedDocumentId) return null
    const docRes = await api.get(`/documents/${result.generatedDocumentId}`)
    const code = String(docRes.data?.data?.code || docRes.data?.code || '')
    lastExportedDocumentId.value = result.generatedDocumentId
    exportedDocumentCode.value = code
    await fetchAudit(auditId.value)
    return { id: result.generatedDocumentId, code }
  }

  const findingForm = ref({
    type: 'non_conformity' as CreateFindingDTO['type'],
    clause_iso: '',
    title: '',
    description: '',
    evidence: '',
    severity: 'minor' as CreateFindingDTO['severity'],
    priority: 3,
  })

  const findingTypes = [
    { value: 'non_conformity', label: 'Non-conformité', icon: AlertTriangle, color: 'red' },
    { value: 'observation', label: 'Observation', icon: Info, color: 'yellow' },
    { value: 'opportunity', label: 'Opportunité d\'amélioration', icon: TrendingUp, color: 'green' },
  ]

  const severities = [
    { value: 'critical', label: 'Critique', color: 'red' },
    { value: 'major', label: 'Majeure', color: 'orange' },
    { value: 'minor', label: 'Mineure', color: 'yellow' },
  ]

  const statusConfig: Record<string, { color: string, icon: any, label: string }> = {
    planned: { color: 'bg-gray-100 text-gray-800', icon: Calendar, label: 'Planifié' },
    in_progress: { color: 'bg-blue-100 text-blue-800', icon: Clock, label: 'En cours' },
    report_draft: { color: 'bg-yellow-100 text-yellow-800', icon: FileText, label: 'Rapport en rédaction' },
    report_approved: { color: 'bg-green-100 text-green-800', icon: CheckCircle, label: 'Rapport approuvé' },
    completed: { color: 'bg-green-100 text-green-800', icon: CheckCircle, label: 'Terminé' },
    closed: { color: 'bg-gray-100 text-gray-800', icon: CheckCircle, label: 'Clôturé' },
  }

  const typeColors: Record<string, string> = {
    interne: 'bg-blue-100 text-blue-800',
    internal: 'bg-blue-100 text-blue-800',
    externe: 'bg-purple-100 text-purple-800',
    external: 'bg-purple-100 text-purple-800',
    certification: 'bg-green-100 text-green-800',
  }

  const auditReference = computed(() => currentAudit.value?.reference || currentAudit.value?.ref || `AUD-${auditId.value}`)
  const auditDisplayType = computed(() => {
    const type = String(currentAudit.value?.type || '').trim()
    const labels: Record<string, string> = {
      interne: 'Interne',
      internal: 'Interne',
      externe: 'Externe',
      external: 'Externe',
      certification: 'Certification',
      system: 'Système',
      process: 'Processus',
      product: 'Produit',
      supplier: 'Fournisseur',
      thematic: 'Thématique',
      surveillance: 'Surveillance',
    }
    return labels[type] || type || '—'
  })
  const auditPlannedDate = computed(() => currentAudit.value?.planned_date || currentAudit.value?.audit_date || '')
  const auditLeadAuditorName = computed(() =>
    currentAudit.value?.lead_auditor?.name
    || currentAudit.value?.leadAuditor?.name
    || '—',
  )
  const auditSiteName = computed(() =>
    currentAudit.value?.site?.name
    || 'Site non défini',
  )
  const auditProgramEntity = computed(() =>
    currentAudit.value?.program
    || currentAudit.value?.audit_program
    || null,
  )
  const auditProgramTitle = computed(() =>
    auditProgramEntity.value?.title
    || 'Aucun programme rattaché',
  )
  const auditProgramReference = computed(() =>
    auditProgramEntity.value?.ref
    || '',
  )
  const auditProcesses = computed(() =>
    Array.isArray(currentAudit.value?.processes) ? currentAudit.value.processes : [],
  )
  const auditAuditees = computed(() =>
    Array.isArray(currentAudit.value?.auditees_details) && currentAudit.value.auditees_details.length > 0
      ? currentAudit.value.auditees_details
      : (Array.isArray(currentAudit.value?.auditees) ? currentAudit.value.auditees : []),
  )
  const auditTeamMembers = computed(() => {
    if (Array.isArray(currentAudit.value?.auditors_details) && currentAudit.value.auditors_details.length > 0) {
      return currentAudit.value.auditors_details
    }

    if (Array.isArray(currentAudit.value?.auditors) && currentAudit.value.auditors.length > 0) {
      return currentAudit.value.auditors
    }

    const teamMembers = (currentAudit.value as any)?.team_members
    if (Array.isArray(teamMembers) && teamMembers.length > 0) {
      return teamMembers
        .map((member: any, index: number) => ({
          id: Number(member?.id || index),
          name: String(member?.name || member || '').trim(),
        }))
        .filter((member: any) => member.name)
    }

    return []
  })
  const auditM9D2 = computed<Record<string, any>>(() => {
    const raw = currentAudit.value?.m9_d2_traceability
    return raw && typeof raw === 'object' ? raw as Record<string, any> : {}
  })
  const auditM9D2Schedule = computed<Array<Record<string, string>>>(() => {
    const schedule = auditM9D2.value.schedule
    if (!Array.isArray(schedule)) return []
    return schedule.map((row: any) => ({
      date_time: String(row?.date_time || '').trim(),
      object: String(row?.object || '').trim(),
      applicable_clauses: String(row?.applicable_clauses || '').trim(),
      auditee: String(row?.auditee || '').trim(),
      audit_team: String(row?.audit_team || '').trim(),
    }))
  })
  const auditReportAvailable = computed(() =>
    Boolean(currentAudit.value?.report_path || currentAudit.value?.global_report_path || currentAudit.value?.external_report_path),
  )
  const auditReportFileName = computed(() => {
    const path = currentAudit.value?.external_report_path
      || currentAudit.value?.report_path
      || currentAudit.value?.global_report_path
      || ''

    return path ? String(path).split('/').pop() || 'rapport-audit' : 'rapport-audit'
  })
  const auditReportIsExternal = computed(() => Boolean(currentAudit.value?.external_report_path))
  const auditReportPreviewable = computed(() => {
    const path = currentAudit.value?.external_report_path
      || currentAudit.value?.report_path
      || ''

    if (!path) return false
    return String(path).toLowerCase().endsWith('.pdf')
  })
  const auditFindingsCount = computed(() =>
    Number(currentAudit.value?.findings_count ?? findings.value.length ?? 0),
  )
  const auditNcCount = computed(() => {
    if (typeof currentAudit.value?.ncs_count === 'number') {
      return currentAudit.value.ncs_count
    }

    const findingsNcCount = findings.value.filter(finding =>
      ['nc_major', 'nc_minor', 'non_conformity'].includes(String(finding.type || '')),
    ).length

    if (findingsNcCount > 0) {
      return findingsNcCount
    }

    return Array.isArray(currentAudit.value?.nonConformities) ? currentAudit.value.nonConformities.length : 0
  })

  const canGenerateReport = computed(() => {
    return ['in_progress', 'report_draft'].includes(String(currentAudit.value?.status || ''))
  })

  const canApprove = computed(() => {
    return currentAudit.value?.status === 'report_draft'
  })

  const canAddFinding = computed(() => {
    return (
      currentAudit.value?.status === 'in_progress'
      || currentAudit.value?.status === 'report_draft'
    )
  })

  function getStatusConfig (status: string | undefined): {
    color: string
    icon: any
    label: string
  } {
    return (statusConfig[status ?? 'planned'] || statusConfig.planned)!
  }

  function getFindingTypeConfig (type: string | undefined): {
    value: string
    label: string
    icon: any
    color: string
  } {
    return (findingTypes.find(t => t.value === type) || findingTypes[0])!
  }

  function getSeverityColor (severity: string) {
    const sev = severities.find(s => s.value === severity)
    return sev?.color || 'gray'
  }

  function formatDate (date: string) {
    return new Date(date).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'long',
      year: 'numeric',
    })
  }

  async function loadData () {
    await Promise.all([
      fetchAudit(auditId.value),
      fetchFindings(auditId.value),
    ])

    await loadReportPreview()
  }

  function clearReportPreview () {
    if (reportPreviewUrl.value) {
      URL.revokeObjectURL(reportPreviewUrl.value)
      reportPreviewUrl.value = null
    }
  }

  async function loadReportPreview () {
    clearReportPreview()

    if (!auditReportAvailable.value || !auditReportPreviewable.value) {
      return
    }

    reportPreviewLoading.value = true

    try {
      const blob = await auditsService.getReportBlob(auditId.value, {
        external: auditReportIsExternal.value,
        format: 'pdf',
      })
      reportPreviewUrl.value = URL.createObjectURL(blob)
    } catch (error) {
      console.error('Failed to load report preview:', error)
    } finally {
      reportPreviewLoading.value = false
    }
  }

  async function handleAddFinding () {
    try {
      const mappedSeverity: CreateFindingDTO['severity'] = findingForm.value.severity === 'critical'
        ? 'critical'
        : (findingForm.value.severity === 'major' ? 'major' : 'minor')

      await addFinding(auditId.value, {
        type: findingForm.value.type,
        standard_clause: findingForm.value.clause_iso || 'N/A',
        title: findingForm.value.title,
        description: findingForm.value.description,
        evidence: findingForm.value.evidence || undefined,
        severity: mappedSeverity,
      })
      showFindingModal.value = false
      resetFindingForm()
    } catch (error) {
      console.error('Failed to add finding:', error)
      toast.error(getErrorMessage(error, 'Impossible d’ajouter le constat.'))
    }
  }

  function resetFindingForm () {
    findingForm.value = {
      type: 'non_conformity',
      clause_iso: '',
      title: '',
      description: '',
      evidence: '',
      severity: 'minor',
      priority: 3,
    }
  }

  async function handleUpdateStatus (status: string) {
    const allowedStatuses: AuditStatus[] = ['planned', 'in_progress', 'report_draft', 'report_approved', 'completed', 'closed']
    if (!allowedStatuses.includes(status as AuditStatus)) return

    try {
      await updateStatus(auditId.value, status as AuditStatus)
      await fetchAudit(auditId.value)
    } catch (error) {
      toast.error(getErrorMessage(error, 'Impossible de mettre à jour le statut de l’audit.'))
    }
  }
  async function handleApprove () {
    if (confirm('Approuver le rapport d\'audit ?')) {
      try {
        await approveReport(auditId.value)
        toast.success('Rapport approuvé.')
      } catch (error) {
        console.error('Failed to approve report:', error)
        toast.error(getErrorMessage(error, 'Impossible d’approuver le rapport.'))
      }
    }
  }

  async function handleDownloadReport () {
    if (!currentAudit.value) return
    try {
      const ref = currentAudit.value.reference || currentAudit.value.ref || auditId.value
      await downloadReport(currentAudit.value.id, `rapport-audit-${ref}.pdf`)
    } catch (error) {
      console.error('Failed to download report:', error)
      toast.error(getErrorMessage(error, 'Impossible de télécharger le rapport.'))
    }
  }

  async function handleDelete () {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet audit ?')) {
      try {
        await deleteAudit(auditId.value)
        toast.success('Audit supprimé.')
        router.push('/company/audits')
      } catch (error) {
        console.error('Failed to delete audit:', error)
        toast.error(getErrorMessage(error, 'Impossible de supprimer cet audit.'))
      }
    }
  }

  async function handleCompleteAudit () {
    if (!confirm('Clôturer cet audit et générer automatiquement le rapport ?'))
      return

    try {
      await completeAudit(auditId.value)
      await loadData()
      toast.success('Audit clôturé. Rapport généré automatiquement.')
    } catch (error) {
      console.error('Failed to complete audit:', error)
      toast.error(getErrorMessage(error, 'Impossible de clôturer l’audit.'))
    }
  }

  function openExternalReportPicker () {
    externalReportInput.value?.click()
  }

  async function handleExternalReportSelected (event: Event) {
    const input = event.target as HTMLInputElement
    const file = input.files?.[0]
    if (!file) return

    try {
      await uploadExternalReport(
        auditId.value,
        file,
        externalReportVersion.value || undefined,
      )
      await loadData()
      toast.success('Rapport externe importé.')
    } catch (error) {
      console.error('Failed to upload external report:', error)
      toast.error(
        getErrorMessage(error, 'Impossible d’importer le rapport externe.'),
      )
    } finally {
      input.value = ''
    }
  }

  onMounted(() => {
    loadData()
  })

  onBeforeUnmount(() => {
    clearReportPreview()
  })
</script>

<template>
  <ClientALayout current-page="audits">
    <v-container class="audit-details-page pa-4 pa-md-6" fluid>
      <PageHeader icon="mdi-clipboard-check-outline" :title="currentAudit ? auditReference : 'Détail audit'">
        <template #subtitle>
          {{ currentAudit?.title || 'Chargement du dossier d’audit...' }}
        </template>
        <template #actions>
          <v-btn prepend-icon="mdi-arrow-left" rounded="lg" variant="outlined" @click="router.back()">
            Retour
          </v-btn>
          <v-btn
            v-if="auditReportAvailable"
            color="success"
            prepend-icon="mdi-download"
            rounded="lg"
            @click="handleDownloadReport"
          >
            Télécharger le rapport
          </v-btn>
          <DocumentFlowButton
            :generate-draft-fn="auditGenerateDraftFn"
            download-filename="rapport-audit.pdf"
            :workflow-status="currentAudit?.report_document_workflow_status ?? null"
          />
          <v-btn
            v-if="canApprove"
            color="success"
            prepend-icon="mdi-check-circle-outline"
            rounded="lg"
            variant="tonal"
            @click="handleApprove"
          >
            Approuver
          </v-btn>
          <v-btn
            v-if="currentAudit?.status === 'in_progress'"
            color="indigo"
            prepend-icon="mdi-flag-checkered"
            rounded="lg"
            variant="tonal"
            @click="handleCompleteAudit"
          >
            Clôturer
          </v-btn>
          <v-btn
            color="error"
            prepend-icon="mdi-delete-outline"
            rounded="lg"
            variant="text"
            @click="handleDelete"
          >
            Supprimer
          </v-btn>
        </template>
      </PageHeader>

      <!-- <input
        ref="externalReportInput"
        accept=".pdf,.doc,.docx"
        class="hidden"
        type="file"
        @change="handleExternalReportSelected"
      > -->

      <div v-if="loading && !currentAudit" class="loading-state">
        <Clock class="animate-spin text-medium-emphasis" :size="42" />
        <p>Chargement de l’audit...</p>
      </div>

      <template v-else-if="currentAudit">
        <v-card class="hero-card mb-6" elevation="0" rounded="xl">
          <v-card-text class="pa-5 pa-md-7">
            <div class="hero-grid">
              <div>
                <div class="hero-kicker mb-2">Dossier d’audit</div>
                <h2 class="text-h4 font-weight-black mb-3">{{ currentAudit.title }}</h2>
                <div class="hero-chips">
                  <v-chip class="hero-chip" :color="typeColors[currentAudit.type] ? undefined : 'primary'" rounded="lg">
                    {{ auditDisplayType }}
                  </v-chip>
                  <v-chip class="hero-chip" rounded="lg" variant="outlined">
                    {{ auditSiteName }}
                  </v-chip>
                  <v-chip class="hero-chip" rounded="lg" variant="outlined">
                    {{ auditPlannedDate ? formatDate(auditPlannedDate) : 'Date non définie' }}
                  </v-chip>
                  <v-chip
                    v-if="auditProgramReference || auditProgramEntity"
                    class="hero-chip"
                    color="primary"
                    rounded="lg"
                    variant="tonal"
                  >
                    {{ auditProgramReference || 'Programme annuel' }}
                  </v-chip>
                </div>
              </div>
              <div class="hero-side">
                <div class="hero-side-card">
                  <span>Statut</span>
                  <strong>{{ getStatusConfig(currentAudit?.status).label }}</strong>
                </div>
                <div class="hero-side-card">
                  <span>Auditeur principal</span>
                  <strong>{{ auditLeadAuditorName }}</strong>
                </div>
                <div class="hero-side-card">
                  <span>Constats / NC</span>
                  <strong>{{ auditFindingsCount }} / {{ auditNcCount }}</strong>
                </div>
              </div>
            </div>
          </v-card-text>
        </v-card>

        <div
          v-if="currentAudit.report_source === 'auto' && (currentAudit.status === 'completed' || currentAudit.status === 'closed')"
          class="info-banner success mb-6"
        >
          Rapport généré automatiquement après clôture de l’audit.
        </div>

        <v-row>
          <v-col cols="12" lg="8">
            <v-card class="section-card mb-6" elevation="0" rounded="xl">
              <v-card-title class="section-head">Informations générales</v-card-title>
              <v-card-text class="pa-5">
                <div class="info-grid">
                  <div class="info-item">
                    <span>Référence</span>
                    <strong>{{ auditReference }}</strong>
                  </div>
                  <div class="info-item">
                    <span>Type</span>
                    <strong>{{ auditDisplayType }}</strong>
                  </div>
                  <div class="info-item">
                    <span>Site</span>
                    <strong>{{ auditSiteName }}</strong>
                  </div>
                  <div class="info-item">
                    <span>Date prévue</span>
                    <strong>{{ auditPlannedDate ? formatDate(auditPlannedDate) : '—' }}</strong>
                  </div>
                  <div class="info-item">
                    <span>Programme d'audit</span>
                    <strong>{{ auditProgramTitle }}</strong>
                  </div>
                  <div class="info-item">
                    <span>Auditeur principal</span>
                    <strong>{{ auditLeadAuditorName }}</strong>
                  </div>
                </div>

                <div class="content-block mt-5">
                  <div class="content-label">Périmètre</div>
                  <div class="content-value">{{ currentAudit.scope || 'Aucun périmètre renseigné.' }}</div>
                </div>

                <div class="content-block mt-5">
                  <div class="content-label">Objectifs</div>
                  <div class="content-value">{{ currentAudit.objectives || 'Aucun objectif renseigné.' }}</div>
                </div>

                <div
                  v-if="auditProgramEntity"
                  class="content-block content-block--tinted mt-5"
                >
                  <div class="content-label">Ancrage dans le programme annuel</div>
                  <div class="content-value">
                    {{ auditProgramTitle }}
                    <span v-if="auditProgramReference" class="inline-meta">({{ auditProgramReference }})</span>
                  </div>
                </div>

                <div class="content-stack mt-5">
                  <div class="content-block">
                    <div class="content-label">Processus audités</div>
                    <div class="content-value">
                      {{ auditProcesses.length > 0 ? auditProcesses.map((process: any) => process.title || process.name).join(', ') : 'Aucun processus rattaché.' }}
                    </div>
                  </div>
                  <div class="content-block">
                    <div class="content-label">Audités</div>
                    <div class="content-value">
                      {{ auditAuditees.length > 0 ? auditAuditees.map((auditee: any) => auditee.name || auditee.full_name || auditee.email).join(', ') : 'Aucun audité renseigné.' }}
                    </div>
                  </div>
                </div>

                <div class="content-block content-block--tinted mt-5">
                  <div class="content-label">Canevas  - Plan d'audit</div>
                  <div class="content-stack mt-3">
                    <div class="content-block">
                      <div class="content-label">Adresse de l’organisme</div>
                      <div class="content-value">{{ auditM9D2.address || 'Non renseignée' }}</div>
                    </div>
                    <div class="content-block">
                      <div class="content-label">Date(s) d’audit</div>
                      <div class="content-value">{{ auditM9D2.audit_dates || (auditPlannedDate ? formatDate(auditPlannedDate) : 'Non renseignées') }}</div>
                    </div>
                    <div class="content-block">
                      <div class="content-label">Type audit</div>
                      <div class="content-value">{{ auditM9D2.audit_type || auditDisplayType }}</div>
                    </div>
                    <div class="content-block">
                      <div class="content-label">Critères d’audit</div>
                      <div class="content-value">{{ auditM9D2.criteria || 'Non renseignés' }}</div>
                    </div>
                  </div>

                  <div v-if="auditM9D2Schedule.length > 0" class="table-scroll-shell mt-4">
                    <v-data-table
                      class="linked-table"
                      :headers="[
                        { title: 'Date/heure', key: 'date_time' },
                        { title: 'Objet', key: 'object' },
                        { title: 'Clauses applicables', key: 'applicable_clauses' },
                        { title: 'Audité', key: 'auditee' },
                        { title: 'Équipe d’audit', key: 'audit_team' },
                      ]"
                      hide-default-footer
                      :items="auditM9D2Schedule"
                      items-per-page="-1"
                    />
                  </div>
                </div>

                <div v-if="currentAudit.observations || currentAudit.recommendations || currentAudit.conclusion" class="content-stack mt-5">
                  <div v-if="currentAudit.observations" class="content-block">
                    <div class="content-label">Observations</div>
                    <div class="content-value">{{ currentAudit.observations }}</div>
                  </div>
                  <div v-if="currentAudit.recommendations" class="content-block">
                    <div class="content-label">Recommandations</div>
                    <div class="content-value">{{ currentAudit.recommendations }}</div>
                  </div>
                  <div v-if="currentAudit.conclusion" class="content-block">
                    <div class="content-label">Conclusion</div>
                    <div class="content-value">{{ currentAudit.conclusion }}</div>
                  </div>
                </div>
              </v-card-text>
            </v-card>

            <v-card class="section-card mb-6" elevation="0" rounded="xl">
              <v-card-title class="section-head d-flex align-center justify-space-between">
                <span>Constats d’audit</span>
                <v-btn
                  v-if="canAddFinding"
                  color="primary"
                  prepend-icon="mdi-plus"
                  rounded="lg"
                  @click="showFindingModal = true"
                >
                  Ajouter un constat
                </v-btn>
              </v-card-title>
              <v-card-text class="pa-5">
                <div v-if="findings.length === 0" class="empty-findings">
                  Aucun constat enregistré pour le moment.
                </div>

                <div v-else class="finding-list">
                  <div
                    v-for="finding in findings"
                    :key="finding.id"
                    class="finding-card"
                  >
                    <div class="finding-top">
                      <div class="finding-badges">
                        <v-chip size="small" variant="tonal">
                          {{ getFindingTypeConfig(finding.type).label }}
                        </v-chip>
                        <v-chip
                          v-if="finding.type === 'non_conformity'"
                          :color="getSeverityColor(finding.severity)"
                          size="small"
                          variant="tonal"
                        >
                          {{ finding.severity }}
                        </v-chip>
                      </div>
                      <span class="finding-clause">{{ finding.standard_clause || 'Clause non précisée' }}</span>
                    </div>
                    <div class="finding-title">{{ finding.title }}</div>
                    <div class="finding-description">{{ finding.description }}</div>
                  </div>
                </div>
              </v-card-text>
            </v-card>
          </v-col>

          <v-col cols="12" lg="4">
            <v-card class="section-card mb-6" elevation="0" rounded="xl">
              <v-card-title class="section-head">Pilotage</v-card-title>
              <v-card-text class="pa-5">
                <div class="status-grid">
                  <button
                    v-for="(config, status) in statusConfig"
                    :key="status"
                    class="status-step"
                    :class="{ 'status-step--active': currentAudit.status === status }"
                    :disabled="loading"
                    @click="handleUpdateStatus(status)"
                  >
                    <component :is="config.icon" :size="18" />
                    <span>{{ config.label }}</span>
                  </button>
                </div>
              </v-card-text>
            </v-card>

            <v-card class="section-card mb-6" elevation="0" rounded="xl">
              <v-card-title class="section-head">Rapport d’audit</v-card-title>
              <v-card-text class="pa-5">
                <div class="info-grid single">
                  <div class="info-item">
                    <span>Source</span>
                    <strong>{{ currentAudit.report_source || 'auto' }}</strong>
                  </div>
                  <div class="info-item">
                    <span>Version</span>
                    <strong>v{{ currentAudit.report_version || 1 }}</strong>
                  </div>
                  <div class="info-item">
                    <span>Fichier</span>
                    <strong>{{ auditReportAvailable ? auditReportFileName : 'Aucun rapport disponible' }}</strong>
                  </div>
                </div>

                <div class="upload-panel mt-4">
                  <v-text-field
                    v-model.number="externalReportVersion"
                    label="Version du rapport externe"
                    min="1"
                    rounded="lg"
                    type="number"
                    variant="outlined"
                  />
                  <v-btn
                    block
                    prepend-icon="mdi-upload-outline"
                    rounded="lg"
                    variant="outlined"
                    @click="openExternalReportPicker"
                  >
                    Importer un rapport externe
                  </v-btn>
                </div>

                <div class="report-preview mt-5">
                  <div class="report-preview-head">
                    <div>
                      <div class="content-label">Prévisualisation du rapport</div>
                      <div class="report-preview-subtitle">
                        Consultez le rapport directement ici ou téléchargez-le.
                      </div>
                    </div>
                    <v-btn
                      v-if="auditReportAvailable"
                      color="primary"
                      prepend-icon="mdi-download"
                      rounded="lg"
                      size="small"
                      variant="tonal"
                      @click="handleDownloadReport"
                    >
                      Télécharger
                    </v-btn>
                  </div>

                  <div v-if="!auditReportAvailable" class="preview-empty">
                    Aucun rapport n’est encore disponible pour cet audit.
                  </div>
                  <div v-else-if="reportPreviewLoading" class="preview-empty">
                    Chargement de la prévisualisation...
                  </div>
                  <div v-else-if="reportPreviewUrl" class="preview-frame-shell">
                    <iframe
                      class="preview-frame"
                      :src="reportPreviewUrl"
                      title="Prévisualisation du rapport d'audit"
                    />
                  </div>
                  <div v-else class="preview-empty">
                    Prévisualisation indisponible pour ce format de fichier. Utilisez le téléchargement pour consulter le rapport.
                  </div>
                </div>
              </v-card-text>
            </v-card>

            <v-card class="section-card" elevation="0" rounded="xl">
              <v-card-title class="section-head">Équipe d’audit</v-card-title>
              <v-card-text class="pa-5">
                <div class="content-block">
                  <div class="content-label">Auditeur principal</div>
                  <div class="content-value">{{ auditLeadAuditorName }}</div>
                </div>
                <div v-if="auditTeamMembers.length > 0" class="team-list mt-4">
                  <div v-for="auditor in auditTeamMembers" :key="auditor.id" class="team-member">
                    <Users :size="16" />
                    <span>{{ auditor.name }}</span>
                  </div>
                </div>
                <div v-else class="empty-findings mt-4">
                  Aucune équipe complémentaire renseignée.
                </div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </template>

      <v-dialog v-model="showFindingModal" max-width="900">
        <v-card class="modal-card" rounded="xl">
          <v-card-title class="modal-title d-flex align-center justify-space-between">
            <div class="d-flex align-center ga-3">
              <v-avatar color="primary" size="40" variant="tonal">
                <v-icon>mdi-clipboard-text-outline</v-icon>
              </v-avatar>
              <div>
                <div class="text-subtitle-1 font-weight-bold">Ajouter un constat</div>
                <div class="text-caption text-medium-emphasis">Décrivez l’écart et la preuve associée.</div>
              </div>
            </div>
            <v-btn icon="mdi-close" variant="text" @click="showFindingModal = false" />
          </v-card-title>
          <v-divider />
          <v-card-text>
            <v-form @submit.prevent="handleAddFinding">
              <v-row dense>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="findingForm.type"
                    item-title="label"
                    item-value="value"
                    :items="findingTypes"
                    label="Type *"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-select
                    v-if="findingForm.type === 'non_conformity'"
                    v-model="findingForm.severity"
                    item-title="label"
                    item-value="value"
                    :items="severities"
                    label="Gravité *"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12">
                  <v-text-field
                    v-model="findingForm.clause_iso"
                    label="Clause de la norme"
                    placeholder="Ex: 8.5.1"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12">
                  <v-text-field
                    v-model="findingForm.title"
                    label="Titre *"
                    required
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12">
                  <v-textarea
                    v-model="findingForm.description"
                    label="Description *"
                    required
                    rows="4"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12">
                  <v-textarea
                    v-model="findingForm.evidence"
                    label="Preuve"
                    rows="2"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12" md="4">
                  <v-text-field
                    v-model.number="findingForm.priority"
                    label="Priorité *"
                    max="5"
                    min="1"
                    required
                    type="number"
                    variant="outlined"
                  />
                </v-col>
              </v-row>
            </v-form>
          </v-card-text>
          <v-card-actions class="px-6 pb-6">
            <v-spacer />
            <v-btn variant="text" @click="showFindingModal = false">Annuler</v-btn>
            <v-btn color="primary" :loading="loading" @click="handleAddFinding">Ajouter</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-container>
  </ClientALayout>
</template>

<style scoped>
.audit-details-page {
  --card-border: rgba(15, 23, 42, 0.08);
}

.hero-card,
.section-card,
.modal-card {
  border: 1px solid var(--card-border);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.96));
  box-shadow: 0 18px 40px rgba(15, 23, 42, 0.05);
}

.hero-card {
  background:
    radial-gradient(1200px 420px at 0% -10%, rgba(10, 132, 255, 0.16), transparent 62%),
    radial-gradient(900px 320px at 100% 0%, rgba(16, 185, 129, 0.14), transparent 60%),
    linear-gradient(135deg, rgba(17, 24, 39, 0.02), rgba(255, 255, 255, 0.95));
}

.hero-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.5fr) minmax(280px, 0.8fr);
  gap: 24px;
  align-items: start;
}

.hero-kicker {
  color: rgb(8, 94, 172);
  font-size: 0.76rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.hero-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.hero-chip {
  background: rgba(255, 255, 255, 0.82);
}

.hero-side {
  display: grid;
  gap: 12px;
}

.hero-side-card,
.info-item {
  padding: 16px 18px;
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.84);
  border: 1px solid rgba(255, 255, 255, 0.7);
  display: grid;
  gap: 4px;
}

.hero-side-card span,
.info-item span,
.content-label,
.finding-clause {
  color: rgba(15, 23, 42, 0.65);
}

.hero-side-card strong,
.info-item strong,
.content-value {
  color: rgb(15, 23, 42);
  font-weight: 800;
}

.info-banner {
  border-radius: 16px;
  padding: 14px 18px;
  border: 1px solid transparent;
}

.info-banner.success {
  background: rgba(16, 185, 129, 0.1);
  border-color: rgba(16, 185, 129, 0.2);
  color: rgb(6, 95, 70);
}

.section-head {
  padding: 20px 24px 0;
  font-weight: 800;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.info-grid.single {
  grid-template-columns: 1fr;
}

.content-block {
  display: grid;
  gap: 6px;
}

.content-block--tinted {
  border: 1px solid rgba(10, 132, 255, 0.12);
  background: linear-gradient(180deg, rgba(239, 246, 255, 0.9), rgba(248, 250, 252, 0.96));
  border-radius: 18px;
  padding: 16px 18px;
}

.inline-meta {
  color: rgba(15, 23, 42, 0.6);
  font-size: 0.92rem;
  font-weight: 600;
}

.content-stack {
  display: grid;
  gap: 16px;
}

.finding-list {
  display: grid;
  gap: 14px;
}

.finding-card {
  border: 1px solid rgba(15, 23, 42, 0.08);
  border-radius: 18px;
  padding: 16px 18px;
  background: #fff;
}

.finding-top {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: center;
  margin-bottom: 10px;
  flex-wrap: wrap;
}

.finding-badges {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.finding-title {
  font-weight: 800;
  color: rgb(15, 23, 42);
  margin-bottom: 8px;
}

.finding-description {
  color: rgba(15, 23, 42, 0.78);
  line-height: 1.55;
}

.status-grid {
  display: grid;
  gap: 10px;
}

.status-step {
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: #fff;
  border-radius: 14px;
  padding: 12px 14px;
  display: flex;
  align-items: center;
  gap: 10px;
  color: rgb(71, 85, 105);
  transition: all 0.2s ease;
}

.status-step--active {
  border-color: rgba(10, 132, 255, 0.3);
  background: rgba(10, 132, 255, 0.08);
  color: rgb(8, 94, 172);
  font-weight: 700;
}

.upload-panel {
  display: grid;
  gap: 12px;
}

.report-preview {
  display: grid;
  gap: 14px;
}

.report-preview-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.report-preview-subtitle {
  color: rgb(100, 116, 139);
  font-size: 0.9rem;
  margin-top: 4px;
}

.preview-frame-shell {
  border: 1px solid rgba(15, 23, 42, 0.08);
  border-radius: 18px;
  overflow: hidden;
  background: rgba(255, 255, 255, 0.85);
}

.preview-frame {
  width: 100%;
  min-height: 420px;
  border: 0;
  background: white;
}

.preview-empty {
  border: 1px dashed rgba(15, 23, 42, 0.14);
  border-radius: 16px;
  padding: 18px;
  color: rgb(100, 116, 139);
  background: rgba(248, 250, 252, 0.8);
}

.team-list {
  display: grid;
  gap: 10px;
}

.team-member {
  display: flex;
  align-items: center;
  gap: 10px;
  color: rgb(15, 23, 42);
}

.table-scroll-shell {
  overflow-x: auto;
}

.linked-table :deep(th),
.linked-table :deep(td) {
  white-space: nowrap;
}

.loading-state,
.empty-findings {
  display: grid;
  place-items: center;
  gap: 12px;
  padding: 32px 12px;
  color: rgb(100, 116, 139);
}

.modal-title {
  padding: 20px 24px;
}

@media (max-width: 960px) {
  .hero-grid,
  .info-grid {
    grid-template-columns: 1fr;
  }

  .report-preview-head {
    flex-direction: column;
    align-items: stretch;
  }
}
</style>
