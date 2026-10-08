<template>
  <ClientALayout current-page="iso-operations">
    <PageHeader
      :icon="icon"
      :subtitle="subtitle"
      :title="title"
    >
      <template #actions>
        <slot name="actions" />
      </template>
    </PageHeader>

    <v-card class="hero mb-6" elevation="0" rounded="xl">
      <v-card-text class="pa-6 pa-md-8">
        <div class="d-flex flex-wrap justify-space-between ga-6 align-center">
          <div>
            <div class="text-overline hero-kicker mb-2">Exécution Opérationnelle</div>
            <h2 class="text-h4 text-md-h3 font-weight-black mb-2">
              {{ heroTitle }}
            </h2>
            <p class="text-body-1 text-medium-emphasis mb-0">
              {{ heroDescription }}
            </p>
          </div>
          <div class="d-flex ga-3 flex-wrap">
            <v-card class="kpi-card" rounded="lg" variant="flat">
              <v-card-text class="px-4 py-3">
                <div class="text-caption text-medium-emphasis">Éléments suivis</div>
                <div class="text-h5 font-weight-bold">{{ items.length }}</div>
              </v-card-text>
            </v-card>
            <v-card class="kpi-card" rounded="lg" variant="flat">
              <v-card-text class="px-4 py-3">
                <div class="text-caption text-medium-emphasis">En cours</div>
                <div class="text-h5 font-weight-bold">{{ inProgressCount }}</div>
              </v-card-text>
            </v-card>
            <v-card class="kpi-card" rounded="lg" variant="flat">
              <v-card-text class="px-4 py-3">
                <div class="text-caption text-medium-emphasis">Clôturés</div>
                <div class="text-h5 font-weight-bold">{{ closedCount }}</div>
              </v-card-text>
            </v-card>
          </div>
        </div>
      </v-card-text>
    </v-card>

    <v-row class="mb-4" dense>
      <v-col cols="12" md="7">
        <v-card rounded="xl" variant="tonal">
          <v-card-title class="text-subtitle-1 font-weight-bold pb-0">
            Ce qui doit être fait
          </v-card-title>
          <v-card-text>
            <ul class="checklist pl-5">
              <li v-for="(step, idx) in checklist" :key="`${moduleKey}-step-${idx}`">
                {{ step }}
              </li>
            </ul>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="5">
        <v-card rounded="xl" variant="outlined">
          <v-card-title class="text-subtitle-1 font-weight-bold pb-0">
            Preuves attendues
          </v-card-title>
          <v-card-text>
            <div class="d-flex flex-wrap ga-2">
              <v-chip
                v-for="(deliverable, idx) in deliverables"
                :key="`${moduleKey}-deliverable-${idx}`"
                color="primary"
                size="small"
                variant="tonal"
              >
                {{ deliverable }}
              </v-chip>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <slot name="procedures" />

    <v-card class="mb-4" rounded="xl" variant="tonal">
      <v-card-text class="pa-4">
        <v-row dense>
          <v-col cols="12" md="4">
            <v-text-field
              v-model="search"
              clearable
              hide-details
              label="Rechercher (référence, action, pilote, preuve...)"
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="statusFilter"
              clearable
              hide-details
              item-title="title"
              item-value="value"
              :items="statusOptions"
              label="Statut"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-text-field
              v-model="processFilter"
              clearable
              hide-details
              label="Processus"
              prepend-inner-icon="mdi-cog-outline"
              variant="outlined"
            />
          </v-col>
          <v-col class="d-flex justify-end align-center" cols="12" md="2">
            <v-btn
              class="mr-2"
              :disabled="!hasActiveFilters"
              prepend-icon="mdi-filter-off"
              variant="outlined"
              @click="resetFilters"
            >
              Réinitialiser
            </v-btn>
            <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreateDialog">
              Ajouter
            </v-btn>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <v-card rounded="xl">
      <v-data-table
        density="comfortable"
        :headers="headers"
        :items="filteredItems"
      >
        <template #[`item.status`]="{ item }">
          <v-chip :color="statusColor(item.status)" size="small" variant="flat">
            {{ statusLabel(item.status) }}
          </v-chip>
        </template>
        <template #[`item.actions`]="{ item }">
          <div class="d-flex ga-1">
            <v-btn icon="mdi-pencil" size="small" variant="text" @click="openEditDialog(item)" />
            <v-btn
              color="error"
              icon="mdi-delete"
              size="small"
              variant="text"
              @click="deleteItem(item.id)"
            />
          </div>
        </template>
        <template #no-data>
          <div class="text-center py-8 text-medium-emphasis">{{ emptyStateMessage }}</div>
        </template>
      </v-data-table>
    </v-card>

    <v-dialog v-model="dialog" max-width="860">
      <v-card rounded="xl">
        <v-card-title>{{ editingId ? 'Modifier un élément' : 'Ajouter un élément' }}</v-card-title>
        <v-card-text>
          <v-row>
            <v-col cols="12" md="4">
              <v-text-field v-model="form.reference" label="Référence" variant="outlined" />
            </v-col>
            <v-col cols="12" md="8">
              <v-text-field v-model="form.action" label="Action / Exigence" variant="outlined" />
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field v-model="form.process" label="Processus" variant="outlined" />
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field v-model="form.owner" label="Pilote / Responsable" variant="outlined" />
            </v-col>
            <v-col cols="12" md="4">
              <v-select
                v-model="form.status"
                item-title="title"
                item-value="value"
                :items="statusOptions"
                label="Statut"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12" md="6">
              <AppDatePickerField v-model="form.deadline" label="Échéance" mode="date" />
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field v-model="form.evidence" label="Preuve associée" variant="outlined" />
            </v-col>
            <v-col cols="12">
              <v-textarea v-model="form.notes" label="Commentaires" rows="3" variant="outlined" />
            </v-col>
          </v-row>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="dialog = false">Annuler</v-btn>
          <v-btn color="primary" @click="saveItem">Enregistrer</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref, watch } from 'vue'

  type StatusValue = 'planned' | 'in_progress' | 'blocked' | 'completed'

  interface WorkspaceItem {
    id: number
    reference: string
    action: string
    process: string
    owner: string
    status: StatusValue
    deadline: string
    evidence: string
    notes: string
  }

  interface WorkspaceForm {
    reference: string
    action: string
    process: string
    owner: string
    status: StatusValue
    deadline: string
    evidence: string
    notes: string
  }

  const props = defineProps<{
    moduleKey: string
    title: string
    subtitle: string
    icon: string
    heroTitle: string
    heroDescription: string
    checklist: string[]
    deliverables: string[]
    seedItems?: Array<Record<string, unknown>>
  }>()

  const statusOptions = [
    { title: 'Planifié', value: 'planned' },
    { title: 'En cours', value: 'in_progress' },
    { title: 'Bloqué', value: 'blocked' },
    { title: 'Clôturé', value: 'completed' },
  ]

  const headers = [
    { title: 'Référence', key: 'reference' },
    { title: 'Action / Exigence', key: 'action' },
    { title: 'Processus', key: 'process' },
    { title: 'Pilote', key: 'owner' },
    { title: 'Échéance', key: 'deadline' },
    { title: 'Statut', key: 'status' },
    { title: 'Preuve', key: 'evidence' },
    { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
  ]

  const items = ref<WorkspaceItem[]>([])
  const dialog = ref(false)
  const editingId = ref<number | null>(null)
  const search = ref('')
  const statusFilter = ref<string | null>(null)
  const processFilter = ref('')

  const form = ref<WorkspaceForm>({
    reference: '',
    action: '',
    process: '',
    owner: '',
    status: 'planned',
    deadline: '',
    evidence: '',
    notes: '',
  })

  const storageKey = computed(() => `operations_workspace_${props.moduleKey}`)

  const filteredItems = computed(() => {
    const searchValue = search.value.trim().toLowerCase()
    const processValue = processFilter.value.trim().toLowerCase()

    return items.value.filter(item => {
      const haystack = [
        item.reference,
        item.action,
        item.process,
        item.owner,
        item.evidence,
        item.notes,
      ].join(' ').toLowerCase()

      const matchesSearch = !searchValue || haystack.includes(searchValue)
      const matchesStatus = !statusFilter.value || item.status === statusFilter.value
      const matchesProcess = !processValue || item.process.toLowerCase().includes(processValue)

      return matchesSearch && matchesStatus && matchesProcess
    })
  })

  const hasActiveFilters = computed(() => {
    return Boolean(search.value.trim() || statusFilter.value || processFilter.value.trim())
  })

  const emptyStateMessage = computed(() => {
    if (items.value.length === 0) {
      return 'Aucun élément suivi pour ce sous-module.'
    }

    return 'Aucun élément ne correspond aux filtres sélectionnés.'
  })

  const inProgressCount = computed(() => items.value.filter(item => item.status === 'in_progress').length)
  const closedCount = computed(() => items.value.filter(item => item.status === 'completed').length)

  function statusLabel (status: StatusValue): string {
    return statusOptions.find(option => option.value === status)?.title || status
  }

  function statusColor (status: StatusValue): string {
    switch (status) {
      case 'in_progress': { return 'warning'
      }
      case 'blocked': { return 'error'
      }
      case 'completed': { return 'success'
      }
      default: { return 'info'
      }
    }
  }

  function resetForm (): void {
    form.value = {
      reference: '',
      action: '',
      process: '',
      owner: '',
      status: 'planned',
      deadline: '',
      evidence: '',
      notes: '',
    }
  }

  function openCreateDialog (): void {
    editingId.value = null
    resetForm()
    dialog.value = true
  }

  function resetFilters (): void {
    search.value = ''
    statusFilter.value = null
    processFilter.value = ''
  }

  function openEditDialog (item: WorkspaceItem): void {
    editingId.value = item.id
    form.value = {
      reference: item.reference,
      action: item.action,
      process: item.process,
      owner: item.owner,
      status: item.status,
      deadline: item.deadline,
      evidence: item.evidence,
      notes: item.notes,
    }
    dialog.value = true
  }

  function saveItem (): void {
    const payload: WorkspaceItem = {
      id: editingId.value || Date.now(),
      reference: form.value.reference.trim(),
      action: form.value.action.trim(),
      process: form.value.process.trim(),
      owner: form.value.owner.trim(),
      status: form.value.status,
      deadline: form.value.deadline,
      evidence: form.value.evidence.trim(),
      notes: form.value.notes.trim(),
    }

    if (editingId.value) {
      items.value = items.value.map(item => item.id === editingId.value ? payload : item)
    } else {
      items.value.unshift(payload)
    }

    dialog.value = false
  }

  function deleteItem (id: number): void {
    items.value = items.value.filter(item => item.id !== id)
  }

  function mapSeedItem (seed: Record<string, unknown>, index: number): WorkspaceItem {
    const seedStatus = String(seed.status || '').trim().toLowerCase()
    let normalizedStatus: StatusValue = 'planned'
    switch (seedStatus) {
      case 'in_progress': {
        normalizedStatus = 'in_progress'

        break
      }
      case 'blocked': {
        normalizedStatus = 'blocked'

        break
      }
      case 'completed': {
        normalizedStatus = 'completed'

        break
      }
    // No default
    }

    return {
      id: Number(seed.id) || Date.now() + index,
      reference: String(seed.reference || `ISO8-${index + 1}`),
      action: String(seed.action || ''),
      process: String(seed.process || ''),
      owner: String(seed.owner || ''),
      status: normalizedStatus,
      deadline: String(seed.deadline || ''),
      evidence: String(seed.evidence || ''),
      notes: String(seed.notes || ''),
    }
  }

  function loadItems (): void {
    const raw = localStorage.getItem(storageKey.value)
    if (raw) {
      try {
        const parsed = JSON.parse(raw)
        if (Array.isArray(parsed)) {
          items.value = parsed.map((item, index) => mapSeedItem(item, index))
          return
        }
      } catch {
        // Ignore invalid local storage value and reset from seeds.
      }
    }

    items.value = Array.isArray(props.seedItems)
      ? props.seedItems.map((seed, index) => mapSeedItem(seed, index))
      : []
  }

  onMounted(loadItems)

  watch(items, value => {
    localStorage.setItem(storageKey.value, JSON.stringify(value))
  }, { deep: true })
</script>

<style scoped>
.hero {
  background:
    radial-gradient(1200px 420px at 0% -10%, rgba(10, 132, 255, 0.18), transparent 60%),
    radial-gradient(900px 360px at 100% 0%, rgba(0, 184, 148, 0.16), transparent 60%),
    linear-gradient(135deg, rgba(17, 24, 39, 0.02), rgba(255, 255, 255, 0.6));
  border: 1px solid rgba(15, 23, 42, 0.08);
}

.hero-kicker {
  letter-spacing: 0.12em;
  color: rgba(15, 23, 42, 0.68);
}

.kpi-card {
  min-width: 150px;
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: rgba(255, 255, 255, 0.85);
}

.checklist {
  margin: 0;
}

.checklist li {
  margin-bottom: 6px;
}
</style>
