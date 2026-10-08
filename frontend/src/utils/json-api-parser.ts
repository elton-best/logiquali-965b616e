/**
 * JSON:API Parser Utility
 *
 * Ce projet utilise le format JSON:API pour les réponses API.
 * Ce fichier fournit des utilitaires pour parser les données.
 */

export interface JsonApiResource {
  id: number | string
  type: string
  attributes: Record<string, any>
  relationships?: Record<string, any>
}

export interface JsonApiResponse {
  data: JsonApiResource | JsonApiResource[]
  meta?: Record<string, any>
  links?: Record<string, any>
}

/**
 * Parse un objet JSON:API en objet simple
 * @param data - Donnée JSON:API ou objet simple
 * @returns Objet avec id + attributes aplatis
 */
export function parseJsonApiResource (data: any): Record<string, any> {
  if (!data) {
    return {}
  }

  // Si c'est déjà un objet simple (pas JSON:API), le retourner
  if (!data.attributes && !data.type) {
    return data
  }

  // Parser la structure JSON:API
  const attrs = data.attributes || {}
  const rels = data.relationships || {}

  // Construire l'objet de retour
  const result: Record<string, any> = {
    id: data.id,
    ...attrs,
  }

  // Parser les relations
  for (const key of Object.keys(rels)) {
    const rel = rels[key]

    // Relation simple
    if (rel && rel.attributes) {
      result[key] = {
        id: rel.id,
        ...rel.attributes,
      }
    } else if (Array.isArray(rel)) {
      // Collection
      result[key] = rel.map(item => parseJsonApiResource(item))
    } else {
      // Null/undefined
      result[key] = rel
    }
  }

  return result
}

/**
 * Parse une collection JSON:API
 * @param response - Réponse API (peut être JSON:API ou tableau simple)
 * @returns Tableau d'objets parsés
 */
export function parseJsonApiCollection (response: any): any[] {
  // Si c'est un tableau direct
  if (Array.isArray(response)) {
    return response.map(item => parseJsonApiResource(item))
  }

  // Si c'est un objet avec data
  if (response.data) {
    if (Array.isArray(response.data)) {
      return response.data.map((item: any) => parseJsonApiResource(item))
    }
    return [parseJsonApiResource(response.data)]
  }

  // Fallback: tableau vide
  return []
}

/**
 * Exemple d'utilisation:
 *
 * // Dans votre composant Vue
 * async function loadUsers() {
 *   const response = await apiClient.get('/users')
 *   users.value = parseJsonApiCollection(response.data)
 * }
 *
 * // Résultat: tableau d'objets simples avec id + attributes + relations parsées
 * // [
 * //   {
 * //     id: 1,
 * //     name: "John Doe",
 * //     email: "john@example.com",
 * //     site: { id: 1, name: "Siège" }
 * //   }
 * // ]
 */
