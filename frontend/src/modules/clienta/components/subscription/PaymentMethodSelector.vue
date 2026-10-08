<template>
  <div class="payment-selector">
    <h3 class="text-h6 font-weight-bold mb-4">Moyen de paiement</h3>

    <v-row>
      <v-col v-for="method in paymentMethods" :key="method.id" cols="12" sm="6">
        <v-card
          :class="['payment-card', { selected: selectedMethod === method.id }]"
          elevation="0"
          rounded="lg"
          @click="selectMethod(method.id)"
        >
          <v-card-text class="pa-4">
            <div class="d-flex align-center gap-3">
              <v-avatar :color="method.color" size="48" variant="tonal">
                <v-icon :color="method.color" size="24">{{ method.icon }}</v-icon>
              </v-avatar>
              <div class="flex-grow-1">
                <div class="font-weight-bold">{{ method.name }}</div>
                <div class="text-caption text-medium-emphasis">{{ method.description }}</div>
              </div>
              <v-radio
                color="primary"
                hide-details
                :model-value="selectedMethod"
                :value="method.id"
              />
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-text-field
      v-if="needsPhoneNumber"
      v-model="phoneNumber"
      class="mt-4"
      label="Numéro de téléphone"
      placeholder="Ex: 22997123456"
      prepend-inner-icon="mdi-phone"
      rounded="lg"
      variant="outlined"
      @update:model-value="$emit('update:phone', phoneNumber)"
    />

    <v-select
      v-if="needsPhoneNumber"
      v-model="indicatif"
      class="mt-3"
      item-title="label"
      item-value="value"
      :items="indicatifOptions"
      label="Indicatif"
      prepend-inner-icon="mdi-flag-outline"
      rounded="lg"
      variant="outlined"
      @update:model-value="$emit('update:indicatif', indicatif)"
    />
  </div>
</template>

<script setup lang="ts">
  import { computed, ref } from 'vue'

  const props = defineProps<{
    modelValue: string
  }>()

  const emit = defineEmits<{
    'update:modelValue': [value: string]
    'update:phone': [value: string]
    'update:indicatif': [value: string]
  }>()

  const phoneNumber = ref('')
  const indicatif = ref('229')
  const indicatifOptions = [
    { label: '+229 (Bénin)', value: '229' },
    { label: '+228 (Togo)', value: '228' },
  ]

  const paymentMethods = [
    {
      id: 'mtn_momo',
      name: 'MTN Mobile Money',
      description: 'Paiement via MTN MoMo',
      icon: 'mdi-cellphone',
      color: 'warning',
    },
    {
      id: 'moov_money',
      name: 'Moov Money',
      description: 'Paiement via Moov Money',
      icon: 'mdi-cellphone',
      color: 'info',
    },
    {
      id: 'coris_money',
      name: 'Coris Money',
      description: 'Paiement via Coris Money',
      icon: 'mdi-cellphone',
      color: 'success',
    },
    {
      id: 'yas_money',
      name: 'YAS Money',
      description: 'Paiement via YAS Money',
      icon: 'mdi-credit-card',
      color: 'primary',
    },
  ]

  const selectedMethod = computed(() => props.modelValue)

  const needsPhoneNumber = computed(() => {
    return ['mtn_momo', 'moov_money', 'coris_money', 'yas_money'].includes(selectedMethod.value)
  })

  function selectMethod (methodId: string) {
    emit('update:modelValue', methodId)
  }
</script>

<style scoped>
.payment-card {
  border: 2px solid #e2e8f0;
  cursor: pointer;
  transition: all 0.2s ease;
}

.payment-card:hover {
  border-color: #5b8dd9;
}

.payment-card.selected {
  border-color: #5b8dd9;
  background: rgba(91, 141, 217, 0.05);
}
</style>
