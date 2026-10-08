import api from '@/api/client'

/**
 * Interface pour un log d'import de fiches de poste
 */
export interface JobDescriptionImportLog {
  id: number
  file_name: string
  file_size: number
  total_rows: number
  processed_rows: number
  successful_rows: number
  failed_rows: number
  status: 'pending' | 'validating' | 'processing' | 'completed' | 'failed'
  started_at?: string
  completed_at?: string
  duration_seconds?: number
  success_rate: number
  has_errors: boolean
  error_report_available: boolean
  user?: {
    id: number
    name: string
    email: string
  }
  created_at: string
}

/**
 * Interface pour la réponse d'upload
 */
export interface ImportUploadResponse {
  message: string
  data: {
    import_id: number
    file_name: string
    total_rows: number
    import_mode: 'strict' | 'flexible'
    status: 'pending'
    warning?: string
  }
}

/**
 * Interface pour la réponse de preview
 */
export interface ImportPreviewResponse {
  data: {
    import_id: number
    file_name: string
    file_size: number
    total_rows: number
    processed_rows: number
    successful_rows: number
    failed_rows: number
    status: string
    started_at?: string
    completed_at?: string
    duration_seconds?: number
    success_rate: number
    has_errors: boolean
    error_report_available: boolean
  }
}

/**
 * Interface pour la réponse de confirmation
 */
export interface ImportConfirmResponse {
  message: string
  data: {
    import_id: number
    status: 'validating'
    message: string
  }
}

/**
 * Interface pour la liste des logs (paginée)
 */
export interface ImportLogsResponse {
  data: JobDescriptionImportLog[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

/**
 * Paramètres de filtre pour la liste des logs
 */
export interface ImportLogsFilters {
  status?: 'pending' | 'validating' | 'processing' | 'completed' | 'failed'
  from_date?: string
  to_date?: string
  per_page?: number
  page?: number
}

/**
 * Service de gestion des imports de fiches de poste
 */
export const jobDescriptionImportService = {
  /**
   * Étape 1 : Upload un fichier d'import
   *
   * @param file - Fichier Excel ou CSV
   * @param importMode - Mode d'import ('strict' ou 'flexible')
   * @param onUploadProgress - Callback pour la progression de l'upload
   * @returns Informations sur l'import créé
   */
  async upload (
    file: File,
    importMode: 'strict' | 'flexible' = 'flexible',
    onUploadProgress?: (progressEvent: any) => void,
  ): Promise<ImportUploadResponse> {
    const formData = new FormData()
    formData.append('file', file)
    formData.append('import_mode', importMode)

    const response = await api.post<ImportUploadResponse>(
      '/job-descriptions/import/upload',
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
        onUploadProgress,
      },
    )

    return response.data
  },

  /**
   * Étape 2 : Récupérer le preview d'un import
   *
   * @param importId - ID de l'import
   * @returns Métadonnées de l'import
   */
  async preview (importId: number): Promise<ImportPreviewResponse> {
    const response = await api.get<ImportPreviewResponse>(
      `/job-descriptions/import/preview/${importId}`,
    )
    return response.data
  },

  /**
   * Étape 3 : Confirmer et lancer l'import
   *
   * @param importId - ID de l'import
   * @param importMode - Mode d'import ('strict' ou 'flexible')
   * @returns Confirmation du lancement
   */
  async confirm (
    importId: number,
    importMode: 'strict' | 'flexible' = 'flexible',
  ): Promise<ImportConfirmResponse> {
    const response = await api.post<ImportConfirmResponse>(
      `/job-descriptions/import/confirm/${importId}`,
      { import_mode: importMode },
    )
    return response.data
  },

  /**
   * Télécharger le template Excel pré-rempli
   *
   * @returns Blob du fichier Excel
   */
  async downloadTemplate (): Promise<Blob> {
    const response = await api.get('/job-descriptions/import/template', {
      responseType: 'blob',
    })
    return response.data
  },

  /**
   * Récupérer la liste des imports (historique)
   *
   * @param filters - Filtres optionnels
   * @returns Liste paginée des imports
   */
  async getLogs (filters?: ImportLogsFilters): Promise<ImportLogsResponse> {
    const response = await api.get<ImportLogsResponse>(
      '/job-descriptions/import/logs',
      { params: filters },
    )
    return response.data
  },

  /**
   * Télécharger le rapport d'erreurs d'un import
   *
   * @param logId - ID du log d'import
   * @returns Blob du fichier Excel d'erreurs
   */
  async downloadErrorReport (logId: number): Promise<Blob> {
    const response = await api.get(
      `/job-descriptions/import/logs/${logId}/errors`,
      { responseType: 'blob' },
    )
    return response.data
  },

  /**
   * Helper : Télécharger un fichier Blob avec un nom
   *
   * @param blob - Blob du fichier
   * @param fileName - Nom du fichier
   */
  downloadFile (blob: Blob, fileName: string): void {
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = fileName
    document.body.append(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  },

  /**
   * Helper : Formater la taille d'un fichier en lecture humaine
   *
   * @param bytes - Taille en octets
   * @returns Taille formatée (ex: "2.5 MB")
   */
  formatFileSize (bytes: number): string {
    if (bytes === 0) {
      return '0 Bytes'
    }

    const k = 1024
    const sizes = ['Bytes', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))

    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
  },

  /**
   * Helper : Formater une durée en secondes
   *
   * @param seconds - Durée en secondes
   * @returns Durée formatée (ex: "2 min 30 sec")
   */
  formatDuration (seconds?: number): string {
    if (!seconds) {
      return 'N/A'
    }

    if (seconds < 60) {
      return `${seconds} sec`
    }

    const minutes = Math.floor(seconds / 60)
    const remainingSeconds = seconds % 60

    if (remainingSeconds === 0) {
      return `${minutes} min`
    }

    return `${minutes} min ${remainingSeconds} sec`
  },

  /**
   * Helper : Obtenir la couleur du badge selon le status
   *
   * @param status - Status de l'import
   * @returns Classe de couleur Tailwind
   */
  getStatusColor (status: JobDescriptionImportLog['status']): string {
    const colors = {
      pending: 'gray',
      validating: 'blue',
      processing: 'blue',
      completed: 'green',
      failed: 'red',
    }
    return colors[status] || 'gray'
  },

  /**
   * Helper : Obtenir le label du status en français
   *
   * @param status - Status de l'import
   * @returns Label en français
   */
  getStatusLabel (status: JobDescriptionImportLog['status']): string {
    const labels = {
      pending: 'En attente',
      validating: 'Validation',
      processing: 'En cours',
      completed: 'Terminé',
      failed: 'Échec',
    }
    return labels[status] || status
  },
}
