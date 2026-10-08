import { defineStore } from 'pinia'
import supportService, { type CodificationElement, type Equipement, type EquipementImportResult, type Maintenance, type MaintenanceImportResult } from '@/services/supportService'

export const useSupportStore = defineStore('support', {
  state: () => ({
    codifications: [] as CodificationElement[],
    equipements: [] as Equipement[],
    maintenances: [] as Maintenance[],
    alertes: [] as Maintenance[],
    loading: false,
    error: null as string | null,
  }),

  getters: {
    categories: state => (state.codifications || []).filter(c => c.type === 'categorie' && c.actif),
    localisations: state => (state.codifications || []).filter(c => c.type === 'localisation' && c.actif),

    equipementsActifs: state => (state.equipements || []).filter(e => e.actif),

    maintenancesPlanifiees: state => (state.maintenances || []).filter(m => m.statut === 'planifie'),
    maintenancesEnRetard: state => (state.maintenances || []).filter(m =>
      m.statut === 'planifie' && new Date(m.date_prevue) < new Date(),
    ),
  },

  actions: {
    async fetchCodifications () {
      this.loading = true
      this.error = null
      try {
        this.codifications = await supportService.getCodifications()
      } catch (error: any) {
        this.error = error?.response?.data?.message || error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async createCodification (data: Partial<CodificationElement>) {
      this.loading = true
      try {
        const newCodification = await supportService.createCodification(data)
        this.codifications.push(newCodification)
        return newCodification
      } catch (error: any) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateCodification (id: number, data: Partial<CodificationElement>) {
      this.loading = true
      try {
        const updated = await supportService.updateCodification(id, data)
        const index = this.codifications.findIndex(c => c.id === id)
        if (index !== -1) {
          this.codifications[index] = updated
        }
        return updated
      } catch (error: any) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async deleteCodification (id: number) {
      this.loading = true
      try {
        await supportService.deleteCodification(id)
        this.codifications = this.codifications.filter(c => c.id !== id)
      } catch (error: any) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchEquipements () {
      this.loading = true
      this.error = null
      try {
        this.equipements = await supportService.getEquipements()
      } catch (error: any) {
        this.error = error?.response?.data?.message || error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async createEquipement (data: Partial<Equipement>) {
      this.loading = true
      try {
        const newEquipement = await supportService.createEquipement(data)
        this.equipements.push(newEquipement)
        return newEquipement
      } catch (error: any) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateEquipement (id: number, data: Partial<Equipement>) {
      this.loading = true
      try {
        const updated = await supportService.updateEquipement(id, data)
        const index = this.equipements.findIndex(e => e.id === id)
        if (index !== -1) {
          this.equipements[index] = updated
        }
        return updated
      } catch (error: any) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async deleteEquipement (id: number) {
      this.loading = true
      try {
        await supportService.deleteEquipement(id)
        this.equipements = this.equipements.filter(e => e.id !== id)
      } catch (error: any) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchMaintenances () {
      this.loading = true
      try {
        this.maintenances = await supportService.getMaintenances()
      } catch (error: any) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async createMaintenance (data: Partial<Maintenance>) {
      this.loading = true
      try {
        const newMaintenance = await supportService.createMaintenance(data)
        this.maintenances.push(newMaintenance)
        return newMaintenance
      } catch (error: any) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateMaintenance (id: number, data: Partial<Maintenance>) {
      this.loading = true
      try {
        const updated = await supportService.updateMaintenance(id, data)
        const index = this.maintenances.findIndex(m => m.id === id)
        if (index !== -1) {
          this.maintenances[index] = updated
        }
        return updated
      } catch (error: any) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async deleteMaintenance (id: number) {
      this.loading = true
      try {
        await supportService.deleteMaintenance(id)
        this.maintenances = this.maintenances.filter(m => m.id !== id)
      } catch (error: any) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async suivreMaintenance (id: number, data: FormData) {
      this.loading = true
      try {
        const result = await supportService.suivreMaintenance(id, data)
        const index = this.maintenances.findIndex(m => m.id === id)
        if (index !== -1) {
          this.maintenances[index] = result.maintenance
        }
        await this.fetchAlertes()
        return result
      } catch (error: any) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchAlertes () {
      try {
        this.alertes = await supportService.getAlertesMaintenance()
      } catch (error: any) {
        this.error = error.message
        throw error
      }
    },

    async importMaintenances (file: File): Promise<MaintenanceImportResult> {
      this.loading = true
      try {
        const formData = new FormData()
        formData.append('file', file)
        const result = await supportService.importMaintenances(formData)
        await this.fetchMaintenances()
        return result
      } catch (error: any) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async exportMaintenances () {
      this.loading = true
      try {
        await supportService.exportMaintenances()
      } catch (error: any) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async downloadMaintenanceTemplate () {
      this.loading = true
      try {
        await supportService.downloadMaintenanceTemplate()
      } catch (error: any) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async importEquipements (file: File): Promise<EquipementImportResult> {
      this.loading = true
      this.error = null
      try {
        const formData = new FormData()
        formData.append('file', file)
        const result = await supportService.importEquipements(formData)
        await this.fetchEquipements()
        return result
      } catch (error: any) {
        this.error = error?.response?.data?.message || error.message
        throw error
      } finally {
        this.loading = false
      }
    },
  },
})
