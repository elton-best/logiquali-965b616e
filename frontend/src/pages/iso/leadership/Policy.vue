<template>
  <ClientALayout>
    <div class="leadership-page">
      <div class="content-shell">
        <!-- Hero Card with Glassmorphism -->
        <div class="hero-card glass-card animate-fade-in">
          <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
            <div class="flex items-start gap-5">
              <div class="hero-icon">
                <v-icon color="primary" size="40">mdi-shield-check</v-icon>
              </div>
              <div>
                <h1 class="hero-title">Politique QHSE</h1>
                <p class="hero-subtitle">Définissez votre politique qualité, santé, sécurité et environnement</p>
                <div class="meta-badges">
                  <span class="badge badge-primary">
                    <v-icon size="14">mdi-check-circle</v-icon>
                    Version 1.0
                  </span>
                  <span class="badge badge-secondary">
                    <v-icon size="14">mdi-update</v-icon>
                    Mise à jour continue
                  </span>
                </div>
              </div>
            </div>
            <div class="status-badge">
              <span class="status-dot" />
              Brouillon
            </div>
          </div>
        </div>

        <!-- Progress Card -->
        <div class="progress-card glass-card animate-slide-up">
          <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-6">
              <div class="progress-circle">
                <svg class="progress-ring" height="72" width="72">
                  <circle class="progress-ring-bg" cx="36" cy="36" r="32" />
                  <circle
                    class="progress-ring-fill"
                    cx="36"
                    cy="36"
                    r="32"
                    :style="{ strokeDashoffset: progressOffset }"
                  />
                </svg>
                <div class="progress-text">{{ completion }}%</div>
              </div>
              <div>
                <div class="progress-label">Progression</div>
                <div class="progress-value">{{ filledFields }}/{{ totalFields }} champs</div>
              </div>
            </div>
            <div class="last-saved">
              <v-icon class="mr-1" size="16">mdi-clock-outline</v-icon>
              {{ lastSavedLabel }}
            </div>
          </div>
        </div>

        <!-- Tabs -->
        <div class="tabs-container">
          <button
            v-for="tab in tabs"
            :key="tab.value"
            :class="['tab-button', { active: activeTab === tab.value }]"
            @click="activeTab = tab.value"
          >
            <v-icon size="20">{{ tab.icon }}</v-icon>
            {{ tab.label }}
          </button>
        </div>

        <!-- Content -->
        <transition mode="out-in" name="fade-slide">
          <div v-if="activeTab === 'form'" key="form" class="content-card glass-card">
            <div class="form-section">
              <h2 class="section-title">
                <span class="section-number">01</span>
                Informations principales
              </h2>

              <div class="form-grid">
                <div class="form-field">
                  <label class="field-label required">Titre</label>
                  <input
                    v-model="form.title"
                    class="field-input"
                    placeholder="Ex: Politique QHSE 2026"
                  >
                </div>

                <div class="form-field full-width">
                  <label class="field-label required">Présentation de la structure</label>
                  <textarea
                    v-model="form.presentation"
                    class="field-textarea"
                    placeholder="Décrivez brièvement votre organisation..."
                    rows="4"
                  />
                </div>

                <div class="form-field full-width">
                  <label class="field-label required">Mission</label>
                  <textarea
                    v-model="form.mission"
                    class="field-textarea"
                    placeholder="Quelle est votre mission principale ?"
                    rows="3"
                  />
                </div>

                <div class="form-field full-width">
                  <label class="field-label required">Vision</label>
                  <textarea
                    v-model="form.vision"
                    class="field-textarea"
                    placeholder="Quelle est votre vision à long terme ?"
                    rows="3"
                  />
                </div>

                <div class="form-field full-width">
                  <label class="field-label">Valeurs</label>
                  <textarea
                    v-model="form.values"
                    class="field-textarea"
                    placeholder="Quelles sont vos valeurs fondamentales ?"
                    rows="3"
                  />
                </div>
              </div>
            </div>

            <div class="form-section">
              <h2 class="section-title">
                <span class="section-number">02</span>
                Axes stratégiques
              </h2>

              <div class="dynamic-list">
                <div v-for="(axis, index) in form.axes" :key="`axis-${index}`" class="list-item">
                  <div class="list-number">{{ index + 1 }}</div>
                  <input
                    v-model="form.axes[index]"
                    class="list-input"
                    :placeholder="`Axe stratégique ${index + 1}`"
                  >
                  <button
                    class="list-remove"
                    :disabled="form.axes.length === 1"
                    @click="removeAxis(index)"
                  >
                    <v-icon size="18">mdi-close</v-icon>
                  </button>
                </div>
                <button class="add-button" @click="addAxis">
                  <v-icon size="20">mdi-plus</v-icon>
                  Ajouter un axe
                </button>
              </div>
            </div>

            <div class="form-section">
              <h2 class="section-title">
                <span class="section-number">03</span>
                Engagements
              </h2>

              <div class="dynamic-list">
                <div v-for="(commitment, index) in form.commitments" :key="`commitment-${index}`" class="list-item">
                  <div class="list-number">{{ index + 1 }}</div>
                  <input
                    v-model="form.commitments[index]"
                    class="list-input"
                    :placeholder="`Engagement ${index + 1}`"
                  >
                  <button
                    class="list-remove"
                    :disabled="form.commitments.length === 1"
                    @click="removeCommitment(index)"
                  >
                    <v-icon size="18">mdi-close</v-icon>
                  </button>
                </div>
                <button class="add-button" @click="addCommitment">
                  <v-icon size="20">mdi-plus</v-icon>
                  Ajouter un engagement
                </button>
              </div>
            </div>

            <div class="form-actions">
              <button class="btn-secondary" @click="resetForm">
                <v-icon size="20">mdi-refresh</v-icon>
                Réinitialiser
              </button>
              <button class="btn-primary" :disabled="!isFormValid" @click="activeTab = 'preview'">
                Aperçu
                <v-icon size="20">mdi-arrow-right</v-icon>
              </button>
            </div>
          </div>

          <div v-else key="preview" class="content-card glass-card">
            <div class="preview-header">
              <div>
                <h2 class="preview-title">{{ form.title || 'Politique QHSE' }}</h2>
                <p class="preview-subtitle">Document généré automatiquement</p>
              </div>
              <div class="preview-actions">
                <button class="btn-secondary" @click="activeTab = 'form'">
                  <v-icon size="20">mdi-arrow-left</v-icon>
                  Retour
                </button>
                <button class="btn-primary" @click="handleDownloadWord">
                  <v-icon size="20">mdi-file-word</v-icon>
                  Télécharger
                </button>
              </div>
            </div>

            <div class="preview-content">
              <div class="preview-section">
                <h3>Présentation de la structure</h3>
                <p>{{ form.presentation || '—' }}</p>
              </div>

              <div class="preview-section">
                <h3>Mission</h3>
                <p>{{ form.mission || '—' }}</p>
              </div>

              <div class="preview-section">
                <h3>Vision</h3>
                <p>{{ form.vision || '—' }}</p>
              </div>

              <div v-if="form.values.trim()" class="preview-section">
                <h3>Valeurs</h3>
                <p>{{ form.values }}</p>
              </div>

              <div class="preview-section">
                <h3>Axes stratégiques</h3>
                <ul v-if="filteredAxes.length > 0">
                  <li v-for="(axis, index) in filteredAxes" :key="index">{{ axis }}</li>
                </ul>
                <p v-else class="empty-state">Aucun axe défini</p>
              </div>

              <div class="preview-section">
                <h3>Engagements</h3>
                <ul v-if="filteredCommitments.length > 0">
                  <li v-for="(item, index) in filteredCommitments" :key="index">{{ item }}</li>
                </ul>
                <p v-else class="empty-state">Aucun engagement défini</p>
              </div>
            </div>
          </div>
        </transition>
      </div>
    </div>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref, watch } from 'vue'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'

  const form = ref({
    title: 'Politique QHSE',
    presentation: '',
    mission: '',
    vision: '',
    values: '',
    axes: [''],
    commitments: [''],
  })

  const activeTab = ref<'form' | 'preview'>('form')
  const lastSavedAt = ref<Date | null>(null)

  const tabs: Array<{ value: 'form' | 'preview', label: string, icon: string }> = [
    { value: 'form', label: 'Formulaire', icon: 'mdi-form-select' },
    { value: 'preview', label: 'Aperçu', icon: 'mdi-eye' },
  ]

  const filteredAxes = computed(() => form.value.axes.filter(item => item.trim()))
  const filteredCommitments = computed(() => form.value.commitments.filter(item => item.trim()))

  const requiredFields = computed(() => [
    form.value.title.trim(),
    form.value.presentation.trim(),
    form.value.mission.trim(),
    form.value.vision.trim(),
  ])

  const totalFields = 6
  const filledFields = computed(() => {
    return requiredFields.value.filter(Boolean).length
      + (filteredAxes.value.length > 0 ? 1 : 0)
      + (filteredCommitments.value.length > 0 ? 1 : 0)
  })

  const completion = computed(() => Math.round((filledFields.value / totalFields) * 100))
  const progressOffset = computed(() => 201 - (201 * completion.value) / 100)
  const isFormValid = computed(() => requiredFields.value.every(Boolean))
  const lastSavedLabel = computed(() => lastSavedAt.value?.toLocaleString() || 'Jamais')

  const STORAGE_KEY = 'leadership-policy-qhse-draft'

  function addAxis () {
    form.value.axes.push('')
  }
  function removeAxis (index: number) {
    if (form.value.axes.length > 1) form.value.axes.splice(index, 1)
  }
  function addCommitment () {
    form.value.commitments.push('')
  }
  function removeCommitment (index: number) {
    if (form.value.commitments.length > 1) form.value.commitments.splice(index, 1)
  }
  function resetForm () {
    if (confirm('Réinitialiser le formulaire ?')) location.reload()
  }

  function handleDownloadWord () {
    const html = `<!DOCTYPE html>
<html><head><meta charset="utf-8"/><title>${form.value.title}</title></head>
<body><h1>${form.value.title}</h1>
<h2>Présentation</h2><p>${form.value.presentation}</p>
<h2>Mission</h2><p>${form.value.mission}</p>
<h2>Vision</h2><p>${form.value.vision}</p>
${form.value.values ? `<h2>Valeurs</h2><p>${form.value.values}</p>` : ''}
<h2>Axes</h2><ul>${filteredAxes.value.map(a => `<li>${a}</li>`).join('')}</ul>
<h2>Engagements</h2><ul>${filteredCommitments.value.map(c => `<li>${c}</li>`).join('')}</ul>
</body></html>`

    const blob = new Blob(['\uFEFF', html], { type: 'application/msword' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `politique_qhse_${Date.now()}.doc`
    a.click()
    URL.revokeObjectURL(url)
  }

  onMounted(() => {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (raw) {
      try {
        const parsed = JSON.parse(raw)
        form.value = { ...form.value, ...parsed }
      } catch {}
    }
  })

  watch(form, value => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(value))
    lastSavedAt.value = new Date()
  }, { deep: true })
