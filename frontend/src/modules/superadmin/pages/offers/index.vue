<template>
  <SuperAdminLayout current-page="offers">
    <v-container class="pa-6" fluid>
      <!-- Header -->
      <v-row class="mb-6">
        <v-col cols="12">
          <div class="d-flex justify-space-between align-center flex-wrap ga-4">
            <div>
              <div class="d-flex align-center mb-2">
                <v-icon
                  class="mr-3"
                  color="primary"
                  size="32"
                >mdi-package-variant-closed</v-icon>
                <h1 class="text-h4 font-weight-bold text-primary">
                  Gestion des Offres
                </h1>
              </div>
              <p class="text-subtitle-1 text-grey-darken-1">
                Gérez toutes les offres d'abonnement disponibles sur la
                plateforme
              </p>
            </div>
            <div class="d-flex align-center ga-3">
              <v-btn-toggle v-model="viewMode" density="compact" mandatory>
                <v-btn value="table" variant="outlined">
                  <v-icon start>mdi-table</v-icon>
                  Table
                </v-btn>
                <v-btn value="cards" variant="outlined">
                  <v-icon start>mdi-view-grid</v-icon>
                  Cartes
                </v-btn>
              </v-btn-toggle>
              <v-btn
                color="primary"
                prepend-icon="mdi-plus"
                variant="flat"
                @click="openDialog()"
              >
                Créer une Offre
              </v-btn>
            </div>
          </div>
        </v-col>
      </v-row>

      <!-- Offers Table -->
      <v-card v-if="loading" elevation="2">
        <v-card-text>
          <v-skeleton-loader type="table-row@5" />
        </v-card-text>
      </v-card>

      <v-card
        v-else-if="offers.length > 0 && viewMode === 'table'"
        elevation="2"
      >
        <v-card-text>
          <v-data-table
            class="offers-table"
            density="compact"
            :headers="headers"
            hide-default-footer
            :items="offers"
            :items-per-page="10"
          >
            <template #item.name="{ item }">
              <div>
                <div class="font-weight-medium">{{ item.name }}</div>
                <div v-if="item.description" class="text-caption text-grey">
                  {{ item.description.substring(0, 60)
                  }}{{ item.description.length > 60 ? "..." : "" }}
                </div>
              </div>
            </template>

            <template #item.norms="{ item }">
              <div class="d-flex flex-wrap gap-1">
                <v-chip
                  v-for="norm in item.norms?.slice(0, 3) || []"
                  :key="norm.id"
                  color="primary"
                  size="x-small"
                  variant="tonal"
                >
                  {{ norm.code }}
                </v-chip>
                <v-chip
                  v-if="(item.norms?.length || 0) > 3"
                  color="primary"
                  size="x-small"
                  variant="outlined"
                >
                  +{{ (item.norms?.length || 0) - 3 }}
                </v-chip>
                <span
                  v-if="!item.norms || item.norms.length === 0"
                  class="text-caption text-grey"
                >
                  -
                </span>
              </div>
            </template>

            <template #item.price="{ item }">
              <span class="font-weight-medium text-primary">{{
                formatPrice(item.price)
              }}</span>
            </template>

            <template #item.duration_months="{ item }">
              {{ item.duration_months }} mois
            </template>

            <template #item.status>
              <v-chip color="success" size="x-small" variant="tonal">
                Disponible
              </v-chip>
            </template>

            <template #item.actions="{ item }">
              <div class="d-flex align-center justify-center gap-2">
                <v-tooltip location="top" text="Modifier">
                  <template #activator="{ props: tooltipProps }">
                    <v-btn
                      v-bind="tooltipProps"
                      icon="mdi-pencil-outline"
                      size="small"
                      variant="text"
                      @click="openDialog(item)"
                    />
                  </template>
                </v-tooltip>
                <v-tooltip location="top" text="Supprimer">
                  <template #activator="{ props: tooltipProps }">
                    <v-btn
                      v-bind="tooltipProps"
                      color="error"
                      icon="mdi-delete-outline"
                      size="small"
                      variant="text"
                      @click="confirmDelete(item)"
                    />
                  </template>
                </v-tooltip>
              </div>
            </template>
          </v-data-table>
        </v-card-text>
      </v-card>

      <v-row v-else-if="offers.length > 0 && viewMode === 'cards'" class="mt-2">
        <v-col
          v-for="offer in offers"
          :key="offer.id"
          cols="12"
          lg="4"
          md="6"
        >
          <v-card elevation="2">
            <v-card-title class="d-flex justify-space-between align-center">
              <div>
                <div
                  class="text-subtitle-1 font-weight-bold text-wrap"
                  style="white-space: normal"
                >
                  {{ offer.name }}
                </div>
                <div class="text-caption text-medium-emphasis">
                  {{ offer.duration_months }} mois
                </div>
              </div>
              <v-chip
                color="success"
                size="x-small"
                variant="tonal"
              >Disponible</v-chip>
            </v-card-title>
            <v-card-text>
              <div class="text-h6 text-primary font-weight-bold mb-2">
                {{ formatPrice(offer.price) }}
              </div>
              <div
                v-if="offer.description"
                class="text-body-2 text-medium-emphasis mb-3"
              >
                {{ offer.description.substring(0, 80)
                }}{{ offer.description.length > 80 ? "..." : "" }}
              </div>
              <div class="d-flex flex-wrap gap-1">
                <v-chip
                  v-for="norm in offer.norms?.slice(0, 3) || []"
                  :key="norm.id"
                  color="primary"
                  size="x-small"
                  variant="tonal"
                >
                  {{ norm.code }}
                </v-chip>
                <v-chip
                  v-if="(offer.norms?.length || 0) > 3"
                  color="primary"
                  size="x-small"
                  variant="outlined"
                >
                  +{{ (offer.norms?.length || 0) - 3 }}
                </v-chip>
              </div>
            </v-card-text>
            <v-card-actions class="justify-end">
              <v-tooltip location="top" text="Modifier">
                <template #activator="{ props: tooltipProps }">
                  <v-btn
                    v-bind="tooltipProps"
                    icon="mdi-pencil-outline"
                    variant="text"
                    @click="openDialog(offer)"
                  />
                </template>
              </v-tooltip>
              <v-tooltip location="top" text="Supprimer">
                <template #activator="{ props: tooltipProps }">
                  <v-btn
                    v-bind="tooltipProps"
                    color="error"
                    icon="mdi-delete-outline"
                    variant="text"
                    @click="confirmDelete(offer)"
                  />
                </template>
              </v-tooltip>
            </v-card-actions>
          </v-card>
        </v-col>
      </v-row>

      <!-- Empty State -->
      <v-row v-else>
        <v-col cols="12">
          <v-card class="text-center pa-12" elevation="2">
            <EmptyState
              description="Créez votre première offre d'abonnement pour commencer."
              icon="mdi-package-variant-closed"
              title="Aucune offre disponible"
            />
            <v-btn
              color="primary"
              prepend-icon="mdi-plus"
              variant="flat"
              @click="openDialog()"
            >
              Créer une offre
            </v-btn>
          </v-card>
        </v-col>
      </v-row>
    </v-container>

    <!-- Create/Edit Dialog -->
    <v-dialog v-model="dialog" max-width="700" persistent>
      <v-card style="border-radius: 16px">
        <v-card-title class="pa-6 pb-4 d-flex align-center">
          <v-icon
            class="mr-3"
            :color="editMode ? 'warning' : 'primary'"
            size="32"
          >
            {{ editMode ? "mdi-pencil-outline" : "mdi-plus-circle-outline" }}
          </v-icon>
          <span class="text-h6 font-weight-bold">
            {{ editMode ? "Modifier l'offre" : "Créer une offre" }}
          </span>
        </v-card-title>

        <v-divider />

        <v-card-text class="pa-6">
          <v-form ref="formRef" @submit.prevent="handleSubmit">
            <v-text-field
              v-model="formData.name"
              class="mb-4"
              :error-messages="fieldErrors.name"
              label="Nom de l'offre *"
              placeholder="Ex: Pack ISO 9001 Standard"
              :rules="[rules.required]"
              variant="outlined"
            />

            <v-textarea
              v-model="formData.description"
              class="mb-4"
              :error-messages="fieldErrors.description"
              label="Description"
              placeholder="Décrivez cette offre..."
              rows="3"
              variant="outlined"
            />

            <v-row class="mb-4">
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model.number="formData.price"
                  :error-messages="fieldErrors.price"
                  label="Prix (FCFA) *"
                  prefix="FCFA"
                  :rules="[rules.required, rules.positive]"
                  type="number"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model.number="formData.duration_months"
                  :error-messages="fieldErrors.duration_months"
                  label="Durée (mois) *"
                  :rules="[rules.required, rules.positive]"
                  suffix="mois"
                  type="number"
                  variant="outlined"
                />
              </v-col>
            </v-row>

            <!-- Norms Selection -->
            <div class="mb-4">
              <p class="text-body-2 font-weight-medium mb-3">
                Norme associée *
              </p>

              <v-skeleton-loader v-if="loadingNorms" type="chip@3" />

              <v-select
                v-else-if="availableNorms.length > 0"
                v-model="formData.norms"
                chips
                closable-chips
                :error-messages="fieldErrors.norms"
                item-title="name"
                item-value="id"
                :items="availableNorms"
                label="Sélectionnez une ou plusieurs normes"
                multiple
                :rules="[rules.required]"
                variant="outlined"
              >
                <template #item="{ item, props }">
                  <v-list-item v-bind="props">
                    <template #prepend>
                      <v-icon>mdi-certificate</v-icon>
                    </template>
                    <v-list-item-title>{{ item.raw.code }}</v-list-item-title>
                    <v-list-item-subtitle>{{
                      item.raw.name
                    }}</v-list-item-subtitle>
                  </v-list-item>
                </template>
              </v-select>

              <div v-else class="mt-2">
                <EmptyState
                  description="Créez-en d'abord dans la section Normes."
                  icon="mdi-certificate-outline"
                  title="Aucune norme disponible"
                />
              </div>

              <v-messages
                v-if="normsError"
                class="mt-2"
                color="error"
                :messages="[normsError]"
              />
            </div>
          </v-form>
        </v-card-text>

        <v-divider />

        <v-card-actions class="pa-6 pt-4">
          <v-spacer />
          <v-btn :disabled="submitting" variant="text" @click="closeDialog">
            Annuler
          </v-btn>
          <v-btn
            color="primary"
            :loading="submitting"
            variant="flat"
            @click="handleSubmit"
          >
            {{ editMode ? "Modifier" : "Créer" }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <ConfirmDialog
      v-model="deleteDialog"
      confirm-label="Supprimer"
      impact="Impact: l'offre sera retiree du catalogue super admin et ne pourra plus etre souscrite."
      :loading="deleting"
      :message="`Etes-vous sur de vouloir supprimer ${offerToDelete?.name} ?`"
      title="Supprimer l'offre"
      @cancel="deleteDialog = false"
      @confirm="handleDelete"
    />
  </SuperAdminLayout>
</template>

<script setup lang="ts">
  import type { Norm, Offer } from '@/types/api'
  import { onMounted, ref, watch } from 'vue'
  import { useToast } from '@/composables/useToast'
  import { useActionLock } from '@/modules/shared/composables/useActionLock'
  import ConfirmDialog from '@/modules/superadmin/components/ConfirmDialog.vue'
  import EmptyState from '@/modules/superadmin/components/EmptyState.vue'
  import SuperAdminLayout from '@/modules/superadmin/components/SuperAdminLayout.vue'
  import superAdminService from '@/services/superAdminService'

  const toast = useToast()

  const loading = ref(true)
  const loadingNorms = ref(true)
  const submitting = ref(false)
  const deleting = ref(false)
  const offers = ref<Offer[]>([])
  const availableNorms = ref<Norm[]>([])
  const viewMode = ref<'table' | 'cards'>(
    localStorage.getItem('sa_view_offers') === 'cards' ? 'cards' : 'table',
  )
  const dialog = ref(false)
  const deleteDialog = ref(false)
  const editMode = ref(false)
  const offerToDelete = ref<Offer | null>(null)
  const normsError = ref('')
  const fieldErrors = ref<Record<string, string[]>>({})
  const { run: runLocked } = useActionLock()

  const formRef = ref()
  const formData = ref({
    name: '',
    description: '',
    norms: [] as number[],
    price: 0,
    duration_months: 1,
  })

  const rules = {
    required: (v: any) => !!v || 'Ce champ est obligatoire',
    positive: (v: number) => v > 0 || 'Doit être supérieur à 0',
  }
  const headers = [
    { title: 'Nom', key: 'name' },
    { title: 'Normes', key: 'norms' },
    { title: 'Prix', key: 'price' },
    { title: 'Durée', key: 'duration_months' },
    { title: 'Statut', key: 'status' },
    {
      title: 'Actions',
      key: 'actions',
      sortable: false,
      align: 'center' as const,
    },
  ]

  watch(viewMode, mode => {
    localStorage.setItem('sa_view_offers', mode)
  })

  onMounted(async () => {
    await Promise.all([loadOffers(), loadNorms()])
  })

  async function loadOffers () {
    loading.value = true
    try {
      offers.value = await superAdminService.getOffers()
    } catch (error: any) {
      toast.error(
        error?.response?.data?.message
        || 'Impossible de charger les offres pour le moment.',
      )
    } finally {
      loading.value = false
    }
  }

  async function loadNorms () {
    loadingNorms.value = true
    try {
      availableNorms.value = await superAdminService.getNorms()
    } catch (error: any) {
      toast.error(
        error?.response?.data?.message
        || 'Impossible de charger les normes disponibles.',
      )
    } finally {
      loadingNorms.value = false
    }
  }

  function openDialog (offer?: Offer) {
    if (offer) {
      editMode.value = true
      formData.value = {
        name: offer.name,
        description: offer.description || '',
        norms: offer.norms?.map(n => n.id) || [],
        price: offer.price,
        duration_months: offer.duration_months,
      };
      (formData.value as any).id = offer.id
    } else {
      editMode.value = false
      formData.value = {
        name: '',
        description: '',
        norms: [],
        price: 0,
        duration_months: 1,
      }
    }
    dialog.value = true
  }

  function closeDialog () {
    dialog.value = false
    normsError.value = ''
    formRef.value?.reset()
  }

  async function handleSubmit () {
    const { valid } = await formRef.value.validate()
    if (!valid) return

    // Validate norms selection
    if (!formData.value.norms || formData.value.norms.length === 0) {
      normsError.value = 'Sélectionnez au moins une norme'
      return
    }

    await runLocked('offer-submit', async () => {
      submitting.value = true
      fieldErrors.value = {}
      try {
        const payload = {
          name: formData.value.name,
          description: formData.value.description || undefined,
          norms: formData.value.norms,
          price: formData.value.price,
          duration_months: formData.value.duration_months,
        }

        if (editMode.value) {
          await superAdminService.updateOffer(
            (formData.value as any).id,
            payload,
          )
          toast.success('Offre modifiée')
        } else {
          await superAdminService.createOffer(payload)
          toast.success('Offre créée')
        }

        closeDialog()
        await loadOffers()
      } catch (error: any) {
        if (error?.response?.status === 422 && error?.response?.data?.errors) {
          fieldErrors.value = error.response.data.errors
          toast.error('Merci de vérifier les champs requis.')
        } else {
          toast.error(
            error?.response?.data?.message
            || 'Erreur lors de la création de l\'offre',
          )
        }
      } finally {
        submitting.value = false
      }
    })
  }

  function confirmDelete (offer: Offer) {
    offerToDelete.value = offer
    deleteDialog.value = true
  }

  async function handleDelete () {
    if (!offerToDelete.value) return

    await runLocked('offer-delete', async () => {
      deleting.value = true
      try {
        await superAdminService.deleteOffer(offerToDelete.value!.id)
        toast.success('Offre supprimée')
        deleteDialog.value = false
        offerToDelete.value = null
        await loadOffers()
      } catch (error: any) {
        toast.error(
          error?.response?.data?.message
          || 'Suppression impossible pour cette offre.',
        )
      } finally {
        deleting.value = false
      }
    })
  }

  function formatPrice (price: number): string {
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: 'XOF',
      minimumFractionDigits: 0,
    }).format(price)
  }

  function formatDate (date?: string): string {
    if (!date) return 'N/A'
    return new Date(date).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    })
  }
</script>
