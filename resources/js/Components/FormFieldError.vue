<template>
  <p v-if="message" class="font-['Montserrat'] text-[12px] text-red-600 mt-1 leading-snug" role="alert">
    {{ message }}
  </p>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  error: { type: [String, Array], default: null },
})

const message = computed(() => {
  if (!props.error) return ''
  return Array.isArray(props.error) ? props.error[0] : props.error
})
</script>
