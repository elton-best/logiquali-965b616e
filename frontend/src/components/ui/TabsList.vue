<script setup lang="ts">
  import { TabsList } from 'radix-vue'
  import { cn } from './utils'

  defineOptions({
    name: 'TabsList',
    inheritAttrs: false,
  })

  const props = defineProps<{
    class?: string
    loop?: boolean
  }>()
</script>

<template>
  <TabsList
    v-bind="$attrs"
    :class="cn(
      'bg-muted text-muted-foreground inline-flex h-9 w-fit items-center justify-center rounded-xl p-[3px] flex',
      props.class
    )"
    data-slot="tabs-list"
    :loop="loop"
  >
    <slot />
  </TabsList>
</template>
