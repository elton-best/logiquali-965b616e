<template>
  <div class="d-flex justify-space-between align-center mb-6">
    <div>
      <div class="d-flex align-center gap-3 mb-2">
        <v-btn
          icon="mdi-arrow-left"
          size="small"
          variant="text"
          @click="emit('back')"
        />
        <div>
          <div class="d-flex align-center gap-2">
            <h1 class="text-h4 font-weight-bold">{{ process.code }}</h1>
            <ProcessStatusChip :status="process.status" />
            <v-chip size="small" variant="outlined">
              v{{ currentVersion?.version_number || '1.0' }}
            </v-chip>
          </div>
          <p class="text-h6 text-medium-emphasis mt-1">{{ process.title }}</p>
        </div>
      </div>
    </div>

    <div class="d-flex gap-2">
      <v-menu>
        <template #activator="{ props: menuProps }">
          <v-btn
            v-bind="menuProps"
            prepend-icon="mdi-download"
            variant="outlined"
          >
            Exporter
            <v-icon end>mdi-chevron-down</v-icon>
          </v-btn>
        </template>
        <v-list density="compact">
          <v-list-item
            prepend-icon="mdi-file-pdf-box"
            title="Fiche Processus (PDF)"
            @click="emit('export', 'pdf')"
          />
          <v-list-item
            prepend-icon="mdi-file-word"
            title="Fiche Processus (Word .docx)"
            @click="emit('export', 'docx')"
          />
        </v-list>
      </v-menu>
      <v-btn
        color="warning"
        :disabled="disableVerify"
        prepend-icon="mdi-shield-check"
        variant="outlined"
        @click="emit('verify-document')"
      >
        Vérifier document
      </v-btn>
      <v-btn color="primary" prepend-icon="mdi-pencil" @click="emit('edit')">
        Modifier
      </v-btn>
    </div>
  </div>
</template>

<script setup lang="ts">
  import ProcessStatusChip from '@/modules/clienta/components/processes/ProcessStatusChip.vue'

  const props = defineProps<{
    process: {
      code?: string
      status?: string
      title?: string
    }
    currentVersion: { version_number?: string | number } | null
    disableVerify?: boolean
  }>()

  const emit = defineEmits<{
    (event: 'back'): void
    (event: 'export', format?: 'pdf' | 'docx'): void
    (event: 'verify-document'): void
    (event: 'edit'): void
  }>()
</script>
