export interface CompanyInfo {
  rccm: string
  ifu: string
  companyName: string
  legalForm: string
  rccmDocument?: File
  ifuDocument?: File
}

export interface ManagerIdentity {
  idType: 'CIP' | 'CNI' | 'Passeport' | ''
  idNumber: string
  firstName: string
  lastName: string
  documents: File[]
}

export interface MainSite {
  address: string
  city: string
  country: string
  postalCode: string
  gpsCoordinates?: string
}

export interface ContactSecurity {
  email: string
  phone?: string
  password: string
  confirmPassword: string
}

export interface Terms {
  acceptedTerms: boolean
  signature?: string
}

export interface CompanySignupData {
  companyInfo: CompanyInfo
  managerIdentity: ManagerIdentity
  mainSite: MainSite
  contactSecurity: ContactSecurity
  terms: Terms
}

export interface KYCDocument {
  id: string
  name: string
  type: string
  url: string
  uploadedAt: string
  status: 'pending' | 'approved' | 'rejected'
}

export interface KYCSubmission {
  id: string
  companyName: string
  rccm: string
  ifu: string
  managerName: string
  email: string
  status: 'pending' | 'approved' | 'rejected'
  submittedAt: string
  reviewedAt?: string
  reviewedBy?: string
  rejectionReason?: string
  documents: KYCDocument[]
}
