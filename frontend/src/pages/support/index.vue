<template>
  <div class="support-module">
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-900">Module Support</h1>
      <p class="text-gray-600 mt-2">Gestion des ressources, compétences, communication et documentation</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <router-link
        class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow cursor-pointer"
        to="/support/ressources"
      >
        <div class="flex items-center justify-center w-12 h-12 bg-blue-100 rounded-lg mb-4">
          <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
          </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Ressources</h3>
        <p class="text-sm text-gray-600">Inventaire des équipements et plan de maintenance</p>
      </router-link>

      <router-link
        class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow cursor-pointer"
        to="/support/competences"
      >
        <div class="flex items-center justify-center w-12 h-12 bg-green-100 rounded-lg mb-4">
          <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
          </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Compétences</h3>
        <p class="text-sm text-gray-600">Gestion des compétences et formations</p>
      </router-link>

      <router-link
        class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow cursor-pointer"
        to="/support/awareness"
      >
        <div class="flex items-center justify-center w-12 h-12 bg-purple-100 rounded-lg mb-4">
          <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
          </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Sensibilisation</h3>
        <p class="text-sm text-gray-600">Actions de sensibilisation des équipes</p>
      </router-link>

      <router-link
        class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow cursor-pointer"
        to="/support/communication"
      >
        <div class="flex items-center justify-center w-12 h-12 bg-indigo-100 rounded-lg mb-4">
          <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M7 8h10M7 12h7m-7 4h10M5 4h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
          </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Communication</h3>
        <p class="text-sm text-gray-600">Plans de communication interne et externe</p>
      </router-link>

      <router-link
        class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow cursor-pointer"
        to="/support/information-documentee"
      >
        <div class="flex items-center justify-center w-12 h-12 bg-orange-100 rounded-lg mb-4">
          <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
          </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Information documentée</h3>
        <p class="text-sm text-gray-600">Gestion documentaire et archives</p>
      </router-link>
    </div>
  </div>
</template>

<script setup lang="ts">
</script>

<style scoped>
.support-module {
  padding: 2rem;
}
</style>
