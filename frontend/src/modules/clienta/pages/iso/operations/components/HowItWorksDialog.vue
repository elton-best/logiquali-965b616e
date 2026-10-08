<template>
  <v-dialog v-model="model" max-width="760">
    <v-card class="howitworks-card" rounded="xl">
      <v-card-title class="howitworks-title">
        <div>
          <div class="text-overline">Guide rapide</div>
          {{ activeTab === 'nc' ? 'Fiche Non-conformité' : 'Fiche Plaintes/Réclamations' }}
        </div>
        <v-btn icon="mdi-close" variant="text" @click="model = false" />
      </v-card-title>
      <v-card-text class="pa-6">
        <div v-if="activeTab === 'nc'" class="howitworks-grid">
          <div class="howitworks-step">
            <div class="step-icon">
              <v-icon color="white" size="18">mdi-file-plus-outline</v-icon>
            </div>
            <div>
              <h4>Créer une NC</h4>
              <p>Renseignez l’identification, l’exigence et les causes.</p>
            </div>
          </div>
          <div class="howitworks-step">
            <div class="step-icon">
              <v-icon color="white" size="18">mdi-tools</v-icon>
            </div>
            <div>
              <h4>Ajouter des actions</h4>
              <p>Ajoutez une ou plusieurs actions avec responsable et délai.</p>
            </div>
          </div>
          <div class="howitworks-step">
            <div class="step-icon">
              <v-icon color="white" size="18">mdi-format-list-bulleted</v-icon>
            </div>
            <div>
              <h4>Consulter la liste</h4>
              <p>La liste des NC apparaît sous le formulaire.</p>
            </div>
          </div>
          <div class="howitworks-step">
            <div class="step-icon">
              <v-icon color="white" size="18">mdi-pencil-outline</v-icon>
            </div>
            <div>
              <h4>Modifier le statut</h4>
              <p>Cliquez sur le crayon pour modifier et ajuster le statut.</p>
            </div>
          </div>
        </div>

        <div v-else class="howitworks-grid">
          <div class="howitworks-step">
            <div class="step-icon">
              <v-icon color="white" size="18">mdi-file-plus-outline</v-icon>
            </div>
            <div>
              <h4>Créer une réclamation</h4>
              <p>Renseignez le client, l’objet et la description.</p>
            </div>
          </div>
          <div class="howitworks-step">
            <div class="step-icon">
              <v-icon color="white" size="18">mdi-account-check-outline</v-icon>
            </div>
            <div>
              <h4>Assigner & analyser</h4>
              <p>Choisissez un responsable et ajoutez l’analyse si nécessaire.</p>
            </div>
          </div>
          <div class="howitworks-step">
            <div class="step-icon">
              <v-icon color="white" size="18">mdi-format-list-bulleted</v-icon>
            </div>
            <div>
              <h4>Consulter la liste</h4>
              <p>La liste des réclamations apparaît sous le formulaire.</p>
            </div>
          </div>
          <div class="howitworks-step">
            <div class="step-icon">
              <v-icon color="white" size="18">mdi-pencil-outline</v-icon>
            </div>
            <div>
              <h4>Modifier</h4>
              <p>Cliquez sur l’icône crayon pour mettre à jour la fiche.</p>
            </div>
          </div>
        </div>
      </v-card-text>
      <v-card-actions class="pa-6 pt-0">
        <v-spacer />
        <v-btn variant="text" @click="emit('dismiss')">Ne plus afficher</v-btn>
        <v-btn color="primary" prepend-icon="mdi-check" @click="model = false">Compris</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  const props = defineProps({
    modelValue: {
      type: Boolean,
      required: true,
    },
    activeTab: {
      type: String,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'update:modelValue', value: boolean): void
    (event: 'dismiss'): void
  }>()

  const model = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit('update:modelValue', value),
  })
</script>

<style scoped>
.howitworks-card {
  border: 1px solid rgba(15, 23, 42, 0.08);
  box-shadow: 0 25px 60px rgba(15, 23, 42, 0.2);
}

.howitworks-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
  font-weight: 700;
  border-bottom: 1px solid rgba(15, 23, 42, 0.08);
  background: linear-gradient(90deg, rgba(91, 141, 217, 0.15), rgba(91, 141, 217, 0.02));
}

.howitworks-grid {
  display: grid;
  gap: 16px;
}

.howitworks-step {
  display: grid;
  grid-template-columns: 42px minmax(0, 1fr);
  gap: 12px;
  padding: 12px 14px;
  border-radius: 14px;
  background: rgba(248, 250, 252, 0.9);
  border: 1px solid rgba(15, 23, 42, 0.08);
}

.howitworks-step h4 {
  margin: 0 0 4px;
  font-size: 1rem;
  color: #0f172a;
}

.howitworks-step p {
  margin: 0;
  color: rgba(15, 23, 42, 0.75);
  font-size: 0.9rem;
}

.step-icon {
  width: 36px;
  height: 36px;
  border-radius: 12px;
  background: linear-gradient(135deg, #5b8dd9 0%, #4a71b0 100%);
  display: grid;
  place-items: center;
}
</style>
