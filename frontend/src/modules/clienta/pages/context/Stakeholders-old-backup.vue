<template>
  <ClientALayout current-page="stakeholders">
    <PageHeader
      icon="mdi-account-group"
      subtitle="Registre des parties intéressées et leurs exigences"
      title="Parties intéressées"
    >
      <template #actions>
        <v-btn color="secondary" prepend-icon="mdi-file-excel" variant="tonal" @click="handleExportExcel">
          Exporter
        </v-btn>
        <v-btn color="primary" prepend-icon="mdi-plus" size="large" @click="handleAdd">
          Nouvelle partie
        </v-btn>
      </template>
    </PageHeader>

    <v-row class="mb-6">
      <v-col cols="12" md="6">
        <v-text-field
          v-model="filters.search"
          bg-color="white"
          clearable
          density="comfortable"
          hide-details
          placeholder="Rechercher une partie intéressée..."
          prepend-inner-icon="mdi-magnify"
          rounded="xl"
          variant="solo"
        />
      </v-col>
      <v-col cols="12" md="2">
        <v-select
          v-model="filters.type"
          bg-color="white"
          clearable
          density="comfortable"
          hide-details
          item-title="title"
          item-value="value"
          :items="stakeholderTypes"
          label="Type"
          rounded="xl"
          variant="solo"
        />
      </v-col>
      <v-col cols="12" md="2">
        <v-select
          v-model="filters.influence"
          bg-color="white"
          clearable
          density="comfortable"
          hide-details
          item-title="title"
          item-value="value"
          :items="influenceLevels"
          label="Influence"
          rounded="xl"
          variant="solo"
        />
      </v-col>
      <v-col cols="12" md="2">
        <v-btn-toggle
          v-model="viewMode"
          class="w-100"
          color="primary"
          density="comfortable"
          mandatory
          rounded="xl"
        >
          <v-btn icon="mdi-view-grid" size="large" value="grid" />
          <v-btn icon="mdi-view-list" size="large" value="list" />
        </v-btn-toggle>
      </v-col>
    </v-row>

    <!-- Vue Grid -->
    <v-row v-if="viewMode === 'grid'">
      <v-col
        v-for="stakeholder in allStakeholders"
        :key="stakeholder.id"
        cols="12"
        lg="4"
        md="6"
      >
        <v-card
          class="stakeholder-card"
          elevation="0"
          rounded="xl"
          :style="`border: 2px solid ${getTypeColor(stakeholder.type)}20`"
          @click="handleEdit(stakeholder)"
        >
          <div class="card-header pa-4" :style="`background: linear-gradient(135deg, ${getTypeColor(stakeholder.type)}15 0%, ${getTypeColor(stakeholder.type)}05 100%)`">
            <div class="d-flex align-center justify-space-between">
              <v-avatar class="elevation-4" :color="getTypeColor(stakeholder.type)" size="56">
                <v-icon color="white" size="32">{{ getTypeIcon(stakeholder.type) }}</v-icon>
              </v-avatar>
              <v-chip
                class="font-weight-bold"
                :color="getInfluenceColor(stakeholder.relevance_degree)"
                size="small"
                variant="flat"
              >
                {{ influenceLabel(stakeholder.relevance_degree) }}
              </v-chip>
            </div>
          </div>

          <v-card-text class="pa-4">
            <h3 class="text-h6 font-weight-bold mb-2">{{ stakeholder.name }}</h3>
            <v-chip
              class="mb-3"
              :color="getTypeColor(stakeholder.type)"
              size="small"
              variant="tonal"
            >
              {{ typeLabel(stakeholder.type) }}
            </v-chip>

            <div class="text-body-2 text-medium-emphasis mb-3" style="min-height: 60px;">
              {{ truncate(stakeholder.needs_expectations, 100) }}
            </div>

            <v-divider class="my-3" />

            <div class="d-flex align-center justify-space-between">
              <div v-if="stakeholder.contact_person" class="text-caption d-flex align-center">
                <v-icon class="mr-1" color="primary" size="16">mdi-account</v-icon>
                {{ stakeholder.contact_person }}
              </div>
              <v-btn
                :color="getTypeColor(stakeholder.type)"
                icon="mdi-pencil"
                size="small"
                variant="tonal"
                @click.stop="handleEdit(stakeholder)"
              />
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col v-if="allStakeholders.length === 0" cols="12">
        <v-card class="text-center pa-16" elevation="0" rounded="xl" style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%)">
          <v-avatar class="mb-6 elevation-8" color="primary" size="120">
            <v-icon color="white" size="64">mdi-account-group-outline</v-icon>
          </v-avatar>
          <h3 class="text-h4 mb-3 font-weight-bold">Aucune partie intéressée</h3>
          <p class="text-h6 text-medium-emphasis mb-6">Commencez par identifier vos parties intéressées</p>
          <v-btn color="primary" prepend-icon="mdi-plus" size="x-large" @click="handleAdd">
            Ajouter la première partie
          </v-btn>
        </v-card>
      </v-col>
    </v-row>

    <!-- Vue Liste -->
    <v-card v-if="viewMode === 'list'" elevation="0" rounded="xl">
      <v-table>
        <thead>
          <tr>
            <th class="text-left font-weight-bold">Nom</th>
            <th class="text-left font-weight-bold">Type</th>
            <th class="text-left font-weight-bold">Influence</th>
            <th class="text-left font-weight-bold">Contact</th>
            <th class="text-left font-weight-bold">Besoins</th>
            <th class="text-center font-weight-bold">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="stakeholder in allStakeholders" :key="stakeholder.id" class="list-row" @click="handleEdit(stakeholder)">
            <td>
              <div class="d-flex align-center gap-3">
                <v-avatar :color="getTypeColor(stakeholder.type)" size="40">
                  <v-icon color="white" size="20">{{ getTypeIcon(stakeholder.type) }}</v-icon>
                </v-avatar>
                <span class="font-weight-bold">{{ stakeholder.name }}</span>
              </div>
            </td>
            <td>
              <v-chip :color="getTypeColor(stakeholder.type)" size="small" variant="tonal">
                {{ typeLabel(stakeholder.type) }}
              </v-chip>
            </td>
            <td>
              <v-chip :color="getInfluenceColor(stakeholder.relevance_degree)" size="small" variant="flat">
                {{ influenceLabel(stakeholder.relevance_degree) }}
              </v-chip>
            </td>
            <td>
              <div v-if="stakeholder.contact_person" class="text-caption">
                <div>{{ stakeholder.contact_person }}</div>
                <div class="text-medium-emphasis">{{ stakeholder.contact_email }}</div>
              </div>
              <span v-else class="text-medium-emphasis">-</span>
            </td>
            <td>
              <div class="text-body-2">{{ truncate(stakeholder.needs_expectations, 80) }}</div>
            </td>
            <td class="text-center">
              <v-btn
                :color="getTypeColor(stakeholder.type)"
                icon="mdi-pencil"
                size="small"
                variant="tonal"
                @click.stop="handleEdit(stakeholder)"
              />
            </td>
          </tr>
          <tr v-if="allStakeholders.length === 0">
            <td class="text-center pa-8" colspan="6">
              <div class="text-h6 text-medium-emphasis">Aucune partie intéressée</div>
            </td>
          </tr>
        </tbody>
      </v-table>
    </v-card>

    <v-dialog v-model="showDialog" max-width="1400" persistent scrollable>
      <v-card class="dialog-card" rounded="xl">
        <div class="dialog-header pa-6" style="background: linear-gradient(135deg, #5b8dd9 0%, #4a71b0 100%)">
          <div class="d-flex align-center justify-space-between">
            <div class="d-flex align-center">
              <v-avatar class="mr-4 elevation-4" color="white" size="56">
                <v-icon color="primary" size="32">mdi-account-group</v-icon>
              </v-avatar>
              <div>
                <h2 class="text-h4 text-white font-weight-bold">
                  {{ currentStakeholder?.id ? 'Modifier' : 'Nouvelle' }} partie intéressée
                </h2>
                <p class="text-white text-opacity-90 mb-0 text-body-1">Renseignez les informations de la partie intéressée</p>
              </div>
            </div>
            <v-btn
              color="white"
              icon="mdi-close"
              size="large"
              variant="text"
              @click="showDialog = false"
            />
          </div>
        </div>

        <v-card-text class="pa-8">
          <!-- Informations principales -->
          <div class="mb-8">
            <h3 class="text-h5 font-weight-bold mb-4 d-flex align-center">
              <v-icon class="mr-2" color="primary">mdi-information</v-icon>
              Informations principales
            </h3>
            <v-row>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="stakeholderForm.name"
                  bg-color="grey-lighten-5"
                  density="comfortable"
                  label="Nom de la partie intéressée *"
                  placeholder="Ex: Clients, Fournisseurs..."
                  prepend-inner-icon="mdi-account-group"
                  rounded="lg"
                  variant="solo"
                />
              </v-col>

              <v-col cols="12" md="3">
                <v-select
                  v-model="stakeholderForm.type"
                  bg-color="grey-lighten-5"
                  density="comfortable"
                  item-title="title"
                  item-value="value"
                  :items="stakeholderTypes"
                  label="Type *"
                  prepend-inner-icon="mdi-tag"
                  rounded="lg"
                  variant="solo"
                />
              </v-col>

              <v-col cols="12" md="3">
                <v-select
                  v-model="stakeholderForm.influence"
                  bg-color="grey-lighten-5"
                  density="comfortable"
                  item-title="title"
                  item-value="value"
                  :items="influenceLevels"
                  label="Niveau d'influence *"
                  prepend-inner-icon="mdi-chart-line"
                  rounded="lg"
                  variant="solo"
                />
              </v-col>
            </v-row>
          </div>

          <!-- Contact -->
          <div class="mb-8">
            <h3 class="text-h5 font-weight-bold mb-4 d-flex align-center">
              <v-icon class="mr-2" color="success">mdi-card-account-details</v-icon>
              Contact
            </h3>
            <v-row>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="stakeholderForm.contactPerson"
                  bg-color="grey-lighten-5"
                  density="comfortable"
                  label="Personne de contact"
                  placeholder="Nom du contact"
                  prepend-inner-icon="mdi-account"
                  rounded="lg"
                  variant="solo"
                />
              </v-col>

              <v-col cols="12" md="6">
                <v-text-field
                  v-model="stakeholderForm.email"
                  bg-color="grey-lighten-5"
                  density="comfortable"
                  label="Email"
                  placeholder="email@exemple.com"
                  prepend-inner-icon="mdi-email"
                  rounded="lg"
                  type="email"
                  variant="solo"
                />
              </v-col>
            </v-row>
          </div>

          <v-divider class="my-8" />

          <!-- Besoins et attentes -->
          <div class="d-flex align-center justify-space-between mb-6">
            <div>
              <h3 class="text-h5 font-weight-bold d-flex align-center">
                <v-icon class="mr-2" color="warning">mdi-clipboard-list</v-icon>
                Besoins et attentes
              </h3>
              <p class="text-body-2 text-medium-emphasis mb-0 mt-1">Ajoutez les besoins spécifiques de cette partie intéressée</p>
            </div>
            <v-btn color="warning" prepend-icon="mdi-plus" size="large" @click="addNeed">
              Ajouter un besoin
            </v-btn>
          </div>

          <v-row>
            <v-col
              v-for="(need, index) in stakeholderForm.needs"
              :key="index"
              cols="12"
            >
              <v-card
                class="need-card"
                rounded="xl"
                style="border: 2px solid #e2e8f0;"
                variant="outlined"
              >
                <v-card-text class="pa-6">
                  <div class="d-flex align-center justify-space-between mb-4">
                    <v-chip class="font-weight-bold" color="warning" size="large">
                      <v-icon start>mdi-numeric-{{ index + 1 }}-circle</v-icon>
                      Besoin {{ index + 1 }}
                    </v-chip>
                    <v-btn
                      color="error"
                      icon="mdi-delete"
                      size="small"
                      variant="tonal"
                      @click="removeNeed(index)"
                    />
                  </div>

                  <v-row>
                    <v-col cols="12">
                      <v-textarea
                        v-model="need.description"
                        bg-color="white"
                        density="comfortable"
                        label="Description du besoin *"
                        placeholder="Décrivez le besoin ou l'attente..."
                        rounded="lg"
                        rows="3"
                        variant="solo"
                      />
                    </v-col>

                    <v-col cols="12" md="3">
                      <v-switch
                        v-model="need.isRequirement"
                        class="mt-2"
                        color="success"
                        hide-details
                        inset
                        label="Est une exigence"
                      />
                    </v-col>

                    <v-col v-if="need.isRequirement" cols="12" md="9">
                      <v-text-field
                        v-model="need.norm"
                        bg-color="white"
                        density="comfortable"
                        label="Norme applicable"
                        placeholder="Ex: ISO 9001:2015 §5.2"
                        prepend-inner-icon="mdi-file-document"
                        rounded="lg"
                        variant="solo"
                      />
                    </v-col>

                    <v-col v-if="need.isRequirement" cols="12">
                      <v-textarea
                        v-model="need.actions"
                        bg-color="white"
                        density="comfortable"
                        label="Actions associées"
                        placeholder="Actions à mettre en place pour cette exigence..."
                        prepend-inner-icon="mdi-check-circle"
                        rounded="lg"
                        rows="2"
                        variant="solo"
                      />
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>

          <v-alert
            v-if="stakeholderForm.needs.length === 0"
            class="mt-4"
            rounded="xl"
            type="info"
            variant="tonal"
          >
            <v-icon start>mdi-information</v-icon>
            Aucun besoin ajouté. Cliquez sur "Ajouter un besoin" pour commencer.
          </v-alert>
        </v-card-text>

        <v-divider />

        <v-card-actions class="pa-6 bg-grey-lighten-5">
          <v-spacer />
          <v-btn size="x-large" variant="outlined" @click="showDialog = false">
            Annuler
          </v-btn>
          <v-btn color="primary" prepend-icon="mdi-content-save" size="x-large" @click="handleSave">
            Enregistrer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import { useToast } from '@/modules/shared/composables/useToast'
  import api from '@/services/api'
  import { useAuthStore } from '@/stores/auth'

  const toast = useToast()
  const authStore = useAuthStore()
  const showDialog = ref(false)
  const currentStakeholder = ref<any>(null)
  const viewMode = ref('grid')

  const filters = ref({
    search: '',
    type: null,
    influence: null,
  })

  // Données statiques d'exemple
  const staticStakeholders = [
    {
      id: 'static-1',
      name: 'Clients',
      type: 'client',
      relevance_degree: 'high',
      contact_person: 'Service Commercial',
      contact_email: 'commercial@entreprise.com',
      needs_expectations: 'Produits de qualité, livraison rapide, service après-vente réactif',
      needs: [
        { description: 'Produits conformes aux spécifications', isRequirement: true, norm: 'ISO 9001:2015 §8.2', actions: 'Contrôle qualité systématique' },
      ],
    },
    {
      id: 'static-2',
      name: 'Fournisseurs',
      type: 'supplier',
      relevance_degree: 'high',
      contact_person: 'Service Achats',
      contact_email: 'achats@entreprise.com',
      needs_expectations: 'Commandes régulières, paiements dans les délais, partenariat long terme',
      needs: [
        { description: 'Cahier des charges clair', isRequirement: false, norm: '', actions: '' },
      ],
    },
    {
      id: 'static-3',
      name: 'Employés',
      type: 'employee',
      relevance_degree: 'high',
      contact_person: 'Direction RH',
      contact_email: 'rh@entreprise.com',
      needs_expectations: 'Conditions de travail sécurisées, formation continue, reconnaissance',
      needs: [
        { description: 'Formation aux procédures qualité', isRequirement: true, norm: 'ISO 9001:2015 §7.2', actions: 'Plan de formation annuel' },
      ],
    },
  ]

  const stakeholderForm = ref({
    name: '',
    type: 'client',
    influence: 'medium',
    contactPerson: '',
    email: '',
    needs: [] as Array<{
      description: string
      isRequirement: boolean
      norm: string
      actions: string
    }>,
  })

  const stakeholders = ref<any[]>([])

  const allStakeholders = computed(() => {
    let result = [...staticStakeholders, ...stakeholders.value]
    if (filters.value.search) {
      const search = filters.value.search.toLowerCase()
      result = result.filter(s =>
        s.name.toLowerCase().includes(search)
        || (s.needs_expectations || '').toLowerCase().includes(search),
      )
    }
    if (filters.value.type) result = result.filter(s => s.type === filters.value.type)
    if (filters.value.influence) result = result.filter(s => s.relevance_degree === filters.value.influence)
    return result
  })

  const stakeholderTypes = [
    { title: 'Client', value: 'client' },
    { title: 'Fournisseur', value: 'supplier' },
    { title: 'Partenaire', value: 'partner' },
    { title: 'Régulateur', value: 'regulator' },
    { title: 'Employé', value: 'employee' },
    { title: 'Actionnaire', value: 'shareholder' },
    { title: 'Autre', value: 'other' },
  ]

  const influenceLevels = [
    { title: 'Faible', value: 'low' },
    { title: 'Moyenne', value: 'medium' },
    { title: 'Élevée', value: 'high' },
  ]

  function getTypeColor (type: string) {
    const colors: Record<string, string> = {
      client: '#5b8dd9', supplier: '#22c55e', partner: '#3b82f6',
      regulator: '#f59e0b', employee: '#a855f7', shareholder: '#f59e0b', other: '#64748b',
    }
    return colors[type] || '#64748b'
  }

  function getTypeIcon (type: string) {
    const icons: Record<string, string> = {
      client: 'mdi-account-star', supplier: 'mdi-truck', partner: 'mdi-handshake',
      regulator: 'mdi-gavel', employee: 'mdi-account-tie', shareholder: 'mdi-chart-line', other: 'mdi-dots-horizontal',
    }
    return icons[type] || 'mdi-account'
  }

  function getInfluenceColor (influence: string) {
    return { low: 'success', medium: 'warning', high: 'error' }[influence] || 'grey'
  }

  function truncate (text: string, length: number) {
    if (!text) return ''
    return text.length > length ? text.slice(0, Math.max(0, length)) + '...' : text
  }

  function addNeed () {
    stakeholderForm.value.needs.push({
      description: '',
      isRequirement: false,
      norm: '',
      actions: '',
    })
  }

  function removeNeed (index: number) {
    stakeholderForm.value.needs.splice(index, 1)
  }

  function handleAdd () {
    currentStakeholder.value = null
    stakeholderForm.value = {
      name: '', type: 'client', influence: 'medium',
      contactPerson: '', email: '', needs: [],
    }
    showDialog.value = true
  }

  function handleEdit (item: any) {
    currentStakeholder.value = item
    stakeholderForm.value = {
      name: item.name || '',
      type: item.type || 'client',
      influence: item.relevance_degree || 'medium',
      contactPerson: item.contact_person || '',
      email: item.contact_email || '',
      needs: item.needs || [],
    }
    showDialog.value = true
  }

  async function handleSave () {
    if (!authStore.currentSiteId) {
      toast.error('Veuillez sélectionner un site.')
      return
    }
    try {
      const payload = {
        site_id: authStore.currentSiteId,
        name: stakeholderForm.value.name,
        type: stakeholderForm.value.type,
        relevance_degree: stakeholderForm.value.influence,
        contact_person: stakeholderForm.value.contactPerson,
        contact_email: stakeholderForm.value.email,
        needs_expectations: stakeholderForm.value.needs.map(n => n.description).join('; '),
        requirements: stakeholderForm.value.needs.filter(n => n.isRequirement).map(n => n.description).join('; '),
        actions: stakeholderForm.value.needs.filter(n => n.actions).map(n => n.actions).join('; '),
      }

      if (currentStakeholder.value?.id && !currentStakeholder.value.id.toString().startsWith('static')) {
        await api.put(`/stakeholders/${currentStakeholder.value.id}`, payload)
        toast.success('Partie intéressée modifiée.')
      } else {
        const { data } = await api.post('/stakeholders', payload)
        stakeholders.value.push(data.data)
        toast.success('Partie intéressée créée.')
      }
      showDialog.value = false
      await loadStakeholders()
    } catch (error) {
      console.error('Erreur sauvegarde:', error)
      toast.error('Erreur lors de l\'enregistrement.')
    }
  }

  async function handleExportExcel () {
    try {
      const response = await api.get('/stakeholders/export-docx', {
        params: { enterprise_id: authStore.currentEnterpriseId },
        responseType: 'blob',
      })
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', 'Registre_Parties_Interessees.docx')
      document.body.append(link)
      link.click()
      link.remove()
      toast.success('Export réussi')
    } catch (error) {
      console.error('Erreur export:', error)
      toast.error('Erreur lors de l\'export')
    }
  }

  async function loadStakeholders () {
    if (!authStore.currentSiteId) return
    try {
      const { data } = await api.get('/stakeholders', {
        params: { site_id: authStore.currentSiteId },
      })
      stakeholders.value = (data?.data || []).map((item: any) => ({
        id: item.id,
        name: item.attributes?.name || item.name,
        type: item.attributes?.type || item.type,
        relevance_degree: item.attributes?.relevance_degree || item.relevance_degree,
        contact_person: item.attributes?.contact_person || item.contact_person,
        contact_email: item.attributes?.contact_email || item.contact_email,
        needs_expectations: item.attributes?.needs_expectations || item.needs_expectations,
        needs: [],
      }))
    } catch (error) {
      console.error('Erreur chargement:', error)
    }
  }

  onMounted(async () => {
    await loadStakeholders()
  })

  function typeLabel (type: string) {
    return stakeholderTypes.find(t => t.value === type)?.title || type
  }

  function influenceLabel (level: string) {
    return influenceLevels.find(l => l.value === level)?.title || level
  }
</script>

<style scoped>
.stakeholder-card {
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
}

.stakeholder-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15);
}

.card-header {
  transition: all 0.3s ease;
}

.stakeholder-card:hover .card-header {
  transform: scale(1.02);
}

.dialog-card {
  overflow: hidden;
}

.dialog-header {
  position: relative;
}

.dialog-header::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #22c55e 0%, #3b82f6 50%, #a855f7 100%);
}

.list-row {
  cursor: pointer;
  transition: all 0.2s ease;
}

.list-row:hover {
  background: rgba(91, 141, 217, 0.05);
}

.need-card {
  transition: all 0.2s ease;
  background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
}

.need-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
}
</style>
