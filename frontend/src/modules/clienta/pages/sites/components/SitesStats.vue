<template>
  <v-row class="mb-6">
    <v-col cols="12" md="3" sm="6">
      <AppWidget icon="MapPin" title="Total Sites" :value="stats.total" variant="primary" />
    </v-col>

    <v-col cols="12" md="3" sm="6">
      <AppWidget icon="CheckCircle" title="Sites Actifs" :value="stats.active" variant="success" />
    </v-col>

    <v-col cols="12" md="3" sm="6">
      <AppWidget icon="Users" title="Utilisateurs" :value="stats.users" variant="info" />
    </v-col>

    <v-col cols="12" md="3" sm="6">
      <AppWidget icon="Factory" title="Processus" :value="stats.processes" variant="warning" />
    </v-col>
  </v-row>
</template>

<script setup lang="ts">
  import { AppWidget } from '@/components/common'

  defineProps<{
    stats: {
      total: number
      active: number
      users: number
      processes: number
    }
  }>()
</script>
