<template>
  <v-container fluid>
    <v-row>
      <v-col cols="12">
        <v-tabs v-model="tab">
          <v-tab value="stocks">Stocks EPI</v-tab>
          <v-tab value="catalogue">Catalogue</v-tab>
          <v-tab value="attributions">Attributions</v-tab>
        </v-tabs>
      </v-col>
    </v-row>

    <v-window v-model="tab">
      <v-window-item value="stocks">
        <v-card class="mt-4">
          <v-card-title class="d-flex align-center">
            <v-icon class="mr-2">mdi-shield-account</v-icon>
            Stocks EPI
            <v-spacer />
            <v-btn color="primary" @click="dialogStock = true">
              <v-icon left>mdi-plus</v-icon>
              Nouveau Stock
            </v-btn>
          </v-card-title>

          <v-card-text>
            <v-data-table
              :headers="headersStock"
              :items="store.stocks"
              :loading="store.loading"
            >
              <template #item.epiCatalogue="{ item }">
                {{ item.epiCatalogue?.designation }}
              </template>

              <template #item.quantite_stock="{ item }">
                <v-chip
                  :color="item.quantite_stock <= item.seuil_alerte ? 'error' : 'success'"
                  size="small"
                >
                  {{ item.quantite_stock }}
                </v-chip>
              </template>

              <template #item.actions="{ item }">
                <v-btn icon size="small" @click="openMouvement(item)">
                  <v-icon>mdi-history</v-icon>
                </v-btn>
                <v-btn icon size="small" @click="openMouvement(item)">
                  <v-icon>mdi-swap-horizontal</v-icon>
                </v-btn>
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-window-item>

      <v-window-item value="catalogue">
        <v-card class="mt-4">
          <v-card-title class="d-flex align-center">
            <v-icon class="mr-2">mdi-book-open-variant</v-icon>
            Catalogue EPI
            <v-spacer />
            <v-btn color="primary" @click="dialogCatalogue = true">
              <v-icon left>mdi-plus</v-icon>
              Ajouter EPI
            </v-btn>
          </v-card-title>

          <v-card-text>
            <v-data-table
              :headers="headersCatalogue"
              :items="store.catalogue"
              :loading="store.loading"
            >
              <template #item.categorie="{ item }">
                <v-chip size="small">{{ item.categorie }}</v-chip>
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-window-item>

      <v-window-item value="attributions">
        <v-card class="mt-4">
          <v-card-title class="d-flex align-center">
            <v-icon class="mr-2">mdi-account-check</v-icon>
            Attributions EPI
            <v-spacer />
            <v-btn color="primary" @click="dialogAttribution = true">
              <v-icon left>mdi-plus</v-icon>
              Attribuer EPI
            </v-btn>
          </v-card-title>

          <v-card-text>
            <v-data-table
              :headers="headersAttribution"
              :items="attributions"
              :loading="store.loading"
            >
              <template #item.user="{ item }">
                {{ item.user?.first_name }} {{ item.user?.last_name }}
              </template>

              <template #item.statut="{ item }">
                <v-chip
                  :color="item.statut === 'en_cours' ? 'success' : 'grey'"
                  size="small"
                >
                  {{ item.statut }}
                </v-chip>
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-window-item>
    </v-window>

    <v-dialog v-model="dialogMouvement" max-width="600">
      <v-card>
        <v-card-title>Mouvement de Stock</v-card-title>
        <v-card-text>
          <v-form>
            <v-select
              v-model="mouvementData.type"
              :items="['entree', 'sortie', 'inventaire', 'rebut']"
              label="Type *"
            />
            <v-text-field
              v-model.number="mouvementData.quantite"
              label="Quantité *"
              type="number"
            />
            <v-text-field
              v-model="mouvementData.motif"
              label="Motif"
            />
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn @click="dialogMouvement = false">Annuler</v-btn>
          <v-btn color="primary" @click="saveMouvement">Enregistrer</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup lang="ts">
  import { onMounted, ref } from 'vue'
  import { useToast } from 'vue-toastification'
  import { useEpiStore } from '@/stores/epiStore'

  const store = useEpiStore()
  const toast = useToast()

  const tab = ref('stocks')
  const dialogStock = ref(false)
  const dialogCatalogue = ref(false)
  const dialogAttribution = ref(false)
  const dialogMouvement = ref(false)
  const attributions = ref([])
  const selectedStock = ref(null)
  const mouvementData = ref({
    type: 'entree',
    quantite: 0,
    motif: '',
  })

  const headersStock = [
    { title: 'EPI', key: 'epiCatalogue' },
    { title: 'Taille', key: 'taille' },
    { title: 'Stock', key: 'quantite_stock' },
    { title: 'Seuil', key: 'seuil_alerte' },
    { title: 'Emplacement', key: 'emplacement_stockage' },
    { title: 'Actions', key: 'actions', sortable: false },
  ]

  const headersCatalogue = [
    { title: 'Catégorie', key: 'categorie' },
    { title: 'Désignation', key: 'designation' },
    { title: 'Référence', key: 'reference_fabricant' },
    { title: 'Norme CE', key: 'norme_ce' },
  ]

  const headersAttribution = [
    { title: 'Personnel', key: 'user' },
    { title: 'EPI', key: 'epiStock.epiCatalogue.designation' },
    { title: 'Quantité', key: 'quantite_attribuee' },
    { title: 'Date', key: 'date_attribution' },
    { title: 'Statut', key: 'statut' },
  ]

  function openMouvement (stock: any) {
    selectedStock.value = stock
    mouvementData.value = { type: 'entree', quantite: 0, motif: '' }
    dialogMouvement.value = true
  }

  async function saveMouvement () {
    try {
      await store.createMouvement({
        epi_stock_id: selectedStock.value.id,
        ...mouvementData.value,
      } as any)
      await store.fetchStocks()
      toast.success('Mouvement enregistré')
      dialogMouvement.value = false
    } catch {
      toast.error('Erreur')
    }
  }

  onMounted(async () => {
    await store.fetchCatalogue()
    await store.fetchStocks()
    attributions.value = await store.fetchAttributions()
  })
</script>
