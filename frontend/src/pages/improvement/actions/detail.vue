<template>
  <v-container class="pa-6" fluid>
    <v-row align="center" class="mb-4">
      <v-col>
        <v-btn class="mr-2" icon variant="text" @click="$router.back()">
          <v-icon>mdi-arrow-left</v-icon>
        </v-btn>
        <v-chip class="mr-2" color="primary" size="small">{{ action?.ref }}</v-chip>
        <span class="text-h5 font-weight-bold">{{ action?.title }}</span>
      </v-col>
    </v-row>

    <v-row class="mb-4">
      <v-col cols="3">
        <v-card>
          <v-card-text>
            <div class="text-h6">{{ action?.progress || 0 }}%</div>
            <div class="text-caption">Progression</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-card>
      <v-tabs v-model="tab" bg-color="primary">
        <v-tab value="general">Général</v-tab>
        <v-tab value="documents">Documents</v-tab>
      </v-tabs>
      <v-window v-model="tab">
        <v-window-item value="general">
          <v-card-text>
            <p>{{ action?.description }}</p>
          </v-card-text>
        </v-window-item>
        <v-window-item value="documents">
          <v-card-text>
            <v-alert type="info">Aucun document</v-alert>
          </v-card-text>
        </v-window-item>
      </v-window>
    </v-card>
  </v-container>
</template>

<script setup lang="ts">
  import type { Action } from '@/types/action'
  import { computed, onMounted, ref } from 'vue'
  import { useRoute } from 'vue-router'
  import { actionService } from '@/services/actionService'

  const route = useRoute()
  const actionId = computed(() => {
    const rawId = (route.params as Record<string, unknown>).id
    const id = typeof rawId === 'string' ? Number(rawId) : Number.NaN
    return Number.isFinite(id) ? id : null
  })
  const action = ref<Action | null>(null)
  const tab = ref('general')

  async function loadAction () {
    if (!actionId.value) {
      return
    }

    try {
      action.value = await actionService.getById(actionId.value)
    } catch (error) {
      console.error(error)
    }
  }

  onMounted(() => loadAction())
</script>
