<template>
  <SuperAdminLayout current-page="offers">
    <v-container class="pa-6" fluid>
      <!-- Header -->
      <v-row class="mb-6">
        <v-col cols="12">
          <div class="d-flex align-center justify-space-between flex-wrap ga-4">
            <div class="d-flex align-center flex-grow-1">
              <v-tooltip location="top" text="Retour">
                <template #activator="{ props: tooltipProps }">
                  <v-btn
                    v-bind="tooltipProps"
                    class="mr-4"
                    icon
                    variant="text"
                    @click="$router.back()"
                  >
                    <v-icon>mdi-arrow-left</v-icon>
                  </v-btn>
                </template>
              </v-tooltip>

              <div class="flex-grow-1">
                <h1 class="text-h4 font-weight-bold text-primary mb-1">
                  {{ offer.name }}
                </h1>
                <p class="text-subtitle-1 text-grey-darken-1">
                  {{ offer.description }}
                </p>
              </div>

              <v-chip
                class="mr-4"
                :color="offer.active ? 'success' : 'error'"
                size="x-small"
                variant="tonal"
              >
                <v-icon size="16" start>{{ offer.active ? 'mdi-check-circle' : 'mdi-close-circle' }}</v-icon>
                {{ offer.active ? 'Active' : 'Inactive' }}
              </v-chip>
            </div>

            <div class="d-flex align-center ga-2 flex-wrap">
              <v-btn
                color="primary"
                prepend-icon="mdi-pencil"
                variant="flat"
                @click="editOffer"
              >
                Modifier
              </v-btn>
            </div>
          </div>
        </v-col>
      </v-row>

      <v-row>
        <!-- Offer Details -->
        <v-col cols="12" lg="8">
          <!-- Pricing Card -->
          <v-card class="mb-6" elevation="2">
            <v-card-text class="pa-6">
              <div class="d-flex align-center mb-6">
                <v-icon class="mr-3" color="primary" size="32">mdi-currency-usd</v-icon>
                <h2 class="text-h6 font-weight-bold">Tarification</h2>
              </div>

              <v-row>
                <v-col cols="12" md="4">
                  <v-card class="pa-4 text-center" elevation="2">
                    <p class="text-caption text-medium-emphasis mb-2">Prix mensuel</p>
                    <p class="text-h5 text-primary font-weight-bold">{{ offer.price }}</p>
                    <p class="text-caption text-medium-emphasis">FCFA / mois</p>
                  </v-card>
                </v-col>
                <v-col cols="12" md="4">
                  <v-card class="pa-4 text-center" elevation="2">
                    <p class="text-caption text-medium-emphasis mb-2">Durée minimum</p>
                    <p class="text-h5 font-weight-bold">{{ offer.minDuration }}</p>
                    <p class="text-caption text-medium-emphasis">mois</p>
                  </v-card>
                </v-col>
                <v-col cols="12" md="4">
                  <v-card class="pa-4 text-center" elevation="2">
                    <p class="text-caption text-medium-emphasis mb-2">Abonnés actifs</p>
                    <p class="text-h5 font-weight-bold">{{ offer.subscribersCount }}</p>
                    <p class="text-caption text-medium-emphasis">entreprises</p>
                  </v-card>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <!-- Description & Features -->
          <v-card class="mb-6" elevation="2">
            <v-card-text class="pa-6">
              <div class="d-flex align-center mb-4">
                <v-icon class="mr-3" color="primary" size="32">mdi-text-box-outline</v-icon>
                <h2 class="text-h6 font-weight-bold">Description détaillée</h2>
              </div>
              <p class="text-body-1 mb-6">{{ offer.detailedDescription }}</p>

              <div class="d-flex align-center mb-4">
                <v-icon class="mr-3" color="primary" size="32">mdi-check-decagram</v-icon>
                <h2 class="text-h6 font-weight-bold">Fonctionnalités incluses</h2>
              </div>
              <v-list class="bg-transparent" elevation="0">
                <v-list-item
                  v-for="(feature, index) in offer.features"
                  :key="index"
                  class="px-0"
                >
                  <template #prepend>
                    <v-icon class="mr-3" color="success">mdi-check-circle</v-icon>
                  </template>
                  <v-list-item-title class="text-body-1">{{ feature }}</v-list-item-title>
                </v-list-item>
              </v-list>
            </v-card-text>
          </v-card>

          <!-- Included Norms -->
          <v-card elevation="2">
            <v-card-text class="pa-6">
              <div class="d-flex align-center mb-4">
                <v-icon class="mr-3" color="primary" size="32">mdi-certificate-outline</v-icon>
                <h2 class="text-h6 font-weight-bold">{{ offer.norms && offer.norms.length > 1 ? 'Normes associées' : 'Norme associée' }}</h2>
              </div>
              <v-row v-if="offer.norms && offer.norms.length > 0">
                <v-col v-for="norm in offer.norms" :key="norm.id" cols="12" md="6">
                  <v-card elevation="2">
                    <v-card-text class="pa-4">
                      <div class="d-flex align-center">
                        <v-avatar class="mr-3" color="primary" variant="tonal">
                          <v-icon>mdi-certificate</v-icon>
                        </v-avatar>
                        <div>
                          <p class="font-weight-bold mb-1">{{ norm.name }}</p>
                          <p class="text-caption text-medium-emphasis">{{ norm.category }}</p>
                        </div>
                      </div>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>
        </v-col>

        <!-- Sidebar -->
        <v-col cols="12" lg="4">
          <!-- Statistics -->
          <v-card class="mb-6" elevation="2">
            <v-card-text class="pa-6">
              <div class="d-flex align-center mb-4">
                <v-icon class="mr-3" color="primary" size="32">mdi-chart-line</v-icon>
                <h2 class="text-h6 font-weight-bold">Statistiques</h2>
              </div>

              <div class="mb-4">
                <div class="d-flex justify-space-between align-center mb-2">
                  <span class="text-body-2 text-medium-emphasis">Revenu mensuel</span>
                  <span class="text-h6 font-weight-bold text-success">{{ offer.monthlyRevenue }} FCFA</span>
                </div>
                <v-divider class="my-3" />
                <div class="d-flex justify-space-between align-center mb-2">
                  <span class="text-body-2 text-medium-emphasis">Taux de conversion</span>
                  <span class="text-body-1 font-weight-medium">{{ offer.conversionRate }}%</span>
                </div>
                <v-divider class="my-3" />
                <div class="d-flex justify-space-between align-center">
                  <span class="text-body-2 text-medium-emphasis">Taux de renouvellement</span>
                  <span class="text-body-1 font-weight-medium">{{ offer.renewalRate }}%</span>
                </div>
              </div>
            </v-card-text>
          </v-card>

          <!-- Subscribed Companies -->
          <v-card elevation="2">
            <v-card-text class="pa-6">
              <div class="d-flex align-center justify-space-between mb-4">
                <div class="d-flex align-center">
                  <v-icon class="mr-3" color="primary" size="32">mdi-office-building</v-icon>
                  <h2 class="text-h6 font-weight-bold">Entreprises abonnées</h2>
                </div>
                <v-chip color="primary" size="x-small" variant="tonal">
                  {{ subscribedCompanies.length }}
                </v-chip>
              </div>

              <v-list class="bg-transparent" elevation="0">
                <v-list-item
                  v-for="company in subscribedCompanies"
                  :key="company.id"
                  class="px-0 mb-3"
                  style="cursor: pointer"
                  @click="viewCompany(company.id)"
                >
                  <template #prepend>
                    <v-avatar class="mr-3" color="primary" variant="tonal">
                      <v-icon>mdi-domain</v-icon>
                    </v-avatar>
                  </template>
                  <v-list-item-title class="font-weight-medium">
                    {{ company.name }}
                  </v-list-item-title>
                  <v-list-item-subtitle class="text-caption">
                    Depuis {{ company.subscribedSince }}
                  </v-list-item-subtitle>
                  <template #append>
                    <v-chip
                      :color="company.status === 'active' ? 'success' : 'grey'"
                      size="x-small"
                      variant="tonal"
                    >
                      {{ company.status === 'active' ? 'Actif' : 'Inactif' }}
                    </v-chip>
                  </template>
                </v-list-item>
              </v-list>

              <v-btn
                v-if="subscribedCompanies.length > 5"
                block
                class="mt-2"
                color="primary"
                variant="text"
              >
                Voir tout ({{ subscribedCompanies.length }})
              </v-btn>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-container>

    <!-- Edit Dialog -->
    <v-dialog v-model="editDialog" max-width="800">
      <v-card elevation="0">
        <v-card-title class="text-h6 font-weight-bold pa-6">
          Modifier l'offre
        </v-card-title>
        <v-card-text class="pa-6">
          <v-form>
            <v-text-field
              v-model="offer.name"
              class="mb-4"
              density="comfortable"
              label="Nom de l'offre"
              variant="outlined"
            />
            <v-textarea
              v-model="offer.description"
              class="mb-4"
              density="comfortable"
              label="Description courte"
              rows="2"
              variant="outlined"
            />
            <v-textarea
              v-model="offer.detailedDescription"
              class="mb-4"
              density="comfortable"
              label="Description détaillée"
              rows="4"
              variant="outlined"
            />
            <v-text-field
              v-model="offer.price"
              class="mb-4"
              density="comfortable"
              label="Prix (FCFA)"
              type="number"
              variant="outlined"
            />
            <v-switch
              v-model="offer.active"
              class="mb-4"
              color="primary"
              label="Offre active"
            />
          </v-form>
        </v-card-text>
        <v-card-actions class="pa-6 pt-0">
          <v-spacer />
          <v-btn variant="text" @click="editDialog = false">Annuler</v-btn>
          <v-btn color="primary" variant="flat" @click="saveOffer">Enregistrer</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </SuperAdminLayout>
