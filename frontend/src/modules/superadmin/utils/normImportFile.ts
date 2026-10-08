export type NormImportFileModel = File | File[] | null | undefined

const ALLOWED_EXTENSIONS = new Set(['xlsx', 'xls'])
const MAX_FILE_SIZE_BYTES = 10 * 1024 * 1024

export function resolveNormImportFile (model: NormImportFileModel): File | null {
  if (!model) {
    return null
  }

  if (Array.isArray(model)) {
    return model[0] ?? null
  }

  return model instanceof File ? model : null
}

export function validateNormImportFile (file: File | null): string | null {
  if (!file) {
    return 'Veuillez sélectionner un fichier Excel.'
  }

  const extension = (file.name.split('.').pop() || '').toLowerCase()
  if (!ALLOWED_EXTENSIONS.has(extension)) {
    return 'Format invalide. Utilisez un fichier .xlsx ou .xls.'
  }

  if (file.size > MAX_FILE_SIZE_BYTES) {
    return 'Fichier trop volumineux. Taille maximale: 10 Mo.'
  }

  return null
}
