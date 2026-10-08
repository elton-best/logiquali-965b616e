<template>
  <div class="terms-section">
    <v-checkbox
      v-model="modelValue"
      class="terms-checkbox"
      color="#4471c4"
      hide-details
      :rules="rules"
    >
      <template #label>
        <div class="terms-label">
          J'accepte les
          <a class="terms-link" href="#" @click.prevent>conditions d'utilisation</a>
          et la
          <a class="terms-link" href="#" @click.prevent>politique de confidentialité</a>
        </div>
      </template>
    </v-checkbox>
  </div>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import { computed } from 'vue'

  const props = defineProps({
    modelValue: {
      type: Boolean,
      required: true,
    },
    rules: {
      type: Array as PropType<Array<(v: boolean) => boolean | string>>,
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
