<template>
  <v-list-group
    v-if="!collapsed && moduleMenu"
    class="menu-group"
    :value="menuValueResolved"
  >
    <template #activator="{ props: activatorProps }">
      <v-list-item
        v-bind="activatorProps"
        class="mb-1 menu-item group-activator"
        color="primary"
        :prepend-icon="icon"
        rounded="lg"
      >
        <v-list-item-title class="font-weight-medium">{{ title }}</v-list-item-title>
      </v-list-item>
    </template>

    <template v-for="item in moduleMenu.children" :key="item.uid">
      <v-list-group v-if="item.children" class="submenu-group" :value="`submenu-${item.uid}`">
        <template #activator="{ props: subActivatorProps }">
          <v-list-item
            v-bind="subActivatorProps"
            class="submenu-item"
            color="primary"
            :prepend-icon="item.icon"
            rounded="lg"
          >
            <v-list-item-title>{{ item.title }}</v-list-item-title>
          </v-list-item>
        </template>

        <v-list-item
          v-for="section in item.children"
          :key="section.uid"
          class="section-item"
          color="primary"
          :prepend-icon="section.icon"
          rounded="lg"
          :to="section.to"
          :value="`section-${section.uid}`"
        >
          <v-list-item-title class="text-body-2">{{ section.title }}</v-list-item-title>
        </v-list-item>
      </v-list-group>

      <v-list-item
        v-else
        class="submenu-item"
        color="primary"
        :prepend-icon="item.icon"
        rounded="lg"
        :to="item.to"
        :value="`submodule-${item.uid}`"
      >
        <v-list-item-title>{{ item.title }}</v-list-item-title>
      </v-list-item>
    </template>
  </v-list-group>
</template>

