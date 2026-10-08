<template>
  <div class="hero-progress-row">
    <slot />
  </div>
</template>

<style scoped>
.hero-progress-row {
  display: grid;
  grid-template-columns: 1fr 400px;
  gap: 24px;
  align-items: start;
}

@media (max-width: 1024px) {
  .hero-progress-row {
    grid-template-columns: 1fr;
  }
}
</style>
