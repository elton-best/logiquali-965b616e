<template>
  <v-card
    :class="['offer-card', { selected: isSelected }]"
    elevation="0"
    rounded="lg"
    @click="$emit('select')"
  >
    <v-card-text class="pa-6">
      <div class="d-flex justify-space-between align-center mb-4">
        <h3 class="text-h6 font-weight-bold">{{ offer.name }}</h3>
        <v-checkbox
          color="primary"
          hide-details
          :model-value="isSelected"
          @click.stop
          @update:model-value="$emit('select')"
        />
      </div>

      <p class="text-body-2 text-medium-emphasis mb-4">{{ offer.description }}</p>

      <div class="price-section">
        <div class="price">
          <span class="amount">{{ formatPrice(offer.price) }}</span>
          <span class="currency">FCFA</span>
        </div>
        <div class="duration text-caption text-medium-emphasis">
          / {{ offer.duration_months }} mois
        </div>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import type { Offer } from '@/services/subscriptionService'

  defineProps<{
    offer: Offer
    isSelected: boolean
  }>()

  defineEmits<{
    select: []
  }>()

  function formatPrice (price: string | number): string {
    return new Intl.NumberFormat('fr-FR').format(Number(price))
  }
</script>

<style scoped>
.offer-card {
  border: 2px solid #e2e8f0;
  cursor: pointer;
  transition: all 0.3s ease;
}

.offer-card:hover {
  border-color: #5b8dd9;
  transform: translateY(-4px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

.offer-card.selected {
  border-color: #5b8dd9;
  background: rgba(91, 141, 217, 0.05);
}

.price-section {
  padding: 16px;
  background: rgba(91, 141, 217, 0.05);
  border-radius: 8px;
}

.price {
  display: flex;
  align-items: baseline;
  gap: 4px;
}

.amount {
  font-size: 2rem;
  font-weight: 700;
  color: #1e293b;
}

.currency {
  font-size: 1rem;
  font-weight: 600;
  color: #64748b;
}
</style>
