export type Person = {
  id: number
  nom: string
  prenoms: string
  fullName: string
  telephone: string
  email: string
  poste: string
  adresse: string
  datePriseService: string
  role?: string
  siteId: number | null
  siteName?: string
  permissions: string[]
  directPermissionsCount: number
  rolePermissionsCount: number
  activeScopedPermissionsCount: number
  permissionsCount: number
  approvalStatus?: string
  approvalRequestedAt?: string
  approvalRequestedByName?: string
  approvalRejectedAt?: string
  approvalRejectedByName?: string
  approvalRejectionReason?: string
}
