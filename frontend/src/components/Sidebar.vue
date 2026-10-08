<template>
  <aside class="sidebar">
    <div class="sidebar-brand">
      <h1 class="brand-title">SMI Manager</h1>
      <p class="brand-subtitle">Gestion intégrée</p>
    </div>

    <nav class="sidebar-nav">
      <ul class="nav-list">
        <li v-for="item in menuItems" :key="item.id">
          <button
            :class="['nav-item', { 'nav-item-active': currentPage === item.id }]"
            @click="onPageChange(item.id)"
          >
            <component :is="item.icon" class="nav-icon" />
            <span>{{ item.label }}</span>
          </button>
        </li>
      </ul>
    </nav>

    <div class="sidebar-footer">
      <div class="user-profile">
        <div class="user-avatar">
          <span class="avatar-text">AD</span>
        </div>
        <div class="user-info">
          <p class="user-name">Admin User</p>
          <p class="user-email">admin@smi.com</p>
        </div>
      </div>
    </div>
  </aside>
</template>

<script setup lang="ts">
  import type { Component } from 'vue'

  interface MenuItem {
    id: string
    label: string
    icon: Component
  }

  defineProps<{
    menuItems: MenuItem[]
    currentPage: string
  }>()

  const emit = defineEmits<{
    'page-change': [page: string]
  }>()

  function onPageChange (page: string) {
    emit('page-change', page)
  }
</script>

<style scoped>
.sidebar {
  width: 280px;
  background: var(--bg-primary);
  border-right: 1px solid var(--border-color);
  display: flex;
  flex-direction: column;
  height: 100vh;
  position: fixed;
  left: 0;
  top: 0;
}

.sidebar-brand {
  padding: var(--spacing-6);
  border-bottom: 1px solid var(--border-color);
}

.brand-title {
  font-size: var(--font-size-xl);
  font-weight: var(--font-weight-bold);
  color: var(--color-primary-500);
}

.brand-subtitle {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
  margin-top: var(--spacing-1);
}

.sidebar-nav {
  flex: 1;
  padding: var(--spacing-4);
  overflow-y: auto;
}

.nav-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: var(--spacing-1);
}

.nav-item {
  width: 100%;
  display: flex;
  align-items: center;
  gap: var(--spacing-3);
  padding: var(--spacing-3) var(--spacing-4);
  border-radius: var(--radius-base);
  border: none;
  background: transparent;
  color: var(--text-primary);
  font-size: var(--font-size-base);
  font-weight: var(--font-weight-medium);
  cursor: pointer;
  transition: all var(--transition-base);
  text-align: left;
}

.nav-item:hover {
  background: var(--bg-secondary);
}

.nav-item-active {
  background: var(--color-primary-50);
  color: var(--color-primary-600);
  font-weight: var(--font-weight-semibold);
}

.nav-icon {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
}

.sidebar-footer {
  padding: var(--spacing-4);
  border-top: 1px solid var(--border-color);
}

.user-profile {
  display: flex;
  align-items: center;
  gap: var(--spacing-3);
}

.user-avatar {
  width: 40px;
  height: 40px;
  border-radius: var(--radius-full);
  background: var(--color-primary-100);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.avatar-text {
  color: var(--color-primary-600);
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-semibold);
}

.user-info {
  flex: 1;
  min-width: 0;
}

.user-name {
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  color: var(--text-primary);
}

.user-email {
  font-size: var(--font-size-xs);
  color: var(--text-secondary);
  margin-top: 2px;
}
</style>