</script>

<style scoped>
.leadership-page {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  min-height: 100vh;
  padding: 32px 24px;
}

.content-shell {
  max-width: 1400px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.glass-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 24px;
  border: 1px solid rgba(255, 255, 255, 0.3);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1), 0 2px 8px rgba(0, 0, 0, 0.05);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.glass-card:hover {
  box-shadow: 0 12px 48px rgba(0, 0, 0, 0.15), 0 4px 16px rgba(0, 0, 0, 0.08);
  transform: translateY(-2px);
}

.hero-card {
  padding: 32px;
}

.hero-icon {
  width: 80px;
  height: 80px;
  border-radius: 20px;
  background: linear-gradient(135deg, #5b8dd9 0%, #4a71b0 100%);
  display: grid;
  place-items: center;
  box-shadow: 0 8px 24px rgba(91, 141, 217, 0.3);
}

.hero-title {
  font-size: 2rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 8px;
}

.hero-subtitle {
  font-size: 1rem;
  color: #64748b;
  margin-bottom: 16px;
}

.meta-badges {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
}

.badge-primary {
  background: rgba(91, 141, 217, 0.15);
  color: #4a71b0;
}

.badge-secondary {
  background: rgba(100, 116, 139, 0.15);
  color: #475569;
}

.status-badge {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  background: rgba(245, 158, 11, 0.15);
  border-radius: 12px;
  font-weight: 600;
  color: #d97706;
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #f59e0b;
  animation: pulse 2s infinite;
}

.progress-card {
  padding: 24px 32px;
}

.progress-circle {
  position: relative;
  width: 72px;
  height: 72px;
}

.progress-ring {
  transform: rotate(-90deg);
}

.progress-ring-bg {
  fill: none;
  stroke: #e2e8f0;
  stroke-width: 6;
}

.progress-ring-fill {
  fill: none;
  stroke: url(#gradient);
  stroke-width: 6;
  stroke-linecap: round;
  stroke-dasharray: 201;
  transition: stroke-dashoffset 0.5s ease;
}

.progress-text {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 1.25rem;
  font-weight: 700;
  color: #1e293b;
}

.progress-label {
  font-size: 0.875rem;
  color: #64748b;
  margin-bottom: 4px;
}

.progress-value {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1e293b;
}

.last-saved {
  display: flex;
  align-items: center;
  font-size: 0.875rem;
  color: #64748b;
}

.tabs-container {
  display: flex;
  gap: 8px;
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(20px);
  padding: 6px;
  border-radius: 16px;
  border: 1px solid rgba(255, 255, 255, 0.3);
}

.tab-button {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px 24px;
  border-radius: 12px;
  font-weight: 600;
  color: #64748b;
  background: transparent;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
}

.tab-button:hover {
  background: rgba(91, 141, 217, 0.1);
  color: #4a71b0;
}

.tab-button.active {
  background: linear-gradient(135deg, #5b8dd9 0%, #4a71b0 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(91, 141, 217, 0.3);
}

.content-card {
  padding: 40px;
}

.form-section {
  margin-bottom: 48px;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 16px;
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 32px;
}

.section-number {
  display: grid;
  place-items: center;
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: linear-gradient(135deg, #5b8dd9 0%, #4a71b0 100%);
  color: white;
  font-size: 1.25rem;
  font-weight: 700;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 24px;
}

.form-field {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-field.full-width {
  grid-column: 1 / -1;
}

.field-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #1e293b;
}

.field-label.required::after {
  content: '*';
  color: #ef4444;
  margin-left: 4px;
}

.field-input,
.field-textarea {
  padding: 14px 16px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 0.9375rem;
  color: #1e293b;
  background: white;
  transition: all 0.2s;
}

.field-input:focus,
.field-textarea:focus {
  outline: none;
  border-color: #5b8dd9;
  box-shadow: 0 0 0 4px rgba(91, 141, 217, 0.1);
}

.field-textarea {
  resize: vertical;
  font-family: inherit;
}

.dynamic-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.list-item {
  display: flex;
  align-items: center;
  gap: 12px;
}

.list-number {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: rgba(91, 141, 217, 0.1);
  color: #4a71b0;
  display: grid;
  place-items: center;
  font-weight: 600;
  font-size: 0.875rem;
}

.list-input {
  flex: 1;
  padding: 12px 16px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 0.9375rem;
  transition: all 0.2s;
}

.list-input:focus {
  outline: none;
  border-color: #5b8dd9;
  box-shadow: 0 0 0 4px rgba(91, 141, 217, 0.1);
}

.list-remove {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  border: none;
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
  cursor: pointer;
  transition: all 0.2s;
}

.list-remove:hover:not(:disabled) {
  background: rgba(239, 68, 68, 0.2);
}

.list-remove:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.add-button {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px 24px;
  border: 2px dashed #cbd5e1;
  border-radius: 12px;
  background: transparent;
  color: #64748b;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.add-button:hover {
  border-color: #5b8dd9;
  color: #5b8dd9;
  background: rgba(91, 141, 217, 0.05);
}

.form-actions {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  padding-top: 32px;
  border-top: 1px solid #e2e8f0;
}

.btn-primary,
.btn-secondary {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 14px 28px;
  border-radius: 12px;
  font-weight: 600;
  font-size: 0.9375rem;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary {
  background: linear-gradient(135deg, #5b8dd9 0%, #4a71b0 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(91, 141, 217, 0.3);
}

.btn-primary:hover:not(:disabled) {
  box-shadow: 0 6px 20px rgba(91, 141, 217, 0.4);
  transform: translateY(-2px);
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-secondary {
  background: white;
  color: #64748b;
  border: 2px solid #e2e8f0;
}

.btn-secondary:hover {
  border-color: #cbd5e1;
  background: #f8fafc;
}

.preview-header {
  display: flex;
  justify-content: space-between;
  align-items: start;
  margin-bottom: 40px;
  padding-bottom: 24px;
  border-bottom: 2px solid #e2e8f0;
}

.preview-title {
  font-size: 2rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 8px;
}

.preview-subtitle {
  font-size: 0.875rem;
  color: #64748b;
}

.preview-actions {
  display: flex;
  gap: 12px;
}

.preview-content {
  display: flex;
  flex-direction: column;
  gap: 32px;
}

.preview-section h3 {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 16px;
}

.preview-section p {
  font-size: 1rem;
  line-height: 1.7;
  color: #475569;
}

.preview-section ul {
  list-style: none;
  padding: 0;
}

.preview-section li {
  position: relative;
  padding-left: 28px;
  margin-bottom: 12px;
  font-size: 1rem;
  line-height: 1.7;
  color: #475569;
}

.preview-section li::before {
  content: '';
  position: absolute;
  left: 0;
  top: 10px;
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #5b8dd9;
}

.empty-state {
  color: #94a3b8;
  font-style: italic;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.animate-fade-in {
  animation: fadeIn 0.5s ease;
}

.animate-slide-up {
  animation: slideUp 0.5s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.3s ease;
}

.fade-slide-enter-from {
  opacity: 0;
  transform: translateX(-20px);
}

.fade-slide-leave-to {
  opacity: 0;
  transform: translateX(20px);
}

@media (max-width: 768px) {
  .leadership-page { padding: 16px; }
  .hero-card, .content-card { padding: 24px; }
  .form-grid { grid-template-columns: 1fr; }
  .form-actions { flex-direction: column; }
  .preview-header { flex-direction: column; gap: 16px; }
}
</style>
