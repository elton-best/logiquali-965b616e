<template>
  <div class="demo-section" data-aos="fade-up">
    <button class="demo-toggle" type="button" @click="emit('toggle')">
      <v-icon size="18">mdi-flask-outline</v-icon>
      <span>Comptes de démonstration</span>
      <v-icon :class="{ rotated: open }" size="18">mdi-chevron-down</v-icon>
    </button>

    <v-expand-transition>
      <div v-if="open" class="demo-accounts">
        <div
          v-for="(account, index) in accounts"
          :key="index"
          class="demo-account"
          @click="emit('select', account)"
        >
          <div class="demo-account-icon">
            <v-icon size="20">mdi-account-circle</v-icon>
          </div>
          <div class="demo-account-info">
            <div class="demo-account-role">{{ account.role }}</div>
            <div class="demo-account-email">{{ account.email }}</div>
          </div>
          <v-icon class="demo-account-arrow" size="18">mdi-arrow-right</v-icon>
        </div>
      </div>
    </v-expand-transition>
  </div>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'

  type DemoAccount = { role: string, email: string, password: string }

  defineProps({
    open: {
      type: Boolean,
      required: true,
    },
    accounts: {
      type: Array as PropType<DemoAccount[]>,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'toggle'): void
    (event: 'select', value: DemoAccount): void
  }>()
</script>
