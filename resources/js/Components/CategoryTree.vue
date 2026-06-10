<template>
  <div class="space-y-[6px]">
    <button
      type="button"
      class="block w-full text-left font-['Montserrat'] text-[16px] py-[6px]"
      :class="activeSlug === 'all' ? 'text-[#2E7D32] font-bold' : 'text-[#666666] hover:text-[#2E7D32]'"
      @click="$emit('select', 'all')"
    >
      Все товары
    </button>

    <CategoryNode
      v-for="node in tree"
      :key="node.id"
      :node="node"
      :active-slug="activeSlug"
      @select="$emit('select', $event)"
    />
  </div>
</template>

<script setup>
import CategoryNode from '@/Components/CategoryNode.vue'

defineProps({
  tree: { type: Array, default: () => [] },
  activeSlug: { type: String, default: 'all' },
})

defineEmits(['select'])
</script>
