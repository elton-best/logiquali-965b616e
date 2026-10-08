<template>
  <div>
    <v-checkbox v-model="modelValue" color="primary">
      <template #label>
        <span class="text-body-2">
          J'accepte les
          <a class="text-primary" href="#">conditions d'utilisation</a>
          et la
          <a class="text-primary" href="#">politique de confidentialité</a>
        </span>
      </template>
    </v-checkbox>

    <v-alert v-if="!modelValue" class="mt-4" type="warning" variant="tonal">
      Vous devez accepter les conditions pour continuer
    </v-alert>
  </div>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  const props = defineProps({
    modelValue: {
      type: Boolean,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'update:modelValue', value: boolean): void
  }>()

  const modelValue = computed({
    get: () => props.modelValue,
    set: value => emit('update:modelValue', value),
  })
</script>
