<template>
  <div class="risks-page">
    <div class="page-header">
      <h1 class="page-title">Matrice des Risques</h1>
      <button class="btn-primary" @click="showForm = true">Nouveau Risque</button>
    </div>

    <RiskMatrix @risk-click="viewRisk" />

    <dialog v-if="showForm" class="modal-overlay">
      <div class="modal-content">
        <RiskForm @cancel="showForm = false" @success="handleSuccess" />
      </div>
    </dialog>
  </div>
</template>

<script setup lang="ts">
  import { ref } from 'vue'
  import RiskForm from '@/components/improvement/Risk/RiskForm.vue'
  import RiskMatrix from '@/components/improvement/Risk/RiskMatrix.vue'

  const showForm = ref(false)
  const viewRisk = (risk: any) => console.log('View risk:', risk)
  function handleSuccess () {
    showForm.value = false
    location.reload()
  }
</script>

<style scoped>
.risks-page {
  padding: var(--spacing-6);
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-6);
}

.page-title {
  font-size: var(--font-size-2xl);
  font-weight: var(--font-weight-bold);
  color: var(--color-text-primary);
}

.btn-primary {
  padding: var(--spacing-3) var(--spacing-4);
  background: var(--color-primary);
  color: white;
  border: none;
  border-radius: var(--border-radius-md);
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  cursor: pointer;
  transition: all var(--transition-normal);
}

.btn-primary:hover {
  background: var(--color-primary-dark);
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.btn-primary:focus {
  outline: none;
  box-shadow: 0 0 0 3px var(--color-primary-light);
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: var(--z-modal);
  animation: fadeIn var(--transition-normal);
}

.modal-content {
  background: white;
  border-radius: var(--border-radius-lg);
  padding: var(--spacing-6);
  max-width: 640px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: var(--shadow-2xl);
  animation: slideUp var(--transition-normal);
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: var(--spacing-4);
  }

  .modal-content {
    margin: var(--spacing-4);
    max-width: calc(100% - var(--spacing-8));
  }
}
</style>
