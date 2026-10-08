<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-white flex items-center justify-center p-4">
    <ForgotPasswordSuccess
      v-if="submitted"
      :email="email"
      @login="navigateTo('login')"
    />

    <ForgotPasswordForm
      v-else
      v-model="email"
      @login="navigateTo('login')"
      @submit="handleSubmit"
    />
  </div>
</template>

<script setup lang="ts">
  import { ref } from 'vue'
  import { useAuth } from '@/modules/shared/composables/useAuth'
  import ForgotPasswordForm from '@/pages/auth/components/forgot-password/ForgotPasswordForm.vue'
  import ForgotPasswordSuccess from '@/pages/auth/components/forgot-password/ForgotPasswordSuccess.vue'

  const { navigateTo } = useAuth()

  const email = ref('')
  const submitted = ref(false)

  function handleSubmit () {
    submitted.value = true
  }
</script>
