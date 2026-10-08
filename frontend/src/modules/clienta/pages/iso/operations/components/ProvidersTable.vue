<template>
  <v-data-table
    class="elevation-0"
    :headers="headers"
    item-value="id"
    :items="items"
    :loading="loading"
    no-data-text="Aucun prestataire trouvé"
  >
    <template #item.designation="{ item }">
      <div>
        <div class="font-weight-medium">{{ item.designation }}</div>
        <div class="text-caption text-medium-emphasis">{{ item.reference }}</div>
      </div>
    </template>

    <template #item.provider_type="{ item }">
      <v-chip size="small" variant="tonal">
        {{ formatProviderType(item.provider_type) }}
      </v-chip>
    </template>

    <template #item.phones="{ item }">
      <div class="text-body-2">
        <div>{{ item.phone_primary || '-' }}</div>
        <div class="text-medium-emphasis">{{ item.phone_secondary || '' }}</div>
      </div>
    </template>

    <template #item.contract="{ item }">
      <v-chip :color="contractStatusColor(item.contract?.status)" size="small" variant="tonal">
        {{ contractStatusLabel(item.contract?.status) }}
      </v-chip>
    </template>

    <template #item.actions="{ item }">
      <div class="d-flex ga-1 justify-end">
        <v-btn icon="mdi-eye-outline" size="small" variant="text" @click="emit('view', item)" />
        <v-btn
          icon="mdi-file-document-edit-outline"
          size="small"
          variant="text"
          @click="emit('contract', item)"
        />
        <v-btn icon="mdi-pencil-outline" size="small" variant="text" @click="emit('edit', item)" />
        <v-btn icon="mdi-delete-outline" size="small" variant="text" @click="emit('delete', item)" />
      </div>
    </template>
  </v-data-table>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import type { ProviderPartner } from '@/api/services/providerPartners.service'

  defineProps({
    headers: {
      type: Array as PropType<Array<Record<string, unknown>>>,
      required: true,
    },
    items: {
      type: Array as PropType<ProviderPartner[]>,
      required: true,
    },
    loading: {
      type: Boolean,
      required: true,
    },
    formatProviderType: {
      type: Function as PropType<(value?: string) => string>,
      required: true,
    },
    contractStatusColor: {
      type: Function as PropType<(status?: string) => string>,
      required: true,
    },
    contractStatusLabel: {
      type: Function as PropType<(status?: string) => string>,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'view', value: ProviderPartner): void
    (event: 'edit', value: ProviderPartner): void
    (event: 'contract', value: ProviderPartner): void
    (event: 'delete', value: ProviderPartner): void
  }>()
</script>