</template>

<script setup lang="ts">
  import { ref } from 'vue'
  import { useRouter } from 'vue-router'
  import SuperAdminLayout from '../../components/SuperAdminLayout.vue'

  const router = useRouter()
  const editDialog = ref(false)

  const offer = ref({
    id: '1',
    name: 'Pack Premium ISO 9001',
    description: 'Solution complète pour la gestion de la qualité ISO 9001',
    detailedDescription: 'Le Pack Premium ISO 9001 offre une solution complète et personnalisée pour la gestion de votre système qualité. Avec un accompagnement dédié, des outils avancés de suivi et de reporting, et un accès illimité à nos experts, ce pack est idéal pour les entreprises qui souhaitent optimiser leur conformité ISO 9001.',
    price: '450,000',
    minDuration: 12,
    active: true,
    subscribersCount: 12,
    monthlyRevenue: '5,400,000',
    conversionRate: 45,
    renewalRate: 89,
    features: [
      'Tableau de bord avancé en temps réel',
      'Gestion illimitée des documents',
      'Audit interne automatisé',
      'Rapports personnalisés et exports Excel',
      'Support prioritaire 24/7',
      'Formation en ligne incluse',
      'Consultation avec expert certifié (4h/mois)',
      'Mises à jour automatiques des normes',
    ],
    norms: [
      { id: 1, name: 'ISO 9001:2015', category: 'Qualité' },
      { id: 2, name: 'ISO 9001:2008', category: 'Qualité (Ancienne version)' },
      { id: 3, name: 'ISO 19011', category: 'Audit' },
      { id: 4, name: 'ISO 10002', category: 'Satisfaction client' },
    ],
  })

  const subscribedCompanies = ref([
    { id: 1, name: 'SOMDIAA', subscribedSince: 'Jan 2024', status: 'active' },
    { id: 2, name: 'ALUCAM', subscribedSince: 'Fév 2024', status: 'active' },
    { id: 3, name: 'SABC', subscribedSince: 'Mar 2024', status: 'active' },
    { id: 4, name: 'SOCAPALM', subscribedSince: 'Avr 2024', status: 'active' },
    { id: 5, name: 'CDC', subscribedSince: 'Mai 2024', status: 'active' },
    { id: 6, name: 'SONARA', subscribedSince: 'Juin 2024', status: 'active' },
  ])

  function editOffer () {
    editDialog.value = true
  }

  function saveOffer () {
    editDialog.value = false
    console.log('Offre sauvegardée:', offer.value)
  }

  function viewCompany (companyId: number) {
    router.push(`/superadmin/companies/${companyId}`)
  }
</script>

<style scoped>
.v-btn {
  text-transform: none;
}

.v-list-item {
  border-radius: 8px;
  transition: all 0.2s ease;
}

.v-list-item:hover {
  background-color: rgba(var(--v-theme-primary), 0.06);
}
</style>
