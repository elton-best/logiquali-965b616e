/**
 * Composable unifié pour les templates de nomenclature.
 * Remplace useNomenclatures en s'appuyant sur le nouveau système
 * nomenclature_templates + document_type_configurations.
 */
import { ref } from 'vue'
import api from '@/api/client'

export interface NomenclaturePart {
  id?: string
  order: number
  type: 'document_type' | 'process_code' | 'year' | 'month' | 'day' | 'sequential_number' | 'custom' | 'separator' | 'site_code' | 'free_text'
  token?: string
  label: string
  length: number
  value: string | null
  editable: boolean
  auto: boolean
  separator_after?: string | null
  /** Pour les parties de type liste fixe : les options disponibles */
  options?: string[]
  /** Scope de la séquence (uniquement pour sequential_number) */
  sequence_scope?: 'global' | 'by_type' | 'by_type_process' | 'by_type_year' | 'by_type_process_year' | 'by_type_process_year_month'
}

export interface NomenclatureTemplate {
  id: number
  enterprise_id: number
  site_id: number
  document_type_catalog_id: number | null
  process_catalog_id: number | null
  name: string
  version: number
  status: 'draft' | 'published' | 'archived'
  format_structure: NomenclaturePart[]
  separator: string
  preview_example: string | null
  builder_config: Record<string, any> | null
  description: string | null
  is_active: boolean
  documents_count?: number
  published_at: string | null
  documentTypeCatalog?: { id: number, name: string, abbreviation: string }
  processCatalog?: { id: number, name: string, code: string }
  created_at: string
  updated_at: string
}

export interface TemplatePreviewResult {
  preview: string
}

export function useNomenclatureTemplates () {
  const templates = ref<NomenclatureTemplate[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchTemplates (params?: { site_id?: number, status?: string, active_only?: boolean }) {
    loading.value = true
    error.value = null
    try {
      const response = await api.get('/nomenclature-templates', { params })
      const payload = response.data as any
      templates.value = Array.isArray(payload?.data) ? payload.data : (Array.isArray(payload) ? payload : [])
    } catch (err: any) {
      error.value = err?.response?.data?.message ?? err.message
    } finally {
      loading.value = false
    }
  }

  async function createTemplate (data: Partial<NomenclatureTemplate> & { site_id: number }) {
    loading.value = true
    error.value = null
    try {
      const response = await api.post('/nomenclature-templates', data)
      const created = (response.data as any)?.data ?? response.data
      templates.value.push(created)
      return created as NomenclatureTemplate
    } catch (err: any) {
      error.value = err?.response?.data?.message ?? err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  // Alias exposé pour les composants qui veulent un état de sauvegarde distinct
  const saving = loading

  async function updateTemplate (id: number, data: Partial<NomenclatureTemplate>) {
    loading.value = true
    error.value = null
    try {
      const response = await api.put(`/nomenclature-templates/${id}`, data)
      const updated = (response.data as any)?.data ?? response.data
      const idx = templates.value.findIndex(t => t.id === id)
      if (idx !== -1) templates.value[idx] = updated
      return updated as NomenclatureTemplate
    } catch (err: any) {
      error.value = err?.response?.data?.message ?? err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  async function deleteTemplate (id: number) {
    loading.value = true
    error.value = null
    try {
      await api.delete(`/nomenclature-templates/${id}`)
      templates.value = templates.value.filter(t => t.id !== id)
    } catch (err: any) {
      error.value = err?.response?.data?.message ?? err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  async function previewCode (formatStructure: NomenclaturePart[], separator: string): Promise<string> {
    try {
      const response = await api.post('/nomenclature-templates/preview-code', {
        format_structure: formatStructure,
        separator,
      })
      return (response.data as any)?.data?.preview ?? ''
    } catch {
      return ''
    }
  }

  async function simulateSamples (
    formatStructure: NomenclaturePart[],
    separator: string,
    context: Record<string, string> = {},
    count = 3,
  ): Promise<Array<{ code: string, index: number }>> {
    try {
      const response = await api.post('/nomenclature-templates/simulate-samples', {
        format_structure: formatStructure,
        separator,
        context,
        sample_count: count,
      })
      return (response.data as any)?.data ?? []
    } catch {
      return []
    }
  }

  /** Résoudre le template actif pour un type + processus donné */
  async function resolveActiveTemplate (params: {
    site_id: number
    type: string
    process_id?: number
    processus?: string
  }): Promise<{ code: string, nomenclature_template_id: number | null, nomenclature_template_version: number | null } | null> {
    try {
      const response = await api.get('/documents/preview-code', { params })
      return (response.data as any)?.data ?? null
    } catch {
      return null
    }
  }

  return {
    templates,
    loading,
    saving,
    error,
    fetchTemplates,
    createTemplate,
    updateTemplate,
    deleteTemplate,
    previewCode,
    simulateSamples,
    resolveActiveTemplate,
  }
}
