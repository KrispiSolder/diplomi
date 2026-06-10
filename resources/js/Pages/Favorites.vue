<template>
  <AppLayout>
    <Breadcrumb :breadcrumbs="['Главная страница', 'Избранное']" />

    <div class="page-container py-6 md:py-[30px]">
      <h1 class="font-['Montserrat'] text-[24px] font-bold text-black mb-[24px]">Избранное</h1>

      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4 sm:gap-6 md:gap-8 justify-items-center">
        <ProductCard v-for="fav in favorites" :key="fav.id" :product="fav.product" :is-favorite="true" compact />
      </div>

      <div v-if="favorites.length === 0" class="text-center py-[60px] text-[#888888] font-['Montserrat'] text-[16px]">
        У вас нет избранных товаров
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import Breadcrumb from '@/Components/Breadcrumb.vue'
import ProductCard from '@/Components/ProductCard.vue'

defineProps({
  favorites: Array,
})
</script>
