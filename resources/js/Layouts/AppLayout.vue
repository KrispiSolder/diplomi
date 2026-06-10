<template>
  <div class="min-h-screen flex flex-col bg-white overflow-x-hidden">
    <Header :current-route="currentRoute" />
    <main class="flex-grow pt-[64px] md:pt-[80px]">
      <slot />
    </main>
    <Footer />
  </div>
</template>

<script setup>
import Header from '@/Components/Header.vue'
import Footer from '@/Components/Footer.vue'
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()

// Derive a simple route key from the current Inertia URL for nav highlighting
const currentRoute = computed(() => {
  const path = page.url.split('?')[0] || '/'
  if (path === '/') return 'home'
  if (path.startsWith('/catalog')) return 'catalog'
  return ''
})
</script>