<script setup lang="ts">
  import type { SubModule, SubModuleSection } from '@/modules/clienta/types/subscription.types'
  import { computed } from 'vue'
  import { useRouter } from 'vue-router'
  import { useNorms } from '@/composables/useNorms'
  import { useSectionAccess } from '@/modules/clienta/composables/useSectionAccess'
  import { useSubModuleAccess } from '@/modules/clienta/composables/useSubModuleAccess'
  import { hasReadAccess } from '@/modules/clienta/utils/accessPermissions'
  import { canonicalizeCompanyRoute } from '@/modules/clienta/utils/routeCanonicalizer'

  const { norms } = useNorms()
  const activeNormsCount = computed(() => norms.value.length)

  type MenuItem = {
    uid: string
    title: string
    icon: string
    code: string
    to?: string
    children?: MenuItem[]
  }

  const props = defineProps<{
    moduleCode: string
    title: string
    icon: string
    menuValue?: string
    collapsed?: boolean
  }>()

  const menuValueResolved = computed(() => props.menuValue || `module-${props.moduleCode}`)

  const { accessibleSubModules } = useSubModuleAccess()
  const { getSectionsBySubModule } = useSectionAccess()
  const router = useRouter()

  function normalizeRoute (route: string | undefined, fallback: string) {
    return canonicalizeCompanyRoute(route, fallback)
  }

  function makeUid (parts: Array<string | number | undefined>): string {
    return parts
      .map(part => String(part ?? '').trim().toLowerCase())
      .filter(Boolean)
      .join('::')
  }

  function resolveMenuTitle (rawTitle: string | undefined, code: string | undefined, route: string | undefined): string {
    const normalizedCode = String(code ?? '').trim().toLowerCase()
    const normalizedRoute = String(route ?? '').trim().toLowerCase()
    const normalizedTitle = String(rawTitle ?? '').trim().toLowerCase()

    const isSmPlansEntry = normalizedCode === 'plans_action'
      || normalizedRoute.includes('/iso/planning/action-plans')
      || normalizedTitle === 'plans d\'action'
      || normalizedTitle === 'plan d\'actions'

    if (isSmPlansEntry) {
      return 'Plans du SM'
    }

    if (normalizedCode === 'satisfaction_client' && normalizedRoute.includes('/performance/surveillance')) {
      return 'Évaluations PIP'
    }

    // REQ-5.1-01 : Renommer le menu « Politique Qualité » en « Politique QHSE » de manière dynamique
    if (normalizedCode === 'politique' || normalizedCode === 'politique_qualite' || normalizedTitle.includes('politique')) {
      return activeNormsCount.value > 1 ? 'Politique QHSE' : 'Politique Qualité'
    }

    return rawTitle || ''
  }

  function isProductServiceRequirementsSubmodule (subModule: SubModule): boolean {
    const normalizedCode = String(subModule.code || '').trim().toLowerCase()
    const normalizedRoute = String(subModule.route || '').trim().toLowerCase()
    return normalizedCode === 'exigences_produits_services'
      || normalizedCode === 'product_service_requirements'
      || normalizedRoute.includes('/operations/product-service-requirements')
  }

  function isKnownPath (path: string): boolean {
    if (!path) return false
    const normalizedPath = (path.split(/[?#]/)[0] || '/').replace(/\/+$/, '') || '/'

    return router.getRoutes().some(route => {
      const routePath = (route.path || '/').replace(/\/+$/, '') || '/'
      if (routePath === normalizedPath) {
        return true
      }

      if (!routePath.includes(':')) {
        return false
      }

      const pattern = routePath.replace(/:[^/]+/g, '[^/]+')
      const regex = new RegExp(`^${pattern}$`)
      return regex.test(normalizedPath)
    })
  }

  const moduleMenu = computed(() => {
    const moduleSubModules = accessibleSubModules.value
      .filter(sm => sm.module_code === props.moduleCode)
      .slice()
      // eslint-disable-next-line unicorn/no-array-sort -- toSorted is unavailable with current TS lib target
      .sort((a: SubModule, b: SubModule) => a.order - b.order)

    if (moduleSubModules.length === 0) return null

    const children = moduleSubModules.map((subModule: SubModule, subModuleIndex: number): MenuItem | null => {
      if (isProductServiceRequirementsSubmodule(subModule)) {
        const directTo = normalizeRoute('/company/operations/compliance-obligations', '/company/operations/compliance-obligations')
        const hasRead = hasReadAccess(subModule.permissions)
        const knownPath = isKnownPath(directTo)
        if (!hasRead || !knownPath) {
          return null
        }

        return {
          uid: makeUid([props.moduleCode, subModule.code, directTo, subModule.id, subModuleIndex, 'flat']),
          title: resolveMenuTitle(subModule.name, subModule.code, directTo),
          icon: subModule.icon || 'mdi-file-document',
          code: subModule.code,
          to: directTo,
        }
      }

      const sections = getSectionsBySubModule(subModule.code)

      if (sections.length > 0) {
        const sectionChildren = sections
          .map((section: SubModuleSection, sectionIndex: number): MenuItem | null => {
            const to = normalizeRoute(section.route, `/company/iso/${props.moduleCode}/${section.code}`)
            const hasRead = hasReadAccess(section.permissions)
            const knownPath = isKnownPath(to)
            if (!hasRead || !knownPath) {
              return null
            }
            return {
              uid: makeUid([props.moduleCode, subModule.code, section.code, to, section.id, sectionIndex]),
              title: resolveMenuTitle(section.name, section.code, to),
              icon: section.icon || 'mdi-circle-small',
              code: section.code,
              to,
            }
          })
          .filter((item: MenuItem | null): item is MenuItem => item !== null)
          .filter((item: MenuItem, index: number, arr: MenuItem[]) => arr.findIndex((i: MenuItem) => i.uid === item.uid) === index)

        if (sectionChildren.length === 0) return null
        return {
          uid: makeUid([props.moduleCode, subModule.code, subModule.id, subModuleIndex]),
          title: subModule.name,
          icon: subModule.icon || 'mdi-file-document',
          code: subModule.code,
          children: sectionChildren,
        }
      }

      const to = normalizeRoute(
        subModule.route,
        `/company/${props.moduleCode}/${subModule.code}`,
      )
      const hasRead = hasReadAccess(subModule.permissions)
      const knownPath = isKnownPath(to)
      if (!hasRead || !knownPath) {
        return null
      }

      return {
        uid: makeUid([props.moduleCode, subModule.code, to, subModule.id, subModuleIndex]),
        title: resolveMenuTitle(subModule.name, subModule.code, to),
        icon: subModule.icon || 'mdi-file-document',
        code: subModule.code,
        to,
      }
    })
      .filter((item: MenuItem | null): item is MenuItem => item !== null)
      .filter((item: MenuItem, index: number, arr: MenuItem[]) => arr.findIndex((i: MenuItem) => i.uid === item.uid) === index)

    if (children.length === 0) return null

    return {
      title: props.title,
      icon: props.icon,
      children,
    }
  })
</script>
