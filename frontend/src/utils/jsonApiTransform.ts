/**
 * Utility functions to transform JSON:API format to simple objects
 */

/**
 * Transform a single JSON:API item {id, type, attributes} to a simple object {id, ...attributes}
 */
export function transformJsonApiItem<T = any> (item: any): T {
  if (!item) {
    return item
  }

  // If already in simple format, return as-is
  if (!item.type || !item.attributes) {
    return item
  }

  // Transform JSON:API format
  return {
    id: item.id,
    ...item.attributes,
  } as T
}

/**
 * Transform an array of JSON:API items to simple objects
 */
export function transformJsonApiArray<T = any> (data: any[]): T[] {
  if (!Array.isArray(data)) {
    return []
  }
  return data.map(item => transformJsonApiItem<T>(item))
}

/**
 * Extract and transform data from a JSON:API response
 */
export function extractJsonApiData<T = any> (response: any): T[] {
  const data = response?.data?.data || response?.data || []
  return transformJsonApiArray<T>(data)
}
