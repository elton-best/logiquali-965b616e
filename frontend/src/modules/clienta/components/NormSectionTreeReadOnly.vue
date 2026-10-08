<template>
  <div class="norm-section-tree">
    <div
      v-for="section in sections"
      :key="section.id"
      class="section-node"
      :style="{ marginLeft: `${levelValue * 24}px` }"
    >
      <div
        class="section-row"
        :class="{ 'is-expanded': expandedSections.has(section.id) }"
        @click="toggleSection(section.id)"
      >
        <v-btn
          v-if="section.children && section.children.length > 0"
          class="expand-btn"
          icon
          size="x-small"
          variant="text"
          @click.stop="toggleSection(section.id)"
        >
          <v-icon size="20">
            {{ expandedSections.has(section.id) ? 'mdi-chevron-down' : 'mdi-chevron-right' }}
          </v-icon>
        </v-btn>
        <div v-else style="width: 28px" />

        <v-icon class="mr-2" :color="getTypeColor(section.type)" size="20">
          {{ getTypeIcon(section.type) }}
        </v-icon>

        <div class="section-info flex-grow-1">
          <div class="d-flex align-center">
            <span class="section-number font-weight-bold mr-2">{{ section.number }}</span>
            <span class="section-title">{{ section.title || 'Sans titre' }}</span>
            <v-chip
              v-if="section.type === 'note'"
              class="ml-2"
              color="info"
              size="x-small"
              variant="outlined"
            >
              Note
            </v-chip>
            <v-chip
              v-if="section.type === 'annex'"
              class="ml-2"
              color="warning"
              size="x-small"
              variant="outlined"
            >
              Annexe
            </v-chip>
          </div>
          <div v-if="section.content" class="section-content mt-1">
            {{ section.content }}
          </div>
          <div v-if="section.references && section.references.length > 0" class="section-refs mt-1">
            <v-icon class="mr-1" size="14">mdi-link-variant</v-icon>
            <span class="text-caption">Réf: {{ section.references.join(', ') }}</span>
          </div>
        </div>
      </div>

      <div v-if="expandedSections.has(section.id) && section.children && section.children.length > 0">
        <NormSectionTreeReadOnly
          :expanded-sections="expandedSections"
          :level="levelValue + 1"
          :sections="section.children"
          @toggle-section="$emit('toggle-section', $event)"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { NormSection } from '@/types/api'
  import { computed } from 'vue'

  const props = withDefaults(defineProps<{
    sections: NormSection[]
    level?: number
    expandedSections: Set<number>
  }>(), {
    level: 0,
  })

  const levelValue = computed(() => props.level ?? 0)

  const emit = defineEmits<{
    'toggle-section': [id: number]
  }>()

  function toggleSection (id: number) {
    emit('toggle-section', id)
  }

  function getTypeIcon (type: string): string {
    const icons: Record<string, string> = {
      chapter: 'mdi-book-open-variant',
      subchapter: 'mdi-file-document-outline',
      paragraph: 'mdi-text',
      point: 'mdi-circle-small',
      note: 'mdi-note-text',
      annex: 'mdi-paperclip',
    }
    return icons[type] || 'mdi-file'
  }

  function getTypeColor (type: string): string {
    const colors: Record<string, string> = {
      chapter: 'primary',
      subchapter: 'secondary',
      paragraph: 'info',
      point: 'default',
      note: 'warning',
      annex: 'success',
    }
    return colors[type] || 'default'
  }
</script>

<style scoped>
.norm-section-tree {
  width: 100%;
}

.section-node {
  transition: all 0.2s ease;
}

.section-row {
  display: flex;
  align-items: flex-start;
  padding: 12px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  border: 1px solid transparent;
}

.section-row:hover {
  background-color: rgba(var(--v-theme-primary), 0.05);
  border-color: rgb(var(--v-theme-outline-variant));
}

.section-row.is-expanded {
  background-color: rgba(var(--v-theme-primary), 0.02);
}

.expand-btn {
  margin-right: 8px;
}

.section-info {
  min-width: 0;
}

.section-number {
  color: rgb(var(--v-theme-primary));
  font-size: 0.875rem;
  white-space: nowrap;
}

.section-title {
  font-size: 0.875rem;
  font-weight: 500;
}

.section-content {
  color: rgb(var(--v-theme-on-surface));
  line-height: 1.5;
  white-space: pre-wrap;
  word-break: break-word;
  font-size: 0.875rem;
}

.section-refs {
  display: flex;
  align-items: center;
  color: rgb(var(--v-theme-info));
  font-size: 0.75rem;
}
</style>
