<template>
  <ClientALayout>
    <div class="leadership-page">
      <div class="content-shell">
        <!-- Hero Card -->
        <div class="hero-card glass-card animate-fade-in">
          <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
            <div class="flex items-start gap-5">
              <div class="hero-icon">
                <v-icon color="white" size="40">mdi-account-multiple</v-icon>
              </div>
              <div>
                <h1 class="hero-title">Rôles et responsabilités</h1>
                <p class="hero-subtitle">Gérez les fiches de poste et informations du personnel</p>
                <div class="meta-badges">
                  <span class="badge badge-primary">
                    <v-icon size="14">mdi-account-group</v-icon>
                    {{ personnel.length }} personnes
                  </span>
                  <span class="badge badge-secondary">
                    <v-icon size="14">mdi-update</v-icon>
                    Suivi RH
                  </span>
                </div>
              </div>
            </div>
            <button class="btn-primary" @click="addPerson">
              <v-icon size="20">mdi-plus</v-icon>
              Ajouter
            </button>
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
                <div class="progress-label">Complétude</div>
                <div class="progress-value">{{ filledCount }} champs remplis</div>
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
            <div v-for="(person, index) in personnel" :key="person.id" class="person-card">
              <div class="person-header">
                <div class="person-number">{{ index + 1 }}</div>
                <h3 class="person-title">Personnel {{ index + 1 }}</h3>
                <button
                  class="person-remove"
                  :disabled="personnel.length === 1"
                  @click="removePerson(index)"
                >
                  <v-icon size="18">mdi-delete</v-icon>
                </button>
              </div>

              <div class="person-grid">
                <div class="form-field">
                  <label class="field-label required">Nom</label>
                  <input v-model="person.nom" class="field-input" placeholder="Nom de famille">
                </div>

                <div class="form-field">
                  <label class="field-label required">Prénoms</label>
                  <input v-model="person.prenoms" class="field-input" placeholder="Prénoms">
                </div>

                <div class="form-field">
                  <label class="field-label">Téléphone</label>
                  <input v-model="person.telephone" class="field-input" placeholder="+225 01 02 03 04 05">
                </div>

                <div class="form-field">
                  <label class="field-label">Email</label>
                  <input v-model="person.email" class="field-input" placeholder="email@exemple.com" type="email">
                </div>

                <div class="form-field">
                  <label class="field-label required">Poste</label>
                  <input v-model="person.poste" class="field-input" placeholder="Intitulé du poste">
                </div>

                <div class="form-field">
                  <label class="field-label">Date de prise de service</label>
                  <input v-model="person.datePriseService" class="field-input" type="date">
                </div>

                <div class="form-field full-width">
                  <label class="field-label">Adresse</label>
                  <textarea v-model="person.adresse" class="field-textarea" placeholder="Adresse complète" rows="2" />
                </div>
              </div>
            </div>

            <div class="form-actions">
              <button class="btn-secondary" @click="addPerson">
                <v-icon size="20">mdi-plus</v-icon>
                Ajouter une personne
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
                <h2 class="preview-title">Liste du personnel</h2>
                <p class="preview-subtitle">{{ filteredPersonnel.length }} personnes enregistrées</p>
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

            <div class="table-container">
              <table class="modern-table">
                <thead>
                  <tr>
                    <th>Nom</th>
                    <th>Prénoms</th>
                    <th>Téléphone</th>
                    <th>Email</th>
                    <th>Poste</th>
                    <th>Adresse</th>
                    <th>Date de service</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="person in filteredPersonnel" :key="person.id">
                    <td>{{ person.nom || '—' }}</td>
                    <td>{{ person.prenoms || '—' }}</td>
                    <td>{{ person.telephone || '—' }}</td>
                    <td>{{ person.email || '—' }}</td>
                    <td><span class="badge-role">{{ person.poste || '—' }}</span></td>
                    <td>{{ person.adresse || '—' }}</td>
                    <td>{{ person.datePriseService || '—' }}</td>
                  </tr>
                  <tr v-if="filteredPersonnel.length === 0">
                    <td class="empty-row" colspan="7">Aucun personnel enregistré</td>
                  </tr>
                </tbody>
              </table>
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

  type Person = {
    id: number
    nom: string
    prenoms: string
    telephone: string
    email: string
    poste: string
    adresse: string
    datePriseService: string
  }

  const personnel = ref<Person[]>([createEmptyPerson()])
  const activeTab = ref<'form' | 'preview'>('form')
  const lastSavedAt = ref<Date | null>(null)

  const tabs: Array<{ value: 'form' | 'preview', label: string, icon: string }> = [
    { value: 'form', label: 'Formulaire', icon: 'mdi-form-select' },
    { value: 'preview', label: 'Aperçu', icon: 'mdi-eye' },
  ]

  const filteredPersonnel = computed(() =>
    personnel.value.filter(p => Object.entries(p).some(([k, v]) => k !== 'id' && String(v).trim())),
  )

  const isFormValid = computed(() =>
    personnel.value.every(p => p.nom.trim() && p.prenoms.trim() && p.poste.trim()),
  )

  const filledCount = computed(() => {
    return personnel.value.reduce((count, p) => {
      return count + Object.entries(p).filter(([k, v]) => k !== 'id' && String(v).trim()).length
    }, 0)
  })

  const totalFields = personnel.value.length * 7
  const completion = computed(() => Math.round((filledCount.value / totalFields) * 100))
  const progressOffset = computed(() => 201 - (201 * completion.value) / 100)
  const lastSavedLabel = computed(() => lastSavedAt.value?.toLocaleString() || 'Jamais')

  const STORAGE_KEY = 'leadership-personnel-draft'

  function createEmptyPerson (): Person {
    return {
      id: Date.now() + Math.random(),
      nom: '',
      prenoms: '',
      telephone: '',
      email: '',
      poste: '',
      adresse: '',
      datePriseService: '',
    }
  }

  function addPerson () {
    personnel.value.push(createEmptyPerson())
  }
  function removePerson (index: number) {
    if (personnel.value.length > 1) personnel.value.splice(index, 1)
  }

  function handleDownloadWord () {
    const rows = filteredPersonnel.value.map(p => `
    <tr>
      <td>${p.nom}</td>
      <td>${p.prenoms}</td>
      <td>${p.telephone}</td>
      <td>${p.email}</td>
      <td>${p.poste}</td>
      <td>${p.adresse}</td>
      <td>${p.datePriseService}</td>
    </tr>
  `).join('')

    const html = `<!DOCTYPE html>
<html><head><meta charset="utf-8"/><title>Liste du personnel</title>
<style>table{width:100%;border-collapse:collapse}th,td{border:1px solid #ddd;padding:8px;font-size:12px}th{background:#f3f4f6}</style>
</head><body><h1>Liste du personnel</h1>
<table><thead><tr><th>Nom</th><th>Prénoms</th><th>Téléphone</th><th>Email</th><th>Poste</th><th>Adresse</th><th>Date</th></tr></thead>
<tbody>${rows || '<tr><td colspan="7">Aucun personnel</td></tr>'}</tbody></table></body></html>`

    const blob = new Blob(['\uFEFF', html], { type: 'application/msword' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `personnel_${Date.now()}.doc`
    a.click()
    URL.revokeObjectURL(url)
  }

  onMounted(() => {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (raw) {
      try {
        const parsed = JSON.parse(raw)
        if (Array.isArray(parsed) && parsed.length > 0) personnel.value = parsed
      } catch {}
    }
  })

  watch(personnel, value => {
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
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.glass-card:hover {
  box-shadow: 0 12px 48px rgba(0, 0, 0, 0.15);
  transform: translateY(-2px);
}

.hero-card, .progress-card {
  padding: 32px;
}

.hero-icon {
  width: 80px;
  height: 80px;
  border-radius: 20px;
  background: linear-gradient(135deg, #ec4899 0%, #be185d 100%);
  display: grid;
  place-items: center;
  box-shadow: 0 8px 24px rgba(236, 72, 153, 0.3);
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
  background: rgba(236, 72, 153, 0.15);
  color: #be185d;
}

.badge-secondary {
  background: rgba(100, 116, 139, 0.15);
  color: #475569;
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
  stroke: #ec4899;
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
  background: rgba(236, 72, 153, 0.1);
  color: #be185d;
}

.tab-button.active {
  background: linear-gradient(135deg, #ec4899 0%, #be185d 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(236, 72, 153, 0.3);
}

.content-card {
  padding: 40px;
}

.person-card {
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 20px;
  padding: 32px;
  margin-bottom: 24px;
  transition: all 0.2s;
}

.person-card:hover {
  border-color: #ec4899;
  box-shadow: 0 4px 16px rgba(236, 72, 153, 0.1);
}

.person-header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 24px;
  padding-bottom: 16px;
  border-bottom: 2px solid #f1f5f9;
}

.person-number {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: linear-gradient(135deg, #ec4899 0%, #be185d 100%);
  color: white;
  display: grid;
  place-items: center;
  font-weight: 700;
}

.person-title {
  flex: 1;
  font-size: 1.25rem;
  font-weight: 700;
  color: #1e293b;
}

.person-remove {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  border: none;
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
  cursor: pointer;
  transition: all 0.2s;
}

.person-remove:hover:not(:disabled) {
  background: rgba(239, 68, 68, 0.2);
  transform: scale(1.1);
}

.person-remove:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.person-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
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
  border-color: #ec4899;
  box-shadow: 0 0 0 4px rgba(236, 72, 153, 0.1);
}

.field-textarea {
  resize: vertical;
  font-family: inherit;
}

.form-actions {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  padding-top: 32px;
  border-top: 2px solid #e2e8f0;
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
  background: linear-gradient(135deg, #ec4899 0%, #be185d 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(236, 72, 153, 0.3);
}

.btn-primary:hover:not(:disabled) {
  box-shadow: 0 6px 20px rgba(236, 72, 153, 0.4);
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
  margin-bottom: 32px;
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

.table-container {
  overflow-x: auto;
  border-radius: 16px;
  border: 2px solid #e2e8f0;
}

.modern-table {
  width: 100%;
  border-collapse: collapse;
}

.modern-table th {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  padding: 16px;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 700;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.modern-table td {
  padding: 16px;
  border-top: 1px solid #e2e8f0;
  font-size: 0.875rem;
  color: #1e293b;
}

.modern-table tbody tr {
  transition: all 0.2s;
}

.modern-table tbody tr:hover {
  background: rgba(236, 72, 153, 0.05);
}

.badge-role {
  display: inline-block;
  padding: 4px 12px;
  background: rgba(236, 72, 153, 0.1);
  color: #be185d;
  border-radius: 8px;
  font-size: 0.75rem;
  font-weight: 600;
}

.empty-row {
  text-align: center;
  color: #94a3b8;
  font-style: italic;
  padding: 48px !important;
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
  .person-card { padding: 20px; }
  .person-grid { grid-template-columns: 1fr; }
  .form-actions { flex-direction: column; }
  .preview-header { flex-direction: column; gap: 16px; }
}
</style>
