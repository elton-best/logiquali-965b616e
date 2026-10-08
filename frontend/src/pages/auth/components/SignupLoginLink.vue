<template>
  <div class="text-center">
    <p class="text-body-1 mb-3">
      Vous avez déjà un compte ?
    </p>
    <v-btn
      color="primary"
      size="large"
      variant="outlined"
      @click="emit('login')"
    >
      Se connecter
    </v-btn>
  </div>
</template>

<script setup lang="ts">
  const emit = defineEmits<{
    (event: 'login'): void
  }>()
</script>
