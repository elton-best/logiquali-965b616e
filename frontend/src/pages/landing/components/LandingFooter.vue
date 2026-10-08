<template>
  <v-footer class="bg-grey-darken-4 text-white py-8">
    <v-container>
      <v-row>
        <v-col cols="12" md="4">
          <div class="d-flex align-center mb-4">
            <AppLogo framed surface="dark" variant="full" />
          </div>
          <p class="text-body-2" style="opacity: 0.7;">
            La solution QHSE de référence pour les entreprises modernes.
          </p>
        </v-col>

        <v-col cols="12" md="8">
          <v-row>
            <v-col cols="6" md="4">
              <h4 class="text-subtitle-1 font-weight-bold mb-3">Produit</h4>
              <v-list class="bg-transparent" density="compact">
                <v-list-item class="px-0 footer-link" @click="emit('scroll', 'features')">Fonctionnalités</v-list-item>
                <v-list-item class="px-0 footer-link" @click="emit('scroll', 'pricing')">Tarifs</v-list-item>
                <v-list-item class="px-0 footer-link" @click="emit('scroll', 'faq')">FAQ</v-list-item>
              </v-list>
            </v-col>
            <v-col cols="6" md="4">
              <h4 class="text-subtitle-1 font-weight-bold mb-3">Ressources</h4>
              <v-list class="bg-transparent" density="compact">
                <v-list-item class="px-0 footer-link" @click="emit('navigate', 'guide')">Guide d'utilisation</v-list-item>
                <v-list-item class="px-0 footer-link" @click="emit('navigate', 'contact')">Contact</v-list-item>
                <v-list-item class="px-0 footer-link" @click="emit('navigate', 'login')">Espace client</v-list-item>
              </v-list>
            </v-col>
            <v-col cols="6" md="4">
              <h4 class="text-subtitle-1 font-weight-bold mb-3">Légal</h4>
              <v-list class="bg-transparent" density="compact">
                <v-list-item class="px-0 footer-link" @click="emit('navigate', 'privacy')">Politique de confidentialité</v-list-item>
                <v-list-item class="px-0 footer-link" @click="emit('navigate', 'privacy')">Cookies</v-list-item>
                <v-list-item class="px-0 footer-link" @click="emit('navigate', 'privacy')">Protection des données</v-list-item>
              </v-list>
            </v-col>
          </v-row>
        </v-col>
      </v-row>

      <v-divider class="my-6 border-opacity-25" />

      <div class="text-center text-body-2" style="opacity: 0.7;">
        © 2026 Best Experts Group. Tous droits réservés.
      </div>
    </v-container>
  </v-footer>
</template>

<script setup lang="ts">
  import AppLogo from '@/components/branding/AppLogo.vue'

  const emit = defineEmits<{
    (event: 'scroll', target: string): void
    (event: 'navigate', route: string): void
  }>()
</script>

<style scoped>
.footer-link {
  opacity: 0.76;
  cursor: pointer;
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.footer-link:hover {
  opacity: 1;
  transform: translateX(2px);
}
</style>
