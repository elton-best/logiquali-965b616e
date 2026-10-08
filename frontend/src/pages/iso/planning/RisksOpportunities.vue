<template>
  <ClientALayout>
    <div class="page-header mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Risques & Opportunités</h1>
          <p class="mt-2 text-gray-600">Identifier et évaluer les risques et opportunités par site.</p>
        </div>
        <div class="flex gap-3">
          <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreate">
            Ajouter
          </v-btn>
        </div>
      </div>
    </div>

    <Card class="mb-6" padding="sm" variant="bordered">
      <div class="flex flex-wrap gap-4 items-center">
        <v-btn
          color="primary"
          :variant="activeTab === 'risk' ? 'flat' : 'outlined'"
          @click="activeTab = 'risk'; fetchItems()"
        >
          Risques
        </v-btn>
        <v-btn
          color="primary"
          :variant="activeTab === 'opportunity' ? 'flat' : 'outlined'"
          @click="activeTab = 'opportunity'; fetchItems()"
        >
          Opportunités
        </v-btn>
      </div>
    </Card>

    <Card padding="none" variant="bordered">
      <v-data-table
        :headers="headers"
        :items="items"
        :items-per-page="10"
        :loading="loading"
      >
        <template #item.type="{ item }">
          <Badge size="sm" :variant="item.type === 'risk' ? 'danger' : 'success'">
            {{ item.type === 'risk' ? 'Risque' : 'Opportunité' }}
          </Badge>
        </template>

        <template #item.criticality_level="{ item }">
          <Badge size="sm" :variant="criticalityVariant(item.criticality_level)">
            {{ item.criticality_level || 'n/a' }}
          </Badge>
        </template>

        <template #item.actions="{ item }">
          <div class="flex gap-1">
            <v-btn icon="mdi-pencil" size="small" variant="text" @click.stop="openEdit(item)" />
            <v-btn
              color="error"
              icon="mdi-delete"
              size="small"
              variant="text"
              @click.stop="handleDelete(item)"
            />
          </div>
        </template>

        <template #no-data>
          <div class="text-center py-12">
            <p class="text-gray-400 mb-4">Aucun élément enregistré</p>
            <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreate">
              Ajouter le premier
            </v-btn>
          </div>
        </template>
      </v-data-table>
    </Card>

    <v-dialog v-model="showDialog" max-width="700">
      <v-card>
        <v-card-title>
          {{ editingId ? 'Modifier' : 'Ajouter' }} {{ activeTab === 'risk' ? 'un risque' : 'une opportunité' }}
        </v-card-title>
        <v-card-text>
          <div class="space-y-4">
            <FormField label="Titre" required>
              <v-text-field v-model="form.title" density="compact" variant="outlined" />
            </FormField>

            <FormField label="Catégorie" required>
              <v-select
                v-model="form.category"
                density="compact"
                item-title="title"
                item-value="value"
                :items="categories"
                variant="outlined"
              />
            </FormField>

            <FormField label="Description" required>
              <v-textarea v-model="form.description" rows="4" variant="outlined" />
            </FormField>

            <div class="grid grid-cols-2 gap-4">
              <FormField label="Probabilité (1-3)">
                <v-text-field v-model.number="form.probability" density="compact" type="number" variant="outlined" />
              </FormField>
              <FormField label="Gravité (1-3)">
                <v-text-field v-model.number="form.gravity" density="compact" type="number" variant="outlined" />
              </FormField>
            </div>

            <FormField label="Responsable">
              <v-select
                v-model="form.responsible_id"
                clearable
                density="compact"
                item-title="name"
                item-value="id"
                :items="responsibleUsers"
                variant="outlined"
              />
            </FormField>
          </div>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn @click="showDialog = false">Annuler</v-btn>
          <v-btn color="primary" @click="handleSave">Enregistrer</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { onMounted, ref } from 'vue'
  import api from '@/api/client'
  import FormField from '@/components/forms/FormField.vue'
  import Badge from '@/components/ui/Badge.vue'
  import Card from '@/components/ui/Card.vue'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import { useToast } from '@/modules/shared/composables/useToast'
  import userService from '@/services/userService'
  import { useAuthStore } from '@/stores/auth'

  const toast = useToast()
  const authStore = useAuthStore()

  const activeTab = ref<'risk' | 'opportunity'>('risk')
  const items = ref<any[]>([])
  const loading = ref(false)
  const showDialog = ref(false)
  const editingId = ref<number | null>(null)
  const responsibleUsers = ref<any[]>([])

  const headers = [
    { title: 'Titre', key: 'title', sortable: true },
    { title: 'Type', key: 'type', sortable: true },
    { title: 'Catégorie', key: 'category', sortable: true },
    { title: 'Probabilité', key: 'probability', sortable: true },
    { title: 'Gravité', key: 'gravity', sortable: true },
    { title: 'Criticité', key: 'criticality_level', sortable: false },
    { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
  ] as const

  const categories = [
    { title: 'Stratégique', value: 'strategic' },
    { title: 'Opérationnel', value: 'operational' },
    { title: 'Financier', value: 'financial' },
    { title: 'Conformité', value: 'compliance' },
    { title: 'Sécurité', value: 'safety' },
    { title: 'Environnement', value: 'environmental' },
    { title: 'Réputation', value: 'reputation' },
    { title: 'IT', value: 'it' },
    { title: 'Légal', value: 'legal' },
    { title: 'Autre', value: 'other' },
  ]

  const form = ref({
    title: '',
    category: 'strategic',
    description: '',
    probability: null as number | null,
    gravity: null as number | null,
    responsible_id: null as number | null,
  })

  onMounted(async () => {
    await fetchItems()
    if (authStore.currentSiteId) {
      responsibleUsers.value = await userService.getBySite(authStore.currentSiteId)
    }
  })

  async function fetchItems () {
    if (!authStore.currentSiteId) return
    loading.value = true
    try {
      const { data } = await api.get('/risks', {
        params: { site_id: authStore.currentSiteId, type: activeTab.value },
      })
      items.value = data || []
    } catch (error) {
      console.error('Erreur chargement risques:', error)
    } finally {
      loading.value = false
    }
  }

  function openCreate () {
    editingId.value = null
    form.value = {
      title: '',
      category: 'strategic',
      description: '',
      probability: null,
      gravity: null,
      responsible_id: null,
    }
    showDialog.value = true
  }

  function openEdit (item: any) {
    editingId.value = item.id
    form.value = {
      title: item.title || '',
      category: item.category || 'strategic',
      description: item.description || '',
      probability: item.probability || null,
      gravity: item.gravity || null,
      responsible_id: item.responsible_id || null,
    }
    showDialog.value = true
  }

  async function handleSave () {
    if (!authStore.currentSiteId) {
      toast.error('Veuillez sélectionner un site.')
      return
    }
    if (!form.value.title || form.value.description.length < 10) {
      toast.error('Veuillez renseigner un titre et une description (min. 10 caractères).')
      return
    }
    try {
      const payload = {
        site_id: authStore.currentSiteId,
        type: activeTab.value,
        title: form.value.title,
        category: form.value.category,
        description: form.value.description,
        probability: form.value.probability,
        gravity: form.value.gravity,
        responsible_id: form.value.responsible_id,
      }

      await (editingId.value ? api.put(`/risks/${editingId.value}`, payload) : api.post('/risks', payload))

      showDialog.value = false
      await fetchItems()
      toast.success('Enregistrement effectué.')
    } catch (error) {
      console.error('Erreur sauvegarde:', error)
      toast.error('Erreur lors de l’enregistrement.')
    }
  }

  async function handleDelete (item: any) {
    if (!confirm('Supprimer cet élément ?')) return
    try {
      await api.delete(`/risks/${item.id}`)
      await fetchItems()
      toast.success('Supprimé.')
    } catch (error) {
      console.error('Erreur suppression:', error)
      toast.error('Erreur lors de la suppression.')
    }
  }

  function criticalityVariant (level: string): 'success' | 'warning' | 'danger' | 'default' {
    const map: Record<string, 'success' | 'warning' | 'danger'> = {
      low: 'success',
      medium: 'warning',
      high: 'danger',
      critical: 'danger',
    }
    return map[level] || 'default'
  }
</script>

<style scoped>
.page-header {
  @apply pb-6 border-b border-gray-200;
}
</style>
