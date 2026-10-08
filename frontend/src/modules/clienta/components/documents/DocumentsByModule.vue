<template>
  <div class="documents-by-module">

    <!-- Barre de recherche -->
    <v-text-field
      v-model="search"
      class="mb-4"
      clearable
      density="comfortable"
      hide-details
      placeholder="Filtrer par titre, code, module…"
      prepend-inner-icon="mdi-magnify"
      variant="outlined"
    />

    <div v-if="loading || catalogLoading" class="d-flex justify-center py-8">
      <v-progress-circular color="primary" indeterminate />
    </div>

    <v-alert
      v-else-if="tree.length === 0"
      density="comfortable"
      icon="mdi-folder-open-outline"
      type="info"
      variant="tonal"
    >
      Aucun document rattaché à un module. Créez un document et assignez-le à un module/sous-module.
    </v-alert>

    <template v-else>
      <!-- Arborescence Module → Sous-module → Section → Documents -->
      <v-expansion-panels v-model="openModules" multiple variant="accordion">
        <v-expansion-panel
          v-for="moduleNode in tree"
          :key="moduleNode.code"
          :value="moduleNode.code"
          rounded="lg"
        >
          <!-- En-tête module -->
          <v-expansion-panel-title class="module-header">
            <div class="d-flex align-center ga-3 w-100">
              <v-icon :color="moduleNode.color" size="20">{{ moduleNode.icon }}</v-icon>
              <div>
                <div class="text-subtitle-2 font-weight-bold">{{ moduleNode.name }}</div>
                <div class="text-caption text-medium-emphasis">{{ moduleNode.normName }}</div>
              </div>
              <v-chip class="ml-auto mr-2" :color="moduleNode.color" size="x-small" variant="tonal">
                {{ moduleNode.totalDocs }} doc{{ moduleNode.totalDocs > 1 ? 's' : '' }}
              </v-chip>
            </div>
          </v-expansion-panel-title>

          <v-expansion-panel-text class="pa-0">
            <!-- Sous-modules -->
            <div
              v-for="subNode in moduleNode.subModules"
              :key="subNode.code"
              class="submodule-block"
            >
              <!-- En-tête sous-module -->
              <div class="submodule-header d-flex align-center ga-2 px-5 py-2">
                <v-icon color="primary" size="16">{{ subNode.icon || 'mdi-folder-outline' }}</v-icon>
                <span class="text-body-2 font-weight-medium">{{ subNode.name }}</span>
                <v-chip class="ml-auto" color="grey" size="x-small" variant="tonal">
                  {{ subNode.totalDocs }}
                </v-chip>
              </div>

              <!-- Documents directement dans le sous-module (sans section) -->
              <v-list v-if="subNode.directDocs.length > 0" density="compact" lines="two">
                <DocumentListItem
                  v-for="doc in subNode.directDocs"
                  :key="doc.id"
                  :doc="doc"
                  @click="$emit('open-details', doc)"
                />
              </v-list>

              <!-- Sections -->
              <div
                v-for="sectionNode in subNode.sections"
                :key="sectionNode.code"
                class="section-block"
              >
                <div class="section-header d-flex align-center ga-2 px-6 py-1">
                  <v-icon color="grey" size="14">mdi-chevron-right</v-icon>
                  <span class="text-caption font-weight-medium text-medium-emphasis">
                    {{ sectionNode.name }}
                  </span>
                  <v-chip class="ml-auto" color="grey" size="x-small" variant="text">
                    {{ sectionNode.docs.length }}
                  </v-chip>
                </div>
                <v-list density="compact" lines="two">
                  <DocumentListItem
                    v-for="doc in sectionNode.docs"
                    :key="doc.id"
                    :doc="doc"
                    indent
                    @click="$emit('open-details', doc)"
                  />
                </v-list>
              </div>
            </div>

            <!-- Documents sans sous-module (rattachés directement au module) -->
            <div v-if="moduleNode.directDocs.length > 0">
              <div class="submodule-header d-flex align-center ga-2 px-5 py-2">
                <v-icon color="grey" size="16">mdi-file-multiple-outline</v-icon>
                <span class="text-body-2 text-medium-emphasis">Sans sous-module</span>
              </div>
              <v-list density="compact" lines="two">
                <DocumentListItem
                  v-for="doc in moduleNode.directDocs"
                  :key="doc.id"
                  :doc="doc"
                  @click="$emit('open-details', doc)"
                />
              </v-list>
            </div>
          </v-expansion-panel-text>
        </v-expansion-panel>
      </v-expansion-panels>

      <!-- Documents non classés -->
      <v-card v-if="unclassifiedDocs.length > 0" class="mt-4" elevation="0" rounded="lg">
        <v-card-title class="d-flex align-center ga-2 text-subtitle-2 pa-4 pb-2">
          <v-icon color="grey" size="18">mdi-help-circle-outline</v-icon>
          Non classés
          <v-chip class="ml-auto" color="grey" size="x-small" variant="tonal">
            {{ unclassifiedDocs.length }}
          </v-chip>
        </v-card-title>
        <v-list density="compact" lines="two">
          <DocumentListItem
            v-for="doc in unclassifiedDocs"
            :key="doc.id"
            :doc="doc"
            @click="$emit('open-details', doc)"
          />
        </v-list>
      </v-card>
    </template>
  </div>
