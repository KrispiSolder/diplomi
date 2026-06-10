<template>
  <header class="fixed top-0 w-full z-50 bg-white shadow-sm">
    <div class="flex items-center justify-between gap-2 px-4 py-3 md:px-8 md:py-[12px] lg:px-[205px]">
      <div class="flex items-center gap-2 cursor-pointer min-w-0" @click="goHome">
        <div class="w-[28px] h-[28px] md:w-[30px] md:h-[30px] bg-[#2E7D32] rounded-full flex items-center justify-center shrink-0">
          <svg class="w-3.5 h-3.5 md:w-4 md:h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10 2C6 2 3 5 3 9c0 4 3 8 7 9v2h2v-2c4-1 7-5 7-9 0-4-3-7-7-7z" />
          </svg>
        </div>
        <span class="font-['Vetrino'] text-[26px] md:text-[36px] font-bold text-[#2E7D32] truncate">Plantform</span>
      </div>

      <nav class="hidden lg:flex gap-[70px]">
        <Link href="/" :class="{ 'border-b-2 border-[#2E7D32]': currentRoute === 'home' }" class="text-[#666666] font-['Montserrat'] text-[20px] py-[21px]">
          Главная
        </Link>
        <Link href="/catalog" :class="{ 'border-b-2 border-[#2E7D32]': currentRoute === 'catalog' }" class="text-[#666666] font-['Montserrat'] text-[20px] py-[21px]">
          Каталог
        </Link>
        <Link href="/#about" class="text-[#666666] font-['Montserrat'] text-[20px] py-[21px]">
          О нас
        </Link>
      </nav>

      <div class="flex items-center gap-3 md:gap-5 lg:gap-[36px] shrink-0">
        <div class="relative hidden sm:block">
          <button type="button" class="text-[#000000] hover:opacity-70" @click="toggleSearch">
            <svg class="w-6 h-6 md:w-[30px] md:h-[30px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </button>

          <form
            v-if="showSearch"
            @submit.prevent="submitSearch"
            class="absolute right-0 mt-2 bg-white shadow-lg rounded-full flex items-center px-3 py-1 gap-2 z-50"
          >
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Поиск"
              class="w-[160px] md:w-[220px] text-sm text-black placeholder:text-[#888888] focus:outline-none"
            />
            <button type="submit" class="text-[#2E7D32] hover:opacity-80">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </button>
          </form>
        </div>

        <Link href="/cart" class="text-[#000000] hover:opacity-70">
          <svg class="w-6 h-6 md:w-[30px] md:h-[30px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
        </Link>
        <Link href="/favorites" class="text-[#000000] hover:opacity-70">
          <svg class="w-6 h-6 md:w-[30px] md:h-[30px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
          </svg>
        </Link>
        <Link v-if="user && user.role === 'admin'" href="/admin/dashboard" class="text-[#000000] hover:opacity-70 hidden sm:block">
          <svg class="w-6 h-6 md:w-[30px] md:h-[30px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.298.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
        </Link>
        <Link href="/cabinet" class="text-[#000000] hover:opacity-70">
          <svg class="w-6 h-6 md:w-[30px] md:h-[30px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
        </Link>

        <button
          type="button"
          class="lg:hidden text-[#000000] p-1"
          aria-label="Меню"
          @click="mobileMenuOpen = !mobileMenuOpen"
        >
          <svg v-if="!mobileMenuOpen" class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
          <svg v-else class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>

    <div v-if="mobileMenuOpen" class="lg:hidden border-t border-gray-100 bg-white px-4 py-4 shadow-inner">
      <form class="sm:hidden mb-4 flex gap-2" @submit.prevent="submitSearch">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Поиск по товарам"
          class="flex-1 h-10 rounded-lg border border-gray-300 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#2E7D32]"
        />
        <button type="submit" class="bg-[#2E7D32] text-white px-4 rounded-lg text-sm">Найти</button>
      </form>

      <nav class="flex flex-col gap-1 font-['Montserrat'] text-[16px]">
        <Link href="/" class="py-3 px-2 text-[#333333]" @click="mobileMenuOpen = false">Главная</Link>
        <Link href="/catalog" class="py-3 px-2 text-[#333333]" @click="mobileMenuOpen = false">Каталог</Link>
        <Link href="/#about" class="py-3 px-2 text-[#333333]" @click="mobileMenuOpen = false">О нас</Link>
        <Link v-if="user && user.role === 'admin'" href="/admin/dashboard" class="py-3 px-2 text-[#333333] sm:hidden" @click="mobileMenuOpen = false">
          Админ-панель
        </Link>
      </nav>
    </div>
  </header>
</template>

<script setup>
import { Link, router, usePage } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

defineProps({
  currentRoute: String,
})

const page = usePage()
const user = computed(() => page.props.auth?.user || null)

const showSearch = ref(false)
const searchQuery = ref('')
const mobileMenuOpen = ref(false)

const goHome = () => {
  window.location.href = '/'
}

const toggleSearch = () => {
  showSearch.value = !showSearch.value
}

const submitSearch = () => {
  const query = searchQuery.value.trim()
  if (!query) return

  router.visit(`/catalog?search=${encodeURIComponent(query)}`)
  showSearch.value = false
  mobileMenuOpen.value = false
  searchQuery.value = ''
}
</script>
