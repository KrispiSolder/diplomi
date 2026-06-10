<template>
  <div class="pl-[4px]">
    <div class="flex items-start gap-[8px] py-[6px]">
      <button
        v-if="node.children && node.children.length"
        type="button"
        class="mt-[2px] w-[22px] h-[22px] flex items-center justify-center text-[#2E7D32] border border-[#2E7D32] rounded text-[12px]"
        @click.stop="open = !open"
      >
        {{ open ? '−' : '+' }}
      </button>
      <span v-else class="w-[22px] inline-block" />

      <button
        type="button"
        class="text-left font-['Montserrat'] text-[16px] flex-1"
        :class="activeSlug === node.slug ? 'text-[#2E7D32] font-bold' : 'text-[#666666] hover:text-[#2E7D32]'"
        @click="$emit('select', node.slug)"
      >
        {{ node.name }}
        <span class="text-[#888888] text-[14px]">({{ node.product_count }})</span>
      </button>
    </div>

    <div v-if="open && node.children && node.children.length" class="ml-[18px] border-l border-dashed border-[#DDDDDD] pl-[12px]">
      <CategoryNode
        v-for="child in node.children"
        :key="child.id"
        :node="child"
        :active-slug="activeSlug"
        @select="$emit('select', $event)"
      />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

defineProps({
  node: { type: Object, required: true },
  activeSlug: { type: String, default: 'all' },
})

defineEmits(['select'])

const open = ref(false)
</script>
