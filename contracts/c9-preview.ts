/**
 * CONTRAT C9 — EXPORT & PRÉVISUALISATION
 * Propriétaire : Dev A (composant UI et logique de prévisualisation)
 * Consommateur : Dev B (tous les exports de Dev B doivent passer par ce contrat/composant)
 *
 * Règle RT-01 : Tout export (PDF, Excel, Word, fiches, rapports) DOIT être
 * prévisualisé avant son téléchargement effectif.
 */

export type ExportFileType = 'pdf' | 'docx' | 'xlsx' | 'csv'

export interface PreviewExportPayload {
  title: string
  fileType: ExportFileType
  filename: string
  // Soit une URL vers le blob/fichier, soit un Blob directement
  blob?: Blob | null
  blobUrl?: string | null
  // Métadonnées additionnelles optionnelles pour l'affichage sommaire
  metadata?: {
    processCode?: string
    author?: string
    version?: string
    date?: string
    summaryItems?: Array<{ label: string, value: string | number }>
  }
}

export interface PreviewExportEvents {
  confirmDownload: () => Promise<void> | void
  close: () => void
}
