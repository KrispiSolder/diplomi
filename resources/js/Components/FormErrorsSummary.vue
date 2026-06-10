<template>
  <div
    v-if="items.length"
    class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 space-y-2"
    role="alert"
  >
    <p class="font-['Montserrat'] text-[14px] font-semibold text-red-800">
      {{ title }}
    </p>
    <ul class="list-disc list-inside space-y-1 font-['Montserrat'] text-[13px] text-red-700">
      <li v-for="item in items" :key="item.field">
        <span class="font-semibold">{{ item.label }}:</span> {{ item.message }}
      </li>
    </ul>
  </div>
</template>

<script setup>
defineProps({
  items: { type: Array, default: () => [] },
  title: { type: String, default: 'Исправьте ошибки в форме:' },
})
</script>