</template>

<script setup lang="ts">
import type { UnifiedDocument } from '../../types/document-unified.types'
import { computed, defineComponent, h, onMounted, ref, watch } from 'vue'
import { DocumentHelpers } from '../../types/document-unified.types'
import { useAccessCatalog } from '../../composables/useAccessCatalog'
import StateBadge from './StateBadge.vue'
import StatusBadge from './StatusBadge.vue'
import TypeBadge from './TypeBadge.vue'

// ── Props / Emits ─────────────────────────────────────────────
const props = defineProps<{
  documents: UnifiedDocument[]
  loading?: boolean
}>()

defineEmits<{
  'open-details': [doc: UnifiedDocument]
}>()

// ── Catalogue sidebar ─────────────────────────────────────────
const { catalog, loading: catalogLoading, fetchCatalog } = useAccessCatalog()

onMounted(() => fetchCatalog())

// ── Recherche ─────────────────────────────────────────────────
const search = ref('')
const openModules = ref<string[]>([])

const filteredDocs = computed(() => {
  if (!search.value.trim()) return props.documents
  const q = search.value.toLowerCase()
  return props.documents.filter(doc => {
    const title = DocumentHelpers.getTitle(doc).toLowerCase()
    const code = (doc.code ?? '').toLowerCase()
    return title.includes(q) || code.includes(q)
  })
})

// ── Couleurs / icônes par module ──────────────────────────────
const MODULE_META: Record<string, { color: string, icon: string }> = {
  context:        { color: 'teal',    icon: 'mdi-earth' },
  leadership:     { color: 'indigo',  icon: 'mdi-account-tie' },
  planning:       { color: 'blue',    icon: 'mdi-calendar-check' },
  support:        { color: 'cyan',    icon: 'mdi-cog-outline' },
  operations:     { color: 'orange',  icon: 'mdi-factory' },
  evaluation:     { color: 'purple',  icon: 'mdi-chart-bar' },
  amelioration:   { color: 'green',   icon: 'mdi-trending-up' },
  default:        { color: 'grey',    icon: 'mdi-folder-outline' },
}

function moduleMeta (code: string) {
  return MODULE_META[code] ?? MODULE_META.default
}

// ── Extraction du source_context d'un document ────────────────
function getSourceContext (doc: UnifiedDocument): { module: string, submodule: string, section: string } {
  const ctx = (doc.metadata as any)?.source_context ?? {}
  return {
    module:    String(ctx.module    ?? '').trim(),
    submodule: String(ctx.submodule ?? '').trim(),
    section:   String(ctx.section   ?? '').trim(),
  }
}

// ── Construction de l'arbre ───────────────────────────────────
interface SectionNode {
  code: string
  name: string
  docs: UnifiedDocument[]
}

interface SubModuleNode {
  code: string
  name: string
  icon: string
  directDocs: UnifiedDocument[]
  sections: SectionNode[]
  totalDocs: number
}

interface ModuleNode {
  code: string
  name: string
  normName: string
  color: string
  icon: string
  subModules: SubModuleNode[]
  directDocs: UnifiedDocument[]
  totalDocs: number
}

