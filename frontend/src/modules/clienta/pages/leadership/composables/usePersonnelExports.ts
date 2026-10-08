import type { Ref } from 'vue'
import type { Person } from '@/modules/clienta/pages/leadership/types'

import { ref } from 'vue'
import api from '@/api/client'
import { usersService } from '@/api/services/users.service'

const APP_NAME = String(import.meta.env.VITE_APP_NAME || 'BestQHSE').trim() || 'BestQHSE'

function escapeHtml (value: string): string {
  return String(value || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;')
}

function buildPersonnelRowsHtml (personnel: Person[]): string {
  return personnel
    .map(
      person => `
      <tr>
        <td>${escapeHtml(person.fullName)}</td>
        <td>${escapeHtml(person.email || '—')}</td>
        <td>${escapeHtml(person.poste || '—')}</td>
        <td>${escapeHtml(person.siteName || '—')}</td>
        <td>${escapeHtml(person.telephone || '—')}</td>
        <td>${escapeHtml(person.datePriseService || '—')}</td>
      </tr>`,
    )
    .join('')
}

function buildPersonnelContentHtml (personnel: Person[]): string {
  const rows = buildPersonnelRowsHtml(personnel)

  return `
    <table>
      <thead>
        <tr>
          <th>Nom complet</th>
          <th>Email</th>
          <th>Poste</th>
          <th>Site</th>
          <th>Téléphone</th>
          <th>Date de prise de service</th>
        </tr>
      </thead>
      <tbody>${rows}</tbody>
    </table>
  `
}

function buildPersonnelPreviewHtml (
  personnel: Person[],
  authStore: any,
): string {
  const enterprise = (authStore.user as any)?.enterprise || {}
  const branding = enterprise.branding || {}
  const rawLogo
    = String(branding.logo_url || enterprise.logo_url || '').trim()
      || String(branding.logo_path || enterprise.logo_path || '').trim()
  const logoUrl = /^https?:\/\//i.test(rawLogo)
    ? rawLogo
    : (rawLogo
        ? `${String(
          import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api/v1',
        ).replace(/\/api\/v1\/?$/, '')}/storage/${rawLogo
          .replace(/^\/?storage\//i, '')
          .replace(/^\/+/, '')}`
        : '')
  const content = buildPersonnelContentHtml(personnel)

  return `<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Liste du personnel</title>
  <style>
    body { font-family: Arial, sans-serif; color: #1f2937; margin: 24px; }
    .doc-header { border-bottom: 2px solid #111827; padding-bottom: 12px; margin-bottom: 16px; display:flex; align-items:center; justify-content:space-between; gap:16px; }
    .doc-header-left { min-width: 0; }
    .doc-logo { max-height: 52px; max-width: 180px; object-fit: contain; }
    .doc-title { margin: 0; font-size: 22px; font-weight: 700; }
    .doc-subtitle { margin: 6px 0 0; color: #6b7280; font-size: 13px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #d1d5db; padding: 8px; font-size: 12px; text-align: left; }
    th { background: #f3f4f6; }
    .doc-footer { margin-top: 16px; padding-top: 10px; border-top: 1px solid #d1d5db; color: #6b7280; font-size: 12px; }
    @media print {
      .doc-header, .doc-footer { position: fixed; left: 0; right: 0; }
      .doc-header { top: 0; }
      .doc-footer { bottom: 0; }
      table { margin-top: 90px; margin-bottom: 50px; }
    }
  </style>
</head>
<body>
  <header class="doc-header">
    <div class="doc-header-left">
      <h1 class="doc-title">Liste du personnel</h1>
      <p class="doc-subtitle">Généré le ${new Date().toLocaleString()} · Total: ${personnel.length} collaborateurs</p>
    </div>
    ${logoUrl ? `<img class="doc-logo" src="${logoUrl}" alt="Logo entreprise" />` : ''}
  </header>
  ${content}
  <footer class="doc-footer">
    ${APP_NAME} · Document interne RH
  </footer>
</body>
</html>`
}

export function usePersonnelExports (
  personnel: Ref<Person[]>,
  authStore: any,
  pdfExportService: {
    previewDocument: (payload: any) => Promise<string>
    exportDocument: (payload: any) => Promise<Blob>
    exportDocumentWithMeta: (payload: any) => Promise<{ blob: Blob, generatedDocumentId: number | null }>
    downloadPdf: (blob: Blob, filename: string) => void
  },
  exportingPdf: Ref<boolean>,
) {
  const lastExportedDocumentId = ref<number | null>(null)
  const exportedDocumentCode = ref('')
  const submittingForVerification = ref(false)

  async function previewPersonnel () {
    try {
      const html = await pdfExportService.previewDocument({
        document_type: 'personnel_list',
        document_id: 0,
        content: {
          html: buildPersonnelContentHtml(personnel.value),
          title: 'Liste du personnel',
        },
      })

      if (!html || !String(html).trim()) {
        alert(' Aperçu indisponible.')
        return
      }

      const blob = new Blob([html], { type: 'text/html;charset=utf-8' })
      const previewUrl = URL.createObjectURL(blob)
      const popup = window.open(previewUrl, '_blank')
      if (!popup) {
        alert(' Impossible d\'ouvrir l\'aperçu (popup bloquée).')
        URL.revokeObjectURL(previewUrl)
        return
      }

      setTimeout(() => URL.revokeObjectURL(previewUrl), 60_000)
    } catch (error) {
      console.error('Erreur aperçu personnel:', error)
      alert(' Erreur lors de l\'aperçu')
    }
  }

  function exportPersonnelCsv () {
    const header = [
      'Nom complet',
      'Email',
      'Poste',
      'Site',
      'Téléphone',
      'Date de prise de service',
    ]

    const sanitize = (value: string) =>
      `"${String(value || '').replace(/"/g, '""')}"`
    const rows = personnel.value.map(person =>
      [
        person.fullName,
        person.email,
        person.poste,
        person.siteName || '',
        person.telephone || '',
        person.datePriseService || '',
      ]
        .map(value => sanitize(value))
        .join(','),
    )

    const csv = [header.join(','), ...rows].join('\n')
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `personnel_${new Date().toISOString().slice(0, 10)}.csv`
    document.body.append(link)
    link.click()
    link.remove()
    URL.revokeObjectURL(url)
  }

  async function exportPersonnelDocx () {
    try {
      const blob = await usersService.exportUsersDocx()
      const url = URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = `personnel_${new Date().toISOString().slice(0, 10)}.docx`
      document.body.append(link)
      link.click()
      link.remove()
      URL.revokeObjectURL(url)
    } catch (error) {
      console.error('Erreur lors de l\'export DOCX:', error)
      alert('Erreur lors de la génération du fichier Word.')
    }
  }

  async function exportPersonnelPdf (download = true): Promise<boolean> {
    exportingPdf.value = true
    try {
      const { blob, generatedDocumentId } = await pdfExportService.exportDocumentWithMeta({
        document_type: 'personnel_list',
        document_id: 0,
        content: {
          html: buildPersonnelContentHtml(personnel.value),
          title: 'Liste du personnel',
        },
      })
      if (generatedDocumentId) {
        lastExportedDocumentId.value = generatedDocumentId
        const docResponse = await api.get(`/documents/${generatedDocumentId}`)
        exportedDocumentCode.value = String(docResponse.data?.data?.code || docResponse.data?.code || '')
      }
      if (download) {
        pdfExportService.downloadPdf(
          blob,
          `personnel_${new Date().toISOString().slice(0, 10)}.pdf`,
        )
      }
      return Boolean(generatedDocumentId)
    } catch (error) {
      console.error('Erreur export PDF personnel:', error)
      alert(' Erreur lors de l\'export PDF')
      return false
    } finally {
      exportingPdf.value = false
    }
  }

  async function submitPersonnelForVerification () {
    if (!lastExportedDocumentId.value || !exportedDocumentCode.value) {
      const generated = await exportPersonnelPdf(false)
      if (!generated || !lastExportedDocumentId.value || !exportedDocumentCode.value) {
        alert('Impossible de préparer le brouillon documentaire avant soumission.')
        return
      }
    }

    try {
      submittingForVerification.value = true
      await api.post(`/documents/${lastExportedDocumentId.value}/confirm-code`, {
        needs_verification: true,
        confirmed_code: exportedDocumentCode.value,
      })
      alert('Document envoyé pour vérification.')
      lastExportedDocumentId.value = null
      exportedDocumentCode.value = ''
    } catch (error) {
      console.error('Erreur soumission vérification personnel:', error)
      alert('Échec de la soumission pour vérification.')
    } finally {
      submittingForVerification.value = false
    }
  }

  return {
    previewPersonnel,
    exportPersonnelCsv,
    exportPersonnelDocx,
    exportPersonnelPdf,
    submitPersonnelForVerification,
    lastExportedDocumentId,
    submittingForVerification,
  }
}
