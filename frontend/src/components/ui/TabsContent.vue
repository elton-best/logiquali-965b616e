<script setup lang="ts">
  import { TabsContent } from 'radix-vue'
  import { cn } from './utils'

  defineOptions({
    name: 'TabsContent',
    inheritAttrs: false,
  })

  const props = defineProps<{
    class?: string
    value: string
    forceMount?: boolean
  }>()
</script>

<template>
  <TabsContent
    v-bind="$attrs"
    :class="cn('flex-1 outline-none', props.class)"
    data-slot="tabs-content"
    :force-mount="forceMount"
    :value="value"
  >
    <slot />
  </TabsContent>
</template>
