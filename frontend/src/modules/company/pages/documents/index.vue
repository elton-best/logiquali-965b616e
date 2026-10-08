<template>
  <v-container class="pa-6" fluid>
    <div class="d-flex justify-space-between align-center mb-6">
      <div>
        <h1 class="text-h4 font-weight-bold text-primary">Documents QHSE</h1>
        <p class="text-subtitle-1 text-grey-darken-1 mt-1">
          Gestion documentaire pyramide QHSE
        </p>
      </div>
      <v-btn
        color="primary"
        prepend-icon="mdi-plus"
        size="large"
        @click="$router.push('/company/documents/create')"
      >
        Nouveau document
      </v-btn>
    </div>

    <!-- Filters -->
    <v-card class="mb-6" elevation="2">
      <v-card-text>
        <v-row>
          <v-col cols="12" md="3">
            <v-text-field
              v-model="searchQuery"
              clearable
              density="comfortable"
              label="Rechercher"
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              @update:model-value="debouncedSearch"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="statusFilter"
              clearable
              density="comfortable"
              :items="statusOptions"
              label="Statut"
              variant="outlined"
              @update:model-value="applyFilters"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="categoryFilter"
              clearable
              density="comfortable"
              item-title="name"
              item-value="id"
              :items="categoryOptions"
              label="Catégorie"
              variant="outlined"
              @update:model-value="applyFilters"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-btn
              class="mt-1"
              color="grey"
              variant="outlined"
              @click="resetAllFilters"
            >
              <v-icon start>mdi-filter-off</v-icon>
              Réinitialiser
            </v-btn>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- Stats Cards -->
    <v-row class="mb-6">
      <v-col cols="12" md="3" sm="6">
        <v-card elevation="2">
          <v-card-text>
            <div class="d-flex justify-space-between align-center">
              <div>
                <div class="text-overline text-grey">Total</div>
                <div class="text-h4 font-weight-bold">{{ pagination.total }}</div>
              </div>
              <v-avatar color="primary" size="56">
                <v-icon size="32">mdi-file-document</v-icon>
              </v-avatar>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3" sm="6">
        <v-card elevation="2">
          <v-card-text>
            <div class="d-flex justify-space-between align-center">
              <div>
                <div class="text-overline text-grey">Brouillons</div>
                <div class="text-h4 font-weight-bold">{{ draftDocuments.length }}</div>
              </div>
              <v-avatar color="warning" size="56">
                <v-icon size="32">mdi-file-edit</v-icon>
              </v-avatar>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3" sm="6">
        <v-card elevation="2">
          <v-card-text>
            <div class="d-flex justify-space-between align-center">
              <div>
                <div class="text-overline text-grey">Publiés</div>
                <div class="text-h4 font-weight-bold">{{ publishedDocuments.length }}</div>
              </div>
              <v-avatar color="success" size="56">
                <v-icon size="32">mdi-check-circle</v-icon>
              </v-avatar>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3" sm="6">
        <v-card elevation="2">
          <v-card-text>
            <div class="d-flex justify-space-between align-center">
              <div>
                <div class="text-overline text-grey">Archivés</div>
                <div class="text-h4 font-weight-bold">{{ archivedDocuments.length }}</div>
              </div>
              <v-avatar color="grey" size="56">
                <v-icon size="32">mdi-archive</v-icon>
              </v-avatar>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Documents Table -->
    <v-card elevation="2">
      <v-card-text>
        <v-data-table
          :headers="headers"
          hide-default-footer
          :items="documents"
          :items-per-page="pagination.per_page"
          :loading="loading"
        >
          <template #item.document_number="{ item }">
            <span class="font-weight-medium text-primary">{{ item.document_number }}</span>
          </template>

          <template #item.title="{ item }">
            <div>
              <div class="font-weight-medium">{{ item.title }}</div>
              <div v-if="item.description" class="text-caption text-grey">
                {{ item.description.substring(0, 60) }}{{ item.description.length > 60 ? '...' : '' }}
              </div>
            </div>
          </template>

          <template #item.category="{ item }">
            <v-chip
              v-if="item.category"
              :color="getCategoryColor(item.category.level)"
              label
              size="small"
            >
              {{ item.category.name }}
            </v-chip>
          </template>

          <template #item.status="{ item }">
            <WorkflowStatusBadge :status="item.workflow_status || item.status" />
          </template>

          <template #item.current_version="{ item }">
            <span v-if="item.current_version">
              v{{ item.current_version.version_number }}
            </span>
            <span v-else class="text-grey">-</span>
          </template>

          <template #item.created_at="{ item }">
            {{ formatDate(item.created_at) }}
          </template>

          <template #item.actions="{ item }">
            <div class="d-flex gap-2">
              <v-tooltip location="top" text="Voir">
                <template #activator="{ props: tooltipProps }">
                  <v-btn
                    v-bind="tooltipProps"
                    icon="mdi-eye"
                    size="small"
                    variant="text"
                    @click="viewDocument(item.id)"
                  />
                </template>
              </v-tooltip>

              <v-tooltip location="top" text="Télécharger">
                <template #activator="{ props: tooltipProps }">
                  <v-btn
                    v-bind="tooltipProps"
                    :disabled="!item.current_version"
                    icon="mdi-download"
                    size="small"
                    variant="text"
                    @click="downloadDoc(item.id)"
                  />
                </template>
              </v-tooltip>

              <v-tooltip location="top" text="Supprimer">
                <template #activator="{ props: tooltipProps }">
                  <v-btn
                    v-bind="tooltipProps"
                    color="error"
                    :disabled="item.status !== 'draft'"
                    icon="mdi-delete"
                    size="small"
                    variant="text"
                    @click="confirmDelete(item)"
                  />
                </template>
              </v-tooltip>
            </div>
          </template>
        </v-data-table>

        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="d-flex justify-center mt-4">
          <v-pagination
            v-model="currentPage"
            :length="pagination.last_page"
            @update:model-value="changePage"
          />
        </div>
      </v-card-text>
    </v-card>

    <!-- Delete Dialog -->
    <v-dialog v-model="deleteDialog" max-width="500">
      <v-card>
        <v-card-title class="text-h5">Confirmer la suppression</v-card-title>
        <v-card-text>
          Êtes-vous sûr de vouloir supprimer le document "{{ documentToDelete?.title }}" ?
          Cette action est irréversible.
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="deleteDialog = false">Annuler</v-btn>
          <v-btn color="error" variant="flat" @click="executeDelete">Supprimer</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup lang="ts">
  import type { DocumentStatus } from '@/types/shared'
  import WorkflowStatusBadge from '@/modules/clienta/components/documents/WorkflowStatusBadge.vue'
  import { format } from 'date-fns'
  import { fr } from 'date-fns/locale'
  import { storeToRefs } from 'pinia'
  import { computed, onMounted, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import { useDocumentStore } from '@/stores/documentStore'

  const router = useRouter()
  const documentStore = useDocumentStore()

  const {
    documents,
    loading,
    pagination,
    categories,
    draftDocuments,
    publishedDocuments,
    archivedDocuments,
  } = storeToRefs(documentStore)

  const searchQuery = ref('')
  const statusFilter = ref<DocumentStatus>()
  const categoryFilter = ref<number>()
  const currentPage = ref(1)
  const deleteDialog = ref(false)
  const documentToDelete = ref<any>(null)

  const headers = [
    { title: 'N° Document', key: 'document_number', sortable: false },
    { title: 'Titre', key: 'title', sortable: false },
    { title: 'Catégorie', key: 'category', sortable: false },
    { title: 'Statut', key: 'status', sortable: false },
    { title: 'Version', key: 'current_version', sortable: false },
    { title: 'Date création', key: 'created_at', sortable: false },
    { title: 'Actions', key: 'actions', sortable: false, align: 'end' },
  ] as any

  const statusOptions = [
    { title: 'Brouillon', value: 'draft' },
    { title: 'En révision', value: 'under_review' },
    { title: 'Approuvé', value: 'approved' },
    { title: 'Archivé', value: 'archived' },
    { title: 'Obsolète', value: 'obsolete' },
  ]

  const categoryOptions = computed(() => categories.value)

  let searchTimeout: ReturnType<typeof setTimeout>
  function debouncedSearch () {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
      applyFilters()
    }, 500)
  }

  function applyFilters () {
    documentStore.fetchDocuments({
      search: searchQuery.value,
      status: statusFilter.value as DocumentStatus | undefined,
      category_id: categoryFilter.value,
      page: 1,
    })
    currentPage.value = 1
  }

  function resetAllFilters () {
    searchQuery.value = ''
    statusFilter.value = undefined
    categoryFilter.value = undefined
    documentStore.resetFilters()
    documentStore.fetchDocuments()
  }

  function changePage (page: number) {
    documentStore.setPage(page)
  }

  function getCategoryColor (level?: number) {
    const colors = ['blue', 'green', 'orange', 'purple', 'red']
    return colors[(level || 1) - 1] || 'grey'
  }

  function formatDate (dateString: string) {
    return format(new Date(dateString), 'dd MMM yyyy', { locale: fr })
  }

  function viewDocument (id: number) {
    router.push(`/company/documents/${id}`)
  }

  async function downloadDoc (id: number) {
    await documentStore.downloadDocument(id)
  }

  function confirmDelete (doc: any) {
    documentToDelete.value = doc
    deleteDialog.value = true
  }

  async function executeDelete () {
    if (documentToDelete.value) {
      await documentStore.deleteDocument(documentToDelete.value.id)
      deleteDialog.value = false
      documentToDelete.value = null
    }
  }

  onMounted(async () => {
    await documentStore.fetchCategories()
    await documentStore.fetchDocuments()
  })
</script>
