<template>
  <div :class="wrapperClass">
    <p class="font-['Montserrat'] text-[#888888] text-[14px]">
      {{ breadcrumbs.join(' / ') }}
    </p>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  breadcrumbs: {
    type: Array,
    required: true,
  },
  /** Узкая шапка: без большого отступа сверху (каталог и т.п.) */
  compact: {
    type: Boolean,
    default: false,
  },
  /** Админка: меньше воздуха от хедера до крошек (вместо pt 120px) */
  admin: {
    type: Boolean,
    default: false,
  },
})

const wrapperClass = computed(() => {
  if (props.admin) {
    return 'pt-[56px] pb-[14px] px-0'
  }
  if (props.compact) {
    return 'pt-0 pb-0 px-0'
  }
  return 'pt-4 pb-3 px-0 md:pt-[120px] md:pb-[30px] md:px-[205px]'
})
</script>
