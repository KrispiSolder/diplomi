<template>
  <Transition
    enter-active-class="transition ease-out duration-300"
    enter-from-class="opacity-0 translate-y-2"
    enter-to-class="opacity-100 translate-y-0"
    leave-active-class="transition ease-in duration-200"
    leave-from-class="opacity-100 translate-y-0"
    leave-to-class="opacity-0 translate-y-2"
  >
    <div
      v-if="show"
      class="fixed top-24 right-4 z-50 text-white px-6 py-3 rounded-lg shadow-lg flex items-center gap-3 max-w-[min(420px,calc(100vw-2rem))]"
      :class="isError ? 'bg-red-500' : 'bg-green-500'"
    >
      <svg v-if="!isError" class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
      </svg>
      <svg v-else class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
      </svg>
      <span class="font-['Montserrat'] text-[14px] leading-snug">{{ message }}</span>
    </div>
  </Transition>
</template>

<script setup>
import { ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()
const show = ref(false)
const message = ref('')
const isError = ref(false)
let hideTimer = null

const display = (text, error = false) => {
  if (!text) return
  if (hideTimer) clearTimeout(hideTimer)
  message.value = text
  isError.value = error
  show.value = true
  hideTimer = setTimeout(() => {
    show.value = false
  }, 4000)
}

watch(() => page.props.flash?.success, (newVal) => {
  if (newVal) {
    display(newVal, false)
  }
}, { immediate: true })

const firstPageError = (errors) => {
  if (!errors || typeof errors !== 'object') return null
  const keys = ['product', 'cart', 'content', 'order', 'promo_code', 'code', 'description', 'category']
  for (const key of keys) {
    const val = errors[key]
    if (val) return Array.isArray(val) ? val[0] : val
  }
  const firstKey = Object.keys(errors)[0]
  if (firstKey) {
    const val = errors[firstKey]
    return Array.isArray(val) ? val[0] : val
  }
  return null
}

watch(() => firstPageError(page.props.errors), (newVal) => {
  if (newVal) {
    display(newVal, true)
  }
}, { immediate: true })
</script>