const tree = computed<ModuleNode[]>(() => {
  const modules  = catalog.value.modules
  const subMods  = catalog.value.sub_modules
  const sections = catalog.value.sections

  // Indexer les docs par module/submodule/section
  const byModule    = new Map<string, UnifiedDocument[]>()
  const bySubModule = new Map<string, UnifiedDocument[]>()
  const bySection   = new Map<string, UnifiedDocument[]>()

  for (const doc of filteredDocs.value) {
    const ctx = getSourceContext(doc)
    if (!ctx.module) continue

    // Clé module
    if (!byModule.has(ctx.module)) byModule.set(ctx.module, [])

    if (!ctx.submodule) {
      // Rattaché directement au module
      byModule.get(ctx.module)!.push(doc)
      continue
    }

    const subKey = `${ctx.module}::${ctx.submodule}`
    if (!bySubModule.has(subKey)) bySubModule.set(subKey, [])

    if (!ctx.section) {
      bySubModule.get(subKey)!.push(doc)
      continue
    }

    const secKey = `${ctx.module}::${ctx.submodule}::${ctx.section}`
    if (!bySection.has(secKey)) bySection.set(secKey, [])
    bySection.get(secKey)!.push(doc)
  }

  // Construire l'arbre en suivant l'ordre du catalogue
  const result: ModuleNode[] = []

  for (const mod of modules.filter(m => m.is_active).sort((a, b) => a.order - b.order)) {
    const meta = moduleMeta(mod.code)

    // Sous-modules de ce module
    const subModuleNodes: SubModuleNode[] = []
    for (const sm of subMods.filter(s => s.module_code === mod.code && s.is_active).sort((a, b) => a.order - b.order)) {
      const subKey = `${mod.code}::${sm.code}`
      const directDocs = bySubModule.get(subKey) ?? []

      // Sections de ce sous-module
      const sectionNodes: SectionNode[] = []
      for (const sec of sections.filter(s => s.sub_module_code === sm.code && s.is_active).sort((a, b) => a.order - b.order)) {
        const secKey = `${mod.code}::${sm.code}::${sec.code}`
        const secDocs = bySection.get(secKey) ?? []
        if (secDocs.length > 0) {
          sectionNodes.push({ code: sec.code, name: sec.name, docs: secDocs })
        }
      }

      const totalDocs = directDocs.length + sectionNodes.reduce((s, n) => s + n.docs.length, 0)
      if (totalDocs > 0) {
        subModuleNodes.push({
          code: sm.code,
          name: sm.name,
          icon: sm.icon,
          directDocs,
          sections: sectionNodes,
          totalDocs,
        })
      }
    }

    const directDocs = byModule.get(mod.code) ?? []
    const totalDocs  = directDocs.length + subModuleNodes.reduce((s, n) => s + n.totalDocs, 0)

    if (totalDocs > 0) {
      result.push({
        code:      mod.code,
        name:      mod.name,
        normName:  '',
        color:     meta.color,
        icon:      mod.icon || meta.icon,
        subModules: subModuleNodes,
        directDocs,
        totalDocs,
      })
    }
  }

  return result
})

// Documents sans source_context
const unclassifiedDocs = computed(() =>
  filteredDocs.value.filter(doc => !getSourceContext(doc).module),
)

// Ouvrir tous les modules par défaut
watch(tree, (nodes) => {
  if (openModules.value.length === 0 && nodes.length > 0) {
    openModules.value = nodes.map(n => n.code)
  }
}, { immediate: true })

// ── Composant interne DocumentListItem ────────────────────────
const DocumentListItem = defineComponent({
  props: {
    doc: { type: Object as () => UnifiedDocument, required: true },
    indent: { type: Boolean, default: false },
  },
  emits: ['click'],
  setup (props, { emit }) {
    function formatDate (date: string) {
      return new Date(date).toLocaleDateString('fr-FR')
    }
    function isReviewSoon (doc: UnifiedDocument) {
      if (!doc.prochaine_revision) return false
      return new Date(doc.prochaine_revision).getTime() - Date.now() < 30 * 24 * 60 * 60 * 1000
    }
    return () => h('div', {
      class: ['doc-item', 'd-flex', 'align-center', 'ga-3', 'px-5', 'py-2', props.indent ? 'doc-item--indent' : ''],
      onClick: () => emit('click'),
    }, [
      h(StateBadge, { etat: props.doc.etat }),
      h('div', { class: 'flex-grow-1 min-width-0' }, [
        h('div', { class: 'text-body-2 font-weight-medium text-truncate' },
          DocumentHelpers.getTitle(props.doc)),
        h('div', { class: 'd-flex align-center flex-wrap ga-2 mt-1' }, [
          h('code', { class: 'code-pill' }, props.doc.code || '—'),
          h('span', { class: 'text-caption text-medium-emphasis' }, `v${props.doc.version}`),
          props.doc.prochaine_revision
            ? h('span', {
                class: ['text-caption', isReviewSoon(props.doc) ? 'text-warning' : 'text-medium-emphasis'],
              }, `· Révision ${formatDate(props.doc.prochaine_revision)}`)
            : null,
        ]),
      ]),
      h('div', { class: 'd-flex align-center ga-1 flex-shrink-0' }, [
        h(TypeBadge, { type: props.doc.type }),
        h(StatusBadge, { statut: props.doc.statut }),
      ]),
    ])
  },
})
</script>

<style scoped>
.module-header {
  background: linear-gradient(135deg, rgba(91, 141, 217, 0.05), rgba(16, 185, 129, 0.04));
  border-bottom: 1px solid #e2e8f0;
}

.submodule-block {
  border-bottom: 1px solid #f1f5f9;
}

.submodule-header {
  background: #f8fafc;
  border-bottom: 1px solid #f1f5f9;
}

.section-block {
  border-bottom: 1px solid #f8fafc;
}

.section-header {
  background: #fafafa;
  border-bottom: 1px solid #f1f5f9;
}

.doc-item {
  cursor: pointer;
  border-bottom: 1px solid #f8fafc;
  transition: background 0.15s;
  min-height: 52px;
}

.doc-item:hover { background: #f8fafc; }

.doc-item--indent { padding-left: 40px !important; }

.code-pill {
  background: #f1f5f9;
  padding: 1px 6px;
  border-radius: 999px;
  font-size: 0.72rem;
  color: #1e3a8a;
  font-weight: 600;
}
</style>
