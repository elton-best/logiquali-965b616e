<template>
  <div class="hero-card">
    <div class="hero-content">
      <div class="hero-left">
        <div :class="['hero-icon', iconColor]">
          <v-icon color="white" size="32">{{ icon }}</v-icon>
        </div>
        <div class="hero-info">
          <h1 class="hero-title">{{ title }}</h1>
          <p class="hero-subtitle">{{ subtitle }}</p>
          <div class="meta-badges">
            <span v-for="badge in badges" :key="badge.text" :class="['badge', `badge-${badge.variant}`]">
              <v-icon size="14">{{ badge.icon }}</v-icon>
              {{ badge.text }}
            </span>
          </div>
        </div>
      </div>
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup lang="ts">
  interface Badge {
    icon: string
    text: string
    variant: 'primary' | 'secondary'
  }

  withDefaults(defineProps<{
    icon: string
    iconColor?: string
    title: string
    subtitle: string
    badges: Badge[]
  }>(), {
    iconColor: 'primary',
  })
</script>

<style scoped>
.hero-card {
  background: white;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
  padding: 24px 32px;
  transition: all 0.2s;
}

.hero-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.hero-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
}

.hero-left {
  display: flex;
  align-items: center;
  gap: 20px;
  flex: 1;
}

.hero-icon {
  width: 64px;
  height: 64px;
  border-radius: 16px;
  display: grid;
  place-items: center;
  flex-shrink: 0;
}

.hero-icon.primary {
  background: linear-gradient(135deg, #5b8dd9 0%, #4a71b0 100%);
  box-shadow: 0 4px 12px rgba(91, 141, 217, 0.25);
}

.hero-icon.teal {
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  box-shadow: 0 4px 12px rgba(20, 184, 166, 0.25);
}

.hero-icon.pink {
  background: linear-gradient(135deg, #ec4899 0%, #be185d 100%);
  box-shadow: 0 4px 12px rgba(236, 72, 153, 0.25);
}

.hero-info {
  flex: 1;
}

.hero-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 6px;
  line-height: 1.2;
}

.hero-subtitle {
  font-size: 0.9375rem;
  color: #64748b;
  margin-bottom: 12px;
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
  padding: 4px 12px;
  border-radius: 8px;
  font-size: 0.75rem;
  font-weight: 600;
}

.badge-primary {
  background: rgba(91, 141, 217, 0.1);
  color: #4a71b0;
}

.badge-secondary {
  background: rgba(100, 116, 139, 0.1);
  color: #475569;
}

@media (max-width: 768px) {
  .hero-card { padding: 20px; }
  .hero-content { flex-direction: column; align-items: flex-start; }
  .hero-left { flex-direction: column; align-items: flex-start; }
  .hero-title { font-size: 1.5rem; }
}
</style>
