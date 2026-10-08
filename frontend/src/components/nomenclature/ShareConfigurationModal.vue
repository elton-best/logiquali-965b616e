<template>
  <v-dialog v-model="show" max-width="560" persistent>
    <v-card>
      <v-card-title class="d-flex justify-space-between align-center pa-4">
        <span class="text-h6">Partager la configuration</span>
        <v-btn icon size="small" variant="text" @click="close">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-card-title>

      <v-divider />

      <v-card-text class="pa-4">
        <!-- Info config -->
        <v-sheet class="pa-3 mb-4 d-flex align-center gap-3" color="surface-variant" rounded="lg">
          <v-icon color="primary">mdi-cog-outline</v-icon>
          <div>
            <div class="text-body-2 font-weight-medium">{{ configuration?.type_label }}</div>
            <code class="text-caption">{{ configuration?.type_code }}</code>
          </div>
        </v-sheet>

        <!-- Tabs -->
        <v-tabs v-model="tab" class="mb-4" color="primary" density="compact">
          <v-tab prepend-icon="mdi-office-building-outline" value="sites">Sites</v-tab>
          <v-tab v-if="canShareWithEnterprises" prepend-icon="mdi-domain" value="enterprises">
            Entreprises
          </v-tab>
        </v-tabs>

        <v-tabs-window v-model="tab">

          <!-- Sites -->
          <v-tabs-window-item value="sites">
            <p class="text-body-2 text-medium-emphasis mb-3">
              Une copie sera créée pour chaque site sélectionné.
            </p>
            <v-select
              v-model="conflictStrategy"
              class="mb-3"
              density="compact"
              :items="[
                { title: 'Ignorer si existant (skip)', value: 'skip' },
                { title: 'Remplacer si existant (override)', value: 'override' },
              ]"
              label="Gestion des conflits"
              variant="outlined"
            />
            <div v-if="loadingSites" class="d-flex justify-center py-4">
              <v-progress-circular color="primary" indeterminate size="32" />
            </div>
            <v-alert v-else-if="availableSites.length === 0" density="compact" type="info" variant="tonal">
              Aucun site disponible pour le partage.
            </v-alert>
            <v-list
              v-else
              class="rounded border"
              density="compact"
              max-height="260"
              style="overflow-y:auto;"
            >
              <v-list-item
                v-for="site in availableSites"
                :key="site.id"
                :subtitle="`ID ${site.id}`"
                :title="site.name"
                @click="toggleSite(site.id)"
              >
                <template #prepend>
                  <v-checkbox-btn
                    color="primary"
                    :model-value="selectedSiteIds.includes(site.id)"
                    @click.stop="toggleSite(site.id)"
                  />
                </template>
              </v-list-item>
            </v-list>
            <div class="text-caption text-medium-emphasis mt-2">
              {{ selectedSiteIds.length }} site(s) sélectionné(s)
            </div>
          </v-tabs-window-item>

          <!-- Entreprises -->
          <v-tabs-window-item value="enterprises">
            <p class="text-body-2 text-medium-emphasis mb-3">
              Partage inter-entreprises — réservé aux super-admins.
            </p>
            <div v-if="loadingEnterprises" class="d-flex justify-center py-4">
              <v-progress-circular color="primary" indeterminate size="32" />
            </div>
            <v-alert v-else-if="availableEnterprises.length === 0" density="compact" type="info" variant="tonal">
              Aucune entreprise disponible.
            </v-alert>
            <v-list
              v-else
              class="rounded border"
              density="compact"
              max-height="260"
              style="overflow-y:auto;"
            >
              <v-list-item
                v-for="ent in availableEnterprises"
                :key="ent.id"
                :title="ent.name"
                @click="toggleEnterprise(ent.id)"
              >
                <template #prepend>
                  <v-checkbox-btn
                    color="primary"
                    :model-value="selectedEnterpriseIds.includes(ent.id)"
                    @click.stop="toggleEnterprise(ent.id)"
                  />
                </template>
              </v-list-item>
            </v-list>
            <div class="text-caption text-medium-emphasis mt-2">
              {{ selectedEnterpriseIds.length }} entreprise(s) sélectionnée(s)
            </div>
          </v-tabs-window-item>

        </v-tabs-window>

        <!-- Résultats -->
        <template v-if="shareResults">
          <v-alert
            v-if="shareResults.success.length > 0"
            class="mt-3"
            density="compact"
            type="success"
            variant="tonal"
          >
            <strong>{{ shareResults.success.length }}</strong> partage(s) réussi(s)
            <span v-if="shareResults.success.some((s: any) => s.already_exists)" class="ml-1">
              (dont {{ shareResults.success.filter((s: any) => s.already_exists).length }} déjà existant(s))
            </span>
          </v-alert>
          <v-alert
            v-if="shareResults.errors.length > 0"
            class="mt-2"
            density="compact"
            type="error"
            variant="tonal"
          >
            <div><strong>{{ shareResults.errors.length }}</strong> erreur(s) :</div>
            <ul class="mt-1 pl-4">
              <li v-for="(err, i) in shareResults.errors" :key="i" class="text-body-2">{{ err.error }}</li>
            </ul>
          </v-alert>
        </template>
      </v-card-text>

      <v-divider />

      <v-card-actions class="pa-4">
        <v-spacer />
        <v-btn variant="text" @click="close">Annuler</v-btn>
        <v-btn
          color="primary"
          :disabled="!canShare"
          :loading="sharing"
          @click="share"
        >
          Partager
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import { computed, ref, watch } from 'vue'
  import { documentTypeConfigurationApi } from '@/api/documentTypeConfiguration'

  const props = defineProps<{
    modelValue: boolean
    configuration: any
    canShareWithEnterprises?: boolean
  }>()

  const emit = defineEmits<{
    'update:modelValue': [value: boolean]
    'shared': []
  }>()

  const show = computed({
    get: () => props.modelValue,
    set: v => emit('update:modelValue', v),
  })

  const tab = ref<'sites' | 'enterprises'>('sites')
  const availableSites = ref<any[]>([])
  const availableEnterprises = ref<any[]>([])
  const selectedSiteIds = ref<number[]>([])
  const selectedEnterpriseIds = ref<number[]>([])
  const loadingSites = ref(false)
  const loadingEnterprises = ref(false)
  const sharing = ref(false)
  const shareResults = ref<any>(null)
  const conflictStrategy = ref<'skip' | 'override'>('skip')

  const canShare = computed(() =>
    tab.value === 'sites' ? selectedSiteIds.value.length > 0 : selectedEnterpriseIds.value.length > 0,
  )

  function toggleSite (id: number) {
    const idx = selectedSiteIds.value.indexOf(id)
    idx === -1 ? selectedSiteIds.value.push(id) : selectedSiteIds.value.splice(idx, 1)
  }

  function toggleEnterprise (id: number) {
    const idx = selectedEnterpriseIds.value.indexOf(id)
    idx === -1 ? selectedEnterpriseIds.value.push(id) : selectedEnterpriseIds.value.splice(idx, 1)
  }

  watch(() => props.modelValue, async open => {
    if (!open || !props.configuration) return
    shareResults.value = null
    selectedSiteIds.value = []
    selectedEnterpriseIds.value = []
    conflictStrategy.value = 'skip'
    await loadSites()
  })

  watch(tab, async t => {
    if (!props.modelValue || !props.configuration) return
    if (t === 'enterprises' && availableEnterprises.value.length === 0) await loadEnterprises()
  })

  async function loadSites () {
    loadingSites.value = true
    try {
      const res = await documentTypeConfigurationApi.getAvailableSitesForSharing(props.configuration.id)
      availableSites.value = (res.data as any)?.data ?? res.data ?? []
    } finally {
      loadingSites.value = false
    }
  }

  async function loadEnterprises () {
    loadingEnterprises.value = true
    try {
      const res = await documentTypeConfigurationApi.getAvailableEnterprisesForSharing(props.configuration.id)
      availableEnterprises.value = (res.data as any)?.data ?? res.data ?? []
    } finally {
      loadingEnterprises.value = false
    }
  }

  async function share () {
    sharing.value = true
    shareResults.value = null
    try {
      const res = tab.value === 'sites'
        ? await documentTypeConfigurationApi.shareWithSites(
          props.configuration.id,
          selectedSiteIds.value,
          conflictStrategy.value,
        )
        : await documentTypeConfigurationApi.shareWithEnterprises(props.configuration.id, selectedEnterpriseIds.value)

      shareResults.value = (res.data as any)?.data ?? res.data
      if (!shareResults.value?.errors?.length) {
        setTimeout(() => {
          emit('shared'); close()
        }, 1500)
      }
    } finally {
      sharing.value = false
    }
  }

  function close () {
    show.value = false
  }
</script>
