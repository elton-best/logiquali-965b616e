<template>
  <div class="table-container">
    <table class="modern-table">
      <thead>
        <tr>
          <th>Nom complet</th>
          <th>Email</th>
          <th>Poste</th>
          <th>Date de prise de service</th>
          <th>Téléphone</th>
          <th>Permissions actives</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="person in filteredPersonnel" :key="person.id">
          <td>
            <div class="person-name">
              <div class="person-avatar">
                {{ getInitials(person.nom, person.prenoms) }}
              </div>
              <div>
                <div class="name-text">{{ person.fullName }}</div>
              </div>
            </div>
          </td>
          <td>{{ person.email }}</td>
          <td>
            <span class="badge-role">{{ person.poste }}</span>
          </td>
          <td>{{ person.datePriseService || "—" }}</td>
          <td>{{ person.telephone || "—" }}</td>
          <td>
            <span class="badge-permissions">
              {{ person.activeScopedPermissionsCount || 0 }} actives
            </span>
          </td>
          <td>
            <div class="action-buttons">
              <button
                class="btn-icon info"
                @click="emit('view', person)"
              >
                <v-icon size="18">mdi-eye</v-icon>
              </button>
              <button class="btn-icon" @click="emit('edit', person)">
                <v-icon size="18">mdi-pencil</v-icon>
              </button>
              <button
                class="btn-icon danger"
                @click="emit('delete', person.id)"
              >
                <v-icon size="18">mdi-delete</v-icon>
              </button>
            </div>
          </td>
        </tr>
        <tr v-if="filteredPersonnel.length === 0">
          <td class="empty-row" colspan="7">
            {{
              personnel.length === 0
                ? "Aucun collaborateur enregistré"
                : "Aucun collaborateur ne correspond aux filtres"
            }}
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
  export type PersonnelListPerson = {
    id: number
    nom: string
    prenoms: string
    fullName: string
    telephone: string
    email: string
    poste: string
    datePriseService: string
    activeScopedPermissionsCount: number
  }

  defineProps<{
    personnel: PersonnelListPerson[]
    filteredPersonnel: PersonnelListPerson[]
    getInitials: (nom: string, prenoms: string) => string
  }>()

  const emit = defineEmits<{
    (event: 'view', person: PersonnelListPerson): void
    (event: 'edit', person: PersonnelListPerson): void
    (event: 'delete', id: number): void
  }>()

</script>

<style scoped>
.table-container {
  overflow-x: auto;
  border-radius: 16px;
}

.modern-table {
  width: 100%;
  border-collapse: collapse;
}

.modern-table th {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  padding: 12px;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 700;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.modern-table td {
  padding: 12px;
  border-top: 1px solid #e2e8f0;
  font-size: 0.85rem;
  color: #1e293b;
}

.modern-table tbody tr:hover {
  background: rgba(91, 141, 217, 0.05);
}

.person-name {
  display: flex;
  align-items: center;
  gap: 10px;
}

.person-avatar {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  background: linear-gradient(135deg, #5b8dd9 0%, #4a71b0 100%);
  color: white;
  display: grid;
  place-items: center;
  font-weight: 700;
  font-size: 0.75rem;
}

.name-text {
  font-weight: 600;
  color: #1e293b;
}

.date-text {
  font-size: 0.75rem;
  color: #64748b;
}

.badge-role {
  display: inline-block;
  padding: 3px 10px;
  background: rgba(91, 141, 217, 0.1);
  color: #4a71b0;
  border-radius: 8px;
  font-size: 0.7rem;
  font-weight: 600;
}

.badge-permissions {
  display: inline-block;
  padding: 3px 10px;
  background: rgba(34, 197, 94, 0.1);
  color: #16a34a;
  border-radius: 8px;
  font-size: 0.7rem;
  font-weight: 600;
}

.action-buttons {
  display: flex;
  gap: 8px;
}

.btn-icon {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  border: none;
  background: rgba(91, 141, 217, 0.1);
  color: #4a71b0;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-icon:hover {
  background: rgba(91, 141, 217, 0.2);
}

.btn-icon.danger {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

.btn-icon.danger:hover {
  background: rgba(239, 68, 68, 0.2);
}

.btn-icon.info {
  background: rgba(14, 165, 233, 0.12);
  color: #0284c7;
}

.btn-icon.info:hover {
  background: rgba(14, 165, 233, 0.2);
}

.empty-row {
  text-align: center;
  color: #94a3b8;
  font-style: italic;
  padding: 32px !important;
}
</style>
