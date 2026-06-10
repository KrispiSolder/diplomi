<template>
  <div
    v-if="promo"
    class="rounded-xl border border-[#A5D6A7] bg-[#F1F8E9] px-4 py-3"
    role="status"
  >
    <p class="font-['Montserrat'] text-[15px] font-bold text-[#1B5E20]">
      Промокод <span class="tracking-wide">{{ promo.code }}</span>
    </p>
    <p v-if="promo.description" class="font-['Montserrat'] text-[13px] text-[#2E7D32] mt-1">
      {{ promo.description }}
    </p>
    <p v-if="promo.hint" class="font-['Montserrat'] text-[12px] text-[#555555] mt-2 leading-snug">
      {{ promo.hint }}
    </p>
  </div>
</template>

<script setup>
defineProps({
  promo: {
    type: Object,
    default: null,
  },
})
</script>
