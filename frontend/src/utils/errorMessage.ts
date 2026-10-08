export function getErrorMessage (error: any, fallback = 'Une erreur est survenue.'): string {
  const apiMessage = error?.response?.data?.error?.message
    || error?.response?.data?.message
    || (Array.isArray(error?.response?.data?.errors)
      ? error.response.data.errors.join(', ')
      : null)

  return apiMessage || error?.message || fallback
}
