<template>
  <AppTable
    :enable-column-visibility="enableColumnVisibility"
    :default-hidden-columns="defaultHiddenColumns"
    :headers="mappedHeaders"
    :items="items"
    :items-per-page="itemsPerPage"
    :loading="loading"
    v-bind="$attrs"
    @page-change="$emit('update:page', $event)"
  >
    <!-- Status slot -->
    <template v-if="hasStatusColumn" #item.status="{ item }">
      <slot :item="item" name="status">
        <StatusChip :status="item.status" />
      </slot>
    </template>

    <!-- Active/Inactive slot -->
    <template v-if="hasActiveColumn" #item.is_active="{ item }">
      <slot :item="item" name="is_active">
        <AppBadge :variant="item.is_active ? 'success' : 'neutral'">
          {{ item.is_active ? "Actif" : "Inactif" }}
        </AppBadge>
      </slot>
    </template>

    <!-- Actions slot -->
    <template v-if="!$slots['item.actions']" #item.actions="{ item }">
      <div class="flex gap-2">
        <AppIconButton
          v-if="showView"
          ariaLabel="Voir"
          icon="Eye"
          size="sm"
          variant="ghost"
          @click="$emit('view', item)"
        />
        <AppIconButton
          v-if="showEdit"
          ariaLabel="Modifier"
          icon="Pencil"
          size="sm"
          variant="ghost"
          @click="$emit('edit', item)"
        />
        <AppIconButton
          v-if="showDelete"
          ariaLabel="Supprimer"
          class="text-error-600 hover:text-error-700"
          icon="Trash2"
          size="sm"
          variant="ghost"
          @click="$emit('delete', item)"
        />
      </div>
    </template>

    <!-- Custom slots forwarding -->
    <template v-for="(_, name) in customSlots" #[name]="slotProps">
      <slot :name="name" v-bind="slotProps" />
    </template>

    <!-- Empty state -->
    <template #empty>
      <slot name="no-data">
        <EmptyState
          :action-label="emptyActionLabel"
          :description="emptyDescription"
          :icon="emptyIcon"
          :title="emptyTitle"
          @action="$emit('create')"
        />
      </slot>
    </template>
  </AppTable>
</template>

<script setup lang="ts">
  import { computed, useSlots } from 'vue'
  import AppBadge from '@/components/common/AppBadge.vue'
  import AppIconButton from '@/components/common/AppIconButton.vue'
  import AppTable from '@/components/common/AppTable.vue'
  import EmptyState from './EmptyState.vue'
  import StatusChip from './StatusChip.vue'

  interface Props {
    headers: any[]
    items: any[]
    loading?: boolean
    itemsPerPage?: number
    search?: string
    page?: number
    totalItems?: number
    showView?: boolean
    showEdit?: boolean
    showDelete?: boolean
    emptyTitle?: string
    emptyDescription?: string
    emptyIcon?: string
    emptyActionLabel?: string
    enableColumnVisibility?: boolean
    defaultHiddenColumns?: string[]
  }

  const props = withDefaults(defineProps<Props>(), {
    loading: false,
    itemsPerPage: 10,
    search: '',
    page: 1,
    totalItems: 0,
    showView: false,
    showEdit: true,
    showDelete: true,
    emptyTitle: 'Aucune donnée',
    emptyDescription: 'Commencez par créer un élément',
    emptyIcon: 'mdi-package-variant',
    emptyActionLabel: 'Créer',
    enableColumnVisibility: true,
    defaultHiddenColumns: () => [],
  })

  defineEmits<{
    (e: 'view' | 'edit' | 'delete', item: any): void
    (e: 'create'): void
    (e: 'update:page' | 'update:items-per-page', page: number): void
  }>()

  const slots = useSlots()

  // Map Vuetify headers to AppTable columns
  const mappedHeaders = computed(() =>
    props.headers.map(h => ({
      key: h.key || h.value,
      label: h.title || h.text,
      sortable: h.sortable !== false,
    })),
  )

  const hasStatusColumn = computed(() =>
    props.headers.some(h => h.key === 'status' || h.value === 'status'),
  )

  const hasActiveColumn = computed(() =>
    props.headers.some(h => h.key === 'is_active' || h.value === 'is_active'),
  )

  const customSlots = computed(() => {
    const excluded = new Set(['status', 'is_active', 'no-data', 'empty'])
    return Object.keys(slots).reduce(
      (acc, key) => {
        const cleanKey = key.replace('item.', '')
        if (!excluded.has(cleanKey)) {
          acc[key] = true
        }
        return acc
      },
      {} as Record<string, boolean>,
    )
  })
</script>
