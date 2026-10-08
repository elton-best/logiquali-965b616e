<template>
  <v-card class="mt-6" elevation="0">
    <v-card-text class="pa-6">
      <!-- Header skeleton -->
      <div class="d-flex align-center justify-space-between mb-6">
        <v-skeleton-loader type="heading" width="200" />
        <v-skeleton-loader type="button" width="120" />
      </div>

      <!-- Table skeleton -->
      <v-table>
        <thead>
          <tr>
            <th v-for="n in columns" :key="n">
              <v-skeleton-loader type="text" width="100" />
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="n in rows" :key="n">
            <td v-for="m in columns" :key="m">
              <v-skeleton-loader :type="getSkeletonType(m)" />
            </td>
          </tr>
        </tbody>
      </v-table>

      <!-- Pagination skeleton -->
      <div class="d-flex justify-end mt-6">
        <v-skeleton-loader type="button" width="300" />
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  interface Props {
    rows?: number
    columns?: number
  }

  withDefaults(defineProps<Props>(), {
    rows: 5,
    columns: 6,
  })

  function getSkeletonType (column: number): string {
    if (column === 1) return 'text@2'
    if (column === 2) return 'chip'
    return 'text'
  }
</script>
